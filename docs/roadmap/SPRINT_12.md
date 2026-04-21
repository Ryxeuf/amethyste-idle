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
> Avancement : sous-phases 1 (selecteur de langue securise), 2a (parite UI FR/EN atteinte) et 2b (audit des cles utilisees) livrees.
- [x] Extraction des chaines via Symfony Translation — base existante sous `translations/messages.{fr,en}.json` (format JSON arborescent, deja exploite par `|trans`). Non re-extrait en XLIFF (choix projet de conserver JSON).
- [~] Traduction EN prioritaire (UI, items, quetes, dialogues) — sous-phase 2a livree 2026-04-21
  - [x] **2a — Parite de cles FR/EN sur l'UI** (2026-04-21) : audit systematique de `messages.{fr,en}.json` via flatten + diff ensembliste revele 19 cles presentes en FR mais absentes en EN. Ajout cible : `game.nav.bestiary` + `game.nav.craft` (entrees de navigation) et l'integralite du namespace `game.bestiary.*` (17 cles : `title`, `subtitle`, `discovered`, `total_kills`, `kills`, `tier_weaknesses`, `tier_loot`, `tier_title`, `weaknesses`, `loot_table`, `loot_probability`, `next_tier`, `empty`, `empty_hint`, `boss`, `level`, `difficulty`). Resultat : 432 cles FR = 432 cles EN, ecart nul. Impact immediat : la page `/game/bestiary` et les liens de navigation ne tombent plus sur le fallback de cle brute en anglais.
  - [x] **2b — audit des cles utilisees mais absentes de `messages.*.json`** (2026-04-21) : nouveau script `scripts/audit-translations.php` qui scanne `templates/` et `src/` pour extraire via regex les cles `|trans` (Twig) et `->trans(` (PHP), flatten les fichiers `messages.fr.json` / `messages.en.json` et compare. Flag `--active-only` pour ignorer `templates/old_game/` (legacy). Exit code 1 si une cle manque ou si la parite FR/EN est rompue (utilisable en pre-commit ou CI). Audit initial revele 82 cles manquantes dont 76 dans `templates/old_game/` (legacy non reference depuis `src/` ni `config/` — volontairement ignorees) et 6 dans les templates actifs : `game.map.aria_label`, `game.map.dialog_aria`, `game.map.close_dialog`, `game.map.dialog_choices`, `game.map.dialog_next` (`templates/game/map/index.html.twig`) et `game.fight.turns` (`templates/game/fight/index.html.twig` + `partials/_timeline.html.twig`). Les 6 cles sont ajoutees en FR et EN avec traductions concordantes (aucune casse du `|default(...)` existant cote template). Parite maintenue : 438 cles FR = 438 cles EN.
  - [ ] **2c** — traduction EN des items, quetes, dialogues PNJ (contenu de jeu, necessite systeme dedie cf. sous-phase 4)
- [x] Selecteur de langue dans les parametres joueur — `framework.yaml` declare `enabled_locales: ['fr', 'en']` ; `LocaleController` valide strictement la locale contre cette whitelist (fallback `default_locale` si inconnue), bloque les payloads exotiques via contrainte regex de route, verifie que le referer pointe vers le meme host avant redirection. `/game/settings` expose un `<select>` branche a un controller Stimulus `locale-switcher` qui redirige vers `/change-locale/{locale}` au changement ; l'option selectionnee reflete la locale courante (`app.request.locale`). Tests unitaires (`LocaleControllerTest`, 6 cas) couvrent les chemins valide / invalide / payload malveillant / referer safe-or-not.
- [ ] Contenu de jeu multilingue (noms items, descriptions sorts) — non entame (necessitera colonnes `nameTranslations`/`descriptionTranslations` ou un systeme dedie)

---

### Definition of Done

- [ ] Load test passe avec 200 joueurs simultanes
- [ ] Traduction anglaise complete de l'interface
- [ ] Selecteur de langue fonctionnel
