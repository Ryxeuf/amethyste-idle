## Vague 5 — Endgame & contenu avance

> **10 taches** de contenu endgame et systemes avances.
> Organisees en 4 pistes paralleles.

---

### Piste A — Narration avancee (‖)

### 80 — Trame Acte 2 : Les Fragments (L | ★★★)
> 4 chaines de quetes dans 4 zones. Prerequis : ← 46, 67, 68
> A decouper en 4 sous-phases (1 par fragment/zone) quand les zones seront pretes.
- [ ] Fragment Foret : chaine de 3-4 quetes (exploration, combat, enigme PNJ)
- [ ] Fragment Mines : chaine de 3-4 quetes (recolte, craft, boss minier)
- [ ] Fragment Marais : chaine de 3-4 quetes (enquete, livraison, combat)
- [ ] Fragment Montagne : chaine de 3-4 quetes (exploration, defi de boss)
- [ ] Chaque fragment donne un item cle collectible

### 86 — Quetes de decouverte cachees (S | ★★)
> Quetes non visibles dans le journal tant que non declenchees. Recompense l'exploration. Prerequis : ← 13
- [ ] Champ `isHidden` (bool) sur Quest + champ `triggerCondition` (JSON)
- [ ] HiddenQuestTriggerListener : ecoute PlayerMoveEvent, SpotHarvestEvent, MobDeadEvent
- [ ] Si condition remplie, creer automatiquement le PlayerQuest + notification
- [ ] 3-4 quetes cachees dans les fixtures (lieu secret, mob rare, action inhabituelle)

### 87 — Types quetes avances : enquete et defi boss (M | ★★)
> Mecaniques plus complexes, a faire quand le contenu de base est solide. Prerequis : ← 31, 13
- [ ] Type `enquete` : requirements.talk_to = [{pnj_id, condition}], tracking sur dialogue PNJ
- [ ] Type `boss_challenge` : requirements.boss = {monster_slug, conditions: {no_heal, solo, time_limit}}
- [ ] Conditions de defi trackees dans le combat (FightController enregistre les contraintes)
- [ ] 2 quetes fixtures : 1 enquete (parler a 3 PNJ), 1 defi de boss

---

### Piste B — Combat (‖)

### 81 — Combat cooperatif (L | ★★★)
> Combat a plusieurs joueurs contre des groupes de monstres. Prerequis : ← 53, 49
> **Attention** : phase large, a re-decouper au moment de l'implementation.
- [ ] FightController : creer un combat avec plusieurs joueurs du meme groupe
- [ ] Timeline multi-joueurs dans FightTurnResolver
- [ ] Chaque joueur joue son tour independamment (Mercure pour notifier le tour actif)
- [ ] Template combat : afficher tous les joueurs allies avec leurs barres de vie
- [ ] Loot partage : round-robin par defaut (chaque joueur a son ecran de loot)
- [ ] XP partagee (repartition equitable entre participants)
- [ ] Tests : combat 2 joueurs, mort d'un joueur, loot repartition

### 83 — Invasions (M | ★★)
> Vagues de monstres cooperatives via GameEvent. Prerequis : ← 21, 35
- [ ] GameEventExecutor traite `invasion` : spawner N mobs supplementaires sur une zone (params JSON : mobSlugs, count, mapId)
- [ ] Vagues progressives : 3 vagues espacees de 2 min, difficulte croissante
- [ ] Tracker les kills de tous les joueurs pendant l'invasion
- [ ] Recompenses collectives si objectif atteint (X mobs tues avant la fin)
- [ ] Nettoyer les mobs d'invasion a la fin de l'event

---

### Piste C — Donjons & events (‖)

### 84 — Donjons mecaniques & loot (L | ★★★)
> Rend les donjons interessants avec des mecaniques propres. Prerequis : ← 72, 37
> A decouper en sous-phases si necessaire.
- [ ] Mobs du donjon : spawns specifiques au DungeonRun, stats scalees selon difficulte
- [ ] Boss de fin de donjon avec mecaniques de phase (reutiliser bossPhases existant)
- [ ] LootTable specifique donjon : items exclusifs par difficulte (utiliser minDifficulty de EG-5)
- [ ] Completion du donjon : marquer DungeonRun completed, teleporter le joueur hors du donjon
- [ ] Succes lies aux donjons (premier clear, clear Mythique, clear sans mort)

### 85 — Evenements aleatoires (M | ★★)
> Reutilise l'entite `GameEvent` existante. Ajoute du dynamisme au monde. Prerequis : ← 21
- [ ] `RandomEventGenerator` : selectionne un type d'evenement aleatoire selon des poids configurables
- [ ] Types : `invasion` (vague de mobs), `merchant` (marchand itinerant temporaire), `aurora` (buff XP zone)
- [ ] Commande Scheduler `app:events:random` (toutes les 30-60 min, probabilite 30%)
- [ ] Creer automatiquement un `GameEvent` avec duree limitee (10-30 min)
- [ ] Pour `invasion` : spawner des mobs temporaires sur la zone ciblee
- [ ] Pour `merchant` : creer un PNJ temporaire avec boutique speciale
- [ ] Pour `aurora` : activer un buff XP via Mercure broadcast
- [ ] Notification Mercure `game/event` pour alerter les joueurs connectes
- [ ] Bandeau visuel dans le HUD quand un evenement est actif

---

### Piste D — Divers (‖)

### ~~88 — Stock boutique & restock (M | ★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### 89 — Enchantements temporaires (S | ★)
> Alchimiste applique un buff temporaire sur une arme/armure. Prerequis : ← 26
- [ ] Entite `Enchantment` (playerItem, type, value, expiresAt)
- [ ] Migration SQL
- [ ] Service `EnchantmentManager` : apply(PlayerItem, enchantType, duration), tick(), remove()
- [ ] Route POST `/game/craft/enchant` (necessite skill alchimiste + ingredients)
- [ ] Expiration automatique (verifiee au debut de chaque combat ou via Scheduler)
- [ ] Fixtures : 3-4 enchantements (Tranchant de feu +5 degats feu 1h, Protection de glace +3 defense 30min, etc.)
- [ ] Tests EnchantmentManager

### 90 — Herbier & catalogue minier (S | ★★)
> Fiche par ressource, premiere decouverte, completion. Prerequis : ← 28
- [ ] Herbier & catalogue minier : fiche par ressource, premiere decouverte, completion


---
