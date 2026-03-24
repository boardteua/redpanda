#!/usr/bin/env bash
# T83 — послідовність на сервері (адаптуйте шляхи, гілку, compose).
# Секрети не в репозиторії: DB_* / BACKUP_* задавайте в env (systemd, /etc/profile.d, тощо).
set -euo pipefail

REPO_DIR="${REPO_DIR:-/var/www/redpanda}"
BACKEND_DIR="${BACKEND_DIR:-$REPO_DIR/backend}"
BACKUP_DIR="${BACKUP_DIR:-$REPO_DIR/backups}"
DEPLOY_GIT_REF="${DEPLOY_GIT_REF:-main}"

# --- 1) Опційний бекап MySQL перед міграціями (prod): BACKUP_BEFORE_DEPLOY=1 + DB_* у середовищі ---
if [[ "${BACKUP_BEFORE_DEPLOY:-}" == "1" ]]; then
  : "${DB_HOST:?set DB_HOST or unset BACKUP_BEFORE_DEPLOY}"
  : "${DB_USER:?}"
  : "${DB_PASSWORD:?}"
  : "${DB_DATABASE:?}"
  mkdir -p "$BACKUP_DIR"
  TS="$(date -u +%Y%m%dT%H%M%SZ)"
  mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_DATABASE" \
    | gzip -c > "$BACKUP_DIR/redpanda-$TS.sql.gz"
  test -s "$BACKUP_DIR/redpanda-$TS.sql.gz"
fi

cd "$REPO_DIR"
git fetch origin
git checkout "$DEPLOY_GIT_REF"
git pull --ff-only origin "$DEPLOY_GIT_REF"

# На проді не потрібні Cursor, внутрішні спеки та дока в репо (не чіпаємо backend/resources/markdown — там контент для Vite).
if [[ "${DEPLOY_SKIP_REPO_CLEANUP:-}" != "1" ]]; then
  rm -rf \
    "$REPO_DIR/.cursor" \
    "$REPO_DIR/docs" \
    "$REPO_DIR/project-tasks" \
    "$REPO_DIR/project-specs"
  rm -f \
    "$REPO_DIR/AGENTS.md" \
    "$REPO_DIR/README.md" \
    "$REPO_DIR/docker/README.md" \
    "$REPO_DIR/backend/README.md" || true
fi

# Збірка й artisan у контейнерах (PHP 8.3 + Composer 2.x), щоб не залежати від PHP/Composer на хості.
# Опційно: docker/compose.deploy.env + compose.override.yml (див. compose.override.prod.example.yml).
COMPOSE_ENV=()
if [[ -f "$REPO_DIR/docker/compose.deploy.env" ]]; then
  COMPOSE_ENV=(--env-file "$REPO_DIR/docker/compose.deploy.env")
fi
COMPOSE=(docker compose "${COMPOSE_ENV[@]}" -f "$REPO_DIR/docker/compose.yaml")

"${COMPOSE[@]}" up -d mysql redis
"${COMPOSE[@]}" --profile app build php
"${COMPOSE[@]}" --profile app run --rm \
  -e COMPOSER_ALLOW_SUPERUSER=1 \
  php sh -lc \
  'composer install --no-dev --optimize-autoloader --no-interaction && php artisan migrate --force && php artisan optimize && (php artisan queue:restart || true)'

docker run --rm \
  -v "$BACKEND_DIR:/var/www/html" \
  -w /var/www/html \
  node:22-bookworm \
  sh -lc 'npm ci && npm run build'

"${COMPOSE[@]}" --profile app up -d --build
"${COMPOSE[@]}" --profile app restart php nginx queue reverb

# --- 2) Перевірка готовності (задайте DEPLOY_HEALTH_URL, напр. https://new.board.te.ua/health/ready) ---
if [[ -n "${DEPLOY_HEALTH_URL:-}" ]]; then
  curl -fsS "$DEPLOY_HEALTH_URL"
  echo
fi

echo "OK: deploy finished ($(date -u +%Y-%m-%dT%H:%M:%SZ))"
