Importer les cartes Tiled Map Editor dans la base de donnees.

Executer dans le repertoire du projet (`/var/www/amethyste-idle`) les commandes suivantes dans l'ordre :

1. Importer les fichiers TMX :
   ```bash
   docker compose exec php php bin/console app:terrain:import
   ```

2. Generer les classes CSS sprites :
   ```bash
   docker compose exec php php bin/console app:tmx:generate-css
   ```

3. Vider le cache :
   ```bash
   docker compose exec php php bin/console cache:clear
   ```

Rapporter ce qui a ete importe (nombre de zones, tilesets, etc.).

CONTEXT : les cartes sont editees dans Tiled Map Editor (fichiers `.tmx` dans `terrain/`). L'import parse le XML TMX en JSON, extrait les couches de tuiles, les collisions (bitmask directionnel), et les tilesets.
