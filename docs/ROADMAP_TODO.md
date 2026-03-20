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

> **Etat actuel** : 10 quetes en base (kill/collect), tracking monster uniquement,
> journal de quetes basique (actives/terminees), PnjDialogParser avec conditions
> (quest, quest_not, quest_active, has_item, domain_xp_min).
> **Prerequis v0.4** : v0.4-D (tracking collect/craft) doit etre fait avant QN-2.

### QN-1 — Recompenses de quetes completes (Priorite: HAUTE | Complexite: S | Gain: FORT)
> Le controller ne distribue que les gils. Les champs XP et items existent dans les fixtures mais sont ignores.
- [ ] Appliquer `rewards.xp` dans QuestController::complete() (ajouter XP au domaine ou XP generique)
- [ ] Appliquer `rewards.items` : creer les PlayerItem a partir de genericItemSlug + quantity
- [ ] Afficher les recompenses detaillees (XP, items, gils) dans le template journal de quetes
- [ ] Tester : completer une quete avec recompenses mixtes, verifier inventaire + gils + XP

### QN-2 — Types de quetes : livraison et exploration (Priorite: HAUTE | Complexite: M | Gain: FORT)
> Prerequis : v0.4-D (tracking collect/craft). Ajoute 2 types de quetes realisables avec l'infra existante.
- [ ] Ajouter support `requirements.deliver` dans QuestTrackingFormater : {item_slug, pnj_id, quantity}
- [ ] Tracking livraison : listener sur dialogue PNJ, verifier si le joueur a l'item en inventaire
- [ ] Ajouter support `requirements.explore` dans QuestTrackingFormater : {map_id} ou {coordinates}
- [ ] Tracking exploration : listener sur PlayerMoveEvent, verifier si zone/coordonnees atteintes
- [ ] 2-3 quetes fixtures : 1 livraison (apporter item a un PNJ), 1 exploration (atteindre un lieu)
- [ ] Tests unitaires : progression livraison, progression exploration

### QN-3 — Prerequis de quetes et chaines simples (Priorite: HAUTE | Complexite: S | Gain: FORT)
> Permet de creer des chaines Q1→Q2→Q3. Le PnjDialogParser supporte deja `quest` et `quest_not`.
- [ ] Ajouter champ `prerequisiteQuests` (JSON, nullable) sur l'entite Quest (migration)
- [ ] Verifier les prerequis dans QuestController::accept() (refuser si prerequis non remplis)
- [ ] Adapter PnjDialogParser : afficher la quete suivante seulement si prerequis remplis
- [ ] 1 chaine de 3 quetes dans les fixtures (Q1→Q2→Q3 avec recompense finale)
- [ ] Afficher les quetes disponibles (prerequis ok, non acceptees, non completees) dans le journal

### QN-4 — Journal de quetes enrichi (Priorite: MOYENNE | Complexite: S | Gain: FORT)
> Le journal existe mais est basique. Ajout d'un onglet "disponibles" et meilleure UX.
- [ ] Onglet "Disponibles" : lister les quetes dont les prerequis sont remplis et non encore acceptees
- [ ] Filtrage par type de quete (kill, collect, deliver, explore)
- [ ] Afficher le PNJ donneur de quete (nom + localisation) pour chaque quete
- [ ] Indicateur de chaine : afficher "Quete 2/3" si la quete fait partie d'une chaine
- [ ] Lien vers la carte pour localiser le PNJ donneur

### QN-5 — Trame principale — Acte 1 : L'Eveil (Priorite: HAUTE | Complexite: M | Gain: TRES FORT)
> Tutoriel narratif. Chaine de 4-5 quetes guidant le joueur dans ses premieres actions.
> Utilise les systemes existants (kill, collect, deliver, explore) — pas de nouvelle mecanique.
- [ ] Quete 1.1 "Reveil" : dialogue d'introduction avec un PNJ guide, explorer le village
- [ ] Quete 1.2 "Premiers pas" : aller voir le forgeron, recevoir une arme de base
- [ ] Quete 1.3 "Bapteme du feu" : tuer 2 monstres faibles dans la zone de depart
- [ ] Quete 1.4 "Recolte" : collecter des ressources de base (herbes ou minerai)
- [ ] Quete 1.5 "Le cristal d'amethyste" : explorer un lieu specifique, dialogue revelateur
- [ ] Dialogues narratifs pour chaque PNJ implique (guide, forgeron, ancien du village)
- [ ] Recompenses progressives (equipement starter, gils, XP, premiere materia)

### QN-6 — Quetes a choix (Priorite: MOYENNE | Complexite: M | Gain: FORT)
> Ajoute des embranchements narratifs. Le PnjDialogParser supporte deja les choices.
- [ ] Ajouter champ `choiceOutcome` (JSON, nullable) sur Quest : mapper choix → quete suivante
- [ ] Adapter QuestController::complete() : si choix fait, orienter vers la branche correspondante
- [ ] Stocker le choix du joueur dans PlayerQuestCompleted (champ `choiceMade`, JSON nullable)
- [ ] 1 quete a choix dans les fixtures (2 branches, recompenses differentes)
- [ ] Condition `quest_choice` dans PnjDialogParser : adapter le dialogue selon le choix passe

### QN-7 — Quetes quotidiennes (Priorite: MOYENNE | Complexite: M | Gain: FORT)
> Contenu renouvelable qui donne une raison de revenir chaque jour.
- [ ] Champ `isDaily` (bool) + `dailyPool` (JSON) sur Quest : pool de variantes
- [ ] DailyQuestScheduler (Symfony Scheduler) : chaque jour, selectionner 3 quetes du pool
- [ ] Permettre de re-accepter une quete quotidienne (lever la contrainte unique player+quest)
- [ ] Entite PlayerDailyQuest ou reset du PlayerQuest chaque jour
- [ ] 5-8 quetes quotidiennes dans les fixtures (kill X, collect Y, variantes simples)
- [ ] Section "Quotidiennes" dans le journal de quetes

### QN-8 — Trame principale — Acte 2 : Les Fragments (Priorite: BASSE | Complexite: L | Gain: FORT)
> 4 chaines de quetes dans 4 zones. Prerequis : plusieurs cartes existantes (v0.5 Nouvelles zones).
> A decouper en 4 sous-phases (1 par fragment/zone) quand les zones seront pretes.
- [ ] Fragment Foret : chaine de 3-4 quetes (exploration, combat, enigme PNJ)
- [ ] Fragment Mines : chaine de 3-4 quetes (recolte, craft, boss minier)
- [ ] Fragment Marais : chaine de 3-4 quetes (enquete, livraison, combat)
- [ ] Fragment Montagne : chaine de 3-4 quetes (exploration, defi de boss)
- [ ] Chaque fragment donne un item cle collectible

### QN-9 — Types de quetes avances : enquete et defi de boss (Priorite: BASSE | Complexite: M | Gain: MOYEN)
> Mecaniques plus complexes, a faire quand le contenu de base est solide.
- [ ] Type `enquete` : requirements.talk_to = [{pnj_id, condition}], tracking sur dialogue PNJ
- [ ] Type `boss_challenge` : requirements.boss = {monster_slug, conditions: {no_heal, solo, time_limit}}
- [ ] Conditions de defi trackees dans le combat (FightController enregistre les contraintes)
- [ ] 2 quetes fixtures : 1 enquete (parler a 3 PNJ), 1 defi de boss

### QN-10 — Quetes de decouverte cachees (Priorite: BASSE | Complexite: S | Gain: MOYEN)
> Quetes non visibles dans le journal tant que non declenchees. Recompense l'exploration.
- [ ] Champ `isHidden` (bool) sur Quest + champ `triggerCondition` (JSON)
- [ ] HiddenQuestTriggerListener : ecoute PlayerMoveEvent, SpotHarvestEvent, MobDeadEvent
- [ ] Si condition remplie, creer automatiquement le PlayerQuest + notification
- [ ] 3-4 quetes cachees dans les fixtures (lieu secret, mob rare, action inhabituelle)

### QN-11 — Portraits de personnages (Priorite: BASSE | Complexite: S | Gain: MOYEN)
> Amelioration visuelle des dialogues. Pas de nouvelle mecanique.
- [ ] Champ `portrait` (string, nullable) sur Pnj : chemin vers l'image
- [ ] Afficher le portrait dans le template dialogue (bulle de dialogue + portrait a gauche)
- [ ] 5-10 portraits pour les PNJ narratifs principaux (guide, forgeron, ancien, boss)
- [ ] Fallback : icone generique par class_type si pas de portrait

### QN-12 — Trame principale — Acte 3 : La Convergence (Priorite: BASSE | Complexite: L | Gain: FORT)
> Donjon final. Prerequis : systeme de donjons instancies, Acte 2 complete.
> A detailler quand les prerequis seront prets.
- [ ] Donjon final accessible apres les 4 fragments
- [ ] 3-5 salles avec puzzles, mobs, boss final
- [ ] Dialogues de conclusion et epilogue
- [ ] Recompenses de fin de trame (titre, equipement legendaire unique)

### QN-RETIRE — Escorte (reporte indefiniment)
> Le type "escorte" necessite un systeme de pathfinding PNJ, de combat en temps reel
> et d'IA de suivi qui n'existent pas. Complexite XL pour un gain faible.
> Reporte apres les systemes multijoueur/groupes si toujours pertinent.

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

### Donjons instancies
- [ ] Donjons par zone (Foret → Racines, Grotte → Mine abandonnee, Montagne → Tour du dragon)
- [ ] Mecaniques de donjon (pieges, puzzles, salles secretes)
- [ ] Loot de donjon (epiques/legendaires exclusifs)
- [ ] Difficulte progressive (Normal → Heroique → Mythique)

### World boss
- [ ] Boss de zone ouvert (horaires fixes, tous les joueurs participent)
- [ ] Loot base sur la contribution
- [ ] Annonce serveur via Mercure

### Reputation et factions
- [ ] Factions (Marchands, Chevaliers, Mages, Ombres)
- [ ] Paliers de reputation (Inconnu → Exalte)
- [ ] Recompenses par palier (recettes, equipements, zones secretes, reductions)

### Evenements temporaires
- [ ] Invasions (vagues de monstres cooperatives)
- [ ] Festivals saisonniers (quetes, cosmétiques, mini-jeux)
- [ ] Tournois PvP

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
