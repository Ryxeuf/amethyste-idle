#!/bin/bash
set -euo pipefail

# Deploiement amethyste-idle
# Usage: ./scripts/deploy.sh [--prod|--dev]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
COMPOSE_ARGS=(-f "$PROJECT_DIR/compose.yaml")
MODE="prod"

while [[ $# -gt 0 ]]; do
  case $1 in
    --prod) MODE="prod"; shift ;;
    --dev)  MODE="dev"; shift ;;
    *) echo "Usage: $0 [--prod|--dev]"; exit 1 ;;
  esac
done

if [[ "$MODE" == "prod" ]]; then
  COMPOSE_ARGS+=(-f "$PROJECT_DIR/compose.prod.yaml")
fi

cd "$PROJECT_DIR"
export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-amethyste-idle}"

echo "==> Deploiement (mode: $MODE)"
echo "==> Fichiers Compose: ${COMPOSE_ARGS[*]}"
echo ""

echo "==> 1/4 Construction et demarrage des services..."
docker compose "${COMPOSE_ARGS[@]}" up -d --build --wait

echo ""
echo "==> 2/4 Configuration des transports Messenger (table messenger_messages)..."
docker compose "${COMPOSE_ARGS[@]}" exec -T php php /app/bin/console messenger:setup-transports --no-interaction

echo ""
echo "==> 3/4 Redemarrage du worker de mouvement..."
docker compose "${COMPOSE_ARGS[@]}" restart watcher_async_move_consumer

echo ""
echo "==> 4/4 Etat des services"
docker compose "${COMPOSE_ARGS[@]}" ps

echo ""
echo "Deploiement termine."
echo "Pour suivre les logs du worker: docker compose ${COMPOSE_ARGS[*]} logs -f watcher_async_move_consumer"
echo "Pour les logs PHP: docker compose ${COMPOSE_ARGS[*]} logs -f php"
