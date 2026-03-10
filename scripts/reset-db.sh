#!/bin/bash
set -euo pipefail

# Recreer la base PostgreSQL 17 from scratch et charger les fixtures.
# Usage: ./scripts/reset-db.sh [--prod|--dev]

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
VOLUME_NAME="${COMPOSE_PROJECT_NAME}_database_data"

echo "==> Arret des services..."
docker compose "${COMPOSE_ARGS[@]}" down

echo ""
echo "==> Suppression du volume de donnees PostgreSQL ($VOLUME_NAME)..."
docker volume rm "$VOLUME_NAME" 2>/dev/null || true

echo ""
echo "==> Demarrage des services (PostgreSQL 17 frais)..."
docker compose "${COMPOSE_ARGS[@]}" up -d

echo ""
echo "==> Attente du demarrage de la base (healthcheck)..."
DB_NAME="${POSTGRES_DB:-amethyste}"
DB_USER="${POSTGRES_USER:-app}"
for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20; do
  if docker compose "${COMPOSE_ARGS[@]}" exec -T database pg_isready -d "$DB_NAME" -U "$DB_USER" 2>/dev/null; then
    break
  fi
  sleep 2
done
docker compose "${COMPOSE_ARGS[@]}" exec -T database pg_isready -d "$DB_NAME" -U "$DB_USER" || exit 1

echo ""
echo "==> Attente du service php (healthcheck)..."
for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30; do
  if docker compose "${COMPOSE_ARGS[@]}" exec -T php true 2>/dev/null; then
    break
  fi
  sleep 2
done

echo ""
echo "==> Creation du schema (conteneur php)..."
if [ -d "$PROJECT_DIR/migrations" ] && [ -n "$(find "$PROJECT_DIR/migrations" -maxdepth 1 -name 'Version*.php' 2>/dev/null)" ]; then
  docker compose "${COMPOSE_ARGS[@]}" exec -T php php /app/bin/console doctrine:migrations:migrate --no-interaction
else
  docker compose "${COMPOSE_ARGS[@]}" exec -T php php /app/bin/console doctrine:schema:drop --full-database --force 2>/dev/null || true
  docker compose "${COMPOSE_ARGS[@]}" exec -T php php /app/bin/console doctrine:schema:create
  echo ""
  echo "==> Correction des sequences PostgreSQL (DEFAULT nextval sur colonnes id)..."
  cat "$PROJECT_DIR/scripts/post-schema-fix-sequences.sql" | docker compose "${COMPOSE_ARGS[@]}" exec -T database psql -U "$DB_USER" -d "$DB_NAME" -f - >/dev/null
fi

echo ""
echo "==> Installation des dependances dev pour les fixtures (volume monte)..."
docker compose "${COMPOSE_ARGS[@]}" run --rm -v "$PROJECT_DIR:/app" -w /app -e APP_ENV=dev php composer install --no-interaction

echo ""
echo "==> Chargement des fixtures (conteneur php, APP_ENV=dev)..."
docker compose "${COMPOSE_ARGS[@]}" run --rm -v "$PROJECT_DIR:/app" -w /app -e APP_ENV=dev php php bin/console doctrine:fixtures:load --no-interaction

echo ""
echo "Base recreee (PostgreSQL 17) et fixtures importees."
