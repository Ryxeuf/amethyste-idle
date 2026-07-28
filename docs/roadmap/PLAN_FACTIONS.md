# Plan — Factions & réputations

> **Numérotation :** les jalons de **ce** document sont préfixés **FAC-** (Factions).
> Pas de conflit avec GCC- / ZON- / ECO- / FOY- / RET- / NAR-.

> Décline les décisions du 2026-07-28 : [../GAME_WORLD.md](../GAME_WORLD.md) **§6.4**
> (tension par paires, gestes, patronage, Hostile à conséquences), **§12.2** (la Fonderie
> complétée), **§12.4** (la Confrérie des Ruelles), **§12.5** (les trois autres maisons,
> les cinq portes). Le socle code est **livré** : `Faction`, `PlayerFaction` (7 paliers),
> `FactionReward`, `ReputationListener` (kills + quêtes), `CrafterReputation`,
> `PlayerRenown`. Ce plan **transforme** un système de boutiques tièdes en pilier
> d'identité — il ne repart pas de zéro.

## Vue d'ensemble

**10 jalons** (**FAC-01** à **FAC-10**) en 4 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| FAC-01 | Tension par paires + patronage exclusif | M | ∅ |
| FAC-02 | Les gestes nourrissent la réputation | S | ← FAC-01 |
| FAC-03 | Hostile à conséquences | S | ← FAC-01 |
| FAC-04 | La Fonderie : faction + fondre/lire + essence | L → 2 sous-phases | ∅ |
| FAC-05 | Contrats d'approvisionnement (Fonderie) | S | ← FAC-04, RET-01 ✅ |
| FAC-06 | Les Ruelles : approche nocturne + receleur + rumeurs | M | ← FAC-01 |
| FAC-07 | La contrefaçon (flag, trahison, faussaire) | M | ← FAC-06 |
| FAC-08 | Contrebande & placements (système Ruelles) | M | ← FAC-06, FAC-07 |
| FAC-09 | Échelles latérales + les cinq portes | L → par maison | ← FAC-01→03 |
| FAC-10 | Tests du plan | S | ‖ |

```
Piste A — Le système   : FAC-01 → FAC-02 → FAC-03
Piste B — La Fonderie  : FAC-04 → FAC-05
Piste C — Les Ruelles  : FAC-06 → FAC-07 → FAC-08
Piste D — Les échelles : FAC-09, FAC-10
```

**Quand.** Le chantier s'intercale dans l'ordre global **après le socle des foyers**
(étape 5 — FAC-05 et les fouilles au Bastion ont besoin des types de foyer) ; **FAC-04
(fondre/lire) peut venir plus tôt** — il ne dépend de rien et porte à lui seul l'axe
doctrinal. Le Programme du Cercle (FAC-09/Mages) gagne à suivre le Répertoire d'éveil
(dossier §12.3, non jalonné à ce jour).

---

## Piste A — Le système

### FAC-01 — Tension par paires & patronage (M | ★★★ | CRITIQUE)
> GAME_WORLD §6.4 a/c. Le cœur du rework : l'identité naît de ce qu'on renonce.
> Prérequis : ∅
- [ ] Paires de tension en config (`config/game/factions.yaml`) : `fonderie ↔ mages`,
      `chevaliers ↔ ruelles` (slug de code hérité : `ombres`) ; Marchands hors tension. **Rien en dur**
- [ ] Décote : tout gain au-delà du palier Ami chez l'un retire une fraction (paramètre,
      ~50 %) chez l'opposé. Jamais de décroissance par inactivité (principe RET)
- [ ] **Patronage** : une seule faction portée (choix du joueur, changeable hors combat) ;
      seuls les bonus de stats du patron s'appliquent. Migration : les
      `FactionReward` de stats existants deviennent des bonus de patronage
- [ ] UI : l'écran de factions montre l'axe, les paires, le patronage porté
- [ ] Tests : décote au-delà d'Ami seulement, pas de décote par inactivité, un seul
      patronage actif

### FAC-02 — Les gestes nourrissent (S | ★★ | HAUTE)
> §6.4 b : les quêtes amorcent, les gestes font le régime de croisière.
> Prérequis : ← FAC-01
- [ ] `ReputationListener` étendu aux events existants : ventes HV → Marchands,
      morts-vivants/Effacés → Chevaliers ; crochets prêts pour fondre/lire (FAC-04) et
      marché gris (FAC-06)
- [ ] Plafonds journaliers par faction (`InfluenceAntiExploit` réutilisé)
- [ ] Tests : routage geste → faction, plafond, crochet inactif sans la faction cible

### FAC-03 — Hostile à conséquences (S | ★★ | HAUTE)
> §6.4 d. Bornes absolues : jamais la boucle cœur, jamais une agression.
> Prérequis : ← FAC-01
- [ ] Table déclarative par faction : Marchands = surcharge 10 % PNJ ; Chevaliers =
      fouilles (surcoût de voyage vers zones Bastion) + taxe d'Autel au plafond ; Ruelles =
      rumeurs empoisonnées ; Fonderie = plancher de rachat fermé ; Mages = lecture refusée
- [ ] Garde-fou testé : aucun Hostile ne bloque énergie, voyage de base, combat, plancher T1
- [ ] Tests : chaque conséquence, et le garde-fou

## Piste B — La Fonderie

### FAC-04 — La Fonderie : faction, fondre/lire, essence (L | ★★★ | CRITIQUE)
> §12.2. Le geste doctrinal quotidien. Découper en **04a** (faction + fixtures + boutique
> + plancher de rachat) et **04b** (conversion fondre/lire sur `PlayerItem` matéria,
> essence en monnaie de services, gains Codex/accord côté lecture).
> Prérequis : ∅
- [ ] 04a : entité de faction (5e), PNJ au carreau des Mines, visible dès le jour 1,
      plancher d'achat du cristal
- [ ] 04b : action fondre (gils + essence) / lire (Codex + réputation Lecteurs + accord),
      essence dépensable **uniquement en services** ; chaque lecture versée au Répertoire
      (crochet no-op tant que le Répertoire n'est pas jalonné)
- [ ] Tests : conversion à deux destinataires, essence non échangeable, crochet Répertoire

### FAC-05 — Contrats d'approvisionnement (S | ★★ | HAUTE)
> §12.2 complément. Prix garanti **toujours sous le marché** — le miroir inverse du
> receleur. Rotation du lundi (RET-01 ✅, point de rotation unique).
> Prérequis : ← FAC-04, foyers utiles mais non bloquants
- [ ] Contrats hebdomadaires déclaratifs (matière, volume, prix, paiement gils + essence)
- [ ] Garde-fou : prix contractuel < médiane HV de la matière (vérifié au tirage)
- [ ] Tests : rotation, garde-fou de prix, paiement mixte

## Piste C — Les Ruelles

### FAC-06 — L'approche, le receleur, les rumeurs (M | ★★★ | HAUTE)
> §12.4. La faction invisible jusqu'au premier contact.
> Prérequis : ← FAC-01
- [ ] Entrée différée : gestes qualifiants (nuits d'exploration, étal nocturne,
      contrefaçon découverte) → chaîne nocturne de 3-4 étapes → la faction apparaît.
      Façades : Tancrède (le Fanal), Kolm (Mines) — PNJ existants, seconde vie
- [ ] **Receleur** : vente hors taxe, coupe 15 % (toujours > taxe max de cité, 10 %),
      plafond ~5 lots/semaine/joueur, accès Ami+ — les trois garde-fous en config
- [ ] **Rumeurs** : achat d'informations (bandes hautes, filons reposés, Affleurement) ;
      Hostile = rumeurs empoisonnées (FAC-03)
- [ ] Tests : invisibilité avant contact, les trois garde-fous du receleur, rumeur vraie/fausse

### FAC-07 — La contrefaçon (M | ★★★ | HAUTE)
> §12.4. Neuf fois, puis la dixième.
> Prérequis : ← FAC-06
- [ ] `PlayerItem` : flag `counterfeit` + état non-identifié (marché gris et butin
      uniquement — jamais entre joueurs)
- [ ] Trahison : compteur caché 8-12 tiré à la création ; échec du sort + contrecoup +
      bris en améthyste Trouble
- [ ] Œil du faussaire (Honoré), désamorçage (Révéré), main du faussaire (recette Révéré)
- [ ] **Canaux verrouillés, testés** : HV refuse ; échange direct affiche « Contrefaçon » ;
      un joueur ne peut jamais tromper un joueur
- [ ] Tests : trahison, identification, les verrous de canaux

### FAC-08 — Contrebande & placements (M | ★★ | MOYENNE)
> §12.4 d. Le système propre de la Confrérie — créé avec elle, pas dérivé des caravanes.
> Prérequis : ← FAC-06, FAC-07 ; les fouilles exigent les types de foyer (FOY-03)
- [ ] Contrats de contrebande : cargaison de nuit, capacité réduite, confiscation possible
      à la fouille (la cargaison du contrat, jamais l'inventaire)
- [ ] Contrats de placement : écouler des contrefaçons via les contacts PNJ — gains,
      risque, décote Chevaliers
- [ ] Tests : fenêtre nocturne, confiscation bornée, décote au placement raté

## Piste D — Les échelles

### FAC-09 — Échelles latérales & les cinq portes (L — par maison | ★★★ | HAUTE)
> §12.5. Un sous-jalon **par maison** (09a Marchands, 09b Chevaliers, 09c Mages,
> 09d Fonderie, 09e Ruelles), chacun commitable seul : récompenses de palier latérales
> (recettes, cosmétiques, montures, accès) + le système propre restant (commissions de
> négoce, tableau des primes, Programme du Cercle) + la zone `interior` d'Exalté.
> Prérequis : ← FAC-01→03 ; les zones passent par le chemin déclaratif de `world_1.yaml`
- [ ] Les cinq portes : Cour des Miracles, Grand Fourneau, Grande Halle, Salle du Serment,
      Scriptorium — zones `interior` cachées, gate de réputation Exalté
- [ ] Aucune récompense verticale : revue systématique contre §6.4 c
- [ ] Tests : gates d'accès, exclusivité des portes

### FAC-10 — Tests du plan (S | ★★ | HAUTE)
> ‖ au fil des jalons.
- [ ] Invariants transverses : tension symétrique, latéral partout (aucun
      `FactionReward` de stats hors patronage), bornes d'Hostile, verrous de contrefaçon
- [ ] Un contrat : « aucun geste d'un joueur ne peut nuire directement à un autre joueur »

---

## Risques

| Risque | Parade |
|---|---|
| Le marché gris devient la norme | Les trois garde-fous du receleur (coupe > taxe max, plafond de lots, Ami requis) sont des critères d'acceptance testés |
| Les contrats Fonderie remplacent le HV | Garde-fou de prix < médiane HV, vérifié au tirage |
| La tension punit l'exploration des contenus | La décote ne commence qu'au-delà d'Ami — tout le monde peut tout goûter |
| Cinq zones Exalté = cinq chantiers de contenu | Zones `interior` petites, déclaratives, un sous-jalon par maison |
| La contrefaçon fuit vers les joueurs | Verrous de canaux testés en FAC-07 et re-testés en FAC-10 |
