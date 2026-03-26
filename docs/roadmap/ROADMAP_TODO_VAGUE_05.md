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

### ~~86 — Quetes de decouverte cachees (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~87 — Types quetes avances : enquete et defi boss (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

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

### ~~83 — Invasions (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

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

### ~~85 — Evenements aleatoires (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste D — Divers (‖)

### ~~88 — Stock boutique & restock (M | ★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~89 — Enchantements temporaires (S | ★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~90 — Herbier & catalogue minier (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.


---
