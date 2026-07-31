# SYNC — design ↔ code

**Dernier point : 31 juillet 2026.** Repo `Ryxeuf/amethyste-idle`, branche `main`.
Ce fichier remplace la version du 28/07, qui s'arrêtait au Tour 6.

## Tours maquettés

| Tour | Écran | Vues | État code |
| --- | --- | --- | --- |
| 3 | Zone (chiffres périmés) | — | historique, ne pas suivre |
| 4 | Zone | 4a desktop, 4b mobile | livré, conforme aux `ds-*` ; ordre des blocs à trancher |
| 5 | Tableau de bord | 5a, 5b, 5d compte neuf | livré ; 3 écarts + dettes RET-10 |
| 6 | Combat | 6a, 6b, 6c états | livré ; **7 demandes, aucune reprise** ; 3 zones hors palette |
| 7 | Sac | 7a, 7b, 7c, 7d | livré ; 5 écarts, 1 seul bougé à moitié ; 5 partiels hors palette |
| 8 | Compétences | 8a, 8b, 8c | livré ; 7 écarts, 1 fermé (DOM-02) |
| 9 | Hub de la semaine | 9a, 9b lundi, 9c, 9d | **non codé** (RET-08 → RET-10) |
| 10 | Hôtel des ventes | 10a, 10b, 10c, 10d | livré ; **entièrement hors direction Parchemin** |
| 11 | Cours du marché | 11a, 11b, 11c, 11d | **non codé** ; dépend d'une décision et d'un arbre à écrire |

## Décisions ouvertes

1. `actions.market.*` — étendre la liste blanche des déblocages de compétences, ou
   Négoce n'est pas une compétence (`CLAUDE.md` § 10, README § C.5).
2. **L'arbre de Négoce n'existe pas** : ni fixtures ni doc. Les quatre paliers de savoir
   de cours sont une proposition de forme. Domaine d'accueil non tranché (le Vagabond est
   le candidat le plus naturel, pas une décision).
3. Ordre des blocs de l'écran de Zone (bande « se passe ici, maintenant »).

## Règles tenues dans toutes les maquettes

- Pas de niveau global de joueur — progression par arbres (`CLAUDE.md` § 6). Les niveaux
  de **monstre** restent légitimes.
- **Gils** = gameplay, **améthystes** = cosmétique et confort, aucune conversion, aucun
  dark pattern (`docs/MONETIZATION.md`).
- Compétences **passives** ; les sorts viennent des matérias serties + `actions.materia.unlock`.
- L'énergie gate les tentatives ; le combat est gratuit une fois engagé.
- Une **seule action primaire** par écran.
- Aucun numéro de rang ou de palier à l'écran : l'interface dit ce que le joueur sait
  faire, pas à quel échelon il est.
- Aucune ombre portée : la séparation se fait par bordure et teinte.

## Correspondance écran ↔ sources

Voir la table `## Screen map` de `github.md` côté projet de design, reprise en § « Fichiers
joints » du README.
