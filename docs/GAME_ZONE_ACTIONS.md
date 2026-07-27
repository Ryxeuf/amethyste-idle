# Actions et information de zone — cadrage

> **Statut : proposé** · Juillet 2026
> Source de vérité de **ce qu'on peut faire et de ce qu'on voit** sur l'écran de zone.
> Complète (ne remplace pas) : [GAME_PRINCIPLES.md](GAME_PRINCIPLES.md) (principes de design),
> [BALANCE.md](BALANCE.md) (énergie, rendement, PV), [PIVOT_PBBG.md](PIVOT_PBBG.md) (décision du pivot),
> `design/Amethyste - Design System.dc.html` §07 (traduction visuelle).
> Les règles absolues de [CLAUDE.md](../CLAUDE.md) priment sur tout ce qui suit.

Ce document existe pour une raison précise : l'écran de zone est **le** écran du jeu
depuis le pivot PBBG, il agrège aujourd'hui onze blocs empilés à plat, et chaque
nouvelle feature y ajoute le sien. Sans cadre, la reprise design invente — des
niveaux de zone, des rendements à la minute, des paliers de profondeur — c'est-à-dire
exactement ce que la section 07 du design system a déjà dû corriger une fois.

---

## 1. Garde-fous — ce qu'on n'invente pas

Cinq contraintes ferment la porte aux inventions les plus tentantes. Toute
proposition qui en viole une est rejetée sans discussion.

| # | Contrainte | Origine | Ce qu'elle interdit |
|---|-----------|---------|---------------------|
| G1 | **Pas de niveau global** | CLAUDE.md §6 | « zone niveau 12 », « recommandé niveau 8 », toute jauge de puissance globale |
| G2 | **Le budget d'énergie est égalitaire** | BALANCE.md §8 | Tout bonus qui augmente le **débit** d'actions : réduction de coût, action gratuite, recharge, cooldown raccourci |
| G3 | **Le combat ne coûte pas d'énergie** | PIVOT_PBBG.md | Facturer les tours, les sorts de combat, la fuite |
| G4 | **Compétences = passifs uniquement** | CLAUDE.md §9 | Un bouton d'action débloqué par une compétence de l'arbre |
| G5 | **Pas de PvP** | CLAUDE.md §11 | Vol de filon, blocage d'un joueur, file d'attente compétitive, tag de mob exclusif |

**Corollaire de G2, central pour ce document** : une compétence de découverte
**révèle de l'information** ou **augmente le rendement d'une action**. Elle ne rend
jamais l'action moins chère et n'en donne jamais davantage par jour. C'est ce qui
permet d'ajouter de la profondeur au métier de mineur sans creuser l'écart avec le
joueur qui se connecte vingt minutes par jour.

---

## 2. L'existant — ce que la zone propose aujourd'hui

Onze points d'entrée, tous livrés, tous affichés sur le même écran, dans cet ordre.

| Action | Coût | Nature | Code |
|--------|------|--------|------|
| **Explorer** | ⚡ 5 | Tirage pondéré : mob / coffre / filon / PNJ / rien | `ExploreService` |
| **Chasser** | ⚡ 5 | Combat ciblé sur un monstre **déjà au bestiaire** | `HuntService` |
| **Récolter** | ⚡ 3 | Puise dans un filon partagé de la zone | `GatherService` |
| **Assaut de boss** | ⚡ 10 | Coup porté à un boss de zone asynchrone | `ZoneBossService` |
| **Rejoindre un événement** | ⚡ 10 | Participation à un événement de zone temporaire | `ZoneEventService` |
| **Expédition** | — | Time-gating réel : 1 h / 4 h / 12 h, hors ligne | `ExpeditionService` |
| **Voyager** | — | Time-gating réel via une connexion du graphe | `ZoneTravelService` |
| **Donjon de groupe** | — | Lancement + tours semi-synchrones | `GameEngine/Dungeon` |
| **Parler à un PNJ / boutique** | — | Dialogue, marchand (horaires) | `PnjController` |
| **Échoppe joueur** | — | Boutique tenue par un autre joueur | `PlayerShopController` |
| **Chat & présence de zone** | — | Mercure SSE, invitation en groupe | `ChatManager` |

Les coûts sont paramétrables (`zone.energy.cost.*`) ; les valeurs ci-dessus sont
les défauts du code. Repère de budget : **240 points par jour**, soit ~48 explorations
ou ~80 récoltes.

---

## 3. Le cadre : trois registres, pas onze blocs

L'écran n'a pas onze sujets, il en a **trois** — ceux de la section 9 de BALANCE.md,
qui existent déjà dans l'équilibrage et qu'il suffit de rendre visibles.

```
┌─ REGISTRE 1 — TENTER (coûte de l'énergie, résultat immédiat) ────────────┐
│  Explorer · Chasser · Récolter · Assaut de boss · Rejoindre un événement │
│  → budget égalitaire, jamais plus de 240 pts/jour                        │
├─ REGISTRE 2 — ENGAGER (coûte du temps réel, un seul créneau à la fois) ──┤
│  Voyager · Expédition                                                     │
│  → tourne sans le joueur ; s'affiche comme un état, pas comme un bouton  │
├─ REGISTRE 3 — FRÉQUENTER (gratuit, illimité) ───────────────────────────┤
│  PNJ · échoppes · donjon de groupe · chat · présence · profils           │
│  → la couche « temps investi » : plus on joue, plus on gagne             │
└──────────────────────────────────────────────────────────────────────────┘
```

**Règles de composition qui en découlent :**

1. Le registre 1 est **un seul bloc**, toujours au même endroit, chaque action
   portant son coût en ⚡. On n'éparpille plus « Chasser » et « Récolter » dans des
   sections séparées de « Explorer ».
2. Le registre 2 est **exclusif** : voyage et expédition occupent le même créneau.
   Quand l'un tourne, l'écran affiche un état (« retour dans 38 min ») et le
   registre 1 est désactivé — ce que le code fait déjà, mais que la mise en page ne
   dit pas.
3. Le registre 3 vit **sous** les deux autres. Il ne dispute jamais l'attention à
   l'action primaire.
4. **Une seule action primaire** : « Explorer ». C'est la règle du design system,
   elle est déjà tenue, elle le reste.

---

## 4. Le principe d'information : la zone montre ce que le personnage sait

C'est la réponse à « est-ce qu'on a besoin de voir tous les spots de ressources ? ».

> **Non. L'écran de zone n'est pas un inventaire de la zone, c'est la fiche de
> renseignement du personnage sur cette zone.**

Trois niveaux de connaissance, par joueur et par zone. Ils ne créent pas de contenu :
ils décident de ce qui est **affiché**.

| Niveau | Comment on y arrive | Ce qui devient visible |
|--------|--------------------|------------------------|
| **Rumeur** | Zone jamais visitée, connue par une connexion | Nom, type, sûre/non sûre, durée de voyage |
| **Repérée** | Au moins une visite (`PlayerVisitedZone`) | Présence joueurs, PNJ, échoppes, connexions ouvertes, événements actifs, donjons, filons **connus** du joueur, proies **au bestiaire** |
| **Cartographiée** | Repérage complet (§8) ou carte consommée (§9) | Tous les filons de la zone + stocks + respawn, table de rencontres jour/nuit, connexions verrouillées, bornes de butin des coffres |

Deux règles de rédaction pour ne pas transformer ça en frustration :

- **Le manque se montre, il ne se cache pas.** Une zone repérée qui contient
  quatre filons dont un seul connu affiche : *« Herbes de la forêt · 19/24 »* puis
  *« 3 filons non identifiés — explorez, ou apprenez à les reconnaître »*. Le
  compteur d'inconnu est ce qui donne envie d'explorer ; l'absence pure ne donne
  envie de rien. C'est aussi la règle des états vides du design system : un état
  vide dit quoi faire.
- **Ce qu'on sait ne dit pas ce qu'on peut faire.** Voir un filon de mithril et
  pouvoir le miner sont deux choses distinctes (§6.3).

---

## 5. Filons : la découverte

Quatre sources de connaissance d'un filon. Trois s'appuient sur des mécaniques déjà
en base ; aucune n'ajoute de spot au monde.

### 5.1 Le passif d'arbre — la demande directe

Dans l'arbre **Mineur** (idem Herboriste, Pêcheur, Dépeceur), des paliers passifs
« Prospection » révèlent les filons d'une profession jusqu'à un rang de rareté.

```php
// Forme déclarative, alignée sur celles déjà acceptées par ActionYieldResolver
'actions' => [['action' => 'discovery', 'profession' => 'mining', 'tier' => 2]],
```

Un joueur qui a « Prospection II » en minage voit **tous les filons de minage de
rang ≤ 2 de toutes les zones**, sans les avoir explorées. C'est le bénéfice de
métier : le mineur lit la roche.

**Garde-fou G2** : ce passif ne réduit pas le coût de `Récolter` et n'accorde pas
d'action supplémentaire. Il fait gagner du **temps de joueur**, pas du débit.

### 5.2 Le catalogue de ressources — déjà en base, inexploité

`PlayerResourceCatalog` compte les récoltes par ressource et déclare déjà trois
paliers : `TIER_LOCATIONS = 5`, `TIER_RECIPES = 25`, `TIER_SPECIALIST = 50`. Le
palier « localisations » n'a aujourd'hui aucun effet de jeu.

**Décision** : avoir récolté 5 fois une ressource **où que ce soit** la rend visible
partout. On apprend à reconnaître la sauge en la cueillant, pas en lisant une carte.

### 5.3 L'exploration — réparer un trou existant

L'événement `harvest` d'`ExploreService::resolveHarvest()` écrit une ligne de
journal (« filon repéré ») et **ne fait rien d'autre**. Dans la Forêt des murmures
c'est 12 % des explorations de jour qui coûtent 5 énergie pour un message.

**Décision** : cet événement **révèle un filon non connu** de la zone courante,
tiré parmi les inconnus. S'il n'y en a plus, il retombe sur le comportement actuel
(ou, mieux, alimente le repérage de §8). Le coût est nul — c'est du branchement, pas
de la mécanique nouvelle — et il donne enfin un sens à un cinquième du tableau de
rencontres.

### 5.4 Les cartes — objets échangeables

Voir §9.

### 5.5 Les paliers d'information du prospecteur

Voir un filon et **le lire** sont deux choses différentes. C'est là que l'arbre de
métier paie, et c'est un levier parfaitement propre au regard de G2 : l'information
exclusive ne donne ni énergie, ni action, ni butin. Elle donne de la **décision**.

| Palier | Ce que le prospecteur voit et que les autres ne voient pas |
|--------|-----------------------------------------------------------|
| **Aucun** | Le filon existe, son nom, sa profession. Rien de plus. |
| **Prospection I** | La **vitalité** du filon (stock courant / capacité) et son rendement nominal |
| **Prospection II** | Le **temps de retour** à pleine vitalité, et sa vitesse de régénération |
| **Prospection III** | Son **rendement effectif attendu** — vitalité et fatigue personnelle comprises (§6.2) : il sait ce que le prochain coup de pioche va réellement rapporter |
| **Prospection IV** | La vitalité de ce même minerai **dans les zones adjacentes**, sans y voyager |

Le palier IV est le plus intéressant en jeu coopératif : il fait du mineur avancé un
**courtier d'information**. C'est lui qui dit à la guilde « le fer des Mines est à
15 %, montez au nord » — un rôle social que rien d'autre ne remplit aujourd'hui, et
qui donne une raison de monter un arbre de récolte au-delà du rendement.

Les autres joueurs, eux, voient le filon comme un état qualitatif : *florissant /
entamé / éreinté*. Assez pour décider, pas assez pour optimiser.

---

## 6. Filons : partage et concurrence

### 6.1 Faut-il partager ? Oui.

Le stock partagé est **la seule chose de l'écran qui rende les autres joueurs
réels**. Un filon personnel transforme la zone en interface solo à chat intégré. Le
design system l'a déjà écrit noir sur blanc : *« stock commun à tous »*, *« c'est une
information sociale, pas un compteur perso »*.

L'alternative — instancier les filons par joueur — est **rejetée** : elle supprime la
seule tension coopérative de la boucle, et rien d'autre dans le jeu ne la remplace
(pas de PvP, pas de compétition de classement en zone).

### 6.2 Ce qui ne va pas dans le modèle livré

Le modèle livré est *stock partagé, épuisement, respawn intégral après délai*. Il a
trois défauts constatés dans le code, dont deux sont des bugs de conception :

| Défaut | Constat | Effet en jeu |
|--------|---------|--------------|
| **Régénération tout-ou-rien** | `GatherService::hasRespawned()` exige `stock <= 0` | Un filon laissé à 3/24 **ne repousse jamais**. La zone se dégrade en permanence. |
| **Pas de verrou** | Aucun lock ni `UPDATE … WHERE stock >= :n` | Deux récoltes simultanées lisent le même stock : survente possible sur les dernières unités |
| **Monopolisable** | Aucune borne par joueur | 240 énergie = 80 récoltes ; un seul joueur vide les quatre filons d'une forêt et personne d'autre ne récolte de la journée |

### 6.3 La saturation — pourquoi un stock partagé exclut le joueur occasionnel

Avant de choisir un modèle, il faut regarder ce que la régénération continue supporte
réellement. Un filon régénère `capacity` unités par `respawn_seconds`. Un joueur
dispose de 240 énergie par jour, soit **80 récoltes** à ⚡ 3.

| Filon | Capacité | Respawn | Unités/jour | Récoltes/jour supportées | **Joueurs à plein temps avant saturation** |
|-------|---------:|--------:|------------:|-------------------------:|-----------------------------------------:|
| Filon de cuivre | 24 | 20 min | 1728 | ~864 | **10,8** |
| Filon de fer | 18 | 30 min | 864 | ~576 | **7,2** |
| Mandragore des tourbières | 12 | 30 min | 576 | 576 | **7,2** |
| Spores fantômes | 12 | 35 min | 493 | ~329 | **4,1** |
| **Veine d'or** | **8** | **60 min** | **192** | **192** | **2,4** |

La veine d'or sature à **deux joueurs et demi**. Au-delà, elle reste à zéro en
permanence, et le joueur qui se connecte vingt minutes le soir trouve un filon vide
tous les soirs de sa vie. Les filons rares sont, par construction, les plus fragiles :
plus la ressource est précieuse, plus sa capacité est basse, plus vite elle sature.

**La réponse est donc oui : le modèle de la §6.2 telle qu'elle était d'abord écrite
— stock partagé, refus quand le stock est à zéro — exclut le joueur occasionnel dès
que la zone est fréquentée.** La part personnelle limite ce qu'un joueur *prend*,
elle ne *réserve* rien à celui qui n'est pas là. Elle est anti-monopole, pas
anti-saturation. C'est une erreur de conception, et elle frappe précisément le
joueur que les trois couches de BALANCE.md §9 cherchent à protéger.

### 6.4 Modèle retenu : le filon module le rendement, il ne ferme jamais l'accès

**Une récolte ne peut pas échouer.** Le stock partagé cesse d'être un portillon pour
devenir un **facteur de rendement**. C'est le même déplacement que celui déjà fait
pour l'énergie : l'accès est égalitaire, l'investissement se voit dans ce que chaque
action rapporte.

```
rendement = tirage nominal (YAML)
          × bonus d'arbre        (ActionYieldResolver, plafonné à +100 %)
          × vitalité partagée    (1,00 filon plein → 0,60 filon à sec)
          × fatigue personnelle  (1,00 frais → 0,40 après acharnement)
          plancher absolu : 1 unité
```

1. **Vitalité partagée** — c'est l'ancien stock, régénéré en continu
   (`capacity / respawn_seconds`). À zéro, le filon n'est pas fermé : il est
   **éreinté**, et rend 60 % du nominal. C'est l'information sociale de la §6.1,
   conservée intacte, mais sa conséquence devient graduelle.
2. **Fatigue personnelle** — vos propres prélèvements récents sur *ce* filon, qui se
   dissipent en quelques heures. Elle ne pénalise que celui qui s'acharne, et elle
   est invisible aux autres.
3. **Plancher** — une récolte rapporte toujours au moins une unité. Le message
   *« filon épuisé, revenez dans 20 minutes »* disparaît : c'est le pire message
   possible pour quelqu'un qui a vingt minutes.

**Ce que ça donne concrètement :**

| Situation | Vitalité | Fatigue | Rendement |
|-----------|---------:|--------:|----------:|
| Joueur occasionnel, filon intact | 1,00 | 1,00 | **100 %** |
| **Joueur occasionnel, filon farmé par vingt personnes** | 0,60 | 1,00 | **60 %** |
| Joueur assidu qui campe le même filon | 0,60 | 0,40 | **24 %** |
| Joueur assidu qui tourne entre zones | 0,85 | 1,00 | **85 %** |

Le joueur occasionnel n'est **jamais** exclu : au pire il récolte 60 % — et il n'a
de toute façon pas 80 récoltes à dépenser. Le joueur assidu qui campe s'auto-punit
et a tout intérêt à répartir ses 80 récoltes sur plusieurs filons et plusieurs
zones, ce qui est exactement le comportement souhaité : il fait vivre la carte au
lieu de la stériliser.

C'est une **tragédie des communs qui se lit correctement** : surexploiter dégrade le
rendement pour tout le monde, y compris pour celui qui surexploite, et le groupe a
une raison collective de s'étaler. Le tout sans qu'un joueur puisse jamais en bloquer
un autre — G5 est tenu par construction, ce qui n'était pas le cas d'un modèle où le
premier arrivé prend tout.

**Effet de bord bienvenu** : la course au verrou disparaît. Puisqu'aucune récolte
n'est refusée faute de stock, la survente de la dernière unité (E2) cesse d'être un
bug fonctionnel. Le décompte reste atomique pour la justesse de la vitalité affichée,
mais un échec de compteur ne prive plus personne de son action.

**Conséquence à assumer** : le mineur assidu ne compense plus par le volume. Il
compense par le **rendement** (`ActionYieldResolver`, plafonné à +100 %), par
l'accès aux **filons rares** que son arbre débloque, et par l'**information
exclusive** de la §5.5 qui lui dit où le rendement est le meilleur. C'est exactement
la répartition voulue par BALANCE.md §8.

### 6.5 Accès : le champ `profession` ne sert à rien aujourd'hui

`GatherService` ne consulte **jamais** les compétences du joueur. Un personnage sans
un seul point en minage peut vider une veine d'or. Le champ `profession` d'un filon
n'est utilisé que pour choisir une icône dans le template.

Symétriquement, l'arbre Mineur contient une trentaine de compétences
`['action' => 'harvest', 'spots' => ['spot-mithril-xs', …]]` qui pointent vers les
`ObjectLayer` de l'ancienne carte navigable, supprimée avec ZON-21. **Ces
compétences ne débloquent plus rien.** Un joueur paie 30 points de talent pour
« Minage du mithril » et le mithril était déjà minable sans.

**Décision** : la donnée déclarative de zone porte l'exigence, l'arbre porte le
droit.

```yaml
# config/game/zones/world_1.yaml
gather:
  - { slug: veine-d-or, item: ore-gold, profession: mining, tier: 2, capacity: 8, … }
```

Un filon de `tier` *n* exige d'avoir appris la compétence d'accès correspondante de
la profession. Les compétences `harvest`/`spots` existantes sont **réécrites** en
`['action' => 'harvest', 'profession' => 'mining', 'tier' => 2]`. Le filon reste
**visible** si le joueur le connaît (§4) : il voit ce qu'il rate, avec la mention
*« exige Minage de l'or »*. C'est ce qui vend l'arbre de talent.

---

## 7. Monstres : même principe, déjà à moitié en place

La découverte des monstres est **déjà implémentée** et fonctionne comme demandé :
`HuntService::getHuntTargets()` ne propose que les monstres présents **et** inscrits
au bestiaire du joueur. On ne chasse que ce qu'on a déjà rencontré.

Ce qui manque est la symétrie avec les filons :

1. **Le compteur d'inconnu.** Une zone non sûre sans proie affiche aujourd'hui un
   état vide générique. Elle doit dire : *« 4 créatures rôdent ici, aucune que vous
   sachiez traquer — explorez pour les rencontrer »*.
2. **Le passif de pistage** (arbres Chasseur, Dompteur, Vagabond) :
   `['action' => 'discovery', 'profession' => 'hunting', 'tier' => n]` révèle les
   créatures présentes non encore rencontrées, sous forme de **traces** — le joueur
   sait *quoi* rôde, il doit toujours l'affronter une fois pour pouvoir le traquer.
3. **Rappel G2** : le pistage ne réduit pas le coût de `Chasser` (⚡ 5) et ne donne
   pas de chasse gratuite.
4. Les créatures nocturnes (`night.mob_slugs`) restent **invisibles de jour**, quel
   que soit le rang de pistage. La variance jour/nuit est un contenu, pas une
   information à déverrouiller.

---

## 8. Explorer doit payer : le repérage de zone

Aujourd'hui, explorer une zone pour la 200ᵉ fois donne exactement ce que la première
donnait. Il n'existe aucune trace cumulative de l'effort d'exploration — alors que
23 % des tirages de la forêt sont « rien ».

**Décision** : `PlayerVisitedZone` porte un compteur de **repérage**, incrémenté à
chaque exploration de la zone (y compris — surtout — sur un tirage « rien », qui
cesse ainsi d'être une action perdue). Quatre paliers, purement informatifs :

| Palier | Ce qui se débloque |
|--------|-------------------|
| **25** | La table de rencontres de la zone devient lisible (le bandeau « ce que la zone tire », valeurs réelles du YAML) |
| **50** | Les bornes de butin des coffres, et la table **nocturne** |
| **100** | Tous les filons de la zone, quel que soit le métier du joueur |
| **150** | Les connexions `requires_discovery` au départ de la zone |

Le repérage **ne donne aucun bonus chiffré** : ni rendement, ni énergie, ni loot.
C'est de l'information et du confort. C'est délibéré — un bonus cumulatif attaché au
temps passé récompenserait la disponibilité, ce que G2 refuse.

Le palier 100 est aussi le filet de sécurité de tout le système : un joueur qui
refuse les cartes et les arbres de récolte finit quand même par tout voir, en
explorant. Rien n'est définitivement hors de portée.

---

## 9. Les cartes de zone

Un objet, pas une mécanique nouvelle. Type `resource` existant, sous-type `map`.

**Règle fondatrice — une carte révèle, elle ne crée pas.** Une carte n'ajoute jamais
un filon, ne fait jamais apparaître de ressource, n'augmente jamais une capacité. Le
monde a le contenu que le YAML déclare. Une carte qui créerait des spots serait une
imprimante à ressources dans une économie tenue par les joueurs (cf.
PLAN_PLAYER_ECONOMY).

| Objet | Rareté | Effet | Consommation |
|-------|--------|-------|--------------|
| **Croquis de zone** | Commun | Révèle tous les filons d'**une** zone | Permanent, une zone |
| **Relevé de rencontres** | Peu commun | Révèle la table jour **et** nuit d'une zone | Permanent, une zone |
| **Carte régionale** | Rare | Révèle les connexions `requires_discovery` d'une région | Permanent, une région |
| **Carte au trésor** | Rare | Une exploration à résultat **forcé** : coffre majoré, dans la zone indiquée | À usage unique |

La carte au trésor est la seule qui produise du butin, et elle est à usage unique :
c'est un objet de loot et de commerce, pas une source.

**Sources** : loot de coffre (événement `chest`), butin de boss et de donjon,
récompense de quête, marchand PNJ, et **hôtel des ventes** — une carte est un objet
non lié, donc échangeable. C'est sa raison d'être économique : un joueur explore, un
autre achète le temps qu'il n'a pas.

### 9.1 Le domaine « Cartographe » n'est pas viable — et c'est réparable autrement

La question mérite d'être tranchée, parce que la chaîne d'entrée existe : le
parchemin vient du Dépeceur, l'encre de l'Herboriste. Un 33ᵉ domaine s'insérerait
sans peine dans la synergie élémentaire (air, aux côtés du Vagabond).

**Ce qui le tue est la demande, pas l'offre.** Les quatre domaines d'artisanat
livrés produisent des biens qui **quittent l'économie** : une potion se boit, une
arme s'use et se remplace, un bijou se déclasse au palier suivant. La demande se
renouvelle, donc le métier vit. Une carte, elle, révèle une information **permanente
et non rivale** : chaque joueur en achète une par zone, **une seule fois, à vie**.

```
Demande totale = nb de joueurs × nb de zones          (puis zéro, pour toujours)
```

Un domaine complet — quarante compétences, un arbre, des recettes, un outil — adossé
à un marché qui sature définitivement condamne le premier Cartographe à être aussi le
dernier. Ce n'est pas un problème d'équilibrage, c'est un problème de forme : on
n'accroche pas une profession à un stock fini.

**Le critère qui le rendrait viable** est unique et clair : **il faudrait que la
connaissance périme**. Si les tables de rencontres et les filons se recomposaient à
chaque saison (`InfluenceSeason`, `SeasonArc`, `Festival` existent déjà), les cartes
redeviendraient un consommable et la demande se renouvellerait tous les trois mois.
C'est une décision de portée bien plus large que cet écran — elle touche la
saisonnalité du monde — et elle n'est pas à prendre ici. Tant qu'elle n'est pas
prise, **pas de domaine Cartographe**.

**Ce qu'on fait à la place** : la cartographie est une **facette des métiers
existants**, pas un métier. Un palier avancé de chaque arbre de récolte
(« Relevé de prospection ») permet de coucher sur le parchemin **ce que le joueur
sait déjà** — et rien d'autre.

```php
'actions' => [['action' => 'survey', 'profession' => 'mining']],
```

Quatre propriétés qui tombent juste :

- **Pas de domaine nouveau.** Le coût de conception est un palier par arbre.
- **La chaîne d'entrée existe déjà** : parchemin (Dépeceur) + encre (Herboriste).
  Deux métiers de récolte gagnent un débouché sans qu'on écrive une ligne d'économie.
- **On ne peut vendre que ce qu'on connaît.** Le croquis encode les filons que son
  auteur a lui-même découverts — l'objet est adossé à un effort réel, pas à une
  recette.
- **La saturation cesse d'être fatale** : c'est un revenu d'appoint pour un
  prospecteur, pas le gagne-pain d'une profession entière.

Défaut retenu, donc : **cartes non craftables par un domaine dédié**, productibles
en appoint par les récolteurs avancés, et par ailleurs loot / quête / marchand / HV.

---

## 10. L'écran : inventaire d'information et hiérarchie

Ce que l'écran affiche, dans l'ordre, et **pourquoi**. C'est le contrat que la
reprise design doit tenir.

```
1. IDENTITÉ            Nom · type · sûre/non sûre · phase jour-nuit · illustration
2. ÉTAT DU PERSONNAGE  Énergie (n/240 + prochain point) · PV (+ plein dans)
3. CRÉNEAU ENGAGÉ      Voyage OU expédition en cours — sinon, rien du tout
4. URGENCES            Événement de zone actif · boss · donjon en cours
5. AGIR                Les 5 actions d'énergie, coûts en ⚡, une seule primaire
6. CE QUE JE SAIS      Filons connus (vitalité partagée, rendement attendu selon
                       le palier de prospection du joueur, exigence si
                       verrouillé) · proies au bestiaire · compteurs d'inconnu
7. LA ZONE             PNJ · échoppes · donjons proposés · joueurs présents · chat
8. PARTIR              Connexions, durée réelle (monture comprise), verrous
```

**Invariants de l'écran :**

- Tout chiffre est en monospace, aligné à droite (`ds-num`). Sans exception :
  énergie, PV, vitalité, durée, coût, rendement.
- Un filon n'est **jamais** affiché comme fermé. Éreinté se dit *« rendement
  minimal »*, jamais *« revenez dans 20 minutes »*.
- Une seule action primaire — « Explorer ». Tout le reste est secondaire ou discret.
- Chaque liste vide dit **quoi faire**, jamais « aucun élément ».
- Le bloc 3 est **exclusif** et désactive le bloc 5. Un seul créneau de temps réel.
- Les blocs 4 et 7 sont **conditionnels** : ils disparaissent, ils ne s'affichent pas
  vides.
- Aucun chiffre affiché n'est calculé pour l'affichage : tout vient du YAML de zone,
  d'un paramètre `zone.*` ou de l'état en base. Si une valeur n'a pas de source, elle
  ne s'affiche pas.

**Ce qui change par rapport au template livré** : les onze blocs à plat deviennent
huit sections ordonnées ; « Chasser » et « Récolter » rejoignent « Explorer » dans le
bloc 5 au lieu d'avoir chacun leur titre ; les filons passent de « tout est visible »
à « ce que je sais + ce qui me manque » ; le bandeau « ce que la zone tire » du
design system §07 n'apparaît qu'à partir du palier de repérage 25.

---

## 11. Écarts constatés dans le code livré

Relevés en préparant ce document. Ils sont indépendants de la reprise design et
peuvent être traités séparément.

| # | Écart | Fichier | Gravité |
|---|-------|---------|---------|
| E1 | Un filon partiellement vidé ne repousse jamais (`hasRespawned` exige `stock <= 0`) | `GatherService.php:238` | **Haute** — dégradation permanente du monde |
| E2 | Prélèvement sans verrou : survente possible en accès concurrent | `GatherService.php:129-139` | Moyenne |
| E3 | L'événement `harvest` d'exploration n'a aucun effet | `ExploreService.php:231` | Moyenne — 12 à 24 % des tirages |
| E4 | Aucune vérification de compétence ni d'outil à la récolte | `GatherService.php:101` | **Haute** — les arbres de récolte ne débloquent rien |
| E5 | ~30 compétences pointent vers des `spots` supprimés par ZON-21 | `SkillFixtures.php:3860+` | **Haute** — points de talent dépensés pour rien |
| E6 | `PlayerResourceCatalog::TIER_LOCATIONS` déclaré, jamais consommé | `PlayerResourceCatalog.php:17` | Basse |
| E7 | Un filon peut être vidé par un seul joueur en une session | conception | Moyenne |
| E8 | **Saturation** : la veine d'or ne supporte que 2,4 joueurs à plein temps ; au-delà, elle est vide en permanence et le joueur occasionnel ne la voit jamais autrement | conception (§6.3) | **Haute** |

E4 et E5 sont deux faces du même trou : le pivot a emporté le système qui donnait
leur sens aux arbres de récolte, et rien ne l'a remplacé.

---

## 12. Décisions actées et questions ouvertes

**Actées dans ce document** (à valider avant implémentation) :

1. Trois registres d'action, huit sections d'écran, une seule action primaire.
2. La zone affiche ce que le personnage sait — trois niveaux : rumeur, repérée,
   cartographiée.
3. Les filons connus se montrent ; les inconnus se comptent, ils ne se cachent pas.
4. Découverte d'un filon par : passif d'arbre, catalogue (5 récoltes), exploration,
   carte.
5. Les filons restent **partagés**, mais **une récolte n'échoue jamais** : la
   vitalité partagée et la fatigue personnelle modulent le **rendement**, elles ne
   ferment pas l'accès. Plancher d'une unité. C'est ce qui protège le joueur
   occasionnel de la saturation (§6.3-6.4).
6. L'accès à un filon est gaté par le `tier` déclaré en YAML et la compétence
   d'arbre correspondante ; la visibilité et l'accès sont deux choses distinctes.
7. La progression dans l'arbre de récolte donne une **information exclusive** sur les
   filons — vitalité, temps de retour, rendement effectif, état des zones voisines.
   Le récolteur avancé est un courtier d'information (§5.5).
8. Le repérage cumulatif d'une zone débloque de l'**information** à 25/50/100/150
   explorations, jamais un bonus chiffré.
9. Une carte révèle, elle ne crée pas.
10. **Pas de domaine « Cartographe »** : son marché sature définitivement (§9.1). La
    cartographie est un palier avancé des arbres de récolte existants.
11. Aucune compétence de découverte ne réduit un coût d'énergie ni n'augmente le
    débit d'actions.

**Ouvertes** :

- **Péremption de la connaissance** — si les filons et les tables se recomposaient
  par saison, les cartes redeviendraient un consommable et le domaine Cartographe
  redeviendrait viable (§9.1). Décision de portée mondiale, à prendre ailleurs.
- **Courbes de vitalité et de fatigue** — les facteurs 0,60 et 0,40, la vitesse de
  dissipation de la fatigue : posés comme repères paramétrables, à confronter aux
  données de jeu réelles.
- **Capacités des filons rares** — la veine d'or (8 / 60 min) sature à 2,4 joueurs.
  Même avec le modèle de rendement, une capacité aussi basse maintient la zone en
  permanence près du plancher. Les capacités du YAML sont à réviser en même temps.
- **Rangs de filons par profession** — combien de `tier` (2 ? 4 ?), et redécoupage
  des ~30 compétences `spots` existantes sur ces rangs.
- **Fatigue personnelle et groupes** — quatre joueurs qui exploitent un filon
  ensemble subissent chacun leur propre fatigue, donc rien ne les pénalise
  collectivement. À vérifier en jeu : c'est voulu, le groupe doit rester avantageux.
