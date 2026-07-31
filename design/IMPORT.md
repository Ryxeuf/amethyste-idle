# IMPORT — à faire dans `Ryxeuf/amethyste-idle` (branche `main`)

Paquet de design du **31 juillet 2026**. Il remplace intégralement le dossier `design/`
du repo, qui est en retard de plusieurs tours (son `SYNC.md` datait du 28/07 et
s'arrêtait au Tour 6 : ni Sac, ni Compétences, ni Hôtel des ventes, ni cours du marché).

## 1. Où poser les fichiers

| Fichier du paquet | Destination dans le repo |
| --- | --- |
| `README.md` | `design/README.md` (écrase) |
| `SYNC.md` | `design/SYNC.md` (écrase) |
| `ecrans.dc.html` | `design/ecrans.dc.html` (écrase) |
| `design-system.dc.html` | `design/design-system.dc.html` (écrase) |
| `design-system-repo-paths.dc.html` | `design/design-system-repo-paths.dc.html` (écrase) |
| `inventaire-ecrans.dc.html` | `design/inventaire-ecrans.dc.html` (écrase) |
| `directions.dc.html` | `design/directions.dc.html` (écrase) |
| `support.js` | `design/support.js` (écrase — runtime, ne pas éditer) |
| `captures/*.png` | `design/captures/` (20 PNG à l'échelle 1) |

## 2. À supprimer en même temps

Les quatre doublons `Amethyste - ….dc.html` de `design/` (~600 Ko) : seules les copies en
minuscules sont maintenues. `design/PR_BODY.md` reste tel quel s'il est encore ouvert.

## 3. Ce que le paquet demande au code

Tout est dans `README.md`, dans l'ordre d'attaque recommandé :

1. **§ A — migration de palette** (mécanique, à faire d'abord) : table de correspondance
   de vingt lignes ancien → `ds-*`, état écran par écran. Un seul token neuf légitime,
   le châssis de strate (`#b9a8d8` / `#f6f1fb`).
2. **§ 1.1–1.2 Combat** : deux suppressions (jauge d'énergie morte, sorts verrouillés
   rendus en boutons).
3. **§ B — hub de la semaine** (RET-08 → RET-10), cadré par `docs/GAME_DASHBOARD.md`.
   Rien n'est codé.
4. **§ C — couche d'information de marché** (Tour 11). **Attend deux décisions** :
   `actions.market.*` étend-il la liste blanche des déblocages (§ C.5, `CLAUDE.md` § 10) ;
   et **l'arbre de Négoce n'existe pas** — les quatre paliers sont une proposition de
   forme, pas une arborescence arrêtée. À écrire en fixtures comme l'arbre du Mineur
   avant que le gating puisse être codé.
5. **§§ 2–5** : les reprises restantes sur Sac, Compétences, Hub, Zone.

## 4. Nature des fichiers

Les `.dc.html` sont des **références de design**, pas du code à porter. Ils s'ouvrent
directement dans un navigateur, sans build. La cible reste Twig + Tailwind v4 + Stimulus
+ AssetMapper, avec `assets/styles/design-system.css` (`.ds-*`) déjà livré : aucun
composant nouveau n'est demandé.
