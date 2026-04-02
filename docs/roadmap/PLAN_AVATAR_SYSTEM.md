# Plan — Systeme d'avatar modulaire

> Roadmap d'integration du systeme d'avatar modulaire pour les personnages joueurs.
> Source : `data/amethyste-avatar-pack/` (blueprint architecture + code MVP).
> Derniere mise a jour : 2026-04-02

---

## Contexte

Aujourd'hui, tous les joueurs utilisent `spriteKey: 'player_default'` (hard-code dans `MapApiController`).
L'objectif est de permettre un **avatar composite par couches** (body, cheveux, barbe, equipement visible)
tout en conservant le pipeline existant pour mobs et PNJ.

### Principe architectural

```
layers modulaires (72x128 chacun)
  -> composition client PixiJS (RenderTexture)
    -> SpriteAnimator existant (zero modification)
      -> cache LRU par avatarHash
```

### Ce qui existe deja

| Element | Etat |
|---------|------|
| `SpriteAnimator.js` | Operationnel, format RPG Maker 3x4 |
| `SpriteConfigProvider.php` | Registre sprites hard-code |
| `/api/map/entities` | Renvoie `spriteKey` pour chaque entite |
| `/api/map/config` | Expose le catalogue sprites |
| Systeme d'equipement (`PlayerItem`, bitmask slots) | Complet |
| `GearHelper` | Getters par slot (head, chest, foot, etc.) |
| `Race.spriteSheet` | Champ existant mais non exploite |

### Ce que fournit le blueprint (`data/amethyste-avatar-pack/`)

| Fichier | Role |
|---------|------|
| `src/Service/Avatar/AvatarHashGenerator.php` | Hash SHA256 deterministe |
| `src/Service/Avatar/PlayerAvatarPayloadBuilder.php` | Construction payload API |
| `assets/lib/avatar/AvatarTextureComposer.js` | Composition layers -> RenderTexture |
| `assets/lib/avatar/AvatarSpriteSheetCache.js` | Cache LRU textures composites |
| `assets/lib/avatar/AvatarAnimatorFactory.js` | Factory legacy/avatar -> SpriteAnimator |
| `docs/avatar-modulaire-amethyste-idle.md` | Spec complete (626 lignes) |

---

## Legende

| Symbole | Signification |
|---------|---------------|
| S / M / L | Complexite (Small < Medium < Large) |
| ★★★ | Gain gameplay/visuel fort |
| ★★ | Gain moyen |
| ★ | Gain faible |
| ∅ | Aucun prerequis |
| ← AVT-XX | Depend de la tache XX |
| ‖ | Parallelisable |

---

## Phase 1 — Fondations backend (priorite absolue)

> Objectif : stocker l'apparence, generer le hash, exposer le payload API.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-01 | **Ajouter les champs avatar sur `Player`** | M | ★★★ | ∅ |
| | `avatarAppearance` (JSON nullable), `avatarHash` (string 64), `avatarVersion` (int), `avatarUpdatedAt` (datetime) | | | |
| | Migration Doctrine + valeurs par defaut pour joueurs existants | | | |
| AVT-02 | **Integrer `AvatarHashGenerator`** | S | ★★ | ∅ |
| | Copier depuis blueprint, adapter namespace, enregistrer comme service | | | |
| AVT-03 | **Integrer `PlayerAvatarPayloadBuilder`** | M | ★★★ | ← AVT-01, AVT-02 |
| | Adapter `extractAppearance()` pour lire les vrais champs Player | | | |
| | Adapter `resolveSheetFromItem()` pour utiliser un champ `avatarSheet` sur Item | | | |
| | Brancher sur `GearHelper` pour les items equipes | | | |
| AVT-04 | **Ajouter `avatarSheet` sur `Item`** | S | ★★ | ∅ |
| | Champ nullable string : chemin vers le sprite sheet du layer visuel de l'item | | | |
| | Migration Doctrine | | | |
| AVT-05 | **Enrichir `/api/map/entities`** | M | ★★★ | ← AVT-03 |
| | Joueurs : ajouter `renderMode`, `avatarHash`, `avatar` (baseSheet + layers) | | | |
| | Conserver `spriteKey` en fallback (`renderMode: 'legacy'` si pas d'avatar) | | | |
| AVT-06 | **Enrichir `/api/map/config`** | S | ★★ | ← AVT-04 |
| | Ajouter `avatarCatalog` : liste des sheets avatar a precharger | | | |

---

## Phase 2 — Integration frontend

> Objectif : composer les textures cote client et les animer via SpriteAnimator.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-07 | **Integrer `AvatarTextureComposer.js`** | S | ★★★ | ∅ |
| | Copier depuis blueprint dans `assets/lib/avatar/` | | | |
| | Verifier compatibilite PixiJS v8 (RenderTexture API) | | | |
| AVT-08 | **Integrer `AvatarSpriteSheetCache.js`** | S | ★★ | ∅ |
| | Copier depuis blueprint, cache LRU 128 entrees | | | |
| AVT-09 | **Integrer `AvatarAnimatorFactory.js`** | S | ★★★ | ← AVT-07, AVT-08 |
| | Copier depuis blueprint, adapter les imports | | | |
| | Deux chemins : legacy (spriteKey) et avatar (composite) | | | |
| AVT-10 | **Modifier `map_pixi_controller.js`** | M | ★★★ | ← AVT-05, AVT-09 |
| | Instancier `AvatarAnimatorFactory` au chargement | | | |
| | Remplacer `_createAnimator()` par `_createAnimatorForEntity()` | | | |
| | Gerer le preload des sheets avatar depuis `avatarCatalog` | | | |
| AVT-11 | **Gerer le joueur local** | S | ★★ | ← AVT-10 |
| | Le joueur courant (`self: true`) utilise aussi le pipeline avatar | | | |
| | Invalidation du cache quand l'equipement change | | | |

---

## Phase 3 — Assets graphiques MVP

> Objectif : creer le set minimum d'assets pour un rendu fonctionnel.
> Format : 72x128 pixels, layout RPG Maker 3x4 (24x32 par frame).
> Arborescence cible : `assets/styles/images/avatar/`

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-12 | **Body de base** | M | ★★★ | ∅ |
| | `base/human_m_light_01.png` — silhouette masculine | | | |
| | `base/human_f_light_01.png` — silhouette feminine (optionnel MVP) | | | |
| AVT-13 | **Coiffures (3-5 variantes)** | M | ★★ | ‖ |
| | `hair/short_01.png`, `hair/ponytail_01.png`, `hair/long_01.png` | | | |
| | Support tint couleur via le champ `hairColor` | | | |
| AVT-14 | **Barbe (1 variante)** | S | ★ | ‖ |
| | `beard/short_beard_01.png` avec support tint | | | |
| AVT-15 | **Marque faciale (1 variante)** | S | ★ | ‖ |
| | `face/scar_eye_left_01.png` | | | |
| AVT-16 | **Gear : torse (2 variantes)** | M | ★★★ | ‖ |
| | `gear/chest/tunic_01.png`, `gear/chest/iron_armor_01.png` | | | |
| AVT-17 | **Gear : jambes (2 variantes)** | M | ★★ | ‖ |
| | `gear/leg/pants_01.png`, `gear/leg/plate_legs_01.png` | | | |
| AVT-18 | **Gear : pieds, tete, arme, bouclier** | M | ★★ | ‖ |
| | `gear/foot/boots_01.png` | | | |
| | `gear/head/leather_cap_01.png` | | | |
| | `gear/main_weapon/sword_iron_01.png` | | | |
| | `gear/side_weapon/shield_wood_01.png` | | | |

---

## Phase 4 — Apparence joueur & creation de personnage

> Objectif : permettre au joueur de choisir son apparence.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-19 | **Ecran de creation de personnage** | L | ★★★ | ← AVT-01, AVT-12..18 |
| | Choix body (race/genre), coiffure, couleur cheveux, barbe, marque faciale | | | |
| | Preview temps reel avec composition PixiJS | | | |
| | Sauvegarde dans `avatarAppearance` JSON | | | |
| AVT-20 | **Lier la Race au body de base** | S | ★★ | ← AVT-19 |
| | Utiliser `Race.spriteSheet` pour determiner le body de base | | | |
| | Variantes par race (humain, elfe, nain, etc.) | | | |
| AVT-21 | **Ecran de personnalisation (post-creation)** | M | ★★ | ← AVT-19 |
| | Modifier apparence apres creation (coiffeur PNJ ou menu) | | | |
| | Recalcul du hash + persistence | | | |

---

## Phase 5 — Equipement visible & Mercure

> Objectif : l'equipement change le rendu visuel, les autres joueurs voient les mises a jour en temps reel.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-22 | **Peupler `avatarSheet` sur les items existants** | M | ★★★ | ← AVT-04, AVT-16..18 |
| | Associer chaque item d'equipement a son sprite sheet avatar | | | |
| | Mettre a jour les fixtures | | | |
| AVT-23 | **Recalcul automatique du hash** | S | ★★ | ← AVT-03 |
| | EventSubscriber sur changement d'equipement | | | |
| | Recalcul `avatarHash` + `avatarUpdatedAt` | | | |
| AVT-24 | **Publication Mercure `player.avatar.updated`** | M | ★★★ | ← AVT-23 |
| | Quand le hash change : publier le nouveau payload avatar | | | |
| | Le client invalide le cache et recompose la texture | | | |
| AVT-25 | **Gestion cote client des updates Mercure** | M | ★★ | ← AVT-10, AVT-24 |
| | Ecouter `player.avatar.updated` dans le controleur map | | | |
| | Invalider le cache + recreer l'animator pour le joueur concerne | | | |

---

## Phase 6 — Polish & extensions

> Objectif : ameliorations visuelles et qualite de vie.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-26 | **Paper doll dans l'inventaire** | L | ★★★ | ← AVT-10, AVT-22 |
| | Preview du personnage equipe dans l'ecran d'inventaire | | | |
| | Composition PixiJS dans un canvas dedie | | | |
| AVT-27 | **Outils visibles (gathering)** | S | ★ | ← AVT-22 |
| | Afficher pioche/faucille/canne a peche quand outil equipe | | | |
| | `tools/tool_pickaxe_bronze.png`, etc. | | | |
| AVT-28 | **Lazy loading intelligent** | M | ★★ | ← AVT-10 |
| | Precharger uniquement les sheets des joueurs visibles | | | |
| | Lazy load les sheets rares au besoin | | | |
| AVT-29 | **Cache IndexedDB** | M | ★★ | ← AVT-08 |
| | Persister les textures composites entre sessions | | | |
| | Invalidation par `avatarHash` | | | |
| AVT-30 | **Variantes raciales & cosmetiques** | L | ★★ | ← AVT-20 |
| | Bodies supplementaires par race | | | |
| | Coiffures et barbes supplementaires | | | |
| | Options cosmetiques premium (futurs) | | | |

---

## Graphe de dependances

```
Phase 1 — Backend
  AVT-01 (Player fields)  ──────┐
  AVT-02 (HashGenerator)  ──┐   │
  AVT-04 (Item avatarSheet) │   │
                             ├───┤
  AVT-03 (PayloadBuilder) ──┘   │
       │                         │
  AVT-05 (API entities) ────────┘
  AVT-06 (API config) ← AVT-04

Phase 2 — Frontend
  AVT-07 (Composer) ──┐
  AVT-08 (Cache)    ──┤
                       ├─ AVT-09 (Factory) ─┐
                       │                     │
  AVT-05 ──────────────┼────────────────────┤
                       │                     │
                       └─ AVT-10 (Controller)┘
                              │
                         AVT-11 (Local player)

Phase 3 — Assets (‖ parallelisable)
  AVT-12..18 (sprites)  ← production graphique

Phase 4 — Creation personnage
  AVT-19 (Ecran creation) ← AVT-01, AVT-12+
  AVT-20 (Race -> body)   ← AVT-19
  AVT-21 (Personnalisation) ← AVT-19

Phase 5 — Equipement visible & temps reel
  AVT-22 (Peupler avatarSheet) ← AVT-04, assets
  AVT-23 (Recalcul hash)  ← AVT-03
  AVT-24 (Mercure publish) ← AVT-23
  AVT-25 (Mercure client)  ← AVT-10, AVT-24

Phase 6 — Polish
  AVT-26 (Paper doll inventaire) ← AVT-10, AVT-22
  AVT-27 (Outils visibles)       ← AVT-22
  AVT-28 (Lazy loading)          ← AVT-10
  AVT-29 (Cache IndexedDB)       ← AVT-08
  AVT-30 (Variantes raciales)    ← AVT-20
```

---

## Ordre d'empilement des couches (z-order)

Ordre recommande pour la composition des layers (du fond vers le devant) :

1. `body` — silhouette de base
2. `leg` — pantalon / jambieres
3. `foot` — bottes
4. `chest` — torse / armure
5. `belt` — ceinture
6. `shoulder` — epaulieres
7. `head` — casque / chapeau
8. `beard` — barbe
9. `face_mark` — cicatrice / tatouage
10. `main_weapon` — arme principale
11. `side_weapon` — bouclier / arme secondaire

---

## Mapping slots equipement -> layers visuels

| Constante Item | Layer avatar |
|----------------|-------------|
| `GEAR_LOCATION_HEAD` | `gear/head/` |
| `GEAR_LOCATION_CHEST` | `gear/chest/` |
| `GEAR_LOCATION_BELT` | `gear/belt/` |
| `GEAR_LOCATION_LEG` | `gear/leg/` |
| `GEAR_LOCATION_FOOT` | `gear/foot/` |
| `GEAR_LOCATION_SHOULDER` | `gear/shoulder/` |
| `GEAR_LOCATION_MAIN_WEAPON` | `gear/main_weapon/` |
| `GEAR_LOCATION_SIDE_WEAPON` | `gear/side_weapon/` |

---

## Risques identifies

| Risque | Mitigation |
|--------|-----------|
| Assets graphiques non disponibles | Phase 3 peut etre realisee en parallele ; placeholder sprites possibles |
| Performance composition sur mobiles | Cache LRU + preload catalogue limité |
| Trop de micro-couches = bruit visuel 24x32 | Limiter a ~10 layers max, dessiner les layers adaptes a leur taille finale |
| Migration joueurs existants | Valeurs par defaut dans la migration (`avatarAppearance` = null -> fallback legacy) |
| Rupture API pour clients existants | `renderMode: 'legacy'` si pas d'avatar, backward-compatible |

---

## Estimation globale

| Phase | Taches | Complexite estimee |
|-------|--------|--------------------|
| Phase 1 — Backend | 6 taches | M (2-3 sessions) |
| Phase 2 — Frontend | 5 taches | M (2-3 sessions) |
| Phase 3 — Assets | 7 taches | M-L (production graphique, parallelisable) |
| Phase 4 — Creation personnage | 3 taches | L (2-3 sessions) |
| Phase 5 — Equipement & Mercure | 4 taches | M (2 sessions) |
| Phase 6 — Polish | 5 taches | L (3-4 sessions) |
| **Total** | **30 taches** | |

Les phases 1-2 constituent le MVP technique (joueurs avec avatar par defaut sur la map).
La phase 3 (assets) est le goulot d'etranglement principal et peut etre realisee en parallele.
Les phases 4-6 enrichissent progressivement l'experience.
