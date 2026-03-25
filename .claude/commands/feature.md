---
description: Prend un besoin brut en entree, le structure via le template de prompt, puis l'implemente. Usage — /feature je souhaite ajouter un systeme de craft d'armes
---

# Agent Feature — Amethyste-Idle

Tu recois un besoin brut de l'utilisateur. Ton travail se fait en 2 phases obligatoires.

## Besoin brut de l'utilisateur

$ARGUMENTS

## Phase 1 — Structuration du prompt (OBLIGATOIRE avant toute implementation)

Lis le template dans `docs/PROMPT_TEMPLATE.md` puis :

1. **Analyse le besoin brut** : identifie l'intention, les entites concernees, les interactions attendues.
2. **Explore le code existant** : trouve les fichiers, controllers, templates, entites et CSS directement lies au besoin. Identifie ce qui existe deja et peut etre reutilise.
3. **Genere un prompt structure** en suivant exactement le template (Objectif, Contexte technique, Specification fonctionnelle, UX/UI, Contraintes, Hors perimetre).
4. **Affiche le prompt structure** a l'utilisateur et demande validation avant de passer a la phase 2.

### Regles de structuration

- Chaque sous-feature doit etre assez petite pour un commit atomique.
- Le contexte technique doit lister les chemins exacts des fichiers existants, pas des suppositions.
- Les contraintes du projet (CLAUDE.md) doivent etre integrees automatiquement : Docker, Turbo Frames, pas de bundler, mobile-first, dark theme.
- Le "Hors perimetre" doit empecher toute derive de scope par rapport au besoin initial.

## Phase 1.5 — Verification CI en amont (OBLIGATOIRE avant implementation)

Avant de toucher au code, verifier que la CI est au vert :

1. **Lancer les checks localement** :
   - `docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff` (lint)
   - `docker compose exec php vendor/bin/phpstan analyse` (analyse statique)
   - `docker compose exec php vendor/bin/phpunit` (tests)
2. **Si la CI est en erreur** : corriger les problemes existants AVANT de commencer l'implementation. Ne pas empiler du nouveau code sur une base cassee.
3. **Informer l'utilisateur** des erreurs pre-existantes trouvees et des corrections appliquees.

## Phase 2 — Implementation (apres validation utilisateur)

Une fois le prompt valide :

1. **Decoupe en taches** : cree une liste de taches ordonnees (une par sous-feature).
2. **Implemente chaque tache** sequentiellement :
   - Ecris le code (backend, frontend, CSS selon besoin)
   - Teste que ca fonctionne (pas de regression)
   - Fais un commit atomique
3. **Respecte les contraintes du projet** :
   - Toutes les commandes via `docker compose exec php`
   - Pas de Node.js / npm / yarn — AssetMapper uniquement
   - Turbo Frames pour les rechargements partiels
   - Mobile-first, responsive
   - Coherent avec le design existant (dark theme, glass-morphism, couleurs rarete)
   - `IF NOT EXISTS` dans les migrations Doctrine
   - Competences = passives uniquement, sorts actifs = materia uniquement

## Phase 3 — Babysit PR & CI (apres implementation)

Une fois tous les commits faits :

1. **Pousser la branche et creer la PR**.
2. **Surveiller la CI** : verifier que les checks GitHub Actions passent via `gh pr checks`.
3. **Si un check echoue** :
   - Lire les logs d'erreur (`gh run view <run-id> --log-failed`)
   - Diagnostiquer et corriger le probleme
   - Committer le fix, pousser, et re-verifier
   - Repeter jusqu'a ce que la CI soit entierement au vert
4. **Confirmer a l'utilisateur** que la PR est prete (CI verte, pas de conflit).

## Comportement attendu

- Si le besoin est flou ou ambigu : poser des questions AVANT de structurer.
- Si le besoin est trop gros (> 5 sous-features) : proposer un decoupage en plusieurs sessions.
- Ne JAMAIS commencer a coder avant que l'utilisateur ait valide le prompt structure.
- Ne JAMAIS modifier des fichiers hors du perimetre identifie.
