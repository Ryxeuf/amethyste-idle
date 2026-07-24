# Principes de jeu — Amethyste-Idle

> **Statut : adopté** · Juillet 2026
> Source de vérité des **grands principes de game design** post-pivot PBBG.
> Complète (ne remplace pas) : [PIVOT_PBBG.md](PIVOT_PBBG.md) (décision technique du pivot),
> [roadmap/PLAN_GUILD_CITY_CONTROL.md](roadmap/PLAN_GUILD_CITY_CONTROL.md) (contrôle de cité, livré),
> [roadmap/PLAN_PLAYER_ECONOMY.md](roadmap/PLAN_PLAYER_ECONOMY.md) (déclinaison en jalons de l'économie joueur).

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

**Décision : trame de monde large + acte d'introduction fort + narration épisodique
saisonnière.** Pas de campagne linéaire massive écrite d'avance.

Justification : dans ce genre, la rétention vient des systèmes, pas d'une histoire
linéaire. Une campagne fournie est un coût d'écriture élevé pour une valeur consommée
une fois. La contre-référence narrative (Fallen London) est un jeu *narrative-first*
au coût d'écriture colossal — ce n'est pas le modèle visé.

Dosage retenu :

1. **Acte d'introduction** = tutoriel narratif qui pose l'univers, les enjeux et enseigne
   les systèmes (voyage, énergie, combat, craft, guilde). C'est l'onboarding et le crochet.
2. **Trame de monde** = lore des régions/factions, assez large pour donner du contexte et
   des accroches, sans tout scripter.
3. **Narration saisonnière** posée par-dessus les systèmes : le champ `theme` de
   `InfluenceSeason` est le véhicule (chaque saison porte un événement/une menace).
4. **Chaînes de quêtes répétables/procédurales par zone** pour le contenu de fond.

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

## 6. Questions ouvertes

- HV : segmentation régionale stricte vs marché global avec taxe régionale à la vente
  (choix d'ampleur — l'arbitrage géographique n'existe qu'avec segmentation).
- Paliers de **qualité de craft** (le champ `Recipe.quality` existe) : les exposer dans
  les commandes (qualité minimale exigée par le client) ?
- Emplacements d'échoppe : liés au housing du joueur, ou **actif de ville** attribué par
  la guilde contrôlante (renforce le contrôle de cité) ?
- Durabilité/réparation : gold sink à introduire, ou s'appuyer uniquement sur les
  consommables perpétuels pour la demande de fond ?
