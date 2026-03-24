Importer les cartes Tiled Map Editor dans la base de donnees.

## Commande unifiee (recommandee)

Executer la commande unique qui orchestre tout le pipeline :

```bash
docker compose exec php php bin/console app:terrain:sync --all
```

Cette commande effectue automatiquement :
1. Import de tous les fichiers TMX (parse, export JSON)
2. Synchronisation des entites (mobs, portails, spots, coffres)
3. Synchronisation des zones/biomes vers les Area
4. Reconstruction du cache Dijkstra pour toutes les cartes
5. Affichage d'un rapport diff (fichiers, cells, entites creees/modifiees)

### Options utiles

| Option | Description |
|--------|------------|
| `--dry-run` | Analyse sans ecriture (fichiers ni BDD) |
| `--map-id=N` | Forcer l'ID de carte cible pour le sync |
| `--skip-dijkstra` | Ne pas reconstruire le cache Dijkstra |
| `--skip-entities` | Ne pas synchroniser mobs/portails/spots |
| `--skip-zones` | Ne pas synchroniser zones/biomes |

### Exemples

```bash
# Sync un seul fichier TMX
docker compose exec php php bin/console app:terrain:sync world-1-map-0-1.tmx

# Dry-run pour verifier avant d'ecrire
docker compose exec php php bin/console app:terrain:sync --all --dry-run

# Import sans Dijkstra (plus rapide pour iteration)
docker compose exec php php bin/console app:terrain:sync --all --skip-dijkstra
```

## Commandes separees (legacy)

Si besoin d'executer les etapes individuellement :

1. Import TMX avec sync entites et zones :
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

Rapporter ce qui a ete importe (nombre de zones, tilesets, cells, objets).

CONTEXT : les cartes sont editees dans Tiled Map Editor (fichiers `.tmx` dans `terrain/`). L'import parse le XML TMX en JSON, extrait les couches de tuiles, les collisions (bitmask directionnel), les object layers (mobs, portails, spots), et les animations de tiles.
