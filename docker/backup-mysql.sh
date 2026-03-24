#!/usr/bin/env bash
# Бекап MySQL (mysqldump) у gzip. Пароль береться з середовища контейнера (MYSQL_ROOT_PASSWORD).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="${REPO_DIR:-$(cd "$SCRIPT_DIR/.." && pwd)}"
COMPOSE_FILE="$REPO_DIR/docker/compose.yaml"
BACKUP_DIR="${BACKUP_DIR:-$REPO_DIR/backups}"

COMPOSE_ENV=()
if [[ -f "$REPO_DIR/docker/compose.deploy.env" ]]; then
  COMPOSE_ENV=(--env-file "$REPO_DIR/docker/compose.deploy.env")
fi

cd "$REPO_DIR"

mkdir -p "$BACKUP_DIR"
TS="$(date -u +%Y%m%dT%H%M%SZ)"
OUT="$BACKUP_DIR/redpanda-$TS.sql.gz"

docker compose "${COMPOSE_ENV[@]}" -f "$COMPOSE_FILE" exec -T mysql \
  sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --quick --routines "${MYSQL_DATABASE:-redpanda}"' \
  | gzip -c > "$OUT"

test -s "$OUT"

echo "OK: $OUT ($(du -h "$OUT" | cut -f1))"
