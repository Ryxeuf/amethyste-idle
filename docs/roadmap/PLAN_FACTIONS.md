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
| FAC-01 ✅ | Tension par paires + patronage exclusif | M | ∅ |
| FAC-02 ✅ | Les gestes nourrissent la réputation | S | ← FAC-01 |
| FAC-03 ✅ | Hostile à conséquences | S | ← FAC-01 |
| FAC-04 ✅ | La Fonderie : faction + fondre/lire + essence | L → 2 sous-phases | ∅ |
| FAC-05 ✅ | Contrats d'approvisionnement (Fonderie) | S | ← FAC-04, RET-01 ✅ |
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

### FAC-01 — Tension par paires & patronage (M | ★★★ | CRITIQUE) ✅
> GAME_WORLD §6.4 a/c. Le cœur du rework : l'identité naît de ce qu'on renonce.
> Prérequis : ∅ — **livré le 2026-07-29**
- [x] Paires de tension en config (`config/game/factions.yaml`) : `fonderie ↔ mages`,
      `chevaliers ↔ ruelles` (slug de code hérité : `ombres`) ; Marchands hors tension. **Rien en dur**.
      La paire de la Fonderie est déclarée **avant** que la faction existe (FAC-04) : une paire dont
      un membre manque est **inerte**, pas invalide — sans quoi il faudrait se souvenir de revenir ici
- [x] Décote : la part du gain **au-delà du seuil d'Ami** retire 50 % chez l'opposé. Plancher
      **dérivé** du palier (`-2000`) : on ne renonce pas à plus qu'on aurait pu donner. Aucun champ
      de durée dans le catalogue, et un test le vérifie : l'absence ne coûte jamais rien
- [x] **Patronage** : une seule faction portée, changeable hors combat, portée par une **colonne
      unique** (`player.patron_faction_id`) — c'est le schéma qui tient l'exclusivité, pas un service
- [x] Les `FactionReward` de stats **s'appliquent enfin**, et seulement pour le patron : ils
      n'étaient lus par aucun calcul — l'écran affichait « +15 % de dégâts » et rien ne l'appliquait.
      Un seul palier compte (le plus haut atteint), jamais leur somme
- [x] UI : l'axe, l'opposé nommé, « hors tension » là où il n'y a pas d'opposition, les couleurs
      portées, le choix et le retrait
- [x] Tests : décote au-delà d'Ami seulement, pas de décote par inactivité, un seul patronage actif,
      plancher, paire inerte, opposé jamais créé de rien

**Reste ouvert, et volontairement.** `speed` figure dans les données livrées (le Pas de l'ombre des
Ruelles) sans qu'aucune agrégation ne l'attende : le résolveur la rend visible plutôt que de l'avaler,
et son application viendra avec le jalon qui donnera une vitesse au personnage.

### FAC-02 — Les gestes nourrissent (S | ★★ | HAUTE) ✅
> §6.4 b : les quêtes amorcent, les gestes font le régime de croisière.
> Prérequis : ← FAC-01 — **livré le 2026-08-02**
- [x] `ReputationListener` étendu : `AuctionSaleEvent` créé et émis aux deux points où une
      annonce devient une transaction (achat direct, enchère finalisée) → Marchands côté
      vendeur ; morts-vivants → Chevaliers au barème du palier (classification déclarative
      par slug dans `config/game/factions.yaml`, le bestiaire n'ayant pas de champ famille ;
      les Effacés attendent leur substrat d'extension) ; crochets `materia_melt`/`materia_read`
      (FAC-04) et `grey_market_sale` (FAC-06) **routés dès maintenant**, inertes tant que la
      cible n'est pas semée — même doctrine que la paire de tension de la Fonderie
- [x] Plafond journalier par faction : même doctrine qu'`InfluenceAntiExploit` (un geste
      répété nourrit, un geste fermé ne nourrit plus), mais pas son mécanisme — l'influence
      agrège un journal, la réputation n'en a pas : un couple (clé de jour, cumul) sur
      `player_factions` suffit, sans cron. Le gain est rogné puis laissé à zéro, jamais
      refusé ; les quêtes restent hors plafond (on ne refait pas une quête) ; les kills
      passent par le chemin plafonné (un kill est un geste)
- [x] Tests : routage geste → faction, plafond (rogne, ferme, rouvre le lendemain), crochet
      inactif sans la faction cible, tension appliquée sur le chemin plafonné, dispatch de
      l'event de vente, chaque slug mort-vivant existe au bestiaire

### FAC-03 — Hostile à conséquences (S | ★★ | HAUTE) ✅
> §6.4 d. Bornes absolues : jamais la boucle cœur, jamais une agression.
> Prérequis : ← FAC-01 — **livré le 2026-08-02**
- [x] Table déclarative par faction (bloc `hostile` de `config/game/factions.yaml`) :
      Marchands = surcharge 10 % PNJ (**active** — appliquée à l'achat et affichée sur
      l'écran boutique) ; Chevaliers = fouilles, +50 % de temps de voyage vers les zones à
      foyer Bastion (**active** — appliquée après la monture, jamais sur une liaison
      instantanée, ONB-10 gagne toujours) + taxe d'Autel au plafond (**crochet** — l'Autel
      d'éveil n'est pas branché) ; Ruelles = rumeurs empoisonnées (**crochet** ← FAC-06) ;
      Fonderie = plancher de rachat fermé, Mages = lecture refusée (**crochets** ← FAC-04)
- [x] Garde-fou : le **vocabulaire fermé** des types de conséquence — un type inconnu est
      refusé par le loader, aucun type ne sait bloquer énergie, voyage de base, combat ou
      plancher T1 (une conséquence surcharge ou refuse un privilège, jamais un droit) ;
      jamais Hostile par défaut (pas de ligne de réputation = rien à payer)
- [x] Tests : chaque conséquence active (surcharge appliquée/affichée, fouilles vers
      Bastion seulement), le garde-fou (vocabulaire figé, un Hostile solvable achète
      toujours, une liaison instantanée le reste, le premier voyage reste offert), et les
      refus du loader

## Piste B — La Fonderie

### FAC-04 — La Fonderie : faction, fondre/lire, essence (L | ★★★ | CRITIQUE) ✅
> §12.2. Le geste doctrinal quotidien. Découper en **04a** (faction + fixtures + boutique
> + plancher de rachat) et **04b** (conversion fondre/lire sur `PlayerItem` matéria,
> essence en monnaie de services, gains Codex/accord côté lecture).
> Prérequis : ∅
- [x] 04a ✅ (**livré le 2026-08-02**) : la 5e faction semée (`fonderie` — tout l'attendait :
      la paire de tension FAC-01, la route `materia_melt` FAC-02 et la conséquence
      `buyback_floor_closed` FAC-03 s'activent d'elles-mêmes, un test le vérifie) ; le
      comptoir au carreau des Mines (`mines-comptoir-de-la-fonderie`, PNJ déclaratif de
      `world_1.yaml`, visible dès le jour 1) ; le **plancher d'achat du cristal**
      (`CrystalBuybackFloor` : l'améthystite rend 9 gils garantis au comptoir au lieu du
      taux commun de 30 %, miroir du plancher T1 d'ECO-02 — fermé aux Hostiles, la vente
      au taux commun restant toujours ouverte)
- [x] 04b ✅ (**livré le 2026-08-02**) : `MateriaConversionService` — **fondre** rend gils
      (taux commun 30 %, jamais un meilleur prix : l'essence et la réputation font la
      différence) + **essence** (1/palier, colonne `player.essence`, migration) et nourrit
      la Fonderie (route `materia_melt` FAC-02) ; **lire** inscrit — page de Codex par flux
      (`UNLOCK_MATERIA_READ`, 8 entrées, une par élément), réputation du Cercle
      (`materia_read`), progrès d'accord dans **l'arbre qui enseigne** la materia (jamais
      dérivé de l'élément — doctrine `ActOneMateriaGranter`, 2 pts/palier) — et la lecture
      se refuse aux Hostiles du Cercle (`materia_reading_refused`, FAC-03 prend vie), la
      fonte jamais. Deux boutons sur l'écran materia, confirmation, solde d'essence affiché.
      Essence dépensable **uniquement en services** : l'invariant se tient par l'absence de
      chemin d'achat, figée par `EssenceServiceOnlyTest` (liste blanche des dépensiers) ;
      chaque lecture versée au Répertoire via `MateriaReadEvent` (crochet sans abonné tant
      que REP n'est pas jalonné — le Programme du Cercle écoutera au même endroit)
- [x] Tests : conversion à deux destinataires, essence non échangeable (canaux marchands +
      liste blanche), crochet Répertoire, lecture refusée sans rien consommer, la fonte
      reste ouverte aux Hostiles du Cercle, chaque flux a sa page de Codex
      (04a testé : crochets pointés sur le slug semé, plancher borné bas/haut par la
      donnée réelle, fermeture Hostile, comptoir déclaré et marchand)

### FAC-05 — Contrats d'approvisionnement (S | ★★ | HAUTE) ✅
> §12.2 complément. Prix garanti **toujours sous le marché** — le miroir inverse du
> receleur. Rotation du lundi (RET-01 ✅, point de rotation unique).
> Prérequis : ← FAC-04, foyers utiles mais non bloquants — **livré le 2026-08-02**
- [x] Contrats hebdomadaires déclaratifs (`config/game/foundry_contracts.yaml` : matière,
      volume, prix unitaire, essence — paiement mixte obligatoire, le loader refuse une
      essence nulle) ; un contrat global par semaine (`FoundryContract`, clé de semaine
      unique), tiré par `crc32(weekKey)` via `WeekKey` (le point de rotation unique de
      RET-01, `GameEngine/Reputation` ajouté au périmètre du contrat de plan) ; rejouer la
      rotation n'est jamais un reroll ; commande `app:weekly-foundry-contract:rotate` au
      lundi minute 8, tirage paresseux à la lecture en filet (le calendrier n'a pas de
      worker) ; accès au palier Ami, fermé aux Hostiles de la maison (un contrat optionnel
      est un privilège) ; remise au comptoir des Mines, une fois par joueur et par semaine
      (contrainte unique (contrat, joueur)) ; consommation du moins pur au plus pur
      (`InventoryHelper`, ECO-21), jamais payée en partie ; affiche sur la carte Fonderie
      de l'écran des factions
- [x] Garde-fou : prix contractuel **strictement sous** la médiane HV de la matière
      (`AuctionTransactionRepository::medianUnitPriceForSlug`, ventes conclues sur 7 jours,
      médiane — pas moyenne — des prix unitaires), vérifié au tirage ; marché muet → repli
      sur le prix d'item (même doctrine que le plancher du cristal) ; la référence lue est
      **figée sur la ligne** (`referencePrice`), vérifiable après coup
- [x] Tests : rotation (déterminisme, clé RET-01, jamais un reroll), garde-fou de prix
      (rognage sous la médiane, repli marché muet), paiement mixte (gils + essence, pool
      sans essence refusé), blockers (palier, Hostile, déjà honoré, mauvais guichet,
      volume manquant), livraison partielle jamais payée

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
- [ ] **Renommage du libellé** « Confrérie des Ombres » → « Confrérie des Ruelles »
      (name + traductions, slug `ombres` inchangé) — porté par **ZON-39** (PLAN_ZONES) ;
      si ZON-39 n'est pas passé avant ce jalon, le faire ici. Aujourd'hui le canon
      « les Ruelles » n'apparaît **nulle part côté joueur**, alors que le commentaire de
      `factions.yaml` promet « le canon vit dans le libellé »
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
