# Plan — Foyers, Crue et Pâleur

> **Numérotation :** les jalons de **ce** document sont préfixés **FOY-** (Foyers).
> Ils n'entrent **pas** en conflit avec les numéros de la roadmap globale (`SPRINT_*.md`)
> ni avec les jalons **GCC-** / **ZON-** / **ECO-** / **NAR-**.

> Le monde cesse d'être un décor traversé : les joueurs le **bâtissent**. L'activité dépose
> du sédiment sur le **foyer** d'une zone, le foyer monte en rang et ouvre des services, la
> **Crue** limite le nombre de grandes cités, et l'oubli fait redescendre. Décisions de
> conception : [../GAME_WORLD.md](../GAME_WORLD.md) §3 (foyers), §5 (économie territoriale),
> §12.1 (Pâleur et Étale) — décisions **A → G** actées les 2026-07-27/28.
> Références du genre : [../GAME_INSPIRATIONS.md](../GAME_INSPIRATIONS.md) (Ashes of
> Creation, EVE, FFXIV/Ishgard, Black Desert, Wakfu, SWG).

## Vue d'ensemble

**17 jalons** (**FOY-01** à **FOY-17**) organisés en 6 pistes. **17 livrés — vague 1
complète.** **Vague 2 ouverte** le 2026-07-29 : le logement dans les foyers
(**FOY-18** → **FOY-21**, voir en fin de document).

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
| FOY-01 | Entité `Settlement` — rang, type, quatre indices, seed non nul ✅ |
| FOY-02 ✅ | Dépôt de sédiment (subscriber sur les events existants) |
| FOY-03 ✅ | Décroissance, calcul du rang et du type (hystérésis) |
| FOY-04 ✅ | Le foyer sur l'écran de zone — chantier lisible |
| FOY-05 ✅ | Gate déclaratif des services par rang |
| FOY-06 ✅ | Services gatés : marché local, banque (l'éveil de matéria passe à ECO-22) |
| FOY-07 ✅ | Bonus d'atelier par foyer (ligne de production × type) |
| FOY-08 ✅ | Crue — quotas indexés sur la population active |
| FOY-09 ✅ | Zone d'influence & vassalité |
| FOY-10 ✅ | Étiage & régression bornée |
| FOY-11 ✅ | Pâleur — état de zone, effets sur rendement et pureté |
| FOY-12 ✅ | Restauration payée au trésor de guilde |
| FOY-13 ✅ | Ateliers de doctrine (Fonderie / Lecteurs) |
| FOY-14 ✅ | Crédit au journal de monde à la clôture de marée |
| FOY-15 ✅ | Marées « conséquence » (la Pâleur, l'Appel de la Crue) |
| FOY-16 ✅ | Tests unitaires du plan |
| FOY-17 | Facteur de monde — mesure ✅ (a) et échelle ✅ (b) |

```
Piste A — Socle du foyer      : FOY-01 ✅ → FOY-02 ✅ → FOY-03 ✅ → FOY-04 ✅
Piste B — Ce que le rang ouvre: FOY-05 ✅ → FOY-06 ✅ → FOY-07 ✅ **(piste complete)**
Piste C — La Crue             : FOY-17a ✅ → FOY-17b ✅ → FOY-08 ✅ → FOY-09 ✅ → FOY-10 ✅ **(piste complete)**
Piste D — Pâleur              : FOY-11 ✅ → FOY-12 ✅ **(piste complete)**
Piste E — Doctrine & guilde   : FOY-13 ✅ → FOY-14 ✅ **(piste complete)**
Piste F — Contenu & tests     : FOY-15 ✅, FOY-16 ✅ **(piste complete)**
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

**Le chiffrage est fait.** [BALANCE.md §23](../BALANCE.md) (2026-07-28) donne toutes les
valeurs de départ à W = 1 : le **grain** de sédiment et sa table de dépôt (§23.1), la
décroissance de 2 %/jour (§23.2), les seuils de rang avec leurs échéanciers vérifiés —
le 1er Bourg = l'effort d'une marée pour une guilde de 12 (§23.3) —, l'hystérésis du
type à 25 % tenus une marée (§23.4), le **seed du monde livré** zone par zone (§23.5,
les Vallons naissent en Ruine : tout est à bâtir), la régression bornée à un rang par
marée avec réascension à dépôts doublés (§23.6), et la période de grâce des deux
premières marées (§23.7). FOY-01, 02, 03, 08 et 10 consomment ces valeurs.

**Anti-exploit réutilisé.** Le dépôt de sédiment passe par `InfluenceAntiExploit`
(plafonds journaliers, rendements décroissants) : on ne réécrit pas les garde-fous, on
s'y branche.

---

## Piste A — Socle du foyer (séquentiel)

### FOY-01 — Entité `Settlement` ✅ (S | ★★ | CRITIQUE)
> Fondation. Un foyer par zone, avec son rang, son type et ses quatre indices.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).

### FOY-02 — Dépôt de sédiment ✅ (S | ★★★ | CRITIQUE)
> L'activité des joueurs devient la matière du monde. Aucun event nouveau.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Correction au plan** : « aucun event nouveau » était vrai avant le pivot PBBG et ne
> l'était plus. `GatherService` n'émettait rien ; ZON-38 l'a rebranché avant ce jalon.

### FOY-03 — Décroissance, rang et type ✅ (M | ★★★ | CRITIQUE)
> Ce qui n'est plus fréquenté s'amincit. Et le type se décide tout seul.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Arbitrage** : BALANCE §23.4 pose côte à côte « le type ne se perd qu'en le cédant à un
> autre » et « en dessous du Hameau, pas de type ». La seconde l'emporte — un Campement qui
> se souviendrait d'avoir été un Comptoir afficherait une identité que plus rien ne soutient.
>
> **Hors périmètre, laissé à FOY-10** : le plancher d'un rang perdu par marée et l'annonce
> d'étiage une marée à l'avance (§23.6). Le rang se calcule ici directement sur les seuils.

### FOY-04 — Le foyer sur l'écran de zone ✅ (M | ★★★ | HAUTE)
> Un compteur qui monte n'est pas un jeu ; un chantier visible en est un.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Piste A complète (FOY-01 → 04)** ; avec FOY-05, le socle du foyer est livré.

---

## Piste B — Ce que le rang ouvre (séquentiel)

### FOY-05 — Gate déclaratif des services ✅ (S | ★★ | HAUTE)
> Décision A : **rien n'est rétro-gaté**. Le gate ne s'applique qu'aux services nouveaux.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).

### FOY-06 — Services gatés par rang ✅ (M | ★★★ | HAUTE)
> Le rang cesse d'être un chiffre : il ouvre des portes.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Arbitrage** : `group_dungeon` figurait dans `services` au rang Cité depuis FOY-05. Les
> donjons de groupe sont livrés depuis ZON-19/20 et aucun foyer du monde livré n'atteint la
> Cité : appliquer le gate les aurait **tous** fermés — ce que la décision A interdit. Le
> service rejoint `never_gated`. Un gate déclaré mais jamais appliqué ne peut pas se tromper ;
> le premier jalon qui l'applique est donc aussi celui qui le découvre.
>
> **Reporté à ECO-22** : l'**Autel d'éveil** reste une promesse de palier (déclaré, non
> branché). Sans bande `parfait` (pureté), un rite d'éveil n'aurait rien à consommer, et le
> gate d'éveil figure déjà au cahier des charges d'ECO-22. Même statut pour l'**étal loué**,
> qui attend ECO Piste D.

### FOY-07 — Bonus d'atelier par foyer ✅ (M | ★★★ | HAUTE)
> On voyage pour crafter. C'est ce qui donne une identité économique aux régions.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Périmètre tenu, et ce qui en sort** : des trois effets annoncés (rendement de matière,
> chance de qualité supérieure, palier de recette accessible), seul le **second** est livré.
> C'est le seul qui s'additionne à une grandeur existante (`CraftSpecializationService`) sans
> inventer d'unité, et donc le seul calibrable dès aujourd'hui. Le rendement de matière et le
> palier de recette touchent au coût et au déblocage — ils relèvent de la chaîne de production
> (ECO-25→27) et se calibreront avec elle.
>
> **Piste B complète (FOY-05 → 07)** : faire vivre une zone y ouvre un marché *et* de meilleurs
> ateliers.

---

## Piste C — La Crue (séquentiel — c'est ce qui rend le pilier politique)

### FOY-17 — Facteur de monde (calibrage dynamique)

> **Scindé en deux (règle 8)** : le jalon porte deux choses de nature différente — une
> **mesure** (d'où vient le nombre) et une **échelle** (ce qu'on en fait). La première est
> livrable et testable seule, et elle a une valeur propre : elle corrige au passage le
> garde-fou anti-exploit des guildes.

#### FOY-17a — La mesure de la charge ✅ (livré 2026-07-28)
> Livré. Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Ce que FOY-17b hérite** : `WorldLoadService::effectivePopulation()` rend le
> dénominateur de tout le dimensionnement, et `measuredDays()` dit sur combien de jours il
> est établi — c'est ce qui permettra à la période de grâce de refuser une contraction sur
> une fenêtre incomplète.

#### FOY-17b — Le facteur `W` et sa mise à l'échelle ✅ (livré 2026-07-28)
> Livré. Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Ce que FOY-08 hérite** : `WorldScaleService::current()` donne `W`, et
> `WorldLoadService::effectivePopulation()` le nombre sur lequel indexer les quotas de
> Crue. Les seuils de sédiment d'un foyer devront être multipliés par `W` au même titre
> que la capacité des filons — c'est ce qui garde constant le **temps** de montée.
>
> **Constat d'audit (2026-07-29)** : cette multiplication des seuils de sédiment n'est
> **pas** implémentée — aucun appel à `WorldScaleService` dans
> `src/GameEngine/Settlement/` — et la checklist de FOY-17b avait retiré l'item
> « seuils de sédiment × W » sans corriger cette prose. **Tranché le 2026-07-29 : la doc
> a raison.** Dette de code à solder : `SettlementRankCalculator` doit lire des seuils
> × `W`, à implémenter avec la pondération du grain (§24.0), qui touche la même chaîne.
> Consigné dans [../BALANCE.md](../BALANCE.md) §24.3.

### FOY-08 — Quotas indexés sur la population active ✅ (M | ★★★ | CRITIQUE)
> Décision B. Sans quota, tout le monde monte tout et il n'y a pas d'enjeu de territoire.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Aucun champ ajouté** : l'attente est **dérivée** (rang naturel > rang tenu, et quota
> plein). Le sédiment n'est jamais perdu parce que le rang **se lit** dessus au lieu de le
> consommer — une colonne « en attente » aurait fallu tenir d'accord avec un calcul qui ne
> peut pas se tromper.
>
> **Refus partiel, pas refus en bloc** : une montée qui franchit plusieurs crans redescend au
> premier rang autorisé. Un foyer qui mérite la Cité sans pouvoir l'avoir devient quand même
> Bourg si la place existe — tout refuser lui ferait payer deux fois le succès des autres.

### FOY-09 — Zone d'influence & vassalité ✅ (M | ★★ | MOYENNE)
> Une grande ville boit la croissance de ses voisines.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Le plafond est dérivé, jamais stocké** : il se relit à chaque tick sur le rang *tenu* des
> voisins directs. C'est ce qui rend la libération automatique — le jour où la capitale
> tombe, ses vassales montent au tick suivant sans qu'aucun champ n'ait à être remis à zéro,
> et sans commande de rattrapage.
>
> **Seule la croissance est plafonnée.** Un rang déjà tenu n'est jamais retiré : le vassal
> garde son marché, son type et son identité. C'est la décision A du pilier (FOY-05) appliquée
> au voisinage — on borne ce qui reste à acquérir, on ne reprend pas ce qui est acquis.
>
> **Un voisin de même rang ne domine pas.** Il faut le dépasser strictement. Sans cette règle,
> deux bourgs voisins se plafonneraient mutuellement au Hameau et aucun des deux ne pourrait
> plus grandir — un blocage réciproque que rien ne viendrait dénouer.

### FOY-10 — Étiage & régression bornée ✅ (M | ★★★ | HAUTE)
> Décision C. Le message est « ce lieu s'endort », jamais « vous avez perdu ».
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Reste ouvert** : la notification aux contributeurs récents et à la guilde contrôlante,
> qui demande le canal Mercure de FOY-04b. Le patrimoine (upgrades, parcelles, échoppes)
> survit déjà par construction — FOY-05 interdit de gater un service existant.

---

## Piste D — Pâleur (séquentiel)

### FOY-11 — Pâleur d'une zone ✅ (M | ★★★ | MOYENNE)
> L'extraction laisse une trace. Graduelle, bornée, réversible — jamais une Étale (§12.1).
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Périmètre tenu, et ce qui en sort.** Des effets annoncés, le rendement et la bande de
> pureté sont livrés, plus le filtre sur l'Affleurement (le report nommé par RET-06). La
> **faune plus rare** est écartée, avec sa raison : la Pâleur est **par filon** par conception —
> c'est ce qui garantit qu'elle ne frappe que l'exploitation concentrée — alors que la faune est
> par zone. L'y appliquer ferait de l'agrégat de zone une *mécanique*, là où ce plan dit qu'il ne
> doit rester qu'un *affichage*, et ferait retomber sur le passage diffus la sanction réservée à
> l'exploitation concentrée.
>
> **Ce que FOY-12 hérite** : `ZoneVein.paleness` est la grandeur sur laquelle indexer le coût de
> restauration, et `settlements.yaml` porte déjà le bloc `paleness:` où ce coût trouvera sa place.

### FOY-12 — Restauration payée au trésor ✅ (M | ★★★ | MOYENNE)
> Mécanique de Wakfu : la sanction devient une **dépense politique**, pas une perte sèche.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Deux écarts assumés à la lettre du jalon.** La trace ne passe pas par le `GuildVaultLog` :
> ce registre exige un `Item` non nul, c'est un journal d'objets et non de Gils. La ligne
> `VeinRestoration` porte donc elle-même la comptabilité, et la mention publique passe par le
> journal de monde (FOY-14). Et le chantier n'est pas réservé à la **guilde contrôlante** : le
> lien zone → région court par `Zone::getSourceMap()`, un héritage d'avant le pivot que les
> zones récentes n'ont pas — la majorité de la carte serait restée irréparable. L'autorité
> retenue est celle qui gouverne déjà la dépense, le rang qui peut retirer du trésor.
>
> **L'interdit ajouté en cours de route** : le bonus n'entre jamais dans la branche de montée.
> Payer ne compense pas une surexploitation en cours, sinon la Pâleur devenait une facture
> qu'une guilde riche acquitte en pressant en continu. Le loader refuse une config où le bonus
> atteindrait `rise_per_pressure` — la règle est écrite dans le calcul **et** dans la validation.

---

## Piste E — Doctrine & guilde (séquentiel)

### FOY-13 — Ateliers de doctrine ✅ (M | ★★ | MOYENNE)
> L'axe Extraire / Préserver (§6.2) devient un bâtiment qu'on voit sur l'écran de zone.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **L'exclusivité n'est pas surveillée, elle est impossible.** Une colonne sur `Settlement`,
> pas deux booléens : aucun chemin de code ne *peut* cumuler les deux ateliers.
>
> **Écart au plan** : `RegionUpgrade` / `RegionUpgradeManager` n'a pas été réutilisé. Ce
> mécanisme est adossé à `RegionControl`, donc au lien zone → région hérité d'avant le pivot —
> le même héritage qui avait déjà fait dévier FOY-12. Un foyer est porté par une `Zone`. En
> revanche, « qui peut engager le trésor ? » était désormais posé deux fois à l'identique :
> la réponse part dans `GuildSpendingAuthority`, et FOY-12 y bascule.
>
> **Écarté, avec sa raison** : les entrées de Codex et le progrès d'accord promis aux Lecteurs.
> Ni l'un ni l'autre n'a de canal **de zone** — le Codex se débloque par découverte personnelle,
> l'XP de matéria par le combat. Le sédiment `lore` dit la même chose du lieu et existe déjà :
> ce qui s'y apprend s'y dépose, et pousse la ville vers l'Athénée.

### FOY-14 — Crédit au journal de monde ✅ (S | ★★ | MOYENNE)
> Le serveur garde la trace de qui a bâti quoi — en bien comme en mal.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Le seul champ stocké du pilier, et pourquoi.** Tout le reste se dérive — le plafond de
> Crue, la vassalité, le rang lui-même —, et c'est ce qui rend les libérations automatiques.
> `Settlement::tideStartRank` fait exception parce qu'il est de **l'histoire** et non de
> l'état : pour dire « ce lieu a grandi », il faut avoir gardé ce qu'il était avant. Le repère
> roule à chaque clôture, si bien qu'une marée se compare à **une** marée, et qu'il n'y a
> qu'un seul point d'écriture — celui-là même qui lit.
>
> **Correction au plan** : « avec `creditedGuildName` » restait muet sur *quelle* guilde.
> C'est la **bâtisseuse** (celle qui a le plus déposé de sédiment), pas la contrôlante :
> `SeasonResolutionService` crédite déjà celle qui *tient* la région à l'issue de l'élection
> d'influence. Ce ne sont pas les mêmes guildes, et une guilde qui a fait monter une ville dont
> elle a perdu le contrôle est précisément le fait qui mérite d'être gravé.
>
> **Piste E à moitié** : reste FOY-13 (ateliers de doctrine).

---

## Piste F — Contenu & tests (parallélisable)

### FOY-15 — Marées « conséquence » ✅ (M | ★★★ | MOYENNE)
> Ce qui transforme la saison en **boucle** plutôt qu'en calendrier (GAME_WORLD §8).
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Précision au plan** : les deux conditions ne se mesurent pas de la même façon. La Pâleur se
> lit sur un **état** (assez de filons visiblement pâlis), parce qu'elle est réversible — un
> serveur qui a réparé ne la revoit pas. L'Appel de la Crue se lit sur une **variation**, sans
> quoi il sonnerait dès la première marée : au lancement, toutes les places sont libres. Un
> unique vecteur de places libres, relevé à chaque clôture, couvre les deux causes annoncées
> (palier de population franchi, quota libéré par régression) — du point de vue des joueurs,
> la nouvelle est la même.
>
> **Aucune migration** : le repère est un vecteur de trois entiers, il vit dans `Parameter`.

### FOY-16 — Tests unitaires du plan ✅ (M | ★★ | HAUTE)
> Objectif : **40+ méthodes** dédiées aux foyers, plus un test de contrat transverse.
> **Livré le 2026-07-29.** Carte complète dans
> [SETTLEMENT_TEST_COVERAGE.md](SETTLEMENT_TEST_COVERAGE.md).
>
> **235 méthodes sur 21 fichiers** — l'objectif est dépassé d'un facteur six, parce que les
> tests ont été écrits *au fil des jalons* plutôt que rattrapés à la fin. Ce jalon n'a donc pas
> eu à combler un retard : il a ajouté ce qu'aucune brique ne pouvait vérifier seule.
>
> **Le contrat transverse verrouille dix propriétés** qui ne sont vraies que de l'ensemble —
> dont trois qu'aucun test de comportement n'aurait vues : tout paramètre déclaré est **lu**
> (un bloc ignoré du chargeur se lit comme une garantie), le rang d'un foyer ne se pose qu'à
> **deux** endroits (un troisième écrivain contournerait la Crue sans même la connaître), et
> `never_gated` ne peut pas être **vidé** — désarmer la décision A sans jamais la contredire
> était le seul chemin que le chargeur laissait ouvert.

---

## Découpage en sprints proposé

Conforme à la règle 8 de `CLAUDE.md` (aucune phase XL, chaque jalon commitable seul) :

| Sprint | Jalons | Ce que le joueur voit à la fin |
|---|---|---|
| **Sprint 16** — Socle des foyers | FOY-01 → FOY-05 | Chaque zone a un foyer visible qui monte quand on y joue |
| **Sprint 17** — Le rang ouvre des portes | FOY-06, FOY-07, FOY-10 | Faire vivre une zone y ouvre un marché et de meilleurs ateliers ; l'abandonner l'endort |
| **Sprint 18** — La Crue | FOY-17, FOY-08, FOY-09, FOY-14 | Il n'y a pas de place pour deux métropoles, et le journal grave qui a bâti |
| **Sprint 19** — Pâleur & doctrine | FOY-11 → FOY-13, FOY-15 | L'extraction laisse une trace, la guilde choisit sa doctrine et paie la restauration |

FOY-16 court en parallèle sur les quatre sprints.

## Risques identifiés

| Risque | Parade prévue |
|---|---|
| **Friction sociale** (leçon d'Eco) : un joueur en veut à un autre d'avoir « gâché » sa région | Régression annoncée et bornée (FOY-10), patrimoine préservé, restauration payante (FOY-12) plutôt que perte définitive |
| **Le quota vécu comme un bug** | FOY-08 nomme explicitement qui occupe la place, et le sédiment en attente n'est jamais perdu |
| **Le type de foyer qui clignote** | Hystérésis obligatoire en FOY-03 |
| **Serveur petit → monde figé** | Indexation des quotas sur la population active (FOY-08) |
| **Le calibrage dynamique annule la rareté qu'il sert** | FOY-17 : le facteur est **aveugle au local**. Il indexe l'ampleur du monde sur la population globale, jamais la réponse d'un filon à sa propre fréquentation |
| **Régression qui casse le HV** | Le marché local ferme, les annonces **ne sont pas détruites** : elles redeviennent accessibles au retour du rang (à vérifier explicitement en FOY-10) |
| **Zones délaissées qui ne remontent jamais** | Remontée accélérée (`highestRank`) + marées « conséquence » qui ramènent l'attention (FOY-15) |
| **Le creux du milieu** : les zones intermédiaires se vident quand les vétérans sont en fin de jeu et les nouveaux courent | **Hors de portée de ce plan seul** — le système redistribue l'attention, il ne crée pas de demande. Traité en amont par les cinq leviers de GAME_WORLD §5.5, dont le principal (raffinage consommant le palier inférieur) relève de `PLAN_PLAYER_ECONOMY`. Ce plan y contribue par le sédiment de passage (FOY-02) et par la Crue qui pousse les guildes vers l'extérieur (FOY-08) |
| **Attendre l'abstention des joueurs** | Interdit par conception : aucune mécanique ne récompense le fait de ne pas jouer quelque part (GAME_WORLD §3.5) |

## Ordre d'implémentation recommandé

```
Phase 1 (socle)      : FOY-01 → FOY-02 → FOY-03 → FOY-04 → FOY-05
Phase 2 (valeur)     : FOY-06 → FOY-07 → FOY-10
Phase 3 (enjeu)      : FOY-17 ✅ → FOY-08 ✅ → FOY-09 ✅ → FOY-14 ✅
Phase 4 (conséquence): FOY-11 ✅ → FOY-12 ✅ → FOY-13 ✅ → FOY-15 ✅
Phase 5 (tests)      : FOY-16  (parallélisable)
```

---

## Vague 2 — le logement dans les foyers (FOY-18 → FOY-21, ouverte le 2026-07-29)

> Décline [../GAME_WORLD.md](../GAME_WORLD.md) **§12.6** : le housing livré (tâche 129,
> HOU) rejoint le pilier territorial. Le Quartier des Jardins reste le plancher jamais
> gaté ; le jardin ne change pas.
>
> **Chantier voisin, non jalonné ici** : le vrai système de mobilier (hérité de la
> tâche 129 / Sprint 11 — `HouseFurnishing` reste minimal, le style payant `HouseStyle`
> en tient lieu) sera à jalonner **avec ou après FOY-20**.

### FOY-18 — Parcelles résidentielles par rang (M | ★★★ | HAUTE) ✅
> §12.6 b. `HousingManager::RESIDENTIAL_ZONE_SLUGS` (constante, une zone) devient une
> règle : tout foyer Hameau+ est résidentiel, à capacité par rang. **Livré le 2026-08-03.**
- [x] Capacités par rang en config (`settlements.yaml` bloc `housing.parcels_per_rank` :
      Hameau 8, Bourg 20, Cité 40, Métropole 80 à W = 1, mises à l'échelle par W via la
      même primitive que les seuils de rang — `max(1, round(cap × W))`). Le chargeur
      refuse une capacité qui ne croît pas avec le rang, et un rang sous Hameau (« on ne
      s'installe pas dans ce qui peut disparaître ») ; le bloc est optionnel (absent =
      règle inerte) ; le vocabulaire gelé du contrat accueille `housing` — un acte
      délibéré, comme le test l'exige. La règle vit dans `ResidentialParcels`
      (`isRankResidential` / `scaledCapacity` / `panel` / `canOpenParcel`)
- [x] **Jamais d'expulsion** : la capacité ne gate que `canOpenParcel` — l'achat d'une
      NOUVELLE parcelle (`HousingManager::buyLand`). Une régression de rang **ou une
      contraction de W** peut laisser plus de demeures que de parcelles : `free` s'écrase
      à zéro, rien d'autre ne se passe — aucun chemin du service ne touche une demeure
      existante, la borne tient par construction (décision A, testée)
- [x] Le Quartier des Jardins reste résidentiel inconditionnel : il n'a **pas de foyer du
      tout** (`without_settlement`, bâti sur la Voûte) — il ne peut pas être une règle de
      rang, il est une garantie, tenue par le plancher `RESIDENTIAL_ZONE_SLUGS` de
      `HousingManager`, hors capacité
- [x] Restitution : `SettlementPanelBuilder` expose `housing` (capacité/prises/libres),
      l'écran de zone l'affiche dans le bloc foyer (`game.zone.settlement.parcels`,
      trans FR/EN) ; `PlayerHouseRepository::countInZone` en COUNT dédié (le plafond de
      50 de `findInZone` aurait sous-compté une Métropole)
- [x] Tests : capacité par rang (sur le fichier livré), échelle W (×2 et contraction
      ×0,5), non-expulsion (20 demeures sur 8 parcelles : rien ne bouge), plancher
      (les Jardins ignorent la capacité), Campement/Ruine ne logent pas, foyer plein
      refuse l'achat, refus du chargeur (croissance, sous-Hameau, rang inconnu)

### FOY-19 ✅ — Le loyer politique (S | ★★ | HAUTE) — livré le 2026-08-19
> §12.6 c. Le même canal que la taxe HV (GCC-11/ECO-04).
- [x] Loyer d'une zone à foyer → trésor de la guilde contrôlante ; **sans foyer ou sans
      guilde → sink**
- [x] Restitution : la provenance « loyers » visible au trésor (`Guild::gilsFromRents`)
- [x] Tests : les trois cas, le sink qui n'enrichit personne, et **la boucle entière**

> **Livré (2026-08-19).** Le loyer était un **sink pur** : les gils quittaient la bourse et
> n'allaient nulle part. Il devient politique — *habiter chez quelqu'un est un acte politique
> doux*, et une guilde bien gérée a désormais des **habitants** comme source de revenus.
>
> **La règle est plus générale que son illustration.** Le plan écrivait « Quartier des Jardins →
> sink toujours » ; ce n'est pas une exception au nom du Quartier, c'est qu'il **n'a pas de
> foyer** — bâti sur la Voûte, rien ne s'y dépose. Une zone sans foyer n'a aucun corps politique
> pour percevoir. La règle s'écrit donc sur l'**absence de foyer**, et le Quartier en est le
> cas, pas la cause : le jour où une seconde zone résidentielle naîtra hors foyer, elle sera
> traitée sans qu'on revienne au code. *Une règle illustrée par son unique instance ne vieillit
> pas* — la leçon de `HawthornValesTest` (FAC-09a), retrouvée ici.
>
> C'est aussi ce qui rend le plancher du logement **inconditionnel** : personne ne peut fermer
> le lotissement du Fanal, parce que personne n'en tire rien.
>
> **Les deux chemins de paiement sont routés.** Le prélèvement automatique emprunte le même
> chemin que le paiement à la main : router l'un et pas l'autre ferait dépendre le revenu d'une
> guilde du **bouton** sur lequel ses habitants ont appuyé.
>
> **Le sink reste un sink**, comme à l'hôtel des ventes, à l'échoppe et à l'Autel : les gils
> sortent du jeu. Les rendre au joueur en ferait une remise déguisée, l'inverse d'un gold sink.
> Un test le vérifie par ce qui **ne bouge pas** — aucun trésor du monde ne monte.
>
> **La restitution demandée n'avait pas de support**, et le jalon le nomme : `addGilsTreasury()`
> est un simple accumulateur, et il n'existe **aucun journal du trésor** — la taxe HV et celle
> de l'échoppe arrivent sans provenance distinguable. `gilsFromRents` est un **cumul**, pas un
> solde, et il ne prétend pas tenir lieu de journal : il répond à la seule question que le canon
> pose, *combien les habitants rapportent-ils*. Le trait politique ne vaut que s'il se voit — un
> trésor qui monte sans dire d'où ne dit rien de la ville qu'on tient.

### FOY-20 ✅ — Le retour au logis & les cheminées (M | ★★ | MOYENNE) — livré le 2026-08-19
> §12.6 d. La commodité de fin de session (playtest V2) et le grain de résidence.
- [x] **Retour au logis** : 1×/jour, voyage instantané vers sa zone de résidence.
      **La borne est dans la forme** — `Homecoming::comeHome()` ne prend *aucune
      destination*, il n'y a donc aucun endroit où en passer une. Un téléporteur
      libre rendrait le graphe de zones décoratif, et avec lui les durées de trajet
      et le choix d'itinéraire, c'est-à-dire tout ce que le pivot PBBG a mis à la
      place de la carte navigable. Cadence tenue par une **clé de jour** portée par
      la demeure (`homecomingDayKey` + compteur) : *une clé différente est un autre
      jour*, ni purge ni cron. **Les deux refus du voyage restent** (en combat, en
      cours de voyage) : *la commodité raccourcit un trajet, elle n'annule pas un
      état* — et chaque refus rend **sa raison**, un bouton grisé sans explication se
      lisant comme une panne
- [x] **Coffre domestique** : naît avec la demeure, 60 places contre les centaines de
      la banque — *ce n'est pas un second entrepôt, c'est ce qu'on laisse chez soi*.
      **Exempté du point unique d'entrée en inventaire** (ECO-17) avec son motif :
      il ne fait entrer aucun objet dans le jeu, il en déplace un entre deux
      inventaires **du même joueur** ; repasser par `InventoryHelper::addItem()`
      réappliquerait la liaison à l'obtention à un objet qui n'est pas obtenu.
      *Ce que le point unique protège, c'est l'entrée, pas le rangement* — et un test
      miroir vérifie que l'exception ne devient jamais une porte (elle ne construit
      aucune pièce, et elle refuse toujours de déplacer ce qui ne circule pas)
- [x] **Grain de résidence** : une **ligne de la table de sédiment** (`residence`,
      `spread`, 4 grains, **non plafonné**) et pas un chemin à part — c'est ce qui lui
      fait obéir aux mêmes règles que les autres gestes, multiplicateurs de doctrine
      compris. Non plafonné parce qu'un plafond le ferait disparaître chez les joueurs
      les plus actifs, *c'est-à-dire chez ceux qui font vivre la ville*. Conditionné au
      **loyer à jour** : sans quoi on entretiendrait une ville avec des logis vides.
      Commande `app:house:residence-grain` au calendrier, après le tick de foyer,
      idempotente par clé de jour
- [x] Tests : `HomecomingAndHearthTest` (6) — cadence 1/jour, cible unique, les deux
      refus d'état, le coffre né avec la demeure et plus petit que la banque, la
      cheminée conditionnée au loyer **et** idempotente, le grain déclaré au YAML

> **Ce que le jalon a trouvé.** La dérivation de FAC-10 (« tout canal entre joueurs
> refuse la contrefaçon ») a **attrapé le coffre domestique** : il en a la forme
> exacte — il déplace une pièce et se demande si elle peut circuler. Y poser le
> verrou aurait été **activement faux** : refuser de ranger une contrefaçon dans son
> propre coffre la **révélerait**, quand FAC-07 la veut indiscernable jusqu'à la
> trahison. *Un verrou qui trahit ce qu'il protège est pire que pas de verrou.* D'où
> une exemption **nommée**, avec son motif — et un garde-fou qui la fait tomber le
> jour où le fichier qu'elle nomme cesse d'être un canal : *une exemption qui ne
> correspond plus à rien survit à la raison qui l'a justifiée*.

### FOY-21 ✅ — Tests de la vague (S | ★★ | HAUTE) — livré le 2026-08-19
> Le contrat transverse de la vague logement (`SettlementsHousingContractTest`).
> Quatre jalons ont leurs tests ; celui-ci n'en refait aucun et porte ce qu'aucun
> d'eux ne peut voir depuis sa position.
- [x] **Jamais d'expulsion, tenu par la forme.** `ResidentialParcels` écrivait dans
      son en-tête « aucun chemin de ce service ne touche une demeure existante, la
      borne tient par construction » — *un commentaire n'est pas un invariant*. Le
      contrat dérive la forme d'une expulsion : effacer une demeure, ou **lui retirer
      son propriétaire** (le plus insidieux — la ligne reste en base et le joueur n'a
      plus de chez-soi). `setOwner()` n'est légitime qu'à la construction. Plus la
      porte de service : le dépôt n'offre **aucun verbe** de destruction, invisible au
      scan tant que personne ne l'appelle et disponible le jour où quelqu'un la cherche
- [x] **Le plancher jamais gaté, lu sur la règle et non sur son cas.**
      `HouseRentRoutingTest` nomme le Quartier des Jardins ; c'est correct et
      insuffisant — *une règle illustrée par son unique instance ne vieillit pas*. Le
      contrat dérive de la **constante** et croise trois sources qu'aucun jalon ne voit
      ensemble : le code, le monde semé (aucun foyer) et la déclaration
      (`without_settlement`, qui distingue une omission d'une décision). Second volet :
      **la capacité ne se consulte jamais sans exempter le plancher d'abord** — un
      second appelant sans l'exemption le fermerait le jour où la ville est pleine,
      c'est-à-dire le jour où le plancher sert
- [x] **Le loyer routé ou détruit, jamais perdu en route.** Les tests de FOY-19
      vérifient chaque destination une fois le loyer *entré* dans le routeur ; ils ne
      voient pas ce qui se passe avant. Un chemin qui débiterait sans router ne serait
      ni versé ni brûlé, et les deux disparitions se ressemblent du côté du joueur. La
      règle se lit en **comptant** : autant de routages que de débits, par fichier.
      Plus : le routeur ne peut jamais payer un joueur (aucune méthode ne nomme
      `Player`) — un loyer ne revient jamais à quelqu'un
- [x] **Le retour au logis sans exploit, lu dans la signature.** Les refus de FOY-20
      sont un *comportement* : une surcharge ou un paramètre optionnel ajouté « pour la
      flexibilité » le changerait sans qu'aucun test ne parle. L'invariant se lit donc
      dans la **signature** — *un geste qui emmènerait ailleurs devrait le nommer*,
      le motif d'ARC-16a. Plus : la destination se lit sur la **demeure**, jamais sur
      une zone d'attache portée par le joueur, qui se réglerait à volonté
- [x] Chaque invariant porte son garde-fou de **non-vacuité**, et les sept ont été
      vérifiés par mutation : chacun échoue quand on écrit la violation qu'il décrit

> **Ce que le contrat a trouvé.** Le plancher est le **seul endroit du jeu où une
> demeure existe sans foyer** — c'est même ce qui le rend inconditionnel. Aucun test
> de jalon ne pouvait voir ce croisement : FOY-20 pose ses demeures dans une zone à
> foyer, et FOY-19 sait que le plancher n'a pas de percepteur mais ignore les
> cheminées. Au croisement, un défaut livré : `SettlementDepositService::deposit()`
> rend zéro quand la zone n'a pas de foyer, et `ResidenceGrain` comptait la cheminée
> **allumée** quand même. Aucune conséquence de jeu — mais *un compteur qui compte des
> grains qui ne sont pas tombés ne mesure plus rien*, et c'est ce compteur qu'un
> opérateur lit pour savoir que le calendrier tourne. Corrigé : la cheminée est
> comptée *skipped*, et **la clé de jour n'est pas posée** — rien n'a eu lieu, donc
> rien à marquer, et le jour où un foyer lèvera dans cette zone elle fumera sans
> rattrapage.

**La vague 2 est complète : FOY-18 → FOY-21, 4/4.**
