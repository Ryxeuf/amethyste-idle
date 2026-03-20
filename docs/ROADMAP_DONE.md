# Roadmap realisee — Amethyste-Idle

> Historique des phases completees. Ce fichier est la reference pour tout ce qui a ete implemente.
> Derniere mise a jour : 2026-03-21

---

## Modernisation de la stack (2026-03-09) ✅

> Refonte complete de l'infrastructure technique.

| Tache | Detail |
|-------|--------|
| Migration Doctrine ORM 3.6 / DBAL 4.4 | 22 entites migrees, config nettoyee |
| Migration Tailwind CSS v3 → v4.1 | Config CSS-native, suppression tailwind.config.js |
| Suppression Node.js | Retrait complet de l'image Docker |
| Correction Mercure | URL dynamique, Turbo Streams active |
| Controller Stimulus Mercure | Remplacement du script brut move-listener.js |
| Refactoring deplacement | Suppression usleep(250ms), chemin complet en 1 event |
| Remplacement Typesense → PostgreSQL | Cache Symfony, suppression service Docker |
| Remplacement cron-bundle → Symfony Scheduler | Composant natif Symfony |
| Docker : 4 services → 2 services | Suppression typesense + worker async |

**Stack finale** : PHP 8.3 + Symfony 7.2.9 + FrankenPHP + PostgreSQL 16 + Doctrine ORM 3.6.2 + Tailwind v4.1 + Mercure SSE

---

## Phase 1 — Fondations techniques (2026-03-13) ✅

### 1.1 Pipeline Tiled ameliore ✅
- Import des Object Layers depuis TMX (mob_spawn, npc_spawn, portal, chest, harvest_spot)
- Validation automatique des maps (--validate)
- Auto-detection des tilesets et dimensions
- Support des proprietes personnalisees Tiled
- Mode dry-run (--dry-run)
- Statistiques detaillees (--stats)

### 1.2 Workflow de creation de cartes (partiel) ✅
- Conventions de layers documentees
- Commande d'import enrichie avec --sync-entities
- Commande de validation
- Systeme de portails (teleportation entre zones avec fade, particules, camera shake)

### 1.3 Systeme de sprites complet ✅
- SpriteAnimator format RPG Maker VX (3x4 single, 12x8 multi)
- Animation idle breathing (oscillation Y desynchronisee)
- Systeme d'emotes (!, ?, coeur, etoile, etc.)
- Etats d'animation (idle, walk, interact)

### 1.4 Boucle de jeu PixiJS ✅
- Ticker 60fps avec delta time, camera lerp
- Camera shake parametrable
- Cycle jour/nuit (overlay ambiant)
- Systeme de particules
- Fade transition pour changements de carte

### 1.5 Support mobile ✅
- Controles WASD/ZQSD + fleches
- Joystick virtuel 4 directions
- Retour haptique (vibration)
- Mode paysage CSS adaptatif
- Touch events unifies
- Responsive canvas (ResizeObserver)

### 1.6 Dialogues PNJ ✅
- Typewriter intelligent (pauses ponctuation)
- Navigation clavier (Espace/Entree/Echap)
- Animations slide-up/down
- Parser conditionnel (quest, has_item, domain_xp_min)
- Variables {{player_name}}, {{pnj_name}}
- Actions de choix (close, quest_offer, open_shop, next)
- Accessibilite ARIA

### 1.7 Performance ✅
- Tile sprite pool
- Entity container pool
- Spatial hash O(1)
- Texture cache (GID, couleur, sheet)
- Lazy loading + preload cells
- Pruning des cellules distantes
- Frame budget monitoring

### 1.8 Registre d'assets centralise ✅
- SpriteConfigProvider avec metadonnees
- Filtrage par categorie
- 30+ sprite sheets (7 joueurs, 12 monstres, 10 PNJ)

### 1.9 Accessibilite web ✅
- ARIA attributes (role, aria-label, aria-live)
- Hints clavier
- Backdrop blur pour lisibilite

### 1.10 Preview terrain et templates Tiled ✅
- Commande `app:terrain:preview --map=X` : genere un PNG a partir d'un fichier TMX
  - Support scale (0.25, 0.5, 1, 2), overlay collisions, overlay objets
  - Rendu complet multi-layers avec tous les tilesets
  - Mode `all` pour generer toutes les cartes d'un coup
- Templates de cartes Tiled pre-configures dans `terrain/templates/` :
  - `template_outdoor.tmx` — Zone exterieure 60x60 (4 tilesets, 5 layers, object group)
  - `template_indoor.tmx` — Interieur 20x20 (4 tilesets, 5 layers, object group)
  - `template_dungeon.tmx` — Grotte/donjon 60x30 (3 tilesets, 5 layers, object group)
  - Chaque template inclut les conventions de layers, les GID de reference, et des exemples d'objets commentes

---

## Phase 2 — Panel d'administration ✅

### 2.1 Infrastructure admin ✅
- Firewall admin (pattern /admin/*, role ROLE_ADMIN)
- Layout admin dedie avec sidebar
- Dashboard avec metriques cles
- Recherche et filtrage avec pagination

### 2.2 Gestion du contenu de jeu (CRUD complet) ✅
- Items, Monstres, Sorts, Competences, Domaines
- Quetes, PNJ, Recettes de craft, Tables de loot

### 2.3 Gestion des cartes ✅
- Visualisation des maps avec statistiques par zone
- Monitoring par zone (joueurs, mobs, PNJ)
- Gestion des spawns : placer/deplacer mobs et PNJ sur la carte via interface admin
- Gestion des portails : configurer les liens entre zones depuis l'admin (CRUD complet)
- Import de map : upload d'un fichier TMX depuis l'admin

### 2.4 Gestion des joueurs ✅
- Liste joueurs avec recherche et pagination
- Fiche joueur detaillee (stats, inventaire, quetes, progression)
- Actions admin (ban/unban, reset position, donner items/gils)
- Logs d'actions admin

### 2.5 Outils de maintenance ✅
- Mode maintenance activable depuis l'admin
- Logs d'administration
- Reload des fixtures selectif : commande CLI `app:fixtures:load-selective` (12 groupes : items, monsters, spells, skills, domains, mobs, pnjs, quests, maps, players, achievements, slots)
- Console Mercure : voir les topics connus, publier des messages de test depuis l'admin
- Planificateur d'evenements : entite GameEvent (boss_spawn, xp_bonus, drop_bonus, invasion, custom), CRUD admin, recurrence, filtrage par statut

---

## Game Design — Phases 1 a 13 ✅

### Phase GD-1 : Enum Element centralise ✅
- PHP 8.4 backed enum (none, fire, water, earth, air, light, dark, metal, beast)
- Migration des constantes ELEMENT_* vers l'enum

### Phase GD-2 : Systeme de race ✅
- Entite Race (slug, name, description, statModifiers)
- Race Humain (stats neutres)
- Assignation automatique a la creation

### Phase GD-3 : Spell niveau + valueType + calculators ✅
- Champs level et valueType (fixed/percent) sur Spell
- DamageCalculator, HitChanceCalculator, CriticalCalculator extraits

### Phase GD-4 : Side effects enrichis ✅
- StatusEffect avec category (buff/debuff/hot/dot) et frequency
- PlayerStatusEffect pour effets persistants hors combat
- StatusEffectManager avec tick selon frequence

### Phase GD-5 : Competences multi-domaines ✅ (BREAKING)
- Skill.domain ManyToOne → ManyToMany
- CrossDomainSkillResolver (auto-unlock, XP 100% par domaine)

### Phase GD-6 : Infrastructure 32 domaines + tous les arbres de talent ✅
- 32 domaines (24 combat + 4 recolte + 4 craft)
- 400+ competences avec 13-24 skills par domaine
- Sous-phases 6.A a 6.I toutes completees :
  - 6.A : Infrastructure + Pyromancien modele (15 skills)
  - 6.B : Feu (Berserker 15 + Artificier 15)
  - 6.C : Eau (Hydromancien 13 + Guerisseur 13 + Maremancien 13)
  - 6.D : Air (Foudromancien 13 + Archer 13 + Vagabond 13)
  - 6.E : Terre (Geomancien 24 + Defenseur 15 + Gardien 24)
  - 6.F : Metal (Soldat 15 + Chevalier 15 + Ingenieur 15)
  - 6.G : Bete (Chasseur 13 + Dompteur 13 + Druide 13)
  - 6.H : Lumiere + Ombre (Paladin, Pretre, Inquisiteur, Assassin, Necromancien, Sorcier — 13 chacun)
  - 6.I : Recolte + Craft (8 domaines x 15 skills) + 8 skills partages multi-domaines

### Phase GD-7 : Tout est un sort + Soulbound ✅
- boundToPlayer sur items
- use_spell comme norme d'action pour consommables
- Icone "lie" sur items bound en inventaire

### Phase GD-8 : Materia = Capacites de combat (partiel) ✅
- CombatCapacityResolver cree (sorts = materia equipees)
- Attaque arme TOUJOURS disponible gratuitement
- Bonus matching element slot/materia (+25% degats, +25% XP)
- **Non fait** : verification actions.materia.unlock avant autorisation sort (→ Phase 14)

### Phase GD-9 : Inventaire groupement visuel ✅
- Groupement par genericItem.slug avec comptage
- Badge "x3" avec compteur, grille responsive

### Phase GD-10 : Dashboard enrichi ✅
- Statistiques par zone (PNJ, mobs, joueurs)
- Section "Repartition par zone" dans l'admin

### Phase GD-11 : Bestiaire joueur ✅
- Entite PlayerBestiary (killCount, tiers 10/50/100)
- BestiaryListener sur MobDeadEvent
- Route /game/bestiary avec stats, badges, barres de progression

### Phase GD-12 : Systeme de succes ✅
- 34+ achievements (24 combat, 3 exploration, 4+ quetes)
- AchievementTracker (ecoute MobDeadEvent + QuestCompletedEvent)
- Route /game/achievements avec onglets par categorie
- Recompenses gils + titres

### Phase GD-13 : Mise a jour documentation ✅
- ROADMAP.md, DOCUMENTATION.md, AGENTS.md, CLAUDE.md mis a jour

---

## Combat enrichi — Elements deja implementes ✅

> Ces fonctionnalites etaient listees dans le TODO mais sont deja presentes dans le code.

### Synergies elementaires ✅
- 8 combos bidirectionnels dans `ElementalSynergyCalculator` (Eau+Feu=Vapeur, Terre+Air=Tempete, Lumiere+Ombre=Eclipse, Feu+Terre=Explosion florale, Metal+Feu=Forge, Metal+Lumiere=Lame sacree, Bete+Terre=Furie primale, Bete+Ombre=Ombre venimeuse)
- Multiplicateurs de degats (1.2x a 2.5x), procs de statut, self-damage (Eclipse)
- Tracking du dernier element utilise dans `Fight.lastElementUsed`

### Materia Fusion ✅
- `MateriaFusionManager` : upgrade same-element (niveaux 1→5) + 14 fusions cross-element
- Interface fusion dans l'inventaire

### Materia XP ✅
- `MateriaXpGranter` : XP sur MobDeadEvent (10 × niveau monstre, ×5 boss, ×1.25 element match)

### Statuts alteres (8/8) ✅
- `StatusEffectManager` (472 lignes) : poison, paralysie, brulure, gel, silence, regeneration, bouclier, berserk
- 14 effets dans `StatusEffectFixtures` (variantes normales, fortes, lentes, persistantes hors combat)
- DoT/HoT avec frequence configurable, stat modifiers JSON, absorption bouclier
- Effets persistants hors combat (`PlayerStatusEffect`)
- Badges visuels colores par type dans le template combat (`index.html.twig`)

### Resistances elementaires par monstre ✅
- Champ `elementalResistances` JSON sur `Monster`
- Appliquees dans `DamageCalculator.applyElementalResistance()`
- Renseignees sur les monstres existants (Golem, Dragon, etc.)

### IA monstres — patterns et alertes ✅
- `MobActionHandler` : sequences d'actions JSON, spell_chance, low_hp_heal, role (healer)
- Alertes de danger : `danger_alert` dans aiPattern + `danger_message` dans bossPhases

### Boss — phases et cooldown ✅
- `Monster.bossPhases` JSON : phases par seuil HP, actions specifiques, danger_message
- `MobDeathQueuing` : respawn boss 3600s (1h), normal 10s
- Dragon ancestral : 3 phases avec sorts preferes

---

## Systemes existants fonctionnels

| Systeme | Detail |
|---------|--------|
| Carte PixiJS | 9 zones (3x3), pathfinding Dijkstra, camera fluide, culling |
| Deplacement temps reel | Mercure SSE, animation sprites, sync multi-joueurs |
| Combat tour par tour | Timeline, attaque, sorts, items, fuite, loot |
| Inventaire | Sac (100), Materia (50), Banque (1000), equipement 12 slots |
| Systeme Materia | Sertissage, 9 elements, slots sur equipement |
| Arbres de talent | 32 domaines, 13-24 skills par domaine, XP par domaine |
| Monstres | 12 types, tables de loot, respawn par queue |
| Quetes | 10 quetes (tuer/collecter), recompenses |
| PNJ & Dialogues | 60 PNJ, dialogues conditionnels, branches |
| Recolte (basique) | Minage et herboristerie basiques (peche/depecage non actifs) |
| Auth | Login/register, roles USER/PLAYER/ADMIN |
| Race | Race Humain (stats neutres), extensible |
| Items soulbound | boundToPlayer, use_spell |
| Bestiaire | 3 paliers (10/50/100 kills), faiblesses, loot, titres |
| Succes | 34+ achievements, recompenses gils+titres |
| Inventaire groupe | Groupement par slug, badge "x3" |
| Administration | Panel admin complet (dashboard enrichi, CRUD, logs, maintenance) |
| CI/CD | GitHub Actions (lint, PHPStan, PHPUnit, build Docker) + deploy auto sur main |
