#!/usr/bin/env bash
# Перезапуск усіх сервісів redpanda з docker/compose.yaml (+ compose.override.yml, якщо є).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="${REPO_DIR:-$(cd "$SCRIPT_DIR/.." && pwd)}"
COMPOSE_FILE="$REPO_DIR/docker/compose.yaml"

COMPOSE_ENV=()
if [[ -f "$REPO_DIR/docker/compose.deploy.env" ]]; then
  COMPOSE_ENV=(--env-file "$REPO_DIR/docker/compose.deploy.env")
fi

cd "$REPO_DIR"

docker compose "${COMPOSE_ENV[@]}" -f "$COMPOSE_FILE" --profile app restart \
  mysql redis php nginx queue reverb

echo "OK: restarted mysql redis php nginx queue reverb"
