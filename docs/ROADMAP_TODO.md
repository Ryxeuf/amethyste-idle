# Roadmap a venir — Amethyste-Idle

> Toutes les taches restantes a implementer.
> Derniere mise a jour : 2026-03-20

---

## Reliquats des phases completees

### Phase GD-8 — Materia (reste)
- [ ] Verification `actions.materia.unlock` dans CombatCapacityResolver avant d'autoriser un sort
- [ ] Methode `getUnlockedMateriaSpellSlugs(Player)` dans CombatSkillResolver
- [ ] `canEquipMateria()` dans PlayerItemHelper : verifier competence requise
- [ ] Validation cote controleur dans FightSpellController
- [ ] Griser les sorts sans skill requis dans les templates combat

### Phase 10 — Catalogue (reste)
- [ ] Herbier & catalogue minier : fiche par ressource, premiere decouverte, completion
- [ ] Achievements caches : decouverts par des actions inhabituelles
- [ ] Categories de succes additionnelles : Recolte, Craft, Social, Secrets

---

## Gameplay Core (v0.4) — Completions & corrections

> Infrastructure existante : ShopController, HarvestManager (21 spots, Mercure),
> CraftManager + CraftingManager (2 systemes), Quest (10 quetes, tracking monster uniquement).
> Les sous-phases ci-dessous comblent les lacunes identifiees.

### v0.4-A — Fixtures boutiques PNJ (Priorite: HAUTE | Complexite: S | Gain: FORT)
> Le controller et le template existent. Il manque les donnees pour que les joueurs puissent acheter/vendre.
- [ ] Configurer `shopItems` sur 3-5 PNJ existants dans PnjFixtures (armurier, alchimiste, marchand general)
- [ ] Verifier que les items references dans shopItems existent dans ItemFixtures
- [ ] Tester manuellement : ouvrir boutique, acheter, vendre, fonds insuffisants

### v0.4-B — Consolidation craft : supprimer le systeme duplique (Priorite: HAUTE | Complexite: S | Gain: MOYEN)
> Deux systemes concurrents (CraftManager/CraftController + CraftingManager/CraftingController) creent de la confusion.
> Garder un seul systeme, supprimer l'autre.
- [ ] Auditer les 2 systemes : identifier lequel est le plus complet (CraftManager vs CraftingManager)
- [ ] Supprimer le systeme redondant (controller, manager, templates, entity si applicable)
- [ ] Mettre a jour les references (routes, liens dans templates, DOCUMENTATION.md)

### v0.4-C — Fixtures recettes de craft (Priorite: HAUTE | Complexite: M | Gain: FORT)
> Le systeme de craft existe mais 0 recette en base. Sans donnees, le craft est inutilisable.
- [ ] Creer CraftRecipeFixtures avec ~10 recettes de base :
  - Forge : epee en fer, bouclier en fer, casque en fer (ingredients : minerai de fer)
  - Alchimie : potion de soin, potion de mana (ingredients : herbes)
  - Tannerie : armure en cuir (ingredients : cuir brut)
  - Joaillerie : anneau simple (ingredients : minerai d'argent/or)
- [ ] Verifier que les items ingredients existent dans ItemFixtures (creer si manquants)
- [ ] Tester manuellement : acceder a un atelier, crafter un item, verifier inventaire

### v0.4-D — Tracking quetes collect/craft (Priorite: HAUTE | Complexite: M | Gain: FORT)
> PlayerQuestHelper ne traite que les objectifs `monsters`. Les quetes collect et craft ne progressent jamais.
- [ ] Ajouter le tracking `collect` dans PlayerQuestHelper::getPlayerQuestProgress()
- [ ] Creer QuestCollectTrackingListener : ecoute SpotHarvestEvent, met a jour les quetes actives
- [ ] Creer QuestCraftTrackingListener : ecoute CraftEvent, met a jour les quetes actives
- [ ] Verifier que les 2 quetes existantes avec objectif collect (mushroom, wood) progressent
- [ ] Tests unitaires : progression collect, progression craft, completion automatique

### v0.4-E — Indicateurs quetes sur PNJ (Priorite: MOYENNE | Complexite: S | Gain: FORT)
> Aucun indicateur visuel (! ou ?) n'apparait au-dessus des PNJ donneurs de quetes sur la carte.
- [ ] Ajouter un champ `hasAvailableQuest` dans /api/map/entities pour les PNJ
- [ ] Afficher une icone (! quete dispo, ? quete en cours) au-dessus du sprite PNJ dans PixiJS
- [ ] Mettre a jour l'icone dynamiquement quand le joueur accepte/complete une quete

### v0.4-F — Tests unitaires systemes core (Priorite: MOYENNE | Complexite: M | Gain: MOYEN)
> Aucun test pour shop, harvest, craft ni quest. Fragilise la base de code.
- [ ] Tests ShopController : achat OK, fonds insuffisants, item soulbound invendable
- [ ] Tests HarvestManager : recolte OK, skill manquant, cooldown actif, XP accordee
- [ ] Tests CraftManager : craft OK, ingredients manquants, skill manquant, item cree
- [ ] Tests QuestProgressTracker : progression monster, collect, craft, completion

### v0.4-G — Stock boutique et restock (Priorite: BASSE | Complexite: M | Gain: FAIBLE)
> Actuellement les boutiques ont un stock illimite. Le restock ajoute de la profondeur economique
> mais n'est pas bloquant pour le gameplay de base.
- [ ] Ajouter champs stock/maxStock/restockInterval dans la structure shopItems (JSON)
- [ ] ShopRestockScheduler : commande/scheduler qui restock les boutiques periodiquement
- [ ] Afficher le stock restant dans le template boutique
- [ ] Bloquer l'achat si stock = 0

---

## Combat enrichi

> **Note** : De nombreuses fonctionnalites de combat sont deja implementees (voir ROADMAP_DONE.md).
> Les items ci-dessous sont les **taches restantes reelles**, decoupees en sous-phases.

### CE-1 — Icones statuts sur la timeline (Taille S)
> Complexite: Faible | Priorite: Haute | Gain: Feedback visuel immediat en combat
- [ ] Ajouter les badges statut actifs sous chaque avatar dans `_timeline.html.twig`
- [ ] Afficher l'icone emoji + tours restants (tooltip au survol)
- [ ] Tester visuellement avec poison, bouclier, berserk

### CE-2 — Indicateur de difficulte monstres (Taille S)
> Complexite: Faible | Priorite: Moyenne | Gain: Lisibilite pour le joueur
- [ ] Champ `difficulty` (int 1-5) sur l'entite Monster (migration)
- [ ] Afficher des etoiles dans le template combat (a cote du nom du mob)
- [ ] Afficher les etoiles dans le bestiaire
- [ ] Renseigner la difficulte dans MonsterFixtures

### CE-3 — Recompenses uniques de boss (Taille S)
> Complexite: Faible | Priorite: Haute | Gain: Motivation a affronter les boss
- [ ] Ajouter des items legendaires boss-only dans les ItemFixtures (1-2 items par boss)
- [ ] Configurer les LootTable des boss existants avec drop garanti d'un item legendaire
- [ ] Badge "Boss" sur les items dans l'inventaire (via item.rarity ou lootSource)

### CE-4 — Slots de Materia lies (Taille M)
> Complexite: Moyenne | Priorite: Basse | Gain: Profondeur build, synergie equipement
> Prerequis: systeme materia deja fonctionnel
- [ ] Ajouter un champ `linkedSlotId` (nullable) sur l'entite Slot (migration)
- [ ] Logique `LinkedMateriaResolver` : si 2 slots lies ont des materia du meme element, bonus +15% degats
- [ ] Integrer le bonus dans CombatCapacityResolver
- [ ] Afficher le lien entre slots dans le template inventaire (trait visuel)
- [ ] Ajouter des slots lies sur quelques equipements avances dans les fixtures

### CE-5 — Monstres soigneurs (Taille M)
> Complexite: Moyenne | Priorite: Moyenne | Gain: Variete tactique des combats
> Prerequis: necessite des combats multi-mobs (groupes de mobs)
- [ ] Support multi-mobs dans FightController : engager un groupe de mobs (2-3 mobs)
- [ ] MobActionHandler : role `healer` cible un allie blesse (mob avec le moins de PV%)
- [ ] SpellApplicator : supporter les heals mob→mob
- [ ] Template combat : afficher plusieurs mobs avec barres de vie individuelles
- [ ] Fixtures : groupe de mobs avec un soigneur (ex: 2 Squelettes + 1 Necromancien soigneur)
- [ ] Tests : heal mob→mob, ciblage du plus blesse

### CE-6 — Monstres invocateurs (Taille M)
> Complexite: Moyenne | Priorite: Basse | Gain: Combats dynamiques et imprevisibles
> Prerequis: CE-5 (multi-mobs)
- [ ] Nouvelle action IA `summon` dans MobActionHandler
- [ ] Creer un Mob en cours de combat (ajout a la Fight, insertion dans la timeline)
- [ ] Limite d'invocation (max 2 renforts par combat)
- [ ] FightTurnResolver : recalculer la timeline quand un mob est ajoute
- [ ] Fixtures : monstre invocateur (ex: Necromancien invoque des Squelettes)
- [ ] Message de log specifique ("X invoque un Y !")

---

## Contenu & monde (v0.5)

### Contenu monstres & tables de loot
- [ ] 8 nouveaux monstres niveaux 1-10 (1 par element)
- [ ] 8 monstres intermediaires niveaux 10-25
- [ ] 2 boss de zone avec mecaniques de phases
- [ ] Tables de loot avec ressources de craft
- [ ] Mise a jour bestiaire et succes pour les nouveaux monstres

### Equipement & items varies
- [ ] 3 tiers d'equipement (starter, intermediaire, avance) avec arme, casque, plastron, jambieres, bottes, gants, bouclier
- [ ] Elements varies sur l'equipement
- [ ] Slots materia sur l'equipement avance
- [ ] 16+ materia (2 par element : basique et avancee)
- [ ] Consommables : potions, nourriture, parchemins

### Nouvelles zones & cartes
- [ ] Systeme de teleportation entre cartes (entite + frontend)
- [ ] Carte "Foret des murmures" (zone lvl 5-15) avec spots recolte, monstres, PNJ
- [ ] Carte "Mines profondes" (zone lvl 10-25) avec filons, boss, tresor
- [ ] Carte "Village central" (hub) avec boutiques, PNJ quetes, banque, forge

### Equilibre & balancing
- [ ] Courbe de progression XP par domaine
- [ ] Bareme des prix boutique (ratio achat/vente)
- [ ] Table de drop rates par tier de monstre
- [ ] Degats/HP des monstres par palier
- [ ] Cout en XP des competences (calibrage 400+ skills)
- [ ] Temps de recolte et rendement par skill level
- [ ] Commande CLI BalanceReportCommand

---

## Progression & builds

### Arbres de talent etendus
- [ ] Chaque domaine combat passe a 10-15 competences en 3 branches
- [ ] Competence ultime au sommet de chaque arbre

### Systeme de build
- [ ] Multi-domaine avec points limites
- [ ] Respec payant (prix croissant)
- [ ] Presets de build (sauvegarder/charger configurations)
- [ ] Synergies cross-domaine (combos entre domaines differents)

### Equipement et raretes
- [ ] Systeme de rarete complet (Commun → Peu commun → Rare → Epique → Legendaire → Amethyste)
- [ ] Enchantement temporaire par alchimiste
- [ ] Sets d'equipement avec bonus croissants (2/3/4 pieces)

---

## Monde vivant

### Cycle jour/nuit (gameplay)
- [ ] Impact gameplay : monstres nocturnes, plantes de nuit, PNJ marchands fermes la nuit
- [ ] Visibilite reduite la nuit
- [ ] Cycle 1h reelle = 1 journee in-game (configurable admin)

### Systeme meteo
- [ ] Types de meteo : ensoleille, nuageux, pluie, orage, brouillard, neige
- [ ] Impact gameplay (bonus/malus elementaires, monstres speciaux)
- [ ] Meteo aleatoire par zone (changement toutes les 15-30 min)
- [ ] Effets visuels PixiJS (particules pluie, flocons, eclairs)

### Ecosysteme vivant
- [ ] PNJ avec routines (maison → travail → taverne selon l'heure)
- [ ] Evenements aleatoires (invasion monstres, marchand itinerant, aurore boreale)
- [ ] Saisonnalite (festivals lies aux vraies saisons)

---

## Quetes et narration

### Types de quetes varies
- [ ] Escorte : proteger un PNJ d'un point A a B
- [ ] Livraison : apporter un item a un PNJ dans une autre zone
- [ ] Exploration : decouvrir X zones / atteindre un lieu
- [ ] Craft : fabriquer un item pour un PNJ
- [ ] Enquete : parler a plusieurs PNJ pour rassembler des indices
- [ ] Defi de boss : vaincre un boss dans des conditions donnees

### Chaines de quetes et choix
- [ ] Chaines de quetes liees (Q1 → Q2 → Q3 → recompense finale)
- [ ] Quetes a choix (influence recompenses ou suite de l'histoire)
- [ ] Journal de quetes (actives, terminees, disponibles)

### Trame principale
- [ ] Acte 1 — L'Eveil : tutoriel narratif (forgeron, premier combat, premiere recolte, cristal d'amethyste)
- [ ] Acte 2 — Les Fragments : 4 fragments dans 4 zones (non-lineaire)
- [ ] Acte 3 — La Convergence : donjon final
- [ ] Portraits de personnages dans les dialogues

### Quetes secondaires
- [ ] Quetes de faction (reputation avec guildes)
- [ ] Quetes quotidiennes (renouvelees chaque jour)
- [ ] Quetes de decouverte (cachees, declenchees par exploration)

---

## Multijoueur & social

### Chat en jeu
- [ ] Chat global (Mercure SSE)
- [ ] Chat de zone
- [ ] Messages prives
- [ ] Commandes (/whisper, /zone, /global, /emote)
- [ ] Filtres anti-spam et moderation

### Guildes
- [ ] Creation de guilde (nom, blason, description)
- [ ] Rangs (Maitre, Officier, Membre, Recrue)
- [ ] Chat de guilde
- [ ] Coffre de guilde (inventaire partage + logs)
- [ ] Quetes de guilde (objectifs collectifs)
- [ ] Classement des guildes

### Groupes de combat
- [ ] Formation de groupe (2-4 joueurs)
- [ ] Combat de groupe
- [ ] Loot partage (round-robin, besoin/cupidite, free for all)
- [ ] Synergie de groupe (bonus roles complementaires)

### PvP
- [ ] Arene 1v1 classee avec matchmaking
- [ ] Saisons PvP avec classements et recompenses
- [ ] Duels entre joueurs

---

## Contenu endgame

> Infrastructure existante : GameEvent (entity + admin CRUD, types boss_spawn/invasion/xp_bonus/drop_bonus/custom,
> recurrence, mais aucun executeur backend). Fight supporte multi-mobs en entite (OneToMany) mais
> MobActionHandler ne traite que le premier mob. LootTable basique (MonsterItem + probabilite).
> Mercure fonctionne pour map/move, map/respawn, map/spot. Pas de systeme faction/reputation ni donjon/instance.

### EG-1 — Executeur GameEvent : activer les evenements planifies (Priorite: HAUTE | Complexite: S | Gain: FORT)
> GameEvent existe en BDD avec admin CRUD mais rien ne se passe quand un event est ACTIVE.
> Ce service est le socle de tout le contenu endgame evenementiel (world boss, invasions, bonus XP/drop).
- [ ] Creer `GameEventExecutor` : service qui lit les GameEvent SCHEDULED dont startsAt <= now, les passe ACTIVE
- [ ] Traiter les types existants : `xp_bonus` (modifier global XP), `drop_bonus` (modifier global drop rate)
- [ ] Creer `GameEventSchedulerMessage` + handler Symfony Scheduler (toutes les 60s)
- [ ] Passer les events expires (endsAt < now) en COMPLETED automatiquement
- [ ] Gerer la recurrence : si `recurrenceInterval` non null, creer le prochain event a la completion
- [ ] Tester : creer un event xp_bonus via admin, verifier qu'il s'active et expire correctement

### EG-2 — Annonces serveur Mercure pour evenements (Priorite: HAUTE | Complexite: S | Gain: FORT)
> Les joueurs n'ont aucun moyen de savoir qu'un evenement est en cours. Prerequis : EG-1.
- [ ] Nouveau topic Mercure `event/announce` : publier quand un GameEvent passe ACTIVE
- [ ] Stimulus controller `event-notification` : afficher un toast/banner quand un event demarre
- [ ] Afficher les events actifs dans le HUD (petite icone avec tooltip)
- [ ] Tester : activer un event, verifier que tous les joueurs connectes voient la notification

### EG-3 — Systeme de reputation et factions (Priorite: HAUTE | Complexite: M | Gain: FORT)
> Systeme autonome sans prerequis technique lourd. Ajoute une boucle de progression endgame
> et permet de gater du contenu (recettes, equipements, zones) derriere des paliers de reputation.
- [ ] Entite `Faction` : slug, name, description, icon
- [ ] Entite `PlayerFaction` : player (ManyToOne), faction (ManyToOne), reputation (int), tier (enum)
- [ ] Enum `ReputationTier` : Inconnu(0), Hostile(-1), Neutre(1), Ami(2), Honore(3), Revere(4), Exalte(5)
- [ ] Calcul automatique du tier selon les seuils de reputation (0, 500, 2000, 5000, 10000, 20000)
- [ ] Migration + fixtures 4 factions (Marchands, Chevaliers, Mages, Ombres) avec description et PNJ associe
- [ ] Route `/game/factions` : liste des factions, reputation actuelle, palier, barre de progression

### EG-4 — Gains et recompenses de reputation (Priorite: HAUTE | Complexite: S | Gain: FORT)
> Prerequis : EG-3. Sans gains ni recompenses, le systeme de faction est une coquille vide.
- [ ] `ReputationManager::addReputation(Player, Faction, amount)` : ajouter/retirer de la reputation
- [ ] Integrer les gains : quetes completees (+rep faction liee), mobs tues (+rep si faction associee)
- [ ] Entite `FactionReward` : faction, requiredTier, rewardType (recipe_unlock/item/discount/zone_access), rewardData JSON
- [ ] Fixtures : 2-3 recompenses par palier significatif (Ami, Honore, Exalte) par faction
- [ ] Afficher les recompenses debloquees/verrouillees sur la page faction
- [ ] Tester : gagner de la reputation, changer de palier, debloquer une recompense

### EG-5 — Loot exclusif et rarete etendue (Priorite: MOYENNE | Complexite: S | Gain: MOYEN)
> Enrichir le systeme de loot existant (MonsterItem) pour supporter du contenu endgame.
> Pas de prerequis technique, ameliore directement la motivation des joueurs.
- [ ] Ajouter champ `guaranteed` (bool, defaut false) sur MonsterItem : drop garanti (100%) en plus de la proba
- [ ] Ajouter champ `minDifficulty` (nullable int) sur MonsterItem : drop uniquement si difficulte >= X (pour Heroique/Mythique)
- [ ] Creer 4-6 items legendaires exclusifs lies aux boss existants dans les fixtures
- [ ] Configurer les LootTable des boss avec au moins 1 drop garanti legendaire
- [ ] Badge visuel "Legendaire" dans l'inventaire (couleur doree sur les items rarity=legendary)

### EG-6 — World boss : spawn et combat multi-joueurs (Priorite: MOYENNE | Complexite: L | Gain: FORT)
> Prerequis : EG-1 (executeur events), EG-2 (annonces Mercure).
> Necessite de resoudre le combat multi-joueurs (plusieurs joueurs dans un meme Fight).
> Decoupage en 2 sous-taches si trop volumineux.
- [ ] **Sous-phase A — Spawn** : GameEventExecutor traite `boss_spawn` → creer un Mob boss sur une map donnee (params JSON)
- [ ] **Sous-phase A** : Afficher le world boss sur la carte avec un sprite/aura distinctif
- [ ] **Sous-phase A** : Despawn automatique quand l'event expire (si non vaincu)
- [ ] **Sous-phase B — Combat multi-joueurs** : Permettre a plusieurs joueurs d'engager le meme Mob (Fight partage)
- [ ] **Sous-phase B** : `ContributionTracker` : tracker les degats infliges par chaque joueur pendant le combat
- [ ] **Sous-phase B** : Loot base sur la contribution (top 3 = loot garanti, autres = loot probabiliste)
- [ ] Tester : spawn world boss via event admin, 2+ joueurs l'engagent, loot distribue

### EG-7 — Donjons : entite et entree (Priorite: MOYENNE | Complexite: M | Gain: FORT)
> Premier pas vers les donjons instancies. Creer la structure sans les mecaniques complexes.
> Prerequis : systeme de teleportation entre cartes (section "Nouvelles zones & cartes").
- [ ] Entite `Dungeon` : slug, name, description, map (ManyToOne vers la carte du donjon), minLevel (int), maxPlayers (int)
- [ ] Entite `DungeonRun` : dungeon, player(s), startedAt, completedAt, difficulty (enum Normal/Heroique/Mythique)
- [ ] Enum `DungeonDifficulty` : Normal, Heroique, Mythique (avec multiplicateurs HP/degats mobs)
- [ ] Migration + fixtures : 1 donjon de test (ex: "Racines de la foret", lie a une carte existante ou nouvelle)
- [ ] Route `/game/dungeon/{slug}/enter` : creer un DungeonRun, teleporter le joueur dans la carte du donjon
- [ ] Route `/game/dungeon/{slug}` : fiche du donjon (description, difficulte, loot possible, cooldown)
- [ ] Cooldown entre runs (ex: 1h Normal, 4h Heroique, 24h Mythique)

### EG-8 — Donjons : mecaniques et loot (Priorite: BASSE | Complexite: L | Gain: FORT)
> Prerequis : EG-7, EG-5 (loot exclusif). Rend les donjons interessants avec des mecaniques propres.
> A decouper en sous-phases si necessaire.
- [ ] Mobs du donjon : spawns specifiques au DungeonRun, stats scalees selon difficulte
- [ ] Boss de fin de donjon avec mecaniques de phase (reutiliser bossPhases existant)
- [ ] LootTable specifique donjon : items exclusifs par difficulte (utiliser minDifficulty de EG-5)
- [ ] Completion du donjon : marquer DungeonRun completed, teleporter le joueur hors du donjon
- [ ] Succes lies aux donjons (premier clear, clear Mythique, clear sans mort)

### EG-9 — Evenements temporaires : invasions (Priorite: BASSE | Complexite: M | Gain: MOYEN)
> Prerequis : EG-1 (executeur), EG-2 (annonces). Vagues de monstres cooperatives.
- [ ] GameEventExecutor traite `invasion` : spawner N mobs supplementaires sur une zone (params JSON : mobSlugs, count, mapId)
- [ ] Vagues progressives : 3 vagues espacees de 2 min, difficulte croissante
- [ ] Tracker les kills de tous les joueurs pendant l'invasion
- [ ] Recompenses collectives si objectif atteint (X mobs tues avant la fin)
- [ ] Nettoyer les mobs d'invasion a la fin de l'event

### EG-10 — Evenements temporaires : bonus et festivals (Priorite: BASSE | Complexite: S | Gain: MOYEN)
> Prerequis : EG-1, EG-2. Les types xp_bonus et drop_bonus sont deja dans GameEvent.
> Il manque l'integration dans les calculs de jeu.
- [ ] Integrer `drop_bonus` dans LootGenerator : multiplier les probabilites de drop pendant l'event actif
- [ ] Integrer `xp_bonus` dans les systemes d'XP (combat, recolte, craft) : multiplier l'XP gagnee
- [ ] Quetes d'evenement : quetes temporaires liees a un GameEvent (disparaissent a la fin)
- [ ] Cosmetiques d'evenement : items decoratifs exclusifs comme recompenses

### EG-11 — Tournois PvP (Priorite: BASSE | Complexite: XL | Gain: MOYEN)
> Prerequis : PvP arene 1v1 (section "Multijoueur & social"). Trop dependant d'autres systemes
> pour etre implemente a court terme. Garder comme objectif long terme.
- [ ] Entite `Tournament` : type, bracket, dates, recompenses
- [ ] Inscription et matchmaking par bracket
- [ ] Deroulement automatique (ou semi-auto) des rounds
- [ ] Classement et recompenses saisonnieres

---

## Polish & qualite (v0.6)

### Tests fonctionnels & E2E
- [ ] Tests fonctionnels pour tous les controleurs Game/*
- [ ] Tests E2E Panther : parcours combat, quete, craft
- [ ] Tests integration : evenements et listeners
- [ ] Objectif : couverture >= 60% sur src/GameEngine/

### UX/UI ameliorations
- [ ] Minimap (position joueur, mobs, PNJ)
- [ ] Notifications in-game (toast pour drops, level-up, quetes, succes)
- [ ] Indicateurs PNJ (! quete, $ boutique, ? dialogue)
- [ ] Barre d'action rapide (raccourcis consommables/sorts)
- [ ] Journal de combat ameliore (log detaille, couleurs elementaires)
- [ ] Optimisation tactile mobile

### Effets visuels & ambiance
- [ ] Cycle jour/nuit PixiJS (filtre teinte chaude → froide)
- [ ] Particules pour sorts en combat, recolte, level-up
- [ ] Animations de combat (shake critiques, flash elementaire)
- [ ] Transitions de zone (fondu au noir)
- [ ] Sons (optionnel) : Howler.js pour effets sonores de base

### Performance & monitoring
- [ ] Cache Doctrine (result cache sur requetes frequentes)
- [ ] Optimisation queries (N+1 detection, eager loading)
- [ ] Index DB composites sur tables critiques
- [ ] Monitoring Prometheus/Grafana basiques
- [ ] Rate limiting API (mouvements, achats, craft)

---

## Pipeline Tiled (transverse)

### T1 — Animations de tiles
- [ ] Backend : parser les animations TSX (<tile><animation>)
- [ ] API : exposer les animations dans /api/map/config
- [ ] Frontend : PIXI.AnimatedSprite au lieu de PIXI.Sprite pour les tiles animees

### T2 — Pipeline unifie `app:terrain:sync`
- [ ] Nouvelle commande unifiee (import + upsert Area + sync objets + Dijkstra + rapport diff)
- [ ] Extraire TmxParser, AreaSynchronizer, DijkstraTagGenerator en services
- [ ] Mise a jour de l'agent import-terrain

### T3 — Zones/biomes depuis Tiled
- [ ] Support des zones rectangulaires dans Tiled (biome, ambient, weather, music, light)
- [ ] API zones dans /api/map/config
- [ ] Frontend : effets d'ambiance par zone (particules, assombrissement, transitions)

### T4 — Supprimer la commande CSS morte
- [ ] Supprimer TmxCssGeneratorCommand
- [ ] Supprimer assets/styles/map/ (CSS genere)
- [ ] Nettoyer les references dans CLAUDE.md et DOCUMENTATION.md

### T5 — De-hardcoder les map IDs
- [ ] syncEntitiesFromObjects : deduire mapId depuis le TMX
- [ ] loadMap() : utiliser player->getMap()->getId() au lieu de 10
- [ ] Endpoint move : utiliser le mapId du joueur courant
