#!/bin/bash
set -euo pipefail

# Deploiement amethyste-idle
# Toutes les commandes applicables sont executees dans le conteneur php.
# Usage: ./scripts/deploy.sh [--prod|--dev] [--composer-update]
#   --composer-update : met a jour composer.lock dans le conteneur avant le build (necessite une image existante).

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
COMPOSE_ARGS=(-f "$PROJECT_DIR/compose.yaml")
MODE="prod"
MAINTENANCE_ON=0
COMPOSER_UPDATE=0

while [[ $# -gt 0 ]]; do
  case $1 in
    --prod) MODE="prod"; shift ;;
    --dev)  MODE="dev"; shift ;;
    --composer-update) COMPOSER_UPDATE=1; shift ;;
    *) echo "Usage: $0 [--prod|--dev] [--composer-update]"; exit 1 ;;
  esac
done

if [[ "$MODE" == "prod" ]]; then
  COMPOSE_ARGS+=(-f "$PROJECT_DIR/compose.prod.yaml")
fi

cd "$PROJECT_DIR"
export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-amethyste-idle}"

run_php() {
  docker compose "${COMPOSE_ARGS[@]}" exec -T php "$@"
}

run_php_mounted() {
  docker compose "${COMPOSE_ARGS[@]}" run --rm -v "$PROJECT_DIR:/app" -w /app php "$@"
}

cleanup_maintenance() {
  if [[ "$MAINTENANCE_ON" -eq 1 ]]; then
    echo ""
    echo "==> Desactivation de la page de maintenance..."
    run_php rm -f /app/var/maintenance.flag 2>/dev/null || true
    echo "==> Maintenance desactivee."
  fi
}
trap cleanup_maintenance EXIT INT TERM

echo "==> Deploiement (mode: $MODE)"
echo "==> Fichiers Compose: ${COMPOSE_ARGS[*]}"
echo ""

if [[ "$COMPOSER_UPDATE" -eq 1 ]]; then
  echo "==> 0/5 Mise a jour du composer.lock (conteneur php, volume monte)..."
  run_php_mounted composer update --no-scripts --no-install
  echo ""
fi

if [[ "$MODE" == "prod" ]]; then
  echo "==> 1/5 Pull de l'image de production et demarrage des services..."
  docker compose "${COMPOSE_ARGS[@]}" pull php
  docker compose "${COMPOSE_ARGS[@]}" up -d --wait
else
  echo "==> 1/5 Construction et demarrage des services..."
  docker compose "${COMPOSE_ARGS[@]}" up -d --build --wait
fi

echo ""
echo "==> 2/5 Activation de la page de maintenance (conteneur php)..."
run_php touch /app/var/maintenance.flag
MAINTENANCE_ON=1
echo "    (les visiteurs voient la page de maintenance)"

echo ""
echo "==> 3/5 Compilation des assets (Tailwind + AssetMapper)..."
run_php php /app/bin/console tailwind:build --no-interaction 2>/dev/null || true
run_php php /app/bin/console asset-map:compile 2>/dev/null || true

echo ""
echo "==> 4/5 Vidage du cache (conteneur php)..."
run_php php /app/bin/console cache:clear --no-warmup 2>/dev/null || true
run_php php /app/bin/console cache:warmup 2>/dev/null || true

echo ""
echo "==> 5/5 Etat des services"
docker compose "${COMPOSE_ARGS[@]}" ps

echo ""
echo "Deploiement termine (commandes executees dans les conteneurs)."
echo "Pour les logs PHP: docker compose ${COMPOSE_ARGS[*]} logs -f php"
echo "Pour executer une commande dans le conteneur php: docker compose ${COMPOSE_ARGS[*]} exec php <commande>"
