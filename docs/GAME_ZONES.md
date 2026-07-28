# Zones du jeu de base — définitions actées

> **Statut : acté le 2026-07-28.** Ce document ferme le §13.2 de
> [GAME_WORLD.md](GAME_WORLD.md) : il transforme la proposition « base + deux extensions »
> en définitions opposables, zone par zone. Ses entrées amont sont toutes closes :
> la population cible (~50 joueurs quotidiens, GAME_WORLD §13.4), la colonne de progression
> ([GAME_PROGRESSION.md](GAME_PROGRESSION.md) §6), la décision G sur l'améthyste
> (GAME_WORLD §13.3) et l'audit de la chaîne de production ([BALANCE.md](BALANCE.md) §21-22).
>
> Les jalons d'exécution sont dans [roadmap/PLAN_ZONES.md](roadmap/PLAN_ZONES.md) (ZON-30+).
> Ce document décrit **ce que chaque zone est** ; il ne pose aucune valeur de calibrage —
> les profils de palier de `config/game/zones/world_1.yaml` restent la seule référence.

## 0. Les lois que chaque zone doit respecter

Rappel opposable — chaque dossier de zone ci-dessous est écrit contre ces huit lois :

1. **Source exclusive** : toute zone est la source exclusive d'au moins une chose
   (GAME_WORLD §5.5). Une zone qui produit « la même chose en un peu mieux » mourra.
2. **Raretés inversées** : une matière de base est présente dans beaucoup de zones, une
   matière de haut palier dans très peu (GAME_WORLD §5.1).
3. **Un objet de désir à chaque horizon** : une zone qui n'offre rien à désirer à la
   semaine, à la marée ou à l'an est une zone morte (GAME_PROGRESSION §6d).
4. **Le palier est la profondeur de strate** : plus on s'éloigne de Lumière, plus la
   strate est ancienne et le danger haut (GAME_WORLD §4.2).
5. **Les récoltes à portée du hub dès la semaine 1** : minage, herboristerie, pêche,
   dépeçage (GAME_PROGRESSION §6a) — et le **bûcheronnage, décidé le 2026-07-28**
   (cinquième récolte, domaine à créer) : l'arc, le bâton et la baguette existent en
   items sans qu'aucune recette ne les produise, et le housing attendra la même matière.
   La carte des essences est en §3 bis ; le dépeçage, lui, n'a pas de filon — il se
   pratique sur la faune.
6. **La récolte n'échoue jamais** : vitalité partagée et fatigue personnelle modulent le
   rendement, jamais l'accès (GAME_ZONE_ACTIONS).
7. **Peu de zones profondes** plutôt que beaucoup de zones minces : le jeu de base
   n'ajoute qu'**une** zone au monde livré (les Vallons), tout le reste est de
   l'approfondissement.
8. **L'améthyste n'a pas de filon** : sous-produit universel de toute action ; chaque zone
   porte une **signature** (tendance de bande, élément) — jamais un point de farm
   (GAME_WORLD §13.3).

## 1. Vue d'ensemble

**Jeu de base — 8 zones** : 1 sanctuaire + 6 foyers + 1 donjon.

```
                          ❄ Glacier du Silence          ← Extension 1
                          │
                          ❄ Pas de Givre                ← Extension 1
                          │
      ⛰ Crête ─── ⛏ Mines profondes
        │  ╲          ╱ │
   🜁 Marais ─── 🌲 Forêt des murmures
        │  ╲          │
        │   🌾 VALLONS D'AUBÉPINE (à créer)
        │             │
        │      ✨ VILLAGE DE LUMIÈRE ─ 🏡 Jardins
        │
   🏜 Dunes d'Ambre  →  🧂 Mer de Sel (← Ext. 1)  →  🏛 Cité ensevelie
```

> **Note de graphe.** La Mer de Sel part en Extension 1 (§4) : au lancement, la liaison
> Dunes → Cité ensevelie est **directe** (la piste des caravanes d'autrefois). Quand
> l'Extension 1 ouvre, la Mer de Sel s'intercale et rallonge la route — le monde
> s'agrandit sans jamais rétrécir.

| Zone | Région | Palier | Foyer | Ligne de production | Exclusivité (loi 1) |
|---|---|---|---|---|---|
| Lumière + Jardins | Sanctuaire | T0 — sûre | non (§3.4) | plancher T1 PNJ, herbes T0 | la sécurité ; le Cristal d'Améthyste |
| **Vallons d'Aubépine** | Plaines de l'Éveil | T1 | oui | agriculture, gibier/cuir, pêche de rive, hêtre | **le lin** (fibre), le blé, le gibier de plaine |
| Forêt des murmures | Plaines de l'Éveil | T1 | oui | herbes, bois, pêche de rivière | le ginseng, le saumon, **le chêne murmurant**, la sylve (Chœur) |
| Mines profondes | Terres Sauvages | T2 (fond T4) | oui | métal de base, forge | **le fer**, le sombracier, l'anguille |
| Marais brumeux | Terres Sauvages | T2 | oui | alchimie, toxines, bois noir | la ligne des poisons, **le bois tourbé** |
| Crête de Ventombre | Terres Sauvages | T3 (sommet T4) | oui | pierre, gemme, haute forge | **le cobalt**, le mithril, la givrecoiffe |
| Dunes d'Ambre | l'Ambre | T3 | oui | cuir/os, bronze du sud | **l'ambre fossile**, l'os, le platine, **le bois pétrifié** |
| Cité ensevelie | l'Ambre | T4 — donjon | non | reliques | **les plans anciens**, l'orichalque, le diamant |

## 2. Les huit zones

Chaque dossier suit le même gabarit : ce que la zone est (biome, loi du temps), ce qu'elle
produit (dont ce qu'il faut ajouter), sa signature d'améthyste, sa faune, et ce qu'on y
désire à chaque horizon.

### 2.1 Village de Lumière + Quartier des Jardins — le sanctuaire

- **Loi du temps** : aucune. La Voûte porte le village : **rien n'y dépose, rien n'y
  pourrit** (GAME_WORLD §4.3). Hors Crue, hors foyer, hors jeu territorial.
- **Production** : jardins du temple T0 (thym, lavande) + plancher T1 PNJ. Rien d'autre,
  jamais : Lumière garantit le plancher, jamais le plafond.
- **Signature d'améthyste : néant.** C'est la seule zone du monde où **aucune action ne
  rend d'améthyste** — le temps ne s'y dépose pas. Conséquence canon forte : le Cristal
  sous la Voûte est un **cœur**, pas un gisement ; on vit à côté de la plus grande
  améthyste du monde et on n'en ramasse pas un éclat. Premier fil de la trame §13.3.
- **Objets de désir** : semaine — la Commission s'y prend (RET-02) ; marée — l'acte
  saisonnier s'y ouvre ; an — le Codex et le Cristal (narratif).
- **État** : livré. Aucun contenu neuf requis.

### 2.2 Vallons d'Aubépine — la zone à créer

> Comble le trou identifié en GAME_WORLD §4.5 (« le manque le plus criant ») **et** le
> défaut de la loi 5 : le **dépeçage** n'a aujourd'hui aucun terrain d'apprentissage à
> portée du hub — la faune de la Forêt est trop disputée et trop mêlée de non-dépeçables.

- **Identité** : bocage et rivière à une demi-heure de marche de Lumière. Haies
  d'aubépine, prés, vergers, un gué. Le grenier du monde — la première zone « douce »
  après le sanctuaire, et le premier endroit où l'on comprend que le monde se cultive.
- **Loi du temps** : déposé **régulièrement et récemment** — la strate la plus jeune du
  monde. Danger T1 bas : de quoi apprendre à se battre sans y mourir.
- **Type / graphe** : `wilderness`, foyer possible (région Plaines de l'Éveil, 5 %).
  Connexions : Lumière ↔ Vallons (court), Vallons ↔ Forêt (court), Vallons ↔ Marais
  (moyen). La zone est **sur la route** du sud : le passage y dépose du sédiment (levier
  4 du §5.5).
- **Production & filons** *(paliers T0/T1 du calibrage — jamais un goulot)* :
  - Carrés de blé *(item à créer : `plant-wheat`)* — T0. Ouvre une chaîne de cuisine
    joueur (pain, ragoûts) aujourd'hui uniquement PNJ.
  - Linières *(item à créer : `plant-flax`)* — T1. **L'exclusivité de la zone** : le lin
    est la fibre du tanneur et du textile ; aucune autre zone n'en produira.
  - Perches du gué *(item à créer : `fish-perch`)* — T1, la pêche d'apprentissage entre
    la truite (Forêt) et la carpe (Marais).
  - **Gibier de plaine** : pas un filon — une faune dense et dépeçable (sangliers,
    cerfs *(monstres à créer)*, loups existants) qui fait des Vallons la source du
    `leather-raw` à taux plein. Le dépeceur commence ici.
- **Signature d'améthyste** : **abondante mais basse** — Trouble dominante, Claire
  courante, Bois/Bête. Le temps y passe vite et léger ; c'est la zone où le débutant
  apprend *qu'il existe* de l'améthyste, pas celle où on la cherche pure.
- **Faune** : gibier T1 (sanglier, cerf, renard), quelques loups en lisière, rien de
  nocturne d'agressif — la nuit des Vallons est calme, en contraste voulu avec la Forêt.
- **Objets de désir** : semaine — recettes de cuisine, premier plan de tannerie ; marée —
  le foyer (un Comptoir agricole naturel) et la foire de récolte ; an — rien, et c'est
  normal : une zone d'Acte I n'a pas à retenir un vétéran, elle le *revoit* quand la
  demande en lin et en cuir l'y ramène (leviers 1 et 3 du §5.5).
- **État** : à créer entièrement — jalon **ZON-30**.

### 2.3 Forêt des murmures — l'école

- **Loi du temps** : déposé régulièrement. La zone-école : on y apprend le combat,
  la cueillette, la nuit.
- **Production** : la plus riche ligne d'herboristerie du monde (9 herbes), truite et
  saumon. C'est voulu : l'herboriste débute ici et y revient.
- **Exclusivités** : le **ginseng** et le **saumon** (T2, uniques au monde) — et le
  **Chœur** : la croisée des veines Bois et Bête en fait le premier *biome de cristal*
  candidat (GAME_WORLD §4.4), à ouvrir plus tard comme lieu-dit.
- **Signature d'améthyste** : régulière, **Claire stable**, Bois/Bête. Le dépôt sans
  à-coups donne une améthyste sans surprise — la référence contre laquelle les autres
  zones se lisent.
- **Faune** : livrée et déclarée (ZON-26b fait) — vivier diurne complet, nuit aux
  morts-vivants, Gardien de la Forêt en rencontre sommitale.
- **Objets de désir** : semaine — le saumon et le ginseng du niveau qui monte ; marée —
  le foyer (Athénée d'herboristerie naturel) ; an — le Chœur quand il ouvrira.
- **État** : livré. Le Chœur est une réserve, pas un manque.

### 2.4 Mines profondes — le cœur industriel

- **Loi du temps** : déposé **très longtemps** — strates épaisses, temps tassé jusqu'à
  l'illisible.
- **Production** : tout le métal de base (cuivre, étain, fer, or) + l'anguille des
  bassins noyés. La matière première de toute la chaîne de forge.
- **Exclusivités** : le **fer** (unique au monde — l'étain est répliqué aux Dunes, le
  cuivre l'est déjà, le fer reste le monopole de la mine) et, au fond, le **sombracier**
  (`ore-darksteel`, T4 — cf. carte des minerais §3). L'anguille électrique reste la
  pêche que personne d'autre n'a.
- **Signature d'améthyste** : la **plus grande quantité du monde** — le postulat
  l'impose : c'est ici que le plus de temps s'est déposé. Mais bande **Trouble
  dominante** : trop de temps tassé rend le geste illisible. Le fond des galeries tire
  plus haut. Élément Métal.
- **Faune** : livrée et déclarée — constructs, patrouille d'automates, Seigneur de la
  Forge.
- **Objets de désir** : semaine — le palier de minage suivant ; marée — le foyer (le
  Bastion/Comptoir industriel où se joue la doctrine, §6) ; an — le sombracier et le
  volume d'améthyste.
- **État** : livré. Manque le filon de sombracier (ECO-24b).

### 2.5 Marais brumeux — l'officine

- **Loi du temps** : **stagnant**, jamais tassé. Brume, hantises, eaux mortes.
- **Production** : la ligne d'alchimie hostile — ce que le débutant ne trouve ni aux
  jardins ni en forêt.
- **Exclusivités** : la **ligne des toxines entière** — mandragore, belladone, spores
  fantômes. Tout poison, tout élixir sombre du jeu passe par le Marais. C'est déjà vrai
  dans les données ; on l'énonce comme loi : **aucune toxine ne sera jamais sourcée
  ailleurs**.
- **Signature d'améthyste** : **erratique** — Trouble le jour, tire haut la nuit. Le
  temps stagnant se dépose mal, mais quand il prend, il prend bien : le Marais nocturne
  est le premier endroit où un joueur d'Acte II voit du **Pur**. Élément Eau/Ténèbre.
  C'est l'information exclusive type du prospecteur (GAME_ZONE_ACTIONS).
- **Faune** : **non déclarée** — le Marais dépend encore de sa carte TMX d'origine.
  C'est le reste de ZON-26b (Sprint 13), pas un jalon neuf.
- **Objets de désir** : semaine — la commande d'élixirs (les commandes de craft exigeant
  une bande, ECO-23, mordront ici en premier) ; marée — le foyer (Athénée) ; an — le
  Pur nocturne.
- **État** : livré côté filons ; population à déclarer (ZON-26b).

### 2.6 Crête de Ventombre — le toit du monde de base

- **Loi du temps** : **arraché par le vent** — strates à nu, failles.
- **Production** : pierre et gemme — argent, cobalt, givrecoiffe. La zone de joaillerie.
- **Exclusivités** : le **cobalt** (goulot assumé de la chaîne actuelle, BALANCE §21 —
  la rareté se règle par le palier, pas en étranglant la capacité) et, au sommet, le
  **mithril** (`ore-mithril`, T4 — §3). La givrecoiffe reste l'herbe que personne
  d'autre n'a.
- **Signature d'améthyste** : **peu, mais la plus haute moyenne du monde** — le vent a
  emporté le meuble, ce qui reste est net : **Pure fréquente, Parfaite possible** sur
  les strates hautes reposées. Élément Air. C'est le pendant exact des Mines (beaucoup
  et trouble / peu et pur) — les deux zones s'expliquent l'une par l'autre, et le
  premier **Affleurement** permanent du jeu (biome §4.4) a vocation à ouvrir ici.
- **Faune** : **non déclarée** — même reste ZON-26b que le Marais.
- **Objets de désir** : semaine — l'argent et le cobalt du joaillier qui monte ; marée —
  le foyer, et l'Affleurement de la semaine quand RET-06 tirera la Crête ; an — le
  mithril et la Parfaite.
- **État** : livré côté filons de base ; mithril à poser (ECO-24b), population à
  déclarer (ZON-26b).

### 2.7 Dunes d'Ambre — le sud fossile

- **Loi du temps** : **épuisé** — ancien fond de mer, temps tari, choses conservées.
- **Production** : la ligne cuir/os du désert et le bronze du sud (cuivre livré, étain à
  répliquer — ECO-24b). La zone est aujourd'hui **la plus pauvre du monde livré** (un
  filon, quatre monstres) : c'est le gros de son chantier.
- **Exclusivités** *(à créer — jalon ZON-31)* : l'**ambre fossile** (résine de l'âge
  précédent, réactif d'enchantement et de joaillerie — l'« Ambre » de la région devient
  enfin une matière) et l'**os** (la faune du désert se dépeçe en os, pas en cuir souple
  — l'autre moitié de la ligne du tanneur). Le **platine** (`ore-platinum`, T3 — §3)
  complète le versant minier.
- **Signature d'améthyste** : **rare** — le temps épuisé n'a plus grand-chose à
  déposer. Mais le biome Ambre conserve : ce qu'on y trouve vient du **butin** (toute
  récolte réveille ce qui était conservé, §4.4), en bande haute — de l'améthyste d'un
  autre âge, pas de la fraîche. Élément Feu/Terre.
- **Faune** : déclarée mais maigre — à densifier en gibier à os (jalon ZON-31, la
  matière du dépeceur d'Acte III).
- **Objets de désir** : semaine — l'os et l'ambre du tanneur/joaillier ; marée — le
  foyer (la taxe la plus forte du monde : l'arbitrage entre marchés y rapporte le plus,
  §4.3) ; an — le platine, intrant du lingot de mithril (§3).
- **État** : le chantier de contenu principal du jeu de base après les Vallons.

### 2.8 Cité ensevelie — le donjon du monde de base

- **Loi du temps** : **enseveli d'un coup** — une civilisation conservée entière sous le
  sable, personne ne sait laquelle.
- **Nature** : **donjon, pas foyer.** La Cité ne monte pas, ne se taxe pas, ne se
  sédimente pas — on la fouille. C'est la 8e « zone » au sens du graphe et la fin de la
  progression du jeu de base.
- **Exclusivités** : les **plans anciens** (la source des recettes qu'aucun arbre ne
  donne), l'**orichalque** (`ore-orichalcum`, T4 — §3), le fruit du vide et le diamant
  (livrés).
- **Signature d'améthyste** : quasi nulle au sol — mais ses occupants en **rendent** :
  l'améthyste de la Cité vient du combat, en bande haute, chargée d'un autre âge. En
  fiction comme en mécanique, c'est le premier avant-goût de l'Étale (§5) : un lieu où
  l'on ne récolte pas, où l'on **retrouve**.
- **Faune** : déclarée (spectres des dunes, basilics, pillards). À terme, le biome
  **Miroir** (§4.4 — un lieu qui rejoue le jour de l'ensevelissement) est le support
  naturel d'une marée entière : réserve narrative, pas un manque.
- **Objets de désir** : semaine — un plan ; marée — l'événement de fouille ; an —
  l'orichalque, dont le lingot attend l'Extension 1 (§3) : le donjon de base reste
  chargé de valeur future.
- **État** : livré. Orichalque à poser (ECO-24b).

## 3. La carte des minerais — l'entrée d'ECO-24b

L'audit (BALANCE §21) a montré six minerais de haut palier sans filon (portés par des
`ObjectLayer` hérités) et l'étain à source unique. Voici la carte actée — c'est l'entrée
du jalon ECO-24b, qui l'exécutera :

| Minerai | Zone | Palier | Pourquoi là |
|---|---|---|---|
| `ore-tin` (2e source) | Dunes d'Ambre | T0 | le bronze du sud — la région du cuivre répliqué reçoit l'étain |
| `ore-darksteel` | Mines profondes (fond) | T4 | le métal du temps le plus tassé |
| `ore-mithril` | Crête de Ventombre (sommet) | T4 | le métal que le vent a mis à nu |
| `ore-platinum` | Dunes d'Ambre | T3 | le métal du fond de mer fossile |
| `ore-orichalcum` | Cité ensevelie | T4 | le métal de l'âge précédent — butin de donjon plus que filon |
| `ore-adamantite` | Mer de Sel | T4 | **Extension 1** |
| `ore-starmetal` | Glacier du Silence | T4 | **Extension 1** |
| `ore-voidium` | l'Étale | — | **Extension 2** |

**La conséquence est déjà écrite dans les recettes livrées**, et elle est excellente —
chaque alliage T4 exige deux zones, et les paliers d'extension enjambent la frontière :

- **Lingot de mithril** = mithril (Crête) + platine (Dunes) → **le sommet du jeu de
  base**, et un commerce nord-sud obligatoire (loi des caravanes, §5.3).
- **Lingot d'adamantite** = adamantite (Mer de Sel, Ext. 1) + sombracier (Mines,
  **base**) → l'Extension 1 renvoie ses vétérans dans les Mines (levier 1 du §5.5).
- **Lingot d'orichalque** = orichalque (Cité, **base**) + métal étoilé (Glacier,
  Ext. 1) → le donjon de base reste un passage obligé de l'endgame d'extension.

Rien à inventer : il suffit de **placer les filons là où les recettes l'exigent déjà**.
La règle des raretés inversées est respectée sur toute la ligne : cuivre 3 sources,
étain 2, fer 1 (T2, exclusivité assumée), T3/T4 une source chacun.

## 3 bis. La carte du bois — la ligne à créer *(décidé le 2026-07-28)*

Aucune ressource bois n'existait ; la ligne entière (armes de bois, housing) était sans
matière. Quatre essences, raretés inversées respectées, chaque zone forestière gagnant
une exclusivité de plus :

| Essence | Palier | Zones | Débouchés visés |
|---|---|---|---|
| Bois de hêtre | T0 | Vallons **+** Forêt (deux sources — jamais un goulot) | manches d'outils, flèches, meubles simples |
| Chêne murmurant | T2 | Forêt (exclusif — l'arbre de la zone-titre) | arcs, bâtons, baguettes |
| Bois tourbé | T3 | Marais (exclusif — noirci par l'eau morte) | baguettes sombres, teintures, housing |
| Bois pétrifié | T4 | Dunes (exclusif — l'âge précédent, thème Ambre) | armes de maître, mobilier de prestige |

La Crête et le Silence n'ont **pas** d'arbres (le vent et le gel l'interdisent — la
fiction et la loi des biomes concordent) : le bois reste une affaire de plaines, de
sous-bois et de fossiles. Le domaine de récolte (bûcheron) et les essences relèvent du
jalon **ZON-34** ; le métier consommateur est tranché — le **charpentier** (ECO-30,
Piste H de PLAN_PLAYER_ECONOMY), aux côtés du **cuisinier** (ECO-29, débouché de la
pêche et du blé) et du **tailleur** (ECO-31, la ligne tissu née du lin des Vallons).

## 4. Extension 1 — le Silence *(esquisse actée)*

**Zones** : Mer de Sel, Pas de Givre, Glacier du Silence (livrées), plus une à deux
zones neuves. **Modèle expédition** (GAME_WORLD §4.3) : aucun foyer ne tient — pas de
marché, pas de banque, pas de taxe. On monte une expédition, on rapporte tout sur le dos.

- **Économie** : adamantite (Mer de Sel) et métal étoilé (Glacier) — les deux intrants
  qui manquent aux alliages T4+ (§3). La demande de l'extension retombe sur les Mines et
  la Cité de base : l'extension **nourrit** le monde existant au lieu de le vider.
- **Améthyste** : le temps **arrêté net** rend une améthyste figée — bande haute mais
  **quantité quasi nulle**, et uniquement sur ce qu'on *dérange* (combat, fouille). Le
  Silence est riche et ne se laisse pas traire.
- **La Mer de Sel change de bras** : livrée sur la route du sud, elle rejoint le Silence
  à l'ouverture de l'extension (le sel est du temps épuisé *et* arrêté — la fiction
  l'autorise) ; la liaison directe Dunes → Cité du lancement (§1) s'allonge alors d'un
  cran. Décision de graphe à jouer à l'ouverture, pas avant.

## 5. Extension 2 — l'Étale *(esquisse actée)*

La frontière — ce que le Reflux a repris (GAME_WORLD §12.1). Biome d'**absence** :
aucune récolte, aucun PNJ, aucun foyer, seulement les Effacés.

- **Source exclusive** : les plans perdus, la matéria perdue (la seule « matéria
  trouvée » qui ne vienne pas d'une table de butin ordinaire), le voidium (§3).
- **Améthyste** : on n'y **récolte** rien — on y **retrouve**. Les lots d'améthyste de
  l'Étale sont des souvenirs entiers, bande Parfaite plus fréquente que partout, en
  quantités infimes. C'est l'aboutissement du fil narratif §13.3 : au bord du monde, la
  distinction entre « ressource » et « mémoire » cesse d'exister.
- Contenu de fin de jeu, déjà conçu dans ses principes (§12.1, §7.4) — rien à décider ici.

## 6. Ce que ce document ne décide pas

- **Aucune valeur de calibrage** : capacités, respawns et rendements des filons neufs
  reprennent les profils de palier de `world_1.yaml` — et le recalibrage global
  (BALANCE §22.3) s'applique à tout, ancien comme neuf.
- **Les biomes de cristal** (Chœur en Forêt, Affleurement en Crête, Miroir à la Cité)
  sont des **réserves nommées** : chacun est une mécanique à part entière, ouverte par
  son propre jalon le jour venu, jamais un simple décor à poser.
- **Le détail des items à créer** (blé, lin, perche, ambre fossile, os, gibier) relève
  des jalons ZON-30/31 : ce document fixe *ce qui doit exister et où*, pas les fiches.
- **Les signatures d'améthyste** sont des tendances de conception : leur traduction en
  config est le jalon ZON-32, consommée par le tirage de pureté (ECO-22).
