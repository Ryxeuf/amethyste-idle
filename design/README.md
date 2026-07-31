# Handoff : écrans Amethyste — ce que les maquettes demandent au code

Point de synchro du **29 juillet 2026**, repo `Ryxeuf/amethyste-idle`, branche `main`.

## Vue d'ensemble

Deux choses dans ce paquet, et il faut les traiter différemment :

1. **Deux écrans à construire** — le hub de la semaine (§ B), cadré par
   `docs/GAME_DASHBOARD.md` et ouvert en jalons RET-08 → RET-10 ; et la couche
   d'information de marché (§ C), qui rend l'Hôtel des ventes lisible et fait du savoir
   des prix une ressource qui s'apprend et se négocie. Rien n'en est codé.
2. **Une migration de palette** (§ A) — trois écrans livrés sont encore sur l'ancienne
   rampe sombre, en tout ou en partie. Purement mécanique, et à faire d'abord.
3. **Vingt reprises sur quatre écrans livrés** (§§ 1 à 5) — des écarts entre ce que le
   gabarit rend et ce que le code sait déjà faire. Chacune est petite ; plusieurs sont
   purement mécaniques (remplacement de classes, retrait d'un libellé mort).

Si le temps est compté, l'ordre est : § A (la palette, tout de suite — elle conditionne la
lecture du reste), § 1.1–1.2 (jauge morte et sorts verrouillés, deux suppressions), puis
les deux jalons § B et § C, dont § C attend une décision de fond (§ C.5).

## Objet

Six écrans du jeu ont été maquettés par tours de revue (Zone, Combat, Sac, Tableau de bord,
Compétences, Hôtel des ventes — ce dernier pas encore fait). Chaque tour a produit deux
choses : une maquette haute fidélité, et une **liste d'écarts** entre ce que le gabarit
rend aujourd'hui et ce que le code sait déjà faire. Ce paquet contient les maquettes et la
liste consolidée des reprises, à jour du relevé de ce jour.

## Nature des fichiers joints

Les `.dc.html` sont des **références de design**, pas du code à copier. Ils s'ouvrent
directement dans un navigateur, sans build. La cible réelle est la stack du repo :
**Twig 3.23 + Tailwind v4 (config CSS-native) + Stimulus + AssetMapper**, avec la couche
de composants `assets/styles/design-system.css` (classes `.ds-*`) déjà livrée. Aucun
composant nouveau n'est demandé : tout ce qui suit se fait avec `ds-panel`, `ds-card`,
`ds-row`, `ds-meter`, `ds-seal`, `ds-num`, `ds-log`, `ds-btn`, `ds-empty`, `ds-tabs`.

## Fidélité

**Haute fidélité.** Couleurs, typo, densités et états sont définitifs — ils viennent de la
direction « Parchemin » déjà traduite en code dans `assets/styles/design-system.css`. Deux
conventions de lecture valables dans tout le document de maquettes :

- **pointillé gris** = explication au survol (affordance, pas décoration) ;
- **pointillé rouge** = valeur inventée pour la maquette (PV courants, noms de monstre,
  entrées de journal, fourchettes de dégâts). Tout le reste est repris des fixtures, de la
  config ou du code.

---

## B. Chantier neuf 1 : le hub de la semaine (Tour 9)

C'est la **seule partie de ce paquet qui demande de construire un écran** plutöt que de
reprendre un existant. Les sections 1 à 5 sont des corrections ; celle-ci est un jalon.

Source de vérité : `docs/GAME_DASHBOARD.md` (écrit le 29/07, il cite ces maquettes) et
`docs/roadmap/PLAN_RETENTION.md` vague 2, **RET-08 / RET-09 / RET-10**. Rien n'en est
codé : `PlayerHubDigest` n'a aucune notion de semaine et `templates/game/index.html.twig`
aucune mention.

Maquettes : **9a** (desktop 1280, semaine en cours), **9b** (l'état du lundi), **9c**
(mobile 452), **9d** (lignes absentes, demandes au code, interdits).

### B.1 L'écran, et ce qu'il répond

Le hub **reste un hub de reprise** — il ne devient pas un centre de commande. La semaine y
entre comme une **strate**, pas comme une page. À chaque visite, dans cet ordre : *où j'en
étais* (la reprise, existante), *où en est ma semaine* (le bloc neuf), *où on m'attend*
(les attentes, existantes), *ce que j'ai fait* (le récap 24 h, existant). Une fois par
semaine, à la première visite après la rotation, une quatrième : *qu'est-ce qui s'est
fini*.

### B.2 Le bloc « La semaine » (RET-08) — maquette 9a

**Position** : colonne principale, **entre** la reprise et « Ce qui vous attend ». Un
**seul** panneau, jamais cinq. En mobile (9c), même position dans la colonne unique.

**Châssis** : `border-radius: 10px`, `border: 1px solid #b9a8d8` (le violet du châssis
distingue la strate des panneaux ordinaires, qui sont en `#cfc4ab`), fond `#fff`.
En-tête `padding: 12px 18px`, fond `#f6f1fb`, séparateur bas `1px solid #e0d7c1`.
Titre « La semaine » en Cormorant Garamond 22px/700 `#26221c`.
À droite de l'en-tête, le **repère de semaine** : « Semaine du **27 juillet** », 13px
`#8a806d` avec la date en `#5d564a`. À partir du samedi, « se referme demain soir ».
**Jamais** de compte à rebours en heures — une semaine n'est pas un timer.
Corps : `padding: 8px 10px`, lignes en colonne, `gap: 2px`. Pied de bloc
`padding: 9px 18px 11px`, séparateur `1px solid #f0ead9`, texte 12px `#8a806d`.

**Cinq lignes au maximum**, chacune `padding: 10px 12px`, `border-radius: 6px`, en trois
zones : corps flexible à gauche, colonne de chiffre `width: 92px; text-align: right`.
La ligne la plus urgente porte un fond `#f6f2e6` ; les autres n'ont pas de fond.
Chaque ligne est **cliquable vers l'écran où le geste se fait** — le hub lit, il ne fait
pas (unique exception : § 0.4).

| Ligne | Ce qu'elle dit | Données (déjà exposées) |
| --- | --- | --- |
| **Commission** | activité + domaine, ce qui reste (« encore 19 unités »), zone de livraison, récompense choisie ; « livrable » quand c'est complet | `PlayerWeeklyCommissionRepository::findCurrent()`, `getProgressPercent()`, `getDeliveryZone()`, `getReward()` |
| **Défis de guilde** | agrégat « 1 réussi / 3 » + le plus proche de sa cible mis en avant | `GuildController::buildChallengeEntries()` — **à extraire en service** |
| **Commande de guilde** | « ouverte » / « prise par X » / « livrée » | `GuildCraftOrderManager::activeThisWeek()` |
| **Chantier du foyer** | celui de la **zone courante**, nommé, avec le type du foyer ; si la zone n'en a pas, la ligne n'existe pas | `SettlementPanelBuilder::weeklyWork()` |
| **Assiduité** | les paliers **visualisés**, le prochain avec ses deux récompenses (gils **et** énergie) | `WeeklyAttendanceService`, `HubAttendance` |

**Règle d'écriture, tenue sur les cinq** : chaque ligne dit *ce qui reste* (« encore
1 jour pour 1 800 gils »), jamais une donnée brute que le joueur devrait convertir. Tout
chiffre en IBM Plex Mono.

**Le rail de paliers d'assiduité** (le seul composant graphique un peu spécifique) : une
rangée `display: flex; align-items: center; gap: 6px` alternant segments (`flex: 1;
height: 6px; border-radius: 99px`) et pastilles (`9px × 9px; border-radius: 50%`).
Segment franchi et pastille franchie en `#2f6b3a` ; segment courant en piste `#e0d7c1`
rempli à la proportion en `#5b2fb0` ; pastille du prochain palier en `#fff` cerclée
`2px solid #5b2fb0` ; segments et pastilles à venir en `#e0d7c1`. Sous le rail, trois
légendes mono 10px `#8a806d` en `justify-content: space-between` : `2 j · 800 g`,
`4 j · 1 800 g`, `6 j · 3 500 g + 60 én.`

**Valeurs réelles à câbler** (aucune n'est inventée) :

- Paliers d'assiduité, `config/game/weekly_attendance.yaml` : `{days: 2, gils: 800}`,
  `{days: 4, gils: 1800}`, `{days: 6, gils: 3500, energy: 60}`. **Les 60 énergie du
  dernier palier sont versées par le code et n'ont jamais été affichées** — c'est du temps
  de jeu rendu, la seule récompense qu'un joueur assidu ne peut pas s'acheter.
- Commission de démonstration : `filon-de-la-semaine`, activité `harvest`, domaine
  `mineur`, cible **60** (`weekly_commissions.yaml`).
- Récompenses : bourse **2 500 gils**, renfort **120 énergie**, tribut **×3** au foyer
  (`weekly_commissions.yaml` → `rewards`, `WeeklyCommissionReward`).
- Défis : `per_week: 3` ; « Veines ouvertes » cible 150 / +110 influence, « Forge
  ardente » 20, « Héros du peuple » 10 (`weekly_challenges.yaml`).
- Chantier : Forêt des murmures, rang **Hameau**, type **Comptoir** → demande `harvest`,
  cible `targets.harvest: 200` × `rank_multipliers.hamlet: 1` = **200**
  (`settlements.yaml` → `weekly_work`).

Inventé et marqué en pointillé rouge dans la maquette : les avancements (41/60, 138/150,
124/200, 3 jours), le nom « Maelis », la ligne de chronique.

### B.3 Le lundi (RET-09) — maquette 9b

À la **première visite après la rotation** (la clef de semaine ISO du joueur diffère de
celle de la semaine courante), le bloc s'ouvre **en tête de colonne principale**, précédé
d'un récap de la semaine close, puis suivi de « La semaine qui s'ouvre ».

**Récap de semaine close** : même châssis violet, titre Cormorant 24px « La semaine s'est
refermée », sous-titre 13px `#8a806d` « Semaine du 20 juillet · ce déroulé ne paraît
qu'une fois ». Cinq lignes en registre `ds-log` : une goutttière mono 12px de
`width: 88px` portant l'étiquette (`DÉPOSÉ` et `LIVRÉ` en `#2f6b3a`, `GUILDE`,
`CHANTIER`, `CHRONIQUE` en `#5d564a` / `#8a806d`), puis la phrase. La ligne de chronique
est en italique.

Contenu, dans cet ordre : palier d'assiduité atteint et ce qu'il a payé ; commission
livrée (ou expirée — **sur le ton du constat** : « la commission est repartie sans vous,
une autre s'ouvre », jamais un reproche) ; défis réussis et commande ; contribution au
chantier ; **une** ligne de chronique — le fait de monde le plus récent concernant la
zone d'attache. La chronique des foyers existe et n'est aujourd'hui lisible que par le
Codex : **c'est sa première surface joueur**, et elle n'en montre qu'une ligne.

**« La semaine qui s'ouvre »** : le bloc normal, avec la ligne Commission dépliée en carte
de choix (fond `#f6f1fb`, `border: 1px solid #ddd0ee`) portant les trois options en trois
cartes `flex: 1` égales — la bourse / le renfort / le tribut, chacune avec son chiffre en
mono. Les autres lignes sont à zéro (`0 / 3`, `0 / 200`, `0 jour`).

Dès la visite suivante, le déroulé disparaît et le bloc reprend sa place compacte (9a).
**Pas d'écran dédié, pas de modale bloquante** — un état du même bloc.

### B.4 L'action primaire du lundi

Le seul **choix** hebdomadaire demandé au joueur est la récompense de commission. Il ne se
fait aujourd'hui que sur l'écran de zone ; il remonte au hub : **quand l'état de reprise
est `ready` et qu'une commission attend son choix, c'est elle l'action primaire**
(« Choisir la commission de la semaine », bouton `#5b2fb0`, hauteur 52 px). C'est la seule
écriture que le hub s'autorise, et elle passe par le POST existant.

La **livraison** reste un geste de terrain : le hub dit « livrable — rejoindre les
Mines profondes », il ne livre pas.

Les 7 états de reprise existants ne changent pas : `dead` → `fight` → `travel` →
`expedition` / `expedition_done` → `ready` → `lost`, un seul primaire, les états
d'attente rendant une bannière et un bouton secondaire.

### B.5 Un arbitrage à porter en code

**Le bloc « Assiduité » (RET-04) quitte la colonne latérale et disparaît.** Ses deux
phrases de texte deviennent la cinquième ligne de la semaine, avec ses paliers dessinés.
Un rendez-vous hebdomadaire ne vit pas en panneau latéral, et le garder ferait deux
endroits où lire la même chose.

### B.6 Lignes qui disparaissent — maquette 9d

Une ligne absente vaut mieux qu'un état vide qui culpabilise. **Aucun** de ces cas ne
produit d'encadré :

- **sans guilde** : les deux lignes de guilde n'existent pas, le bloc en compte trois ;
- **zone sans foyer** (Lumière, Quartier des jardins, Cité ensevelie — listées dans
  `settlements.yaml` → `without_settlement`) : pas de ligne chantier ;
- **compte neuf** : sans commission ni assiduité, le bloc ne s'affiche pas du tout — même
  règle que les blocs repliés de la maquette 5d.

### B.7 Ce que l'écran ne montre jamais

- **L'Affleurement de la semaine** (décision RET-06) : jamais annoncé, même « découvert ».
  C'est l'information des prospecteurs et elle se monnaye entre joueurs.
- **Les chiffres internes** : pas de grains de sédiment, pas de seuils de rang, pas de
  pourcentages de tirage. La ligne chantier dit ce que le foyer demande, pas ce qu'il
  calcule.
- **Un compte à rebours de semaine en heures.**
- **Les rendez-vous des systèmes non livrés** (caravanes, marées de rotation) : le bloc
  s'étendra ligne par ligne quand ils existeront, même format.

### B.8 Ce que ça impose au code (contrat des jalons)

1. **Lecture seule** : le bloc agrège des services existants ; aucun calcul neuf, aucune
   écriture hors le POST de choix de commission.
2. **Un digest, pas six requêtes dans le gabarit** : `PlayerHubDigest` s'étend d'un
   `week()` (ou se double d'un `WeeklyDigest`) ; le gabarit reçoit un objet, comme
   aujourd'hui.
3. **Les défis de guilde n'ont pas de service de lecture** — construits en ligne dans
   `GuildController::buildChallengeEntries()`. L'agrégat du hub impose de les extraire ;
   l'écran de guilde se rebranche dessus. C'est la seule dette de code que RET-08 paie en
   chemin.
4. **Le lundi est un état, pas un écran** : comparaison de la clef de semaine ISO stockée
   sur le joueur à la visite. **Zéro cron, zéro table neuve** — cinq mécaniques
   hebdomadaires ne doivent pas vouloir dire cinq horloges qui dérivent (contrat RET-07).
5. **Rien n'est retiré aux écrans de terrain** : zone, guilde, maison gardent leurs
   panneaux détaillés ; le hub renvoie vers eux.
6. **Regarder ne compte pas comme jouer** : `WeeklyAttendanceService::currentDays()` lit
   sans rien créer, et c'est le seul endroit où la distinction pourrait se perdre. Le
   tableau de bord ne doit jamais inscrire une présence.

---

## A. La migration de palette — tous les écrans, d'abord

**C'est la reprise à faire avant les autres**, parce qu'elle conditionne la lecture de tout
le reste : trois écrans livrés sont encore, en tout ou en partie, sur l'ancienne rampe
sombre. Les maquettes de ce paquet sont **toutes** sur la direction Parchemin ; le code ne
l'est pas encore.

Pourquoi ce n'est pas cosmétique : `assets/styles/design-system.css` réindexe les rampes
héritées (`gray-*`, `purple-*`, `text-white`) vers le parchemin, donc un gabarit pas
encore repris **reste lisible** — mais il est faux. Un `bg-gray-800/50` réindexe rend une
surface qui ne correspond à aucun niveau du système : ni carte, ni panneau, ni ligne. Le
résultat est un écran qui change de peau au premier clic (le sac) ou un écran entier hors
système (l'hôtel des ventes).

### A.1 Table de correspondance

À appliquer mécaniquement. Aucune couleur nouvelle n'est à déclarer, aucune structure à
changer — ce sont des remplacements de classes.

| Ancien | Nouveau | Note |
| --- | --- | --- |
| `bg-gray-800/50` + `border-gray-700/30` (bloc) | `ds-panel` | le conteneur d'un onglet ou d'une section |
| `bg-gray-700/50` + `border-gray-600` (carte) | `ds-card` | un élément de liste ; `ds-row` s'il porte une action à droite |
| `bg-gray-800` / `bg-gray-900/60` (piste) | `ds-meter` | jamais un fond de carte |
| `text-white` (titre) | `ds-title` / `ds-name` | selon le niveau ; `ds-name--sm` en liste |
| `text-gray-300` | corps par défaut (`#5d564a`) | pas de classe : c'est la couleur du texte |
| `text-gray-400` / `text-gray-500` / `text-gray-600` | `ds-hint` (`#8a806d`) | les trois se replient sur un seul niveau |
| `bg-purple-600 hover:bg-purple-500` | `ds-btn ds-btn--primary` | **une seule** action primaire par écran |
| `bg-green-600` (Vendre, Valider) | `ds-btn ds-btn--secondary` | le vert n'est pas un registre d'action, c'est un registre de gain |
| `bg-gray-600` + `cursor-not-allowed` | `ds-btn ds-btn--disabled` | état, pas couleur |
| `bg-gray-700 hover:bg-gray-600` (pagination) | `ds-btn ds-btn--quiet ds-btn--sm` | |
| `text-yellow-400` (gils) | `ds-num` (+ `#e8c97c` **uniquement** sur fond sombre) | le doré n'existe que dans la nav |
| `bg-yellow-900/20 border-yellow-700/30` (solde) | `ds-card` + `ds-num` | le solde n'est pas une alerte |
| `bg-amber-900/40 text-amber-300` (enchère) | `ds-seal` neutre, libellé `ENCHÈRE` | encre `#8f6a15` sur `#f7ecd4` dans la maquette |
| `bg-pink-900/40 text-pink-300` (flash) | châssis à part, `1px dashed #b9a8d8` sur `#f6f1fb` | ce n'est pas une rareté, c'est un canal (§ 4.10) |
| `bg-purple-900/20 border-purple-700/30` (ristourne) | châssis de strate `#b9a8d8` / `#f6f1fb` | même châssis que le bloc « La semaine » |
| `bg-green-900/60` / `bg-yellow-900/40` / `bg-red-900/60` (bandeaux de combat) | `ds-banner` + `ds-seal--gain` / `--loss` | coop, danger, alerte |
| `bg-loss-tint` déjà correct | — | ne pas toucher |
| `stat-badge stat-badge-*` avec emoji | `ds-seal` + libellé texte | retirer ⚔ ✦ 🎯 💥 ❤ (§ 3.7) |
| `element.value` en slug brut | `game.inventory.element.*` | § 2.4 et § 1 (résistances de boss) |

Le **bon modèle à suivre** est déjà dans le code : `Purity::sealClass()` fait correspondre
une bande à un sceau du système, dans l'énum plutöt que dans trois gabarits. Toute
correspondance état → classe devrait vivre là.

### A.2 Où c'est, écran par écran

| Écran | État au 29/07 | À reprendre |
| --- | --- | --- |
| **Hôtel des ventes** | ❌ entièrement hors système | `auction/index.html.twig`, `sell.html.twig`, `my_listings.html.twig` — le plus gros morceau, et le plus visible |
| **Sac** | ⚠️ coque reprise, **cinq partiels** non | `inventory/{items,equipment,materia,materials,bank}/_list.html.twig` — purement mécanique |
| **Combat** | ⚠️ corps repris, trois zones non | bandeau coop, alerte de danger, barre de boss dans `fight/index.html.twig` |
| **Compétences** | ✅ aux classes `ds-*` | reste les emoji de `stat-badge` et de l'en-tête de domaine |
| **Hub** | ✅ | — |
| **Zone** | ✅ | — |

### A.3 Deux pièges

- **Ne pas remplacer une classe par une couleur en dur.** Si aucun `ds-*` ne correspond, la
  bonne réponse est presque toujours qu'on regardait un niveau de surface qui n'existe pas
  dans le système — pas qu'il faut un token neuf. Le seul ajout légitime rencontré est le
  **châssis de strate** (`#b9a8d8` / `#f6f1fb`), qui distingue une couche d'information
  d'un panneau ordinaire : bloc « La semaine », bandeau de ristourne, étal de
  l'intendance, cours verrouillé.
- **Une seule action primaire par écran.** La migration met à nu des écrans qui ont deux
  ou trois boutons pleins (l'hôtel des ventes en a trois : Vendre, Mes ventes, Filtrer).
  Les maquettes tranchent à chaque fois ; suivre la maquette plutöt que le gabarit.

---

## C. Chantier neuf 2 : le cours du marché (Tour 11)

L'Hôtel des ventes montre aujourd'hui des prix sans référence : un vendeur découvre la
valeur de son lot par échec, et les 5 % de frais le punissent d'avoir essayé. Ce jalon
ajoute l'information de marché — **beaucoup** d'information — mais la traite comme une
ressource : elle s'acquiert dans un arbre de compétences, et elle se négocie entre joueurs
sous forme de relevés datés.

Maquettes : **11a** (desktop 1280, panneau de cours à côté des annonces), **11b** (le même
panneau au rang 0, ce qu'on voit sans rien savoir), **11c** (mobile 452), **11d** (la
branche Négoce, le relevé comme objet, les sept demandes au code).

### C.1 Ce que le panneau montre

Un panneau **par objet et par marché**, ouvert depuis une annonce (desktop : colonne de
452 px à droite de la liste ; mobile : feuille sous l'annonce). Cinq blocs :

| Bloc | Contenu | Palier requis |
| --- | --- | --- |
| **Sur l'étal** | lots et unités en vente ici, prix demandés | palier 1 — acquis d'office, c'est le marché lui-même, personne ne peut le cacher |
| **Cours du jour + dernier vendu** | deux cartouches : médiane du jour à l'unité et variation 7 j ; dernier prix conclu et son âge (« il y a 40 min ») | palier 2 |
| **Écart au cours sur chaque annonce** | « 12 % sous le cours » en `#2f6b3a`, « 31 % au-dessus » en `#8f2a15`, « au cours » en gris | palier 2 |
| **Quatorze derniers jours** | histogramme de 14 barres (médiane par jour), pied portant bas et haut de fourchette ; plus volume vendu sur 7 jours | palier 3 |
| **Les autres marchés** | le cours du même objet ailleurs, une ligne par marché | palier 4 |

(Les numéros de palier servent à la conversation entre nous, et ils sont alignés sur les
gouttières `PALIER 1` → `PALIER 4` de la maquette 11d — ils ne s'affichent **jamais** dans
le jeu, cf. § C.2.)

**L'histogramme** : `height: 74px` (52 px en mobile), `display: flex; align-items:
flex-end; gap: 4px`, une barre par jour en `flex: 1`, `border-radius: 2px 2px 0 0`. La
teinte porte l'ancienneté : `#d9cdb4` pour les jours lointains, `#c3b394` à mi-parcours,
puis `#8f6ad0` → `#7c4ddb` → `#6b3ac9` → `#5b2fb0` sur les quatre derniers jours. Aucun
axe, aucune grille, aucune infobulle : ce n'est pas un graphique, c'est une silhouette.

### C.2 Le verrouillage se voit, mais ne s'explique pas

C'est le point de design central. Un joueur sans la compétence ne voit ni des cases vides,
ni le **catalogue de ce qui lui manque** — une table de paliers verrouillés se lit comme un
reproche. Il lit **une seule phrase** (maquette 11b) : ce registre en dit bien davantage à
qui sait le lire, et ceux qui ont l'oreille du commerce y voient un cours là où les autres
voient une liste de prix.

**Deux blocs distincts, jamais fondus en un.** Le premier **constate** qu'un savoir existe
(la phrase ci-dessus, sans bouton). Le second est une **offre du marché**, sur son propre
châssis : « Certains écrivent ce qu'ils lisent et le vendent — deux relevés du fer sont en
vente ici, le plus récent daté d'hier », puis un bouton **« Acheter un relevé · à partir de
400 g »**. C'est l'achat du travail d'un autre joueur, avec son prix et sa date — pas une
génération, pas un abonnement, et surtout pas un appel à l'action accolé à la phrase qui
constate le manque.

**Aucun bouton ne produit de relevé depuis ce panneau**, y compris pour un joueur qui en est
capable : voir § C.4. **Pas de lien vers l'arbre de compétences, pas de bouton
« apprendre »** non plus : où se forme un négociant est au joueur de le découvrir, comme le
reste du monde. L'écran dit qu'un savoir existe ; il n'enseigne pas le chemin.

Pour un joueur qui a déjà une partie du savoir (maquette 11a), la ligne qu'il n'a pas encore
reste **floutée** (`filter: blur(3.5px)`, `user-select: none`), sans sceau et sans mention
d'un palier suivant : **s'il est dans l'arbre, il sait déjà ce que la marche suivante
ouvre** — le lui répéter ici serait du remplissage. Le bloc garde son châssis de strate
(`1px dashed #b9a8d8` sur `#f6f1fb`) et une seule sortie, la même qu'ailleurs : le relevé
d'un autre joueur, daté, à son prix (« Acheter ce relevé · 400 g »).

**Aucun numéro de palier à l'écran**, nulle part, en-têtes compris : le savoir se décrit par
ce qu'il permet de lire (« vous savez lire un cours »), jamais par un échelon.

Un flou muet reste tenable ici parce que le marché, lui, offre toujours une porte : un flou
qui ne mène à rien serait un dark pattern.

### C.3 La branche Négoce — ⚠️ l'arbre reste à définir

**Aucun arbre de Négoce n'existe à ce jour**, ni en fixtures ni en doc. Ce qui suit est une
proposition de **forme**, pas une arborescence arrêtée : quatre paliers de valeur, dans
l'ordre où l'écran doit pouvoir les ouvrir.

1. **Palier 1 — l'étal** : lots et unités en vente ici, prix demandés. Acquis d'office :
   c'est le marché lui-même, personne ne peut le cacher.
2. **Palier 2 — le dernier prix conclu** sur ce marché, et l'écart au cours porté sur
   chaque annonce. Le palier qui change tout : il transforme trois lignes semblables en une
   bonne affaire et deux pièges.
3. **Palier 3 — l'histoire** : médiane par jour sur quatorze jours, fourchette pratiquée,
   volume vendu sur sept jours.
4. **Palier 4 — les autres marchés** : le cours du même objet ailleurs. L'information la
   plus chère du jeu, parce qu'elle fait du voyage un métier ; c'est aussi celle qui se
   revend le mieux.

Ce que la proposition **ne tranche pas** : le nombre de nœuds, le coût en XP, les fusions,
et le domaine d'accueil définitif — le **Vagabond** est le candidat le plus naturel (il
existe déjà, et sa commission hebdomadaire est « les affaires courantes »), mais rien
n'oblige à s'y tenir. L'arbre est un chantier à mener comme celui du Mineur l'a été
(maquette 8a) : un fichier de fixtures, des rangs, des prérequis nommés, des motifs de
refus lisibles. **Tant qu'il n'existe pas, l'écran se contente de dire qu'un savoir
existe** — et cet état est tenable en production : c'est exactement la maquette 11b.

### C.4 Le relevé de cours : un objet, pas un bouton

Un joueur des deux derniers paliers peut **établir un relevé** : un objet échangeable, daté, portant
l'état d'un marché à l'instant où il a été écrit. Il se vend à l'Hôtel des ventes comme le
reste. Quatre règles le tiennent :

- **Il vieillit** — il porte sa date et perd sa valeur avec elle. Sans péremption,
  l'information devient un stock et le marché du savoir meurt.
- **Il coûte de l'énergie à produire** — sinon un joueur en fait cent par jour.
- **Il se consomme à la lecture** et s'inscrit dans le panneau du lecteur, avec sa date :
  on achète une photographie, pas un abonnement.
- **Il se paie en gils** — jamais en améthystes. Une information de marché vendue à la
  boutique serait du pay-to-win, et `docs/MONETIZATION.md` l'interdit.

**Où vivent les deux gestes** — c'est une séparation à tenir dans l'implémentation :

| Geste | Qui | Où | Pourquoi là |
| --- | --- | --- | --- |
| **Écrire** un relevé | les deux derniers paliers | le parcours de **mise en vente** (maquette 10c), comme sortir un objet de son sac | c'est un geste de vendeur, qui coûte de l'énergie et produit un objet ; le panneau de cours ne le propose **jamais**, même à qui en est capable |
| **Acheter** un relevé | tout le monde | le **panneau de cours** (11a et 11b), et la liste de l'Hôtel des ventes comme n'importe quel objet | c'est une offre du marché qui répond à un manque précis, à cet endroit précis |
| **Lire** un relevé | son porteur | l'inventaire | il se consomme et s'inscrit dans le panneau, avec sa date |

### C.5 Ce que ça demande au code — dont **une décision à valider d'abord**

1. **Les données existent déjà.** `AuctionTransaction` conserve prix total, taxe, ristourne
   et date d'achat de chaque vente conclue ; les annonces actives sont déjà comptées par
   marché. Le cours est une **agrégation en lecture**, pas une donnée neuve.
2. **Une médiane, pas une moyenne, et par unité, pas par lot.** Un lot de 60 et un lot de
   12 ne pèsent pas pareil ; une vente aberrante ne doit pas déplacer le cours. Prévoir un
   **plancher de fiabilité** : sous trois ventes sur la période, afficher « trop peu de
   ventes pour dire un cours » — jamais un chiffre inventé.
3. **Par bande de pureté** quand l'objet en porte une (la ligne du cristal). Un cours
   toutes bandes confondues y serait un mensonge, et le filtre de bande existe déjà
   (ECO-23).
4. **Un cache par objet et par marché**, invalidé à la vente : ces agrégats se lisent bien
   plus souvent qu'ils ne changent. Le motif existe (`CellSearchEngine` : Doctrine +
   `cache.app`).
5. **⚠️ Deux décisions à prendre avant d'écrire.** *(a)* **L'arbre de Négoce n'existe pas**
   — il doit être défini (nœuds, rangs, coûts, domaine d'accueil) comme celui du Mineur ;
   § C.3 n'en donne que la forme. *(b)* Les compétences sont aujourd'hui passives — cinq
   statistiques, plus `actions.materia.unlock`. Négoce demande le même mécanisme appliqué à
   l'information : `actions.market.*`. Soit on étend la liste blanche des déblocages (et
   `CLAUDE.md` § 10 doit le dire), soit Négoce n'est pas une compétence et le déblocage
   passe par autre chose. **Le panneau, lui, est livrable avant ces décisions** : dans son
   état sans savoir (11b), il tient tout seul.
6. **Le relevé est un objet échangeable** avec des données embarquées (marché, objet,
   bande, cours, date) — le premier objet du jeu dont le contenu compte autant que le nom.
   Il doit être `isExchangeable()` pour passer par l'Hôtel des ventes.
7. **Deux garde-fous à chiffrer dans `BALANCE.md`** : le coût en énergie d'un relevé et sa
   durée de pertinence. Trop longue, l'information ne se renégocie jamais ; trop courte,
   personne n'en achète.

### C.6 Ce que cette couche ne doit jamais devenir

- **Un tableur.** Même prudence que la pureté, qui a choisi quatre bandes plutöt qu'une
  note continue : une médiane par jour, une fourchette, un volume. Pas de courbe zoomable,
  pas d'export, pas d'indicateur dérivé.
- **Un oracle.** Aucune prédiction, aucune « bonne affaire » signalée par le jeu au-delà
  de l'écart au cours, qui est un fait et non un conseil.
- **Un achat de confort.** L'information s'apprend ou s'échange entre joueurs.

---

## 1. Combat — 7 reprises, aucune faite

Gabarits : `templates/game/fight/index.html.twig`, `partials/_timeline.html.twig`.
Contrôleurs : `src/Controller/Game/Fight/FightIndexController.php`,
`FightTimelineController.php`. Maquettes : `ecrans.dc.html` **6a** (desktop 1280),
**6b** (mobile 452, onglets Actions/Journal), **6c** (états + écarts).

1. **Retirer la jauge d'énergie du combat.** Aucun sort de `fixtures/game/spell/` ne
   déclare de `energyCost` : la valeur reste à 0, la jauge ne bouge jamais et
   « Énergie insuffisante » est inatteignable. `docs/PIVOT_PBBG.md` le dit — le combat est
   gratuit une fois engagé, l'énergie gate l'entrée en combat, pas les tours.
   Aujourd'hui : un `ds-meter__fill--mp` par combattant + le libellé mort.
2. **Filtrer les entrées `locked`** de `getEquippedMateriaSpells()` avant rendu. Un cadre
   non cliquable n'a rien à faire dans le panneau d'actions ; la raison du verrou se lit
   sur l'écran de matéria. Aujourd'hui : rendu en `ds-action--locked`.
3. **Plafonner le panneau à cinq sorts** sous la main, **quatre sur mobile**, plus une
   tuile « N autres sorts » qui déplie le reste en liste. Quatre places offensives — bonus
   élémentaire d'abord, puis dégâts décroissants — la cinquième réservée au meilleur soin,
   en fin de rangée. **Tri figé au premier tour du combat** (sinon les boutons dansent
   d'un tour à l'autre). Le resolver renvoie déjà `elementMatch`, `linkedBonus`, `locked` :
   c'est un tri et une coupe à l'affichage, pas un calcul neuf. Douze sorts serties = six
   rangées de boutons aujourd'hui.
4. **Ne plus répéter trois rounds identiques** : `getTimeline($fight, 3)` aux deux appels.
   Rendre le round courant, le suivant estompé, et l'écart de vitesse en clair.
5. **Ajouter une ligne « Dernier tour » permanente** sous les combattants. Sur mobile les
   dégâts subis sont derrière l'onglet Journal : le joueur ne voit pas ce qui l'a frappé.
6. **Remplacer les deux `alert()`** (« Veuillez sélectionner une cible », erreurs
   d'action) par cette même ligne, en registre d'alerte.
7. **Exposer une estimation de dégâts hors résolution**, à côté de `DamageCalculator`,
   alimentée par le domaine du joueur et le monstre visé, recalculée au changement de
   cible. Les boutons affichent aujourd'hui la valeur brute de fixture
   (`{{ spell.damage }} DMG`), qui ignore la résistance et ment vers le bas.

Relevés en plus le 29/07, sur le même écran :

- Libellés de coût en abrégé anglais (`EN`, `DMG`, `HEAL`) — passer aux libellés traduits.
- Emoji 💥 en préfixe du nombre flottant de critique — l'effet visuel suffit.
- Les résistances de boss impriment le **slug d'élément** (`element|capitalize`) alors que
  `game.inventory.element.*` et `Element::label()` existent. Même écart que le sac.
- Le bandeau coop, l'alerte de danger et la barre de boss sont restés sur l'ancienne
  palette (`bg-green-900/60`, `text-yellow-200`, `text-white`) : trois zones hors
  direction Parchemin sur l'écran le plus vu du jeu.

## 2. Sac (inventaire) — 5 écarts tranchés, 1 à moitié fait

Gabarits : `templates/game/inventory/index.html.twig` + les cinq partiels d'onglet
(`items`, `equipment`, `materia`, `materials`, `bank`). Code : `InventoryPayloadBuilder`,
`Item::GEAR_LOCATIONS` (12 emplacements), `Item::TOOL_TYPE_LABELS` (8 types d'outil).
Maquettes : **7a** (desktop, onglet Équipement), **7b** (mobile, onglet Objets),
**7c** (écarts + états par onglet), **7d** (mobile, onglet Équipement).

1. **La jauge devient l'encombrement.** `getOccupiedSpace()` renvoie `count($items)` et
   ignore le champ `space` (1 à 5) : la jauge ne mesure pas ce qu'elle annonce. Sommer les
   `space`, et porter le poids sur chaque ligne. Le sac de démonstration passe de 47/100
   à 68/100.
2. **`ammo` devient une treizième case « Carquois »**, sous la main gauche, présente dans
   le paperdoll desktop (7a) et mobile (7d). Elle est vide et annotée « 2 fabricables »
   (les fixtures établissent deux carquois fabricables, pas leur présence dans le sac).
   `gear_location: 'ammo'` n'est pas dans `GEAR_LOCATIONS` aujourd'hui, donc jamais
   portable ni affichée. Les quatre constantes doublons `legs`/`feet`/`finger`/`main_hand`
   disparaissent au profit du vocabulaire déjà rendu. Le paperdoll est donc à six
   emplacements à gauche et **sept** à droite ; l'état vide Équipement compte treize cases.
3. **`gold` ne sort plus du contrôleur.** Deux bourses sont passées au gabarit (`gold` du
   sac + `gils` du joueur) et seuls les gils sont affichés. `docs/MONETIZATION.md` ne
   connaît que les gils (gameplay) et les améthystes (cosmétique) : l'or du sac est versé
   en gils à la migration, puis le champ part.
4. **Libellés français d'élément partout** (« feu », « lumière »), jamais le slug.
   *Partiellement fait* : `_materia_track.html.twig` et `index.html.twig` passent par
   `game.inventory.element.*`, mais `_slot_select_panel.html.twig` et `_modify.html.twig`
   restent sur `element.label`.
5. **Les cinq partiels passent aux jetons de l'en-tête.** `index.html.twig` est en
   classes `ds-*` mais les cinq partiels sont toujours en `bg-gray-800/50` : l'écran
   change de peau au premier clic. Remplacement de classes, aucun changement de structure.
   *Non fait au 29/07 — c'est la reprise la plus rentable du lot, elle est mécanique.*

Note de cohérence utile au chiffrage : onze pièces portées en 7a/7d, dont dix sourcées,
totalisant douze emplacements de matéria — exactement la saturation traitée en 6d côté
combat (point 3 ci-dessus).

## 3. Compétences — 7 écarts, 1 fermé

Gabarit : `templates/game/skills/index.html.twig`, `assets/controllers/skills_controller.js`.
Code : `PlayerSkillHelper` (six motifs de refus, plafond 500), `SkillRespecManager`
(50 × compétences × 1,25^respec), `BuildPresetManager` (3 presets max),
`BuildDomainResolver` (DOM-02). Arbre du Mineur : `fixtures/game/skill/miner.yaml`
(18 nœuds, 5 rangs, 0 à 150 pts, deux fusions). Maquettes : **8a** (desktop 1280, domaine
Mineur), **8b** (mobile 452, par rangs), **8c** (écarts + états).

1. **Rendre l'arbre comme un arbre.** Aujourd'hui : deux listes plates (Disponibles /
   Acquises) — rangs, fusions et chemins invisibles. Maquette : rangs en colonnes,
   prérequis nommés sur le nœud.
2. **Rendre les nœuds refusés.** `PlayerSkillHelper` distingue six motifs (dont `dormant`
   et `other_branch`, longuement documentés) et le gabarit n'en rend aucun : le motif
   n'arrive qu'en flash après POST. Cinq états de nœud lisibles à l'écran : acquis,
   apprenable, prérequis manquant, dormant, autre branche.
3. **Annoncer le coût avant le clic.** Le plafond global refuse au clic sans que la barre
   128/500 dise ce que coûte le nœud visé. Maquette : repère doré « où vous serez après »
   sur la barre, et reste annoncé par nœud (« il vous restera 15 XP »).
4. **Annoncer la pente du respec.** 50 × compétences × 1,25^respec n'est jamais dit.
5. **Charger un preset est une redistribution payante**, mentionnée seulement dans un
   `confirm()` JS. Le coût doit être à l'écran avant l'action.
6. **DOM-02 lisible au toucher.** *Fait au 29/07* : une phrase visible (`ds-card` +
   `activationHint`) sous l'en-tête quand l'arbre n'est pas exprimé. Reste à retirer le
   `title=""` du sceau Exprimé / Non exprimé, devenu redondant.
7. **Retirer les emoji** ⚔ ✦ 🎯 💥 ❤ de `stat-badge` **et** de l'en-tête de domaine.

Deux points de code à trancher, pas de design :

- `hasEnoughDomainXp()` compare à `getTotalExperience()` (XP **totale**, pas disponible) :
  un preset accepté peut échouer après facturation.
- `Domain::getSlug()` dérive du titre — un renommage casse les références.

## 4. Tableau de bord (hub) — cadré par la doc, non implémenté

Gabarit : `templates/game/index.html.twig`. Code : `IndexController`, `PlayerHubDigest`
(`HubResume` / `HubPendingItem` / `HubRecap` / `HubAttendance`). Maquettes : **5a**
(desktop), **5b** (mobile), **5c** (six états de reprise + écarts), **5d** (compte neuf).

`docs/GAME_DASHBOARD.md`, écrit le 29/07, est désormais la source de vérité de cet écran
et cite ces maquettes. Il ouvre trois jalons (RET-08 bloc « La semaine », RET-09 état du
lundi, RET-10 dettes d'écran) dont **rien n'est codé** : `PlayerHubDigest` n'a aucune
notion de semaine et le gabarit aucune mention. Les dettes RET-10, dont trois viennent de
ces maquettes :

1. **XP disponible comptée deux fois** — la ligne d'attente `talent_xp` gagne (elle est
   actionnable et disparaît une fois dépensée) ; le bloc domaines ne garde que ses jauges.
2. **État vide replié** (maquette 5d) : un bloc vide se replie sur une ligne — son titre
   plus une phrase adressée au joueur, jamais un reproche, jamais un second appel à
   l'action. Un bloc ouvert ne se replie plus. Quatre `ds-empty` pleins aujourd'hui.
   Clés `hub.empty.*` à créer dans `translations/messages.fr.json`, une par bloc — elles
   sont prévues depuis le Tour 5 et n'ont jamais été posées.
3. **Loyer daté** : la ligne `house_rent` porte l'échéance et le montant (les deux
   existent sur `PlayerHouse`), pas seulement un sceau rouge.
4. **L'enchantement rejoint les attentes** : il a un `remainingSeconds` et n'apparaît que
   sur l'écran de craft — même règle d'admission que le `CraftJob`.

Deux écarts du Tour 5 restants, hors RET-10 : les trois jauges PV / énergie / gils de
l'en-tête répètent la navigation épinglée (la maquette les remplace par l'énergie traduite
en actions) ; et le nom du personnage est en `ds-title` alors qu'il ne dit rien
d'actionnable.

Écart de version à noter : le hub implémenté a gagné un sixième bloc « Assiduité »
(RET-04) que les maquettes 5a/5b ne montrent pas encore.

## 5. Zone — recommandation d'ordre, à trancher

Gabarit : `templates/game/zone/index.html.twig`. Maquettes : **4a** (desktop), **4b**
(mobile + onglet Monde), **4c** (états exclusifs + écarts).

Le gabarit empile expédition / événements / boss / donjons **avant** l'en-tête de zone et
pousse « Explorer » sous la ligne de flottaison. Proposition : une bande unique
« Se passe ici, maintenant » sous l'en-tête, **après** l'action primaire.

## Règles à ne pas enfreindre en implémentant

Vérifiées sur le repo, elles ont déjà coûté trois corrections de maquette :

- `CLAUDE.md` §6 : **pas de niveau global** — la progression passe par les arbres. Les
  niveaux de **monstre** restent légitimes (`MateriaXpGranter` = 10 × niveau du monstre).
- `CLAUDE.md` §10 + `AGENTS.md` : les sorts actifs viennent de la **matéria** (possédée +
  compétence `actions.materia.unlock` + sertie) ; l'attaque d'arme est toujours gratuite ;
  les compétences sont **toujours passives** (`damage`, `heal`, `hit`, `critical`, `life`).
- `docs/MONETIZATION.md` : gils = gameplay, améthystes = cosmétique et confort, aucune
  conversion, **aucun dark pattern** (pas de compte à rebours, pas de remise barrée).
- Éléments : `none, fire, water, earth, air, light, dark, metal, beast`. Bonus élémentaire
  slot/matéria : +25 % dégâts et +25 % XP ; synergie liée +15 %.
- Coûts d'énergie arbitrés dans le code : explorer 5 (`ExploreService::DEFAULT_COST`),
  chasser 5, récolter 3, événement 10, assaut de boss 10. Régén énergie 360 s ; régén PV
  12 s par point. Expéditions : paliers 1 h / 4 h / 12 h, sans coût d'énergie, état
  exclusif.

## Tokens et composants

Rien à recréer : `assets/styles/design-system.css` porte le `@theme` et la couche `.ds-*`,
importé par `assets/styles/app.css`. Les rampes héritées (`gray-*`, `purple-*`,
`text-white`) sont réindexées vers le parchemin, pour que les gabarits pas encore repris
basculent sans être touchés — c'est pourquoi les partiels en `bg-gray-800/50` ne sont pas
*illisibles*, seulement hors système. Le document `design-system.dc.html` joint montre
tokens, typo, composants et raretés ; `design-system-repo-paths.dc.html` est le même avec
les chemins d'icônes pointant sur les planches du repo.

## Tokens de design

Les maquettes sont écrites avec ces valeurs littérales ; elles correspondent aux tokens
déjà posés dans `assets/styles/design-system.css`. En cas de doute, le fichier CSS du repo
fait foi — cette table sert à lire les maquettes.

**Couleurs**

| Rôle | Hex | Usage |
| --- | --- | --- |
| Fond de bureau | `#f0ead9` | fond du document de maquettes, hors écran |
| Papier | `#fbf8f0` | fond d'écran, en-têtes de panneau |
| Papier 2 | `#f4efe2` | panneau de reprise, surfaces surlignées |
| Carte | `#ffffff` | fond des panneaux de contenu |
| Ligne | `#e0d7c1` | séparateurs, piste de jauge |
| Ligne faible | `#f0ead9` | séparateurs internes de liste |
| Bordure | `#cfc4ab` / `#ddd2ba` | bordure de panneau / d'écran |
| Bordure de strate | `#b9a8d8` + fond `#f6f1fb` | le bloc « La semaine » uniquement |
| Encre | `#26221c` | titres, valeurs |
| Encre 2 | `#5d564a` | corps de texte |
| Encre 3 | `#8a806d` | méta, explications, légendes |
| Marque | `#5b2fb0` | action primaire, remplissage de jauge |
| Marque claire | `#7c4ddb` | nav active, jauge sur fond sombre |
| Marque foncée | `#4a1f95` | liens secondaires |
| Gain | `#2f6b3a` sur `#dcecdc` | sceaux GAIN, paliers franchis |
| Perte | `#8f2a15` sur `#f7dcd4` | sceaux PERTE, ligne de loyer `#fdf3f0` |
| Or | `#e8c97c` | gils dans la nav sombre |
| Améthyste | `#c4a2f5` | monnaie cosmétique, nav sombre |
| Neutre de sceau | `#5d564a` sur `#ece5d5` | EN ATTENTE, jetons |
| Nav sombre | `#26221c`, séparateur `#3a342b`, texte `#c9bfa8`, méta `#8a7f68` | colonne de navigation |
| Pointillé gris | `1px dashed #a8a08c` | « explication au survol » |
| Pointillé rouge | `1px dotted #a8341f` | « valeur inventée pour la maquette » |

**Typographie** — trois familles, chargées par Google Fonts :

- `'Cormorant Garamond', serif` (400/600/700) — titres de panneau et d'écran :
  50px/700 (titre de tour), 34px/700 (nom en reprise), 24–26px/700 (titre de bloc),
  20–22px/700 (bloc latéral), 18–19px/700 (mobile).
- `'Karla', system-ui, sans-serif` (400/500/600/700) — **tout le corps** :
  16px (paragraphe de tour), 15px/700 (nom de ligne), 14px (corps, légende de ligne),
  13px (méta), 12px (note), 11px/700 avec `letter-spacing: 0.1em` + majuscules
  (sur-titre « Reprise »).
- `'IBM Plex Mono', monospace` (400/500/600) — **tout chiffre**, sans exception :
  14px/600 (valeur de ligne), 13px, 12px, 11px (méta, sceaux), 10px (légende de rail).

`line-height` : 1.02 sur les grands titres, 1.2–1.3 sur les titres de ligne, 1.45–1.6 sur
le corps. `text-wrap: pretty` sur les paragraphes longs.

**Rayons** : 22px (écran mobile), 14px (écran desktop), 10px (panneau), 6px (ligne,
bouton, sceau de nav), 3px (sceau, jeton), 99px (jauge, pastille).

**Espacements** : 2px (entre lignes d'une liste), 6–8px (interne serré), 10–14px (corps de
ligne, goutttière mobile), 16px (entre panneaux), 20–28px (marge d'écran), 40px (entre
options d'un tour), 56px (marge de section).

**Jauges** : 6px de haut dans une ligne de semaine, 7px dans un bloc latéral, 8px dans la
nav sombre. Piste `#e0d7c1` (ou `#3a342b` sur fond sombre), remplissage `#5b2fb0`,
`border-radius: 99px`, `overflow: hidden`.

**Ombres** : aucune. La direction Parchemin sépare les surfaces par la bordure et la
teinte, jamais par l'ombre portée.

**Cibles tactiles** : 44px minimum sur mobile ; les boutons primaires des maquettes font
48–56px.

## Assets

Aucun asset n'est requis pour le Tour 9 — le bloc est entièrement typographique. Les
autres tours utilisent les planches d'icônes **déjà dans le repo**
(`assets/styles/images/Resources/Shikashi's Fantasy Icons Pack v2`, et
`assets/styles/images/monster/Enemy 06-1.png` = squelette, `Enemy 09-1.png` = fantôme,
les deux espèces nocturnes de la Forêt des murmures). Les quelques emoji des lignes
d'attente (🏚️ 🔨 ✉️ ✦) sont ceux du gabarit livré ; ils viennent de
`game.home.pending.*.icon` dans `translations/messages.fr.json` et ne sont pas une
invention de la maquette — à la différence des emoji de `stat-badge` (§ 3.7) et du 💥 de
combat (§ 1), qui sont à retirer.

## Captures

`captures/` — vingt PNG à l'échelle 1, un par écran maquetté : `11a` `11b` `11c` (le
cours), `10a` `10c` (hôtel des ventes, mise en vente), `9a` `9b` `9c` (hub de la semaine),
`8a` `8b` (compétences), `7a` `7b` `7d` (sac), `6a` `6b` (combat), `5a` `5b` `5d` (hub),
`4a` `4b` (zone). Elles servent de référence visuelle rapide ; le document
`ecrans.dc.html` reste la source (il porte les annotations, les états et les écarts, que
les captures ne montrent pas).

## Fichiers joints

| Fichier | Contenu |
| --- | --- |
| `ecrans.dc.html` | Les maquettes, un `<section>` par tour, le plus récent en haut. Identifiants visibles (`6a`, `7d`, `8b`…) utilisables en discussion |
| `design-system.dc.html` | Direction « Parchemin » : tokens, typo, composants, raretés |
| `design-system-repo-paths.dc.html` | Idem, chemins d'icônes du repo |
| `inventaire-ecrans.dc.html` | Les 71 gabarits `render('game/…')` du jeu et leur état (61 écrans pleins, 10 fragments) |
| `support.js` | Runtime des documents — ne pas éditer, ne pas porter |

Le dossier `design/` du repo contient une copie de ces documents, **en retard de deux
tours** au 29/07 (son `SYNC.md` est daté du 28/07 et s'arrête au Tour 6 : ni Sac, ni
Compétences). S'y fier pour le Sac ou les Compétences donnerait de vieux écrans.
