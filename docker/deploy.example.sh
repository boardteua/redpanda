#!/usr/bin/env bash
# T83 — приклад послідовності на сервері (адаптуйте шляхі, гілку, compose-файл).
# Не зберігайте паролі в репозиторії; передавайте через env / secrets CI.
set -euo pipefail

REPO_DIR="${REPO_DIR:-/var/www/redpanda}"
BACKEND_DIR="${BACKEND_DIR:-$REPO_DIR/backend}"

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
# … reverb / php-fpm …

echo "OK: оновіть цей скрипт під реальний деплой і бекап (див. docs/chat-v2/T80-DEPLOY-CHECKLIST.md)."
