## Sprint 10 — Contenu de groupe & decommission carte

> **4 taches** (3 livrees) | Priorite : **Moyenne** | Origine : Pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : le PvE cooperatif en modele zone (boss asynchrones, donjons semi-synchrones) et la suppression definitive du code carte.
> Prerequis : Sprint 9 (presence & evenements de zone)

> **Pivot PBBG** : ce sprint reutilise le numero de l'ancien Sprint 10 « Avatar: Polish & Animations », interrompu a 4/8 par le pivot (AVT-31, AVT-32, AVT-36, AVT-37 + AVT-35 sous-phases 1-2 livrees, tracees dans `ROADMAP_DONE.md` ; reliquat abandonne : AVT-33, AVT-34, AVT-35 sous-phase 3, AVT-38 — voir `PLAN_AVATAR_SYSTEM.md`).

---

> **ZON-18 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : boss de zone asynchrone — entite `ZoneBoss` (pool de PV partage, 1:1 `GameEvent` de zone), `ZoneBossService::assault` (chaque joueur present depense `zone.energy.cost.assault`, inflige des degats bases sur sa stat d'attaque, alimente `PlayerZoneEventParticipation.contribution`), aucune presence simultanee. A 0 PV, loot distribue a la contribution (top-3 = drops garantis + proba boostee, autres = probabiliste) — generalisation de `WorldBossLootDistributor` au modele zone. `ZoneBossManager` cree le boss a l'activation d'un evenement de zone porteur (`monster_slug`/`boss_hp`). Barre de PV + bouton Assaut sur l'ecran de zone, annonce Mercure de defaite. Curseurs BALANCE section 8.

### ZON-19 — Donjon de groupe semi-synchrone (XL | ★★★) — **decoupe en sous-jalons**
> Prerequis : ← ZON-14
> **Sous-jalon 1 (modele & formation) livre le 2026-07-25** (voir `ROADMAP_DONE.md`) : entites `GroupDungeonRun` + `GroupDungeonMember` (instantane des membres), `GroupDungeonService::launch` (le leader forme le groupe parmi les presents via `Party`, garde d'unicite/presence/taille) + `abandon`, controleur (launch/abandon), banniere de run actif sur l'ecran de zone. Reutilise `Party`.
> **Sous-jalon 2 (boucle de combat) livre le 2026-07-25** (voir `ROADMAP_DONE.md`) : combat tour par tour partage sur une rencontre a PV partages (`GroupDungeonCombatService`), ordre de tour des membres, delai par tour (`zone.dungeon.turn_seconds` defaut 45 s) resolu paresseusement — action par defaut = attaque de base auto. A 0 PV, le run est complete. Barre de PV + bouton Attaquer (a son tour) sur l'ecran de zone.
> **Sous-jalon 3 (Mercure temps reel) livre le 2026-07-25** (voir `ROADMAP_DONE.md`) : `GroupDungeonCombatPublisher` publie l'etat de combat sur le topic `dungeon/run/<id>` a chaque changement (attaque, resolution auto d'un tour en retard, defaite). Controleur Stimulus `group-dungeon` sur la banniere de zone : rafraichit PV/tour/bouton Attaquer sans recharger, decompte local du minuteur. Modele semi-synchrone preserve — Mercure n'est qu'un confort quand le groupe est en ligne. **ZON-19 complet.**

> **ZON-20 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : recompenses de donjon de groupe **decroissantes** plutot que lockout dur. A la reussite d'un run, chaque membre recoit des gils (`zone.dungeon.reward.base_gils`, defaut 150) ; chaque reussite supplementaire du meme donjon dans la fenetre glissante (`zone.dungeon.lockout.window_hours`, defaut 24 h) reduit la recompense d'un facteur `zone.dungeon.lockout.decay` (defaut 0.5), borne par `zone.dungeon.lockout.min_factor` (defaut 0.25). Le joueur peut toujours rejouer (variete de contenu, cooperation) — le farm rapporte de moins en moins. Entite `GroupDungeonClear` (trace par membre), `GroupDungeonRewardService`, recompense affichee dans la banniere de zone.

### ZON-21 — Suppression du code carte (L | ★)
> Prerequis : ← ZON-16 (modele zone stabilise)
- [ ] Supprimer : `map_pixi_controller.js`, `SpriteAnimator`, bundle PixiJS (sortie de l'importmap), pipeline avatar client (`AvatarTextureComposer`, `AvatarSheetLoader`, caches), pathfinding Dijkstra, `PlayerMoveProcessor`, publishers `Realtime/Map`
- [ ] Deprecier puis supprimer les endpoints `/api/map/*` ; retirer `app:index:cell` (Typesense garde objets/entites)
- [ ] Archiver `terrain/` et `docs/TILED_GUIDE.md` avec le code carte (reutilisables par le projet Zelda-like separe)
- [ ] MAJ documentation : CLAUDE.md (§7 coordonnees, §9 rendu PixiJS, routes), AGENTS.md, DOCUMENTATION.md (§20 Tiled) — documenter le modele zone
- [ ] Extraire au prealable ce qui se reutilise (entites, spawns, donnees de zones)

---

### Definition of Done

- [ ] Boss de zone jouables sans presence simultanee, loot a la contribution
- [x] Donjons de groupe jouables en semi-synchrone avec lockout/recompenses decroissantes
- [ ] Plus aucun code carte dans le repo (PixiJS retire de l'importmap, client allege) ; documentation a jour
