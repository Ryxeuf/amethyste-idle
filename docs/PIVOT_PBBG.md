# Pivot — Amethyste devient un PBBG (abandon de la carte en tuiles)

> **Statut : adopté** · Juillet 2026
> Emplacement suggéré : `docs/PIVOT_PBBG.md`

## TL;DR

Amethyste-Idle abandonne la carte navigable en tuiles (Tiled/PixiJS, déplacement case par case, pathfinding, position temps réel). Le jeu devient un **PBBG** (*persistent browser-based game*) : un monde structuré en **graphe de zones**, rythmé par des ressources régénérantes et du time-gating réel, dans la lignée de Torn, Cartel Empire ou Fallen London — en conservant l'univers, le combat tour par tour, les matérias, les quêtes et les guildes existants.

## Contexte et constat

Le concept initial (MMORPG rétro Zelda + FF, vue 2D top-down, tuiles 32×32) a conduit à investir massivement dans le moteur de carte : import TMX, rendu PixiJS optimisé (pooling, spatial hash, cycle jour/nuit), pathfinding Dijkstra, déplacement synchronisé via Mercure (`map/move`, `map/respawn`), et un plan avatar complet (Sprints 7-10).

Constat :

- Le moteur de carte absorbe l'essentiel de l'effort de dev, au détriment du gameplay et de la profondeur.
- Ce n'est pas l'ADN recherché : ce qui fait l'intérêt du projet, c'est la **profondeur systémique** du monde persistant (progression par arbres, économie, coopération), pas la navigation temps réel.
- La carte est la première source de complexité et de bugs (synchronisation de position, rendu, collisions), pour un apport faible sur le cœur du jeu.

Les références du genre (Torn : 20 ans de rétention sans carte) démontrent que toutes les mécaniques visées sont orthogonales au déplacement en tuiles.

Si un Zelda-like avec carte et déplacement voit le jour un jour, ce sera un **projet séparé**, reparti de la base technique actuelle (le code carte reste dans l'historique git).

## Décision

1. **Décommission** de la carte navigable : rendu PixiJS, déplacement case par case, pathfinding Dijkstra, topics Mercure `map/move` / `map/respawn`, import TMX comme source du monde jouable.
2. Le monde devient un **graphe de zones** : des lieux nommés (les zones TMX actuelles du World 1 en sont le point de départ naturel), reliés entre eux, avec un **coût de voyage en temps réel**. La zone courante du joueur conditionne les actions disponibles.
3. Le rythme du jeu repose sur des **ressources régénérantes** (énergie/points d'action) et du **time-gating réel** (voyages, expéditions, craft) : le monde vit quand le joueur est déconnecté.
4. Le développement se recentre sur le **gameplay et la profondeur** : progression par arbres de talents (toujours **sans niveau global**), économie, farm, récolte, coopération.

### Modalité : gel avant suppression

Le code carte n'est **pas supprimé immédiatement** : il est d'abord désactivé (routes/menus retirés, contrôleurs Stimulus non chargés), puis supprimé une fois le modèle zone stabilisé. Objectif : pouvoir extraire proprement ce qui se réutilise (entités, spawns, données de zones) avant le nettoyage.

## Ce qui est conservé tel quel

- **Univers, lore, actes narratifs** et chaînes de quêtes.
- **Combat tour par tour** (GameEngine/Fight) — menu-based par nature, il fonctionne sans carte. Les rencontres changent seulement de déclencheur (voir tableau).
- **Matérias et arbres de talents** (compétences passives uniquement, sorts actifs via matéria — règles inchangées).
- **Guildes et Guild City Control** — le contrôle de cité s'exprime même mieux en modèle zone (bonus de zone contrôlée par la guilde).
- **World bosses et invasions** — deviennent des événements de zone annoncés, un pattern PBBG classique.
- **PvE coopératif exclusivement** — aucun PvP, conformément aux règles du projet.
- **Hôtel des ventes, succès, bestiaire, PNJ** — inchangés.
- **Le pixel art**, recentré : illustrations de zones, monstres, objets, et une carte du monde illustrée cliquable (image map) pour garder l'intuition géographique sans moteur de rendu.
- **Mercure** — reste pertinent pour le combat, le chat, les événements de zone ; seuls les topics de déplacement disparaissent.

## Équivalences : de la carte aux systèmes

| Sur la carte (actuel) | Modèle zone (cible) |
|---|---|
| Clic sur une cellule → Dijkstra → `PlayerMoveProcessor` | Voyager de zone en zone (graphe + durée réelle) |
| Croiser un mob sur le chemin (troncature du path) | Action **Explorer** : coûte de l'énergie, tire un événement selon la table de la zone (mob, filon, coffre, PNJ, événement rare) |
| Farmer les spawns d'une zone | Action **Chasser** : tables de mobs/loot par zone (le bestiaire existant est réutilisé tel quel) |
| Nœuds de récolte/minage placés sur la carte | Actions de récolte par zone + **filons partagés** : stock collectif qui s'épuise et respawn |
| Farm actif prolongé | **Expéditions time-gated** : on envoie son personnage N heures réelles, on revient chercher le butin |
| Voir les autres joueurs sur la carte | Présence par zone : liste des joueurs présents, base de la coopération (groupes, commerce, chat de zone) |
| World boss / invasion à un endroit du monde | Événement de zone annoncé, à rejoindre dans un temps limité |
| Montures : +50 % vitesse de déplacement | Montures : **réduction du temps de voyage** entre zones (le système d'obtention livré — achat/quête/drop — est conservé intégralement ; seul le rendu PixiJS devient sans objet) |
| Fast travel verrouillé par découverte | Identique : liaisons rapides déverrouillées en visitant la zone (déjà livré, se transpose tel quel) |
| Housing avec jardin (Sprint 11) | Identique, et même renforcé : la récolte passive en temps réel est un pilier du modèle PBBG |

Chaque zone devient une **configuration déclarative** (tables de rencontres, loot, ressources, actions, connexions) : ajouter du contenu = ajouter de la donnée, pas du code.

## Économie d'action

Principe directeur : **l'énergie gate l'accès aux rencontres, jamais le combat lui-même.**

- **Coûtent de l'énergie** : explorer, chasser, récolter, rejoindre un événement de zone, assaillir un boss de zone, tenter une épreuve chronométrée.
- **Gratuits et illimités** : les tours de combat, une fois la rencontre engagée. Un combat peut durer 30 secondes ou 2 heures sans différence — le combat tour par tour (matérias, tactique) est le cœur conservé du jeu et ne doit jamais être pénalisé par la durée.
- **Gratuits car déjà payés en temps réel** : voyager entre zones, lancer et récupérer une expédition. Une version antérieure de ce document les listait comme coûtant de l'énergie ; ils ne l'ont jamais fait dans le code, et c'est la bonne décision. Le voyage coûte des minutes réelles, et c'est sur ce coût-temps que repose l'arbitrage entre marchés régionaux (ECO-03) : le doubler d'un coût en énergie tuerait le transport de marchandises. Quant à l'expédition, c'est précisément l'outil du joueur qui n'a pas le temps de jouer activement — la facturer reviendrait à taxer l'absence.
- **Second régulateur : les PV.** Sortir d'un combat affaibli impose de régénérer (temps réel) ou de consommer des soins. L'énergie limite les *tentatives*, la vie fait payer les *échecs*.

Quatre curseurs indépendants, tous équilibrables via `docs/BALANCE.md` sans toucher au code : énergie (tentatives), PV (échecs), lockouts (donjons), contribution (loot de groupe).

## Contenu de groupe (PvE coopératif)

Deux formats complémentaires, dont le premier existe déjà en grande partie dans le code :

### Boss de zone — asynchrone

Généralisation directe de `WorldBossManager` / `WorldBossLootDistributor` : un boss avec un large pool de PV apparaît dans une zone pour une fenêtre donnée. Chaque joueur présent dépense de l'énergie pour lancer ses assauts quand il le souhaite ; le loot est distribué à la contribution. Aucune présence simultanée requise.

### Donjon de groupe — semi-synchrone

Un leader forme un groupe parmi les joueurs présents dans la zone, puis lance le donjon : une séquence de combats en **tour par tour partagé**.

- **Gestion de l'asynchronie** : chaque joueur dispose d'un délai pour jouer son tour (30-60 s) ; au-delà, son personnage exécute une action par défaut (l'attaque de base de l'arme, toujours disponible gratuitement — règle matéria inchangée).
- **Temps réel** : Mercure (déjà en place) rend l'expérience fluide quand le groupe est connecté simultanément. Le donjon devient un événement social planifié — assumé comme une feature du jeu coopératif.
- **Anti-farm** : lockout par joueur et par donjon (ex. 1 clear/jour ou cooldown de X heures), de préférence sous forme de **récompenses décroissantes** plutôt qu'un blocage sec, pour protéger l'économie et encourager la variété de contenu.

## Impacts sur la roadmap

À traiter selon le process habituel (retrait/ajout dans les fichiers de sprint, `ROADMAP_DONE.md` inchangé) :

- **Sprints 1-6** (stabilité, bestiaire, arsenal, progression, hôtel des ventes, social/économie) : **quasi intacts** — c'est précisément le cœur que le pivot veut prioriser. Repasser les tâches pour retirer les dépendances carte.
- **Sprints 7-10 (Avatar)** : **obsolètes en l'état** (le système avatar est un système de rendu sur carte). À remplacer par un sprint « Modèle zone » : entités Zone/Connexion, voyages, énergie, actions par zone, migration des spawns existants.
- **Sprint 11 (Monde vivant)** : housing/jardin et nouvelles zones conservés (les « 4 cartes via l'éditeur » deviennent « 4 zones de contenu ») ; sous-phase 4 des montures (rendu PixiJS du sprite) annulée.
- **`PLAN_MAP_EDITOR.md`** : terminé mais sans suite ; l'éditeur peut resservir au projet Zelda-like séparé.
- **`TILED_GUIDE.md`**, `terrain/` : à archiver avec le code carte.

## Impacts techniques à instruire

- **Règle « coordonnées `x.y` »** (CLAUDE.md §7) : à remplacer par une référence de zone (slug/FK). Migration des positions joueurs → zone d'appartenance.
- **CLAUDE.md / AGENTS.md / DOCUMENTATION.md** : retirer les sections carte (rendu PixiJS §9, Tiled §20), documenter le modèle zone.
- **Typesense** : l'indexation des cellules (`app:index:cell`) devient inutile ; l'index reste pertinent pour objets/entités.
- **`/api/map/*`** : endpoints à déprécier ; nouveaux endpoints zone (état de zone, voyage, exploration).
- **Frontend** : `map_pixi_controller.js`, `SpriteAnimator` et le bundle PixiJS sortent de l'importmap → allègement significatif du client.

## Questions ouvertes

- Granularité du graphe au lancement (reprendre les 9 zones du World 1 + intérieurs, ou regrouper).
- Formules de régénération d'énergie et coûts des actions (à étalonner via `docs/BALANCE.md`, en s'inspirant des références du genre).
- Sort du cycle jour/nuit : purement cosmétique sur la carte aujourd'hui, il peut devenir mécanique (tables de rencontres jour/nuit par zone).
