# Plan — Économie joueur

> **Numérotation :** les jalons de **ce** document sont préfixés **ECO-** (Player
> Economy). Ils n'entrent **pas** en conflit avec les numéros de la roadmap globale
> (`SPRINT_*.md`) ni avec les jalons **GCC-** / **ZON-**.

> Économie de production gérée par les joueurs (avec modération). Tout l'équipement et les
> consommables de valeur sont **craftés par les joueurs** ; seuls les **plans** sont des
> découvertes ou récompenses. Décisions de conception : [../GAME_PRINCIPLES.md](../GAME_PRINCIPLES.md) §4.

## Vue d'ensemble

**17 jalons** (**ECO-01** à **ECO-17**) organisés en 5 pistes.
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

```
Piste A — Socle & liaison        : ECO-01 → ECO-02
Piste B — HV régional            : ECO-03 → ECO-04
Piste C — Commandes de craft     : ECO-05 → ECO-06 → ECO-07 → ECO-08 → ECO-09
Piste D — Échoppes               : ECO-10 → ECO-11 → ECO-12 → ECO-13
Piste E — Métiers & équilibrage  : ECO-14, ECO-15, ECO-16, ECO-17
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

### ECO-12b — Recherche transversale (S | ★★ | MOYENNE)
> Prérequis : ← ECO-12a
- [ ] Recherche transversale : « qui vend / peut crafter l'objet X, dans quelle zone ? »
- [ ] Point d'entrée commande directe (ECO-07) depuis la vitrine
- [ ] Achat d'emplacements supplémentaires (`slotCount` existe, borné à 24)

### ECO-13 — Emplacements d'échoppe = actif de ville (S | ★ | BASSE, option)
> Renforce le contrôle de cité : la guilde contrôlante attribue/loue les emplacements.
> Prérequis : ← ECO-11, GCC-13 ✅
- [ ] Emplacements d'échoppe en ville = actif de zone attribué par la guilde contrôlante
- [ ] Loyer d'emplacement → trésor de guilde
- [ ] Décision actée selon GAME_PRINCIPLES §6 (housing vs actif de ville)

---

## Piste E — Métiers & équilibrage (parallélisable)

### ECO-14 — Interdépendance des métiers (S | ★★ | MOYENNE)
> Aucun métier autosuffisant : chaque métier consomme la sortie d'un autre.
- [ ] Audit des recettes : identifier les métiers autosuffisants
- [ ] Rééquilibrer les `ingredients` pour croiser les métiers (ex. un craft a besoin d'un
      intermédiaire produit par un autre métier)
- [ ] Documenter la chaîne de production dans BALANCE.md

### ECO-15 — Gold sinks (S | ★★ | MOYENNE)
> Compenser la perte du gold sink « boutique PNJ » quand tout est crafté.
- [ ] Choisir le(s) sink(s) : durabilité/réparation (consomme des matériaux) et/ou
      consommables perpétuels (demande de fond)
- [ ] Étalonner faucets (récolte/PvE) vs sinks dans BALANCE.md
- [ ] Alerte d'inflation dans `app:balance:report` (ratio entrées/sorties de gils)

### ECO-16 — Modération économique (S | ★★ | HAUTE)
> Anti price-fixing, farm par alts, RMT.
- [ ] Escrow système garanti sur tous les canaux (HV, commandes, échoppes)
- [ ] Réutiliser les patterns anti-exploit influence (plafonds, diminishing returns, min
      membres actifs) contre la manipulation
- [ ] Journal/analytics des transactions pour détection d'anomalies (ShopSaleLog,
      AuctionTransaction, CraftOrder fulfilled)
- [ ] Outils de modération admin (suspension d'échoppe, annulation de listing)

### ECO-17 — Tests unitaires du plan (M | ★★ | HAUTE)
> Prérequis : ← ECO-05, ECO-07, ECO-10
- [ ] Tests liaison (BindType, bind-on-pickup via commande)
- [ ] Tests escrow (commandes, échoppes : dépôt, restitution, livraison)
- [ ] Tests taxe région → trésor de guilde (HV, commande, échoppe)
- [ ] Tests anti-abus & modération
- [ ] Objectif : 30+ tests unitaires

---

## Ordre d'implémentation recommandé

```
Phase 1 (socle)        : ECO-01 → ECO-02
Phase 2 (HV régional)  : ECO-03 → ECO-04
Phase 3 (commandes)    : ECO-05 → ECO-06 → ECO-07 → ECO-08 → ECO-09
Phase 4 (échoppes)     : ECO-10 → ECO-11 → ECO-12 → ECO-13
Phase 5 (équilibrage)  : ECO-14, ECO-15, ECO-16, ECO-17  (parallélisable)
```
