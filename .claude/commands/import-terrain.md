Importer les cartes Tiled Map Editor dans la base de donnees.

Executer dans le repertoire du projet les commandes suivantes dans l'ordre :

1. Importer les fichiers TMX avec sync des entites :
   ```bash
   docker compose exec php php bin/console app:terrain:import --all --sync-entities --stats
   ```

2. Generer les tags Dijkstra :
   ```bash
   docker compose exec php php bin/console app:map:dump
   ```

3. Vider le cache :
   ```bash
   docker compose exec php php bin/console cache:clear
   ```

Rapporter ce qui a ete importe (nombre de zones, tilesets, cells, objets).

CONTEXT : les cartes sont editees dans Tiled Map Editor (fichiers `.tmx` dans `terrain/`). L'import parse le XML TMX en JSON, extrait les couches de tuiles, les collisions (bitmask directionnel), les object layers (mobs, portails, spots), et les animations de tiles.

OPTIONS UTILES :
- `--validate` : verifier la coherence sans importer
- `--dry-run` : analyse sans ecriture
- `--stats` : statistiques detaillees

FUTUR : Une commande unifiee `app:terrain:sync` remplacera ces etapes (voir docs/TILED_PIPELINE_ROADMAP.md Phase T2).
