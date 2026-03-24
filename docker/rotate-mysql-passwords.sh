#!/usr/bin/env bash
# Ротація паролів MySQL усередині вже ініціалізованого тома (prod).
# Нові паролі не повинні містити одинарних лапок ' (обмеження цього скрипта).
#
# 1) Зробіть бекап: ./docker/backup-mysql.sh
# 2) Згенеруйте паролі (лише безпечні символи), напр.:
#      NEW_R=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9' | head -c 40)
#      NEW_A=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9' | head -c 40)
# 3) Експорт змінних і запуск:
#      export OLD_MYSQL_ROOT_PASSWORD='...поточний root з БД...'
#      export NEW_MYSQL_ROOT_PASSWORD="$NEW_R"
#      export NEW_MYSQL_PASSWORD="$NEW_A"
#      ./docker/rotate-mysql-passwords.sh
# 4) Оновіть docker/production.env: MYSQL_ROOT_PASSWORD, MYSQL_PASSWORD, DB_PASSWORD (як MYSQL_PASSWORD).
# 5) Перезапуск: docker compose --env-file docker/production.env -f docker/compose.yaml --profile app up -d
#
set -euo pipefail

: "${OLD_MYSQL_ROOT_PASSWORD:?}"
: "${NEW_MYSQL_ROOT_PASSWORD:?}"
: "${NEW_MYSQL_PASSWORD:?}"

if [[ "$NEW_MYSQL_ROOT_PASSWORD" == *"'"* || "$NEW_MYSQL_PASSWORD" == *"'"* ]]; then
  echo "Refuse: passwords must not contain single-quote (')" >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="${REPO_DIR:-$(cd "$SCRIPT_DIR/.." && pwd)}"
COMPOSE_FILES=(-f "$REPO_DIR/docker/compose.yaml")
[[ -f "$REPO_DIR/docker/compose.override.yml" ]] && COMPOSE_FILES+=(-f "$REPO_DIR/docker/compose.override.yml")

if [[ -f "$REPO_DIR/docker/production.env" ]]; then
  COMPOSE_ENV=(--env-file "$REPO_DIR/docker/production.env")
elif [[ -f "$REPO_DIR/docker/compose.deploy.env" ]]; then
  COMPOSE_ENV=(--env-file "$REPO_DIR/docker/compose.deploy.env")
else
  COMPOSE_ENV=()
fi

MYSQL_USER="${MYSQL_USER:-redpanda}"

cd "$REPO_DIR"

docker compose "${COMPOSE_ENV[@]}" "${COMPOSE_FILES[@]}" exec -T mysql \
  mysql -uroot -p"${OLD_MYSQL_ROOT_PASSWORD}" -e "
ALTER USER 'root'@'localhost' IDENTIFIED BY '${NEW_MYSQL_ROOT_PASSWORD}';
ALTER USER '${MYSQL_USER}'@'%' IDENTIFIED BY '${NEW_MYSQL_PASSWORD}';
FLUSH PRIVILEGES;
"

echo "OK: MySQL passwords updated in the data directory."
echo "Next: set MYSQL_ROOT_PASSWORD / MYSQL_PASSWORD / DB_PASSWORD in docker/production.env to the new values, then compose up -d (app profile)."
