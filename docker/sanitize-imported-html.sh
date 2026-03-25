#!/usr/bin/env bash
# chat:sanitize-imported-html у контейнері php (той самий compose + --env-file, що deploy.sh / restart-stack.sh).
# Приклад: ./docker/sanitize-imported-html.sh --dry-run
# Production: ./docker/sanitize-imported-html.sh --force
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
  php artisan chat:sanitize-imported-html "$@"

echo "OK: chat:sanitize-imported-html ($(date -u +%Y-%m-%dT%H:%M:%SZ))"
