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
| FAC-06 ✅ | Les Ruelles : approche nocturne + receleur + rumeurs | M | ← FAC-01 |
| FAC-07 ✅ | La contrefaçon (flag, trahison, faussaire) | M | ← FAC-06 |
| FAC-08 ✅ | Contrebande & placements (système Ruelles) | M | ← FAC-06, FAC-07 |
| FAC-09a ✅ | La loi latérale + les cinq portes | M | ← FAC-01→03 |
| FAC-09b→e | Les échelles par maison (Ami / Honoré / Révéré) | L → par maison | ← FAC-09a |
| FAC-10 ✅ | Tests du plan | S | ‖ |

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

### FAC-06 — L'approche, le receleur, les rumeurs (M | ★★★ | HAUTE) ✅
> §12.4. La faction invisible jusqu'au premier contact.
> Prérequis : ← FAC-01 — **livré le 2026-08-02**
- [x] Entrée différée : le geste qualifiant (explorations **nocturnes**, seuil en config)
      → un mot glissé au journal → la faction apparaît **à Neutre** (la ligne de réputation
      naît à zéro : découvrir n'est pas un geste qui nourrit). Invisible avant sur toutes
      les surfaces (écran + API, `FactionVisibility` — l'axe des Chevaliers se calcule sur
      la liste complète pour ne pas basculer « hors tension » à tort). Façade : Tancrède
      (le Fanal) — **ses horaires 20h-6h sont enfin posés** (le commentaire les promettait,
      l'échoppe était ouverte 24h/24) ; Kolm (Mines) en second guichet. *La chaîne
      narrative de 3-4 étapes est condensée dans le geste qualifiant + le mot glissé — la
      quête scénarisée reste ouvrable avec FAC-09e*
- [x] **Receleur** : vente hors taxe au guichet d'un agent, aux heures de sa couverture —
      prix d'item moins la coupe (15 %, **strictement > taxe max de cité** : la borne 10 %
      extraite en `Region::MAX_TAX_RATE_PERCENT`, le loader refuse une coupe ≤), plafond
      5 lots/semaine/joueur (`PlayerWeeklyFenceSale`, clé de semaine RET-01), accès Ami+ —
      les trois garde-fous en config (`factions.yaml` bloc `ruelles`), refusés au
      chargement s'ils manquent ; le refus ne ferme jamais la vente (repli au rachat
      commun) ; un objet lié ne passe jamais sous le comptoir ; chaque lot nourrit la
      Confrérie (route `grey_market_sale` de FAC-02 — le crochet prend vie)
- [x] **Rumeurs** : achat d'informations au guichet (l'Affleurement de la semaine RET-06 —
      zone + filon, la bande qui tire haut — signatures ZON-32 exposées, le filon le plus
      reposé — stock effectif) ; le réseau ne parle pas aux inconnus (même refus neutre) ;
      **Hostile = rumeurs empoisonnées** (le crochet `poisoned_rumors` de FAC-03 prend
      vie : même forme, même prix, une zone qui n'est jamais la bonne)
- [x] **Renommage du libellé** — fait par ZON-39, vérifié : `FactionFixtures` dit
      « Confrérie des Ruelles »
- [x] Tests : invisibilité avant contact (+ apparition à Neutre, jamais deux fois), les
      trois garde-fous du receleur (+ couverture fermée, guichet ordinaire, objet lié),
      rumeur vraie/fausse (+ bourse vide, inconnus), horaires de Tancrède, guichets réels

### FAC-07 — La contrefaçon (M | ★★★ | HAUTE) ✅
> §12.4. Neuf fois, puis la dixième. **Livré le 2026-08-03.**
> Prérequis : ← FAC-06 ✅
- [x] `PlayerItem` : flag `counterfeit` + compteur caché `counterfeit_charges` + état
      `counterfeit_identified` (migration `Version20260803ECounterfeit`). L'état
      non-identifié n'existe que sur le **butin** (`LootGenerator` tire la chance du
      catalogue — 4 % du butin de matéria sort contrefait, indiscernable) — jamais entre
      joueurs. Le bloc `ruelles.counterfeit` de `factions.yaml` porte toute la config
      (fourchette 8-12, contrecoup 25 % de la vie max, paliers, recette), refusée au
      chargement si incohérente (min < 2 = un piège, pas une trahison)
- [x] **Trahison** : compteur tiré à la création (`CounterfeitService::mark`), décrémenté
      à chaque lancement dans `FightSpellController` ; à zéro le sort **échoue** (le tour
      est perdu, le monstre joue), le **contrecoup** frappe le lanceur (25 % de la vie max
      effective), la matéria **se brise** — elle quitte son emplacement et laisse une
      améthystite **Trouble** + des **éclats de matéria** (`materia-shards`, la matière
      première de la main du faussaire)
- [x] **Œil du faussaire** (Honoré) : passif — l'inventaire matéria montre le badge rouge
      sur toute contrefaçon vue (identifiée, ou percée par le palier), et casse le
      groupement pour qu'un bouton puisse la désigner. **Désamorçage** (Révéré) : route
      `/game/inventory/materia/defuse/{id}` — démonte une contrefaçon **vue** en
      améthystite Trouble + essence ; désamorcer une authentique répond le refus neutre
      « rien à désamorcer » (ne révèle rien, ne coûte rien). **Main du faussaire**
      (recette Révéré) : `recipe-forgers-hand` (joaillier 2 — 3 améthystites + 2 éclats
      → une contrefaçon **identifiée**, le faussaire connaît son œuvre) ; le gate est le
      palier Révéré des Ruelles tenu par `CraftingManager::isRecipeUnlocked`, jamais un
      arbre (exemption déclarée dans `SkillRecipeConsistencyTest`). Les éclats se brisent
      en combat ou s'achètent au guichet de Tancrède, la nuit. Débouché : les contrats de
      placement (FAC-08) — jamais un joueur
- [x] **Canaux verrouillés, testés** : le HV refuse (les **deux** entrées —
      `createListing` et `createAuctionListing` — plus le filtre du formulaire) ;
      l'échoppe joueur refuse (`ShopManager::stock` + filtre d'écran) ; le coffre de
      guilde refuse (`GuildVaultManager::deposit`) ; la commande d'artisan refuse
      (`CraftOrderManager::createServiceOrder`). *L'échange direct joueur-joueur n'existe
      pas encore dans le code (SPRINT_09) — le jour où il naît, le badge « Contrefaçon »
      y est obligatoire, et FAC-10 re-testera l'invariant.* Le receleur, lui, l'accepte :
      un PNJ n'est pas un joueur, c'est le canon
- [x] Tests : trahison (compteur, bris, contrecoup, une authentique ne trahit jamais),
      œil/identification (Honoré, l'œuvre du faussaire, refus muet), désamorçage
      (palier, composants, sertie refusée), verrous (HV ×2, échoppe, coffre), butin
      (chance du catalogue), gate de recette inerte sans faction semée

### FAC-08 — Contrebande & placements (M | ★★ | MOYENNE) ✅
> §12.4 d. Le système propre de la Confrérie — créé avec elle, pas dérivé des caravanes.
> **Livré le 2026-08-03.** Prérequis : ← FAC-06 ✅, FAC-07 ✅ ; les fouilles exigent les
> types de foyer (FOY-03 ✅ — le type est dynamique : la fouille est un crochet inerte
> tant qu'aucun foyer n'a basculé Bastion, et mord seule le jour où l'un bascule)
- [x] **Contrats de contrebande** (`SmugglingContract`, migration
      `Version20260803FSmuggling`) : un ballot pris au guichet des Ruelles **la nuit**
      (phase mécanique, pas l'horaire du PNJ — Kolm est ouvert 24h/24), à livrer à
      **l'autre guichet** (le Fanal ↔ les Mines), Ami+ requis. **La cargaison vit dans le
      contrat, jamais dans l'inventaire** : la confiscation est bornée par construction.
      Capacité réduite = un seul ballot à la fois + plafond hebdo (`WeekKey`, 3/semaine).
      Prime figée à l'acceptation (120 gils), versée à la livraison, qui nourrit la
      Confrérie (route `grey_market_sale`). **La fouille aux portes** : au départ d'un
      voyage vers une zone à foyer **Bastion** (`ZoneTravelService` →
      `ShadowsSmuggling::inspectAtGates`), tirage 35 % → le contrat passe `confiscated`,
      la décote Chevaliers tombe (−200, peut faire basculer Hostile — se faire prendre
      EST le geste opposé), le journal le grave — et le voyage part quand même, délesté :
      jamais un refus
- [x] **Contrats de placement** (`ShadowsPlacement`) : écouler une contrefaçon **vue**
      (identifiée ou percée par l'œil, ni sertie ni portée) via le contact, la nuit,
      Ami+. Rémunérateur : **120 % du prix** contre 85 % au receleur — le chargeur
      **refuse** un tarif qui ne bat pas strictement le receleur, c'est sa raison d'être.
      Risque 25 % : saisie (l'objet quitte le monde), amende (60 gils, jamais au-delà de
      la bourse), décote Chevaliers (−200). Dans les deux issues l'objet disparaît — le
      placement est le **seul débouché** d'une contrefaçon (FAC-07). Config complète dans
      `factions.yaml` blocs `ruelles.smuggling`/`ruelles.placement`, refus du chargeur sur
      le risque (jamais 0, jamais 100), UI au comptoir (`/game/shop`), CSRF sur les trois
      routes
- [x] Tests : fenêtre nocturne (accepter/placer refusés le jour), confiscation bornée
      (seul le contrat change, Bastion seul fouille, tirage au-dessus passe, pas de
      contrat pas de fouille), décote au placement raté (et jamais au succès), le plafond
      hebdo, la livraison au bon guichet seulement, le tarif du placement bat le receleur
      (invariant du chargeur + test), `ZoneTravelServiceTest` (le départ interroge les
      portes)

## Piste D — Les échelles

### FAC-09a ✅ — La loi latérale & les cinq portes (M | ★★★ | HAUTE) — livré le 2026-08-19
> §12.5. **Le découpage a changé** : le plan prévoyait un sous-jalon par maison, mais le
> mécanisme n'existait nulle part — chaque maison l'aurait réinventé. Le socle passe donc
> devant, et les échelles par maison deviennent du contenu (FAC-09b→e).
- [x] Vocabulaire **fermé** des formes de récompense (`FactionRewardForm`), refusé à la lecture
- [x] `stat_bonus` devient **`patronage`** : la seule forme qui puisse nommer une statistique
      porte le nom de la règle qui la borne
- [x] Les cinq portes : Grande Halle, Salle du Serment, Scriptorium, Cour des Miracles,
      Grand Fourneau — zones `interior` cachées, garde de réputation Exalté
- [x] La garde se déclare dans `zones.yaml` (`requires_reputation`), refusée si à moitié écrite
- [x] Tests : `FactionLadderContractTest`, `FactionGateTest`, refus de garde au chargeur

> **Livré (2026-08-19).** Le plan demandait « une revue systématique contre §6.4 c », comme si
> quelques récompenses verticales s'étaient glissées parmi des récompenses latérales. **La
> mesure dit l'inverse, et c'est pire** : sur les 12 récompenses livrées, **3 sont des remises
> et 9 sont des statistiques**. Hors de la Guilde des Marchands — la seule maison hors tension
> —, l'échelle **ne contenait rien d'autre que des statistiques** ; FAC-01 les ayant bornées au
> patron, un Exalté chez les Chevaliers qui portait d'autres couleurs recevait, pour une échelle
> entière, **exactement rien**. Et la Fonderie n'avait **aucune récompense du tout**. *Ce n'est
> pas « le reste est vertical » : il n'y avait pas de reste.*
>
> `FactionRewardForm` ferme la liste — c'est le geste d'ARC-16a sur les accointances, appliqué
> ici : murer la porte de service par laquelle la puissance entrait. `rewardData` étant un JSON
> libre, l'invariant ne peut pas tenir par le type ; il tient par **la forme**, et il est écrit
> en négatif — *tout ce qui n'est pas le patronage est latéral*, si bien qu'une dixième forme
> ajoutée demain est latérale par défaut : **l'oubli penche du côté de la doctrine**.
>
> **La porte répond à deux questions, et la seconde n'est pas un refus.** Peut-on entrer
> (`ZoneTravelService`, au crochet que la règle du MJ avait déjà nommé) — et *doit-on seulement
> voir la liaison* (l'écran de zone). Une porte listée mais barrée ne serait plus cachée : elle
> dirait son existence à qui ne l'a pas gagnée, et une récompense d'exaltation qu'on lit
> par-dessus l'épaule d'un autre a déjà donné la moitié de ce qu'elle donne. Les deux passent
> par **le même appel** : les séparer les ferait dériver. Pour la même raison, le refus emprunte
> la clé d'« indisponible » plutôt qu'un message propre — *une porte cachée qui se nomme en se
> refusant n'est plus cachée*.
>
> **Une garde qui nomme une maison pas encore semée est inerte**, jamais fermée : même doctrine
> que la paire de tension déclarée avant que la Fonderie existe (FAC-01). *On ne ferme pas une
> porte au nom de quelqu'un qui n'est pas là.*
>
> **Un test voisin savait déjà la règle, au singulier.** `HawthornValesTest` vérifie que les
> Vallons sont la zone la plus proche du hub, en excluant les Jardins — avec ce commentaire :
> « les compter ferait gagner la comparaison à une **porte intérieure** ». La règle était donc
> écrite, mais contre un slug, parce que le monde n'avait alors qu'un seul intérieur : *une
> règle illustrée par son unique cas ne vieillit pas*. Elle se lit désormais sur le `type`.

### FAC-09b→e — Les échelles par maison (L — par maison | ★★★ | HAUTE)
> §12.5. Ce que le socle laisse : les paliers **Ami, Honoré et Révéré** de chaque maison, et le
> système propre restant. Un sous-jalon par maison, chacun commitable seul.
- [ ] **Marchands** : cote des marchés (Ami), commissions de négoce + balance de précision
      (Honoré), mule bâtée + priorité d'étal (Révéré)
- [ ] **Chevaliers** : tableau des primes (Ami), bénédictions + héraldique (Honoré), monture
      caparaçonnée + garde du corps (Révéré)
- [ ] **Mages** : tarif de lecture + bibliothèque (Ami), Programme du Cercle (Honoré),
      familier-lanterne + archive (Révéré) — gagne à suivre PLAN_REPERTOIRE
- [ ] **Fonderie** et **Ruelles** : leurs systèmes propres sont livrés (FAC-05, FAC-06→08) ;
      restent les récompenses de palier
- [ ] Aucune récompense verticale : la loi est tenue par `FactionLadderContractTest`

### FAC-10 ✅ — Tests du plan (S | ★★ | HAUTE) — livré le 2026-08-19
> ‖ au fil des jalons.
- [x] Invariants transverses : tension symétrique, latéral partout (le côté **code**),
      seconde borne d'Hostile, verrous de contrefaçon dérivés
- [x] Un contrat : « aucun geste d'un joueur ne peut nuire directement à un autre joueur »

> **Livré (2026-08-19).** `FactionsPlanContractTest`. Vingt-deux fichiers de test couvraient
> déjà les factions, chacun sur son jalon ; ce contrat n'en refait aucun. Il porte les
> invariants **transverses** — ceux qu'aucun test de jalon ne peut voir, parce que chacun ne
> connaît que son propre sous-système — et sert de table des matières, en vérifiant que son
> propre index ne pourrit pas (le geste de `DungeonsPlanContractTest`).
>
> Quatre invariants, chacun **complétant** un test existant plutôt que le doublant :
>
> 1. **La tension est symétrique.** Le catalogue vérifiait l'axe livré ; il ne vérifiait pas la
>    réciproque. `opponentOf()` parcourt les paires en lisant `left` puis `right` : une paire
>    écrite à l'envers rendrait la tension à sens unique — la Fonderie coûterait au Cercle, et
>    le Cercle ne coûterait rien à la Fonderie. Le défaut serait **muet**, et il donnerait un
>    côté avantageux à l'axe doctrinal.
> 2. **Latéral partout, du côté du code.** `FactionLadderContractTest` (FAC-09a) tient la loi
>    sur les données ; elle ne dit rien d'un **second lecteur** qui irait chercher une
>    statistique ailleurs dans le moteur — une porte de service qui ne se verrait dans aucune
>    donnée. Exactement une classe lit une récompense pour en tirer une statistique.
> 3. **La seconde borne d'Hostile : jamais une agression** (§6.4 d, §6.1 — le Serment). Le
>    catalogue tient la première (la boucle cœur reste ouverte) ; la seconde n'était vérifiée
>    nulle part, et c'est la plus facile à franchir par inadvertance.
> 4. **Aucun geste d'un joueur ne peut nuire directement à un autre joueur**, la traduction
>    testable de la règle 11. Il se lit dans la **signature** : un geste qui nuirait à un autre
>    joueur devrait le nommer, et aucun service de réputation ne prend deux joueurs.
>
> **Ce que la dérivation a trouvé, et qui la valide.** La liste des canaux entre joueurs n'est
> pas écrite dans le test — *un test qui n'interroge que sa propre liste ne mesure plus rien dès
> qu'elle vieillit* (DOM-09) : un canal se **dérive** de sa forme, un fichier qui déplace un
> objet d'un inventaire à un autre et qui se demande si l'objet peut circuler. Le **coffre de
> guilde** échappait aux deux critères : il réécrivait `isExchangeable()` en deux conditions
> séparées — les mêmes deux, dans le même ordre — au lieu de l'appeler. Il portait bien son
> verrou, *mais rien n'aurait dit le contraire*. Le prédicat a désormais un seul endroit, les
> deux messages distincts sont conservés (ce qui a de la valeur, c'est de dire **laquelle** des
> deux raisons s'applique), et le coffre entre dans la dérivation comme les autres.
>
> Chaque invariant porte un garde-fou de **non-vacuité** : la dérivation doit trouver au moins
> quatre canaux, la réflexion doit voir au moins dix signatures, le catalogue au moins une
> conséquence par maison. *Un contrat vide ressemble à un contrat tenu.*

---

## Risques

| Risque | Parade |
|---|---|
| Le marché gris devient la norme | Les trois garde-fous du receleur (coupe > taxe max, plafond de lots, Ami requis) sont des critères d'acceptance testés |
| Les contrats Fonderie remplacent le HV | Garde-fou de prix < médiane HV, vérifié au tirage |
| La tension punit l'exploration des contenus | La décote ne commence qu'au-delà d'Ami — tout le monde peut tout goûter |
| Cinq zones Exalté = cinq chantiers de contenu | Zones `interior` petites, déclaratives, un sous-jalon par maison |
| La contrefaçon fuit vers les joueurs | Verrous de canaux testés en FAC-07 et re-testés en FAC-10 |
