# Inventaire des assets avatar — AVT-01

> Inventaire complet des sprites personnage disponibles dans le projet.
> Genere le 2026-04-16. Reference pour la creation du systeme avatar modulaire.

---

## Format actuel des sprites

Tous les sprites individuels utilisent le format **RPG Maker VX** :
- **Dimensions sheet** : 96x128 px (3 colonnes x 4 lignes)
- **Taille par frame** : 32x32 px
- **Layout** : down (row 0), left (row 1), right (row 2), up (row 3)
- **Cycle de marche** : col 0 (pied gauche), col 1 (idle), col 2 (pied droit)

Les multi-sheets suivent le meme format en 12 colonnes x 8 lignes (8 personnages de 3x4).

---

## Sprites individuels (single, 96x128)

### Male — 18 personnages, 69 fichiers

| Base | Variantes | Fichiers |
|------|-----------|----------|
| Male 01 | 4 | Male 01-1.png ... Male 01-4.png |
| Male 02 | 4 | Male 02-1.png ... Male 02-4.png |
| Male 03 | 4 | Male 03-1.png ... Male 03-4.png |
| Male 04 | 4 | Male 04-1.png ... Male 04-4.png |
| Male 05 | 4 | Male 05-1.png ... Male 05-4.png |
| Male 06 | 4 | Male 06-1.png ... Male 06-4.png |
| Male 07 | 4 | Male 07-1.png ... Male 07-4.png |
| Male 08 | 4 | Male 08-1.png ... Male 08-4.png |
| Male 09 | 4 | Male 09-1.png ... Male 09-4.png |
| Male 10 | 4 | Male 10-1.png ... Male 10-4.png |
| Male 11 | 4 | Male 11-1.png ... Male 11-4.png |
| Male 12 | 4 | Male 12-1.png ... Male 12-4.png |
| Male 13 | 4 | Male 13-1.png ... Male 13-4.png |
| Male 14 | 4 | Male 14-1.png ... Male 14-4.png |
| Male 15 | 4 | Male 15-1.png ... Male 15-4.png |
| Male 16 | 4 | Male 16-1.png ... Male 16-4.png |
| Male 17 | 4 | Male 17-1.png ... Male 17-4.png |
| Male 18 | 1 | Male 18-1.png |

**Total** : 69 fichiers, 100% au format 96x128.

### Female — 25 personnages, 91 fichiers

| Base | Variantes | Fichiers |
|------|-----------|----------|
| Female 01-22 | 4 chacun | Female XX-1.png ... Female XX-4.png |
| Female 23 | 1 | Female 23-1.png |
| Female 24 | 1 | Female 24-1.png |
| Female 25 | 1 | Female 25-1.png |

**Total** : 91 fichiers (22 bases x 4 + 3 bases x 1), 100% au format 96x128.

### Soldier — 7 personnages, 28 fichiers

| Base | Variantes | Fichiers |
|------|-----------|----------|
| Soldier 01-07 | 4 chacun | Soldier XX-1.png ... Soldier XX-4.png |

**Total** : 28 fichiers (7 bases x 4), 100% au format 96x128.

---

## Multi-character sheets

| Fichier | Dimensions | Frame | Personnages | Coherent |
|---------|-----------|-------|-------------|----------|
| Midona.png | 384x256 | 32x32 | 8 | OK |
| monk.png | 384x256 | 32x32 | 8 | OK |
| DwarfSprites2.png-par-Terra-chan.png | 384x256 | 32x32 | 8 | OK |
| Spiritual for paint 1.png | 384x256 | 32x32 | 8 | OK |
| TechsheetB.png-par-Kazzador.png | 384x280 | 32x35 | 8 | **NON** (h=280, attendu 256) |
| palett13.png | 384x304 | 32x38 | 8 | **NON** (h=304, attendu 256) |

**Note** : TechsheetB et palett13 ont des hauteurs non standard. Pas utilises actuellement dans `SpriteConfigProvider`.

---

## Monstres (single, 96x128)

| Categorie | Fichiers | Format |
|-----------|----------|--------|
| Enemy 01-1 ... Enemy 17-8 | 38 fichiers | 96x128 (OK) |
| Multi-sheets (EnemySpriteSheet1, abeille, zombies, etc.) | 9 fichiers | 384x256 (OK) |
| Exceptions | rcLn5dzAi.png (761x1069), SpriteCompEB (288x192), enemy-sprite-png-2 (320x256) | Non standard |

---

## Sprites utilises en jeu (SpriteConfigProvider)

### Joueurs (7 clefs)
- `player_default` → Male 01-2.png
- `player_male_01` → Male 01-2.png
- `player_male_02` → Male 02-1.png
- `player_male_03` → Male 03-2.png
- `player_female_01` → Female 01-1.png
- `player_female_02` → Female 02-1.png
- `player_soldier_01` → Soldier 01-1.png

### PNJ (11 clefs)
- `pnj_default`, `pnj_villager`, `pnj_merchant`, `pnj_guard`, `pnj_noble`, `pnj_warrior`, `pnj_mage`, `pnj_healer`, `pnj_blacksmith`, `pnj_farmer`, `pnj_hunter`

### Monstres (21 clefs)
- 4 via multi-sheet (demons.png, index 0-3)
- 17 via single-sheet (Enemy 01-1 ... Enemy 17-5)

---

## Verification de coherence inter-layers

### Sprites individuels (single)
- **188 fichiers** : 100% au format 96x128 → **COHERENT**
- Frame size : 32x32 uniforme → **COHERENT**

### Multi-character sheets
- **4 sur 6** au format standard 384x256 (32x32 frames) → **COHERENT**
- **2 exceptions** (TechsheetB, palett13) avec hauteur non standard → **NON UTILISES** en jeu

### Conclusion coherence
Tous les assets **utilises en jeu** respectent le meme format :
- Single : 96x128, frame 32x32
- Multi : 384x256, frame 32x32

---

## Assets modulaires avatar — Etat actuel

**Aucun asset modulaire n'existe encore.**

Le repertoire `assets/styles/images/avatar/` n'est pas cree. Les sprites actuels sont des personnages complets (body + vetements + cheveux fusionnes dans une seule image).

### Structure cible (a creer dans AVT-03)

```
assets/styles/images/avatar/
  base/           # Corps nus (body) — 96x128, meme layout RPG Maker
    human_m_light_01.png
    human_f_light_01.png
  hair/           # Coiffures transparentes superposables
    short_01.png
    ponytail_01.png
  beard/          # Barbes
    short_beard_01.png
  face/           # Marques de visage (cicatrices, tatouages)
    scar_eye_left_01.png
  gear/           # Equipement visible, par slot
    head/         # Casques, chapeaux
    chest/        # Armures, tuniques
    belt/         # Ceintures
    leg/          # Pantalons, jambieres
    foot/         # Bottes
    shoulder/     # Epaulieres
    main_weapon/  # Armes principales
    side_weapon/  # Boucliers, armes secondaires
  tools/          # Outils de recolte
    tool_pickaxe_bronze.png
```

### Format requis pour les layers
Chaque layer doit etre une sprite sheet **96x128** (3x4, 32x32 par frame) avec fond transparent, parfaitement alignee sur le body de base. La composition se fait par superposition alpha dans `AvatarTextureComposer.js`.

### Variantes par categorie a prevoir (liste ci-dessous pour la creation graphique)

| Categorie | Variantes MVP |
|-----------|--------------|
| Body (base) | 2 (humain M, humain F) + teintes de peau via tint |
| Hair | 3-5 styles |
| Beard | 1-2 styles |
| Face mark | 1-2 (cicatrice, tatouage) |
| Head gear | 2 (casque cuir, casque fer) |
| Chest | 2-3 (tunique, armure cuir, armure fer) |
| Leg | 2 (pantalon, jambieres) |
| Foot | 1-2 (bottes) |
| Main weapon | 2 (epee, baton) |
| Side weapon | 1 (bouclier) |

---

## Erreurs de documentation identifiees

1. **ASSETS.md** : dimensions documentees comme 72x128 / 24x32 mais la realite est **96x128 / 32x32** → a corriger
2. **ASSETS.md** : reference `MapApiController::getSpriteConfig()` mais la config est dans `SpriteConfigProvider` → a corriger
3. **PLAN_AVATAR_SYSTEM.md** : mentionne "72x128, 24x32 par frame" → dimensions reelles 96x128, 32x32
4. **SpriteConfigProvider** : constante `FRAME_H = 48` mais les frames reelles sont 32x32 → a verifier l'usage
