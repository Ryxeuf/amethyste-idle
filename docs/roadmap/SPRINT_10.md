## Sprint 10 — Avatar: Polish & Animations

> **8 taches** | Priorite : **Basse** | Origine : Plan Avatar, Phase 7
>
> Avancement : 3/8 (AVT-31, AVT-32, AVT-36).
> Objectif : exploiter pleinement les animations 8x8 et ameliorer la qualite visuelle.
> Prerequis : Sprint 9 (creation personnage + equipement visible)
> Reference detaillee : [PLAN_AVATAR_SYSTEM.md](PLAN_AVATAR_SYSTEM.md)

---

### ~~AVT-31 — Animation Run (S | ★★)~~ ✅
> Prerequis : ← AVT-07
- [x] Activer quand le joueur a un buff de vitesse ou sprint — mode sprint active par la touche Shift : `setAnimation('run')` + step delay multiplie par 0.6 pendant l'animation de deplacement. `_sprintActive` maintenu via `keydown`/`keyup` (+ reset sur `blur`), capture unique au debut de `_animateAlongPath` pour garantir la coherence visuelle sur toute la trajectoire. Aucun changement serveur : seule la duree de tween cote client varie, l'API `/api/map/move` reste inchangee. Aucun impact sur les sprites legacy (`SpriteAnimator.setAnimation` retourne `false` en dehors du type `avatar`).

### ~~AVT-32 — Animation Jump (S | ★★)~~ ✅
> Prerequis : ← AVT-07
- [x] Teleportation / changement de zone — `_handlePortalTransition` declenche `setAnimation('jump')` sur le `_playerAnimator` avant le fade-out, puis revient sur `walk` + `stop()` apres le fade-in
- [x] Helpers `_playPlayerAvatarAnimation(name)` et `_restorePlayerAvatarWalkAnimation()` (no-op pour les sprites legacy : `setAnimation` retourne `false`)
- [ ] Traversee d'obstacle — non applicable : aucun mecanisme d'obstacle franchissable n'existe encore (a re-evaluer si AVT-33 push/pull introduit des objets interactifs)

### AVT-33 — Animations Push/Pull (S | ★)
> Prerequis : ← AVT-07
- [ ] Interaction avec objets du monde (meubles, leviers, puzzles)

### AVT-34 — Paper doll dans l'inventaire (L | ★★★)
> Prerequis : ← AVT-20, AVT-27
- [ ] Preview du personnage equipe dans l'ecran d'inventaire
- [ ] Composition PixiJS dans un canvas dedie

### AVT-35 — Ecran de personnalisation post-creation (M | ★★)
> Prerequis : ← AVT-25
- [ ] Modifier apparence apres creation (coiffeur PNJ ou menu)
- [ ] Recalcul du hash + persistence

### ~~AVT-36 — Lazy loading intelligent (M | ★★)~~ ✅
> Prerequis : ← AVT-19
- [x] Precharger uniquement les sheets des joueurs visibles — nouveau module `assets/lib/avatar/AvatarSheetLoader.js` qui charge a la demande via `PIXI.Assets.load`, avec deduplication des chargements concurrents. Le boot du controleur `map_pixi_controller` n'itere plus sur `config.avatarCatalog` (suppression du preload body/hair/beard/facemark/gear).
- [x] Lazy load les sheets rares au besoin — `_ensureAvatarSheetsForEntities(players)` appele au debut de `_loadEntities` scanne les `avatar.baseSheet` + `avatar.layers[].sheet` des joueurs visibles et attend leur chargement en parallele avant de composer les textures. Toute arrivee ulterieure de joueur passe par `map/respawn` -> `_loadEntities`, qui reapplique la meme logique.

### AVT-37 — Cache IndexedDB (M | ★★)
> Prerequis : ← AVT-11
- [ ] Persister les textures composites entre sessions
- [ ] Invalidation par `avatarHash`

### AVT-38 — Variantes raciales & cosmetiques supplementaires (L | ★★)
> Prerequis : ← AVT-26
- [ ] Bodies supplementaires par race
- [ ] Coiffures et barbes supplementaires
- [ ] Options cosmetiques premium (futurs)

---

### Definition of Done

- [ ] Animations run/jump/push/pull fonctionnelles
- [ ] Paper doll dans l'inventaire
- [ ] Personnalisation post-creation
- [ ] Lazy loading et cache optimises
