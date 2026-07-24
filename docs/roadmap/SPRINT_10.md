## Sprint 10 — Contenu de groupe & decommission carte

> **4 taches** | Priorite : **Moyenne** | Origine : Pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : le PvE cooperatif en modele zone (boss asynchrones, donjons semi-synchrones) et la suppression definitive du code carte.
> Prerequis : Sprint 9 (presence & evenements de zone)

> **Pivot PBBG** : ce sprint reutilise le numero de l'ancien Sprint 10 « Avatar: Polish & Animations », interrompu a 4/8 par le pivot (AVT-31, AVT-32, AVT-36, AVT-37 + AVT-35 sous-phases 1-2 livrees, tracees dans `ROADMAP_DONE.md` ; reliquat abandonne : AVT-33, AVT-34, AVT-35 sous-phase 3, AVT-38 — voir `PLAN_AVATAR_SYSTEM.md`).

---

### ZON-18 — Boss de zone asynchrone (M | ★★★)
> Prerequis : ← ZON-15
- [ ] Boss a large pool de PV dans une zone pour une fenetre donnee ; chaque joueur present depense de l'energie pour lancer ses assauts quand il le souhaite
- [ ] Loot distribue a la contribution (generalisation de `WorldBossLootDistributor`)
- [ ] Aucune presence simultanee requise

### ZON-19 — Donjon de groupe semi-synchrone (XL | ★★★)
> Prerequis : ← ZON-14
- [ ] Un leader forme un groupe parmi les joueurs presents dans la zone, puis lance le donjon (sequence de combats en tour par tour partage)
- [ ] Delai par tour (30-60 s) ; au-dela, action par defaut = attaque de base de l'arme (toujours gratuite, regle materia inchangee)
- [ ] Mercure pour l'experience fluide quand le groupe est connecte simultanement (evenement social planifie, assume)
- [ ] Reutiliser `DungeonRun` / le systeme de groupe (Party) existants

### ZON-20 — Lockouts & recompenses decroissantes (M | ★★)
> Prerequis : ← ZON-19
- [ ] Lockout par joueur et par donjon (ex: 1 clear/jour ou cooldown X heures)
- [ ] Preferer les recompenses decroissantes au blocage sec (protection de l'economie, variete de contenu)
- [ ] Curseurs dans `docs/BALANCE.md` (4e curseur : contribution/lockouts)

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
- [ ] Donjons de groupe jouables en semi-synchrone avec lockout/recompenses decroissantes
- [ ] Plus aucun code carte dans le repo (PixiJS retire de l'importmap, client allege) ; documentation a jour
