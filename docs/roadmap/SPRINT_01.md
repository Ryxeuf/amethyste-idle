## Sprint 1 — Stabilite & Onboarding

> **3 taches** | Priorite : **Critique** | Origine : Vague 7 (restant)
> Objectif : corriger les derniers bugs, equilibrer le combat, et creer le tutoriel joueur.
> Prerequis : ∅ (peut demarrer immediatement)

---

### ~~110 — Correction bugs connus & dette technique (M | ★★★)~~ ✅
> Prerequis : ∅
- [x] Audit des issues GitHub ouvertes et priorisation (voir `docs/audits/GITHUB_ISSUES_AUDIT_2026-04.md`)
- [x] Correction des bugs critiques gameplay (combat, inventaire, quetes)
- [x] Nettoyage code mort detecte par PHPStan
- [x] Verification coherence DB via `app:game:validate` en CI

### ~~111 — Equilibrage combat avance (M | ★★★)~~ ✅
> Prerequis : ∅
- [x] Rapport d'equilibrage via commande admin : DPS moyen par tier, temps de combat, taux de mort
- [x] Ajustement formules de degats si ecarts > 30% entre builds
- [x] Equilibrage donjons : difficulte vs recompenses
- [x] Equilibrage world boss : HP et loot en fonction du nombre de joueurs actifs

### ~~113 — Tutoriel / onboarding nouveau joueur (M | ★★★)~~ ✅
> Prerequis : ∅
- [x] Sequence tutoriel : deplacement → combat → inventaire → quetes → craft
- [x] Indicateurs visuels (fleches, highlights) pour guider le joueur
- [x] PNJ tuteur avec dialogues contextuels
- [x] Possibilite de skip pour les joueurs experimentes
- [x] Achievement "Premier pas" a la fin du tutoriel

---

### Definition of Done

- [x] Tous les bugs critiques corriges et verifies
- [x] Formules de degats equilibrees (ecart < 30%)
- [x] Tutoriel fonctionnel de bout en bout
- [x] Tests de non-regression passes

---

**Statut : ✅ Sprint 1 termine (2026-04-11)** — Voir `docs/ROADMAP_DONE.md` (Sprint 1) et `docs/audits/GITHUB_ISSUES_AUDIT_2026-04.md`.
