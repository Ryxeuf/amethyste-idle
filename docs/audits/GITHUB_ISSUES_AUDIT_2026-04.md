# Audit des issues GitHub & dette technique — Avril 2026

> **Date de l'audit** : 2026-04-11
> **Periode couverte** : depuis la creation du depot jusqu'au 2026-04-11
> **Depot** : [ryxeuf/amethyste-idle](https://github.com/ryxeuf/amethyste-idle)
> **Reference roadmap** : Sprint 1 — Task 110 (Correction bugs connus & dette technique)

---

## 1. Etat des issues GitHub

| Statut | Nombre |
|--------|--------|
| Issues ouvertes | **0** |
| Pull requests ouvertes | **0** |

Aucune issue n'est actuellement ouverte sur le tracker GitHub. Les retours de bug
sont remontes directement via les sessions de developpement et traites dans le
flux roadmap (voir `docs/ROADMAP_DONE.md`, Sprint 1).

## 2. Bugs critiques gameplay — synthese

Les bugs critiques identifies pendant la phase de stabilisation ont tous ete
corriges et verifies par des tests automatises. Historique complet dans
`docs/ROADMAP_DONE.md` (section Sprint 1 — Task 110) :

| Bug | Correction | Verification |
|-----|------------|--------------|
| Attaque basique ne dispatchait pas `MobDeadEvent` / `PlayerDeadEvent` | Dispatch ajoute dans le handler | `FightAttackControllerTest` |
| Loot non transfere vers l'inventaire | `FightLootProceedController` gere le transfert | Test d'integration |
| Compteur monstres quetes depassait `necessary` | Plafonnement aligne sur les quetes quotidiennes | `testUpdateMobKilledCapsAtNecessary` |
| Division par zero dans `MobActionHandler` | Methode `getHpPercent()` (6 occurrences) | PHPStan + tests unitaires |

## 3. Dette technique PHPStan & qualite de code

- **Niveau PHPStan** : 6 (passage effectue dans Task 108 / TST-12)
- **Baseline** : 528 → 507 entrees (-21) apres le nettoyage de Task 110
- **Nettoyage realise** : ajout de return types manquants, typage des proprietes,
  corrections logiques (`ItemHelper`), suppression d'entrees baseline stale
- **Tests** : couverture PHPUnit + mutation testing (Infection) sur les
  calculateurs de combat (MSI 79% / Covered MSI 80%)
- **CI** : step `app:game:validate --env=test` apres le chargement des fixtures
  pour detecter toute incoherence DB sur chaque push / PR

## 4. Validation de coherence en CI

La commande `app:game:validate` a ete integree au pipeline CI (`.github/workflows/ci.yml`)
apres le chargement des fixtures. Elle execute 7 checks :

1. Items orphelins (references mais inexistants)
2. Monstres sans stats valides
3. Quetes avec objectifs impossibles
4. Joueurs avec equipements incoherents
5. Cartes sans spawn point
6. Domaines avec XP negative (`negative_domain_experience`)
7. Items equipes hors inventaire joueur (`equipped_items_wrong_location`)

Toute regression sur la coherence des fixtures est detectee avant merge.

## 5. Priorisation des issues ouvertes

**Aucune issue ouverte** — la priorisation n'est donc pas applicable a ce jour.

Si des issues sont remontees a l'avenir, le processus recommande est :

1. **Critique** (bloque le gameplay, perte de donnees, crash) → correction immediate, Sprint 1
2. **Haute** (fonctionnalite majeure cassee, balance grave) → insertion dans le sprint en cours
3. **Moyenne** (UX degradee, comportement inattendu non bloquant) → sprint suivant
4. **Basse** (polish, amelioration qualite de vie) → a planifier selon la roadmap

## 6. Conclusion

Le Sprint 1 "Stabilite & Onboarding" est **pret a etre cloture** :

- ✅ Task 110 — Correction bugs connus & dette technique (toutes les sous-taches completees)
- ✅ Task 111 — Equilibrage combat avance (rapport, donjons, world boss, formules)
- ✅ Task 113 — Tutoriel / onboarding nouveau joueur (5 etapes, achievement, highlights)

La base de code est stable. Aucun bug critique connu n'est en attente.
Le pipeline qualite (lint + PHPStan 6 + PHPUnit + validation DB en CI) protege
des regressions futures.

**Prochaine etape roadmap** : Sprint 4 — Progression & Narration (Sprint 3 deja
termine, Sprint 2 deja termine).
