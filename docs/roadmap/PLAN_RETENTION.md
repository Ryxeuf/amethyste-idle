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

> **Avancement : 7/7 — plan complet** (2026-07-28 ; détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md)).
> La bascule du lundi 00h00 est **une** : `WeekKey` en est le point de calcul unique, les quatre
> rotations tombent le même lundi à des minutes distinctes, et RET-04 n'a même pas de cron — sa
> remise à zéro est dérivée. Ce que le contrat de RET-07 verrouille désormais par test.
> Couverture : [RETENTION_TEST_COVERAGE.md](RETENTION_TEST_COVERAGE.md).

| Code | Brique | Profil | Dépendances |
|------|--------|--------|-------------|
| RET-01 | Rotation du `WeeklyChallenge` + restitution | Guilde | ✅ **livré (2026-07-28)** |
| RET-02 ✅ | La Commission de la semaine | Solo | ✅ **livré (2026-07-28)** |
| RET-03 ✅ | La commande de guilde | Guilde | ECO Piste C ✅ |
| RET-04 ✅ | L'assiduité en paliers | Solo | ✅ **livré (2026-07-28)** |
| RET-05 ✅ | Le chantier de la semaine | Guilde | ✅ **livré (2026-07-28)** |
| RET-06 ✅ | L'Affleurement de la semaine | Solo | ✅ **livré (2026-07-28)** |
| RET-07 ✅ | Tests du plan | — | ✅ **livré (2026-07-28)** |

```
Vague 1 (indépendant)   : RET-01 → RET-02 → RET-03
Vague 2 (après FOY)     : RET-04 ✅, RET-05 ✅
Vague 3 (après pureté)  : RET-06
Transverse              : RET-07 ✅
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

### RET-02 — La Commission de la semaine ✅ (M | ★★★ | CRITIQUE)
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

### RET-04 — L'assiduité en paliers, jamais en série ✅ (S | ★★ | MOYENNE)
> On récompense la présence, on ne sanctionne jamais l'absence.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **L'interdit est porté par la forme, pas par une règle.** Une ligne de
> `player_weekly_attendance` par personnage et par semaine ISO : il n'y a *rien* à remettre à
> zéro, et une série continue inter-semaines devient **inécrivable** sans changer le schéma.
> C'est ce qui la protège du jalon futur qui la réintroduirait « parce que c'est standard » —
> le risque que la table des risques de ce plan nomme explicitement. Le loader porte l'autre
> moitié : aucun palier ne peut exiger 7 jours, un palier à 7 faisant d'un jour manqué une perte.
>
> **Précision au plan** : « remises à zéro chaque lundi » se lisait comme une tâche de
> rotation. Il n'y en a pas — la bascule est **dérivée** de la semaine ISO, la même clef que
> RET-01 et RET-02. RET-04 n'ajoute aucun cron à une famille qui en compte déjà quatre, ce qui
> est la forme la plus forte du contrat transverse de RET-07 : pas « un seul point de
> rotation », mais **pas de rotation du tout**.

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

### RET-06 — L'Affleurement de la semaine ✅ (M | ★★★ | MOYENNE)
> La rotation hebdomadaire du monde, à coût d'écriture nul (levier Ryzom). Le savoir du
> prospecteur redevient monnayable à cadence fixe.
> **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **La discrétion est rendue exécutable** : un test parcourt contrôleurs, gabarits et assets
> et échoue si l'un d'eux nomme l'affleurement. La commande de rotation elle-même ne nomme pas
> le filon tiré — les journaux d'exploitation finissent par être lus et recopiés.
>
> **Report soldé** (FOY-11 ✅) : un filon pâli ne peut plus être tiré — la Pâleur existe et
> le filtre est branché : `WeeklyOutcropSelector` lit `paleness.dulls_purity_from` pour
> écarter les candidats pâlis, comme prévu, sans changer le reste.

### RET-07 — Tests du plan ✅ (S | ★★ | HAUTE)
> ‖ au fil des jalons. **Livré le 2026-07-28.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
> Synthèse de couverture : [RETENTION_TEST_COVERAGE.md](RETENTION_TEST_COVERAGE.md).
>
> **Le jalon a trouvé ce qu'il cherchait.** « Cinq horloges qui dérivent » n'était pas un risque
> théorique : la semaine ISO se calculait à **deux** endroits — `WeeklyChallengeRotator` (RET-01)
> recopiait la formule que les quatre autres briques partageaient. Les deux s'accordaient, ce qui
> est précisément ce qui rend ce genre de duplication dangereux : elle ne se signale que le jour
> où elle a déjà divergé. `WeekKey` est désormais le point unique, et un test refuse que le format
> réapparaisse ailleurs.
>
> **Le second invariant est lexical, pas comportemental.** « Aucune brique hebdomadaire ne
> pénalise une semaine d'absence » ne se teste pas brique par brique — un test de comportement ne
> voit que ce qu'il connaît, jamais la brique que quelqu'un écrira l'an prochain. Le contrat
> interdit donc le **vocabulaire** de la série continue dans les moteurs, commentaires exclus :
> ce plan doit pouvoir nommer par écrit ce qu'il refuse d'implémenter.

---

## Risques

| Risque | Parade |
|---|---|
| La Commission devient une corvée d'optimisation (reroll, farming de récompense) | Une par semaine, pas de reroll, récompenses d'ampleur comparable |
| L'Affleurement annoncé publiquement → ruée sans découverte | La discrétion est un critère d'acceptance testé, pas une option |
| Cinq mécaniques hebdomadaires = cinq horloges qui dérivent | Un seul point de rotation (lundi 00h00), contrat testé en RET-07 |
| La série d'assiduité réintroduite « parce que c'est standard » | Interdit explicite en RET-04 ; l'invariant est testé |
