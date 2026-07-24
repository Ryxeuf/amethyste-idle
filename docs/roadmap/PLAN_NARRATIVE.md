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

### NAR-04 — Intégration onboarding & garantie de progression (S | ★★★ | HAUTE)
> Aucun joueur bloqué : l'intro garantit l'accès au T1 et à la première boucle systémique.
> Prérequis : ← NAR-03, ECO-02 (plancher T1 / kit d'onboarding)
- [ ] Récompenses de l'arc intro = kit T1 échangeable (`BindType::None`, cohérence ECO-01/02)
- [ ] Le joueur termine l'intro capable de jouer la boucle cœur **sans** dépendre d'un autre
      joueur (protection cold-start, cohérence GAME_PRINCIPLES §4.1)
- [ ] Multi-personnages (CLAUDE.md §12) : comportement de l'intro au 2ᵉ personnage à trancher
      (cf. GAME_PRINCIPLES §6) — rejoué vs raccourci
- [ ] Tests de parcours (nouveau compte → fin d'intro → boucle cœur accessible)

---

## Piste C — Codex & trame de monde (séquentiel)

### NAR-05 — Entité Codex & déblocage par découverte (M | ★★ | MOYENNE)
> Foyer de la trame de monde ; chaque entrée se débloque en jouant.
> Prérequis : ← modèle zone (ZON) pour les régions/zones
- [ ] Entité `CodexEntry` : slug, catégorie (`region` / `faction` / `bestiary_lore` /
      `world_fact`), titre + corps (avec `*_translations`), condition de déblocage (JSON),
      `illustrationPath` (nullable)
- [ ] Entité de liaison `PlayerCodexEntry` (déblocage par joueur, horodaté)
- [ ] Déclencheurs de déblocage : visite de zone, kill de boss, fin d'arc, clôture de saison
      (réutiliser les Events domaine existants — architecture Event-Driven)
- [ ] Décision (GAME_PRINCIPLES §6) : entité dédiée vs extension des succès existants — acter
- [ ] Migration + tests (déblocage idempotent, pas de doublon)

### NAR-06 — Écran Codex (S | ★★ | MOYENNE)
> Lecture de la trame large, au rythme du joueur, hors du flux de jeu.
> Prérequis : ← NAR-05
- [ ] Route/UI `/game/codex` : entrées débloquées par catégorie, entrées verrouillées en
      teasing (titre grisé + indice de déblocage)
- [ ] Complétion affichée (n/total) — objectif de collection, à croiser avec les succès
- [ ] Localisation via les champs `*_translations`
- [ ] Tests de rendu (débloqué / verrouillé / complétion)

### NAR-07 — Journal de monde (S | ★★ | MOYENNE)
> Le serveur « a une histoire » : faits canon horodatés, visibles de tous.
> Prérequis : ← NAR-05
- [ ] `CodexEntry` de catégorie `world_fact` générées par les résolutions de saison marquées
      « canon » (§3.2), horodatées, **débloquées pour tous** (fait public)
- [ ] Affichage chronologique dans le Codex (fil de l'histoire du serveur)
- [ ] Mention de la guilde créditée quand applicable (branché en NAR-11)
- [ ] Tests (création d'un fait canon, visibilité globale)

---

## Piste D — Narration saisonnière (séquentiel — moteur récurrent)

### NAR-08 — Structure d'arc saisonnier (M | ★★★ | HAUTE)
> `theme` cesse d'être un libellé : il nomme un mini-arc en 4 beats datés.
> Prérequis : ← NAR-01, `InfluenceSeason` (existant), `GameEvent` (existant)
- [ ] Modéliser l'arc de saison : `theme` + 4 `GameEvent` (amorce / montée / climax /
      résolution) rattachés à la saison, avec fenêtres temporelles
- [ ] Admin : composer un arc de saison de façon **déclarative** (ajouter une saison = ajouter
      de la donnée, pas du code)
- [ ] Un `storyArc` dédié par saison (`season_<slug>`) pour regrouper ses quêtes (réutilise
      NAR-01)
- [ ] Tests (séquencement des beats, fenêtres cohérentes)

### NAR-09 — Quêtes d'événement de saison (S | ★★ | MOYENNE)
> Chaque beat porte ses quêtes d'accroche, actives seulement dans sa fenêtre.
> Prérequis : ← NAR-08
- [ ] Quêtes rattachées aux `GameEvent` de la saison via `Quest.gameEvent` (déjà branchable ;
      `isEventActive()` gère la fenêtre)
- [ ] Quêtes thématiques de montée nourrissant l'accumulation d'influence (lien pilier cité)
- [ ] Fixtures d'exemple pour une première saison
- [ ] Tests (activation/désactivation selon la fenêtre d'événement)

### NAR-10 — Boss / climax de saison (M | ★★ | MOYENNE)
> Le climax = événement de zone à rejoindre (asynchrone, contribution).
> Prérequis : ← NAR-08, `WorldBossManager` (existant), modèle zone (ZON)
- [ ] Généraliser `WorldBossManager` / `WorldBossLootDistributor` en **boss de saison** de
      zone, annoncé sur la fenêtre de climax (cf. PIVOT §Contenu de groupe — asynchrone)
- [ ] Énergie pour lancer les assauts, loot à la contribution, aucune présence simultanée
      requise (cohérence PIVOT §Économie d'action)
- [ ] Récompenses de climax thématiques de la saison
- [ ] Tests (fenêtre, contribution, distribution)

### NAR-11 — Résolution de saison & crédits narratifs (M | ★★★ | HAUTE)
> Le liant de la boucle : la guilde gagnante est *créditée* de la résolution (D10).
> Prérequis : ← NAR-10, GCC (fin de saison / contrôle de région ✅)
- [ ] À la clôture de saison, issue **prédéfinie** (une seule branche) ; identifier la guilde
      contrôlante de la région (réutiliser la logique GCC / `RegionControl`)
- [ ] Crédits narratifs à la guilde gagnante : titre de saison, mention dans le récit de
      région, cosmétiques, **nom au journal de monde** (NAR-07)
- [ ] Aucune branche par vainqueur (pas de coût combinatoire) — seuls les crédits varient
- [ ] Tests (attribution des crédits, cas sans guilde contrôlante)

### NAR-12 — Marquage « canon » & entrée journal de monde (S | ★★ | MOYENNE)
> Le monde hybride : seuls les beats marqués « canon » laissent une trace durable.
> Prérequis : ← NAR-07, NAR-11
- [ ] Marqueur « canon » sur un beat/résolution de saison (curation, cf. GAME_PRINCIPLES §6)
- [ ] Un beat canon génère un `world_fact` horodaté (NAR-07), créditant la guilde (NAR-11)
- [ ] Les saisons non marquées se clôturent **sans** trace durable (au-delà des récompenses)
- [ ] Tests (canon → fait de monde ; non-canon → pas de fait)

---

## Piste E — Contenu de fond & tests (parallélisable)

### NAR-13 — Gabarits de quêtes de fond (M | ★★ | BASSE)
> Volume sans coût d'écriture intégral : squelettes procéduraux + ancrages écrits (§3.7).
> Prérequis : ← modèle zone (ZON), tables de zone déclaratives
- [ ] Gabarits de chaînes de quêtes de zone (structure, objectifs, récompenses dérivés des
      tables de zone existantes)
- [ ] **Nœuds saillants écrits à la main** (donneur mémorable, twist, révélation liée au lore)
- [ ] Gating par découverte (`isHidden`) et renommée (`minRenownScore`) — briques existantes
- [ ] Le contenu de fond **n'est jamais bloquant** pour la progression système
- [ ] Fixtures d'exemple sur 1-2 zones

### NAR-14 — Tests unitaires du plan (M | ★★ | HAUTE)
> Prérequis : ← NAR-01, NAR-05, NAR-08, NAR-11
- [ ] Tests marqueur d'arc (regroupement, tri, quêtes isolées)
- [ ] Tests Codex (déblocage par découverte, idempotence, journal de monde public)
- [ ] Tests arc saisonnier (séquencement des beats, quêtes d'événement selon fenêtre)
- [ ] Tests crédits narratifs & canon (attribution guilde, génération de `world_fact`)
- [ ] Objectif : 25+ tests unitaires

---

## Ordre d'implémentation recommandé

```
Phase 1 (socle)        : NAR-01 → NAR-02
Phase 2 (intro)        : NAR-03 → NAR-04
Phase 3 (Codex)        : NAR-05 → NAR-06 → NAR-07
Phase 4 (saisons)      : NAR-08 → NAR-09 → NAR-10 → NAR-11 → NAR-12
Phase 5 (fond & tests) : NAR-13, NAR-14  (parallélisable)
```
