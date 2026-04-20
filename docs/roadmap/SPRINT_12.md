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
> Avancement : sous-phase 1 livree (selecteur de langue securise).
- [x] Extraction des chaines via Symfony Translation — base existante sous `translations/messages.{fr,en}.json` (format JSON arborescent, deja exploite par `|trans`). Non re-extrait en XLIFF (choix projet de conserver JSON).
- [ ] Traduction EN prioritaire (UI, items, quetes, dialogues) — couverture partielle existante (`messages.en.json`), audit exhaustif restant a faire
- [x] Selecteur de langue dans les parametres joueur — `framework.yaml` declare `enabled_locales: ['fr', 'en']` ; `LocaleController` valide strictement la locale contre cette whitelist (fallback `default_locale` si inconnue), bloque les payloads exotiques via contrainte regex de route, verifie que le referer pointe vers le meme host avant redirection. `/game/settings` expose un `<select>` branche a un controller Stimulus `locale-switcher` qui redirige vers `/change-locale/{locale}` au changement ; l'option selectionnee reflete la locale courante (`app.request.locale`). Tests unitaires (`LocaleControllerTest`, 6 cas) couvrent les chemins valide / invalide / payload malveillant / referer safe-or-not.
- [ ] Contenu de jeu multilingue (noms items, descriptions sorts) — non entame (necessitera colonnes `nameTranslations`/`descriptionTranslations` ou un systeme dedie)

---

### Definition of Done

- [ ] Load test passe avec 200 joueurs simultanes
- [ ] Traduction anglaise complete de l'interface
- [ ] Selecteur de langue fonctionnel
