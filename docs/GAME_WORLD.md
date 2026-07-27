# Monde d'Améthyste — géographie, civilisation, politique, trame

> **Statut : proposition, à valider.** Juillet 2026.
> Socle de monde : le cadre dans lequel ajouter des zones, des factions, des saisons et du
> contenu économique sans se contredire. Ce document ne redéfinit aucune règle du projet :
> il fournit la **fiction qui les rend nécessaires**, et le **système territorial** qui les
> fait tenir ensemble.
>
> Complète : [GAME_PRINCIPLES.md](GAME_PRINCIPLES.md) (décisions D1→D14),
> [roadmap/PLAN_GUILD_CITY_CONTROL.md](roadmap/PLAN_GUILD_CITY_CONTROL.md) (contrôle de cité, livré),
> [roadmap/PLAN_PLAYER_ECONOMY.md](roadmap/PLAN_PLAYER_ECONOMY.md) (économie joueur),
> [GAME_ZONE_ACTIONS.md](GAME_ZONE_ACTIONS.md) (ce qu'on fait dans une zone).
>
> **Références assumées** : Ashes of Creation (nœuds, vassalité, régression), Albion Online
> (spécialisation régionale des ressources, marchés locaux, caravanes, pas de niveau global),
> FF6 / FF9 (l'industrialisation du merveilleux, la magie comme ressource qui s'épuise).
> Ce qui est repris de chacun est **désarmé de son PvP** : ici la compétition se joue par
> l'investissement et l'attention, jamais par les armes (règle 11).

---

## 0. L'idée en une page

Trois affirmations, dont tout le reste découle :

1. **Le temps vécu ne disparaît pas : il se dépose.** Ce qui a été fait longtemps, ou
   intensément, se tasse en une matière dure et translucide — **l'améthystite**. Le sol est
   de la mémoire tassée. La magie est un geste conservé dans un cristal. On n'invente pas un
   sort : on hérite de celui d'un mort.

2. **Là où les joueurs agissent, le monde s'épaissit.** Un lieu fréquenté accumule du
   sédiment, et cette épaisseur porte une civilisation : un campement devient hameau,
   bourg, cité. **Ce ne sont pas les développeurs qui décident où sont les villes.** Un lieu
   délaissé, lui, s'amincit et redevient sauvage.

3. **Le monde ne peut pas tout porter en même temps.** L'âge précédent est mort d'être trop
   souvenu : la terre montait, l'air durcissait, plus rien de neuf ne pouvait arriver. Depuis,
   il existe une limite dure au nombre de grandes cités qui peuvent coexister. **La croissance
   est donc structurellement compétitive** — et se joue par les œuvres, jamais par le sang.

La troisième est le cœur. Elle donne à un MMO **sans PvP** un enjeu de territoire réel :
on ne prend pas la cité du voisin, on fait monter la sienne en sachant qu'il n'y a pas de
place pour deux.

---

## 1. Vocabulaire canonique

À figer maintenant, il ruisselle partout ensuite.

| Terme | Sens |
|---|---|
| **Sédiment** | Le dépôt laissé par toute chose vécue. Invisible, omniprésent, mesurable. |
| **Améthystite** | Sédiment durci. Minerai, inerte, commerçable. *(Déjà dans les données : `spot-amethystite-*`.)* |
| **Matéria** | Améthystite où un geste est resté lisible. Ne se fabrique pas : se trouve, se taille, s'éveille. |
| **Veine** | Coulée d'améthystite sous une région. Elle lui donne son élément et son caractère. |
| **Accord** | Capacité d'entendre une famille de gestes. Ce que font pousser les arbres de talent. |
| **Foyer** | Le point d'une zone où le sédiment s'accumule. Ce qui devient — ou pas — une ville. **C'est le nœud.** |
| **Crue** | La loi qui limite le nombre de grandes cités. Nommée d'après ce qui a tué l'âge précédent. |
| **Reflux** | Le mouvement lent par lequel le monde reprend ce qu'il a déposé. |
| **Blanc** | Terre dont la mémoire a été reprise. Ni faune, ni flore, ni récolte, ni souvenir. |
| **Marée** | Respiration du cristal, tous les ~28 jours : une strate ancienne affleure. *(= une saison.)* |
| **Limpide** *(formel : sans-strate)* | Être sans passé propre, donc capable de porter celui des autres. **Le personnage joueur.** |

---

## 2. Ce que le postulat rend nécessaire

Un socle de monde ne vaut pas par sa poésie mais par ce qu'il **oblige**. Chaque règle
absolue du projet reçoit ici une raison interne — elle cesse d'être une contrainte technique
pour devenir une loi du monde.

| Règle du jeu | Raison dans le monde |
|---|---|
| **Pas de niveau global** (règle 6) | La puissance n'est pas une propriété de la personne : c'est ce qu'elle **porte**. Un Limpide vide est un Limpide faible. Il n'y a rien à faire monter. |
| **Sorts uniquement via matéria** (règles 9-10) | On ne peut pas inventer un geste. On ne peut qu'**hériter** de celui d'un mort, et seulement si on l'a en main. |
| **Compétences passives** (règle 9) | Un arbre n'enseigne pas un sort : il apprend à **entendre** une famille de gestes. Sans accord, la matéria reste une pierre muette. |
| **Pas de PvP** (règle 11) | Le **Serment** : *« le sang versé ne se dépose pas »*. Tuer un vivant retire sa durée du monde — le seul acte irréversible là où tout laisse une trace. Les cités s'y sont liées ; les Chevaliers le font tenir. |
| **Position = zone** (règle 7) | On ne se situe pas par des coordonnées mais par **la strate sur laquelle on se tient**. Une zone est une profondeur de temps. |
| **Économie joueur, plans = découvertes** (D1-D2) | L'améthystite se travaille à la main ou pas du tout. Un plan n'est pas une information, c'est un geste retrouvé : ça se trouve, ça ne s'achète pas. |
| **Objets liés au commanditaire** (D5) | Une pièce faite *pour quelqu'un* prend sa durée en se formant. La liaison est une propriété physique, pas une restriction. |
| **Marchés régionaux** (D13) | Un marché est la bouche d'un foyer. Il n'existe pas de marché « du monde », parce qu'il n'existe pas de lieu qui soit partout. |
| **Contrôle de cité par l'influence** (GCC) | Une cité n'appartient pas à qui la prend, mais à qui l'a **faite monter**. |
| **Saisons de 4 semaines** (D8-D12) | Le cristal respire. À chaque marée, une strate affleure et le monde redevient un mois durant ce qu'il a été. |
| **Codex** (D11) | Ce n'est pas un menu : c'est **la mémoire que les joueurs sauvent du Reflux**. Ce qui y est inscrit ne blanchit pas. |

---

## 3. Les foyers — le monde que les joueurs construisent

C'est le pilier neuf, et le plus structurant. Emprunt direct au système de nœuds d'Ashes of
Creation, **débarrassé de ses sièges** et rebranché sur des systèmes déjà livrés
(influence de guilde, saisons, marchés régionaux, artisanat).

### 3.1 Principe

Chaque zone possède un **foyer** : un endroit où l'on peut s'installer. Toute action de
joueur dans la zone y dépose du sédiment — combattre, récolter, crafter, explorer, rendre
une quête. Passé un seuil, le foyer **monte d'un rang** :

```
Ruine  →  Campement  →  Hameau  →  Bourg  →  Cité  →  Métropole
 (0)        (1)          (2)       (3)      (4)       (5)
```

Le rang du foyer détermine **ce que la zone offre** :

| Rang | Ce qui apparaît |
|---|---|
| **Campement** | Un PNJ, un feu, stockage minimal. Point de repos. |
| **Hameau** | Boutique T1 (plancher D1), premier atelier, quêtes locales. |
| **Bourg** | **Marché local** (le HV régional s'y ouvre), ateliers de palier 2, parcelles d'habitation, échoppes. |
| **Cité** | Ateliers de haut palier, banque, donjon de groupe accessible, quêtes de faction, étals loués. |
| **Métropole** | Éveil de matéria, plans de fin de jeu, boss de région, services uniques au monde. |

**Un foyer qui ne reçoit plus rien redescend.** Pas de siège, pas de destruction : de
l'oubli. C'est la version douce et PvE de la destruction de nœud — et c'est exactement le
Reflux à l'échelle d'un village.

**Règles de régression** *(arbitrage tranché)* — une régression fait mal, et la douleur doit
être bornée :

- **Annoncée une marée à l'avance.** Un foyer qui décroche entre en *étiage* : la guilde et
  les habitués sont prévenus, et la saison entière reste pour redresser.
- **On perd le rang, pas les investissements.** Les upgrades payés, les parcelles, les
  échoppes et les stocks survivent à la chute ; ils redeviennent actifs au retour du rang.
  Perdre un rang coûte des *services*, jamais du patrimoine.
- **La remontée est accélérée.** Un foyer qui a déjà été Cité remonte à Cité bien plus vite
  qu'il n'y est monté la première fois : la strate est encore là, il ne manque que du monde.

Le message est « ce lieu s'endort », jamais « vous avez perdu ». La comparaison utile est
Haven & Hearth / Wurm Online, où la dégradation est brutale et fait fuir : on garde le
principe, on retire la punition.

### 3.1 bis — Intégration au monde existant *(arbitrage tranché)*

Les PNJ, boutiques et ateliers déjà posés dans les zones **ne sont pas rétro-gatés**. On
n'enlève rien à personne : le système de foyer est **incrémental**.

- Toute zone déjà peuplée démarre à un **rang non nul** correspondant à ce qu'elle offre
  aujourd'hui (Forêt des murmures, Mines profondes… = Hameau ; les zones de l'Acte 4 =
  Campement).
- Seuls les **services nouveaux** — marché local, ateliers de haut palier, parcelles,
  étals, éveil de matéria, boss de région — sont gardés par le rang.
- Conséquence : le pilier territorial peut se livrer **par tranches**, sans jamais casser
  une zone existante ni migrer les fixtures de PNJ.

### 3.2 Le type d'un foyer se décide tout seul

Ce n'est pas un choix de menu : le foyer prend le caractère de **ce qu'on y a le plus fait**.
Quatre types, chacun branché sur un système existant :

| Type | Ce qui le fait naître | Ce qu'il donne |
|---|---|---|
| **Comptoir** | Récolte, artisanat, ventes | Meilleur marché, taxes réduites, caravanes, emplacements d'échoppe supplémentaires |
| **Bastion** | Combats, donjons, boss | Faune de palier supérieur, expéditions, services de combat, apparition de boss |
| **Athénée** | Exploration, prospection, quêtes, Codex | Plans et recettes, prospection assistée, cartes de zone, révélation des filons |
| **Sanctuaire** | Rites de marée, entrées de Codex, matéria | Éveil et purification de matéria, bénédictions saisonnières |

Deux zones voisines aux mêmes ressources deviennent donc **deux villes différentes** selon
qui les a fréquentées. C'est le premier vrai levier d'identité régionale, et il ne coûte
aucune écriture : il émerge de l'activité mesurée par le système d'influence déjà en place.

### 3.3 La Crue — pourquoi on ne peut pas tout avoir

> Le monde ne supporte qu'une quantité finie de temps accumulé.

Concrètement, un quota **par monde, indexé sur la population active** *(arbitrage tranché)* :
le monde grandit quand le serveur grandit, mais toujours moins vite que lui — c'est ce qui
maintient la tension quel que soit le nombre de joueurs, et ce qui évite qu'un petit serveur
soit figé à une seule grande ville.

| Rang | Quota de base | Indexation |
|---|---|---|
| Métropole | **1** | +1 par palier de population active |
| Cité | 3 | +1 par palier |
| Bourg | 6 | +2 par palier |
| Hameau | libre | — |

Un « palier de population active » se lit sur les joueurs actifs de la marée écoulée, pas sur
les comptes créés — un serveur qui se vide reperd son quota, et le monde se resserre comme il
s'était ouvert.

Et une **zone d'influence** : un foyer de rang N plafonne ses voisins directs au rang N-1.
Une Métropole fait de ses voisins des vassaux — ils gardent leur marché et leur identité,
mais leur croissance est bue par la grande ville.

Cette limite n'est pas un équilibrage arbitraire : c'est **la cause de la mort de l'âge
précédent**, inscrite dans la fiction avant d'être inscrite dans le code (§6.3). Le jeu
peut donc l'énoncer franchement au joueur, et elle en devient un sujet plutôt qu'une
frustration.

Ce qu'elle produit :

- **Un enjeu de territoire sans une seule attaque.** Deux guildes qui veulent une Métropole
  chacune ne peuvent pas : elles s'affrontent en faisant monter leur foyer, pas en abattant
  l'autre.
- **Un choix stratégique lisible.** Concentrer (une Métropole et des vassaux riches) ou
  répartir (trois Cités autonomes) sont deux politiques valables, aux économies différentes.
- **Une raison pour que le monde change.** Une Métropole qui s'effondre au bout de deux
  saisons libère le quota — et une autre région se réveille. Le monde d'un serveur d'un an
  ne ressemble pas à celui du premier jour.

### 3.4 Ce qui ne bouge jamais

**Le Village de Lumière n'est pas un foyer.** Il est bâti sur la Voûte ; rien n'y sédimente
ni ne s'y délite. Il ne monte pas, ne descend pas, n'appartient à personne, et garantit à
perpétuité le plancher T1 (D1) et les services de base. C'est la protection *cold-start* du
système : quoi qu'il arrive à la carte politique, un nouveau joueur trouve toujours une
boutique, un atelier et une quête. La région `sanctuaire-lumiere` est donc
`isContestable = false` **par nature**, pas par convention.

---

## 4. Géographie

### 4.1 La loi des biomes

> **Un biome n'est pas un climat : c'est une manière dont le temps s'est déposé là.**

| Ce qui est arrivé au temps | Sol obtenu | Zones existantes |
|---|---|---|
| Déposé **régulièrement** | Forêt, bocage, plaine vivante | Forêt des murmures, Quartier des Jardins |
| Déposé **très longtemps** | Roche, filons profonds | Mines profondes |
| **Stagnant**, jamais tassé | Marais, brume, hantises | Marais brumeux |
| **Arraché** par le vent | Crêtes, strates à nu, failles | Crête de Ventombre |
| **Épuisé** | Désert, sel, ambre | Dunes d'Ambre, Mer de Sel |
| **Arrêté net** | Glace, silence, choses conservées entières | Pas de Givre, Glacier du Silence |
| **Enseveli d'un coup** | Ruines intactes sous la surface | Cité ensevelie |
| **Retiré** | Le **Blanc** — absence | *(à venir)* |

Les onze zones livrées se rangent dans la grille **sans une retouche**. On formalise ce qui
existe, on n'impose pas une couche.

### 4.2 La forme du monde

Le graphe actuel — un centre et deux bras — est repris tel quel :

```
                          ❄ Glacier du Silence      le Silence — le temps arrêté
                          │
                          ❄ Pas de Givre
                          │
      ⛰ Crête ─── ⛏ Mines profondes                 Terres Sauvages — strates à nu
        │  ╲          ╱ │
   🜁 Marais ─── 🌲 Forêt des murmures                Plaines de l'Éveil — dépôt régulier
        │             ╲ │
        │      ✨ VILLAGE DE LUMIÈRE ─ 🏡 Jardins    Sanctuaire — sur la Voûte
        │
   🏜 Dunes d'Ambre  →  🧂 Mer de Sel  →  🏛 Cité ensevelie     l'Ambre — le temps épuisé
```

**Plus on s'éloigne de Lumière, plus la strate sous les pieds est ancienne et abîmée.** Le
bras nord monte vers ce qui s'est figé, le bras sud descend vers ce qui s'est tari. Les
deux finissent au bord du Blanc. C'est aussi la lecture du **palier de danger** : la
profondeur de strate *est* le tier de la zone, à la manière des anneaux d'Albion.

### 4.3 Les régions

Trois existent en base ; deux sont à formaliser (les bras de l'Acte 4 n'ont aucune région),
une est réservée.

| Région | Veine | Caractère | Zones | Contestable | Enjeu |
|---|---|---|---|---|---|
| **Sanctuaire de Lumière** | *aucune* (la Voûte) | Rien n'y dépose, rien n'y pourrit | Village de Lumière, Quartier des Jardins | Non | Siège de la Concorde, plancher T1 garanti |
| **Plaines de l'Éveil** | Bois / Bête, peu profonde | Dépôt récent, veine fine mais sûre | Forêt des murmures *(+ bocage, rivière à venir)* | Oui — 5 % | Le grenier. On s'y dispute la régularité. |
| **Terres Sauvages** | Métal, Eau/Ténèbre, Air | Strates ouvertes, veines épaisses | Mines profondes, Marais brumeux, Crête de Ventombre | Oui — 8 % | Cœur industriel : **c'est là que se joue la doctrine** (§6) |
| **L'Ambre** *(à créer)* | Feu / Terre, épuisée | Ancien fond de mer, temps fossilisé | Dunes d'Ambre, Mer de Sel, Cité ensevelie | Oui — 10 % | Les reliques de l'âge précédent |
| **Le Silence** *(à créer)* | Eau / Lumière, figée | Le temps s'y est arrêté en plein geste | Pas de Givre, Glacier du Silence | **Non** — 0 % | Aucun foyer ne tient : on y monte des **expéditions** |
| **Le Blanc** *(réservée)* | *néant* | Ce que le Reflux a repris | *(expansion)* | Sans objet | La frontière |

Deux propriétés de conception à garder :

- **Le Silence est riche mais insédimentable** : pas de foyer, donc pas de marché, pas de
  taxe, pas de banque. On y va en expédition et on repart avec sa cargaison sur le dos.
  Variation économique offerte par la fiction, gratuite en code.
- **L'Ambre a la taxe la plus forte et la distance la plus longue** : c'est là que
  l'arbitrage entre marchés régionaux (D13) rapporte le plus, et que les caravanes (§5.3)
  prennent leur sens.

### 4.4 Biomes de cristal — l'originalité, à décliner progressivement

Chacun est **une mécanique**, pas une couleur de fond :

| Biome | Ce qu'on y voit | Ce qu'il fait au jeu |
|---|---|---|
| **Affleurement** | Veine à ciel ouvert, bêtes prises dans le cristal | Améthystite brute, très haut rendement, **vitalité de filon fragile** — le levier de sur-extraction |
| **Blanc** | Rien. Ni couleur, ni bruit, ni faune | Biome **d'absence** : aucune récolte, aucun PNJ, seulement des Effacés. Peut **apparaître** en conséquence d'une saison |
| **Ambre** | Choses conservées en plein geste | Butin de l'âge précédent ; **toute récolte réveille ce qui était conservé** |
| **Chœur** | Deux veines croisées, deux lumières à contretemps | Synergie élémentaire forte, gain de matéria accru, météo instable |
| **Faille** | Un canyon où chaque assise est une époque | Donjon **vertical** : descendre = remonter le temps, une faune par palier |
| **Miroir** | Un lieu qui rejoue en boucle un jour ancien | Support des marées : le passé y est **jouable** pendant une fenêtre |

### 4.5 Grille d'anticipation

À ouvrir quand le contenu le demandera, par ordre de valeur :

| Biome | Apport | Élément | Priorité |
|---|---|---|---|
| Bocage / rivière (Plaines) | Comble le trou entre hub et forêt ; pêche, agriculture | Eau/Bois | **Le manque le plus criant** |
| Côte vivante / archipel | Vraie pêche, navigation, second grand foyer | Eau | Fort |
| Volcan / champ de scories | Forge de haut palier | Feu/Métal | Fort (l'Ambre le prépare) |
| Canopée / sylve ancienne | Verticalité, herboristerie haute, faune Bête | Bois/Bête | Moyen |
| Karst / grottes noyées | Donjons naturels | Eau/Ténèbre | Moyen |
| Steppe / badlands | Montures, caravanes longues | Terre/Air | Faible |
| Le Blanc | Frontière, endgame, expansion | *néant* | Réservé |

Rien à modéliser aujourd'hui. Ce qui compte : **chaque entrée sait déjà à quelle veine, quel
élément et quel métier elle se rattache.** L'ajout devient un fichier YAML, pas un débat.

---

## 5. Économie territoriale

C'est l'apport d'Albion, et il se branche sans effort sur ce qui est livré (marchés
régionaux, artisanat joueur, commandes de craft, échoppes).

### 5.1 Chaque biome nourrit une ligne de production

Une ressource ne se trouve pas « un peu partout » : elle appartient à une veine.

| Ligne | Biome source | Métier | Débouché |
|---|---|---|---|
| Bois & fibre | Forêt, bocage | Herboristerie, bûcheronnage | Armes légères, alchimie |
| Métal | Mines, faille | Minage, forge | Armures lourdes, armes |
| Pierre & gemme | Crête, sel | Minage, joaillerie | Sertissage, matéria |
| Cuir & os | Plaines, dunes | Dépeçage, tannerie | Armures légères |
| Eau & sel | Marais, mer, rivière | Pêche, alchimie | Consommables perpétuels |
| Améthystite | Affleurements | Prospection | **Matéria, seul intrant qui ne se substitue pas** |

**Aucun métier n'est autosuffisant** (D-WoW §4.6) et **aucune région n'a tout** : le commerce
entre régions n'est pas un bonus, c'est une nécessité.

### 5.2 Une ville est bonne à quelque chose de précis

Le foyer accorde un **bonus d'atelier** sur la ligne de sa veine et de son type. Une épée se
forge mieux à un Comptoir bâti sur du métal ; une potion se distille mieux à un Athénée du
marais. Concrètement : rendement de matière, chance de qualité supérieure, palier de recette
accessible.

Conséquence recherchée : **on voyage pour crafter**, les artisans s'installent où leur métier
est bon, et une guilde qui fait monter un foyer choisit littéralement le métier de sa région.
C'est le liant qui manquait entre le contrôle de cité (livré) et l'économie joueur (livrée) :
il ne demande qu'un multiplicateur lu depuis le foyer.

### 5.3 Caravanes — le transport comme contenu

Les marchés sont régionaux (D13) : la matière est au nord, la demande au sud. Le transport
est donc un métier. Version PvE de la caravane d'Albion :

- Une **cargaison** dépasse la capacité normale d'un sac : elle impose un voyage plus lent et
  interdit le voyage rapide.
- La route déclenche des **rencontres** (pillards, bêtes, événements de marée) : le risque
  est PvE, jamais un autre joueur.
- Une caravane de guilde se **convoie à plusieurs** — contenu coopératif nouveau, sans
  combat de groupe synchrone.
- Elle **dépose du sédiment** aux deux bouts : commercer fait monter les foyers.

C'est le gold sink et le sink de temps que réclame §4.7 de GAME_PRINCIPLES, sous une forme
qui produit du jeu au lieu d'une taxe.

### 5.4 Ce qu'on ne prend pas d'Albion

Le **full loot** et le PvP ouvert, évidemment (règle 11) — mais aussi l'**obsolescence par
saison** : ici l'équipement ne s'évapore pas à chaque marée. La demande de fond vient des
consommables perpétuels, de la réparation et de la croissance des foyers, pas d'un
effacement périodique du stock.

---

## 6. Politique

### 6.1 La Concorde et le Serment

Les cités ne forment pas un royaume mais une **Concorde**, tenue par un seul texte :

> *« Le sang versé ne se dépose pas. »*

On peut ruiner un rival, l'affamer, lui rafler sa veine — on ne le tue pas. Non par vertu,
mais parce qu'un mort emporte sa durée hors du monde, et que le monde en manque déjà.
**C'est la raison interne du « pas de PvP »**, et elle est plus forte qu'un « le jeu est
coopératif » : elle rend la coopération *politique*. L'**Ordre des Chevaliers** existe pour
faire tenir ce texte et rien d'autre — un ordre qui empêche la guerre sans avoir le droit
d'en faire une.

### 6.2 L'axe qui divise tout le monde

La question politique n'est pas qui règne, c'est **que fait-on du cristal ?**

```
                          PRÉSERVER
                              │
              Cercle des Mages│(Lecteurs)
                              │
    ORDRE ────────────────────┼──────────────────── LIBERTÉ
       Ordre des Chevaliers   │   Confrérie des Ombres
                              │
                   La Fonderie│  Guilde des Marchands
                              │
                          EXTRAIRE
```

- **La Fonderie** *(faction à créer)* — brûle le cristal pour en tirer de la force
  immédiate. Elle éclaire les cités, arme les caravanes, paie bien. Elle a raison à court
  terme, et c'est ce qui la rend redoutable : elle n'est pas méchante, elle est **utile**.
  Analogue assumé du Magitek de FF6, mais sans empire ni tyran — une industrie prospère et
  populaire.
- **Le Cercle des Mages — les Lecteurs** — une matéria fondue est un mort effacé deux fois.
  Ils veulent lire les veines, pas les fondre. Ils ont raison à long terme, et personne n'a
  le temps d'attendre le long terme.
- **La Guilde des Marchands** — vend aux deux, de préférence en même temps. Elle tient les
  prix, donc le monde.
- **La Confrérie des Ombres** — trafique la matéria volée et, pire, **la fausse** : un
  cristal où l'on a mis le mauvais geste. Une contrefaçon marche neuf fois et vous trahit à
  la dixième.
- **L'Ordre des Chevaliers** — ne prend pas parti sur la doctrine, seulement sur le sang. Ce
  qui, dans les faits, protège toujours celui qui exploite.

Les quatre factions existantes tiennent leur place **sans retouche** ; seule la Fonderie est
à ajouter, et c'est elle qui allume le conflit.

### 6.3 Où se branchent les guildes de joueurs

Le contrôle de cité (livré) prend un sens politique **sans une ligne de code neuve** : ce
qu'une guilde fait de son foyer la place sur l'axe.

- Investir dans l'**extraction** (rendement des filons, ateliers, taxes) : la région
  s'enrichit vite, la vitalité de ses veines baisse.
- Investir dans la **préservation** (Codex, expéditions, entretien, restauration) : moins de
  revenus immédiats, une région qui tient dans la durée.

La compétition reste **indirecte et PvE** : on ne combat pas la guilde d'en face, on constate
à la fin de la marée quel pays a mieux vieilli — et lequel a pris le quota de Crue. Le
**journal de monde** (NAR-07) grave le nom de celle qui gouvernait, en bien comme en mal.

---

## 7. La trame

### 7.1 Pitch

> Il y a mille ans, le monde a choisi d'oublier quelque chose pour survivre.
> Ce qu'il a oublié est enfermé dans une pierre violette, sous une ville qui porte son nom.
> Tout ce que les vivants appellent « magie » n'est que la poussière tombée de cette pierre.
> Et la poussière s'épuise.

### 7.2 Trois horizons (compatible D8/D9)

| Horizon | Contenu | Cadence | Support code |
|---|---|---|---|
| **Acte d'introduction** | *Le réveil du Limpide.* Un être sans passé ouvre les yeux à Lumière, rassemble quatre fragments, atteint la Voûte — et découvre qu'elle est **fermée de l'intérieur**. | Une fois par personnage | Arc `intro` + Actes 2-3 (livrés) |
| **Marées** | Un épisode par mois : une strate affleure, une menace monte, un climax se combat, une résolution s'inscrit. Résoluble, oubliable. | 4 semaines | `InfluenceSeason` + 4 `GameEvent` (livré) |
| **Le Reflux** | La ligne longue : le Blanc gagne, la Fonderie accélère, les foyers montent et tombent, la Voûte s'ouvrira. N'avance que par **basculements canon rares**. | Années | `is_canon` + journal de monde (livré) |

L'existant s'y range sans réécriture : les quatre fragments des Actes 2-3 (vert/forêt,
orange/mines, bleu-gris/marais, blanc/sommet) deviennent les **quatre clés de voûte**, une
par grande veine, et le *Gardien de la Convergence* cesse d'être un monstre gardant un
trésor — c'est **la serrure**, et elle a une opinion.

### 7.3 Le retournement (fin de méta-arc, à ne jamais livrer tôt)

L'Améthyste n'est pas la source de la magie. C'est un **coffre**.

L'âge précédent n'est pas mort d'une guerre : il est mort **d'être trop souvenu**. Tout se
déposait — chaque prière, chaque geste répété, chaque nom prononcé — la terre montait, l'air
durcissait, et plus rien de neuf ne pouvait arriver dans un monde saturé de son propre passé.
Ils ont fait la seule chose qui restait : ils ont pris **une existence** — la plus souvenue
de toutes, celle autour de laquelle tout le reste se déposait — l'ont mise dans une pierre,
puis ont oublié qu'elle avait existé. Le monde a redémarré, allégé.

Ce que les Actes 2-3 appellent « la vérité du cristal », c'est cela : **la magie que tout le
monde utilise est la fuite de ce coffre.** Chaque matéria est un éclat de la personne
enfermée ; chaque sort lancé est un fragment d'elle qui repart dans le monde. Le Reflux n'est
pas une maladie : c'est **la pierre qui se vide**.

Et la **Crue** — la loi qui plafonne les cités (§3.3) — n'est pas une règle d'équilibrage :
c'est la cicatrice de cette mort. Le monde s'interdit de recommencer. Un joueur qui bute sur
le quota bute sur le trauma fondateur de son monde.

D'où la question de fin, qui n'a pas de bonne réponse :

- **Ouvrir.** Rendre au monde tout ce qu'il a déposé. La magie redevient infinie, le Blanc
  recule — et le poids revient, et avec lui la raison qui avait fait fermer.
- **Refermer.** Sceller ce qui reste, accepter que la magie s'éteigne en une génération, et
  laisser un monde ordinaire à ceux qui viendront.
- **Répartir.** Ne plus garder la mémoire dans une pierre, mais **dans ce que les vivants ont
  bâti et écrit** — les foyers, le Codex, les faits canon des marées. Ce que les joueurs ont
  sauvé, le monde le garde ; ce qu'ils ont laissé blanchir, il le perd.

La troisième voie est la raison d'être du Codex (D11) *et* des foyers (§3) : **la
civilisation que les joueurs construisent est la réponse à la question de fin.** Aucune
branche narrative supplémentaire à écrire — l'issue reste unique, seul ce qui est *inscrit*
varie (compatible D10).

### 7.4 Les Effacés

Ce qui sort du Blanc n'a pas de forme propre, puisque plus rien ne se souvient de lui.
**Un Effacé prend le geste de qui le combat** : il vous rend vos matérias, une par une, un
tour plus tard. Bestiaire infiniment variable pour un seul comportement, lecture immédiate
(« il me refait mon propre sort »), et un contre-jeu qui récompense la diversité
d'équipement plutôt que la puissance brute. Réutilise `SpellApplicator` et
`MobActionHandler`, sans nouveau moteur. C'est l'ennemi générique du jeu, disponible pour
n'importe quelle marée et n'importe quelle zone qui blanchit.

---

## 8. La fabrique de marées

Une marée = **une strate qui affleure**. Le gabarit ne change jamais (amorce → montée →
climax → résolution, livré) ; seule la strate change.

| Marée | Ce qui remonte | Climax | Enjeu |
|---|---|---|---|
| **La Marée d'Ambre** | Un jour de l'âge précédent, dans les Dunes | Ce qui était conservé se réveille | Lecteurs contre Fondeurs sur le butin |
| **La Fonte** | L'été d'avant le gel, au Silence | Ce qui s'était arrêté reprend son geste | Une zone gelée devient temporairement exploitable |
| **Le Chœur** | Deux veines entrent en phase | Boss à double élément | Synergie élémentaire poussée à l'extrême |
| **Le Blanchiment** | *Rien ne remonte* — une zone se vide | Empêcher un foyer de tomber | **Conséquence directe** de la sur-extraction du mois précédent |
| **L'Appel de la Crue** | Le quota se libère | Faire monter un foyer avant les autres | Course de nœuds entre guildes, sans un coup échangé |
| **La Contrefaçon** | Les Ombres inondent le marché de fausses matérias | Remonter à l'atelier | Marée **économique** : le HV est le terrain |

Deux propriétés à tenir : environ une marée sur trois est **canon** (D9/D12) ; et les marées
« conséquence » (*Blanchiment*, *Appel de la Crue*) doivent être **déclenchées par ce que les
joueurs ont fait le mois d'avant**. C'est ce qui fait une boucle plutôt qu'un calendrier.

---

## 9. Impact sur le modèle

Par ordre d'effort croissant. Les points 1 à 4 rendent le monde lisible **sans changer une
seule mécanique** ; le pilier des foyers (5 à 8) est un chantier de roadmap à part entière.

| # | Ajout | Nature |
|---|---|---|
| 1 | Régions `ambre` et `silence` + rattachement des zones de l'Acte 4 | Donnée (`RegionFixtures`) |
| 2 | Faction **la Fonderie** + récompenses | Donnée (`FactionFixtures`) |
| 3 | Entrées de Codex : régions, factions, vocabulaire (sédiment, veine, Crue, Blanc, Limpide) | Donnée (`CodexEntryFixtures`) |
| 4 | `Region.veine` (élément dominant) | Une colonne |
| 5 | **`Settlement`** : rang, type, sédiment accumulé, `Zone` (1-1) | Entité + migration |
| 6 | Dépôt de sédiment : brancher sur les events déjà écoutés par `InfluenceListener` | Un subscriber de plus, aucun event neuf |
| 7 | Déblocage par rang : services, ateliers, marché, parcelles gardés par le rang du foyer | Gardes dans les contrôleurs existants |
| 8 | Crue : quotas + zone d'influence + régression par marée d'inactivité | Logique au tick de saison |
| 9 | Bonus d'atelier par foyer (ligne de production × type) | Un multiplicateur lu au craft |
| 10 | Caravanes : cargaison, voyage lent, rencontres de route | S'appuie sur le graphe de voyage |
| 11 | Biome **Blanc** (`Zone.isBlanched`) + famille **Effacés** | Un booléen + un comportement de mob |

Le point 6 est le plus important à noter : **le système d'influence de guilde mesure déjà
exactement ce dont les foyers ont besoin** (kills, craft, récolte, pêche, quêtes, défis). Le
pilier territorial ne demande pas d'instrumenter le jeu — il demande de brancher un second
consommateur sur des événements déjà émis.

---

## 10. Arbitrages à trancher

### Tranchés (2026-07-27)

| # | Question | Décision |
|---|---|---|
| A | Les foyers remplacent-ils les villes en dur ? | **Non — incrémental.** Rien n'est rétro-gaté ; les zones peuplées démarrent à un rang non nul, seuls les services nouveaux dépendent du rang (§3.1 bis). |
| B | Quota de Crue par monde ou par région ? | **Par monde, indexé sur la population active** (§3.3). |
| C | La régression est-elle une punition ? | **Bornée** : annonce une marée à l'avance, perte du rang mais pas des investissements, remontée accélérée (§3.1). |

### Encore ouverts

1. **Le nom du « Blanc »** — se confond avec le fragment blanc du Sommet (Acte 2).
   Alternatives : *la Page*, *l'Étale*, *le Retiré*.
2. **La Fonderie : faction jouable ou force lointaine ?** Recommandation : **jouable**. Un
   antagoniste chez qui on fait ses courses vaut mille fois mieux qu'un antagoniste qu'on ne
   fréquente jamais.
3. **La sur-extraction blanchit-elle vraiment une zone ?** Superbe en fiction, risqué en jeu.
   Piste retenue de Wakfu (cf. [GAME_INSPIRATIONS.md](GAME_INSPIRATIONS.md)) : réversible, et
   **la restauration se paie au trésor de guilde** — la taxe finance la remise en état de la
   veine. La sanction devient une dépense, pas une perte sèche.
4. **Qualité variable de l'améthystite ?** (idée SWG, cf. GAME_INSPIRATIONS §3) — donnerait
   au HV une profondeur qu'un tas fongible n'a pas, et un métier réel au prospecteur.
5. **Quand ouvrir la Voûte ?** Le retournement ne vaut que gardé : aucun contenu ne s'en
   approche avant des dizaines de marées, et il n'est jamais « joué » — il est *inscrit*,
   comme un fait canon.

---

## 11. Ce qui est figé, ce qui reste ouvert

**Figé** — le postulat (§0-1), la correspondance règle ↔ fiction (§2), le principe des
foyers et de la Crue (§3), la loi des biomes (§4.1), la spécialisation régionale (§5), le
Serment et l'axe doctrinal (§6), les trois horizons (§7).

**Ouvert** — toutes les zones, toutes les créatures, tous les PNJ, les seuils numériques des
foyers, le calendrier des marées, la date du retournement.

C'est délibéré : le monde est un **cadre de production**, pas un roman. Ajouter une zone ne
demande que trois réponses :

> *Quelle veine ? Comment le temps s'y est-il déposé ? Que peut-on y bâtir ?*
