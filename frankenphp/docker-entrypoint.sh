#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	# Install the project the first time PHP is started
	# After the installation, the following block can be deleted
	if [ ! -f composer.json ]; then
		rm -Rf tmp/
		composer create-project "symfony/skeleton $SYMFONY_VERSION" tmp --stability="$STABILITY" --prefer-dist --no-progress --no-interaction --no-install

		cd tmp
		cp -Rp . ..
		cd -
		rm -Rf tmp/

		composer require "php:>=$PHP_VERSION" runtime/frankenphp-symfony
		composer config --json extra.symfony.docker 'true'

		if grep -q ^DATABASE_URL= .env; then
			echo 'To finish the installation please press Ctrl+C to stop Docker Compose and run: docker compose up --build -d --wait'
			sleep infinity
		fi
	fi

	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	# Display information about the current project
	# Or about an error in project initialization
	php bin/console -V

	if grep -q ^DATABASE_URL= .env; then
		echo 'Waiting for database to be ready...'
		ATTEMPTS_LEFT_TO_REACH_DATABASE=60
		until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql -q "SELECT 1" 2>&1); do
			if [ $? -eq 255 ]; then
				# If the Doctrine command exits with 255, an unrecoverable error occurred
				ATTEMPTS_LEFT_TO_REACH_DATABASE=0
				break
			fi
			sleep 1
			ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
			echo "Still waiting for database to be ready... Or maybe the database is not reachable. $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left."
		done

		if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
			echo 'The database is not up or not reachable:'
			echo "$DATABASE_ERROR"
			exit 1
		else
			echo 'The database is now ready and reachable'
		fi

		if [ "$( find ./migrations -iname '*.php' -print -quit )" ]; then
			php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
		fi
	fi

	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var

	# Vider et reconstruire le cache (traductions, LiveComponent TemplateMap, etc.)
	php bin/console cache:clear --no-warmup || true
	php bin/console cache:warmup

	# var/ est un volume Docker : le CSS Tailwind compile (var/tailwind/*.built.css) n'est pas
	# conserve depuis l'image. On le regenere si absent pour eviter les erreurs asset() / importmap.
	if [ ! -f var/tailwind/app.built.css ]; then
		echo 'Building Tailwind CSS (var/tailwind/app.built.css missing)...'
		php bin/console tailwind:build --no-interaction || true
	fi

	# Précompiler les assets (manifest + fichiers digestés) pour éviter la compilation
	# à la volée sur chaque GET /assets/... (timeouts 30s sous charge parallèle).
	if [ "${APP_ENV:-prod}" = "prod" ]; then
		echo 'Compiling asset map for production...'
		php bin/console asset-map:compile --no-interaction || true
	fi

	# Carte id 1 (fixtures map_1) : tag_1_* et map_1_* sont nécessaires pour le déplacement.
	# Les images anciennes n'ont souvent que tag_10_* (même graphe). Dupliquer si manquant.
	DATA_MAP="data/map"
	if [ -d "$DATA_MAP" ]; then
		for prefix in tag map; do
			has_id1=""
			for f in "$DATA_MAP/${prefix}_1_"*; do
				[ -f "$f" ] && has_id1=1 && break
			done
			if [ -z "$has_id1" ]; then
				for src in "$DATA_MAP/${prefix}_10_"*; do
					[ -f "$src" ] || continue
					base="${src##*/}"
					suffix="${base#"${prefix}_10_"}"
					dest="$DATA_MAP/${prefix}_1_$suffix"
					if [ ! -f "$dest" ]; then
						cp "$src" "$dest"
						echo "Created $dest for map id 1 (copied from $base)"
					fi
					break
				done
			fi
		done
	fi

	echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
