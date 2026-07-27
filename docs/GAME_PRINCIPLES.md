# Principes de jeu — Amethyste-Idle

> **Statut : adopté** · Juillet 2026
> Source de vérité des **grands principes de game design** post-pivot PBBG.
> Complète (ne remplace pas) : [PIVOT_PBBG.md](PIVOT_PBBG.md) (décision technique du pivot),
> [roadmap/PLAN_GUILD_CITY_CONTROL.md](roadmap/PLAN_GUILD_CITY_CONTROL.md) (contrôle de cité, livré),
> [roadmap/PLAN_PLAYER_ECONOMY.md](roadmap/PLAN_PLAYER_ECONOMY.md) (déclinaison en jalons de l'économie joueur),
> [roadmap/PLAN_NARRATIVE.md](roadmap/PLAN_NARRATIVE.md) (déclinaison en jalons de la narration),
> [GAME_ZONE_ACTIONS.md](GAME_ZONE_ACTIONS.md) (actions et information de l'écran de zone).

Ce document fige les décisions de direction issues des discussions de conception.
Les règles absolues du projet (pas de PvP, pas de niveau global, PvE coopératif,
progression par arbres, sorts actifs uniquement via matéria) restent définies dans
[CLAUDE.md](../CLAUDE.md) et priment.

---

## 1. Cadre général

Amethyste-Idle est un **PBBG** (persistent browser-based game) coopératif PvE :
monde en **graphe de zones**, rythmé par l'énergie et le time-gating réel, combat
tour par tour, matérias, guildes. La rétention vient de la **profondeur systémique**
(progression, économie, coopération, saisons), pas de la navigation ni d'un récit
consommé une seule fois.

Trois piliers systémiques se renforcent mutuellement et forment **une seule boucle** :

```
Économie joueur ─┬─ alimente ──> Contrôle de cité (taxe des marchés → trésor de guilde)
                 │
Contrôle de cité ─┴─ cadence ──> Saisons (thèmes, compétition d'influence, narration)
                 │
Saisons ─────────┴─ rythment ──> l'offre de plans, événements, demande économique
```

---

## 2. Contrôle des villes par les guildes

**Statut : livré** (jalons GCC-01 à GCC-20, cf. `PLAN_GUILD_CITY_CONTROL.md`).

Principes actés :

- **Compétition indirecte, jamais de PvP.** Les guildes accumulent de l'**influence**
  via des activités PvE (kills, craft, récolte, pêche, dépeçage, quêtes, défis).
- **Saisons de 4 semaines** (`InfluenceSeason`, avec thème et multiplicateurs) : à la
  fin, la guilde en tête d'une région en prend le **contrôle**.
- **Bénéfices du contrôle** : taxe commerciale reversée au trésor de guilde, réduction
  boutique pour les membres, titres, cosmétiques, **upgrades de ville**.
- **Adaptation au pivot zone** : le contrôle s'exprime *mieux* en modèle zone (bonus de
  zone contrôlée). Seul du recâblage est requis : détermination de région via
  `player.currentZone.region` au lieu de `player.map.region`. Les events domaine écoutés
  par l'`InfluenceListener` survivent au pivot.

**Décision clé** : la **taxe des marchés** (HV, échoppes, commissions) d'une région est
reversée au trésor de la guilde qui la contrôle. C'est ce qui fait du contrôle de cité
un **enjeu économique réel** et non seulement cosmétique.

---

## 3. Scénario et narration

**Décision (D8) : trame de monde large + acte d'introduction fort + narration épisodique
saisonnière.** Pas de campagne linéaire massive écrite d'avance.

Justification : dans ce genre, la rétention vient des systèmes, pas d'une histoire
linéaire. Une campagne fournie est un coût d'écriture élevé pour une valeur consommée
une fois. La contre-référence narrative (Fallen London) est un jeu *narrative-first*
au coût d'écriture colossal — ce n'est pas le modèle visé.

Objectif : **maximiser la valeur narrative par unité écrite.** Chaque couche est adossée
à une brique déjà présente dans le modèle (`Quest`, `GameEvent`, `InfluenceSeason`,
`Region`, `Zone`, `Pnj`) — poser la narration = *structurer* et *relier* l'existant, pas
bâtir un moteur de dialogue. Déclinaison en jalons : [roadmap/PLAN_NARRATIVE.md](roadmap/PLAN_NARRATIVE.md).

### 3.1 Les quatre couches narratives

| Couche | Rôle | Brique code support | Cadence | Coût d'écriture |
|---|---|---|---|---|
| **Trame de monde** | Socle immuable : lore des régions/factions, contexte, accroches | `Zone.description`, **Codex** (§3.5) | Écrite une fois | Moyen, amorti |
| **Acte d'introduction** | Onboarding narratif : pose l'univers *et* enseigne les systèmes | Chaîne `Quest` via `prerequisiteQuests` + marqueur d'arc (§3.6) | Une fois par joueur | Élevé, ponctuel |
| **Narration saisonnière** | Moteur épisodique : chaque saison porte une menace/un événement | `InfluenceSeason.theme` + `GameEvent` + `Quest.gameEvent` | Toutes les 4 semaines | Faible par saison (template) |
| **Contenu de fond** | Volume : chaînes de quêtes de zone répétables | `Quest` (`minRenownScore`, `isHidden`, `triggerCondition`) | Continue | Faible (hybride, §3.7) |

### 3.2 État du monde — hybride (D9)

**Décision : trame de fond stable + méta-arc lent qui n'avance que par jalons rares et
scénarisés.** Chaque saison est un **épisode résoluble** (le monde ne dépend pas de sa
mémorisation), mais quelques **basculements marquants** s'inscrivent durablement au canon
du serveur (une menace vaincue laisse une trace, une région change de main pour de bon).

Ni méta-arc persistant pur (poids d'un état de monde cumulatif, casse-tête de catch-up
pour les nouveaux, cohérence fragile), ni reset total (le monde « ne garde rien » des
exploits collectifs). L'hybride donne un serveur qui **a une histoire** sans imposer de la
lire pour jouer. Concrètement : un petit **journal de monde** (faits canon horodatés)
alimenté par les résolutions de saison marquées « canon » ; le reste des saisons se clôt
sans trace durable au-delà des récompenses.

### 3.3 Structure d'un arc saisonnier

`InfluenceSeason.theme` cesse d'être un simple libellé : il **nomme un mini-arc** en quatre
beats, chacun matérialisé par un `GameEvent` daté et ses quêtes d'événement (`Quest.gameEvent`,
déjà branchable) :

1. **Amorce** (semaine 1) — un `GameEvent` d'ouverture révèle la menace/le thème ; quêtes
   d'accroche débloquées.
2. **Montée** (semaines 2-3) — activités PvE thématiques ; l'accumulation d'influence des
   guildes *est* la participation à l'arc (le pilier contrôle de cité fournit la tension).
3. **Climax** (fin semaine 3 / semaine 4) — événement de zone / boss de saison à rejoindre
   (généralisation de `WorldBossManager`, cf. PIVOT §Contenu de groupe).
4. **Résolution** — clôture ; distribution des récompenses de saison ; entrée éventuelle au
   journal de monde si le beat est marqué « canon » (§3.2).

Un arc saisonnier est donc une **donnée déclarative** (thème + 4 `GameEvent` + quêtes liées),
pas du code : ajouter une saison = ajouter de la donnée.

### 3.4 Cité × narration — crédits narratifs (D10)

**Décision : l'issue narrative d'une saison est prédéfinie et PvE (une seule branche à
écrire et tester) ; la guilde qui remporte la région en récolte les _crédits narratifs_.**

La guilde gagnante ne *réécrit* pas l'histoire (pas de branches multiples par vainqueur, coût
combinatoire prohibitif), mais elle **y laisse son nom** : titre de saison, mention dans le
récit de la région, cosmétiques, **nom gravé au journal de monde** (§3.2). C'est le liant
concret de la boucle à trois piliers : le contrôle de cité devient *l'auteur crédité* de la
résolution saisonnière, sans coût d'écriture proportionnel au nombre d'issues.

### 3.5 Le Codex — foyer de la trame et surface de rétention

La trame de monde vit aujourd'hui, diffuse, dans `Zone.description`. On lui donne un foyer :
un **Codex** (journal de connaissance), écran joueur où chaque entrée (région, faction,
bestiaire lore, fait de saison) se **débloque par la découverte** : visiter une zone, vaincre
un boss, terminer un arc, clore une saison. Double fonction :

- **Lecture** de la trame large, à son rythme, sans la scripter dans le flux de jeu.
- **Rétention** : la complétion du Codex est un objectif de collection (à croiser avec les
  succès existants), et le journal de monde (§3.2) s'y affiche — le joueur voit l'histoire
  du serveur s'écrire, avec le nom des guildes créditées.

### 3.6 Marqueur d'arc sur les quêtes (impact modèle)

Rien ne distingue aujourd'hui une quête d'« acte principal » d'une quête de fond, ni ne les
regroupe. On enrichit `Quest` d'une notion d'**arc** : `story_arc` (slug de l'arc, nullable —
`null` = quête isolée) + `arc_order` (position dans l'arc). L'acte d'introduction est
simplement l'arc `intro` ; un arc saisonnier réutilise le même mécanisme. Le chaînage dur
reste porté par `prerequisiteQuests` ; `story_arc` sert au **regroupement, à l'affichage
(journal de quêtes par arc) et au marquage narratif**.

### 3.7 Contenu de fond — hybride procédural / écrit

**Décision : squelettes procéduraux + points d'ancrage écrits à la main.** Les chaînes de
quêtes de zone reposent sur des **gabarits** (structure, objectifs, récompenses générés à
partir des tables de zone déjà déclaratives) dont les **nœuds saillants** (donneur de quête
mémorable, twist, révélation liée au lore) sont écrits à la main. On obtient du volume sans
le coût d'écriture intégral, et sans le vide d'un contenu 100 % généré. Le contenu de fond
**ne bloque jamais** la progression système (il l'enrobe).

---

## 4. Économie interne gérée par les joueurs

**Décision : économie de production principalement gérée par les joueurs, avec
modération, et un plancher hybride.** Tout l'équipement et les consommables de valeur
sont **craftés par les joueurs** ; seuls les **plans** sont des découvertes ou des
récompenses (exploration, boss, quêtes, réputation).

Objectif : créer de l'**interdépendance entre joueurs**, moteur de rétention voulu par
le pivot. Références du genre : EVE, Albion, et l'artisanat de WoW (voir §4.6).

### 4.1 Plancher T1 chez les PNJ (indispensable)

Le **tier 1 de survie** (outils de base, potions/consommables de base) reste disponible
chez les PNJ **ou** garanti dans le loot du tutoriel/onboarding. Aucun joueur — nouveau
ou solo — n'est jamais **hard-bloqué** par un marché joueur vide (protection cold-start).

- **Tier 1** : PNJ / drops garantis (échangeable).
- **Tier 2 et au-delà** : craft joueur uniquement.

### 4.2 Plans = découvertes / récompenses

Les recettes de valeur ne s'achètent pas au marché : elles se **découvrent** (exploration,
zones), se **gagnent** (boss, quêtes, réputation de faction/région). Posséder un plan rare
fait de toi un fournisseur recherché. Le modèle de recettes existe déjà (`Recipe.craft`,
`requiredLevel`, `requiredSpecialization`).

### 4.3 Trois canaux de commerce, rôles distincts

Ne **jamais** les faire se cannibaliser. Chacun a un rôle net :

| Canal | Rôle | Trouve l'acheteur par | Timing | Objets liés ? |
|---|---|---|---|---|
| **HV régional** | Commodités, matériaux, volume | Prix (anonyme, liquide) | Instantané | Non |
| **Commandes de craft** | Service sur plan, à la demande | Le plan possédé | Client apporte les matériaux | **Oui (voir §4.5)** |
| **Échoppe** | Spécialités, pièces de marque | Réputation + localisation de l'artisan | Asynchrone | Non |

- **HV régional** : segmenté par région → la géographie compte (arbitrage, transport =
  temps de voyage = sink logistique). La taxe (`AuctionListing.region_tax_rate`, déjà
  présente) alimente la guilde contrôlante.
- **Commandes de craft** : le client fournit matériaux + commission, l'artisan fournit
  plan + savoir-faire. Deux directions : **tableau public régional** (anonyme) et
  **commande directe** (ciblée, via la vitrine d'un artisan).
- **Échoppe** : vitrine persistante **asynchrone** d'un artisan, rattachée à une zone,
  **gated** (housing + rang de métier, loyer d'entretien = gold sink auto-régulateur).
  Vend en escrow pendant que le propriétaire est déconnecté.

### 4.4 Découvrabilité et escrow (conditions de survie)

- **Escrow système partout** (objet ET gils bloqués par le serveur) : toute transaction
  asynchrone passe par un intermédiaire de confiance. Aucune ne repose sur la présence
  simultanée ou la bonne foi. WYSIWYG (pas de bait-and-switch).
- **Recherche transversale** obligatoire : « qui vend / peut crafter l'objet X, et dans
  quelle zone ? ». Une vitrine invisible ne sert à rien — c'est le vrai risque du modèle
  échoppe.

### 4.5 Objets liés (bind on pickup) — point stratégique

Décision qui réconcilie « économie 100 % joueur » et « objets puissants non-échangeables » :

- **HV et échoppes ne vendent que des objets échangeables.** Un objet lié est lié à son
  propriétaire, donc invendable — par construction, pas par limitation.
- **Seules les commandes de craft peuvent produire du bind-on-pickup**, et l'objet naît
  **lié au commanditaire, pas à l'artisan**. Conséquences :
  - Le client obtient un objet lié qu'il ne pourrait **jamais** acheter au marché.
  - L'artisan monétise son **service** (commission + réputation), pas l'objet.
  - **Anti-RMT et anti-inflation par construction** : un objet lié ne peut être ni flippé,
    ni farmé par des alts pour revente, ni vendu contre argent réel.

Hiérarchie qui en découle : **T1 échangeable** (marché libre) → **haut de gamme / endgame
lié** uniquement via commande (matériaux gatés PvE/récolte, plan gaté découverte, savoir-
faire gaté rang de métier). Les commandes sont donc **le pilier de l'endgame économique**,
stratégiquement **prioritaires sur les échoppes**.

**Impact modèle** : `Item.boundToPlayer` (booléen actuel) doit être enrichi en **enum de
type de liaison** : `none` / `bind_on_equip` (échangeable jusqu'au 1er équipement) /
`bind_on_pickup` (lié dès l'obtention).

### 4.6 Emprunts à WoW (curés)

À **prendre** : plancher T1 PNJ ; professions = arbres de domaine avec recettes de rareté
variable ; **interdépendance des métiers** (aucun métier autosuffisant) ; **consommables
perpétuels** (demande stable) ; réparation/durabilité (gold sink) ; bind on pickup vs bind
on equip ; recettes gatées par réputation ; **crafting orders** (commandes de craft).

À **ne pas prendre** : obsolescence du stuff à chaque saison (tue le craft joueur) ;
endgame raid-centrique ; tout niveau global (interdit par CLAUDE.md).

### 4.7 Modération et gold sinks

- **Modération** : réutiliser les patterns anti-exploit de l'influence (plafonds
  journaliers, diminishing returns, minimum de membres actifs) contre le price-fixing,
  le farm par alts et le RMT.
- **Gold sinks** (avec une éco joueur, les boutiques PNJ ne suffisent plus) :
  durabilité/réparation, taxe des marchés (= taxe de cité), loyers d'échoppe, frais de
  commande, respec, entretien du housing, consommables brûlés en expéditions.
- Levier de réglage n° 1 : le **type de liaison** (échangeable vs lié). À étalonner dans
  [BALANCE.md](BALANCE.md).

---

## 5. Décisions actées

| # | Décision |
|---|----------|
| D1 | T1 de survie chez les PNJ / loot d'onboarding ; T2+ craft joueur uniquement. |
| D2 | Plans = découvertes/récompenses, jamais achetables au marché. |
| D3 | Trois canaux distincts : HV régional (commodités), commandes (service/lié), échoppes (vitrine). |
| D4 | Taxe des marchés d'une région → trésor de la guilde contrôlante. |
| D5 | Seules les commandes produisent du bind-on-pickup, lié au commanditaire. |
| D6 | `Item.boundToPlayer` → enum de type de liaison (none / bind_on_equip / bind_on_pickup). |
| D7 | Séquençage : plancher T1 + liaison → HV régional → commandes → échoppes → métiers/équilibrage. |
| D8 | Narration : trame large + acte d'intro + narration saisonnière ; pas de campagne linéaire massive. |
| D9 | État du monde **hybride** : trame de fond stable + méta-arc lent (basculements canon rares, journal de monde) ; chaque saison est un épisode résoluble. |
| D10 | Cité × narration : issue de saison **prédéfinie** ; la guilde gagnante récolte les **crédits narratifs** (titres, mention, cosmétiques, nom au journal), sans branches par vainqueur. |
| D11 | **Codex** : foyer de la trame de monde, débloqué par la découverte ; double rôle lecture + rétention (collection + journal de monde). |
| D12 | `Quest` enrichie d'un marqueur d'**arc** (`story_arc` + `arc_order`) pour regrouper/afficher/marquer ; le chaînage dur reste sur `prerequisiteQuests`. |
| D13 | HV : **segmentation régionale stricte**. Une annonce appartient au marché où elle a été déposée ; on n'accède à un marché qu'en s'y rendant. Le transport n'est pas un système à part — c'est le temps de voyage du graphe de zones. Les ventes flash, canal **système**, restent globales. |
| D14 | Échoppes : **les deux**. La demeure donne l'échoppe et ses 6 emplacements de base — l'adresse appartient au joueur, nul ne peut la lui retirer. La cité loue les **étals** au-delà : en nombre fini par ville, payés à la guilde contrôlante. Une guilde hostile contient un artisan, elle ne le ferme jamais. |

## 6. Questions ouvertes

- Paliers de **qualité de craft** (le champ `Recipe.quality` existe) : les exposer dans
  les commandes (qualité minimale exigée par le client) ?
- Durabilité/réparation : gold sink à introduire, ou s'appuyer uniquement sur les
  consommables perpétuels pour la demande de fond ?
- Narration — quels beats de saison méritent le statut « canon » (§3.2) : tous les climax,
  seulement les premières résolutions d'une menace, ou une curation manuelle par l'équipe ?
- Codex : entité dédiée (`CodexEntry` + déblocage joueur) ou réutilisation/extension du
  système de succès existant (les paliers de bestiaire jouent déjà ce rôle en partie) ?
- Contenu de fond procédural (§3.7) : jusqu'où pousser la génération (objectifs + récompenses
  seulement) avant que le manque d'ancrage écrit ne le rende générique ?
- Rejouabilité de l'acte d'intro sur un compte multi-personnages (§CLAUDE.md 12) : rejoué
  intégralement par personnage, ou raccourci/skippable dès le 2ᵉ personnage ?
