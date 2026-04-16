## Sprint 8 — Avatar: Backend & Carte

> **10 taches** | Priorite : **Moyenne** | Origine : Plan Avatar, Phases 3-4
> Objectif : stocker l'apparence du joueur, servir via l'API, integrer dans le renderer PixiJS.
> Prerequis : Sprint 7 (fondations avatar)
> Reference detaillee : [PLAN_AVATAR_SYSTEM.md](PLAN_AVATAR_SYSTEM.md)

---

### Phase 3 — Backend : entite Player + API avatar

### ~~AVT-13 — Ajouter les champs avatar sur Player (M | ★★★)~~ ✅
> Prerequis : ∅
- [x] `avatarAppearance` (JSON nullable), `avatarHash` (string 64), `avatarVersion` (int), `avatarUpdatedAt` (datetime)
- [x] Migration Doctrine + valeurs par defaut pour joueurs existants
- [x] Structure JSON : `{ "body": "human_m_light", "hair": "short_01", "hairColor": "#d6b25e", "outfit": "starter_tunic" }`

### ~~AVT-14 — Integrer AvatarHashGenerator (S | ★★)~~ ✅
> Prerequis : ∅
- [x] Copier depuis blueprint, adapter namespace, enregistrer comme service

### AVT-15 — Integrer PlayerAvatarPayloadBuilder (M | ★★★)
> Prerequis : ← AVT-13, AVT-14
- [ ] Adapter `extractAppearance()` pour lire les vrais champs Player
- [ ] Construire le payload avec baseSheet + layers
- [ ] Brancher sur `GearHelper` pour les items equipes

### ~~AVT-16 — Ajouter `avatarSheet` sur Item (S | ★★)~~ ✅
> Prerequis : ∅
- [x] Champ nullable string : chemin vers le sprite sheet du layer visuel de l'item
- [x] Migration Doctrine

### AVT-17 — Enrichir `/api/map/entities` (M | ★★★)
> Prerequis : ← AVT-15
- [ ] Joueurs : ajouter `renderMode`, `avatarHash`, `avatar` (baseSheet + layers)
- [ ] Conserver `spriteKey` en fallback (`renderMode: 'legacy'` si pas d'avatar)

### AVT-18 — Enrichir `/api/map/config` (S | ★★)
> Prerequis : ← AVT-16
- [ ] Ajouter `avatarCatalog` : liste des sheets avatar a precharger

---

### Phase 4 — Integration dans le renderer map

### AVT-19 — Instancier AvatarAnimatorFactory dans le map controller (M | ★★★)
> Prerequis : ← AVT-12, AVT-17
- [ ] Instanciation apres chargement des textures
- [ ] Precharger les sheets avatar depuis `avatarCatalog`

### AVT-20 — Remplacer `_createAnimator()` par `_createAnimatorForEntity()` (M | ★★★)
> Prerequis : ← AVT-19
- [ ] Si `entity.renderMode === 'avatar'` → pipeline composition
- [ ] Sinon → pipeline legacy (spriteKey, inchange)

### AVT-21 — Gerer le joueur local (self) (S | ★★)
> Prerequis : ← AVT-20
- [ ] Le joueur courant utilise aussi le pipeline avatar
- [ ] Invalidation du cache quand l'equipement change

### AVT-22 — Tests integration carte (S | ★★)
> Prerequis : ← AVT-20
- [ ] Verifier : joueurs en avatar, mobs en legacy, PNJ en legacy
- [ ] Verifier : taille, positionnement, z-order, emotes sur avatars

---

### Definition of Done

- [ ] Champs avatar persistes sur Player avec migration
- [ ] API `/api/map/entities` sert le payload avatar
- [ ] Renderer PixiJS utilise le pipeline avatar pour les joueurs
- [ ] Pipeline legacy inchange pour mobs/PNJ
