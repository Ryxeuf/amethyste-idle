Importer les cartes Tiled Map Editor dans la base de donnees.

## Commande unifiee (recommandee)

Utiliser `app:terrain:sync` qui orchestre tout le pipeline en une seule commande :

```bash
docker compose exec php php bin/console app:terrain:sync
```

Cette commande effectue dans l'ordre :
1. Import de tous les fichiers TMX (parse + export JSON)
2. Sync des entites (mobs, portails, spots de recolte, coffres)
3. Sync des zones/biomes vers les entites Area
4. Regeneration du cache Dijkstra (pathfinding)

### Options

| Option | Description |
|--------|------------|
| `<name>` | Fichier TMX specifique (ex: `world-1-map-0-1.tmx`) |
| `--all` | Traiter tous les fichiers TMX (defaut si aucun nom) |
| `--map-id=N` | ID de la carte cible (auto-detect par defaut) |
| `--dry-run` | Analyse sans ecriture |
| `--skip-entities` | Ne pas synchroniser les entites |
| `--skip-zones` | Ne pas synchroniser les zones/biomes |
| `--skip-dijkstra` | Ne pas regenerer le cache Dijkstra |

### Exemples

```bash
# Pipeline complet (tous les TMX)
docker compose exec php php bin/console app:terrain:sync

# Un seul fichier, dry-run
docker compose exec php php bin/console app:terrain:sync world-1-map-0-1.tmx --dry-run

# Import sans regenerer Dijkstra
docker compose exec php php bin/console app:terrain:sync --skip-dijkstra

# Import + Dijkstra pour une carte specifique
docker compose exec php php bin/console app:terrain:sync --map-id=1
```

## Commandes separees (legacy)

Si besoin d'executer les etapes individuellement :

1. Import TMX avec sync entites :
   ```bash
   docker compose exec php php bin/console app:terrain:import --all --sync-entities --sync-zones --stats
   ```

2. Generer les tags Dijkstra :
   ```bash
   docker compose exec php php bin/console app:map:dump
   ```

3. Vider le cache :
   ```bash
   docker compose exec php php bin/console cache:clear
   ```

CONTEXT : les cartes sont editees dans Tiled Map Editor (fichiers `.tmx` dans `terrain/`). L'import parse le XML TMX en JSON, extrait les couches de tuiles, les collisions (bitmask directionnel), les object layers (mobs, portails, spots), et les animations de tiles.
