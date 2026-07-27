# Inspirations — ce qu'on prend, ce qu'on refuse, et pourquoi

> **Statut : recherche, juillet 2026.** Revue des jeux dont Amethyste peut s'inspirer,
> chacun ramené à **une idée précise** et à **l'endroit du code où elle se brancherait**.
> Compagnon de [GAME_WORLD.md](GAME_WORLD.md) (le socle de monde) et de
> [GAME_PRINCIPLES.md](GAME_PRINCIPLES.md) (les décisions actées).
>
> Un jeu n'est pas cité parce qu'il est bon, mais parce qu'il a **résolu un problème qu'on a**.

## 1. Les trois filtres

Toute idée doit passer les trois, sinon elle ne figure pas ici :

1. **Sans PvP.** Le Serment interdit le combat entre joueurs (règle 11). Une mécanique
   territoriale qui repose sur le siège, le raid ou le full loot est **désarmée ou écartée**.
2. **Asynchrone et navigateur.** Pas d'adresse manuelle, pas de présence simultanée requise,
   pas de session de trois heures. Une mécanique qui demande vingt joueurs en même temps ne
   passe pas.
3. **Coût d'écriture amorti.** On préfère un système qui produit du contenu à un contenu
   qu'on écrit une fois et qui se consomme une fois.

---

## 2. Premier cercle — les jeux structurellement proches

### 2.1 A Tale in the Desert — la preuve qu'un MMO sans combat peut être politique

MMO en Égypte antique **sans aucun combat**, ni PvP ni PvE : la progression est artisanale,
sociale et politique. Les joueurs **écrivent et votent des lois**, et l'avancement passe par
des « Tests » — des épreuves collectives qu'aucun joueur ne peut réussir seul. Le monde a une
**fin annoncée** : chaque « Telling » dure des mois, se conclut, et le suivant recommence.

**Ce qu'on prend** : l'**épreuve collective de serveur** — un objectif dimensionné pour que
personne ne le boucle seul. C'est exactement ce qu'est la montée d'un foyer, et ça donne une
raison de coopérer qui ne passe pas par le combat de groupe synchrone.
**Ce qu'on laisse** : le reset intégral entre Tellings — incompatible avec notre monde
hybride (D9), où le serveur garde une histoire.

### 2.2 Wakfu — l'écosystème qu'on peut détruire, et la politique élue

Le plus proche de nous, et français. Deux systèmes :

- **Écosystème vivant** : les ressources ne sont pas des respawns fixes. Récolter sans
  replanter fait **disparaître définitivement** une espèce d'une zone.
- **Politique élue** : un **Gouverneur élu par les joueurs toutes les deux semaines**, un
  Trésorier qui fixe la **taxe de mise en vente entre 5 % et 70 %**, un **Écologiste** chargé
  de surveiller l'écosystème. Et le point décisif : **la taxe finance la réintroduction des
  espèces éteintes** et la modification du climat.

**Ce qu'on prend** : la **restauration payée par le trésor**. C'est la réponse à notre
arbitrage n° 3 (« la sur-extraction blanchit-elle une zone ? ») : oui, mais la guilde
contrôlante peut **payer** la remise en état de la veine. La sanction devient une dépense —
donc un gold sink, donc un choix politique — au lieu d'une perte sèche.
**Ce qu'on prend aussi** : l'idée qu'une organisation a des **rôles spécialisés** dont un
consacré à la santé du territoire.
**Ce qu'on laisse** : l'extinction définitive (trop punitive, Ankama a dû l'assouplir) et
l'écoterrorisme entre nations — qui est du PvP déguisé.

### 2.3 Eco — l'avertissement

Trois piliers explicites : **Économie / Écologie / Gouvernement**, avec une thèse de design
affichée — *résoudre la tragédie des communs*. La gouvernance y est **nécessaire pour
gagner** ; les lois votées ont des effets mécaniques réels.

Mais l'enseignement le plus utile est un retour d'expérience documenté : le stresseur
principal du jeu n'est pas la catastrophe, c'est la **friction sociale** — la frustration de
voir un voisin agir par intérêt propre.

**Ce qu'on prend** : le principe que la gouvernance doit avoir des **conséquences
mécaniques**, sinon c'est du cosmétique.
**Ce qu'on retient comme garde-fou** : notre Crue et notre Blanchiment sont des contraintes
collectives. Sans amortisseur, elles produiront exactement cette friction — un joueur en
voudra à un autre d'avoir « gâché » sa région. D'où les décisions déjà prises : régression
annoncée, patrimoine préservé, restauration payante plutôt que perte définitive.

### 2.4 EVE Online — le territoire appartient à qui y vit

La souveraineté ne se tient pas par la force mais par des **indices d'activité** :
*militaire* (PNJ tués), *industriel* (minerai extrait), *stratégique* (durée de possession).
Leur somme donne un multiplicateur de défense, et surtout : **les indices décroissent** quand
l'activité baisse. Un territoire qu'on n'habite plus devient indéfendable tout seul. Les
structures consomment du **carburant** : ne plus payer, c'est perdre.

**Ce qu'on prend** : c'est **la validation à grande échelle de notre sédiment**. Deux
raffinements à voler tels quels :
- **Plusieurs indices distincts plutôt qu'un compteur unique** — ce qui produit naturellement
  le *type* de foyer (Comptoir/Bastion/Athénée/Sanctuaire) au lieu de le faire choisir.
- **La décroissance comme mécanique lisible et continue**, plutôt qu'un couperet de fin de
  saison.

### 2.5 Ryzom — la récolte comme métier, la saison comme contenu

Sandbox où les matières **ne sont pas visibles** : il faut prospecter pour les trouver.
Pendant l'extraction, il faut **soigner le site**, sinon il s'effondre ou explose. Et surtout :
**la saison, la météo et l'heure changent ce qui est disponible**, avec des migrations de
faune au changement de saison.

**Ce qu'on prend** : notre [GAME_ZONE_ACTIONS.md](GAME_ZONE_ACTIONS.md) est déjà sur cette
ligne (prospection, information exclusive, vitalité de filon). Ryzom pousse plus loin :
**la marée change la table de récolte d'une zone**. C'est du contenu neuf tous les mois pour
un coût d'écriture nul — la variance jour/nuit déjà livrée (ZON-17) en est la moitié du chemin.

### 2.6 Star Wars Galaxies — la meilleure idée non exploitée

Deux mécaniques, vingt ans plus tard toujours inégalées :

- **Les ressources ont des statistiques aléatoires.** Chaque gisement a des caractéristiques
  notées de 1 à 1000, et chaque recette n'en utilise que certaines. Il existe donc du *bon* et
  du *mauvais* fer, les artisans surveillent les apparitions, stockent les bons lots, et se
  font une réputation sur la qualité de ce qu'ils sortent. Les gisements changent chaque
  semaine et **ne reviennent jamais**.
- **Dix villes joueur maximum par planète** — un cap dur, qui a produit exactement la
  compétition d'implantation qu'on cherche.

**Ce qu'on prend** : le cap valide la **Crue**. Et les statistiques aléatoires sont, à mon
avis, **le meilleur rapport valeur/effort de toute cette revue** (§4).

---

## 3. Second cercle — une idée précise chacun

| Jeu | L'idée | Où ça se branche chez nous |
|---|---|---|
| **FFXIV — Restauration d'Ishgard** | Reconstruction d'un quartier **à l'échelle du serveur**, réservée aux récolteurs et artisans (zéro combat), avec paliers visibles, classement par monde, et un chantier qui **se termine** | Le modèle exact de la montée d'un foyer : un chantier lisible, pas un compteur caché |
| **FFXIV — livraisons personnalisées** | Un PNJ commande à un artisan, régulièrement, contre réputation | Valide nos commandes de craft (ECO-05→09, Sprint 15) |
| **Black Desert — nœuds** | On **investit** des points **plafonnés** dans les nœuds : impossible de tout prendre. Deux nœuds voisins investis forment une **route commerciale** ; vendre hors route coûte −30 % | Une seconde Crue, **à l'échelle du joueur**. Et la route commerciale donne aux caravanes une vraie mécanique de réseau |
| **Guild Wars 2 — événements dynamiques** | Les chaînes **cascadent** : échouer à défendre un village change ce qu'on joue ensuite. Déclenchés par l'état du monde, pas par l'horloge | Nos marées : le beat suivant dépend du **résultat** du précédent, pas seulement du calendrier |
| **Haven & Hearth / Wurm** | Les claims et bâtiments se **dégradent** faute d'entretien ; le monde reprend ses droits | Notre régression par oubli — avec l'avertissement que chez eux c'est brutal et fait fuir |
| **Travian / OGame / Tribal Wars** | La grammaire PBBG : files de construction, timers longs, « reviens dans 4 h » | La montée d'un foyer se joue en **files et timers**, ce qui est notre boucle naturelle et ne demande aucune présence |
| **Kingdom of Loathing** | Budget de tours quotidien ; et l'**ascension** — recommencer volontairement pour débloquer d'autres chemins | Notre énergie, et une piste d'endgame qui ne viole pas « pas de niveau global » |
| **RuneScape / OSRS** | Compétences séparées sans niveau global ; et les **sondages** qui donnent le monde aux joueurs | Valide notre règle 6 ; les sondages sont une piste de gouvernance légère |
| **Path of Exile — les ligues** | Une mécanique neuve tous les 3 mois, testée en conditions réelles ; les meilleures rejoignent le jeu de base | Une marée peut **essayer une mécanique**, pas seulement raconter une histoire. Les bonnes restent |
| **Foxhole** | La logistique **est** le jeu : quelqu'un doit transporter les obus, et c'est un rôle respecté | Valide les caravanes comme contenu, à condition que le convoyeur soit valorisé et pas corvéable |
| **Dofus** | Métiers interdépendants, ressources rares créant des micro-économies | Confirme §5.1 de GAME_WORLD : aucune région n'a tout |
| **Fallen London** | Contre-référence actée (coût d'écriture) — mais un seul paragraphe bien écrit porte un système entier | Nos nœuds saillants écrits à la main (NAR-13) |

---

## 4. Les cinq idées que je retiens

Classées par rapport valeur/effort, la première étant celle que je pousserais en premier.

1. **Qualité variable de l'améthystite** *(SWG)* — un filon produit de la matière **notée**,
   variable selon la zone, la marée et la vitalité du filon. Effets en chaîne : le HV cesse
   d'être un tas fongible ; le prospecteur a enfin un métier dont le savoir vaut de l'argent ;
   `Recipe.quality` — qui existe déjà et dort — trouve son intrant ; et les commandes de craft
   peuvent exiger une qualité minimale (question ouverte de GAME_PRINCIPLES §6, résolue).
   Coût : une colonne sur le lot récolté, un modificateur au craft.

2. **Indices d'activité multiples et décroissants** *(EVE)* — le sédiment d'un foyer n'est pas
   un compteur mais quatre, qui décroissent. Le type du foyer *est* l'indice dominant. Coût :
   la mesure existe déjà dans `InfluenceListener`.

3. **Le foyer comme chantier visible** *(FFXIV Ishgard)* — paliers annoncés, contributions
   nominatives, classement de guilde, et une transformation visible de l'écran de zone quand
   le palier tombe. La différence entre « un compteur monte » et « on bâtit une ville » est
   entièrement dans la présentation.

4. **Restauration financée par le trésor** *(Wakfu)* — une veine épuisée se répare en payant.
   Transforme notre plus gros risque de design (la punition collective) en décision politique
   et en gold sink.

5. **La marée change la table de récolte** *(Ryzom)* — un mois neuf sans écrire de contenu. La
   variance jour/nuit livrée montre que la plomberie est là.

---

## 5. Ce qu'on refuse explicitement

| Mécanique | Jeu | Pourquoi non |
|---|---|---|
| Full loot, siège, guerre de territoire | Albion, Mortal, Life is Feudal, New World, ArcheAge | Règle 11 — sans appel |
| Extinction définitive d'une ressource | Wakfu | Punition irréversible ; même Ankama a dû l'assouplir |
| Écoterrorisme entre factions | Wakfu | Du PvP déguisé en écologie |
| Dégradation brutale des constructions | Wurm, Haven & Hearth | Fait fuir ; on garde le principe, pas la violence |
| Obsolescence de l'équipement à chaque saison | WoW, la plupart des MMO à paliers | Tue l'artisanat joueur (déjà acté, GAME_PRINCIPLES §4.6) |
| Reset intégral du monde | A Tale in the Desert | Incompatible avec le monde hybride (D9) |
| Campagne narrative massive | Fallen London | Coût d'écriture, valeur consommée une fois (déjà acté, D8) |
| Contenu de groupe synchrone obligatoire | Raids MMO classiques | Filtre 2 : asynchrone ou rien |

---

## 6. La conclusion de la revue

Le positionnement d'Amethyste sort net de cet examen. Il existe :

- des MMO **sans PvP mais sans territoire** (A Tale in the Desert, FFXIV) ;
- des MMO **avec territoire mais dont le territoire se prend par la force** (Albion, EVE,
  AoC, New World) ;
- des jeux **où l'écologie est l'enjeu, mais sur des serveurs de trente joueurs** (Eco), ou
  qui l'ont tenté à grande échelle et l'ont adouci (Wakfu).

Personne n'occupe la case : **un territoire qui se gagne par l'attention, se perd par
l'oubli, et qui est plafonné par une loi du monde** — le tout dans un navigateur, en
asynchrone. C'est notre place, et les briques déjà livrées (influence de guilde, saisons,
marchés régionaux, artisanat joueur, journal de monde) en couvrent l'essentiel de la
plomberie.

---

## Sources

- [A Tale in the Desert — Wikipedia](https://en.wikipedia.org/wiki/A_Tale_in_the_Desert) ·
  [MMOGames](https://www.mmogames.com/game/a-tale-in-the-desert/)
- [Wakfu — Écosystème (tutoriel officiel)](https://www.wakfu.com/en/mmorpg/tutorials/425008-ecosystem) ·
  [Wakfu — Politique (tutoriel officiel)](https://www.wakfu.com/en/mmorpg/tutorials/425665-politics) ·
  [Wakfu Wiki — Political & Economy](https://wakfu.fandom.com/wiki/Political_%26_Economy)
- [Eco — The Design Pillars of Eco](https://eco-servers.org/blog/41/-the-design-pillars-of-eco/) ·
  [GamesBeat — « We stopped the meteor, but we couldn't stop ourselves »](https://venturebeat.com/pc-gaming/eco-we-destroyed-the-meteor-heres-what-i-learned/)
- [EVE Online — Activity Defense Multiplier](https://support.eveonline.com/hc/en-us/articles/203354271-Activity-Defense-Multiplier) ·
  [EVE University — Sovereignty](https://wiki.eveuniversity.org/Sovereignty)
- [Ryzom (Steam)](https://store.steampowered.com/app/373720/Ryzom/) ·
  [Ryzom — Wikipedia](https://en.wikipedia.org/wiki/Ryzom)
- [SWG — Crafting Resources](https://swgr.org/wiki/crafting_resources/) ·
  [SWG Wiki — Resource](https://swg.fandom.com/wiki/Resource) ·
  [Ben Overmyer — SWG Crafting](https://benovermyer.com/blog/star-wars-galaxies-crafting/)
- [FFXIV — Ishgardian Restoration (Lodestone)](https://na.finalfantasyxiv.com/lodestone/ishgardian_restoration/) ·
  [Gamer Escape — Ishgardian Restoration](https://ffxiv.gamerescape.com/wiki/Ishgardian_Restoration)
- [Black Desert — Guide des nœuds (officiel)](https://www.naeu.playblackdesert.com/en-us/Wiki?wikiNo=20) ·
  [Gameranx — Nodes & Workers](https://gameranx.com/features/id/109994/article/black-desert-online-nodes-workers-guide/)
- [Guild Wars 2 Wiki — Dynamic event](https://wiki.guildwars2.com/wiki/Dynamic_event) ·
  [GDC Vault — Designing Guild Wars 2 Dynamic Events](https://gdcvault.com/play/1013691/Designing-Guild-Wars-2-Dynamic)
- [pbbg.com — annuaire des jeux navigateur persistants](https://pbbg.com/) ·
  [Kingdom of Loathing — Wikipedia](https://en.wikipedia.org/wiki/Kingdom_of_Loathing)
