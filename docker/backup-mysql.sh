#!/usr/bin/env bash
# Бекап MySQL (mysqldump) у gzip. Пароль з контейнера (MYSQL_ROOT_PASSWORD).
# Той самий --env-file, що deploy.sh: docker/production.env (канон) або compose.deploy.env (legacy).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="${REPO_DIR:-$(cd "$SCRIPT_DIR/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-$REPO_DIR/backups}"
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

mkdir -p "$BACKUP_DIR"
TS="$(date -u +%Y%m%dT%H%M%SZ)"
OUT="$BACKUP_DIR/redpanda-$TS.sql.gz"

docker compose "${COMPOSE_ENV[@]}" "${COMPOSE_FILES[@]}" exec -T mysql \
  sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --quick --routines "${MYSQL_DATABASE:-redpanda}"' \
  | gzip -c > "$OUT"

test -s "$OUT"

echo "OK: $OUT ($(du -h "$OUT" | cut -f1))"
