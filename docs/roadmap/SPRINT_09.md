## Sprint 9 — Avatar: Personnage & Equipement

> **8 taches** | Priorite : **Moyenne** | Origine : Plan Avatar, Phases 5-6
> Objectif : ecran de creation de personnage avec choix d'apparence, equipement visible en temps reel.
> Prerequis : Sprint 8 (backend + integration carte)
> Reference detaillee : [PLAN_AVATAR_SYSTEM.md](PLAN_AVATAR_SYSTEM.md)

---

### Phase 5 — Ecran de creation de personnage

### ~~AVT-23 — Ajouter les champs d'apparence au formulaire (M | ★★★)~~ ✅
> Prerequis : ← AVT-13
- [x] `CharacterCreateType` : choix body (skin tone), hair, hairColor, outfit de depart — 4 champs `ChoiceType` expanded + palette hairColor hex de 6 tons
- [x] Liste des choix alimentee depuis les assets disponibles dans `avatar/` — nouvelle methode `AvatarCatalogProvider::getCreationChoices()` qui scanne body/hair/outfit/head
- [x] Preview sprite `data-sheet` exposee sur chaque option via `choice_attr` (consommable par le futur `character_creator_controller.js` — AVT-24)

### AVT-24 — Preview temps reel (L | ★★★)
> Prerequis : ← AVT-10, AVT-23
- [ ] Stimulus controller `character_creator_controller.js`
- [ ] Mini canvas PixiJS composant les layers en temps reel au changement
- [ ] Ou fallback : images statiques pre-generees

### AVT-25 — Persister l'apparence a la creation (S | ★★)
> Prerequis : ← AVT-23
- [ ] `PlayerFactory` sauvegarde `avatarAppearance` + calcule `avatarHash`

### AVT-26 — Lier Race au body de base (S | ★★)
> Prerequis : ← AVT-25
- [ ] Exploiter `Race.spriteSheet` pour determiner le body de base
- [ ] Race affecte les stats (inchange), body affecte le visuel

---

### Phase 6 — Equipement visible & Mercure

### ~~AVT-27 — Peupler `avatarSheet` sur les items existants (M | ★★★)~~ ✅
> Prerequis : ← AVT-16, AVT-03
- [x] Associer chaque item d'equipement a son sprite sheet avatar (format 8x8) — service `ItemAvatarSheetResolver` qui derive `/avatar/{gear_directory}/{slug}.png` depuis `Item.gearLocation` + `Item.slug`, couvrant head / chest / leg / foot / hand / belt / shoulder / weapon_main / weapon_side
- [x] Mettre a jour les fixtures — approche convention-based : aucune modification manuelle de `ItemFixtures.php` (4087 lignes) necessaire. Les items d'equipement obtiennent leur sheet automatiquement via le resolveur. Le champ explicite `Item.avatarSheet` reste prioritaire pour les overrides custom futurs.

### ~~AVT-28 — Recalcul automatique du hash (S | ★★)~~ ✅
> Prerequis : ← AVT-15
- [x] Hook sur changement d'equipement dans `GearSetter::setGear`/`unsetGear` — appel direct du service `AvatarHashRecalculator`
- [x] Recalcul `avatarHash` + `avatarUpdatedAt` — `Player::setAvatarHash` touche `avatarUpdatedAt` uniquement quand la valeur change

### ~~AVT-29 — Publication Mercure `player.avatar.updated` (M | ★★★)~~ ✅
> Prerequis : ← AVT-28
- [x] Quand le hash change : publier le nouveau payload avatar — nouveau service `App\GameEngine\Realtime\Avatar\AvatarUpdatedPublisher` (topic `map/avatar`, type `avatar_updated`), appele depuis `AvatarHashRecalculator::recalculate()` uniquement quand le hash change effectivement
- [ ] Le client invalide le cache et recompose la texture — cote serveur pret, integration client couverte par AVT-30

### AVT-30 — Gestion cote client des updates Mercure (M | ★★)
> Prerequis : ← AVT-20, AVT-29
- [ ] Ecouter `player.avatar.updated` dans le controleur map
- [ ] Invalider le cache + recreer l'animator pour le joueur concerne

---

### Definition of Done

- [ ] Creation de personnage avec choix d'apparence fonctionnel
- [ ] Preview temps reel dans le formulaire
- [ ] Equipement modifie le rendu visuel du joueur
- [ ] Autres joueurs voient les changements en temps reel via Mercure
