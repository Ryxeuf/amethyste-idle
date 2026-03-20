# Roadmap a venir — Amethyste-Idle

> Toutes les taches restantes a implementer.
> Derniere mise a jour : 2026-03-20

---

## Reliquats des phases completees

### Phase 1 — Fondations (reste)
- [ ] Commande de preview : `app:terrain:preview --map=X` genere une image PNG
- [ ] Templates de cartes Tiled pre-configures (template_outdoor.tmx, template_indoor.tmx, template_dungeon.tmx)

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

## Gameplay Core (v0.4)

### Boutiques PNJ & economie de base
- [ ] Entite Shop (slug, name, pnj ManyToOne)
- [ ] Entite ShopItem (shop, genericItem, buyPrice, sellPrice, stock, restockInterval)
- [ ] Champ `gils` sur Player (int, default 0)
- [ ] Migration SQL
- [ ] ShopManager : buy(Player, ShopItem, qty), sell(Player, PlayerItem, qty)
- [ ] ShopController : routes /game/shop/{pnjId}, /game/shop/buy, /game/shop/sell
- [ ] Template boutique (grille items, prix, stock)
- [ ] Bouton "Boutique" dans les dialogues PNJ si le PNJ a un shop
- [ ] Fixtures boutiques : armurier, alchimiste, marchand general
- [ ] Tests ShopManager (achat OK, fonds insuffisants, stock epuise, item soulbound invendable)

### Systeme de recolte
- [ ] Entite HarvestSpot (map, coordinates, resource, harvestDomain, requiredSkillSlug, respawnSeconds, harvestedAt, harvestedByPlayer)
- [ ] Repository HarvestSpotRepository
- [ ] Migration SQL
- [ ] Afficher les spots de recolte dans /api/map/entities
- [ ] Afficher les spots sur la carte PixiJS
- [ ] HarvestProcessor : harvest(Player, HarvestSpot) — verification skill, cooldown, XP, drop, Mercure
- [ ] HarvestController : route POST /api/map/harvest/{spotId}
- [ ] HarvestCompletedEvent + HarvestListener (XP + achievements)
- [ ] Topic Mercure map/spot pour broadcast recolte/respawn
- [ ] Items de ressource dans les fixtures (minerai de fer, herbe de soin, poisson, cuir brut)
- [ ] Fixtures ~20 spots sur la carte existante
- [ ] Tests HarvestProcessor (recolte OK, skill manquant, cooldown actif)

### Systeme d'artisanat
- [ ] Entite CraftRecipe (slug, name, craftDomain, resultItem, resultQuantity, requiredSkillSlug, craftTimeSeconds, xpReward)
- [ ] Entite CraftIngredient (recipe, genericItem, quantity)
- [ ] Migration SQL
- [ ] CraftProcessor : craft(Player, CraftRecipe) — verification skill + ingredients, consomme materiaux, cree item, XP
- [ ] CraftController : routes /game/craft (liste), /game/craft/{recipeSlug} (POST)
- [ ] Template craft (onglets par domaine, ingredients requis, progression)
- [ ] CraftCompletedEvent
- [ ] Fixtures ~15-20 recettes de base (forgeron, tanneur, alchimiste, joaillier)
- [ ] Tests CraftProcessor (craft OK, ingredients manquants, skill manquant)

### Systeme de quetes
- [ ] Entite Quest (slug, title, description, questGiver, questType, minDomainLevel, prerequisites, rewards JSON, repeatable)
- [ ] Entite QuestObjective (quest, type, targetSlug, targetQuantity, description)
- [ ] Entite PlayerQuest (player, quest, status, acceptedAt, completedAt)
- [ ] Entite PlayerQuestProgress (playerQuest, objective, currentQuantity)
- [ ] Migrations SQL
- [ ] QuestManager : accept(), checkProgress(), complete(), getAvailableQuests(Player)
- [ ] QuestProgressTracker : ecoute MobDeadEvent, HarvestCompletedEvent, CraftCompletedEvent
- [ ] QuestController : routes /game/quests, /game/quest/{id}/accept, /game/quest/{id}/complete
- [ ] Templates journal de quetes
- [ ] Indicateur quete (! au-dessus du PNJ)
- [ ] Fixtures ~10 quetes (tutoriel, combat, recolte, craft, exploration, chaine de 3)
- [ ] Tests QuestManager + QuestProgressTracker

---

## Combat enrichi

### Synergies elementaires et Materia
- [ ] Combos elementaires (Eau+Feu=Vapeur, Terre+Air=Tempete de sable, etc.)
- [ ] Materia Fusion : combiner 2 materias au repos
- [ ] Materia XP : les materias gagnent de l'XP en combat (3 niveaux)
- [ ] Slots de Materia lies : amplification des materias adjacentes

### Statuts alteres
- [ ] Poison (X% PV/tour, 3 tours)
- [ ] Paralysie (50% chance ne pas agir, 2 tours)
- [ ] Brulure (degats reduits 25% + degats feu/tour)
- [ ] Gel (vitesse reduite 50%, 2 tours)
- [ ] Silence (impossible d'utiliser des sorts, 3 tours)
- [ ] Regeneration (recupere X PV/tour, 3 tours)
- [ ] Bouclier (absorbe X prochains points de degats)
- [ ] Berserk (+50% degats, -30% defense, ne peut pas fuir)
- [ ] Icones visuelles sur la timeline de combat
- [ ] Resistances elementaires par monstre

### IA monstres amelioree
- [ ] Patterns d'attaque : sequences d'actions par monstre
- [ ] Monstres soigneurs (soignent leurs allies)
- [ ] Monstres invocateurs (appellent des renforts)
- [ ] Alertes de danger (indicateur visuel attaque puissante)

### Boss et combats speciaux
- [ ] Mecaniques de boss : phases multiples, attaques speciales
- [ ] Recompenses uniques par boss (equipement legendaire)
- [ ] Cooldown de boss (1h reel)
- [ ] Indicateur de difficulte (etoiles)

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
