# Plan — Narration

> **Numérotation :** les jalons de **ce** document sont préfixés **NAR-** (Narrative).
> Ils n'entrent **pas** en conflit avec les numéros de la roadmap globale
> (`SPRINT_*.md`) ni avec les jalons **GCC-** / **ZON-** / **ECO-**.

> Narration adossée aux systèmes : trame de monde large + acte d'introduction fort +
> narration saisonnière épisodique, sur un monde **hybride** (méta-arc lent, épisodes
> résolubles). Poser la narration = *structurer* et *relier* l'existant (`Quest`,
> `GameEvent`, `InfluenceSeason`, `Region`, `Zone`, `Pnj`), pas bâtir un moteur de
> dialogue. Décisions de conception : [../GAME_PRINCIPLES.md](../GAME_PRINCIPLES.md) §3
> (D8 à D12).

## Vue d'ensemble

**14 jalons** (**NAR-01** à **NAR-14**) organisés en 5 pistes.
Prérequis roadmap : **modèle zone** (ZON, Sprints 7-9) pour les zones/régions et le
time-gating, **guildes & contrôle de cité** (GCC ✅) pour les crédits narratifs, et les
briques déjà présentes : `Quest` (chaînage, choix, event, renommée), `GameEvent`,
`InfluenceSeason.theme`, `WorldBossManager`.

| Code | Sujet (résumé) |
|------|----------------|
| NAR-01 | Marqueur d'arc sur `Quest` (`story_arc` + `arc_order`) |
| NAR-02 | Journal de quêtes regroupé par arc (UI) |
| NAR-03 | Arc d'introduction — chaîne scriptée enseignant les systèmes |
| NAR-04 | Intégration onboarding — fil conducteur & garantie de progression |
| NAR-05 | Entité Codex (`CodexEntry`) & déblocage par découverte |
| NAR-06 | Écran Codex — lecture de la trame (régions / factions) |
| NAR-07 | Journal de monde — faits canon horodatés |
| NAR-08 | Structure d'arc saisonnier — `theme` → 4 beats `GameEvent` |
| NAR-09 | Quêtes d'événement de saison (branchement `Quest.gameEvent`) |
| NAR-10 | Boss / climax de saison (généralisation `WorldBossManager`) |
| NAR-11 | Résolution de saison & crédits narratifs (guilde gagnante) |
| NAR-12 | Marquage « canon » d'un beat → entrée journal de monde |
| NAR-13 | Gabarits de quêtes de fond (procédural + ancrages écrits) |
| NAR-14 | Tests unitaires du plan |

```
Piste A — Socle narratif       : NAR-01 → NAR-02
Piste B — Acte d'introduction  : NAR-03 → NAR-04
Piste C — Codex & trame        : NAR-05 → NAR-06 → NAR-07
Piste D — Narration saisonnière: NAR-08 → NAR-09 → NAR-10 → NAR-11 → NAR-12
Piste E — Contenu de fond      : NAR-13, NAR-14
```

**Ordre de valeur/effort** (cf. GAME_PRINCIPLES §3) :
`Piste A → Piste B → Piste C → Piste D → Piste E`. Le socle d'arc (A) débloque
l'affichage et le marquage de toutes les couches ; l'acte d'intro (B) est le crochet
d'onboarding, prioritaire pour la rétention précoce ; la narration saisonnière (D) est le
moteur récurrent qui referme la boucle à trois piliers.

---

## Piste A — Socle narratif (séquentiel)

### NAR-01 — Marqueur d'arc sur `Quest` ✅ (livré 2026-07-24 — cf. `ROADMAP_DONE.md`)
> Fondation livrée : champs `Quest.storyArc` / `Quest.arcOrder`, `QuestRepository::findByStoryArc()`,
> helper `Quest::sortByArcOrder()`, index `idx_game_quests_story_arc`, migration idempotente, tests.
> Reste optionnel (déféré au contenu) : **backfill** des chaînes de quêtes existantes vers un arc —
> à faire quand c'est pertinent avec l'arc d'intro (NAR-03), sinon `storyArc = null`.

### NAR-02 — Journal de quêtes regroupé par arc ✅ (livré 2026-07-24 — cf. `ROADMAP_DONE.md`)
> Rendre les arcs lisibles côté joueur : suivre un fil, pas une liste plate. L'écran
> `/game/quests` regroupe les quêtes actives/terminées par `storyArc` (progression `n/total`),
> quêtes isolées sous « Divers ». Service `QuestArcGrouper`, cartes de quête en partials Twig,
> badge « Étape N ». Tests : `QuestArcGrouperTest`.

---

## Piste B — Acte d'introduction (séquentiel)

### NAR-03 — Arc d'introduction scripté ✅ (livré 2026-07-24 — cf. `ROADMAP_DONE.md`)
> Backfill de la chaîne d'onboarding Acte 1 vers l'arc `intro` (`storyArc='intro'`,
> `arcOrder` 1-7) + deux étapes ajoutées (craft T1 « première potion », consultation des
> guildes). Chaque étape enseigne un système : voyage/explore → équipement → combat →
> récolte → lore → craft T1 → guilde. Mentor fil rouge : Claire la Sage (`pnj_15`).
> Chaînage `prerequisiteQuests` linéaire, back-patch du `pnj_id` mentor. Tests :
> `IntroArcFixturesTest` (ordre contigu, chaîne de prérequis, étapes craft/guilde).

### NAR-04 — Intégration onboarding & garantie de progression ✅ (livré 2026-07-24 — cf. `ROADMAP_DONE.md`)
> L'arc intro accorde un kit T1 **échangeable** (arme dotée d'un sort + soin), rendant la
> boucle cœur accessible à un joueur solo. Échangeabilité matérialisée par
> `PlayerItem::isExchangeable()` (non lié + non équipé) ; le `BindType` enum reste déféré à
> ECO-01. 2ᵉ personnage : intro **rejouée intégralement** (progression par `Player`).
> Tests : `OnboardingKitTest` (kit T1 échangeable, arme à sort, cold-start) +
> `PlayerItemExchangeableTest`.

---

## Piste C — Codex & trame de monde (séquentiel)

### NAR-05 — Entité Codex & déblocage par découverte ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> Entité dédiée `CodexEntry` (slug, catégorie, titre/corps + `*_translations`, couple
> déclaratif `unlockType`/`unlockKey`, `illustrationPath`) + liaison `PlayerCodexEntry`
> (déblocage horodaté, unique). `CodexUnlockService` idempotent + 3 subscribers :
> visite de zone (nouvel `ZoneVisitedEvent`), kill de boss (`MobDeadEvent`), fin d'arc
> (`QuestCompletedEvent` + `countCompletedInArc`). Clôture de saison **déférée** à la
> Piste D (NAR-11/12). Décision actée : **entité dédiée** (pas d'extension des succès).
> Migration idempotente, fixtures (4 entrées), tests `CodexUnlockServiceTest` + `CodexEntryTest`.

### NAR-06 — Écran Codex ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> Route/UI `/game/codex` (`CodexController` + `game/codex/index.html.twig`) : entrées
> débloquées lisibles par catégorie, entrées verrouillées teasées (titre masqué + indice
> de déblocage par type), complétion `n/total`, localisation via `getLocalizedTitle/Description`.
> Lien de navigation (desktop + tiroir mobile). Tests : `CodexControllerTest` + route
> ajoutée au `SmokeTest`.

### NAR-07 — Journal de monde ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> Faits canon publics, horodatés, visibles de tous. `CodexEntry` de catégorie `world_fact`
> **publique** (`isPublic()`, hors complétion), champ `creditedGuildName` (mention de la
> guilde, branchée en NAR-11), `WorldFactService::recordWorldFact()` idempotent par slug.
> Affichage chronologique (fil de l'histoire du serveur, plus récent en tête) dans l'écran
> Codex. Tests : `WorldFactServiceTest`, `CodexControllerTest` (exclusion complétion), `CodexEntryTest`.

---

## Piste D — Narration saisonnière (séquentiel — moteur récurrent)

### NAR-08 — Structure d'arc saisonnier ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> `theme` devient un mini-arc en 4 beats datés : `GameEvent` enrichi de `season`
> (ManyToOne `InfluenceSeason`) + `beat` (amorce/montée/climax/résolution) + `beatOrder`,
> avec fenêtres temporelles. `GameEventRepository::findBySeasonOrdered`, `SeasonArcService`
> (`getBeats`, `getActiveBeat` à un instant donné via `GameEvent::isActiveAt`), convention
> `InfluenceSeason::getStoryArc()` = `season_<slug>`. Composition déclarative (`SeasonArcFixtures`,
> Saison 1). Tests : `GameEventBeatTest`, `SeasonArcServiceTest`, `SeasonArcFixturesTest`.

### NAR-09 — Quêtes d'événement de saison ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> 4 quêtes de la Saison 1 (arc `season_saison-1`), une par beat, rattachées via
> `Quest.gameEvent` → actives **seulement** dans la fenêtre de leur beat (`isEventActive()`).
> Quête de montée = kills (nourrit l'effort de contrôle des régions). Fixtures dans
> `QuestFixtures` (dépend de `SeasonArcFixtures`). Tests : `SeasonQuestFixturesTest`
> (4 quêtes ordonnées rattachées à un beat, montée active / climax gaté par la fenêtre).

### NAR-10 — Boss / climax de saison ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> `WorldBossManager` généralisé : spawn aussi sur le **beat de climax** d'un arc de saison
> (gate de type élargi + params de boss sur le beat), despawn en fin de fenêtre. Le boss
> hérite du modèle existant — combat partagé **asynchrone à la contribution** (`Fight.contributions`)
> et **loot par contribution** (`WorldBossLootDistributor`, top-3 + proportionnel) sans changement.
> `Mob::isSeasonBoss()`. Fixture : `forest_guardian` sur le climax de la Saison 1. Tests :
> `WorldBossManagerTest` (spawn climax / pas hors climax), `MobSeasonBossTest`.
> **Déféré** : énergie-par-assaut (change le flux de mouvement/combat, hors périmètre narratif) —
> le modèle actuel reste asynchrone et sans présence simultanée requise.

### NAR-11 — Résolution de saison & crédits narratifs ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> À la clôture (`SeasonTickCommand::handleExpiredSeasons`), `SeasonResolutionService::resolve`
> consomme le résultat de `TownControlManager::attributeControl` (slug région → guilde
> contrôlante) et **crédite la guilde gagnante au journal de monde** (fait canon public via
> `WorldFactService`, NAR-07 ; `creditedGuildName`). Une seule branche : seul le nom crédité
> varie. Cas sans guilde → fait de résolution neutre. Idempotent par slug. Tests :
> `SeasonResolutionServiceTest`, `SeasonTickCommandTest` (mock ajouté).

### NAR-12 — Marquage « canon » & entrée journal de monde ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> Monde hybride : marqueur `InfluenceSeason::isCanon()` (colonne `is_canon`, migration
> idempotente). `SeasonResolutionService::resolve` gate désormais l'écriture au journal de
> monde — seule une saison **canon** génère des `world_fact` (crédit de guilde, NAR-11) ;
> une saison non-canon se clôture **sans trace durable**. Saison 1 marquée canon (fixture).
> Tests : `SeasonResolutionServiceTest` (canon → fait / non-canon → aucun), `InfluenceSeasonCanonTest`.

---

## Piste E — Contenu de fond & tests (parallélisable)

### NAR-13 — Gabarits de quêtes de fond ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> Chaîne de zone d'exemple « Forêt des Murmures » (`storyArc='zone_foret-des-murmures'`,
> 3 étapes) : objectifs/récompenses dérivés des tables de zone (herbe `plant-mint`, faune
> `wolf`/`mushroom_golem`), avec un **nœud saillant écrit à la main** (le Cœur endormi,
> révélation liée au lore). Gating **découverte** (`isHidden` + `triggerCondition` explore)
> puis **renommée** (`minRenownScore` croissant 50→100). **Non bloquant** : aucune quête
> système ne dépend d'une quête de fond. Test : `BackgroundQuestFixturesTest`.

### NAR-14 — Tests unitaires du plan ✅ (livré 2026-07-25 — cf. `ROADMAP_DONE.md`)
> Tests livrés au fil des jalons (**80+ méthodes** dédiées à la narration, objectif 25+
> largement dépassé) : marqueur d'arc, Codex, arc saisonnier, crédits & canon. Consolidation :
> test de contrat transverse (`NarrativePlanContractTest`) verrouillant le vocabulaire
> déclaratif, et synthèse [NARRATIVE_TEST_COVERAGE.md](NARRATIVE_TEST_COVERAGE.md).

---

## 🎉 Plan narratif complet — NAR-01 → NAR-14 livrés (2026-07-25)

Les 14 jalons des 5 pistes (socle d'arc, acte d'introduction, Codex & trame, narration
saisonnière, contenu de fond & tests) sont livrés et mergés. Voir `ROADMAP_DONE.md` pour le
détail de chaque jalon.

---

## Ordre d'implémentation recommandé

```
Phase 1 (socle)        : NAR-01 → NAR-02
Phase 2 (intro)        : NAR-03 → NAR-04
Phase 3 (Codex)        : NAR-05 → NAR-06 → NAR-07
Phase 4 (saisons)      : NAR-08 → NAR-09 → NAR-10 → NAR-11 → NAR-12
Phase 5 (fond & tests) : NAR-13, NAR-14  (parallélisable)
```

---

## Vague 2 — l'an 1 des marées (NAR-15 → NAR-19, ouverte le 2026-07-28)

> Décline [../GAME_SEASONS.md](../GAME_SEASONS.md) (la partition de l'an 1 : colonne
> vertébrale canon, rotation par indice mondial le plus faible, conséquences
> déclenchées). Le moteur d'arc est livré (vague 1) — cette vague écrit du **contenu**
> et branche la **règle de tirage**.

### NAR-15 ✅ — Le tireur de marées (M | ★★★ | HAUTE) — livré le 2026-08-19
> La partition §0 devient un système : conséquence déclenchée > colonne datée > rotation.
> Prérequis : moteur d'arc livré ; conditions Pâleur/Crue ← FOY-11 / FOY-08
- [x] **Livré par FOY-15 (2026-07-28)** : la priorité des conséquences sur la rotation —
      préemption du prochain créneau seulement si aucun thème n'est déjà posé
      (`SeasonTickCommand::handleConsequenceTide`)
- [x] **Livré par FOY-15 (2026-07-28)** : la Pâleur passe avant l'Appel si les deux
      conditions sont vraies (`ConsequenceTide::precedence()`)
- [x] **La colonne vertébrale** (2026-08-19) — les créneaux M2/M4/M8/M13 entrent dans
      la donnée (bloc `canon` de `tides.yaml`, indexé par `InfluenceSeason::seasonNumber`).
      **Elle réserve, elle n'écrit pas** : le contenu de chaque marée canon arrive avec
      son jalon (NAR-16 → NAR-19), et tant qu'il n'est pas là le créneau reste **vide
      plutôt que pris**. C'est tout l'objet du jalon — sans elle, une rotation ou une
      conséquence prenait le créneau M2 et « La Première Pierre » n'arrivait jamais
- [x] **La rotation au plus faible indice de sédiment mondial** (2026-08-19) —
      `RotationTideSelector` somme les quatre indices sur **tous** les foyers (lire le
      foyer dominant ferait dépendre la partition de l'humeur d'une ville). Un gabarit à
      deux indices est éligible par le **plus bas** des deux. **Le tirage n'en est pas
      un** : rien n'est tiré au sort, ici pas plus qu'au Répertoire (REP-03) — à manque
      égal, c'est le gabarit **joué il y a le plus longtemps** qui passe, si bien que la
      variété vient de l'histoire du serveur et non d'un dé
- [x] **Les 6 gabarits rejouables** en données (2026-08-19) — la Marée d'Ambre, la
      Fonte, le Chœur, la Contrefaçon, la Grande Battue, la Foire Franche, chacun avec
      ses quatre beats et les indices qu'il nourrit. Les quatre indices sont couverts,
      vérifié en CI : sans cela, un serveur qui manquerait de rite se verrait prescrire
      autre chose indéfiniment
- [x] Tests : les trois voix et leur ordre, la réservation non préemptable, le tirage
      par indice, le départage par ancienneté, le déterminisme, les refus du chargeur

> **La question que le plan laissait ouverte est tranchée : un fichier, un chargeur, un
> sélecteur.** Le plan mettait en garde contre « un second sélecteur concurrent de celui
> que FOY-15 a posé ». La réponse n'est pas une cohabitation mais une **subordination** :
> `consequence_tides.yaml` devient `tides.yaml` et porte les trois voix ; `TideSelector`
> est le seul endroit où l'ordre *conséquence > colonne > rotation* existe, et il
> **délègue** — le service de FOY-15 pour les conséquences, `RotationTideSelector` pour
> le tirage. Chaque voix garde son service pour répondre à sa propre question, et un
> seul endroit décide laquelle parle. Le chargeur et le composeur, qui ne servent plus
> les seules conséquences, sont renommés en conséquence.

> **Ce que le jalon a tranché.** GAME_SEASONS se contredit à un endroit : le § 0 ordonne
> *conséquence > colonne > rotation*, et le § 3 dit qu'une conséquence préempte « le
> prochain créneau **de rotation** ». Lu au premier degré, le § 0 laisserait une Pâleur
> prendre le créneau M2 — et « La Première Pierre » ne se jouerait jamais, ce que le § 1
> interdit (« cinq marées canon à date à peu près fixe : elles portent l'histoire du
> serveur »). Le § 0 ordonne donc les trois voix **pour un créneau libre** ; un créneau
> que la partition a réservé n'en est pas un. C'est aussi ce que FOY-15 avait déjà décidé
> pour une marée écrite, et la colonne étend la règle plutôt que d'en créer une seconde :
> *un créneau déjà pris est déjà pris, qu'il le soit par un thème posé ou par la
> partition*.

### NAR-16 — La Première Pierre (M | ★★★ | HAUTE)
> M2 — le premier Bourg comme événement fondateur. Se cale sur BALANCE §23.3.
- [ ] 4 beats (annonce, chantiers rivaux à jauges publiques, dernière semaine, consécration)
- [ ] Le nom de la guilde bâtisseuse au journal de monde (canon)
- [ ] Tests : jauges, résolution, entrée canon

### NAR-17 — Le Procès de la Fonderie (M | ★★★ | HAUTE)
> M4 — l'axe doctrinal s'ouvre. Fondre/lire compte double au dossier pendant la marée.
> Prérequis : ← FAC-04 (fondre/lire)
- [ ] 4 beats (incident, instruction, audience, verdict = la dominante du serveur)
- [ ] Résolution : doctrine inscrite au journal, atelier de doctrine au foyer vainqueur
      (← FOY-13), démarrage du Programme du Cercle
- [ ] Tests : comptage double, verdict dérivé des gestes réels

### NAR-18 — La Marée Basse et le Grand Inventaire (M | ★★ | MOYENNE)
> M8 (le premier cran du Reflux — jamais résolu entièrement) et M13 (l'anniversaire).
- [ ] M8 : baisse globale d'une bande d'améthyste pendant la marée, premier Effacé hors
      de la Cité, entrée de Codex scellée — **aucune résolution complète** (signature du
      méta-arc, un seul cran par an)
- [ ] M13 : rétrospective jouable (défis rejouant l'année), monument de l'an 1 au Fanal
      dérivé du journal réel du serveur, récompenses annuelles en paliers (jamais de série)
- [ ] Tests : cran de méta-arc unique, monument dérivé des données

### NAR-19 — La Grande Battue & la Foire Franche (M | ★★ | MOYENNE)
> Les deux gabarits neufs — un par indice manquant (`war`, `trade`).
- [ ] Battue : faune débordante déclarative dans une zone tirée, tableau des primes
      (réutilise le système Chevaliers, FAC-09b), trophées de bestiaire
- [ ] Foire : foyer hôte tiré, étals éphémères par artisan (réutilise les échoppes,
      ECO Piste D), fenêtre de prix — précurseur des caravanes
- [ ] Tests : tirage de zone/foyer, fenêtres, récompenses

### NAR-20 — Révision de l'acte d'introduction : le réveil au Fanal ✅ (M | ★★ | MOYENNE)
> Livré avec **ZON-39**, qui touche les mêmes textes. **Livré le 2026-07-30.** Détail dans
> [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> L'essentiel : trois des cinq points étaient déjà livrés par la piste d'onboarding — la
> matéria garantie, accordée et lancée (ONB-12b), la lettre du foyer d'attache (ONB-13) et la
> rejouabilité du second personnage. Restait la **fiction**, posée sans qu'aucune mécanique
> bouge : le joueur est dit **Limpide** (étape 1), la matéria devient un éclat d'améthyste où
> un geste est resté lisible (étape 4), le Fanal est **bâti sur la Voûte** (étape 9). Le
> teaser du Cristal est planté **deux fois** — une réplique d'Iris l'Alchimiste et une entrée
> de Codex « La Voûte » — et pas davantage. `ActOneNarrativeTest` tient la fiction là où
> `ActOneChainTest` tient la forme.
>
> **Écart assumé** : le jalon demandait une lettre **par race** ; GAME_ONBOARDING l'amende
> (le foyer d'attache se gagne, il ne se choisit pas) et ONB-13 a livré l'amendement — la
> lettre suit les gestes de l'acte I, pas le peuple.
