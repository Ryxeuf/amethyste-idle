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
