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

> Decoupage en 10 sous-phases independantes, classees par priorite.
> Complexite : S (1-2h), M (2-4h), L (4-8h) — Priorite : P1 critique, P2 important, P3 bonus
> Prerequis v0.4 indiques quand applicables.

### C-1 — Consommables de base (P1 | S | Gain: fort)
> Aucun prerequis v0.4. Utilisable immediatement en combat via use_spell existant.
- [ ] 5 potions (soin mineur/moyen/majeur, mana, antidote) — fixtures YAML
- [ ] 3 nourritures (pain, viande grillee, ragoût) — buff temporaire HP regen
- [ ] 3 parchemins (teleportation spawn, boost XP 10min, identification)
- [ ] Ajouter les items aux loot tables des monstres existants

### C-2 — Materia complement (P1 | M | Gain: fort)
> Aucun prerequis. Enrichit le combat directement (10 existantes → 18).
- [ ] 8 nouvelles materia (1 par element : basique tier 2)
  - Feu: Combustion, Eau: Brume glaciale, Air: Eclair en chaine
  - Terre: Mur de pierre, Metal: Riposte d'acier, Bete: Morsure sauvage
  - Lumiere: Benediction, Ombre: Drain vital
- [ ] Sorts associes dans SpellFixtures (si non existants)
- [ ] Lien materia → skill unlock dans les arbres de talent existants

### C-3 — Monstres tier 1 (niveaux 1-10) (P1 | M | Gain: fort)
> Aucun prerequis. 20 monstres existent, on en ajoute 8 pour couvrir chaque element.
- [ ] 8 monstres elementaires niveaux 1-10 :
  - Feu: Salamandre (lvl 3), Eau: Ondine (lvl 2), Air: Sylphe (lvl 4)
  - Terre: Golem d'argile (lvl 5), Metal: Automate rouille (lvl 3)
  - Bete: Loup alpha (lvl 4), Lumiere: Feu follet (lvl 2), Ombre: Ombre rampante (lvl 5)
- [ ] Stats, AI patterns, resistances elementaires pour chaque monstre
- [ ] Tables de loot (drops ressources + consommables C-1)
- [ ] Succes bestiaire (3 paliers x 8 monstres = 24 achievements)
- [ ] Placement sur la carte existante (MobFixtures)

### C-4 — Equipement tier 1 Starter (P2 | M | Gain: moyen)
> Aucun prerequis. Remplace/complete les 5 pieces existantes.
- [ ] Set complet 7 pieces (arme, casque, plastron, jambieres, bottes, gants, bouclier) — element None, stats basiques
- [ ] Ajouter aux loot tables des monstres lvl 1-5
- [ ] Verifier l'affichage dans l'inventaire/equipement

### C-5 — Equipement tier 2 Intermediaire (P2 | M | Gain: moyen)
> Prerequis : C-4 (conventions de nommage/structure).
- [ ] Set complet 7 pieces — 4 variantes elementaires (Feu, Eau, Terre, Air)
  - = 28 items au total (7 pieces x 4 elements)
- [ ] Bonus elementaire sur chaque piece (+10% degats element)
- [ ] Ajouter aux loot tables des monstres lvl 5-15

### C-6 — Equipement tier 3 Avance + slots materia (P3 | M | Gain: moyen)
> Prerequis : C-5, systeme materia fonctionnel (GD-8 complet).
- [ ] Set complet 7 pieces — 4 variantes elementaires (Metal, Bete, Lumiere, Ombre)
  - = 28 items au total
- [ ] 1-2 slots materia sur chaque piece avancee
- [ ] Ajouter aux loot tables des monstres lvl 15-25 et boss

### C-7 — Monstres tier 2 (niveaux 10-25) + Boss (P2 | L | Gain: fort)
> Prerequis : C-3 (conventions), idealement C-5 (drops equipement intermediaire).
> Decouper en 2 sous-parties si trop gros.
- [ ] **Sous-partie A** : 4 monstres intermediaires (lvl 10-15)
  - Stats, AI patterns, resistances, loot tables
  - Succes bestiaire (12 achievements)
- [ ] **Sous-partie B** : 4 monstres intermediaires (lvl 15-25)
  - Monstres plus complexes (soigneurs, invocateurs selon combat enrichi)
  - Stats, AI patterns, resistances, loot tables
  - Succes bestiaire (12 achievements)
- [ ] **Sous-partie C** : 2 boss de zone avec mecaniques de phases
  - Boss Foret (element Bete/Terre, 2 phases)
  - Boss Mine (element Metal/Ombre, 3 phases)
  - Loot unique (equipement tier 3, materia rare)
  - Succes boss (2 achievements)

### C-8 — Teleportation entre cartes (P1 | L | Gain: critique)
> Prerequis technique pour toute nouvelle carte. Infrastructure portail existe dans Tiled.
> Dependance : aucune v0.4, mais bloquant pour C-9/C-10.
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

### C-9 — Carte "Village central" hub (P2 | L | Gain: fort)
> Prerequis : C-8 (teleportation). Prerequis v0.4 : boutiques PNJ pour etre utile.
> Peut etre cree "vide" puis peuple incrementalement.
- [ ] Design carte Tiled (~40x40, interieur/exterieur) avec places, batiments
- [ ] Import BDD via app:terrain:import
- [ ] PNJ hub : forgeron, alchimiste, marchand, maitre des quetes, banquier (5-8 PNJ)
- [ ] Dialogues PNJ (JSON conditions)
- [ ] Portail bidirectionnel vers carte existante
- [ ] Aucun monstre (zone safe)

### C-10 — Cartes de contenu : Foret & Mines (P3 | XL → 2 sous-phases | Gain: fort)
> Prerequis : C-8, C-3/C-7 (monstres), idealement v0.4 (recolte, quetes).
> Chaque carte = 1 sous-phase independante.
- [ ] **C-10a — Foret des murmures (lvl 5-15)**
  - Design Tiled (~60x60), arbres, clairiere, riviere
  - Import BDD + portails vers hub
  - Placement 8-10 mobs (monstres C-3 + C-7a)
  - 3-5 PNJ (garde forestier, herboriste, ermite)
  - 5-8 spots de recolte (herbes, bois) — si v0.4 recolte pret
  - 2-3 quetes zone (si v0.4 quetes pret)
- [ ] **C-10b — Mines profondes (lvl 10-25)**
  - Design Tiled (~60x30), tunnels, salles, filons
  - Import BDD + portails vers hub
  - Placement 8-10 mobs (monstres C-7a + C-7b)
  - Boss de mine (C-7c)
  - 3-5 PNJ (mineur, ingenieur, marchand souterrain)
  - 5-8 spots de recolte (minerais) — si v0.4 recolte pret
  - Coffre tresor (si systeme implemente)

### C-11 — Equilibrage & rapport (P2 | M | Gain: moyen)
> Prerequis : au moins C-1 a C-5 implementes pour avoir du contenu a equilibrer.
> La commande CLI est utile des qu'il y a du contenu.
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

### Ordre d'implementation recommande

```
Independant (faisable maintenant) :
  C-1 Consommables ──→ C-3 Monstres T1 (utilise C-1 pour loot)
  C-2 Materia ─────────┘
  C-4 Equip T1 ──→ C-5 Equip T2

Bloquant pour les cartes :
  C-8 Teleportation ──→ C-9 Village hub ──→ C-10a Foret
                                          ──→ C-10b Mines

Apres contenu :
  C-7 Monstres T2+Boss (apres C-3, C-5)
  C-6 Equip T3 (apres C-5, GD-8)
  C-11 Equilibrage (apres C-1 a C-5 minimum)
```

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
