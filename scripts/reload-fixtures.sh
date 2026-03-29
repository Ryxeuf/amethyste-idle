#!/bin/bash
set -euo pipefail

# Reinitialise le schema et recharge les fixtures via exec dans les conteneurs.
# Ne stoppe pas les services — tout est execute dans les conteneurs deja actifs.
# Usage: ./scripts/reload-fixtures.sh [--prod|--dev]

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

# Lire les variables de base de données depuis .env
source <(grep -E "^POSTGRES_(DB|USER|PASSWORD)" .env | sed 's/^/export /')
DB_NAME="${POSTGRES_DB:-amethyste}"
DB_USER="${POSTGRES_USER:-amethyste}"

php_exec() {
  docker compose "${COMPOSE_ARGS[@]}" exec -T php "$@"
}

db_exec() {
  docker compose "${COMPOSE_ARGS[@]}" exec -T database "$@"
}

echo "==> [1/7] Suppression du schema..."
# Double nettoyage : Doctrine d'abord, puis DROP CASCADE sur le schema public en fallback
php_exec php /app/bin/console doctrine:schema:drop --full-database --force --no-interaction 2>/dev/null || true
db_exec psql -U "$DB_USER" -d "$DB_NAME" -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;" 2>/dev/null || true

echo ""
echo "==> [2/7] Recreation du schema..."
php_exec php /app/bin/console doctrine:schema:create --no-interaction 2>/dev/null || true
# Rattrapage : si le schema existait partiellement, on force la mise a jour
php_exec php /app/bin/console doctrine:schema:update --force --no-interaction --complete

echo ""
echo "==> [3/7] Synchronisation des migrations (marquer toutes comme executees)..."
php_exec php /app/bin/console doctrine:migrations:sync-metadata-storage --no-interaction
php_exec php /app/bin/console doctrine:migrations:version --add --all --no-interaction

echo ""
echo "==> [4/7] Correction des sequences PostgreSQL (DEFAULT nextval)..."
db_exec psql -U "$DB_USER" -d "$DB_NAME" << 'SQL'
DO $$
DECLARE
  r RECORD;
  seq_name TEXT;
BEGIN
  FOR r IN
    SELECT
      t.table_name,
      kcu.column_name
    FROM information_schema.tables t
    JOIN information_schema.table_constraints tc
      ON tc.table_name = t.table_name AND tc.constraint_type = 'PRIMARY KEY'
    JOIN information_schema.key_column_usage kcu
      ON kcu.constraint_name = tc.constraint_name AND kcu.table_name = t.table_name
    WHERE t.table_schema = 'public'
      AND t.table_type = 'BASE TABLE'
  LOOP
    -- Doctrine cree des sequences nommees {table}_{colonne}_seq
    seq_name := r.table_name || '_' || r.column_name || '_seq';
    -- Ne pas modifier les colonnes IDENTITY (ex: users.id)
    IF EXISTS (SELECT 1 FROM pg_sequences WHERE schemaname = 'public' AND sequencename = seq_name)
       AND NOT EXISTS (
         SELECT 1 FROM pg_attribute a
         JOIN pg_class c ON c.oid = a.attrelid
         JOIN pg_namespace n ON n.oid = c.relnamespace
         WHERE n.nspname = 'public' AND c.relname = r.table_name AND a.attname = r.column_name
           AND a.attidentity IN ('a', 'd')
       ) THEN
      EXECUTE format(
        'ALTER TABLE %I ALTER COLUMN %I SET DEFAULT nextval(%L)',
        r.table_name, r.column_name, seq_name
      );
      RAISE NOTICE 'Fixed: %.% -> %', r.table_name, r.column_name, seq_name;
    END IF;
  END LOOP;
END $$;
SQL

echo ""
echo "==> [5/7] Chargement des fixtures (conteneur temporaire avec deps dev)..."
# On utilise un conteneur temporaire avec le code monte en volume
# pour ne PAS polluer le conteneur prod avec les deps dev.
docker compose "${COMPOSE_ARGS[@]}" run --rm \
  -v "$PROJECT_DIR:/app" \
  -w /app \
  -e APP_ENV=dev \
  php bash -c "
    composer install --no-interaction --quiet 2>/dev/null
    php bin/console doctrine:fixtures:load --no-interaction
  "

echo ""
echo "==> [6/7] Restauration des deps dans le conteneur principal..."
if [[ "$MODE" == "prod" ]]; then
  php_exec composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>/dev/null || true
else
  php_exec composer install --no-interaction --quiet 2>/dev/null || true
fi
php_exec php /app/bin/console cache:clear --no-warmup 2>/dev/null || true

echo ""
echo ""
echo "==> [7/7] Verification du status des migrations..."
php_exec php /app/bin/console doctrine:migrations:status --no-interaction

echo ""
echo "Done. Schema reinitialise, migrations synchronisees, fixtures chargees, deps restaurees ($MODE)."
