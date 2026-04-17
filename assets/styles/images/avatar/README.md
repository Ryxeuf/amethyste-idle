# Assets avatar modulaire

Ce repertoire accueille les sprite sheets du systeme d'avatar modulaire 8x8.
Chaque categorie (body, hair, outfit, head) contient des layers compatibles
qui sont composes a la volee par `AvatarTextureComposer.js`.

Les fichiers fournis proviennent du pack Mana Seed Character Base (free demo)
par Seliel the Shaper : https://seliel-the-shaper.itch.io/character-base

## Format requis

- **Dimensions** : 512x512 px (page 1, `char_a_p1`)
- **Grille** : 8 colonnes x 8 lignes — chaque cellule = 64x64 px
- **Format image** : PNG 32-bit (RGBA, fond transparent obligatoire pour les layers)
- **Layout** : layout natif Mana Seed (rows 0-3 = animations courtes, rows 4-7 = walk/run)
- **Ordre des directions** : down, up, left, right (specifique Mana Seed, ≠ legacy)

Specification complete : [`docs/avatar-spritesheet-layout.md`](../../../../docs/avatar-spritesheet-layout.md).

## Repertoires

| Dossier | Contenu | Tinting |
|---------|---------|---------|
| `body/` | Corps de base (skin tone, code Mana Seed `0bas`) | Optionnel via `sprite.tint` |
| `hair/` | Coiffures (`4har`) | Oui (`hairColor` dans l'apparence joueur) |
| `outfit/` | Tenues / armures (`1out`, chest + leg + foot fusionnes en MVP) | Non |
| `head/` | Casques / coiffes (`5hat`) | Non |

## Convention de nommage

`{style}_v{NN}.png`

Exemples :
- `body/human_v00.png` — corps humain, teint 1
- `body/human_v03.png` — corps humain, teint 4
- `hair/bob_v00.png` — cheveux bob variante 1
- `outfit/forester_v01.png` — tenue rodeur palette 1
- `head/pointy_v01.png` — chapeau pointu palette 1

## Catalogue fourni (MVP)

- **body** : `human_v00.png` a `human_v03.png` (4 peaux)
- **outfit** : `forester_v01.png` a `forester_v05.png` (5 tenues rodeur)
- **hair** : `bob_v00.png` a `bob_v04.png` (5 couleurs de coupe bob)
- **head** : `pointy_v01.png` a `pointy_v05.png` (5 chapeaux pointus)

Total : 19 fichiers, ~300 Ko.

## Regles d'alignement (AVT-04)

Toutes les layers d'une meme animation doivent :
1. Avoir des dimensions identiques (512x512, grille 8x8)
2. Centrer le personnage horizontalement dans chaque cellule 64x64
3. Aligner les pieds au bas de la cellule (± 2 px de marge)
4. Utiliser un fond RGBA transparent (sauf body)
5. S'aligner pixel-perfect entre body, outfit, hair, head

> Les assets Mana Seed integres satisfont ces regles par construction : tous
> les layers (`0bas`, `1out`, `4har`, `5hat`) de la page 1 partagent le meme
> canvas et les memes ancrages (verifie AVT-04).

## Z-order de composition

```
1. body       (arriere) — corps de base
2. outfit              — tenue / armure
3. hair                — cheveux
4. head_gear  (avant)  — casque / coiffe
```

## Catalogue exploitable

Le service `PlayerAvatarPayloadBuilder` consomme ces sheets via les chemins
declares dans `Item.avatarSheet` (pour les equipements) et dans le champ
`Player.avatarAppearance` (pour body / hair / outfit / head_gear de base).

Sans `avatarAppearance` defini, les joueurs retombent automatiquement sur le
pipeline legacy (`renderMode: 'legacy'` dans `/api/map/entities`).
