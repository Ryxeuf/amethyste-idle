## Vague 4 — Monde & systemes avances

### Controle de cite par les guildes (plan detaille)

Competition PvE entre guildes pour le controle temporaire des villes : les guildes accumulent des points d'influence sur une saison via combat, craft, recolte et quetes dans les regions liees aux cartes.

**Prerequis roadmap globale :** **38** (liste d'amis), **48** (village central hub), **52** (guildes fondation — detail en Vague 3). La tache **52** couvre le socle minimal ; le plan dedie decrit entites fines, saisons, moteur d'influence et benefices economiques.

**Documentation :** [PLAN_GUILD_CITY_CONTROL.md](PLAN_GUILD_CITY_CONTROL.md) — *jalons internes **GCC-01** a **GCC-20** (sans collision avec les numeros de la roadmap globale). **GCC-01** prolonge la tache globale **52** (guildes fondation).*

---

> **16 taches** qui dependent des Vagues 2-3.
> Organisees en 5 pistes paralleles.

---

### Piste A — Contenu avance (‖)

### 64 — Equipement tier 3 + slots materia (M | ★★)
> Set avance avec slots materia integres pour les builds endgame. Prerequis : ← 29 (Equipement tier 2), 06 (Systeme materia fonctionnel)
- [ ] Set complet 7 pieces — 4 variantes elementaires (Metal, Bete, Lumiere, Ombre)
  - = 28 items au total
- [ ] 1-2 slots materia sur chaque piece avancee
- [ ] Ajouter aux loot tables des monstres lvl 15-25 et boss

---

### 65 — Monstres tier 2 avances lvl 15-25 (M | ★★★)
> Monstres complexes (soigneurs, invocateurs) pour les zones de contenu avance. Prerequis : ← 47 (Monstres tier 2 sous-partie A)
- [ ] **Sous-partie B** : 4 monstres intermediaires (lvl 15-25)
  - Monstres plus complexes (soigneurs, invocateurs selon combat enrichi)
  - Stats, AI patterns, resistances, loot tables
  - Succes bestiaire (12 achievements)

---

### 66 — Boss de zone (M | ★★★)
> Deux boss avec mecaniques de phases, loot unique et succes associes. Prerequis : ← 65 (Monstres tier 2 avances)
- [ ] **Sous-partie C** : 2 boss de zone avec mecaniques de phases
  - Boss Foret (element Bete/Terre, 2 phases)
  - Boss Mine (element Metal/Ombre, 3 phases)
  - Loot unique (equipement tier 3, materia rare)
  - Succes boss (2 achievements)

---

### 67 — Foret des murmures (L | ★★★)
> Carte de contenu lvl 5-15 avec monstres, PNJ, spots de recolte et quetes de zone. Prerequis : ← 30 (Teleportation entre cartes), 28 (Village central hub), 47 (Monstres tier 1)
- [ ] **C-10a — Foret des murmures (lvl 5-15)**
  - Design Tiled (~60x60), arbres, clairiere, riviere
  - Import BDD + portails vers hub
  - Placement 8-10 mobs (monstres C-3 + C-7a)
  - 3-5 PNJ (garde forestier, herboriste, ermite)
  - 5-8 spots de recolte (herbes, bois) — si v0.4 recolte pret
  - 2-3 quetes zone (si v0.4 quetes pret)

---

### 68 — Mines profondes (L | ★★★)
> Carte de contenu lvl 10-25 avec tunnels, boss de mine et filons a exploiter. Prerequis : ← 30 (Teleportation entre cartes), 47 (Monstres tier 1), 66 (Boss de zone)
- [ ] **C-10b — Mines profondes (lvl 10-25)**
  - Design Tiled (~60x30), tunnels, salles, filons
  - Import BDD + portails vers hub
  - Placement 8-10 mobs (monstres C-7a + C-7b)
  - Boss de mine (C-7c)
  - 3-5 PNJ (mineur, ingenieur, marchand souterrain)
  - 5-8 spots de recolte (minerais) — si v0.4 recolte pret
  - Coffre tresor (si systeme implemente)

---

### Piste B — Combat avance (‖)

### 69 — Monstres invocateurs (M | ★★)
> Monstres capables d'invoquer des renforts en cours de combat, rendant les combats dynamiques. Prerequis : ← 49 (Monstres soigneurs / multi-mobs)
- [ ] Nouvelle action IA `summon` dans MobActionHandler
- [ ] Creer un Mob en cours de combat (ajout a la Fight, insertion dans la timeline)
- [ ] Limite d'invocation (max 2 renforts par combat)
- [ ] FightTurnResolver : recalculer la timeline quand un mob est ajoute
- [ ] Fixtures : monstre invocateur (ex: Necromancien invoque des Squelettes)
- [ ] Message de log specifique ("X invoque un Y !")

---

### 70 — Slots materia lies (M | ★★)
> Synergie entre slots adjacents : bonus elementaire si les materia sockettees partagent le meme element. Prerequis : ← 06 (Systeme materia fonctionnel)
- [ ] Ajouter un champ `linkedSlotId` (nullable) sur l'entite Slot (migration)
- [ ] Logique `LinkedMateriaResolver` : si 2 slots lies ont des materia du meme element, bonus +15% degats
- [ ] Integrer le bonus dans CombatCapacityResolver
- [ ] Afficher le lien entre slots dans le template inventaire (trait visuel)
- [ ] Ajouter des slots lies sur quelques equipements avances dans les fixtures

---

### 71 — World boss spawn & combat (L | ★★★)
> Boss mondial spawn via evenements, visible sur la carte, combat multi-joueurs avec loot a contribution. Prerequis : ← 21 (Executeur GameEvent), 35 (Annonces Mercure evenements)
- [ ] **Sous-phase A — Spawn** : GameEventExecutor traite `boss_spawn` → creer un Mob boss sur une map donnee (params JSON)
- [ ] **Sous-phase A** : Afficher le world boss sur la carte avec un sprite/aura distinctif
- [ ] **Sous-phase A** : Despawn automatique quand l'event expire (si non vaincu)
- [ ] **Sous-phase B — Combat multi-joueurs** : Permettre a plusieurs joueurs d'engager le meme Mob (Fight partage)
- [ ] **Sous-phase B** : `ContributionTracker` : tracker les degats infliges par chaque joueur pendant le combat
- [ ] **Sous-phase B** : Loot base sur la contribution (top 3 = loot garanti, autres = loot probabiliste)
- [ ] Tester : spawn world boss via event admin, 2+ joueurs l'engagent, loot distribue

---

### Piste C — Donjons & events (‖)

### 72 — Donjons entite & entree (M | ★★★)
> Structure de donjon instancie : entite, difficultes, cooldown et point d'entree. Prerequis : ← 30 (Teleportation entre cartes)
- [ ] Entite `Dungeon` : slug, name, description, map (ManyToOne vers la carte du donjon), minLevel (int), maxPlayers (int)
- [ ] Entite `DungeonRun` : dungeon, player(s), startedAt, completedAt, difficulty (enum Normal/Heroique/Mythique)
- [ ] Enum `DungeonDifficulty` : Normal, Heroique, Mythique (avec multiplicateurs HP/degats mobs)
- [ ] Migration + fixtures : 1 donjon de test (ex: "Racines de la foret", lie a une carte existante ou nouvelle)
- [ ] Route `/game/dungeon/{slug}/enter` : creer un DungeonRun, teleporter le joueur dans la carte du donjon
- [ ] Route `/game/dungeon/{slug}` : fiche du donjon (description, difficulte, loot possible, cooldown)
- [ ] Cooldown entre runs (ex: 1h Normal, 4h Heroique, 24h Mythique)

---

### 79 — Evenements bonus/festivals (S | ★★)
> Integration des types xp_bonus et drop_bonus dans les calculs de jeu, plus quetes et cosmetiques d'evenement. Prerequis : ← 21 (Executeur GameEvent), 35 (Annonces Mercure evenements)
- [ ] Integrer `drop_bonus` dans LootGenerator : multiplier les probabilites de drop pendant l'event actif
- [ ] Integrer `xp_bonus` dans les systemes d'XP (combat, recolte, craft) : multiplier l'XP gagnee
- [ ] Quetes d'evenement : quetes temporaires liees a un GameEvent (disparaissent a la fin)
- [ ] Cosmetiques d'evenement : items decoratifs exclusifs comme recompenses

---

### Piste D — Social avance (‖)

### 73 — Guildes chat (S | ★★)
> Canal de communication dedie a la guilde via un nouveau topic Mercure. Prerequis : ← 52 (Guildes fondation)
- [ ] Nouveau channel `CHANNEL_GUILD` dans ChatMessage
- [ ] Topic Mercure `chat/guild/{guildId}` dans ChatManager
- [ ] Methodes `sendGuildMessage()` et `getGuildHistory()` dans ChatManager
- [ ] Onglet "Guilde" dans le chat (stimulus controller)
- [ ] Verification d'appartenance a la guilde avant envoi
- [ ] Tests unitaires : envoi, historique, joueur hors guilde refuse

---

### 74 — Guildes coffre partage (M | ★★)
> Inventaire collectif de guilde avec systeme de permissions par rang et tracabilite des actions. Prerequis : ← 52 (Guildes fondation)
- [ ] Entite `GuildVault` (guild, items: Collection, maxSlots: int)
- [ ] Entite `GuildVaultLog` (guild, player, action: deposit/withdraw, item, quantity, createdAt)
- [ ] GuildVaultManager : deposit, withdraw (permissions par rang)
- [ ] Route `GET /game/guild/vault` : affichage coffre + logs recents
- [ ] Route `POST /game/guild/vault/deposit` et `POST /game/guild/vault/withdraw`
- [ ] Permissions : recruit = depot seul, member+ = retrait, officier+ = tout
- [ ] Tests unitaires : depot, retrait, permissions, logs

---

### 75 — PNJ routines (L | ★★)
> Les PNJ se deplacent selon un horaire in-game, animes sur la carte via Mercure. Prerequis : ← 20 (Horloge in-game & API temps)
- [ ] Entite `PnjSchedule` (pnj, hour, coordinates, map) — table horaire du PNJ
- [ ] Migration SQL
- [ ] `PnjRoutineService` : deplace les PNJ selon l'heure in-game courante
- [ ] Commande Scheduler `app:pnj:routine` (toutes les 5 min)
- [ ] Topic Mercure `map/pnj-move` pour animer le deplacement cote client
- [ ] Animation de marche du PNJ dans le renderer PixiJS (reutiliser SpriteAnimator)
- [ ] Fixtures : 3-5 PNJ avec routines simples (maison ↔ travail ↔ taverne)
- [ ] Gerer le cas ou un joueur parle a un PNJ qui se deplace

---

### Piste E — Progression & equilibrage (‖)

### 76 — Sets d'equipement (M | ★★)
> Bonus progressifs quand plusieurs pieces du meme set sont portees simultanement. Prerequis : ← 17 (Raretes d'equipement), 29 (Equipement tier 2)
- [ ] Entite `EquipmentSet` (slug, name, description)
- [ ] Entite `EquipmentSetBonus` (set, requiredPieces, bonusType, bonusValue)
- [ ] Champ `equipmentSet` (ManyToOne, nullable) sur Item + migration
- [ ] Service `EquipmentSetResolver` : detecte les sets actifs depuis l'equipement du joueur
- [ ] Bonus appliques dans le combat (CombatSkillResolver) et affiches dans l'inventaire
- [ ] Fixtures : 2-3 sets de base (Set du Gardien 2/3/4 pieces, Set de l'Ombre 2/3 pieces)
- [ ] Affichage dans inventaire : pieces du set equipees, bonus actifs/inactifs
- [ ] Tests EquipmentSetResolver

---

### 77 — Effets ambiance par zone (M | ★★★)
> Detection de la zone courante du joueur et application d'effets visuels dynamiques en frontend. Prerequis : ← 58 (Parsing zones/biomes depuis Tiled)
- [ ] Charger les zones depuis l'API au chargement de la carte
- [ ] Detecter la zone courante du joueur (point-in-rect)
- [ ] Appliquer les effets par zone : teinte/overlay, particules (pluie, brume, poussiere)
- [ ] Transition fluide entre zones (fondu des effets)

---

### 78 — Equilibrage & rapport (M | ★★)
> Commande CLI de rapport d'equilibrage et document de reference pour ajuster les stats du jeu. Prerequis : ← 15 (Consommables de base), 17 (Raretes d'equipement), 28 (Monstres tier 2 sous-partie A), 29 (Equipement tier 2)
- [ ] Commande CLI `app:balance:report` :
  - Courbe XP par domaine (actuel vs theorique)
  - Stats monstres par palier (HP, degats, XP donne)
  - Table de drop rates par tier
  - Alertes si desequilibre detecte (monstre trop fort/faible, drop rate aberrant)
- [ ] Document de reference `docs/BALANCE.md` :
  - Courbe de progression XP cible par domaine
  - Bareme des prix boutique (ratio achat/vente 30-50%)
  - Degats/HP attendus par palier de monstre
  - Temps de recolte et rendement par skill level
- [ ] Ajustement des stats monstres/items si desequilibre constate


---
