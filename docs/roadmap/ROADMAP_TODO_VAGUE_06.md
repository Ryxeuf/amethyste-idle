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

### 94 — Trame Acte 3 : La Convergence (L | ★★★)
> Donjon final. Prerequis : ← 80, 72
> A detailler quand les prerequis seront prets.
- [ ] Donjon final accessible apres les 4 fragments
- [ ] 3-5 salles avec puzzles, mobs, boss final
- [ ] Dialogues de conclusion et epilogue
- [ ] Recompenses de fin de trame (titre, equipement legendaire unique)

### ~~95 — Saisonnalite & festivals (S | ★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~97 — Parsing animations tiles (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~98 — Rendu tiles animees PixiJS (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~99 — Transitions de zone (S | ★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### 100 — Sons basiques (L | ★★)
> Optionnel. Ajoute de l'immersion mais necessite des assets sonores.
- [ ] Integrer Howler.js via importmap
- [ ] Sons d'interface : clic bouton, ouverture menu, notification
- [ ] Sons de combat : attaque, sort, critique, mort
- [ ] Sons d'ambiance : loop par biome (foret, grotte, village)
- [ ] Bouton mute/volume dans les parametres joueur
- [ ] Persistance preference son en localStorage

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
