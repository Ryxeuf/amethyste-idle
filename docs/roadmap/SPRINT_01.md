## Sprint 1 — Stabilite & Onboarding

> **3 taches** | Priorite : **Critique** | Origine : Vague 7 (restant)
> Objectif : corriger les derniers bugs, equilibrer le combat, et creer le tutoriel joueur.
> Prerequis : ∅ (peut demarrer immediatement)

---

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

### 113 — Tutoriel / onboarding nouveau joueur (M | ★★★)
> Prerequis : ∅
- [ ] Sequence tutoriel : deplacement → combat → inventaire → quetes → craft
- [ ] Indicateurs visuels (fleches, highlights) pour guider le joueur
- [ ] PNJ tuteur avec dialogues contextuels
- [ ] Possibilite de skip pour les joueurs experimentes
- [ ] Achievement "Premier pas" a la fin du tutoriel

---

### Definition of Done

- [ ] Tous les bugs critiques corriges et verifies
- [ ] Formules de degats equilibrees (ecart < 30%)
- [ ] Tutoriel fonctionnel de bout en bout
- [ ] Tests de non-regression passes
