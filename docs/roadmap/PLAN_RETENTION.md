# Plan — Rétention hebdomadaire

> **Numérotation :** les jalons de **ce** document sont préfixés **RET-** (Rétention).
> Ils n'entrent **pas** en conflit avec **GCC- / ZON- / ECO- / NAR- / FOY-**.

> L'horizon hebdomadaire est le plus fragile de la colonne de progression
> ([../GAME_PROGRESSION.md](../GAME_PROGRESSION.md) §3 ter) : le quotidien est vivant et
> personnel (`PlayerDailyQuest`), la marée est couverte (arc saisonnier), mais **un joueur
> solo n'a rien à l'horizon de la semaine**, et le défi hebdomadaire de guilde ne tourne pas.
> Six briques actées le 2026-07-28 (trois solo, trois guilde), chacune passée au test des
> horizons : *ce qu'elle clôt, ce qu'elle laisse*. Aucune n'exige de présence simultanée.

## Vue d'ensemble

**7 jalons** (**RET-01** à **RET-07**), volontairement petits — la plupart s'appuient sur des
systèmes livrés (quotidiennes, commandes de craft, saisons) ou planifiés (foyers, pureté).

> **Avancement : 1/7.** RET-01 livré le 2026-07-28 (détail dans
> [../ROADMAP_DONE.md](../ROADMAP_DONE.md)). La rotation du lundi 00h00 existe désormais et
> constitue le **point d'entrée unique** que RET-02, RET-04, RET-05 et RET-06 doivent
> réutiliser — c'est le contrat transverse de RET-07.

| Code | Brique | Profil | Dépendances |
|------|--------|--------|-------------|
| RET-01 | Rotation du `WeeklyChallenge` + restitution | Guilde | ✅ **livré (2026-07-28)** |
| RET-02 | La Commission de la semaine | Solo | ∅ (crochet sédiment quand FOY-02 arrive) |
| RET-03 | La commande de guilde | Guilde | ECO Piste C ✅ |
| RET-04 | L'assiduité en paliers | Solo | `Player.lastActivityAt` (← FOY-17) |
| RET-05 | Le chantier de la semaine | Guilde | ← FOY-02, FOY-04 |
| RET-06 | L'Affleurement de la semaine | Solo | ← ECO-21, ECO-22 (pureté) |
| RET-07 | Tests du plan | — | ‖ au fil des jalons |

```
Vague 1 (indépendant)   : RET-01 → RET-02 → RET-03
Vague 2 (après FOY)     : RET-04, RET-05
Vague 3 (après pureté)  : RET-06
Transverse              : RET-07
```

**Pourquoi cet ordre.** Le critère de priorisation de la colonne
(GAME_PROGRESSION §4) : *toute fonctionnalité qui rapproche de la semaine 3 le moment où
« quelqu'un compte sur moi » vaut mieux que celle qui ajoute du contenu au mois 6*. RET-01
coûte une ligne de cron ; RET-02 et RET-03 créent le rendez-vous hebdomadaire personnel et le
« on compte sur moi » **sans attendre les foyers**. Le reste suit ses dépendances.

---

## Vague 1 — indépendante de tout chantier en cours

### RET-01 — Rotation du `WeeklyChallenge` & restitution ✅ (livré 2026-07-28)
> Livré. Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Ce que les briques suivantes héritent** : la commande `app:weekly-challenge:rotate`
> (`0 0 * * 1`) est le **point de rotation unique** du lundi. RET-02, RET-04, RET-05 et
> RET-06 s'y branchent — elles n'ajoutent pas leur propre cron. Le pool déclaratif
> `config/game/weekly_challenges.yaml` et son loader donnent le patron pour toute
> génération hebdomadaire ultérieure ; l'idempotence par semaine ISO (`Parameter`) est le
> mécanisme à réutiliser.

### RET-02 — La Commission de la semaine (M | ★★★ | CRITIQUE)
> Le rendez-vous hebdomadaire **personnel**. Générée depuis les domaines et zones du joueur,
> **livrée à un foyer** — le solo participe au chantier collectif sans guilde.
> Prérequis : ∅ (le dépôt de sédiment se branche quand FOY-02 existe)
- [ ] Entité `PlayerWeeklyCommission` : player, semaine, objectif (type + cible + quantité),
      zone de livraison, progression, état (`open` / `delivered` / `expired`), récompense choisie
- [ ] Génération à la rotation hebdomadaire : objectif tiré des **domaines travaillés** du
      joueur et des **tables de la zone** de livraison (mêmes gabarits déclaratifs que les
      quotidiennes — aucun contenu écrit à la main)
- [ ] **Livraison à un foyer** : l'objet se remet dans la zone cible, pas à un guichet
      abstrait. Crochet `SettlementSedimentListener` prêt (no-op tant que FOY-02 n'est pas
      livré, sédiment réel ensuite)
- [ ] **Récompense au choix parmi trois** (un plan, une matéria de palier, un lot de
      ressources à pureté garantie — ce dernier dégradé en lot simple tant qu'ECO-21 n'est
      pas livré). Un choix hebdomadaire est plus mémorable qu'un dû
- [ ] Anti-abus : une commission par semaine et par personnage, pas de reroll
- [ ] UI : carte sur le tableau de bord (hub livré) + écran de zone de la ville cible
- [ ] Tests : génération depuis les domaines, livraison, choix de récompense, expiration

### RET-03 — La commande de guilde (S | ★★ | HAUTE)
> « On compte sur moi » à cadence fixe, pour le prix d'un canal *guilde* sur un système livré.
> Prérequis : ECO Piste C ✅ (CraftOrder, escrow, réputation)
- [ ] `CraftOrder.guild` (nullable) + visibilité : une commande de guilde n'est prenable que
      par un membre
- [ ] Droit de poste : rôle officier ; financement possible par le `GuildVault` (escrow
      inchangé — on réutilise, on ne réécrit pas)
- [ ] Cadence : plafond d'une commande de guilde **active** par semaine (paramètre) — c'est
      un rendez-vous, pas un tableau infini
- [ ] Restitution sur l'écran de guilde : la commande de la semaine, qui l'a servie
- [ ] Tests : visibilité membre, escrow via trésor, plafond hebdomadaire

---

## Vague 2 — après le socle des foyers

### RET-04 — L'assiduité en paliers, jamais en série (S | ★★ | MOYENNE)
> On récompense la présence, on ne sanctionne jamais l'absence. Une série qui casse
> transforme un PBBG en corvée — c'est l'inverse du contrat du genre.
> Prérequis : ← FOY-17 (`Player.lastActivityAt`, mis à jour à la dépense d'énergie)
- [ ] Compteur de **jours actifs dans la semaine** (activité = énergie dépensée, jamais la
      simple connexion — même définition que la population effective, BALANCE § 22.5)
- [ ] Paliers 2 / 4 / 6 jours : récompenses croissantes, **remises à zéro chaque lundi sans
      mémoire des semaines ratées**. Interdit : toute mécanique de série continue inter-semaines
- [ ] Restitution discrète sur le tableau de bord (pas de compteur culpabilisant)
- [ ] Tests : comptage par jour, paliers, absence d'effet inter-semaines

### RET-05 — Le chantier de la semaine (M | ★★★ | HAUTE)
> La liste de besoins hebdomadaire d'un foyer, à la Restauration d'Ishgard. La marée dit *où
> va* la ville ; le chantier dit *ce qu'elle attend cette semaine*.
> Prérequis : ← FOY-02 (sédiment), FOY-04 (bloc foyer sur l'écran de zone)
- [ ] `SettlementWeeklyWork` : foyer, semaine, liste de besoins (générée depuis les tables de
      zone et le **type** du foyer — un Comptoir demande de la matière, un Bastion des kills)
- [ ] Contributions **nominatives**, jauge visible dans le bloc foyer (FOY-04)
- [ ] Liste remplie → bonus de sédiment au foyer + mention des contributeurs
- [ ] La Commission (RET-02) d'un joueur peut pointer sur le chantier de sa zone : les deux
      systèmes convergent au même guichet
- [ ] Tests : génération par type de foyer, contribution, bonus, rotation

---

## Vague 3 — après la pureté

### RET-06 — L'Affleurement de la semaine (M | ★★★ | MOYENNE)
> La rotation hebdomadaire du monde, à coût d'écriture nul (levier Ryzom). Le savoir du
> prospecteur redevient monnayable à cadence fixe.
> Prérequis : ← ECO-21, ECO-22 (bandes de pureté au filon)
- [ ] À la rotation hebdomadaire : tirage d'**un filon** du monde dont la bande maximale
      monte d'un cran pendant 7 jours (jamais deux semaines de suite la même zone)
- [ ] **Aucune annonce publique** : l'information se découvre par prospection sur place —
      ou s'achète à qui l'a trouvée. C'est le point de la brique ; l'afficher la tuerait
- [ ] Interaction Pâleur (quand FOY-11 existe) : un filon pâli ne peut pas être tiré
- [ ] Tests : tirage, fenêtre de 7 jours, non-répétition, discrétion (rien dans l'API publique)

### RET-07 — Tests du plan (S | ★★ | HAUTE)
> ‖ au fil des jalons.
- [ ] Couverture par brique + un contrat transverse : la rotation du lundi est **une** (un
      seul point d'entrée pour RET-01/02/04/05/06 — pas cinq crons qui dérivent)
- [ ] Invariant : aucune brique hebdomadaire ne pénalise une semaine d'absence

---

## Risques

| Risque | Parade |
|---|---|
| La Commission devient une corvée d'optimisation (reroll, farming de récompense) | Une par semaine, pas de reroll, récompenses d'ampleur comparable |
| L'Affleurement annoncé publiquement → ruée sans découverte | La discrétion est un critère d'acceptance testé, pas une option |
| Cinq mécaniques hebdomadaires = cinq horloges qui dérivent | Un seul point de rotation (lundi 00h00), contrat testé en RET-07 |
| La série d'assiduité réintroduite « parce que c'est standard » | Interdit explicite en RET-04 ; l'invariant est testé |
