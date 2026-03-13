Deployer le projet amethyste-idle.

Arguments : $ARGUMENTS (optionnel: `--prod` ou `--dev`, defaut: `--dev`)

Etapes :
1. Se placer dans le repertoire du projet : `cd /var/www/amethyste-idle`
2. Executer le script de deploiement : `./scripts/deploy.sh $ARGUMENTS`
3. Verifier le statut des services avec `docker compose ps`
4. Rapporter le resultat du deploiement

Si aucun argument n'est fourni, demander a l'utilisateur s'il veut deployer en `--prod` ou `--dev`.

IMPORTANT : le deploiement prod active une page de maintenance, compile les assets (Tailwind + AssetMapper), et vide le cache. Le script gere tout automatiquement.
