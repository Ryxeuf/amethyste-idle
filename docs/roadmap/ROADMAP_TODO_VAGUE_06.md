## Vague 6 — Long terme & polish final

> **11 taches** a planifier quand le contenu de base est solide.
> Aucune urgence — objectifs long terme.

---

### ~~92 — Classement guildes (S | ★)~~ ✅
> Tableau de classement simple par points de guilde. Prerequis : ← 52
- [x] Champ `points` sur Guild (incremente par succes membres, quetes)
- [x] Route `GET /game/guilds/ranking` : classement pagine
- [x] GuildPointsListener : ajoute des points sur MobDeadEvent, QuestCompletedEvent
- [x] Tests : attribution points, classement ordonne

### ~~93 — Quetes de guilde (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~94 — Trame Acte 3 : La Convergence (L | ★★★)~~ ✅
> Donjon final. Prerequis : ← 80, 72
- [x] Donjon final accessible apres les 4 fragments (entryRequirements JSON sur Dungeon, verification dans DungeonManager)
- [x] Boss final "Gardien de la Convergence" (800 PV, 3 phases, 5 sorts, resistances multi-element)
- [x] Chaine de 3 quetes (L'Appel des Fragments → Le Gardien du Nexus → Epilogue), prerequis = 4 fragments
- [x] Recompenses de fin de trame : Lame de la Convergence (Amethyst), Amulette de la Convergence (Amethyst), titre "Heros de la Convergence"
- [x] Achievement "La Convergence" (10 000 gils + titre)
- [x] Migration PostgreSQL entry_requirements

### ~~95 — Saisonnalite & festivals (S | ★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~97 — Parsing animations tiles (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~98 — Rendu tiles animees PixiJS (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~99 — Transitions de zone (S | ★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~100 — Sons basiques (L | ★★)~~ ✅
> Sons proceduraux via Web Audio API (sans fichiers audio externes).
- [x] SoundManager procedural (Web Audio API) — remplace Howler.js, zero dependance
- [x] Sons d'interface : clic bouton, ouverture menu, notification, succes, erreur
- [x] Sons de combat : attaque, sort, critique, miss, mort, victoire, defaite, fuite, bouclier, statut, boss phase
- [x] Sons exploration : pas, recolte, dialogue, level up, quete completee, item pickup
- [x] Bouton mute/volume dans les parametres joueur
- [x] Persistance preference son en localStorage

### ~~101 — Monitoring basique (M | ★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~102 — Index DB composites (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~103 — Achievements caches & categories succes (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~Escorte~~ (RETIRE)
> Le type "escorte" necessite un systeme de pathfinding PNJ, de combat en temps reel
> et d'IA de suivi qui n'existent pas. Complexite XL pour un gain faible.
> Reporte apres les systemes multijoueur/groupes si toujours pertinent.

### ~~Arbres de talent etendus~~ (RETIRE)
> Les 32 domaines ont deja 13-24 skills chacun (838 skills total). Les arbres sont deja
> etendus avec 3-5 tiers et des ultimates. Considere comme complete (Phase GD-6).
