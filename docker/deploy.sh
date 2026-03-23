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

cd "$BACKEND_DIR"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart || true

cd "$REPO_DIR"
docker compose -f docker/compose.yaml --profile app up -d --build
docker compose -f docker/compose.yaml --profile app restart php nginx queue reverb

# --- 2) Перевірка готовності (задайте DEPLOY_HEALTH_URL, напр. https://new.board.te.ua/health/ready) ---
if [[ -n "${DEPLOY_HEALTH_URL:-}" ]]; then
  curl -fsS "$DEPLOY_HEALTH_URL"
  echo
fi

echo "OK: deploy finished ($(date -u +%Y-%m-%dT%H:%M:%SZ))"
