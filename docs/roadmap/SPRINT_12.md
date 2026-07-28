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
- [x] **Jalon F — Plan de scaling horizontal** 🔍 **audit fait** — 5 obstacles verifies dans
      [`LOAD_TESTING_BOTTLENECKS.md`](../LOAD_TESTING_BOTTLENECKS.md). **F.0 est bloquant et
      anterieur au scaling** : aucun processus ne consomme le calendrier des taches, donc
      **aucune tache recurrente ne tourne** (expiration d'escrow, loyers, restock PNJ, saisons,
      releve de masse monetaire). Preuve : `api:mob:move` etait planifiee toutes les minutes
      alors que ZON-21 l'avait supprimee. Garde-fou `ScheduledCommandTest` livre ; les 7
      commandes recurrentes orphelines sont declarees.
- [x] **F.0 — activation rendue sure** ✅ — l'audit a trouve que brancher le worker tel quel
      prelevererait **une semaine de loyer par jour** a chaque proprietaire jusqu'a rattraper un
      arriere que personne n'a contracte (`extendRent()` repart de l'echeance precedente, et une
      execution ne rattrape qu'une periode). `app:economy:rent-backlog-reset` efface cet arriere,
      et le mode d'emploi documente les trois pieges (arriere, entrypoint, nombre de repliques).
- [ ] **F.0 — ajouter le service `worker`** a `compose.prod.yaml` (changement d'infrastructure,
      **non testable sans Docker** et le CD deploie automatiquement sur `main` — a appliquer par
      l'exploitant en suivant le mode d'emploi)
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
- [x] **Passe de non-regression i18n post-pivot** ✅ — les quatre ecrans nes du pivot sont
      **integralement traduits** (mesure faite, aucun texte code en dur) et `HardcodedTextTest` les
      maintient a zero. Le reste de l'interface joueur porte **163 extraits sur 42 gabarits**,
      **geles** par plan de reference : un gabarit hors plan doit etre propre, un gabarit endette ne
      doit pas empirer. Les nombres sont faits pour baisser (voir lots ci-dessous).
- [ ] **Resorption de la dette gelee**, par lots. Les plus lourds : `housing/index` (16),
      `quest/index` (10), `auction/index` et `skills/index` (9), `inventory/equipment/_list` (8,
      dont douze libelles d'emplacement passes en dur a une macro), `craft_order/new` (8).
      `templates/admin/` reste hors perimetre (ecrans d'exploitant).
- [x] **Garde-fou CI** ✅ — `App\Translation\TranslationCatalogAudit` porte la logique,
      `TranslationCatalogAuditTest` la verifie a chaque build, et le job `lint` rend le meme verdict
      sans base de donnees. Premiere execution : une cle indefinie trouvee
      (`game.inventory.paper_doll_label`, dont le `|default` de repli etait mort a l'ecriture).

---

### 136 — La documentation de design dans l'admin (S | ★★)

> `/admin/roadmap` n'expose que la roadmap (index + 15 sprints + DONE). **Aucun document
> de design n'est consultable depuis l'admin** — GAME_PRINCIPLES, GAME_WORLD, GAME_ZONES,
> GAME_PROGRESSION, GAME_INSPIRATIONS, GAME_ZONE_ACTIONS, BALANCE, les plans annexes
> (`docs/roadmap/PLAN_*.md`) et l'atlas (`docs/atlas-zones-ressources.html`) ne se lisent
> que dans le depot. Constat du 2026-07-28.

- [ ] Onglet « Documentation » dans l'admin, sur le meme gabarit que la roadmap :
      table declarative fichier → libelle (les 3 constantes de `RoadmapController`
      montrent le motif ; extraire un controleur ou une constante dediee)
- [ ] Sections : Principes (GAME_PRINCIPLES), Monde (GAME_WORLD), Zones (GAME_ZONES +
      atlas HTML servi tel quel), Progression, Inspirations, Actions de zone, Balance,
      et un sous-onglet par plan annexe (PLAN_SETTLEMENTS, PLAN_RETENTION, PLAN_ZONES,
      PLAN_FACTIONS, PLAN_PLAYER_ECONOMY…)
- [ ] Ajouter un document = ajouter une entree de table, pas du code (meme regle que
      les sprints, CLAUDE.md regle 13)
- [ ] Tests : rendu d'un document, fichier manquant tolere, l'atlas HTML servi sans
      echappement

---

### Definition of Done

- [ ] Load test passe avec 200 joueurs simultanes (scenarios modele zone)
- [ ] Traduction anglaise complete de l'interface **et** du contenu de jeu
- [x] Selecteur de langue fonctionnel
