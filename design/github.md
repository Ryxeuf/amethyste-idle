repo: Ryxeuf/amethyste-idle
branch: main

## Last sync
date: 2026-07-27T12:30:46Z

### Updated in this project
- Audit des maquettes contre les règles déclarées (`docs/PIVOT_PBBG.md`, `docs/GAME_PRINCIPLES.md`) : les chiffres des écrans 6A-6C étaient inventés, et deux contredisaient le modèle post-pivot (pas de niveau global, énergie + time-gating réel au lieu de rendements à la minute).
- Nouvelle section « 07 — Zone, d'après les règles déclarées » construite uniquement sur `config/game/zones/world_1.yaml` : table de rencontres de la Forêt des murmures (45/12/12/8/23), gils de coffre 5-25 le jour / 10-40 la nuit, filons partagés avec capacité et respawn, durées de voyage réelles (village 300 s, marais 300 s, mines 480 s), variance jour/nuit.
- Token DS « valeur non arbitrée » (souligné pointillé) pour les coûts d'énergie, question ouverte de `BALANCE.md`.
- Reste à corriger : mentions de niveau (« Niv. 44 », « Forge niv. 6 »), monnaie « or » au lieu de gils, et sorts actifs placés dans l'arbre de compétences alors qu'ils viennent des matérias.

## Sync history
- 2026-07-27T10:22:15Z — direction 2c « Parchemin », tokens + composants, écrans Combat / Exploration / Compétences, import des icônes du repo.
- 2026-07-27T09:46:41Z — lecture de `assets/styles/app.css`, création de `Amethyste — Directions.dc.html` (3 directions).

## Screen map
| Écran | Fichier projet | Source repo |
| --- | --- | --- |
| 07 — Zone (Forêt des murmures) | Amethyste - Design System.dc.html | config/game/zones/world_1.yaml, docs/PIVOT_PBBG.md, docs/GAME_PRINCIPLES.md |
| 06 — Zone, 3 directions (pré-audit, chiffres inventés) | Amethyste - Design System.dc.html | — |
| Tokens + composants + Combat / Sac / Artisanat / Boutique / Compétences | Amethyste - Design System.dc.html | assets/styles/app.css, assets/styles/images/Resources/Shikashi's Fantasy Icons Pack v2 |
| Directions 1a–2c (Équipement / Materia) | Amethyste - Directions.dc.html | assets/styles/app.css |
