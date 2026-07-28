# Plan — Économie joueur

> **Numérotation :** les jalons de **ce** document sont préfixés **ECO-** (Player
> Economy). Ils n'entrent **pas** en conflit avec les numéros de la roadmap globale
> (`SPRINT_*.md`) ni avec les jalons **GCC-** / **ZON-**.

> Économie de production gérée par les joueurs (avec modération). Tout l'équipement et les
> consommables de valeur sont **craftés par les joueurs** ; seuls les **plans** sont des
> découvertes ou récompenses. Décisions de conception : [../GAME_PRINCIPLES.md](../GAME_PRINCIPLES.md) §4.

## Vue d'ensemble

**28 jalons** (**ECO-01** à **ECO-17**, puis **ECO-21** à **ECO-31**) organisés en 8 pistes.
*(ECO-18 à ECO-20 sont nés en cours de campagne et sont tracés dans `ROADMAP_DONE.md` /
`BALANCE.md` — les numéros ne sont pas réutilisés.)*
Prérequis roadmap : socle **HV** (Sprint 5 ✅), **guildes & contrôle de cité** (GCC ✅),
**modèle zone** (ZON, Sprints 7-9) pour la segmentation régionale et le time-gating.

| Code | Sujet (résumé) |
|------|----------------|
| ECO-01 | Type de liaison des objets (enum BindType) |
| ECO-02 | Plancher T1 PNJ & kit d'onboarding |
| ECO-03 | HV régional (segmentation par région) |
| ECO-04 | Taxe HV → trésor de guilde contrôlante |
| ECO-05 | Entité CraftOrder & escrow |
| ECO-06 | Tableau de commandes régional (public) |
| ECO-07 | Exécution de commande & commande directe |
| ECO-08 | Bind-on-pickup via commande & réputation d'artisan |
| ECO-09 | Anti-abus commandes (expiration, annulation) |
| ECO-10 | Entités échoppe & gating |
| ECO-11 | Vente asynchrone, caisse & loyer |
| ECO-12 | Vitrine UI & recherche transversale |
| ECO-13 | Emplacements d'échoppe = actif de ville (option) |
| ECO-14 | Interdépendance des métiers (audit recettes) |
| ECO-15 | Gold sinks (durabilité / consommables) |
| ECO-16 | Modération économique (anti price-fixing / RMT) |
| ECO-17 | Tests unitaires du plan |
| ECO-21 | Bandes de pureté & modèle |
| ECO-22 | Tirage de pureté à la récolte |
| ECO-23 | Pureté au marché et dans les commandes |
| ECO-24 | Audit de la chaîne de production ✅ |
| ECO-24b | Prérequis : sources des minerais ✅ (a) & cuirs du tanneur ✅ (b) |
| ECO-25 ✅ | Chaînage des paliers raffinés |
| ECO-26 ✅ | Propagation de la pureté dans la chaîne |
| ECO-27 | Équilibrage & tests de la chaîne |
| ECO-28 | Commandes de service — travailler un objet lié |
| ECO-29 | Cuisinier — le débouché de la pêche et des vivres |
| ECO-30 | Charpentier — le débouché du bois |
| ECO-31 | Tailleur — la ligne tissu et l'armure des mages |

```
Piste A — Socle & liaison        : ECO-01 → ECO-02
Piste B — HV régional            : ECO-03 → ECO-04
Piste C — Commandes de craft     : ECO-05 → ECO-06 → ECO-07 → ECO-08 → ECO-09
Piste D — Échoppes               : ECO-10 → ECO-11 → ECO-12 → ECO-13
Piste E — Métiers & équilibrage  : ECO-14, ECO-15, ECO-16, ECO-17
Piste F — Pureté & améthyste     : ECO-21 ✅ → ECO-22 ✅ → ECO-23 ✅ → ECO-28
Piste G — Chaîne de production    : ECO-24 ✅ → ECO-24b-a ✅ → ECO-24b-b ✅ → ECO-25 ✅ → ECO-26 ✅ → ECO-27
Piste H — Métiers manquants       : ECO-29, ECO-30 (← ZON-34), ECO-31 (← ZON-30)
```

**Ordre de valeur/effort** (cf. GAME_PRINCIPLES §4.5, D7) :
`Piste A → Piste B → Piste C → Piste D → Piste E`. Les commandes (C) précèdent les
échoppes (D) car elles seules produisent le stuff lié : c'est le pilier de l'endgame.

---

## Piste A — Socle & liaison (séquentiel)

### ECO-01 — Type de liaison des objets (S | ★★★ | CRITIQUE)
> Fondation économique : distingue échangeable / lié-équipement / lié-obtention.
- [ ] Enum `BindType` : `none`, `bind_on_equip`, `bind_on_pickup`
- [ ] Migration : `Item.boundToPlayer` (bool) → `Item.bindType` (enum), rétro-compat
      (`true` → `bind_on_pickup`, `false` → `none`)
- [ ] `PlayerItem` : flag `bound` (matérialise la liaison effective, ex. après 1er équipement
      pour `bind_on_equip`)
- [ ] Blocage vente HV/échoppe si l'item est lié (garde-fou côté service)
- [ ] Tests unitaires

### ECO-02 — Plancher T1 PNJ & kit d'onboarding (S | ★★ | CRITIQUE)
> Anti cold-start : aucun joueur jamais hard-bloqué par un marché joueur vide.
> Prérequis : ← ECO-01
- [ ] Audit : lister le T1 de survie (outils de base, potions/consommables de base)
- [ ] Garantir la disponibilité PNJ **ou** le loot garanti du tutoriel pour ce T1
- [ ] Marquer le T1 comme échangeable (`BindType::None`)
- [ ] Vérifier qu'un nouveau joueur peut progresser jusqu'au premier palier de craft
      **sans** dépendre d'un autre joueur

---

## Piste B — HV régional (séquentiel)

### ECO-03 — HV régional — segmentation par région (M | ★★★ | HAUTE)
> La géographie compte : arbitrage, transport = temps de voyage.
> Prérequis : ← ZON (modèle zone), socle HV (Sprint 5 ✅)
- [ ] `AuctionListing` rattaché à une région (via la zone du vendeur au moment du dépôt)
- [ ] Recherche/consultation HV filtrée par région (marché local par défaut)
- [ ] Décision actée : segmentation stricte vs marché global taxé (cf. GAME_PRINCIPLES §6)
- [ ] Transport de marchandises entre régions = coût de voyage/énergie (réutilise le graphe)
- [ ] Tests

### ECO-04 — Taxe HV → trésor de guilde contrôlante (S | ★★ | HAUTE)
> Branche l'HV sur le contrôle de cité (le champ `region_tax_rate` existe déjà).
> Prérequis : ← ECO-03, GCC-10/GCC-11 ✅
- [ ] À la vente, `region_tax_rate` prélevée → `gilsTreasury` de la guilde contrôlante
      (réutiliser `RegionBonusProvider` / la logique de taxe GCC-11)
- [ ] Réduction membre appliquée si acheteur dans la guilde contrôlante (cohérence GCC)
- [ ] Aucune guilde contrôlante → taxe conservée comme gold sink (destruction de gils)
- [ ] Tests

---

## Piste C — Commandes de craft (séquentiel — pilier endgame)

### ECO-05 — Entité CraftOrder & escrow (M | ★★★ | CRITIQUE)
> Le client fournit matériaux + commission ; l'artisan fournit plan + savoir-faire.
> Prérequis : ← ECO-01
- [ ] Enum `CraftOrderStatus` : `open`, `claimed`, `fulfilled`, `expired`, `cancelled`
- [ ] Entité `CraftOrder` : commanditaire (Player), recipe (Recipe), matériaux fournis
      (escrow), commission (gils, escrow), region, minQuality (nullable), crafter (nullable),
      createdAt, expiresAt, claimedAt, fulfilledAt
- [ ] **Escrow des deux côtés** : matériaux + commission bloqués à la création
- [ ] Migration
- [ ] Tests unitaires (escrow, restitution)

### ECO-06 — Tableau de commandes régional public (M | ★★ | HAUTE)
> Canal anonyme : n'importe quel artisan qualifié peut prendre la commande.
> Prérequis : ← ECO-05
- [ ] Route/UI : liste des commandes ouvertes de la région
- [ ] Filtre par métier (`Recipe.craft`) / recette
- [ ] Prise en charge : vérifie plan possédé + `requiredLevel` + `requiredSpecialization`
- [ ] Une commande claimed est réservée à l'artisan (verrou anti-double-prise)

### ECO-07 — Exécution de commande & commande directe (M | ★★★ | HAUTE)
> Le craft consomme l'escrow, respecte le time-gating, livre au client.
> Prérequis : ← ECO-06
- [ ] Exécution : consomme les matériaux **en escrow** (pas ceux de l'artisan), applique
      `craftingTime` (time-gating réel), produit `result`
- [ ] Livraison : objet au commanditaire, commission (moins taxe région) à l'artisan
- [ ] **Commande directe** : le client cible un artisan précis (depuis sa vitrine/réputation)
- [ ] Taxe de région sur la commission → guilde contrôlante (cohérence ECO-04)
- [ ] Tests

### ECO-08 — Bind-on-pickup via commande & réputation d'artisan (M | ★★★ | HAUTE)
> Le seul canal produisant du stuff lié ; l'objet naît lié au commanditaire.
> Prérequis : ← ECO-07
- [ ] Un `result` marqué `bind_on_pickup` produit par commande est lié au **commanditaire**
- [ ] Réputation d'artisan : livrer des commandes l'augmente (visibilité + tarifs)
- [ ] Classement/recherche des artisans par métier et réputation
- [ ] Tests (liaison au bon joueur, montée de réputation)

### ECO-09 — Anti-abus commandes (S | ★★ | MOYENNE)
> Expiration, annulation, restitution propre de l'escrow.
> Prérequis : ← ECO-05
- [ ] Expiration commande non prise → restitution matériaux + commission au client
- [ ] Annulation par le client tant que `open` (non `claimed`)
- [ ] Non-livraison dans le délai après `claimed` → libération + pénalité réputation artisan
- [ ] Plafonds anti-farm (réutiliser patterns anti-exploit influence)
- [ ] Tests

---

## Piste D — Échoppes (séquentiel)

### ECO-10 — Entités échoppe & gating (M | ★★ | MOYENNE) ✅
> Vitrine persistante d'un artisan, rattachée à une zone, gated.
> Prérequis : ← ECO-01 ✅, housing (Sprint 11) ✅
- [x] Entité `PlayerShop` : owner (unique), name, sign, zone, status, slotCount, vaultGils, rentDueAt
- [x] Entité `ShopListing` : shop, playerItem (escrow), quantity, unitPrice
- [x] `ShopSaleLog` (journal). **La caisse est un entier sur `PlayerShop`, pas une entité** :
      une caisse n'a qu'un solde, et lui donner une table ajouterait une jointure à chaque vente
      pour ne rien exprimer de plus. Le détail vit dans le journal.
- [x] **Gating** : demeure possédée + niveau 5 dans un métier d'artisanat
- [x] Refus de dépôt d'un objet lié (cohérence ECO-01)
- [x] Migration + tests
- [ ] Reste hors périmètre : la **réputation** n'est pas dupliquée sur `PlayerShop` —
      `CrafterReputation` (ECO-08b) existe déjà, par métier. La vitrine la lira (ECO-12).

### ECO-11 — Vente asynchrone, caisse & loyer (M | ★★ | MOYENNE) ✅
> Vend pendant que le propriétaire est déconnecté ; loyer = gold sink auto-régulateur.
> Prérequis : ← ECO-10 ✅
- [x] Approvisionnement : dépôt d'objets en escrow, prix par lot (livré en ECO-10)
- [x] Achat asynchrone : débit acheteur, livraison, gils (moins taxe) → caisse
- [x] Encaissement de la caisse + journal des ventes (`ShopSaleLog`)
- [x] **Loyer/entretien** (1 000 Gils / 7 jours) ; non-paiement → rideau baissé, **rien n'est
      confisqué**. La caisse paie avant la bourse : une échoppe qui vend s'entretient seule.
      Commande `app:shop:rent`.
- [x] Taxe de vente → guilde contrôlante : **réutilise `AuctionSettlement`** (ECO-04) plutôt
      qu'un second calcul. Deux calculs concurrents auraient fini par diverger, et un vendeur
      aurait appris à arbitrer entre HV et échoppe sur un détail d'implémentation.
- [x] Achat **sur place** : une échoppe est une adresse (règle #7). À distance, elle serait un
      second HV et annulerait le coût de voyage.
- [ ] Slots upgradables (gold sink) : `slotCount` existe et est borné à 24, l'achat de slots
      reste à câbler — reporté à ECO-12 avec la vitrine.

### ECO-12a — Vitrine UI (M | ★★ | MOYENNE) ✅
> Découvrabilité : une vitrine invisible ne sert à rien.
> Prérequis : ← ECO-11 ✅
- [x] Page vitrine `/game/shops/{id}` : nom d'artisan, enseigne, lots, réputation par métier
- [x] Écran de gestion `/game/shops` : ouverture (les deux gardiens annoncés **avant** le bouton),
      enseigne, approvisionnement, retrait, caisse, loyer, rideau, journal des ventes
- [x] Présence par zone : les échoppes ouvertes apparaissent sur l'écran de zone, à côté des PNJ

### ECO-12b — Recherche transversale (S | ★★ | MOYENNE) ✅
> Prérequis : ← ECO-12a ✅
- [x] Recherche transversale `/game/shops/search` : **deux moitiés d'une même réponse** — qui vend
      l'objet X et dans quelle zone, puis, à défaut, qui saurait le fabriquer. Un résultat vide qui
      n'ouvre sur rien fait cesser la recherche.
- [x] Critère « artisan capable » = **niveau de métier**, pas plan appris : un plan est une
      information privée, l'exposer publierait la feuille de progression de chaque joueur
- [x] Point d'entrée commande directe (ECO-07b) depuis la vitrine **et** depuis la recherche ;
      `/game/craft-order/new?crafter=` pré-remplit le champ, qui reste éditable
- [x] Achat d'emplacements supplémentaires — **livré en ECO-13** (étals de place), c'était la même
      fonctionnalité vue d'un autre bout

**Piste D complète : ECO-10 → ECO-13.**

### ECO-13 — Emplacements d'échoppe = actif de ville (S | ★ | BASSE) ✅
> Renforce le contrôle de cité : la guilde contrôlante loue les étals.
> Prérequis : ← ECO-11 ✅, GCC-13 ✅
- [x] **Décision actée** (GAME_PRINCIPLES D14, la question §6 est retirée) : *les deux*. La
      demeure donne l'échoppe et ses 6 emplacements de base ; la cité loue les étals au-delà.
- [x] Étals en **nombre fini par ville** (24) : la rareté est ce qui en fait un actif, sinon
      ce ne serait qu'un gold sink de plus
- [x] Bail → trésor de la guilde contrôlante, **détruit** si la cité n'a pas de maître
      (cohérence ECO-04 / ECO-11)
- [x] Prix croissant par étal : une progression linéaire aurait laissé les plus riches rafler
      la place du marché en une transaction
- [x] Ferme l'item d'ECO-12b « achat d'emplacements supplémentaires »

---

## Piste E — Métiers & équilibrage (parallélisable)

### ECO-14 — Interdépendance des métiers (S | ★★ | MOYENNE)
> Aucun métier autosuffisant : chaque métier consomme la sortie d'un autre.
- [ ] Audit des recettes : identifier les métiers autosuffisants
- [ ] Rééquilibrer les `ingredients` pour croiser les métiers (ex. un craft a besoin d'un
      intermédiaire produit par un autre métier)
- [ ] Documenter la chaîne de production dans BALANCE.md

### ECO-15 — Gold sinks (S | ★★ | MOYENNE) ✅
> Compenser la perte du gold sink « boutique PNJ » quand tout est crafté.
- [x] Sinks choisis — **déjà livrés** : durabilité/réparation (`GoldSinkManager`, dégradation
      branchée sur la fin de combat), voyage rapide, renommage, loyers de demeure et d'échoppe,
      étals de place, création de guilde, enchantement, respec, montures, et la **taxe d'HV en
      région sans guilde** (§ 14) — le seul puits adossé au volume d'échange entre joueurs
- [x] Faucets vs sinks inventoriés dans [BALANCE.md § 20](../BALANCE.md) — 8 robinets, 13 puits
- [x] **Alerte d'inflation** — mesurée sur le **stock** et non le flux : 26 fichiers appellent
      `addGils()`/`removeGils()` directement, les canaliser serait une refonte qui ne mesure rien
      tant qu'elle n'est pas finie. `GilsSupplySnapshot` relève bourses + trésors + caisses +
      **escrow**, `app:economy:snapshot` le planifie (00h10, après le tick de saison), et
      `app:balance:report -s economy` alerte au-delà de ±15 %/semaine **par personnage**.

> **L'escrow est le poste qu'on oublie.** Des Gils mis en enchère ou en commission de commande
> ont quitté une bourse sans être détruits. Les omettre ferait lire une déflation à chaque fois
> que le marché se remplit.

### ECO-16 — Modération économique (S | ★★ | HAUTE)
> Anti price-fixing, farm par alts, RMT.
- [ ] Escrow système garanti sur tous les canaux (HV, commandes, échoppes)
- [ ] Réutiliser les patterns anti-exploit influence (plafonds, diminishing returns, min
      membres actifs) contre la manipulation
- [ ] Journal/analytics des transactions pour détection d'anomalies (ShopSaleLog,
      AuctionTransaction, CraftOrder fulfilled)
- [ ] Outils de modération admin (suspension d'échoppe, annulation de listing)

### ECO-17 — Tests unitaires du plan (M | ★★ | HAUTE) ✅
> Prérequis : ← ECO-05 ✅, ECO-07 ✅, ECO-10 ✅
- [x] Tests liaison — 11 fichiers touchent `BindType` / bind-on-pickup
- [x] Tests escrow — couverts par `AuctionManagerTest`, `CraftOrderManagerTest` (47 tests),
      `ShopManagerTest`, `ShopRentServiceTest`
- [x] Tests taxe région → trésor de guilde — les trois canaux
- [x] Tests anti-abus & modération — `AuctionAntiExploitTest`, `InfluenceAntiExploitTest`
- [x] **Objectif dépassé** : l'audit a trouvé **211 tests unitaires** déjà écrits sur le domaine

> **Le trou n'était pas le volume.** Les 211 tests sont tous **par canal** ; aucun n'énonçait
> la **loi** que les trois canaux partagent via `AuctionSettlement::compute()`.
> `AuctionSettlementTest` épinglait huit scénarios chiffrés à la main.
>
> Livré à la place : **8 lois** balayées sur **630 combinaisons** (`EconomyInvariantTest`) —
> conservation, destruction comme seul changement de masse (le lien direct avec ECO-15),
> indépendance du vendeur, plancher à zéro, plafond de ristourne, taux négatif inoffensif.
> Plus **6 gardes des points d'étranglement** (`EconomyChokePointTest`).
>
> **Défaut trouvé** : `GuildVaultManager::withdraw()` remettait l'objet en sac sans appliquer
> la liaison à l'obtention. Latent — la garde du dépôt le couvrait — mais un objet dont le type
> passe à « lié à l'obtention » pendant qu'il dort dans le coffre en serait ressorti libre.

**Piste E complète : ECO-14 → ECO-17.**

---

## Piste F — Pureté des ressources (séquentiel)

> **Décision D** du socle de monde ([../GAME_WORLD.md](../GAME_WORLD.md) §5.4, actée le
> 2026-07-27) : toute améthyste ne se vaut pas. Emprunt à Star Wars Galaxies, corrigé de
> son défaut — chez eux *toutes* les ressources avaient des statistiques, ce qui a transformé
> l'artisanat en tableur. Ici : **la ligne du cristal uniquement**, et **quatre bandes** au
> lieu d'une note continue.

### ECO-21 — Bandes de pureté & modèle ✅ (M | ★★★ | HAUTE)
> Fondation. Le champ `Recipe.quality` existe déjà et dort : il lui manque son intrant.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Correction au plan — la règle de pile.** Dans ce code les objets **ne s'empilent pas** :
> chaque lot est une ligne de `player_item`, donc « deux lots ne fusionnent que dans la même
> bande » est vrai par construction. Le vrai risque était ailleurs et silencieux :
> `removeItemBySlug()` prenait les lots **dans l'ordre du sac**, si bien qu'un lot parfait
> gardé pour éveiller une matéria aurait fondu dans la première épée venue. La consommation
> part désormais du **moins pur**.
>
> **Hors périmètre, laissé à ECO-22** : aucune bande n'est attribuée. `PlayerItem.purity`
> reste nul partout — rétro-attribuer une pureté à des lots qui n'en avaient pas reviendrait
> à inventer un passé au joueur.

### ECO-22 — Tirage de pureté à la récolte ✅ (M | ★★★ | HAUTE)
> D'où vient la bande. C'est ici que le savoir du prospecteur prend une valeur marchande.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Périmètre tenu** : vitalité, compétence du récolteur, plafond déclaratif, information
> exclusive. La **marée** et le **biome** ne sont pas branchés — la table de tirage les
> accueillera sans changement de moteur, mais aucun des deux n'a de calibrage acté.
>
> **Reporté, avec sa raison — le gate d'éveil.** Vérification faite, **il n'existe aucun rite
> d'éveil** : les matérias tombent des tables de butin, aucune recette n'en produit, et FOY-06
> avait déjà reporté l'Autel d'éveil *vers* ce jalon. Poser le gate ici aurait produit un
> service que rien n'appelle. Il suivra le rite — retirer les matérias des tables de butin est
> un chantier de contenu à part entière, à ouvrir explicitement.
>
> **Reporté à FOY-11** : le plafond de **Pâleur**. Le mécanisme de plafond existe et est
> déclaratif ; la Pâleur y ajoutera une seconde borne, plus basse, quand elle existera.

### ECO-23 — Pureté au marché et dans les commandes ✅ (S | ★★ | MOYENNE)
> Sans ça, la pureté existe mais ne se négocie pas — et le HV reste un tas.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Correction au plan** : « `AuctionListing` porte la bande » aurait dupliqué la vérité.
> L'annonce ne porte pas sa pureté, elle porte **l'objet** qui la porte — le filtre passe donc
> par une jointure qui existait déjà. Deux colonnes à tenir d'accord auraient fini par diverger.
>
> **Le refus arrive avant l'escrow**, pas à la livraison : sinon un client immobiliserait
> matière et commission dans une commande qu'aucun artisan ne pourrait honorer.

### ECO-28 — Commandes de service : travailler un objet lié (M | ★★★ | HAUTE)
> Le joaillier améliore les emplacements de matéria d'une pièce qui ne lui appartient pas —
> y compris une pièce **liée**. Réponse au problème structurel : sans ce canal, tout
> l'artisanat de service sur le stuff HL lié est impossible (GAME_WORLD §2.1).
> Prérequis : ← ECO-05..09 ✅ (commandes & escrow), ← ECO-21 (bandes de pureté)
- [ ] `CraftOrder` étendu : type `service` — la commande cible un **`PlayerItem` du client**
      (placé en escrow) au lieu de produire un objet neuf
- [ ] **La liaison n'est jamais violée** : l'objet reste lié à son propriétaire pendant tout
      le processus ; l'artisan ne peut ni l'équiper, ni le vendre, ni le garder ; à la
      livraison (ou à l'expiration) il **revient au client**, amélioré ou intact
- [ ] Premier service : le **sertissage** — ajouter/améliorer un emplacement de matéria,
      consomme de l'améthyste **Pure** fournie par le client (+ commission)
- [ ] Restitution garantie à l'expiration/annulation (mêmes invariants d'escrow qu'ECO-09)
- [ ] Loi transverse à ajouter à `EconomyInvariantTest` : un objet en escrow de service
      conserve son propriétaire de liaison, quel que soit le chemin de sortie
- [ ] Tests : liaison préservée, escrow, restitution, refus si bande insuffisante

---

## Piste G — Chaîne de production par paliers (séquentiel)

> **Le levier principal contre le creux du milieu** (GAME_WORLD §5.5). Mécanique d'Albion :
> raffiner du palier N consomme du **raffiné N-1** en plus du brut N. La demande en matière
> intermédiaire devient alors **proportionnelle à l'activité de fin de jeu** — la ressource
> du début cesse d'être un produit fini pour devenir un **intrant**, et ne meurt jamais.
>
> **Le problème est mesurable aujourd'hui** : `recipe_orichalcum_ingot` (niveau 8) ne
> consomme que de l'orichalque et du métal stellaire. Rien de ce que produit une zone de
> début n'y entre. Le jour où les vétérans sont tous à l'orichalque, le cuivre ne vaut plus
> rien et la Forêt des murmures n'intéresse plus personne.

### ECO-24 — Audit de la chaîne de production (S | ★★ | HAUTE) ✅
> Livré 2026-07-27. Résultats complets : [../BALANCE.md § 21](../BALANCE.md).
- [x] Cartographie des **82 recettes** livrées, par métier et par niveau
- [x] **54 chaînées**, 22 orphelines de niveau 1-2 (**voulu** — palier d'entrée solo, ECO-02),
      et **6 orphelines de niveau ≥ 3** : le défaut réel
- [x] Chaîne cible posée dans BALANCE § 21.3

> **La chaîne est bâtie horizontalement, plate verticalement.** Les biens finis consomment
> bien des intermédiaires ; ce sont les intermédiaires qui ne se consomment pas entre eux.
> **Quatre des six orphelines sont l'échelle de raffinage du métal elle-même** (`cobalt`,
> `adamantite`, `orichalcum` ingots + `steel_chainmail`) ; les deux autres sont
> `poison_vial` (niv 3) et `masterwork_drakehide_cloak` (niv **10**, trois cuirs bruts).
>
> **Le précédent existe déjà** : `recipe_mithril_ingot` n'est pas orpheline, parce qu'ECO-19
> a fait de la transmutation alchimique la seule source d'`ore-mithril` (BALANCE § 19). La
> forme visée est donc déjà appliquée — à un seul palier.

> **Deux défauts découverts en chemin, à traiter avant ECO-25 :**
>
> **a) Deux systèmes de récolte coexistent.** `ore-mithril`, `ore-platinum`, `ore-darksteel`,
> `ore-adamantite`, `ore-starmetal`, `ore-orichalcum` n'ont **aucun filon déclaré** dans
> `config/game/zones/*.yaml` : ils n'existent que comme `ObjectLayer` hérités (sur `map_4`),
> encore servis par `HarvestController`. Le **haut de la ligne du métal échappe donc au
> modèle calibré** — or la pureté (ECO-22) se tire de la vitalité d'un `ZoneVein`, et la
> Pâleur (FOY-11) se calcule par `ZoneVein`. Ni l'une ni l'autre ne les couvre.
>
> **b) L'étain n'a qu'un seul filon au monde** (le cuivre en a deux), alors que le bronze
> exige les deux à parts égales. D'où une règle de conception ajoutée au socle : **une
> matière de base doit être présente dans beaucoup de zones, une matière de haut palier dans
> très peu** — raretés inversées.

### ECO-24b — Prérequis de la chaîne : sources et répartition

> Scindé en deux sous-jalons (règle 8) : le second passage d'audit (BALANCE §21.7) y a
> ajouté les cuirs du tanneur, qui n'ont rien à voir avec la ligne du métal — matière
> différente, système différent (tables de butin et non filons de zone), risque différent.
> Même découpage que ECO-07a/b, ECO-08a/b ou ZON-26b-a/b.

#### ECO-24b-a — La ligne du métal ✅ (livré 2026-07-28)
> Livré. Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md), carte appliquée dans
> [../GAME_ZONES.md](../GAME_ZONES.md) §3.
>
> **Ce qu'ECO-25 hérite** : chaque palier de la ligne du métal du **jeu de base** a
> désormais une source, et le chaînage peut s'appuyer dessus sans renforcer un goulot.
> Deux points à garder en tête au moment de chaîner :
> - `ore-adamantite` et `ore-starmetal` restent **sans source** (Extension 1). Les recettes
>   `recipe_adamantite_ingot` et `recipe_orichalcum_ingot` demeurent donc injouables dans le
>   jeu de base — c'est voulu, et `OreSourceReferenceTest` le documente plutôt que de le
>   masquer. Chaîner ces deux paliers n'a de sens qu'avec l'extension.
> - Le goulot annoncé par BALANCE §21.4 reste le **cobalt** (source unique, Crête) : c'est
>   toujours le point de tension à surveiller au coefficient 1.
>
> <s>**Ouvert par le jalon (ECO-24c)** : `GatherService` n'a aucun gate de compétence, ce qui
> rend décoratives les six compétences hautes de l'arbre du mineur. Voir BALANCE §21.5.</s>
> **ECO-24c livré le 2026-07-28** — détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).

#### ECO-24c — Le gate de compétence sur les filons ✅ (livré 2026-07-28)
> Livré. Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Arbitrage** : des deux issues laissées ouvertes par BALANCE §21.5 — porter le gate dans le
> modèle déclaratif, ou assumer que la zone *est* le gate et reconvertir les compétences en
> bonus de rendement —, c'est la **première** qui est retenue. La seconde aurait supprimé le
> seul endroit du jeu où un arbre de récolte ouvre une porte plutôt que d'ajouter un
> pourcentage, et la « zone comme gate » ne tient pas : la Crête et les Mines sont accessibles
> dès les premières heures, elles ne filtrent personne.
>
> **Ce que les jalons suivants héritent** : `requires_skill:` est le vocabulaire du gate de
> récolte. FOY-11 (Pâleur) et ZON-34 (la ligne du bois) s'y branchent sans moteur nouveau — un
> filon de bois pétrifié se garde de la même façon qu'un filon d'orichalque.

#### ECO-24b-b — Les cuirs du tanneur ✅ (livré 2026-07-28)
> Livré. Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Piste G débloquée** : avec ECO-24b-a, plus aucune matière de recette du jeu de base
> n'est sans source, hors réserves d'extension explicites. ECO-25 peut chaîner les paliers
> sans renforcer un goulot.

### ECO-25 — Chaînage des paliers raffinés ✅ (M | ★★★ | HAUTE)
> Le cœur du jalon. Changement de **données**, pas de moteur.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Correction à la chaîne cible** : BALANCE §21.3 chaînait le cobalt sur le bronze, sautant le
> fer — parce que `crafted-iron-ingot` était un **objet mort** et qu'on ne chaîne pas sur ce qui
> n'existe pas. La fonte du fer, le geste le plus banal d'une forge, n'existait pas dans le jeu.
> ECO-25 l'écrit : l'échelle devient continue (bronze → fer → cobalt → mithril → adamantite →
> orichalque), le bronze garde un consommateur, et l'ordre des niveaux est respecté. §21.3 a été
> mis à jour en conséquence.
>
> **Périmètre tenu, et ce qui en sort.** Le palier d'entrée de la taille de gemme
> (`recipe-cut-gem-basic`, niveau 1) reste sur du métal : **aucune gemme n'affleure près du
> hub**, et l'y exiger fermerait la première recette du joaillier. Les trois gemmes brutes
> entrent aux rangs supérieurs, où le métier voyage déjà. Poser un filon de gemme au départ
> relève du contenu de zone (PLAN_ZONES), pas de ce jalon.
>
> **Ce qu'ECO-26 hérite** : une chaîne verticale complète sur la ligne du métal, du cuivre d'un
> débutant au lingot d'orichalque. C'est elle que la pureté va remonter maillon par maillon —
> sans elle, « le maillon le plus faible » n'aurait eu qu'un seul maillon à juger.

### ECO-26 — Propagation de la pureté dans la chaîne ✅ (M | ★★★ | MOYENNE)
> C'est ce qui rend une zone intermédiaire **reposée** indispensable à la fin de jeu.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Précision au plan — ce que « maillon le plus faible » ne doit pas vouloir dire.** Une
> matière fongible n'a pas de bande, et son absence ne compte **pas** comme « trouble » :
> presque toutes les recettes mélangent du fongible et du cristal, et la lecture naïve aurait
> fait rendre du trouble à presque tout. `null` signifie « pas de bande », jamais « bande nulle ».
>
> **Défaut trouvé en chemin** : `CraftingManager::removeIngredients()` avait sa propre boucle de
> consommation, restée dans l'ordre du sac — le défaut qu'ECO-21 croyait avoir corrigé survivait
> sur le chemin le plus emprunté du jeu. La règle de pile vit désormais à un seul endroit.
>
> **Périmètre étendu au canal des commandes** : les matériaux d'escrow sont des `PlayerItem`,
> donc porteurs d'une bande. La commande étant le canal de l'endgame, y perdre la pureté aurait
> cassé la chaîne haute exactement là où elle compte le plus.

### ECO-27 — Équilibrage & tests de la chaîne (M | ★★ | HAUTE)
> Prérequis : ← ECO-25, ← ECO-26
- [ ] Recalibrer les prix PNJ et les valeurs de référence en tenant compte du coût propagé
- [ ] Vérifier que la demande en matière de début **croît** avec l'activité de fin de jeu
      (c'est la propriété qu'on achète : la mesurer, pas la supposer)
- [ ] Surveiller le risque inverse : une matière de début devenue *goulot* qui bloque la fin
      de jeu — les paliers T0 du calibrage des filons existent pour ça
- [ ] Loi transverse à ajouter à `EconomyInvariantTest` : **aucune ligne de production n'a de
      palier orphelin** (tout palier ≥ 2 consomme le palier inférieur)
- [ ] Documenter la chaîne finale dans [../BALANCE.md](../BALANCE.md)

---

## Piste H — Métiers manquants *(validée le 2026-07-28)*

> Le second audit ([../BALANCE.md](../BALANCE.md) §21.7) a montré que des lignes entières
> n'ont **aucun métier consommateur** : la pêche ne nourrit rien, le bois n'existait pas,
> et **aucune armure tissu n'existe** parmi les 121 items d'équipement — les mages
> s'habillent en cuir et en métal. Trois métiers d'artisanat sont actés ; l'**enchanteur**
> reste en réserve (c'est de l'amélioration, pas de la nécessité — l'usage « améthyste
> Claire → enchantements » vit déjà dans la Piste F). Le paysage cible : **5 récoltes**
> (mineur, herboriste, pêcheur, dépeceur, bûcheron ← ZON-34) + **7 artisanats**.
> Chaque jalon suit le gabarit éprouvé : domaine + arbre, recettes, intrants sourcés,
> loi transverse testée.

### ECO-29 — Cuisinier : le débouché de la pêche et des vivres (M | ★★★ | HAUTE)
> Répare d'un coup le défaut le plus large de l'audit : 6 poissons sans débouché. Donne
> son sens au blé des Vallons et au gibier. La nourriture à effets est le consommable
> perpétuel idéal (demande de fond, GAME_WORLD §5.6).
> Prérequis : ∅ (s'enrichit de ZON-30 quand le blé existe)
- [ ] Domaine `cook` (Cuisinier) + arbre (gabarits des artisanats existants)
- [ ] Recettes T0→T3 consommant **chaque poisson** (truite → anguille), le gibier
      (viande via dépeçage) et le blé/farine (← ZON-30) — plus aucun poisson orphelin
- [ ] Nourriture à effets : buffs temporaires modestes (énergie de confort, bonus de
      récolte/combat courts) — jamais indispensable, toujours agréable
- [ ] Le pain et le ragoût des PNJ gagnent leur équivalent joueur (le PNJ reste le
      plancher T1, règle D1)
- [ ] **Épices** : les herbes banales sans débouché (pissenlit, romarin, échinacée,
      ortie) deviennent des intrants de cuisine (← ZON-35, loi 9)
- [ ] Réveiller ou purger `fish-moonfish` / `fish-baby-kraken` au passage
- [ ] Tests : plus aucun poisson sans consommateur (loi transverse étendue)

### ECO-30 — Charpentier : le débouché du bois (M | ★★★ | HAUTE)
> Le consommateur de la ligne du bois (ZON-34). L'arc et le bâton existants gagnent
> leur recette ; le housing (PlayerHouse, mobilier — livré côté code) gagne son métier.
> Prérequis : ← ZON-34 (essences et filons)
- [ ] Domaine `carpenter` (Charpentier) + arbre
- [ ] Recettes d'armes : arc, bâton, baguette par palier d'essence (hêtre → chêne
      murmurant → bois tourbé → bois pétrifié), lanière de cuir en liant (chaîne croisée
      avec le tanneur, D-WoW §4.6)
- [ ] Recettes de mobilier : la ligne `HouseFurnishing` gagne des versions craftées
- [ ] Flèches consommables pour l'archer (le consommable perpétuel du charpentier)
- [ ] Tests : chaque essence a ≥ 1 débouché, aucune arme de bois sans recette

### ECO-31 — Tailleur : la ligne tissu et l'armure des mages (M | ★★★ | HAUTE)
> Le trou béant : **aucune armure tissu n'existe**. Les domaines de sort (pyromancien,
> hydromancien, nécromancien…) n'ont aucun métier qui les habille. Le lin des Vallons
> (exclusivité, ZON-30) et l'item mort `crafted-cloth` attendent ce jalon.
> Prérequis : ← ZON-30 (le lin)
- [ ] Domaine `tailor` (Tailleur) + arbre
- [ ] Chaîne : lin → `crafted-cloth` (l'item mort se réveille) → pièces d'armure tissu
- [ ] **Créer la catégorie d'équipement tissu** : robes, coiffes, gants T1→T4, orientées
      magie (le pendant exact de la série cuir du tanneur et métal du forgeron)
- [ ] Paliers hauts croisés : fourrure (tanneur) ou fil d'argent (joaillier) en liant —
      aucun métier autosuffisant
- [ ] Tests : chaque pièce a une recette, le lin a ≥ 2 débouchés (tannerie + couture)

---

## Ordre d'implémentation recommandé

```
Phase 1 (socle)        : ECO-01 → ECO-02
Phase 2 (HV régional)  : ECO-03 → ECO-04
Phase 3 (commandes)    : ECO-05 → ECO-06 → ECO-07 → ECO-08 → ECO-09
Phase 4 (échoppes)     : ECO-10 → ECO-11 → ECO-12 → ECO-13
Phase 5 (équilibrage)  : ECO-14, ECO-15, ECO-16, ECO-17  (parallélisable)
Phase 6 (pureté)       : ECO-21 → ECO-22 → ECO-23
Phase 7 (chaîne)       : ECO-24 ✅ → ECO-24b ✅ → ECO-25 ✅ → ECO-26 ✅ → ECO-27
```

**Pistes F et G — pourquoi elles comptent.** La Piste G est le **levier principal contre le
creux du milieu** (GAME_WORLD §5.5) : sans elle, les zones intermédiaires meurent quand les
vétérans atteignent le dernier palier, et aucun système territorial ne les ranimera — les
foyers redistribuent l'attention, ils ne créent pas la demande. La Piste F la précède parce
qu'ECO-26 en dépend, et parce qu'elle réveille à elle seule `Recipe.quality`, la valeur
marchande du savoir du prospecteur, et l'exigence de qualité dans les commandes.

**Articulation avec le plan des foyers** ([PLAN_SETTLEMENTS.md](PLAN_SETTLEMENTS.md)) :
ECO-22 lit la Pâleur d'un filon (FOY-11) pour plafonner la bande. Les deux plans peuvent
avancer en parallèle — seul ce plafond les couple, et il se livre des deux côtés avec une
valeur par défaut neutre.
