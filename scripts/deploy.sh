#!/bin/bash
set -euo pipefail

# Deploiement amethyste-idle
# Toutes les commandes applicables sont executees dans le conteneur php.
# Usage: ./scripts/deploy.sh [--prod|--dev] [--composer-update] [--no-pull] [--scheduler-optional]
#   --composer-update     : met a jour composer.lock dans le conteneur avant le build (necessite une image existante).
#   --no-pull             : en prod, ne pas faire docker compose pull (image deja build localement ou deja presente).
#   --scheduler-optional  : un planificateur qui ne repart pas devient un avertissement au lieu d'un echec.
#
# ## L'ordre de ce script est une decision (jalon F.0b)
#
# Le site se deploie d'abord, le planificateur ensuite. Deux raisons, toutes
# deux payees en production le 2026-08-03 :
#
#   1. **Le worker attend les migrations que ce script joue a l'etape 3.** Le
#      mettre dans le `--wait` de l'etape 1 met le deploiement en attente d'un
#      service qui attend le deploiement.
#   2. **Un planificateur en panne bloquait la mise a jour du jeu.** `up -d
#      --wait` echoue des que le worker se declare malsain — avant la page de
#      maintenance, avant les migrations, avant les assets. Cinq releases
#      consecutives ont ete perdues comme ca, sans qu'aucun log du worker
#      n'apparaisse nulle part. Le site n'a pas besoin du planificateur pour
#      servir une page : son echec doit se **voir**, pas tout arreter.
#
# D'ou : `--wait` ne porte que sur `database` et `php` ; le worker est releve a
# l'etape 6, verifie, et ses logs sont affiches s'il ne consomme pas.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
COMPOSE_ARGS=(-f "$PROJECT_DIR/compose.yaml")
MODE="prod"
MAINTENANCE_ON=0
COMPOSER_UPDATE=0
NO_PULL=0
SCHEDULER_REQUIRED=1
SCHEDULER_OK=0

while [[ $# -gt 0 ]]; do
  case $1 in
    --prod) MODE="prod"; shift ;;
    --dev)  MODE="dev"; shift ;;
    --composer-update) COMPOSER_UPDATE=1; shift ;;
    --no-pull) NO_PULL=1; shift ;;
    --scheduler-optional) SCHEDULER_REQUIRED=0; shift ;;
    *) echo "Usage: $0 [--prod|--dev] [--composer-update] [--no-pull] [--scheduler-optional]"; exit 1 ;;
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

# Le `--wait` ne porte que sur le site (`database` + `php`). Le worker est
# volontairement absent : il attend les migrations que l'etape 3 va jouer, et sa
# sonde de sante ne passe au vert qu'une fois le calendrier consomme. L'y
# inclure, c'est attendre un service qui nous attend — et perdre tout le
# deploiement s'il ne demarre pas.
if [[ "$MODE" == "prod" ]]; then
  if [[ "$NO_PULL" -eq 1 ]]; then
    echo "==> 1/7 Demarrage du site (sans pull GHCR)..."
  else
    echo "==> 1/7 Pull de l'image de production et demarrage du site..."
    docker compose "${COMPOSE_ARGS[@]}" pull php
  fi
  docker compose "${COMPOSE_ARGS[@]}" up -d --wait database php
else
  echo "==> 1/7 Construction et demarrage du site..."
  docker compose "${COMPOSE_ARGS[@]}" up -d --build --wait database php
fi

echo ""
echo "==> 2/7 Activation de la page de maintenance (conteneur php)..."
run_php touch /app/var/maintenance.flag
MAINTENANCE_ON=1
echo "    (les visiteurs voient la page de maintenance)"

# Le planificateur de l'ancienne version tourne encore : sans cet arret, une
# tache posee a la minute pres s'executerait avec du code d'avant sur un schema
# en cours de migration. Il est relance a l'etape 6, sur l'image neuve.
# Sans conteneur (premier deploiement), la commande ne fait rien.
echo "    Arret du planificateur pendant la migration..."
docker compose "${COMPOSE_ARGS[@]}" stop worker >/dev/null 2>&1 || true

echo ""
echo "==> 3/7 Migrations de la base de donnees..."
run_php php /app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Contenu de monde declaratif : le YAML des zones est la source de verite, et
# rien ne le rejouait au deploiement — une zone, un filon ou un habitant ajoute
# en donnee n'atteignait la production que si quelqu'un pensait a lancer la
# commande a la main. L'import est idempotent et non destructif (upsert par
# slug, aucune suppression, l'etat runtime n'est pas touche).
echo "    Import du graphe de zones (declaratif, idempotent)..."
run_php php /app/bin/console app:zone:import --no-interaction

# Filet : les entites de monde persistees avant l'existence de leur zone (le
# backfill de Version20260724WorldEntitiesZone est un one-shot) restaient
# invisibles a jamais, l'ecran de zone listant strictement par zone.
echo "    Verification du rattachement des entites a leur zone..."
run_php php /app/bin/console app:zone:audit --fix --no-interaction

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
# Sans `--wait` : la sonde du worker signifie « le calendrier est consomme »,
# donc elle est fausse pendant toute la sequence de demarrage et un worker qui
# meurt en boucle se declare malsain en quelques secondes. On le releve, puis on
# verifie nous-memes — avec un budget de temps explicite et des logs a la cle.
docker compose "${COMPOSE_ARGS[@]}" up -d worker

echo ""
echo "    Verification : le calendrier est-il consomme ? (jusqu'a 180 s)"
# Le budget couvre le pire demarrage a froid : purge du cache de prod, cache DI
# reconstruit au premier `bin/console`, attente de la base, attente des
# migrations, effacement de l'arriere de loyers.
for _ in $(seq 1 90); do
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
  # L'echec du planificateur est silencieux par nature : rien ne casse, rien ne
  # leve, le monde se fige simplement (les encheres n'expirent plus, les
  # boutiques ne se reapprovisionnent plus, la semaine ne tourne plus). Il faut
  # donc que le deploiement le dise fort, et qu'il le dise **avec la raison** —
  # sinon on relit « container worker is unhealthy » sans jamais savoir pourquoi.
  echo ""
  echo "    ATTENTION — le worker ne consomme pas le calendrier. Aucune tache"
  echo "    recurrente ne tournera (escrow, loyers, restock PNJ, saisons)."
  echo ""
  echo "    ---------------- etat du conteneur worker ----------------"
  docker compose "${COMPOSE_ARGS[@]}" ps -a worker || true
  echo ""
  echo "    ---------------- derniers logs du worker -----------------"
  docker compose "${COMPOSE_ARGS[@]}" logs --no-color --tail=200 worker || true
  echo "    ----------------------------------------------------------"
fi

echo ""
echo "==> 7/7 Etat des services"
docker compose "${COMPOSE_ARGS[@]}" ps

echo ""
echo "Deploiement termine (commandes executees dans les conteneurs)."
echo "Pour les logs PHP: docker compose ${COMPOSE_ARGS[*]} logs -f php"
echo "Pour executer une commande dans le conteneur php: docker compose ${COMPOSE_ARGS[*]} exec php <commande>"

# Le site est deploye, servi, et sorti de maintenance : c'est le sens de cet
# echec tardif. Le planificateur mort ne prive personne de jouer aujourd'hui,
# mais il fige le monde a partir de demain — il ne peut donc ni faire echouer la
# mise a jour, ni passer inapercu. `--scheduler-optional` degrade en
# avertissement pour un deploiement ou l'on sait deja que le worker est arrete.
if [[ "$SCHEDULER_OK" -ne 1 && "$SCHEDULER_REQUIRED" -eq 1 ]]; then
  echo ""
  echo "ECHEC — le site est a jour et en ligne, mais le planificateur ne tourne pas."
  echo "        Les logs du worker sont ci-dessus. Relance apres correction :"
  echo "          docker compose ${COMPOSE_ARGS[*]} up -d --force-recreate worker"
  exit 1
fi
