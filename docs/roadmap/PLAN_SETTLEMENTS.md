# Plan — Foyers, Crue et Pâleur

> **Numérotation :** les jalons de **ce** document sont préfixés **FOY-** (Foyers).
> Ils n'entrent **pas** en conflit avec les numéros de la roadmap globale (`SPRINT_*.md`)
> ni avec les jalons **GCC-** / **ZON-** / **ECO-** / **NAR-**.

> Le monde cesse d'être un décor traversé : les joueurs le **bâtissent**. L'activité dépose
> du sédiment sur le **foyer** d'une zone, le foyer monte en rang et ouvre des services, la
> **Crue** limite le nombre de grandes cités, et l'oubli fait redescendre. Décisions de
> conception : [../GAME_WORLD.md](../GAME_WORLD.md) §3 (foyers), §5 (économie territoriale),
> §12.1 (Pâleur et Étale) — décisions **A → F** actées le 2026-07-27.
> Références du genre : [../GAME_INSPIRATIONS.md](../GAME_INSPIRATIONS.md) (Ashes of
> Creation, EVE, FFXIV/Ishgard, Black Desert, Wakfu, SWG).

## Vue d'ensemble

**16 jalons** (**FOY-01** à **FOY-16**) organisés en 6 pistes.

Prérequis roadmap — tous **livrés** :
**modèle zone** (ZON, Sprints 7-10) pour `Zone`, l'énergie et le time-gating ;
**contrôle de cité** (GCC ✅) pour l'influence, les saisons et le trésor de guilde ;
**économie joueur** (ECO Pistes A/B/C ✅) pour le HV régional et les commandes ;
**narration** (NAR ✅) pour le journal de monde et les arcs de marée.

Le pilier ne demande **aucun événement domaine nouveau** : `InfluenceListener` écoute déjà
exactement ce dont les foyers ont besoin (`MobDeadEvent`, `CraftEvent`, `SpotHarvestEvent`,
`FishingEvent`, `ButcheringEvent`, `QuestCompletedEvent`). On branche un second consommateur.

| Code | Sujet (résumé) |
|------|----------------|
| FOY-01 | Entité `Settlement` — rang, type, quatre indices, seed non nul |
| FOY-02 | Dépôt de sédiment (subscriber sur les events existants) |
| FOY-03 | Décroissance, calcul du rang et du type (hystérésis) |
| FOY-04 | Le foyer sur l'écran de zone — chantier lisible |
| FOY-05 | Gate déclaratif des services par rang |
| FOY-06 | Services gatés : marché local, banque, éveil de matéria |
| FOY-07 | Bonus d'atelier par foyer (ligne de production × type) |
| FOY-08 | Crue — quotas indexés sur la population active |
| FOY-09 | Zone d'influence & vassalité |
| FOY-10 | Étiage & régression bornée |
| FOY-11 | Pâleur — état de zone, effets sur rendement et pureté |
| FOY-12 | Restauration payée au trésor de guilde |
| FOY-13 | Ateliers de doctrine (Fonderie / Lecteurs) |
| FOY-14 | Crédit au journal de monde à la clôture de marée |
| FOY-15 | Marées « conséquence » (la Pâleur, l'Appel de la Crue) |
| FOY-16 | Tests unitaires du plan |

```
Piste A — Socle du foyer      : FOY-01 → FOY-02 → FOY-03 → FOY-04
Piste B — Ce que le rang ouvre: FOY-05 → FOY-06 → FOY-07
Piste C — La Crue             : FOY-08 → FOY-09 → FOY-10
Piste D — Pâleur              : FOY-11 → FOY-12
Piste E — Doctrine & guilde   : FOY-13 → FOY-14
Piste F — Contenu & tests     : FOY-15, FOY-16
```

**Ordre de valeur/effort** : `A → B → C → D → E → F`.
La Piste A seule ne donne rien au joueur (un compteur monte). **Le premier livrable utile est
A + FOY-05/06** : à partir de là, faire vivre une zone y ouvre un marché, et le pilier existe.
La Crue (C) est ce qui le rend *politique* ; sans elle, tout le monde monte tout, et il n'y a
pas d'enjeu.

**Hors périmètre**, volontairement :
les **caravanes** (GAME_WORLD §5.3) relèvent de l'économie — à ouvrir dans `PLAN_PLAYER_ECONOMY`
quand la Piste D des échoppes sera close ; la **pureté** des ressources (§5.4) relève de la
récolte et de l'artisanat — ce plan se contente de la **consommer** en FOY-11 ; le biome de
**l'Étale** et les **Effacés** relèvent du contenu de zone, pas du système territorial.

---

## Conventions de ce plan

**Les quatre indices.** Un foyer n'a pas un compteur mais quatre, qui décroissent
indépendamment (emprunt EVE, cf. GAME_INSPIRATIONS §2.4). Le **rang** se lit sur leur somme,
le **type** sur le dominant :

| Indice | Alimenté par | Donne le type |
|---|---|---|
| `trade` | `CraftEvent`, `SpotHarvestEvent`, `FishingEvent`, `ButcheringEvent`, ventes HV de la zone | **Comptoir** |
| `war` | `MobDeadEvent`, clears de donjon, assauts de boss de zone | **Bastion** |
| `lore` | `QuestCompletedEvent`, premières visites, entrées de Codex débloquées | **Athénée** |
| `rite` | Matéria lue chez les Lecteurs, participation aux beats de marée | **Sanctuaire** |

**Rien en dur.** Seuils de rang, taux de décroissance, quotas de Crue et coûts de
restauration sont des **paramètres** (`config/game/settlements.yaml` + `BALANCE.md`), jamais
des constantes de classe. Le calibrage se fait sans redéploiement de code.

**Anti-exploit réutilisé.** Le dépôt de sédiment passe par `InfluenceAntiExploit`
(plafonds journaliers, rendements décroissants) : on ne réécrit pas les garde-fous, on
s'y branche.

---

## Piste A — Socle du foyer (séquentiel)

### FOY-01 — Entité `Settlement` (S | ★★ | CRITIQUE)
> Fondation. Un foyer par zone, avec son rang, son type et ses quatre indices.
> Prérequis : ∅ (modèle zone livré)
- [ ] Entité `Settlement` : `zone` (OneToOne), `rank` (0-5), `type` (enum nullable),
      `sedimentTrade/War/Lore/Rite` (int), `rankedAt`, `decayedAt`, timestamps
- [ ] Enums `SettlementRank` (Ruine → Métropole) et `SettlementType`
      (Comptoir/Bastion/Athénée/Sanctuaire)
- [ ] Migration idempotente (`CREATE TABLE IF NOT EXISTS`, index sur `zone_id` unique)
- [ ] **Seed du rang de départ** (décision A — incrémental) : chaque zone déjà peuplée
      démarre au rang correspondant à ce qu'elle offre aujourd'hui. Village de Lumière et
      Quartier des Jardins **n'ont pas de foyer** (§3.4 : bâtis sur la Voûte)
- [ ] Fichier de paramètres `config/game/settlements.yaml` (seuils, décroissance, quotas)
- [ ] Tests : création, seed, absence de foyer sur les zones du Sanctuaire

### FOY-02 — Dépôt de sédiment (S | ★★★ | CRITIQUE)
> L'activité des joueurs devient la matière du monde. Aucun event nouveau.
> Prérequis : ← FOY-01
- [ ] `SettlementSedimentListener` branché sur les **six events déjà émis**
      (`MobDeadEvent`, `CraftEvent`, `SpotHarvestEvent`, `FishingEvent`, `ButcheringEvent`,
      `QuestCompletedEvent`)
- [ ] Résolution de la zone : `player.currentZone` (règle 7 — jamais des coordonnées)
- [ ] Routage vers l'indice selon le type d'activité (table des conventions ci-dessus)
- [ ] Passage par `InfluenceAntiExploit` : plafonds journaliers et rendements décroissants
- [ ] Aucun dépôt sur une zone sans foyer (Sanctuaire, et le Silence — §4.3)
- [ ] Tests : chaque event alimente le bon indice ; plafond respecté ; zone sans foyer ignorée

### FOY-03 — Décroissance, rang et type (M | ★★★ | CRITIQUE)
> Ce qui n'est plus fréquenté s'amincit. Et le type se décide tout seul.
> Prérequis : ← FOY-02
- [ ] `SettlementRankCalculator` : rang = seuils sur la somme des indices ; type = indice
      dominant
- [ ] **Hystérésis sur le type** : il faut dépasser le second indice d'une marge donnée et
      la tenir une marée entière — sinon le type clignote et la ville n'a pas d'identité
- [ ] Décroissance périodique des indices (commande `app:settlement:tick`, branchée sur le
      cron existant)
- [ ] Publication d'un événement domaine `SettlementRankChangedEvent` (montée **et** descente)
- [ ] Tests : franchissement de seuil, décroissance, non-clignotement du type,
      idempotence du tick

### FOY-04 — Le foyer sur l'écran de zone (M | ★★★ | HAUTE)
> Un compteur qui monte n'est pas un jeu ; un chantier visible en est un
> (modèle FFXIV/Ishgard, cf. GAME_INSPIRATIONS §3).
> Prérequis : ← FOY-03
- [ ] Bloc « foyer » sur `/game/zone` : rang, type, jauge vers le palier suivant, et **ce que
      le prochain palier ouvrira**
- [ ] Contribution du joueur et de sa guilde à la marée en cours
- [ ] État d'étiage signalé quand le foyer décroche (prépare FOY-10)
- [ ] Composants `.ds-*` du système de design ; un seul chiffre par ligne, en monospace
- [ ] Tests fonctionnels : affichage par rang, zone sans foyer, jauge

---

## Piste B — Ce que le rang ouvre (séquentiel)

### FOY-05 — Gate déclaratif des services (S | ★★ | HAUTE)
> Décision A : **rien n'est rétro-gaté**. Le gate ne s'applique qu'aux services nouveaux.
> Prérequis : ← FOY-01
- [ ] Service `SettlementGate::allows(Zone, string $service): bool`, table de correspondance
      **déclarative** (`settlements.yaml`) service → rang minimum
- [ ] Garde-fou explicite : un service **existant** (PNJ, boutique, atelier déjà posés) n'est
      jamais gaté, quel que soit le rang
- [ ] Message joueur uniforme quand un service est fermé : *pourquoi*, et *ce qui manque*
- [ ] Tests : service existant toujours ouvert, service nouveau gaté, message

### FOY-06 — Services gatés par rang (M | ★★★ | HAUTE)
> Le rang cesse d'être un chiffre : il ouvre des portes.
> Prérequis : ← FOY-05, ← FOY-04
- [ ] **Bourg** : ouverture du marché local (le HV régional devient consultable depuis la zone)
- [ ] **Cité** : banque de zone, donjon de groupe, étals loués (← ECO Piste D si livrée)
- [ ] **Métropole** : éveil de matéria (consomme de l'améthystite *Parfaite*, §5.4),
      plans de fin de jeu
- [ ] Chaque ouverture publie une annonce Mercure sur la zone (le palier se **voit**)
- [ ] Tests : chaque service par rang, fermeture après régression

### FOY-07 — Bonus d'atelier par foyer (M | ★★★ | HAUTE)
> On voyage pour crafter. C'est ce qui donne une identité économique aux régions.
> Prérequis : ← FOY-06
- [ ] Multiplicateur d'artisanat lu depuis le foyer : **ligne de production de la veine**
      (§5.1) × **type de foyer**
- [ ] Effets : rendement de matière, chance de qualité supérieure, palier de recette accessible
- [ ] Affichage du bonus dans l'écran d'artisanat (le joueur doit pouvoir arbitrer *où* crafter)
- [ ] Tests : bonus appliqué, absent hors zone concernée, cumul plafonné

---

## Piste C — La Crue (séquentiel — c'est ce qui rend le pilier politique)

### FOY-08 — Quotas indexés sur la population active (M | ★★★ | CRITIQUE)
> Décision B. Sans quota, tout le monde monte tout et il n'y a pas d'enjeu de territoire.
> Prérequis : ← FOY-03
- [ ] `CrueQuotaService` : quotas de base (1 Métropole / 3 Cités / 6 Bourgs) **+ indexation**
      sur les joueurs actifs de la marée écoulée
- [ ] Blocage de promotion quand le quota est plein — le foyer **reste en attente**, son
      sédiment n'est pas perdu
- [ ] Message joueur **nommant qui occupe la place** : la compétition doit être lisible, sinon
      elle est vécue comme un bug
- [ ] Libération du quota → promotion du foyer en attente le mieux placé, au tick suivant
- [ ] Tests : quota plein, indexation, libération, sédiment conservé en attente

### FOY-09 — Zone d'influence & vassalité (M | ★★ | MOYENNE)
> Une grande ville boit la croissance de ses voisines.
> Prérequis : ← FOY-08
- [ ] Un foyer de rang N plafonne ses voisins **directs du graphe de zones** au rang N-1
- [ ] Le vassal garde son marché, son type et son identité — seule sa **croissance** est
      plafonnée
- [ ] Affichage de la relation sur l'écran de zone et sur la carte du monde
- [ ] Tests : plafonnement, libération quand la métropole tombe, pas de cascade au-delà des
      voisins directs

### FOY-10 — Étiage & régression bornée (M | ★★★ | HAUTE)
> Décision C. Le message est « ce lieu s'endort », jamais « vous avez perdu ».
> Prérequis : ← FOY-03
- [ ] **Étiage** : passage sous le seuil → état annoncé, avec **une marée entière** pour
      redresser avant la perte du rang
- [ ] Notification aux contributeurs récents et à la guilde contrôlante
- [ ] **Le patrimoine survit** : upgrades payés, parcelles, échoppes et stocks sont conservés
      et redeviennent actifs au retour du rang
- [ ] **Remontée accélérée** : seuils réduits pour un rang déjà atteint une fois
      (`Settlement.highestRank`)
- [ ] Tests : étiage → délai → régression, patrimoine intact, remontée moins chère

---

## Piste D — Pâleur (séquentiel)

### FOY-11 — Pâleur d'une zone (M | ★★★ | MOYENNE)
> L'extraction laisse une trace. Graduelle, bornée, réversible — jamais une Étale (§12.1).
> Prérequis : ← FOY-03
- [ ] `Zone.paleness` (0-100), alimentée par le rapport extraction / vitalité des `ZoneVein`
      sur la marée
- [ ] Effets progressifs : rendement de récolte en baisse, **bande de pureté plafonnée**
      (§5.4), faune plus rare
- [ ] **Plancher dur** : une zone pâlie ne devient jamais stérile et ne produit **aucun**
      Effacé — c'est ce qui la distingue de l'Étale
- [ ] Décroissance naturelle lente si l'extraction cesse
- [ ] Affichage : la zone se délave visuellement, avec un texte qui dit quoi faire
- [ ] Tests : montée, plancher, décroissance, effet sur la pureté

### FOY-12 — Restauration payée au trésor (M | ★★★ | MOYENNE)
> Mécanique de Wakfu : la sanction devient une **dépense politique**, pas une perte sèche.
> Prérequis : ← FOY-11
- [ ] Chantier de restauration ouvert par la guilde contrôlante, payé au `GuildVault`
- [ ] Coût indexé sur la Pâleur accumulée ; effet étalé sur plusieurs jours (pas d'achat
      instantané d'un monde propre)
- [ ] Trace au `GuildVaultLog` et mention publique — restaurer est un **acte de gouvernement**
      qui doit se voir
- [ ] Tests : coût, application progressive, trésor insuffisant, idempotence

---

## Piste E — Doctrine & guilde (séquentiel)

### FOY-13 — Ateliers de doctrine (M | ★★ | MOYENNE)
> L'axe Extraire / Préserver (§6.2) devient un bâtiment qu'on voit sur l'écran de zone.
> Prérequis : ← FOY-07, ← FOY-11
- [ ] Atelier de la **Fonderie** : rendement d'extraction en hausse, Pâleur accélérée
- [ ] Atelier des **Lecteurs** : Pâleur ralentie, entrées de Codex et progrès d'accord
- [ ] Exclusifs entre eux sur un même foyer — la guilde **choisit**, elle ne cumule pas
- [ ] Réutilise le mécanisme d'upgrade existant (`RegionUpgrade` / `RegionUpgradeManager`)
- [ ] Tests : effets opposés, exclusivité, conservation à la régression (FOY-10)

### FOY-14 — Crédit au journal de monde (S | ★★ | MOYENNE)
> Le serveur garde la trace de qui a bâti quoi — en bien comme en mal.
> Prérequis : ← FOY-08, ← NAR-07 (livré)
- [ ] À la clôture d'une marée, `WorldFactService::recordWorldFact()` pour chaque
      promotion/chute de rang marquante, avec `creditedGuildName`
- [ ] Gate **canon** (`InfluenceSeason::isCanon()`, NAR-12) : une marée non-canon ne laisse
      pas de trace durable
- [ ] Idempotent par slug (convention NAR-07)
- [ ] Tests : fait écrit sur marée canon, aucun sinon, idempotence

---

## Piste F — Contenu & tests (parallélisable)

### FOY-15 — Marées « conséquence » (M | ★★★ | MOYENNE)
> Ce qui transforme la saison en **boucle** plutôt qu'en calendrier (GAME_WORLD §8).
> Prérequis : ← FOY-11, ← FOY-08
- [ ] **La Pâleur** : thème déclenché quand l'extraction du mois écoulé dépasse un seuil ;
      climax = empêcher un foyer de tomber
- [ ] **L'Appel de la Crue** : thème déclenché quand un quota se libère ; climax = course de
      foyers entre guildes, sans un coup échangé
- [ ] Sélection du thème au tick de saison à partir d'un agrégat du mois précédent
- [ ] Composition déclarative des 4 beats (convention `SeasonArcFixtures`, NAR-08)
- [ ] Tests : sélection par agrégat, fenêtres de beat, absence de déclenchement sous le seuil

### FOY-16 — Tests unitaires du plan (M | ★★ | HAUTE)
> Objectif : **40+ méthodes** dédiées aux foyers, plus un test de contrat transverse.
> Prérequis : ‖ (au fil des jalons)
- [ ] Couverture par piste : sédiment, rang/type, gate, Crue, régression, Pâleur, doctrine
- [ ] `SettlementPlanContractTest` : verrouille le vocabulaire déclaratif
      (`settlements.yaml`) et les invariants — un foyer ne dépasse jamais son quota, une zone
      pâlie ne devient jamais stérile, un service existant n'est jamais gaté
- [ ] Synthèse de couverture, à la manière de `NARRATIVE_TEST_COVERAGE.md`

---

## Découpage en sprints proposé

Conforme à la règle 8 de `CLAUDE.md` (aucune phase XL, chaque jalon commitable seul) :

| Sprint | Jalons | Ce que le joueur voit à la fin |
|---|---|---|
| **Sprint 16** — Socle des foyers | FOY-01 → FOY-05 | Chaque zone a un foyer visible qui monte quand on y joue |
| **Sprint 17** — Le rang ouvre des portes | FOY-06, FOY-07, FOY-10 | Faire vivre une zone y ouvre un marché et de meilleurs ateliers ; l'abandonner l'endort |
| **Sprint 18** — La Crue | FOY-08, FOY-09, FOY-14 | Il n'y a pas de place pour deux métropoles, et le journal grave qui a bâti |
| **Sprint 19** — Pâleur & doctrine | FOY-11 → FOY-13, FOY-15 | L'extraction laisse une trace, la guilde choisit sa doctrine et paie la restauration |

FOY-16 court en parallèle sur les quatre sprints.

## Risques identifiés

| Risque | Parade prévue |
|---|---|
| **Friction sociale** (leçon d'Eco) : un joueur en veut à un autre d'avoir « gâché » sa région | Régression annoncée et bornée (FOY-10), patrimoine préservé, restauration payante (FOY-12) plutôt que perte définitive |
| **Le quota vécu comme un bug** | FOY-08 nomme explicitement qui occupe la place, et le sédiment en attente n'est jamais perdu |
| **Le type de foyer qui clignote** | Hystérésis obligatoire en FOY-03 |
| **Serveur petit → monde figé** | Indexation des quotas sur la population active (FOY-08) |
| **Régression qui casse le HV** | Le marché local ferme, les annonces **ne sont pas détruites** : elles redeviennent accessibles au retour du rang (à vérifier explicitement en FOY-10) |
| **Zones délaissées qui ne remontent jamais** | Remontée accélérée (`highestRank`) + marées « conséquence » qui ramènent l'attention (FOY-15) |

## Ordre d'implémentation recommandé

```
Phase 1 (socle)      : FOY-01 → FOY-02 → FOY-03 → FOY-04 → FOY-05
Phase 2 (valeur)     : FOY-06 → FOY-07 → FOY-10
Phase 3 (enjeu)      : FOY-08 → FOY-09 → FOY-14
Phase 4 (conséquence): FOY-11 → FOY-12 → FOY-13 → FOY-15
Phase 5 (tests)      : FOY-16  (parallélisable)
```
