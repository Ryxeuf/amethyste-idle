## Vague 3 — Contenu & enrichissement

> **18 taches** initiales, **10 completees**, 8 restantes.
> Organisees en 5 pistes paralleles.

---

### Piste A — Narration & quetes (‖)
### 46 — Trame Acte 1 : L'Eveil (M | ★★★)
> Tutoriel narratif. Chaine de 4-5 quetes guidant le joueur dans ses premieres actions. Utilise les systemes existants (kill, collect, deliver, explore) — pas de nouvelle mecanique. Prerequis : ← 12 (QN-3 prerequis quetes), 13 (QN-4 journal enrichi), 31 (QN-1 recompenses quetes)
- [ ] Quete 1.1 "Reveil" : dialogue d'introduction avec un PNJ guide, explorer le village
- [ ] Quete 1.2 "Premiers pas" : aller voir le forgeron, recevoir une arme de base
- [ ] Quete 1.3 "Bapteme du feu" : tuer 2 monstres faibles dans la zone de depart
- [ ] Quete 1.4 "Recolte" : collecter des ressources de base (herbes ou minerai)
- [ ] Quete 1.5 "Le cristal d'amethyste" : explorer un lieu specifique, dialogue revelateur
- [ ] Dialogues narratifs pour chaque PNJ implique (guide, forgeron, ancien du village)
- [ ] Recompenses progressives (equipement starter, gils, XP, premiere materia)

### ~~54 — Quetes a choix (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~55 — Quetes quotidiennes (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste B — Contenu monde (‖)

### ~~47 — Monstres tier 2 lvl 10-15 (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### 48 — Village central hub (L | ★★★)
> Nouvelle carte "Village central" servant de hub principal entre les zones. Prerequis : ← 30 (C-8 teleportation entre cartes), 25 (v0.4-A boutiques PNJ)
- [ ] Design carte Tiled (~40x40, interieur/exterieur) avec places, batiments
- [ ] Import BDD via app:terrain:import
- [ ] PNJ hub : forgeron, alchimiste, marchand, maitre des quetes, banquier (5-8 PNJ)
- [ ] Dialogues PNJ (JSON conditions)
- [ ] Portail bidirectionnel vers carte existante
- [ ] Aucun monstre (zone safe)

### ~~49 — Monstres soigneurs / multi-mobs (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste C — Meteo & visuels (‖)

### 50 — Meteo effets visuels PixiJS (M | ★★★)
> Effets visuels de meteo dans le renderer PixiJS (pluie, neige, orage, brouillard). Prerequis : ← 34 (MV-3 meteo backend & diffusion)
- [ ] Ecouter le topic Mercure `map/weather` dans `map_pixi_controller.js`
- [ ] Container de particules dedie (zIndex au-dessus des entites, sous le HUD)
- [ ] Effet pluie : particules tombantes bleues semi-transparentes
- [ ] Effet neige : particules blanches lentes avec oscillation laterale
- [ ] Effet orage : flash blanc intermittent (alpha spike sur l'overlay) + particules pluie
- [ ] Effet brouillard : overlay blanc semi-transparent avec alpha pulse doux
- [ ] Effet nuageux : leger assombrissement (overlay gris alpha 0.08)
- [ ] Transition douce entre meteos (fade 2 secondes)

### ~~62 — Particules combat et recolte (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~63 — Flash elementaire et animations combat (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste D — Social & builds (‖)

### 52 — Guildes fondation (M | ★★★)
> Premiere brique du systeme de guilde : creation et gestion des membres. Prerequis : ← 38 (MS-3 liste d'amis)

> **Plan controle de cite :** pour un decoupage implementation etendu (regions, saisons, influence, benefices economiques), voir [PLAN_GUILD_CITY_CONTROL.md](PLAN_GUILD_CITY_CONTROL.md). L'ancrage strategique du systeme « controle de cite » est en **Vague 4** de cette roadmap.
- [ ] Entite `Guild` (name unique, tag 3-5 chars, description, createdAt, leader: Player)
- [ ] Entite `GuildMember` (guild, player, rank: enum master/officer/member/recruit, joinedAt)
- [ ] Migrations + repositories
- [ ] GuildManager : create (cout en gils), invite, accept, leave, kick, promote, demote
- [ ] Route `GET /game/guild` : page de guilde (infos, liste membres avec rangs)
- [ ] Route `POST /game/guild/create` : formulaire creation
- [ ] Route `POST /game/guild/invite/{playerId}` : invitation (officier+ requis)
- [ ] Validation : nom unique, max 1 guilde par joueur, cout creation (ex: 5000 gils)
- [ ] Tests unitaires : creation, invitation, promotion, depart

### 53 — Groupes de combat formation (M | ★★★)
> Systeme de groupe pour jouer ensemble. Base pour le combat coop et donjons futurs. Prerequis : ← 38 (MS-3 liste d'amis)
- [ ] Entite `Party` (leader: Player, maxSize: 4, createdAt)
- [ ] Entite `PartyMember` (party, player, joinedAt)
- [ ] Migration + repository
- [ ] PartyManager : create, invite, accept, leave, kick, disband, transfer leader
- [ ] Topic Mercure `party/{partyId}` pour notifications groupe (invite, join, leave)
- [ ] Route `GET /game/party` : interface du groupe (membres, barres de vie)
- [ ] Route `POST /game/party/invite/{playerId}` : invitation
- [ ] Affichage des membres du groupe sur la carte (icone ou bordure coloree)
- [ ] Dissolution automatique si tous les membres partent
- [ ] Tests unitaires : creation, invitation, depart, dissolution

### ~~56 — Presets de build (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste E — Infra & qualite (‖)

### 57 — Commande terrain:sync (M | ★★)
> Commande unifiee qui orchestre tout le pipeline d'import Tiled. Prerequis : ← 44 (T2a extraction services depuis TerrainImportCommand)
- [ ] Creer `TerrainSyncCommand` : import TMX + upsert Area + sync entites + rebuild Dijkstra + rapport diff
- [ ] Integrer l'appel Dijkstra post-import (regeneration du cache collisions)
- [ ] Ajouter un rapport diff (entites creees/modifiees/supprimees)
- [ ] Mettre a jour l'agent `.claude/commands/import-terrain.md`

### 58 — Parsing zones/biomes Tiled (M | ★★★)
> Peuplement de l'entite Area depuis les objets rectangulaires de type "zone" dans Tiled. Prerequis : ← 44 (T2a extraction services)
- [ ] Ajouter les champs biome, weather, music, lightLevel sur l'entite `Area` + migration
- [ ] Parser les objets de type `zone`/`biome` dans TmxParser (rectangles avec proprietes)
- [ ] Creer `AreaSynchronizer` : upsert des Area depuis les zones Tiled
- [ ] Exposer les zones dans `/api/map/config` (coordonnees, biome, meteo, musique)

### 59 — Tests E2E Panther (M | ★★)
> Tests de parcours complets multi-pages valides via Panther. Prerequis : ← 23 (P6-3 tests fonctionnels controleurs), 42 (P6-5 tests integration evenements)
- [ ] Parcours combat : carte → engagement mob → combat → victoire → loot → retour carte
- [ ] Parcours quete : PNJ dialogue → accepter quete → tuer mob → rendre quete → recompense
- [ ] Parcours craft : inventaire → atelier → crafter → verifier item cree

### ~~60 — Minimap PixiJS (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~61 — Barre d'action rapide (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---
