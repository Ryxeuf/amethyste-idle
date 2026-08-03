# PLAN_WIKI — Les pages de règles & savoir du site

> **Origine** : point global du 2026-07-29. Structure validée le 2026-07-29 (7 chapitres,
> ~20 pages). Décline les `GAME_*.md` en **pages joueur** : les documents de design restent
> la source de vérité (avec leurs raisonnements et leurs chiffres internes), le wiki n'en
> dit que **ce qu'un joueur a le droit de savoir**.

## Les deux interdits structurels

1. **Jamais de seuils cachés** : pas de valeurs d'équilibrage interne (seuils de sédiment,
   probabilités de tirage, compteurs dissimulés). Seules les règles que le joueur voit ou
   doit connaître (coûts d'énergie, taxes affichées, noms de rangs et de bandes).
2. **Jamais de spoil de découverte** : on peut dire qu'une chose existe, jamais comment on
   la trouve si c'est un secret (approches de faction, accords dormants, carte exacte des
   signatures — l'information des prospecteurs reste aux prospecteurs).

Et une règle de périmètre : **une page wiki ne documente jamais un système non livré.**
Le wiki décrit le jeu tel qu'il est, pas la roadmap.

## Vue d'ensemble

| Jalon | Titre | Taille | Statut |
|---|---|---|---|
| WIK-01 | Le contenu — 7 chapitres, ~20 pages sous `docs/wiki/` | L | ✅ livré le 2026-07-29 |
| WIK-02 ✅ | Le contrôleur public `/wiki` | M | **livré le 2026-07-31** |
| WIK-03 ✅ | L'accès depuis le site + le contrat d'entretien | S | **complet** — entrée publique, contrat, et les trois liens contextuels (2026-08-03) |

### WIK-01 — Le contenu ✅ (L)
> Livré le 2026-07-29. Les 7 chapitres : Commencer (le monde, créer son personnage,
> première session, l'énergie), Devenir (le principe fondateur, arbres, équipement-build,
> matéria), Le monde (zones & voyage, foyers, Crue & Pâleur, saisons), Produire (récoltes,
> artisanats, pureté, commerce), Vivre ensemble (guildes & cités, factions, logement, la
> semaine), Combattre (combat, éléments, donjons/boss/bestiaire), Les règles d'or.
> Sommaire dans `docs/wiki/README.md`.

### WIK-02 — Le contrôleur public `/wiki` ✅ (M | ★★★)
> **Livré le 2026-07-31.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] Contrôleur public, réutilisant le `MarkdownParser` de l'écran de roadmap
- [x] Sommaire **constaté** depuis les dossiers, jamais recopié — une page déposée dans un
      chapitre existant y apparaît toute seule
- [x] Habillage Parchemin : `.ds-*` et jetons du système de design, aucune couleur redéclarée
- [x] Liens relatifs réécrits en routes `/wiki/...` — **y compris les liens entre voisins de
      chapitre** (`energie.md` sans dossier), qui sont la moitié des 80 liens du wiki
- [x] Tests : chaque fichier atteignable, aucun lien mort, et une adresse inventée ne trouve
      rien — les deux segments d'URL sont des **clefs d'index**, jamais des morceaux de chemin

> **Écart au cadrage, assumé** : pas de cache. Le jalon en prévoyait un « sur le pattern de
> `RoadmapController` » — qui n'en a pas non plus. Vingt-cinq fichiers lus au vol par une page
> consultée rarement ne justifient pas un invalidateur de plus à tenir juste.


### WIK-03 — L'accès et le contrat d'entretien ✅ (S | ★★)
> **Livré le 2026-07-31** (entrée publique + moitié vérifiable du contrat), **complété le
> 2026-08-03** : les trois liens contextuels sont posés.
- [x] Entrée « Règles & savoir » dans la navigation du site public, **hors du bloc
      `app.user`** : les règles se lisent avant de jouer, et un wiki derrière
      l'authentification ne sert qu'à ceux qui n'en ont déjà plus besoin
- [x] Liens contextuels : l'écran de zone renvoie à `03-le-monde/zones-et-voyage`,
      l'inventaire matéria à `02-devenir/materia`, l'écran de craft à
      `04-produire/artisanats` — clés `game.wiki.context.*` (FR/EN), le même patron que
      les encarts coach d'ONB-17b (« une règle publique se lit où l'on joue »). Les
      reprises d'écrans futures pourront en ajouter, la base est posée
- [x] **Contrat d'entretien**, dans sa moitié vérifiable : un test refuse que le wiki nomme
      un système acté mais non livré (caravanes, Répertoire des gestes). La liste est courte
      et chaque entrée se justifie par un jalon ouvert ; **le jour où l'un est livré, son
      entrée quitte la liste dans le même changement** — c'est ce qui rend la mise à jour du
      wiki visible au moment de la livraison plutôt que six mois plus tard
- [x] Revue périmètre : faite. Rien à corriger — et une fausse alerte instructive,
      l'Affleurement. Le wiki en **explique la règle** (et dit que le savoir des prospecteurs
      se monnaye) sans jamais dire où il est cette semaine : c'est exactement ce que
      GAME_DASHBOARD §6 demande. L'interdit porte sur l'**annonce**, pas sur l'existence
