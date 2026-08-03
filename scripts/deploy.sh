#!/bin/bash
set -euo pipefail

# Deploiement amethyste-idle
# Toutes les commandes applicables sont executees dans le conteneur php.
# Usage: ./scripts/deploy.sh [--prod|--dev] [--composer-update] [--no-pull]
#   --composer-update : met a jour composer.lock dans le conteneur avant le build (necessite une image existante).
#   --no-pull         : en prod, ne pas faire docker compose pull (image deja build localement ou deja presente).

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
COMPOSE_ARGS=(-f "$PROJECT_DIR/compose.yaml")
MODE="prod"
MAINTENANCE_ON=0
COMPOSER_UPDATE=0
NO_PULL=0

while [[ $# -gt 0 ]]; do
  case $1 in
    --prod) MODE="prod"; shift ;;
    --dev)  MODE="dev"; shift ;;
    --composer-update) COMPOSER_UPDATE=1; shift ;;
    --no-pull) NO_PULL=1; shift ;;
    *) echo "Usage: $0 [--prod|--dev] [--composer-update] [--no-pull]"; exit 1 ;;
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
  if [[ "$NO_PULL" -eq 1 ]]; then
    echo "==> 1/7 Demarrage des services (sans pull GHCR)..."
  else
    echo "==> 1/7 Pull de l'image de production et demarrage des services..."
    docker compose "${COMPOSE_ARGS[@]}" pull php
  fi
  docker compose "${COMPOSE_ARGS[@]}" up -d --wait
else
  echo "==> 1/7 Construction et demarrage des services..."
  docker compose "${COMPOSE_ARGS[@]}" up -d --build --wait
fi

echo ""
echo "==> 2/7 Activation de la page de maintenance (conteneur php)..."
run_php touch /app/var/maintenance.flag
MAINTENANCE_ON=1
echo "    (les visiteurs voient la page de maintenance)"

echo ""
echo "==> 3/7 Migrations de la base de donnees..."
run_php php /app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo ""
echo "==> 4/7 Compilation des assets (Tailwind + AssetMapper)..."
run_php rm -rf /app/public/assets/
run_php php /app/bin/console tailwind:build --no-interaction
run_php php /app/bin/console asset-map:compile
run_php chown -R "$(id -u):$(id -g)" /app/public/assets /app/public/build 2>/dev/null || true

echo ""
echo "==> 5/7 Vidage du cache (conteneur php)..."
run_php php /app/bin/console cache:clear --no-warmup
run_php php /app/bin/console cache:warmup

# Le worker a son propre volume `var/` : le `cache:clear` ci-dessus ne le touche
# pas. On le releve apres les migrations pour qu'il reparte sur le schema neuf,
# et surtout pour qu'un deploiement ne laisse jamais le planificateur eteint —
# c'est exactement l'etat dans lequel le jeu a vecu jusqu'au jalon F.0.
echo ""
echo "==> 6/7 Redemarrage du planificateur de taches (worker)..."
docker compose "${COMPOSE_ARGS[@]}" up -d --wait worker

echo ""
echo "    Verification : le calendrier est-il consomme ?"
SCHEDULER_OK=0
for _ in $(seq 1 30); do
  if docker compose "${COMPOSE_ARGS[@]}" exec -T worker \
      sh -c "tr '\0' '\n' < /proc/1/cmdline | grep -q messenger:consume" 2>/dev/null; then
    SCHEDULER_OK=1
    break
  fi
  sleep 2
done

if [[ "$SCHEDULER_OK" -eq 1 ]]; then
  echo "    OK — le worker consomme le transport scheduler_default."
else
  # Non bloquant : le site est deja debout et fonctionnel. Mais il faut le dire
  # fort, parce que l'echec est silencieux par nature (rien ne casse, le monde
  # se fige simplement).
  echo "    ATTENTION — le worker ne consomme pas le calendrier. Aucune tache"
  echo "    recurrente ne tournera (escrow, loyers, restock PNJ, saisons)."
  echo "    Diagnostic : docker compose ${COMPOSE_ARGS[*]} logs --tail=100 worker"
fi

echo ""
echo "==> 7/7 Etat des services"
docker compose "${COMPOSE_ARGS[@]}" ps

echo ""
echo "Deploiement termine (commandes executees dans les conteneurs)."
echo "Pour les logs PHP: docker compose ${COMPOSE_ARGS[*]} logs -f php"
echo "Pour executer une commande dans le conteneur php: docker compose ${COMPOSE_ARGS[*]} exec php <commande>"
