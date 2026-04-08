## Sprint 9 — Avatar: Personnage & Equipement

> **8 taches** | Priorite : **Moyenne** | Origine : Plan Avatar, Phases 5-6
> Objectif : ecran de creation de personnage avec choix d'apparence, equipement visible en temps reel.
> Prerequis : Sprint 8 (backend + integration carte)
> Reference detaillee : [PLAN_AVATAR_SYSTEM.md](PLAN_AVATAR_SYSTEM.md)

---

### Phase 5 — Ecran de creation de personnage

### AVT-23 — Ajouter les champs d'apparence au formulaire (M | ★★★)
> Prerequis : ← AVT-13
- [ ] `CharacterCreateType` : choix body (genre x skin tone), hair, hairColor, outfit de depart
- [ ] Liste des choix alimentee depuis les assets disponibles dans `avatar/`

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

### AVT-27 — Peupler `avatarSheet` sur les items existants (M | ★★★)
> Prerequis : ← AVT-16, AVT-03
- [ ] Associer chaque item d'equipement a son sprite sheet avatar (format 8x8)
- [ ] Mettre a jour les fixtures

### AVT-28 — Recalcul automatique du hash (S | ★★)
> Prerequis : ← AVT-15
- [ ] EventSubscriber sur changement d'equipement
- [ ] Recalcul `avatarHash` + `avatarUpdatedAt`

### AVT-29 — Publication Mercure `player.avatar.updated` (M | ★★★)
> Prerequis : ← AVT-28
- [ ] Quand le hash change : publier le nouveau payload avatar
- [ ] Le client invalide le cache et recompose la texture

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
