---
description: Planifier l'implementation d'une feature avant d'ecrire du code. Delegue au planner pour analyser, decomposer et produire un plan valide par l'utilisateur. Usage — /plan systeme d'hotel des ventes
---

# /plan — Planification d'implementation

Invoque l'agent **planner** pour etablir un plan d'implementation complet avant d'ecrire du code.

## Besoin a planifier

$ARGUMENTS

## Ce que fait cette commande

1. **Analyse** et reformule la demande
2. **Explore** le code existant pour identifier les fichiers concernes
3. **Decompose** le travail en phases avec taches atomiques
4. **Identifie** les risques, dependances et points de blocage
5. **Evalue** la complexite (S/M/L/XL)
6. **Presente** le plan et attend la validation utilisateur

## IMPORTANT

- L'agent **NE VA PAS** ecrire de code tant que le plan n'est pas valide
- Tu peux demander des modifications : "modifie la phase 2" ou "ajoute un point sur les tests"
- Tu peux rejeter : "non, reprends avec une approche differente"
- Tu peux valider : "oui", "ok", "proceed"

## Apres validation

Enchaine avec :
- `/feature` pour l'implementation structuree
- `/tdd` pour une approche test-first
- `/build-fix` si des erreurs de build apparaissent
- `/code-review` pour la revue du code produit
