#!/bin/sh
#
# Entrypoint du worker de taches recurrentes (tache 134, jalon F.0).
#
# Ce script existe pour une raison precise : `frankenphp/docker-entrypoint.sh`
# declenche son bloc d'installation pour tout `$1` valant `frankenphp`, `php`
# ou `bin/console` — migrations Doctrine, `cache:clear`, `cache:warmup`,
# `tailwind:build`, `asset-map:compile`. Un worker lance par
# `php bin/console messenger:consume ...` rejouerait donc **les migrations en
# concurrence avec le conteneur web** et refabriquerait les assets a chaque
# redemarrage. C'est le piege n° 2 du mode d'emploi
# (`docs/LOAD_TESTING_BOTTLENECKS.md` § F.0).
#
# Le worker ne migre pas, ne compile pas d'asset, ne sert pas de HTTP. Il
# attend que le conteneur web ait fini, efface l'arriere de loyers, puis
# consomme le calendrier — et rien d'autre.
#
set -e

CONSOLE="php /app/bin/console"

log() {
	echo "[scheduler] $*"
}

# --- 1. La base repond ------------------------------------------------------
#
# `depends_on: database: service_healthy` couvre deja ce cas ; la boucle reste
# pour les demarrages hors Compose (docker run, redemarrage machine) ou l'ordre
# n'est garanti par personne.
log 'Attente de la base de donnees...'
ATTEMPTS=60
until [ "$ATTEMPTS" -eq 0 ] || $CONSOLE dbal:run-sql -q "SELECT 1" >/dev/null 2>&1; do
	ATTEMPTS=$((ATTEMPTS - 1))
	sleep 2
done

if [ "$ATTEMPTS" -eq 0 ]; then
	log 'ERREUR : la base est injoignable apres 120 s.'
	exit 1
fi
log 'Base joignable.'

# --- 2. Le schema est a jour ------------------------------------------------
#
# Les migrations appartiennent au conteneur web (son entrypoint les joue). On
# ne les rejoue pas : on attend qu'elles soient passees, sinon la premiere
# tache planifiee taperait sur un schema d'avant-deploiement.
log 'Attente de la fin des migrations (jouees par le conteneur web)...'
ATTEMPTS=90
until [ "$ATTEMPTS" -eq 0 ] || $CONSOLE doctrine:migrations:up-to-date -q >/dev/null 2>&1; do
	ATTEMPTS=$((ATTEMPTS - 1))
	sleep 2
done

if [ "$ATTEMPTS" -eq 0 ]; then
	# Volontairement non bloquant : un `up-to-date` qui echoue peut signaler un
	# schema en retard **ou** une table de migrations absente (base neuve montee
	# par `doctrine:schema:update`). Bloquer le planificateur pour cette
	# ambiguite couterait plus cher que de continuer en le disant.
	log "AVERTISSEMENT : le schema ne s'est pas declare a jour en 180 s. On continue."
else
	log 'Schema a jour.'
fi

# --- 3. L'arriere de loyers -------------------------------------------------
#
# Piege n° 1 du mode d'emploi. `PlayerHouse::extendRent()` et
# `ShopRentService::extend()` avancent l'echeance de sept jours **a partir de
# l'echeance precedente** et ne rattrapent qu'une periode par execution : des
# echeances vieilles de six mois se traduiraient par 26 prelevements
# quotidiens consecutifs.
#
# `--min-periods=2` est ce qui rend l'appel sans danger *a chaque demarrage* :
# en regime normal le planificateur preleve tous les jours, donc aucune echeance
# ne depasse un jour de retard et la commande ne trouve rien. Au-dela de deux
# periodes (14 jours), le retard ne peut venir que d'une interruption longue —
# exactement la dette que personne n'a contractee.
log "Effacement de l'arriere de loyers (--min-periods=2)..."
$CONSOLE app:economy:rent-backlog-reset --min-periods=2 --no-interaction || {
	log "AVERTISSEMENT : la remise a zero de l'arriere a echoue. On ne consomme pas le calendrier."
	exit 1
}

# --- 4. Le calendrier -------------------------------------------------------
#
# Pas de `--time-limit` : le redemarrage tomberait a heure fixe, et une tache
# quotidienne posee dans cette fenetre serait sautee **tous les jours**. Le
# calendrier est volontairement sans etat (`Schedule::stateful()` n'est pas
# appele) : un declenchement manque pendant un redemarrage est perdu, jamais
# rejoue. C'est le compromis voulu — un loyer saute vaut mieux qu'un loyer
# preleve deux fois.
#
# `--memory-limit` reste la soupape : un arret propre apres traitement, puis
# `restart: unless-stopped` releve le conteneur.
log 'Consommation du calendrier (transport scheduler_default).'
exec php /app/bin/console messenger:consume scheduler_default \
	--memory-limit=256M \
	--no-interaction \
	-v
