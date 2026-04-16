# ASSETS.md — Guide des Assets Graphiques

## Format des sprites personnage

Tous les sprites personnage/monstre/PNJ utilisent le format **RPG Maker VX/MV** :

### Single character (un personnage par fichier)
- **Dimensions** : 96x128 px (3 colonnes x 4 lignes)
- **Taille par frame** : 32x32 px
- **Layout** :
  - Ligne 0 = face (bas)
  - Ligne 1 = gauche
  - Ligne 2 = droite
  - Ligne 3 = dos (haut)
  - Colonnes 0, 1, 2 = cycle de marche (colonne 1 = idle/stand)

### Multi character (plusieurs personnages par fichier)
- **Dimensions** : variable (ex: 384x256 pour 8 personnages)
- **Layout** : 12 colonnes x 8 lignes totales
- **Chaque personnage** occupe un bloc 3x4 (même layout que single)
- **charIndex** : 0-3 = moitié haute, 4-7 = moitié basse

## Structure des dossiers

```
assets/styles/images/
  terrain/                    # Tilesets terrain pour les cartes
    terrain.png               # Tileset principal
    forest.png                # Tileset forêt
    BaseChip_pipo.png         # Objets/décorations
    collisions.png            # Visualisation collisions (dev only)
  character/                  # Sprites personnages
    Male/                     # Hommes (Male 01-1.png ... Male 18-4.png)
    Female/                   # Femmes
    Soldier/                  # Soldats
    Midona.png                # Multi-char sheet
    monk.png                  # Multi-char sheet
  Animal/                     # Sprites animaux (Cat, Dog)
  Boss/                       # Boss sprites
  demons.png                  # Multi-char monstres (zombies, etc.)
```

## Comment ajouter un nouveau sprite

1. Créer/obtenir un sprite au format RPG Maker VX (96x128 single ou 12col x 8row multi)
2. Placer dans le dossier approprié (`character/`, `Animal/`, etc.)
3. Ajouter la config sprite dans `SpriteConfigProvider` (`src/GameEngine/Map/SpriteConfigProvider.php`)
4. Le `SpriteAnimator.js` détecte automatiquement la taille des frames

## Configuration backend

Dans `src/GameEngine/Map/SpriteConfigProvider.php` :

```php
// Single character (96x128, 3x4)
'mon_sprite_key' => $this->single('character/MonSprite.png', 'player'),

// Multi character (384x256, 12x8, 8 personnages)
'mob_key' => $this->multi('monsters.png', 0, 'mob'),  // charIndex 0-7
```

## Sources d'assets compatibles

- **itch.io** : chercher "RPG Maker VX character" ou "RPG Maker MV sprites"
- **OpenGameArt.org** : filtrer par "RPG" + licence compatible
- Les sprites existants viennent principalement de packs RPG Maker VX Ace RTP

## Tilesets terrain

- Édités dans **Tiled Map Editor**
- Taille de tuile : **32x32 px**
- Formats supportés : `.tsx` (Tiled tileset) référençant des `.png`
- Placement : `terrain/tileset/` pour les `.tsx`, `assets/styles/images/terrain/` pour les `.png`
