# Layout du spritesheet avatar 8x8 — Specification AVT-02

> Specification du format de spritesheet pour le systeme d'avatar modulaire.
> Document de reference pour AVT-06 (SpriteAnimator type `avatar`) et la creation d'assets.
> Derniere mise a jour : 2026-04-17

---

## Vue d'ensemble

Le systeme avatar introduit un format de spritesheet **8 colonnes x 8 lignes** pour les personnages joueurs, aligne sur le **Mana Seed Character Base** (page 1, `char_a_p1`). Ce layout coexiste avec le format legacy RPG Maker VX (3x4 / 12x8) utilise par les mobs et PNJ.

| Propriete | Valeur |
|-----------|--------|
| **Colonnes** | 8 |
| **Lignes (base)** | 8 |
| **Frame size** | 64x64 px |
| **Sheet size (base)** | 512x512 px |
| **Format image** | PNG 32-bit (RGBA, fond transparent) |
| **Animations base** | stand, push, pull, jump, walk, run |
| **Directions** | 4 (down, up, left, right) |

### Justifications techniques

- **64x64 par frame** : 4x la resolution des sprites legacy (32x32), offre un rendu plus detaille pour les joueurs
- **512x512 (sheet)** : puissance de 2, optimal GPU (pas de padding ni waste VRAM)
- **Layout Mana Seed natif** : les assets proviennent directement de `ManaSeedRPGStarterPack/character_base/char_a_p1/`, donc on utilise leur convention de grille sans reslice pour garantir l'alignement pixel-perfect entre layers

---

## Layout de la grille 8x8 (sheet de base)

La sheet 512x512 est coupee en deux blocs horizontaux de 4 lignes, chaque bloc contient les 4 directions (1 row par direction) :

```
        Col 0    Col 1    Col 2    Col 3    Col 4    Col 5    Col 6    Col 7
       ┌────────┬────────┬────────┬────────┬────────┬────────┬────────┬────────┐
Row 0  │ Stand  │ Push-1 │ Pull-1 │ Jump-1 │ Jump-2 │ Jump-3 │ Jump-4 │  --    │  facing ↓ down
Row 1  │ Stand  │ Push-1 │ Pull-1 │ Jump-1 │ Jump-2 │ Jump-3 │ Jump-4 │  --    │  facing ↑ up
Row 2  │ Stand  │ Push-1 │ Pull-1 │ Jump-1 │ Jump-2 │ Jump-3 │ Jump-4 │  --    │  facing ← left
Row 3  │ Stand  │ Push-1 │ Pull-1 │ Jump-1 │ Jump-2 │ Jump-3 │ Jump-4 │  --    │  facing → right
       ├────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
Row 4  │ Walk-1 │ Walk-2 │ Walk-3 │ Walk-4 │ Walk-5 │ Walk-6 │ Run-a  │ Run-b  │  facing ↓ down
Row 5  │ Walk-1 │ Walk-2 │ Walk-3 │ Walk-4 │ Walk-5 │ Walk-6 │ Run-a  │ Run-b  │  facing ↑ up
Row 6  │ Walk-1 │ Walk-2 │ Walk-3 │ Walk-4 │ Walk-5 │ Walk-6 │ Run-a  │ Run-b  │  facing ← left
Row 7  │ Walk-1 │ Walk-2 │ Walk-3 │ Walk-4 │ Walk-5 │ Walk-6 │ Run-a  │ Run-b  │  facing → right
       └────────┴────────┴────────┴────────┴────────┴────────┴────────┴────────┘

Chaque cellule = 64x64 px
```

### Convention des directions (Mana Seed)

| Direction | Offset row | Description |
|-----------|-----------|-------------|
| down | +0 | Face camera (sud) |
| up | +1 | Dos camera (nord) |
| left | +2 | Profil gauche |
| right | +3 | Profil droit |

> Note : l'ordre differe du legacy RPG Maker (down/left/right/up). Cette inversion est imposee par le pack Mana Seed et ne concerne que le type `avatar`.

Pour une animation donnee : `row = animation.startRow + AVATAR_DIRECTION_ROW[direction]`.

### Animations disponibles

| Animation | startRow | Colonnes (frames) | Nb frames | Timing conseille (ms) |
|-----------|----------|-------------------|-----------|----------------------|
| `stand` | 0 | `[0]` | 1 | — (pose statique) |
| `push` | 0 | `[0, 1]` | 2 | 300 / 300 |
| `pull` | 0 | `[0, 2]` | 2 | 400 / 400 |
| `jump` | 0 | `[3, 4, 5, 6]` | 4 | 300 / 150 / 100 / 300 |
| `walk` | 4 | `[0, 1, 2, 3, 4, 5]` | 6 | 135 / 135 / 135 / 135 / 135 / 135 |
| `run` | 4 | `[0, 1, 6, 3, 4, 7]` | 6 | 80 / 55 / 125 / 80 / 55 / 125 |

### Notes par animation

- **stand** : 1 frame statique par direction (col 0 des rows 0-3). Pour obtenir un effet "respiration", activer un leger offset Y programmatique (comme le legacy breathing).
- **push / pull** : alternent la pose neutre (col 0) avec une pose d'effort (col 1 pour push, col 2 pour pull).
- **jump** : 4 frames (preparation → envol → apogee → atterrissage). Par convention la 4eme frame est une reprise de la 1ere.
- **walk** : 6 frames d'un cycle complet de marche.
- **run** : reutilise les frames walk 1, 2, 4, 5 et y substitue les frames 7 et 8 (cols 6 et 7) aux positions 3 et 6 du cycle.

---

## Mapping des animations (AVATAR_ANIMATIONS)

Configuration JS pour `SpriteAnimator` type `avatar` :

```javascript
const AVATAR_ANIMATIONS = {
    stand: { startRow: 0, cols: [0],                    frames: 1 },
    push:  { startRow: 0, cols: [0, 1],                 frames: 2 },
    pull:  { startRow: 0, cols: [0, 2],                 frames: 2 },
    jump:  { startRow: 0, cols: [3, 4, 5, 6],           frames: 4 },
    walk:  { startRow: 4, cols: [0, 1, 2, 3, 4, 5],     frames: 6 },
    run:   { startRow: 4, cols: [0, 1, 6, 3, 4, 7],     frames: 6 },
};

const AVATAR_DIRECTION_ROW = { down: 0, up: 1, left: 2, right: 3 };
```

Pour obtenir une frame specifique :

```javascript
const anim = AVATAR_ANIMATIONS[animationName];
const row  = anim.startRow + AVATAR_DIRECTION_ROW[direction];
const col  = anim.cols[frameIndex % anim.frames];
const x    = col * 64;
const y    = row * 64;
```

---

## Sheets etendues (futur)

Mana Seed propose plusieurs "pages" de 512x512 partageant la meme convention de layers (paper doll) :

| Page | Contenu | Etat |
|------|---------|------|
| `char_a_p1` | stand / push / pull / jump / walk / run | **Integree** (MVP) |
| `char_a_pONE1` | combat 1-main page 1 (swing, thrust) | Prevu phase 3+ |
| `char_a_pONE2` | combat 1-main page 2 | Prevu phase 3+ |
| `char_a_pONE3` | combat 1-main page 3 | Prevu phase 3+ |

Les pages combat suivront le meme principe (sheet 512x512, grille 8x8), avec de nouvelles animations mappees via `AVATAR_ANIMATIONS`.

---

## Composition multi-layers

Chaque layer (body, hair, outfit, head_gear) utilise le **meme layout exact** :

```
body.png   (512x512)  — corps de base, skin tone
 + outfit.png (512x512) — tenue/armure, fond transparent
 + hair.png  (512x512)  — coiffure, fond transparent
 + head.png  (512x512)  — casque/coiffe, fond transparent
 = composite (512x512)  — resultat empile
```

### Regles d'alignement inter-layers

1. **Memes dimensions** : toutes les layers doivent etre 512x512 (ou multiple de la page)
2. **Meme grid** : 8 colonnes x 8 lignes, 64x64 par frame
3. **Memes animations** : chaque layer contient les memes rows et colonnes que le body
4. **Pixel-perfect** : le body et les layers doivent s'aligner exactement sur chaque frame
5. **Fond transparent** : les layers (hors body) ont un fond RGBA transparent
6. **Ancrage** : chaque frame est centree horizontalement, les pieds du personnage au bas de la frame

Les assets Mana Seed garantissent nativement ces regles : toutes les categories (`0bas`, `1out`, `4har`, `5hat`) partagent le meme canvas et les memes ancrages par construction.

### Ordre d'empilement (z-order)

```
Layer 1 (arriere) : body        (code Mana Seed : 0bas)
Layer 2           : outfit      (1out)
Layer 3           : hair        (4har)
Layer 4 (avant)   : head_gear   (5hat)
```

> Note : pour la phase MVP, l'outfit est une sheet complete (armure + bottes + jambieres fusionnees).
> Les micro-layers par slot (chest, leg, foot separes) sont prevus en Phase 6+.

### Tinting (coloration)

Certaines layers supportent le tinting via PixiJS :
- **hair** : couleur de cheveux (`tint: 0xd6b25e` pour blond, etc.)
- **body** : skin tone alternative (preferer des sheets distinctes v00-v03 plutot que le tint)

Le tint est applique en multiplicatif par `sprite.tint` dans PixiJS.

---

## Comparaison avec le format legacy

| Propriete | Legacy (RPG Maker VX) | Avatar (Mana Seed 8x8) |
|-----------|----------------------|------------------------|
| Grid | 3 cols x 4 rows | 8 cols x 8 rows |
| Frame size | 32x32 px | 64x64 px |
| Sheet size (single) | 96x128 px | 512x512 px |
| Animations | walk uniquement | stand, push, pull, jump, walk, run |
| Walk frames | 3 (left-foot, stand, right-foot) | 6 (cycle complet) |
| Idle | Breathing programmatique | 1 frame statique + breathing optionnel |
| Layers | Aucun (sprite composite) | body + outfit + hair + head_gear |
| Directions (order) | down / left / right / up | down / up / left / right |
| Type SpriteAnimator | `single` / `multi` | `avatar` |
| Utilise par | Mobs, PNJ | Joueurs |
| Coexistence | Inchange | Pipeline parallele |

---

## Integration dans SpriteAnimator (reference AVT-06)

Le type `avatar` s'ajoute comme nouvelle branche dans `SpriteAnimator.js` :

### _computeFrameSize()

```javascript
if (this._type === 'avatar') {
    this._frameW = 64;
    this._frameH = 64;
    this._cols = 8;
    this._totalRows = Math.floor(h / 64);
}
```

### _buildFrames()

```javascript
if (this._type === 'avatar') {
    for (let row = 0; row < this._totalRows; row++) {
        const rowTextures = [];
        for (let col = 0; col < 8; col++) {
            const frame = new PIXI.Rectangle(col * 64, row * 64, 64, 64);
            rowTextures.push(new PIXI.Texture({ source, frame }));
        }
        this._frames.push(rowTextures);
    }
}
```

### Compatibilite

- Le type `avatar` ne modifie **rien** au comportement des types `single` et `multi`
- L'ordre des directions avatar est propre au layout Mana Seed (down/up/left/right)
- Le cycle de walk utilise 6 frames (vs le pattern `[0,1,2,1]` du legacy)

---

## Ancrage et positionnement sur la carte (reference AVT-08)

Le frame avatar (64x64) est plus grand que le tile carte (32x32). Le positionnement doit :

1. **Centrer horizontalement** le sprite sur le tile :
   - offsetX = `(64 - 32) / 2 = 16 px` a gauche
2. **Aligner les pieds** au bas du tile :
   - offsetY = `64 - 32 = 32 px` vers le haut
3. **Ou appliquer un scale** : facteur `0.5` pour ramener le 64x64 en 32x32 sur la carte
   - Approche recommandee pour le MVP (simplicite, coherence visuelle avec les mobs legacy)

### Approche recommandee (scale)

```javascript
if (type === 'avatar') {
    sprite.scale.set(0.5);
    // Le sprite 64x64 est rendu en 32x32 sur la carte
    // Les details supplementaires sont visibles en zoom
}
```

> Note : dans une version future, le scale pourra etre augmente (ex: 0.75) si la carte passe en tiles plus grands ou en mode zoom.

---

## Specifications pour la creation d'assets

### Contraintes pour les artistes (assets custom)

1. **Canvas** : 512x512 px exactement
2. **Grid** : 8 colonnes x 8 lignes — chaque cellule = 64x64
3. **Layout** : identique a Mana Seed `char_a_p1` (cf. grille ci-dessus)
4. **Centre** : le personnage est centre horizontalement dans chaque frame
5. **Pieds** : les pieds du personnage touchent le bord inferieur de la frame (± 2 px de marge)
6. **Tete** : la tete ne depasse pas le bord superieur de la frame
7. **Palette** : coherente avec le style retro du jeu (pas de degrades photographiques)
8. **Transparence** : fond RGBA 100% transparent (pas de fond colore)
9. **Nommage** : `{categorie}_{style}_v{NN}.png` (ex: `human_v00.png`, `bob_v01.png`)

### Assets fournis (Mana Seed Character Base - free demo)

Les assets de reference sont deposes tels quels (sans reslice) :

```
assets/styles/images/avatar/
    body/
        human_v00.png        # Corps humain, teint 1
        human_v01.png        # Corps humain, teint 2
        human_v02.png        # Corps humain, teint 3
        human_v03.png        # Corps humain, teint 4
    hair/
        bob_v00.png          # Coupe courte, couleur 1
        bob_v01.png          # Coupe courte, couleur 2
        bob_v02.png          # Coupe courte, couleur 3
        bob_v03.png          # Coupe courte, couleur 4
        bob_v04.png          # Coupe courte, couleur 5
    outfit/
        forester_v01.png     # Tenue rodeur, palette 1
        forester_v02.png     # Tenue rodeur, palette 2
        forester_v03.png     # Tenue rodeur, palette 3
        forester_v04.png     # Tenue rodeur, palette 4
        forester_v05.png     # Tenue rodeur, palette 5
    head/
        pointy_v01.png       # Chapeau pointu, palette 1
        pointy_v02.png       # Chapeau pointu, palette 2
        pointy_v03.png       # Chapeau pointu, palette 3
        pointy_v04.png       # Chapeau pointu, palette 4
        pointy_v05.png       # Chapeau pointu, palette 5
```

### Source des assets

Les fichiers integres proviennent du pack **Mana Seed Character Base (free demo)** par Seliel the Shaper :
- Original : https://seliel-the-shaper.itch.io/character-base
- License : usage commercial et non commercial autorises
- Source dans le repo : `assets/styles/images/ManaSeedRPGStarterPack/character_base/char_a_p1/`
