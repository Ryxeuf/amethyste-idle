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

> Decoupee en sous-phases independantes, classees par priorite (P1=haute, P2=moyenne, P3=basse).
> Complexite : S=petit, M=moyen, L=gros. Gain = impact gameplay.

### PB-1 — Respec basique (P1 | S | Gain: fort)
> Prerequis : aucun. Debloque l'experimentation de builds.
- [ ] Service `SkillRespecManager` : retire tous les skills du joueur, rembourse l'XP usee dans chaque `DomainExperience`
- [ ] Cout en gils (formule : 50 * nombre de skills acquis), prix croissant a chaque respec (+25% par respec, stocke dans Player)
- [ ] Champ `respecCount` (int, default 0) sur Player + migration
- [ ] Route POST `/game/skills/respec` + confirmation modale cote template
- [ ] Bouton "Redistribuer" dans la page /game/skills
- [ ] Tests unitaires SkillRespecManager (respec OK, fonds insuffisants, prix croissant)

### PB-2 — Raretes d'equipement (P1 | S | Gain: fort)
> Le champ `rarity` existe deja sur Item (nullable). Il suffit de normaliser les valeurs et l'afficher.
- [ ] Enum PHP `ItemRarity` (common, uncommon, rare, epic, legendary, amethyst) avec couleur CSS associee
- [ ] Migration : mettre a jour les items existants avec rarity = 'common' par defaut
- [ ] Affichage couleur du nom de l'item selon sa rarete (inventaire, loot, boutique, tooltip)
- [ ] Mise a jour des fixtures items existants avec des raretes variees
- [ ] Badge rarete dans la fiche item (inventaire detail)

### PB-3 — Synergies cross-domaine (P2 | M | Gain: fort)
> Les bonus s'accumulent deja cross-domaine. Il faut des bonus explicites pour encourager le multi-domaine.
- [ ] Entite `DomainSynergy` (domainA, domainB, bonusType, bonusValue, description)
- [ ] Migration SQL
- [ ] Service `SynergyCalculator` : detecte les combos actifs selon les domaines ou le joueur a >= X XP
- [ ] Seuil d'activation : 50 XP dans chaque domaine du combo
- [ ] Fixtures ~8 synergies (Feu+Metal=Forge ardente +10% degats physiques, Eau+Lumiere=Purification +15% soin, etc.)
- [ ] Affichage des synergies actives dans /game/skills (section "Synergies")
- [ ] Integration dans CombatSkillResolver : appliquer les bonus de synergie aux stats combat
- [ ] Tests SynergyCalculator

### PB-4 — Presets de build (P2 | M | Gain: moyen)
> Prerequis : PB-1 (respec). Permet de sauvegarder/charger des configurations de skills.
- [ ] Entite `BuildPreset` (player, name, skillSlugs JSON, createdAt)
- [ ] Migration SQL
- [ ] Service `BuildPresetManager` : save(Player, name), load(Player, presetId), delete(presetId)
- [ ] `load()` = respec gratuit + acquisition auto des skills du preset (si XP suffisante)
- [ ] Limite : 3 presets par joueur
- [ ] Route GET/POST `/game/skills/presets` (liste, sauvegarder, charger, supprimer)
- [ ] Template : liste des presets avec boutons Charger/Supprimer + formulaire de sauvegarde
- [ ] Tests BuildPresetManager (save/load OK, limite atteinte, XP insuffisante pour charger)

### PB-5 — Limite de points multi-domaine (P2 | S | Gain: moyen)
> Empeche de tout maxer, force des choix strategiques.
- [ ] Constante ou config : `MAX_TOTAL_SKILL_POINTS` (ex: 500 points cumulés sur tous les domaines)
- [ ] Verification dans `SkillAcquiring::acquire()` : somme des `usedExperience` de tous les domaines < max
- [ ] Affichage du total utilise / max dans /game/skills (barre de progression globale)
- [ ] Tests (acquisition OK sous la limite, refus au-dessus)

### PB-6 — Sets d'equipement (P3 | M | Gain: moyen)
> Bonus progressifs quand on porte plusieurs pieces du meme set.
- [ ] Entite `EquipmentSet` (slug, name, description)
- [ ] Entite `EquipmentSetBonus` (set, requiredPieces, bonusType, bonusValue)
- [ ] Champ `equipmentSet` (ManyToOne, nullable) sur Item + migration
- [ ] Service `EquipmentSetResolver` : detecte les sets actifs depuis l'equipement du joueur
- [ ] Bonus appliques dans le combat (CombatSkillResolver) et affiches dans l'inventaire
- [ ] Fixtures : 2-3 sets de base (Set du Gardien 2/3/4 pieces, Set de l'Ombre 2/3 pieces)
- [ ] Affichage dans inventaire : pieces du set equipees, bonus actifs/inactifs
- [ ] Tests EquipmentSetResolver

### PB-7 — Enchantements temporaires (P3 | S | Gain: faible)
> Alchimiste applique un buff temporaire sur une arme/armure.
- [ ] Entite `Enchantment` (playerItem, type, value, expiresAt)
- [ ] Migration SQL
- [ ] Service `EnchantmentManager` : apply(PlayerItem, enchantType, duration), tick(), remove()
- [ ] Route POST `/game/craft/enchant` (necessite skill alchimiste + ingredients)
- [ ] Expiration automatique (verifiee au debut de chaque combat ou via Scheduler)
- [ ] Fixtures : 3-4 enchantements (Tranchant de feu +5 degats feu 1h, Protection de glace +3 defense 30min, etc.)
- [ ] Tests EnchantmentManager

### ~~Arbres de talent etendus~~ (RETIRE)
> Les 32 domaines ont deja 13-24 skills chacun (838 skills total). Les arbres sont deja etendus avec 3-5 tiers et des ultimates. Cette tache est consideree comme completee (Phase GD-6).

---

## Monde vivant

> Decoupage en 8 sous-phases independantes, classees par priorite.
> L'overlay visuel jour/nuit existe deja (Phase 1.4). Le GameEvent admin existe deja (Phase 2.5).
>
> **Legende** : Taille S (<50 lignes), M (50-150 lignes), L (150-300 lignes)

### MV-1 — Horloge in-game & API temps [Priorite: HAUTE | Taille: S | Gain: MOYEN]
> Fondation obligatoire pour toutes les autres sous-phases monde vivant.
- [ ] `GameTimeService` : convertit le temps reel en temps in-game (1h reelle = 1 journee, ratio configurable)
- [ ] Methodes `getHour()`, `getMinute()`, `getTimeOfDay()` (dawn/day/dusk/night), `getSeason()`
- [ ] Parametre Symfony `game.time_ratio` (configurable admin via `parameters.yaml`)
- [ ] Route API `GET /api/game/time` (heure in-game, periode, saison)
- [ ] Adapter `_computeTimeOfDay()` dans `map_pixi_controller.js` pour utiliser l'API au lieu du temps reel
- [ ] Affichage discret de l'heure in-game dans le HUD carte

### MV-2 — Impact gameplay jour/nuit [Priorite: HAUTE | Taille: M | Gain: HAUT]
> Prerequis : MV-1. Donne une raison concrete au cycle jour/nuit.
- [ ] Champ `nocturnal` (bool) sur l'entite `Mob` — mobs nocturnes n'apparaissent que de nuit
- [ ] Filtre dans `MobSpawnManager` : exclure mobs nocturnes le jour, mobs diurnes la nuit
- [ ] Champ `nightOnly` (bool) sur `HarvestSpot` — plantes de nuit recoltables uniquement la nuit
- [ ] Verification dans `HarvestProcessor`
- [ ] Champ `opensAt`/`closesAt` (int, heure in-game) sur `Shop` ou `Pnj` — horaires d'ouverture
- [ ] Verification dans `ShopManager` + message "La boutique est fermee" dans le template
- [ ] Augmenter l'alpha de l'overlay nuit (0.35 → 0.45) pour renforcer l'effet visuel
- [ ] Migration SQL (3 champs)

### MV-3 — Meteo : backend & diffusion [Priorite: MOYENNE | Taille: S | Gain: MOYEN]
> Systeme autonome, pas de prerequis strict (mais MV-1 recommande pour coherence).
- [ ] Enum PHP `WeatherType` : sunny, cloudy, rain, storm, fog, snow
- [ ] Champ `currentWeather` (string) + `weatherChangedAt` (datetime) sur l'entite `Map`
- [ ] Migration SQL
- [ ] `WeatherService` : `changeWeather(Map)` — tire une meteo aleatoire ponderee par zone/saison
- [ ] Commande Scheduler `app:weather:tick` (toutes les 15-30 min) qui appelle `WeatherService` sur chaque map
- [ ] Ajouter au `DefaultScheduleProvider`
- [ ] Route API `GET /api/map/weather?mapId=X` (ou inclure dans `/api/map/config`)
- [ ] Topic Mercure `map/weather` pour broadcast changement meteo en temps reel

### MV-4 — Meteo : effets visuels PixiJS [Priorite: MOYENNE | Taille: M | Gain: HAUT]
> Prerequis : MV-3. Fort impact immersion visuelle.
- [ ] Ecouter le topic Mercure `map/weather` dans `map_pixi_controller.js`
- [ ] Container de particules dedie (zIndex au-dessus des entites, sous le HUD)
- [ ] Effet pluie : particules tombantes bleues semi-transparentes
- [ ] Effet neige : particules blanches lentes avec oscillation laterale
- [ ] Effet orage : flash blanc intermittent (alpha spike sur l'overlay) + particules pluie
- [ ] Effet brouillard : overlay blanc semi-transparent avec alpha pulse doux
- [ ] Effet nuageux : leger assombrissement (overlay gris alpha 0.08)
- [ ] Transition douce entre meteos (fade 2 secondes)

### MV-5 — Meteo : impact gameplay [Priorite: BASSE | Taille: S | Gain: MOYEN]
> Prerequis : MV-3. Ajoute de la profondeur strategique.
- [ ] Table de bonus/malus par meteo × element dans `WeatherService` (ex: pluie → eau +20%, feu -20%)
- [ ] Appliquer le modificateur dans `DamageCalculator` via `WeatherService::getElementalModifier(map, element)`
- [ ] Monstres speciaux par meteo : champ `spawnWeather` (nullable) sur `Mob`
- [ ] Filtre dans `MobSpawnManager` : certains mobs n'apparaissent que sous orage/brouillard/neige
- [ ] Migration SQL (1 champ)

### MV-6 — PNJ routines [Priorite: BASSE | Taille: L | Gain: MOYEN]
> Prerequis : MV-1. Feature ambitieuse, peut etre reportee.
- [ ] Entite `PnjSchedule` (pnj, hour, coordinates, map) — table horaire du PNJ
- [ ] Migration SQL
- [ ] `PnjRoutineService` : deplace les PNJ selon l'heure in-game courante
- [ ] Commande Scheduler `app:pnj:routine` (toutes les 5 min)
- [ ] Topic Mercure `map/pnj-move` pour animer le deplacement cote client
- [ ] Animation de marche du PNJ dans le renderer PixiJS (reutiliser SpriteAnimator)
- [ ] Fixtures : 3-5 PNJ avec routines simples (maison ↔ travail ↔ taverne)
- [ ] Gerer le cas ou un joueur parle a un PNJ qui se deplace

### MV-7 — Evenements aleatoires [Priorite: BASSE | Taille: M | Gain: MOYEN]
> Reutilise l'entite `GameEvent` existante. Ajoute du dynamisme au monde.
- [ ] `RandomEventGenerator` : selectionne un type d'evenement aleatoire selon des poids configurables
- [ ] Types : `invasion` (vague de mobs), `merchant` (marchand itinerant temporaire), `aurora` (buff XP zone)
- [ ] Commande Scheduler `app:events:random` (toutes les 30-60 min, probabilite 30%)
- [ ] Creer automatiquement un `GameEvent` avec duree limitee (10-30 min)
- [ ] Pour `invasion` : spawner des mobs temporaires sur la zone ciblee
- [ ] Pour `merchant` : creer un PNJ temporaire avec boutique speciale
- [ ] Pour `aurora` : activer un buff XP via Mercure broadcast
- [ ] Notification Mercure `game/event` pour alerter les joueurs connectes
- [ ] Bandeau visuel dans le HUD quand un evenement est actif

### MV-8 — Saisonnalite & festivals [Priorite: TRES BASSE | Taille: S | Gain: FAIBLE]
> Prerequis : MV-1 + MV-7. Contenu evenementiel, a planifier apres le reste.
- [ ] Detection de la saison reelle (printemps/ete/automne/hiver) dans `GameTimeService`
- [ ] Poids meteo ajustes par saison (plus de neige en hiver, plus d'orages en ete)
- [ ] Entite `Festival` (slug, name, season, startDay, endDay, quests, rewards)
- [ ] 4 festivals de base (1 par saison) — contenu a definir plus tard
- [ ] Decorations saisonnieres sur la carte (sprites overlays)

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
