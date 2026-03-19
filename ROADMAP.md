# ROADMAP — Amethyste-Idle

> MMORPG navigateur web — Univers médiéval-fantastique-futuriste (FF7/8/9 × Zelda × stein.world)
> Dernière mise à jour : 2026-03-18

> **Voir aussi** : [docs/GAME_DESIGN_ROADMAP.md](docs/GAME_DESIGN_ROADMAP.md) — Plan d'implémentation détaillé des nouvelles règles de game design (éléments, multi-domaine, materia=capacités, bestiaire, succès)

---

## Vue d'ensemble

Cette roadmap couvre toutes les fonctionnalités nécessaires pour transformer Amethyste-Idle d'un prototype fonctionnel en un jeu complet, engageant et rejouable. Elle est organisée en **12 phases** allant des fondations techniques au contenu endgame.

**Philosophie de design :**
- Pas de niveau global → la puissance vient des arbres de talent et de l'équipement
- Chaque activité doit être satisfaisante en elle-même (récolter, crafter, combattre, explorer)
- Le monde doit sembler vivant : PNJ avec des routines, météo, cycle jour/nuit
- La progression doit offrir des choix significatifs, pas juste des chiffres qui montent
- Le craft doit être un vrai pilier du jeu, pas un à-côté

---

## État actuel — Ce qui fonctionne (Mars 2026)

| Système | État | Détail |
|---------|------|--------|
| Carte PixiJS | ✅ Fait | 9 zones (3×3), pathfinding Dijkstra, camera fluide, culling |
| Déplacement temps réel | ✅ Fait | Mercure SSE, animation sprites, sync multi-joueurs |
| Combat tour par tour | ✅ Fait | Timeline, attaque, sorts, items, fuite, loot |
| Inventaire | ✅ Fait | Sac (100), Materia (50), Banque (1000), équipement 12 slots |
| Système Materia | ✅ Fait | Sertissage, 7 éléments, slots sur équipement |
| Arbres de talent | ✅ Fait | 32 domaines (8 éléments), Pyromancien modèle (15 skills), XP par domaine |
| Monstres | ✅ Fait | 12 types, tables de loot, respawn par queue |
| Quêtes | ✅ Fait | 10 quêtes (tuer/collecter), récompenses |
| PNJ & Dialogues | ✅ Fait | 60 PNJ, dialogues conditionnels, branches |
| Récolte | 🟡 Partiel | Minage et herboristerie basiques, pêche/dépeçage non actifs |
| Sprites animés | ✅ Fait | SpriteAnimator, format RPG Maker VX, direction persistée, breathing idle, émotes, états |
| Mobile | ✅ Fait | Responsive, tap-to-move, pointer events, WASD/ZQSD, joystick virtuel, haptic feedback |
| Boucle de jeu | ✅ Fait | Ticker 60fps, camera lerp, shake, cycle jour/nuit, système de particules |
| Dialogues PNJ | ✅ Fait | Typewriter avec ponctuation, clavier (Espace/Entrée/Échap), animation slide, choix animés |
| Pipeline Tiled | ✅ Fait | Import TMX, object layers, validation, dry-run, statistiques détaillées |
| Assets | ✅ Fait | Registre centralisé, métadonnées sprites, catégories, 30+ sprite sheets |
| Performance | ✅ Fait | Tile pool, entity pool, spatial hash, texture cache, marker cache, monitoring FPS |
| Auth | ✅ Fait | Login/register, rôles USER/PLAYER/ADMIN |
| Accessibilité | ✅ Fait | ARIA labels, rôles WAI-ARIA, mode landscape, hints clavier |
| Crafting | 🔴 Absent | Domaines définis, aucune mécanique ni UI |
| Commerce | 🔴 Absent | Pas de boutiques PNJ ni échanges joueurs |
| Administration | ✅ Fait | Panel admin complet (dashboard, CRUD, logs, maintenance) |

---

## Phase 1 — Fondations techniques ✅ *Terminée*

> Solidifier les bases avant d'ajouter du contenu.

### 1.1 Pipeline Tiled amélioré ✅
- [x] Import des Object Layers depuis TMX (types : `mob_spawn`, `npc_spawn`, `portal`, `chest`, `harvest_spot`)
- [x] Validation automatique des maps (`--validate`) : tilesets présents, collisions cohérentes, spawns accessibles
- [x] Auto-détection des tilesets et dimensions depuis le fichier TMX
- [x] Support des propriétés personnalisées Tiled (metadata sur les objets)
- [x] Mode **dry-run** (`--dry-run`) : analyse complète sans écriture de fichiers
- [x] **Statistiques détaillées** (`--stats`) : tableau récapitulatif (cells walkable/mur, layers, tilesets, objets)

### 1.2 Workflow de création de cartes ✅
- [x] **Conventions de layers** documentées (ground, ground_overlay, objects, objects_overlay, collisions, object layers)
- [x] **Commande d'import enrichie** : `app:terrain:import --all --sync-entities` parse les object layers et crée automatiquement les entités (Mob, Pnj, ObjectLayer) en base
- [x] **Commande de validation** : `app:terrain:import --validate` vérifie la cohérence des maps (spawns walkable, portails complets, tilesets existants)
- [x] **Système de portails** : téléportation entre zones avec fade transition, chargement nouvelle zone via API, particules visuelles, camera shake
- [ ] **Commande de preview** : `app:terrain:preview --map=X` génère une image PNG de la map pour vérification rapide
- [ ] **Templates de cartes Tiled** : fichiers .tmx pré-configurés (template_outdoor.tmx, template_indoor.tmx, template_dungeon.tmx)

### 1.3 Système de sprites complet ✅
- [x] **SpriteAnimator** : format RPG Maker VX (3×4 single, 12×8 multi), direction persistée
- [x] **Animation idle breathing** : oscillation subtile Y quand le personnage est immobile (désynchronisée entre sprites)
- [x] **Système d'émotes** : bulles au-dessus des sprites (!, ?, ♥, ✦, ~, ♪, …, ★) avec animation flottante et fade-out
- [x] **États d'animation** : idle, walk, interact avec vitesse d'animation adaptative
- [x] **Émote API** : `showEmote(type, duration)` / `hideEmote()` disponible sur chaque SpriteAnimator

### 1.4 Boucle de jeu PixiJS ✅
- [x] **Ticker 60fps** : game loop avec delta time, camera lerp (0.15), animation sprites
- [x] **Camera shake** : effet de tremblement paramétrable (intensité, durée, décroissance) — déclenché lors des portails
- [x] **Cycle jour/nuit** : overlay ambiant qui change selon l'heure réelle (jour, aube, crépuscule, nuit) — vérification toutes les 60s
- [x] **Système de particules** : spawner des particules avec couleur, durée, spread, vélocité — utilisé sur les portails
- [x] **Fade transition** : fondu noir pour les changements de carte

### 1.5 Support mobile ✅
- [x] **Contrôles clavier WASD/ZQSD** : support clavier AZERTY et QWERTY en plus des flèches
- [x] **Joystick virtuel** : 4 directions, deadzone, repeat-on-hold, visible uniquement sur touch devices
- [x] **Retour haptique** : vibration légère sur mouvement et portails (`navigator.vibrate`)
- [x] **Mode paysage** : CSS adaptatif pour écrans en orientation landscape (padding réduit, titre masqué)
- [x] **Touch events** : pointer down/move/up unifié, swipe detection (30px threshold, 400ms window)
- [x] **Responsive canvas** : ResizeObserver, aspect-ratio 1:1, max 672px, redimensionnement dynamique

### 1.6 Dialogues PNJ ✅
- [x] **Typewriter intelligent** : pauses sur ponctuation (×6 pour `.!?`, ×3 pour `,`), vitesse configurable
- [x] **Navigation clavier** : Espace/Entrée pour avancer, Échap pour fermer
- [x] **Animations** : slide-up à l'ouverture, slide-down à la fermeture, choix avec fade-in échelonné
- [x] **Parser conditionnel** : quest, quest_not, quest_active, has_item, domain_xp_min
- [x] **Variables** : substitution `{{player_name}}`, `{{pnj_name}}` dans le texte
- [x] **Actions de choix** : close, quest_offer, open_shop, next (branching)
- [x] **Accessibilité** : ARIA roles (dialog, polite live region), backdrop-blur, hints clavier

### 1.7 Performance ✅
- [x] **Tile sprite pool** : réutilisation des sprites de tuiles (`_acquireSprite` / `_releaseSprite`)
- [x] **Entity container pool** : réutilisation des conteneurs d'entités (`_acquireEntityContainer` / `_releaseEntityContainer`)
- [x] **Spatial hash** : lookup O(1) des entités par position pour les interactions PNJ
- [x] **Texture cache** : cache par GID pour les tuiles, par couleur pour les markers, par sheet pour les sprites
- [x] **Lazy loading** : chargement d'entités tous les 5 tiles, preload cells tous les 10 pas
- [x] **Pruning** : suppression des cellules distantes (3× radius)
- [x] **Frame budget monitoring** : compteur de slow frames (>25ms) pour diagnostics de performance

### 1.8 Registre d'assets centralisé ✅
- [x] **SpriteConfigProvider** avec métadonnées : catégorie (player/mob/pnj), animations supportées, taille de frame
- [x] **Registre complet** : `getRegistry()` retourne toutes les sprites avec métadonnées
- [x] **Filtrage par catégorie** : `getKeysByCategory('mob')` pour accès ciblé
- [x] **30+ sprite sheets** : 7 joueurs, 12 monstres, 10 PNJ avec sprites variés par classe

### 1.9 Accessibilité web ✅
- [x] **ARIA attributes** : `role="application"`, `role="dialog"`, `aria-label`, `aria-live="polite"`
- [x] **Hints clavier** : affichage des raccourcis dans l'interface (Espace, Entrée, Échap)
- [x] **Backdrop blur** : lisibilité améliorée de la boîte de dialogue sur fond de carte

---

## Phase 2 — Panel d'administration ✅ *Terminée*

> Permettre aux admins de gérer tout le jeu sans toucher au code ni aux fixtures.

### 2.1 Infrastructure admin ✅
- [x] Activer le firewall admin dans `security.yaml` (pattern `/admin/*`, rôle `ROLE_ADMIN`)
- [x] Layout admin dédié (`templates/admin/base.html.twig`) : sidebar navigation, thème distinct du jeu
- [x] Dashboard admin avec métriques clés : joueurs en ligne, combats en cours, économie (or total en circulation), monstres actifs
- [x] Système de recherche et filtrage sur toutes les listes avec pagination

### 2.2 Gestion du contenu de jeu (CRUD complet) ✅
- [x] **Items** : créer/modifier/supprimer des items, définir type, rareté, stats, effet, icône, prix
- [x] **Monstres** : stats, sorts, tables de loot (avec probabilités), zones d'apparition
- [x] **Sorts** : nom, élément, dégâts/soin/toucher/critique, animation, coût en énergie
- [x] **Compétences** : gestion des talents, pré-requis, bonus
- [x] **Domaines** : gérer les domaines de progression, associer compétences et actions
- [x] **Quêtes** : éditeur de quêtes (objectifs JSON, récompenses JSON, PNJ donneur)
- [x] **PNJ** : position, sprite, dialogues (éditeur JSON), quêtes associées
- [x] **Recettes de craft** : ingrédients, résultat, compétence requise, niveau minimum, profession
- [x] **Tables de loot** : éditeur avec probabilités et monstre/item associés

### 2.3 Gestion des cartes ✅
- [x] **Visualisation des maps** : cartes avec statistiques par zone (joueurs, mobs, PNJ)
- [x] **Monitoring par zone** : nombre de joueurs, mobs vivants, PNJ présents
- [ ] **Gestion des spawns** : placer/déplacer mobs et PNJ sur la carte via interface visuelle
- [ ] **Gestion des portails** : configurer les liens entre zones
- [ ] **Import de map** : upload d'un fichier TMX depuis l'admin, import automatique

### 2.4 Gestion des joueurs ✅
- [x] Liste des joueurs avec recherche (nom, email, rôle) et pagination
- [x] Fiche joueur détaillée : stats, inventaire complet, quêtes actives/terminées, progression par domaine, talents
- [x] Actions admin : ban/unban, reset position, donner items, donner gils, changer rôle
- [x] Logs d'actions admin (qui a fait quoi, quand, IP) pour traçabilité

### 2.5 Outils de maintenance ✅
- [x] Mode maintenance activable depuis l'admin (redirige les joueurs avec message personnalisé, admins exemptés)
- [x] Logs d'administration avec recherche et pagination
- [ ] Reload des fixtures sélectif (items seulement, monstres seulement, etc.)
- [ ] Console Mercure : voir les topics actifs, publier des messages de test
- [ ] Planificateur d'événements : programmer des spawns de boss, des bonus XP temporaires

---

## Phase 3 — Système de combat enrichi

> Rendre les combats tactiques, profonds et satisfaisants.

### 3.1 Compétences actives en combat
- [ ] **Compétences liées aux arbres de talent** : chaque compétence débloquée dans l'arbre donne accès à une action en combat (pas seulement un bonus passif)
  - Pyromancien : Boule de feu → Pluie de flammes → Inferno
  - Soldat : Frappe puissante → Charge → Tourbillon d'épée
  - Soigneur : Soin mineur → Régénération → Résurrection
  - Défenseur : Parade → Bouclier magique → Mur de fer
  - Nécromancien : Drain de vie → Malédiction → Invocation squelette
  - Druide : Liane → Empoisonnement → Appel de la forêt
  - Mage blanc : Lumière → Purification → Jugement sacré
- [ ] **Barre d'actions dynamique** : en combat, les actions disponibles dépendent des compétences débloquées (le joueur personnalise son "build")
- [ ] **Coût en énergie** : chaque compétence consomme de l'énergie (ressource qui se régénère entre les combats)
- [ ] **Cooldowns** : certaines compétences puissantes ont un temps de recharge (X tours)

### 3.2 Synergies élémentaires et Materia
- [ ] **Combos élémentaires** : enchaîner des sorts de différents éléments crée des effets spéciaux
  - Eau + Feu = Vapeur (baisse précision ennemie)
  - Terre + Air = Tempête de sable (dégâts de zone)
  - Lumière + Ténèbres = Éclipse (dégâts massifs, affecte aussi le lanceur)
  - Nature + Feu = Explosion florale (poison + dégâts feu)
- [ ] **Materia Fusion** (inspiré FF7) : combiner 2 materias au repos pour créer une materia plus puissante
  - Feu Niv.1 + Feu Niv.1 = Feu Niv.2
  - Feu + Vent = Flamme tourbillonnante (sort unique)
  - Soin + Mort = Drain de vie
- [ ] **Materia XP** : les materias gagnent de l'expérience en combat et évoluent (3 niveaux par materia)
- [ ] **Slots de Materia liés** : certains équipements ont des slots connectés qui amplifient les materias adjacentes

### 3.3 Statuts altérés
- [ ] **Poison** : perd X% PV par tour (3 tours)
- [ ] **Paralysie** : chance de ne pas agir (50%, 2 tours)
- [ ] **Brûlure** : dégâts réduits de 25% + dégâts de feu par tour
- [ ] **Gel** : vitesse réduite de 50% (2 tours)
- [ ] **Silence** : impossible d'utiliser des sorts (3 tours)
- [ ] **Régénération** : récupère X PV par tour (3 tours)
- [ ] **Bouclier** : absorbe les X prochains points de dégâts
- [ ] **Berserk** : +50% dégâts, -30% défense, ne peut pas fuir
- [ ] Icônes visuelles sur la timeline de combat pour chaque statut actif
- [ ] Résistances élémentaires par monstre (un monstre de feu résiste au feu, faible à l'eau)

### 3.4 IA monstres améliorée
- [ ] **Patterns d'attaque** : les monstres ont des séquences d'actions (pas juste attaque aléatoire)
  - Ex: Troll → Charge (tour 1) → Frappe (tour 2-3) → Rage (quand PV < 30%)
- [ ] **Monstres soigneurs** : certains mobs soignent leurs alliés
- [ ] **Monstres invocateurs** : certains mobs appellent des renforts
- [ ] **Alertes de danger** : indicateur visuel quand un monstre prépare une attaque puissante ("Le dragon prend une grande inspiration...")

### 3.5 Boss et combats spéciaux
- [ ] **Mécaniques de boss** : phases multiples, attaques spéciales à esquiver/contrer
  - Dragon : Phase 1 (attaque) → Phase 2 (vol, souffle de feu zone) → Phase 3 (rage, attaques doubles)
- [ ] **Récompenses uniques** : chaque boss drop un équipement légendaire unique
- [ ] **Cooldown de boss** : un boss vaincu ne réapparaît qu'après un délai long (1h réel)
- [ ] **Indicateur de difficulté** : étoiles de difficulté avant d'engager le combat

---

## Phase 4 — Récolte complète

> Faire de la récolte une activité satisfaisante avec sa propre profondeur.

### 4.1 Quatre métiers de récolte
- [ ] **Minage** : minerais (fer, cuivre, argent, or, mithril, cristal améthyste)
  - Filons visibles sur la carte avec animation de brillance
  - Mini-jeu optionnel : timing pour qualité du minerai (normal → pur → parfait)
  - Filons rares qui n'apparaissent qu'à certaines heures
- [ ] **Herboristerie** : plantes (menthe, sauge, lavande, thym, romarin, mandragore, fleur lunaire)
  - Plantes qui poussent progressivement (3 stades visuels sur la carte)
  - Certaines plantes uniquement nocturnes (fleur lunaire)
  - Récolte délicate : chance de détruire la plante si compétence insuffisante
- [ ] **Pêche** : poissons (truite, saumon, carpe, poisson-lune, anguille électrique, kraken juvénile)
  - Points de pêche sur les cases d'eau
  - Mini-jeu de pêche : barre de tension (trop fort = ligne casse, trop faible = poisson s'échappe)
  - Poissons rares selon la zone et les conditions météo
- [ ] **Dépeçage** : cuirs et matériaux sur les monstres vaincus
  - Se déclenche après un combat gagné (bouton "Dépecer")
  - Qualité du cuir selon le niveau de compétence
  - Matériaux spéciaux sur les boss (écaille de dragon, fourrure de loup-garou)

### 4.2 Mécanique de récolte
- [ ] **Outils par métier** : pioche, faucille, canne à pêche, couteau de dépeçage
  - Outils améliorables par le craft (bronze → fer → acier → mithril)
  - Chaque tier d'outil débloque de nouvelles ressources
  - Durabilité : l'outil s'use et doit être réparé ou remplacé
- [ ] **Spots de récolte sur la carte** : icônes distinctes par type, animation quand le joueur récolte
- [ ] **Respawn des ressources** : temps variable selon la rareté (30s commun, 5min rare, 30min légendaire)
- [ ] **XP de récolte** : chaque récolte donne de l'XP dans le domaine correspondant
- [ ] **Découvertes** : première récolte d'une ressource = entrée dans le Catalogue (voir Phase 10)

---

## Phase 5 — Système de craft

> Le craft doit être un pilier majeur du jeu — pas un menu secondaire.

### 5.1 Quatre métiers d'artisanat
- [ ] **Forgeron** : armes et armures métalliques
  - Lingots (fondre minerais → lingots au fourneau)
  - Recettes : Épée en fer (2 lingots fer + 1 bois), Armure en acier (4 lingots acier + 2 cuirs), etc.
  - Spécialisation : armes OU armures (choix qui débloque des recettes exclusives)
- [ ] **Tanneur** : équipements en cuir, sacs, accessoires
  - Tannage : cuir brut → cuir traité (nécessite un atelier)
  - Recettes : Bottes en cuir (3 cuirs + 1 fil), Cape du rôdeur (5 cuirs + 2 fils + 1 teinture)
  - Spécialisation : armures légères OU accessoires
- [ ] **Alchimiste** : potions, poisons, enchantements
  - Distillation : combiner plantes dans un alambic
  - Recettes : Potion de soin (menthe + sauge), Potion de force (mandragore + minerai fer), Antidote (lavande + thym)
  - **Expérimentation** : le joueur peut tenter des combinaisons non-recettes → chance de découvrir une nouvelle recette OU de rater (perte des ingrédients + petit dégât)
  - Spécialisation : potions de soin OU potions offensives
- [ ] **Joaillier** : bijoux (anneaux, colliers, amulettes) et sertissage de materia
  - Taille de gemmes : minerai cristallin → gemme taillée
  - Recettes : Anneau de rubis (1 lingot or + 1 rubis taillé), Amulette de protection (1 lingot argent + 1 cristal améthyste)
  - Le joaillier peut ajouter des slots de materia aux équipements existants
  - Spécialisation : bijoux offensifs OU bijoux défensifs

### 5.2 Interface de craft
- [ ] **Atelier de craft** : interface dédiée accessible depuis les PNJ artisans ou établis dans le monde
- [ ] **Livre de recettes** : liste des recettes connues avec ingrédients et résultat (preview de l'item)
- [ ] **Recettes découvrables** : certaines recettes s'apprennent via des quêtes, des drops, ou l'expérimentation alchimique
- [ ] **Indicateur de faisabilité** : les recettes dont on a tous les ingrédients sont mises en avant
- [ ] **File de craft** : possibilité de lancer plusieurs crafts à la suite (avec barre de progression)
- [ ] **Qualité du craft** : selon le niveau de compétence, le résultat peut être Normal / Supérieur / Exceptionnel / Chef-d'œuvre
  - Qualité supérieure = stats bonus sur l'item crafté
  - Chef-d'œuvre = nom personnalisé par le joueur + effet unique

### 5.3 Économie du craft
- [ ] **Chaîne de production** : Minage → Fonderie → Forgeron (chaque étape nécessite une compétence)
- [ ] **Ateliers dans le monde** : forges, alambics, établis de tannage situés dans les villes (pas dans l'inventaire)
- [ ] **Réparation d'équipement** : les items s'usent avec le temps, nécessitent un artisan ou des matériaux pour être réparés
- [ ] **Démontage** : recycler un équipement pour récupérer une partie des matériaux

---

## Phase 6 — Économie et commerce

> Donner de la valeur aux items et créer une économie vivante.

### 6.1 Boutiques PNJ
- [ ] **Marchands par zone** : chaque ville/zone a des marchands spécialisés
  - Marchand d'armes, marchand d'armures, apothicaire, marchand général
  - Stock limité pour certains items rares (se renouvelle périodiquement)
- [ ] **Interface d'achat/vente** : prix clairement affichés, comparaison avec l'équipement actuel
- [ ] **Prix dynamiques** : le prix de vente = 30% du prix d'achat (évite l'exploit achat/revente)
- [ ] **Monnaie** : Gils (pièces d'or) — gagnés via monstres, quêtes, vente d'items

### 6.2 Hôtel des ventes (joueur à joueur)
- [ ] **Mise en vente** : le joueur fixe un prix et une durée (24h, 48h, 72h)
- [ ] **Recherche et filtres** : par type d'item, rareté, prix, niveau requis, élément
- [ ] **Historique des prix** : graphique simple montrant l'évolution du prix moyen d'un item
- [ ] **Taxe de transaction** : 5% prélevés sur chaque vente (puits d'or pour l'économie)
- [ ] **Notifications** : alerte quand un item est vendu ou quand une enchère est remportée

### 6.3 Échange direct
- [ ] **Trade entre joueurs** : fenêtre d'échange sécurisée (les deux joueurs valident)
- [ ] **Échange d'or** inclus dans la fenêtre de trade

---

## Phase 7 — Quêtes et narration

> Créer une raison de jouer — une histoire qui donne du sens à l'exploration.

### 7.1 Système de quêtes enrichi
- [ ] **Types de quêtes variés** :
  - Tuer X monstres (existant)
  - Collecter X ressources (existant)
  - **Escorte** : protéger un PNJ d'un point A à un point B
  - **Livraison** : apporter un item à un PNJ dans une autre zone
  - **Exploration** : découvrir X zones / atteindre un lieu spécifique
  - **Craft** : fabriquer un item spécifique pour un PNJ
  - **Enquête** : parler à plusieurs PNJ pour rassembler des indices
  - **Défi de boss** : vaincre un boss spécifique dans des conditions données
- [ ] **Chaînes de quêtes** : quêtes liées qui racontent une histoire (Q1 → Q2 → Q3 → récompense finale)
- [ ] **Quêtes à choix** : certaines quêtes offrent des choix qui influencent les récompenses ou la suite de l'histoire
- [ ] **Journal de quêtes** : interface dédiée avec quêtes actives, terminées, et disponibles

### 7.2 Trame principale
- [ ] **Acte 1 — L'Éveil** : le joueur arrive dans le village de départ, apprend les bases (tutoriel intégré narrativement)
  - Rencontrer le Forgeron → apprendre le craft de base
  - Premier combat guidé → comprendre la timeline et les sorts
  - Première récolte → découvrir les spots de ressources
  - Culmination : découverte d'un cristal d'améthyste mystérieux
- [ ] **Acte 2 — Les Fragments** : 4 fragments d'améthyste cachés dans 4 zones (forêt, grotte, montagne, marais)
  - Chaque zone a son propre arc narratif, ses boss, ses secrets
  - Le joueur peut les faire dans l'ordre qu'il veut (non-linéaire)
- [ ] **Acte 3 — La Convergence** : les fragments assemblés révèlent une menace, ouverture du donjon final
- [ ] Dialogues riches avec illustrations de personnages (portraits dans les dialogues)

### 7.3 Quêtes secondaires
- [ ] **Quêtes de faction** : réputation avec différentes factions (Guilde des Marchands, Ordre des Chevaliers, Cercle des Mages, etc.)
- [ ] **Quêtes quotidiennes** : missions renouvelées chaque jour (tuer X, récolter Y, crafter Z) pour encourager le jeu régulier
- [ ] **Quêtes de découverte** : cachées dans le monde, déclenchées par l'exploration ou des interactions spécifiques

---

## Phase 8 — Progression et builds

> Permettre aux joueurs de créer des personnages uniques.

### 8.1 Arbres de talent étendus
- [ ] **Profondeur des arbres** : chaque domaine de combat passe de 3-5 compétences à **10-15 compétences** organisées en 3 branches
  - Pyromancien : Destruction (dégâts) / Contrôle (statuts) / Maîtrise (efficacité)
  - Soldat : Attaque (dégâts physiques) / Endurance (survie) / Tactique (vitesse, critique)
- [ ] **Compétences passives + actives** : chaque branche alterne entre bonus passifs et nouvelles actions
- [ ] **Compétence ultime** : au sommet de chaque arbre, une compétence unique et puissante (nécessite beaucoup d'investissement)
  - Pyromancien ultime : "Éruption solaire" — dégâts massifs de feu à tous les ennemis
  - Défenseur ultime : "Forteresse" — invulnérable pendant 3 tours

### 8.2 Système de build
- [ ] **Multi-domaine** : un joueur peut investir dans plusieurs arbres, mais les points sont limités → choix stratégiques
- [ ] **Respec payant** : possibilité de réinitialiser un arbre de talent contre de l'or (prix croissant)
- [ ] **Presets de build** : sauvegarder/charger des configurations de compétences + materia
- [ ] **Synergies cross-domaine** : certaines combinaisons de compétences de domaines différents débloquent des bonus spéciaux
  - Pyromancien + Soldat = "Lame enflammée" (attaque physique + dégâts feu)
  - Soigneur + Nécromancien = "Vampirisme" (les soins infligent aussi des dégâts)

### 8.3 Équipement et raretés
- [ ] **Système de rareté complet** :
  - 🔘 Commun (gris) — stats de base
  - 🟢 Peu commun (vert) — 1 bonus aléatoire
  - 🔵 Rare (bleu) — 2 bonus + 1 slot materia
  - 🟣 Épique (violet) — 3 bonus + 2 slots materia + effet spécial
  - 🟠 Légendaire (orange) — stats max + 3 slots materia + effet unique + nom propre
  - 💎 Améthyste (violet brillant) — items liés à la quête principale, les plus puissants du jeu
- [ ] **Enchantement** : l'alchimiste peut ajouter un bonus temporaire à une arme/armure (ex: +10% dégâts feu pour 50 combats)
- [ ] **Sets d'équipement** : porter 2/3/4 pièces du même set donne des bonus croissants
  - Set du Dragon : 2p = +10% feu, 3p = résistance feu, 4p = "Souffle draconique" en combat
  - Set du Rôdeur : 2p = +vitesse, 3p = +critique, 4p = "Embuscade" (premier tour garanti)

---

## Phase 9 — Monde vivant

> Faire sentir que le monde existe indépendamment du joueur.

### 9.1 Cycle jour/nuit
- [ ] **Cycle visuel** : palette de couleurs qui évolue (aube dorée → jour lumineux → crépuscule orangé → nuit bleutée)
- [ ] **Impact gameplay** :
  - Monstres nocturnes plus puissants mais meilleur loot
  - Certaines plantes ne poussent que la nuit (Fleur lunaire, Champignon phosphorescent)
  - PNJ marchands fermés la nuit → taverne ouverte (quêtes spéciales)
  - Visibilité réduite la nuit (brouillard de guerre étendu)
- [ ] Cycle de 1h réelle = 1 journée in-game (configurable admin)

### 9.2 Système météo
- [ ] **Types de météo** : ensoleillé, nuageux, pluie, orage, brouillard, neige (selon la zone)
- [ ] **Impact gameplay** :
  - Pluie : bonus pêche, malus feu (-20% dégâts), bonus eau (+20%)
  - Orage : sorts de foudre boostés, monstres électriques apparaissent
  - Brouillard : portée de vision réduite, monstres furtifs plus fréquents
  - Neige : vitesse de déplacement réduite, bonus glace
- [ ] Météo aléatoire par zone, changement toutes les 15-30 min réelles
- [ ] Effets visuels PixiJS (particules de pluie, flocons, éclairs)

### 9.3 Écosystème vivant
- [ ] **PNJ avec routines** : les PNJ se déplacent entre des points d'intérêt selon l'heure (maison → travail → taverne)
- [ ] **Événements aléatoires** : invasion de monstres dans une zone, marchand itinérant rare, aurore boréale (bonus XP)
- [ ] **Saisonnalité** : événements spéciaux liés aux vraies saisons (festival d'été, marché de Noël, chasse aux œufs)

---

## Phase 10 — Catalogue et découvertes

> Récompenser l'exploration et la curiosité.

### 10.1 Bestiaire
- [ ] **Fiche par monstre** : sprite, stats, faiblesses élémentaires, loot possible, lore/description
- [ ] **Progression** : chaque monstre vaincu augmente le compteur → paliers de récompenses
  - 10 kills : faiblesses révélées
  - 50 kills : loot table complète visible
  - 100 kills : titre "Chasseur de [monstre]" + bonus dégâts permanent contre ce type

### 10.2 Herbier & catalogue minier
- [ ] **Fiche par ressource** : image, description, zone de récolte, usages en craft
- [ ] **Première découverte** : bonus XP + notification spéciale
- [ ] **Complétion** : récompenses pour avoir trouvé toutes les ressources d'un type

### 10.3 Système d'achievements
- [ ] **Catégories** : Combat, Exploration, Récolte, Craft, Social, Quêtes, Secrets
- [ ] **Récompenses** : titres, icônes de profil, emotes, items cosmétiques, bonus permanents
- [ ] **Achievements cachés** : découverts par des actions inhabituelles (visiter toutes les cases d'une map, vaincre un boss sans subir de dégâts, etc.)

---

## Phase 11 — Contenu multijoueur et social

> Un MMO a besoin d'interactions entre joueurs.

### 11.1 Chat en jeu
- [ ] **Chat global** : visible par tous (via Mercure SSE)
- [ ] **Chat de zone** : visible par les joueurs de la même map
- [ ] **Messages privés** : joueur à joueur
- [ ] **Commandes** : `/whisper`, `/zone`, `/global`, `/emote`
- [ ] **Filtres anti-spam et modération** : rate limiting, mots bannis, signalement

### 11.2 Guildes
- [ ] **Création de guilde** : nom, blason (choix parmi des icônes), description
- [ ] **Rangs** : Maître, Officier, Membre, Recrue (permissions configurables)
- [ ] **Chat de guilde** dédié
- [ ] **Coffre de guilde** : inventaire partagé avec logs d'accès
- [ ] **Quêtes de guilde** : objectifs collectifs avec récompenses pour tous les membres
- [ ] **Classement des guildes** : basé sur les achievements collectifs

### 11.3 Groupes de combat
- [ ] **Formation de groupe** (2-4 joueurs) : invitation, acceptation
- [ ] **Combat de groupe** : tous les membres du groupe participent au combat
- [ ] **Loot partagé** : système de répartition (round-robin, besoin/cupidité, free for all)
- [ ] **Synergie de groupe** : bonus quand les rôles sont complémentaires (tank + healer + dps)

### 11.4 PvP
- [ ] **Arène** : combats 1v1 classés avec matchmaking basé sur la puissance
- [ ] **Saisons PvP** : classements réinitialisés périodiquement, récompenses exclusives
- [ ] **Duels** : défi entre joueurs (sans impact sur le classement)

---

## Phase 12 — Contenu endgame et rejouabilité

> Garder les joueurs investis sur le long terme.

### 12.1 Donjons instanciés
- [ ] **Donjons par zone** : chaque grande zone a un donjon avec plusieurs étages
  - Forêt maudite → Donjon des Racines (3 étages, boss Ochu Ancien)
  - Grotte cristalline → Mine abandonnée (4 étages, boss Golem de cristal)
  - Montagne → Tour du dragon (5 étages, boss Dragon ancestral)
- [ ] **Mécaniques de donjon** : pièges, puzzles simples (leviers, plaques de pression), salles secrètes
- [ ] **Loot de donjon** : équipements épiques/légendaires exclusifs, matériaux rares
- [ ] **Difficulté progressive** : Normal → Héroïque → Mythique (même donjon, stats monstres augmentées, mécaniques ajoutées)

### 12.2 World boss
- [ ] **Boss de zone ouvert** : apparaît à heures fixes, tous les joueurs présents peuvent participer
- [ ] **Contribution-based loot** : récompenses basées sur la participation (dégâts infligés, soins, tanking)
- [ ] **Annonce serveur** : notification Mercure quand un world boss apparaît

### 12.3 Système de réputation et factions
- [ ] **Factions** : Guilde des marchands, Ordre des chevaliers, Cercle des mages, Confrérie des ombres
- [ ] **Réputation** : gagner de la réputation via quêtes et actions liées à la faction
- [ ] **Paliers de réputation** : Inconnu → Neutre → Amical → Honoré → Révéré → Exalté
- [ ] **Récompenses par palier** : recettes exclusives, équipements uniques, accès à des zones secrètes, réductions marchands

### 12.4 Événements temporaires
- [ ] **Invasions** : vagues de monstres attaquant une zone (tous les joueurs coopèrent)
- [ ] **Festivals** : événements saisonniers avec quêtes, items cosmétiques, mini-jeux
- [ ] **Tournois PvP** : compétitions organisées avec bracket et récompenses

---

## Priorités de développement (ordre recommandé)

| Priorité | Phase | Justification |
|----------|-------|---------------|
| 🔴 1 | **Phase 1** — Fondations techniques | Nécessaire pour tout le reste (portails, object layers, perf) |
| 🔴 2 | **Phase 2** — Administration | Permet de créer du contenu sans toucher au code |
| 🔴 3 | **Phase 3** — Combat enrichi | Cœur du gameplay — doit être satisfaisant rapidement |
| 🔴 4 | **Phase 4** — Récolte complète | Deuxième pilier — alimente le craft |
| 🟠 5 | **Phase 5** — Craft | Troisième pilier — boucle gameplay récolte → craft → équipement |
| 🟠 6 | **Phase 6** — Commerce | Donne de la valeur aux items craftés |
| 🟠 7 | **Phase 7** — Quêtes & narration | Donne un but au joueur, guide la progression |
| 🟡 8 | **Phase 8** — Progression & builds | Profondeur stratégique pour la rétention long terme |
| 🟡 9 | **Phase 9** — Monde vivant | Polish qui rend le monde immersif |
| 🟢 10 | **Phase 10** — Catalogue & achievements | Récompense l'exploration, rejouabilité |
| 🟢 11 | **Phase 11** — Multijoueur & social | Communauté et rétention sociale |
| 🟢 12 | **Phase 12** — Endgame | Contenu pour joueurs investis |

---

## Métriques de succès

Pour chaque phase, on considère le succès atteint quand :
- **Phase 1** : un nouveau contributeur peut créer une carte Tiled et l'intégrer au jeu en < 30 minutes
- **Phase 2** : un admin non-technique peut ajouter un monstre, un item, une quête sans toucher au code
- **Phase 3** : un combat contre un boss dure > 5 tours avec des décisions tactiques réelles
- **Phase 4** : un joueur peut passer 15 minutes à récolter sans s'ennuyer
- **Phase 5** : un joueur peut s'équiper entièrement avec des items qu'il a craftés
- **Phase 6** : une économie joueur émergente fonctionne (items achetés/vendus entre joueurs)
- **Phase 7** : un nouveau joueur a 2-3h de contenu narratif guidé
- **Phase 8** : deux joueurs du même "niveau" ont des builds significativement différents
- **Phase 9** : le monde semble vivant même quand le joueur ne fait rien
- **Phase 10** : les joueurs cherchent activement à compléter leur catalogue
- **Phase 11** : les joueurs interagissent et s'entraident régulièrement
- **Phase 12** : les joueurs vétérans ont du contenu qui les challenge
