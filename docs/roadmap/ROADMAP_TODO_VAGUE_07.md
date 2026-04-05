## Vague 7 — Qualite, stabilisation & fondations UX

> **16 taches** de qualite, stabilite, polish UX et fondations joueur.
> Priorite absolue : consolider l'existant et combler les lacunes critiques d'experience joueur.
> Integre les taches TST restantes du plan testing.

---

### Piste A — Testing (sequentiel, reprise du plan TST)

### ~~104 — Tests integration quetes & progression (M | ★★★) — TST-07~~ ✅
> Prerequis : ← TST-04 ✅
- [x] `QuestProgressionIntegrationTest` : accepter quete → tuer mob → objectif mis a jour → completion → recompense
- [x] `SkillProgressionIntegrationTest` : gagner XP domaine → niveau augmente → competence deblocable → materia utilisable
- [x] **Verification** : `docker compose exec php vendor/bin/phpunit --testsuite Integration --filter Quest`

### ~~105 — Stabiliser tests E2E existants (M | ★★) — TST-09~~ ✅
> Prerequis : ← TST-01 ✅
- [x] Corriger fixtures E2E (joueur avec mob adjacent, quete en cours)
- [x] Helpers `waitForPixi()`, `waitForTurbo()` dans `AbstractE2ETestCase`
- [x] Corriger selecteurs CSS casses
- [x] **Verification** : `docker compose exec php vendor/bin/phpunit --testsuite E2E`

### ~~106 — Nouveaux tests E2E critiques (M | ★★) — TST-10~~ ✅
> Prerequis : ← 105
- [x] `InventoryFlowTest` : equiper item → stat change → desequiper → stat revient
- [x] `MapNavigationTest` : clic deplacement → joueur bouge → changement de carte
- [x] `ShopFlowTest` : acheter item → or diminue → item dans inventaire

### ~~107 — Reactiver E2E dans la CI (S | ★★) — TST-11~~ ✅
> Prerequis : ← 105
- [x] Decomenter le job `e2e` dans `.github/workflows/ci.yml`
- [x] `continue-on-error: true` initialement, retirer apres 5 CI vertes

### ~~108 — PHPStan niveau 6 + reduction baseline (M | ★★★) — TST-12~~ ✅
> Prerequis : ∅
- [x] Corriger erreurs `property.onlyWritten` et `nullCoalesce.offset`
- [x] Passer le niveau de 5 a 6
- [x] Baseline regenere : 0 erreurs reelles, 499 `missingType.*` en baseline (typage iterables/generics)

### ~~109 — Mutation testing avec Infection PHP (M | ★★) — TST-13~~ ✅
> Prerequis : ← TST-05 ✅
- [x] Installer Infection PHP
- [x] Configurer sur `src/GameEngine/Fight/Calculator/` (zone critique)
- [x] Objectif : MSI >= 60%, Covered MSI >= 80%

---

### Piste B — Stabilite & polish (parallelisable)

### 110 — Correction bugs connus & dette technique (M | ★★★)
> Prerequis : ∅
- [ ] Audit des issues GitHub ouvertes et priorisation
- [ ] Correction des bugs critiques gameplay (combat, inventaire, quetes)
- [ ] Nettoyage code mort detecte par PHPStan
- [x] Verification coherence DB via `app:game:validate` en CI

### 111 — Equilibrage combat avance (M | ★★★)
> Prerequis : ∅
- [x] Rapport d'equilibrage via commande admin : DPS moyen par tier, temps de combat, taux de mort
- [ ] Ajustement formules de degats si ecarts > 30% entre builds
- [ ] Equilibrage donjons : difficulte vs recompenses
- [ ] Equilibrage world boss : HP et loot en fonction du nombre de joueurs actifs

### ~~112 — Optimisation requetes N+1 & performance DB (M | ★★)~~ ✅
> Prerequis : ∅
- [x] Profiling Doctrine : identifier les requetes N+1 (Symfony Profiler / logs)
- [x] Ajouter les `JOIN FETCH` et index manquants
- [x] Cache Symfony pour les donnees statiques (items, monstres, sorts)
- [x] Benchmark : temps de reponse < 200ms pour les routes critiques

---

### Piste C — UX & accessibilite (parallelisable)

### 113 — Tutoriel / onboarding nouveau joueur (M | ★★★)
> Prerequis : ∅
- [ ] Sequence tutoriel : deplacement → combat → inventaire → quetes → craft
- [ ] Indicateurs visuels (fleches, highlights) pour guider le joueur
- [ ] PNJ tuteur avec dialogues contextuels
- [ ] Possibilite de skip pour les joueurs experimentes
- [ ] Achievement "Premier pas" a la fin du tutoriel

### 114 — Centre de notifications in-game (S | ★★) ✅
> Prerequis : ∅
- [x] Panel de notifications (icone cloche, badge non-lues)
- [x] Types : quete completee, level up domaine, succes debloque, invitation guilde, objet recu
- [x] Persistance en DB (derniers 50 par joueur)
- [x] Notifications push via Mercure SSE

### 115 — Journal de bord joueur (S | ★★) ✅
> Prerequis : ∅
- [x] Page `/game/journal` : historique chronologique des evenements du joueur
- [x] Entrees automatiques : combats, quetes, decouvertes, craft, niveau domaine
- [x] Filtrage par type d'evenement
- [x] Limite : 200 entrees par joueur (rotation)

### ~~136 — Creation de personnage (M | ★★★)~~ ✅
> Prerequis : ∅
- [x] Ecran de creation post-inscription (si aucun Player actif sur le compte)
- [x] Choix du nom de personnage (validation unicite, filtrage mots interdits)
- [x] Choix de la race (parmi `Race.availableAtCreation = true`) avec apercu sprite
- [x] Affichage des bonus de stats par race (`statModifiers`)
- [x] Limite configurable du nombre de personnages par compte (defaut : 1)
- [x] Selecteur de personnage au login si le joueur en possede plusieurs
- [x] Refactoring du `RegistrationController` : inscription compte → redirection creation personnage

---

### Piste D — Feedback visuels & celebrations (parallelisable)

### ~~137 — Feedback visuels combat (M | ★★)~~ ✅
> Prerequis : ∅
- [x] Damage numbers flottants (texte jaune degats, rouge critique, vert soin, gris miss)
- [x] Auras d'effets de statut sur les cartes combattants (lueur coloree : poison vert, brulure orange, bouclier bleu, etc.)
- [x] Screen shake sur coup critique ou danger (deja implemente precedemment)
- [x] Boss health bar (barre rouge en haut d'ecran avec nom du boss, phase et animation low HP)
- [x] Notification synergie elementaire (banniere "SYNERGIE" animee avec label)

### ~~138 — Feedback progression & celebrations (S | ★★)~~ ✅
> Prerequis : ∅
- [x] Popup "Competence debloquee !" quand un palier de domaine est atteint
- [x] Banniere "Succes debloque" avec animation pour les achievements
- [x] Toast d'animation craft reussi avec XP gagnee
- [x] Notification visuelle Mercure quand un joueur proche atteint un palier

### ~~139 — Comparaison d'equipement & QoL inventaire (S | ★★)~~ ✅
> Prerequis : ∅
- [x] Tooltip/modal de comparaison avant equipement (delta stats : +3 ATK, -1 DEF)
- [x] Apercu de l'objet au hover (stats, rarete, description)
- [x] File d'attente de craft (input quantite, craft en arriere-plan)
- [x] Timer reset quetes quotidiennes visible ("Prochain reset : 14h32")

---
