## Sprint 7 — Avatar: Fondations

> **12 taches** | Priorite : **Moyenne** | Origine : Plan Avatar, Phases 0-2
> Objectif : preparer les assets, etendre le SpriteAnimator pour le format 8x8, composer les textures.
> Prerequis : ∅ (peut demarrer en parallele des sprints 1-6)
> Reference detaillee : [PLAN_AVATAR_SYSTEM.md](PLAN_AVATAR_SYSTEM.md)

---

### Phase 0 — Preparation des assets

### ~~AVT-01 — Inventorier les assets disponibles (S | ★★★)~~ ✅
> Prerequis : ∅
- [x] Lister tous les body, outfits, hairstyles fournis dans le pack — inventaire complet dans `docs/avatar-asset-inventory.md` : 188 sprites individuels (96x128), 6 multi-sheets, 50 personnages uniques, 7 joueurs / 11 PNJ / 21 mobs configures
- [x] Verifier la coherence de taille entre layers (memes dimensions) — 100% des sprites utilises en jeu respectent le format 96x128 / 32x32 (single) ou 384x256 / 32x32 (multi). Correction des dimensions erronees dans ASSETS.md (72x128 → 96x128)

### AVT-02 — Documenter le layout exact du spritesheet (S | ★★★)
> Prerequis : ← AVT-01
- [ ] Taille totale (ex: 512x512), taille par frame (ex: 64x64)
- [ ] Mapping precis des animations par zone de la grille 8x8
- [ ] Reference sheet annotee (quelles cols/rows = quelle animation)

### AVT-03 — Organiser les assets dans le projet (S | ★★)
> Prerequis : ← AVT-01
- [ ] Deposer dans `assets/styles/images/avatar/` : body/, hair/, outfit/, head/

### AVT-04 — Verifier l'alignement pixel-perfect (S | ★★)
> Prerequis : ← AVT-03
- [ ] Superposer body + outfit + hair dans un editeur d'image
- [ ] Confirmer que les layers s'alignent sur les 64 frames

### AVT-05 — Mettre a jour ASSETS.md (S | ★)
> Prerequis : ← AVT-02
- [ ] Ajouter la section "Format avatar 8x8" avec le layout documente

---

### Phase 1 — SpriteAnimator multi-animations

### AVT-06 — Ajouter le type `avatar` dans SpriteAnimator.js (M | ★★★)
> Prerequis : ← AVT-02
- [ ] Nouvelle branche dans `_computeFrameSize()` : grille 8x8
- [ ] Nouvelle branche dans `_buildFrames()` : 8 rows x 8 cols
- [ ] Mapping configurable des animations (stand, walk, run, jump, push, pull)

### AVT-07 — Methode `setAnimation(name)` + animation courante (S | ★★★)
> Prerequis : ← AVT-06
- [ ] Switcher entre stand/walk/run/jump/push/pull
- [ ] Animation par defaut : `walk` (compatibilite mouvement)

### AVT-08 — Adapter le positionnement dans le tile (S | ★★)
> Prerequis : ← AVT-06
- [ ] Frame plus grande (64x64 vs 24x32) → ajuster ancrage et scale
- [ ] Alignement correct sur les tiles 32x32

### AVT-09 — Tests manuels : type avatar isole (S | ★★)
> Prerequis : ← AVT-06
- [ ] Charger un spritesheet 8x8 brut et verifier toutes les animations/directions
- [ ] Verifier que les types `single` et `multi` ne sont pas impactes

---

### Phase 2 — Composition de textures par layers

### ~~AVT-10 — Integrer AvatarTextureComposer.js (S | ★★★)~~ ✅
> Prerequis : ∅
- [x] Copier depuis blueprint dans `assets/lib/avatar/`
- [x] Verifier compatibilite PixiJS v8 (RenderTexture API)

### ~~AVT-11 — Integrer AvatarSpriteSheetCache.js (S | ★★)~~ ✅
> Prerequis : ∅
- [x] Copier depuis blueprint, cache LRU 128 entrees

### AVT-12 — Adapter AvatarAnimatorFactory.js (M | ★★★)
> Prerequis : ← AVT-06, AVT-10, AVT-11
- [ ] `createFromAvatarPayload()` cree un SpriteAnimator avec `type: 'avatar'`
- [ ] `createFromLegacySpriteKey()` reste identique (type single/multi)
- [ ] Deux pipelines coexistent : legacy pour mobs/PNJ, avatar pour joueurs

---

### Definition of Done

- [ ] Assets inventories, organises et documentes
- [ ] SpriteAnimator supporte le type `avatar` (8x8)
- [ ] Composition de textures multi-layers fonctionnelle
- [ ] Types legacy (single/multi) inchanges
