Compiler tous les assets du projet (Tailwind CSS + AssetMapper).

Executer dans le repertoire du projet (`/var/www/amethyste-idle`) les commandes suivantes dans l'ordre :

1. Compiler Tailwind CSS :
   ```bash
   docker compose exec php php bin/console tailwind:build
   ```

2. Compiler les assets (importmap) :
   ```bash
   docker compose exec php php bin/console asset-map:compile
   ```

3. Vider le cache :
   ```bash
   docker compose exec php php bin/console cache:clear
   ```

Rapporter le resultat de chaque etape.

NOTE : en environnement de dev (`APP_ENV=dev`), la compilation AssetMapper n'est generalement pas necessaire car les assets sont servis dynamiquement. Ne compiler (`asset-map:compile`) que pour la production ou le debug. Si `public/assets/` existe en dev, les fichiers compiles prennent le dessus sur l'AssetMapper dynamique — supprimer ce dossier en dev si necessaire.
