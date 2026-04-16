# Layout du spritesheet avatar 8x8 — Specification AVT-02

> Specification du format de spritesheet pour le systeme d'avatar modulaire.
> Document de reference pour AVT-06 (SpriteAnimator type `avatar`) et la creation d'assets.
> Derniere mise a jour : 2026-04-16

---

## Vue d'ensemble

Le systeme avatar introduit un nouveau format de spritesheet **8 colonnes x 8 lignes** pour les personnages joueurs, coexistant avec le format legacy RPG Maker VX (3x4 / 12x8) utilise par les mobs et PNJ.

| Propriete | Valeur |
|-----------|--------|
| **Colonnes** | 8 |
| **Lignes (base)** | 8 |
| **Frame size** | 64x64 px |
| **Sheet size (base)** | 512x512 px |
| **Format image** | PNG 32-bit (RGBA, fond transparent) |
| **Animations base** | walk, stand (idle) |
| **Animations etendues** | run, jump, push, pull |
| **Directions** | 4 (down, left, right, up) |

### Justifications techniques

- **64x64 par frame** : 4x la resolution des sprites legacy (32x32), offre un rendu plus detaille pour les joueurs
- **512x512 (sheet)** : puissance de 2, optimal GPU (pas de padding ni waste VRAM)
- **8 frames par animation** : cycle fluide (vs 3 frames legacy), ameliore la qualite visuelle de la marche et de l'idle

---

## Layout de la grille 8x8 (sheet de base)

La sheet de base (512x512) contient **2 animations x 4 directions = 8 lignes** :

```
        Col 0    Col 1    Col 2    Col 3    Col 4    Col 5    Col 6    Col 7
       ┌────────┬────────┬────────┬────────┬────────┬────────┬────────┬────────┐
Row 0  │ Walk ↓ │ Walk ↓ │ Walk ↓ │ Walk ↓ │ Walk ↓ │ Walk ↓ │ Walk ↓ │ Walk ↓ │  walk — facing down
       ├────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
Row 1  │ Walk ← │ Walk ← │ Walk ← │ Walk ← │ Walk ← │ Walk ← │ Walk ← │ Walk ← │  walk — facing left
       ├────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
Row 2  │ Walk → │ Walk → │ Walk → │ Walk → │ Walk → │ Walk → │ Walk → │ Walk → │  walk — facing right
       ├────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
Row 3  │ Walk ↑ │ Walk ↑ │ Walk ↑ │ Walk ↑ │ Walk ↑ │ Walk ↑ │ Walk ↑ │ Walk ↑ │  walk — facing up
       ├────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
Row 4  │Stand ↓ │Stand ↓ │Stand ↓ │Stand ↓ │Stand ↓ │Stand ↓ │Stand ↓ │Stand ↓ │  stand — facing down
       ├────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
Row 5  │Stand ← │Stand ← │Stand ← │Stand ← │Stand ← │Stand ← │Stand ← │Stand ← │  stand — facing left
       ├────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
Row 6  │Stand → │Stand → │Stand → │Stand → │Stand → │Stand → │Stand → │Stand → │  stand — facing right
       ├────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
Row 7  │Stand ↑ │Stand ↑ │Stand ↑ │Stand ↑ │Stand ↑ │Stand ↑ │Stand ↑ │Stand ↑ │  stand — facing up
       └────────┴────────┴────────┴────────┴────────┴────────┴────────┴────────┘

Chaque cellule = 64x64 px
```

### Convention des directions (identique au legacy)

| Direction | Offset row | Description |
|-----------|-----------|-------------|
| down | +0 | Face camera (sud) |
| left | +1 | Profil gauche |
| right | +2 | Profil droit |
| up | +3 | Dos camera (nord) |

Pour une animation donnee, la row = `startRow + directionOffset`.

### Cycle de marche (walk, rows 0-3)

8 frames par direction formant un cycle complet :

```
Frame:   0      1      2      3      4      5      6      7
Pose:  contact mid-1  pass  mid-2  contact mid-1  pass  mid-2
Pied:  gauche  →     droit   →    gauche   →     droit   →
```

- **Frame 0, 4** : contact (pied au sol)
- **Frame 1, 5** : mi-phase montante
- **Frame 2, 6** : passage (pied en l'air)
- **Frame 3, 7** : mi-phase descendante
- Le cycle boucle naturellement : frame 7 → frame 0

### Animation idle (stand, rows 4-7)

8 frames d'animation subtile en boucle :

```
Frame:   0      1      2      3      4      5      6      7
Pose:  neutre  →     inspir  →    neutre   →    expir   →
```

- Mouvement subtil de respiration / leger balancement
- Frame 0 = pose de repos par defaut (utilisee comme idle statique si besoin)
- Le cycle boucle avec un rythme lent (~4 FPS pour un rendu naturel)

---

## Mapping des animations (AVATAR_ANIMATIONS)

Configuration JS pour `SpriteAnimator` type `avatar` (reference pour AVT-06) :

```javascript
const AVATAR_ANIMATIONS = {
    walk:  { startRow: 0,  frames: 8 },
    stand: { startRow: 4,  frames: 8 },
    // Etendues (disponibles si sheet height > 512)
    run:   { startRow: 8,  frames: 8 },
    jump:  { startRow: 12, frames: 8 },
    push:  { startRow: 16, frames: 8 },
    pull:  { startRow: 20, frames: 8 },
};

const AVATAR_DIRECTION_OFFSET = { down: 0, left: 1, right: 2, up: 3 };
```

Pour obtenir une frame specifique :

```javascript
const row = animation.startRow + AVATAR_DIRECTION_OFFSET[direction];
const col = frameIndex % animation.frames;
const x = col * 64;
const y = row * 64;
```

---

## Sheets etendues (optionnel)

Les animations supplementaires suivent le meme pattern de 4 rows (une par direction) :

### Sheet etendue 8x12 (512x768) — walk + stand + run

| Rows | Animation | Description |
|------|-----------|-------------|
| 0-3 | walk | Cycle de marche (identique a la base) |
| 4-7 | stand | Idle/respiration (identique a la base) |
| 8-11 | run | Course rapide (jambes ecartees, bras en mouvement) |

### Sheet etendue complete 8x24 (512x1536) — toutes les animations

| Rows | Animation | Description |
|------|-----------|-------------|
| 0-3 | walk | Cycle de marche standard |
| 4-7 | stand | Idle/respiration |
| 8-11 | run | Course rapide |
| 12-15 | jump | Saut (preparation → envol → apogee → atterrissage) |
| 16-19 | push | Pousser un objet (bras en avant, effort) |
| 20-23 | pull | Tirer un objet (bras en arriere, effort) |

### Detection automatique des animations disponibles

Le `SpriteAnimator` type `avatar` detecte les animations disponibles a partir de la hauteur :

```
height = 512  → 8 rows  → walk + stand
height = 768  → 12 rows → walk + stand + run
height = 1024 → 16 rows → walk + stand + run + jump
height = 1280 → 20 rows → walk + stand + run + jump + push
height = 1536 → 24 rows → walk + stand + run + jump + push + pull
```

Formule : `availableRows = sheetHeight / 64`

---

## Composition multi-layers

Chaque layer (body, hair, outfit, etc.) utilise le **meme layout exact** :

```
body.png   (512x512)  — corps de base, skin tone
 + hair.png  (512x512)  — coiffure, fond transparent
 + outfit.png (512x512) — tenue/armure, fond transparent
 + head.png  (512x512)  — casque/coiffe, fond transparent
 = composite (512x512)  — resultat empile
```

### Regles d'alignement inter-layers

1. **Memes dimensions** : toutes les layers d'une meme animation doivent avoir la meme taille (512x512 pour la base)
2. **Meme grid** : 8 colonnes x N lignes, 64x64 par frame
3. **Memes animations** : chaque layer contient les memes rows d'animation que le body
4. **Pixel-perfect** : le body et les layers doivent s'aligner exactement sur chaque frame
5. **Fond transparent** : les layers (hors body) ont un fond RGBA transparent ; seuls les pixels visibles sont dessines
6. **Ancrage** : chaque frame est centree horizontalement, les pieds du personnage au bas de la frame

### Ordre d'empilement (z-order)

```
Layer 1 (arriere) : body        — corps de base
Layer 2           : outfit      — armure / tenue (remplace le body visible)
Layer 3           : hair        — cheveux (par-dessus la tenue)
Layer 4 (avant)   : head_gear   — casque / coiffe (par-dessus les cheveux)
```

> Note : pour la phase MVP, l'outfit est une sheet complete (armure + bottes + jambieres fusionnees).
> Les micro-layers par slot (chest, leg, foot separes) sont prevus en Phase 6+.

### Tinting (coloration)

Certaines layers supportent le tinting via PixiJS :
- **hair** : couleur de cheveux (`tint: 0xd6b25e` pour blond, etc.)
- **beard** : meme couleur que les cheveux par defaut
- **body** : skin tone alternative (optionnel, preferer des sheets distinctes)

Le tint est applique en multiplicatif par `sprite.tint` dans PixiJS.

---

## Comparaison avec le format legacy

| Propriete | Legacy (RPG Maker VX) | Avatar (8x8) |
|-----------|----------------------|---------------|
| Grid | 3 cols x 4 rows | 8 cols x 8+ rows |
| Frame size | 32x32 px | 64x64 px |
| Sheet size (single) | 96x128 px | 512x512 px |
| Animations | walk uniquement | walk, stand, run, jump, push, pull |
| Walk frames | 3 (left-foot, stand, right-foot) | 8 (cycle complet) |
| Idle | Breathing programmatique | 8 frames animees |
| Layers | Aucun (sprite composite) | body + outfit + hair + head_gear |
| Directions | 4 (down/left/right/up) | 4 (identique) |
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
    // Construire les frames pour chaque row disponible
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
- La direction suit la meme convention (down=0, left=1, right=2, up=3)
- Le cycle de walk utilise les 8 frames sequentiellement (vs le pattern `[0,1,2,1]` du legacy)

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

### Contraintes pour les artistes

1. **Canvas** : 512x512 px exactement
2. **Grid** : 8 colonnes x 8 lignes (base) — chaque cellule = 64x64
3. **Centre** : le personnage est centre horizontalement dans chaque frame
4. **Pieds** : les pieds du personnage touchent le bord inferieur de la frame (± 2px de marge)
5. **Tete** : la tete ne depasse pas le bord superieur de la frame
6. **Palette** : coherente avec le style retro du jeu (pas de degrades photographiques)
7. **Transparence** : fond RGBA 100% transparent (pas de fond colore)
8. **Nommage** : `{categorie}_{style}_{variante}.png` (ex: `body_human_m_light.png`, `hair_short_01.png`)

### Modele vierge

Un template 512x512 avec la grille 8x8 visible peut etre genere pour guider les artistes :

```
512 x 512 px
├── 8 colonnes de 64 px
├── 8 lignes de 64 px
├── Guides de centre (colonne 32 de chaque cellule)
└── Guides de pieds (ligne 62 de chaque cellule, 2px de marge)
```

### Structure des fichiers attendue (AVT-03)

```
assets/styles/images/avatar/
    body/
        human_m_light.png       # Corps masculin peau claire
        human_m_dark.png        # Corps masculin peau foncee
        human_f_light.png       # Corps feminin peau claire
        human_f_dark.png        # Corps feminin peau foncee
    hair/
        short_01.png            # Cheveux courts
        long_01.png             # Cheveux longs
        ponytail_01.png         # Queue de cheval
    outfit/
        starter_tunic.png       # Tenue de depart
        leather_armor.png       # Armure de cuir
        iron_armor.png          # Armure de fer
    head/
        leather_cap.png         # Casque cuir
        iron_helm.png           # Casque fer
```
