# Prompt de génération d'image — carte du monde (World 1)

> À coller dans ChatGPT (ou tout générateur d'images) pour produire l'illustration de
> `/game/world-map`. Les données de placement viennent de `config/game/zones/world_1.yaml`
> (`map_x` / `map_y`, en % de 0 à 100, origine en **haut à gauche**) ; l'identité de chaque
> zone vient de [GAME_ZONES.md](GAME_ZONES.md) §2.

## 1. Contraintes techniques

Le gabarit d'affichage (`templates/game/zone/world_map.html.twig`) impose :

- **Format carré 1:1**, largeur utile 640 px (générer en 1024×1024 ou 2048×2048).
- Les **pastilles de zone et les libellés sont dessinés par l'application** par-dessus
  l'image, à la position exacte `map_x` / `map_y`. L'illustration destinée au jeu ne doit
  donc **ni pastilles ni texte** à ces emplacements — juste le décor.
- Palette du système de design « Parchemin » : papier `#fbf8f0`, encres `#26221c` /
  `#5d564a`, filets `#ddd2ba`, améthyste de marque `#5b2fb0`.

## 2. Table de placement (source de vérité)

| Zone | x % (gauche) | y % (haut) | Nature |
|---|---:|---:|---|
| Glacier du Silence | 80 | 6 | glacier mort, extension |
| Pas de Givre | 74 | 14 | col venteux, extension |
| Mines profondes | 72 | 36 | cœur industriel |
| Crête de Ventombre | 74 | 70 | pics, joaillerie |
| Forêt des murmures | 28 | 38 | zone-école |
| Quartier des Jardins | 42 | 46 | faubourg du hub |
| Village de Lumière | 50 | 55 | hub, sanctuaire |
| Vallons d'Aubépine | 38 | 60 | bocage agricole |
| Marais Brumeux | 32 | 74 | marécage d'alchimie |
| Dunes d'Ambre | 30 | 78 | désert fossile |
| Mer de Sel | 24 | 88 | croûte de sel |
| Cité Ensevelie | 18 | 96 | donjon terminal |

Routes à figurer (17 liaisons bidirectionnelles) : Village↔Jardins, Village↔Forêt,
Village↔Marais, Village↔Mines, Village↔Crête, Village↔Vallons, Vallons↔Forêt,
Vallons↔Marais, Forêt↔Marais, Forêt↔Mines, Mines↔Crête, Marais↔Crête, Marais↔Dunes,
Dunes↔Mer de Sel, Mer de Sel↔Cité Ensevelie, Crête↔Pas de Givre, Pas de Givre↔Glacier.

## 3. Prompt A — fond de carte pour le jeu (sans texte)

```
Crée une illustration carrée (1:1, 2048x2048) : une carte du monde fantasy dessinée à la
main sur du parchemin, style carte de jeu de rôle rétro, vue du dessus légèrement
isométrique. AUCUN texte, AUCUNE légende, AUCUN libellé, AUCUNE pastille ou marqueur de
lieu : uniquement le décor peint, les lieux seront étiquetés par-dessus plus tard.

Palette : parchemin crème (#fbf8f0), encre brune (#26221c), traits sépia (#5d564a), filets
beiges (#ddd2ba), et une seule couleur d'accent, un violet améthyste (#5b2fb0), employée
avec parcimonie pour les veines de cristal. Aquarelle légère et hachures à la plume, grain
de papier ancien, bords légèrement usés. Pas de style photoréaliste, pas de 3D moderne, pas
d'interface.

Compose le terrain en plaçant chaque lieu à la position indiquée (pourcentages depuis le
bord GAUCHE et depuis le bord HAUT de l'image) :

- 80% / 6% : un glacier mort et silencieux, glace bleu pâle, crevasses.
- 74% / 14% : un col enneigé battu par le vent, entre deux parois.
- 72% / 36% : l'entrée d'une mine profonde à flanc de montagne, terrils, chevalement de
  bois, fumées.
- 74% / 70% : une crête de pics déchiquetés balayés par le vent, strates de roche à nu.
- 28% / 38% : une forêt ancienne et dense, clairières, une rivière vive.
- 42% / 46% : un faubourg de jardins et de vergers, petits murets, parcelles cultivées.
- 50% / 55% : la ville centrale, un village fortifié autour d'un grand phare de pierre
  (le Fanal) dont le sommet luit d'une lumière chaude — le seul lieu vraiment lumineux de
  la carte, le point focal de la composition.
- 38% / 60% : un bocage vallonné, haies d'aubépine, prés, un vieux moulin en ruine, un gué.
- 32% / 74% : un marais brumeux, eaux stagnantes, arbres morts, nappes de brouillard.
- 30% / 78% : des dunes de sable ocre, ossements affleurants, résine d'ambre.
- 24% / 88% : une croûte de sel blanche et craquelée, plate à perte de vue.
- 18% / 96% : les toits d'une cité engloutie affleurant le sable, colonnes brisées.

Relie ces lieux par des chemins tracés en pointillés fins à l'encre sépia, comme des routes
de carte ancienne, en suivant exactement ces liaisons : ville centrale vers le faubourg de
jardins, vers la forêt, vers le marais, vers la mine, vers la crête, vers le bocage ;
bocage vers forêt et vers marais ; forêt vers marais et vers mine ; mine vers crête ;
marais vers crête et vers dunes ; dunes vers la croûte de sel ; croûte de sel vers la cité
engloutie ; crête vers le col enneigé ; col enneigé vers le glacier.

Le climat va du froid en haut à droite au désert aride en bas à gauche, avec les terres
tempérées et habitées au centre. Ajoute une rose des vents discrète dans un angle vide et
de fines hachures de relief. Laisse les zones sans lieu (coins en haut à gauche et en bas
à droite) en mer, en brume ou en parchemin nu.
```

## 4. Prompt B — variante affiche (avec les noms)

Pour un usage hors jeu (wiki, communication, écran de titre), reprendre le **prompt A** et
remplacer le premier paragraphe par :

```
Crée une illustration carrée (1:1, 2048x2048) : une carte du monde fantasy dessinée à la
main sur du parchemin, style carte de jeu de rôle rétro. Écris le nom de chaque lieu en
français, à la plume, dans une capitale ancienne lisible, posé JUSTE EN DESSOUS du lieu
sans le recouvrir. Ajoute un cartouche de titre en haut de la carte portant les mots
« Améthyste — Monde I ».
```

…puis, dans la liste des positions, accoler son nom à chaque lieu :
Glacier du Silence, Pas de Givre, Mines profondes, Crête de Ventombre, Forêt des murmures,
Quartier des Jardins, Village de Lumière, Vallons d'Aubépine, Marais Brumeux, Dunes
d'Ambre, Mer de Sel, Cité Ensevelie.

> **Attention** : les générateurs d'images écrivent mal les accents et les mots longs.
> Prévoir de reprendre les libellés en post-traitement, ou préférer le prompt A et laisser
> l'application poser le texte.

## 5. Itérer

Les modèles respectent les pourcentages **approximativement**. Si le placement dérive :

1. Corriger par relance ciblée : « garde la même image, mais déplace le marais plus bas à
   gauche, à 32 % depuis la gauche et 74 % depuis le haut ».
2. Vérifier en superposant l'image dans `/game/world-map` : les pastilles de l'application
   sont la référence, l'illustration doit s'y conformer, pas l'inverse.
3. Si un biome mange son voisin (fréquent entre Marais 32/74 et Dunes 30/78), demander
   explicitement une **frontière nette** : « une ligne franche entre le marais et les
   dunes, sans transition ».
