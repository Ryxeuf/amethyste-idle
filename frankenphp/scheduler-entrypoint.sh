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
# Le worker ne migre pas, ne compile pas d'asset, ne sert pas de HTTP. Il purge
# son cache, attend que le conteneur web ait fini, efface l'arriere de loyers,
# puis consomme le calendrier — et rien d'autre.
#
# ## Rien n'echoue en silence (jalon F.0b)
#
# Un echec ici est invisible par construction : le conteneur meurt, `restart:
# unless-stopped` le releve, et la seule trace est dans `docker compose logs
# worker` — sur le serveur, ou personne ne va. Trois regles en decoulent :
#   1. toute attente qui expire **reaffiche la vraie erreur** (les boucles
#      avalent leur sortie, sinon le premier essai polluerait le log) ;
#   2. tout arret fatal passe par `fatal()`, qui **temporise avant de sortir** —
#      sinon une erreur permanente devient une boucle de redemarrage qui noie
#      ses propres logs et fait clignoter la sonde de sante ;
#   3. `scripts/deploy.sh` verifie le resultat et `.github/workflows/deploy.yml`
#      publie ces logs dans la sortie de la CI.
#
set -e

CONSOLE="php /app/bin/console"
APP_ENV="${APP_ENV:-prod}"

log() {
	echo "[scheduler] $*"
}

# Arret fatal, temporise.
#
# `restart: unless-stopped` releve le conteneur aussitot : sortir dans la
# seconde transforme une erreur permanente (schema absent, transport
# introuvable) en boucle a plusieurs tours par minute, ou chaque tour efface la
# lisibilite du precedent. Docker temporise de lui-meme, mais seulement apres
# plusieurs echecs ; cette pause rend le premier tour lisible — et c'est le
# premier tour qu'on lit.
fatal() {
	log "ERREUR : $*"
	log 'Le worker s arrete. Docker va le relever ; si la cause persiste, il bouclera.'
	sleep "${SCHEDULER_FAILURE_BACKOFF:-15}"
	exit 1
}

# --- 0. Le cache du worker n'est pas celui de l'image ------------------------
#
# `VOLUME /app/var/` (Dockerfile) donne au worker un volume anonyme **qui
# survit a la recreation du conteneur**. Une image neuve arrive donc sur le
# conteneur DI compile par l'image precedente : qu'un service soit renomme ou
# supprime entre deux versions, et le premier `bin/console` part en erreur
# fatale — irrattrapable, puisque `cache:clear` doit lui-meme booter le noyau
# pour s'executer. D'ou le `rm -rf` : c'est la seule facon de sortir d'un cache
# empoisonne, et la seule qui n'ait pas besoin que PHP demarre.
#
# L'entrypoint du web fait le meme constat pour la meme raison (« var/ est un
# volume Docker ») et vide son cache a chaque demarrage.
#
# Uniquement en prod : en dev, `compose.override.yaml` monte `.:/app` dans le
# web **et** dans le worker, ou le cache est donc partage — le purger reviendrait
# a vider celui du conteneur qui sert les requetes.
if [ "$APP_ENV" = 'prod' ] && [ -d "/app/var/cache/$APP_ENV" ]; then
	log "Purge du cache herite du deploiement precedent (var/cache/$APP_ENV)..."
	rm -rf "/app/var/cache/$APP_ENV"
fi

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
	log 'La base est restee injoignable pendant 120 s. Derniere erreur :'
	$CONSOLE dbal:run-sql "SELECT 1" 2>&1 | tail -n 40 || true
	fatal 'base de donnees injoignable.'
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
	# ambiguite couterait plus cher que de continuer en le disant — mais on dit
	# *pourquoi*, sinon l'avertissement n'aide personne.
	log "AVERTISSEMENT : le schema ne s'est pas declare a jour en 180 s. Raison :"
	$CONSOLE doctrine:migrations:up-to-date 2>&1 | tail -n 20 || true
	log 'On continue quand meme.'
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
$CONSOLE app:economy:rent-backlog-reset --min-periods=2 --no-interaction \
	|| fatal "la remise a zero de l'arriere a echoue. On ne consomme pas le calendrier."

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
#
# `exec` remplace PID 1 : c'est ce que la sonde de sante lit dans
# `/proc/1/cmdline` pour declarer le worker sain.
log 'Consommation du calendrier (transport scheduler_default).'
exec php /app/bin/console messenger:consume scheduler_default \
	--memory-limit=256M \
	--no-interaction \
	-v
