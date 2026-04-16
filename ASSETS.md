# ASSETS.md — Guide des Assets Graphiques

## Format des sprites personnage

### Format legacy — RPG Maker VX/MV (mobs, PNJ)

Tous les sprites monstre/PNJ utilisent le format **RPG Maker VX/MV** :

#### Single character (un personnage par fichier)
- **Dimensions** : 96x128 px (3 colonnes x 4 lignes)
- **Taille par frame** : 32x32 px
- **Layout** :
  - Ligne 0 = face (bas)
  - Ligne 1 = gauche
  - Ligne 2 = droite
  - Ligne 3 = dos (haut)
  - Colonnes 0, 1, 2 = cycle de marche (colonne 1 = idle/stand)

#### Multi character (plusieurs personnages par fichier)
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

### Format avatar 8x8 (joueurs)

Les sprites joueurs utilisent un format **8 colonnes x 8 lignes** avec composition multi-layers :

- **Dimensions (base)** : 512x512 px (8 colonnes x 8 lignes)
- **Taille par frame** : 64x64 px
- **Animations base** : walk (rows 0-3) + stand/idle (rows 4-7)
- **Directions** : identiques au legacy (down=0, left=1, right=2, up=3)
- **Layers** : body + outfit + hair + head_gear (meme layout, empiles avec transparence)
- **Animations etendues** : run, jump, push, pull (sheets 512xN, rows additionnelles)
- **Type SpriteAnimator** : `avatar`

Layout de la grille :

| Rows | Animation | Contenu |
|------|-----------|---------|
| 0-3 | walk | Cycle de marche 8 frames (down, left, right, up) |
| 4-7 | stand | Idle/respiration 8 frames (down, left, right, up) |
| 8-11 | run (etendu) | Course rapide 8 frames |
| 12-15 | jump (etendu) | Saut 8 frames |
| 16-19 | push (etendu) | Pousser 8 frames |
| 20-23 | pull (etendu) | Tirer 8 frames |

> Specification complete : [docs/avatar-spritesheet-layout.md](docs/avatar-spritesheet-layout.md)

#### Structure des fichiers avatar

```
assets/styles/images/avatar/
    body/           # Corps de base (skin tones, genres)
    hair/           # Coiffures (fond transparent)
    outfit/         # Armures / tenues completes
    head/           # Casques, coiffes
```

Chaque layer doit etre une sprite sheet **512x512** (8x8, 64x64 par frame) avec le meme layout que le body, parfaitement alignee. La composition se fait par `AvatarTextureComposer.js` (superposition alpha dans PixiJS).

---

## Comment ajouter un nouveau sprite

### Sprite legacy (mob, PNJ)


1. Créer/obtenir un sprite au format RPG Maker VX (96x128 single ou 12col x 8row multi)
2. Placer dans le dossier approprié (`character/`, `Animal/`, etc.)
3. Ajouter la config sprite dans `SpriteConfigProvider` (`src/GameEngine/Map/SpriteConfigProvider.php`)
4. Le `SpriteAnimator.js` détecte automatiquement la taille des frames

### Sprite avatar (joueur)

1. Créer les layers au format 512x512 (8x8, 64x64 par frame) — voir [specification](docs/avatar-spritesheet-layout.md)
2. Placer dans `assets/styles/images/avatar/{body,hair,outfit,head}/`
3. Le `AvatarTextureComposer.js` empile les layers au runtime
4. Le `SpriteAnimator` type `avatar` gere l'animation

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
