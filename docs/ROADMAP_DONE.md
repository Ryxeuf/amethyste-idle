# Roadmap realisee — Amethyste-Idle

> Historique des phases completees. Ce fichier est la reference pour tout ce qui a ete implemente.
> Derniere mise a jour : 2026-03-26

---

## Modernisation de la stack (2026-03-09) ✅

> Refonte complete de l'infrastructure technique.

| Tache | Detail |
|-------|--------|
| Migration Doctrine ORM 3.6 / DBAL 4.4 | 22 entites migrees, config nettoyee |
| Migration Tailwind CSS v3 → v4.1 | Config CSS-native, suppression tailwind.config.js |
| Suppression Node.js | Retrait complet de l'image Docker |
| Correction Mercure | URL dynamique, Turbo Streams active |
| Controller Stimulus Mercure | Remplacement du script brut move-listener.js |
| Refactoring deplacement | Suppression usleep(250ms), chemin complet en 1 event |
| Remplacement Typesense → PostgreSQL | Cache Symfony, suppression service Docker |
| Remplacement cron-bundle → Symfony Scheduler | Composant natif Symfony |
| Docker : 4 services → 2 services | Suppression typesense + worker async |

**Stack finale** : PHP 8.3 + Symfony 7.2.9 + FrankenPHP + PostgreSQL 16 + Doctrine ORM 3.6.2 + Tailwind v4.1 + Mercure SSE

---

## Phase 1 — Fondations techniques (2026-03-13) ✅

### 1.1 Pipeline Tiled ameliore ✅
- Import des Object Layers depuis TMX (mob_spawn, npc_spawn, portal, chest, harvest_spot)
- Validation automatique des maps (--validate)
- Auto-detection des tilesets et dimensions
- Support des proprietes personnalisees Tiled
- Mode dry-run (--dry-run)
- Statistiques detaillees (--stats)

### 1.2 Workflow de creation de cartes (partiel) ✅
- Conventions de layers documentees
- Commande d'import enrichie avec --sync-entities
- Commande de validation
- Systeme de portails (teleportation entre zones avec fade, particules, camera shake)

### 1.3 Systeme de sprites complet ✅
- SpriteAnimator format RPG Maker VX (3x4 single, 12x8 multi)
- Animation idle breathing (oscillation Y desynchronisee)
- Systeme d'emotes (!, ?, coeur, etoile, etc.)
- Etats d'animation (idle, walk, interact)

### 1.4 Boucle de jeu PixiJS ✅
- Ticker 60fps avec delta time, camera lerp
- Camera shake parametrable
- Cycle jour/nuit (overlay ambiant)
- Systeme de particules
- Fade transition pour changements de carte

### 1.5 Support mobile ✅
- Controles WASD/ZQSD + fleches
- Joystick virtuel 4 directions
- Retour haptique (vibration)
- Mode paysage CSS adaptatif
- Touch events unifies
- Responsive canvas (ResizeObserver)

### 1.6 Dialogues PNJ ✅
- Typewriter intelligent (pauses ponctuation)
- Navigation clavier (Espace/Entree/Echap)
- Animations slide-up/down
- Parser conditionnel (quest, has_item, domain_xp_min)
- Variables {{player_name}}, {{pnj_name}}
- Actions de choix (close, quest_offer, open_shop, next)
- Accessibilite ARIA

### 1.7 Performance ✅
- Tile sprite pool
- Entity container pool
- Spatial hash O(1)
- Texture cache (GID, couleur, sheet)
- Lazy loading + preload cells
- Pruning des cellules distantes
- Frame budget monitoring

### 1.8 Registre d'assets centralise ✅
- SpriteConfigProvider avec metadonnees
- Filtrage par categorie
- 30+ sprite sheets (7 joueurs, 12 monstres, 10 PNJ)

### 1.9 Accessibilite web ✅
- ARIA attributes (role, aria-label, aria-live)
- Hints clavier
- Backdrop blur pour lisibilite

### 1.10 Preview terrain et templates Tiled ✅
- Commande `app:terrain:preview --map=X` : genere un PNG a partir d'un fichier TMX
  - Support scale (0.25, 0.5, 1, 2), overlay collisions, overlay objets
  - Rendu complet multi-layers avec tous les tilesets
  - Mode `all` pour generer toutes les cartes d'un coup
- Templates de cartes Tiled pre-configures dans `terrain/templates/` :
  - `template_outdoor.tmx` — Zone exterieure 60x60 (4 tilesets, 5 layers, object group)
  - `template_indoor.tmx` — Interieur 20x20 (4 tilesets, 5 layers, object group)
  - `template_dungeon.tmx` — Grotte/donjon 60x30 (3 tilesets, 5 layers, object group)
  - Chaque template inclut les conventions de layers, les GID de reference, et des exemples d'objets commentes

---

## Phase 2 — Panel d'administration ✅

### 2.1 Infrastructure admin ✅
- Firewall admin (pattern /admin/*, role ROLE_ADMIN)
- Layout admin dedie avec sidebar
- Dashboard avec metriques cles
- Recherche et filtrage avec pagination

### 2.2 Gestion du contenu de jeu (CRUD complet) ✅
- Items, Monstres, Sorts, Competences, Domaines
- Quetes, PNJ, Recettes de craft, Tables de loot

### 2.3 Gestion des cartes ✅
- Visualisation des maps avec statistiques par zone
- Monitoring par zone (joueurs, mobs, PNJ)
- Gestion des spawns : placer/deplacer mobs et PNJ sur la carte via interface admin
- Gestion des portails : configurer les liens entre zones depuis l'admin (CRUD complet)
- Import de map : upload d'un fichier TMX depuis l'admin

### 2.4 Gestion des joueurs ✅
- Liste joueurs avec recherche et pagination
- Fiche joueur detaillee (stats, inventaire, quetes, progression)
- Actions admin (ban/unban, reset position, donner items/gils)
- Logs d'actions admin

### 2.5 Outils de maintenance ✅
- Mode maintenance activable depuis l'admin
- Logs d'administration
- Reload des fixtures selectif : commande CLI `app:fixtures:load-selective` (12 groupes : items, monsters, spells, skills, domains, mobs, pnjs, quests, maps, players, achievements, slots)
- Console Mercure : voir les topics connus, publier des messages de test depuis l'admin
- Planificateur d'evenements : entite GameEvent (boss_spawn, xp_bonus, drop_bonus, invasion, custom), CRUD admin, recurrence, filtrage par statut

---

## Game Design — Phases 1 a 13 ✅

### Phase GD-1 : Enum Element centralise ✅
- PHP 8.4 backed enum (none, fire, water, earth, air, light, dark, metal, beast)
- Migration des constantes ELEMENT_* vers l'enum

### Phase GD-2 : Systeme de race ✅
- Entite Race (slug, name, description, statModifiers)
- Race Humain (stats neutres)
- Assignation automatique a la creation

### Phase GD-3 : Spell niveau + valueType + calculators ✅
- Champs level et valueType (fixed/percent) sur Spell
- DamageCalculator, HitChanceCalculator, CriticalCalculator extraits

### Phase GD-4 : Side effects enrichis ✅
- StatusEffect avec category (buff/debuff/hot/dot) et frequency
- PlayerStatusEffect pour effets persistants hors combat
- StatusEffectManager avec tick selon frequence

### Phase GD-5 : Competences multi-domaines ✅ (BREAKING)
- Skill.domain ManyToOne → ManyToMany
- CrossDomainSkillResolver (auto-unlock, XP 100% par domaine)

### Phase GD-6 : Infrastructure 32 domaines + tous les arbres de talent ✅
- 32 domaines (24 combat + 4 recolte + 4 craft)
- 400+ competences avec 13-24 skills par domaine
- Sous-phases 6.A a 6.I toutes completees

### Phase GD-7 : Tout est un sort + Soulbound ✅
- boundToPlayer sur items
- use_spell comme norme d'action pour consommables
- Icone "lie" sur items bound en inventaire

### Phase GD-8 : Materia = Capacites de combat (partiel) ✅
- CombatCapacityResolver cree (sorts = materia equipees)
- Attaque arme TOUJOURS disponible gratuitement
- Bonus matching element slot/materia (+25% degats, +25% XP)

### Phase GD-9 : Inventaire groupement visuel ✅
### Phase GD-10 : Dashboard enrichi ✅
### Phase GD-11 : Bestiaire joueur ✅
### Phase GD-12 : Systeme de succes ✅
### Phase GD-13 : Mise a jour documentation ✅

---

## Combat enrichi — Elements deja implementes ✅
### Synergies elementaires ✅
### Materia Fusion ✅
### Materia XP ✅
### Statuts alteres (8/8) ✅
### Resistances elementaires par monstre ✅
### IA monstres — patterns et alertes ✅
### Boss — phases et cooldown ✅

---

## Vague 1 — Fondations & Quick Wins (2026-03-20)

### 01 — De-hardcoder les map IDs ✅
### 02 — Supprimer la commande CSS morte ✅
### 04 — Rate limiting API ✅
### 07 — Raretes d'equipement ✅
### 08 — Combat log frontend ✅
### 09 — Icones statuts timeline combat ✅
### 10 — Indicateur difficulte monstres ✅
### 12 — Recompenses de quetes completes ✅
### 14 — Respec basique ✅
### 24 — Notifications toast in-game ✅
### 25 — Boutiques PNJ fixtures ✅

---

## Tache 06 — Materia unlock verification (2026-03-21) ✅

> Verification `actions.materia.unlock` avant d'autoriser un sort en combat. Gain gameplay : ★★★

- [x] Methode `getUnlockedMateriaSpellSlugs(Player)` dans CombatSkillResolver (scan skills pour `materia.unlock`)
- [x] Methode `hasUnlockedMateriaSpell(Player, spellSlug)` dans CombatSkillResolver
- [x] Flag `locked` dans `CombatCapacityResolver::getEquippedMateriaSpells()` pour chaque sort
- [x] Validation dans `FightSpellController` : rejet des sorts verrouilles (HTTP 403)
- [x] `PlayerItemHelper::canEquipMateria()` verifie le skill unlock avant d'autoriser l'equipement
- [x] Template combat : sorts verrouilles grises (opacity-50, texte "Competence requise")
- [x] Tests unitaires CombatCapacityResolverTest (flag locked) et CombatSkillResolverMateriaTest

---

## 13 — Prerequis de quetes et chaines (2026-03-21) ✅

> Permet de creer des chaines de quetes Q1→Q2→Q3. Gain gameplay : ★★★

- [x] Ajout du champ `prerequisiteQuests` (JSON, nullable) sur l'entite Quest + migration PostgreSQL
- [x] Verification des prerequis dans `QuestController::accept()` (refus si prerequis non remplis)
- [x] Nouvelle condition `quest_prerequisites_met` dans `PnjDialogParser` pour les dialogues PNJ
- [x] Methode `getAvailableQuests()` dans `PlayerQuestHelper` (filtre par prerequis satisfaits)
- [x] Onglet "Disponibles" dans le journal de quetes (affiche les quetes acceptables)
- [x] Chaine de 3 quetes dans les fixtures : "La Menace Rampante" (gobelins → squelettes → troll)
- [x] Support admin : champ prerequis dans le formulaire de creation/edition de quetes

---

## Tache 15 — Consommables de base (2026-03-21) ✅

> Ajout de consommables fonctionnels : potions, nourritures et parchemins. Tous utilisables en combat et hors combat via le systeme de sorts existant.

- [x] 6 nouveaux sorts de consommables dans SpellFixtures (potion-heal-major, antidote-heal, bread-heal, grilled-meat-heal, stew-heal)
- [x] 5 potions/remedes : potion de soin majeure (heal 15, 200G), antidote (heal 3, 75G) + existants (mineure, moyenne, energie)
- [x] 3 nourritures : pain (heal 4, 15G), viande grillee (heal 8, 40G), ragout (heal 12, 80G)
- [x] 3 parchemins : teleportation (150G), savoir/XP boost (300G), identification (100G)
- [x] Tous les consommables fonctionnels ajoutes aux loot tables des monstres (par tier de difficulte)
- [x] Boutiques PNJ enrichies : Elise vend potions + antidote, Pierre vend nourritures

---

## Tache 19 — Profil joueur public (2026-03-21) ✅

> Page de profil public pour consulter les infos d'un autre joueur.

- [x] Route `GET /game/player/{id}/profile` : nom, classe, race, stats, domaines, succes, bestiaire
- [x] Template profil public avec stats (vie, energie, vitesse, precision), domaines et bonus, succes obtenus, titres de chasseur
- [x] Lien cliquable sur les noms de joueurs dans le chat (global, carte, messages prives) — Twig et Stimulus.js

---

## Tache 09 — Icones statuts timeline combat (2026-03-21) ✅

> Badges statut actifs sous chaque avatar dans la timeline combat.

- [x] Badges statut color-codes sous chaque avatar dans `_timeline.html.twig`
- [x] Icone emoji + tours restants (tooltip au survol)
- [x] 8 types supportes : poison, burn, freeze, paralysis, silence, regeneration, shield, berserk

---

## Tache 10 — Indicateur difficulte monstres (2026-03-21) ✅

> Affichage de la difficulte des monstres en etoiles.

- [x] Champ `difficulty` (int 1-5) sur l'entite Monster
- [x] Affichage en etoiles dans le template combat et le bestiaire
- [x] Difficulte renseignee dans MonsterFixtures pour les 25 monstres

---

## Tache 14 — Respec basique (2026-03-21) ✅

> Redistribution de tous les points de talent avec cout croissant.

- [x] Service `SkillRespecManager` : retire tous les skills, rembourse l'XP usee
- [x] Cout en gils (50 * nb skills * 1.25^respecCount), prix croissant
- [x] Champ `respecCount` sur Player + migration
- [x] Route POST `/game/skills/respec` + RespecController avec CSRF
- [x] Modale de confirmation dans la page /game/skills
- [x] Tests unitaires SkillRespecManagerTest

---

## Tache 20 — Horloge in-game & API temps (2026-03-21) ✅

> Systeme de temps in-game avec ratio configurable (1h reelle = 1 jour in-game).

- [x] `GameTimeService` : conversion temps reel → in-game (ratio configurable via `game.time_ratio`)
- [x] Methodes `getHour()`, `getMinute()`, `getTimeOfDay()` (dawn/day/dusk/night), `getSeason()`, `getDay()`
- [x] Parametre Symfony `game.time_ratio` dans `services.yaml`
- [x] Route API `GET /api/game/time` (heure, minute, periode, saison, jour, ratio)
- [x] `map_pixi_controller.js` utilise l'API au lieu du temps reel local
- [x] HUD discret sur la carte : heure in-game + icone saison (PixiJS Text overlay)
- [x] Extrapolation client-side entre les fetches API (re-sync toutes les 5 min)
- [x] 12 tests unitaires GameTimeServiceTest

---

## Tache 24 — Notifications toast in-game (2026-03-21) ✅

> Systeme de notifications toast generaliste pour toutes les actions du joueur.

- [x] Stimulus controller `toast_controller.js` : toasts empiles en bas-droite, auto-dismiss 4s
- [x] 4 types visuels : success (vert), error (rouge), warning (orange), info (bleu)
- [x] API globale `window.Toast.show(type, message)` pour JS
- [x] Integration flash messages Symfony (`addFlash`) → toasts automatiques
- [x] Container dans `game.html.twig` avec support safe-area mobile

## Tache 11 — Recompenses uniques de boss (2026-03-21) ✅

> Items legendaires exclusifs au boss Dragon ancestral, avec drop garanti dans la loot table.

- [x] 2 items legendaires boss-only dans ItemFixtures : Lame de croc draconique (arme feu), Plastron en ecailles de dragon (armure feu)
- [x] Loot table du Dragon configuree : epee a 15%, plastron a 10% de drop
- [x] Badge rarity Legendary (jaune dore) automatique dans inventaire et ecran de loot

## Tache 32 — Journal de quetes enrichi (2026-03-22) ✅

> Journal de quetes ameliore avec filtrage par type, PNJ donneur, et indicateurs de chaines.

- [x] Onglet "Disponibles" avec bouton "Accepter" et filtrage par type (combat, recolte, livraison, exploration)
- [x] Affichage du PNJ donneur de quete (nom + lien carte) pour chaque quete active/disponible
- [x] Indicateur de chaine "Quete X/Y" pour les quetes faisant partie d'une serie
- [x] Service QuestGiverResolver : resolution PNJ donneur via scan dialog JSON, detection type de quete, calcul position dans chaine

---

## 21 — GameEvent executor (2026-03-22) ✅

> Service d'execution automatique des GameEvent planifies. Socle de tout le contenu evenementiel (bonus XP/drop, world boss, invasions).

- [x] `GameEventExecutor` : scanne les SCHEDULED dont startsAt <= now, les passe ACTIVE
- [x] `GameEventBonusProvider` : expose les multiplicateurs XP et drop actifs (global ou par map)
- [x] Integration `MateriaXpGranter` : applique le bonus XP des events actifs
- [x] Integration `LootGenerator` : applique le bonus drop des events actifs
- [x] Commande `app:game-event:execute` + tache Scheduler (toutes les 60s)
- [x] Passage ACTIVE → COMPLETED quand endsAt < now
- [x] Recurrence : creation automatique du prochain event a la completion
- [x] Events schedules deja expires → marques COMPLETED directement
- [x] Tests unitaires : GameEventExecutorTest (5 tests), GameEventBonusProviderTest (6 tests)

---

## 05 — Consolidation craft : supprimer le systeme duplique (2026-03-22) ✅

> Deux systemes concurrents (CraftManager/CraftController + CraftingManager/CraftingController). CraftingManager conserve (plus complet : experimentation avec hints, 5 niveaux de qualite, decouverte par joueur). CraftManager supprime.

- [x] Audit des 2 systemes : CraftingManager retenu (meilleure experimentation, qualite 5 tiers, decouverte par joueur)
- [x] Suppression systeme redondant : CraftController, CraftManager, CraftQuality, CraftResult, CraftRecipe, CraftEvent, CraftRecipeController, CraftRecipeType, templates game/craft/ et admin/craft_recipe/
- [x] Mise a jour references : DashboardController (Recipe au lieu de CraftRecipe), DomainExperienceEvolver (retrait CraftEvent), RateLimitingSubscriber (routes unifiees), templates nav
- [x] Renommage routes CraftingController : game_crafting → app_game_craft (convention unifiee)
- [x] Migration pour supprimer la table game_craft_recipes
- [x] PHPStan OK, PHP-CS-Fixer OK

---

## 03 — Optimisation queries N+1 (2026-03-22) ✅

> Eager loading des relations Doctrine et index composites pour reduire les requetes N+1 sur les pages critiques.

- [x] MobRepository : eager load Monster+Spells+Attack+MonsterItems pour /api/map/entities
- [x] FightRepository : eager load Mob→Monster→Spells+MonsterItems pour le combat
- [x] PlayerBestiaryRepository : eager load Monster+MonsterItems+Item pour /game/bestiary
- [x] MapApiController : utilise MobRepository au lieu de findBy generique
- [x] FightChecker : utilise FightRepository.findWithRelations au lieu de find()
- [x] Index composites : idx_mob_map (mob.map_id), idx_player_map (player.map_id)
- [x] PHPStan OK, PHP-CS-Fixer OK

## 17 — Equipement tier 1 Starter (2026-03-22) ✅

> Set complet 7 pieces d'equipement starter (element None, rarete Common, sans prerequis de skill).

- [x] 7 pieces d'equipement : epee en bois, casque rouille, tunique rembourrée, jambieres en tissu, sandales usees, gants de travail, bouclier en bois
- [x] Prix bas (8-20 or), duree de vie 60 utilisations, aucun prerequis de competence
- [x] Ajout aux loot tables des monstres lvl 1 (slime, goblin, bat, giant_rat, zombie) avec probabilites 2-6%

## 39 — Limite points multi-domaine (2026-03-22) ✅

> Empeche de tout maxer, force des choix strategiques de build.

- [x] Constante `MAX_TOTAL_SKILL_POINTS = 500` dans `PlayerSkillHelper`
- [x] Verification dans `canAcquireSkill()` : somme des `usedExperience` de tous les domaines + cout du skill <= max
- [x] Methode `getTotalUsedPoints()` pour calculer le total utilise cross-domaine
- [x] Affichage barre de progression globale dans `/game/skills` (couleur adaptative : violet/orange/rouge)
- [x] Messages contextuels (alerte quand >= 80%, erreur quand limite atteinte)
- [x] 7 tests unitaires (sous la limite, a la limite exacte, au-dessus, deja acquis, constante)
- [x] PHPStan OK, PHP-CS-Fixer OK, 323 tests OK

## 16 — Materia complement — 8 nouvelles (2026-03-22) ✅

> 8 nouvelles materias tier 2 (1 par element), enrichit le combat de 10 → 18 materias.

- [x] 7 nouveaux sorts dans SpellFixtures : Brume glaciale (eau), Eclair en chaine (air), Mur de pierre (terre), Riposte d'acier (metal), Morsure sauvage (bete), Benediction (lumiere), Drain vital (ombre) + Combustion (feu, existait deja)
- [x] 8 nouveaux items materia tier 2 dans ItemFixtures (rarete Rare, level 2, prix 150-180 or, 10-15 utilisations)
- [x] 7 nouveaux skill unlock dans SkillFixtures : hydromancer, stormcaller, geomancer, soldier, hunter, paladin, assassin (berserker existait deja pour Combustion)
- [x] YAML materia.yaml mis a jour pour coherence
- [x] PHPStan OK, PHP-CS-Fixer OK

---

## 35 — Annonces Mercure evenements (2026-03-22) ✅

> Notification temps reel quand un GameEvent passe ACTIVE via Mercure SSE + affichage HUD.

- [x] Domain event `GameEventActivatedEvent` dispatche quand un GameEvent passe ACTIVE
- [x] Publisher Mercure `GameEventAnnouncementHandler` publie sur topic `event/announce`
- [x] `GameEventExecutor` dispatche l'event apres flush (activation automatique)
- [x] Admin toggle dispatche aussi l'event lors d'activation manuelle
- [x] API `GET /api/game/events/active` : liste les events actuellement actifs
- [x] Stimulus controller `event-notification` : souscrit Mercure, affiche toast, HUD badge avec dropdown
- [x] HUD dans `game.html.twig` : badge "Events" avec compteur et liste hover des events actifs
- [x] Tests unitaires : GameEventAnnouncementHandler, GameEventExecutor (dispatch event)
- [x] PHPStan OK, PHP-CS-Fixer OK, PHPUnit 318 tests OK

## 37 — Loot exclusif et rarete etendue (2026-03-22) ✅

> Enrichissement du systeme de loot : drops garantis, filtrage par difficulte, items legendaires exclusifs.

- [x] Champ `guaranteed` (bool) sur MonsterItem : drop garanti (100%) independamment de la probabilite
- [x] Champ `minDifficulty` (nullable int) sur MonsterItem : drop uniquement si difficulte monstre >= seuil
- [x] Migration SQL (ALTER TABLE game_monster_items ADD COLUMN guaranteed, min_difficulty)
- [x] LootGenerator mis a jour : gestion guaranteed (skip roll) + filtrage minDifficulty
- [x] 4 items legendaires crees : Anneau de serre de griffon, Heaume cornu du minotaure, Bouclier coeur de golem, Ceinture du roi troll
- [x] Drops legendaires garantis sur le boss Dragon (dragon_fang_blade, dragon_scale_armor)
- [x] Drops legendaires rares (3%) sur monstres haut niveau (griffon, minotaure, golem, troll) avec minDifficulty=3
- [x] Badge visuel legendaire deja operationnel (fond dore, bordure doree via inv-tooltip-rarity--legendary)

## 38 — Liste d'amis (2026-03-22) ✅

> Systeme complet de liste d'amis avec statut en ligne.

- [x] Entite Friendship (player, friend, status: pending/accepted/blocked, createdAt)
- [x] FriendshipManager : sendRequest, accept, decline, block, unfriend
- [x] Routes GET/POST /game/friends
- [x] Notification Mercure quand un ami se connecte

## 22 — Factions & reputation (2026-03-22) ✅

> Systeme de factions avec reputation et paliers.

- [x] Entite Faction : slug, name, description, icon
- [x] Entite PlayerFaction : player (ManyToOne), faction (ManyToOne), reputation (int)
- [x] Enum ReputationTier : Hostile, Inconnu, Neutre, Ami, Honore, Revere, Exalte
- [x] Calcul automatique du tier selon les seuils de reputation (0, 500, 2000, 5000, 10000, 20000)
- [x] Migration + fixtures 4 factions (Marchands, Chevaliers, Mages, Ombres)
- [x] Route /game/factions : liste des factions, reputation actuelle, palier, barre de progression
- [x] Traductions FR/EN completes

## 27 — Tracking quetes collect/craft (2026-03-22) ✅

> Correction du tracking des quetes de type collect et craft qui ne progressaient jamais.

- [x] QuestTrackingFormater : ajout formatCollect() et formatCraft() pour initialiser le tracking
- [x] PlayerQuestHelper::getPlayerQuestProgress() etendu pour traiter collect et craft
- [x] PlayerQuestUpdater : ajout updateItemCollected() et updateItemCrafted()
- [x] QuestCollectTrackingListener : ecoute SpotHarvestEvent et GatheringEvent
- [x] QuestCraftTrackingListener : ecoute CraftEvent
- [x] CraftEvent cree et dispatche dans CraftingManager apres craft reussi
- [x] SpotHarvestEvent enrichi avec les items recoltes (harvestedItems)
- [x] Templates quest/index et game/index mis a jour pour afficher progression collect/craft
- [x] Fixtures PlayerQuest mises a jour au nouveau format de tracking

---

### 41 — Indicateurs quetes sur PNJ (2026-03-23) ✅

> Indicateurs visuels (! ou ?) au-dessus des PNJ donneurs de quetes sur la carte PixiJS.

- [x] Service `PnjQuestIndicatorResolver` : resout l'indicateur (available/in_progress/null) par PNJ pour un joueur
- [x] Champ `questIndicator` ajoute dans `/api/map/entities` pour chaque PNJ
- [x] Rendu PixiJS : icone `!` jaune (quete disponible) ou `?` grise (quete en cours) au-dessus du sprite PNJ
- [x] Mise a jour dynamique a chaque rechargement des entites (acceptation/completion de quete)

### 36 — Gains et recompenses reputation (2026-03-23) ✅

> Systeme de gains de reputation (mobs tues, quetes completees) et recompenses par palier pour chaque faction.

- [x] `ReputationManager::addReputation(Player, Faction, amount)` : service core de gestion de reputation
- [x] `ReputationListener` : event subscriber sur `MobDeadEvent` et `QuestCompletedEvent`
- [x] Champ `faction` nullable sur `Monster` : monstres associes a une faction donnent de la rep
- [x] Support `rewards.reputation` JSON dans les quetes : gain de reputation configurable par quete
- [x] Entite `FactionReward` : faction, requiredTier, rewardType, rewardData JSON, label, description
- [x] Fixtures : 3 recompenses par faction (Ami, Honore, Exalte) — remises, bonus stats, bonus combat
- [x] Affichage recompenses debloquees/verrouillees sur la page factions
- [x] Migration SQL : table `game_faction_rewards` + colonne `faction_id` sur `game_monsters`

---

## 18 — Commandes chat slash (2026-03-19) ✅

> Systeme de chat avec commandes slash pour la communication entre joueurs.

- [x] `ChatCommandHandler` : detection et routage de 8 commandes (/whisper, /zone, /global, /emote, /who, /help + aliases)
- [x] `ChatManager` : envoi de messages sur 3 canaux (global, map, prive), rate limiting, sanitisation
- [x] `ChatController` : route POST `/game/chat/send`, delegation aux handlers
- [x] Stimulus controller `chat_controller.js` : UI multi-onglets, Mercure SSE temps reel, recherche joueur
- [x] Entite `ChatMessage` : channel, content, sender, recipient, soft delete pour moderation
- [x] 27 tests unitaires ChatCommandHandlerTest

---

## 26 — Recettes de craft fixtures (2026-03-23) ✅

> 10 recettes de craft couvrant les 4 professions d'artisanat : forge, tannerie, alchimie, joaillerie.

- [x] `RecipeFixtures` : 10 recettes de base (4 forge, 3 tannerie, 2 alchimie, 1 joaillerie)
- [x] 4 nouveaux items craftables : dague en fer, bouclier en fer, casque en fer, anneau de cuivre
- [x] Correction `CraftingController` : types de craft alignes sur les slugs de domaine (forgeron, tanneur, alchimiste, joaillier)
- [x] Correction template artisanat : labels francais corrects pour les onglets
- [x] Ingredients utilises : minerais (fer, cuivre), cuirs (brut, epais), plantes (menthe, sauge, lavande)

---

## 45 — Portraits de personnages (2026-03-23) ✅

> Amelioration visuelle des dialogues PNJ avec portraits et icones fallback par class_type.

- [x] Champ `portrait` (string, nullable) sur entite Pnj + migration PostgreSQL
- [x] API `/api/map/pnj/{id}/dialog` retourne `portrait` et `classType` dans la reponse JSON
- [x] Template dialogue Twig : element portrait a gauche du nom du PNJ
- [x] Stimulus `dialog_controller.js` : affichage portrait image ou icone fallback par class_type
- [x] 10 portraits configures dans PnjFixtures pour les PNJ narratifs principaux
- [x] Formulaire admin PNJ : champ portrait ajoute
- [x] Fallback : 10 icones emoji par class_type (villager, merchant, guard, noble, warrior, mage, healer, blacksmith, farmer, hunter)

---

## 23 — Tests fonctionnels controleurs (2026-03-23) ✅

> 18 tests fonctionnels couvrant les 5 controleurs gameplay sans couverture.

- [x] ShopControllerTest (5 tests) : achat OK, fonds insuffisants, item pas en boutique, boutique introuvable, vente soulbound refusee
- [x] InventoryControllerTest (5 tests) : equiper OK, equiper item introuvable, desequiper OK, desequiper introuvable, utiliser consommable (spell + decrementation usages)
- [x] AcquireControllerTest (3 tests) : acquisition OK, skill introuvable, domaine introuvable
- [x] BestiaryControllerTest (3 tests) : rendu avec donnees correctes, redirection sans joueur, zero decouvertes
- [x] AchievementControllerTest (2 tests) : rendu avec categories, comptage succes completes
- [x] Tous les tests existants (342 unit + 51 functional) toujours verts

## 43 — Tests integration events (2026-03-23) ✅

> 19 tests d'integration verifiant que les events declenchent correctement tous les listeners concernes.

- [x] MobDeadEventIntegrationTest (7 tests) : BestiaryListener + AchievementTracker + QuestMonsterTrackingListener + ReputationListener — triggers simultanes, joueurs morts ignores, pas de fight → early return, progression/completion succes, gain reputation
- [x] SpotHarvestEventIntegrationTest (5 tests) : DomainExperienceEvolver + QuestCollectTrackingListener — XP domaine + tracking quete, pas de domaine → skip XP, items vides, items multiples
- [x] QuestCompletedEventIntegrationTest (7 tests) : AchievementTracker + ReputationListener — progression succes + gain reputation, pas de recompense rep, faction inconnue, completion succes avec gils, succes deja complete, reputations multiples
- [x] PHPStan OK, PHP-CS-Fixer OK, 430 tests OK (hors E2E)

## 44 — Extraction services TerrainImport (2026-03-23) ✅

> Refactoring de `TerrainImportCommand` (674 lignes monolithiques) en 2 services reutilisables + commande legere.

- [x] Extraction `TmxParser` (`src/GameEngine/Terrain/TmxParser.php`) : parsing TMX/TSX → tilesets, layers, collision slugs, object groups
- [x] Extraction `EntitySynchronizer` (`src/GameEngine/Terrain/EntitySynchronizer.php`) : creation/mise a jour des entites (portails, mobs, spots, coffres) depuis les objets parses
- [x] Refactoring `TerrainImportCommand` pour deleguer entierement a `TmxParser` et `EntitySynchronizer`
- [x] PHPStan OK, PHP-CS-Fixer OK, 367 tests unitaires OK

## 31 — Types quetes livraison/exploration (2026-03-23) ✅

> Ajout de 2 nouveaux types de quetes : livraison (deliver) et exploration (explore).

- [x] Support `requirements.deliver` dans QuestTrackingFormater : {item_slug, pnj_id, quantity, name}
- [x] Support `requirements.explore` dans QuestTrackingFormater : {map_id, coordinates, name}
- [x] PlayerQuestUpdater : methodes updateDelivered() et updateExplored()
- [x] PlayerQuestHelper : calcul de progression incluant deliver et explore
- [x] QuestExploreTrackingListener : ecoute PlayerMovedEvent pour tracker l'exploration
- [x] Dispatch de PlayerMovedEvent dans PlayerMoveProcessor
- [x] Endpoint POST /game/quests/deliver/{pnjId} pour la livraison d'items
- [x] Action dialog quest_deliver : retrait items inventaire + maj tracking
- [x] Auto-injection pnj_id pour quest_deliver dans PnjDialogParser
- [x] Frontend dialog_controller.js : support action quest_deliver
- [x] InventoryHelper::removeItemBySlug() pour retirer items par slug
- [x] Template Twig : affichage tracking deliver et explore (quetes actives + disponibles)
- [x] 2 quetes fixtures : "Livraison de champignons" (deliver) + "Cartographier la foret" (explore)
- [x] Dialogs PNJ fixtures pour les 2 nouvelles quetes (Henri le Fermier, Mathilde la Cartographe)
- [x] 7 tests unitaires : deliver/explore dans PlayerQuestUpdater + QuestTrackingFormater
- [x] PHPStan OK, PHP-CS-Fixer OK, 379 tests OK

---

## 30 — Teleportation entre cartes (2026-03-23) ✅

> Infrastructure de portails pour voyager entre zones.

- [x] Entite Portal enrichie (sourceMap, targetMap, coordonnees, bidirectionnel)
- [x] PortalManager : teleport(Player, Portal) avec validation
- [x] Endpoint POST /api/map/teleport/{portalId}
- [x] Rendu visuel portails sur la carte PixiJS (cercles violets lumineux)
- [x] Transition visuelle (fade noir existant)
- [x] Topic Mercure map/teleport
- [x] Fixtures portails de test

## 33 — Impact gameplay jour/nuit (2026-03-23) ✅

> Donne une raison concrete au cycle jour/nuit : mobs nocturnes, spots de nuit, horaires boutiques.

- [x] Champ `nocturnal` (bool) sur Mob — mobs nocturnes n'apparaissent que de nuit
- [x] Filtre dans MobSpawnManager : exclure mobs nocturnes le jour, diurnes la nuit
- [x] Champ `nightOnly` (bool) sur HarvestSpot — plantes recoltables uniquement la nuit
- [x] Validation dans HarvestController
- [x] Champs `opensAt`/`closesAt` sur Pnj — horaires d'ouverture boutiques
- [x] Verification dans ShopController + message "La boutique est fermee"
- [x] Migration SQL (3 champs)

## 34 — Meteo backend & diffusion (2026-03-23) ✅

> Systeme meteo aleatoire pondere par saison, diffuse en temps reel via Mercure.

- [x] Enum PHP `WeatherType` : sunny, cloudy, rain, storm, fog, snow
- [x] Champ `currentWeather` + `weatherChangedAt` sur Map
- [x] `WeatherService` : changeWeather(Map) — tirage aleatoire pondere par saison
- [x] Commande Scheduler `app:weather:tick` (toutes les 15 min)
- [x] Route API `GET /api/map/weather`
- [x] Topic Mercure `map/weather` pour broadcast en temps reel
- [x] Migration SQL

## 40 — Synergies cross-domaine (2026-03-23) ✅

> Bonus explicites pour encourager le multi-domaine : combos actifs selon les domaines maitrises.

- [x] Entite `DomainSynergy` (domainA, domainB, bonusType, bonusValue, description)
- [x] Service `SynergyCalculator` : detecte les combos actifs (seuil 50 XP par domaine)
- [x] ~8 synergies fixtures (Feu+Metal=Forge ardente, Eau+Lumiere=Purification, etc.)
- [x] Affichage synergies actives dans /game/skills
- [x] Integration CombatSkillResolver : bonus de synergie appliques aux stats combat
- [x] Tests SynergyCalculator
- [x] Migration SQL

## 42 — Tests unitaires systemes core (2026-03-23) ✅

> Tests unitaires pour les systemes critiques sans couverture : shop, harvest, craft, quest progress.

- [x] Tests HarvestManager : recolte OK, skill manquant, cooldown actif, XP accordee
- [x] Tests CraftingManager : craft OK, ingredients manquants, skill manquant, item cree
- [x] Tests PlayerQuestUpdater : progression monster, collect, craft, completion
- [x] PHPStan OK, PHP-CS-Fixer OK

## 51 — Meteo impact gameplay (2026-03-23) ✅

> Bonus/malus elementaires selon la meteo active et monstres exclusifs par condition meteorologique.

- [x] Table de bonus/malus par meteo × element dans WeatherService
- [x] Modificateur applique dans DamageCalculator via WeatherService::getElementalModifier()
- [x] Champ `spawnWeather` (nullable) sur Mob — mobs exclusifs par meteo
- [x] Filtre dans MapApiController : mobs meteo-specifiques
- [x] Migration SQL (1 champ)

## 28 — Monstres tier 1 — 8 mobs elementaires (2026-03-23) ✅

> 8 monstres elementaires (un par element) niveaux 1-10, avec stats, AI, resistances, loot et succes.

- [x] 8 monstres : Salamandre (Feu/3), Ondine (Eau/2), Sylphe (Air/4), Golem d'argile (Terre/5), Automate rouille (Metal/3), Loup alpha (Bete/4), Feu follet (Lumiere/2), Ombre rampante (Ombre/5)
- [x] Stats, AI patterns et resistances elementaires pour chaque monstre
- [x] Tables de loot (5 drops par monstre, materia elementaire incluse)
- [x] 24 succes bestiaire (3 paliers × 8 monstres)
- [x] 16 mobs places sur la carte (2 par monstre, distances adaptees au niveau)

## 29 — Equipement tier 2 Intermediaire (2026-03-23) ✅

> Set complet 7 pieces × 4 variantes elementaires (Feu, Eau, Terre, Air) = 28 items, avec bonus +10% degats elementaires et loot tables.

- [x] 28 items : Epee, Bouclier, Casque, Plastron, Jambieres, Bottes, Gantelets × 4 elements
- [x] Bonus elementaire sur chaque piece (+10% degats de l'element via effet JSON)
- [x] Mecanique combat : GearHelper calcule le bonus elementaire de l'equipement porte, applique dans FightSpellController
- [x] Tables de loot : drops sur monstres Niveau 2-4 et elementaires tier 1 (probas 2-5%)

## 62 — Particules combat et recolte (2026-03-24) ✅

> Effets de particules visuels branches sur les evenements de combat et de recolte.

- [x] Particules DOM sur sort lance en combat (couleur selon l'element du sort)
- [x] Particules dorees sur coup critique (explosion avec particules blanches)
- [x] Champs `spellElement` et `critical` ajoutes a la reponse JSON de FightSpellController
- [x] Delai de 500ms avant rechargement pour laisser les particules visibles
- [x] Particules vertes PixiJS sur recolte reussie (dispatch event Stimulus harvest→map_pixi)
- [x] Particules dorees (etoiles XP) en complement de la recolte pour le gain de domaine

## 60 — Minimap PixiJS (2026-03-23) ✅

> Overlay minimap en coin haut-droit avec points colores representant les entites.

- [x] Container PixiJS fixe en coin haut-droit (150x150px), fond semi-transparent avec coins arrondis
- [x] Points colores : blanc=joueur, rouge=mobs, bleu=PNJ, jaune=spots recolte, violet=portails
- [x] Fond de terrain vert subtil depuis les cellules en cache
- [x] Viewport rectangle (zone visible) affiche en surbrillance blanche
- [x] Mise a jour throttlee (500ms) pour la performance
- [x] Toggle affichage avec la touche M

## 63 — Flash elementaire et animations combat (2026-03-23) ✅

> Effets visuels complementaires au combat : flash colore, shake camera, animations sprites.

- [x] Flash colore plein ecran sur degats elementaires (rouge=feu, bleu=eau, vert=bete, etc.)
- [x] Shake camera sur coups critiques (animation CSS sur le conteneur .game-page)
- [x] Animation de tremblement sur le sprite cible quand il recoit des degats
- [x] Fondu progressif du sprite a la mort d'un mob (desaturation + opacite reduite)

## 56 — Presets de build (2026-03-24) ✅

> Sauvegarde et chargement de configurations de skills (max 3 presets par joueur).

- [x] Entite `BuildPreset` (player, name, skillSlugs JSON, createdAt)
- [x] Migration SQL (table build_preset)
- [x] Service `BuildPresetManager` : save, load (respec + re-acquire), delete
- [x] `load()` = respec (cout en gils) + acquisition auto des skills du preset
- [x] Limite : 3 presets par joueur
- [x] Routes POST `/game/skills/presets/save`, `/game/skills/presets/{id}/load`, `/game/skills/presets/{id}/delete`
- [x] Section presets dans la page competences avec formulaire de sauvegarde et boutons Charger/Supprimer
- [x] Tests BuildPresetManager (save/load OK, limite atteinte, owner check, combat check)

## 61 — Barre d'action rapide (2026-03-24) ✅

> Raccourcis clavier/boutons en bas de l'ecran carte pour utiliser consommables.

- [x] Barre fixe en bas de l'ecran carte (6 slots) via Stimulus controller `quickbar_controller`
- [x] Picker modal pour selectionner les consommables depuis l'inventaire
- [x] Raccourcis clavier 1-6 pour activer un slot
- [x] Persistance des slots en localStorage
- [x] API `/api/quickbar/items` et `/api/quickbar/use/{id}` avec cooldown 1s

## 47 — Monstres tier 2 lvl 10-15 (2026-03-24) ✅

> 4 monstres intermediaires (lvl 10-15) avec stats, AI patterns, resistances, loot tables et succes bestiaire.

- [x] 4 monstres : Wyverne (Air/Feu/10), Chevalier maudit (Dark/Metal/12), Naga (Eau/Bete/13), Golem de cristal (Terre/Lumiere/15)
- [x] Stats, AI patterns et resistances elementaires pour chaque monstre
- [x] Tables de loot (8 drops par monstre, materia et equipement T2 inclus)
- [x] 12 succes bestiaire (3 paliers × 4 monstres)
- [x] 8 mobs places sur la carte (2 par monstre, zones eloignees du spawn)

## 54 — Quetes a choix (2026-03-24) ✅

> Embranchements narratifs : le joueur fait un choix en rendant une quete, ce qui influence les recompenses et les dialogues futurs.

- [x] Champ `choiceOutcome` (JSON, nullable) sur entite Quest : liste de choix possibles avec cle, label et bonus rewards
- [x] Champ `choiceMade` (string, nullable) sur entite PlayerQuestCompleted : stocke la cle du choix fait
- [x] Migration SQL (2 colonnes)
- [x] QuestController::complete() adapte : validation du choix, application des bonus rewards specifiques au choix
- [x] Methode privee `applyRewards()` extraite pour reutilisation (base + bonus)
- [x] Condition `quest_choice` dans PnjDialogParser : conditionner le dialogue selon le choix passe (format `{"questId": "choiceKey"}`)
- [x] Modal de choix dans le journal de quetes (bouton "Choisir & Rendre" au lieu de "Rendre")
- [x] Affichage du choix fait dans l'onglet "Terminees" du journal
- [x] Formulaire admin : champ `choiceOutcomeJson` pour editer les choix
- [x] 1 quete fixture "Allegeance contestee" : 2 branches (aide garde = bouclier, aide marchand = or + potions)
- [x] Dialogue PNJ conditionnel post-choix (Michel le Garde reagit differemment selon le choix)

---

## 49 — Monstres soigneurs / multi-mobs (2026-03-24) ✅

> Combat multi-mobs et IA soigneur. Les mobs peuvent former des groupes (groupTag) et combattre ensemble.
- [x] Champ `groupTag` (VARCHAR 50, nullable) sur l'entite Mob + migration
- [x] `FightHandler::startGroupFight()` : demarrer un combat avec plusieurs mobs
- [x] `PlayerMoveProcessor::resolveGroupMobs()` : engagement automatique du groupe quand un mob est rencontre
- [x] `MobActionHandler::doAction()` : tous les mobs vivants agissent a chaque tour
- [x] IA soigneur (`role: healer`) : cible l'allie mob le plus blesse (% PV < 70%)
- [x] `SpellApplicator` : supporte deja les heals mob→mob (CharacterInterface)
- [x] Template combat : boucle deja sur `fight.mobs` (multi-mob ready)
- [x] `FightFleeController` : fuite basee sur le mob le plus rapide, verifie tous les boss
- [x] `FightIndexController` : danger alert verifie tous les mobs vivants
- [x] Fixtures : monstre Necromancien (soigneur) + groupe 2 Squelettes + 1 Necromancien
- [x] 5 tests unitaires : multi-mob actions, mobs morts ignores, ciblage soigneur, auto-soin

---

## 55 — Quetes quotidiennes (2026-03-24) ✅

> Systeme de quetes quotidiennes avec rotation automatique.
- [x] Champs `isDaily` (bool) + `dailyPool` (string) sur Quest
- [x] Entite `PlayerDailyQuest` (player, quest, date, tracking, completedAt)
- [x] `DailyQuestService` : rotation, acceptation, progression, completion
- [x] `DailyQuestRotateCommand` : selection aleatoire de 3 quetes/jour
- [x] Symfony Scheduler : rotation quotidienne a 00h01
- [x] QuestController : routes daily/accept, daily/complete, daily/abandon
- [x] 6 quetes quotidiennes dans les fixtures (combat + recolte)
- [x] Section "Quotidiennes" dans le journal de quetes

---

## 52 — Guildes fondation (2026-03-24) ✅

> Systeme de guilde : creation, invitations, gestion des membres et rangs.
- [x] Entite `Guild` (name unique, tag 3-5 chars, description, leader)
- [x] Entite `GuildMember` (guild, player, rank enum, joinedAt) — unique par joueur
- [x] Entite `GuildInvitation` (guild, player, invitedBy)
- [x] Enum `GuildRank` (Leader, Officer, Member, Recruit) avec permissions
- [x] Migration PostgreSQL (3 tables + index + contraintes)
- [x] `GuildManager` : create (5000 gils), invite, accept, leave, kick, promote, demote
- [x] `GuildController` : page de guilde, creation, invitation, gestion membres
- [x] Template Twig avec formulaire creation, liste membres, actions par rang
- [x] Validation : nom unique, max 1 guilde/joueur, cout creation
- [x] 12 tests unitaires : creation, invitation, promotion, depart, kick

---

## 53 — Groupes de combat formation (2026-03-24) ✅

> Systeme de groupe (party) pour jouer ensemble. Base pour le combat coop et donjons futurs.
- [x] Entite `Party` (leader, maxSize: 4, membres)
- [x] Entite `PartyMember` (party, player, joinedAt) — unique par joueur
- [x] Entite `PartyInvitation` (party, player, invitedBy)
- [x] Migration PostgreSQL (3 tables + index + contraintes)
- [x] `PartyManager` : create, invite, accept, leave, kick, transfer leader, disband
- [x] Dissolution automatique si tous les membres partent
- [x] Transfert automatique de leadership si le chef quitte
- [x] `PartyController` : page de groupe, creation, invitation, gestion membres
- [x] Template Twig avec interface de groupe (membres, invitations, actions)
- [x] Lien "Groupe" dans la navigation du jeu
- [x] 13 tests unitaires : creation, invitation, depart, dissolution, transfert leadership

---

## 58 — Parsing zones/biomes Tiled (2026-03-24) ✅

> Peuplement de l'entite Area depuis les objets rectangulaires de type "zone"/"biome" dans Tiled.
- [x] Champs `biome`, `weather`, `music`, `light_level` sur l'entite `Area` + migration PostgreSQL
- [x] Champs bornes de zone `zone_x`, `zone_y`, `zone_width`, `zone_height` sur `Area`
- [x] `AreaSynchronizer` : filtre les objets zone/biome et upsert les Area en BDD
- [x] Exposition des zones dans `/api/map/config` (coordonnees, biome, meteo, musique)
- [x] Option `--sync-zones` dans `app:terrain:import`
- [x] 7 tests unitaires (AreaSynchronizer + TmxParser zones)

---

## 50 — Meteo effets visuels PixiJS (2026-03-24) ✅

> Effets visuels de meteo dans le renderer PixiJS (pluie, neige, orage, brouillard).
- [x] Ecoute du topic Mercure `map/weather` dans `map_pixi_controller.js`
- [x] Container de particules dedie (zIndex 400, au-dessus des entites, sous le HUD)
- [x] Effet pluie : particules tombantes bleues semi-transparentes
- [x] Effet neige : particules blanches lentes avec oscillation laterale
- [x] Effet orage : flash blanc intermittent + particules pluie
- [x] Effet brouillard : overlay blanc semi-transparent avec alpha pulse doux
- [x] Effet nuageux : leger assombrissement (overlay gris alpha 0.08)
- [x] Transition douce entre meteos (fade 2 secondes)

---

## 57 — Commande terrain:sync (2026-03-24) ✅

> Commande unifiee `app:terrain:sync` orchestrant tout le pipeline d'import Tiled.
- [x] `TerrainSyncCommand` : import TMX + sync entites + sync zones + rebuild Dijkstra + rapport diff
- [x] Integration Dijkstra post-import (regeneration du cache collisions)
- [x] Rapport diff (fichiers exportes, entites/zones synchronisees, Dijkstra maps regenerees)
- [x] Mise a jour de l'agent `.claude/commands/import-terrain.md`

---

## 73 — Guildes chat (2026-03-24) ✅

> Canal de communication dedie a la guilde via un nouveau topic Mercure.
- [x] `CHANNEL_GUILD` dans ChatMessage + relation `guild` (ManyToOne)
- [x] Migration PostgreSQL : colonne `guild_id` sur `chat_message`
- [x] Topic Mercure `chat/guild/{guildId}` dans ChatManager
- [x] Methodes `sendGuildMessage()` et `getGuildHistory()` dans ChatManager
- [x] Onglet "Guilde" dans le chat (template + Stimulus controller)
- [x] Couleur emerald pour les messages de guilde
- [x] Abonnement Mercure `chat/guild/{guildId}` cote client
- [x] Verification d'appartenance a la guilde avant envoi (ChatController + ChatCommandHandler)
- [x] Commande `/guild` (alias `/gu`) dans ChatCommandHandler

---

## 46 — Trame Acte 1 : L'Eveil (2026-03-24) ✅

> Tutoriel narratif. Chaine de 5 quetes guidant le joueur dans ses premieres actions.

- [x] Quete 1.1 "Reveil" : dialogue d'introduction avec Claire la Sage, explorer la place du village
- [x] Quete 1.2 "Premiers pas" : aller voir Gerard le Forgeron, recevoir une epee courte
- [x] Quete 1.3 "Bapteme du feu" : tuer 2 slimes dans la zone de depart
- [x] Quete 1.4 "Recolte" : collecter 3 champignons pour Marie la Herboriste
- [x] Quete 1.5 "Le Cristal d'Amethyste" : explorer la clairiere au sud, dialogue revelateur
- [x] Dialogues narratifs pour Claire la Sage (guide), Gerard le Forgeron, Marie la Herboriste
- [x] Recompenses progressives : gils, XP, epee courte, potions, parchemin herboristerie, materia Soin
- [x] Chaine de prerequis : Reveil → Premiers pas → Bapteme → Recolte → Cristal

---

## 59 — Tests E2E Panther (2026-03-24) ✅

> Tests de parcours complets multi-pages via Symfony Panther (Chrome headless).

- [x] Parcours combat : carte → engagement mob via API → combat → attaque en boucle → victoire/loot → retour carte
- [x] Parcours quete : page quetes → navigation onglets → accepter quete disponible → verifier suivi actif → abandonner
- [x] Parcours craft : inventaire → atelier → navigation onglets professions → affichage recettes → tentative fabrication
- [x] Tests UI combat : verification boutons action (attaque, sorts, objets, fuite), combattants visibles
- [x] Tests navigation craft : onglets professions, section experimentation, cartes de recettes

---

## 48 — Village central hub (2026-03-24) ✅

> Nouvelle carte "Village de Lumière" servant de hub principal entre les zones. Zone safe (aucun monstre).

- [x] Carte Tiled 40x40 (world-1-village-1.tmx) avec plaza centrale, batiments, chemins pavés
- [x] Entité Map "Village de Lumière" (map_2) dans MapFixtures
- [x] 6 PNJ hub : Aldric le Forgeron (armes/armures), Iris l'Alchimiste (potions), Marcellin le Marchand (outils/nourriture), Oriane la Maîtresse des Quêtes, Théodore le Banquier, Gareth le Garde
- [x] Dialogues PNJ avec boutiques, horaires d'ouverture, et substitution {{player_name}}
- [x] Portails bidirectionnels : carte principale (30.30) ↔ village (19.39/20.39)
- [x] Données d'area générées pour le rendu PixiJS (area_data.json + world-1-village-1.json)

---

## 79 — Événements bonus/festivals (2026-03-24) ✅

> Intégration des bonus xp_bonus et drop_bonus dans tous les systèmes de jeu, quêtes d'événement temporaires, et cosmétiques d'événement.

- [x] Intégrer `drop_bonus` dans LootGenerator (déjà fait en tâche 21)
- [x] Intégrer `xp_bonus` dans CraftingManager (multiplicateur sur l'XP de craft)
- [x] Intégrer `xp_bonus` dans DomainExperienceEvolver (gathering, fishing, butchering)
- [x] Quêtes d'événement : champ `gameEvent` sur Quest, filtrage automatique des quêtes expirées
- [x] Cosmétiques d'événement : flag `isCosmetic` sur Item, items décoratifs exclusifs
- [x] Fixtures : Festival des Étoiles (bonus XP x2, bonus drop x1.5, 2 quêtes, 2 cosmétiques)
- [x] Migration PostgreSQL idempotente
- [x] Tests unitaires : bonus XP crafting, quêtes d'événement actives/inactives, flag cosmétique

---

## Tâche 76 — Sets d'équipement (2026-03-25) ✅

> Bonus progressifs quand plusieurs pièces du même set sont portées simultanément.

- [x] Entité `EquipmentSet` (slug, name, description)
- [x] Entité `EquipmentSetBonus` (set, requiredPieces, bonusType, bonusValue)
- [x] Champ `equipmentSet` (ManyToOne, nullable) sur Item + migration PostgreSQL
- [x] Service `EquipmentSetResolver` : détecte les sets actifs depuis l'équipement du joueur
- [x] Bonus appliqués dans le combat via `CombatSkillResolver` (damage, heal, hit, critical, life, protection)
- [x] Affichage dans inventaire : pièces du set équipées, bonus actifs/inactifs, nom du set par pièce
- [x] Fixtures : 3 sets de base (Set du Gardien 2/3/4 pièces, Set de l'Ombre 2/3, Set du Veilleur 2/3)
- [x] Tests unitaires EquipmentSetResolver (7 tests)

---

## Tâche 77 — Effets ambiance par zone (2026-03-25) ✅

> Détection de la zone courante du joueur et application d'effets visuels dynamiques en frontend.

- [x] Charger les zones depuis l'API `/api/map/config` au chargement de la carte
- [x] Détecter la zone courante du joueur (point-in-rect) à chaque déplacement
- [x] Appliquer les effets par zone : overlay teinté par biome (forêt, marais, dark, etc.)
- [x] Particules ambiantes par biome (feuilles en forêt, bulles en marais, lucioles sombres, poussière)
- [x] Modificateur de lumière par zone (intégré au cycle jour/nuit)
- [x] Override météo par zone (ex: brouillard permanent en marais, orage dans la lande)
- [x] Transition fluide entre zones (fondu progressif overlay + lumière)
- [x] Re-détection après téléportation (portail vers nouvelle carte)
- [x] Fixtures : 6 zones (5 sur carte principale + 1 village) avec biomes, météo et niveaux de lumière

---

## Tâche 72 — Donjons entité & entrée (2026-03-25) ✅

> Structure de donjon instancié : entités, difficultés, cooldown et point d'entrée.

- [x] Enum `DungeonDifficulty` : Normal, Heroique, Mythique (multiplicateurs HP/dégâts, cooldowns 1h/4h/24h)
- [x] Entité `Dungeon` : slug, name, description, map (ManyToOne), minLevel, maxPlayers, lootPreview (JSON)
- [x] Entité `DungeonRun` : dungeon, player, difficulty, startedAt, completedAt
- [x] Migration PostgreSQL : tables `game_dungeons` + `dungeon_run` avec FK et index
- [x] `DungeonRunRepository` : findActiveRun, findLastCompletedRun, findPlayerHistory
- [x] `DungeonManager` : entrée avec vérifications (run actif, niveau requis, cooldown, combat), téléportation, complétion
- [x] `DungeonController` : liste des donjons, fiche donjon avec choix de difficulté, entrée POST
- [x] Templates Twig : liste des donjons, fiche détaillée avec sélection de difficulté et cooldowns
- [x] Fixtures : 1 donjon de test "Racines de la forêt" (minLevel 5, 1 joueur)

---

## Tache 69 — Monstres invocateurs (2026-03-25) ✅

> Monstres capables d'invoquer des renforts en cours de combat (max 2 par combat).

- [x] Action IA `summon` dans MobActionHandler (generateAction + executeSummon)
- [x] Creation de Mob en cours de combat (ajout a la Fight, insertion dans la timeline)
- [x] Limite d'invocation (MAX_SUMMONS_PER_FIGHT = 2)
- [x] FightTurnResolver : recalcul dynamique de la timeline (getTurnOrder itere fight.getMobs())
- [x] Fixtures : Necromancien invoque des Squelettes (aiPattern.summon avec cooldown, role: summoner)
- [x] Message de log specifique via CombatLogger.logSummon ("X invoque un Y !")
- [x] Champ `summoned` sur Mob : les mobs invoques ne droppent pas de loot
- [x] Type de log `TYPE_SUMMON` + `logSummon()` dans CombatLogger
- [x] Migration PostgreSQL idempotente
- [x] Tests unitaires (5 tests) : invocation, limite atteinte, proprietes mob, chance 0%, slug inconnu

---

## Tache 70 — Slots materia lies (2026-03-25) ✅

> Synergie entre slots adjacents : bonus +15% degats si les materia sockettees partagent le meme element.

- [x] Champ `linkedSlot` (OneToOne, nullable) sur l'entite Slot + migration PostgreSQL
- [x] Service `LinkedMateriaResolver` : detection synergie, multiplicateur de degats (1.15x)
- [x] Integration dans CombatCapacityResolver (champ `linkedBonus` dans getEquippedMateriaSpells)
- [x] Application du bonus dans FightSpellController (+15% degats)
- [x] Affichage visuel dans le template inventaire (badge "Lie", couleur cyan, connecteur ⟷)
- [x] Fixtures : slots lies automatiquement par paires sur equipements a 2+ slots
- [x] Tests unitaires LinkedMateriaResolverTest (10 tests)

---

## Tache 65 — Monstres tier 2 avances lvl 15-25 (2026-03-25) ✅

> 4 monstres intermediaires (lvl 15-25) avec IA complexe (soigneurs, invocateurs), loot tables et succes bestiaire.

- [x] **Archidruide corrompu** (lvl 16, diff 4) : soigneur nature/ombre, heal a 45% HP, sorts nature + dark_harvest
- [x] **Liche mineure** (lvl 18, diff 5) : invocateur dark, invoque 2 squelettes, sorts ombre + dark_ritual
- [x] **Hydre des marais** (lvl 20, diff 5) : tank eau/bete multi-attaque, sequence 6 coups, tidal_wave
- [x] **Forgeron abyssal** (lvl 24, diff 5) : tank metal/feu, tres resistant, shrapnel_burst + steel_shield
- [x] Loot tables pour les 4 monstres (potions, materia, equipement T2)
- [x] Placement sur la carte (8 mobs, zones eloignees 32-138 du spawn)
- [x] 12 succes bestiaire (3 paliers x 4 monstres : 10/50/100 kills)

---

## 74 — Guildes coffre partage (2026-03-25) ✅

> Inventaire collectif de guilde avec permissions par rang et tracabilite des actions.
- [x] Entite `GuildVault` (guild OneToOne, items OneToMany PlayerItem, maxSlots)
- [x] Entite `GuildVaultLog` (guild, player, action deposit/withdraw, item, quantity, createdAt)
- [x] Relation `guildVault` sur `PlayerItem` + relation `vault` sur `Guild`
- [x] Migration PostgreSQL : tables `guild_vault`, `guild_vault_log`, colonne `guild_vault_id` sur `player_item`
- [x] Permissions vault dans `GuildRank` : `canDeposit()` (tous), `canWithdraw()` (member+)
- [x] `GuildVaultManager` : deposit, withdraw, getOrCreateVault, getRecentLogs
- [x] Routes : `GET /game/guild/vault`, `POST .../deposit/{itemId}`, `POST .../withdraw/{itemId}`
- [x] Template vault avec affichage coffre, depot depuis sac, historique recent
- [x] Lien "Coffre de guilde" dans la page guilde
- [x] 12 tests unitaires : depot, retrait, permissions recruit/member, coffre plein, objet equipe/lie, item pas dans coffre

---

## 66 — Boss de zone (2026-03-25) ✅

> Deux boss avec mecaniques de phases, loot unique et succes associes.

- [x] **Gardien de la Foret** (lvl 15, diff 5, 400 HP) : boss Bete/Terre, 2 phases
  - Phase 1 — Eveil sylvestre : sorts forest_call, entangling_roots
  - Phase 2 — Fureur de la nature (< 50% HP) : sort signature primordial_roar (AoE + paralysie)
  - Resistances : bete/terre +50%, feu -50%, metal -30%
- [x] **Seigneur de la Forge** (lvl 20, diff 5, 500 HP) : boss Metal/Ombre, 3 phases
  - Phase 1 — Le Forgeron : sorts blade_dance, shrapnel_burst
  - Phase 2 — Metal en fusion (< 60% HP) : blade_dance preferee
  - Phase 3 — Forge obscure (< 30% HP) : sort signature dark_forge_blast (AoE + brulure)
  - Resistances : metal +60%, dark +40%, eau -50%, lumiere -40%
- [x] 2 sorts de boss : primordial_roar (Beast AoE, paralysie), dark_forge_blast (Metal AoE, brulure)
- [x] 4 items legendaires uniques :
  - Cuirasse d'ecorce ancestrale (Beast, protection 18, 2 slots materia)
  - Baton d'epines primordiales (Earth, arme mage, 2 slots materia)
  - Lame d'obsidienne du Seigneur (Metal, arme soldat, 2 slots materia)
  - Plastron de la forge obscure (Dark, protection 22, 2 slots materia)
- [x] Tables de loot avec drops garantis pour chaque boss
- [x] 2 succes : Gardien terrasse, Seigneur de la forge vaincu (avec titres)
- [x] Placement sur la carte (1 mob par boss, zones eloignees)

---

## 64 — Equipement tier 3 + slots materia (2026-03-25) ✅

> Set avance avec slots materia integres pour les builds endgame.

- [x] 28 items tier 3 : 7 pieces × 4 elements (Metal, Bete, Lumiere, Ombre)
  - Epees, boucliers, casques, plastrons, jambieres, bottes, gantelets
  - Rarete Epic, niveau 15, +15% degats elementaires
- [x] 1-2 slots materia sur chaque piece (2 pour armes/plastrons, 1 pour le reste)
- [x] Spell `none_attack_3` (damage 3) pour les armes tier 3
- [x] 4 equipment sets avec bonus progressifs (2/4/6 pieces) :
  - Acier Runique (Metal) : protection + degats + critique
  - Predateur Sauvage (Bete) : degats + critique + vie
  - Aurore Sacree (Lumiere) : soin + precision + vie
  - Abysses Eternelles (Ombre) : degats + soin + critique
- [x] Loot tables : drops T3 sur monstres lvl 15-25 et boss de zone

## 78 — Equilibrage & rapport (2026-03-25) ✅

> Commande CLI de rapport d'equilibrage et document de reference pour ajuster les stats du jeu.

- [x] Commande `app:balance:report` avec sections : monsters, items, drops, domains, spells, alerts
- [x] Courbe XP par domaine (cout unitaire vs cumul)
- [x] Stats monstres par palier (HP, degats, XP donne)
- [x] Table de drop rates par monstre et rarete
- [x] Alertes automatiques si desequilibre detecte (monstre trop fort/faible, drop rate aberrant, item sans prix)
- [x] Sort sans effet, sort gratuit surpuissant, domaine vide
- [x] Document de reference `docs/BALANCE.md` : courbe XP, bareme prix, degats attendus, seuils d'alerte

## 75 — PNJ routines (2026-03-26) ✅

> Les PNJ se deplacent selon un horaire in-game, animes sur la carte via Mercure.
- [x] Entite `PnjSchedule` (pnj, hour, coordinates, map) — table horaire du PNJ
- [x] Migration SQL
- [x] `PnjRoutineService` : deplace les PNJ selon l'heure in-game courante
- [x] Commande Scheduler `app:pnj:routine` (toutes les 5 min)
- [x] Topic Mercure `map/pnj-move` pour animer le deplacement cote client
- [x] Animation de marche du PNJ dans le renderer PixiJS (reutiliser SpriteAnimator)
- [x] Fixtures : 4 PNJ avec routines simples (maison - travail - taverne)
- [x] Gestion du cas ou un joueur parle a un PNJ qui se deplace

## 88 — Stock boutique & restock (2026-03-26) ✅

> Les boutiques PNJ ont desormais un stock limite qui se reapprovisionne periodiquement.
- [x] Colonne `shop_stock` (JSON) sur l'entite Pnj — stock, maxStock, restockInterval par item
- [x] Migration SQL
- [x] `ShopController::buy()` verifie le stock et le decremente a l'achat
- [x] Commande `app:shop:restock` (mode one-shot ou boucle) — reapprovisionne selon l'intervalle
- [x] Affichage du stock restant dans le template boutique (badge couleur, rupture)
- [x] Bouton Acheter desactive si stock = 0
- [x] Fixtures : stock initial pour toutes les boutiques (PnjFixtures + VillageHubPnjFixtures)

## 86 — Quetes de decouverte cachees (2026-03-26) ✅

> Quetes non visibles dans le journal tant que non declenchees. Se declenchent automatiquement via les actions du joueur.
- [x] Champ `isHidden` (bool) sur Quest + champ `triggerCondition` (JSON)
- [x] `HiddenQuestTriggerListener` : ecoute PlayerMoveEvent, SpotHarvestEvent, MobDeadEvent
- [x] Si condition remplie, creer automatiquement le PlayerQuest
- [x] 4 quetes cachees dans les fixtures (clairiere secrete, slime rare, herborisme, cache gobelin)

## 85 — Evenements aleatoires (2026-03-26) ✅

> Systeme d'evenements aleatoires pour dynamiser le monde avec des bonus temporaires.
- [x] `RandomEventGenerator` : selection ponderee parmi 3 templates (Aurore Mystique, Esprit du Marchand, Heure Doree)
- [x] Prevention des doublons : un seul evenement aleatoire actif a la fois
- [x] Commande `app:events:random` (probabilite 30%, option `--force`)
- [x] Scheduler : execution toutes les 30 minutes
- [x] Duree limitee 10-30 min, parametres `random_event: true` pour identification
- [x] Integration automatique via GameEventExecutor (activation, Mercure broadcast, completion)
- [x] HUD existant affiche les evenements sans modification frontend
- [x] 8 tests unitaires couvrant generation, probabilite, doublons, parametres

## 90 — Herbier & catalogue minier (2026-03-26) ✅

> Catalogue des ressources recoltees par le joueur, avec paliers de decouverte et completion.
- [x] Entite `PlayerResourceCatalog` (player, item, collectCount, firstCollectedAt) — paliers 5/25/50
- [x] Migration SQL
- [x] `ResourceCatalogListener` : ecoute SpotHarvestEvent et GatheringEvent pour tracker les recoltes
- [x] `PlayerResourceCatalogRepository` avec requetes optimisees
- [x] `ResourceCatalogController` : page `/game/catalog`
- [x] Template Twig avec badges paliers, barre de progression, infos revelees
- [x] Navigation : lien dans le dropdown Aventure et le drawer mobile
- [x] Traductions FR/EN

## GCC-07 — Influence — entites score & log (2026-03-26) ✅

> Tables de score et journal des gains d'influence pour le systeme de controle de cite par les guildes.
- [x] Enum `InfluenceActivityType` : mob_kill, craft, harvest, fishing, butchering, quest, challenge
- [x] Entite `GuildInfluence` : guild, region, season, points (UNIQUE guild+region+season, index ranking)
- [x] Entite `InfluenceLog` : guild, region, season, player, activityType, pointsEarned, details (JSON), createdAt
- [x] Migration PostgreSQL (2 tables avec FK et index)

## GCC-08 — InfluenceListener — hook events PvE (2026-03-26) ✅

> Coeur du moteur d'influence : ecoute les evenements PvE existants et attribue des points d'influence aux guildes.
- [x] `InfluenceManager` : calculatePoints (formules par type), addPoints (upsert GuildInfluence + insert InfluenceLog), awardInfluence (orchestrateur)
- [x] Region determinee via `player.map.region` (FK directe)
- [x] Multiplicateur saisonnier via `season.parameters.multipliers[activityType]`
- [x] `InfluenceListener` (EventSubscriber) : MobDeadEvent, CraftEvent, SpotHarvestEvent, FishingEvent, ButcheringEvent, QuestCompletedEvent
- [x] Ignore si joueur pas en guilde ou map sans region
- [x] Tests unitaires : 15 tests InfluenceManagerTest + 14 tests InfluenceListenerTest

## 87 — Types quetes avances : enquete et defi boss (2026-03-26) ✅

> Deux nouveaux types de quetes avec tracking complet et integration UI.
- [x] Type `enquete` (talk_to) : parler a plusieurs PNJ pour avancer, tracke via PnjDialogEvent
- [x] Type `boss_challenge` : vaincre un boss sous conditions (no_heal, solo, time_limit)
- [x] Conditions de defi trackees dans le combat (colonne metadata sur Fight)
- [x] QuestBossChallengeTrackingListener et QuestTalkToTrackingListener
- [x] QuestTrackingFormater : formatTalkTo() et formatBossChallenge()
- [x] 2 quetes fixtures : enquete herboriste (3 PNJ), defi gardien de la foret

## 83 — Invasions (2026-03-26) ✅

> Vagues de monstres cooperatives via GameEvent. Les joueurs collaborent pour repousser l'invasion.
- [x] `InvasionManager` (EventSubscriber) : spawn des mobs a l'activation, vagues progressives, cleanup a la fin
- [x] Vagues progressives : 3 vagues espacees de 2 min, difficulte croissante (+2 niveaux par vague)
- [x] `InvasionKillTracker` : ecoute MobDeadEvent, track les kills par joueur dans les params de l'event
- [x] Recompenses collectives proportionnelles aux kills si objectif atteint
- [x] `InvasionTickCommand` (`app:invasion:tick`) : avancement periodique des vagues
- [x] Notifications Mercure : invasion_start, invasion_progress, invasion_end, invasion_mob_spawn/despawn
- [x] Nettoyage automatique des mobs d'invasion a la fin de l'event
- [x] Fixture : invasion gobeline (3 vagues de 4 mobs, objectif 8 kills, recurrente)

## 89 — Enchantements temporaires (2026-03-26) ✅

> Alchimiste applique un buff temporaire sur une arme/armure equipee. Les bonus s'appliquent en combat.
- [x] Entite `EnchantmentDefinition` (slug, name, element, statBonuses, duration, ingredients, requiredLevel, cost)
- [x] Entite `Enchantment` (playerItem, definition, appliedAt, expiresAt, isExpired(), getRemainingSeconds())
- [x] Migration SQL (tables game_enchantment_definitions + enchantments)
- [x] Service `EnchantmentManager` : apply, canEnchant, remove, cleanExpired, getEnchantmentBonuses
- [x] Route POST `/game/craft/enchant` (necessite skill alchimiste + ingredients + gils)
- [x] Expiration automatique verifiee au debut de chaque combat (FightHandler)
- [x] Bonus d'enchantement integres dans FightSpellController et FightAttackController
- [x] Section enchantements dans la page Artisanat (template _enchantment.html.twig)
- [x] Fixtures : 4 enchantements (Tranchant de feu, Protection de glace, Robustesse tellurique, Precision lumineuse)
- [x] Tests EnchantmentManager (10 tests, 20 assertions)

---

## 71 — World boss spawn & combat (2026-03-26) ✅

> Boss mondial spawn via evenements, visible sur la carte, combat multi-joueurs avec loot a contribution.
- [x] GameEventExecutor traite `boss_spawn` → creer un Mob boss sur une map donnee (params JSON)
- [x] Afficher le world boss sur la carte avec un sprite/aura distinctif
- [x] Despawn automatique quand l'event expire (si non vaincu)
- [x] Permettre a plusieurs joueurs d'engager le meme Mob (Fight partage)
- [x] `ContributionTracker` : tracker les degats infliges par chaque joueur pendant le combat
- [x] Loot base sur la contribution (top 3 = loot garanti, autres = loot probabiliste)
- [x] Tests world boss : FightContributionTest, WorldBossLootDistributorTest, FightHandlerWorldBossTest (18 tests, 48 assertions)

## 97 — Parsing animations tiles (2026-03-26) ✅

> Les fichiers TSX contiennent des animations de tiles (eau, torches). Le backend les extrait et les expose dans l'API.
- [x] `TmxParser::parseTileAnimations()` : extraction des `<tile><animation>` depuis les TSX (tileId local + duration)
- [x] Stockage des animations dans les metadonnees terrain (cle `animations` par tileset)
- [x] Exposition dans `GET /api/map/config` via champ `tileAnimations` (GID global → frames + durations)
- [x] Test unitaire `TmxParserAnimationTest` (parsing avec et sans animations)

## 67 — Foret des murmures (2026-03-26) ✅

> Carte de contenu lvl 5-15 : foret 60x60 avec monstres, PNJ, spots de recolte et portails vers le hub.
- [x] Design TMX 60x60 genere proceduralement (arbres, clairieres, riviere, chemins)
- [x] Map entity `map_3` dans MapFixtures
- [x] 10 mobs adaptes lvl 5-15 (slime, spider, undine, ochu, venom_snake, sylph, alpha_wolf, salamander, will_o_wisp nocturne, creeping_shadow nocturne)
- [x] 3 PNJ : Sylvain le Garde forestier, Elara l'Herboriste (boutique potions), Thadeus l'Ermite
- [x] Portails bidirectionnels Village ↔ Foret (3 portails)
- [x] 6 spots de recolte (menthe, sauge, pissenlit/lavande, romarin, mandragore, peche riviere)

## 68 — Mines profondes (2026-03-26) ✅

> Carte de contenu lvl 10-25 : mines 60x30 avec tunnels, boss de mine, filons et PNJ.
- [x] Map entity `map_4` dans MapFixtures (60x30)
- [x] 11 mobs adaptes lvl 10-25 (stone_golem, rusty_automaton, clay_golem, crystal_golem, gargoyle, cursed_knight nocturne, abyssal_blacksmith, lesser_lich nocturne, groupe patrouille automates)
- [x] Boss de mine : Seigneur de la Forge (forge_lord) en salle profonde
- [x] 3 PNJ : Grimmur le Contremaître, Hilda l'Ingenieure (boutique potions + pioche), Noric le Marchand souterrain (boutique minerais)
- [x] Portails bidirectionnels Village ↔ Mines (3 portails)
- [x] 6 spots de recolte minerais (cuivre, fer x2, argent, or, rubis) repartis par profondeur

## MED-08 — Undo / Redo editeur de carte (2026-03-26) ✅

> Historique des modifications dans l'editeur de carte web (tiles, collisions, murs). 50 operations max.
- [x] Systeme d'historique integre au controller Stimulus (stack undo/redo, 50 ops)
- [x] Capture des changements par stroke (mousedown→mouseup = 1 entree)
- [x] Support tiles, collisions, murs et bucket fill
- [x] Raccourcis Ctrl+Z (undo) / Ctrl+Y ou Ctrl+Shift+Z (redo)
- [x] Boutons undo/redo dans la barre d'outils avec etat disabled
- [x] Reset historique apres sauvegarde ou annulation

## MED-16 — Export TMX & tests unitaires (2026-03-27) ✅

> Export des cartes creees dans l'editeur web vers le format Tiled (.tmx) pour validation externe.
- [x] Classe `TmxExporter` dans `src/GameEngine/Terrain/TmxExporter.php`
- [x] Export 5 layers (background, ground, decoration, overlay, collision) en CSV
- [x] Export objectgroup (portals, mob_spawn, harvest_spot, npc_spawn) avec coordonnees pixels
- [x] Route `GET /admin/maps/{id}/export-tmx` avec telechargement (Content-Disposition: attachment)
- [x] Bouton "Exporter TMX" dans la toolbar de l'editeur
- [x] 10 tests unitaires (27 assertions) : XML valide, attributs map, tilesets, layers, GIDs, collisions, borders, filename
