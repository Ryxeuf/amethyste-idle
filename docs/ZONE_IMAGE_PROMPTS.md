# Prompts de génération d'image — illustration par zone

> Pendant de [WORLD_MAP_IMAGE_PROMPT.md](WORLD_MAP_IMAGE_PROMPT.md), qui couvre la **carte
> du monde**. Ce document couvre les **12 illustrations de zone** — le bandeau affiché en
> tête de `/game/zone`. À coller dans ChatGPT (ou tout générateur d'images).
>
> Sources de vérité : l'identité de chaque zone vient de [GAME_ZONES.md](GAME_ZONES.md) §2
> (loi du temps, production, faune, signature d'améthyste, micro-lieux) et de
> `config/game/zones/world_1.yaml` (description en jeu, filons, population). Rien n'est
> inventé ici : ce document **traduit** ces deux sources en consignes visuelles.

## 1. Où l'image atterrit

L'emplacement existe déjà dans le code : `Zone::illustrationPath`
(`src/Entity/App/Zone.php:54`), rendu par `templates/game/zone/index.html.twig:670` :

```twig
<img src="{{ asset('images/' ~ zone.illustrationPath) }}" alt="{{ zone.localizedName(...) }}"
     class="mt-3 rounded-panel border border-line max-h-48 w-full object-cover">
```

Conséquences opposables :

- **C'est un bandeau, pas une vignette** : pleine largeur du panneau (~640 px), hauteur
  plafonnée à 192 px, recadré en `object-cover`. Le format cible est donc **≈ 3:1**.
- **Le recadrage mange le haut et le bas.** Composer la scène de façon que l'essentiel
  tienne dans le **tiers horizontal médian** : un ciel magnifique et un premier plan chargé
  seront coupés.
- **Aucun texte dans l'image** : ni nom de zone, ni cartouche, ni légende. Le nom est écrit
  par l'application juste au-dessus, et l'`alt` est déjà rempli — l'image est décorative.
- **Livrer en WebP**, comme la carte du monde (`quality=86, method=6`). Ordre de grandeur
  visé : 80–200 Ko par bandeau.
- **Chemin de fichier** : `public/images/zones/<slug>.webp`, et
  `illustrationPath = zones/<slug>.webp`.
- **Le champ n'est pas encore importé depuis le YAML** : ni `ZoneDefinitionLoader` ni
  `ZoneImporter` ne connaissent la clé. Il ne se renseigne aujourd'hui qu'à la main dans
  `/admin/zone`, et **cette saisie est perdue au prochain `doctrine:fixtures:load`**, qui
  réimporte les zones depuis `world_1.yaml`. Déposer les images ne suffira donc pas : le
  câblage est spécifié par le jalon **ZON-41**
  ([roadmap/PLAN_ZONES.md](roadmap/PLAN_ZONES.md)) — clé `illustration` dans le YAML,
  validée au chargement (`zones/<slug>.webp`, le nom de fichier *est* le slug), avec
  avertissement non fatal quand le fichier manque. **À faire avant la première image**, ou
  au moins avant la deuxième.

### Format à demander au générateur

ChatGPT ne sort pas de 3:1 nativement (ses formats sont 1024×1024, 1536×1024, 1024×1536).
La marche à suivre :

1. Demander **1536×1024** (paysage 3:2) en précisant *« compose l'image comme une bannière
   panoramique : tout l'essentiel dans la bande horizontale centrale »*.
2. Recadrer la bande centrale en 1536×512, puis convertir :
   `Image.open(src).crop((0, 256, 1536, 768)).convert('RGB').save(dst, 'WEBP', quality=86, method=6)`.

| Zone | Fichier | `illustrationPath` |
|---|---|---|
| Le Fanal | `public/images/zones/village-de-lumiere.webp` | `zones/village-de-lumiere.webp` |
| Quartier des Jardins | `.../quartier-des-jardins.webp` | `zones/quartier-des-jardins.webp` |
| Vallons d'Aubépine | `.../vallons-d-aubepine.webp` | `zones/vallons-d-aubepine.webp` |
| Forêt des Murmures | `.../foret-des-murmures.webp` | `zones/foret-des-murmures.webp` |
| Mines Profondes | `.../mines-profondes.webp` | `zones/mines-profondes.webp` |
| Marais Brumeux | `.../marais-brumeux.webp` | `zones/marais-brumeux.webp` |
| Crête de Ventombre | `.../crete-de-ventombre.webp` | `zones/crete-de-ventombre.webp` |
| Dunes d'Ambre | `.../dunes-d-ambre.webp` | `zones/dunes-d-ambre.webp` |
| Mer de Sel | `.../mer-de-sel.webp` | `zones/mer-de-sel.webp` |
| Cité Ensevelie | `.../cite-ensevelie.webp` | `zones/cite-ensevelie.webp` |
| Pas de Givre | `.../pas-de-givre.webp` | `zones/pas-de-givre.webp` |
| Glacier du Silence | `.../glacier-du-silence.webp` | `zones/glacier-du-silence.webp` |

## 2. Mode d'emploi — une conversation, douze images

Les douze bandeaux doivent **se ressembler** : ils s'affichent l'un après l'autre au fil des
voyages, et une seule image au style différent casse la série. La méthode qui tient :

1. Ouvrir **une seule conversation** ChatGPT et y coller le **§3 (charte)** en premier
   message, sans demander d'image.
2. Coller ensuite **un bloc de zone du §4 par message**, dans l'ordre du document (du Fanal
   vers l'extérieur) : le modèle garde le style de l'image précédente.
3. Si la conversation dérive (ça arrive au bout de 5–6 images), recoller la charte avant le
   bloc suivant. Chaque bloc du §4 est écrit pour rester lisible seul : *charte + bloc* est
   toujours un prompt complet.

## 3. La charte — premier message de la conversation

```
Nous allons produire une série de 12 illustrations pour un jeu de rôle web. Ce sont des
bandeaux d'ambiance : une image par lieu, toutes du même monde, toutes de la même main.
Lis cette charte, ne génère rien tout de suite, et réponds simplement « prêt ».

FORMAT
Chaque image : 1536x1024, paysage. Compose-la comme une bannière panoramique — l'essentiel
doit tenir dans la bande horizontale centrale, car le haut et le bas seront recadrés.

STYLE (identique pour les 12)
Peinture numérique à l'aquarelle et à l'encre, comme une planche de carnet de voyage
ancien : lavis souples, hachures fines à la plume pour les ombres, grain de papier visible,
bords qui s'estompent légèrement dans le papier. Vue à hauteur d'homme ou en léger surplomb,
paysage large. Pas de photoréalisme, pas de rendu 3D moderne, pas de style anime, pas
d'aplats vectoriels.

PALETTE (identique pour les 12)
Le papier est un parchemin crème (#fbf8f0). Les encres vont du brun profond (#26221c) au
sépia (#5d564a), les filets clairs sont beiges (#ddd2ba). Chaque lieu a UNE dominante
colorée propre, toujours désaturée, posée en lavis par-dessus cette base. Une seule couleur
vraiment saturée existe dans ce monde : un violet améthyste (#5b2fb0). Elle est rare et
elle a un sens précis — je te dirai, pour chaque lieu, combien d'améthyste on y voit et
sous quelle forme. N'en ajoute jamais de ton propre chef.

INTERDITS ABSOLUS (pour les 12)
- Aucun texte, aucun mot, aucun chiffre, aucune enseigne lisible, aucun cartouche, aucune
  légende, aucune signature.
- Aucun élément d'interface : pas de cadre, pas d'icône, pas de barre, pas de marqueur de
  lieu, pas de rose des vents.
- Aucun personnage au premier plan et aucun visage : au plus deux ou trois silhouettes
  lointaines et petites, de dos, quand je le demande explicitement.
- Aucune arme brandie, aucune scène de combat : ces images montrent un lieu, pas une action.
- Aucun logo, aucun blason inventé, aucune technologie moderne.

CE QUE J'ATTENDS
Une image par message. Si un lieu ressemble trop au précédent, écarte-les par la lumière
(heure du jour), par la dominante colorée et par l'échelle du relief, jamais par le style.
```

## 4. Les douze prompts

Chaque bloc suit le même gabarit : d'abord une note de conception (pourquoi l'image montre
ça — à ne PAS coller), puis le prompt à coller tel quel.

---

### 4.1 Le Fanal — le sanctuaire

> **Note de conception.** Zone sûre, hub, `safe: true`. C'est le seul lieu du monde où
> *rien ne se dépose* : sa signature d'améthyste est **néant** (GAME_ZONES §2.1). Donc
> **aucune veine violette dans la pierre** — la seule lueur d'améthyste autorisée est celle
> qui filtre de la Voûte, sous le village : le Cristal est un cœur, pas un gisement. Le feu
> du haut de la tour n'a jamais été laissé s'éteindre : c'est le point focal, et la seule
> vraie source de lumière chaude de toute la série.

```
Illustration 1 sur 12 — Le Fanal.

Un village fortifié de pierre chaude, blotti autour d'une haute tour-phare dont le sommet
porte un feu vif qui n'a jamais été éteint. Toits d'ardoise, cheminée de forge qui fume,
étals d'échoppes sous des auvents de toile, un jardin de temple aux carrés d'herbes bien
tenus, un rempart bas et un chemin de terre qui entre par une porte ouverte. Fin
d'après-midi, lumière dorée, ciel calme et dégagé.

Dominante : ocre chaud et miel, pierre blonde, un peu de vert tendre pour les jardins.
C'est le lieu le plus lumineux et le plus rassurant de toute la série — l'image doit donner
envie de s'y arrêter.

Améthyste : aucune veine violette dans la roche, nulle part. Une seule note de violet, très
discrète : une lueur froide qui monte des soupiraux et des marches d'un escalier de pierre
descendant sous le village, comme si quelque chose de très grand dormait dessous. On la
devine, on ne la voit pas.

Deux ou trois silhouettes lointaines de villageois, petites, de dos. Aucun texte, aucune
enseigne lisible, aucune interface.
```

---

### 4.2 Quartier des Jardins — le faubourg

> **Note de conception.** `type: city`, `safe: true`, faubourg résidentiel du Fanal :
> « allées de terre battue, murets bas et parcelles à vendre. On y bruit des projets plus
> que des combats. » C'est l'écran du logement (FOY) : l'image doit montrer des **maisons en
> train de se faire** et des **parcelles vides**, pas un quartier fini.

```
Illustration 2 sur 12 — le Quartier des Jardins.

Le faubourg d'un village, juste à l'extérieur de ses murs, que l'on aperçoit au loin avec
sa tour-phare. Des allées de terre battue bordées de murets bas en pierre sèche, des
parcelles cultivées et des potagers, quelques vergers en rangs, des puits, du linge qui
sèche. Deux ou trois maisons en construction : échafaudages de bois, tas de moellons, un
toit à moitié couvert. Et surtout des parcelles encore vides, simplement délimitées par des
piquets et une corde — des emplacements qui attendent leur propriétaire.

Matin clair, ombres longues, atmosphère paisible et un peu bourdonnante de travaux.
Dominante : vert tendre et terre battue beige, un peu de bois neuf blond.

Améthyste : aucune. Ce lieu ne porte pas de violet.

Une ou deux silhouettes lointaines de dos, occupées à jardiner ou à bâtir. Aucun texte,
aucune interface.
```

---

### 4.3 Vallons d'Aubépine — le grenier

> **Note de conception.** La strate la plus jeune du monde, zone-école du dépeceur, gibier
> dense et calme. Ses micro-lieux sont nommés (GAME_ZONES §2.2) : **le Gué**, **le Vieux
> Moulin en ruine** (le cœur du futur foyer — il doit être *en ruine*), **les Vergers**, **la
> Halle à foin**. Ses filons : carrés de blé, **linières** (son exclusivité — le lin fleurit
> bleu pâle), perches du gué, hêtraie. Signature : améthyste **abondante mais basse** →
> beaucoup d'éclats, mais ternes, laiteux, dans la terre remuée.

```
Illustration 3 sur 12 — les Vallons d'Aubépine.

Un bocage vallonné traversé par une rivière large et peu profonde, franchie par un gué de
pierres plates. Des haies d'aubépine en fleur découpent des prés et des champs : un carré de
blé mûr, et à côté un champ de lin en fleur, d'un bleu pâle très doux. Un verger en rangs, une
grande halle à foin ouverte, une hêtraie claire en lisière. Au bord du gué, les ruines d'un
vieux moulin à eau : mur éventré, roue brisée, poutres tombées — un chantier qui attend.

Fin d'après-midi de fin d'été, lumière dorée et basse, air doux. Dominante : vert prairie et
or du blé, avec la note bleu pâle du lin.

Faune, au loin et paisible : un cerf immobile en lisière, une nuée de corbeaux au-dessus du
champ de blé. Rien de menaçant — c'est la zone douce du monde.

Améthyste : beaucoup d'éclats minuscules, mais ternes et laiteux, presque gris-violet, dans
la terre fraîchement labourée et sur les berges remuées du gué. On voit qu'il y en a
partout ; on voit aussi qu'aucun ne vaut grand-chose.

Aucun personnage au premier plan. Aucun texte, aucune interface.
```

---

### 4.4 Forêt des Murmures — l'école

> **Note de conception.** « Arbres centenaires, clairières et rivière aux eaux vives. On y
> apprend à se battre et à cueillir — et, la nuit venue, à ne pas s'attarder. » Signature :
> **Claire stable** — l'améthyste de référence, régulière et sans surprise. Le **Chœur** est
> une réserve narrative : on le *suggère* par une clairière où la lumière est plus dense, on
> ne le dessine pas comme un lieu. La nuit y est dangereuse, mais le bandeau reste diurne
> (le danger se lit à la profondeur des sous-bois, pas à une scène de nuit).

```
Illustration 4 sur 12 — la Forêt des Murmures.

Une forêt ancienne et dense : troncs énormes couverts de mousse, houppier haut qui laisse
tomber des rais de lumière obliques, fougères et tapis de feuilles. Une rivière vive court
au premier plan sur des rochers, avec un rapide et une petite cascade. Une clairière s'ouvre
au centre, plus lumineuse que le reste, où l'air semble un peu plus dense — comme si le
silence y était plus épais qu'ailleurs. Au bord du sentier, des touffes d'herbes
médicinales, et un chêne isolé, plus vieux et plus large que tous les autres.

Milieu de matinée, brume basse entre les troncs, contre-jour doux. Dominante : vert profond
et brun mousse, avec des blancs de brume. Le fond de la forêt, à droite, s'assombrit
franchement — on comprend qu'il ne faut pas s'y attarder.

Améthyste : quelques éclats violets nets et réguliers, bien formés, affleurant à la racine
des grands arbres et dans le lit de la rivière. Peu nombreux, mais francs et propres.

Aucun personnage. Aucun texte, aucune interface.
```

---

### 4.5 Mines Profondes — le cœur industriel

> **Note de conception.** « Tunnels étayés, bassins noirs et filons à perte de galerie. »
> Loi du temps : déposé **très longtemps**, strates épaisses. Signature : **la plus grande
> quantité d'améthyste du monde**, mais bande **Trouble** — l'image doit montrer *beaucoup*
> de violet, et un violet *sale*. Le sombracier est au **fond**, pas à l'entrée. Faune :
> constructs et automates (à peine suggérés). Un bassin noyé (les anguilles) doit figurer.

```
Illustration 5 sur 12 — les Mines Profondes.

L'intérieur d'une grande galerie de mine, éclairée par des lanternes accrochées aux
étais. Charpente de bois massive, rails et wagonnets, échelles, plates-formes à flanc de
paroi. Les strates de la roche sont visibles et très épaisses, empilées comme des pages. Un
bassin d'eau noire parfaitement immobile occupe une partie du sol et reflète les lanternes.
Au fond, la galerie descend et s'assombrit jusqu'au noir complet ; on devine tout au fond un
affleurement de métal sombre et mat qui n'accroche pas la lumière.

Souterrain, lumière chaude et rare des lanternes contre une obscurité minérale. Dominante :
noir, gris de poussière et ocre de rouille, avec les halos orangés des lampes.

Améthyste : c'est le lieu qui en contient le plus au monde. Des veines violettes courent
partout dans les parois, épaisses et nombreuses — mais elles sont troubles, laiteuses,
opaques, presque grises par endroits. Beaucoup de matière, aucune pureté.

Au loin, dans la pénombre, la silhouette immobile d'un automate de pierre et de métal, à
peine lisible. Aucun personnage humain, aucun texte, aucune interface.
```

---

### 4.6 Marais Brumeux — l'officine

> **Note de conception.** « Brume épaisse et eaux stagnantes ; des créatures corrompues
> rôdent. » Loi du temps : **stagnant, jamais tassé**. Toute la ligne des toxines du jeu
> vient d'ici (mandragore, belladone, spores fantômes), plus le **bois tourbé** (souches
> noircies). Signature **erratique** : Trouble le jour, tire haut la nuit. Le bandeau étant
> diurne, l'améthyste y est rare et sale — mais l'image doit laisser sentir que la nuit
> change tout.

```
Illustration 6 sur 12 — le Marais Brumeux.

Un marécage de tourbières : nappes d'eau stagnante couvertes de lentilles et d'un film
irisé, îlots de mousse, arbres morts aux branches nues, souches noircies par l'eau. Des
passerelles de planches à demi pourries serpentent d'un îlot à l'autre. Une brume épaisse
noie l'arrière-plan et efface l'horizon. Au premier plan, des plantes vénéneuses : capsules
sombres, champignons pâles et grêles poussant en touffes, une plante à baies noires.

Jour gris et sans soleil, lumière plate et diffuse. Dominante : vert-de-gris malade, brun
tourbe, gris de brume. L'ambiance doit être fiévreuse et malsaine sans devenir horrifique.

Améthyste : très peu, et trouble — deux ou trois éclats ternes noyés dans la vase. Mais la
brume, elle, porte par endroits un très léger halo violacé, comme si quelque chose de plus
pur attendait la nuit pour se montrer.

Aucun personnage, aucune créature visible — seulement des remous dans l'eau et un
frémissement dans les roseaux. Aucun texte, aucune interface.
```

---

### 4.7 Crête de Ventombre — le toit du monde

> **Note de conception.** « Pics balayés par des vents violents ; le sommet abrite les
> créatures les plus rudes du World 1. » Loi du temps : **arraché par le vent**, strates à
> nu. C'est le pendant exact des Mines : **peu d'améthyste, mais la plus pure du monde**
> (Pure fréquente, Parfaite possible). Les deux images doivent se lire l'une contre
> l'autre — beaucoup et sale / rare et net. Argent, cobalt, givrecoiffe ; le mithril au
> sommet.

```
Illustration 7 sur 12 — la Crête de Ventombre.

Une haute crête de montagne : pics déchiquetés, arêtes coupantes, failles profondes, parois
où les strates de roche sont mises à nu et tordues. Aucun arbre — le vent l'interdit. Des
plaques de neige tiennent dans les creux, des rubans de neige poudreuse s'envolent des
arêtes. Un sentier de corniche exposé, balisé par des cairns, longe le vide. Sur un replat
abrité, une petite herbe grise à capuchon givré pousse en touffes serrées.

Fin de journée, ciel dur et limpide, lumière rasante et froide, tout est net et coupant.
Dominante : gris-bleu de pierre, blanc de neige, un ciel très pâle. C'est le lieu le plus
dépouillé et le plus large de la série.

Améthyste : très peu — trois ou quatre cristaux seulement, mais chacun parfaitement net,
translucide, taillé comme un prisme, pris dans les strates hautes. Ils accrochent la lumière
du soir et sont le seul point de couleur saturée de l'image. Rareté et pureté : c'est
l'exact contraire de la mine.

Aucun personnage. Aucun texte, aucune interface.
```

---

### 4.8 Dunes d'Ambre — le sud fossile

> **Note de conception.** « Une mer de sable ocre au sud du marais. Rien n'y pousse, tout y
> rampe. » Loi du temps : **épuisé** — ancien fond de mer, temps tari, choses *conservées*.
> Ses exclusivités sont visuelles : **l'ambre fossile** qui se dégage du sable et les
> **troncs pétrifiés**. Signature : améthyste **rare mais de bande haute**, et elle vient de
> ce qu'on **réveille** — donc dans les choses déterrées, pas dans le sol.

```
Illustration 8 sur 12 — les Dunes d'Ambre.

Un désert de dunes ocre en vagues longues, ancien fond de mer. Rien ne pousse. Émergeant du
sable : des ossements blanchis d'un très grand animal, une côte et une mâchoire à demi
dégagées, et des troncs d'arbres couchés changés en pierre, veinés comme du bois mais durs
comme du roc. Dans le creux d'une dune, une coulée de résine fossile ambrée, translucide et
dorée, affleure en gros nodules qui retiennent la lumière. Au loin, une piste de caravane
jalonnée de perches penchées, et une tente basse de couleur passée.

Milieu d'après-midi écrasant, ciel blanchi de chaleur, air qui tremble à l'horizon.
Dominante : ocre, sable doré, ambre — c'est l'image la plus chaude de la série.

Améthyste : presque rien au sol. Un seul éclat, mais profond et intense, pris dans un
nodule d'ambre déterré — d'un autre âge, conservé plutôt que déposé.

Un scorpion immobile à l'ombre d'un os, petit et loin. Aucun personnage, aucun texte,
aucune interface.
```

---

### 4.9 Mer de Sel — la croûte

> **Note de conception.** « Une croûte blanche à perte de vue, craquelée par la chaleur. Ce
> qui bouge ici a appris à ne plus avoir soif. » Zone d'Extension 1 par destination (le
> Silence), livrée sur la route du sud. Deux récoltes seulement : la **gemme de sel** et,
> dans la seule eau profonde du monde, les **bancs de krakens** — d'où la saumure noire.
> Faune : colosses de sel. Signature : temps épuisé *et* arrêté — quantité quasi nulle.

```
Illustration 9 sur 12 — la Mer de Sel.

Une plaine de sel absolument plate, jusqu'à l'horizon : une croûte blanche craquelée en
polygones réguliers, soulevée sur les bords comme des écailles. Des concrétions de sel
poussent en colonnes et en champignons de cristal blanc. Au milieu de la plaine, un trou
d'eau profonde à la surface parfaitement immobile, presque noire, dont on ne voit pas le
fond — la seule eau du lieu. Sur une bosse de la croûte, quelques gemmes rouges affleurent,
prises dans le sel.

Plein midi, soleil au zénith, lumière aveuglante et sans ombre, ciel délavé jusqu'au blanc.
Dominante : blanc sur blanc, avec des ombres bleu très pâle dans les craquelures. L'image
doit paraître trop claire, presque brûlée — un lieu où l'on ne peut pas s'arrêter.

Améthyste : aucune visible. Le temps s'est arrêté ici, il n'a rien laissé.

Au loin, une forme massive et immobile, faite des mêmes cristaux blancs que la plaine, qu'on
prendrait pour un rocher si elle n'avait pas des épaules. Aucun personnage, aucun texte,
aucune interface.
```

---

### 4.10 Cité Ensevelie — le donjon

> **Note de conception.** « Des toits qui affleurent le sable. On ne sait pas qui vivait là,
> et ce qui l'occupe aujourd'hui ne le dira pas. » Loi du temps : **enseveli d'un coup** —
> une civilisation conservée entière. C'est un **donjon** : on ne s'y installe pas, on la
> fouille. Signature : quasi nulle au sol, mais ses **occupants** en rendent — donc le
> violet est dans l'ouverture noire, pas dans le sable.

```
Illustration 10 sur 12 — la Cité Ensevelie.

Une cité engloutie sous le sable, dont seuls les hauts affleurent : faîtes de toits, sommets
de colonnes brisées, un fronton sculpté à moitié émergé, l'arc d'un pont qui ne mène nulle
part. Une architecture ancienne, élégante et inconnue, sans rien qui permette de la
rattacher à un peuple connu. Au centre, une brèche s'ouvre dans un toit : un escalier de
pierre y descend dans le noir, encore intact, comme si la ville avait été recouverte en une
seule nuit sans que personne ait eu le temps de fuir.

Fin de journée, lumière rasante et orangée, longues ombres portées sur le sable. Dominante :
sable pâle et ombre bleutée, pierre grise. L'ambiance est celle d'une fouille : silencieuse,
immense, un peu solennelle.

Améthyste : rien dans le sable. Mais du fond de l'escalier, très loin sous terre, monte une
lueur violette froide et régulière — comme une respiration. Elle vient de ce qui vit encore
là-dessous.

Aucun personnage. Aucun texte, aucune interface.
```

---

### 4.11 Pas de Givre — le col

> **Note de conception.** « Le col au-delà de la crête. Le vent y porte des choses qui
> hurlent, et le froid ne laisse pas s'arrêter. » Extension 1, **modèle expédition** : aucun
> foyer, pas de marché — l'image ne doit contenir **aucune installation permanente**, tout au
> plus un campement. Faune : meute de wargs, harpies d'hiver. Récoltes : fleur de lune des
> neiges, émeraude.

```
Illustration 11 sur 12 — le Pas de Givre.

Un col de haute montagne entre deux parois verticales qui se rapprochent : un couloir de
neige et de glace, en pente, balayé par un vent violent qui arrache des voiles de poudreuse
et les couche à l'horizontale. Des congères en vagues, des blocs de glace tombés, des
perches de balisage à demi ensevelies et penchées par le vent. Sur un rocher abrité, une
petite fleur pâle, presque blanche, ouverte malgré le froid. Dans la paroi, une veine verte
affleure, du vert froid d'une pierre précieuse.

Fin d'après-midi de tempête qui se lève, lumière filtrée et bleutée, visibilité qui se
referme au fond du col. Dominante : bleu glacier et blanc, roche noire mouillée. Tout, dans
l'image, doit dire qu'on ne peut pas s'arrêter ici.

Améthyste : aucune visible.

Au fond du col, très loin et à peine distinctes dans la neige soufflée, trois silhouettes
basses de bêtes qui avancent en file. Aucun personnage humain, aucun texte, aucune
interface.
```

---

### 4.12 Glacier du Silence — le bout du monde

> **Note de conception.** « Rien n'y pousse, rien n'y crie. Ce qu'on entend sous la glace n'a
> pas de nom, et il vaut mieux ne pas s'arrêter pour écouter. » Loi du temps : **arrêté
> net** — l'améthyste y est figée, bande haute mais quantité quasi nulle, et **uniquement sur
> ce qu'on dérange**. C'est le point le plus lointain de la série : l'image doit être la plus
> vide et la plus silencieuse des douze.

```
Illustration 12 sur 12 — le Glacier du Silence.

Un glacier mort, immense et immobile. Une étendue de glace bleu pâle striée de moraines
noires, fendue de crevasses profondes dont on ne voit pas le fond. Des séracs se dressent
comme des ruines. Rien ne pousse, aucun oiseau, aucune trace, aucune plante — sauf, dans une
faille abritée, une unique feuille sombre et coriace, et une petite fleur d'un rouge éteint,
absurdes à cet endroit.

Lumière de jour blanc sans soleil, ombres bleues, ciel bas et laiteux confondu avec la
glace. Dominante : bleu pâle et blanc, avec le noir des moraines. C'est l'image la plus vide
et la plus silencieuse de la série : beaucoup d'espace, très peu d'éléments.

Améthyste : rien en surface. Mais dans la paroi bleue d'une crevasse, sous plusieurs mètres
de glace, on distingue une forme violette nette et intense, prise là depuis très longtemps
et parfaitement conservée. Elle est enfermée, pas exposée.

Aucun personnage, aucune créature. Aucun texte, aucune interface.
```

## 5. Contrôler et itérer

Une image est bonne quand elle passe ces cinq contrôles :

1. **Le recadrage.** Masquer le tiers haut et le tiers bas : ce qui reste doit encore
   raconter le lieu. Sinon : *« recompose plus bas / plus haut, tout l'essentiel dans la
   bande centrale »*.
2. **L'améthyste.** Compter les points violets. Le générateur en ajoute spontanément
   partout, parce que « fantasy ». Or la quantité et la bande **disent quelque chose** —
   c'est la ligne de canon la plus facile à casser. Reprise type : *« retire tout le violet
   sauf X ; ici l'améthyste est rare et très pure »*.
3. **Le texte.** Les générateurs collent des enseignes, des panneaux et des signatures
   malgré la consigne. Reprise : *« supprime tout signe écrit, y compris les enseignes et la
   signature en bas »*.
4. **La série.** Poser les images côte à côte : si l'une a un style à part (trait plus dur,
   couleurs plus saturées, rendu 3D), la refaire en recollant la charte du §3.
5. **La lisibilité en petit.** Réduire à 640×192 : le lieu doit rester identifiable d'un coup
   d'œil. Les zones qui ratent ce test sont toujours celles où le générateur a mis trop de
   détails — demander *« moins d'éléments, des masses plus larges »*.

**Trois pièges connus**, tous vus sur la carte du monde :

- **Les biomes voisins se ressemblent.** Marais / Vallons et Dunes / Mer de Sel se
  confondent facilement. Les écarter par l'heure du jour et la dominante (le Marais est
  gris et plat, les Vallons dorés et bas ; les Dunes sont ocres et écrasées, la Mer de Sel
  blanche et aveuglante).
- **Le générateur peuple les zones vides.** Le Glacier, la Mer de Sel et la Crête doivent
  rester dépeuplés : il y ajoutera spontanément un randonneur ou une cabane. À retirer.
- **Le Fanal devient une cité.** Il grossit à chaque relance jusqu'à la métropole à
  cathédrale. Reprise : *« c'est un gros village, pas une ville : une vingtaine de toits
  autour d'une seule tour »*.
