# Nuffle Arena — Roadmap

## Sprint 1 : Fondations Monorepo

| # | Tâche | Estimation | Dépendances |
|---|-------|-----------|-------------|
| 1 | - [x] Initialiser monorepo pnpm + Turborepo + tsconfig partagé | 30min | — |
| 2 | - [x] Créer packages/game-engine : types de base (Player, Team, Pitch, GameState) | 45min | 1 |
| 3 | - [ ] Implémenter le RNG déterministe (utils/rng.ts) avec tests | 30min | 1 |
| 4 | - [ ] Créer apps/server : skeleton Express + socket.io | 30min | 1 |
| 5 | - [ ] Créer apps/web : skeleton Next.js 14 | 30min | 1 |
| 6 | - [ ] Créer packages/ui : skeleton Pixi.js | 30min | 1 |
| 7 | - [ ] Configurer Vitest + premiers tests game-engine | 30min | 2 |
| 8 | - [ ] Configurer ESLint + Prettier partagés | 20min | 1 |
| 9 | - [ ] Pipeline CI GitHub Actions (lint, typecheck, test, build) | 30min | 8 |

## Sprint 2 : Modèle de données Blood Bowl

| # | Tâche | Estimation | Dépendances |
|---|-------|-----------|-------------|
| 10 | - [ ] Définir le Pitch (grille 26x15) et système de coordonnées | 45min | 2 |
| 11 | - [ ] Modéliser les races et rosters (Human, Orc, Skaven, Dwarf) | 1h | 2 |
| 12 | - [ ] Implémenter les stats de joueur (MA, ST, AG, PA, AV) | 30min | 11 |
| 13 | - [ ] Implémenter le système de compétences (skills) de base | 1h | 12 |
| 14 | - [ ] Créer le state machine du match (setup → kickoff → turn → TD → halftime → end) | 1h | 10 |

## Sprint 3 : Mécanique de jeu — Déplacements

| # | Tâche | Estimation | Dépendances |
|---|-------|-----------|-------------|
| 15 | - [ ] Mouvement simple (Move Action, décrément MA) | 45min | 14 |
| 16 | - [ ] Going For It (GFI) — jet de dé risqué pour mouvement extra | 30min | 15, 3 |
| 17 | - [ ] Dodge roll (esquive en quittant une tackle zone) | 45min | 15, 3 |
| 18 | - [ ] Tackle zones et zones de contrôle | 30min | 10 |
| 19 | - [ ] Ramasser le ballon (pickup roll) | 30min | 15, 3 |

## Sprint 4 : Mécanique de jeu — Blocs et combat

| # | Tâche | Estimation | Dépendances |
|---|-------|-----------|-------------|
| 20 | - [ ] Block action — calcul nombre de dés (ST comparison) | 45min | 12, 3 |
| 21 | - [ ] Résolution Block dice (Pow, Stumble, Push, Skull, Both Down) | 30min | 20 |
| 22 | - [ ] Armour roll et Injury roll | 30min | 21 |
| 23 | - [ ] Pushback et follow-up | 30min | 21, 10 |
| 24 | - [ ] Blitz action (move + block combiné) | 30min | 15, 20 |

## Sprint 5 : Passes et turnover

| # | Tâche | Estimation | Dépendances |
|---|-------|-----------|-------------|
| 25 | - [ ] Pass action — jets de passe (Short, Long, Bomb) | 45min | 3, 10 |
| 26 | - [ ] Catch roll | 30min | 25 |
| 27 | - [ ] Interception | 30min | 25 |
| 28 | - [ ] Handoff (passe courte sans jet) | 20min | 26 |
| 29 | - [ ] Système de Turnover (conditions de déclenchement) | 45min | 14 |

## Sprint 6 : Multijoueur temps réel

| # | Tâche | Estimation | Dépendances |
|---|-------|-----------|-------------|
| 30 | - [ ] Rooms socket.io (create, join, spectate) | 45min | 4 |
| 31 | - [ ] Synchronisation GameState via WebSocket | 30min | 30, 14 |
| 32 | - [ ] Timer de tour (45s par action) | 30min | 31 |
| 33 | - [ ] Reconnexion joueur | 30min | 31 |
| 34 | - [ ] Schéma Prisma (User, Match, Replay) | 30min | 4 |

## Sprint 7 : UI Pixi.js — Plateau de jeu

| # | Tâche | Estimation | Dépendances |
|---|-------|-----------|-------------|
| 35 | - [ ] Rendu du pitch 26x15 avec Pixi.js | 1h | 6, 10 |
| 36 | - [ ] Affichage des joueurs (sprites, numéros, couleurs) | 45min | 35, 12 |
| 37 | - [ ] Surbrillance cases accessibles (mouvement) | 30min | 36, 15 |
| 38 | - [ ] Animation de déplacement des joueurs | 30min | 36 |
| 39 | - [ ] Affichage tackle zones et indicateurs de danger | 30min | 36, 18 |
