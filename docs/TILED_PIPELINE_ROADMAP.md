# Pipeline Tiled → Jeu — Plan d'amélioration

> Diagnostic et roadmap pour corriger les 7 problèmes identifiés dans le pipeline Tiled.
> Créé le 2026-03-20.

---

## Diagnostic — Ce qui marche bien

- Format TMX standard, Wang Sets pour jonctions auto
- `terrain:import` parse correctement layers, collisions et objets
- Rendu PixiJS performant (pooling, cache texture, spatial hash)
- Architecture Area/Map/ObjectLayer propre

---

## Les 7 problèmes identifiés

| # | Problème | Impact | Effort |
|---|----------|--------|--------|
| 1 | Animations de tiles ignorées | Élevé — monde mort | Modéré |
| 2 | Pas de données biome/zone | Moyen — pas d'ambiance | Modéré |
| 3 | Commande CSS obsolète | Faible — code mort | Faible |
| 4 | Pipeline 3 étapes déconnectées | Moyen — fragile | Modéré |
| 5 | Map ID hardcodé | Élevé — bloquant multi-maps | Faible |
| 6 | Pas d'animations dans /api/map/config | Élevé — lié au #1 | Faible |
| 7 | Object layers sans zones/régions | Moyen — pas de biomes | Modéré |

---

## Phase T1 — Animations de tiles (impact maximal)

**Problèmes résolus** : #1, #6
**Taille** : M | **Release** : v0.6 (Effets visuels)
**Dépendances** : Aucune

### Contexte

Les TSX contiennent des `<animation>` (eau, lave, fleurs — 10 dans `Terrain.tsx`, 1 dans `forest.tsx`). `TerrainImportCommand` ne les parse jamais. Le frontend utilise `PIXI.Sprite` statique — jamais `PIXI.AnimatedSprite`.

### T1.A — Backend : parser les animations TSX [S]

**Fichiers à modifier** :
- `src/Command/TerrainImportCommand.php`

**Changements** :
1. Dans la méthode de parsing TSX, détecter les éléments `<tile><animation>`
2. Pour chaque tile animée, extraire les frames : `[{tileid, duration}, ...]`
3. Construire un dictionnaire `animations` par tileset :
```php
$animations = [
    175 => [ // tileId local dans le tileset
        ['frame' => 175, 'duration' => 500],
        ['frame' => 176, 'duration' => 500],
        ['frame' => 177, 'duration' => 500],
    ],
];
```
4. Inclure `animations` dans le JSON exporté de chaque tileset
5. Propager dans `Area.fullData` si utilisé

**Tests** :
- Test unitaire : parsing d'un TSX avec animations → dictionnaire correct
- Test : TSX sans animation → dictionnaire vide

### T1.B — API : exposer les animations [S]

**Fichiers à modifier** :
- `src/Controller/Api/MapApiController.php`

**Changements** :
1. Dans `/api/map/config`, ajouter un champ `animations` par tileset
2. Format de sortie :
```json
{
  "tilesets": [
    {
      "name": "Terrain",
      "firstGid": 3073,
      "animations": {
        "175": [
          {"frame": 175, "duration": 500},
          {"frame": 176, "duration": 500},
          {"frame": 177, "duration": 500}
        ]
      }
    }
  ]
}
```
3. Le frontend pourra calculer le GID global : `firstGid + localTileId`

**Tests** :
- Test fonctionnel : `/api/map/config` retourne les animations

### T1.C — Frontend : PIXI.AnimatedSprite [M]

**Fichiers à modifier** :
- `assets/controllers/map_pixi_controller.js`

**Changements** :
1. Au chargement de la config, construire un map `_animatedGids` : `{ globalGid → [{frame: globalGid, duration}, ...] }`
2. Dans `_renderCell()`, quand un GID est dans `_animatedGids` :
   - Créer un `PIXI.AnimatedSprite` au lieu de `PIXI.Sprite`
   - Configurer les frames depuis les textures du tileset
   - `animatedSprite.animationSpeed = 1000 / (avgDuration * 60)`
   - `animatedSprite.play()`
3. Pool séparé `_animatedSpritePool` pour les animated sprites
4. Tracker `_activeAnimatedTiles[]` pour cleanup au pruning
5. Décaler aléatoirement le `currentFrame` initial pour éviter la synchronisation

**Performance** :
- Les `AnimatedSprite` PixiJS gèrent leur propre timer via le ticker
- Pas de boucle manuelle nécessaire
- Le pooling évite les allocations

**Résultat** : eau, lave, fleurs animées sans modifier Tiled.

---

## Phase T2 — Pipeline unifié `app:terrain:sync`

**Problèmes résolus** : #4
**Taille** : M | **Release** : v0.5
**Dépendances** : Phase T1 (animations dans le JSON)

### Contexte

Actuellement 3 étapes manuelles déconnectées :
```
terrain:import → (modifier BDD manuellement) → map:dump
```

### T2.A — Nouvelle commande unifiée [M]

**Fichiers à créer** :
- `src/Command/TerrainSyncCommand.php`

**Comportement** :
```bash
docker compose exec php php bin/console app:terrain:sync --all
```

Étapes automatisées :
1. **Import TMX → JSON** : réutilise la logique de `terrain:import`
2. **Upsert Area** : crée/met à jour les entités `Area` en BDD avec `fullData`
3. **Sync objets** : portails, mobs, spots → `ObjectLayer` entities
4. **Génère tag maps** : Dijkstra (logique `map:dump`)
5. **Rapport de diff** : affiche ce qui a changé (ajouté/modifié/supprimé)

**Options** :
- `--all` : toutes les maps
- `--map=<name>` : une map spécifique
- `--dry-run` : affiche le diff sans appliquer
- `--force` : écrase les données même si identiques

**Fichiers à modifier** :
- `src/Command/TerrainImportCommand.php` — extraire la logique de parsing en service réutilisable
- `src/Command/MapDumpCommand.php` — extraire la logique de génération tags

**Services à créer** :
- `src/Service/Terrain/TmxParser.php` — parsing TMX/TSX (extrait de TerrainImportCommand)
- `src/Service/Terrain/AreaSynchronizer.php` — upsert Area + ObjectLayer
- `src/Service/Terrain/DijkstraTagGenerator.php` — génération tags (extrait de MapDumpCommand)

### T2.B — Mise à jour de l'agent import-terrain [S]

- Mettre à jour `.claude/commands/import-terrain.md` pour utiliser `app:terrain:sync`
- Garder `terrain:import` comme commande low-level (non dépréciée)

**Tests** :
- Test : `terrain:sync --dry-run` ne modifie rien en BDD
- Test : `terrain:sync` sur un TMX crée les entities correctement
- Test : relancer `terrain:sync` sur des données identiques = pas de diff

---

## Phase T3 — Zones/biomes depuis Tiled

**Problèmes résolus** : #2, #7
**Taille** : M | **Release** : v0.6 (Effets visuels)
**Dépendances** : Phase T2 (pipeline unifié)

### Contexte

Le JSON exporté ne contient aucune métadonnée de "zone". Impossible d'appliquer des effets d'ambiance contextuellement. Les object layers ne supportent que les objets ponctuels.

### T3.A — Support des zones rectangulaires dans Tiled [S]

**Convention Tiled** :
- Créer un Object Layer nommé `zones` dans chaque TMX
- Y placer des rectangles/polygones avec les propriétés custom :
  - `biome` (string) : `forest`, `cave`, `water`, `village`, `mountain`, `desert`
  - `ambient` (string, optionnel) : `leaves`, `dust`, `bubbles`, `fireflies`
  - `weather` (string, optionnel) : `rain`, `snow`, `fog`, `none`
  - `music` (string, optionnel) : slug du thème musical
  - `light` (float, optionnel) : modificateur de luminosité (0.0 = noir, 1.0 = normal)

**Fichiers à modifier** :
- `src/Command/TerrainImportCommand.php` (ou `TmxParser` après T2)

**Changements** :
1. Détecter les objets de type rectangle/polygon dans le layer `zones`
2. Extraire les coordonnées (x, y, width, height en pixels → convertir en tiles)
3. Stocker dans le JSON :
```json
{
  "zones": [
    {
      "name": "Forêt sombre",
      "biome": "forest",
      "ambient": "leaves",
      "weather": "none",
      "light": 0.7,
      "bounds": {"x": 5, "y": 10, "w": 20, "h": 15}
    }
  ]
}
```

### T3.B — API zones [S]

**Fichiers à modifier** :
- `src/Controller/Api/MapApiController.php`

**Changements** :
- Ajouter un champ `zones` dans `/api/map/config` ou créer `/api/map/zones`
- Retourner la liste des zones avec leurs propriétés

### T3.C — Frontend : effets d'ambiance par zone [M]

**Fichiers à modifier** :
- `assets/controllers/map_pixi_controller.js`

**Changements** :
1. Au déplacement du joueur, détecter la zone courante (point-in-rect)
2. Appliquer les effets selon le biome :
   - `forest` → particules de feuilles (existant dans le particle system)
   - `cave` → assombrissement (overlay alpha), particules de poussière
   - `water` → reflets, particules de bulles
   - `village` → pas d'effet spécial, luminosité normale
   - `mountain` → brouillard léger, vent
3. Transition douce entre zones (fade 500ms)
4. `weather` override : pluie/neige/brouillard en overlay plein écran

**Tests** :
- Test backend : parsing zones depuis TMX
- Test : zones dans la réponse API

---

## Phase T4 — Supprimer la commande CSS morte

**Problèmes résolus** : #3
**Taille** : S | **Release** : v0.5
**Dépendances** : Aucune

### Fichiers à supprimer
- `src/Command/TmxCssGeneratorCommand.php`
- `assets/styles/map/` (CSS généré, si présent)

### Fichiers à modifier
- `.claude/commands/import-terrain.md` — retirer l'étape `app:tmx:generate-css`
- `DOCUMENTATION.md` section 20 — retirer la mention de `app:tmx:generate-css`
- `CLAUDE.md` — retirer la commande des commandes courantes

### Vérification avant suppression
- Grep `generate-css` et `tmx:generate` dans tout le projet
- Vérifier qu'aucun template Twig n'utilise les classes CSS `.tileset-*`
- Vérifier qu'aucun controller JS n'importe ces CSS

---

## Phase T5 — Dé-hardcoder les map IDs

**Problèmes résolus** : #5
**Taille** : S | **Release** : v0.5
**Dépendances** : Phase T2 (pour le naming convention)

### Contexte

- `syncEntitiesFromObjects()` hardcode `map_id = 10`
- L'endpoint `/api/map/move` hardcode `loadMap(10)`
- Non scalable pour plusieurs maps

### Fichiers à modifier

**Backend** :
- `src/Command/TerrainImportCommand.php` — `syncEntitiesFromObjects()` : déduire le `mapId` depuis le nom du fichier TMX ou une propriété custom `map_id` dans le TMX
- `src/GameEngine/Map/MovementCalculator.php` — `loadMap()` : utiliser `$player->getMap()->getId()` au lieu de `10`
- `src/Controller/Api/MapApiController.php` — endpoint move : utiliser le mapId du joueur courant

**Convention de nommage TMX → mapId** :
- Option A : propriété custom `<property name="map_id" value="10"/>` dans le TMX
- Option B : table de correspondance `filename → mapId` en config
- **Recommandé** : Option A (données dans Tiled, source de vérité unique)

**Tests** :
- Test : `loadMap()` avec différents mapIds
- Test : `syncEntitiesFromObjects` avec mapId depuis TMX

---

## Ordre d'exécution

```
T4 (Supprimer CSS)          ← indépendant, rapide
T5 (Dé-hardcoder map IDs)   ← indépendant, rapide
  ↓
T1 (Animations tiles)       ← impact maximal
  ↓
T2 (Pipeline unifié)        ← dépend T1 pour inclure animations
  ↓
T3 (Zones/biomes)           ← dépend T2 pour le parser
```

### Parallélisations possibles

- T4 et T5 sont indépendants et parallélisables immédiatement
- T1 peut commencer en parallèle de T4/T5
- T3 attend T2

---

## Intégration avec les phases existantes

| Phase pipeline | S'intègre avec | Raison |
|---------------|----------------|--------|
| T1 (Animations) | Phase 25 (Effets visuels) | Même thématique d'ambiance |
| T2 (Pipeline sync) | Phase 21 (Nouvelles zones) | Prérequis pour ajouter des maps facilement |
| T3 (Zones/biomes) | Phase 25 (Effets visuels) | Particules et météo par zone |
| T4 (CSS morte) | Phase 26 (Performance) | Nettoyage de code mort |
| T5 (Map IDs) | Phase 21 (Nouvelles zones) | Prérequis multi-maps |

**Recommandation** : exécuter T4 + T5 immédiatement, puis T1 en parallèle de la release v0.4, puis T2 + T3 avant la Phase 21.

---

## Résumé

| Phase | Description | Taille | Problèmes | Release |
|-------|------------|--------|-----------|---------|
| T1 | Animations de tiles | M | #1, #6 | v0.6 |
| T2 | Pipeline unifié `terrain:sync` | M | #4 | v0.5 |
| T3 | Zones/biomes depuis Tiled | M | #2, #7 | v0.6 |
| T4 | Supprimer commande CSS morte | S | #3 | v0.5 |
| T5 | Dé-hardcoder map IDs | S | #5 | v0.5 |
