## Vague 6 — Long terme & polish final

> **13 taches** a planifier quand le contenu de base est solide.
> Aucune urgence — objectifs long terme.

---

### 91 — Arene PvP classee (L | ★★)
> Systeme competitif avec matchmaking et classement. Prerequis : ← 82
> **Attention** : phase large, a re-decouper au moment de l'implementation.
- [ ] Entite `ArenaRating` (player, rating ELO, wins, losses, season)
- [ ] Entite `ArenaSeason` (number, startDate, endDate, active)
- [ ] File d'attente matchmaking (recherche adversaire +/- 200 ELO)
- [ ] Calcul ELO apres chaque match
- [ ] Route `GET /game/arena` : classement, stats personnelles, bouton recherche
- [ ] Recompenses de fin de saison (titres, items cosmetiques)
- [ ] Tests : matchmaking, calcul ELO, classement

### 92 — Classement guildes (S | ★)
> Tableau de classement simple par points de guilde. Prerequis : ← 52
- [ ] Champ `points` sur Guild (incremente par succes membres, quetes, PvP)
- [ ] Route `GET /game/guilds/ranking` : classement pagine
- [ ] GuildPointsListener : ajoute des points sur MobDeadEvent, QuestCompletedEvent, ArenaDuelEndedEvent
- [ ] Tests : attribution points, classement ordonne

### 93 — Quetes de guilde (M | ★★)
> Objectifs collectifs hebdomadaires. Prerequis : ← 52, 92
- [ ] Entite `GuildQuest` (guild, type: kill/collect/craft, target, progress, goal, reward, expiresAt)
- [ ] GuildQuestManager : generer 3 quetes hebdomadaires, tracker progression, distribuer recompenses
- [ ] Listeners sur MobDeadEvent, SpotHarvestEvent, CraftEvent pour progression collective
- [ ] Route `GET /game/guild/quests` : liste quetes actives avec barres de progression
- [ ] Recompenses : gils + points de guilde pour tous les membres
- [ ] Tests : progression, completion, recompenses

### 94 — Trame Acte 3 : La Convergence (L | ★★★)
> Donjon final. Prerequis : ← 80, 72
> A detailler quand les prerequis seront prets.
- [ ] Donjon final accessible apres les 4 fragments
- [ ] 3-5 salles avec puzzles, mobs, boss final
- [ ] Dialogues de conclusion et epilogue
- [ ] Recompenses de fin de trame (titre, equipement legendaire unique)

### 95 — Saisonnalite & festivals (S | ★)
> Contenu evenementiel saisonnier. Prerequis : ← 20, 85
- [ ] Detection de la saison reelle (printemps/ete/automne/hiver) dans `GameTimeService`
- [ ] Poids meteo ajustes par saison (plus de neige en hiver, plus d'orages en ete)
- [ ] Entite `Festival` (slug, name, season, startDay, endDay, quests, rewards)
- [ ] 4 festivals de base (1 par saison) — contenu a definir plus tard
- [ ] Decorations saisonnieres sur la carte (sprites overlays)

### 96 — Tournois PvP (XL | ★★)
> Prerequis : ← 91. Trop dependant d'autres systemes pour le court terme.
- [ ] Entite `Tournament` : type, bracket, dates, recompenses
- [ ] Inscription et matchmaking par bracket
- [ ] Deroulement automatique (ou semi-auto) des rounds
- [ ] Classement et recompenses saisonnieres

### 97 — Parsing animations tiles (S | ★★)
> Les fichiers TSX contiennent des animations. Le backend les ignore. Prerequis : ← 44
- [ ] Etendre le parsing TSX dans TmxParser : extraire les frames d'animation (tileId, duration)
- [ ] Exposer les animations dans `/api/map/config` (tableau par GID : frames + durations)

### 98 — Rendu tiles animees PixiJS (M | ★★★)
> Remplacer PIXI.Sprite par PIXI.AnimatedSprite pour les tiles animees. Prerequis : ← 97
- [ ] Dans `_renderCell()` : detecter les tiles animees depuis les donnees API
- [ ] Creer des `PIXI.AnimatedSprite` avec les frames/durations pour ces tiles
- [ ] Gerer le cycle d'animation (elapsed time, frame index) dans le ticker
- [ ] Tester visuellement (eau animee, torches, etc.)

### 99 — Transitions de zone (S | ★)
> Fondu au noir lors du changement de carte/teleportation. Prerequis : ← 30
- [ ] Overlay noir plein ecran avec alpha 0→1→0 (PIXI.Graphics + GSAP ou requestAnimationFrame)
- [ ] Declenchement sur teleportation portail
- [ ] Declenchement sur changement de map

### 100 — Sons basiques (L | ★★)
> Optionnel. Ajoute de l'immersion mais necessite des assets sonores.
- [ ] Integrer Howler.js via importmap
- [ ] Sons d'interface : clic bouton, ouverture menu, notification
- [ ] Sons de combat : attaque, sort, critique, mort
- [ ] Sons d'ambiance : loop par biome (foret, grotte, village)
- [ ] Bouton mute/volume dans les parametres joueur
- [ ] Persistance preference son en localStorage

### 101 — Monitoring basique (M | ★)
> Utile en production pour detecter les problemes, mais pas bloquant pour le gameplay.
- [ ] Endpoint `/health` (status BDD, Mercure, cache)
- [ ] Metriques Prometheus via `prometheus-metrics-bundle` (requetes/s, temps reponse, erreurs)
- [ ] Dashboard Grafana minimal (4-5 panels : requetes, latence, erreurs, joueurs connectes)
- [ ] Alertes basiques (latence > 2s, erreurs > 5/min)

### 102 — Index DB composites (S | ★★)
> Ameliore les performances sur les tables critiques sans changement de code.
- [ ] Migration : index composite `(player_id, map_id)` sur table player/position
- [ ] Migration : index composite `(fight_id, turn)` sur FightLog
- [ ] Migration : index sur `(player_id, quest_id)` sur PlayerQuest
- [ ] Migration : index sur `(monster_slug, player_id)` sur BestiaryEntry

### 103 — Achievements caches & categories succes (S | ★★)
> Enrichissement du systeme de succes existant.
- [ ] Achievements caches : decouverts par des actions inhabituelles
- [ ] Categories de succes additionnelles : Recolte, Craft, Social, Secrets

---

### ~~Escorte~~ (RETIRE)
> Le type "escorte" necessite un systeme de pathfinding PNJ, de combat en temps reel
> et d'IA de suivi qui n'existent pas. Complexite XL pour un gain faible.
> Reporte apres les systemes multijoueur/groupes si toujours pertinent.

### ~~Arbres de talent etendus~~ (RETIRE)
> Les 32 domaines ont deja 13-24 skills chacun (838 skills total). Les arbres sont deja
> etendus avec 3-5 tiers et des ultimates. Considere comme complete (Phase GD-6).
