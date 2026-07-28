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

> **Avancement : 4/7.** RET-01, RET-02, RET-03 et RET-05 livrés le 2026-07-28 (détail dans
> [../ROADMAP_DONE.md](../ROADMAP_DONE.md)). La rotation du lundi 00h00 existe désormais et
> constitue le **point d'entrée unique** que RET-02, RET-04, RET-05 et RET-06 doivent
> réutiliser — c'est le contrat transverse de RET-07.

| Code | Brique | Profil | Dépendances |
|------|--------|--------|-------------|
| RET-01 | Rotation du `WeeklyChallenge` + restitution | Guilde | ✅ **livré (2026-07-28)** |
| RET-02 ✅ | La Commission de la semaine | Solo | ✅ **livré (2026-07-28)** |
| RET-03 ✅ | La commande de guilde | Guilde | ECO Piste C ✅ |
| RET-04 | L'assiduité en paliers | Solo | `Player.lastActivityAt` (← FOY-17) |
| RET-05 ✅ | Le chantier de la semaine | Guilde | ✅ **livré (2026-07-28)** |
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

### RET-02 — La Commission de la semaine 🟡 (M | ★★★ | CRITIQUE)
> Le rendez-vous hebdomadaire **personnel**. Générée depuis les domaines et zones du joueur,
> **livrée à un foyer** — le solo participe au chantier collectif sans guilde.
>
> **Socle livré le 2026-07-28** (RET-02a) : entité, pool déclaratif, tirage hebdomadaire
> déterministe, zone de livraison choisie parmi les foyers, anti-reroll. Détail dans
> [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
> **RET-02b livré le 2026-07-28** : avancement branché sur les six événements, livraison au
> foyer (dépôt hors plafond journalier — une commission est structurellement ingrindable),
> récompense au choix parmi trois dont le **Tribut** qui triple le dépôt en échange de la
> part du joueur, carte sur l'écran de zone et attente actionnable sur le tableau de bord.
> Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).

### RET-03 — La commande de guilde ✅ (S | ★★ | HAUTE)
> « On compte sur moi » à cadence fixe, pour le prix d'un canal *guilde* sur un système livré.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).

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

### RET-05 — Le chantier de la semaine ✅ (M | ★★★ | HAUTE)
> La liste de besoins hebdomadaire d'un foyer, à la Restauration d'Ishgard. La marée dit *où
> va* la ville ; le chantier dit *ce qu'elle attend cette semaine*.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Correction au plan** : les besoins sont générés depuis le **type** du foyer et son rang,
> pas depuis « les tables de zone ». Le type se déduit de l'indice dominant (FOY-03) — donc de
> la fréquentation passée —, ce qui rend la demande lisible et non arbitraire. Les tables de
> zone auraient lié le chantier à ce qu'on peut y récolter, alors que le sens du jalon est que
> la ville demande selon **ce qu'elle est devenue**.
>
> **Reporté** : la convergence Commission ↔ chantier (une Commission qui pointe sur le chantier
> de sa zone). Les deux systèmes existent et se remplissent des mêmes six événements ; les
> faire converger demande de décider si une même action compte deux fois, ce qui est une
> question d'équilibrage et non de plomberie.

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
