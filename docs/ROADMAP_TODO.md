# Roadmap a venir — Amethyste-Idle

> Toutes les taches restantes a implementer, organisees en 6 vagues de priorite.
> Numerotation unifiee : chaque tache a un identifiant unique (01 a 77).
> Derniere mise a jour : 2026-03-20

---

## Legende

| Symbole | Signification |
|---------|---------------|
| S / M / L / XL | Complexite (Small < Medium < Large < XL) |
| ★★★ | Gain gameplay fort |
| ★★ | Gain gameplay moyen |
| ★ | Gain gameplay faible |
| ∅ | Aucun prerequis |
| ← XX | Depend de la tache XX |
| ‖ | Parallelisable avec les autres taches du meme bloc |

---

## Graphe de dependances global

```
VAGUE 1 (aucun prerequis — tout en parallele)
  ┌─ ✅ 01 De-hardcoder map IDs (FAIT) ────────────────────────────────┐
  ├─ ✅ 02 Supprimer CSS mort (FAIT)                                    │
  ├─ 03 Optimisation queries N+1                                      │
  ├─ ✅ 04 Rate limiting API (FAIT)                                    │
  ├─ 05 Consolidation craft (supprimer doublon)                       │
  ├─ 06 Materia unlock verification (reliquat GD-8)                   │
  ├─ ✅ 07 Raretes d'equipement (FAIT)                                  │
  ├─ ✅ 08 Combat log frontend (FAIT)                                  │
  ├─ ✅ 09 Icones statuts timeline combat (FAIT)                        │
  ├─ 10 Indicateur difficulte monstres                                │
  ├─ 11 Recompenses uniques de boss                                   │
  ├─ ✅ 12 Recompenses de quetes completes (FAIT)                       │
  ├─ 13 Prerequis de quetes et chaines                                │
  ├─ 14 Respec basique                                                │
  ├─ 15 Consommables de base                                          │
  ├─ 16 Materia complement (8 nouvelles)                              │
  ├─ 17 Equipement tier 1 Starter                                     │
  ├─ 18 Commandes chat slash                                          │
  ├─ 19 Profil joueur public                                          │
  ├─ 20 Horloge in-game & API temps                                   │
  ├─ 21 GameEvent executor                                            │
  ├─ 22 Factions & reputation (entites)                               │
  ├─ 23 Tests fonctionnels controleurs                                │
  ├─ 24 Notifications toast in-game                                   │
  └─ ✅ 25 Boutiques PNJ fixtures (FAIT)                               │
                                                                      │
VAGUE 2 (depend de Vague 1)                                           │
  ┌─ 26 Recettes de craft fixtures ← 05                               │
  ├─ 27 Tracking quetes collect/craft ← 05                            │
  ├─ 28 Monstres tier 1 (8 mobs) ← 15                                │
  ├─ 29 Equipement tier 2 ← 17                                        │
  ├─ 30 Teleportation entre cartes ← 01 ─────────────────────────────┘
  ├─ 31 Types quetes livraison/exploration ← 27
  ├─ 32 Journal de quetes enrichi ← 13
  ├─ 33 Impact gameplay jour/nuit ← 20
  ├─ 34 Meteo backend & diffusion ← 20
  ├─ 35 Annonces Mercure evenements ← 21
  ├─ 36 Gains et recompenses reputation ← 22
  ├─ 37 Loot exclusif et rarete etendue ← 07
  ├─ 38 Liste d'amis ← 19
  ├─ 39 Limite points multi-domaine ← 14
  ├─ 40 Synergies cross-domaine (∅ strict mais logique apres 14)
  ├─ 41 Indicateurs quetes sur PNJ ← 27
  ├─ 42 Tests unitaires systemes core ← 25, 26, 27
  ├─ 43 Tests integration events ← 23
  ├─ 44 Extraction services TerrainImport ← 01
  └─ 45 Portraits de personnages (∅ strict)

VAGUE 3 (depend de Vague 2)
  ┌─ 46 Trame Acte 1 : L'Eveil ← 12, 13, 31
  ├─ 47 Monstres tier 2 (lvl 10-15) ← 28, 29
  ├─ 48 Village central hub ← 30, 25
  ├─ 49 Monstres soigneurs (multi-mobs) ← 28
  ├─ 50 Meteo effets visuels PixiJS ← 34
  ├─ 51 Meteo impact gameplay ← 34
  ├─ 52 Guildes fondation ← 38
  ├─ 53 Groupes de combat formation ← 38
  ├─ 54 Quetes a choix ← 13, 31
  ├─ 55 Quetes quotidiennes ← 12, 27
  ├─ 56 Presets de build ← 14
  ├─ 57 Commande terrain:sync ← 44
  ├─ 58 Parsing zones/biomes Tiled ← 44
  ├─ 59 Tests E2E Panther ← 23, 42
  ├─ 60 Minimap PixiJS (∅)
  ├─ 61 Barre d'action rapide (∅)
  ├─ 62 Particules combat/recolte (∅)
  └─ 63 Flash elementaire combat (∅)

VAGUE 4 (depend de Vague 3)
  ┌─ 64 Equipement tier 3 + slots materia ← 29, 06
  ├─ 65 Monstres tier 2 avances (lvl 15-25) ← 47
  ├─ 66 Boss de zone ← 65
  ├─ 67 Foret des murmures ← 30, 28, 47
  ├─ 68 Mines profondes ← 30, 47, 65
  ├─ 69 Monstres invocateurs ← 49
  ├─ 70 Slots materia lies ← 06
  ├─ 71 World boss ← 21, 35
  ├─ 72 Donjons entite & entree ← 30
  ├─ 73 Guildes chat ← 52
  ├─ 74 Guildes coffre ← 52
  ├─ 75 PNJ routines ← 20
  ├─ 76 Sets d'equipement ← 17, 29
  ├─ 77 Effets ambiance zones ← 58
  ├─ 78 Equilibrage & rapport ← 15, 17, 28, 29
  └─ 79 Evenements bonus/festivals ← 21, 35

VAGUE 5 (depend de Vague 4)
  ┌─ 80 Trame Acte 2 : Les Fragments ← 46, 67, 68
  ├─ 81 Combat cooperatif ← 53, 49
  ├─ 82 Duels PvP ← 38
  ├─ 83 Invasions ← 21, 35
  ├─ 84 Donjons mecaniques & loot ← 72, 37
  ├─ 85 Evenements aleatoires ← 21
  ├─ 86 Quetes cachees ← 13
  ├─ 87 Types quetes avances ← 31, 13
  ├─ 88 Stock boutique & restock ← 25
  ├─ 89 Enchantements temporaires ← 26
  └─ 90 Herbier & catalogue ← 28

VAGUE 6 — Long terme
  ┌─ 91 Arene PvP classee ← 82
  ├─ 92 Classement guildes ← 52
  ├─ 93 Quetes de guilde ← 52, 92
  ├─ 94 Trame Acte 3 : La Convergence ← 80, 72
  ├─ 95 Saisonnalite & festivals ← 20, 85
  ├─ 96 Tournois PvP ← 91
  ├─ 97 Parsing animations tiles ← 44
  ├─ 98 Rendu tiles animees ← 97
  ├─ 99 Transitions de zone ← 30
  ├─ 100 Sons basiques
  ├─ 101 Monitoring
  ├─ 102 Index DB composites
  └─ 103 Achievements caches & categories succes
```

---

## Vague 1 — Fondations & Quick Wins

> **25 taches** sans aucun prerequis, toutes realisables immediatement.
> Organisees en 5 pistes paralleles. Toutes les pistes sont independantes entre elles.
> Toutes les taches au sein d'une meme piste sont aussi independantes entre elles (sauf mention contraire).

---

## Piste A — Dette technique & performance (‖)


---

### 03 — Optimisation queries N+1 (S | ★★★)

> Impact direct sur les temps de chargement. Pas de nouveau code metier, juste du tuning. Prerequis : ∅

- [ ] Auditer les requetes avec Symfony Profiler (toolbar) sur les pages critiques (carte, combat, inventaire)
- [ ] Ajouter `fetch: EAGER` ou `->addSelect()->leftJoin()` sur les relations N+1 detectees
- [ ] Index composites : `(player_id, map_id)` sur positions, `(fight_id, turn)` sur FightLog
- [ ] Mesurer avant/apres (nombre de queries par page)

---

---

## Piste B — Corrections gameplay core (‖)

### 05 — Consolidation craft : supprimer le systeme duplique (S | ★★)

> Deux systemes concurrents (CraftManager/CraftController + CraftingManager/CraftingController) creent de la confusion. Garder un seul systeme, supprimer l'autre. Prerequis : ∅

- [ ] Auditer les 2 systemes : identifier lequel est le plus complet (CraftManager vs CraftingManager)
- [ ] Supprimer le systeme redondant (controller, manager, templates, entity si applicable)
- [ ] Mettre a jour les references (routes, liens dans templates, DOCUMENTATION.md)

---

### 06 — Materia unlock verification — reliquat GD-8 (S | ★★★)

> Verification `actions.materia.unlock` dans CombatCapacityResolver avant d'autoriser un sort. Prerequis : ∅

- [ ] Verification `actions.materia.unlock` dans CombatCapacityResolver avant d'autoriser un sort
- [ ] Methode `getUnlockedMateriaSpellSlugs(Player)` dans CombatSkillResolver
- [ ] `canEquipMateria()` dans PlayerItemHelper : verifier competence requise
- [ ] Validation cote controleur dans FightSpellController
- [ ] Griser les sorts sans skill requis dans les templates combat

---

---

### ~~12 — Recompenses de quetes completes~~ ✅ FAIT

---

### 13 — Prerequis de quetes et chaines (S | ★★★)

> Permet de creer des chaines Q1→Q2→Q3. Le PnjDialogParser supporte deja `quest` et `quest_not`. Prerequis : ∅

- [ ] Ajouter champ `prerequisiteQuests` (JSON, nullable) sur l'entite Quest (migration)
- [ ] Verifier les prerequis dans QuestController::accept() (refuser si prerequis non remplis)
- [ ] Adapter PnjDialogParser : afficher la quete suivante seulement si prerequis remplis
- [ ] 1 chaine de 3 quetes dans les fixtures (Q1→Q2→Q3 avec recompense finale)
- [ ] Afficher les quetes disponibles (prerequis ok, non acceptees, non completees) dans le journal

---

### 14 — Respec basique (S | ★★★)

> Prerequis : aucun. Debloque l'experimentation de builds. Prerequis : ∅

- [ ] Service `SkillRespecManager` : retire tous les skills du joueur, rembourse l'XP usee dans chaque `DomainExperience`
- [ ] Cout en gils (formule : 50 * nombre de skills acquis), prix croissant a chaque respec (+25% par respec, stocke dans Player)
- [ ] Champ `respecCount` (int, default 0) sur Player + migration
- [ ] Route POST `/game/skills/respec` + confirmation modale cote template
- [ ] Bouton "Redistribuer" dans la page /game/skills
- [ ] Tests unitaires SkillRespecManager (respec OK, fonds insuffisants, prix croissant)

---

### ~~25 — Boutiques PNJ fixtures~~ ✅ FAIT

---

## Piste C — Combat polish (‖)

---

### ~~09 — Icones statuts timeline combat~~ ✅ FAIT

---

### 10 — Indicateur difficulte monstres (S | ★★)

> Complexite: Faible | Priorite: Moyenne | Gain: Lisibilite pour le joueur. Prerequis : ∅

- [ ] Champ `difficulty` (int 1-5) sur l'entite Monster (migration)
- [ ] Afficher des etoiles dans le template combat (a cote du nom du mob)
- [ ] Afficher les etoiles dans le bestiaire
- [ ] Renseigner la difficulte dans MonsterFixtures

---

### 11 — Recompenses uniques de boss (S | ★★★)

> Complexite: Faible | Priorite: Haute | Gain: Motivation a affronter les boss. Prerequis : ∅

- [ ] Ajouter des items legendaires boss-only dans les ItemFixtures (1-2 items par boss)
- [ ] Configurer les LootTable des boss existants avec drop garanti d'un item legendaire
- [ ] Badge "Boss" sur les items dans l'inventaire (via item.rarity ou lootSource)

---

## Piste D — Contenu de base (‖)

### 15 — Consommables de base (S | ★★★)

> Aucun prerequis v0.4. Utilisable immediatement en combat via use_spell existant. Prerequis : ∅

- [ ] 5 potions (soin mineur/moyen/majeur, mana, antidote) — fixtures YAML
- [ ] 3 nourritures (pain, viande grillee, ragoût) — buff temporaire HP regen
- [ ] 3 parchemins (teleportation spawn, boost XP 10min, identification)
- [ ] Ajouter les items aux loot tables des monstres existants

---

### 16 — Materia complement — 8 nouvelles (M | ★★★)

> Aucun prerequis. Enrichit le combat directement (10 existantes → 18). Prerequis : ∅

- [ ] 8 nouvelles materia (1 par element : basique tier 2)
  - Feu: Combustion, Eau: Brume glaciale, Air: Eclair en chaine
  - Terre: Mur de pierre, Metal: Riposte d'acier, Bete: Morsure sauvage
  - Lumiere: Benediction, Ombre: Drain vital
- [ ] Sorts associes dans SpellFixtures (si non existants)
- [ ] Lien materia → skill unlock dans les arbres de talent existants

---

### 17 — Equipement tier 1 Starter (M | ★★)

> Aucun prerequis. Remplace/complete les 5 pieces existantes. Prerequis : ∅

- [ ] Set complet 7 pieces (arme, casque, plastron, jambieres, bottes, gants, bouclier) — element None, stats basiques
- [ ] Ajouter aux loot tables des monstres lvl 1-5
- [ ] Verifier l'affichage dans l'inventaire/equipement

---

## Piste E — Social & systemes transverses (‖)

### 18 — Commandes chat slash (S | ★★★)

> Le chat fonctionne mais n'a pas de commandes slash. Amelioration UX immediate. Prerequis : ∅

- [ ] Parser de commandes dans ChatManager : detecter `/whisper <nom> <msg>`, `/zone <msg>`, `/global <msg>`
- [ ] Commande `/emote <action>` : afficher "*Joueur danse*" en italique dans le chat
- [ ] Commande `/who` : lister les joueurs presents sur la meme carte
- [ ] Feedback d'erreur si commande inconnue ou arguments invalides
- [ ] Tests unitaires : parsing commandes, cas d'erreur

---

### 19 — Profil joueur public (S | ★★★)

> Prerequis social de base : voir les infos d'un autre joueur avant toute interaction avancee. Prerequis : ∅

- [ ] Route `GET /game/player/{id}/profile` : nom, classe, race, succes, domaines principaux
- [ ] Template profil public (stats non-sensibles, achievements notables, titre)
- [ ] Lien cliquable sur les noms de joueurs dans le chat et sur la carte
- [ ] Tests fonctionnels : acces profil, joueur inexistant

---

### 20 — Horloge in-game & API temps (S | ★★)

> Fondation obligatoire pour toutes les autres sous-phases monde vivant. Prerequis : ∅

- [ ] `GameTimeService` : convertit le temps reel en temps in-game (1h reelle = 1 journee, ratio configurable)
- [ ] Methodes `getHour()`, `getMinute()`, `getTimeOfDay()` (dawn/day/dusk/night), `getSeason()`
- [ ] Parametre Symfony `game.time_ratio` (configurable admin via `parameters.yaml`)
- [ ] Route API `GET /api/game/time` (heure in-game, periode, saison)
- [ ] Adapter `_computeTimeOfDay()` dans `map_pixi_controller.js` pour utiliser l'API au lieu du temps reel
- [ ] Affichage discret de l'heure in-game dans le HUD carte

---

### 21 — GameEvent executor (S | ★★★)

> GameEvent existe en BDD avec admin CRUD mais rien ne se passe quand un event est ACTIVE. Ce service est le socle de tout le contenu endgame evenementiel (world boss, invasions, bonus XP/drop). Prerequis : ∅

- [ ] Creer `GameEventExecutor` : service qui lit les GameEvent SCHEDULED dont startsAt <= now, les passe ACTIVE
- [ ] Traiter les types existants : `xp_bonus` (modifier global XP), `drop_bonus` (modifier global drop rate)
- [ ] Creer `GameEventSchedulerMessage` + handler Symfony Scheduler (toutes les 60s)
- [ ] Passer les events expires (endsAt < now) en COMPLETED automatiquement
- [ ] Gerer la recurrence : si `recurrenceInterval` non null, creer le prochain event a la completion
- [ ] Tester : creer un event xp_bonus via admin, verifier qu'il s'active et expire correctement

---

### 22 — Factions & reputation — entites (M | ★★★)

> Systeme autonome sans prerequis technique lourd. Ajoute une boucle de progression endgame et permet de gater du contenu (recettes, equipements, zones) derriere des paliers de reputation. Prerequis : ∅

- [ ] Entite `Faction` : slug, name, description, icon
- [ ] Entite `PlayerFaction` : player (ManyToOne), faction (ManyToOne), reputation (int), tier (enum)
- [ ] Enum `ReputationTier` : Inconnu(0), Hostile(-1), Neutre(1), Ami(2), Honore(3), Revere(4), Exalte(5)
- [ ] Calcul automatique du tier selon les seuils de reputation (0, 500, 2000, 5000, 10000, 20000)
- [ ] Migration + fixtures 4 factions (Marchands, Chevaliers, Mages, Ombres) avec description et PNJ associe
- [ ] Route `/game/factions` : liste des factions, reputation actuelle, palier, barre de progression

---

### 23 — Tests fonctionnels controleurs (M | ★★★)

> 0 test fonctionnel pour shop, inventory, skills, bestiary, achievements. Fragilise la base. Prerequis : ∅

- [ ] Test ShopController : achat OK, fonds insuffisants, item inexistant
- [ ] Test InventoryController : equiper, desequiper, utiliser consommable
- [ ] Test SkillController : acquerir skill, XP insuffisante, prerequis manquant
- [ ] Test BestiaryController : acces page, filtres, affichage paliers
- [ ] Test AchievementController : acces page, succes debloque vs verrouilles

---

### 24 — Notifications toast in-game (M | ★★★)

> Aucun systeme de notification generaliste. Seul FightNotification existe (combat only). Impact fort : feedback immediat pour toutes les actions du joueur. Prerequis : ∅

- [ ] Composant Stimulus `toast_controller.js` : affiche des toasts empiles en bas-droite (auto-dismiss 4s)
- [ ] 4 types visuels : succes (vert), info (bleu), alerte (orange), erreur (rouge)
- [ ] Integration dans les evenements existants :
  - Drop d'item apres combat (ecran loot)
  - XP gagnee / domaine level-up
  - Quete completee / objectif progresse
  - Succes debloque
- [ ] Helper Twig `toast()` ou data-attribute Stimulus pour declencher depuis le serveur


---

## Vague 2 — Systemes core completes

> **20 taches** qui dependent de la Vague 1.
> Organisees en 5 pistes paralleles.
> Les pistes sont independantes entre elles. Les dependances intra-piste sont indiquees.

---

## Piste A — Donnees & fixtures (‖)

### 26 — Recettes de craft fixtures (M | ★★★)
> Le systeme de craft existe mais 0 recette en base. Sans donnees, le craft est inutilisable. Prerequis : ← 05
- [ ] Creer CraftRecipeFixtures avec ~10 recettes de base :
  - Forge : epee en fer, bouclier en fer, casque en fer (ingredients : minerai de fer)
  - Alchimie : potion de soin, potion de mana (ingredients : herbes)
  - Tannerie : armure en cuir (ingredients : cuir brut)
  - Joaillerie : anneau simple (ingredients : minerai d'argent/or)
- [ ] Verifier que les items ingredients existent dans ItemFixtures (creer si manquants)
- [ ] Tester manuellement : acceder a un atelier, crafter un item, verifier inventaire

### 27 — Tracking quetes collect/craft (M | ★★★)
> PlayerQuestHelper ne traite que les objectifs `monsters`. Les quetes collect et craft ne progressent jamais. Prerequis : ← 05
- [ ] Ajouter le tracking `collect` dans PlayerQuestHelper::getPlayerQuestProgress()
- [ ] Creer QuestCollectTrackingListener : ecoute SpotHarvestEvent, met a jour les quetes actives
- [ ] Creer QuestCraftTrackingListener : ecoute CraftEvent, met a jour les quetes actives
- [ ] Verifier que les 2 quetes existantes avec objectif collect (mushroom, wood) progressent
- [ ] Tests unitaires : progression collect, progression craft, completion automatique

### 28 — Monstres tier 1 — 8 mobs elementaires (M | ★★★)
> 20 monstres existent, on en ajoute 8 pour couvrir chaque element. Prerequis : ← 15
- [ ] 8 monstres elementaires niveaux 1-10 :
  - Feu: Salamandre (lvl 3), Eau: Ondine (lvl 2), Air: Sylphe (lvl 4)
  - Terre: Golem d'argile (lvl 5), Metal: Automate rouille (lvl 3)
  - Bete: Loup alpha (lvl 4), Lumiere: Feu follet (lvl 2), Ombre: Ombre rampante (lvl 5)
- [ ] Stats, AI patterns, resistances elementaires pour chaque monstre
- [ ] Tables de loot (drops ressources + consommables C-1)
- [ ] Succes bestiaire (3 paliers x 8 monstres = 24 achievements)
- [ ] Placement sur la carte existante (MobFixtures)

### 29 — Equipement tier 2 Intermediaire (M | ★★)
> Set complet avec variantes elementaires. Prerequis : ← 17
- [ ] Set complet 7 pieces — 4 variantes elementaires (Feu, Eau, Terre, Air)
  - = 28 items au total (7 pieces x 4 elements)
- [ ] Bonus elementaire sur chaque piece (+10% degats element)
- [ ] Ajouter aux loot tables des monstres lvl 5-15

---

## Piste B — Systemes quetes & carte (‖)

### 30 — Teleportation entre cartes (L | ★★★)
> Prerequis technique pour toute nouvelle carte. Infrastructure portail existe dans Tiled. Prerequis : ← 01
- [ ] Entite Portal (sourceMap, sourceCoordinates, targetMap, targetCoordinates, requiredLevel, bidirectional)
- [ ] Migration SQL
- [ ] PortalManager : teleport(Player, Portal) — validation + deplacement
- [ ] Endpoint POST /api/map/teleport/{portalId}
- [ ] Rendu visuel portail sur la carte PixiJS (sprite anime + particules)
- [ ] Interaction joueur (clic/touche sur portail → confirmation → teleportation)
- [ ] Transition visuelle (fade existant)
- [ ] Topic Mercure map/teleport pour notifier les joueurs
- [ ] Fixtures portails sur la carte existante (2-3 portails de test)
- [ ] Tests PortalManager

### 31 — Types quetes livraison/exploration (M | ★★★)
> Prerequis : v0.4-D (tracking collect/craft). Ajoute 2 types de quetes realisables avec l'infra existante. Prerequis : ← 27
- [ ] Ajouter support `requirements.deliver` dans QuestTrackingFormater : {item_slug, pnj_id, quantity}
- [ ] Tracking livraison : listener sur dialogue PNJ, verifier si le joueur a l'item en inventaire
- [ ] Ajouter support `requirements.explore` dans QuestTrackingFormater : {map_id} ou {coordinates}
- [ ] Tracking exploration : listener sur PlayerMoveEvent, verifier si zone/coordonnees atteintes
- [ ] 2-3 quetes fixtures : 1 livraison (apporter item a un PNJ), 1 exploration (atteindre un lieu)
- [ ] Tests unitaires : progression livraison, progression exploration

### 32 — Journal de quetes enrichi (S | ★★★)
> Le journal existe mais est basique. Ajout d'un onglet "disponibles" et meilleure UX. Prerequis : ← 13
- [ ] Onglet "Disponibles" : lister les quetes dont les prerequis sont remplis et non encore acceptees
- [ ] Filtrage par type de quete (kill, collect, deliver, explore)
- [ ] Afficher le PNJ donneur de quete (nom + localisation) pour chaque quete
- [ ] Indicateur de chaine : afficher "Quete 2/3" si la quete fait partie d'une chaine
- [ ] Lien vers la carte pour localiser le PNJ donneur

### 41 — Indicateurs quetes sur PNJ (S | ★★★)
> Aucun indicateur visuel (! ou ?) n'apparait au-dessus des PNJ donneurs de quetes sur la carte. Prerequis : ← 27
- [ ] Ajouter un champ `hasAvailableQuest` dans /api/map/entities pour les PNJ
- [ ] Afficher une icone (! quete dispo, ? quete en cours) au-dessus du sprite PNJ dans PixiJS
- [ ] Mettre a jour l'icone dynamiquement quand le joueur accepte/complete une quete

---

## Piste C — Monde vivant & events (‖)

### 33 — Impact gameplay jour/nuit (M | ★★★)
> Prerequis : MV-1. Donne une raison concrete au cycle jour/nuit. Prerequis : ← 20
- [ ] Champ `nocturnal` (bool) sur l'entite `Mob` — mobs nocturnes n'apparaissent que de nuit
- [ ] Filtre dans `MobSpawnManager` : exclure mobs nocturnes le jour, mobs diurnes la nuit
- [ ] Champ `nightOnly` (bool) sur `HarvestSpot` — plantes de nuit recoltables uniquement la nuit
- [ ] Verification dans `HarvestProcessor`
- [ ] Champ `opensAt`/`closesAt` (int, heure in-game) sur `Shop` ou `Pnj` — horaires d'ouverture
- [ ] Verification dans `ShopManager` + message "La boutique est fermee" dans le template
- [ ] Augmenter l'alpha de l'overlay nuit (0.35 → 0.45) pour renforcer l'effet visuel
- [ ] Migration SQL (3 champs)

### 34 — Meteo backend & diffusion (S | ★★)
> Systeme autonome, pas de prerequis strict (mais MV-1 recommande pour coherence). Prerequis : ← 20
- [ ] Enum PHP `WeatherType` : sunny, cloudy, rain, storm, fog, snow
- [ ] Champ `currentWeather` (string) + `weatherChangedAt` (datetime) sur l'entite `Map`
- [ ] Migration SQL
- [ ] `WeatherService` : `changeWeather(Map)` — tire une meteo aleatoire ponderee par zone/saison
- [ ] Commande Scheduler `app:weather:tick` (toutes les 15-30 min) qui appelle `WeatherService` sur chaque map
- [ ] Ajouter au `DefaultScheduleProvider`
- [ ] Route API `GET /api/map/weather?mapId=X` (ou inclure dans `/api/map/config`)
- [ ] Topic Mercure `map/weather` pour broadcast changement meteo en temps reel

### 35 — Annonces Mercure evenements (S | ★★★)
> Les joueurs n'ont aucun moyen de savoir qu'un evenement est en cours. Prerequis : EG-1. Prerequis : ← 21
- [ ] Nouveau topic Mercure `event/announce` : publier quand un GameEvent passe ACTIVE
- [ ] Stimulus controller `event-notification` : afficher un toast/banner quand un event demarre
- [ ] Afficher les events actifs dans le HUD (petite icone avec tooltip)
- [ ] Tester : activer un event, verifier que tous les joueurs connectes voient la notification

### 36 — Gains et recompenses reputation (S | ★★★)
> Prerequis : EG-3. Sans gains ni recompenses, le systeme de faction est une coquille vide. Prerequis : ← 22
- [ ] `ReputationManager::addReputation(Player, Faction, amount)` : ajouter/retirer de la reputation
- [ ] Integrer les gains : quetes completees (+rep faction liee), mobs tues (+rep si faction associee)
- [ ] Entite `FactionReward` : faction, requiredTier, rewardType (recipe_unlock/item/discount/zone_access), rewardData JSON
- [ ] Fixtures : 2-3 recompenses par palier significatif (Ami, Honore, Exalte) par faction
- [ ] Afficher les recompenses debloquees/verrouillees sur la page faction
- [ ] Tester : gagner de la reputation, changer de palier, debloquer une recompense

### 37 — Loot exclusif et rarete etendue (S | ★★)
> Enrichir le systeme de loot existant (MonsterItem) pour supporter du contenu endgame. Prerequis : ← 07
- [ ] Ajouter champ `guaranteed` (bool, defaut false) sur MonsterItem : drop garanti (100%) en plus de la proba
- [ ] Ajouter champ `minDifficulty` (nullable int) sur MonsterItem : drop uniquement si difficulte >= X (pour Heroique/Mythique)
- [ ] Creer 4-6 items legendaires exclusifs lies aux boss existants dans les fixtures
- [ ] Configurer les LootTable des boss avec au moins 1 drop garanti legendaire
- [ ] Badge visuel "Legendaire" dans l'inventaire (couleur doree sur les items rarity=legendary)

---

## Piste D — Social & progression (‖)

### 38 — Liste d'amis (S | ★★★)
> Base pour toute interaction sociale recurrente (invitations groupe, messages rapides). Prerequis : ← 19
- [ ] Entite `Friendship` (player, friend, status: pending/accepted/blocked, createdAt)
- [ ] Migration + repository
- [ ] FriendshipManager : sendRequest, accept, decline, block, unfriend
- [ ] Route `GET /game/friends` : liste d'amis avec statut en ligne (derniere activite < 5 min)
- [ ] Route `POST /game/friends/request/{id}` + `POST /game/friends/accept/{id}`
- [ ] Notification Mercure quand un ami se connecte
- [ ] Tests unitaires : ajout, acceptation, blocage, suppression

### 39 — Limite points multi-domaine (S | ★★)
> Empeche de tout maxer, force des choix strategiques. Prerequis : ← 14
- [ ] Constante ou config : `MAX_TOTAL_SKILL_POINTS` (ex: 500 points cumulés sur tous les domaines)
- [ ] Verification dans `SkillAcquiring::acquire()` : somme des `usedExperience` de tous les domaines < max
- [ ] Affichage du total utilise / max dans /game/skills (barre de progression globale)
- [ ] Tests (acquisition OK sous la limite, refus au-dessus)

### 40 — Synergies cross-domaine (M | ★★★)
> Les bonus s'accumulent deja cross-domaine. Il faut des bonus explicites pour encourager le multi-domaine.
- [ ] Entite `DomainSynergy` (domainA, domainB, bonusType, bonusValue, description)
- [ ] Migration SQL
- [ ] Service `SynergyCalculator` : detecte les combos actifs selon les domaines ou le joueur a >= X XP
- [ ] Seuil d'activation : 50 XP dans chaque domaine du combo
- [ ] Fixtures ~8 synergies (Feu+Metal=Forge ardente +10% degats physiques, Eau+Lumiere=Purification +15% soin, etc.)
- [ ] Affichage des synergies actives dans /game/skills (section "Synergies")
- [ ] Integration dans CombatSkillResolver : appliquer les bonus de synergie aux stats combat
- [ ] Tests SynergyCalculator

---

## Piste E — Qualite & pipeline (‖)

### 42 — Tests unitaires systemes core (M | ★★)
> Aucun test pour shop, harvest, craft ni quest. Fragilise la base de code. Prerequis : ← 25, 26, 27
- [ ] Tests ShopController : achat OK, fonds insuffisants, item soulbound invendable
- [ ] Tests HarvestManager : recolte OK, skill manquant, cooldown actif, XP accordee
- [ ] Tests CraftManager : craft OK, ingredients manquants, skill manquant, item cree
- [ ] Tests QuestProgressTracker : progression monster, collect, craft, completion

### 43 — Tests integration events (S | ★★)
> 21 evenements domaine existent, mais 0 test d'integration sur les listeners. Prerequis : ← 23
- [ ] Test MobKilledEvent → BestiaryListener + AchievementListener + QuestProgressListener
- [ ] Test SpotHarvestEvent → XP progression + (futur) QuestCollectListener
- [ ] Test PlayerLevelUpEvent → AchievementListener
- [ ] Objectif : couverture >= 60% sur src/GameEngine/

### 44 — Extraction services TerrainImport (M | ★★)
> La commande actuelle fait 663 lignes monolithiques (parsing TMX, sync objets, validation, export). Prerequis : ← 01
- [ ] Extraire `TmxParser` : parsing TMX/TSX → structure de donnees (layers, tilesets, objets)
- [ ] Extraire `EntitySynchronizer` : creation/mise a jour des entites (portails, mobs, spots, coffres) depuis les objets TMX
- [ ] Refactorer `TerrainImportCommand` pour deleguer a ces services
- [ ] Verifier que `app:terrain:import` fonctionne identiquement apres refactoring

### 45 — Portraits de personnages (S | ★★)
> Amelioration visuelle des dialogues. Pas de nouvelle mecanique.
- [ ] Champ `portrait` (string, nullable) sur Pnj : chemin vers l'image
- [ ] Afficher le portrait dans le template dialogue (bulle de dialogue + portrait a gauche)
- [ ] 5-10 portraits pour les PNJ narratifs principaux (guide, forgeron, ancien, boss)
- [ ] Fallback : icone generique par class_type si pas de portrait


---

## Vague 3 — Contenu & enrichissement

> **18 taches** qui dependent de la Vague 2.
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

### 54 — Quetes a choix (M | ★★★)
> Ajoute des embranchements narratifs. Le PnjDialogParser supporte deja les choices. Prerequis : ← 13 (QN-4 journal enrichi), 31 (QN-1 recompenses quetes)
- [ ] Ajouter champ `choiceOutcome` (JSON, nullable) sur Quest : mapper choix → quete suivante
- [ ] Adapter QuestController::complete() : si choix fait, orienter vers la branche correspondante
- [ ] Stocker le choix du joueur dans PlayerQuestCompleted (champ `choiceMade`, JSON nullable)
- [ ] 1 quete a choix dans les fixtures (2 branches, recompenses differentes)
- [ ] Condition `quest_choice` dans PnjDialogParser : adapter le dialogue selon le choix passe

### 55 — Quetes quotidiennes (M | ★★★)
> Contenu renouvelable qui donne une raison de revenir chaque jour. Prerequis : ← 12 (QN-3 prerequis quetes), 27 (systeme de scheduling)
- [ ] Champ `isDaily` (bool) + `dailyPool` (JSON) sur Quest : pool de variantes
- [ ] DailyQuestScheduler (Symfony Scheduler) : chaque jour, selectionner 3 quetes du pool
- [ ] Permettre de re-accepter une quete quotidienne (lever la contrainte unique player+quest)
- [ ] Entite PlayerDailyQuest ou reset du PlayerQuest chaque jour
- [ ] 5-8 quetes quotidiennes dans les fixtures (kill X, collect Y, variantes simples)
- [ ] Section "Quotidiennes" dans le journal de quetes

---

### Piste B — Contenu monde (‖)

### 47 — Monstres tier 2 lvl 10-15 (M | ★★★)
> Sous-partie A des monstres tier 2 : 4 monstres intermediaires (lvl 10-15). Prerequis : ← 28 (C-3 monstres tier 1), 29 (C-5 equipement tier 2)
- [ ] **Sous-partie A** : 4 monstres intermediaires (lvl 10-15)
  - Stats, AI patterns, resistances, loot tables
  - Succes bestiaire (12 achievements)

### 48 — Village central hub (L | ★★★)
> Nouvelle carte "Village central" servant de hub principal entre les zones. Prerequis : ← 30 (C-8 teleportation entre cartes), 25 (v0.4-A boutiques PNJ)
- [ ] Design carte Tiled (~40x40, interieur/exterieur) avec places, batiments
- [ ] Import BDD via app:terrain:import
- [ ] PNJ hub : forgeron, alchimiste, marchand, maitre des quetes, banquier (5-8 PNJ)
- [ ] Dialogues PNJ (JSON conditions)
- [ ] Portail bidirectionnel vers carte existante
- [ ] Aucun monstre (zone safe)

### 49 — Monstres soigneurs / multi-mobs (M | ★★★)
> Support du combat multi-mobs avec role soigneur pour les monstres. Prerequis : ← 28 (C-3 monstres tier 1)
- [ ] Support multi-mobs dans FightController : engager un groupe de mobs (2-3 mobs)
- [ ] MobActionHandler : role `healer` cible un allie blesse (mob avec le moins de PV%)
- [ ] SpellApplicator : supporter les heals mob→mob
- [ ] Template combat : afficher plusieurs mobs avec barres de vie individuelles
- [ ] Fixtures : groupe de mobs avec un soigneur (ex: 2 Squelettes + 1 Necromancien soigneur)
- [ ] Tests : heal mob→mob, ciblage du plus blesse

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

### 51 — Meteo impact gameplay (S | ★★)
> Bonus/malus elementaires selon la meteo active et monstres exclusifs par condition meteorologique. Prerequis : ← 34 (MV-3 meteo backend & diffusion)
- [ ] Table de bonus/malus par meteo × element dans `WeatherService` (ex: pluie → eau +20%, feu -20%)
- [ ] Appliquer le modificateur dans `DamageCalculator` via `WeatherService::getElementalModifier(map, element)`
- [ ] Monstres speciaux par meteo : champ `spawnWeather` (nullable) sur `Mob`
- [ ] Filtre dans `MobSpawnManager` : certains mobs n'apparaissent que sous orage/brouillard/neige
- [ ] Migration SQL (1 champ)

### 62 — Particules combat et recolte (S | ★★)
> Branchement des appels spawnParticles() sur les evenements de combat et de recolte existants. Le systeme spawnParticles() existe deja.
- [ ] Particules sur sort lance en combat (couleur = element du sort)
- [ ] Particules sur coup critique (explosion doree)
- [ ] Particules sur recolte reussie (etincelles vertes)
- [ ] Particules sur level-up domaine (pluie d'etoiles)

### 63 — Flash elementaire et animations combat (S | ★★)
> Effets visuels complementaires au combat : flash colore, shake camera, animations sprites.
- [ ] Flash colore plein ecran sur degats elementaires (rouge=feu, bleu=eau, etc.)
- [ ] Shake camera sur coups critiques (branche sur evenement critique existant)
- [ ] Animation de tremblement sur le sprite cible quand il recoit des degats
- [ ] Fondu progressif du sprite a la mort d'un mob

---

### Piste D — Social & builds (‖)

### 52 — Guildes fondation (M | ★★★)
> Premiere brique du systeme de guilde : creation et gestion des membres. Prerequis : ← 38 (MS-3 liste d'amis)
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

### 56 — Presets de build (M | ★★)
> Sauvegarde et chargement de configurations de skills. Prerequis : ← 14 (PB-1 respec basique)
- [ ] Entite `BuildPreset` (player, name, skillSlugs JSON, createdAt)
- [ ] Migration SQL
- [ ] Service `BuildPresetManager` : save(Player, name), load(Player, presetId), delete(presetId)
- [ ] `load()` = respec gratuit + acquisition auto des skills du preset (si XP suffisante)
- [ ] Limite : 3 presets par joueur
- [ ] Route GET/POST `/game/skills/presets` (liste, sauvegarder, charger, supprimer)
- [ ] Template : liste des presets avec boutons Charger/Supprimer + formulaire de sauvegarde
- [ ] Tests BuildPresetManager (save/load OK, limite atteinte, XP insuffisante pour charger)

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

### 60 — Minimap PixiJS (M | ★★★)
> Overlay minimap en coin haut-droit avec points colores representant les entites. L'API /api/map/entities retourne deja toutes les positions.
- [ ] Container PixiJS fixe en coin haut-droit (150x150px), semi-transparent
- [ ] Points colores : blanc=joueur, rouge=mobs, bleu=PNJ, jaune=spots recolte, violet=portails
- [ ] Viewport rectangle (zone visible) affiche en surbrillance
- [ ] Mise a jour a chaque mouvement joueur
- [ ] Toggle affichage (touche M ou bouton)

### 61 — Barre d'action rapide (S | ★★)
> Raccourcis clavier/boutons en bas de l'ecran carte pour utiliser consommables et sorts frequents.
- [ ] Barre fixe en bas de l'ecran carte (4-6 slots)
- [ ] Drag & drop items consommables depuis l'inventaire vers les slots
- [ ] Raccourcis clavier 1-6 pour activer un slot
- [ ] Persistance des slots en localStorage


---

## Vague 4 — Monde & systemes avances

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

## Vague 5 — Endgame & contenu avance

> **11 taches** de contenu endgame et systemes avances.
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

### Piste B — Combat & PvP (‖)

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

### 82 — Duels PvP (M | ★★)
> PvP consensuel simple : defi 1v1 sans classement. Prerequis : ← 38
- [ ] Route `POST /game/duel/challenge/{playerId}` : envoyer un defi
- [ ] Notification Mercure au joueur defie (accepter/refuser)
- [ ] Creer un Fight PvP (joueur vs joueur, pas de mob)
- [ ] Adapter FightTurnResolver pour PvP (2 joueurs alternent)
- [ ] Pas de perte d'items/gils (combat amical)
- [ ] Ecran de resultat (victoire/defaite)
- [ ] Tests : defi, acceptation, combat, resultat

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

### 88 — Stock boutique & restock (M | ★)
> Actuellement les boutiques ont un stock illimite. Prerequis : ← 25
- [ ] Ajouter champs stock/maxStock/restockInterval dans la structure shopItems (JSON)
- [ ] ShopRestockScheduler : commande/scheduler qui restock les boutiques periodiquement
- [ ] Afficher le stock restant dans le template boutique
- [ ] Bloquer l'achat si stock = 0

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
