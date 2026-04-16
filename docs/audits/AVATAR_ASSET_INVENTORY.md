# Inventaire des assets avatar

> Genere le 2026-04-16 par `php bin/console app:avatar:inventory --export`
> Tache roadmap : AVT-01 (Sprint 7)

---

## Resume

- **Total fichiers scannes** : 248
- **Categories actives** : 7 (sur 10 scannees)
- **Avatar 8x8 prets** : 0/4 repertoires

---

## Personnages masculins

69 fichier(s) — `character/Male/`

| Modele | Variantes | Dimensions | Format |
|--------|-----------|-----------|--------|
| Male 01 | 01-1, 01-2, 01-3, 01-4 | 96x128 | single 3x4 (32x32/frame) |
| Male 02 | 02-1, 02-2, 02-3, 02-4 | 96x128 | single 3x4 (32x32/frame) |
| Male 03 | 03-1, 03-2, 03-3 | 96x128 | single 3x4 (32x32/frame) |
| Male 04–18 | 1 a 4 variantes chacun | 96x128 | single 3x4 (32x32/frame) |

**Coherence** : OK — toutes en 96x128

---

## Personnages feminins

91 fichier(s) — `character/Female/`

| Modele | Variantes | Dimensions | Format |
|--------|-----------|-----------|--------|
| Female 01 | 01-1, 01-2, 01-3, 01-4 | 96x128 | single 3x4 (32x32/frame) |
| Female 02–12 | 1 a 4 variantes chacun | 96x128 | single 3x4 (32x32/frame) |

**Coherence** : OK — toutes en 96x128

---

## Soldats

28 fichier(s) — `character/Soldier/`

| Modele | Dimensions | Format |
|--------|-----------|--------|
| Soldier 01-1 a Soldier 07-4 | 96x128 | single 3x4 (32x32/frame) |

**Coherence** : OK — toutes en 96x128

---

## Monstres

47 fichier(s) — `monster/`

| Sous-groupe | Fichiers | Dimensions | Format |
|-------------|----------|-----------|--------|
| Enemy 01-1 a Enemy 17-5 (single) | 36 | 96x128 | single 3x4 (32x32/frame) |
| Multi-sheets | 8 | 384x256 | multi 12x8 (32x32/frame) |
| Colormap single | 6 | 96x128 | single 3x4 (32x32/frame, 4-bit) |
| Non standard | 1 | 288x192 | autre |
| Non standard | 1 | 320x256 | autre |
| Non standard | 1 | 761x1069 | autre |

**Coherence** : ATTENTION — 6 tailles differentes (96x128, 384x256, 288x192, 320x256, 761x1069)

---

## Boss

1 fichier(s) — `Boss/`

| Fichier | Dimensions | Format |
|---------|-----------|--------|
| `Boss 01.png` | 288x384 | single 3x4 (96x96/frame) |

**Coherence** : OK — un seul fichier

---

## Animaux

6 fichier(s) — `Animal/`

| Fichier | Dimensions | Format |
|---------|-----------|--------|
| `Cat 01-1.png` a `Cat 01-3.png` | 96x128 | single 3x4 (32x32/frame) |
| `Dog 01-1.png` a `Dog 01-3.png` | 96x128 | single 3x4 (32x32/frame) |

**Coherence** : OK — toutes en 96x128

---

## Sheets multi-personnages (racine)

7 fichier(s)

| Fichier | Dimensions | Format |
|---------|-----------|--------|
| `character/DwarfSprites2.png-par-Terra-chan.png` | 384x256 | multi 12x8 (32x32/frame) |
| `character/Midona.png` | 384x256 | multi 12x8 (32x32/frame) |
| `character/Spiritual for paint 1.png` | 384x256 | multi 12x8 (32x32/frame) |
| `character/TechsheetB.png-par-Kazzador.png` | 384x280 | autre |
| `character/monk.png` | 384x256 | multi 12x8 (32x32/frame) |
| `character/palett13.png` | 384x304 | autre |
| `demons.png` | 384x256 | multi 12x8 (32x32/frame) |

**Coherence** : ATTENTION — 3 tailles differentes (384x256, 384x280, 384x304)

---

## Avatar : corps

> Repertoire `avatar/body/` inexistant.

## Avatar : cheveux

> Repertoire `avatar/hair/` inexistant.

## Avatar : tenues

> Repertoire `avatar/outfit/` inexistant.

## Avatar : coiffes

> Repertoire `avatar/head/` inexistant.

---

## Alertes

- Repertoire manquant : avatar/body/ (necessaire pour le systeme avatar)
- Repertoire manquant : avatar/hair/ (necessaire pour le systeme avatar)
- Repertoire manquant : avatar/outfit/ (necessaire pour le systeme avatar)
- Repertoire manquant : avatar/head/ (necessaire pour le systeme avatar)
- Incoherence de taille dans Monstres : 6 tailles differentes
- Incoherence de taille dans Sheets multi-personnages (racine) : 3 tailles differentes

---

## Analyse pour le systeme avatar

Le systeme avatar modulaire (voir `docs/roadmap/PLAN_AVATAR_SYSTEM.md`) necessite des assets
au format **8 colonnes x 8 lignes** avec layers separees (body, hair, outfit, head).

### Assets existants (format RPG Maker VX)

- **188 sprites personnages** single 3x4 (32x32/frame) pour joueurs, PNJ, soldats
- **5 sheets multi-personnages** 12x8 (32x32/frame) pour mobs et personnages additionnels
- **47 sprites monstres** dont 36 single 3x4 et 8 multi 12x8
- **1 boss** en 3x4 agrandi (96x96/frame)
- **6 animaux** single 3x4

### Constats

- Frame size reelle : **32x32** (96/3 = 32, 128/4 = 32) — a noter que ASSETS.md indique 24x32 et SpriteConfigProvider declare 32x48, a corriger
- Tous les personnages jouables sont au meme format : 96x128 (coherence OK)
- Pas de layers separees : body + outfit + hair sont integres dans chaque sprite composite
- Les variantes (ex: Male 01-1 a 01-4) representent des changements de tenue/couleur, pas des layers

### Manquant pour le systeme avatar 8x8

- [ ] Assets body de base (skin tones, genres) dans `avatar/body/`
- [ ] Assets coiffures dans `avatar/hair/`
- [ ] Assets tenues/armures dans `avatar/outfit/`
- [ ] Assets coiffes/casques dans `avatar/head/`
- [ ] Documentation du layout exact des frames 8x8 (AVT-02)
- [ ] Verification alignement pixel-perfect entre layers (AVT-04)
