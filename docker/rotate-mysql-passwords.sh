#!/usr/bin/env bash
# Ротація паролів MySQL усередині вже ініціалізованого тома (prod).
# Нові паролі не повинні містити одинарних лапок ' (обмеження цього скрипта).
#
# Варіант A — мінімум ручної роботи (стек уже up, паролі як у docker/production.env):
#   ./docker/rotate-mysql-passwords.sh
#   Поточний root береться з змінної MYSQL_ROOT_PASSWORD у контейнері; нові паролі генеруються
#   і виводяться в кінці — скопіюйте в docker/production.env.
#
# Варіант B — явно:
#   export OLD_MYSQL_ROOT_PASSWORD='...'
#   export NEW_MYSQL_ROOT_PASSWORD='...'
#   export NEW_DB_PASSWORD='...'   # або NEW_MYSQL_PASSWORD (те саме)
#   ./docker/rotate-mysql-passwords.sh
#
# 1) Бекап: ./docker/backup-mysql.sh
# 2) Оновіть docker/production.env: MYSQL_ROOT_PASSWORD, DB_PASSWORD (один рядок для app user)
#    docker compose --env-file docker/production.env -f docker/compose.yaml --profile app up -d
#
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Потрібен запущений контейнер mysql і той самий --env-file, що в деплої, АБО задайте змінні:

  export OLD_MYSQL_ROOT_PASSWORD='поточний root (як у БД)'
  export NEW_MYSQL_ROOT_PASSWORD='...'
  export NEW_DB_PASSWORD='...'
  ./docker/rotate-mysql-passwords.sh

Без змінних: поточний root з контейнера; нові паролі згенеруються — скопіюйте вивід у production.env.
EOF
}

genpw() {
  openssl rand -base64 32 | tr -dc 'A-Za-z0-9' | head -c 40
}

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

cd "$REPO_DIR"

APP_DB_USER="${DB_USERNAME:-}"
if [[ -z "$APP_DB_USER" && -f docker/production.env ]]; then
  APP_DB_USER=$(grep -E '^DB_USERNAME=' docker/production.env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r\n' || true)
  APP_DB_USER="${APP_DB_USER//\"/}"
fi
APP_DB_USER="${APP_DB_USER:-${MYSQL_USER:-redpanda}}"

if [[ -z "${OLD_MYSQL_ROOT_PASSWORD:-}" ]]; then
  if ! OLD_MYSQL_ROOT_PASSWORD=$(docker compose "${COMPOSE_ENV[@]}" "${COMPOSE_FILES[@]}" exec -T mysql printenv MYSQL_ROOT_PASSWORD 2>/dev/null | tr -d '\r\n'); then
    usage
    exit 1
  fi
  if [[ -z "$OLD_MYSQL_ROOT_PASSWORD" ]]; then
    echo "rotate-mysql-passwords: не вдалося прочитати MYSQL_ROOT_PASSWORD з контейнера (mysql не запущений або немає --env-file?)." >&2
    usage
    exit 1
  fi
  echo "Використовую поточний MYSQL_ROOT_PASSWORD з контейнера mysql (довжина ${#OLD_MYSQL_ROOT_PASSWORD} символів)." >&2
fi

if [[ -z "${NEW_MYSQL_ROOT_PASSWORD:-}" ]]; then
  NEW_MYSQL_ROOT_PASSWORD=$(genpw)
  echo "Згенеровано NEW_MYSQL_ROOT_PASSWORD (збережіть у production.env)." >&2
fi
NEW_APP_PW="${NEW_DB_PASSWORD:-${NEW_MYSQL_PASSWORD:-}}"
if [[ -z "$NEW_APP_PW" ]]; then
  NEW_APP_PW=$(genpw)
  echo "Згенеровано новий пароль для користувача БД ${APP_DB_USER} (збережіть як DB_PASSWORD у production.env)." >&2
fi

if [[ "$NEW_MYSQL_ROOT_PASSWORD" == *"'"* || "$NEW_APP_PW" == *"'"* ]]; then
  echo "rotate-mysql-passwords: паролі не повинні містити одинарну лапку (')." >&2
  exit 1
fi

docker compose "${COMPOSE_ENV[@]}" "${COMPOSE_FILES[@]}" exec -T mysql \
  mysql -uroot -p"${OLD_MYSQL_ROOT_PASSWORD}" -e "
ALTER USER 'root'@'localhost' IDENTIFIED BY '${NEW_MYSQL_ROOT_PASSWORD}';
ALTER USER '${APP_DB_USER}'@'%' IDENTIFIED BY '${NEW_APP_PW}';
FLUSH PRIVILEGES;
"

echo "OK: паролі в БД оновлено."
echo ""
echo "Додайте в docker/production.env (або замініть рядки):"
echo "MYSQL_ROOT_PASSWORD=${NEW_MYSQL_ROOT_PASSWORD}"
echo "DB_PASSWORD=${NEW_APP_PW}"
echo ""
echo "Потім: docker compose --env-file docker/production.env -f docker/compose.yaml --profile app up -d"
