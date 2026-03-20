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

> Decoupee en sous-phases independantes, classees par priorite.
> Complexite : S (1-2h), M (2-4h), L (4-8h) — Priorite : P1=haute, P2=moyenne, P3=basse
>
> **Deja fait (a ne pas reimplementer) :**
> - ~~Cycle jour/nuit PixiJS~~ → Phase 1.7 (4 periodes, overlay couleur)
> - ~~Camera shake~~ → Phase 1.7 (shakeCamera avec decay)
> - ~~Systeme de particules de base~~ → Phase 1.7 (spawnParticles generique)
> - ~~Cache Doctrine~~ → Deja configure en prod (query_cache + result_cache)
> - ~~Optimisation tactile mobile~~ → Phase 1.5 (joystick, responsive)
> - ~~Indicateurs PNJ quetes~~ → Doublon v0.4-E (traite la-bas)

### P6-1 — Rendu combat log en frontend (P1 | S | Gain: fort)
> Le CombatLogger ecrit deja tout en BDD (FightLog). Il manque juste l'affichage dans le template combat.
> Quick win : le backend est pret a 95%.
- [ ] Template partiel `_combat_log.html.twig` : liste scrollable des messages du tour courant
- [ ] Couleurs par type d'evenement (degats=rouge, soin=vert, critique=orange, elementaire=couleur element)
- [ ] Icones par type (epee=attaque, etoile=critique, bouclier=defense, crane=mort)
- [ ] Auto-scroll vers le dernier message a chaque tour

### P6-2 — Notifications toast in-game (P1 | M | Gain: fort)
> Aucun systeme de notification generaliste. Seul FightNotification existe (combat only).
> Impact fort : feedback immediat pour toutes les actions du joueur.
- [ ] Composant Stimulus `toast_controller.js` : affiche des toasts empiles en bas-droite (auto-dismiss 4s)
- [ ] 4 types visuels : succes (vert), info (bleu), alerte (orange), erreur (rouge)
- [ ] Integration dans les evenements existants :
  - Drop d'item apres combat (ecran loot)
  - XP gagnee / domaine level-up
  - Quete completee / objectif progresse
  - Succes debloque
- [ ] Helper Twig `toast()` ou data-attribute Stimulus pour declencher depuis le serveur

### P6-3 — Tests fonctionnels controleurs Game (P1 | M | Gain: fort)
> 0 test fonctionnel pour shop, inventory, skills, bestiary, achievements. Fragilise la base.
> Prerequis : aucun. Stabilise le code existant.
- [ ] Test ShopController : achat OK, fonds insuffisants, item inexistant
- [ ] Test InventoryController : equiper, desequiper, utiliser consommable
- [ ] Test SkillController : acquerir skill, XP insuffisante, prerequis manquant
- [ ] Test BestiaryController : acces page, filtres, affichage paliers
- [ ] Test AchievementController : acces page, succes debloques vs verrouilles

### P6-4 — Tests E2E Panther : parcours joueur (P2 | M | Gain: moyen)
> Prerequis : P6-3 (les fonctionnels valident les controleurs individuels d'abord).
> Valide des parcours complets multi-pages.
- [ ] Parcours combat : carte → engagement mob → combat → victoire → loot → retour carte
- [ ] Parcours quete : PNJ dialogue → accepter quete → tuer mob → rendre quete → recompense
- [ ] Parcours craft : inventaire → atelier → crafter → verifier item cree

### P6-5 — Tests integration evenements/listeners (P2 | S | Gain: moyen)
> 21 evenements domaine existent, mais 0 test d'integration sur les listeners.
> Valide que les side-effects (XP, achievements, quetes) se declenchent correctement.
- [ ] Test MobKilledEvent → BestiaryListener + AchievementListener + QuestProgressListener
- [ ] Test SpotHarvestEvent → XP progression + (futur) QuestCollectListener
- [ ] Test PlayerLevelUpEvent → AchievementListener
- [ ] Objectif : couverture >= 60% sur src/GameEngine/

### P6-6 — Minimap (P2 | M | Gain: fort)
> L'API /api/map/entities retourne deja toutes les positions. Il faut un rendu en overlay PixiJS.
- [ ] Container PixiJS fixe en coin haut-droit (150x150px), semi-transparent
- [ ] Points colores : blanc=joueur, rouge=mobs, bleu=PNJ, jaune=spots recolte, violet=portails
- [ ] Viewport rectangle (zone visible) affiche en surbrillance
- [ ] Mise a jour a chaque mouvement joueur
- [ ] Toggle affichage (touche M ou bouton)

### P6-7 — Barre d'action rapide (P2 | S | Gain: moyen)
> Raccourcis clavier/boutons pour utiliser consommables et sorts frequents hors combat (carte).
- [ ] Barre fixe en bas de l'ecran carte (4-6 slots)
- [ ] Drag & drop items consommables depuis l'inventaire vers les slots
- [ ] Raccourcis clavier 1-6 pour activer un slot
- [ ] Persistance des slots en localStorage

### P6-8 — Particules combat et recolte (P2 | S | Gain: moyen)
> Le systeme spawnParticles() existe deja. Il faut l'appeler aux bons moments.
> Quick win : juste brancher les appels sur les evenements existants.
- [ ] Particules sur sort lance en combat (couleur = element du sort)
- [ ] Particules sur coup critique (explosion doree)
- [ ] Particules sur recolte reussie (etincelles vertes)
- [ ] Particules sur level-up domaine (pluie d'etoiles)

### P6-9 — Flash elementaire et animations combat (P2 | S | Gain: moyen)
> Camera shake existe. Ajouter des effets visuels complementaires au combat.
- [ ] Flash colore plein ecran sur degats elementaires (rouge=feu, bleu=eau, etc.)
- [ ] Shake camera sur coups critiques (branche sur evenement critique existant)
- [ ] Animation de tremblement sur le sprite cible quand il recoit des degats
- [ ] Fondu progressif du sprite a la mort d'un mob

### P6-10 — Optimisation queries N+1 (P1 | S | Gain: fort)
> Impact direct sur les temps de chargement. Pas de nouveau code metier, juste du tuning.
- [ ] Auditer les requetes avec Symfony Profiler (toolbar) sur les pages critiques (carte, combat, inventaire)
- [ ] Ajouter `fetch: EAGER` ou `->addSelect()->leftJoin()` sur les relations N+1 detectees
- [ ] Index composites : `(player_id, map_id)` sur positions, `(fight_id, turn)` sur FightLog
- [ ] Mesurer avant/apres (nombre de queries par page)

### P6-11 — Rate limiting API (P1 | S | Gain: fort)
> Aucun rate limiting. Risque d'abus sur les endpoints critiques.
> Symfony RateLimiter est inclus dans le framework, simple a configurer.
- [ ] Configurer `framework.rate_limiter` dans `config/packages/rate_limiter.yaml`
- [ ] Limiter `/api/map/move` : 10 req/s par joueur (anti-speedhack)
- [ ] Limiter `/game/fight/*` : 5 req/s par joueur
- [ ] Limiter `/game/shop/buy` et `/game/craft` : 3 req/s par joueur
- [ ] Reponse 429 avec message explicite

### P6-12 — Index DB composites (P3 | S | Gain: moyen)
> Ameliore les performances sur les tables critiques sans changement de code.
- [ ] Migration : index composite `(player_id, map_id)` sur table player/position
- [ ] Migration : index composite `(fight_id, turn)` sur FightLog
- [ ] Migration : index sur `(player_id, quest_id)` sur PlayerQuest
- [ ] Migration : index sur `(monster_slug, player_id)` sur BestiaryEntry

### P6-13 — Transitions de zone (P3 | S | Gain: faible)
> Fondu au noir lors du changement de carte/teleportation.
- [ ] Overlay noir plein ecran avec alpha 0→1→0 (PIXI.Graphics + GSAP ou requestAnimationFrame)
- [ ] Declenchement sur teleportation portail
- [ ] Declenchement sur changement de map

### P6-14 — Sons basiques (P3 | L | Gain: moyen)
> Optionnel. Ajoute de l'immersion mais necessite des assets sonores.
> Dependance : trouver/creer des sons libres de droits.
- [ ] Integrer Howler.js via importmap
- [ ] Sons d'interface : clic bouton, ouverture menu, notification
- [ ] Sons de combat : attaque, sort, critique, mort
- [ ] Sons d'ambiance : loop par biome (foret, grotte, village)
- [ ] Bouton mute/volume dans les parametres joueur
- [ ] Persistance preference son en localStorage

### P6-15 — Monitoring basique (P3 | M | Gain: faible)
> Utile en production pour detecter les problemes, mais pas bloquant pour le gameplay.
- [ ] Endpoint `/health` (status BDD, Mercure, cache)
- [ ] Metriques Prometheus via `prometheus-metrics-bundle` (requetes/s, temps reponse, erreurs)
- [ ] Dashboard Grafana minimal (4-5 panels : requetes, latence, erreurs, joueurs connectes)
- [ ] Alertes basiques (latence > 2s, erreurs > 5/min)

### Ordre d'implementation recommande

```
Quick wins immédiats (backend pret, juste brancher) :
  P6-1 Combat log frontend ──→ P6-8 Particules combat
  P6-10 Optimisation N+1
  P6-11 Rate limiting

Stabilisation :
  P6-3 Tests fonctionnels ──→ P6-4 Tests E2E
  P6-5 Tests integration    ──→ (objectif 60% couverture)

UX/UI (independants entre eux) :
  P6-2 Toast notifications
  P6-6 Minimap
  P6-7 Barre action rapide

Effets visuels :
  P6-8 Particules ──→ P6-9 Flash elementaire
  P6-13 Transitions zone

Infra (basse priorite) :
  P6-12 Index DB
  P6-14 Sons
  P6-15 Monitoring
```

---

## Pipeline Tiled (transverse)

> Infrastructure de cartes Tiled : nettoyage, corrections, et fonctionnalites.
> Ordre recommande : T5 → T4 → T2a → T2b → T3a → T3b → T1a → T1b

### T5 — De-hardcoder les map IDs (P1 | S | Gain: fort)
> 3 endroits hardcodent `map_id=10`. Bloquant pour le multi-cartes (C-8/C-9).
> Le contexte Player/Map est deja disponible partout — correction triviale.
- [ ] `MapApiController::move()` ligne 231 : remplacer `loadMap(10)` par `$player->getMap()->getId()`
- [ ] `Twig/Components/Map::move()` ligne 101 : remplacer `loadMap(10)` par `$this->player->getMap()->getId()`
- [ ] `TerrainImportCommand::syncEntitiesFromObjects()` ligne 530 : ajouter option `--map-id` ou deduire depuis le nom du fichier TMX
- [ ] Tester le deplacement sur la carte existante apres correction

### T4 — Supprimer la commande CSS morte (P1 | S | Gain: moyen)
> `TmxCssGeneratorCommand` (308 lignes) + `world-1.css` (335 Ko) sont obsoletes.
> Le rendu passe par PixiJS canvas, pas par CSS. Deja marque "obsolete" dans les docs.
- [ ] Supprimer `src/Command/TmxCssGeneratorCommand.php`
- [ ] Supprimer le dossier `assets/styles/map/` (world-1.css)
- [ ] Retirer les imports CSS dans `assets/app.js`
- [ ] Nettoyer les references dans CLAUDE.md, DOCUMENTATION.md, AGENTS.md, `.claude/commands/import-terrain.md`

### T2a — Extraction services depuis TerrainImportCommand (P2 | M | Gain: moyen)
> La commande actuelle fait 663 lignes monolithiques (parsing TMX, sync objets, validation, export).
> Extraire en services reutilisables avant d'ajouter des fonctionnalites.
- [ ] Extraire `TmxParser` : parsing TMX/TSX → structure de donnees (layers, tilesets, objets)
- [ ] Extraire `EntitySynchronizer` : creation/mise a jour des entites (portails, mobs, spots, coffres) depuis les objets TMX
- [ ] Refactorer `TerrainImportCommand` pour deleguer a ces services
- [ ] Verifier que `app:terrain:import` fonctionne identiquement apres refactoring

### T2b — Commande unifiee `app:terrain:sync` (P2 | M | Gain: moyen)
> Prerequis : T2a. Commande unique qui orchestre tout le pipeline d'import.
- [ ] Creer `TerrainSyncCommand` : import TMX + upsert Area + sync entites + rebuild Dijkstra + rapport diff
- [ ] Integrer l'appel Dijkstra post-import (regeneration du cache collisions)
- [ ] Ajouter un rapport diff (entites creees/modifiees/supprimees)
- [ ] Mettre a jour l'agent `.claude/commands/import-terrain.md`

### T3a — Parsing zones/biomes depuis Tiled (P2 | M | Gain: fort)
> Prerequis : T2a (TmxParser extrait). Les objets rectangulaires Tiled de type "zone" definissent les biomes.
> L'entite `Area` existe deja mais n'est pas peuplee a l'import.
- [ ] Ajouter les champs biome, weather, music, lightLevel sur l'entite `Area` + migration
- [ ] Parser les objets de type `zone`/`biome` dans TmxParser (rectangles avec proprietes)
- [ ] Creer `AreaSynchronizer` : upsert des Area depuis les zones Tiled
- [ ] Exposer les zones dans `/api/map/config` (coordonnees, biome, meteo, musique)

### T3b — Effets d'ambiance par zone en frontend (P3 | M | Gain: fort)
> Prerequis : T3a. Effets visuels dynamiques quand le joueur entre dans une zone.
- [ ] Charger les zones depuis l'API au chargement de la carte
- [ ] Detecter la zone courante du joueur (point-in-rect)
- [ ] Appliquer les effets par zone : teinte/overlay, particules (pluie, brume, poussiere)
- [ ] Transition fluide entre zones (fondu des effets)

### T1a — Parsing et API des animations de tiles (P3 | S | Gain: moyen)
> Les fichiers TSX contiennent deja des `<tile><animation><frame>`. Le backend les ignore.
- [ ] Etendre le parsing TSX dans TmxParser : extraire les frames d'animation (tileId, duration)
- [ ] Exposer les animations dans `/api/map/config` (tableau par GID : frames + durations)

### T1b — Rendu des tiles animees en PixiJS (P3 | M | Gain: fort)
> Prerequis : T1a. Remplacer PIXI.Sprite par PIXI.AnimatedSprite pour les tiles avec animation.
- [ ] Dans `_renderCell()` : detecter les tiles animees depuis les donnees API
- [ ] Creer des `PIXI.AnimatedSprite` avec les frames/durations pour ces tiles
- [ ] Gerer le cycle d'animation (elapsed time, frame index) dans le ticker
- [ ] Tester visuellement (eau animee, torches, etc.)

### Ordre d'implementation recommande

```
Quick wins (faisable maintenant, aucun prerequis) :
  T5 De-hardcoder map IDs ──→ debloque le multi-cartes
  T4 Supprimer CSS mort ────→ nettoyage dette technique

Refactoring (apres quick wins) :
  T2a Extraction services ──→ T2b Commande terrain:sync

Fonctionnalites (apres refactoring) :
  T3a Parsing zones ──→ T3b Effets ambiance frontend
  T1a Parsing animations ──→ T1b Rendu tiles animees
```
