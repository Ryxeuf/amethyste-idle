# Assets avatar modulaire

Ce repertoire accueille les sprite sheets du systeme d'avatar modulaire 8x8.
Chaque categorie (body, hair, outfit, head) contient des layers compatibles
qui sont composes a la volee par `AvatarTextureComposer.js`.

## Format requis

- **Dimensions** : 512x512 px (sheet de base) ou 512x768 / 1024 / 1280 / 1536 (sheets etendues)
- **Grille** : 8 colonnes x 8 lignes (base) — chaque cellule = 64x64 px
- **Format image** : PNG 32-bit (RGBA, fond transparent obligatoire pour les layers)
- **Animations base** : walk (rows 0-3) + stand (rows 4-7), une row par direction (down/left/right/up)
- **Animations etendues** : run, jump, push, pull (rows 8+, optionnel)

Specification complete : [`docs/avatar-spritesheet-layout.md`](../../../../docs/avatar-spritesheet-layout.md).

## Repertoires

| Dossier | Contenu | Tinting |
|---------|---------|---------|
| `body/` | Corps de base (skin tone) | Optionnel via `sprite.tint` |
| `hair/` | Coiffures | Oui (`hairColor` dans l'apparence joueur) |
| `outfit/` | Tenues / armures (chest + leg + foot fusionnes en MVP) | Non |
| `head/` | Casques / coiffes | Non |

## Convention de nommage

`{categorie}_{style}_{variante}.png`

Exemples :
- `body/human_m_light.png` — corps masculin peau claire
- `body/human_f_dark.png` — corps feminin peau foncee
- `hair/short_01.png` — cheveux courts variante 1
- `outfit/starter_tunic.png` — tenue de depart
- `head/leather_cap.png` — casque cuir

## Regles d'alignement (AVT-04)

Toutes les layers d'une meme animation doivent :
1. Avoir des dimensions identiques (memes rows, meme grille 8x8)
2. Centrer le personnage horizontalement dans chaque cellule 64x64
3. Aligner les pieds au bas de la cellule (± 2 px de marge)
4. Utiliser un fond RGBA transparent (sauf body)
5. S'aligner pixel-perfect entre body, outfit, hair, head

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

Tant qu'aucune sheet 8x8 reelle n'est disponible, les joueurs sans
`avatarAppearance` retombent automatiquement sur le pipeline legacy
(`renderMode: 'legacy'` dans `/api/map/entities`).
