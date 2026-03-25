#!/usr/bin/env bash
# chat:legacy-link-user-avatars у контейнері php (після chat:legacy-sync-avatars і заповненого LEGACY_AVATAR_RSYNC_DEST).
set -euo pipefail

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

docker compose "${COMPOSE_ENV[@]}" "${COMPOSE_FILES[@]}" --profile app exec -T php \
  php artisan chat:legacy-link-user-avatars "$@"

echo "OK: chat:legacy-link-user-avatars ($(date -u +%Y-%m-%dT%H:%M:%SZ))"
