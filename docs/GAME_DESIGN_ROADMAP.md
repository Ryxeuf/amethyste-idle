# Plan d'implémentation — Règles de conception MMORPG

## Contexte

Le game design d'Amethyste-Idle évolue avec des règles structurantes pour le gameplay : 8 éléments, compétences multi-domaines, sorts comme base de toute action, materia liées aux compétences, slots d'équipement comme capacités de combat, items soulbound, bestiaire et succès. Ce plan transforme ces règles en implémentation technique, ordonnée par dépendances.

**État actuel** : 7 éléments (manque metal/bete), compétences liées à 1 seul domaine (ManyToOne), sorts sans niveau ni type de valeur, pas de soulbound, pas de bestiaire ni de succès.

---

## Phase 1 — Enum Element centralisé + 2 nouveaux éléments [Taille: S]

**Problème** : Les éléments sont dupliqués en constantes dans `Spell.php`, `Item.php`, `StatusEffect.php`. Il manque `metal` et `beast`.

### Fichiers à modifier
- **Créer** `src/Enum/Element.php` — PHP 8.4 backed enum (string)
  ```
  none, fire, water, earth, air, light, dark, metal, beast
  ```
- **Modifier** `src/Entity/Game/Spell.php` — Remplacer les constantes ELEMENT_* par l'enum, ajouter les 2 nouveaux
- **Modifier** `src/Entity/Game/Item.php` — Idem, supprimer constantes dupliquées
- **Modifier** `src/Entity/Game/StatusEffect.php` — Idem
- **Modifier** `src/GameEngine/Fight/ElementalSynergyCalculator.php` — Ajouter synergies metal/beast
- **Modifier** `src/GameEngine/Fight/SpellApplicator.php` — Adapter aux enum
- **Modifier** fixtures : `SpellFixtures`, `StatusEffectFixtures`, `ItemFixtures` — Ajouter sorts/effets metal et beast
- **Migration** Doctrine : aucune (les colonnes element restent string, les valeurs changent)

### Tests
- Test unitaire `ElementTest` : valider les 9 valeurs
- Test unitaire `ElementalSynergyCalculator` : nouvelles synergies
- PHPStan + CS-Fixer

---

## Phase 2 — Spell : niveau + type de valeur [Taille: S]

**Problème** : Les sorts n'ont pas de niveau ni de type de valeur (fixe vs pourcentage).

### Fichiers à modifier
- **Modifier** `src/Entity/Game/Spell.php` :
  - Ajouter `level` (int, default 1) — puissance + comparaison toucher vs niveau mob
  - Ajouter `valueType` (string: 'fixed'|'percent', default 'fixed')
- **Modifier** `src/GameEngine/Fight/SpellApplicator.php` — Calcul dégâts/soin : si `percent`, appliquer en % des PV max
- **Modifier** `src/GameEngine/Fight/FightCalculator.php` — Hit chance : comparaison `spell.level` vs `monster.level`
- **Modifier** `src/DataFixtures/SpellFixtures.php` — Ajouter les niveaux aux sorts existants
- **Migration** Doctrine : ALTER TABLE game_spells ADD level, ADD value_type

### Tests
- Test unitaire `SpellApplicator` : dégâts fixes vs pourcentage
- Test unitaire `FightCalculator` : hit chance avec niveaux

---

## Phase 3 — Side effects enrichis (buff/debuff/HoT/DoT) [Taille: M]

**Problème** : Les StatusEffect ont un type rigide. Le design demande buff/debuff/HoT/DoT avec fréquence et expiration.

**Décision** : Système hybride — tours en combat + datetime hors combat (buffs de nourriture, etc.).

### Fichiers à modifier
- **Modifier** `src/Entity/Game/StatusEffect.php` :
  - Ajouter `category` (string: 'buff'|'debuff'|'hot'|'dot') — classification métier
  - Ajouter `frequency` (int, nullable) — tours entre chaque tick (default: 1 = chaque tour)
  - Garder `duration` en tours pour le combat
  - Ajouter `realTimeDuration` (int, nullable) — durée en secondes pour les effets hors combat (buff nourriture, potion, etc.)
- **Modifier** `src/Entity/App/FightStatusEffect.php` — Ajouter `lastTickTurn` pour gérer la fréquence
- **Créer** `src/Entity/App/PlayerStatusEffect.php` — Effets persistants hors combat :
  - `player` (ManyToOne Player)
  - `statusEffect` (ManyToOne StatusEffect)
  - `expiresAt` (datetime) — calculé à partir de `realTimeDuration`
  - `appliedAt` (datetime)
- **Modifier** `src/GameEngine/Fight/StatusEffectManager.php` — Tick selon fréquence + charger les effets persistants du joueur en début de combat
- **Modifier** `src/DataFixtures/Game/StatusEffectFixtures.php` — Ajouter category, frequency et realTimeDuration
- **Migration** Doctrine : ALTER TABLE + CREATE TABLE player_status_effects

### Tests
- Test unitaire StatusEffectManager : tick avec fréquence 1, 2, 3
- Test unitaire PlayerStatusEffect : expiration datetime
- Test intégration : buff nourriture persiste hors combat, appliqué en combat

---

## Phase 4 — Compétences multi-domaines [Taille: L] ⚠️ BREAKING CHANGE

**Problème** : `Skill.domain` est ManyToOne (1 domaine). Le design exige ManyToMany + auto-apprentissage cross-domaine + XP dans les deux domaines.

### Stratégie de migration
1. Créer la table pivot `skill_domain` (skill_id, domain_id)
2. Migrer les données : pour chaque skill, insérer (skill.id, skill.domain_id)
3. Supprimer la colonne `domain_id` de game_skills
4. Adapter tout le code

### Fichiers à modifier
- **Modifier** `src/Entity/Game/Skill.php` :
  - Remplacer `$domain` (ManyToOne) par `$domains` (ManyToMany)
  - Ajouter `getDomains(): Collection`, `addDomain()`, `removeDomain()`
  - Garder `getDomain()` comme raccourci → premier domaine (rétrocompat)
- **Modifier** `src/Entity/Game/Domain.php` — Adapter la relation inverse
- **Modifier** `src/GameEngine/Progression/SkillAcquiring.php` :
  - Si le joueur possède la compétence dans un autre domaine → auto-unlock gratuit
  - Déduire les points du bon DomainExperience
- **Créer** `src/GameEngine/Progression/CrossDomainSkillResolver.php` :
  - `checkAutoUnlock(Player, Skill)` — Vérifie si la compétence est déjà acquise dans un autre domaine
  - `grantXpToAllDomains(Player, Skill, int xp)` — Distribue 100% de l'XP à CHAQUE domaine de la compétence (récompense la polyvalence)
- **Modifier** `src/GameEngine/Fight/CombatSkillResolver.php` — Adapter getAvailableSkillsForCombat
- **Modifier** `src/GameEngine/Fight/MateriaXpGranter.php` — XP aux domaines multiples
- **Modifier** `src/Helper/PlayerSkillHelper.php` — Adapter les vérifications
- **Modifier** `src/DataFixtures/Game/SkillFixtures.php` — Définir les compétences partagées entre domaines
- **Modifier** `src/Controller/Game/SkillController.php` — Adapter l'affichage des arbres
- **Modifier** templates skills — Afficher les domaines multiples
- **Migration** Doctrine : CREATE skill_domain + data migration + DROP domain_id

### Prérequis de compétences enrichis
- **Modifier** `src/Entity/Game/Skill.php` — La relation `requirements` (ManyToMany self) existe déjà
- **Modifier** `src/GameEngine/Progression/SkillAcquiring.php` — Vérifier aussi :
  - Items en possession (`Item.requirements` existe déjà via ManyToMany)
  - Compétences d'autres arbres (déjà possible via `requirements`)
  - Pas de nouveau champ nécessaire, la mécanique existe déjà dans le modèle

### Tests
- Test unitaire `CrossDomainSkillResolver` : auto-unlock, XP multi-domaine
- Test unitaire `SkillAcquiring` : migration ManyToMany, prérequis cross-tree
- Test d'intégration : parcours complet apprentissage multi-domaine

---

## Phase 5 — Tout est un sort (armes + consommables) [Taille: M]

**Problème** : Les armes ont déjà un sort (via `Item.spell`). Les consommables utilisent un système effet JSON. Uniformiser.

### Fichiers à modifier
- **Modifier** `src/Entity/Game/Item.php` :
  - S'assurer que TOUS les items d'action (stuff, gear weapon) ont un `spell` associé
  - Ajouter `boundToPlayer` (bool, default false) — soulbound
- **Modifier** `src/GameEngine/Item/ItemEffectEncoder.php` — Simplifier : l'action `use_spell` devient la norme
- **Modifier** `src/Controller/Game/Inventory/UseItemController.php` — Consommables : cast spell puis destroy
- **Modifier** fixtures `stuff.yaml` — Chaque potion/nourriture → sort associé (heal, buff, etc.)
- **Créer** sorts pour consommables dans `SpellFixtures` : `potion-heal-minor`, `potion-heal-major`, `food-mushroom`, etc.
- **Modifier** `src/GameEngine/Fight/Handler/PlayerAttackHandler.php` — Déjà basé sur weapon.spell, vérifier cohérence

### Soulbound
- **Modifier** `src/Entity/Game/Item.php` — Ajouter `boundToPlayer` (bool)
- **Modifier** `src/Entity/App/PlayerItem.php` — Ajouter `boundToPlayerId` (int, nullable) — lie l'instance au joueur
- **Modifier** templates inventaire — Afficher icône "lié" sur les items bound
- **Modifier** futures interfaces de trade/vente — Bloquer les items bound

### Tests
- Test unitaire : consommable cast sort + destruction
- Test unitaire : soulbound empêche transfert

---

## Phase 6 — Materia & Slots = Capacités de combat [Taille: L]

**Problème** : Les slots de materia sur l'équipement existent mais ne déterminent pas les capacités disponibles en combat. Le design dit : nombre de slots materia équipés = nombre de sorts disponibles.

### Fichiers à modifier
- **Créer** `src/GameEngine/Fight/CombatCapacityResolver.php` :
  - `getAvailableSpells(Player): array<Spell>` — Parcourt l'équipement porté → slots → materia → spells
  - Bonus si élément materia = élément slot (dégâts +X%, XP +X%)
  - L'attaque physique de l'arme est TOUJOURS disponible gratuitement (ne compte pas dans les slots)
  - Le joueur a donc : 1 attaque arme + N sorts (N = nombre total de materia slots équipées)
- **Modifier** `src/GameEngine/Fight/CombatSkillResolver.php` — Intégrer CombatCapacityResolver pour les sorts disponibles
- **Modifier** `src/Controller/Game/FightController.php` — Barre d'actions = sorts des materia équipées
- **Modifier** `src/GameEngine/Fight/SpellApplicator.php` — Bonus élémentaire slot/materia matching
- **Modifier** `src/GameEngine/Fight/MateriaXpGranter.php` — Bonus XP si slot match
- **Modifier** `src/Helper/PlayerItemHelper.php` — `canEquipMateria()` : vérifier que le joueur a la compétence requise
- **Modifier** templates combat — Afficher dynamiquement les sorts disponibles selon materia
- **Modifier** templates équipement — Afficher les slots avec leur élément et le bonus de matching

### Validation materia
- Le joueur doit avoir appris la compétence correspondante dans un domaine pour équiper une materia
- Exemple : materia "Flammèche" (feu niv1) → compétence "pyro-materia-1" requise
- Ceci est déjà partiellement en place via `Item.requirements` (ManyToMany Skill)

### Tests
- Test unitaire `CombatCapacityResolver` : sorts disponibles selon équipement
- Test unitaire : bonus matching slot/materia
- Test intégration : combat avec sorts issus des materia

---

## Phase 7 — Inventaire : groupement visuel + items individuels [Taille: S]

**Problème** : Les items sont déjà stockés individuellement (PlayerItem). Il faut un groupement visuel dans l'UI.

### Fichiers à modifier
- **Modifier** `src/Controller/Game/Inventory/ItemsController.php` — Grouper par `genericItem.slug` pour l'affichage
- **Modifier** `templates/game/inventory/items/_list.html.twig` — Afficher "Potion de soin x3" avec dépliage
- **Modifier** `templates/game/inventory/materia/_list.html.twig` — Idem pour materia identiques
- Pas de changement de modèle de données : les items restent individuels en base

### Tests
- Test fonctionnel : vérifier le groupement visuel correct

---

## Phase 8 — Dashboard enrichi (PNJ/Mob/Joueurs par zone) [Taille: S]

**Problème** : Le dashboard admin ne montre pas la répartition par carte/zone.

### Fichiers à modifier
- **Modifier** `src/Controller/Admin/DashboardController.php` — Ajouter requêtes :
  - COUNT(pnj) GROUP BY map
  - COUNT(mob WHERE diedAt IS NULL) GROUP BY map
  - COUNT(player WHERE online) GROUP BY map
- **Modifier** `templates/admin/dashboard.html.twig` — Ajouter section "Répartition par zone" avec tableau

### Tests
- Test fonctionnel : dashboard affiche les compteurs par zone

---

## Phase 9 — Bestiaire joueur [Taille: M]

**Problème** : Aucun tracking des monstres rencontrés par le joueur.

### Fichiers à créer/modifier
- **Créer** `src/Entity/App/PlayerBestiary.php` :
  - `player` (ManyToOne Player)
  - `monster` (ManyToOne Monster)
  - `killCount` (int, default 0)
  - `firstEncounteredAt` (datetime)
  - `firstKilledAt` (datetime, nullable)
- **Créer** `src/Repository/App/PlayerBestiaryRepository.php`
- **Créer** `src/EventListener/BestiaryListener.php` — Écoute `MobDeadEvent` pour incrémenter killCount
- **Créer** `src/Controller/Game/BestiaryController.php` — Route `/game/bestiary`
- **Créer** `templates/game/bestiary/index.html.twig` — Liste monstres rencontrés avec stats, faiblesses (débloquées par paliers)
- **Modifier** `src/Entity/App/Player.php` — Ajouter relation `bestiary` (OneToMany)
- **Migration** Doctrine : CREATE TABLE player_bestiary

### Paliers de découverte (depuis la roadmap existante)
- 10 kills : faiblesses élémentaires révélées
- 50 kills : table de loot complète visible
- 100 kills : titre "Chasseur de [monstre]"

### Tests
- Test unitaire `BestiaryListener`
- Test fonctionnel : bestiaire se remplit après combat

---

## Phase 10 — Système de succès/achievements [Taille: M]

**Problème** : Aucun système de succès n'existe.

### Fichiers à créer/modifier
- **Créer** `src/Entity/App/Achievement.php` :
  - `slug` (string, unique)
  - `title` (string)
  - `description` (text)
  - `category` (string: 'combat', 'quest', 'exploration', 'craft', 'discovery')
  - `criteria` (JSON) — ex: `{"type": "mob_kill", "monster_slug": "goblin", "count": 100}`
  - `reward` (JSON, nullable) — ex: `{"xp": 50, "title": "Goblin Slayer"}`
  - `icon` (string, nullable)
- **Créer** `src/Entity/App/PlayerAchievement.php` :
  - `player` (ManyToOne Player)
  - `achievement` (ManyToOne Achievement)
  - `progress` (int, default 0)
  - `completedAt` (datetime, nullable)
  - `unlockedAt` (datetime)
- **Créer** `src/GameEngine/Achievement/AchievementTracker.php` :
  - Écoute les événements domaine (MobDeadEvent, QuestCompletedEvent, etc.)
  - Met à jour la progression
  - Déclenche `AchievementUnlockedEvent` quand complété
- **Créer** `src/Controller/Game/AchievementController.php` — Route `/game/achievements`
- **Créer** `templates/game/achievements/index.html.twig` — Grille de succès avec progression
- **Créer** `src/DataFixtures/AchievementFixtures.php` — Succès initiaux
- **Migration** : CREATE TABLE achievements + player_achievements

### Succès initiaux
- Nombre de quêtes effectuées (5, 10, 25, 50)
- Nombre de mobs tués par type (10, 50, 100 par monstre)
- Nombre de monstres découverts (5, 10, tous)

### Tests
- Test unitaire `AchievementTracker`
- Test intégration : succès débloqué après kill

---

## Phase 11 — Mise à jour ROADMAP.md et DOCUMENTATION.md [Taille: S]

- Mettre à jour `ROADMAP.md` — Marquer les items implémentés
- Mettre à jour `DOCUMENTATION.md` — Documenter les nouveaux systèmes
- Mettre à jour `AGENTS.md` — Nouvelles conventions (enum Element, multi-domaine)

---

## Ordre d'exécution et dépendances

```
Phase 1 (Enum Element)
  ↓
Phase 2 (Spell niveau/valueType)
  ↓
Phase 3 (StatusEffect enrichi)
  ↓
Phase 4 (Skills multi-domaines) ← dépend de Phase 1 pour metal/beast domains
  ↓
Phase 5 (Tout est un sort + Soulbound) ← dépend de Phase 2
  ↓
Phase 6 (Materia = Capacités combat) ← dépend de Phase 4 + 5
  ↓
Phase 7 (Inventaire groupement visuel) — indépendant, peut être parallélisé
  ↓
Phase 8 (Dashboard) — indépendant, peut être parallélisé
  ↓
Phase 9 (Bestiaire) — indépendant après Phase 1
  ↓
Phase 10 (Succès) ← dépend de Phase 9 pour critères bestiary
  ↓
Phase 11 (Documentation)
```

**Parallélisables** : Phases 7, 8 et 9 peuvent être faites en parallèle des phases 4-6.

---

## Vérification end-to-end

Pour chaque phase :
1. `docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff`
2. `docker compose exec php vendor/bin/phpstan analyse`
3. `docker compose exec php vendor/bin/phpunit`
4. `docker compose exec php php bin/console doctrine:migrations:migrate`
5. `docker compose exec php php bin/console doctrine:fixtures:load` (dev)
6. Test manuel en jeu : vérifier le flux complet (combat, équipement, inventaire)

---

## Résumé des estimations

| Phase | Description | Taille | Breaking? |
|-------|------------|--------|-----------|
| 1 | Enum Element + metal/beast | S | Non (rétrocompat) |
| 2 | Spell niveau + valueType | S | Non |
| 3 | StatusEffect enrichi | M | Non |
| 4 | Skills multi-domaines | L | **OUI** (migration BDD) |
| 5 | Tout est un sort + Soulbound | M | Non |
| 6 | Materia = Capacités combat | L | Partiel (combat change) |
| 7 | Inventaire groupement UI | S | Non |
| 8 | Dashboard zones | S | Non |
| 9 | Bestiaire | M | Non |
| 10 | Succès/Achievements | M | Non |
| 11 | Documentation | S | Non |
