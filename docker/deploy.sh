#!/usr/bin/env bash
# T83 — послідовність на сервері (адаптуйте шляхи, гілку, compose).
# Один маніфест: docker/compose.yaml + опційно --env-file docker/production.env (prod). Окремий prod-override не потрібен.
# Секрети не в репозиторії: docker/production.env (або legacy compose.deploy.env).
# BACKUP_BEFORE_DEPLOY / DEPLOY_* — змінні середовища хоста (systemd, /etc/profile.d).
set -euo pipefail

REPO_DIR="${REPO_DIR:-/var/www/redpanda}"
BACKEND_DIR="${BACKEND_DIR:-$REPO_DIR/backend}"
BACKUP_DIR="${BACKUP_DIR:-$REPO_DIR/backups}"
DEPLOY_GIT_REF="${DEPLOY_GIT_REF:-main}"

if [[ -f "$REPO_DIR/docker/production.env" ]]; then
  COMPOSE_ENV=(--env-file "$REPO_DIR/docker/production.env")
elif [[ -f "$REPO_DIR/docker/compose.deploy.env" ]]; then
  COMPOSE_ENV=(--env-file "$REPO_DIR/docker/compose.deploy.env")
else
  COMPOSE_ENV=()
fi

# --- 1) Опційний бекап MySQL перед міграціями: BACKUP_BEFORE_DEPLOY=1 (лише docker/backup-mysql.sh, без mysqldump на хості) ---
if [[ "${BACKUP_BEFORE_DEPLOY:-}" == "1" ]]; then
  mkdir -p "$BACKUP_DIR"
  REPO_DIR="$REPO_DIR" BACKUP_DIR="$BACKUP_DIR" "$REPO_DIR/docker/backup-mysql.sh"
fi

cd "$REPO_DIR"
git fetch origin "$DEPLOY_GIT_REF"
git checkout "$DEPLOY_GIT_REF"
# Після минулого deploy локально видалені tracked-файли (cleanup) ламають pull — вирівнюємо до remote.
if git rev-parse --verify "origin/$DEPLOY_GIT_REF" >/dev/null 2>&1; then
  git reset --hard "origin/$DEPLOY_GIT_REF"
else
  git pull --ff-only origin "$DEPLOY_GIT_REF"
fi

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
# --env-file: docker/production.env (канон) або docker/compose.deploy.env (legacy).
# Опційно другий -f: docker/compose.override.yml (лише локальний проброс портів; на VPS зазвичай відсутній).
COMPOSE_FILES=(-f "$REPO_DIR/docker/compose.yaml")
[[ -f "$REPO_DIR/docker/compose.override.yml" ]] && COMPOSE_FILES+=(-f "$REPO_DIR/docker/compose.override.yml")
COMPOSE=(docker compose "${COMPOSE_ENV[@]}" "${COMPOSE_FILES[@]}")

# Файл env для кроку Vite (той самий, що й для compose).
VITE_ENV_SRC=""
if [[ -f "$REPO_DIR/docker/production.env" ]]; then
  VITE_ENV_SRC="$REPO_DIR/docker/production.env"
elif [[ -f "$REPO_DIR/docker/compose.deploy.env" ]]; then
  VITE_ENV_SRC="$REPO_DIR/docker/compose.deploy.env"
fi

"${COMPOSE[@]}" up -d mysql redis
"${COMPOSE[@]}" --profile app build php
"${COMPOSE[@]}" --profile app run --rm \
  -e COMPOSER_ALLOW_SUPERUSER=1 \
  php sh -lc \
  'rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/services.php 2>/dev/null || true; \
   composer install --no-dev --optimize-autoloader --no-interaction && \
   echo "[deploy] php artisan migrate --force" && php artisan migrate --force && \
   php artisan optimize && (php artisan queue:restart || true)'

# Vite (mode production) зчитує .env.production у backend/. Копія з канонічного env гарантує
# REVERB_APP_KEY / APP_URL у бандлі навіть якщо backend/.env — зламаний symlink або volume docker/ не змонтувався.
# Додатково монтуємо docker/ — тоді працює symlink backend/.env → ../docker/production.env.
if [[ -n "$VITE_ENV_SRC" ]]; then
  cp -f "$VITE_ENV_SRC" "$BACKEND_DIR/.env.production"
  chmod 600 "$BACKEND_DIR/.env.production"
fi
docker run --rm \
  -v "$BACKEND_DIR:/var/www/html" \
  -v "$REPO_DIR/docker:/var/www/docker:ro" \
  -w /var/www/html \
  node:22-bookworm \
  sh -lc 'npm ci && npm run build' \
  || { rm -f "$BACKEND_DIR/.env.production"; exit 1; }
rm -f "$BACKEND_DIR/.env.production"

"${COMPOSE[@]}" --profile app up -d --build
"${COMPOSE[@]}" --profile app restart php nginx queue reverb

# --- 2) Перевірка готовності (задайте DEPLOY_HEALTH_URL, напр. https://board.te.ua/health/ready) ---
if [[ -n "${DEPLOY_HEALTH_URL:-}" ]]; then
  curl -fsS "$DEPLOY_HEALTH_URL"
  echo
fi

echo "OK: deploy finished ($(date -u +%Y-%m-%dT%H:%M:%SZ))"
