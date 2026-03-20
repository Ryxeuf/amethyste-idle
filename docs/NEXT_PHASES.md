# Prochaines phases — Amethyste-Idle

> Plan d'implémentation post-Phase 13. Approuvé le 2026-03-20.

## Vue d'ensemble

Les phases 1-13 ont posé les **fondations** du jeu : système élémentaire, 32 domaines, 400+ compétences, combat tour par tour, materia, bestiaire et succès. Le jeu possède une architecture solide mais manque de **boucles de gameplay complètes** et de **contenu**.

### Objectifs stratégiques

1. **Gameplay complet** — Rendre chaque système jouable de bout en bout (récolte, craft, quêtes, économie)
2. **Contenu & monde** — Enrichir le monde avec des zones, monstres, items et équipements variés
3. **Qualité & polish** — Tests robustes, UX améliorée, effets visuels, cycle jour/nuit

### Releases planifiées

| Release | Nom | Phases | Objectif |
|---------|-----|--------|----------|
| **v0.4** | Gameplay Core | 14-18 | Toutes les boucles gameplay fonctionnelles |
| **v0.5** | Monde vivant | 19-22 | Contenu, zones, monstres, équilibre |
| **v0.6** | Polish & Immersion | 23-26 | UX, visuels, tests, performance |

---

## Release v0.4 — Gameplay Core

### Phase 14 — Finalisation materia & vérification skill [S] → PR #14

**Problème** : La Phase 8 est partiellement terminée. Le `CombatCapacityResolver` existe mais ne vérifie pas que le joueur possède le skill `actions.materia.unlock` avant d'autoriser un sort materia.

**Dépendances** : Aucune (finition de Phase 8)

#### Fichiers
- **Modifier** `src/GameEngine/Fight/CombatCapacityResolver.php` — Ajouter vérification `actions.materia.unlock` via `CombatSkillResolver`
- **Modifier** `src/GameEngine/Fight/CombatSkillResolver.php` — Ajouter `getUnlockedMateriaSpellSlugs(Player): array`
- **Modifier** `src/Helper/PlayerItemHelper.php` — `canEquipMateria()` : vérifier compétence requise avant sockettage
- **Modifier** `src/Controller/Game/Fight/FightSpellController.php` — Valider côté contrôleur aussi
- **Modifier** templates combat — Griser les sorts sans skill requis

#### Tests
- Test unitaire : `CombatCapacityResolverTest` — materia SANS skill → sort indisponible
- Test unitaire : `CombatCapacityResolverTest` — materia AVEC skill → sort disponible
- Test unitaire : `CombatSkillResolverTest::getUnlockedMateriaSpellSlugs()`
- Test unitaire : `PlayerItemHelperTest::canEquipMateria()` — refuse sans skill

---

### Phase 15 — Boutiques PNJ & économie de base [M] → PR #15

**Problème** : Aucun moyen d'acheter/vendre des items. Les joueurs n'ont pas de gold sink.

**Dépendances** : Aucune

#### Sous-phases

**15.A — Infrastructure boutiques [S]**
- **Créer** `src/Entity/Game/Shop.php` — slug, name, pnj (ManyToOne Pnj)
- **Créer** `src/Entity/Game/ShopItem.php` — shop (ManyToOne), genericItem (ManyToOne), buyPrice (int), sellPrice (int, nullable), stock (int, -1 = illimité), restockInterval (int, nullable, secondes)
- **Modifier** `src/Entity/App/Player.php` — Ajouter `gils` (int, default 0)
- **Migration** : CREATE TABLE + ALTER TABLE player

**15.B — Logique achat/vente [S]**
- **Créer** `src/GameEngine/Economy/ShopManager.php` — `buy(Player, ShopItem, qty)`, `sell(Player, PlayerItem, qty)`, vérification fonds + stock
- **Créer** `src/Controller/Game/ShopController.php` — Routes `/game/shop/{pnjId}` (GET liste), `/game/shop/buy` (POST), `/game/shop/sell` (POST)
- **Créer** `templates/game/shop/index.html.twig` — Interface boutique (grille items, prix, stock)
- **Modifier** `src/Controller/Game/Map/PnjDialogController.php` — Ajouter bouton "Boutique" si PNJ a un shop

**15.C — Fixtures boutiques [S]**
- **Créer** `src/DataFixtures/Game/ShopFixtures.php` — 2-3 boutiques (armurier, alchimiste, marchand général)
- Items de base : potions de soin, antidotes, équipement starter, materia de base
- **Modifier** `src/DataFixtures/Game/ItemFixtures.php` — Ajouter items manquants pour les boutiques

#### Tests
- Test `ShopManager::buy()` — succès, fonds insuffisants, stock épuisé
- Test `ShopManager::sell()` — succès, item soulbound invendable
- Test fonctionnel : route boutique accessible

---

### Phase 16 — Système de récolte [L] → PR #16

**Problème** : Les arbres de talent récolte (Mineur, Herboriste, Pêcheur, Dépeceur) existent mais aucune mécanique de gameplay.

**Dépendances** : Phase 15 (pour la vente des ressources)

#### Sous-phases

**16.A — Infrastructure spots de récolte [M]**
- **Créer** `src/Entity/App/HarvestSpot.php` — map, coordinates, resource (ManyToOne Item), harvestDomain (ManyToOne Domain), requiredSkillSlug (string, nullable), respawnSeconds (int), harvestedAt (datetime, nullable), harvestedByPlayer (ManyToOne Player, nullable)
- **Créer** `src/Repository/App/HarvestSpotRepository.php`
- **Migration** : CREATE TABLE
- **Modifier** `src/Controller/Api/Map/MapEntitiesController.php` — Inclure les spots de récolte dans `/api/map/entities`
- **Modifier** `assets/controllers/map_pixi_controller.js` — Afficher les spots sur la carte (sprite `materias.png` pour filons, sprites custom pour herbes/poissons/bêtes)

**16.B — Mécanique de récolte [M]**
- **Créer** `src/GameEngine/Harvest/HarvestProcessor.php` — `harvest(Player, HarvestSpot)` : vérification skill, cooldown, attribution XP domaine, drop item, publication Mercure
- **Créer** `src/Controller/Api/Map/HarvestController.php` — Route POST `/api/map/harvest/{spotId}`
- **Créer** `src/Event/HarvestCompletedEvent.php`
- **Créer** `src/EventListener/HarvestListener.php` — Écoute `HarvestCompletedEvent` pour XP + achievements
- **Modifier** `src/GameEngine/Realtime/Map/MapPublisher.php` — Topic `map/spot` pour broadcast récolte/respawn

**16.C — Fixtures spots & ressources [S]**
- **Modifier** `src/DataFixtures/Game/ItemFixtures.php` — Ajouter items de ressource (minerai de fer, herbe de soin, poisson, cuir brut, etc.)
- **Créer** `src/DataFixtures/App/HarvestSpotFixtures.php` — ~20 spots sur la carte existante (filons, herbes, coins de pêche, gibier)

#### Tests
- Test `HarvestProcessor` : récolte OK, skill manquant, cooldown actif
- Test Mercure : spot broadcast sur `map/spot`
- Test : XP domaine octroyée correctement

---

### Phase 17 — Système d'artisanat [L] → PR #17

**Problème** : Les arbres de talent craft (Forgeron, Tanneur, Alchimiste, Joaillier) existent mais aucune mécanique de gameplay.

**Dépendances** : Phase 16 (ressources récoltables nécessaires pour crafter)

#### Sous-phases

**17.A — Infrastructure recettes [M]**
- **Créer** `src/Entity/Game/CraftRecipe.php` — slug, name, craftDomain (ManyToOne Domain), resultItem (ManyToOne Item), resultQuantity (int), requiredSkillSlug (string, nullable), craftTimeSeconds (int), xpReward (int)
- **Créer** `src/Entity/Game/CraftIngredient.php` — recipe (ManyToOne CraftRecipe), genericItem (ManyToOne Item), quantity (int)
- **Migration** : CREATE TABLE craft_recipes + craft_ingredients

**17.B — Mécanique de craft [M]**
- **Créer** `src/GameEngine/Craft/CraftProcessor.php` — `craft(Player, CraftRecipe)` : vérification skill + ingrédients, consomme matériaux, crée item, attribue XP domaine
- **Créer** `src/Controller/Game/CraftController.php` — Routes `/game/craft` (GET liste recettes), `/game/craft/{recipeSlug}` (POST crafter)
- **Créer** `templates/game/craft/index.html.twig` — Interface craft (onglets par domaine, ingrédients requis, progression)
- **Créer** `src/Event/CraftCompletedEvent.php`

**17.C — Fixtures recettes [S]**
- **Créer** `src/DataFixtures/Game/CraftRecipeFixtures.php` — ~15-20 recettes de base :
  - Forgeron : épée en fer, bouclier en fer, casque en fer
  - Tanneur : armure en cuir, gants en cuir, bottes en cuir
  - Alchimiste : potion de soin, potion de mana, antidote
  - Joaillier : anneau simple, amulette simple, materia basique

#### Tests
- Test `CraftProcessor` : craft OK, ingrédients manquants, skill manquant
- Test : XP domaine craft octroyée
- Test fonctionnel : route craft accessible

---

### Phase 18 — Système de quêtes [L] → PR #18

**Problème** : Les PNJ ont des dialogues mais pas de quêtes trackées. Le `QuestCompletedEvent` existe déjà mais sans système de quêtes complet.

**Dépendances** : Phase 15 (récompenses en gils), Phase 16 (quêtes de récolte)

#### Sous-phases

**18.A — Infrastructure quêtes [M]**
- **Créer** `src/Entity/Game/Quest.php` — slug, title, description, questGiver (ManyToOne Pnj), questType (string: main/side/daily), minDomainLevel (JSON, nullable), prerequisites (ManyToMany Quest, nullable), rewards JSON (gils, xp, items), repeatable (bool)
- **Créer** `src/Entity/Game/QuestObjective.php` — quest (ManyToOne), type (string: kill_monster/collect_item/harvest_resource/talk_to_pnj/craft_item), targetSlug (string), targetQuantity (int), description (string)
- **Créer** `src/Entity/App/PlayerQuest.php` — player, quest, status (accepted/in_progress/completed/failed), acceptedAt, completedAt
- **Créer** `src/Entity/App/PlayerQuestProgress.php` — playerQuest, objective, currentQuantity
- **Migration** : CREATE TABLE × 4

**18.B — Logique quêtes [M]**
- **Créer** `src/GameEngine/Quest/QuestManager.php` — `accept()`, `checkProgress()`, `complete()`, `getAvailableQuests(Player)`
- **Créer** `src/GameEngine/Quest/QuestProgressTracker.php` — Écoute événements (MobDeadEvent, HarvestCompletedEvent, CraftCompletedEvent) et met à jour la progression
- **Créer** `src/Controller/Game/QuestController.php` — Routes `/game/quests` (journal), `/game/quest/{id}/accept` (POST), `/game/quest/{id}/complete` (POST)
- **Créer** `templates/game/quest/` — Journal de quêtes, détail quête, dialogue acceptation
- **Modifier** `src/Controller/Game/Map/PnjDialogController.php` — Ajouter indicateur quête (! au-dessus du PNJ)

**18.C — Fixtures quêtes starter [S]**
- **Créer** `src/DataFixtures/Game/QuestFixtures.php` — ~10 quêtes :
  - Quête tutoriel : "Parler au forgeron"
  - Quête combat : "Éliminer 5 zombies"
  - Quête récolte : "Récolter 3 minerais de fer"
  - Quête craft : "Forger une épée en fer"
  - Quête exploration : "Découvrir 3 types de monstres"
  - Chaîne de quêtes : 3 quêtes liées (prérequis)

#### Tests
- Test `QuestManager` : accept, progress, complete
- Test `QuestProgressTracker` : mise à jour sur événements
- Test fonctionnel : parcours quête complet

---

## Release v0.5 — Monde vivant

### Phase 19 — Contenu monstres & tables de loot [M] → PR #19

**Problème** : Seulement ~5 monstres. Besoin de 20-30 monstres variés pour peupler les zones.

**Dépendances** : Aucune

#### Sous-phases

**19.A — Monstres niveaux 1-10 [S]**
- 8 nouveaux monstres de base (1 par élément) : Slime (eau), Gobelin (terre), Chauve-souris (air), Loup (bête), Automate rouillé (métal), Fantôme (ombre), Lutin (lumière), Salamandre (feu)
- IA patterns variés, résistances élémentaires, sprites
- Tables de loot avec ressources de craft

**19.B — Monstres niveaux 10-25 + Boss [S]**
- 8 monstres intermédiaires + 2 boss de zone
- Boss avec mécanique de phases (comme le Dragon existant)
- Loot rare et materia

**19.C — Mise à jour bestiaire & succès [S]**
- Ajouter les nouveaux monstres aux achievements
- Nouveaux succès pour les boss

---

### Phase 20 — Équipement & items variés [M] → PR #20

**Problème** : Très peu d'items (~25). Les joueurs n'ont pas de progression d'équipement.

**Dépendances** : Phase 17 (craft)

#### Sous-phases

**20.A — Sets d'équipement par tier [M]**
- 3 tiers d'équipement (starter, intermédiaire, avancé)
- Chaque tier : arme, casque, plastron, jambières, bottes, gants, bouclier
- Éléments variés (épée de feu, armure de glace, etc.)
- Slots materia sur l'équipement avancé

**20.B — Materia variées [S]**
- 16+ materia (2 par élément) : basique et avancée
- Sorts associés existants dans les fixtures Spell

**20.C — Consommables [S]**
- Potions (soin, mana, antidote, buff temporaire)
- Nourriture (buffs stats temporaires)
- Parchemins (téléportation, identification)

---

### Phase 21 — Nouvelles zones & cartes [XL] → PR #21

**Problème** : 1 seule carte 60×60. Le monde est trop petit.

**Dépendances** : Phase 19 (monstres pour peupler)

#### Sous-phases

**21.A — Système de téléportation entre cartes [M]**
- **Modifier** `src/Entity/App/Map.php` — Ajouter `teleportLinks` (JSON: coordonnée source → mapId destination + coordonnée)
- **Modifier** `src/GameEngine/Movement/PlayerMoveProcessor.php` — Détecter tuile téléportation et changer de carte
- **Modifier** `assets/controllers/map_pixi_controller.js` — Transition entre cartes

**21.B — Carte "Forêt des murmures" (zone lvl 5-15) [M]**
- Nouveau fichier TMX dans `terrain/`
- Import via `app:terrain:import`
- Spots de récolte (herbes, bois)
- Monstres forestiers
- PNJ + quêtes de zone

**21.C — Carte "Mines profondes" (zone lvl 10-25) [M]**
- Donjon souterrain avec filons de minerai
- Boss de donjon
- Monstres métal/terre
- Trésor de fin de donjon

**21.D — Carte "Village central" (hub) [M]**
- Zone sociale sans combat
- Boutiques (armurier, alchimiste, joaillier, marchand)
- PNJ de quêtes principales
- Banque, forge, autel de materia

---

### Phase 22 — Équilibre & balancing [M] → PR #22

**Problème** : Avec plus de contenu, l'équilibre numérique devient critique.

**Dépendances** : Phases 19-21

- Courbe de progression XP par domaine (formule exponentielle)
- Barème des prix boutique (ratio achat/vente)
- Table de drop rates par tier de monstre
- Dégâts/HP des monstres par palier de niveau
- Coût en XP des compétences (calibrage des 400+ skills)
- Temps de récolte et rendement par skill level
- **Créer** `src/Command/BalanceReportCommand.php` — CLI qui génère un rapport d'équilibre (stats moyennes par tier, courbes XP, etc.)

---

## Release v0.6 — Polish & Immersion

### Phase 23 — Tests fonctionnels & E2E [M] → PR #23

**Problème** : Couverture de tests très faible en fonctionnel/E2E.

**Dépendances** : Phases 14-18 (tester les nouveaux systèmes)

- Tests fonctionnels pour tous les contrôleurs Game/*
- Tests E2E Panther : parcours combat complet, parcours quête, parcours craft
- Tests intégration : événements et listeners (BestiaryListener, AchievementTracker, QuestProgressTracker)
- Objectif : couverture ≥ 60% sur `src/GameEngine/`

---

### Phase 24 — UX/UI améliorations [M] → PR #24

**Problème** : L'interface est fonctionnelle mais manque de polish.

**Dépendances** : Aucune

- **Minimap** : mini-carte dans un coin avec position joueur, mobs, PNJ
- **Notifications in-game** : toast pour drops, level-up domaine, quêtes complétées, succès
- **Indicateurs PNJ** : icônes au-dessus des PNJ (! quête, $ boutique, ? dialogue)
- **Barre d'action rapide** : raccourcis consommables/sorts fréquents
- **Journal de combat** amélioré : log détaillé avec couleurs élémentaires
- **Responsive mobile** : optimisation tactile, menus adaptés

---

### Phase 25 — Effets visuels & ambiance [M] → PR #25

**Problème** : Le rendu est statique, manque d'ambiance.

**Dépendances** : Aucune

- **Cycle jour/nuit** : filtre PixiJS sur le viewport (teinte chaude → froide), durée configurable
- **Particules** : effets PixiJS pour sorts en combat, récolte, level-up
- **Animations de combat** : shake écran sur critiques, flash élémentaire sur sorts
- **Transitions de zone** : fondu au noir entre cartes
- **Sons** (optionnel) : Howler.js pour effets sonores de base (attaque, sort, récolte, level-up)

---

### Phase 26 — Performance & monitoring [S] → PR #26

**Problème** : Avec plus de joueurs et de contenu, la performance devient un enjeu.

**Dépendances** : Aucune

- **Cache Doctrine** : result cache sur requêtes fréquentes (spells, skills, monsters)
- **Optimisation queries** : N+1 detection, eager loading stratégique
- **Index DB** : index composites sur les tables critiques (player_items, fight, harvest_spots)
- **Monitoring** : métriques Prometheus/Grafana basiques (temps de réponse, fights actifs, joueurs connectés)
- **Rate limiting** : protection contre spam API (mouvements, achats, craft)

---

## Ordre d'exécution et dépendances

```
Phase 14 (Materia vérification)     ← PRIORITÉ 1, bloquant
  ↓
Phase 15 (Boutiques PNJ)            ← indépendant
  ↓
Phase 16 (Récolte)                  ← dépend Phase 15 (vente)
  ↓
Phase 17 (Artisanat)                ← dépend Phase 16 (ressources)
  ↓
Phase 18 (Quêtes)                   ← dépend Phase 15 + 16

Phase 19 (Monstres)                 ← parallélisable avec Phase 16-17
Phase 20 (Équipement)               ← dépend Phase 17 (craft)
  ↓
Phase 21 (Zones)                    ← dépend Phase 19 + 20
  ↓
Phase 22 (Balancing)                ← dépend Phase 19-21

Phase 23 (Tests)                    ← parallélisable, après Phase 18
Phase 24 (UX/UI)                    ← indépendant
Phase 25 (Visuels)                  ← indépendant
Phase 26 (Performance)              ← indépendant, fin de release
```

### Parallélisations possibles

- Phases 15 et 19 peuvent avancer en parallèle
- Phases 23, 24, 25 sont indépendantes et parallélisables
- Phase 26 peut commencer dès que Phase 21 est stable

---

## Résumé

| Phase | Description | Taille | Dépendances | Release |
|-------|------------|--------|-------------|---------|
| 14 | Finalisation materia vérification | S | Phase 8 | v0.4 |
| 15 | Boutiques PNJ & économie | M | — | v0.4 |
| 16 | Système de récolte | L | Phase 15 | v0.4 |
| 17 | Système d'artisanat | L | Phase 16 | v0.4 |
| 18 | Système de quêtes | L | Phase 15, 16 | v0.4 |
| 19 | Contenu monstres & loot | M | — | v0.5 |
| 20 | Équipement & items variés | M | Phase 17 | v0.5 |
| 21 | Nouvelles zones & cartes | XL | Phase 19, 20 | v0.5 |
| 22 | Équilibre & balancing | M | Phase 19-21 | v0.5 |
| 23 | Tests fonctionnels & E2E | M | Phase 18 | v0.6 |
| 24 | UX/UI améliorations | M | — | v0.6 |
| 25 | Effets visuels & ambiance | M | — | v0.6 |
| 26 | Performance & monitoring | S | — | v0.6 |

**Total estimé** : 13 phases, 3 releases, progression incrémentale avec valeur jouable à chaque release.

---

## Phases transverses — Pipeline Tiled

> Détail complet dans [TILED_PIPELINE_ROADMAP.md](TILED_PIPELINE_ROADMAP.md)

Ces phases améliorent le pipeline Tiled → Jeu et sont exécutables en parallèle des phases gameplay.

| Phase | Description | Taille | Problèmes résolus | Release |
|-------|------------|--------|-------------------|---------|
| T1 | Animations de tiles (eau, lave, fleurs) | M | Monde statique, pas d'anim dans l'API | v0.6 |
| T2 | Pipeline unifié `app:terrain:sync` | M | 3 étapes manuelles déconnectées | v0.5 |
| T3 | Zones/biomes depuis Tiled | M | Pas de données biome, pas de zones | v0.6 |
| T4 | Supprimer commande CSS morte | S | Code mort `tmx:generate-css` | v0.5 |
| T5 | Dé-hardcoder les map IDs | S | `map_id = 10` hardcodé | v0.5 |

**Recommandation** : T4 + T5 immédiatement, T1 en parallèle de v0.4, T2 + T3 avant Phase 21 (nouvelles zones).
