repo: Ryxeuf/amethyste-idle
branch: main

## Last sync
date: 2026-07-28T09:11:21Z

### Updated in this project
- Les douze sorts de 6d sont ceux des fixtures réelles (lues : `fixtures/game/spell/{spell,fire,none,metal,earth,wind,life,death,nature}.yaml` — quatorze sorts au total) : Boule de feu, Lame d'air, Soin mineur, Lame tranchante, Jet de cailloux, Flammèche, Coup d'épée, Fouet de liane, Attaque, Pluie de feu, Feu, Châtiment. Éléments confirmés sur `src/Enum/Element.php` (none, fire, water, earth, air, light, dark, metal, beast). Seules les fourchettes de dégâts sont inventées.
- **Saturation des matéria** traitée en 6d : `getEquippedMateriaSpells()` (lu dans `src/GameEngine/Fight/CombatCapacityResolver.php`) balaie tous les slots de tous les équipements portés et ne déduplique que par slug — rien ne plafonne la liste, donc 12 sorts = 6 rangées de boutons. Règle retenue : 5 sorts jouables visibles (4 sur mobile) + tuile « N autres sorts », verrouillés regroupés sur une ligne repliée hors du compte, tri figé au premier tour (bonus élémentaire d'abord). Tri et coupe à l'affichage : le resolver renvoie déjà `elementMatch`, `linkedBonus`, `locked`.
- **Combat** maquetté en Tour 6 dans `Amethyste - Écrans.dc.html` : desktop 1280 (6a), mobile 452 avec onglets Actions/Journal (6b), cinq états + écarts (6c). Repris sur `templates/game/fight/index.html.twig` + `partials/_timeline.html.twig`, contrôlé sur `FightIndexController`, `FightTurnResolver` (ordre par vitesse décroissante, joueur prioritaire à égalité), `CombatCapacityResolver` (bonus élémentaire +25 % dégâts et XP), `CriticalCalculator` (×1,5), `HitChanceCalculator` (hit ± 2 par niveau d'écart, borné 5-100). Sorts repris sur `fixtures/game/spell/*.yaml` (Boule de feu 2, Pluie de feu 5, Soin mineur 5, Lame tranchante 1…).
- Quatre écarts + un arbitrage sur le combat : la jauge d'énergie du combat ne bouge jamais (aucune fixture de sort ne déclare `energyCost` — cohérent avec « le combat est gratuit une fois engagé », donc jauge retirée) ; `getTimeline($fight, 3)` répète trois rounds identiques ; sur mobile les dégâts subis sont derrière l'onglet Journal (ajout d'une ligne « Dernier tour » permanente) ; les erreurs et « Veuillez sélectionner une cible » passent par `alert()` natif. À trancher : les boutons de sort affichent la valeur brute de fixture, pas le dégât réel.
- **Hub d'un compte neuf tranché** (5d) : un bloc vide se replie sur une ligne — titre + une phrase légère adressée au joueur, jamais un reproche, jamais un second appel à l'action ; un bloc ouvert ne se replie plus. Phrases à ajouter sous `hub.empty.*` dans `translations/messages.fr.json`.
- **Tableau de bord (hub)** maquetté en Tour 5 dans `Amethyste - Écrans.dc.html` : desktop 1280 (5a), mobile 452 (5b), les six états de reprise + écarts (5c). Repris sur `templates/game/index.html.twig`, `src/Controller/Game/IndexController.php` et `src/GameEngine/Player/PlayerHubDigest.php` ; libellés pris dans `translations/messages.fr.json` (`game.home.*`).
- Cinq blocs, dans l'ordre du code : reprise (une seule action primaire, `HubResume` : dead / fight / travel / expedition / expedition_done / lost / ready — travel et expedition non actionnables), attentes ordonnées par coût d'inaction (`house_rent` perte, puis `expedition_ready`, `craft_ready`, `garden_ripe`, `craft_orders`, `quests_ready`, `talent_xp`, `messages_unread`), récap 24 h (agrégat + 5 lignes, journal borné à 40 entrées), domaines, quêtes (3 max, plus avancées d'abord).
- Trois écarts relevés sur le gabarit : l'XP disponible comptée deux fois (ligne `talent_xp` + rappel sous les barres de domaine) ; les trois jauges PV/énergie/gils de l'en-tête qui répètent la navigation épinglée (remplacées par l'énergie traduite en actions) ; le nom du personnage en `ds-title` alors qu'il ne dit rien d'actionnable. À trancher : le hub d'un compte neuf affiche quatre encadrés vides d'affilée.
- Resync sur l'écran de zone **implémenté** (`templates/game/zone/index.html.twig`, qui utilise les classes `ds-*` du système). Tour 4 ajouté dans `Amethyste - Écrans.dc.html` : desktop 1280 (4a), mobile 452 + onglet Monde (4b), états exclusifs et écarts corrigés (4c).
- Coûts d'énergie **arbitrés** dans le code, plus de « valeur non arbitrée » : explorer 5 (`ExploreService::DEFAULT_COST`), chasser 5, récolter 3, événement 10, assaut de boss 10 ; régen énergie 360 s, régen PV 12 s/point. Expéditions : paliers 1 h / 4 h / 12 h, **sans coût d'énergie**, état exclusif.
- Filons de la Forêt des murmures repris sur `world_1.yaml` (6 filons, capacités 72/60/32, repousse 45 ou 60 min, rendement 1-3). Le « filon non repéré » du Tour 3 est supprimé : la zone déclare tout son contenu. Niveaux de monstre retirés des proies (l'écran réel n'affiche que le nom).
- Blocs manquants intégrés : boss de zone asynchrone (PV partagés), événements de zone, donjon de groupe semi-synchrone (run actif + offres), PNJ présents, échoppes de joueurs (`ECO-12`), chat de zone Mercure, points d'intérêt, sorties de zone avec durée effective monture.
- **Recommandation d'ordre** (à trancher côté code) : le gabarit empile expédition / événements / boss / donjons avant l'en-tête de zone et pousse « Explorer » sous la ligne de flottaison. Proposition : bande unique « Se passe ici, maintenant » sous l'en-tête, après l'action primaire.

### Sync précédent
- `Amethyste - Écrans.dc.html` : écran **Zone** en mobile 452 et desktop 1280 (nav en colonne, énergie épinglée, densité + panneau latéral). Six écrans retenus avec l'utilisateur : Zone, Combat, Sac, Hub, Compétences, Hôtel des ventes.
- Tour 2 de la Zone : bande « ce que tu fais en ce moment » en tête (combat engagé / expédition / rien), « ce que la zone tire » renommé **chances par exploration**, affordance d'explication systématique (pointillé gris + pastille), état non-découvert pour filons et espèces, voyage sorti vers l'Atlas (`zone/world_map`).
- Sprites de créatures : `assets/styles/images/monster/Enemy 06-1.png` est un **squelette** et `Enemy 09-1.png` un **fantôme** — les deux espèces nocturnes déclarées pour la Forêt des murmures (`night.mob_slugs: [ghost, skeleton]`). Copiés en `assets/icons/monster-skeleton.png` et `monster-ghost.png` (planches 96×128, 3×4 cellules de 32 px, ligne 0 = face).

## Sync history
- 2026-07-27T13:38:06Z — trois écarts aux règles corrigés dans les écrans du DS :
  1. Niveaux joueur retirés — « Forge niv. 6 » → rang 6, prérequis en points ou compétences, plan gaté par la découverte (D2). Les niveaux de **monstre** sont conservés (légitimes : `MateriaXpGranter` = 10 × niveau monstre).
  2. Monnaie → **gils** partout ; **améthystes** réservées au cosmétique (`docs/MONETIZATION.md`) — le « Sceau de rappel à 8 améthystes » était du pay-to-win, remplacé par une teinture. Compte à rebours et remise barrée retirés de la Boutique (dark patterns interdits).
  3. Arbre de compétences rendu **passif** : bonus de stat ou `actions.materia.unlock`. L'écran Combat distingue l'attaque d'arme (toujours gratuite) des sorts, qui viennent des matérias serties, avec l'élément et le rang de la matéria ; une matéria non sertie reste visible et dit pourquoi.
- Matériaux d'artisanat repris sur des items réels (`ore-copper`, `ore-iron`, `plant-mint`) et source indiquée sur une zone réelle (filon de fer, Mines profondes) — la « zone du Cratère » n'existe pas.
- Écran Exploration de la section 05 marqué comme remplacé par 07 (carte en tuiles abandonnée au pivot). La section 06 garde ses chiffres inventés, explicitement annotée pré-audit.

## Règles vérifiées (sources)
- `CLAUDE.md` §6 : pas de niveau global — progression par arbres uniquement.
- `CLAUDE.md` §10 et `AGENTS.md` : sorts actifs = matéria possédée + compétence `actions.materia.unlock` + sertissage ; attaque d'arme toujours gratuite. Skills toujours passifs (`damage`, `heal`, `hit`, `critical`, `life`).
- `docs/MONETIZATION.md` : Or (gils) = gameplay, Améthystes = cosmétique et confort, aucune conversion, pas de dark patterns.
- `docs/PIVOT_PBBG.md` : l'énergie gate les tentatives, le combat est gratuit une fois engagé, les PV sont le second régulateur.
- Éléments : fire, water, earth, air, light, dark, metal, beast (+ none). Bonus élémentaire slot/matéria : dégâts +25 %, XP +25 % ; synergie liée +15 %.

## Sync history (suite)
- 2026-07-27T13:26:17Z — inventaire des 71 gabarits `render('game/…')` (61 écrans pleins, 10 fragments), icônes repointées vers les planches du repo, `PR_BODY.md` rédigé.
- 2026-07-27T12:30:46Z — audit contre `docs/PIVOT_PBBG.md` et `docs/GAME_PRINCIPLES.md` ; section « 07 — Zone » reconstruite sur `config/game/zones/world_1.yaml` ; token « valeur non arbitrée » pour les coûts d'énergie.
- 2026-07-27T10:22:15Z — direction 2c « Parchemin », tokens + composants, écrans Combat / Exploration / Compétences, import des icônes du repo.
- 2026-07-27T09:46:41Z — lecture de `assets/styles/app.css`, création de `Amethyste - Directions.dc.html` (3 directions).

## Screen map
| Écran | Fichier projet | Source repo |
| --- | --- | --- |
| Combat — Tour 6, desktop 1280 + mobile 452 + états | Amethyste - Écrans.dc.html | templates/game/fight/index.html.twig, templates/game/fight/partials/_timeline.html.twig, src/Controller/Game/Fight/FightIndexController.php, src/GameEngine/Fight/(FightTurnResolver, CombatCapacityResolver, Calculator/*), fixtures/game/spell/*.yaml, translations/messages.fr.json (game.fight.*) |
| Tableau de bord — Tour 5, desktop 1280 + mobile 452 + états | Amethyste - Écrans.dc.html | templates/game/index.html.twig, src/Controller/Game/IndexController.php, src/GameEngine/Player/PlayerHubDigest.php (HubResume/HubPendingItem/HubRecap), translations/messages.fr.json |
| Zone — Tour 4, desktop 1280 + mobile 452 | Amethyste - Écrans.dc.html | templates/game/zone/index.html.twig, src/Controller/Game/ZoneController.php, src/GameEngine/Zone/* (Explore/Hunt/Gather/Expedition/ZoneEvent/ZoneBoss/ActionEnergy/LifeRegen), config/game/zones/world_1.yaml |
| Zone — Tour 3 (chiffres périmés, conservé comme historique) | Amethyste - Écrans.dc.html | config/game/zones/world_1.yaml, assets/styles/images/monster/Enemy 06-1.png + Enemy 09-1.png |
| Inventaire des 71 interfaces | Amethyste - Inventaire des écrans.dc.html | src/Controller/Game/**, templates/base.html.twig |
| 07 — Zone (Forêt des murmures) | Amethyste - Design System.dc.html | config/game/zones/world_1.yaml, docs/PIVOT_PBBG.md |
| 06 — Zone, 3 directions (pré-audit, chiffres inventés) | Amethyste - Design System.dc.html | — |
| Combat / Compétences | Amethyste - Design System.dc.html | CLAUDE.md §10, AGENTS.md, src/GameEngine/Fight (CombatCapacityResolver, CombatSkillResolver) |
| Sac / Artisanat / Boutique | Amethyste - Design System.dc.html | docs/MONETIZATION.md, docs/GAME_PRINCIPLES.md §4, config/game/zones/world_1.yaml |
| Tokens + composants | Amethyste - Design System.dc.html | assets/styles/app.css, assets/styles/images/Resources/Shikashi's Fantasy Icons Pack v2 |
| Directions 1a–2c (Équipement / Materia) | Amethyste - Directions.dc.html | assets/styles/app.css |
