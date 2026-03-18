# Plan d'implémentation — Règles de conception MMORPG

## Contexte

Le game design d'Amethyste-Idle évolue avec des règles structurantes : 8 éléments, 32+ domaines avec archétypes de joueur (tank, DPS, healer, support...), compétences multi-domaines (15+ par domaine), sorts comme base de toute action, materia = capacités de combat, races de personnage, items soulbound, bestiaire et succès.

**État actuel** : 7 éléments (manque metal/bête), 15 domaines avec 3-7 compétences chacun, pas de race, pas de bestiaire ni succès.

**Règles transversales** :
- **1 PR par phase** — chaque phase produit une pull request distincte
- **Méthodes de calcul abstraites** — les formules (toucher, dégâts, critiques) sont isolées dans des services dédiés pour faciliter les ajustements futurs
- **Sprite `materias.png`** — utilisé pour le rendu des materia dans l'inventaire ET des filons sur la carte (`assets/styles/images/materias.png`)
- **Documentation** mise à jour à chaque phase dans la PR correspondante

---

## Matrice Domaines × Éléments × Archétypes

### Domaines de combat (24 domaines, 3 par élément)

| Élément | Domaine | Archétype | Rôle |
|---------|---------|-----------|------|
| **Feu** | Pyromancien | DPS magique distance | Dégâts de zone, burst |
| | Berserker | DPS CaC | Rage, dégâts physiques+feu, risque/récompense |
| | Artificier | Support offensif | Pièges, AoE, contrôle de zone |
| **Eau** | Hydromancien | DPS magique distance | Contrôle, ralentissements, dégâts continus |
| | Guérisseur | Healer | Soins, purification, boucliers |
| | Marémancien | Support défensif | Buffs d'équipe, debuffs ennemis, CC |
| **Air** | Foudromancien | DPS magique distance | Burst électrique, chaînes d'éclairs |
| | Archer | DPS distance physique | Précision, tirs critiques, mobilité |
| | Vagabond | Support/Évasion | Buffs vitesse, esquive, utilitaire |
| **Terre** | Géomancien | DPS magique | Dégâts de zone, terrain, entrave |
| | Défenseur | Tank | Aggro, absorption, murs |
| | Gardien | Tank/Support | Protection de groupe, boucliers partagés |
| **Métal** | Soldat | DPS CaC | Combos d'armes, techniques martiales |
| | Chevalier | Tank lourd | Armure lourde, contre-attaque, provocation |
| | Ingénieur | Support technique | Constructions, buffs d'équipement, réparations |
| **Bête** | Chasseur | DPS distance | Compagnons animaux, pièges, pistage |
| | Dompteur | Tank/Invocateur | Invocation de bêtes, tanking via familier |
| | Druide | Healer/Support | Soins nature, transformations, poisons |
| **Lumière** | Paladin | Tank/Healer | Guerrier sacré, auras, soins légers |
| | Prêtre | Healer pur | Soins puissants, résurrection, purification |
| | Inquisiteur | DPS magique | Dégâts sacrés, anti-mort-vivant, jugement |
| **Ombre** | Assassin | DPS CaC | Furtivité, critiques massifs, poisons |
| | Nécromancien | DPS magique/Invocateur | Drain de vie, malédictions, morts-vivants |
| | Sorcier | Support/Debuff | Malédictions, affaiblissements, terreur |

### Domaines de récolte (4 domaines, associés à un élément)

| Domaine | Élément | Justification |
|---------|---------|---------------|
| Mineur | Terre | Extraction de minerais, pierres |
| Herboriste | Bête | Cueillette en milieu naturel/sauvage |
| Pêcheur | Eau | Environnement aquatique |
| Dépeceur | Bête | Matériaux d'origine animale |

### Domaines de craft (4 domaines, associés à un élément)

| Domaine | Élément | Justification |
|---------|---------|---------------|
| Forgeron | Métal | Travail des métaux, forge |
| Tanneur | Bête | Travail des cuirs et peaux |
| Alchimiste | Eau | Potions, élixirs, distillation |
| Joaillier | Terre | Gemmes, cristaux, sertissage |

**Total : 32 domaines** (24 combat + 4 récolte + 4 craft)

### Couverture des archétypes

| Archétype | Domaines |
|-----------|----------|
| Tank | Défenseur (Terre), Gardien (Terre), Chevalier (Métal), Dompteur (Bête), Paladin (Lumière) |
| DPS CaC | Berserker (Feu), Soldat (Métal), Assassin (Ombre) |
| DPS Distance | Archer (Air), Chasseur (Bête) |
| DPS Magique | Pyromancien (Feu), Hydromancien (Eau), Foudromancien (Air), Géomancien (Terre), Inquisiteur (Lumière), Nécromancien (Ombre) |
| Healer | Guérisseur (Eau), Druide (Bête), Prêtre (Lumière) |
| Support | Artificier (Feu), Marémancien (Eau), Vagabond (Air), Ingénieur (Métal), Sorcier (Ombre) |

---

## Phase 1 — Enum Element centralisé + 2 nouveaux éléments [S] → PR #1

**Problème** : Éléments dupliqués en constantes, manque metal et beast.

### Fichiers
- **Créer** `src/Enum/Element.php` — PHP 8.4 backed enum (string) : none, fire, water, earth, air, light, dark, metal, beast
- **Modifier** `src/Entity/Game/Spell.php` — Remplacer ELEMENT_* par l'enum
- **Modifier** `src/Entity/Game/Item.php` — Idem
- **Modifier** `src/Entity/Game/StatusEffect.php` — Idem
- **Modifier** `src/GameEngine/Fight/ElementalSynergyCalculator.php` — Synergies metal/beast
- **Modifier** `src/GameEngine/Fight/SpellApplicator.php` — Adapter à l'enum
- **Modifier** fixtures : ajouter sorts/effets metal et beast
- **Migration** : aucune (colonnes string, valeurs changent)

### Tests
- Test unitaire `ElementTest`, `ElementalSynergyCalculator`
- PHPStan + CS-Fixer

---

## Phase 2 — Système de race de personnage [S] → PR #2

**Problème** : Pas de race. À terme, plusieurs races disponibles à la création. Pour l'instant, uniquement "Humain".

### Fichiers
- **Créer** `src/Entity/Game/Race.php` :
  - `slug` (string, unique), `name` (string), `description` (text)
  - `spriteSheet` (string, nullable) — sprite associé
  - `statModifiers` (JSON) — ex: `{"life": 0, "energy": 0, "speed": 0, "hit": 0}` (humain = neutres)
  - `availableAtCreation` (bool, default true)
- **Modifier** `src/Entity/App/Player.php` — Ajouter `race` (ManyToOne Race, nullable pour migration)
- **Créer** `src/DataFixtures/Game/RaceFixtures.php` — Race "Humain" avec stats neutres
- **Modifier** `src/Controller/Security/` — À la création du personnage, assigner la race humain par défaut
- **Migration** : CREATE TABLE game_races + ALTER TABLE player ADD race_id

### Tests
- Test unitaire : création joueur avec race humain
- Test : statModifiers appliqués correctement (même si neutres)

---

## Phase 3 — Spell : niveau + type de valeur + abstraction calculs [S] → PR #3

**Problème** : Sorts sans niveau ni valueType. Formules de calcul codées en dur.

### Fichiers
- **Modifier** `src/Entity/Game/Spell.php` :
  - Ajouter `level` (int, default 1)
  - Ajouter `valueType` (string: 'fixed'|'percent', default 'fixed')
- **Créer** `src/GameEngine/Fight/Calculator/DamageCalculator.php` — Extraire le calcul de dégâts depuis SpellApplicator (formule isolée, facilement modifiable)
- **Créer** `src/GameEngine/Fight/Calculator/HitChanceCalculator.php` — Extraire le calcul de toucher (spell.level vs monster.level)
- **Créer** `src/GameEngine/Fight/Calculator/CriticalCalculator.php` — Extraire le calcul de critique
- **Modifier** `src/GameEngine/Fight/SpellApplicator.php` — Déléguer aux Calculator, gérer valueType percent
- **Modifier** fixtures : ajouter levels aux sorts existants
- **Migration** : ALTER TABLE game_spells ADD level, ADD value_type

### Tests
- Test unitaire pour chaque Calculator
- Test SpellApplicator : dégâts fixes vs pourcentage

---

## Phase 4 — Side effects enrichis (buff/debuff/HoT/DoT) [M] → PR #4

**Décision** : Système hybride — tours en combat + datetime hors combat.

### Fichiers
- **Modifier** `src/Entity/Game/StatusEffect.php` :
  - Ajouter `category` (string: 'buff'|'debuff'|'hot'|'dot')
  - Ajouter `frequency` (int, nullable) — tours entre ticks
  - Ajouter `realTimeDuration` (int, nullable) — secondes hors combat
- **Modifier** `src/Entity/App/FightStatusEffect.php` — Ajouter `lastTickTurn`
- **Créer** `src/Entity/App/PlayerStatusEffect.php` — Effets persistants hors combat (player, statusEffect, expiresAt, appliedAt)
- **Modifier** `src/GameEngine/Fight/StatusEffectManager.php` — Tick selon fréquence + chargement effets persistants
- **Migration** : ALTER TABLE + CREATE TABLE player_status_effects

### Tests
- Test unitaire StatusEffectManager : fréquence variable
- Test PlayerStatusEffect : expiration datetime
- Test intégration : buff nourriture en combat

---

## Phase 5 — Compétences multi-domaines [L] ⚠️ BREAKING → PR #5

**Problème** : `Skill.domain` ManyToOne → ManyToMany + auto-unlock + 100% XP par domaine.

### Stratégie migration
1. CREATE TABLE skill_domain (skill_id, domain_id)
2. INSERT INTO skill_domain SELECT id, domain_id FROM game_skills
3. ALTER TABLE game_skills DROP domain_id

### Fichiers
- **Modifier** `src/Entity/Game/Skill.php` — `$domains` (ManyToMany), garder `getDomain()` rétrocompat
- **Modifier** `src/Entity/Game/Domain.php` — Relation inverse
- **Modifier** `src/GameEngine/Progression/SkillAcquiring.php` — Auto-unlock cross-domaine
- **Créer** `src/GameEngine/Progression/CrossDomainSkillResolver.php` — checkAutoUnlock + grantXpToAllDomains (100% chaque)
- **Modifier** `src/GameEngine/Fight/CombatSkillResolver.php`, `MateriaXpGranter.php`, `PlayerSkillHelper.php`
- **Modifier** controllers + templates skills
- **Migration** : CREATE + data migration + DROP

### Tests
- Test CrossDomainSkillResolver : auto-unlock, XP multi-domaine
- Test intégration : parcours complet

---

## Phase 6 — 32 domaines + 15 compétences par domaine [XL] → PR #6

**Problème** : 15 domaines avec 3-7 compétences → 32 domaines avec 15+ compétences chacun.

### Création des domaines (fixtures YAML)
- **Modifier** `src/DataFixtures/DomainFixtures.php` — Ajouter les 17 nouveaux domaines combat + associer élément à chaque domaine
- **Modifier** `src/Entity/Game/Domain.php` — Ajouter `element` (string, nullable) pour associer domaine ↔ élément

### Structure de chaque arbre (15 compétences minimum)
Chaque domaine suit le pattern 3 branches :
```
                    [Ultime]           (rang 5 - 1 compétence)
                   /        \
             [Avancé A]  [Avancé B]    (rang 4 - 2 compétences)
              /      \    /      \
        [Inter A1] [Inter A2] [Inter B1] [Inter B2]   (rang 3 - 4 compétences)
         /    \      |        |      \
   [Base A1-3]  [Base B1-3]             (rang 2 - 4 compétences)
         \       /
        [Apprenti]                      (rang 1 - 2 compétences, 0 pts)
        [Initiation]
```

### Fichiers fixtures YAML à créer/modifier (1 fichier par domaine)
**Feu** :
- `fixtures/game/skill/pyromancy.yaml` — Refonte : 15 compétences (Apprenti → Éruption solaire)
- `fixtures/game/skill/berserker.yaml` — Nouveau : Rage, Charge enflammée, Furie, ...
- `fixtures/game/skill/artificer.yaml` — Nouveau : Piège incendiaire, Mine explosive, ...

**Eau** :
- `fixtures/game/skill/hydromancer.yaml` — Nouveau : Jet d'eau, Tsunami, Maelström, ...
- `fixtures/game/skill/healer.yaml` — Refonte : 15 compétences (Soin mineur → Résurrection)
- `fixtures/game/skill/tidecaller.yaml` — Nouveau : Marée montante, Brume protectrice, ...

**Air** :
- `fixtures/game/skill/stormcaller.yaml` — Nouveau (remplace white_wizard) : Éclair, Tempête, Foudre en chaîne, ...
- `fixtures/game/skill/archer.yaml` — Nouveau : Tir précis, Pluie de flèches, Tir critique, ...
- `fixtures/game/skill/wanderer.yaml` — Nouveau : Pas du vent, Hâte, Mirage, ...

**Terre** :
- `fixtures/game/skill/geomancer.yaml` — Nouveau : Tremblement, Pilier de roche, Avalanche, ...
- `fixtures/game/skill/defender.yaml` — Refonte : 15 compétences (Parade → Forteresse)
- `fixtures/game/skill/guardian.yaml` — Nouveau : Bouclier partagé, Mur protecteur, Bastion, ...

**Métal** :
- `fixtures/game/skill/soldier.yaml` — Refonte : 15 compétences (Frappe → Danse des lames)
- `fixtures/game/skill/knight.yaml` — Nouveau : Provocation, Riposte, Rempart d'acier, ...
- `fixtures/game/skill/engineer.yaml` — Nouveau : Tourelle, Renforcement, Automate, ...

**Bête** :
- `fixtures/game/skill/hunter.yaml` — Nouveau : Appel du faucon, Piège à ours, Tir empoisonné, ...
- `fixtures/game/skill/tamer.yaml` — Nouveau : Apprivoisement, Lien bestial, Charge sauvage, ...
- `fixtures/game/skill/druid.yaml` — Refonte : 15 compétences (Liane → Appel de la forêt primordiale)

**Lumière** :
- `fixtures/game/skill/paladin.yaml` — Nouveau : Frappe sacrée, Aura de lumière, Jugement, ...
- `fixtures/game/skill/priest.yaml` — Nouveau : Prière, Bénédiction, Miracle, ...
- `fixtures/game/skill/inquisitor.yaml` — Nouveau : Châtiment, Purge, Sentence divine, ...

**Ombre** :
- `fixtures/game/skill/assassin.yaml` — Nouveau : Embuscade, Coup mortel, Ombre dansante, ...
- `fixtures/game/skill/necromancer.yaml` — Refonte : 15 compétences (Drain → Apocalypse nécrotique)
- `fixtures/game/skill/warlock.yaml` — Nouveau : Malédiction, Terreur, Pacte sombre, ...

**Récolte** (15 compétences par domaine) :
- `fixtures/game/skill/miner.yaml` — Refonte : 15 compétences (Pioche basique → Maître mineur)
- `fixtures/game/skill/herbalist.yaml` — Refonte : 15 compétences
- `fixtures/game/skill/fisherman.yaml` — Refonte : 15 compétences
- `fixtures/game/skill/skinner.yaml` — Refonte : 15 compétences

**Craft** (15 compétences par domaine) :
- `fixtures/game/skill/blacksmith.yaml` — Refonte : 15 compétences
- `fixtures/game/skill/leatherworker.yaml` — Refonte : 15 compétences
- `fixtures/game/skill/alchemist.yaml` — Refonte : 15 compétences
- `fixtures/game/skill/jeweller.yaml` — Refonte : 15 compétences

### Compétences partagées entre domaines (exemples)
- "Premiers soins" → Guérisseur + Druide + Prêtre
- "Résistance physique" → Défenseur + Chevalier + Paladin
- "Connaissance des poisons" → Druide + Assassin
- "Méditation" → tous les DPS magiques

### Sorts associés (fixtures)
- **Créer** `fixtures/game/spell/` — 1 fichier YAML par élément avec les sorts de ses domaines
- Chaque compétence qui débloque une materia doit avoir un sort associé

### Tests
- Test fixtures : tous les domaines ont ≥ 15 compétences
- Test : arbre de prérequis cohérent (pas de cycle, pas d'orphelin)
- Test : compétences partagées bien reliées aux domaines multiples

---

## Phase 7 — Tout est un sort + Soulbound [M] → PR #7

**Problème** : Uniformiser actions → sorts. Ajouter items liés au personnage.

### Fichiers
- **Modifier** `src/Entity/Game/Item.php` — Ajouter `boundToPlayer` (bool)
- **Modifier** `src/Entity/App/PlayerItem.php` — Ajouter `boundToPlayerId` (int, nullable)
- **Modifier** `src/GameEngine/Item/ItemEffectEncoder.php` — `use_spell` comme norme
- **Modifier** `src/Controller/Game/Inventory/UseItemController.php` — Cast spell puis destroy
- **Créer** sorts pour consommables dans fixtures
- **Modifier** templates inventaire — Icône "lié" sur items bound

### Tests
- Test : consommable cast sort + destruction
- Test : soulbound bloque transfert

---

## Phase 8 — Materia & Slots = Capacités de combat [L] → PR #8

**Problème** : Slots materia ne déterminent pas les capacités combat.

### Fichiers
- **Créer** `src/GameEngine/Fight/CombatCapacityResolver.php` :
  - Sorts disponibles = materia équipées dans les slots
  - Attaque arme TOUJOURS disponible gratuitement (hors slots)
  - Joueur = 1 attaque arme + N sorts materia
  - Bonus matching élément slot/materia (dégâts +X%, XP +X%)
- **Modifier** `src/GameEngine/Fight/CombatSkillResolver.php` — Intégrer CombatCapacityResolver
- **Modifier** `src/Controller/Game/FightController.php` — Barre d'actions dynamique
- **Modifier** `src/Helper/PlayerItemHelper.php` — `canEquipMateria()` : compétence requise
- **Modifier** templates combat + équipement
- **Utiliser** `assets/styles/images/materias.png` pour le rendu visuel des materia dans l'UI

### Tests
- Test CombatCapacityResolver : sorts selon équipement, bonus matching
- Test intégration : combat avec materia

---

## Phase 9 — Inventaire : groupement visuel [S] → PR #9

### Fichiers
- **Modifier** `src/Controller/Game/Inventory/ItemsController.php` — Grouper par `genericItem.slug`
- **Modifier** `templates/game/inventory/items/_list.html.twig` — "Potion de soin x3" avec dépliage
- **Modifier** `templates/game/inventory/materia/_list.html.twig` — Idem
- **Utiliser** `assets/styles/images/materias.png` pour les icônes materia

### Tests
- Test fonctionnel : groupement visuel correct

---

## Phase 10 — Dashboard enrichi [S] → PR #10

### Fichiers
- **Modifier** `src/Controller/Admin/DashboardController.php` — COUNT par map (PNJ, mobs vivants, joueurs connectés)
- **Modifier** `templates/admin/dashboard.html.twig` — Section "Répartition par zone"

### Tests
- Test fonctionnel : compteurs par zone

---

## Phase 11 — Bestiaire joueur [M] → PR #11

### Fichiers
- **Créer** `src/Entity/App/PlayerBestiary.php` (player, monster, killCount, firstEncounteredAt, firstKilledAt)
- **Créer** `src/Repository/App/PlayerBestiaryRepository.php`
- **Créer** `src/EventListener/BestiaryListener.php` — Écoute MobDeadEvent
- **Créer** `src/Controller/Game/BestiaryController.php` — Route `/game/bestiary`
- **Créer** `templates/game/bestiary/index.html.twig`
- **Modifier** `src/Entity/App/Player.php` — Relation bestiary
- **Migration** : CREATE TABLE player_bestiary

### Paliers
- 10 kills : faiblesses révélées
- 50 kills : table de loot visible
- 100 kills : titre "Chasseur de [monstre]"

### Tests
- Test BestiaryListener, test fonctionnel bestiaire

---

## Phase 12 — Système de succès [M] → PR #12

### Fichiers
- **Créer** `src/Entity/App/Achievement.php` (slug, title, description, category, criteria JSON, reward JSON, icon)
- **Créer** `src/Entity/App/PlayerAchievement.php` (player, achievement, progress, completedAt)
- **Créer** `src/GameEngine/Achievement/AchievementTracker.php` — Écoute événements domaine
- **Créer** `src/Controller/Game/AchievementController.php` — Route `/game/achievements`
- **Créer** `templates/game/achievements/index.html.twig`
- **Créer** `src/DataFixtures/AchievementFixtures.php`
- **Migration** : CREATE TABLE achievements + player_achievements

### Succès initiaux
- Quêtes effectuées (5, 10, 25, 50)
- Mobs tués par type (10, 50, 100)
- Monstres découverts (5, 10, tous)

### Tests
- Test AchievementTracker, test intégration

---

## Phase 13 — Mise à jour documentation [S] → PR #13

- Mettre à jour `ROADMAP.md` — Marquer les items implémentés, ajouter les nouveaux domaines
- Mettre à jour `DOCUMENTATION.md` — Documenter : enum Element, race, 32 domaines, multi-domaine, materia=capacités, calculators, bestiaire, succès
- Mettre à jour `AGENTS.md` — Nouvelles conventions
- Mettre à jour `CLAUDE.md` — Si nécessaire (commandes, routes)

**Note** : La documentation est AUSSI mise à jour dans chaque PR précédente (DOCUMENTATION.md, commentaires code). Cette phase est un pass final de cohérence.

---

## Ordre d'exécution et dépendances

```
Phase 1  (Enum Element)
  ↓
Phase 2  (Race personnage)          ← indépendant, parallélisable avec Phase 1
  ↓
Phase 3  (Spell niveau/valueType + Calculators)
  ↓
Phase 4  (StatusEffect hybride)
  ↓
Phase 5  (Skills multi-domaines)    ← BREAKING, dépend Phase 1
  ↓
Phase 6  (32 domaines + 15 skills)  ← dépend Phase 5, XL
  ↓
Phase 7  (Tout est un sort + Soulbound) ← dépend Phase 3
  ↓
Phase 8  (Materia = Capacités)      ← dépend Phase 5 + 6 + 7
  ↓
Phase 9  (Inventaire groupement)    ← indépendant
Phase 10 (Dashboard)                ← indépendant
Phase 11 (Bestiaire)                ← indépendant
  ↓
Phase 12 (Succès)                   ← dépend Phase 11
  ↓
Phase 13 (Documentation finale)
```

**Parallélisables** : Phases 9, 10, 11 peuvent être faites en parallèle.

---

## Vérification end-to-end (chaque phase)

1. `docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff`
2. `docker compose exec php vendor/bin/phpstan analyse`
3. `docker compose exec php vendor/bin/phpunit`
4. `docker compose exec php php bin/console doctrine:migrations:migrate`
5. `docker compose exec php php bin/console doctrine:fixtures:load` (dev)
6. Test manuel en jeu

---

## Résumé

| Phase | Description | Taille | Breaking? | PR |
|-------|------------|--------|-----------|-----|
| 1 | Enum Element + metal/beast | S | Non | #1 |
| 2 | Race de personnage (Humain) | S | Non | #2 |
| 3 | Spell niveau/valueType + Calculators | S | Non | #3 |
| 4 | StatusEffect hybride | M | Non | #4 |
| 5 | Skills multi-domaines | L | **OUI** | #5 |
| 6 | 32 domaines × 15+ compétences | XL | Non | #6 |
| 7 | Tout est un sort + Soulbound | M | Non | #7 |
| 8 | Materia = Capacités combat | L | Partiel | #8 |
| 9 | Inventaire groupement UI | S | Non | #9 |
| 10 | Dashboard zones | S | Non | #10 |
| 11 | Bestiaire | M | Non | #11 |
| 12 | Succès/Achievements | M | Non | #12 |
| 13 | Documentation finale | S | Non | #13 |
