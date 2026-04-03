# Plan — Systeme d'avatar modulaire (nouveau format 8x8)

> Roadmap d'integration du systeme d'avatar modulaire pour les personnages joueurs.
> Basee sur un nouveau pack d'assets au format **8 colonnes x 8 lignes** avec animations multiples.
> Remplace l'ancien plan base sur le format RPG Maker 3x4 (72x128).
> Derniere mise a jour : 2026-04-03

---

## Contexte

Aujourd'hui, tous les joueurs utilisent `spriteKey: 'player_default'` (hard-code dans `MapApiController`).
Le systeme de sprites repose sur le format **RPG Maker VX** (3 colonnes x 4 lignes, 24x32 par frame).

Un nouveau pack d'assets est disponible avec :
- **Format 8x8** : 8 colonnes x 8 lignes = 64 frames par sheet
- **Animations multiples** : stand, push, pull, jump, walk, run (vs uniquement walk actuellement)
- **Layers superposes** : body, outfit, hair (meme layout, pixel-perfect alignes)
- **Outfits et coiffures pre-dessines** disponibles

### Principe architectural

```
layers modulaires (meme taille, format 8x8)
  -> composition client PixiJS (RenderTexture)
    -> SpriteAnimator etendu (type 'avatar', multi-animations)
      -> cache LRU par avatarHash
```

### Differences avec l'ancien plan

| Aspect | Ancien plan (RPG Maker) | Nouveau plan (8x8) |
|--------|------------------------|---------------------|
| Layout | 3 cols x 4 rows (72x128) | 8 cols x 8 rows |
| Frame size | 24x32 px | ~64x64 px (a confirmer avec assets) |
| Animations | Walk uniquement | Stand, Push, Pull, Jump, Walk, Run |
| SpriteAnimator | Reutilise tel quel | Etendu avec type `avatar` |
| Mobs/PNJ | Inchanges | Inchanges (restent en legacy 3x4) |

### Ce qui existe deja

| Element | Etat |
|---------|------|
| `SpriteAnimator.js` | Operationnel, format RPG Maker 3x4 — **a etendre** |
| `SpriteConfigProvider.php` | Registre sprites hard-code |
| `/api/map/entities` | Renvoie `spriteKey` pour chaque entite |
| `/api/map/config` | Expose le catalogue sprites |
| Systeme d'equipement (`PlayerItem`, bitmask slots) | Complet |
| `GearHelper` | Getters par slot (head, chest, foot, etc.) |
| `Race.spriteSheet` | Champ existant mais non exploite |
| Blueprint avatar (`data/amethyste-avatar-pack/`) | Code JS + PHP a adapter |

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

## Phase 0 — Preparation des assets (prerequis)

> Objectif : organiser, inventorier et documenter le format exact des nouveaux sprites.
> Goulot d'etranglement : cette phase conditionne toutes les autres.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-01 | **Inventorier les assets disponibles** | S | ★★★ | ∅ |
| | Lister tous les body, outfits, hairstyles fournis dans le pack | | | |
| | Verifier la coherence de taille entre layers (memes dimensions) | | | |
| AVT-02 | **Documenter le layout exact du spritesheet** | S | ★★★ | ← AVT-01 |
| | Taille totale (ex: 512x512), taille par frame (ex: 64x64) | | | |
| | Mapping precis des animations par zone de la grille 8x8 | | | |
| | Reference sheet annotee (quelles cols/rows = quelle animation) | | | |
| AVT-03 | **Organiser les assets dans le projet** | S | ★★ | ← AVT-01 |
| | Deposer dans `assets/styles/images/avatar/` : | | | |
| | `body/` — corps de base (skin tones, genres) | | | |
| | `hair/` — coiffures (transparentes, meme layout) | | | |
| | `outfit/` — armures/vetements complets | | | |
| | `head/` — casques, chapeaux (optionnel MVP) | | | |
| AVT-04 | **Verifier l'alignement pixel-perfect** | S | ★★ | ← AVT-03 |
| | Superposer body + outfit + hair dans un editeur d'image | | | |
| | Confirmer que les layers s'alignent sur les 64 frames | | | |
| AVT-05 | **Mettre a jour ASSETS.md** | S | ★ | ← AVT-02 |
| | Ajouter la section "Format avatar 8x8" avec le layout documente | | | |

---

## Phase 1 — SpriteAnimator multi-animations

> Objectif : etendre le moteur d'animation pour supporter le format 8x8 sans casser l'existant.
> Les types `single` et `multi` (mobs, PNJ) restent strictement inchanges.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-06 | **Ajouter le type `avatar` dans `SpriteAnimator.js`** | M | ★★★ | ← AVT-02 |
| | Nouvelle branche dans `_computeFrameSize()` : grille 8x8 | | | |
| | Nouvelle branche dans `_buildFrames()` : 8 rows x 8 cols | | | |
| | Mapping configurable des animations : | | | |
| | `AVATAR_ANIMATIONS = { stand: {row,frames}, walk: {row,frames}, run: {row,frames}, jump: {row,frames}, push: {row,frames}, pull: {row,frames} }` | | | |
| | Le mapping exact depend du layout documente en AVT-02 | | | |
| AVT-07 | **Methode `setAnimation(name)` + animation courante** | S | ★★★ | ← AVT-06 |
| | Switcher entre stand/walk/run/jump/push/pull | | | |
| | L'animation par defaut reste `walk` pour compatibilite avec le flux de mouvement | | | |
| | `play()` et `stop()` fonctionnent comme avant mais sur l'animation courante | | | |
| AVT-08 | **Adapter le positionnement dans le tile** | S | ★★ | ← AVT-06 |
| | Frame plus grande (ex: 64x64 vs 24x32) → ajuster ancrage et scale | | | |
| | Les avatars doivent s'aligner correctement sur les tiles 32x32 | | | |
| | Factor de scale ou offset dans `map_pixi_controller.js` | | | |
| AVT-09 | **Tests manuels : type avatar isole** | S | ★★ | ← AVT-06 |
| | Charger un spritesheet 8x8 brut et verifier toutes les animations/directions | | | |
| | Verifier que les types `single` et `multi` ne sont pas impactes | | | |

---

## Phase 2 — Composition de textures par layers

> Objectif : empiler body + hair + outfit en une seule texture composite.
> Adapte le code existant dans `data/amethyste-avatar-pack/` au nouveau format.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-10 | **Integrer `AvatarTextureComposer.js`** | S | ★★★ | ∅ |
| | Copier depuis blueprint dans `assets/lib/avatar/` | | | |
| | Fonctionne deja pour n'importe quelle taille (lit `source.width/height`) | | | |
| | Verifier compatibilite PixiJS v8 (RenderTexture API) | | | |
| AVT-11 | **Integrer `AvatarSpriteSheetCache.js`** | S | ★★ | ∅ |
| | Copier depuis blueprint, cache LRU 128 entrees | | | |
| AVT-12 | **Adapter `AvatarAnimatorFactory.js`** | M | ★★★ | ← AVT-06, AVT-10, AVT-11 |
| | Copier depuis blueprint, adapter les imports | | | |
| | `createFromAvatarPayload()` cree un SpriteAnimator avec `type: 'avatar'` | | | |
| | `createFromLegacySpriteKey()` reste identique (type single/multi) | | | |
| | Deux pipelines coexistent : legacy pour mobs/PNJ, avatar pour joueurs | | | |

**Ordre des layers pour la composition :**

```
1. body (base)
2. outfit/armor (remplace le body visible)
3. hair (par-dessus)
4. head gear (casque, par-dessus les cheveux)
```

---

## Phase 3 — Backend : entite Player + API avatar

> Objectif : stocker l'apparence du joueur et la servir via l'API.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-13 | **Ajouter les champs avatar sur `Player`** | M | ★★★ | ∅ |
| | `avatarAppearance` (JSON nullable), `avatarHash` (string 64), `avatarVersion` (int), `avatarUpdatedAt` (datetime) | | | |
| | Migration Doctrine + valeurs par defaut pour joueurs existants | | | |
| | Structure JSON `avatarAppearance` : | | | |
| | `{ "body": "human_m_light", "hair": "short_01", "hairColor": "#d6b25e", "outfit": "starter_tunic" }` | | | |
| AVT-14 | **Integrer `AvatarHashGenerator`** | S | ★★ | ∅ |
| | Copier depuis blueprint, adapter namespace, enregistrer comme service | | | |
| AVT-15 | **Integrer `PlayerAvatarPayloadBuilder`** | M | ★★★ | ← AVT-13, AVT-14 |
| | Adapter `extractAppearance()` pour lire les vrais champs Player | | | |
| | Construire le payload avec baseSheet + layers | | | |
| | Brancher sur `GearHelper` pour les items equipes (phase 5) | | | |
| AVT-16 | **Ajouter `avatarSheet` sur `Item`** | S | ★★ | ∅ |
| | Champ nullable string : chemin vers le sprite sheet du layer visuel de l'item | | | |
| | Migration Doctrine | | | |
| AVT-17 | **Enrichir `/api/map/entities`** | M | ★★★ | ← AVT-15 |
| | Joueurs : ajouter `renderMode`, `avatarHash`, `avatar` (baseSheet + layers) | | | |
| | Conserver `spriteKey` en fallback (`renderMode: 'legacy'` si pas d'avatar) | | | |
| | Exemple payload : | | | |
| | `{ "renderMode": "avatar", "avatarHash": "a1b2c3...", "avatar": { "baseSheet": "/avatar/body/human_m_light.png", "layers": [...] } }` | | | |
| AVT-18 | **Enrichir `/api/map/config`** | S | ★★ | ← AVT-16 |
| | Ajouter `avatarCatalog` : liste des sheets avatar a precharger | | | |

---

## Phase 4 — Integration dans le renderer map

> Objectif : le controleur PixiJS utilise le nouveau pipeline pour les joueurs.
> Point d'integration central des phases 1-3.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-19 | **Instancier `AvatarAnimatorFactory` dans le map controller** | M | ★★★ | ← AVT-12, AVT-17 |
| | Instanciation apres chargement des textures | | | |
| | Precharger les sheets avatar depuis `avatarCatalog` | | | |
| AVT-20 | **Remplacer `_createAnimator()` par `_createAnimatorForEntity()`** | M | ★★★ | ← AVT-19 |
| | Si `entity.renderMode === 'avatar'` → pipeline composition | | | |
| | Sinon → pipeline legacy (spriteKey, inchange) | | | |
| | Mobs et PNJ passent toujours par le chemin legacy | | | |
| AVT-21 | **Gerer le joueur local (self)** | S | ★★ | ← AVT-20 |
| | Le joueur courant (`self: true`) utilise aussi le pipeline avatar | | | |
| | Invalidation du cache quand l'equipement change | | | |
| AVT-22 | **Tests integration carte** | S | ★★ | ← AVT-20 |
| | Verifier : joueurs rendus en avatar, mobs en legacy, PNJ en legacy | | | |
| | Verifier : taille, positionnement, z-order, emotes sur avatars | | | |

---

## Phase 5 — Ecran de creation de personnage

> Objectif : permettre au joueur de choisir son apparence a la creation.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-23 | **Ajouter les champs d'apparence au formulaire** | M | ★★★ | ← AVT-13 |
| | `CharacterCreateType` : choix body (genre × skin tone), hair, hairColor, outfit de depart | | | |
| | Liste des choix alimentee depuis les assets disponibles dans `avatar/` | | | |
| AVT-24 | **Preview temps reel** | L | ★★★ | ← AVT-10, AVT-23 |
| | Stimulus controller `character_creator_controller.js` | | | |
| | Mini canvas PixiJS composant les layers en temps reel au changement | | | |
| | Ou fallback : images statiques pre-generees | | | |
| AVT-25 | **Persister l'apparence a la creation** | S | ★★ | ← AVT-23 |
| | `PlayerFactory` sauvegarde `avatarAppearance` + calcule `avatarHash` | | | |
| AVT-26 | **Lier Race au body de base** | S | ★★ | ← AVT-25 |
| | Exploiter `Race.spriteSheet` pour determiner le body de base | | | |
| | Variantes visuelles par race (optionnel MVP : une seule silhouette) | | | |
| | Race affecte les stats (inchange), body affecte le visuel | | | |

---

## Phase 6 — Equipement visible & Mercure

> Objectif : l'equipement change le rendu visuel, les autres joueurs voient les mises a jour en temps reel.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-27 | **Peupler `avatarSheet` sur les items existants** | M | ★★★ | ← AVT-16, AVT-03 |
| | Associer chaque item d'equipement a son sprite sheet avatar (format 8x8) | | | |
| | Mettre a jour les fixtures | | | |
| AVT-28 | **Recalcul automatique du hash** | S | ★★ | ← AVT-15 |
| | EventSubscriber sur changement d'equipement | | | |
| | Recalcul `avatarHash` + `avatarUpdatedAt` | | | |
| AVT-29 | **Publication Mercure `player.avatar.updated`** | M | ★★★ | ← AVT-28 |
| | Quand le hash change : publier le nouveau payload avatar | | | |
| | Le client invalide le cache et recompose la texture | | | |
| AVT-30 | **Gestion cote client des updates Mercure** | M | ★★ | ← AVT-20, AVT-29 |
| | Ecouter `player.avatar.updated` dans le controleur map | | | |
| | Invalider le cache + recreer l'animator pour le joueur concerne | | | |

---

## Phase 7 — Polish & animations avancees (optionnel)

> Objectif : exploiter pleinement les animations et ameliorer la qualite visuelle.

| # | Tache | Taille | Impact | Prerequis |
|---|-------|--------|--------|-----------|
| AVT-31 | **Animation Run** | S | ★★ | ← AVT-07 |
| | Activer quand le joueur a un buff de vitesse ou sprint | | | |
| AVT-32 | **Animation Jump** | S | ★★ | ← AVT-07 |
| | Traversee d'obstacle, teleportation, changement de zone | | | |
| AVT-33 | **Animations Push/Pull** | S | ★ | ← AVT-07 |
| | Interaction avec objets du monde (meubles, leviers, puzzles) | | | |
| AVT-34 | **Paper doll dans l'inventaire** | L | ★★★ | ← AVT-20, AVT-27 |
| | Preview du personnage equipe dans l'ecran d'inventaire | | | |
| | Composition PixiJS dans un canvas dedie | | | |
| AVT-35 | **Ecran de personnalisation post-creation** | M | ★★ | ← AVT-25 |
| | Modifier apparence apres creation (coiffeur PNJ ou menu) | | | |
| | Recalcul du hash + persistence | | | |
| AVT-36 | **Lazy loading intelligent** | M | ★★ | ← AVT-19 |
| | Precharger uniquement les sheets des joueurs visibles | | | |
| | Lazy load les sheets rares au besoin | | | |
| AVT-37 | **Cache IndexedDB** | M | ★★ | ← AVT-11 |
| | Persister les textures composites entre sessions | | | |
| | Invalidation par `avatarHash` | | | |
| AVT-38 | **Variantes raciales & cosmetiques supplementaires** | L | ★★ | ← AVT-26 |
| | Bodies supplementaires par race | | | |
| | Coiffures et barbes supplementaires | | | |
| | Options cosmetiques premium (futurs) | | | |

---

## Graphe de dependances

```
Phase 0 — Assets (prerequis)
  AVT-01 (Inventaire assets)
  AVT-02 (Doc layout 8x8)     ← AVT-01
  AVT-03 (Organiser fichiers)  ← AVT-01
  AVT-04 (Alignement)          ← AVT-03
  AVT-05 (MAJ ASSETS.md)       ← AVT-02

Phase 1 — SpriteAnimator (← Phase 0)
  AVT-06 (Type avatar 8x8)    ← AVT-02
  AVT-07 (setAnimation)       ← AVT-06
  AVT-08 (Positionnement)     ← AVT-06
  AVT-09 (Tests manuels)      ← AVT-06

Phase 2 — Composition layers (‖ Phase 1)
  AVT-10 (Composer.js)        ∅
  AVT-11 (Cache.js)           ∅
  AVT-12 (Factory.js)         ← AVT-06, AVT-10, AVT-11

Phase 3 — Backend (‖ Phases 1-2)
  AVT-13 (Player fields)      ∅
  AVT-14 (HashGenerator)      ∅
  AVT-15 (PayloadBuilder)     ← AVT-13, AVT-14
  AVT-16 (Item avatarSheet)   ∅
  AVT-17 (API entities)       ← AVT-15
  AVT-18 (API config)         ← AVT-16

Phase 4 — Integration map (← Phases 1-3)
  AVT-19 (Factory dans ctrl)  ← AVT-12, AVT-17
  AVT-20 (createAnimator)     ← AVT-19
  AVT-21 (Joueur local)       ← AVT-20
  AVT-22 (Tests integration)  ← AVT-20

Phase 5 — Creation personnage (← Phases 3-4)
  AVT-23 (Formulaire)         ← AVT-13
  AVT-24 (Preview temps reel) ← AVT-10, AVT-23
  AVT-25 (Persistance)        ← AVT-23
  AVT-26 (Race → body)        ← AVT-25

Phase 6 — Equipement visible (← Phases 4-5)
  AVT-27 (Peupler avatarSheet) ← AVT-16, AVT-03
  AVT-28 (Recalcul hash)       ← AVT-15
  AVT-29 (Mercure publish)     ← AVT-28
  AVT-30 (Mercure client)      ← AVT-20, AVT-29

Phase 7 — Polish (← Phase 6)
  AVT-31..33 (Animations)     ← AVT-07
  AVT-34 (Paper doll)         ← AVT-20, AVT-27
  AVT-35 (Personnalisation)   ← AVT-25
  AVT-36 (Lazy loading)       ← AVT-19
  AVT-37 (Cache IndexedDB)    ← AVT-11
  AVT-38 (Variantes raciales) ← AVT-26
```

### Parallelisation

Les phases **1, 2 et 3** peuvent avancer en parallele apres la phase 0.
La phase **4** est le point d'integration central.
Les phases **5-7** sont sequentielles apres la 4.

---

## Coexistence des deux formats de sprites

Le systeme supporte **simultanement** les deux formats :

| Format | Type SpriteAnimator | Utilise par |
|--------|-------------------|-------------|
| RPG Maker 3x4 (legacy) | `single` / `multi` | Mobs, PNJ |
| Nouveau 8x8 (avatar) | `avatar` | Joueurs |

Le routing se fait via `renderMode` dans le payload API :
- `renderMode: 'avatar'` → pipeline composition + type avatar
- `renderMode: 'legacy'` ou absent → pipeline spriteKey classique

---

## Mapping slots equipement -> layers visuels

| Constante Item | Layer avatar |
|----------------|-------------|
| `GEAR_LOCATION_HEAD` | `avatar/head/` |
| `GEAR_LOCATION_CHEST` | `avatar/outfit/` (remplace la tenue de base) |
| `GEAR_LOCATION_BELT` | `avatar/outfit/` (optionnel, integre dans l'outfit) |
| `GEAR_LOCATION_LEG` | `avatar/outfit/` (integre dans l'outfit complet) |
| `GEAR_LOCATION_FOOT` | `avatar/outfit/` (integre dans l'outfit complet) |
| `GEAR_LOCATION_SHOULDER` | `avatar/outfit/` (integre dans l'outfit complet) |
| `GEAR_LOCATION_MAIN_WEAPON` | `avatar/weapon/` (futur) |
| `GEAR_LOCATION_SIDE_WEAPON` | `avatar/shield/` (futur) |

> Note : avec des sprites 8x8 plus detailles, les outfits sont souvent des sheets completes
> (armure + bottes + jambieres) plutot que des micro-layers separes comme en 24x32.

---

## Risques identifies

| Risque | Mitigation |
|--------|-----------|
| Layout exact du spritesheet pas encore documente | Phase 0 obligatoire avant toute implementation |
| Frame size differente (64x64 vs 24x32) → positionnement | Scale/offset dans le map controller (AVT-08) |
| Performance composition sur mobiles | Cache LRU + preload catalogue limite |
| Migration joueurs existants | `avatarAppearance` nullable → fallback `player_default` legacy |
| Rupture API pour clients existants | `renderMode: 'legacy'` par defaut, backward-compatible |
| Assets graphiques insuffisants pour tous les items | Phase 6 progressive, fallback outfit par defaut |

---

## Estimation globale

| Phase | Taches | Complexite estimee |
|-------|--------|--------------------|
| Phase 0 — Assets | 5 taches (AVT-01..05) | S (1 session) |
| Phase 1 — SpriteAnimator | 4 taches (AVT-06..09) | M (1-2 sessions) |
| Phase 2 — Composition | 3 taches (AVT-10..12) | S-M (1 session) |
| Phase 3 — Backend | 6 taches (AVT-13..18) | M (2-3 sessions) |
| Phase 4 — Integration map | 4 taches (AVT-19..22) | M (1-2 sessions) |
| Phase 5 — Creation perso | 4 taches (AVT-23..26) | L (2-3 sessions) |
| Phase 6 — Equipement & Mercure | 4 taches (AVT-27..30) | M (2 sessions) |
| Phase 7 — Polish | 8 taches (AVT-31..38) | L (progressif) |
| **Total** | **38 taches** | |

Le **MVP visuel** (joueurs avec avatar compose sur la carte) necessite les phases 0-4 (22 taches).
La phase 5 (creation personnage) est la premiere extension critique.
Les phases 6-7 enrichissent progressivement l'experience.
