# Monde d'Améthyste — géographie, civilisation, politique, trame

> **Statut : socle adopté.** Juillet 2026 — décisions **A → F** actées le 2026-07-27 (§10).
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
   intensément, se tasse en une matière dure et translucide — **l'améthyste**. Le sol est
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

> Ces trois affirmations sont la version **racontée** du principe fondateur du jeu — *on
> est et on devient ce qu'on fait* ([GAME_PRINCIPLES.md](GAME_PRINCIPLES.md) §0). La
> mécanique et la fiction disent la même chose, à toutes les échelles : le personnage
> (matéria, arbres), le foyer (sédiment), le serveur (Répertoire).

---

## 1. Vocabulaire canonique

À figer maintenant, il ruisselle partout ensuite.

> **Loi de nommage** *(actée le 2026-07-28)* : **aucun nom propre** (ville, faction,
> zone, région, PNJ majeur) **ne réutilise un nom d'élément** (Feu, Eau, Terre, Air,
> Métal, Bête, Lumière, Ténèbres), **d'hybride réservé** (Blizzard, Magma, Éclipse…, §2.2)
> **ni de bande de pureté** (Trouble, Clair, Pur, Parfait). L'élément dark se dit
> **Ténèbres**, jamais « Ombre ». Renommages appliqués en conséquence : le **Village de
> Lumière** devient **le Fanal** (sa description l'appelait déjà « un fanal dressé contre
> les ténèbres »), la région **Sanctuaire de Lumière** devient **Sanctuaire de la Voûte**,
> la **Confrérie des Ombres** devient la **Confrérie des Ruelles**, sa zone d'Exalté est
> **la Cour des Miracles**. Les mots composés restent tolérés (Crête de Ventombre). Les
> **slugs de code** (`village-de-lumiere`, faction `ombres`, commentaire « Ombre » des
> domaines dark) sont hérités — à migrer en session code ; les documents **historiques**
> (`ROADMAP_DONE.md`, archives) gardent les anciens noms.

| Terme | Sens |
|---|---|
| **Sédiment** | Le dépôt laissé par toute chose vécue. Invisible, omniprésent, mesurable. |
| **Améthyste** | Sédiment durci. Minerai, inerte, commerçable. La ressource porte le nom du monde parce que c'est **la même matière** que le grand cristal — seule l'échelle change. Le cristal sous le Fanal se dit toujours avec la majuscule et l'article : **l'Améthyste**, ou le Cristal d'Améthyste. *(Slug hérité : `ore-amethyst-crystal`, spots `spot-amethystite-*` — à renommer avec le contenu de zone.)* |
| **Matéria** | Améthyste où un geste est resté lisible. **Trois verbes distincts, à ne jamais confondre** (§2.1) : on la **trouve** (voie normale et abondante), on l'**accorde** (nœud d'arbre qui apprend à s'en servir), on l'**éveille** (création d'une matéria neuve — voie rare et tardive). |
| **Veine** | Coulée d'améthyste sous une région. Elle lui donne son élément et son caractère. |
| **Accord** | Capacité d'entendre une famille de gestes. Ce que font pousser les arbres de talent. |
| **Foyer** | Le point d'une zone où le sédiment s'accumule. Ce qui devient — ou pas — une ville. **C'est le nœud.** |
| **Crue** | La loi qui limite le nombre de grandes cités. Nommée d'après ce qui a tué l'âge précédent. |
| **Reflux** | Le mouvement lent par lequel le monde reprend ce qu'il a déposé. |
| **Pâleur** | État d'une terre sur-exploitée : elle rend moins, et plus trouble. Graduel, borné, réversible. |
| **l'Étale** | Le lieu où la marée n'est jamais revenue. Terre dont la mémoire a été reprise pour de bon — ni faune, ni flore, ni récolte, ni souvenir. Ancien : **aucun vivant ne l'a causé**. |
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
| **Économie joueur, plans = découvertes** (D1-D2) | L'améthyste se travaille à la main ou pas du tout. Un plan n'est pas une information, c'est un geste retrouvé : ça se trouve, ça ne s'achète pas. |
| **Objets liés au commanditaire** (D5) | Une pièce faite *pour quelqu'un* prend sa durée en se formant. La liaison est une propriété physique, pas une restriction. |
| **Marchés régionaux** (D13) | Un marché est la bouche d'un foyer. Il n'existe pas de marché « du monde », parce qu'il n'existe pas de lieu qui soit partout. |
| **Contrôle de cité par l'influence** (GCC) | Une cité n'appartient pas à qui la prend, mais à qui l'a **faite monter**. |
| **Saisons de 4 semaines** (D8-D12) | Le cristal respire. À chaque marée, une strate affleure et le monde redevient un mois durant ce qu'il a été. |
| **Codex** (D11) | Ce n'est pas un menu : c'est **la mémoire que les joueurs sauvent du Reflux**. Ce qui y est inscrit ne pâlit pas. |

### 2.1 La matéria — abondante à la base, rare au sommet

**Règle de conception, au même rang que les règles absolues.** La matéria est la **seule
source d'actions de combat** (règle 10, héritage de FF7) : sans elle, un personnage n'a que
l'attaque de base de son arme. Elle n'est donc pas un objet de collection, c'est **le build
du personnage**. Il en découle une contrainte que rien ne doit contredire :

> **Un joueur qui se spécialise dans le feu doit avoir ses premières matéria de feu au
> premier jour, pas à la première semaine.** Une matéria de base rare, c'est un personnage
> sans actions — c'est-à-dire pas de jeu.

**Les trois verbes, et ce que chacun gate :**

| Verbe | Ce que c'est | Rareté |
|---|---|---|
| **Trouver** | La matéria existe déjà dans le monde : butin de créature, coffre, récompense, marché | **Abondante.** Voie normale, dès le jour 1 |
| **Accorder** | Apprendre à s'en servir — le nœud d'arbre (`materia.unlock`) | **Gratuit et précoce.** Les nœuds d'entrée d'un domaine sont à 0 point |
| **Éveiller** | Créer une matéria **neuve** à partir d'améthyste *Parfaite* | **Rare et tardif.** Service de grande cité, matéria de haut palier |

Le jeu livré respecte déjà cette règle : 69 matéria tombent des créatures à 4-10 %, et l'arbre
de Pyromancie ouvre **deux matéria de feu à 0 point requis**. Ce document ne l'invente pas —
il la **fige**, pour qu'on ne la casse pas en la resserrant plus tard.

**Où porte la rareté, alors ?** Sur trois choses, jamais sur le sort de base :

1. **Les matéria de haut palier** — les gestes rares, les variantes puissantes.
2. **Les emplacements de sertissage** — l'équipement de haut niveau offre **plus
   d'emplacements** et de **meilleurs bonus sur les mêmes matéria** que l'équipement commun.
3. **L'éveil** — fabriquer une matéria neuve reste un acte exceptionnel.

D'où le principe qui en découle, et qui vaut d'être tenu :

> **On ne progresse pas en changeant de sort, on progresse en le portant mieux.**

**Pas d'évolution sur place — mais une maturation, et une fusion.** Une matéria ne monte pas
de niveau : le geste qu'elle contient est figé, c'est toute la fiction (§1). Ce qui existe,
et que le code livré porte déjà :

- **La maturation.** Une matéria sertie accumule de l'expérience en combat
  (`MateriaXpGranter`, vivant — +25 % si l'élément du porteur s'accorde). Cette expérience
  n'améliore pas le sort : elle mesure combien le geste a été *porté*. Son seul débouché est
  la fusion, qui l'additionne.
- **La fusion** (`MateriaFusionManager` — **écrit mais jamais branché** : aucun contrôleur ne
  l'appelle, les joueurs n'y ont pas accès). Deux voies déjà codées :
  - **même élément** : deux matérias fondues donnent la matéria du **palier supérieur** — ce
    qui est exactement « une matéria plus puissante », pas une évolution sur place ;
  - **éléments croisés** : **14 combinaisons** définies (feu+air → Inferno, eau+air →
    Blizzard, lumière+ténèbre → Éclipse, terre+feu → Magma…), qui produisent des matérias
    **hybrides** n'appartenant à aucun des huit éléments de base.

En fiction, la fusion ne contredit pas « on n'invente pas un geste » : deux gestes déposés
l'un sur l'autre **se tassent en un seul** — c'est de la sédimentation, pas de l'invention.

**Ce qu'est l'éveil — concrètement.** L'éveil ne crée **pas de nouveau sort** : il produit
un **exemplaire neuf d'une matéria du catalogue existant**, choisie par le joueur. C'est un
**craft de matéria**, ni plus ni moins — le seul du jeu, puisque la matéria ne se fabrique
pas autrement (elle se trouve). Déroulé en jeu :

1. **Le service.** Une Métropole (rang 5) ouvre un atelier de plus : l'**Autel d'éveil** —
   même modèle qu'une forge ou un laboratoire, un écran de craft.
2. **Les intrants.** Le joueur apporte : des lots d'améthyste **Parfaite** (l'ingrédient —
   plusieurs lots pour une matéria haute), des gils (le coût du rite), et du temps réel
   (time-gating, comme `craftingTime` — un éveil se compte en heures ou en jours).
3. **Le choix.** L'écran liste les matérias éveillables : celles dont l'**élément**
   correspond à la **provenance** des lots apportés (de l'améthyste Parfaite des Mines ouvre
   la liste métal/terre, de la Forêt la liste bois/feu). Le palier accessible dépend du
   nombre de lots. On choisit **une** matéria dans la liste — c'est tout l'intérêt.
4. **Le résultat.** À la fin du délai, l'objet matéria (le même `Item` que celui qui aurait
   pu tomber en butin) est dans l'inventaire. Il se sertit, s'utilise, se fond ou se lit
   comme n'importe quel autre. Pour s'en servir, il faut toujours l'**accord** de l'arbre —
   l'éveil ne dispense de rien.

**À quoi ça sert, en une phrase :** passer de *« la matéria que le monde veut bien te
donner »* (le butin, 4-10 % de chance) à *« la matéria que tu as choisie »*. C'est la seule
production **délibérée** de matéria du jeu — le contre-hasard de l'endgame. Effets
économiques : un vrai débouché au Parfait, et un **plafond de prix naturel** pour les
matérias rares au marché (personne ne paie plus cher que le coût d'un éveil).

S'il était banal, il remplacerait le butin au lieu de le couronner — d'où son coût, son délai
et son gate de Métropole. Et la fiction reste droite : le Limpide ne crée pas le geste, il
**réveille celui d'un mort** resté lisible dans le Parfait — un héritage, jamais une
invention.

**L'échelle d'usage — chaque bande sert quelque chose.** L'améthyste n'est pas un trophée :
c'est le sang du système matéria, et chaque bande de pureté (§5.4) alimente un étage du build :

| Bande | Usage | Qui la consomme |
|---|---|---|
| **Trouble** | **Fondue** → essence (réparations, entretien de foyer, accélérations) | La Fonderie — le « pétrole » du monde |
| **Claire** | **Enchantements et consommables** (le système `Enchantment` existe, elle s'ajoute à ses ingrédients) | Alchimiste, joaillier |
| **Pure** | **Le sertissage** : tailler, ajouter et améliorer les **emplacements de matéria** sur l'équipement | **Le joaillier** — le métier du build |
| **Parfaite** | **L'éveil** (ci-dessus) | Rite de Métropole |

Un usage quotidien dès le jour 1 (fondre du Trouble), un débouché à chaque niveau de jeu, et
le choix fondre/lire (§12.2) qui prend une gradation naturelle : fondre du Trouble est banal,
fondre du Pur fait mal, fondre du Parfait est un acte politique.

**Le sertissage d'un objet lié — la commande de service.** Le joaillier travaille sur des
pièces qui appartiennent à d'autres, y compris des pièces **liées** (bind on pickup). Aucun
transfert de propriété n'est nécessaire : le canal des **commandes de craft** (ECO Piste C,
livré) s'étend aux **commandes de service** — le client place *sa* pièce en escrow avec la
commission et l'améthyste Pure, l'artisan exécute le travail, la pièce **revient à son
propriétaire**, améliorée. L'objet ne change jamais de mains au sens de la liaison :
l'escrow système le tient, l'artisan ne peut ni l'équiper ni le garder, et la liaison n'est
jamais violée. En fiction : la pièce est liée parce qu'elle a pris la durée de son porteur —
le joaillier ne la possède pas, il **travaille dessus**. (Jalon : ECO-28.)

**Réserve d'extension — les domaines hybrides.** Les 14 fusions croisées sont la porte des
extensions : chaque hybride (Magma, Éclipse…) peut ouvrir à terme un **domaine** propre, avec
son arbre et ses accords, au-delà des huit éléments de base. Rien n'est à construire
aujourd'hui ; il faut seulement **ne pas fermer la porte** — l'enum `Element` et le format
des domaines doivent tolérer des éléments composés le jour venu. La fusion elle-même est un
**contenu d'extension**, pas de lancement : au lancement, la progression du build passe par
les paliers et le sertissage, pas par la fusion.

La boule de feu du jour 1 reste utile au mois 6 : ce qui a changé, c'est le nombre
d'emplacements, la qualité du support et les bonus qui l'entourent. Cela évite d'obsolescer
la matéria fétiche d'un joueur — le défaut qu'on refuse déjà pour l'équipement
([GAME_INSPIRATIONS.md](GAME_INSPIRATIONS.md) §5).

### 2.2 La roue des domaines — des flux, pas des écoles *(nuance actée le 2026-07-28)*

Les huit domaines élémentaires ne sont pas des écoles de magie abstraites : ce sont des
**flux naturels qui traversent tout le monde** et se matérialisent partout. Chacun a une
histoire, des affinités, des opposés, une vie propre — et tous remontent à l'améthyste :

> **Le temps vécu est le substrat ; les huit flux en sont les couleurs.** L'améthyste est
> le dépôt neutre du temps vécu (§1) ; feu, eau, terre, air, métal, bête, lumière et
> ténèbre sont les **manières dont ce temps s'exprime** quand il circule encore. La
> matéria est la cristallisation la plus pure d'un flux ; l'équipement, les
> spécialisations de métier et les ressources en sont des matérialisations mineures.
> C'est *pourquoi* on trouve de l'améthyste partout — et pourquoi tout le reste doit se
> rattacher à un flux.

**La roue.** Quatre paires d'opposés structurent les huit flux :

| Opposition | Lecture |
|---|---|
| Feu ↔ Eau | ce qui consume ↔ ce qui dissout |
| Terre ↔ Air | ce qui pèse ↔ ce qui s'arrache |
| Lumière ↔ Ténèbres | ce qui révèle ↔ ce qui recouvre |
| Métal ↔ Bête | l'inerte façonné ↔ le vivant sauvage |

**Les hybrides sont anticipés, pas construits.** La table de fusion dormante
(`MateriaFusionManager`, §2.1) écrit déjà la suite : air + eau = **Blizzard** (la glace),
terre + feu = **Magma** (la lave), et douze autres. Chaque hybride peut devenir à terme un
domaine propre (réserve d'extension, §2.1). Rien à construire au lancement — seulement ne
pas fermer la porte.

**La fusion d'opposés est l'exception, et c'est un canon.** La table dormante le dit
d'elle-même : lumière + ténèbre existe (l'Éclipse — et elle **coûte**, le lanceur y laisse
du sang, c'est déjà codé), terre + air existe (la Tempête de sable, instable) — mais
**feu + eau et métal + bête n'y figurent pas**. Consigné comme lore : ces deux fusions
sont des **gestes perdus par excellence**, que seul le Répertoire d'éveil (§12.3)
pourra retrouver, au prix de conditions rares. On ne comble pas ce trou de données : on
le raconte.

**Conséquence sur les ressources** : toute ressource de récolte est la matérialisation
mineure d'un flux, et porte donc une **affinité de domaine** — dérivée de la signature de
sa zone, pas d'une table arbitraire. Application et modèle :
[GAME_ZONES.md](GAME_ZONES.md) §3 ter, jalon ZON-36. Les systèmes la consommeront
progressivement (héritage au craft, cuisine à buffs élémentaires, intrants de fusion,
orientation des lectures) — la donnée se pose maintenant, les effets attendront leur jalon.

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
| **Métropole** | **Éveil** de matéria (création de matéria neuve — *pas* leur usage, cf. §2.1), plans de fin de jeu, boss de région, services uniques au monde. |

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
  étals, **éveil** de matéria (création, jamais usage — §2.1), boss de région — sont gardés par le rang.
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

**Le Fanal (le village du hub) n'est pas un foyer.** Il est bâti sur la Voûte ; rien n'y sédimente
ni ne s'y délite. Il ne monte pas, ne descend pas, n'appartient à personne, et **n'occupe
aucune place dans la Crue**. C'est la protection *cold-start* du système : quoi qu'il arrive
à la carte politique, un nouveau joueur trouve toujours une boutique, un atelier et une
quête. La région `sanctuaire-lumiere` est donc `isContestable = false` **par nature**, pas
par convention. Le Quartier des Jardins est son faubourg, même régime.

> **Règle : le Fanal garantit le plancher, jamais le plafond.**
>
> Si le hub faisait tout, gratuitement et sans risque, personne ne bâtirait rien. Le Fanal
> offre donc le T1 complet, un atelier de base, la banque et les quêtes d'intro — de quoi
> n'être jamais bloqué. Elle n'offre **pas** de marché de haut palier, **pas** d'ateliers
> avancés, **pas** d'éveil de matéria. Ces services n'existent que dans les villes que les
> joueurs ont fait pousser. Le hub est un filet de sécurité, pas une destination.

### 3.5 Pression et régénération — personne n'a besoin de partir

Une erreur de conception à ne pas refaire : **il ne doit jamais exister de « mise en
jachère »**, c'est-à-dire de stratégie qui demanderait à un serveur entier de s'abstenir
collectivement d'aller quelque part. Aucune population de MMO ne coordonne ça, et le premier
nouveau venu qui passe « casse » l'effort des autres — recette parfaite pour la friction
sociale d'Eco (cf. [GAME_INSPIRATIONS.md](GAME_INSPIRATIONS.md) §2.3).

**La régénération n'est pas une phase, c'est un débit permanent.** C'est déjà le modèle du
calibrage des filons (cf. `config/game/zones/world_1.yaml` et
[GAME_ZONE_ACTIONS.md](GAME_ZONE_ACTIONS.md) §6.6) : chaque filon rend `R = capacity × 3600 /
respawn_seconds` unités par heure, en continu, et `capacity` n'est qu'un tampon.

| Pression d'extraction | Ce qui se passe |
|---|---|
| **Sous le débit** | La zone se régénère **pendant qu'on y joue**. Personne n'a besoin de partir. |
| **Au-dessus, ponctuellement** | Le tampon absorbe. Rendement en baisse, retour à la normale tout seul. |
| **Au-dessus, durablement, sur les mêmes filons** | La **Pâleur** s'installe (§12.1). |

Conséquence décisive, qui se lit dans les chiffres du calibrage : un filon T1 soutient
**48 récolteurs réguliers**. Quarante débutants dispersés sur six filons ne feront jamais
pâlir une forêt — c'est mécaniquement hors de portée.

> **La Pâleur est une conséquence du succès, jamais du passage.**

Seule une organisation qui industrialise **le même filon** pendant des semaines y parvient.
Personne ne pourra donc jamais dire « les débutants ont gâché ma région » : c'est faux par
construction. Corollaire d'implémentation : **la Pâleur se calcule par filon**
(`ZoneVein`), pas par zone — l'agrégat de zone n'est qu'un affichage.

**Ce que les joueurs peuvent faire** — quatre leviers, tous **unilatéraux**, aucun ne
demandant l'accord de qui que ce soit :

1. **S'étaler.** La vitalité d'un filon est partagée : à plusieurs dessus, le rendement de
   chacun baisse. Aller sur le filon voisin est déjà l'intérêt personnel.
2. **Investir.** Atelier de la Fonderie (+extraction, +Pâleur) ou des Lecteurs (−Pâleur).
3. **Payer.** Restauration au trésor de guilde (§12.1).
4. **Ouvrir ailleurs.** La réponse à une région saturée n'est pas la rotation, c'est
   l'**expansion** — aller faire pousser un foyer là où personne n'est.

Principe de conception à tenir partout : **ne jamais exiger de coordination ; aligner
l'intérêt individuel sur le résultat collectif.**

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
| **Retiré** | **l'Étale** — absence | *(à venir)* |

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

**Plus on s'éloigne du Fanal, plus la strate sous les pieds est ancienne et abîmée.** Le
bras nord monte vers ce qui s'est figé, le bras sud descend vers ce qui s'est tari. Les
deux finissent au bord de l'Étale. C'est aussi la lecture du **palier de danger** : la
profondeur de strate *est* le tier de la zone, à la manière des anneaux d'Albion.

### 4.3 Les régions

Trois existent en base ; deux sont à formaliser (les bras de l'Acte 4 n'ont aucune région),
une est réservée.

| Région | Veine | Caractère | Zones | Contestable | Enjeu |
|---|---|---|---|---|---|
| **Sanctuaire de la Voûte** | *aucune* (la Voûte) | Rien n'y dépose, rien n'y pourrit | le Fanal, Quartier des Jardins | Non | Siège de la Concorde, plancher T1 garanti |
| **Plaines de l'Éveil** | Bois / Bête, peu profonde | Dépôt récent, veine fine mais sûre | Forêt des murmures *(+ bocage, rivière à venir)* | Oui — 5 % | Le grenier. On s'y dispute la régularité. |
| **Terres Sauvages** | Métal, Eau/Ténèbres, Air | Strates ouvertes, veines épaisses | Mines profondes, Marais brumeux, Crête de Ventombre | Oui — 8 % | Cœur industriel : **c'est là que se joue la doctrine** (§6) |
| **L'Ambre** *(à créer)* | Feu / Terre, épuisée | Ancien fond de mer, temps fossilisé | Dunes d'Ambre, Mer de Sel, Cité ensevelie | Oui — 10 % | Les reliques de l'âge précédent |
| **Le Silence** *(à créer)* | Eau / Lumière, figée | Le temps s'y est arrêté en plein geste | Pas de Givre, Glacier du Silence | **Non** — 0 % | Aucun foyer ne tient : on y monte des **expéditions** |
| **L'Étale** *(réservée)* | *néant* | Ce que le Reflux a repris | *(expansion)* | Sans objet | La frontière |

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
| **Affleurement** | Veine à ciel ouvert, bêtes prises dans le cristal | Améthyste brute, très haut rendement, **vitalité de filon fragile** — le levier de sur-extraction |
| **Étale** | Rien. Ni couleur, ni bruit, ni faune | Biome **d'absence** : aucune récolte, aucun PNJ, aucun foyer — seulement des Effacés. On n'y récolte pas, **on y retrouve** (§12.1) |
| **Zone pâlie** | Les couleurs se délavent, les filons rendent trouble | **État**, pas biome : superposable à n'importe quelle zone, réversible contre paiement au trésor (§12.1) |
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
| Karst / grottes noyées | Donjons naturels | Eau/Ténèbres | Moyen |
| Steppe / badlands | Montures, caravanes longues | Terre/Air | Faible |
| L'Étale | Frontière, endgame, expansion | *néant* | Réservé |

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
| Bois & fibre | Forêt, bocage, Vallons | Bûcheronnage, charpenterie ; le lin → tailleur | Arcs et bâtons, housing, armures tissu |
| Métal | Mines, faille | Minage, forge | Armures lourdes, armes |
| Pierre & gemme | Crête, sel | Minage, joaillerie | Sertissage, matéria |
| Cuir & os | Plaines, dunes | Dépeçage, tannerie | Armures légères |
| Eau & sel | Marais, mer, rivière | Pêche, cuisine, alchimie | Consommables perpétuels, nourriture à effets |
| Améthyste | Affleurements | Prospection | **Matéria, seul intrant qui ne se substitue pas** |

**Aucun métier n'est autosuffisant** (D-WoW §4.6) et **aucune région n'a tout** : le commerce
entre régions n'est pas un bonus, c'est une nécessité.

> **Raretés inversées.** Une matière de **base** doit être présente dans **beaucoup** de
> zones ; une matière de **haut palier** dans **très peu**.
>
> C'est ce qui permet à la demande de fin de jeu de se diluer sur la carte au lieu d'écraser
> une seule zone — et ce qui pousse les joueurs vers l'extérieur plutôt que sur un unique
> filon. L'audit de la chaîne de production ([BALANCE.md § 21](BALANCE.md)) a montré que la
> règle n'est pas tenue aujourd'hui : l'étain n'a qu'un filon au monde alors que le bronze
> en consomme autant que de cuivre, qui en a deux.

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

### 5.4 La pureté — toute améthyste ne se vaut pas *(tranché)*

Emprunt à Star Wars Galaxies (cf. [GAME_INSPIRATIONS.md](GAME_INSPIRATIONS.md) §2.6), adapté
pour éviter son défaut : chez eux *toutes* les ressources avaient des statistiques, ce qui a
transformé l'artisanat en tableur.

**Un lot d'améthyste porte une pureté.** Elle se lit sur quatre bandes plutôt que sur une
note continue — c'est ce qui évite d'éclater les piles d'inventaire en mille lots
incomparables :

| Bande | Ce que ça veut dire | Usage |
|---|---|---|
| **Trouble** | Le geste est illisible | Fonte, consommables, matériaux de base |
| **Clair** | Lisible mais confus | Artisanat courant |
| **Pur** | Net | Haut palier, commandes exigeantes |
| **Parfait** | On entend qui l'a fait | **Seule bande qui permet d'*éveiller* une matéria neuve** (§2.1 — n'affecte ni la trouver ni l'utiliser) |

La pureté d'un lot se tire à la récolte, dans une fourchette donnée par : le **palier du
filon**, sa **vitalité** au moment où l'on récolte (un filon éreinté ne rend plus que du
trouble), la **compétence** du récolteur, la **marée** en cours et le **biome** (un
Affleurement ou un Chœur tirent haut).

**Le périmètre est volontairement étroit** : la pureté ne s'applique qu'à la **ligne du
cristal** — améthyste, minerais, gemmes. Les herbes, poissons, cuirs et bois restent
fongibles. Le plancher T1 (D1) ne doit jamais demander à un débutant de comparer des lots.

Ce que ça débloque, et c'est beaucoup pour une colonne :

- **Le HV cesse d'être un tas.** Une annonce porte sa bande ; les prix se séparent ; il
  existe enfin une raison de préférer un vendeur à un autre.
- **Le prospecteur a un métier.** Savoir quel filon rend du *Pur* cette marée-ci est une
  information qui vaut de l'argent — exactement l'information exclusive décrite dans
  [GAME_ZONE_ACTIONS.md](GAME_ZONE_ACTIONS.md).
- **`Recipe.quality` se réveille.** Le champ existe et dort ; il trouve son intrant.
- **Les commandes de craft peuvent exiger une bande minimale** — ce qui répond à la question
  ouverte de [GAME_PRINCIPLES.md](GAME_PRINCIPLES.md) §6.
- **L'*éveil* de matéria devient rare par construction** : il faut du *Parfait*, et le Parfait
  ne se force pas. **La table de butin existante reste la voie normale** — on ne resserre pas
  l'accès aux matéria de base (§2.1), on rend seulement la *création* d'une matéria neuve
  exceptionnelle.

**Impact modèle** : `PlayerItem.purity` (enum de bande, nullable — `null` = hors périmètre),
la bande comme critère de pile et de filtre au HV, un modificateur de qualité au craft.

### 5.5 Le creux du milieu — la demande ne se décrète pas

Le problème le plus dur du genre, et il faut le nommer honnêtement : **au bout de quelques
mois, les vétérans sont en fin de jeu et les nouveaux traversent le début en courant. Les
zones intermédiaires se vident.** Leurs filons repoussent et donnent de la haute pureté —
mais si personne n'a besoin de ce qu'elles produisent, la haute pureté ne sert à rien.

Le système de foyers redistribue **l'attention** ; il ne crée pas de **demande**. D'où une
règle de contenu, préalable à tout le reste :

> **Toute zone doit être la source exclusive d'au moins une chose.**
> Une zone qui produit « la même chose en un peu mieux » que sa voisine mourra, quoi qu'on
> fasse. C'est une contrainte de level design, pas un réglage.

Cela posé, cinq leviers entretiennent une demande **structurelle** pour le milieu :

**1. Le raffinage consomme le palier inférieur** *(mécanique d'Albion — le levier principal)*
Raffiner du palier N exige du raffiné N-1 **plus** du brut N. La demande en matière
intermédiaire devient donc **proportionnelle à l'activité de fin de jeu** : plus il y a de
vétérans, plus le milieu est sollicité. La ressource mid ne devient jamais obsolète, parce
qu'elle est un **intrant** et non un **produit fini**.
*(Chantier économie — à ouvrir dans `PLAN_PLAYER_ECONOMY`, pas dans le plan des foyers.)*

**2. La pureté prime sur le palier** *(décision D, §5.4)*
Seule l'améthyste **Parfaite** permet d'éveiller une matéria neuve. Or un filon reposé de palier moyen sort
du Parfait bien plus souvent qu'un filon éreinté de haut palier. Une zone intermédiaire
délaissée devient donc **la meilleure source du monde pour la chose la plus précieuse du
jeu**. C'est la valeur qui suit la *fraîcheur*, pas le palier — et ça, aucun vétéran ne peut
l'ignorer.

**3. Pas de niveau global** *(règle 6)*
La progression est par arbres de domaine, séparés. Un vétéran du combat qui monte l'alchimie
est un débutant en alchimie : il **doit** retourner au milieu. Un MMO à niveau global n'a pas
cette porte de sortie ; nous l'avons déjà, gratuitement.

**4. Le passage dépose du sédiment**
Traverser une zone y laisse une trace, faible mais réelle. Une zone posée sur une route
commerciale vit donc **du trafic**, même si personne n'y farme. Cela transforme la position
d'une zone dans le graphe en levier de conception : on peut décider qu'un foyer survivra
parce qu'il est sur le chemin.

**5. La Crue pousse vers l'extérieur**
Les grandes régions sont plafonnées par le quota. Une guilde ambitieuse qui arrive après les
autres ne peut pas prendre la place occupée : son seul chemin vers une Cité passe par une
zone que personne ne veut. Le quota fabrique donc de la demande pour le milieu.

**Et une honnêteté de cadrage** : aucun de ces leviers ne sauve un monde qui a trop de zones
pour sa population. **Mieux vaut peu de zones profondes que beaucoup de zones minces.** La
grille d'anticipation (§4.5) est une réserve, pas un programme.

### 5.6 Ce qu'on ne prend pas d'Albion

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
       Ordre des Chevaliers   │   Confrérie des Ruelles
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
- **La Confrérie des Ruelles** — trafique la matéria volée et, pire, **la fausse** : un
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

### 6.4 Réputations : la doctrine se compte *(tranché le 2026-07-28)*

Le jeu a trois compteurs de réputation, chacun à sa place, et quatre décisions les
gouvernent :

**a) La tension par paires.** Sur l'axe doctrinal, progresser chez l'un fait décroître
chez son opposé **au-delà du palier Ami** : **Fonderie ↔ Cercle des Mages** (Extraire /
Préserver), **Ordre des Chevaliers ↔ Confrérie des Ruelles** (Ordre / Liberté). La
**Guilde des Marchands est hors tension** — elle vend aux deux, c'est son identité. On
peut être bien vu de tous jusqu'à Ami ; au-delà, il faut choisir. *(Le précédent du
genre est le choix Aldor/Scryers de WoW Burning Crusade : l'identité naît de ce qu'on
renonce.)* Jamais de décroissance par inactivité : la réputation ne descend que par le
**geste opposé** (principe du plan Rétention — l'absence n'est jamais punie).

**b) Les gestes nourrissent, les quêtes amorcent.** La réputation se gagne d'abord par
les **actes systémiques quotidiens** : fondre une matéria → Fonderie ; la **lire** →
Lecteurs (le même geste nourrit le Répertoire du serveur, §12.3 — deux échos d'un même
acte) ; ventes au HV et caravanes → Marchands ; Effacés abattus et beats de marée →
Chevaliers ; le marché gris → Ruelles. Les quêtes de faction restent l'amorce et la
narration, jamais le régime de croisière.

**c) Le patronage : une seule faction portée.** Les bonus de statistiques des paliers
deviennent un **patronage** — on porte les couleurs d'une seule faction à la fois
(changeable hors combat). Tout le reste des récompenses de palier est **latéral,
jamais vertical** : recettes exclusives, cosmétiques, montures, teintures, titres,
**accès secrets** (connexions de graphe cachées via `requires_discovery`, services
clandestins ou réservés), gestes retrouvés à condition de faction (§12.3). Un palier
de réputation ouvre des portes ; il n'empile jamais de la puissance.

**d) Hostile a des conséquences réelles.** Le bas de l'échelle n'est pas décoratif :
prix majorés, portes fermées, services refusés, informations empoisonnées — selon la
faction. Deux bornes absolues : les conséquences ne bloquent **jamais la boucle cœur**
(énergie, zones, combat, plancher T1), et ne prennent **jamais la forme d'une agression**
(le Serment tient, §6.1).

**Les deux autres compteurs** : la réputation d'**artisan** (`CrafterReputation`, par
métier — livrée) mesure la fiabilité aux commandes ; la **renommée** globale
(`PlayerRenown`) reste une **vitrine sociale pure** — titres, journal de monde — et ne
gate jamais rien. Pas de quatrième compteur par foyer : la contribution nominative par
marée (FOY-04, RET-05) suffit.

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
| **Acte d'introduction** | *Le réveil du Limpide.* Un être sans passé ouvre les yeux au Fanal, rassemble quatre fragments, atteint la Voûte — et découvre qu'elle est **fermée de l'intérieur**. | Une fois par personnage | Arc `intro` + Actes 2-3 (livrés) |
| **Marées** | Un épisode par mois : une strate affleure, une menace monte, un climax se combat, une résolution s'inscrit. Résoluble, oubliable. | 4 semaines | `InfluenceSeason` + 4 `GameEvent` (livré) |
| **Le Reflux** | La ligne longue : la Pâleur gagne, la Fonderie accélère, les foyers montent et tombent, la Voûte s'ouvrira. N'avance que par **basculements canon rares**. | Années | `is_canon` + journal de monde (livré) |

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

- **Ouvrir.** Rendre au monde tout ce qu'il a déposé. La magie redevient infinie, la Pâleur
  recule — et le poids revient, et avec lui la raison qui avait fait fermer.
- **Refermer.** Sceller ce qui reste, accepter que la magie s'éteigne en une génération, et
  laisser un monde ordinaire à ceux qui viendront.
- **Répartir.** Ne plus garder la mémoire dans une pierre, mais **dans ce que les vivants ont
  bâti et écrit** — les foyers, le Codex, les faits canon des marées. Ce que les joueurs ont
  sauvé, le monde le garde ; ce qu'ils ont laissé pâlir, il le perd.

La troisième voie est la raison d'être du Codex (D11) *et* des foyers (§3) : **la
civilisation que les joueurs construisent est la réponse à la question de fin.** Aucune
branche narrative supplémentaire à écrire — l'issue reste unique, seul ce qui est *inscrit*
varie (compatible D10).

### 7.4 Les Effacés

Ce qui sort de l'Étale n'a pas de forme propre, puisque plus rien ne se souvient de lui.
**Un Effacé prend le geste de qui le combat** : il vous rend vos matérias, une par une, un
tour plus tard. Bestiaire infiniment variable pour un seul comportement, lecture immédiate
(« il me refait mon propre sort »), et un contre-jeu qui récompense la diversité
d'équipement plutôt que la puissance brute. Réutilise `SpellApplicator` et
`MobActionHandler`, sans nouveau moteur. C'est l'ennemi générique du jeu, disponible pour
n'importe quelle marée et n'importe quelle zone qui pâlit.

---

## 8. La fabrique de marées

Une marée = **une strate qui affleure**. Le gabarit ne change jamais (amorce → montée →
climax → résolution, livré) ; seule la strate change.

| Marée | Ce qui remonte | Climax | Enjeu |
|---|---|---|---|
| **La Marée d'Ambre** | Un jour de l'âge précédent, dans les Dunes | Ce qui était conservé se réveille | Lecteurs contre Fondeurs sur le butin |
| **La Fonte** | L'été d'avant le gel, au Silence | Ce qui s'était arrêté reprend son geste | Une zone gelée devient temporairement exploitable |
| **Le Chœur** | Deux veines entrent en phase | Boss à double élément | Synergie élémentaire poussée à l'extrême |
| **La Pâleur** | *Rien ne remonte* — une zone se délave | Empêcher un foyer de tomber | **Conséquence directe** de la sur-extraction du mois précédent |
| **L'Appel de la Crue** | Le quota se libère | Faire monter un foyer avant les autres | Course de nœuds entre guildes, sans un coup échangé |
| **La Contrefaçon** | Les Ruelles inondent le marché de fausses matérias | Remonter à l'atelier | Marée **économique** : le HV est le terrain |

Deux propriétés à tenir : environ une marée sur trois est **canon** (D9/D12) ; et les marées
« conséquence » (*la Pâleur*, *Appel de la Crue*) doivent être **déclenchées par ce que les
joueurs ont fait le mois d'avant**. C'est ce qui fait une boucle plutôt qu'un calendrier.

---

## 9. Impact sur le modèle

Par ordre d'effort croissant. Les points 1 à 4 rendent le monde lisible **sans changer une
seule mécanique** ; le pilier des foyers (5 à 8) est un chantier de roadmap à part entière.

| # | Ajout | Nature |
|---|---|---|
| 1 | Régions `ambre` et `silence` + rattachement des zones de l'Acte 4 | Donnée (`RegionFixtures`) |
| 2 | Faction **la Fonderie** + récompenses | Donnée (`FactionFixtures`) |
| 3 | Entrées de Codex : régions, factions, vocabulaire (sédiment, veine, Crue, Pâleur, Étale, Limpide) | Donnée (`CodexEntryFixtures`) |
| 4 | `Region.veine` (élément dominant) | Une colonne |
| 5 | **`Settlement`** : rang, type, sédiment accumulé, `Zone` (1-1) | Entité + migration |
| 6 | Dépôt de sédiment : brancher sur les events déjà écoutés par `InfluenceListener` | Un subscriber de plus, aucun event neuf |
| 7 | Déblocage par rang : services, ateliers, marché, parcelles gardés par le rang du foyer | Gardes dans les contrôleurs existants |
| 8 | Crue : quotas + zone d'influence + régression par marée d'inactivité | Logique au tick de saison |
| 9 | Bonus d'atelier par foyer (ligne de production × type) | Un multiplicateur lu au craft |
| 10 | Caravanes : cargaison, voyage lent, rencontres de route | S'appuie sur le graphe de voyage |
| 11 | **Pâleur** (`Zone.paleness`, réversible contre paiement) + biome de **l'Étale** + famille **Effacés** | Un compteur, un type de zone, un comportement de mob |

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
| D | Qualité variable des ressources ? | **Oui**, sur la ligne du cristal uniquement, en quatre bandes (§5.4). |
| E | Le « Blanc » : sanction ou frontière ? | **Les deux, séparés et nommés** — la **Pâleur** (état de zone, réversible, réparable contre paiement) et **l'Étale** (lieu ancien, permanent, frontière de fin de jeu). Aucun vivant ne cause une Étale (§12.1). |
| F | La Fonderie : jouable ou lointaine ? | **Jouable dès le début**, et sans méchanceté. Levier retenu : **fondre ou lire** une matéria (§12.2). |
| G | La source de l'améthyste ? | **Aucun gisement.** L'améthyste est un **sous-produit universel** : toute action, partout, peut en rendre, en quantité et bande variables selon la zone, la fraîcheur et la marée (§13.3). Elle est au monde ce que le sang est au corps — et une trame scénaristique est à tisser autour (le Cristal d'Améthyste de Lumière en est déjà le centre, Actes 1-3). |

### Encore ouvert

1. **Quand ouvrir la Voûte ?** Le retournement ne vaut que gardé : aucun contenu ne s'en
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

## 12. Dossiers instruits *(tranchés le 2026-07-27)*

Deux notions étaient citées sans être définies. Le raisonnement qui a mené à la décision est
conservé ici : c'est lui qui empêchera de revenir dessus par inadvertance.

### 12.1 La Pâleur et l'Étale

#### Le problème qu'il a fallu voir

Le « Blanc » des premières versions de ce document faisait **deux métiers incompatibles** :

- **Une sanction** — ce que devient une zone sur-extraite.
- **Une frontière** — une région de fin de jeu, pleine de contenu, d'où sortent les Effacés.

Les deux se contredisent. Si c'est riche et gratifiant, ruiner sa région n'est plus une
punition mais un jackpot. S'il est stérile, c'est une perte sèche, et Eco nous a montré
ce que produit une perte sèche collective : de la rancune entre voisins, pas de l'écologie.
**La question n'était donc pas le nom, mais lequel des deux métiers garder.**

#### Trois options cohérentes

| | **A — un état, pas un lieu** | **B — un lieu, pas un état** | **C — les deux, séparés et nommés** |
|---|---|---|---|
| Sur-extraction | Une zone peut s'appauvrir, réversible | Aucune conséquence durable, juste des rendements qui baissent | Une zone peut **pâlir** — graduel, réversible, payant à réparer |
| Frontière | Aucune : l'expansion passe par de nouvelles régions | Une région permanente au bord du monde, contenu de fin de jeu | Un vrai lieu mort **ancien**, hérité de l'âge précédent, jamais causé par les joueurs |
| Effacés | Sans source claire | Sortent de la région | Sortent du lieu ancien ; une zone pâlie n'en produit pas |
| Risque | Le thème écologique existe, mais ne mène nulle part | On perd l'enjeu qui rend le monde signifiant | Deux systèmes à tenir au lieu d'un |
| Confusion de nom | — | — | **Résolue** : deux choses, deux mots |

**Décision : C.** Seule option qui garde le thème *et* le contenu — et le problème de nom
disparaît de lui-même, puisqu'il s'agit de deux objets différents.

#### Ce que donne la décision

**La Pâleur** — un état de zone, graduel et réversible.
Une zone sur-exploitée pâlit : les filons rendent moins et plus trouble (§5.4), la faune
s'éclaircit, le foyer entre en étiage. Elle ne devient **jamais** une Étale : la Pâleur a un
plancher. On la répare en payant — **au trésor de la guilde contrôlante** (mécanique de
Wakfu, cf. GAME_INSPIRATIONS §2.2). La sanction est donc une **dépense** et une décision
politique, jamais une perte définitive.

**L'Étale** — un lieu, ancien, permanent, jamais causé par un joueur vivant. Le nom vient du
moment où la marée s'arrête : ici, elle ne s'est jamais remise à monter.
C'est ce que l'âge précédent a laissé en se retirant. Ses propriétés :

- Aucune récolte, aucun PNJ, aucun foyer possible — **rien ne s'y dépose**.
- On n'y **récolte** pas, on y **retrouve**. L'Étale est l'endroit où échouent les choses
  que le monde a perdues : plans éteints, matérias sans propriétaire, noms. C'est donc
  **une source exclusive de plans et de matéria** — exactement les récompenses que D2 veut
  trouvées et non achetées — sans ajouter une seule ligne de production concurrente.
- **On n'y reste pas.** Le séjour efface : un malus cumulatif qui force l'expédition courte.
  Même grammaire que le Silence, déjà posée.
- C'est de là que viennent les **Effacés** (§7.4).

#### Le nom du lieu — pourquoi *l'Étale*

Trois candidats étaient en lice pour le lieu (l'état, lui, s'appelle la Pâleur sans débat) :

| Nom | Registre | Pour | Contre |
|---|---|---|---|
| **L'Étale** ✅ | Marin — l'instant où la marée s'arrête | Colle au vocabulaire déjà posé (marée, reflux, crue). Un lieu où la marée ne revient plus. | Mot peu connu — le Codex l'explique |
| **La Lacune** | Manuscrit — un trou dans un texte | Colle au Codex : le monde est écrit, une Lacune est ce qui manque à la page | Sonne savant |
| **Le Blanc** | Direct | Immédiat, fort | Se confond avec le fragment blanc du Sommet (Acte 2) et avec « blanchiment » |

**Retenu : l'Étale**, parce qu'il appartient à une famille de mots que le joueur apprend
déjà (marée, reflux, crue), et que « la marée qui ne revient jamais » dit exactement ce
qu'est l'endroit.

### 12.2 La Fonderie

#### Le problème qu'il a fallu voir

Une faction ne vaut que par ce qu'elle **fait faire au joueur**. Les quatre existantes sont
des réputations avec une boutique : correctes, tièdes. Si la Fonderie n'est qu'une cinquième
boutique avec une couleur différente, elle ne portera pas l'axe doctrinal (§6.2), et l'axe
restera un paragraphe de lore.

**La question était donc : quel geste de jeu, répété tous les jours, incarne le choix entre
extraire et préserver ?**

#### La réponse retenue : fondre ou lire

Une matéria trouvée peut aller à deux endroits, et un seul :

| | **La Fonderie — fondre** | **Le Cercle des Mages — lire** |
|---|---|---|
| Ce qu'on donne | La matéria | La matéria |
| Ce qu'on reçoit | Des gils et de l'**essence** — le carburant de la commodité : réparations, entretien de foyer, escorte de caravane, accélération d'artisanat | Une entrée de **Codex**, de la réputation, et un progrès d'**accord** dans l'arbre correspondant |
| Quand | Tout de suite, utile aujourd'hui | Durable, jamais repris |
| Ce que ça fait au monde | Un geste disparaît définitivement | Le geste est **inscrit** — il ne pâlira jamais |

C'est tout le propos du monde ramené à **un bouton**. Le joueur pressé fond ; le joueur qui
pense au serveur lit. Personne n'a tort, et il faut choisir à chaque matéria en double.
Aucune cinématique, aucun dialogue moral : la doctrine devient une micro-décision
quotidienne, et le Codex (D11) cesse d'être une collection pour devenir le camp d'en face.

Cette mécanique, à elle seule, justifie d'ajouter la faction.

**Impact modèle** : une action de conversion sur `PlayerItem` de type matéria, deux
destinataires, deux tables de récompense. L'essence est une monnaie secondaire, dépensable
uniquement en services (jamais en objets — sinon elle concurrence les gils et le craft joueur).

#### Jouable, et surtout pas méchante

| | **A — faction jouable dès le début** | **B — force du monde, non fréquentable** | **C — jouable, débloquée après l'Acte 2** |
|---|---|---|---|
| Présence | Quotidienne : on lui vend, on lui achète, on travaille pour elle | Lointaine, on en subit les effets | Absente à l'onboarding, présente ensuite |
| Force dramatique | On fait ses courses chez celui qui vide le monde | Plus menaçante, mais abstraite | Le choix arrive quand le joueur peut le comprendre |
| Coût | Réputation + boutique + quêtes (comme les 4 autres) | Faible | Moyen |

**Décision : A.** Un antagoniste chez qui on fait ses courses vaut mille fois mieux qu'un
antagoniste qu'on ne fréquente jamais. Et le choix fondre/lire n'a de poids que s'il
est disponible dès la première matéria en double.

**Point de conception à tenir** : la Fonderie n'est pas un empire, elle n'a pas de tyran, et
elle ne conspire pas. Elle éclaire les cités, chauffe les foyers du Silence, arme les
caravanes et paie bien. Ses gens sont sympathiques et inquiets de voir des villages sans
lumière. Elle a **raison à court terme** — c'est précisément ce qui la rend impossible à
combattre. L'antagoniste du jeu est le Reflux ; la Fonderie est ce qui l'accélère en
améliorant la vie de tout le monde.

#### Ce qu'elle apporte au reste des systèmes

- **Un plancher d'achat.** La Fonderie rachète toujours le cristal, à prix bas mais garanti.
  C'est le miroir du plancher T1 de vente (D1) : un débutant n'est jamais bloqué par un
  marché sans acheteur — protection *cold-start* côté vente.
- **Un gold sink et un sink de matière** : ce qu'elle fond disparaît du monde.
- **Un levier de foyer.** Un foyer peut accueillir un atelier de la Fonderie : rendement
  d'extraction en hausse, vitalité de veine en baisse. La doctrine d'une guilde (§6.3)
  devient un bâtiment qu'on voit sur l'écran de zone.
- **Un adversaire d'arc saisonnier** disponible en permanence (*Le Procès de la Fonderie*,
  §8) sans avoir à inventer une menace neuve à chaque marée.


#### Complément tranché le 2026-07-28 — échelle, contrats, contreparties

**L'intégration est le contraste exact des Ruelles : la Fonderie s'affiche.** Enseignes,
comptoirs, recruteurs au carreau des Mines (son siège), visible dans l'écran de factions
dès le jour 1 — et un argument imparable : c'est elle qui éclaire vos villes. On doit
pouvoir l'aimer sincèrement ; c'est ce qui rend l'axe doctrinal réel.

**Son système propre : les contrats d'approvisionnement.** Chaque semaine (rotation du
lundi, RET-07), la Fonderie publie des contrats : gros volumes de matières communes
(minerai, charbon, hêtre) à prix **garanti mais toujours sous le marché**. Garde-fou
inverse du receleur (§12.4) : le receleur prend *plus* que la taxe max pour ne jamais
renverser le HV ; la Fonderie paie *moins* que le HV moyen pour ne jamais le remplacer.
Ses avantages : zéro friction, régularité, paiement mixte **gils + essence**. Effet
secondaire : un plancher de demande permanent pour les ressources du milieu (levier
anti-creux supplémentaire, gratuit).

**L'échelle (latéral pur, §6.4)** :

| Palier | Ce qui s'ouvre |
|---|---|
| **Ami** | Contrats d'approvisionnement ; tarif de fonte amélioré |
| **Honoré** | **Brûleurs d'atelier** (dépenser de l'essence pour accélérer un craft ou tenter la qualité supérieure — un *service*, jamais un objet) ; recettes d'artificier (pétards, signaux, feux de fête) ; tenue d'ouvrier |
| **Révéré** | **Chariot de la Fonderie** (monture utilitaire : +capacité de cargaison) ; lampe inextinguible ; contrats majeurs |
| **Exalté** | **Le Grand Fourneau** — vraie zone `interior` au fond des Mines : là où la Fonderie fond ce qu'elle ne montre pas (matière d'arc saisonnier — *Le Procès de la Fonderie*, §8) ; un geste retrouvé à condition Fonderie ; titre |

**Les contreparties** : la tension actée (§6.4 — chaque fonte au-delà d'Ami décote les
Mages : lectures majorées, Athénée fermé, gestes à condition Lecteurs inaccessibles) ; le
**Codex n'avance pas** (fondre ne donne jamais d'entrée) ; et le **coût collectif sans
malus mécanique** : chaque matéria fondue est une lecture perdue pour le Répertoire du
serveur (§12.3) — la pression sociale naît toute seule. **Hostile chez la Fonderie** :
elle ne rachète plus votre cristal — le plancher d'achat se ferme (le HV reste ; la
boucle cœur tient, et un débutant n'est jamais Hostile).


### 12.3 L'Autel d'éveil, le Répertoire et l'équilibre *(dossier instruit — recommandations)*

Trois questions ouvertes, posées ensemble parce que leurs réponses s'emboîtent.

#### a) À qui appartient l'Autel d'éveil ?

| Option | Effet | Verdict |
|---|---|---|
| **Bâtiment de ville** (service de la Métropole) | Ouvert à tous, la guilde contrôlante le **taxe** | **Recommandé** |
| Bâtiment de guilde | Un joueur solo ne peut jamais éveiller | Contredit le principe D14 |
| Bâtiment de joueur (housing) | Privatise l'aboutissement collectif | Contredit le sens de la Métropole |

**Recommandation : un service de la ville, comme le marché ou la banque.** C'est la doctrine
déjà actée (D14, GCC) : *une guilde taxe, elle ne ferme jamais*. La guilde qui gouverne la
Métropole prélève sur chaque rite (revenu de trésor, cohérent avec D4) ; n'importe quel
joueur — guildé ou non — peut éveiller. Et le **type** de foyer garde un rôle : un foyer
**Sanctuaire** réduit le coût ou le délai des rites — c'est même sa vocation depuis le début
(§3.2).

#### b) Des matérias exclusives à l'éveil ? Oui — et c'est le débouché collectif de « lire »

L'idée d'avancement de serveur qui « découvre de nouvelles façons de travailler la matière »
existe déjà dans nos références (A Tale in the Desert, l'avancée technologique collective —
GAME_INSPIRATIONS §2.1), et elle a **déjà sa source dans nos systèmes** : le choix
**fondre ou lire** (§12.2).

**Le Répertoire d'éveil.** Chaque matéria **lue** chez les Lecteurs nourrit un savoir
collectif du serveur. À des seuils donnés, le serveur **retrouve un geste perdu** : une
recette d'éveil s'ajoute au Répertoire, annoncée au journal de monde (*« les Lecteurs ont
retrouvé le geste de… »*), et tout le monde peut désormais l'éveiller à l'Autel.

**Le Répertoire est orienté par les lectures** *(nuance actée)*. Ce ne sont pas des seuils
génériques qui débloquent une liste fixe : **ce qu'un serveur retrouve dépend de ce qu'il a
lu** — quelles matérias, où, à quelle intensité, à quel moment. Le mécanisme est le même que
le type d'un foyer (§3.2) : l'agrégat des lectures a des dominantes (élément, provenance,
lieu de lecture), et quand un seuil tombe, le geste retrouvé est tiré du **bassin qui
correspond à la dominante**. Un serveur qui lit surtout des matérias de feu dans les Mines
retrouve d'abord des gestes de feu et de métal ; son voisin, qui lit de l'eau au Marais,
retrouve tout autre chose.

- **La divergence entre serveurs devient organique** : même bassin de contenu, ordres de
  déblocage différents, sans une ligne de branche écrite. Deux serveurs d'un an n'ont pas le
  même Répertoire parce qu'ils n'ont pas vécu pareil.
- **Orienter le Répertoire est un projet collectif légitime** : une guilde ou un serveur peut
  faire campagne — *« cette marée, lisez du feu »* — pour tirer le prochain geste vers ce
  qu'il veut. Ce n'est pas un abus, c'est de la politique ; les plafonds anti-exploit
  existants suffisent à empêcher le forçage par spam.
- En fiction, c'est le postulat lui-même : **ce qu'un serveur lit est ce dont il se
  souvient** — chaque monde se souvient différemment.

Deux règles pour que ça reste tenable :

1. **Un seul bassin, écrit une fois.** Les gestes retrouvés sont un contenu global (chaque
   entrée taguée : éléments, provenances, conditions) ; les serveurs le traversent dans des
   ordres différents. On n'écrit jamais du contenu pour *un* serveur — le coût est amorti
   partout. Une petite part du bassin porte des conditions assez rares pour que la plupart
   des serveurs ne voient jamais tout : l'exclusivité naît des conditions, pas d'un
   marquage par serveur.
2. **La règle latérale (c) s'applique intégralement** : les chemins divergent en *options*,
   jamais en *puissance*.

Ce que le Répertoire referme d'un coup :

- **« Lire » gagne son enjeu long terme.** Fondre paie l'individu aujourd'hui ; lire ouvre
  au serveur, pour toujours — et **oriente** ce qui s'ouvre. L'axe doctrinal cesse d'être un
  choix de saveur.
- L'éveil a du contenu **au-delà** du contre-hasard : le catalogue de base par provenance,
  plus les gestes retrouvés.
- C'est un moteur de contenu déclaratif : ajouter un geste retrouvé = ajouter une donnée
  taguée dans le bassin.

#### c) L'équilibre — la règle qui rend tout le reste inoffensif

> **Le Répertoire est latéral, jamais vertical.** Une matéria exclusive à l'éveil est
> *différente* — un effet nouveau, une variante, un utilitaire, un hybride — jamais
> strictement *plus forte* qu'une matéria de butin. Le plafond de puissance reste dans le
> butin et le sertissage.

C'est la même philosophie que « on ne progresse pas en changeant de sort, on progresse en le
portant mieux » (§2.1) : la puissance vient du support, l'éveil apporte des **options**.
Conséquences :

- **Pas de déséquilibre butin/éveil possible** : les deux voies mènent au même plafond, par
  des chemins différents (le hasard généreux contre le choix coûteux).
- **Pas de déséquilibre entre serveurs qui compte** : un serveur en avance a plus d'*options*,
  pas plus de *puissance*.

#### d) Entre serveurs : ne pas synchroniser — indexer

**Recommandation : chaque serveur avance seul.** Sans PvP ni compétition inter-serveurs, un
Répertoire d'avance n'est pas un avantage, c'est une **identité** — « c'est nous qui avons
retrouvé Éclipse en premier », gravé au journal de monde. Synchroniser tuerait précisément ce
que le modèle de clonage préserve (§13.4) : des mondes qui écrivent chacun leur histoire.

Deux garde-fous suffisent :

1. **Cumulatif, jamais manqué.** Un geste retrouvé l'est pour toujours ; aucun contenu du
   Répertoire n'est limité dans le temps. Un serveur jeune peut tout retrouver, à son rythme
   et **dans son propre ordre** (b) — sauf les gestes à conditions rares, qui restent rares
   partout.
2. **Seuils indexés sur la population effective** (le même mécanisme que la Crue et le
   facteur de monde, BALANCE §22.5) : un petit serveur a besoin de proportionnellement moins
   de lectures pour retrouver le même geste. Le rythme *vécu* est comparable partout ; seule
   la date absolue diffère.

---

### 12.4 La Confrérie des Ruelles *(dossier instruit — tranché le 2026-07-28)*

#### Le problème, et la clé

Une faction de voleurs dans un jeu coopératif sans PvP n'a pas de victimes : on ne vole pas
un joueur, on ne l'assassine pas — le Serment tient pour tout le monde (la Confrérie pratique le
vol et le faux, **jamais le sang**). La clé : **la Confrérie ne vole pas les joueurs, elle vole
le système** — la taxe, l'information, la nuit. C'est ce qui la rend politique sans une
goutte de PvP.

#### On ne la trouve pas : c'est elle qui vous trouve

Invisible dans l'écran des factions jusqu'au premier contact. Pas de tableau de quêtes, pas
de recruteur. En façade, ses agents ont des métiers honnêtes — et ils existent déjà dans
les fixtures : Tancrède le veilleur de nuit du Fanal (échoppe ouverte 20 h-6 h), Kolm le
porteur de lanterne des Mines. Après des gestes qualifiants (explorer la nuit à plusieurs
reprises, découvrir une contrefaçon, acheter à un étal nocturne), un PNJ vous glisse un
mot : une chaîne narrative nocturne de 3-4 étapes, et la faction apparaît, à Neutre.
Publiquement, la réputation ne s'affiche jamais en clair — le titre d'Exalté au journal de
monde est un euphémisme (« Ami des ruelles »).

#### Les quatre piliers

**a) Le receleur (marché gris).** Vendre hors taxe de cité, contre une coupe fixe qui va à
la Confrérie au lieu du trésor de la guilde contrôlante. **Garde-fous, pour que le marché
gris ne renverse jamais le HV** : la coupe (**15 %**) est toujours *supérieure* à la taxe
maximale d'une cité (10 % — bornes 1-10 % dans le code livré), l'accès exige Ami, et un
**plafond de ~5 lots/semaine/joueur** (paramètre) borne le volume — la Confrérie n'aime
pas les gros volumes, ça se voit. En gils, le receleur est toujours perdant ; ses seuls
avantages sont politiques (assécher le trésor de la guilde en place), pratiques (écouler ce
que le HV refuse, au-delà de la région — un filet, jamais un canal de masse) et
l'anonymat. Il ne gagne économiquement que face aux guildes gourmandes : **l'arme du
peuple contre la mauvaise gouvernance.**

**b) Le réseau d'oreilles.** L'information est déjà une marchandise (savoir du prospecteur,
Affleurement de la semaine — jamais annoncé, découvert ou *acheté*). La Confrérie en est le
marché : des **rumeurs** achetables — où la bande tire haut cette marée, où un filon
repose, ce que la nuit cache.

**c) La contrefaçon.** « Une contrefaçon marche neuf fois et vous trahit à la dixième » —
mécanique complète ci-dessous.

**d) Les passages dérobés et la contrebande.** Le graphe supporte les connexions cachées
(`requires_discovery`) : la Confrérie en détient, certaines n'existant que la nuit. Les
**contrats de contrebande** — livrer discrètement une cargaison de nuit, moins de capacité,
pas d'escorte — sont un **système propre à la Confrérie**, créé avec elle (pas un dérivé des
caravanes).

#### La contrefaçon, mécanique

- **Indiscernable tant que non identifiée** — l'état « non identifié » n'existe que sur les
  lots du marché gris et le butin, **jamais entre joueurs**.
- Elle se sertit et lance normalement, avec un **compteur caché** (~8-12 utilisations, tiré
  à la création). Au déclenchement : le sort **échoue au pire moment**, un contrecoup frappe
  le lanceur (dégâts ou statut — le prix du faux geste), et la matéria **se brise** en
  améthyste Trouble.
- **L'œil du faussaire** (Honoré) identifie avant sertissage ; le **désamorçage** (Révéré)
  démonte une contrefaçon identifiée en composants (améthyste Trouble + essence).
- **La main du faussaire** (recette, Révéré) : améthyste Trouble + éclats d'une matéria
  brisée → une contrefaçon. Débouché **jamais un joueur** : les **contrats de placement**
  (écouler ses faux via les contacts PNJ de la Confrérie — rémunérateur, chaque placement
  risque la fouille : confiscation, amende, grosse décote Chevaliers).
- **Canaux verrouillés** : le HV refuse toute contrefaçon (flag, testable) ; l'échange
  direct l'affiche en rouge — impossible de la déguiser. La borne absolue tient : **un
  joueur ne peut jamais tromper un autre joueur.**

#### L'échelle des récompenses (latéral pur — §6.4)

| Palier | Ce qui s'ouvre |
|---|---|
| **Ami** | Le receleur ; premières rumeurs |
| **Honoré** | L'œil du faussaire ; recettes de teintures sombres et poisons du Marais ; capuche de la Confrérie (cosmétique) |
| **Révéré** | Premiers passages dérobés ; monture discrète ; le désamorçage ; la main du faussaire |
| **Exalté** | **La Cour des Miracles** — vraie zone du graphe (`interior`, cachée, réservée aux Exaltés) ; un geste retrouvé du Répertoire à condition Ruelles ; « Ami des ruelles » au journal |

#### Les contreparties (jouer les Ruelles se paie)

1. La **tension actée** (§6.4) : chaque geste au-delà d'Ami décote les Chevaliers —
   bénédictions, quêtes d'ordre et patronage inaccessibles.
2. Les **fouilles** aux portes des zones à foyer de type Bastion : surcoût de voyage, et un
   contrat de contrebande peut être **confisqué** (la cargaison du contrat, jamais
   l'inventaire).
3. La **taxe majorée à l'Autel** d'éveil — la ville se méfie de vous.
4. Le **patronage exclusif** : porter les couleurs de la Confrérie, c'est renoncer aux autres.

**Hostile chez les Ruelles** : prix majorés, portes fermées — et surtout **les rumeurs qu'on
vous vend sont fausses**. La Confrérie ne vous attaque pas, elle vous ment. Le miroir chez les
Chevaliers : fouilles systématiques, taxe d'Autel au plafond.

**Impact modèle** : flag `counterfeit` + état non-identifié sur `PlayerItem`, connexions
`requires_discovery` nocturnes, une zone `interior` cachée (la Cour), une entité de contrat
de contrebande/placement, et l'entrée de faction différée (visible après premier contact).

### 12.5 Les trois autres maisons *(tranché le 2026-07-28)*

La même passe que la Fonderie (§12.2) et les Ruelles (§12.4), appliquée aux trois factions
livrées : un geste quotidien, un système propre, une échelle latérale, des contreparties.
**Symétrie voulue : chaque Exalté ouvre une porte quelque part** — cinq zones `interior`
cachées, une par maison.

#### La Guilde des Marchands — hors tension, la maison de tout le monde

- **Gestes** : volume échangé au HV (plafonné/jour), commandes de craft publiques honorées,
  plus tard les convois. La faction de l'économie vivante.
- **Système propre : les commissions de négoce.** Des missions d'arbitrage publiées par
  comptoir : *« achetez ceci ici, livrez-le à tel comptoir régional »* — le jeu apprend
  l'écart de prix entre régions (D13) en le faisant pratiquer. Précurseur assumé des
  caravanes.
- **Échelle** : Ami — remises (livrées) et la **cote des marchés** (prix moyens des autres
  régions, avec un jour de retard) ; Honoré — commissions majeures, balance de précision
  (voir la fourchette de pureté d'un lot avant achat) ; Révéré — mule bâtée (monture
  utilitaire), priorité d'étal (← ECO Piste D) ; Exalté — **la Grande Halle** (zone
  `interior` : la bourse des maisons marchandes), droit de **charte de caravane** quand
  elles arriveront, titre.
- **Contreparties** : aucune décote (hors tension, c'est son identité) — mais tout se paie
  chez eux, et le patronage Marchands renonce aux quatre autres. **Hostile** (rare —
  placements de faux découverts, commissions trahies) : surcharge de 10 % chez tous les
  marchands PNJ du monde.

#### L'Ordre des Chevaliers — la maison du Serment

- **Gestes** : abattre ce qui viole le dépôt (morts-vivants, Effacés), tenir les beats
  défensifs de marée, **remettre les contrefaçons saisies** (une contrefaçon rapportée à
  l'Ordre est détruite contre réputation — le miroir vertueux du placement).
- **Système propre : le tableau des primes.** Des primes hebdomadaires (rotation du lundi)
  sur des créatures nommées des zones menacées — la chasse au monstre comme service
  public. Plus tard : l'escorte de caravanes.
- **Échelle** : Ami — primes ; Honoré — bénédictions (services de soin/préparation avant
  expédition), héraldique (tabard, recettes d'armurier d'ordre — cosmétique) ; Révéré —
  monture caparaçonnée, **le garde du corps** (une escorte PNJ par semaine : un voyage
  sans rencontre hostile) ; Exalté — **la Salle du Serment** (zone `interior` : là où le
  texte original est gravé — la révélation de *pourquoi* le sang ne se dépose pas), un
  geste retrouvé à condition Ordre, titre.
- **Contreparties** : la tension actée (au-delà d'Ami, le marché gris se ferme, les
  rumeurs se tarissent, les passages dérobés resteront inconnus) ; et la **rigueur** : se
  faire prendre en contrebande ou en placement décote immédiatement et fortement.
  **Hostile** : fouilles systématiques, taxe d'Autel au plafond (§12.4).

#### Le Cercle des Mages — les Lecteurs, la maison de la mémoire

- **Gestes** : **lire** des matéria (le geste — qui nourrit aussi le Répertoire du
  serveur, §12.3), compléter le Codex, participer aux expéditions d'étude.
- **Système propre : le Programme du Cercle.** Chaque marée, le Cercle publie ses
  priorités de lecture (*« cette marée, lisez du feu »*) — **l'institution qui incarne
  l'orientation collective du Répertoire** (§12.3). Lire dans le programme rapporte
  davantage (réputation et récompenses) ; le pilotage du serveur cesse d'être une
  consigne de chat pour devenir un système.
- **Échelle** : Ami — tarif de lecture réduit, accès à la bibliothèque (indices de Codex) ;
  Honoré — le Programme (primes de lecture alignée), lunettes d'érudit (cosmétique),
  encres et parchemins (recettes d'appoint) ; Révéré — familier-lanterne (cosmétique),
  accès aux expéditions d'étude (contenu PvE du Silence, plus tard), l'archive (relecture
  enrichie de ses entrées de Codex) ; Exalté — **le Scriptorium** (zone `interior` : là où
  le Répertoire du serveur s'écrit physiquement — on y *voit* l'état des seuils et des
  dominantes), un geste retrouvé à condition Lecteurs, titre.
- **Contreparties** : la tension actée (chaque lecture au-delà d'Ami décote la Fonderie —
  contrats et brûleurs inaccessibles) ; et **l'essence manque** : qui ne fond jamais paie
  tous les services de commodité en gils pleins. **Hostile** : le Cercle refuse de lire
  pour vous — il reste la fonte, ou le stock.

#### Les cinq portes

| Maison | Zone Exalté | Ce qu'on y comprend |
|---|---|---|
| Ruelles | la Cour des Miracles | qui tient vraiment les ruelles |
| Fonderie | le Grand Fourneau | ce qu'elle fond quand personne ne regarde |
| Marchands | la Grande Halle | où les prix se décident |
| Chevaliers | la Salle du Serment | pourquoi le sang ne se dépose pas |
| Mages | le Scriptorium | ce que le serveur a lu, et ce qu'il lui reste à retrouver |

Cinq récompenses d'exaltation, cinq quartiers de lore, zéro puissance verticale.

### 12.6 Le logement dans le monde des foyers *(tranché le 2026-07-29)*

Le housing est livré (demeure rattachée à une zone, loyer-sink, jardin qui multiplie ce
qu'on possède — auto-limitant —, ameublement par le nécessaire du charpentier) mais il
n'a jamais rencontré le pilier territorial : **une seule zone résidentielle** (le
Quartier des Jardins), aucun lien au rang des foyers, un loyer qui ne nourrit personne.
Le raccord, en quatre décisions :

**a) Le lotissement du Fanal reste le plancher.** Le Quartier des Jardins est le logement
de départ : hors Crue, hors foyer, **jamais gaté** (décision A), loyer modeste en pur
gold sink (pas de guilde au Sanctuaire). Tout joueur peut toujours se loger là — le
plancher, jamais le plafond.

**b) Habiter un foyer.** Toute zone à foyer de rang **Hameau ou plus** ouvre des
parcelles résidentielles, dont le **nombre suit le rang** (repères à W = 1 : Hameau ~8,
Bourg ~20, Cité ~40, Métropole ~80 — paramètres `settlements.yaml`, mis à l'échelle par
W). La régression d'un foyer **n'expulse jamais personne** (rien n'est rétro-gaté) —
elle ferme seulement l'ouverture de *nouvelles* parcelles sous le rang. La rareté des
parcelles hautes est un enjeu de plus de la Crue, sans une mécanique neuve.

**c) Le loyer devient politique.** Dans une zone à foyer, le loyer part au **trésor de
la guilde contrôlante** de la région (le même canal que la taxe HV) ; sans guilde
contrôlante, il reste un sink. Habiter chez quelqu'un est un acte politique doux — et
une guilde bien gérée a désormais des *habitants* comme source de revenus : une raison
de plus de rendre sa région vivable.

**d) Les avantages du logis sont des commodités, jamais de la puissance.** Habiter
donne : le **retour au logis** (une fois par jour, le voyage vers sa zone de résidence
est instantané — la commodité de fin de session, cf. le playtest V2), le **jardin**
(livré), un **coffre domestique**, et la vitrine (styles du charpentier, cosmétique).
Et **les cheminées comptent** : chaque demeure habitée dépose un petit grain de
résidence quotidien au foyer de sa zone — la population résidente soutient la ville,
faiblement mais structurellement (un plancher de sédiment pour les foyers résidentiels).

En fiction : on ne « possède » pas un terrain — on **entretient un feu** quelque part,
et le monde s'en souvient. La maison est du temps vécu déposé au même endroit chaque
jour ; c'est pour ça qu'elle nourrit le foyer, et c'est pour ça qu'on ne l'expulse pas
quand la ville décline : elle *est* ce qui reste de la ville.

Jalons : **FOY-18 → FOY-21** (vague 2 du plan foyers). Le jardin, lui, ne change pas —
il est déjà exactement ce qu'il doit être.

### 12.7 Les caravanes — mécanique tranchée *(2026-07-29)*

Le §5.3 posait les principes ; voici la mécanique, tranchée. **La caravane est un
contrat de transport asynchrone** — et son miroir discret existe déjà : la contrebande
des Ruelles (§12.4).

**a) L'affrètement.** Se fait à un marché, vers un marché : **Bourg ↔ Bourg minimum**
(une caravane relie des places marchandes — la valeur du rang s'en trouve renforcée).
Coût en gils (le sink), cargaison au-delà de la capacité du sac (les montures
utilitaires — chariot de la Fonderie, mule des Marchands — l'augmentent), et **deux
curseurs de risque** :

- **L'itinéraire** : le graphe offre des chemins alternatifs (l'anneau) — le plus court
  n'est pas le plus sûr ;
- **L'allure** : *prudente* (lente, taux de rencontre bas — **jamais nul**), *normale*,
  ou *hardie* (rapide, taux haut).

Le couple itinéraire × allure est le facteur temps/risque : la caravane prudente par la
longue route arrive presque toujours entière, presque toujours tard.

**b) Les rencontres, asynchrones.** À chaque segment, un jet de rencontre **PvE**
(pillards PNJ, bêtes, événements de marée). Quand une tombe, la caravane s'arrête :
l'affréteur et les escorteurs sont notifiés et disposent d'une **fenêtre de 3 heures**
pour la résoudre sur place (voyage standard + combat ou choix — payer le péage,
contourner, forcer). Fenêtre expirée : résolution automatique dégradée — la caravane
repart en perdant **~25 % de la cargaison** (jamais tout, jamais l'inventaire — la même
borne que la contrebande). **L'assurance des Marchands** (prime à l'affrètement,
majorée pour une allure hardie) rembourse partiellement les pertes — un service de
faction de plus.

**c) L'escorte ne bloque personne.** S'inscrire comme escorteur n'immobilise pas le
personnage : c'est une **astreinte, pas un voyage**. On joue normalement ; quand une
rencontre tombe, on est appelé (fenêtre de 3 h — large exprès pour les horaires réels),
on s'y rend, on la résout, on reprend sa journée. **La réserve d'astreinte** *(tranché le
2026-07-29)* : à l'inscription, l'escorteur réserve le coût d'une intervention (~10
énergie par convoi, rendue à l'arrivée si elle n'a pas servi) — on ne peut jamais être
appelé sans pouvoir répondre, l'astreinte a un coût d'opportunité réel (c'est ce que la
paie rémunère : rester frais), et le cumul de convois se borne tout seul. Filets en
aval : la fenêtre régénère ~30 énergie à elle seule, les autres escorteurs (premier
arrivé, premier servi), et la résolution dégradée si personne ne vient. Les bénéfices de
l'escorteur :

1. **La paie d'escorte** — une part du coût d'affrètement, fixée au départ (l'affréteur
   paie ses gardes : un transfert joueur → joueur, pas un robinet à gils) ;
2. **La réputation des Chevaliers** — l'escorte est un de leurs gestes (§6.4) ;
3. **Le butin des rencontres** résolues (un combat paie normalement) ;
4. Les **grains `war`** de ses combats, déposés sur la route.

**d) La visibilité, et la ligne rouge.** Les trajets **ne sont pas publics** : seuls
l'affréteur et ses escorteurs voient le convoi ; le monde ne voit que l'effet agrégé
(le sédiment déposé aux deux bouts — commercer fait monter les foyers, §5.3). Les
rumeurs des Ruelles **ne vendent jamais** l'information d'un convoi : cette information
n'aurait d'usage que l'interception, et **l'attaque de caravane par des joueurs serait
du PvP — jamais, règle 11, le Serment** (« le sang versé ne se dépose pas » vaut sur
les routes comme partout). Le risque d'une caravane est le monde, pas les autres
joueurs.

**e) Le miroir des Ruelles.** Le pendant des caravanes existe déjà : **la contrebande**
(§12.4 d, FAC-08). La symétrie est complète, et elle se consigne :

| | **La caravane** (au grand jour) | **La contrebande** (la nuit) |
|---|---|---|
| Volume | gros (au-delà du sac) | petit et précieux |
| Quand | jour, lente | nuit, discrète |
| Protection | escorte de joueurs, assurance | aucune — la discrétion |
| Risque | rencontres PvE, perte partielle | la fouille, confiscation du contrat |
| Qui y gagne | Marchands (assurance), Chevaliers (escorte) | les Ruelles (la coupe) |
| Sédiment | **déposé aux deux bouts** | **aucun** — le transport invisible ne nourrit pas les foyers |

La dernière ligne est un canon : la contrebande vole aussi le sédiment — ce que la
ville ne voit pas passer ne la fait pas grandir. Jalons : **ECO-32 → ECO-35** (Piste I).

## 13. Peuplement du monde — départ, zones, extensions

> **Statut : acté le 2026-07-28.** Les deux entrées attendues (§14) sont closes, et les
> tableaux ci-dessous sont devenus des définitions opposables, zone par zone, dans
> [GAME_ZONES.md](GAME_ZONES.md) (jalons : [roadmap/PLAN_ZONES.md](roadmap/PLAN_ZONES.md)).
> Ce paragraphe reste la version macro ; en cas d'écart, GAME_ZONES.md fait foi.

### 13.1 Un seul point de réveil

**Ne pas éparpiller les départs au lancement.** Éparpiller résout un problème de
**surpeuplement** — c'est-à-dire un problème de succès. Au lancement, le danger est
l'inverse : un monde vide. Et surtout, l'éparpillement **combat le pilier des foyers**, qui
a besoin de concentration pour que quoi que ce soit pousse : cent joueurs répartis sur huit
zones de départ ne font monter aucun foyer, chaque zone paraît morte, et le pilier ne se
démontre jamais. Plusieurs MMO du genre ont dû fusionner leurs zones de départ après coup.

Le monde le dit déjà : **un Limpide s'éveille là où est la Voûte.** Le point de réveil unique
est canon (§7.2), pas un compromis technique.

**Différencier par la destination, pas par l'origine.** La race donne un **foyer d'attache** —
la communauté qui recueille le nouveau venu — qui détermine sa première destination, son kit,
sa réputation de départ et sa première chaîne de quêtes :

| Race | Foyer d'attache | Première destination |
|---|---|---|
| Nain | les mineurs | Mines profondes |
| Elfe | les herboristes | Forêt des murmures |
| Orc | les chasseurs | Crête de Ventombre / Dunes |
| Humain | les marchands | libre — le hub est sa maison |

Le joueur se **sent** distinct dès la création ; la population **reste** groupée au moment où
elle est la plus petite.

> **Amendement (2026-07-29, [GAME_ONBOARDING.md](GAME_ONBOARDING.md) §4.4)** — l'intention
> ci-dessus est conservée, le mécanisme change : **le foyer d'attache ne se dérive plus de la
> race, il se gagne**. C'est la zone où le joueur a réellement travaillé pendant l'acte I ; le
> jeu le constate à la clôture et le lui annonce. Le dériver de la race revenait à demander une
> orientation de carrière — kit, destination, chaîne de quêtes — au moment où le joueur en sait
> le moins, c'est-à-dire à réintroduire une classe déguisée dans un jeu qui n'en a pas
> (CLAUDE.md §9/§10). La concentration de la population est tenue de la même façon (le point de
> réveil reste unique) ; le foyer n'est plus qu'une **adresse constatée après coup**, qui
> n'ouvre ni ne ferme aucun contenu. Le défaut, faute d'activité distinctive, est **le Fanal** —
> ce que ce tableau réservait à l'Humain devient le cas général. Le reste de §13.1
> (éparpillement indexé sur la population) est inchangé.

**Et l'éparpillement s'indexe sur la population**, exactement comme le quota de Crue (§3.3) :
quand le serveur grossit, des foyers d'attache deviennent de vrais points de réveil. Aucune
mécanique nouvelle, et le sens de la marche est le bon — on ajoute toujours un point de
départ facilement, on n'en retire jamais sans douleur.

### 13.2 Architecture proposée — base + deux extensions

Rappel des deux règles qui contraignent l'exercice : **toute zone doit être la source
exclusive d'au moins une chose** (§5.5), et **mieux vaut peu de zones profondes que beaucoup
de zones minces** (§5.5). Le monde livré compte onze zones : la proposition n'en ajoute
qu'une, et réserve les nouvelles aux extensions.

**Jeu de base — 8 zones** (1 sanctuaire + 6 foyers + 1 donjon) :

| Zone | Ligne de production | État |
|---|---|---|
| le Fanal + Quartier des Jardins | *aucune* — plancher garanti, pas de foyer (§3.4) | existe |
| Forêt des murmures | bois, fibre, herbes | existe |
| Mines profondes | métal de base (cuivre, étain, fer) | existe |
| Marais brumeux | alchimie, poisons, eau | existe |
| Crête de Ventombre | pierre, gemme, cobalt | existe |
| **Vallons / rivière** | agriculture, pêche, cuir | **à créer** — comble le trou de niveau 2 identifié en §4.5 |
| Dunes d'Ambre | cuir, os, sel, ambre | existe |
| Cité ensevelie | ruines, plans anciens (donjon) | existe |

**Extension 1 — Le Silence** : Pas de Givre, Glacier du Silence, Mer de Sel, plus une ou deux
zones neuves. Modèle **expédition**, sans foyer, haut palier (§4.3). À moitié écrite.

**Extension 2 — L'Étale** : la frontière. Source exclusive de plans et de matéria perdue,
Effacés (§12.1, §7.4). Extension de fin de jeu, déjà conçue.

### 13.3 L'améthyste — sans gisement *(tranché)*

**Il n'existe aucun filon d'améthyste.** Ce n'est pas un minerai qu'on va chercher à un
endroit : c'est **le sang du monde** — la mémoire, la trace que le monde laisse dans
lui-même. Elle se **trouve partout, en quantité et en qualité variables** :

- **Toute action peut en rendre** : une récolte, un combat, une exploration laissent parfois
  affleurer un éclat d'améthyste **en plus** de leur butin normal. C'est un sous-produit
  universel, jamais une cible qu'on farme à un point fixe.
- **La bande dépend du lieu et du moment** : la signature de la zone, la fraîcheur (un lieu
  reposé rend plus pur — §3.5), la marée en cours, et le biome (Affleurement, Chœur). Le
  *Parfait* ne se force pas : il se rencontre là où le monde a bien dormi.
- **La quantité suit l'intensité** : un boss en rend plus qu'un loup, un filon profond plus
  qu'une cueillette — ce qui a coûté du temps vécu en rend davantage. C'est le postulat,
  appliqué mécaniquement.

Conséquences de conception :

- Le mineur n'a **pas le monopole** de l'améthyste : chacun en ramasse en jouant sa propre
  boucle. Le **prospecteur** garde son métier — savoir *où et quand* les bandes hautes
  affleurent — mais personne n'est bloqué.
- ECO-22 (tirage de pureté) s'applique à l'améthyste **sur toute action**, pas seulement à la
  récolte ; les minerais et gemmes classiques, eux, gardent leurs filons et leur tirage à la
  récolte.
- Le spot hérité `spot-amethystite-xs` (unique source actuelle) disparaît avec le contenu de
  zone ; l'objet est renommé (améthystite → améthyste).

**Trame à tisser** *(note narrative)* : le Cristal d'Améthyste sous le Fanal (Actes 1-3, la
Convergence) est le **cœur** dont toute l'améthyste du monde est la circulation — la fuite du
coffre (§7.3). Le lien entre « la ressource qu'on ramasse chaque jour » et « la pierre sous
la Voûte » est un fil scénaristique à part entière, à poser dans les marées et le Codex.

### 13.4 Dimensionnement — la cible de population *(actée)*

> **Cible retenue : ~50 joueurs actifs quotidiens.** Pas un plafond : une **base de
> calibrage**. Tout ce qui dépend du nombre de joueurs est indexé dessus et s'ouvre quand le
> serveur grandit. En cas de succès franc, on **clone le serveur** plutôt que d'étirer le monde.

Le raisonnement : la plupart des PBBG indépendants vivent entre 20 et 100 joueurs quotidiens.
Concevoir pour 300 et lancer avec 30 produit un monde vide ; concevoir pour 50 et en accueillir
300 produit un monde qui s'ouvre. **On calibre pour ce qui va arriver, pas pour ce qu'on espère.**

**Pourquoi cloner plutôt qu'étirer.** Notre monde est petit et profond — peu de zones, chacune
avec son exclusivité. Absorber dix fois plus de joueurs demanderait de multiplier le *contenu*,
ce qui est cher. Cloner un serveur est presque gratuit, et **préserve la tension de la Crue** :
la rareté d'une Métropole n'a de sens que par rapport à une population qui se connaît. C'est
l'inverse du choix d'EVE (univers unique), et c'est le bon pour nous.

#### L'échelle d'ouverture

La Crue n'est pas qu'une contrainte territoriale : **c'est le régulateur de liquidité de
l'économie**, puisque le marché local ouvre au rang Bourg. Le quota de Bourgs *est* le nombre
de marchés du monde.

| Palier | S'ouvre à (joueurs actifs quotidiens) |
|---|---:|
| Marché du Fanal | **toujours** — hors quota, c'est le plancher (D1) |
| 1er Bourg (1er marché de foyer) | 40 |
| 2e Bourg | 80 |
| 1re Cité | 120 |
| 3e Bourg | 160 |
| 2e Cité | 220 |
| **Métropole** | 300 |

Deux propriétés recherchées :

- **À 50 joueurs, le monde est petit et dense** : le hub, un seul foyer bâti par les joueurs,
  et une vraie compétition entre deux ou trois guildes pour cet unique Bourg. C'est plus tendu,
  et plus lisible, qu'un monde à six marchés à moitié vides.
- **La Métropole n'existe pas tant que le serveur n'a pas réussi.** Le sommet de l'échelle est
  débloqué par la croissance de la communauté, pas par un joueur. C'est la meilleure
  récompense collective qu'on puisse offrir, et elle ne coûte rien à produire.

Les seuils sont de la **donnée** (`config/game/settlements.yaml`) : ils se retendent sans
redéploiement.

#### Ce que la cible impose au reste

**a) Jamais de contenu synchrone.** À 50 joueurs quotidiens connectés 10-15 minutes, on tourne
autour d'**1 à 2 joueurs simultanés**, 5 au pic. Toute fonctionnalité exigeant trois personnes
en même temps est morte-née. Cela valide rétroactivement le choix des boss de monde à
**contribution asynchrone**, et pose une règle : plus jamais de contenu synchrone.

**b) Le monde s'ouvre progressivement.** Onze zones pour 50 joueurs, c'est un désert : on n'y
croise jamais personne. Le graphe supporte déjà le gating (`enabled`, `requires_discovery`).
Ouvrir cinq à six zones au lancement et libérer les suivantes à mesure que la population monte
— la Concorde qui s'étend — donne un monde plein à chaque étape.

**c) Le calibrage devient dynamique.** Le monde doit rester à la taille de son audience sans
recalibrage manuel à chaque palier de croissance. L'invariant visé : **le temps qu'il faut
pour faire monter un foyer, et la tension ressentie sur un filon, doivent être les mêmes à 50
joueurs et à 500.** Un **facteur de monde** indexé sur la population globale met à l'échelle
la capacité des filons et les seuils de sédiment — mais **jamais** la réponse d'un filon à sa
propre fréquentation : un filon qui donnerait plus à mesure qu'on le presse annulerait sa
propre rareté. Et le **rythme** du monde ne change pas, seule son **ampleur** change : la
capacité s'ajuste, la cadence de repousse reste fixe. Conception et garde-fous dans
[BALANCE.md § 22.4](BALANCE.md), jalon **FOY-17**.

**d) Le calibrage des filons est à refaire de fond en comble.** C'est la conséquence la plus
lourde, chiffrée dans [BALANCE.md § 22](BALANCE.md) : en l'état, les filons du monde
soutiendraient **~2 200 récolteurs réguliers**. À 50 joueurs, ils tournent à **1 % de leur
charge** — la vitalité ne bouge jamais, la pureté est toujours au maximum, la Pâleur est
impossible, et l'incitation à s'étaler n'existe pas. **Toute la couche de rareté est inerte.**

---

---

## 14. Ce qui reste à cadrer avant d'écrire du contenu

**Plus rien.** Les trois entrées sont closes, et le §13 est passé de proposition à
définitions actées ([GAME_ZONES.md](GAME_ZONES.md)) :

1. ~~La population cible~~ — **tranchée** : ~50 joueurs actifs quotidiens comme base de
   calibrage, tout étant indexé dessus (§13.4).

2. ~~La colonne vertébrale de progression~~ — **écrite** : [GAME_PROGRESSION.md](GAME_PROGRESSION.md).
   Cinq horizons emboîtés (session, jour, semaine, marée, an), quatre actes dans la vie d'un
   joueur, et le **passage critique des semaines 3 à 6** où tout se joue. Le §6 de ce document
   énonce ce que la colonne impose aux zones et aux ressources — c'est l'entrée qui manquait
   pour figer le §13.

3. ~~La source de l'améthyste~~ — **tranchée** (décision G, §13.3) : aucun gisement,
   sous-produit universel de toute action.

> **Note de méthode.** L'ordre sain reste macro → micro. Le détour par le micro (audit de la
> chaîne, ECO-24) était néanmoins rentable : il a **falsifié** trois hypothèses du macro —
> deux systèmes de récolte coexistants, l'étain à filon unique, et la ressource-titre sans
> source. Du micro qui invalide du macro est le micro le moins cher qui soit ; du micro qui
> ne fait que le décorer est à repousser.
