# GAME_DASHBOARD — Le cadrage du tableau de bord, et le lundi en un écran

> **Source de vérité de l'écran hub** (`/game`), au même titre que GAME_ZONE_ACTIONS.md
> l'est pour l'écran de zone. Cadré le 2026-07-29, issu du verdict V1 du
> [playtest papier du mois 1](PLAYTEST_PAPIER_MOIS_1.md) : « le tableau de bord du lundi
> est le jalon UI le plus rentable, et il n'existe pas ». Décliné en jalons dans
> [roadmap/PLAN_RETENTION.md](roadmap/PLAN_RETENTION.md) (vague 2, RET-08→10).

## 0. Le problème, mesuré

Le lundi ne coûte presque pas d'énergie — 5 des 6 systèmes hebdomadaires comptent des
gestes que le joueur fait de toute façon. Il coûte de la **tête** : pour faire le tour de
sa semaine, il faut aujourd'hui visiter au moins **cinq écrans** (commission et chantier
du foyer sur l'écran de zone — et le chantier seulement si on est dans la bonne zone —,
défis et commande de guilde sur deux écrans de guilde, loyer et jardin sur l'écran de
maison, craft en cours sur l'écran d'artisanat). Le hub, lui, ne connaît de la semaine
que l'assiduité, en deux phrases de texte ; la commission n'y apparaît que comme un
signal binaire tardif (`commission_ready`), et il n'existe **aucune notion de semaine**
dans son gabarit — pas de « la semaine se referme », pas de récap de la semaine close.

## 1. Ce que l'écran répond

Le tableau de bord reste un **hub de reprise** — il ne devient pas un centre de
commande. La semaine y entre comme une **strate**, pas comme une page de plus. À chaque
visite, l'écran répond dans l'ordre à trois questions :

1. **Où j'en étais** — la reprise (état, jauges, l'action primaire). *Existant, conservé.*
2. **Où on m'attend** — les attentes actionnables, ordonnées par coût d'inaction.
   *Existant, conservé.*
3. **Où en est ma semaine** — le bloc neuf, unique, qui agrège les rendez-vous
   hebdomadaires. *À construire (RET-08).*

Et une fois par semaine, à la première visite après la rotation du lundi, une quatrième :
**qu'est-ce qui s'est fini** — le récap de la semaine close (RET-09).

## 2. L'existant, ce qu'on garde tel quel

Le hub livré (« reprise, attentes, récap ») a trois acquis qu'aucun jalon ne doit
défaire :

- **L'action primaire unique à 7 états** exclusifs (`dead` → `fight` → `travel` →
  `expedition`/`expedition_done` → `ready` → `lost`) — les états d'attente rendent une
  bannière et un bouton secondaire, jamais un primaire. La strate semaine ne s'y ajoute
  pas : le choix de commission (§5) n'est l'action primaire que si l'état est `ready`
  et que la commission n'est pas encore choisie.
- **La règle d'admission des attentes** : une ligne n'entre que si le joueur peut agir
  *maintenant* ; l'ordre est le coût d'inaction (le loyer d'abord, les messages en
  dernier).
- **Le récap 24 h** (journal agrégé) — distinct du récap de semaine (§4), qui ne le
  remplace pas : l'un dit « depuis hier », l'autre « la semaine close », une fois.

## 3. Le bloc « La semaine » (RET-08)

Un **seul** panneau, colonne principale, entre la reprise et les attentes. Jamais cinq
panneaux. Cinq lignes au maximum, chacune au format `ds-row` + jauge miniature, chacune
**cliquable vers l'écran où le geste se fait** — le hub lit, il ne fait pas (une seule
exception, §5).

| Ligne | Ce qu'elle dit | Ce qu'elle ne dit pas | Données (déjà exposées) |
|---|---|---|---|
| **Commission** | activité, progression (« 7/10 truites »), zone de livraison, récompense choisie ; « livrable » quand c'est complet | le détail du calcul | `WeeklyCommissionDelivery::current()`, `PlayerWeeklyCommission` |
| **Défis de guilde** | agrégat « 1 réussi / 3 », le plus proche de sa cible mis en avant | les 3 barres détaillées (écran guilde) | `GuildController::buildChallengeEntries()` → à extraire en service (dette, §7) |
| **Commande de guilde** | « ouverte » / « prise par X » / « livrée » | — | `GuildCraftOrderManager::activeThisWeek()` |
| **Chantier du foyer** | celui de la **zone courante**, nommé (« Le Bourg demande : 40 planches — 62 % ») ; si la zone n'en a pas : la ligne n'existe pas | les contributeurs (panneau de zone) | `SettlementPanelBuilder::weeklyWork()` |
| **Assiduité** | les paliers **visualisés** (points/jauge 2·4·6), le prochain avec ses deux récompenses (gils **et** énergie — calculée aujourd'hui et jamais affichée) | un compteur de série (il n'y en a pas) | `WeeklyAttendanceService`, `HubAttendance` |

Deux règles d'écriture, héritées du design system et du playtest :

- **Le tableau compte pour vous** : chaque ligne dit *ce qui reste* (« encore 2 jours
  pour 120 gils et 20 énergie », « 3 livraisons »), jamais une donnée brute que le
  joueur devrait convertir. Tout chiffre en monospace (`ds-num`).
- **Le repère de semaine est discret** : l'en-tête du bloc porte « Semaine du 27
  juillet » ; à partir du samedi, « se referme demain soir ». Jamais de compte à
  rebours en heures — la semaine n'est pas un timer.

Sans guilde, les lignes guilde n'existent pas (pas d'état vide qui culpabilise — la
ligne absente suffit). Sans commission ouverte hors lundi : idem.

## 4. Le lundi (RET-09)

À la **première visite après la rotation** (le `weekKey` du joueur diffère de celui de
la semaine courante), le bloc semaine s'ouvre en tête de colonne principale, précédé
d'un court **récap de la semaine close** :

- ce qui s'est **déposé** : palier d'assiduité atteint (et ce qu'il a payé), commission
  livrée (ou expirée — sur le ton du constat, jamais du reproche : « la commission est
  repartie sans vous, une autre s'ouvre »), défis réussis, contribution au chantier ;
- **une ligne de chronique** : le fait de monde le plus récent qui concerne la zone
  d'attache du joueur (la chronique des foyers existe et n'est aujourd'hui lisible que
  par le Codex — c'est sa première surface joueur) ;
- puis « **la semaine qui s'ouvre** » : le bloc semaine normal, avec le choix de
  commission en évidence (§5).

Ce déroulé n'apparaît **qu'une fois** : dès la visite suivante, le bloc redevient
compact et reprend sa place (§1). Pas d'écran dédié, pas de modale bloquante — un état
du même bloc.

## 5. L'action primaire du lundi : choisir sa commission

Le seul **choix** hebdomadaire demandé au joueur est la récompense de commission
(bourse / vigueur / tribut). Aujourd'hui il ne se fait que sur l'écran de zone ; il
remonte au hub : quand l'état de reprise est `ready` et qu'une commission de la semaine
attend son choix, **c'est elle l'action primaire** (« Choisir la commission de la
semaine »), et le choix s'opère depuis le hub (POST existant).

La **livraison**, elle, reste un geste de terrain : elle se fait en zone, le hub se
contente de dire « livrable — rejoindre les Vallons ». Le hub lit ; le monde agit.

## 6. Ce que l'écran ne montre jamais

- **L'Affleurement de la semaine** — réaffirmé (décision RET-06) : il n'est annoncé
  nulle part, c'est l'information des prospecteurs et elle se monnaye entre joueurs. Le
  tableau de bord ne le mentionne jamais, même « découvert ».
- **Les chiffres internes** : pas de grains de sédiment, pas de seuils de rang, pas de
  pourcentages de tirage. La ligne chantier dit ce que le foyer demande, pas ce qu'il
  calcule.
- **Un compte à rebours de semaine en heures** — le repère du §3 suffit.
- **Les rendez-vous des systèmes non livrés** (caravanes, marées de rotation…) : le
  bloc s'étendra quand ils existeront, ligne par ligne, même format.

## 7. Les dettes d'écran soldées au passage (RET-10)

Quatre reprises que la maquette (Tour 5) a déjà tranchées ou que le relevé a mises au
jour :

1. **L'XP disponible comptée deux fois** (ligne `talent_xp` des attentes + mention verte
   sous chaque barre de domaine) : la ligne d'attente **gagne** (elle est actionnable et
   disparaît une fois dépensée) ; le bloc domaines ne garde que ses jauges.
2. **L'état vide replié** (maquette 5D, compte neuf) : un bloc vide se replie sur une
   ligne qui se déplie au premier contenu — remplace les 4 `ds-empty` pleins actuels ;
   clés `hub.empty.*` à créer (prévues par la maquette, jamais posées).
3. **Le loyer daté** : la ligne `house_rent` porte l'échéance et le montant (les deux
   existent sur `PlayerHouse`), pas seulement un sceau rouge.
4. **L'enchantement rejoint les attentes** : il a un `remainingSeconds` et n'apparaît
   que sur l'écran de craft — même règle d'admission que le `CraftJob`.

Et une dette de code que RET-08 paie en chemin : les défis de guilde n'ont pas de
service de lecture (construits inline dans le contrôleur de guilde) — l'agrégat du hub
impose de les extraire, l'écran de guilde se rebranche dessus.

## 8. Déclinaison design system

Composants existants, aucun nouveau : `ds-panel` (le bloc), `ds-row` + `ds-meter`
(les lignes), `ds-seal --gain/--loss` (livrable / repartie), `ds-num` (tout chiffre),
`ds-log` (le récap du lundi), `ds-hint` (le repère de semaine), `ds-btn --primary`
(le choix de commission, uniquement dans le cas du §5). Maquettes de référence :
`design/ecrans.dc.html`, Tour 5 (`Hub 5A desktop`, `Hub 5B mobile`, `Dashboard 5D
compte neuf`) — la vue mobile empile dans le même ordre, sans réordonnancement le lundi.

## 9. Ce que ça impose au code (contrat des jalons)

- **Lecture seule** : le bloc semaine agrège des services existants ; aucun nouveau
  calcul, aucune écriture (hors le POST de choix de commission déjà existant).
- **Un digest, pas six requêtes dans le gabarit** : `PlayerHubDigest` s'étend (ou se
  double d'un `WeeklyDigest`) — le gabarit reçoit un objet, comme aujourd'hui.
- **Le lundi est un état, pas un écran** : détection par comparaison de `weekKey`
  (stockée sur le joueur à la visite), zéro cron, zéro table neuve.
- **Rien n'est retiré aux écrans de terrain** : zone, guilde, maison gardent leurs
  panneaux détaillés ; le hub renvoie vers eux.
