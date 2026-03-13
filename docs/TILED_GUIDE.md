# Guide de creation de cartes — Tiled Map Editor

## Prérequis

- **Tiled Map Editor** : https://www.mapeditor.org/ (version 1.10+)
- Tilesets du projet dans `terrain/tileset/`

## Convention de nommage

Les fichiers TMX suivent le format :

```
world-{worldId}-map-{x}-{y}.tmx    # Zone du monde (grille 3x3+)
world-{worldId}-cave-{n}.tmx       # Grotte/donjon
world-{worldId}-house-{n}.tmx      # Intérieur de bâtiment
```

Exemples : `world-1-map-0-1.tmx`, `world-1-cave-1.tmx`, `world-1-house-1.tmx`

## Paramètres de la carte

| Paramètre | Valeur |
|-----------|--------|
| Orientation | Orthogonal |
| Taille de tuile | 32 × 32 px |
| Dimensions | 60 × 60 tuiles (standard) |
| Format des données | CSV |

## Tilesets requis

Chaque carte doit inclure ces tilesets (dans cet ordre) :

| Tileset | Fichier source | Usage |
|---------|---------------|-------|
| forest | `./tileset/forest.tsx` | Arbres, buissons, eau |
| Terrain | `./tileset/Terrain.tsx` | Sol, chemins, herbe |
| Collisions | `./tileset/Collisions.tsx` | Masque de collision (invisible) |
| BaseChip_pipo | `./tileset/BaseChip_pipo.tsx` | Bâtiments, murs, décorations |

## Layers obligatoires

Chaque carte doit contenir ces layers **dans cet ordre** :

### Layers de tuiles (tile layers)

| # | Nom | Description |
|---|-----|-------------|
| 1 | `background` | Sol de base (herbe, terre, eau) |
| 2 | `background_overlay` | Détails du sol (fleurs, cailloux, fissures) |
| 3 | `objects` | Objets posés (arbres, rochers, bâtiments) |
| 4 | `objects_overlay` | Surplomb (toits, branches, voûtes) |
| 5 | `collision` | Layer de collision (**visibility off**, utilise tileset Collisions) |

### Object Layers (layers d'objets)

Les object layers permettent de placer des entités interactives importées automatiquement par `app:terrain:import --sync-entities`.

| Nom | Description |
|-----|-------------|
| `mob_spawn` | Zones d'apparition de monstres |
| `npc_spawn` | Positions de PNJ |
| `portal` | Portails de téléportation |
| `harvest_spot` | Points de récolte |
| `chest` | Coffres |

## Collision layer — Référence des tuiles

Le layer `collision` utilise le tileset Collisions avec ces tuiles :

| Tuile # | Effet | Description |
|---------|-------|-------------|
| 0 | Passable | Aucune collision |
| 1 | Mur (`-1`) | Totalement bloqué |
| 2 | Bloque Ouest | Empêche d'entrer par l'ouest |
| 3 | Bloque Est | |
| 4 | Bloque Sud | |
| 5 | Bloque Nord | |
| 6 | Bloque N+S | |
| 7 | Bloque E+W | |
| 8 | Bloque N+E | |
| 9 | Bloque E+S | |
| 10 | Bloque S+W | |
| 11 | Bloque W+N | |
| 12 | Bloque E+S+W | |
| 13 | Bloque N+E+W | |
| 14 | Bloque N+E+S | |
| 15 | Bloque N+S+W | |
| 16 | Escalade | Nécessite la capacité "climb" |

## Object Layers — Propriétés des objets

### mob_spawn

Placer un objet rectangle dans le layer `mob_spawn` :

| Propriété | Type | Requis | Description |
|-----------|------|--------|-------------|
| `monster_slug` | string | Oui | Slug du monstre (ex: `zombie`, `ochu`, `dragon`) |

L'objet sera converti en entité `Mob` à la position de la tuile.

### npc_spawn

Placer un objet point dans le layer `npc_spawn` :

| Propriété | Type | Requis | Description |
|-----------|------|--------|-------------|
| `pnj_id` | int | Non | ID du PNJ existant en base |

### portal

Placer un objet rectangle dans le layer `portal` :

| Propriété | Type | Requis | Description |
|-----------|------|--------|-------------|
| `target_map_id` | int | Oui | ID de la carte de destination |
| `target_x` | int | Oui | Coordonnée X de destination |
| `target_y` | int | Oui | Coordonnée Y de destination |
| `target_map` | string | Non | Nom de la map (pour la validation) |

Exemple : un portail vers la grotte à `(5, 10)` sur la map ID 11 :
- `target_map_id` = `11`
- `target_x` = `5`
- `target_y` = `10`

### harvest_spot

Placer un objet rectangle dans le layer `harvest_spot` :

| Propriété | Type | Requis | Description |
|-----------|------|--------|-------------|
| `slug` | string | Non | Slug du spot (ex: `spot-ruby-xs`) |
| `item_slug` | string | Non | Slug de l'item récolté |
| `item_min` | int | Non | Quantité minimum (défaut: 1) |
| `item_max` | int | Non | Quantité maximum (défaut: 1) |

### chest

Placer un objet rectangle dans le layer `chest` :

| Propriété | Type | Requis | Description |
|-----------|------|--------|-------------|
| `item_slug` | string | Non | Slug de l'item contenu |
| `item_min` | int | Non | Quantité minimum |
| `item_max` | int | Non | Quantité maximum |

## Workflow complet

```bash
# 1. Créer/modifier la carte dans Tiled Map Editor
#    → Sauvegarder en .tmx dans terrain/

# 2. Valider la carte
docker compose exec php php bin/console app:terrain:import --validate world-1-map-X-Y.tmx

# 3. Importer la carte (JSON + slugs)
docker compose exec php php bin/console app:terrain:import world-1-map-X-Y.tmx

# 4. Importer ET créer les entités depuis les object layers
docker compose exec php php bin/console app:terrain:import --sync-entities world-1-map-X-Y.tmx

# 5. Importer toutes les cartes
docker compose exec php php bin/console app:terrain:import --all

# 6. Régénérer le CSS des sprites
docker compose exec php php bin/console app:tmx:generate-css

# 7. Recharger les fixtures (recréer la base)
./scripts/reload-fixtures.sh --dev
```

## Bonnes pratiques

1. **Toujours mettre le layer collision** — une map sans collisions sera signalée par `--validate`
2. **Portails bidirectionnels** — si A mène à B, B doit mener à A
3. **Spawns sur cases passables** — ne pas placer de mob_spawn sur un mur
4. **Tester après import** — vérifier visuellement que les tuiles s'affichent correctement
5. **Nommer les objets** — donner un nom descriptif aux portails et spots de récolte
6. **Un commit par carte** — facilite le suivi des changements
