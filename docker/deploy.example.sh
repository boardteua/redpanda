#!/usr/bin/env bash
# T83 — приклад послідовності на сервері (адаптуйте шляхі, гілку, compose-файл).
# Не зберігайте паролі в репозиторії; передавайте через env / secrets CI.
set -euo pipefail

REPO_DIR="${REPO_DIR:-/var/www/redpanda}"
BACKEND_DIR="${BACKEND_DIR:-$REPO_DIR/backend}"
BACKUP_DIR="${BACKUP_DIR:-$REPO_DIR/backups}"

# --- 1) Бекап MySQL (обов’язково перед міграціями у prod; зірвіть деплой при помилці) ---
# mkdir -p "$BACKUP_DIR"
# TS="$(date -u +%Y%m%dT%H%M%SZ)"
# mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_DATABASE" \
#   | gzip -c > "$BACKUP_DIR/redpanda-$TS.sql.gz"
# test -s "$BACKUP_DIR/redpanda-$TS.sql.gz"

cd "$REPO_DIR"
# git fetch origin && git checkout main && git pull --ff-only

cd "$BACKEND_DIR"
# composer install --no-dev --optimize-autoloader --no-interaction
# npm ci && npm run build
# php artisan migrate --force
# php artisan optimize
#
# Перезапуск процесів (systemd / docker compose / supervisor) — за вашою топологією:
# php artisan queue:restart
# docker compose -f docker/compose.yaml --profile app up -d --build
# … reverb / php-fpm …

# --- 2) Перевірка готовності (після up / reload) ---
# curl -fsS "https://your-host/health/ready" | jq .

# Поки немає реальних кроків деплою — не даємо зелений SSH-job у CI «без змін на сервері».
echo "FAIL: розкоментуйте кроки в docker/deploy.example.sh або замініть шлях у workflow (див. docs/chat-v2/T83-QA.md)." >&2
exit 1
