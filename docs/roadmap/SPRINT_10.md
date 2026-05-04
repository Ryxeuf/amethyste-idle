## Sprint 10 — Avatar: Polish & Animations

> **8 taches** | Priorite : **Basse** | Origine : Plan Avatar, Phase 7
>
> Avancement : 4/8 (AVT-31, AVT-32, AVT-36, AVT-37).
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
> Prerequis : ← AVT-25 ✅ (mergee)
> Avancement : sous-phases 1 (menu self-service) et 2 (integration nav desktop + mobile) livrees.
- [~] Modifier apparence apres creation (coiffeur PNJ ou menu)
  - [x] **Sous-phase 1 — Menu self-service** (2026-05-04) : nouvelle route `GET/POST /game/character/customize` (`app_character_customize`) dans `CharacterController::customize`. Form `CharacterCustomizeType` (mirroir reduit de `CharacterCreateType` : body / hair / hairColor uniquement, sans nom, race ni outfit). Template `templates/game/character/customize.html.twig` reutilise le macro `avatar_choice_group` du formulaire de creation et rajoute des pastilles de couleur de cheveux. Le POST normalise via le helper `stringOrNull`, met a jour `Player::avatarAppearance`, flush, puis appelle `AvatarHashRecalculator::recalculate()` (qui publie sur Mercure si le hash change). Redirection vers la meme page avec flash success en cas de succes. Pre-rempli avec l'apparence courante. 8 cles FR/EN ajoutees sous `game.character.customize.*`. Parite maintenue 727=727. Test `CharacterControllerCustomizeTest::testCustomizeRedirectsWhenNoActivePlayer` (cas: pas de joueur courant -> redirection 302 sans flush ni recalcul).
  - [x] **Sous-phase 2 — Integration nav** (2026-05-04) : lien "Apparence" ajoute dans le dropdown "Personnage" desktop (apres "Journal") et dans le drawer mobile (apres "Montures"). Route `app_character_customize` ajoutee a `character_routes` et a la liste de surlignage du bouton "Plus" mobile pour activer le highlight visuel. Nouvelle cle `game.nav.customize_appearance` FR/EN, parite 728=728.
- [ ] Sous-phase 3 — Coiffeur PNJ dedie (live preview canvas, NPC interaction) — non commencee
- [x] Recalcul du hash + persistence — couvert par sous-phase 1 via `AvatarHashRecalculator::recalculate()`

### ~~AVT-36 — Lazy loading intelligent (M | ★★)~~ ✅
> Prerequis : ← AVT-19
- [x] Precharger uniquement les sheets des joueurs visibles — nouveau module `assets/lib/avatar/AvatarSheetLoader.js` qui charge a la demande via `PIXI.Assets.load`, avec deduplication des chargements concurrents. Le boot du controleur `map_pixi_controller` n'itere plus sur `config.avatarCatalog` (suppression du preload body/hair/beard/facemark/gear).
- [x] Lazy load les sheets rares au besoin — `_ensureAvatarSheetsForEntities(players)` appele au debut de `_loadEntities` scanne les `avatar.baseSheet` + `avatar.layers[].sheet` des joueurs visibles et attend leur chargement en parallele avant de composer les textures. Toute arrivee ulterieure de joueur passe par `map/respawn` -> `_loadEntities`, qui reapplique la meme logique.

### ~~AVT-37 — Cache IndexedDB (M | ★★)~~ ✅
> Prerequis : ← AVT-11
- [x] Persister les textures composites entre sessions — nouveau module `assets/lib/avatar/AvatarTexturePersistentCache.js` (wrapper IndexedDB, store `avatar_textures`, keye par `hash`, TTL 30 jours). Ecriture fire-and-forget apres chaque composition (`renderer.extract.canvas().toBlob('image/png')`). Prefetch parallele des hashs des joueurs visibles dans `_ensureAvatarSheetsForEntities` via `AvatarAnimatorFactory.prefetchFromPersistentCache`, re-hydrate le cache memoire avant la premiere frame (`createImageBitmap` + `PIXI.Texture.from` avec `scaleMode='nearest'`).
- [x] Invalidation par `avatarHash` — `AvatarAnimatorFactory.invalidateAvatarHash` supprime en memoire ET en IndexedDB. Changement d'equipement -> backend recalcule `avatarHash` -> Mercure notifie -> client invalide. Defensif : tout echec IndexedDB (non supporte, quota, erreur) retombe silencieusement sur la composition synchrone.

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
