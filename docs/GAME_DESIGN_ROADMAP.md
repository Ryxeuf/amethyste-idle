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

## Phase 6 — Infrastructure 32 domaines [M] ✅ *Terminée* → PR #6

**Problème** : 15 domaines avec 3-7 compétences → 32 domaines avec 15+ compétences chacun.
**Approche** : Découpée en sous-phases (6.A = infra, 6.B-6.I = arbres de talent par élément).

### 6.A — Infrastructure ✅
- [x] `src/Entity/Game/Domain.php` — Ajout champ `element` (string, nullable)
- [x] `src/DataFixtures/DomainFixtures.php` — 32 domaines avec élément associé
- [x] `src/DataFixtures/SpellFixtures.php` — ~160 sorts couvrant les 8 éléments + sorts spécifiques par domaine
- [x] `src/Entity/Game/Spell.php` / `Item.php` — Constantes ELEMENT_METAL, ELEMENT_BEAST
- [x] Normalisation éléments : life→light, death→dark
- [x] Migration SQL `Version20260319DomainElement.php`
- [x] Template CSS : couleurs des 32 domaines
- [x] Pyromancien : arbre modèle complet (15 skills, 5 rangs) + 1 skill materia feu
- [x] Stormcaller : remplace white_wizard (4 skills)
- [x] Domaines existants (soldat, guérisseur, défenseur, nécromancien, druide, mineur, herboriste) adaptés aux nouvelles clés

### Structure de chaque arbre (15 compétences minimum)
Chaque domaine combat suit le pattern :
```
                    [Ultime]           (rang 5 - 1 compétence, 150+ pts)
                   /        \
             [Avancé A]  [Avancé B]    (rang 4 - 2 compétences, 60-100 pts)
              /      \    /      \
        [Inter 1] [Inter 2] [Inter 3] [Inter 4]   (rang 3 - 4 compétences, 25-50 pts)
         /    \      |        |      \
   [Base 1] [Base 2] [Base 3] [Base 4]            (rang 2 - 4 compétences, 10-20 pts)
         \       /
        [Apprenti 1]                               (rang 1 - 2 compétences, 0 pts)
        [Apprenti 2]
```

### Compétences materia par domaine

**Chaque domaine de combat** doit inclure **1 compétence materia** (rang 3, ~35 pts) qui renforce l'utilisation des materia de l'élément associé. Ces compétences utilisent le champ `actions.materia` du JSON :

```php
'actions' => ['materia' => ['xp_bonus' => 0.20, 'damage_bonus' => 0.10]]
```

| Bonus | Effet | Valeur |
|-------|-------|--------|
| `xp_bonus` | Bonus XP materia de cet élément en combat | +20% |
| `damage_bonus` | Bonus dégâts des sorts lancés via materia de cet élément | +10% |

**Intégration technique** : `CombatSkillResolver` doit lire `actions.materia` pour calculer les bonus. `MateriaXpGranter` et `CombatCapacityResolver` appliquent ces bonus en plus du matching élémentaire existant (+25%).

**Placement dans l'arbre** : rang 3 (25-35 pts requis), prérequis d'un skill de rang 2. Le skill materia n'est pas obligatoire pour progresser vers les rangs supérieurs — c'est une branche optionnelle de spécialisation.

**Convention de nommage** : `{domaine}_materia_affinity` (slug: `{domaine}-materia-affinity`)

---

## Phase 6.B — Arbres de talent Feu (Berserker + Artificier) [S] ✅ *Terminée* → PR #6b

- [x] `SkillFixtures::getBerserkerSkills()` — 15 compétences (Rage → Furie sanguinaire)
- [x] `SkillFixtures::getArtificerSkills()` — 15 compétences (Piège incendiaire → Barrage d'artillerie)
- [x] **Materia** : 1 skill materia par domaine Feu (Pyromancien, Berserker, Artificier) — affinité materia feu (+20% XP, +10% dégâts)

---

## Phase 6.C — Arbres de talent Eau (Hydromancien + Guérisseur 15 + Marémancien) [S] ✅ *Terminée* → PR #6c

- [x] `SkillFixtures::getHydromancerSkills()` — 13 compétences (Jet d'eau → Tsunami)
- [x] `SkillFixtures::getHealerSkills()` — Étendu de 4 à 13 compétences (arbre complet 5 rangs)
- [x] `SkillFixtures::getTidecallerSkills()` — 13 compétences (Marée montante → Maelström)
- [x] **Materia** : 1 skill materia par domaine Eau (Hydromancien, Guérisseur, Marémancien) — affinité materia eau (+20% XP, +10% dégâts)

---

## Phase 6.D — Arbres de talent Air (Foudromancien 15 + Archer + Vagabond) [S] → PR #6d

- [ ] `SkillFixtures::getStormcallerSkills()` — Étendre de 4 à 15 compétences
- [ ] `SkillFixtures::getArcherSkills()` — 15 compétences (Tir précis → Flèche perforante)
- [ ] `SkillFixtures::getWandererSkills()` — 15 compétences (Hâte → Zéphyr)
- [ ] **Materia** : 1 skill materia par domaine Air — affinité materia air (+20% XP, +10% dégâts)

---

## Phase 6.E — Arbres de talent Terre (Géomancien + Défenseur 15 + Gardien) [S] → PR #6e

- [ ] `SkillFixtures::getGeomancerSkills()` — 15 compétences (Jet de cailloux → Déplacement tectonique)
- [ ] `SkillFixtures::getDefenderSkills()` — Étendre de 4 à 15 compétences
- [ ] `SkillFixtures::getGuardianSkills()` — 15 compétences (Bouclier partagé → Bastion)
- [ ] **Materia** : 1 skill materia par domaine Terre — affinité materia terre (+20% XP, +10% dégâts)

---

## Phase 6.F — Arbres de talent Métal (Soldat 15 + Chevalier + Ingénieur) [S] → PR #6f

- [ ] `SkillFixtures::getSoldierSkills()` — Étendre de 4 à 15 compétences
- [ ] `SkillFixtures::getKnightSkills()` — 15 compétences (Provocation → Forteresse d'acier)
- [ ] `SkillFixtures::getEngineerSkills()` — 15 compétences (Tourelle → Engin de siège)
- [ ] **Materia** : 1 skill materia par domaine Métal — affinité materia métal (+20% XP, +10% dégâts)

---

## Phase 6.G — Arbres de talent Bête (Chasseur + Dompteur + Druide 15) [S] → PR #6g

- [ ] `SkillFixtures::getHunterSkills()` — 15 compétences (Appel du faucon → Chasse en meute)
- [ ] `SkillFixtures::getTamerSkills()` — 15 compétences (Lien bestial → Rugissement alpha)
- [ ] `SkillFixtures::getDruidSkills()` — Étendre de 4 à 15 compétences
- [ ] **Materia** : 1 skill materia par domaine Bête — affinité materia bête (+20% XP, +10% dégâts)

---

## Phase 6.H — Arbres de talent Lumière + Ombre (6 domaines) [M] → PR #6h

- [ ] `SkillFixtures::getPaladinSkills()` — 15 compétences (Frappe sacrée → Jugement divin)
- [ ] `SkillFixtures::getPriestSkills()` — 15 compétences (Prière → Miracle)
- [ ] `SkillFixtures::getInquisitorSkills()` — 15 compétences (Châtiment sacré → Sentence divine)
- [ ] `SkillFixtures::getAssassinSkills()` — 15 compétences (Embuscade → Danse des ombres)
- [ ] `SkillFixtures::getNecromancerSkills()` — Étendre de 4 à 15 compétences
- [ ] `SkillFixtures::getWarlockSkills()` — 15 compétences (Maléfice → Pacte sombre)
- [ ] **Materia** : 1 skill materia par domaine Lumière + Ombre (6 skills) — affinité materia lumière/ombre (+20% XP, +10% dégâts)

---

## Phase 6.I — Arbres de talent Récolte + Craft (8 domaines) + Skills partagés [M] → PR #6i

- [ ] Mineur : étendre de 7 à 15 compétences
- [ ] Herboriste : étendre de 3 à 15 compétences
- [ ] Pêcheur : 15 compétences (nouveau)
- [ ] Dépeceur : 15 compétences (nouveau)
- [ ] Forgeron : 15 compétences (nouveau)
- [ ] Tanneur : 15 compétences (nouveau)
- [ ] Alchimiste : 15 compétences (nouveau)
- [ ] Joaillier : 15 compétences (nouveau)
- [ ] Compétences partagées multi-domaines (Premiers soins, Endurance, etc.)
- [ ] **Materia craft** : Joaillier inclut des compétences liées au sertissage de materia (bonus sockets, qualité materia). Forgeron inclut des compétences pour ajouter des slots materia aux équipements

### Tests (à la fin de toutes les sous-phases 6.*)
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
Phase 6.A (Infra 32 domaines)       ← dépend Phase 5 ✅
Phase 6.B-6.I (Arbres de talent)    ← dépend Phase 6.A, S/M chacune
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
| 1 | Enum Element + metal/beast | S | Non | ✅ |
| 2 | Race de personnage (Humain) | S | Non | ✅ |
| 3 | Spell niveau/valueType + Calculators | S | Non | ✅ |
| 4 | StatusEffect hybride | M | Non | ✅ |
| 5 | Skills multi-domaines | L | **OUI** | ✅ |
| 6.A | Infrastructure 32 domaines + Pyromancien modèle | M | Non | #6 ✅ |
| 6.B | Arbres de talent Feu (Berserker, Artificier) | S | Non | #6b ✅ |
| 6.C | Arbres de talent Eau (Hydro, Guérisseur, Marémancien) | S | Non | #6c |
| 6.D | Arbres de talent Air (Foudro, Archer, Vagabond) | S | Non | #6d |
| 6.E | Arbres de talent Terre (Géo, Défenseur, Gardien) | S | Non | #6e |
| 6.F | Arbres de talent Métal (Soldat, Chevalier, Ingénieur) | S | Non | #6f |
| 6.G | Arbres de talent Bête (Chasseur, Dompteur, Druide) | S | Non | #6g |
| 6.H | Arbres de talent Lumière + Ombre (6 domaines) | M | Non | #6h |
| 6.I | Arbres Récolte + Craft + Skills partagés | M | Non | #6i |
| 7 | Tout est un sort + Soulbound | M | Non | #7 |
| 8 | Materia = Capacités combat | L | Partiel | #8 |
| 9 | Inventaire groupement UI | S | Non | #9 |
| 10 | Dashboard zones | S | Non | #10 |
| 11 | Bestiaire | M | Non | #11 |
| 12 | Succès/Achievements | M | Non | #12 |
| 13 | Documentation finale | S | Non | #13 |
