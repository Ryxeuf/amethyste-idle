Executer une requete SQL sur la base de donnees PostgreSQL du projet.

Requete a executer : $ARGUMENTS

Utiliser la commande suivante depuis le repertoire du projet (`/var/www/amethyste-idle`) :
```bash
docker compose exec -T database psql -U app -d amethyste -c "$ARGUMENTS"
```

Pour les requetes complexes ou multi-lignes, utiliser un heredoc :
```bash
docker compose exec -T database psql -U app -d amethyste << 'SQL'
$ARGUMENTS
SQL
```

Presenter les resultats de maniere lisible.

SECURITE : ne jamais executer de requetes destructives (DROP, DELETE, TRUNCATE) sans confirmation explicite de l'utilisateur.
