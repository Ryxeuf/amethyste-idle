## Sprint 12 — Technique & i18n

> **2 taches** | Priorite : **Basse** | Origine : Vague 10, Piste C
> Objectif : preparer l'infrastructure pour la montee en charge et l'internationalisation.
> Prerequis : Sprints 1-6 recommandes (contenu stable avant scaling)

---

### 134 — Load testing & scaling (M | ★★)
> Prerequis : ∅
- [x] Script k6/Locust pour simuler 100+ joueurs simultanes — infrastructure k6 (`scripts/load-test/`) + scenario `guest-browsing` (home, login, register, demo, /health, /metrics). Ramp-up/plateau/ramp-down configurable, thresholds p95<800ms + <1% erreurs, export JSON pour CI. Documentation dans `scripts/load-test/README.md`.
- [ ] Identification goulots d'etranglement (DB, Mercure, FrankenPHP)
- [ ] Optimisations : connection pooling, cache Redis, horizontal scaling plan
- [ ] Objectif : 200 joueurs simultanes sans degradation

### 135 — Localisation i18n (M | ★)
> Prerequis : ∅
- [ ] Extraction des chaines via Symfony Translation (xliff)
- [ ] Traduction EN prioritaire (UI, items, quetes, dialogues)
- [ ] Selecteur de langue dans les parametres joueur
- [ ] Contenu de jeu multilingue (noms items, descriptions sorts)

---

### Definition of Done

- [ ] Load test passe avec 200 joueurs simultanes
- [ ] Traduction anglaise complete de l'interface
- [ ] Selecteur de langue fonctionnel
