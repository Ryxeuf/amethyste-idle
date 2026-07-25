## Sprint 12 — Technique & i18n

> **2 taches** | Priorite : **Basse** | Origine : Vague 10, Piste C
> Objectif : preparer l'infrastructure pour la montee en charge et l'internationalisation.
> Prerequis : Sprints 1-6 ✅ (contenu stable avant scaling)

> **Menage 2026-07-25** : ce fichier pesait **181 Ko** pour 2 taches — il accumulait le detail de
> ~60 sous-phases livrees. Detail retire (regle projet #13). Reference :
> [`ROADMAP_DONE.md`](../ROADMAP_DONE.md) ; forme d'origine conservee dans
> [`ARCHIVE_SPRINT_11_12.md`](ARCHIVE_SPRINT_11_12.md).

---

### 134 — Load testing & scaling (M | ★★)
> Prerequis : ∅
> **Livre** : infra k6 (`scripts/load-test/`) + 4 scenarios (`guest-browsing`, `metrics-stress`,
> `mercure-streaming`, `authenticated-gameplay`) + `run-all.sh` ; analyse consolidee dans
> [`LOAD_TESTING_BOTTLENECKS.md`](../LOAD_TESTING_BOTTLENECKS.md) (jalons **C** et **D** ✅ :
> indexes ciblés `/metrics`, cache TTL des collectors, partial index `idx_fight_in_progress`,
> refactor `MobRepository::findByMapWithMonster`).
> **Realignement fait** : les scenarios ciblent le modele zone depuis **ZON-24** ✅. Reste le
> **jalon Z** de `LOAD_TESTING_BOTTLENECKS.md` : aucune mesure n'a encore ete prise sur ce profil,
> c'est le prerequis de l'objectif 200 joueurs (les jalons C et D ont ete valides sur un profil
> carte qui n'existe plus).

- [ ] **Jalon A — Cache Redis** (cache applicatif partage, sessions, cache Doctrine de 2e niveau)
- [ ] **Jalon B — PgBouncer** (pooling de connexions PostgreSQL)
- [ ] **Jalon E — Hardening Mercure** (limites d'abonnes, backpressure, TTL des topics)
- [ ] **Jalon F — Plan de scaling horizontal** (multi-instances FrankenPHP derriere Traefik)
- [ ] **Jalon Z — passe de mesure sur le profil zone** (prerequis : etalonner les 4 scenarios realignes)
- [ ] **Objectif : 200 joueurs simultanes sans degradation** (mesure de validation finale)

### 135 — Localisation i18n (M | ★)
> Prerequis : ∅
> **Livre** : selecteur de langue (`enabled_locales` fr/en, `LocaleController`), parite de cles
> FR/EN sur l'UI (script d'audit `scripts/audit-translations.php`), i18n de **tous** les controllers
> Stimulus et des pages Twig statiques, et infrastructure multilingue `*_translations` + filters Twig
> + fixtures EN pour : items (noms + descriptions), monstres, quetes, PNJ, races, regions, domaines,
> succes, competences, effets de statut, factions (+ recompenses), donjons, recettes, enchantements,
> montures, festivals, cartes, defis de guilde.

- [ ] **2c — Traduction EN du contenu de jeu restant** : dialogues PNJ (arbres de dialogue),
      descriptions de sorts, et les entites ajoutees depuis le pivot (`Zone`, `CodexEntry`,
      `GameEvent`, `ZoneBoss`, `GroupDungeonRun`) — verifier la parite via `scripts/audit-translations.php`
- [ ] **Passe de non-regression i18n post-pivot** : les ecrans nes du pivot (`/game/zone`,
      `/game/world-map`, banniere de donjon de groupe, banniere de boss de zone) doivent etre
      couverts par `messages.{fr,en}.json` au meme niveau que le reste de l'UI
- [ ] Garde-fou CI : echec du build si la parite de cles FR/EN regresse (le script d'audit existe,
      il n'est pas branche sur la CI)

---

### Definition of Done

- [ ] Load test passe avec 200 joueurs simultanes (scenarios modele zone)
- [ ] Traduction anglaise complete de l'interface **et** du contenu de jeu
- [x] Selecteur de langue fonctionnel
