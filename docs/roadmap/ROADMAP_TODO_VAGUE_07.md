## Vague 7 — Qualite & stabilisation

> **12 taches** de qualite, stabilite et polish UX.
> Priorite absolue : consolider l'existant avant d'ajouter des features.
> Integre les taches TST restantes du plan testing.

---

### Piste A — Testing (sequentiel, reprise du plan TST)

### 104 — Tests integration quetes & progression (M | ★★★) — TST-07
> Prerequis : ← TST-04 ✅
- [ ] `QuestProgressionIntegrationTest` : accepter quete → tuer mob → objectif mis a jour → completion → recompense
- [ ] `SkillProgressionIntegrationTest` : gagner XP domaine → niveau augmente → competence deblocable → materia utilisable
- [ ] **Verification** : `docker compose exec php vendor/bin/phpunit --testsuite Integration --filter Quest`

### 105 — Stabiliser tests E2E existants (M | ★★) — TST-09
> Prerequis : ← TST-01 ✅
- [ ] Corriger fixtures E2E (joueur avec mob adjacent, quete en cours)
- [ ] Helpers `waitForPixi()`, `waitForTurbo()` dans `AbstractE2ETestCase`
- [ ] Corriger selecteurs CSS casses
- [ ] **Verification** : `docker compose exec php vendor/bin/phpunit --testsuite E2E`

### 106 — Nouveaux tests E2E critiques (M | ★★) — TST-10
> Prerequis : ← 105
- [ ] `InventoryFlowTest` : equiper item → stat change → desequiper → stat revient
- [ ] `MapNavigationTest` : clic deplacement → joueur bouge → changement de carte
- [ ] `ShopFlowTest` : acheter item → or diminue → item dans inventaire

### 107 — Reactiver E2E dans la CI (S | ★★) — TST-11
> Prerequis : ← 105
- [ ] Decomenter le job `e2e` dans `.github/workflows/ci.yml`
- [ ] `continue-on-error: true` initialement, retirer apres 5 CI vertes

### 108 — PHPStan niveau 6 + reduction baseline (M | ★★★) — TST-12
> Prerequis : ∅
- [ ] Corriger erreurs `property.onlyWritten` et `nullCoalesce.offset`
- [ ] Passer le niveau de 5 a 6
- [ ] Objectif : baseline < 100 erreurs (actuellement 313)

### 109 — Mutation testing avec Infection PHP (M | ★★) — TST-13
> Prerequis : ← TST-05 ✅
- [ ] Installer Infection PHP
- [ ] Configurer sur `src/GameEngine/Fight/Calculator/` (zone critique)
- [ ] Objectif : MSI >= 60%, Covered MSI >= 80%

---

### Piste B — Stabilite & polish (parallelisable)

### 110 — Correction bugs connus & dette technique (M | ★★★)
> Prerequis : ∅
- [ ] Audit des issues GitHub ouvertes et priorisation
- [ ] Correction des bugs critiques gameplay (combat, inventaire, quetes)
- [ ] Nettoyage code mort detecte par PHPStan
- [ ] Verification coherence DB via `app:game:validate` en CI

### 111 — Equilibrage combat avance (M | ★★★)
> Prerequis : ∅
- [ ] Rapport d'equilibrage via commande admin : DPS moyen par tier, temps de combat, taux de mort
- [ ] Ajustement formules de degats si ecarts > 30% entre builds
- [ ] Equilibrage donjons : difficulte vs recompenses
- [ ] Equilibrage world boss : HP et loot en fonction du nombre de joueurs actifs

### 112 — Optimisation requetes N+1 & performance DB (M | ★★)
> Prerequis : ∅
- [ ] Profiling Doctrine : identifier les requetes N+1 (Symfony Profiler / logs)
- [ ] Ajouter les `JOIN FETCH` et index manquants
- [ ] Cache Symfony pour les donnees statiques (items, monstres, sorts)
- [ ] Benchmark : temps de reponse < 200ms pour les routes critiques

---

### Piste C — UX & accessibilite (parallelisable)

### 113 — Tutoriel / onboarding nouveau joueur (M | ★★★)
> Prerequis : ∅
- [ ] Sequence tutoriel : deplacement → combat → inventaire → quetes → craft
- [ ] Indicateurs visuels (fleches, highlights) pour guider le joueur
- [ ] PNJ tuteur avec dialogues contextuels
- [ ] Possibilite de skip pour les joueurs experimentes
- [ ] Achievement "Premier pas" a la fin du tutoriel

### 114 — Centre de notifications in-game (S | ★★)
> Prerequis : ∅
- [ ] Panel de notifications (icone cloche, badge non-lues)
- [ ] Types : quete completee, level up domaine, succes debloque, invitation guilde, objet recu
- [ ] Persistance en DB (derniers 50 par joueur)
- [ ] Notifications push via Mercure SSE

### 115 — Journal de bord joueur (S | ★★)
> Prerequis : ∅
- [ ] Page `/game/journal` : historique chronologique des evenements du joueur
- [ ] Entrees automatiques : combats, quetes, decouvertes, craft, niveau domaine
- [ ] Filtrage par type d'evenement
- [ ] Limite : 200 entrees par joueur (rotation)

---
