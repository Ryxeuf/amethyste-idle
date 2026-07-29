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
| WIK-02 | Le contrôleur public `/wiki` | M | à faire |
| WIK-03 | L'accès depuis le site et le jeu + le contrat d'entretien | S | à faire |

### WIK-01 — Le contenu ✅ (L)
> Livré le 2026-07-29. Les 7 chapitres : Commencer (le monde, créer son personnage,
> première session, l'énergie), Devenir (le principe fondateur, arbres, équipement-build,
> matéria), Le monde (zones & voyage, foyers, Crue & Pâleur, saisons), Produire (récoltes,
> artisanats, pureté, commerce), Vivre ensemble (guildes & cités, factions, logement, la
> semaine), Combattre (combat, éléments, donjons/boss/bestiaire), Les règles d'or.
> Sommaire dans `docs/wiki/README.md`.

### WIK-02 — Le contrôleur public `/wiki` (M | ★★★)
- [ ] Contrôleur sur le pattern de `RoadmapController` (lecture des fichiers markdown de
      `docs/wiki/`, rendu HTML, cache) — exposé sur le **site public** (amethyste.best),
      pas seulement en jeu
- [ ] Sommaire latéral généré depuis la structure des dossiers (`01-commencer/` …
      `07-regles-dor/`), page d'accueil = `README.md`
- [ ] Habillage Parchemin : composants `.ds-*`, aucune couleur redéclarée
- [ ] Les liens croisés relatifs entre pages (`../01-commencer/energie.md`) sont réécrits
      en routes `/wiki/...`
- [ ] Test : chaque fichier de `docs/wiki/` est atteignable par une route ; un lien
      interne cassé fait rougir la CI

### WIK-03 — L'accès et le contrat d'entretien (S | ★★)
- [ ] Entrée « Règles & savoir » dans la navigation du site public et un lien discret
      depuis le jeu (menu ou pied de page)
- [ ] Liens contextuels : l'écran de zone renvoie à la page zones-et-voyage, l'inventaire
      à materia, l'écran de craft à artisanats (progressif, au fil des reprises d'écrans)
- [ ] **Contrat d'entretien** : quand un plan annexe livre un système visible joueur, la
      campagne de livraison met à jour la page wiki concernée (ajouter cette ligne aux
      critères d'acceptance des plans actifs au fur et à mesure)
- [ ] Revue périmètre : vérifier qu'aucune page ne mentionne un système non livré
