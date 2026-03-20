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

> **Etat des lieux** : le chat en jeu (global, zone, prive) est deja implemente avec Mercure SSE,
> moderation admin, rate limiting et historique. Voir ROADMAP_DONE.md.
> Les sous-phases ci-dessous couvrent les fonctionnalites restantes.

### MS-1 — Commandes chat slash (Priorite: HAUTE | Complexite: S | Gain: FORT)
> Le chat fonctionne mais n'a pas de commandes slash. Amelioration UX immediate.
- [ ] Parser de commandes dans ChatManager : detecter `/whisper <nom> <msg>`, `/zone <msg>`, `/global <msg>`
- [ ] Commande `/emote <action>` : afficher "*Joueur danse*" en italique dans le chat
- [ ] Commande `/who` : lister les joueurs presents sur la meme carte
- [ ] Feedback d'erreur si commande inconnue ou arguments invalides
- [ ] Tests unitaires : parsing commandes, cas d'erreur

### MS-2 — Profil joueur public (Priorite: HAUTE | Complexite: S | Gain: FORT)
> Prerequis social de base : voir les infos d'un autre joueur avant toute interaction avancee.
- [ ] Route `GET /game/player/{id}/profile` : nom, classe, race, succes, domaines principaux
- [ ] Template profil public (stats non-sensibles, achievements notables, titre)
- [ ] Lien cliquable sur les noms de joueurs dans le chat et sur la carte
- [ ] Tests fonctionnels : acces profil, joueur inexistant

### MS-3 — Liste d'amis (Priorite: HAUTE | Complexite: S | Gain: FORT)
> Base pour toute interaction sociale recurrente (invitations groupe, messages rapides).
- [ ] Entite `Friendship` (player, friend, status: pending/accepted/blocked, createdAt)
- [ ] Migration + repository
- [ ] FriendshipManager : sendRequest, accept, decline, block, unfriend
- [ ] Route `GET /game/friends` : liste d'amis avec statut en ligne (derniere activite < 5 min)
- [ ] Route `POST /game/friends/request/{id}` + `POST /game/friends/accept/{id}`
- [ ] Notification Mercure quand un ami se connecte
- [ ] Tests unitaires : ajout, acceptation, blocage, suppression

### MS-4 — Guildes : fondation (Priorite: MOYENNE | Complexite: M | Gain: FORT)
> Premiere brique du systeme de guilde : creation et gestion des membres.
- [ ] Entite `Guild` (name unique, tag 3-5 chars, description, createdAt, leader: Player)
- [ ] Entite `GuildMember` (guild, player, rank: enum master/officer/member/recruit, joinedAt)
- [ ] Migrations + repositories
- [ ] GuildManager : create (cout en gils), invite, accept, leave, kick, promote, demote
- [ ] Route `GET /game/guild` : page de guilde (infos, liste membres avec rangs)
- [ ] Route `POST /game/guild/create` : formulaire creation
- [ ] Route `POST /game/guild/invite/{playerId}` : invitation (officier+ requis)
- [ ] Validation : nom unique, max 1 guilde par joueur, cout creation (ex: 5000 gils)
- [ ] Tests unitaires : creation, invitation, promotion, depart

### MS-5 — Guildes : chat de guilde (Priorite: MOYENNE | Complexite: S | Gain: MOYEN)
> Prerequis: MS-4. Ajoute un canal de communication dedie a la guilde.
- [ ] Nouveau channel `CHANNEL_GUILD` dans ChatMessage
- [ ] Topic Mercure `chat/guild/{guildId}` dans ChatManager
- [ ] Methodes `sendGuildMessage()` et `getGuildHistory()` dans ChatManager
- [ ] Onglet "Guilde" dans le chat (stimulus controller)
- [ ] Verification d'appartenance a la guilde avant envoi
- [ ] Tests unitaires : envoi, historique, joueur hors guilde refuse

### MS-6 — Guildes : coffre partage (Priorite: BASSE | Complexite: M | Gain: MOYEN)
> Prerequis: MS-4. Inventaire collectif avec tracabilite.
- [ ] Entite `GuildVault` (guild, items: Collection, maxSlots: int)
- [ ] Entite `GuildVaultLog` (guild, player, action: deposit/withdraw, item, quantity, createdAt)
- [ ] GuildVaultManager : deposit, withdraw (permissions par rang)
- [ ] Route `GET /game/guild/vault` : affichage coffre + logs recents
- [ ] Route `POST /game/guild/vault/deposit` et `POST /game/guild/vault/withdraw`
- [ ] Permissions : recruit = depot seul, member+ = retrait, officier+ = tout
- [ ] Tests unitaires : depot, retrait, permissions, logs

### MS-7 — Groupes de combat : formation (Priorite: MOYENNE | Complexite: M | Gain: FORT)
> Systeme de groupe pour jouer ensemble. Base pour le combat coop et donjons futurs.
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

### MS-8 — Groupes de combat : combat cooperatif (Priorite: BASSE | Complexite: L | Gain: FORT)
> Prerequis: MS-7 + CE-5 (multi-mobs). Combat a plusieurs joueurs contre des groupes de monstres.
> **Attention** : phase large, a re-decouper au moment de l'implementation.
- [ ] FightController : creer un combat avec plusieurs joueurs du meme groupe
- [ ] Timeline multi-joueurs dans FightTurnResolver
- [ ] Chaque joueur joue son tour independamment (Mercure pour notifier le tour actif)
- [ ] Template combat : afficher tous les joueurs allies avec leurs barres de vie
- [ ] Loot partage : round-robin par defaut (chaque joueur a son ecran de loot)
- [ ] XP partagee (repartition equitable entre participants)
- [ ] Tests : combat 2 joueurs, mort d'un joueur, loot repartition

### MS-9 — Duels entre joueurs (Priorite: BASSE | Complexite: M | Gain: MOYEN)
> PvP consensuel simple : defi 1v1 sans classement.
- [ ] Route `POST /game/duel/challenge/{playerId}` : envoyer un defi
- [ ] Notification Mercure au joueur defie (accepter/refuser)
- [ ] Creer un Fight PvP (joueur vs joueur, pas de mob)
- [ ] Adapter FightTurnResolver pour PvP (2 joueurs alternent)
- [ ] Pas de perte d'items/gils (combat amical)
- [ ] Ecran de resultat (victoire/defaite)
- [ ] Tests : defi, acceptation, combat, resultat

### MS-10 — Arene PvP classee (Priorite: BASSE | Complexite: L | Gain: MOYEN)
> Prerequis: MS-9. Systeme competitif avec matchmaking et classement.
> **Attention** : phase large, a re-decouper au moment de l'implementation.
- [ ] Entite `ArenaRating` (player, rating ELO, wins, losses, season)
- [ ] Entite `ArenaSeason` (number, startDate, endDate, active)
- [ ] File d'attente matchmaking (recherche adversaire +/- 200 ELO)
- [ ] Calcul ELO apres chaque match
- [ ] Route `GET /game/arena` : classement, stats personnelles, bouton recherche
- [ ] Recompenses de fin de saison (titres, items cosmetiques)
- [ ] Tests : matchmaking, calcul ELO, classement

### MS-11 — Classement des guildes (Priorite: BASSE | Complexite: S | Gain: FAIBLE)
> Prerequis: MS-4. Tableau de classement simple par points de guilde.
- [ ] Champ `points` sur Guild (incremente par succes membres, quetes, PvP)
- [ ] Route `GET /game/guilds/ranking` : classement pagine
- [ ] GuildPointsListener : ajoute des points sur MobDeadEvent, QuestCompletedEvent, ArenaDuelEndedEvent
- [ ] Tests : attribution points, classement ordonne

### MS-12 — Quetes de guilde (Priorite: BASSE | Complexite: M | Gain: MOYEN)
> Prerequis: MS-4 + MS-11. Objectifs collectifs hebdomadaires.
- [ ] Entite `GuildQuest` (guild, type: kill/collect/craft, target, progress, goal, reward, expiresAt)
- [ ] GuildQuestManager : generer 3 quetes hebdomadaires, tracker progression, distribuer recompenses
- [ ] Listeners sur MobDeadEvent, SpotHarvestEvent, CraftEvent pour progression collective
- [ ] Route `GET /game/guild/quests` : liste quetes actives avec barres de progression
- [ ] Recompenses : gils + points de guilde pour tous les membres
- [ ] Tests : progression, completion, recompenses

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
