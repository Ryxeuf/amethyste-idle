# design/ — maquettes et design system

Documents HTML autonomes. Ouvrir directement dans un navigateur (aucun build, aucune dépendance réseau autre que les Google Fonts).

| Fichier | Contenu |
| --- | --- |
| `design-system.dc.html` | Direction « Parchemin » : tokens, typo, composants, raretés |
| `design-system-repo-paths.dc.html` | Même document, chemins d'icônes pointant sur les planches du repo |
| `ecrans.dc.html` | Maquettes par tour de revue — Zone, Dashboard, Combat |
| `inventaire-ecrans.dc.html` | Inventaire des écrans du jeu et de leur état |
| `directions.dc.html` | Directions visuelles explorées (trace, non retenues) |
| `SYNC.md` | Association au repo, dernier point de synchro, carte écran → fichiers source |
| `PR_BODY.md` | Corps de PR pour la direction Parchemin + écran de zone |
| `support.js` | Runtime des documents ; ne pas éditer |

## Lire `ecrans.dc.html`

Un `<section>` par tour de revue, le plus récent en haut. Chaque option porte un identifiant visible (`6a`, `6b`…) utilisable en discussion.

Convention de fidélité, valable dans tout le document :

- **pointillé gris** = explication au survol ;
- **pointillé rouge** = valeur inventée pour la maquette (PV courants, noms de monstre, entrées de journal, fourchettes de dégâts).

Tout le reste — sorts, éléments, bonus élémentaire, difficultés, durées, tables de rencontre — est repris des fixtures, de la config et du code, sourcé dans `SYNC.md`.

## Tour 6 — Combat : ce qui est demandé au code

- [ ] Retirer la jauge d'énergie du combat : aucun sort de `fixtures/game/spell/` ne déclare de `energyCost`, la valeur reste à 0 et « Énergie insuffisante » est inatteignable. Le combat est gratuit une fois engagé.
- [ ] Filtrer les entrées `locked` de `getEquippedMateriaSpells()` avant rendu : seuls les sorts des matéria équipées sont jouables, un cadre non cliquable n'a rien à faire dans le panneau d'actions.
- [ ] Plafonner le panneau à cinq sorts sous la main (quatre sur mobile) + tuile « N autres sorts » dépliant le reste en liste dans le panneau. Quatre places offensives — bonus élémentaire d'abord, puis dégâts décroissants — la cinquième réservée au meilleur soin, en fin de rangée. Tri figé au premier tour du combat.
- [ ] Ne plus répéter trois rounds identiques dans la timeline (`getTimeline($fight, 3)`) : round courant, suivant estompé, écart de vitesse en clair.
- [ ] Ajouter une ligne « Dernier tour » permanente sous les combattants — sur mobile les dégâts subis sont aujourd'hui derrière l'onglet Journal.
- [ ] Remplacer les `alert()` natifs (« Veuillez sélectionner une cible », erreurs d'action) par cette même ligne, en registre d'alerte.
- [ ] Exposer une estimation de dégâts hors résolution, à côté de `DamageCalculator`, alimentée par le domaine du joueur et le monstre visé, recalculée au changement de cible — sans elle la borne basse de la fourchette ignore la résistance et ment.

## Tour 5 — Dashboard

- [ ] Un bloc vide se replie sur une ligne : son titre plus une phrase adressée au joueur, jamais un reproche, jamais un second appel à l'action. Un bloc ouvert ne se replie plus.
- [ ] Ajouter ces phrases sous `hub.empty.*` dans `translations/messages.fr.json`, une par bloc.
