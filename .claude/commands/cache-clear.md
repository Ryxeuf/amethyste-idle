Vider le cache Symfony dans le conteneur Docker.

Executer dans le repertoire du projet (`/var/www/amethyste-idle`) :

```bash
docker compose exec php php bin/console cache:clear
```

Apres le vidage du cache, confirmer que l'operation s'est terminee avec succes.

Si une erreur survient (ex: conteneur non demarre), proposer de demarrer les services avec `docker compose up -d`.
