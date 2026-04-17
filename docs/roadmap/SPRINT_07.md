## Sprint 7 — Avatar: Fondations

> **12 taches** | Priorite : **Moyenne** | Origine : Plan Avatar, Phases 0-2
> Objectif : preparer les assets, etendre le SpriteAnimator pour le format 8x8, composer les textures.
> Prerequis : ∅ (peut demarrer en parallele des sprints 1-6)
> Reference detaillee : [PLAN_AVATAR_SYSTEM.md](PLAN_AVATAR_SYSTEM.md)

---

### Phase 0 — Preparation des assets

### ~~AVT-01 — Inventorier les assets disponibles (S | ★★★)~~ ✅
> Prerequis : ∅
- [x] Lister tous les body, outfits, hairstyles fournis dans le pack
- [x] Verifier la coherence de taille entre layers (memes dimensions)

### ~~AVT-02 — Documenter le layout exact du spritesheet (S | ★★★)~~ ✅
> Prerequis : ← AVT-01
- [x] Taille totale (ex: 512x512), taille par frame (ex: 64x64) — sheet base 512x512, frame 64x64, extensible en hauteur
- [x] Mapping precis des animations par zone de la grille 8x8 — walk (rows 0-3) + stand (rows 4-7), animations etendues rows 8+
- [x] Reference sheet annotee (quelles cols/rows = quelle animation) — voir `docs/avatar-spritesheet-layout.md`

### ~~AVT-03 — Organiser les assets dans le projet (S | ★★)~~ ✅
> Prerequis : ← AVT-01
- [x] Deposer dans `assets/styles/images/avatar/` : body/, hair/, outfit/, head/ — structure creee avec `.gitkeep` et README documentant le format 8x8, le z-order et la convention de nommage

### AVT-04 — Verifier l'alignement pixel-perfect (S | ★★)
> Prerequis : ← AVT-03
- [ ] Superposer body + outfit + hair dans un editeur d'image
- [ ] Confirmer que les layers s'alignent sur les 64 frames

> Note : tache bloquee tant qu'aucun asset 8x8 reel n'est livre. Les regles d'alignement sont documentees dans `assets/styles/images/avatar/README.md` et `docs/avatar-spritesheet-layout.md` ; la verification visuelle se fera des reception des premiers PNG.

### ~~AVT-05 — Mettre a jour ASSETS.md (S | ★)~~ ✅
> Prerequis : ← AVT-02
- [x] Ajouter la section "Format avatar 8x8" avec le layout documente — section ajoutee dans ASSETS.md, dimensions legacy corrigees (72x128 → 96x128, 24x32 → 32x32)

---

### Phase 1 — SpriteAnimator multi-animations

### ~~AVT-06 — Ajouter le type `avatar` dans SpriteAnimator.js (M | ★★★)~~ ✅
> Prerequis : ← AVT-02
- [x] Nouvelle branche dans `_computeFrameSize()` : grille 8x8
- [x] Nouvelle branche dans `_buildFrames()` : 8 rows x 8 cols
- [x] Mapping configurable des animations (stand, walk, run, jump, push, pull)

### ~~AVT-07 — Methode `setAnimation(name)` + animation courante (S | ★★★)~~ ✅
> Prerequis : ← AVT-06
- [x] Switcher entre stand/walk/run/jump/push/pull
- [x] Animation par defaut : `walk` (compatibilite mouvement)

### ~~AVT-08 — Adapter le positionnement dans le tile (S | ★★)~~ ✅
> Prerequis : ← AVT-06
- [x] Frame plus grande (64x64 vs 24x32) → ajuster ancrage et scale
- [x] Alignement correct sur les tiles 32x32

### ~~AVT-09 — Tests manuels : type avatar isole (S | ★★)~~ ✅
> Prerequis : ← AVT-06
- [x] Charger un spritesheet 8x8 brut et verifier toutes les animations/directions
- [x] Verifier que les types `single` et `multi` ne sont pas impactes

---

### Phase 2 — Composition de textures par layers

### ~~AVT-10 — Integrer AvatarTextureComposer.js (S | ★★★)~~ ✅
> Prerequis : ∅
- [x] Copier depuis blueprint dans `assets/lib/avatar/`
- [x] Verifier compatibilite PixiJS v8 (RenderTexture API)

### ~~AVT-11 — Integrer AvatarSpriteSheetCache.js (S | ★★)~~ ✅
> Prerequis : ∅
- [x] Copier depuis blueprint, cache LRU 128 entrees

### ~~AVT-12 — Adapter AvatarAnimatorFactory.js (M | ★★★)~~ ✅
> Prerequis : ← AVT-06, AVT-10, AVT-11
- [x] `createFromAvatarPayload()` cree un SpriteAnimator avec `type: 'avatar'`
- [x] `createFromLegacySpriteKey()` reste identique (type single/multi)
- [x] Deux pipelines coexistent : legacy pour mobs/PNJ, avatar pour joueurs

---

### Definition of Done

- [x] Assets inventories, organises et documentes (AVT-04 reste a valider visuellement des reception des premiers PNG)
- [x] SpriteAnimator supporte le type `avatar` (8x8)
- [x] Composition de textures multi-layers fonctionnelle
- [x] Types legacy (single/multi) inchanges
