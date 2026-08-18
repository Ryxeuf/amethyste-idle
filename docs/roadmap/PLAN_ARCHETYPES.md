# Plan — Archétypes de combat et équilibrage des arbres

> **Numérotation :** jalons préfixés **ARC-** (Archetypes). Pas de conflit avec les autres
> préfixes.

> Décline [../GAME_ARCHETYPES.md](../GAME_ARCHETYPES.md) (proposition instruite du
> 2026-07-31 ; **§9 bis** à **§9 septies** en déroulent **quatre** exemples complets, une
> **comparaison croisée** et une **simulation de journée** — qui ont produit **dix-neuf** des
> corrections listées ci-dessous, dont **aucune** ne portait sur un pourcentage) : les trois axes d'un domaine, la ressource par registre, le geste d'arme
> comme matéria, l'intention et la portée du geste, les marques élémentaires, le
> vocabulaire fermé des leviers et de leurs conditions d'équipement, le budget de
> puissance avec la fourche et le pacte, la loi du dépôt, les accointances, le gabarit et
> les six tests.
>
> **Ce plan ne réécrit pas la doctrine des arbres** — GAME_DOMAINS reste la loi
> (trois couches, double borne, équipement-build, 15 nœuds). Il lui donne ce qui lui
> manque pour que deux arbres ne se ressemblent pas : un axe, un vocabulaire, un budget.

## Vue d'ensemble

**20 jalons** (**ARC-01** à **ARC-20**) en 4 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| ARC-01 ✅ | La fonction, troisième axe du domaine (`Domain::role` + palettes) | S | ∅ |
| ARC-02 ✅ | Le registre du geste + premières matéria de technique | M → 2 sous-phases | ← MAT-01, MAT-03 |
| ARC-03 ✅ | Les leviers : les passifs deviennent des pourcentages bornés | **L** → 2 sous-phases | ← ARC-01 |
| ARC-04 ✅ | Les ressources par registre (munitions, temps de reprise) | M → 2 sous-phases | ← ARC-02 |
| ARC-05 ◐ | L'ancre d'échelle : la durée d'un combat en tours | **L** → 2 sous-phases | ← BES-01 |
| ARC-06 ✅ | L'échelle de coût des arbres, et le gain de points indexé au palier | M | ← BES-01 |
| ARC-07 ◐ | Les quatre arbres patrons, écrits au gabarit | **L** → 4 sous-phases | ← ARC-03, 04, 06, **ARC-14** |
| ARC-08 | Conversion mécanique des 20 autres arbres | M | ← ARC-03, ARC-07 |
| ARC-09 | Tests du plan (les 45 invariants) | S | ‖ |
| ARC-10 ✅ | Le plafond global de points — **tranché : suppression** | S | ∅ |
| ARC-11 ✅ | L'intention et la portée du geste, et la loi du dépôt | M → 2 sous-phases | ← ARC-02 |
| ARC-12 ✅ | Les passifs conditionnels d'équipement | M | ← ARC-03 |
| ARC-13 ✅ | Les huit marques élémentaires | M → 2 sous-phases | ← ARC-11 |
| ARC-14 ✅ | La fourche : une branche exclusive par arbre de combat | S | **→ ARC-07** |
| ARC-15 ✅ | Le pacte : un malus rend du budget | S | ← ARC-03 |
| ARC-16 | Les accointances : la synergie donne de la souplesse, pas de la puissance | M | ← ARC-12 |
| ARC-17 | **Le simulateur d'équilibrage** (`app:balance:simulate`) — l'outil qui remplace les repères calculés à la main | M | ← ARC-05, ARC-07 |
| ARC-18 | Les formes de geste : huit mécaniques empruntées, chacune réparant un défaut mesuré | M | ← ARC-11 |
| ARC-19 | L'aggro bornée, et ce qu'elle exige de l'armure | M | ← DON-03, OBJ, **ARC-20** |
| ARC-20 ◐ | **La barre de vie** : le Socle, la loi dérivée du bestiaire, et les cascades | **L** → 3 sous-phases | ← BES-01, ARC-05 |

```
Piste A — Le modèle   : ARC-01 → ARC-03 → ARC-12 → ARC-16 ; ARC-03 → ARC-15
                        ARC-02 → ARC-04 ; ARC-02 → ARC-11 → ARC-13
Piste B — L'échelle   : ARC-05 → ARC-17 ; ARC-06 ‖ ARC-10
Piste C — Le contenu  : ARC-07 → ARC-08 ; ARC-07 → ARC-14 ; ARC-09 ‖
Piste D — Les formes  : ARC-11 → ARC-18 (a livrer par forme, jamais en bloc) ; ARC-19 ← DON-03
Piste B (suite)       : BES-01 → ARC-20 → ARC-17 ; ARC-20 → ARC-19
```

**Le noyau minimal.** Si le chantier doit être livré par morceaux, ARC-01, 02, 03 et
11 sont le socle : sans eux, ni les archétypes d'arme ni les fonctions n'existent.
ARC-13 (les marques) est le premier des jalons de **nuance**, et il est **exigé par
ARC-07** : les capstones d'assaut se déclenchent sur une marque qui doit exister.

**Quand.** ARC-02 se livre **avec** le chantier matéria (PLAN_MATERIA) : c'est la même
migration d'objet, et la scinder ferait deux passes sur les mêmes fixtures. ARC-05 croise
la revue du bestiaire (PLAN_BESTIARY) — la durée d'un combat est un contrat entre les PV
d'un monstre et la valeur d'un geste, on ne peut pas la fixer d'un seul côté.

---

### ARC-01 — La fonction, troisième axe (S | ★★★ | CRITIQUE) ✅

> **Livré le 2026-08-03 — le chantier ARC est ouvert.** `DomainRole`
> (assault / control / upkeep / bulwark) + `Domain::role` nullable comme
> `register` (un domaine hors combat n'a pas de fonction), migration
> idempotente, et les **24 domaines de combat rangés selon la grille du §10**
> (10 assaut · 7 contrôle · 4 entretien · 3 encaisse). Les palettes vivent dans
> `config/game/domain_roles.yaml` — cinq leviers par fonction dont un principal
> exclusif, plafonds 20/20/20 et **15 pour `guard`**, palette d'intentions du
> §5.1, budget et règle des 80/20 **en données**. `DomainRoleDefinitionLoader`
> refuse à la lecture une palette incomplète, un principal partagé, un coût
> structurel vide, un 80/20 qui ne ferme pas, ou deux palettes partageant plus
> de deux secondaires ; il **calcule** la palette effective (§5.0) au lieu de
> la lire. Contrat tenu par `DomainRoleTest`, y compris l'invariant qui compte :
> la fonction ne s'affiche **nulle part** — c'est une contrainte d'auteur.
> GAME_ARCHETYPES §1 et §5. Trois arbres d'eau × sorts occupent la même case : rien, dans
> le modèle, ne dit en quoi ils diffèrent.
- [x] `DomainRole` (assaut / contrôle / entretien / encaisse) + `Domain::role`, avec
      migration ; les 24 domaines de combat rangés selon la grille du §10
- [x] Les **palettes** en configuration (`config/game/domain_roles.yaml`) : **cinq leviers**
      par fonction dont un **principal exclusif** (`power`/`grip`/`mending`/`guard`), plus la
      **palette d'intentions** (§5.1) ; la règle des 80/20 exprimée en données, pas en code
- [x] Plafonds des principaux à **20** (`power`, `mending`, `grip`), `guard` à **15**
      (correction du §9 quinquies : à 18, le capstone consommant 14 pb, un arbre de contrôle
      ne pouvait acheter son levier principal nulle part ailleurs qu'à son sommet)
- [x] Aucun affichage joueur : la fonction est une contrainte d'auteur, pas une classe
- [x] Tests : aucun triplet (élément, registre, fonction) en double ; tout domaine de
      combat a une fonction ; toute palette a cinq leviers ; deux palettes ne partagent
      jamais un levier principal, ni plus de deux secondaires

### ARC-02 — Le registre du geste, et les matéria de technique (M → 2 sous-phases | ★★★ | CRITIQUE) ✅

> **Découpé (règle 8) : ARC-02a le modèle, ARC-02b le contenu.** Le jalon touche
> 254 gestes livrés, un lot de techniques à écrire et le typage de 178 pièces —
> trop pour une passe. Le modèle se livre seul, ne casse rien, et débloque le
> contenu.
>
> **ARC-02a — livré le 2026-08-03.** `Spell::register` (`CombatRegister`, le
> vocabulaire que les domaines parlent déjà depuis DOM-01), `spell` par défaut
> — les 254 gestes livrés sont des sorts, et la valeur par défaut le dit sans
> qu'on les reprenne un par un. La matéria en **hérite** : `getMateriaKind()`
> lisait la seule *présence* d'un sort et rangeait donc en « technique » tout
> ce qui n'en portait pas ; elle lit maintenant le registre, et une matéria
> sans geste devient `Free` — elle n'exige rien de la pièce qui l'accueille.
> Contrat tenu par `SpellRegisterTest`.
>
> **ARC-02b — livré le 2026-08-03.** Les **17 accords exclusifs** des deux arbres
> patrons du §9 déclarent leur registre — 10 en mêlée pour le Soldat, 7 en tir pour
> l'Archer — et **les matéria de technique en découlent sans qu'on en écrive une
> seule** : `MateriaCatalogFixtures` dérive le catalogue des nœuds (MAT-03), donc
> reclasser le geste reclasse la matéria. Le report de **DOM-03** est levé sur la
> moitié que le modèle sait dire : les **12 armes de mêlée et de tir** de la grille
> neutre et les **8 pièces de plaque** au-dessus du palier d'entrée sont typées
> `technique` — *la famille décide, jamais la pièce*, exactement comme OBJ-04 l'a
> fait pour les lanceurs. Le test de DOM-03 change de sens sans changer d'intention
> (`testNoSocketIsAWallWithoutADoor` : pour chaque genre d'emplacement livré, il
> existe au moins une matéria sertissable), et les deux invariants entrent en CI
> (`CombatRegisterCoverageTest`).
>
> **Deux décisions d'implémentation, notées ici parce qu'elles se lisent mal dans
> un diff.** (1) **Le cuir reste `Free`** : « 1 `Spell`, le reste `Technique` »
> (GAME_ITEMS §3.4) est la seule des six lignes du canon qui soit une règle **par
> emplacement**, et `materiaSlotType` est porté par la *pièce* — donc par tous ses
> emplacements à la fois. `Free` est l'approximation honnête (le cuir accepte les
> deux genres) ; ce qui manque est le **plafond**, pas la polyvalence. Un test
> nommé (`testLeatherKeepsBothDoorsOpen`) empêche la fausse réparation, qui
> supprimerait silencieusement la moitié de la règle. **La question ouverte** :
> typer par emplacement demande `Slot::materiaSlotType` *et* un provisionnement
> général des `Slot` — aujourd'hui ils ne naissent que des fixtures et d'ECO-28.
> (2) **Le palier d'entrée reste libre des deux côtés** : `iron-chainmail`
> (niveau 4, 1 emplacement) n'est pas typé, par la même règle que le lin — sinon
> un débutant en cotte de mailles découvrirait que sa première matéria ne se
> sertit pas.
> GAME_ARCHETYPES §3. **Le prérequis dont deux archétypes sur quatre dépendent** : sans
> technique, un arbre de mêlée ou de distance ne qualifie aucune action.
- [x] `Spell::register` (sorts / mêlée / distance), hérité par la matéria comme l'élément *(ARC-02a)*
- [x] Un premier lot de **matéria de technique** — les accords des arbres Soldat et
      Archer du §9, dérivés selon la grille de GAME_MATERIA §2.1 *(ARC-02b)*
- [x] Lever le report de **DOM-03** : plaque → `technique` (le cuir attend un typage par
      emplacement, cf. l'encadré). Le test qui interdisait une pièce typée `technique`
      change de sens : il vérifie désormais qu'aucun emplacement n'est un mur sans
      porte (il existe au moins une matéria sertissable pour chaque type d'emplacement livré)
- [x] La règle 9 reste intacte : l'arbre accorde, il ne donne pas — un test le verrouille
      sur le nouveau chemin
- [x] Tests : toute matéria a un registre ; tout arbre ouvre au moins un geste de son
      registre (invariant 7), avec la **liste d'attente d'ARC-08** nommée et vérifiée
      exacte — elle ne peut que rétrécir

### ARC-03 — Les leviers (L → 2 sous-phases | ★★★ | CRITIQUE) ✅

> **Découpé (règle 8) : ARC-03a le vocabulaire, ARC-03b la formule.** Le jalon touche
> le vocabulaire, quatre consommateurs du moteur de combat et la place de chaque
> levier dans la formule — trop pour une passe. Le vocabulaire se livre seul, ne
> change **aucune valeur de jeu**, et débloque la formule.
>
> **ARC-03a — livré le 2026-08-03.** `CombatLever` (les 15 valeurs du canon,
> vocabulaire fermé) + `Skill::levers` (`(levier, points, condition ?)`, `NULL`
> tant qu'un nœud n'est pas converti — les colonnes plates restent la source
> jusqu'à ARC-07/08). Les taux de change et les plafonds vivent en **données**
> (`config/game/combat_levers.yaml`) parce que §0.2 prévient qu'aucun nombre du
> canon n'est définitif et qu'ARC-17 les rejouera. `CombatLeverScale` est le
> **convertisseur unique** — l'autre moitié de la règle qui rend l'équilibrage
> vérifiable —, `SkillLeverReader` le point de passage unique du vocabulaire
> fermé, et `CombatLeverDefinitionLoader` refuse à la lecture : deux leviers à la
> **même place dans la formule** (le critère d'admission), une entrée hors
> vocabulaire, un taux nul, ou un levier lisible dans deux registres sur trois
> (l'écart n° 13 pris à la racine). `thrift` et `wind` lisent la ressource de
> leur registre, et eux seuls ; `life` et `recovery` restent hors de la double
> borne. Contrat tenu par `CombatLeverTest`.
>
> **ARC-03b — livré le 2026-08-03.** Les leviers entrent dans la formule, et chacun
> **à la place que le canon lui donne** : `power`/`mending` multiplient la valeur de
> base, `critical` s'ajoute au taux et `critical_power` au seul multiplicateur de
> critique, `hit` s'ajoute au jet de touche, `pierce` **ignore une part de la
> résistance avant elle**, `guard` réduit **après** elle, `dodge` évite **avant tout
> calcul**, `life` porte sur les PV de base, et `grip`/`ward` se croisent sur le
> **jet d'application** d'un statut — l'un l'augmente et prolonge, l'autre y résiste.
> `CombatLeverEffects` est le porteur qui traverse le calcul ; il refuse de lire un
> levier **dans la mauvaise unité** (un taux de critique lu comme un multiplicateur
> donnerait un chiffre plausible et faux), et `CombatSkillResolver::getCombatLevers()`
> les somme sous la **même borne** que les statistiques plates — sauf `life` et
> `recovery`, que le canon place hors double borne et qui précèdent donc la borne.
> Deux porteurs et non un : les leviers offensifs sont ceux de l'attaquant, les
> défensifs ceux de la cible. **Aucune valeur de jeu ne change** — aucun nœud ne porte
> de levier, donc tout porteur est vide et le moteur calcule exactement comme avant,
> ce qu'un test nommé verrouille.
>
> **Quatre leviers restent sans consommateur, et c'est une dépendance, pas un oubli** :
> `thrift` et `wind` portent sur la **ressource du registre**, qui n'existe pas encore
> (**ARC-04** : PM, temps de reprise, munitions) ; `recovery` demande un crochet de
> fin de tour et `tempo` un ordre d'initiative, qu'aucun système ne porte aujourd'hui.
> Les brancher sur une ressource inventée aurait été pire que de les laisser lisibles
> et inertes.
> GAME_ARCHETYPES §4. Le refactor central : cinq entiers plats → **quinze** leviers en
> pourcentage, avec taux de change et plafonds.
- [x] `CombatLever` (15 valeurs, dont `dodge` et `recovery`) + `Skill::levers` : une liste
      `(levier, points de budget, condition ?)` destinée à remplacer
      `damage`/`heal`/`hit`/`critical`/`life` *(ARC-03a)*
- [x] **Une place et une seule par levier dans la formule** — déclarée en données et
      **refusée à la lecture** si deux leviers la partagent ; le taux de change vit dans
      **un seul** convertisseur *(ARC-03a)*
- [x] `thrift` et `wind` se convertissent selon la **ressource du registre** (§4, note 1),
      et un levier lisible dans deux registres sur trois est refusé *(ARC-03a)*
- [x] `life` et `recovery` restent hors de la double borne (décision DOM-01, inchangée),
      en pourcentage des PV de base *(ARC-03a)*
- [x] **`dodge` avant tout calcul, `guard` après résistance** : deux places distinctes,
      verrouillées par un test — c'est ce qui distingue le cuir de la plaque autrement que
      par un chiffre *(ARC-03a ; leur consommation est ARC-03b)*
- [x] `DamageCalculator`, `CriticalCalculator`, `FightCalculator` (le jet de touche) et
      `StatusEffectManager` consomment chacun les leviers qui les concernent *(ARC-03b)* —
      plus `PlayerEffectiveStatsCalculator` pour `life` et `DungeonActionResolver` pour
      `power`, sans quoi un arbre converti serait pertinent en zone et muet en donjon
- [ ] `thrift` et `wind` dans la formule : ils portent sur la **ressource du registre**,
      qui n'existe pas encore — reporté à **ARC-04** ; `recovery` (crochet de fin de tour)
      et `tempo` (ordre d'initiative) attendent le système correspondant
- [x] Tests : plafond par levier ; vocabulaire fermé ; un levier accordé deux fois par un
      nœud refusé *(ARC-03a)*
- [ ] Tests : somme = 50 pb par arbre ; règle des 80/20 ; aucun passif plat sur un nœud de
      domaine de combat *(ARC-03b — ils supposent des nœuds convertis)*

### ARC-04 — Les ressources par registre (M → 2 sous-phases | ★★ | HAUTE) ✅

> **Découpé (règle 8) : ARC-04a les deux registres qui ont déjà un modèle, ARC-04b
> le carquois.** La mêlée paie en tours (`Spell::cooldown` existe et est appliqué) et
> les sorts paient en PM (`Player::energy` existe) : ces deux-là se livrent sans créer
> une seule pièce d'équipement. Le registre distance demande `ammoCost`, un carquois
> à écrire, une recette de charpentier et une prime — c'est un autre chantier.
>
> **ARC-04a — livré le 2026-08-03.** *La mêlée cesse de payer en PM.* Les 10 techniques
> du Soldat (ARC-02b) passent à `energyCost: 0` et prennent la **reprise de leur palier**
> (grille 0/1/2/3/4 de GAME_MATERIA §2.3 bis, lue sur `shadow-dance` et non inventée) —
> *un guerrier cesse d'être « un mage qui tape »*, et c'est vérifiable en une lecture.
> Plus **la régénération des PM hors combat**, le curseur ouvert depuis 2026-07-29
> (BALANCE §24.2) : avant ce jalon les PM ne revenaient qu'en **lançant un sort**, donc
> en dépensant ce qu'on cherchait à récupérer, et hors combat rien ne remontait jamais.
> `ManaRegenManager` est calqué sur `LifeRegenManager` — paresseux, en temps réel, ancré
> à la sortie de chaque combat —, avec son curseur en paramètre (`zone.mana.regen_seconds`,
> défaut **6 s**, la moitié des PV : un pool de PM se vide en une rencontre quand une
> barre de vie tient plusieurs combats). La symétrie du canon est rétablie : *les PV
> paient les coups reçus, les PM paient les gestes faits, et les deux se rechargent en
> temps réel*.
>
> **Un constat, pas un arbitrage** : `stone-shield` est un sort de **palier 2** qui porte
> une reprise, ce que la règle 3 de §2.3 bis n'autorise qu'à partir du palier 3. Le
> corriger demande de choisir entre le rendre spammable et monter son palier — deux
> façons de changer une valeur de jeu vivante. Le cas est **nommé et vérifié exact**
> dans le contrat, pour qu'un second ne s'ajoute pas en silence.
>
> **ARC-04b — livré le 2026-08-03.** `Spell::ammoCost` (grille 1/2/3/4/5 par palier) et
> `Item::ammoCapacity` : le geste déclare ce qu'il consomme, le carquois ce qu'il porte.
> Les **7 gestes de tir** passent à `energyCost: 0` et paient en munitions — les trois
> registres ont désormais chacun leur ressource, et la liste d'attente d'ARC-04a est vide.
> La consommation vit dans le **combat** et jamais sur l'objet : le carquois *se vide dans
> la rencontre et se ramasse après*, ce qui rend la ressource intra-rencontre comme les PM
> et rend impossible qu'un joueur soit durablement désarmé faute de courses. Découvert au
> passage : `gear_location: 'ammo'` existait sur les deux carquois depuis leur création
> mais **sans bit d'équipement** — un carquois ne pouvait pas être porté (même défaut que
> la hache avant OBJ-05) ; `PlayerItem::GEAR_AMMO` le répare.
>
> **La prime de munition tombe d'elle-même, et c'est une conséquence à noter** : elle
> existait pour compenser les 90 à 230 gils/jour d'un archer face à un pyromancien qui ne
> paie rien. L'arbitrage du §9 septies ayant supprimé le coût récurrent, il n'y a plus
> rien à compenser — la prime, la calibration du prix contre le revenu du jour et la
> recette de flèches en lot deviennent **sans objet**, pas « reportées ».
>
> **Ce que le carquois ne fait pas : porter l'élément.** Correction du §9 quater — une
> munition qui porterait l'élément seule produirait, avec une flèche ordinaire, une action
> **sans élément**, donc hors de la case du domaine, donc **sans aucun passif d'arbre**.
> L'élément vient de la matéria ; la munition élémentaire le *remplacera* et reste à
> écrire.
> GAME_ARCHETYPES §2. Trois registres qui coûtent la même chose ne sont qu'un registre.
- [x] **Le carquois, pas les munitions** (arbitrage rendu au §9 septies) : **aucun coût
      récurrent en gils** *(ARC-04b)*. Pièce durable (`Item::ammoCapacity`), portée dans
      l'emplacement `ammo` — qui existait en donnée mais n'avait **aucun bit d'équipement**,
      donc aucun carquois ne pouvait être porté. Il **se vide dans la rencontre et se ramasse
      après** : la consommation vit dans le `Fight`, jamais sur l'objet
- [x] **La régénération des PM hors combat** — le curseur qui décide de tout l'équilibre
      solo (BALANCE §24.2, ouvert depuis 2026-07-29) *(ARC-04a)*. `ManaRegenManager`
      calqué sur `LifeRegenManager`, curseur en paramètre `zone.mana.regen_seconds`
      (défaut 6 s contre 12 s/PV), ancre posée à la sortie de chaque combat. Symétrie
      tenue : *les PV paient les coups reçus, les PM paient les gestes faits, et les deux
      se rechargent en temps réel*
- [x] **L'élément vient de la matéria, la munition le remplace** (correction du §9 quater)
      *(ARC-04b — la moitié qui compte : le carquois ne porte pas l'élément, donc aucun tir
      ne sort de la case de son domaine. La munition élémentaire reste à écrire)*
- [x] ~~**La prime de munition**~~ **sans objet** *(ARC-04b)* : elle compensait un coût
      récurrent en gils que l'arbitrage du §9 septies a supprimé. Plus de coût, plus rien à
      compenser
- [x] **La capacité de carquois** *(ARC-04b)* : déclarée par la pièce (20 pour le carquois
      de cuir, 30 pour le renforcé), donc `wind` a de quoi mordre — la contrainte ne se fait
      sentir que sur les **longues** rencontres, ce qui est l'intention
- [x] ~~**Le prix se calibre contre le revenu quotidien**~~ **sans objet** *(ARC-04b)* : la
      munition ordinaire n'a plus de prix. La question renaîtra avec la munition
      **élémentaire**, qui elle s'achètera
- [x] **Temps de reprise** : `Spell::cooldown` branché sur les techniques de mêlée
      *(ARC-04a)* — grille 0/1/2/3/4 par palier, et `energyCost: 0` : un geste ne facture
      que la ressource de son registre (GAME_MATERIA §2.3 bis)
- [x] Les PM restent la ressource des sorts, inchangés *(ARC-04a)*
- [x] ~~Croise ECO : les flèches en lot~~ **sans objet** *(ARC-04b)* : il n'y a pas de
      flèches à fabriquer. Les deux carquois existent déjà comme recettes de tanneur
- [x] Tests : aucune technique de mêlée sans reprise au-delà du geste d'entrée, aucune qui
      facture des PM, la grille suivie palier par palier *(ARC-04a, `RegisterResourceTest`)*
- [x] Tests : un carquois vide n'est **jamais un mur** — l'attaque d'arme reste gratuite et
      tout geste sans munition passe ; la réserve se vide dans la rencontre et repart pleine
      à la suivante *(ARC-04b, `QuiverResolverTest`)*

### ARC-05 — L'ancre d'échelle (L → 3 sous-phases | ★★★ | HAUTE) ◐

> **Découpé (règle 8) : ARC-05a l'instrument, ARC-05b la seconde ancre, ARC-05c la
> recalibration.** *On ne recalibre pas ce qu'on ne mesure pas* — et le jalon demande
> justement de toucher 253 gestes et 65 monstres. Les règles et leurs mesures se
> livrent seules, sans déplacer une valeur ; ARC-05c déplace les valeurs, et le canon
> (§0.2) prévient que ce passage-là se fait **par le simulateur d'ARC-17, jamais par
> une relecture à la main**. D'où la troisième coupe, décidée le 2026-08-05 : ARC-05b
> ne pouvait pas attendre ARC-17, et ARC-17 ne pouvait pas se juger sans l'ancre que
> ARC-05b lui donne.
>
> GAME_ARCHETYPES §6.4. Les gestes valent 1 à 12 points ; les monstres ont 11 à 3 200 PV.
> Des pourcentages posés sur des nombres qui n'ont pas de rapport entre eux ne veulent rien
> dire.
>
> **ARC-05a — livré le 2026-08-03.** `EncounterAnchor` pose la règle du §6.4 et la
> rend **calculable** : *un geste de palier n retire ~25 % des PV d'un adversaire
> commun de palier n*, plus les bandes de durée (commun 3-5, élite 6-10, boss 12-20).
> La cible se **dérive du gabarit du bestiaire** (BES-02) au lieu de vivre dans une
> table — recalibrer les PV d'un monstre déplace automatiquement ce qu'un geste doit
> valoir, et les deux ne peuvent pas diverger en silence. Un test fait l'aller-retour :
> un geste à la cible nettoie un commun en **4 tours**, au milieu de sa bande — la
> règle et les bandes disent bien la même chose.
>
> **L'écart est chiffré, et il est large** : sur le dégât médian par palier, les gestes
> livrés retirent **×4 (m1), ×6 (m2), ×7,6 (m3), ×12,5 (m4), ×9,4 (m5)** de moins que
> la règle n'exige. C'est le « les gestes valent 1 à 12, les monstres ont 11 à 3 200 »
> du canon, enfin mesuré. La base de référence entre en CI comme **cliquet** : l'écart
> peut se réduire librement, il ne peut plus s'aggraver en silence — et personne ne peut
> ajouter un geste sous-calibré sans le voir.
>
> **ARC-05b — livré le 2026-08-05.** La seconde moitié de l'ancre, celle que le §9 ter
> réclamait : *un archétype ne se juge pas sur un combat, il se juge sur la journée que
> la barre d'énergie autorise*. `DailyAnchor` convertit une journée dans la **seule
> monnaie commune** — du temps — et rend calculables les trois choses qui manquaient :
> le budget de rencontres qu'une journée autorise (dérivé des curseurs livrés : 240
> points d'énergie, un tiers au combat, une chasse à 5 → **les « ~16 combats » du canon
> se calculent au lieu d'être posés**), l'attente quotidienne d'un build (PV à 12 s, PM
> à 6 s), et **l'ancre de fonction** (correction 16) comme un juge appelable — le seul
> invariant qui ne se vérifie pas sur un archétype isolé.
>
> **Le résultat du jalon, et il n'était pas acquis : le curseur des PM est ce qui tient
> l'ancre de fonction.** Rejoué sur le relevé du §9 septies.2, l'instrument retrouve la
> colonne du canon **à la minute près** sur les six builds. Les cinq calibrés s'étalent
> alors de x1,81 — dans la borne de x2,0. **À PM gratuits, l'écart passe à x10,1** : le
> guérisseur paie 14 minutes quand le Mur en paie 142, et joue plusieurs fois plus de
> contenu que tout le monde. `zone.mana.regen_seconds` cesse d'être un confort posé par
> ARC-04a : c'est ce qui met l'entretien sur la même ligne que les trois autres. Réponse
> à **BALANCE §24.2**, ouvert depuis le 2026-07-29.
>
> Plus la **correction 14** (`AccordTierRule`) : l'assaut ouvre ses gestes au palier
> plein, contrôle / entretien / encaisse un cran en dessous, le palier d'entrée jamais
> raboté (la règle du jour 1 de GAME_MATERIA §3 s'y oppose). *La différence passe par
> les gestes, pas par les pourcentages.*
>
> **ARC-05c — reste à faire** : ramener l'écart vers 1, c'est-à-dire déplacer les
> valeurs — les dégâts des gestes et les PV de monstre, conjointement. **À livrer avec
> ou après ARC-17** : le canon (§0.2) prévient que la recalibration passe par
> `app:balance:simulate`, jamais par une relecture à la main, et l'ancre de fonction
> livrée ici est précisément le seuil que le simulateur aura à tenir.
- [x] Fixer la **durée cible en tours** (commun 3-5, élite 6-10, boss 12-20) et en dériver
      les valeurs, plutôt que l'inverse *(ARC-05a — la règle est posée et calculable ;
      appliquer les valeurs est ARC-05b)*
- [x] **La seconde ancre** (correction du §9 ter) : le **coût d'une rencontre en ressource,
      rapporté au budget du jour**. Mesuré, un Soldat et un Guérisseur tiennent onze tours
      tous les deux et sortent avec une barre comparable — mais l'un n'a rien dépensé et
      l'autre a vidé 108 PM sur 120. Sur les ~16 combats d'une journée, ils n'ont rien à
      voir. C'est cette ancre qui donne leur sens à `thrift` et `wind` : ils agrandissent
      une **journée**, pas un combat *(ARC-05b, `DailyAnchor` — la journée convertie en
      temps d'attente ; les « ~16 combats » se **dérivent** des curseurs livrés au lieu
      d'être posés)*
- [x] Calibrer en consequence la **régénération des PM hors combat** — chantier deja ouvert
      en BALANCE §24.2, et qui n'avait pas d'archetype a servir *(ARC-05b — le curseur
      livré par ARC-04a reçoit sa justification chiffrée : sans lui l'écart d'attente
      passe de x1,81 à **x10,1**. C'est lui qui tient l'ancre de fonction)*
- [x] Règle unique : *un geste de palier n retire ~25 % des PV d'un adversaire commun de
      palier n* *(ARC-05a — dérivée du gabarit BES-02, jamais d'une table ; l'écart mesuré
      est de ×4 à ×12,5 et entre en CI comme cliquet)*
- [ ] Recalibrage conjoint des PV de monstre (croise **BES-01**, le gabarit `tier × rank`)
      et des valeurs de gestes *(**ARC-05c**, avec ou après ARC-17)*
- [ ] Tests : un **simulateur de combat** en test — la durée moyenne par palier, pas un
      tableau de valeurs relu à la main. C'est la seule forme de test qui attrape une
      régression d'équilibrage *(**ARC-17**)*
- [ ] **Il doit produire la table croisée du §9 sexies**, pas des durées isolées : c'est
      la comparaison des six builds qui a revele le desequilibre, aucun exercice individuel
      ne pouvait le voir *(**ARC-17**)*
- [x] **L'ancre de fonction** (§9 sexies.3) : a arbre complet et equipement egal, les quatre
      fonctions enchainent le meme nombre de rencontres par jour et en sortent dans un etat
      comparable. Ce qui diffère, c'est **comment on paie** *(ARC-05b — posée comme un
      **rapport** (x2,0 au plus, du simple au double) et non comme une minute : c'est ce
      que §0.2 range parmi les nombres qui survivent à une recalibration.
      `DailyAnchor::isWithinFunctionAnchor()` est le juge qu'ARC-17 appellera sur sa table)*
- [x] **Le palier des accords suit la fonction** : assaut au palier plein, controle /
      entretien / encaisse un palier en dessous (~ −25 %). Mesuré : ramène l'ecart de
      « 9 tours contre 11 » à « 7 tours contre 11-14 », sans qu'aucun levier ne bouge
      *(ARC-05b, `AccordTierRule` — un cran et un seul, et le palier d'entrée jamais
      raboté : la règle du jour 1 de GAME_MATERIA §3 s'y oppose. L'**application** aux
      arbres se fait à leur écriture, ARC-07 puis ARC-08)*
- [x] **Trancher la valeur de la vitesse** (§9 sexies.4) — une chasse coute 5 points
      d'energie quel que soit le nombre de tours, donc tuer vite ne rapporte **rien** en
      solo. Option recommandee : les **rencontres à fenêtre** (un boss se termine en 12-20
      tours ou pas du tout). Consequence a porter dans GAME_DUNGEONS et GAME_BESTIARY : un
      boss doit avoir assez de PV pour qu'un archetype lent n'en vienne pas a bout
      *(**tranché le 2026-08-02** dans GAME_ARCHETYPES §9 sexies.4 : option A adoptée,
      **ciblée élites / boss / étapes de donjon, jamais le tout-venant**. Les bandes de
      durée sont livrées depuis ARC-05a (`EncounterAnchor::TURN_BANDS`) ; **poser la
      fenêtre comme une règle de rencontre est du contenu**, à brancher sur DON-03 et le
      rang Elite/Boss du bestiaire — reporté là où il s'écrit, pas dans le moteur ARC)*
- [x] Consigner le résultat dans [../BALANCE.md](../BALANCE.md) (§4 est aujourd'hui
      aspirationnel : les fixtures ne le suivent pas) *(ARC-05b — §24.2 reçoit sa réponse
      pour le curseur des PM ; le reste du chantier (coûts des sorts par palier, durée
      moyenne en tours) reste ouvert et attend ARC-17)*

### ARC-06 — L'échelle de coût et le gain de points (M → 2 sous-phases | ★★ | HAUTE) ✅

> **Découpé (règle 8) : ARC-06a l'échelle et la table, ARC-06b la distribution.** Le
> jalon supposait qu'il suffisait d'indexer un gain existant. **Il n'existe pas** :
> mesuré le 2026-08-05, le combat ne rapporte **aucun point de domaine** — seule la
> matéria gagne de l'expérience (`MateriaXpGranter`). Poser la table est une chose,
> créer le canal en est une autre (écouteur, et un reste à conserver puisque la table
> descend à 0,25) ; les deux se livrent séparément.
>
> **ARC-06a — livré le 2026-08-05.** Les 24 arbres de combat portaient **23 valeurs de
> coût distinctes** (5, 15, 20, 30, 35, 45, 55, 70, 85, 90, 110, 120, 200…), dont cinq
> seulement sur l'échelle : *deux nœuds à 30 et 35 points ne disent pas deux paliers,
> ils disent qu'on a dosé à la main — et un dosage ne se calibre pas, il se re-dose.*
> `SkillCostScale` ferme l'échelle sur le modèle de `CombatLever` (ARC-03a), et **192
> coûts** rejoignent leur barreau : un coût exprime désormais un **palier**.
>
> **Le résultat qui valide la règle : l'arbre de référence du canon tombe pile sur la
> cible.** Le Pyromancien — celui que le §6.3 déroule comme patron et que le §0.1
> chiffrait à 465 points — vaut exactement **390** une fois ses nœuds sur l'échelle,
> sans qu'on ait touché à un seul d'entre eux. L'échelle n'a pas été taillée pour le
> résultat : elle était déjà là, sous les dosages.
>
> Plus **le calendrier, enfin vérifié de bout en bout** (`DomainPointYield`) : la
> journée vient de `DailyAnchor` (ARC-05b), le total de `SkillCostScale`, et la table
> du gain fait le pont — aucun des trois n'est recopié. Sur les curseurs livrés,
> 16 rencontres T2 par jour rendent 8 points, soit **49 jours = 7,0 semaines** pile, ce
> que le canon annonçait. Et *on ne monte pas un arbre en tapant des rats* devient un
> chiffre : chasser un palier en dessous **double** le temps (98 jours contre 49).
>
> **ARC-06b — livré le 2026-08-06.** Le combat rapporte enfin des points. Le canal
> manquait en entier : `DomainPointGranter` écoute `MobDeadEvent` comme
> `MateriaXpGranter` (invocations exclues, coop partagée avec le même plancher de
> 1, joueur mort exclu), et le gain passe par le **reste en quarts**
> (`DomainExperience::addQuarters()`) sans lequel une chasse de palier 1 ne
> rapporterait jamais rien — cent rencontres T1 valent exactement 25 points, et le
> test le vérifie plutôt que de le croire.
>
> **La case se décide au geste, pas à la mort du monstre** : `MobDeadEvent` ne
> porte que le monstre. `CombatGestureLedger` fait le pont dans les métadonnées du
> combat, et sa forme porte la règle — **une entrée par joueur, écrasée à chaque
> geste**. C'est elle qui interdit la multiplication : enchaîner six gestes de six
> cases rapporte une fois, dans la case du dernier. Un geste sans case (élément
> `None`, mains nues) **efface** la ligne, sinon ouvrir au feu puis finir à mains
> nues garderait le crédit du feu.
>
> **Ce que la décision supposait, et que la grille ne tient pas.** Le point 1 dit
> qu'« un geste désigne une case unique de la grille des 24 arbres ». C'est vrai de
> la **case**, pas de l'arbre : la fonction (ARC-01) est le troisième axe, et les 24
> arbres se répartissent sur **18** cases — trois se partagent l'eau × sorts
> (Hydromancien, Guérisseur, Marémancien), et deux partagent quatre autres cases. Le
> geste ne porte pas de fonction, et l'intention d'ARC-11a ne la désigne pas non
> plus : les palettes de `domain_roles.yaml` déclarent des **minimums** (l'assaut
> exige 3 intentions de dégât, le contrôle 1), pas une partition.
>
> On applique donc le départage que la décision a **déjà** rendu pour son point 3,
> où la même ambiguïté se pose sur les nœuds de port partagés : **le premier arbre
> ouvert**. Un arbre non ouvert ne reçoit rien — le parchemin reste la porte. L'écart
> entre en CI comme **cliquet** (`CombatGestureCaseTest`) : une case peut porter
> moins d'arbres, jamais plus, sans qu'on le voie.
>
> **Question ouverte, portée au bilan** : *faut-il que le geste porte une fonction,
> pour que sa case désigne un arbre sans départage ?* Elle se pose à l'écriture des
> arbres (ARC-07/08) et se mesure au simulateur (ARC-17). Tant qu'elle n'est pas
> tranchée, un joueur qui mène deux arbres de la même case crédite toujours le
> premier ouvert — ce qui est lisible, mais n'est pas un choix.
>
> **Hors périmètre, assumé** : le donjon de groupe a son propre modèle de combat
> (DON-02, `DungeonActionResolver`) et ne dispatche pas `MobDeadEvent` — il ne
> distribue donc aucun point. Le jalon nomme `MobDeadEvent` explicitement ; brancher
> le donjon est un jalon à part, et le déclarer ici sans le faire donnerait
> l'illusion d'une couverture qui n'existe pas.
>
> **Ce qui manque est en amont : à quel arbre les points vont-ils ?** Les documents
> fixent le *taux* (T1 0,25 · T2 0,5 · T3 1 · T4 2) et ne disent **nulle part** qui le
> reçoit. Pour la récolte et l'artisanat la question ne se pose pas — le geste
> appartient à un métier, et `DomainExperienceEvolver` lit `getDomainBySkillAction()`.
> Pour le combat il n'y a pas d'équivalent : une rencontre enchaîne plusieurs gestes,
> venus de matéria que **plusieurs arbres** peuvent avoir ouvertes (ARC-02b a compté
> **39 gestes ambigus**, ouverts par des arbres de registres différents — `magnetic-pull`
> est au Soldat en mêlée *et* à l'Ingénieur en tir).
>
> Trois questions à trancher, et elles se tiennent :
>
> 1. **Le porteur du gain** — le ou les arbres qui ont ouvert la matéria employée ? tous
>    les arbres ouverts du joueur ? l'arbre de la pièce qui portait la matéria ?
> 2. **Le partage en cas d'ambiguïté** — un geste ouvert par trois arbres rapporte-t-il
>    à trois arbres (et un joueur qui les mène tous progresse trois fois plus vite), ou
>    se divise-t-il (et mener un seul arbre devient le choix optimal) ?
> 3. **L'attaque d'arme de base**, qui ne vient d'aucune matéria — crédite-t-elle
>    l'arbre qui enseigne le port de l'arme, ou rien du tout ? *Rien du tout* rendrait un
>    combat gagné à mains nues stérile ; *l'arbre du port* ferait progresser un arbre
>    qu'on ne joue pas.
>
> Aucune de ces réponses n'est neutre : elles décident si un joueur a intérêt à mener
> **un** arbre ou **quatre**, ce que GAME_PROGRESSION §1 (« il en mène deux à quatre »)
> suppose sans le garantir.
>
> **Décision (2026-08-06) — les trois questions sont tranchées :**
>
> 1. **Le porteur du gain est la case du geste.** Le gain va **en entier** à l'arbre
>    `élément × registre` du geste employé (`Spell::element` + `Spell::register`,
>    livrés par ARC-02) — un geste désigne une case unique de la grille des 24
>    arbres de combat. On progresse dans ce qu'on **joue**, pas dans ce qu'on a
>    acheté : jouer des gestes de feu fait progresser le feu, quel que soit l'arbre
>    qui a ouvert la matéria.
> 2. **Le partage est sans objet.** Les 39 gestes ambigus le sont par leurs
>    *ouvreurs*, pas par leur nature : `magnetic-pull` employé en combat a un
>    registre et un élément, donc une case. Jamais de division, jamais de
>    multiplication — le multi-arbre reste neutre, comme GAME_PROGRESSION §1 le
>    suppose.
> 3. **L'attaque d'arme de base crédite l'arbre du port de l'arme employée**, gain
>    plein. Précision d'implémentation : un port pouvant être enseigné par
>    plusieurs arbres, le gain va à l'arbre où le joueur a **effectivement appris**
>    le nœud de port de cette arme (le premier appris si plusieurs). Mains nues :
>    aucun nœud de port → aucun point — le repli reste un repli.
> GAME_ARCHETYPES §6.2. Un arbre coûte 465 points pour un plafond global de 500.
- [x] Échelle **0 / 10 / 25 / 50 / 100** (+ 150 dormant) sur les 24 arbres de combat
      *(ARC-06a — `SkillCostScale`, échelle fermée et refusée à la lecture ; 192 coûts
      déplacés, 23 valeurs distinctes ramenées à 6)*
- [x] **Le gain de points suit le palier de l'adversaire** : T1 0,25 · T2 0,5 · T3 1 ·
      T4 2 (paliers de GAME_BESTIARY). On ne monte pas un arbre en tapant des rats
      *(ARC-06a, `DomainPointYield` — la table est posée, **en quarts de point** pour
      qu'un joueur de palier 1 ne gagne pas zéro à chaque arrondi. La **distribution**
      est ARC-06b — livrée : `DomainPointGranter` sur `MobDeadEvent`)*
- [x] **La distribution** : le gain va en entier à la case du geste joué, un arbre par
      rencontre et par joueur *(ARC-06b — `CombatGestureCase` lit `Spell::element` +
      `Spell::register` ; l'attaque d'arme crédite l'arbre du port appris, mains nues
      rien. `CombatGestureLedger` retient la case **au geste**, `MobDeadEvent` ne
      portant que le monstre. Le reste vit en quarts sur `DomainExperience` : cent
      rencontres T1 = 25 points, rien ne se perd. Départage d'une case à plusieurs
      arbres : le premier ouvert — voir la question ouverte ci-dessus)*
- [x] Vérifier le calendrier visé (entrée jour 1 · palier 1 semaine 1 · palier 2
      semaines 3-4 · palier 3 semaines 6-8 · capstone mois 3) contre le budget d'énergie
      réel de GAME_PROGRESSION §5 *(ARC-06a — vérifié de bout en bout : **49 jours =
      7,0 semaines** sur de la faune T2, exactement ce que le canon annonce ; T1 en
      demande le double)*
- [x] Tests : coût de chaque nœud dans l'échelle ; coût total d'un arbre = 390 points
      *(ARC-06a — le premier invariant tient sur les 24 arbres ; le second tient sur le
      Pyromancien et entre en CI comme **cliquet** avec sa liste d'attente nommée : les
      23 autres arbres valent 240 à 525 points parce qu'il leur manque des **nœuds**,
      pas des coûts — le gabarit en écrit 18 quand ils en portent 13 à 18. ARC-07 et
      ARC-08 referment l'écart, qui ne peut plus grandir en silence)*

### ARC-07 — Les quatre arbres patrons (L → 4 sous-phases | ★★★ | HAUTE) ✅

> **Découpé (règle 8) : un arbre par sous-phase.** ARC-07a le Pyromancien, ARC-07b le
> Guérisseur, ARC-07c le Soldat, ARC-07d l'Archer. Chacun réécrit 15 nœuds, crée les gestes
> que sa fourche exige et déplace des ratchets — au-delà des ~200 lignes de données que la
> règle 8 autorise en une passe, et surtout au-delà de ce qu'une seule relecture tient.
>
> **ARC-07a — livré le 2026-08-06.** Le Pyromancien (feu × sorts × assaut, *« le Foyer »*)
> est le **premier arbre du jeu dont les passifs ne sont plus des statistiques plates**.
> `SkillFixtures` câble enfin la colonne `levers`, vide depuis ARC-03a.
>
> **Les deux branches tombent sur 50 pb pile**, sans qu'on ait eu à doser : la Braise
> (`power` 17 · `critical` 9 · `critical_power` 15 · `grip` 9) et l'Éclat (`power` 17 ·
> `critical` 9 · `critical_power` 6 · `pierce` 9 · `tempo` 9). Aucun plafond atteint, la
> teinte `grip` à 9 ≤ 10 sur **un seul** levier, et 41 pb dans la palette d'assaut.
>
> **Chaque branche ouvre son geste** (§6.1 bis, règle 5) — le *Brasier* est créé (le feu qui
> reste, que `grip` allonge), la *Nova de feu* passe sur l'Éclat. Sans cela la fourche serait
> une décoration : *deux branches qui ne diffèrent que par leurs passifs produisent le même
> combat, au tour près.*
>
> **Un effet de bord non prévu, et il vaut d'être noté** : déclarer le Mur de feu en
> `entrave` sort **deux** arbres de la liste « ne sait que frapper » — le Pyromancien et
> l'**Artificier**, qui ouvre le même accord. *Un accord partagé se déclare une fois et sert
> ses voisins*, exactement comme ARC-02b l'avait mesuré sur le registre. La liste passe de
> 10 à 8.
>
> **Ce que la CI a appris au jalon, et qui a changé son périmètre.** L'arbre devait tomber
> sur **390 points exactement** (`SkillCostScaleTest`, le seul invariant de coût qui existe),
> et cette contrainte rend la chirurgie d'accords **obligatoire** plutôt que reportable :
> quatre accords surnuméraires ont dû partir (Feu, Inferno, Souffle du dragon, Explosion
> solaire) pour que `4×10 + 4×25 + 6×50 + 100 = 540` déclarés retombent sur les **390** qu'un
> personnage paie.
>
> **Et le test devait apprendre la fourche** — ARC-14a l'avait annoncé en chiffres sans que
> personne n'ait eu à l'appliquer : *sans fourche, un arbre au gabarit coûte 540 points.*
> Compter les six nœuds du palier 3 ferait échouer **le premier arbre qui obéit au gabarit**,
> c'est-à-dire punir la conformité. `learnableTotal()` retranche désormais une branche.
> Défaut trouvé en l'écrivant : une expression régulière qui traverse les frontières de nœud
> apparie le coût d'un nœud sans branche avec la branche du suivant — elle rendait 100 et 150
> là où les deux branches valent 150, et *le total tombait juste par accident*.
>
> **Deux accords sur quatre survivent ailleurs** : `dragon-breath` est ouvert par un second
> arbre, et `materia_inferno` n'était plus résolvable depuis MAT-01 (le slug dérivé est
> `m<n>-inferno`, pas `materia_inferno`) — la fusion air+feu échouait déjà en silence, ce
> jalon ne fait que le rendre vrai. Seul `PlayerItemFixtures` a dû lâcher `materia_flamer`.
>
> **Reste nommé** : l'arbre déclare encore **deux accords de plus** que les 6 du gabarit, et
> c'est l'arithmétique qui les réclame — les échelons de port sont *générés* hors du corps de
> l'arbre (ONB-20b), donc le compte des 390 ne tombe juste que si le corps en déclare quatre
> par palier. C'est un écart entre la **table du §9.1** et l'**arithmétique du §6.1**, qui se
> referme avec **DOM-09** : un arbre de sorts hérite aujourd'hui de **six** échelons générés
> (bâton, baguette, tissu) là où le gabarit en veut deux.
>
> **ARC-07b — livré le 2026-08-06.** Le Guérisseur (eau × sorts × entretien, *« le
> Ressac »*), et **le premier arbre du jeu qui dépose**. La loi du dépôt (ARC-11b) était
> écrite, opposable — et sans objet : aucun geste ne portait `SpellScope::Group`, qui **ne se
> dérive jamais**. La *Marée* et la *Grande Marée* sont les deux premiers gestes de portée
> collective, et l'entretien devient jouable dans un donjon semi-synchrone.
>
> **Les deux branches tombent sur 50 pb** : le Ressac (`mending` 17 · `thrift` 3 · `wind` 6 ·
> `ward` 6 · `recovery` 9 · `guard` 9) et la Marée (`mending` 17 · `thrift` 12 · `wind` 6 ·
> `ward` 15). La teinte `guard` ne vit que dans le Ressac — *le soigneur de donjon n'a pas de
> main gauche à donner à un bouclier* — et la Marée est au **palier 2**, donc les deux
> branches l'ont : un guérisseur sert son groupe quel que soit son choix.
>
> **Le second accord d'entrée a été remplacé, pas complété** : il portait un soin, il porte
> le Jet d'eau, qui blesse et applique **Trempé**. Le Guérisseur sort donc du cliquet
> d'ARC-13b-a (son capstone devient atteignable au tour 2) *et* tient la loi 1 du §5.1 — un
> arbre d'entretien a besoin d'un geste qui finisse le combat autant que d'un geste qui le
> tienne.
>
> **Deux écarts au canon, nommés plutôt que maquillés** : la branche Ressac ouvre une
> **protection** au lieu de la *Dissipation*, parce qu'aucune mécanique de dissipation
> n'existe dans le moteur et qu'en inventer une serait une décision de conception (elle
> revient avec ARC-18) ; et le capstone se déclenche sur `target_below_quarter_life` plutôt
> que « sous 40 % », le vocabulaire de `SkillCondition` étant fermé et le §0.2 rangeant ce
> seuil parmi les nombres qu'ARC-17 recalculera.
>
> **La chirurgie d'accords a été indolore, contrairement au Pyromancien** : les accords
> retirés sont tous ouverts par un autre arbre, donc aucune matéria ne disparaît. La leçon
> d'ARC-07a a servi — les prérequis ont été vérifiés sur les 561 nœuds avant le push.
>
> **ARC-07c — livré le 2026-08-06.** Le Soldat (métal × mêlée × encaisse, *« la Ligne »*),
> **premier arbre de mêlée écrit au gabarit**. Le canon prévient que cet archétype *n'existe
> pas sans la décision 1* : sans matéria de technique, ses passifs bornés à la mêlée ne
> qualifient **aucune action**. ARC-02 a livré le registre ; ce jalon écrit l'arbre qui s'en
> sert.
>
> **Les deux branches tombent sur 50 pb** : le Mur (`guard` 14 · `hit` 9 · `ward` 12 ·
> `life` 15) et la Ligne mobile (`guard` 14 · `hit` 9 · `ward` 3 · `life` 6 · `dodge` 9 ·
> `power` 9 en teinte). **La palette effective du §5.0 se vérifie d'elle-même** : `guard`
> plafonne à 15, le capstone en consomme 14, il ne reste **1 pb** — un arbre d'encaisse
> achète `guard` à son sommet ou jamais. La contrainte produit toute seule la meilleure
> fourche possible pour cette fonction : **éviter ou absorber**.
>
> **Trois protections de mêlée sont créées — les premières du jeu.** Sans elles, un
> archétype d'encaisse n'avait rien à jouer sur son tour défensif, et le §9 bis avait montré
> qu'une garde qui ne couvre que son propre tour *punit l'encaisse de se défendre*. Le Mur de
> boucliers et le Rempart sont de portée `le groupe` : *une rencontre à PV partagés ne se
> « prend » pas, elle s'amortit.*
>
> **Un défaut trouvé en écrivant l'arbre, et antérieur au jalon** : l'accord d'entrée qui
> portait l'Entaille était `magnetic-pull`, de registre **`Spell`**. Un arbre de mêlée dont le
> geste marqué est un sort ne qualifie pas ses propres passifs (invariant 7 d'ARC-02b), et le
> §9.3 veut que **tous** les accords du Soldat soient des techniques. La marque passe sur
> `sharp-blade` ; `magnetic-pull` reste ouvert par l'Ingénieur, à qui son registre convient.
>
> **Le Soldat sort de trois cliquets d'un coup** : « ne sait que frapper » (8 → 7), et ses
> deux écarts de palette (14 → 12).
>
> **ARC-07d — livré le 2026-08-06. ARC-07 est clos.** L'Archer (air × distance × assaut,
> *« la Portée »*), et **le seul des quatre dont la ressource est matérielle** : il ne paie ni
> en PM ni en tours, il paie en munitions, et sa courbe est une **cadence décroissante** là où
> le Pyromancien a un pic et le Soldat un plateau. Trois différences structurelles avec le
> Pyromancien malgré la fonction partagée, et **aucune numérique** — ce que le §9.5 exige de
> tout couple d'arbres de même fonction.
>
> **Aucun sort neuf, et c'est le seul des quatre dans ce cas** : les 7 gestes de registre
> `Ranged` livrés couvrent exactement les 6 accords du gabarit plus le dormant. Le Pyromancien
> avait dû créer le Brasier, le Guérisseur les deux Marées, le Soldat trois protections.
>
> **Les deux branches tombent sur 50 pb, et c'est le plafond qui les a écrites** : le Guet
> (`power` 17 · `critical` 9 · `critical_power` **15** · `tempo` 9) et la Volée (`power` 17 ·
> `critical` 9 · `critical_power` 6 · `pierce` 9 · `wind` 9 en teinte). `critical_power`
> plafonne à 15 et le Guet y est **pile**, donc un troisième nœud de critique est impossible
> dans cet arbre et la Volée est **obligée** de chercher ailleurs. Même mécanique que le
> `guard` à 15 du Soldat : *le plafond produit l'opposition, on ne la dose pas.* La teinte
> `wind` est la seule du jeu qui porte sur la **ressource** — et depuis que le §9 septies a
> retiré aux munitions leur coût en gils, elle ne rend pas de l'argent, elle rend des tours de
> tir dans le combat où l'on est.
>
> **Un défaut trouvé en écrivant l'arbre, et antérieur au jalon** : trois accords de l'Archer
> — `air-current`, `wind-scythe`, `vacuum-blade` — sont de registre **`Spell`**. C'est le
> `magnetic-pull` du Soldat dans un autre arbre. Ils partent, et **aucune matéria ne perd son
> canal** : le Foudromancien ouvre les deux premiers, le Vagabond le troisième.
>
> **Le Tir entravant garde ses dégâts et déclare son intention.** Le canon le veut en
> `entrave` (§9.4) quand le §1.1 veut qu'une marque soit portée par un geste qui **blesse** ;
> les deux tiennent ensemble parce que *l'intention est une décision d'auteur et le dégât une
> donnée*, et l'invariant d'ARC-13b-a vérifie le second, jamais la première. Conséquence
> assumée plutôt que masquée : par `LeverIntentLaw`, le `power` de l'arbre **n'amplifie pas ce
> geste**. On ne le joue pas pour tuer, on le joue pour le Déséquilibre — qui allume le
> capstone dès le tour 2, gratuitement.
>
> **L'Archer sort de « ne sait que frapper » (7 → 6)** et rejoint les arbres tenus au gabarit
> (3 → **4**).
>
> GAME_ARCHETYPES §9. Pyromancien (assaut), Guérisseur (entretien), Soldat (encaisse),
> Archer (assaut/distance) — un par fonction, trois registres.
- [x] Les quatre arbres écrits au gabarit : 15 nœuds, 5 accords, 7 passifs, 2 échelons de
      port, 1 dormant *(ARC-07a/b/c/d — **4/4** : le Pyromancien, le Guérisseur, le Soldat et
      l'Archer. Les échelons de port ne sont pas
      écrits ici : ils sont générés et partagés (ONB-20b), et le canon les range hors budget)*
- [x] Les accords choisis **par rôle dans le combat**, jamais par niveau de sort
      *(ARC-07a/b/c/d — chaque branche ouvre **son** geste (§6.1 bis, règle 5), et c'est le
      rôle qui décide : le Brasier reste quand la Nova tombe, la Grande Marée provisionne
      quand la Dissipation répond. Le niveau de sort ne décide de rien — il ne sert qu'à
      dériver le palier de la matéria)*
- [x] Les capstones conditionnels, condition atteignable au tour 2 avec les seuls accords
      d'entrée *(ARC-07a — le Foyer entretenu : `power` +14 pb *contre une cible qui brûle*.
      La Flammèche applique la Brûlure et coûte 0 point, donc la condition est **fréquente**
      (×1,4) et non rare — l'écart n° 11 tranché par la décision 23)*
- [x] Ce sont les 4 domaines de combat **nourris en contenu** (GAME_PROGRESSION §7.1) : le
      contenu matéria, butin et donjon se concentre sur eux *(ARC-07a/b/c/d — les quatre
      arbres portent 24 accords écrits, dont 8 gestes créés pour eux)*
- [x] Tests : les 10 invariants du §12 passent sur les quatre *(ARC-07a — `PatronTreeContractTest`, en **cliquet inverse** : la liste des arbres tenus ne peut que grandir)*

### ARC-08 — Les vingt autres arbres (M → sous-phases | ★★ | MOYENNE) ◐
> GAME_ARCHETYPES §11.3. Conversion mécanique plutôt que réécriture — le jeu est en pur
> dev, aucune compatibilité n'est due.
>
> **ARC-08a — le Nécromancien, livré le 2026-08-08.** Le premier des vingt, et il n'est pas
> pris au hasard : **les quatre patrons ne couvrent pas le contrôle.** ARC-07 a livré
> l'assaut *deux fois* (Pyromancien, Archer), l'entretien (Guérisseur) et l'encaisse
> (Soldat) ; la fonction contrôle — **sept arbres sur vingt-quatre**, la deuxième du jeu en
> nombre — n'existait qu'en document. Or ARC-17 doit générer *un build de référence par
> fonction* et tenir le seuil « aucune fonction dominante dans les deux colonnes de la
> matrice » : **le simulateur ne pouvait ni l'un ni l'autre**, et la dépendance ARC-08 →
> ARC-17c ne figurait pas au graphe. C'est le même renversement qu'ARC-14a avait trouvé sur
> ARC-07, et pour la même raison : *un jalon de mesure ne peut pas mesurer ce qui n'est pas
> écrit.*
>
> L'arbre suit GAME_TREE_ANATOMY §12 au nœud près et tombe sur ses nombres **sans dosage** :
> 390 points, deux branches à 50 pb, trois plafonds atteints pile (`grip` 20/20, `tempo`
> 12/12 côté Linceul, `thrift` 15/15 côté Veillée). Le Linceul est le **seul arbre écrit
> sans teinte** — ses 50 pb tiennent dans la palette du contrôle.
>
> **Le plafond a écrit la fourche**, pour la troisième fois (après le `guard` du Soldat et le
> `critical_power` de l'Archer) : `grip` est le levier principal du contrôle, donc le candidat
> naturel de sa fourche — et il est *impossible*, le capstone en consommant 14 pour un plafond
> de 20. C'est le corollaire 2 du §7.1, celui que cet arbre avait produit sur le papier :
> ***le levier principal d'un arbre est presque absent de sa propre fourche***.
>
> **La fourche oppose le solo au donjon** et non deux dosages (§12.3) — le Linceul tient
> l'ennemi et le tient seul, la Veillée tient la durée et sert le groupe.
>
> **Un geste hostile qui ne blesse pas, le premier du jeu.** Les trente gestes à zéro dégât
> livrés sont **tous amicaux** (boucliers, régénérations) ; le Voile de cendre pose un statut
> sur un adversaire sans lui retirer un point de vie, et c'est la signature de la fonction :
> *ses trois premiers tours ne tuent rien*. Ce qui a obligé à corriger un invariant :
> `ElementalMarkReachabilityTest` exigeait qu'une marque d'entrée soit **portée par un geste
> de dégât**, quand la loi d'ARC-13a en offre **deux membres** — *au moins deux tours, **ou**
> portée par un geste de dégât*. Le test était plus strict que la loi qu'il citait, sans que
> rien ne le dise, parce qu'aucun arbre converti n'avait encore ouvert sur une entrave pure.
> Il **appelle** désormais `ElementalMark::durationIsLegal()` au lieu de la réécrire :
> ***une règle recopiée dérive de son original en silence*** — le même défaut qu'ARC-11b-b
> avait trouvé dans un test qui ne parcourait que sa propre liste.
>
> **Le contenu manquait moins que la déclaration** : la palette de contrôle réclame deux
> `entrave` et le Nécromancien n'en ouvrait aucune — mais deux de ses quatre entraves sont des
> gestes **déjà livrés** dont l'intention était seulement muette (la dérivation lit le dégât
> d'abord, donc une malédiction qui blesse se rangeait en `dégât`). Il sort des deux cliquets
> d'un coup : « ne sait que frapper » et « palette non tenue ».
>
> **Nommé, pas fait** : le Serviteur d'ossements est écrit comme un geste qui laisse quelque
> chose derrière lui, **pas comme un familier** — la forme n'existe pas (`DepositLaw` ne
> dépose que la portée `Group` et la protection), et c'est ARC-18 qui l'ouvrira. Même
> discipline qu'ARC-07b avec la Dissipation.
> **ARC-08e — le Vagabond, livré le 2026-08-18. La grille est couverte.** Le cinquième arbre au
> gabarit remplit `control × melee`, et le simulateur joue désormais **toutes les cases que le
> jeu possède**.
>
> **Mesure faite en l'ouvrant, et elle corrige l'instrument** : les 24 arbres de combat
> n'occupent que **9 des 12 cases** de la grille fonction × registre — il n'existe aucun arbre
> d'encaisse à distance, aucun d'encaisse en sorts, aucun d'entretien au tir. Le simulateur
> comptait sur douze et se croyait plus incomplet qu'il ne l'était : *un dénominateur faux rend
> une couverture pessimiste, et une couverture pessimiste se lit comme une excuse.*
> `ReferenceBuildFactory::reachableCells()` le dérive des domaines plutôt que de l'écrire.
>
> **Il était dans deux listes d'attente à la fois, et pour la même raison** : la palette du
> contrôle exige deux `entrave`, et l'arbre n'en portait aucune — que des soins et des
> protections. *Un arbre de contrôle qui ne sait que soigner n'est pas un arbre de contrôle.*
> Deux gestes écrits (**Croc-en-jambe**, qui porte enfin Déséquilibre, et **Vent contraire**)
> l'en sortent des deux.
>
> **Le cliquet d'ARC-05a a fait son travail, deux fois.** Écrire *Vent contraire* à 2 dégâts a
> creusé l'écart à l'ancre du palier 3 (×7,6 → ×9,5) ; et faire passer *Courant d'air* du palier
> 1 au palier 3 sans toucher sa valeur l'a creusé de nouveau — ***changer le palier d'un geste
> sans changer sa valeur, c'est l'affaiblir sans le dire.*** Les deux sont posés à la médiane de
> leur palier. Le cliquet n'est pas un obstacle : c'est ce qui empêche d'ajouter un geste
> sous-calibré sans le voir.
>
> **ARC-08d — le Gardien, livré le 2026-08-18.** Le quatrième au gabarit, et la case
> **`upkeep × melee`** : le simulateur passe à **8 cases sur 12**. GAME_TREE_ANATOMY §14 l'écrit
> **en paire avec le Défenseur**, sur la même case d'élément et de registre — *seule leur
> fonction diffère* —, ce qui démontre le troisième axe sans qu'un seul chiffre ait à le faire.
>
> **Il est le seul arbre du jeu dont le capstone garde ×2,0** (décision 23), et il le mérite :
> sa condition — *le combat dure* — est fausse dans toutes les rencontres de la bande 3-5,
> c'est-à-dire dans le tout-venant. C'est la contrepartie exacte de son coût structurel, et elle
> tombe sans qu'on ait rien ajusté : *la fonction dont la promesse est la durée est celle dont le
> sommet se paie en durée.* Le vocabulaire fermé des conditions gagne donc `long_fight`, dans la
> colonne ×2,0.
>
> **Quatre gestes à écrire, quand le canon en annonçait deux.** Le §14.2 prévoyait Racines et
> Sève de pierre ; il fallait aussi le Bouclier terreux et le Bouclier magique, **partagés avec
> le Géomancien** donc de registre `spell`. C'est la troisième fois que la règle d'ARC-08b décide
> du contenu à écrire — *un accord partagé garde le registre de celui qui l'a ouvert le premier*
> —, et la marque de la terre déménage avec : **Alourdi passe sur le Jet de cailloux**, le seul
> accord d'entrée que l'arbre possède en propre.
>
> **La fourche prend la meilleure version, celle que le document recommande lui-même** : depuis
> l'arbitrage de l'écart n° 13, le Rempart échange `thrift` contre `wind` et oppose la reprise
> **raccourcie** à la reprise **sautée**. Deux façons de payer le même coût de registre.
>
> **Un bénéfice de voisinage, mesuré** : convertir les gestes du Gardien à la mêlée a fait sortir
> le **Défenseur** de la liste d'attente des registres, et l'a fait passer de 0 à 1 `protect` sur
> les 2 que sa palette exige. *Convertir un arbre sert ses voisins de case* — le même effet que
> le Chevalier avait tiré du Soldat.
>
> **ARC-08c — l'Artificier, livré le 2026-08-18.** Le troisième au gabarit, et la case
> **`control × ranged`** : le simulateur passe à **7 cases sur 12**. GAME_TREE_ANATOMY §13 le
> choisit parce qu'il croise le **test du voisin sur trois axes à la fois** — élément et marque
> avec le Pyromancien, fonction avec le Nécromancien, registre avec l'Archer.
>
> **Six gestes exclusifs suffisaient pour quatre des six rôles** : l'arbre écrivait déjà le
> Piège incendiaire, le Bouclier d'étincelles, la Bombe flash et le Barrage d'artillerie. Seules
> les deux `entrave` de la palette de contrôle manquaient — **Nappe de poix** et **Tir
> couvrant** —, parce que tous les gestes de feu à plusieurs cibles déjà livrés sont **partagés
> avec le Pyromancien**, donc de registre `spell`. C'est la règle d'ARC-08b appliquée une
> seconde fois : *un accord partagé garde le registre de celui qui l'a ouvert le premier.*
>
> **Le canon a corrigé le document, et le résultat est meilleur.** Le §13.2 donne à l'arbre un
> capstone sur `thrift` (« Économie de guerre ») ; la table de la décision 22 (§7.1) ne range
> `thrift` parmi les sommets du contrôle que **pour l'arbre à pacte**, et celui-ci n'en porte
> aucun. Le sommet revient donc à `grip`, le levier canonique de sa fonction — et le
> **corollaire 2 s'y montre mieux que dans le document** : 14 pb sur un plafond de 20 laissent
> 6 pb que le palier 1 consomme entièrement, si bien que `grip` est **absent des deux
> branches**. C'est la forme forte de *le levier principal d'un arbre est presque absent de sa
> propre fourche*. `PatronTreeContractTest` l'a attrapé — l'invariant livré valait mieux que la
> table écrite à la main.
>
> **Un défaut d'effet à l'envers, trouvé en déclarant les portées** : le Bouclier d'étincelles
> se posait `sur soi` **et appliquait `burn`**. Sa description dit « brûle les attaquants »,
> mais le statut suit la portée, et la portée est le lanceur ; il portait en plus un dégât de 1,
> qui frappait donc celui qu'il protège. *Ce n'était pas un choix d'équilibrage, c'était un
> effet à l'envers.* Les deux partent, le `shield` que sa fonction réclame arrive.
>
> **ARC-08b — l'Assassin, livré le 2026-08-18.** Le second des vingt, et il remplit la case que
> les quatre patrons laissaient vide : **`assault × melee`**. ARC-07 avait livré l'assaut en
> sorts (Pyromancien) et au tir (Archer), et la mêlée **en encaisse** (Soldat) ; personne ne
> frappait fort au contact, c'est-à-dire dans la case où se joue la moitié du bestiaire. Le
> simulateur passe de **5 à 6 cases sur 12**.
>
> C'est aussi l'arbre que GAME_TREE_ANATOMY déroule comme **méthode** (§ 4) : ses dix-huit nœuds
> y sont écrits un par un. L'écrire revenait donc à vérifier que le document tient en données —
> **et il tient sans un seul dosage** : 390 points, 50 pb par branche, `PatronTreeContractTest`
> vert du premier coup.
>
> **Le plafond a écrit la fourche pour la quatrième fois** : le capstone consomme 14 pb de
> `power` et le palier 1 en met 3, ce qui laisse 3 pb sous le plafond de 20 — `power` ne
> *pouvait* pas être le levier de la fourche. Elle oppose donc **la façon de ne pas être touché**
> à **la façon de trancher** : {`dodge`, `tempo`} contre {`critical_power`, `pierce`}.
>
> **Trois défauts trouvés en écrivant**, tous par les invariants livrés :
>  - **La grille de reprise s'indexe sur le niveau du sort, pas sur le palier du nœud.** On avait
>    supposé l'inverse ; `RegisterResourceTest` a tranché. La conséquence est une correction
>    réelle : **l'Embuscade passe du palier 2 au palier 1**, parce qu'un geste distribué à
>    *zéro point* qui se déclarait de palier 2 mettait en conflit les deux règles qui le
>    régissent — *un accord d'entrée est gratuit* et *au-delà du palier 1, une technique coûte
>    toujours un tour*. Sa matéria devient `m1-ambush`, et le comptoir du Marais suit.
>  - **Un accord partagé garde le registre de celui qui l'a ouvert le premier.** Le § 4.7 nommait
>    « Nova de mort » pour la case à plusieurs cibles — un geste que le Nécromancien ouvre déjà,
>    donc de registre `spell`. Le lui prendre aurait cassé un arbre livré ; le partager aurait
>    obligé un même geste à être une technique ici et un sort là-bas. L'Assassin écrit donc les
>    siens : **Voile** et **Fauchée d'ombre**. C'est la leçon d'ARC-07d, transposée.
>  - **Le catalogue de matéria rétrécit, et c'est voulu.** L'arbre portait onze accords, le
>    gabarit en autorise sept ; `vital-drain` et `death-touch` n'étaient ouverts que par lui et
>    quittent le catalogue. Deux d'entre eux étaient des **drains** — un verbe d'entretien dans
>    un arbre d'assaut. *Un arbre qui ouvre tout n'ouvre rien.*
>
> **Mesuré, et laissé à ARC-05c** : les deux branches ne se départagent pas par leurs leviers
> mais par les **dégâts bruts de leur accord** — `shadow-dance` (8) contre `deadly-strike` (6) —,
> si bien que La Lame met **5 tours** là où L'Ombre en met 4, alors que sa fiction dit
> l'inverse (*une seule ouverture suffit*). Le budget est pourtant égal, et le contrat le
> vérifie : ce qui diffère est une **valeur de dégât**, c'est-à-dire précisément ce qu'ARC-05c
> a pour tâche de recalibrer. On le nomme plutôt que de le doser ici.
>
- [◐] **Les 19 arbres restants** — la liste vit dans `CombatBranchCatalogTest::WAITING_ON_ARC_08`,
      en cliquet *(ARC-08b→e — cinq arbres convertis, **15 restants** ; la **grille des 9 cases
      atteignables est couverte**, donc les suivants ajoutent de la profondeur, plus de la portée)*
- [ ] Table de conversion `damage/heal/hit/critical/life` plat → `(levier, pb)` selon le
      palier du nœud
- [ ] Relecture par arbre avec le **test du plafond** et le **test du voisin** ; corriger
      la fonction d'un arbre plutôt que ses chiffres quand deux voisins se recouvrent
- [ ] Les arbres non nourris restent **jouables sans être des chantiers de contenu**
      (GAME_PROGRESSION §6b)
- [ ] **Vider la liste d'attente d'ARC-02b** : `CombatRegisterCoverageTest::AWAITING_ARC_08`
      nomme les **11 arbres d'arme** dont aucun accord ne déclare encore son registre —
      leurs passifs, bornés à la mêlée ou au tir, ne s'appliquent donc à aucune action.
      Le test vérifie que la liste est **exacte** : elle ne peut que rétrécir, et le jour
      où elle est vide, l'invariant 7 tient sans exception. *Nuance mesurée en ARC-02b :
      le Chevalier n'y figure déjà plus, parce qu'il partage des accords avec le Soldat —
      convertir un arbre patron sert ses voisins de registre.* Les **39 gestes ambigus**
      (ouverts par des arbres de registres différents, ex. `magnetic-pull` : Soldat mêlée
      + Ingénieur tir) demandent un arbitrage par geste, pas une conversion mécanique

### ARC-09 — Tests du plan (S | ★★ | HAUTE) ✅
> ‖ au fil des jalons. Les 45 invariants de GAME_ARCHETYPES §12.
>
> **Livré le 2026-08-18.** `ArchetypesPlanContractTest` répond à une seule question pour chacun
> des 45 : **qui le tient ?** Il ne re-teste pas ce que d'autres tiennent — il est la table des
> matières, il empêche l'index de pourrir (chaque test cité doit exister), et il porte les
> garde-fous que personne ne portait.
>
> **L'inventaire, et c'est lui le livrable** : **26 tenus**, **5 portés ici pour la première
> fois**, **14 impossibles aujourd'hui**. Les distinguer est tout l'objet du fichier — *un
> invariant qu'aucun mécanisme ne peut violer ne mesure rien, et le compter comme tenu serait un
> mensonge d'inventaire.* Les quatorze se rangent en trois familles, chacune avec son jalon :
> **ARC-18** (n° 36-38, 42, 43 — aucune forme de geste n'est livrée), **ARC-19** (n° 39, 40 — la
> riposte ne se déplace pas), **ARC-05c** (n° 29, 34, 35 — le simulateur les mesure depuis
> ARC-17c, et le relevé dit qu'ils ne sont pas tenus ; ils vivent en cliquet plutôt qu'en seuil
> sec).
>
> **Les cinq garde-fous neufs** portent sur les neuf arbres convertis : l'**accord exclusif**
> (n° 45 — *un arbre dont tous les gestes s'obtiennent ailleurs n'est pas un arbre, c'est un
> raccourci* ; il avait déjà décidé du contenu quatre fois sans que rien ne le vérifie), les
> **deux nœuds gratuits qui sont des accords** (n° 5), l'**absence de condition au palier 1** et
> le **plancher de deux passifs inconditionnels** (n° 12), et le **parent unique** (n° 21 quater).
>
> **Un constat que l'index a produit** : le canon veut que *le capstone exige l'accord de
> branche*, quand les neuf arbres convertis le font dépendre du **nœud charnière du palier 2**.
> Ce n'est pas un oubli — un prérequis unique ne peut pas désigner « celui des deux accords de
> fourche que le joueur a pris », et le modèle n'exprime pas l'alternative. L'invariant est donc
> **à moitié tenu**, et le dire vaut mieux que de le compter vert.
- [x] Budget (50 pb), plafonds par levier, règle des 80/20 *(PatronTreeContractTest)*
- [x] Grille : une fonction par domaine, aucun triplet en double *(DomainRoleTest)*
- [x] Gabarit : 15 nœuds, échelle de coût, 2 entrées à 0 point **qui sont des accords**
      *(ARC-09 — le second membre n'était tenu par personne)*
- [◐] Capstone : unique, conditionnel, 14 pb, condition atteignable au tour 2 *(tenu, sauf le
      prérequis de branche — voir le constat ci-dessus)*
- [x] Registre : tout arbre ouvre au moins un geste de son registre *(CombatRegisterCoverageTest)*
- [x] Intentions : palette tenue, un `dégât` et un non-`dégât` par arbre, un geste de
      portée collective pour l'entretien et l'encaisse *(DomainIntentPaletteContractTest)*
- [x] Dépôt : aucun geste de portée `le groupe` instantané ; durée en tours de rencontre
      *(DepositLawTest, DepositedGestureContractTest)*
- [x] Conditions : les cinq garde-fous du §4.3, et le **vocabulaire fermé** (12 entrées) ;
      aucun plafond global de points *(SkillConditionTest ; ARC-09 pour le palier 1 et le
      plancher d'inconditionnels)*
- [x] Marques : une par élément, appliquée par un accord d'entrée de chaque arbre
      *(ElementalMarkReachabilityTest — reste le Prêtre)*
- [x] Fourche : deux branches sans levier commun, chacune tenant les 50 pb *(PatronTreeContractTest)*
- [x] Pacte : unique, borné, feuille, hors palette, jamais au palier 1 *(PactRuleTest)*
- [x] Accointances : aucune ne rend un point de budget, un levier ou une statistique
      *(AccointanceContractTest)*
- [x] Exclusivité : chaque arbre ouvre au moins un accord que nul autre n'ouvre *(ARC-09)*
- [x] Règle 9 étendue au chemin des techniques ; aucun passif plat restant *(DomainPlanContractTest)*

### ARC-10 — Le plafond global de points (S | ★★ | MOYENNE) ✅

> **Livré le 2026-08-03.** `MAX_TOTAL_SKILL_POINTS`, le motif de refus
> `global_cap`, ses deux traductions, la jauge « X/500 » de l'écran des arbres,
> la clé `maxTotalPoints` des charges utiles (web et API v1) et la validation
> de preset ont disparu **ensemble** — un seul de ces restes aurait suffi à
> réintroduire la borne. Le total investi reste affiché : c'est une information
> sur le personnage, jamais un maximum. L'amendement canonique était déjà écrit
> dans GAME_DOMAINS §1 (2026-07-31) ; le code le rattrape, et la note dit
> désormais que c'est effectif.
> GAME_ARCHETYPES §11.2. `MAX_TOTAL_SKILL_POINTS = 500` contredit « le savoir n'est jamais
> borné » (GAME_DOMAINS §1) — et un seul arbre en consomme 465.
> **Tranché le 2026-07-31 : le plafond est supprimé.**
- [x] Retirer `MAX_TOTAL_SKILL_POINTS`, le motif de refus `global_cap`, ses traductions
      FR/EN et le test qui l'exerce
- [x] **Écrire dans GAME_DOMAINS §1 pourquoi** — sinon il reviendra : les trois bornes
      réelles sont l'énergie (rythme), le build (expression, DOM-02) et la spécialisation
      ou le patronage (identité). Un plafond de points ne borne que le temps de jeu, la
      seule chose que ce jeu a décidé de ne jamais punir
- [x] Test : aucun refus d'acquisition ne dépend d'un total de points tous domaines
      confondus

### ARC-11 — L'intention, la portée, et la loi du dépôt (M → 2 sous-phases | ★★★ | HAUTE) ✅

> **Découpé (règle 8) : ARC-11a le vocabulaire, ARC-11b la loi du dépôt.** Le jalon
> tient deux choses de nature différente — deux étiquettes sur le geste, et une
> mécanique de durée qui change le combat de groupe. La première se livre sans
> déplacer une valeur ; la seconde touche la résolution de tour.
>
> **ARC-11a — livré le 2026-08-05.** `SpellIntent` (dégât / soin / protection /
> amélioration / entrave) et `SpellScope` (soi / un allié / le groupe / une cible /
> plusieurs cibles) existent, et `Spell` les porte.
>
> **Les deux colonnes sont nullables, et c'est la décision du jalon** : elles portent la
> décision d'auteur quand il y en a une, et restent vides quand la donnée suffit à dire
> l'intention. `SpellIntentDeriver` répond alors — et ce qui rend la dérivation légitime
> est mesuré : **les huit types de `StatusEffect` se rangent sans reste** dans les cinq
> intentions (poison/paralysie/brûlure/gel/silence → entrave, régénération → soin,
> bouclier → protection, berserk → amélioration). Une table qui laisserait des restes
> dirait qu'on invente une distinction ; celle-ci ne fait que nommer celle que la donnée
> portait déjà. Écrire 253 valeurs à la main aurait été 253 occasions de se tromper, et
> le dépôt dérive partout ailleurs (matéria depuis le sort, stats depuis le gabarit,
> cible depuis le bestiaire).
>
> **L'ordre des questions porte une règle** : le dégât se lit avant l'effet de statut,
> donc *un geste qui blesse **et** marque reste un geste de dégât*. C'est ce que le §1.1
> exige — une marque doit être portée par un geste de dégât, sans quoi une entrave d'un
> tour serait arithmétiquement nulle en duel (§9 quinquies). Le classer en « entrave »
> l'aurait sorti de la palette de l'assaut, qui est pourtant celui qui le lance.
>
> **Et `scope: le groupe` ne se dérive jamais.** Aucune colonne ne pourrait le faire
> apparaître : un soin de groupe et un soin d'allié ont exactement les mêmes valeurs. Ce
> qui les sépare est une décision d'auteur, et la durée qu'ARC-11b y attache.
>
> **ARC-11b-a — livré le 2026-08-06.** La loi du dépôt est écrite et **opposable** :
> `DepositLaw` répond à *ce geste se dépose-t-il ?* (toute portée `Group`, plus toute
> `protection` quelle que soit sa portée — l'extension du §9 bis), *combien de temps ?*
> (`MIN_DURATION = 2`, même raison arithmétique qu'`ElementalMark` : un effet qui ne dure
> que le tour où il est joué n'a rien déposé, il a **réagi**), *combien par tour ?*
> (`spreadPerTurn` — **la durée étale la valeur, elle ne l'augmente pas**), et *cela
> se multiplie-t-il par les alliés ?* (l'état oui, l'action non).
>
> `StatusEffectManager::deposit()` pose l'effet sur tous les alliés **sans jet de
> chance** — on ne provisionne pas au hasard : ce qui est payé d'un tour est posé —, et
> la valeur par tour vit sur `FightStatusEffect` (colonne `value_per_turn`, nullable) et
> non sur la fiche de l'effet, qui est partagée par toutes ses applications : l'écrire
> dessus changerait la valeur de tous les dépôts déjà posés.
>
> **Le jalon n'est pas inerte, et ce n'est pas ce qu'on attendait.** ARC-11a notait
> qu'aucun geste ne porte encore la portée `Group` ; mais l'extension à toute
> `protection` fait basculer **15 gestes de bouclier déjà livrés** vers le dépôt. Vérifié
> plutôt que supposé : leurs trois valeurs sensibles ne bougent pas (durée déjà ≥ 3 donc
> jamais relevée, `chance` à 100 donc le jet contourné ne changeait rien, absorption
> portée par `statModifier` donc jamais étalée). Un test le tient — s'il tombe, c'est
> exactement le moment où il faut le savoir.
>
> **ARC-11b-b — livré le 2026-08-06, et il clôt ARC-11.** *Les leviers visent par
> intention* : `LeverIntentLaw` dit, **une fois**, quels leviers un geste qualifie, et
> `CombatLeverEffects` n'en porte pas d'autres.
>
> **La loi existait, mais nulle part.** Le moteur la portait *implicitement*, éparpillée
> dans quinze positions de formule — `applyPower()` ne lit que les dégâts,
> `applyMending()` que le soin. Tant que la borne est un effet de bord de l'endroit où le
> code appelle, elle n'est pas opposable, et elle **fuit** dès qu'un chemin neuf lit un
> levier ailleurs. C'est arrivé à `grip` : il portait la durée de **tout** statut appliqué
> — bouclier et régénération compris —, si bien qu'un arbre d'entretien pouvait acheter
> 10 pb du levier principal du contrôle en teinte et rallonger ses propres protections.
> Le jalon le referme, et pose la règle générale : **quand la formule exerce une place
> que le canon n'autorise pas, le canon gagne** (deux cas — `grip` sur un bouclier, le jet
> de touche sur un soin ; le critique n'en est pas un, le canon ne le restreignant pas).
>
> **Le bornage se fait en deux temps, parce que l'intention se lit en deux temps.** Le
> dégât et le soin répondent sur la seule fiche du sort ; la protection, l'amélioration et
> l'entrave demandent le **type de l'effet de statut**, que le moteur ne charge qu'au
> moment de l'appliquer. Borner une seule fois en amont obligerait à charger le statut sur
> les 194 gestes qui n'en portent pas. `aimedAt()` rétrécit donc une seconde fois quand le
> statut a parlé — **rétrécir, jamais élargir**, sinon l'ordre des deux questions
> deviendrait une valeur de jeu.
>
> **Un écart trouvé, et il précède le jalon de trois jalons** : ARC-11a avait mesuré que
> *les huit types de `StatusEffect` se rangent sans reste* dans les cinq intentions —
> ARC-13a en a ajouté un **neuvième**, `TYPE_MARK`, et le reste était là. Sans effet
> jusqu'ici ; fatal à partir de maintenant, puisqu'une intention illisible **ne borne aucun
> levier** : un geste de marque pure aurait été le seul du jeu à qualifier les quinze. Une
> marque est une `entrave` à la lettre de ce que l'intention dit (*retirer à l'adversaire
> un tour, une option, ou une résistance*). Le test qui aurait dû l'attraper ne parcourait
> que sa propre liste ; il compare désormais à `StatusEffect::TYPES`.
>
> **La palette d'intentions est enfin opposée** (§5.1). `domain_roles.yaml` la déclare
> depuis ARC-01 — *un arbre d'assaut ouvre ≥ 3 dégâts, un arbre de contrôle ≥ 2 entraves*
> — et **rien ne la lisait** : un fichier de contraintes que personne n'oppose est un
> commentaire. `DomainIntentPaletteContractTest` la mesure, et le résultat est le livrable
> du jalon plutôt que sa conformité :
>
> - **Les 24 arbres ouvrent tous un accord de dégât** (loi 1 — tenue).
> - **10 arbres n'ouvrent QUE des dégâts** (loi 2 — le plan B du jour 1 manque) : Archer,
>   Artificier, Assassin, Berserker, Chasseur, Foudromancien, Nécromancien, Pyromancien,
>   Soldat, Sorcier. Cliquet nommé.
> - **15 écarts de palette**, et ils disent tous la même chose : **aucun des 253 gestes
>   livrés ne dérive vers `entrave`, `protection` ni `amélioration`** — ils se rangent en
>   194 dégâts et 59 soins. C'est l'ordre des questions d'ARC-11a qui le veut (le dégât
>   d'abord, puis le soin), et c'est voulu : le §1.1 exige qu'une marque soit portée par un
>   geste de dégât. Mais cela mesure une chose que le plan ne disait pas — **la protection
>   et l'entrave n'existeront que le jour où un auteur les déclarera**, sur la colonne
>   `Spell::intent` qu'ARC-11a a laissée nullable pour exactement cela. De même pour
>   `SpellScope::Group`, qui ne se dérive jamais : aucun des 4 arbres d'entretien ne tient
>   sa portée de groupe, la seule qui rende la loi du dépôt utile. **C'est le travail
>   d'ARC-07 et ARC-08**, et le cliquet le rend impossible à oublier.
>
> **Aucune valeur de jeu ne bouge** : aucun nœud livré ne porte de levier, et aucun des
> 253 gestes n'a d'intention illisible (testé).
>
> GAME_ARCHETYPES §3.1 et §7 bis. **Ce jalon décide si le donjon de groupe a un sens.**
> Le combat de groupe est semi-synchrone (`GroupDungeonCombatService` : un joueur actif à
> la fois, 45 s par tour, tour d'un absent résolu tout seul) — un soin **réactif** y est
> une mécanique morte.
- [x] `SpellIntent` (dégât / soin / protection / amélioration / entrave) et `SpellScope`
      (soi / un allié / le groupe / une cible / plusieurs cibles) sur `Spell`, hérités par
      la matéria *(ARC-11a — colonnes **nullables** avec repli de dérivation
      (`SpellIntentDeriver`) : les 8 types de `StatusEffect` se rangent sans reste dans
      les 5 intentions. Aucune valeur de jeu ne bouge — les colonnes naissent vides)*
- [x] Les leviers visent par **intention** : `mending` ne touche que `soin`, `grip` que
      l'`entrave`. Une fois sur le geste, jamais quinze fois dans quinze formules
      *(ARC-11b-b — `LeverIntentLaw`, appliquée au **porteur** et non aux consommateurs :
      un porteur qui ne contient que ce qui qualifie ne peut pas fuir)*
- [x] **Les gestes déposés** : un effet de portée `le groupe` pose une **durée** sur les
      alliés — régénération, absorption, amélioration — et elle court **que le lanceur soit
      connecté ou non** *(ARC-11b-a — `StatusEffectManager::deposit()` pose sur tous les
      alliés, le lanceur compris : un geste qui protège le groupe protège celui qui le
      lance)*
- [x] **L'asymétrie du donjon semi-synchrone**, à écrire noir sur blanc (correction du
      §9 quinquies) *(ARC-11b-a — `DepositLaw::multipliesWithAllies()` : l'état oui,
      l'action non, et un test le dit fonction par fonction)* : un effet posé sur les **alliés** se multiplie par leur nombre (×8,8 à
      quatre), un effet posé sur l'**ennemi** ne se multiplie pas (×0,9) — un seul joueur
      agit par tour. Entretien et encaisse gagnent au groupe ; assaut et contrôle n'y
      gagnent rien. Ne pas équilibrer le contrôle comme un soutien
- [x] **Toute `protection` porte une durée**, quelle que soit sa portée (correction du
      §9 bis) *(ARC-11b-a — et c'est ce qui rend le jalon non inerte : 15 gestes de
      bouclier livrés basculent, sans qu'aucune de leurs valeurs ne bouge)* : une garde qui ne couvre que le tour où elle est jouée punit l'encaisse de
      se défendre — il perd en dégâts exactement ce qu'il gagne en survie
- [x] **La durée se compte en tours de la rencontre**, jamais en temps réel ni en tours du
      lanceur : c'est le seul compteur que l'asynchronie ne dérègle pas *(ARC-11b-a —
      `FightStatusEffect::remainingTurns`, le compteur que le moteur possédait déjà)*
- [x] **La durée étale la valeur, elle ne l'augmente pas** (correction du §9 ter) : la
      valeur totale d'un dépôt est fixée par le palier de la matéria. Mesuré, un dépôt de
      10 tours sur quatre alliés vaut **14,7 tours d'attaque** — à ce prix, un groupe sans
      entretien devient non viable, ce que le garde-fou interdit *(ARC-11b-a —
      `spreadPerTurn` ; doubler la durée ne double pas ce qu'on reçoit, et un test le dit
      dans ces termes)*
- [x] Le même geste en `scope: soi` doit rester jouable en solo — un archétype, pas deux
      *(ARC-11b-a — la loi n'interdit pas le soin direct : *le direct est l'urgence, le
      dépôt est la provision* (§7 bis.2 bis), et un test refuse que le soin d'allié
      devienne un dépôt)*
- [x] Le garde-fou : **aucun rôle n'est nécessaire**. Un groupe sans entretien met plus de
      tours et perd plus de PV ; il ne rencontre pas un mur. Exiger un rôle, c'est exiger
      une présence *(ARC-11b-a — tenu par la propriété vraie à toute échelle : un tour
      déposé ne rend jamais, **par cible**, plus qu'un tour direct ; c'est la
      multiplication par les corps qui fait la valeur, jamais le chiffre affiché)*
- [x] Les leviers visent par **intention** ; palette d'intentions tenue par arbre ; tout
      arbre ouvre au moins un `dégât` et au moins un non-`dégât` *(ARC-11b-b —
      `DomainIntentPaletteContractTest` : loi 1 tenue par les 24 arbres, loi 2 manquée par
      **10** d'entre eux, et **15 écarts de palette** — cliquets nommés, refermés par
      ARC-07/08. La mesure est le livrable)*
- [x] Tests : aucun geste `le groupe` instantané *(ARC-11b-a —
      `DepositedGestureContractTest`, écrit **avant** qu'un seul geste ne porte la portée
      pour qu'aucun ne puisse naître instantané, plus le contrat « aucune valeur livrée ne
      bouge »)*

### ARC-12 — Les passifs conditionnels d'équipement (M → 2 sous-phases | ★★★ | HAUTE) ✅

> **Découpé (règle 8) : ARC-12a le vocabulaire et le prix, ARC-12b l'écran.** Le modèle
> et l'interface n'ont ni le même risque ni le même rythme ; le premier se teste seul.
>
> **ARC-12a — livré le 2026-08-05.** `LeverGrant` portait une condition depuis ARC-03a en
> annonçant que *ce qu'elle vaut est ARC-12* — jusqu'ici « une chaîne que rien n'interprète ».
> Elle a désormais un **vocabulaire fermé** (`SkillCondition`) et un prix. **Aucune
> migration** : la colonne existe depuis ARC-03a, et **aucun nœud livré ne porte encore de
> condition** — le jalon pose la grammaire avant qu'il y ait quoi que ce soit à relire.
>
> La grammaire tient les garde-fous **par construction** plutôt que par relecture : seuls
> `weapon:` et `armor:` prennent un sujet, si bien que `rarity:epic` et `item:excalibur`
> sont refusés à la lecture — *la famille, jamais la pièce ni la rareté* (garde-fou 4). Et
> une condition inconnue est **refusée**, jamais corrigée en silence : un passif conditionné
> à une chaîne mal orthographiée serait **toujours inactif**, et un bonus silencieusement
> mort est le pire des défauts — il se lit comme un choix de build.
>
> **La correction du §9 bis est le cœur du jalon, et elle vaut 43 % de puissance.** Le §4.3
> accorde ×2,0 au combat *parce que la condition peut manquer* ; une condition vraie plus
> des deux tiers du temps ne manque pas. D'où **deux listes** de conditions de combat :
> les fréquentes (`target_marked` — la marque de son propre élément est posée dès le tour 1
> par un accord **gratuit** —, `took_hit_last_turn`, `in_melee_range`) payées ×1,4 comme un
> build, et celles qui peuvent réellement manquer, payées ×2,0. Un test le dit sans détour :
> **deux conditions de même nature n'ont pas le même prix**. Les fréquences définitives sont
> à ARC-17 ; ce qui est figé, c'est la règle et l'obligation de déclarer de quel côté on tombe.
>
> **ARC-12b — livré le 2026-08-06.** Trois choses, et la première n'était pas prévue.
>
> **La grammaire n'était pas appliquée.** ARC-12a annonçait qu'une condition inconnue
> serait « refusée à la lecture » — mais `SkillLeverReader` ne vérifiait que « non vide »,
> et `SkillCondition::parse()` n'était appelée par personne sur ce chemin. Une chaîne mal
> orthographiée entrait donc sans bruit et laissait son passif **toujours inactif** :
> exactement le défaut que la grammaire devait fermer. Le lecteur parse désormais.
>
> **Le croisement OBJ**, et il attrape deux erreurs distinctes : une famille qui n'existe
> pas (`weapon:katana`) et une famille qui existe **du mauvais côté** (`weapon:plate`).
> L'échelle de port énumère déjà les familles **et** sait les séparer (clé `line`, `armor`
> par défaut `weapon`) — la relire évite qu'une famille renommée laisse derrière elle un
> passif mort, et évite surtout d'écrire une seconde table.
>
> **Les multiplicateurs, à l'effet et non au budget.** `effectOf()` rend ce qu'un nœud
> donne *quand sa condition est remplie* ; `averageEffectOf()` rend ce que le budget
> compte. Les garder séparés est ce qui empêche la confusion que le §4.3 prévient — un
> arbre qui compterait l'effet affiché dans son budget achèterait sa puissance deux fois.
> Les plafonds ne bougent pas : ils restent en points de budget.
>
> **L'écran** (`SkillLeverPresenter` → `SkillLeverReadout`) dit `+9 % — à la dague`
> plutôt que `weapon:dagger`, et il affiche l'effet **obtenu** : afficher la moyenne
> ferait croire qu'un passif conditionnel rend moins qu'il ne rend, et personne ne le
> prendrait. Les libellés viennent de l'échelle de port, jamais d'une table parallèle.
> Les conditions de **combat** n'y figurent pas : l'écran dit ce qu'il faut *porter*, et
> « vous avez encaissé au tour précédent » ne se porte pas.
>
> **Aucune valeur de jeu ne bouge** : aucun nœud livré ne porte encore de levier
> (ARC-03a a posé la colonne, ARC-07/08 l'écrira), donc l'écran n'affiche rien de neuf
> aujourd'hui — il est prêt, et testé, pour le jour où il y aura quelque chose à lire.
> GAME_ARCHETYPES §4.3. C'est ce qui fait que **l'équipement est le build** au lieu d'être
> un total — la promesse de GAME_DOMAINS §3, qui n'avait jamais eu de quoi la tenir.
- [x] `SkillCondition` sur un nœud passif : famille d'arme, ligne d'armure, bouclier porté,
      main gauche libre, deux armes — plus les conditions de combat déjà utilisées par les
      capstones *(ARC-12a — vocabulaire fermé, refusé à la lecture ; les capstones n'en
      portaient encore aucune, la liste de combat est donc **posée** et non reprise)*
- [x] **Multiplicateurs d'effet** : ×1,0 sans condition, **×1,4** condition de build,
      **×2,0** condition de combat. Le budget compte l'effet **moyen**, pas l'effet affiché ;
      les plafonds restent exprimés en points de budget et ne bougent pas *(ARC-12a — le
      multiplicateur est porté par la condition ; **ARC-12b** l'applique : `effectOf()`
      rend ce qu'on obtient, `averageEffectOf()` ce que le budget compte, et les garder
      séparés empêche d'acheter sa puissance deux fois)*
- [x] **Le croisement OBJ** : les familles validées contre `EquipmentPortCatalog`
      *(ARC-12b — deux erreurs attrapées, la famille inexistante et la famille du mauvais
      côté ; plus la grammaire enfin **appliquée** à la lecture, ce qu'ARC-12a annonçait
      sans que le lecteur le fasse)*
- [x] **L'écran des arbres** dit ce qu'un nœud rapporterait si la condition était remplie,
      et ce qu'il faudrait porter *(ARC-12b — `SkillLeverPresenter` : `+9 % — à la dague`,
      l'effet **obtenu** et non la moyenne ; les libellés viennent de l'échelle de port)*
- [x] **Le multiplicateur suit la fréquence mesurée, pas la famille** (correction du
      §9 bis) : une condition de combat vraie plus des deux tiers du temps se paie ×1,4.
      « Vous avez encaissé au tour précédent » est vraie dès le tour 2 pour qui se bat au
      contact — c'est le simulateur d'ARC-05 qui la mesure, pas l'auteur qui l'estime
- [ ] Les **cinq garde-fous** verrouillés par test : aucune condition au palier 1 ; au moins
      2 des 7 passifs sans condition ; condition satisfaisable par ce que l'arbre débloque ;
      condition portée sur une **famille**, jamais sur une pièce nommée ni sur une rareté ;
      une condition ne ferme rien — elle récompense
- [ ] **UI** : l'écran des arbres dit ce qu'un nœud rapporterait *si la condition était
      remplie*, et ce qu'il faudrait porter. Un bonus silencieusement inactif est un bug
      d'interface, pas un choix de build
- [ ] Croise **OBJ** : les familles d'arme et lignes d'armure doivent être lisibles depuis
      l'objet (`EquipmentPortCatalog` les déclare déjà par famille)

### ARC-13 — Les huit marques élémentaires (M → 2 sous-phases | ★★★ | HAUTE) ✅

> **Découpé (règle 8) : ARC-13a les marques et leur loi, ARC-13b leur emploi.** Poser les
> huit marques est une chose ; les faire appliquer par un accord d'entrée de chacun des 24
> arbres, par les monstres, et les dire au catalogue public en est une autre — cette
> seconde moitié touche 24 arbres et croise BES.
>
> **ARC-13a — livré le 2026-08-05.** Les huit marques existent, et **sept manquaient** : la
> Brûlure était la seule déjà en base. Trempé, Déséquilibre, Alourdi, Entaille, Traqué,
> Révélé et Aveuglé sont écrites, chacune sur son élément.
>
> **La mark-ness vit dans un catalogue, pas dans le type.** `ElementalMark` dit lequel des
> statuts est *la marque de son élément* ; le `type` continue de dire ce que l'effet
> **fait**. Les deux questions sont distinctes, et les mélanger aurait obligé la Brûlure à
> cesser d'être un DOT pour devenir « une marque » — elle est les deux.
>
> **La loi de durée est arithmétique, pas esthétique** (correction du §9 quinquies) : en
> duel, échanger un de ses tours contre un tour adverse laisse les dégâts subis
> **rigoureusement identiques** — 101 dans les quatre cas mesurés. Une entrave d'un tour est
> un nœud mort. D'où `MIN_DURATION = 2`, et l'exception qui la complète : une marque portée
> par un geste de dégât y échappe, parce que le tour n'a pas été échangé — il a servi deux fois.
>
> **ARC-13b-a — livré le 2026-08-06.** Un des deux accords d'entrée applique désormais la
> marque de son arbre, sur **20 des 24**. Les accords d'entrée coûtent 0 point
> (GAME_MATERIA §3) : un joueur les a le jour où il ouvre l'arbre, donc le capstone cesse
> d'être une promesse conditionnée à un geste qu'il n'a pas. 19 gestes touchés, tous
> déjà de l'élément de leur arbre et **aucun ne portait d'effet** — rien n'a été écrasé.
>
> **Quatre arbres ne peuvent pas encore porter leur marque, et c'est une mesure, pas un
> oubli** : aucun de leurs deux accords d'entrée ne fait de dégâts, alors que le §1.1
> veut qu'une marque soit portée par un geste de dégât (sans quoi elle coûte un tour plein
> pour un tour volé — arithmétiquement nul, §9 quinquies). Trois sont **défendables** :
> le Gardien (`bulwark`), le Guérisseur et le Prêtre (`upkeep`) n'ont pas d'intention de
> dégât dans leur palette, et marquer un ennemi avec un soin serait une fiction fausse.
> **Le quatrième ne l'est pas** : le Vagabond est `control`, et la palette du contrôle
> *exige* une intention de dégât — son absence de geste offensif à l'entrée est un écart
> qui lui préexiste, qu'ARC-08 referme en lui écrivant ses nœuds manquants. Liste en
> **cliquet** : elle peut rétrécir, jamais grandir.
>
> **Un défaut antérieur trouvé par l'invariant** : `holy-fire` (lumière),
> `dark-forge-blast` (métal) et `amethyst-shatter` (ténèbres) portaient déjà `burn` avant
> qu'ARC-13a n'en fasse la marque du feu. Depuis, **ils allument le capstone d'un
> Pyromancien** — le capstone d'un arbre s'allume sur le geste d'un autre. Ce n'est pas
> réparable sans une décision : la Brûlure est volontairement deux choses (un DOT **et**
> la marque du feu), ces trois gestes veulent le DOT, et il n'existe pas de DOT neutre pour
> les accueillir. Il faut soit en créer un, soit séparer la Brûlure-marque de la
> brûlure-DOT. **Question ouverte**, tenue en cliquet nommé.
>
> **ARC-13b-b — livré le 2026-08-06, et il clôt ARC-13.** Le côté monstre, et le catalogue
> public.
>
> **La lecture qui fait tout le jalon** : *un joueur porte son élément dans ses gestes, un
> monstre le porte dans sa peau.* Un joueur tient son élément de la matéria qu'il sertit,
> donc du geste — ARC-13b-a a écrit la marque sur l'accord. Un monstre tient son élément de
> **lui-même** (`Monster::element`, livré par BES-01) et ses gestes sont **partagés** :
> `none_attack_1` sert des dizaines d'espèces de sept éléments différents. Écrire la marque
> sur le geste obligerait donc à dupliquer chaque attaque par élément, ou à mentir.
>
> **Et cette lecture est immunisée, par construction, au défaut du côté joueur.** ARC-13b-a
> a trouvé trois gestes qui appliquent la Brûlure sans être du feu et allument donc le
> capstone d'un Pyromancien — le capstone d'un arbre s'allume sur le geste d'un autre. Ici
> la marque **est** l'élément du monstre : elle ne peut pas en désigner un autre, et un test
> le dit plutôt que de le supposer.
>
> **Mesure : 59 des 65 monstres marquent désormais.** Deux exceptions sont les mannequins,
> d'élément neutre — et `MonsterMarkLaw` les refuse une seconde fois par leur `trainingMode`,
> parce que la clémence des mannequins se pose à *chaque* chemin plutôt qu'à un seul.
> `ward` cesse d'être un levier mort pour deux palettes sur quatre, et le jet passe par
> `applyStatusEffect()` — là où `grip` et `ward` se croisent — plutôt que par un chemin
> direct qui contournerait la défense de la cible.
>
> **Les quatre autres sont les monstres de feu, et c'est un écart trouvé en chemin.** La
> loi ne pose qu'une **marque pure** (`TYPE_MARK`). ARC-13a a décidé que *la mark-ness vit
> dans un catalogue, pas dans le type* — la Brûlure est donc **les deux**, un DOT et la
> marque du feu —, et la conséquence n'apparaît que de ce côté-ci : poser la marque du feu
> depuis chaque monstre de feu ne leur donnerait pas une marque, cela leur donnerait **des
> dégâts sur la durée qu'ils n'avaient pas**, plus les 25 % retirés à leur cible par
> `applyBurnReduction()`. C'est une décision d'équilibrage, pas de marquage, et le §0.2
> interdit de la prendre à la main. C'est **le même écart qu'ARC-13b-a a laissé ouvert côté
> joueur** (séparer la Brûlure-marque de la brûlure-DOT, ou créer un DOT neutre), vu depuis
> l'autre bord : il se refermera avec lui. Le refus est ce qui garantit la propriété du
> jalon — **aucune valeur de combat ne bouge**.
>
> **Les deux jalons se verrouillent l'un l'autre** : ARC-11b-b a rangé `TYPE_MARK` dans
> l'intention `entrave`, et `ward` ne qualifie **que** l'entrave. Le porteur des leviers de
> la cible est donc relu pour la marque, séparément du coup qui la porte : les confondre
> reviendrait à faire résister une marque avec la garde, ou à ne la faire résister par rien.
>
> **Le catalogue public dit ce que chaque élément laisse** (GAME_ONBOARDING §6.2) — une
> phrase par élément, dans `domain_catalog.yaml`, rangée à part des arbres parce que **la
> marque appartient à l'élément** : 24 arbres partagent 8 marques, et les recopier serait 24
> occasions de diverger pour une information identique.
>
> **Ce que ces phrases ne disent pas, et le défaut qu'elles révèlent.** Elles ne promettent
> **aucun effet de combat**, et c'est délibéré : les marques déclarent des modificateurs
> (`dodge`, `hit`, `guard`, `tempo`) que le moteur **ne lit pas** —
> `StatusEffectManager::getStatModifiers()` n'a aucun appelant. Les brancher poserait deux
> questions que ce jalon n'a pas à trancher (*un `guard: -0.20` retire-t-il vingt points de
> réduction, ou multiplie-t-il les dégâts subis par 1,20 ?*), et le §0.2 interdit de
> recalibrer à la main — c'est ARC-17 qui mesurera. Écrire « on esquive moins bien » serait
> donc mentir, et *le catalogue omet, il ne ment pas*. Les phrases disent la **trace**, un
> test refuse qu'un chiffre ou un `%` s'y glisse, et **`tempo` restera inerte de toute façon
> tant que l'ordre du tour n'est pas modélisé** (ARC-03b l'avait déjà noté).
>
> **Question ouverte, portée au bilan** : *comment se lisent les modificateurs d'une marque
> ?* Elle bloque la moitié de la valeur des huit marques, et appartient à ARC-17/ARC-18.
> GAME_ARCHETYPES §1.1. **Trois pièces déjà écrites du système en dépendent** : le
> capstone d'assaut (« contre une cible qui porte votre marque »), le levier `grip`
> (« les statuts appliqués ») et la palette de contrôle (deux accords d'`entrave`).
> Sans les marques, aucune des trois n'a d'objet.
- [x] Une marque par élément — Brûlure, Trempé, Déséquilibre, Alourdi, Entaille,
      Traqué, Révélé, Aveuglé — déclarées comme `StatusEffect` et rattachées à `Element`
      *(ARC-13a — **7 des 8 manquaient** ; `ElementalMark` porte le catalogue, le `type`
      continue de dire ce que l'effet fait)*
- [x] **Aucune entrave à un tour** (correction du §9 quinquies) : durée ≥ 2 tours, ou marque
      portée par un geste de dégât. *(ARC-13a — `MIN_DURATION`, tenu par test sur les 8)* Démonstration : en duel, échanger un de ses tours contre
      un tour adverse laisse les dégâts subis **rigoureusement identiques** (101 dans les
      quatre cas mesurés) — une entrave d'un tour est un nœud mort
- [x] **Côté monstre aussi** (correction du §9 ter) : mesuré, **21 monstres sur 65** ont un
      sort et **9 de ces sorts** appliquent un statut. `ward` figure dans deux palettes sur
      quatre et l'accord de dissipation du Guérisseur n'a rien à dissiper — une marque qui
      n'existe que dans un sens est un levier mort pour la moitié des fonctions. Croise
      **BES-01** (l'élément des monstres, prérequis de MAT-01) *(ARC-13b-b — `MonsterMarkLaw` :
      la marque vient de l'**élément du monstre**, jamais de son geste, parce que les gestes
      des monstres sont partagés. **59 des 65** marquent : 2 mannequins et **4 monstres de
      feu** en sont exclus, la loi ne posant qu'une marque **pure** — la Brûlure est aussi un
      DOT, et la poser serait un choix d'équilibrage qu'ARC-17 doit trancher)*
- [x] **Un des deux accords d'entrée de chaque arbre l'applique** : c'est ce qui rend le
      capstone atteignable au jour 1 *(ARC-13b-a — **20 des 24**, 19 gestes touchés, aucun
      effet écrasé. Les 4 restants n'ont aucun accord d'entrée qui blesse, donc rien à
      quoi accrocher une marque : liste nommée en cliquet, et le Vagabond y révèle un
      écart de palette antérieur qu'ARC-08 referme)*
- [x] La marque **se rafraîchit, elle ne se cumule pas** avec elle-même ; deux marques
      différentes coexistent sans règle spéciale *(ARC-13a — la règle est écrite dans
      `ElementalMark` ; le comportement du gestionnaire de statuts est vérifié en ARC-13b)*
- [x] Les 15 statuts livrés se rangent : ceux qui deviennent des marques, ceux qui restent
      des effets ordinaires (poison, régénération, bouclier…) *(ARC-13a — seule la Brûlure
      passe marque ; les 14 autres restent ordinaires, et un test le vérifie)*
- [x] Le **catalogue public** (GAME_ONBOARDING §6) peut enfin dire à quoi sert un élément —
      sans jamais donner de valeur *(ARC-13b-b — une phrase par **élément** et non par arbre ;
      elle dit la **trace**, jamais un effet de combat, parce que les modificateurs des
      marques ne sont lus par personne — défaut nommé, tranché par ARC-17. Un test refuse
      qu'un chiffre ou un `%` s'y glisse)*
- [x] Tests : une marque et une seule par élément ; tout arbre de combat a un accord
      d'entrée qui applique la sienne ; aucune marque ne se cumule avec elle-même
      *(ARC-13a + ARC-13b-a + ARC-13b-b — `MonsterMarkLawTest`,
      `MonsterMarkReachabilityTest` (les 8 marques existent en base, un monstre ne laisse
      **jamais** la marque d'un autre élément) et les 3 lois du catalogue)*

### ARC-14 — La fourche (S → 2 sous-phases | ★★★ | HAUTE) ✅
> GAME_ARCHETYPES §6.1 bis. **Deux pyromanciens finis étaient identiques.** Le mécanisme
> existe déjà et n'a jamais servi au combat : `actions.specialization.branch`, le motif de
> refus `other_branch` et le respec de branche payant sont livrés (DOM-04, DOM-06).
>
> **Découpé (règle 8) : ARC-14a le catalogue, ARC-14b l'exclusivité en jeu.** Déclarer les
> fourches et les faire respecter au moment d'apprendre n'ont ni le même risque ni le même
> rythme — la première se teste seule, la seconde touche une entité et le respec.
>
> **Le graphe du plan avait la dépendance à l'envers, et c'est chiffrable.** Il donne
> ARC-07 → ARC-14 ; c'est l'inverse. Le gabarit écrit **18 nœuds**, dont six au palier 3
> (deux branches de trois) : sans fourche, ils se paient tous et un arbre au gabarit coûte
> **540** points. Avec la fourche, on en apprend trois et l'arbre retombe exactement sur les
> **390** de `SkillCostScale`. Écrire les arbres patrons avant la fourche les ferait donc
> tous échouer au seul invariant de coût qui existe. Un test le dit en deux lignes
> d'arithmétique plutôt qu'en prose.
>
> **ARC-14a — livré le 2026-08-06.** `combat_branches.yaml` + `CombatBranchCatalog`, sur le
> modèle de `craft_branches.yaml` (DOM-04) — ajouter une fourche est un bloc de
> configuration, jamais une ligne de code. **Ce qu'il ne partage pas avec lui est la clé** :
> un métier se choisit par `CraftSpecialization`, un arbre de combat par son domaine ; les
> mélanger aurait fait porter au personnage une seule spécialisation pour les deux mondes,
> c'est-à-dire le défaut que DOM-04 avait corrigé.
>
> Deux refus au chargement : **trois branches** (un éventail ne se raconte pas) et **une
> branche sans accord** — ce dernier étant le plus important, puisque le défaut serait
> *invisible en donnée et fatal en jeu* : mesuré au §9 bis, deux branches qui ne diffèrent
> que par leurs passifs produisent le même combat au tour près (11 contre 11).
>
> **Les quatre fourches du canon sont écrites** (Braise/Éclat, Ressac/Marée, Mur/Ligne
> mobile, Guet/Volée), avec le geste que chacune ouvre. **Les 20 autres arbres n'en ont
> pas** : le canon n'en nomme que quatre, et les inventer serait écrire du contenu sans
> l'avoir instruit — liste nommée en cliquet, c'est le travail d'ARC-08.
>
> **ARC-14b — livré le 2026-08-06.** `PlayerCombatBranch` porte le choix, **une ligne par
> arbre et jamais une par personnage** — la leçon de DOM-04, où une spécialisation unique
> fermait à jamais les autres métiers, c'est-à-dire l'exclusivité *entre* arbres que la
> doctrine interdit. Mener les 24 arbres de combat reste permis ; chacun garde sa fourche,
> et l'exclusivité est tenue par le **schéma** (contrainte unique `(player, domain)`) plutôt
> que par du code.
>
> `PlayerSkillHelper::matchesChosenBranch()` accepte désormais les deux grammaires : un
> métier dit `craft`, un arbre de combat dit `domain`. Les séparer plutôt que de tout ranger
> sous `craft` évite qu'un arbre de combat aille chercher sa branche dans la spécialisation
> d'un métier. Le refus `other_branch` (DOM-06) est réutilisé tel quel.
>
> **Le défaut sur lequel le jalon a buté, et qui vaut d'être noté** : le projet a **deux
> identifiants de domaine**. La clé de fixture est anglaise (`pyromancy` — celle
> qu'`equipment_ports.yaml` emploie déjà) quand `Domain::getSlug()` dérive du **titre
> français** (`pyromancien`). Le catalogue garde la clé anglaise comme ses voisins et fait
> le pont par le libellé, qui **est** le titre du domaine — et un test le vérifie arbre par
> arbre, parce que sans lui renommer un arbre casserait sa fourche **en silence** : le
> catalogue chargerait encore, et la branche deviendrait simplement inchoisissable.
>
> `CombatBranchManager::forgoneBy()` dit ce à quoi on renonce, **avec le geste de l'autre
> branche** : *une fourche dont on ne lit qu'un côté n'est pas un choix, c'est un bouton*.
>
> **Aucune valeur de jeu ne bouge** : la table naît vide, et aucun nœud livré ne déclare
> encore de branche — c'est ARC-07/08 qui les écriront.
- [x] Palier 3 : **deux branches de deux passifs et d'un accord**, une seule apprenable —
      l'arbre écrit 18 nœuds et 60 pb, le personnage en apprend 15 et en porte 50
      *(ARC-14a le catalogue et l'arithmétique 540/390, ARC-14b l'exclusivité : une ligne
      par arbre, tenue par le schéma)*
- [x] **Chaque branche ouvre son geste**, et c'est ce qui décide si la fourche est un choix
      ou une décoration *(ARC-14a — une branche sans accord est **refusée au chargement** :
      le défaut serait invisible en donnée et fatal en jeu)* : mesuré au §9 bis, deux branches qui ne diffèrent que par leurs
      passifs produisent **le même combat, au tour près** (11 tours contre 11). Avec un
      accord par branche, elles se séparent de deux tours et de deux façons de jouer
- [ ] Une fourche peut opposer **deux contextes** (seul / en groupe), pas seulement deux
      dosages — c'est la forme la plus forte trouvée (§9.3), et elle n'oblige personne
- [x] Les quatre arbres patrons ont leur fourche (§9.1→9.4) : Braise/Éclat,
      Ressac/Marée, Mur/Ligne mobile, Guet/Volée *(ARC-14a — avec le geste que chacune
      ouvre ; les 20 autres arbres sont nommés en cliquet pour ARC-08)*
- [ ] Étendre `craft_branches.yaml` (ou son équivalent combat) aux 24 arbres — le loader
      refuse déjà un arbre à moins de deux branches
- [ ] UI : les deux branches **comparables avant le choix**, et le prix du respec affiché
      au moment de choisir (§8 bis)
- [ ] Tests : deux branches par arbre ; **aucun levier commun** entre elles ; **un accord
      par branche** ; chaque branche tient les 50 pb, la palette et les plafonds ;
      exclusivité et respec payant

### ARC-15 — Le pacte (S | ★★ | MOYENNE) ✅
> GAME_ARCHETYPES §6.5. La seule mécanique du document qui rende un personnage
> **mesurablement plus faible** quelque part — sans elle, tous les builds sont des
> additions.
>
> **Livré le 2026-08-06.** `PactGrant` porte le malus, `LeverGrant` le tient, et le poids
> d'un nœud se lit désormais **net** : un nœud à 19 pb dont 10 sont rendus pèse **9**, la
> valeur d'un palier 3 ordinaire. *Le pacte ne change pas ce qu'un arbre pèse, il change sa
> forme* — et compter le brut aurait fait dépasser ses 50 pb à l'arbre sans qu'il ait rien
> gagné.
>
> **Les six règles se répartissent en deux endroits, et c'est la seule découpe qui tienne** :
> ce qu'un nœud peut dire de lui-même est refusé **à la lecture** (`SkillLeverReader` — les
> deux crans et rien entre les deux, jamais son propre levier, le poids qui doit valoir le
> palier plus le malus), et ce qui ne se voit qu'en regardant l'arbre entier vit dans
> `PactRule` (un seul pacte, malus hors palette, nœud feuille).
>
> **La règle 5 tient toute seule, et c'est le bon signe** : le contrôle de plafond
> générique s'applique au nœud complet, pacte compris. Conséquence mesurable — un pacte
> majeur (19 pb) n'entre que sur les quatre leviers plafonnés à 20, donc pas sur `guard`
> (15). C'est exactement ce que la règle 7 annonce : **un arbre à pacte est un autre arbre**.
>
> **Aucune valeur de jeu ne bouge** : aucun nœud livré ne porte de levier, donc aucun ne
> porte de pacte. La grammaire est posée avant qu'il y ait quoi que ce soit à relire, comme
> ARC-12a l'avait fait pour les conditions — et le contrat devient mordant le jour où
> ARC-07/08 écrit le premier pacte.
- [x] Un nœud peut porter un **malus** ; sa valeur au taux de change s'ajoute au budget du
      nœud. Un arbre porte jusqu'à 60 pb de bonus et 10 pb de malus, somme inchangée
      *(ARC-15 — `netBudgetPoints()` ; grille à deux crans, 5 pb → nœud à 14, 10 pb → 19)*
- [x] Les **six règles** verrouillées par test : un seul pacte par arbre ; jamais au
      palier 1 ; malus **hors palette** ; permanent, inconditionnel, une seule stat ; nœud
      **feuille** (aucun nœud ne l'exige) ; plafonds par levier toujours tenus
      *(ARC-15 — celles du nœud refusées à la lecture, celles de l'arbre dans `PactRule`)*
- [x] UI : le malus affiché **avant** l'apprentissage, et le net après (§8 bis)
      *(ARC-15 — `SkillLeverReadout::$pactCost` ; *on assume un choix, on ne se fait pas
      piéger*)*
- [x] Tests : les six règles ; et qu'aucun pacte ne permette de dépasser un plafond de levier
      *(ARC-15 — 15 tests ; le dépassement est refusé **par construction**, le contrôle de
      plafond s'appliquant au nœud complet)*

### ARC-16 — Les accointances (M → 2 sous-phases | ★★ | MOYENNE) ◐
> GAME_ARCHETYPES §9.7. **Constat : les synergies livrées sont une fuite de budget.**
> `DomainSynergy` donne des statistiques plates (`damage +10`, `heal +15`) ajoutées dans
> `CombatSkillResolver` **hors** des 50 pb, hors des plafonds, hors des palettes.
>
> **Découpé (règle 8)** : ARC-16a ferme la fuite et pose le vocabulaire, ARC-16b branche les
> formes dont le lecteur manque.
>
> **ARC-16a — livré le 2026-08-06.** `AccointanceForm` pose les quatre formes du canon en
> liste fermée, et `bonusType`/`bonusValue` **disparaissent de l'entité** : *il n'y a plus de
> champ où écrire un chiffre*. L'invariant est ainsi porté par le **type** plutôt que par les
> valeurs — plus fort qu'une vérification de données, qui ne dirait rien du jour où quelqu'un
> rajoute une colonne. Un second test ferme l'autre côté : le moteur de combat ne va plus
> chercher d'accointance du tout.
>
> **Trouvé en ouvrant le jalon, et qui aggrave le constat** : `DomainSynergy` n'a **aucune
> interface**. Les seules synergies visibles en jeu sont les synergies **élémentaires**, qui
> sont un autre système. Les accointances étaient donc invisibles *et* génératrices de
> puissance — un bonus que le joueur ne peut ni lire, ni prévoir, ni arbitrer.
>
> **Une seule des quatre formes a un lecteur**, et le jalon la branche : `domain_expression`
> (`BuildDomainResolver`) — *ce qu'on porte pour l'un parle aussi pour l'autre*. Les huit
> paires livrées s'y traduisent **sans rien inventer**, parce que toutes sont des couples où
> l'un porte ce que l'autre ne porte pas : l'épée du soldat fait parler la pyromancie.
>
> **Le blocage de la forme 1 est un constat, pas un report de confort** :
> `condition_widening` veut élargir ce qui satisfait la condition d'un passif — mais
> `SkillCondition` est analysée (ARC-12a), valorisée et affichée, et **jamais confrontée à un
> équipement réel**. Aucun service ne répond « ce joueur porte-t-il une dague ? ». Élargir ce
> que personne n'évalue n'aurait aucun effet. Un test refuse donc qu'une accointance soit
> écrite dans une forme sans lecteur : *une accointance inerte n'est pas fausse, mais elle est
> un mensonge d'interface si on la laisse s'écrire sans le savoir.*
- [x] Refonte : une accointance ne donne **jamais** de puissance — quatre formes légales
      seulement (élargir ce qui satisfait une condition, ce qui exprime un domaine, ce
      qu'un emplacement accepte ; réduire un coût d'accès) *(ARC-16a — `AccointanceForm`,
      liste fermée)*
- [x] Migration des synergies livrées vers ces formes, ou suppression *(ARC-16a — les 8 paires
      passent en `domain_expression`, aucune supprimée)*
- [x] Une accointance par paire, effet unique, **jamais nécessaire** *(ARC-16a — testé **quel
      que soit l'ordre des deux domaines**, que la contrainte d'unicité du schéma laisserait
      passer)*
- [x] Tests : aucune accointance ne rend un point de budget, un levier ou une statistique ;
      aucune recette, aucun palier, aucun contenu n'en dépend *(ARC-16a —
      `AccointanceContractTest`)*
- [ ] **ARC-16b** : les trois formes restantes et leurs lecteurs — `slot_acceptance` (ce qu'un
      emplacement de matéria accepte), `access_discount` (un échelon de port qui coûte un
      palier de moins) et `condition_widening`, **qui exige d'abord qu'une condition de passif
      soit évaluée quelque part**

### ARC-17 — Le simulateur d'équilibrage (M → 3 sous-phases | ★★★ | HAUTE) ✅
> **Découpé (règle 8)** : 17a rend les dégâts subis mesurables, 17b branche la dérivation,
> 17c livre le simulateur — **lui-même recoupé** en 17c-a (les builds de référence),
> 17c-b (le moteur et les trois scénarios solo), 17c-c (la journée, les seuils en CI et le
> rapport daté) et 17c-d (le donjon à quatre et la matrice contexte × fonction).
> **Complet le 2026-08-18.**
>
> **ARC-17c-d — livré le 2026-08-18. ARC-17 est complet.** Le donjon à quatre et la matrice
> contexte × fonction. `GroupEncounterSimulator` joue la rencontre **comme le donjon la joue**
> — PV partagés (`encounter_hp_per_member` × membres), tour à tour, riposte sur celui qui vient
> d'agir : ce sont les curseurs et la boucle de `GroupDungeonCombatService`, jamais une version
> à part.
>
> **Les quatre compositions périssent**, aucune n'entamant plus de 14 % d'une élite de palier 1.
> Le même écart d'échelle qu'en solo, amplifié par le multiple.
>
> **Mais le résultat du jalon est ailleurs, et il est structurel : la composition n'existe pas
> encore dans le moteur de donjon.** `DungeonActionResolver` ne rend **qu'un dégât** — pas de
> soin —, il n'y a aucune mitigation, et la riposte ne se déplace pas. Les quatre lignes ne
> diffèrent donc que par les barres de vie et les dégâts des membres qu'on échange, et les deux
> « avec soigneur » remplacent simplement un assaut par quelqu'un qui frappe moins fort.
>
> Deux conséquences, écrites plutôt que tues :
>  - le seuil *« un groupe sans tank ni soigneur vient à bout d'une élite »* est **tenu par
>    construction**. Le test est écrit **maintenant** pour qu'ARC-18 et ARC-19 le trouvent, mais
>    *un seuil qu'aucun mécanisme ne peut faire échouer ne mesure rien tant que le mécanisme
>    n'existe pas* ;
>  - le seuil *« aucune fonction dominante dans les deux colonnes »* est **impossible à tenir**,
>    et pas par déséquilibre : faute de soin, de mitigation et d'aggro, la contribution d'un
>    membre **est** son dégât par tour, si bien que les deux colonnes sont *la même mesure dans
>    deux unités*. ***Un contexte qui ne change pas ce qu'une fonction vaut n'est pas un
>    contexte.***
>
> **Les deux curseurs ne bougent pas, et c'est délibéré.** À l'échelle actuelle des gestes,
> `encounter_hp_per_member` devrait descendre à une vingtaine ; or ARC-05c va multiplier les
> dégâts par ~6, et le curseur juste *après* n'a aucun rapport avec le curseur juste *avant*.
> *Régler un côté d'une échelle cassée, c'est la casser deux fois.* Ils se fixent avec ARC-05c —
> le **recalibrage conjoint** que le canon nomme. Détail au §25.9 de [../BALANCE.md](../BALANCE.md).
>
> **ARC-17c-c — livré le 2026-08-18.** La journée, l'ancre de fonction et le rapport daté.
> `DaySimulator` joue les **16 rencontres** que le budget d'énergie autorise — dérivées des
> curseurs réels, jamais écrites —, dont 14 communs et **2 tentatives** d'élite.
>
> **Le résultat de la passe : l'échelle tient au palier 1 et casse au palier 2.** 8 builds sur
> 10 viennent à bout d'un commun de palier 1 (6 dans leur bande) ; au palier 2, les dix
> tombent. Le diagnostic n'est donc pas « les gestes sont faibles » mais *la courbe des gestes
> et celle du bestiaire divergent d'un palier à l'autre*. **L'ancre de fonction n'est pas
> tenue : x5,62 pour une borne de x2,0**, et l'écart vient entièrement des PM — la seule
> ressource qui se reporte d'une rencontre à la suivante.
>
> **Une lecture corrigée en cours de jalon.** Le premier relevé donnait **x24,15**, et il avait
> tort : il comptait les deux Guérisseurs, tombés au premier combat, dont la journée coûte 7
> minutes. ***Une journée arrêtée coûte peu*** — la compter ferait du build le plus fragile le
> plus économe, soit l'inverse exact de ce qu'on mesure. L'ancre ne lit désormais que les
> journées menées à leur terme, tentatives d'élite exclues.
>
> **Quatre seuils sur cinq sont des cliquets, et c'est délibéré.** Un seuil qu'on sait rouge
> produit soit une CI durablement rouge que tout le monde apprend à ignorer, soit un seuil
> desserré jusqu'à passer — c'est-à-dire un seuil qui ne mesure plus rien. Le cliquet est la
> troisième voie, déjà employée par ARC-05a sur le même écart. Le cinquième est **dur** et il
> est tenu : *une élite tue un joueur seul* ne dit rien de l'échelle, il dit ce qu'une élite
> **est**.
>
> Rapport daté au **§25 de [../BALANCE.md](../BALANCE.md)**, et deux des trois questions du
> §24.2 y trouvent enfin une mesure. **Reste ARC-17c-d** : le donjon à quatre dans ses quatre
> compositions, la matrice contexte × fonction, et les deux curseurs que le simulateur doit
> fixer.
>
> **ARC-17c-b — livré le 2026-08-18.** Le simulateur joue enfin des tours.
> `EncounterSimulator` fait s'affronter une fiche de personnage et une case du bestiaire ;
> `ReferenceCharacterFactory` fabrique la fiche en **convertissant le build par le
> convertisseur unique d'ARC-03** plutôt qu'en recopiant une table — un simulateur qui
> dériverait de la formule qu'il mesure ne mesurerait plus rien. `app:balance:simulate` rend
> la table croisée sur les trois scénarios solo (un commun · une élite · un boss).
>
> **Déterministe, et sans dés du tout.** Le plan demandait une graine fixée ; on va plus
> loin, et il faut dire pourquoi : *une graine reste un tirage*, donc un seuil de CI finirait
> par se décider sur un critique qui tombe ou ne tombe pas. L'espérance mesure la même règle
> sans lui laisser cette latitude. Ce qu'elle ne voit pas — **la variance** — est nommé : le
> jour où un seuil demandera une fréquence de mort plutôt qu'une part de barre, il demandera
> des tirages.
>
> **Ce que la première mesure dit** — chiffres **corrigés le 2026-08-18 par ARC-17c-c** : le
> relevé publié ici avait été pris **avant** le second correctif de ce même jalon (le registre
> sans ressource qui retombait sur l'attaque de base), et il sous-estimait donc la mêlée, le
> tir et le contrôle. Le relevé juste est au §25 de [../BALANCE.md](../BALANCE.md) : au palier
> 1, **8 builds sur 10** viennent à bout d'un commun (6 dans leur bande) ; au palier 2, **les
> dix tombent**. Le diagnostic ne bouge pas, il se précise : *l'échelle tient au palier 1 et
> casse au palier 2* — la courbe des gestes et celle du bestiaire divergent d'un palier à
> l'autre. C'est l'écart d'ARC-05a vu de l'autre côté, et la mesure qu'ARC-05c doit ramener
> vers 1.
>
> **Deux défauts trouvés en jouant**, qu'aucune relecture n'aurait vus :
>  - **La bande de durée se lisait sur une défaite.** Un personnage qui tombe en trois tours
>    face à un commun n'a pas « tenu la bande des 3-5 tours » — il est mort dedans. La bande
>    ne se lit désormais que sur une victoire, sans quoi la table serait verte en ne mesurant
>    rien.
>  - **Un registre sans ressource retombait sur l'attaque de base.** La mêlée paie en tours et
>    le tir dans son carquois : les faire jouer les mains nues revenait à leur facturer un
>    coût qu'ils n'ont pas. *Seul un pool qui existe peut se vider.*
>
> **Ce que l'instrument ne joue pas est déclaré plutôt que tu** — la mitigation d'armure
> (elle n'existe pas dans le moteur, cf. ARC-19), les statuts et les dépôts (**le contrôle est
> donc sous-estimé**, à lire dans toute table produite), et l'ordre du tour, fixe. L'arme,
> elle, est **prêtée au mieux** : la meilleure du jeu plutôt que celle du palier, le palier
> d'une arme n'étant pas une donnée (`t2-axe` déclare `level: 5`). Prêter le maximum est la
> lecture la plus favorable au personnage — *si l'écart tient avec elle, il tient a fortiori*
> — et le chiffre confirme qu'elle ne déplace pas la conclusion : les armes livrées frappent
> de **1 à 3** quand un commun de palier 2 porte **70 PV**.
>
> **ARC-17c-a — livré le 2026-08-08.** La première case de la liste ci-dessous, et le socle de
> tout le reste : *un build par fonction × registre, arbre complet, **jamais écrit à la main**.*
> Le canon dit pourquoi, et c'est une raison de méthode — *écrits en dur, ils se périmeraient
> au premier changement de fixture, et c'est exactement ce qu'on cherche à détecter.* Un build
> en dur mesurerait l'état du jeu **au jour où on l'a écrit**, et continuerait longtemps après
> que ce soit faux.
>
> `ReferenceBuildFactory` dérive donc chaque build d'un arbre réel : sa fonction et son
> registre du domaine, ses leviers de ses nœuds, ses gestes de ses accords. **Une branche fait
> un build, pas un arbre** — la fourche existe pour que deux personnages du même arbre ne
> soient pas le même personnage (ARC-14), et les moyenner effacerait le seul choix que l'arbre
> offre. Cinq arbres au gabarit donnent **dix builds**.
>
> **La fabrique devient le lecteur unique de « ce qu'une branche dépense ».** La règle
> existait — les nœuds communs plus ceux de la branche, en points de budget nets — mais elle
> vivait **dans un test** (`PatronTreeContractTest`), donc hors de portée de tout autre
> appelant. L'écrire une seconde fois aurait produit exactement ce qu'ARC-08a a nommé sur la
> loi de durée : ***une règle recopiée dérive de son original en silence***. Le contrat des
> arbres patrons interroge désormais la fabrique.
>
> **Le simulateur dit ce qu'il ne joue pas.** Les cinq arbres livrés tiennent les quatre
> fonctions et les trois registres, mais **pas les douze cases** de la grille : `coverage()`
> les nomme en cliquet. *Un simulateur qui tairait ce qu'il ne joue pas donnerait à ses
> moyennes une autorité qu'elles n'ont pas.* Et c'est ici que le choix d'ouvrir par ARC-08a
> se paie : sans le Nécromancien, la fonction contrôle serait vide et le seuil « aucune
> fonction dominante dans les deux colonnes » n'aurait pas pu se calculer.
>
> **Trouvé en câblant** : `CombatBranchCatalog` n'avait **jamais été enregistré pour
> l'injection**. Son seul consommateur livré (`CombatBranchManager`) n'est instancié qu'à la
> main dans son test, si bien que le conteneur n'a jamais eu à résoudre son `$projectDir` —
> *un service privé qu'on n'utilise pas est retiré à la compilation, et son câblage manquant
> avec lui.*
>
> **ARC-17a — livré le 2026-08-06.** Le jalon s'ouvre sur un blocage : **quatre des cinq
> seuils portent sur les dégâts subis**, et rien ne permettait de les mesurer. `MonsterStatTemplate`
> dérivait la vie, la précision et la vitesse — **et pas ce que le monstre fait**. Les 65 espèces
> livrées se partagent **17 gestes d'attaque** (1 à quelques points de dégâts), si bien qu'un
> boss T4 et un commun T1 peuvent porter le même geste et frapper pareil : la vie va de 30 à
> 2 400 sur la grille (**×80**) quand les dégâts reçus ne bougent pas. *Monter de palier rendait
> les combats plus longs sans les rendre plus dangereux* — le défaut que la faille du milieu
> avait produit sur la vie (BES-01), transposé aux dégâts.
>
> La grille se **dérive de la vie** (un huitième, puis le rapport de rang) plutôt que de s'écrire :
> deux tables à la main divergent, une dérivation ne peut pas. Le rapport n'est pas choisi au
> hasard — il fait tomber le palier 2 sur **9** et **26**, exactement les deux nombres que le
> §9 octies avait calculés à la main, et un test le verrouille : *une dérivation qui rate sa
> propre référence ne dérive rien.*
>
> Ce qui est figé n'est pas le chiffre mais **le rapport** (§0.2) : une élite frappe près de
> trois fois un commun de son palier pour moins de deux fois ses PV — l'asymétrie qui fait
> qu'elle tue un joueur seul. **Aucune valeur de jeu ne bouge**, comme les deux ancres d'ARC-05
> avant elle, et un test vérifie que la formule de combat ne lit pas encore la dérivation.
>
> **ARC-17b — livré le 2026-08-08.** La dérivation est branchée, et `MonsterDamageLaw` dit
> **une fois** ce qu'un monstre retire de vie. La symétrie avec `MonsterMarkLaw` n'est pas une
> coquetterie : *un joueur porte ses dégâts dans son geste, un monstre les porte dans sa case*,
> parce que les gestes des monstres sont **partagés** — `none_attack_1` sert 38 des 65 espèces,
> de sept éléments et des quatre paliers.
>
> **Ce qui est remplacé est le nombre, et rien d'autre** : le geste garde son nom, son élément,
> son aire, le statut qu'il applique. **Le rapport entre gestes ne survit pas non plus**, et
> c'est une mesure : sur les 126 couples (sort du pool, attaque de base), le rapport va de 0,33
> à 7,0 pour une médiane de 3,0 — ces rapports ne disent pas « ce sort frappe sept fois plus
> fort », ils disent que l'attaque de base est le geste le plus faible d'une échelle plate où
> tout tient **entre 1 et 7**. Le conserver reviendrait à conserver l'artefact et à le
> multiplier par la dérivation : un boss T4 y gagnerait un sort à 1 200 dégâts. ***Ce qui ne
> portait pas d'intention n'en gagne pas en étant mis à l'échelle.***
>
> **Le jalon a trouvé un second chemin, et un défaut à son bout.** Un monstre retire de la vie
> par **deux** chemins, et un seul passe par un geste : le donjon de groupe résout sa riposte
> tout seul (DON-02) et lisait **`Monster::hit`** — la *précision*. La même valeur servait donc
> de **probabilité de toucher** en zone (`FightCalculator::hasAttackHit`) et de **dégâts** en
> donjon. Le commentaire de DON-02 disait pourtant ce qu'il voulait — *« le coup est celui du
> monstre de l'étape : une élite frappe plus fort qu'un commun, sans réglage spécial »* — et il
> ne pouvait pas l'obtenir : **aucun nombre de dégâts n'existait sur un monstre avant ARC-17a**.
> La précision va de 75 à 95 sur toute la grille, soit un facteur **1,27** là où le canon en
> demande 2,9 entre deux rangs voisins ; la riposte d'une élite T1 valait 80 PV et celle d'un
> boss T4 en valait 95. **Brancher un seul des deux chemins aurait laissé ARC-17c mesurer deux
> lois** selon qu'il joue une zone ou un donjon.
>
> **Le garde-fou d'ARC-17a est retourné et pas supprimé** — c'est lui qui documente qu'un jalon
> a livré une dérivation que personne ne lisait, et que c'était voulu. Il **visait pourtant le
> mauvais fichier** : ARC-17a supposait `MobActionHandler`, alors que le dégât se calcule dans
> `SpellApplicator` (donc aussi en invocation, en phase de boss, sur toute action résolue) et
> dans `GroupDungeonCombatService`. *Brancher là où l'action est choisie plutôt que là où le
> dégât est calculé aurait laissé la moitié du jeu hors de la loi.* Et parce qu'**une classe
> nommée n'est pas une classe lue**, le comportement est tenu à part par `MonsterDamageLawTest`.
>
> **Ce que la loi refuse** : un geste qui ne blesse pas ne se met pas à blesser (sinon chaque
> soin de monstre frapperait au palier de son porteur), et un geste en pourcentage garde le
> sien (il porte déjà une échelle).

> GAME_ARCHETYPES §0.2, §9 sexies et §9 septies. **Mesuré : les arbres ne sont pas
> équilibrés, et le classement dépend de l'échelle.** Sur un combat le guerrier domine ;
> sur une journée c'est le guérisseur (70 PV perdus contre 494 à 710), et le tank pur
> devient le pire des six. Aucun exercice individuel ne pouvait le voir.
>
> **Le livrable n'est pas un tableau, c'est un outil.** Quatre exercices manuels ont produit
> vingt corrections ; à cette échelle c'est tenable, au-delà ce ne l'est plus. Tous les
> nombres de GAME_ARCHETYPES sont des **repères calculés à la main sur une échelle
> illustrative** (§0.2) — le simulateur les remplace par des mesures sur les vraies données.

**`app:balance:simulate`** — la sœur **dynamique** de `app:balance:report` (qui, lui, est
statique : il compte et détecte des anomalies, il ne joue pas de combat).

- [x] **Entrées : les vraies données, jamais des constantes.** `Monster` (`tier`/`rank`
      après BES-01), `Item` (lignes d'armure et leur mitigation, armes, carquois),
      `Spell`/matéria (registre, intention, portée, coût, durée), `Skill` (leviers,
      conditions), `Domain` (élément × registre × fonction) *(ARC-17c-b — toutes lues, sauf
      **la mitigation d'armure qui n'existe pas dans le moteur** : elle est déclarée absente
      plutôt que simulée, et ARC-19 la réclame comme prérequis)*
- [x] **Builds de référence générés, jamais écrits à la main** : un par fonction × registre,
      arbre complet, équipement et matéria du palier. Écrits en dur, ils se périmeraient au
      premier changement de fixture — et c'est exactement ce qu'on cherche à détecter
      *(ARC-17c-a — `ReferenceBuildFactory` : **deux builds par arbre**, un par branche ; la
      couverture de la grille est nommée en cliquet. **Reste à ARC-17c-b** : l'équipement et
      la matéria du palier, qui demandent de jouer un tour pour signifier quelque chose)*
- [ ] **Y compris un invocateur**, et joué **dans les deux modes — présent et absent**
      *(impossible avant ARC-18 : la forme « familier » n'existe pas, donc aucun build dont la
      puissance dépende de la façon de jouer)*.
      C'est le premier build dont la puissance dépend de **la façon de jouer** et non de ce
      qu'on porte : aucune table statique ne peut le mesurer (§13.3, correction 21)
- [x] **Cinq scénarios** : un commun · une élite · un boss *(la rencontre à fenêtre)* · une
      **journée** (14 communs + 2 tentatives) · un **donjon à quatre**, joué dans les quatre
      compositions (avec/sans tank × avec/sans soigneur) *(ARC-17c-b les trois solo, ARC-17c-c
      la journée, **ARC-17c-d le donjon** — dont le relevé montre que la composition n'existe
      pas encore dans le moteur)*
- [◐] **Sorties** : la **table croisée** du §9 sexies (durée, PV restants, ressource
      dépensée, attente convertie en minutes) · l'**ancre de fonction** (écart entre le
      meilleur et le pire) · la **mortalité solo des élites** · la **matrice contexte ×
      fonction** du §9 septies.3 *(ARC-17c-c les trois premières, **ARC-17c-d la matrice** —
      et le relevé montre que ses deux colonnes sont aujourd'hui la même mesure dans deux
      unités)*
- [x] **Déterministe** — graine fixée. Une CI qui clignote ne sert à rien, et un
      équilibrage qu'on ne peut pas reproduire n'est pas un équilibrage *(ARC-17c-b — **aucun
      dé du tout** : on joue l'espérance, parce qu'une graine reste un tirage et qu'un seuil
      de CI finirait par se décider dessus. La variance est ce que l'espérance ne voit pas,
      et c'est dit)*
- [◐] **Seuils tenus en CI** *(ARC-17c-c — `BalanceSimulationRatchetTest`. **Quatre des cinq
      sont des cliquets** et non des seuils secs : ils sont rouges aujourd'hui, et un seuil
      qu'on sait rouge produit soit une CI qu'on ignore, soit un seuil desserré jusqu'à ne
      plus rien mesurer. Le relevé peut s'améliorer librement, il ne peut plus se dégrader en
      silence — le passage au seuil sec, après ARC-05c, sera un changement de chiffre)* :
  - [◐] écart d'attente quotidienne entre le meilleur et le pire build **< ×1,5**
        *(mesuré ×5,62 — cliquet à ×5,7)*
  - [x] **une élite tue un joueur seul**, quel que soit son archétype (102-129 % de sa barre)
        *(ARC-17c-c — **seuil dur**, et tenu : il ne dit rien de l'échelle, il dit ce qu'une
        élite est)*
  - [x] **un groupe sans tank ni soigneur vient à bout d'une élite de son palier** — sinon un
        rôle est devenu nécessaire, ce que le §7 bis interdit *(ARC-17c-d — **seuil dur**, tenu
        **par construction** : le donjon ne connaît ni soin ni mitigation. Écrit maintenant pour
        qu'ARC-18/19 le trouvent)*
  - [◐] aucun build hors des fourchettes de durée du §6.4 (commun 3-5, élite 6-10, boss 12-20)
        *(mesuré 6 builds sur 10 dans leur bande au palier 1, **0 au palier 2** — cliquet sur
        le nombre, qui ne peut que monter)*
  - [◐] aucune fonction dominante dans les **deux** colonnes de la matrice *(ARC-17c-d —
        l'assaut domine les deux, mais **le seuil est impossible à tenir en l'état** : les deux
        colonnes sont la même mesure dans deux unités tant que le groupe n'a ni soin ni aggro)*
- [x] **Rapport archivé et daté** dans [../BALANCE.md](../BALANCE.md), pour comparer d'une
      passe à l'autre — c'est la trace qui rend une régression lisible *(ARC-17c-c — §25,
      passe du 2026-08-18 ; il rend au §24.2 deux de ses trois questions ouvertes)*
- [◐] **Les deux curseurs que le simulateur doit fixer** : la **régénération des PM** hors
      combat (~6 s/point, à confronter aux 12 s/PV livrés) et
      `zone.dungeon.encounter_hp_per_member` (200 → ~110). Ce sont eux qui décident de
      l'équilibre solo et de l'équilibre de groupe respectivement *(ARC-17c-d — **mesurés, pas
      déplacés** : ARC-05c va multiplier les gestes par ~6, et le curseur juste après n'a aucun
      rapport avec le curseur juste avant. Ils se fixent dans le recalibrage conjoint, §25.9 de
      [../BALANCE.md](../BALANCE.md))*
- [ ] **Ce que le simulateur ne décide pas** : les règles (§0.2, colonne de gauche). Elles
      tiennent quelles que soient les valeurs, et une mesure qui les contredirait signalerait
      un bug de simulation avant un défaut de conception

### ARC-18 — Les formes de geste (L | ★★★ | MOYENNE) ✅
> **Complet le 2026-08-18 : les huit formes ont un lecteur.** `GestureForm::implemented()`
> rend désormais `cases()` — et c'est la forme la plus honnête, une liste écrite à la main
> ayant divergé de ses membres en silence une fois déjà (le défaut trouvé par ARC-18b sur
> `StatusEffect::TYPES`). Une neuvième forme devra **retirer** son nom de la liste tant
> qu'elle n'est pas branchée, et le test le lui rappellera.
>
> Reste la dernière ligne du jalon — les **sept refus** du §13.3 verrouillés par test.
>
> GAME_ARCHETYPES §13. Le vocabulaire d'intentions dit ce qu'un geste **fait** ; il ne dit
> rien de sa **forme**. C'est là que vivent les archétypes des autres MMO — un chasseur et
> un nécromancien ne diffèrent pas par leurs statistiques, mais parce qu'un **familier joue
> à leur place**. Huit formes retenues, **chacune réparant un défaut mesuré** (§13.2).
>
> **À livrer une forme à la fois**, jamais en bloc : chacune est un mécanisme de combat
> indépendant, et l'ordre ci-dessous va du moins cher au plus cher.
- [x] **La riposte** (S) — être frappé rend des dégâts. Un point d'accroche à l'encaissement.
      Répare : le tank ne tue pas (14 tours contre 6). Ne s'applique jamais aux dégâts évités
      *(**ARC-18a — livré le 2026-08-18.** `GestureForm` pose le **vocabulaire fermé des huit
      formes** — une neuvième est une décision de moteur, jamais un ajout de fixture (invariant
      36) — et une seule a un lecteur, la discipline d'ARC-16a appliquée aux formes.
      `RiposteLaw` dit ce qu'elle rend et quand elle ne rend rien.
      **Le garde-fou est le cœur de la forme, et sa lecture est arithmétique plutôt que
      déclarative** : on ne demande pas « la cible a-t-elle esquivé ? » mais « combien de points
      de vie ont réellement été retirés ? ». Un coup esquivé, absorbé par un bouclier ou ramené
      à zéro par la garde retire zéro, et zéro ne riposte pas — *poser la question sur le
      résultat plutôt que sur la cause ferme d'un coup tous les chemins d'évitement, y compris
      ceux qui n'existent pas encore*. Sans lui, l'encaisse optimale consisterait à se faire
      toucher exprès.
      **Elle rend une valeur fixe, jamais une part des dégâts reçus** : une part grandirait avec
      le palier de l'adversaire, donc la riposte vaudrait le plus contre ceux qui frappent le
      plus fort — elle récompenserait d'être en danger, ce que le garde-fou interdit déjà dans
      l'autre sens. `DepositValue` porte la borne de la correction 21, lue une fois pour toutes
      les formes qui déposent.
      **Aucune valeur de jeu ne bouge** : aucun geste livré ne porte la forme, et la loi est
      posée avant qu'il y ait quelque chose à relire — comme `ElementalMark` et `DepositLaw`
      avant elle)*
- [x] **La posture** (S) — un dépôt `scope: soi`, sans durée, **exclusif**. Répare : aucun
      choix à l'échelle d'une rencontre. En changer coûte le tour
      *(**ARC-18b — livré le 2026-08-18.** `StanceLaw` pose les quatre propriétés qui font
      qu'une posture est une décision, et la plus importante n'est pas dans le canon : **elle
      déplace le budget, elle ne l'ajoute pas.** Le garde-fou écrit — *en changer coûte le
      tour* — borne la **fréquence** des changements, jamais la **valeur** de ce qu'on prend :
      une posture qui donnerait `+9 %` sans rien retirer se paierait un tour et rapporterait
      dix tours de bonus sur une rencontre ordinaire, soit le meilleur rapport du jeu et un
      geste qu'on ne se pose même pas la question de jouer. La borne se **dérive** de la
      phrase qui définit la forme plutôt que de s'inventer : une fourche **répartit** les
      50 points de l'arbre entre deux branches, donc la posture — *la fourche à l'échelle de
      la rencontre* — répartit aussi. Le sacrifice reste légal ; seul le cadeau est refusé.
      **Elle s'écrit en leviers, jamais en `statModifier`** : ce champ existait et aurait été
      le rangement évident, mais son vocabulaire est **ouvert** (les quinze statuts livrés y
      écrivent `damage`, `speed`, `defense`, `shield_absorb`, `max_life`, et aussi quatre noms
      de leviers) — la leçon d'ARC-16a, *un système qui compte 50 points et laisse à côté un
      champ où l'on écrit n'importe quel chiffre ne compte rien*. Conséquence heureuse : la
      posture se somme aux nœuds **comme un dix-neuvième nœud**, si bien que rien dans la
      formule n'a besoin de savoir qu'elle existe, et que **les bornes des nœuds s'appliquent
      gratuitement** (une posture qui déplace `grip` ne parle pas sur un bouclier).
      **Deux défauts antérieurs trouvés, et le second est le plus grave.**
      (1) `TYPE_RIPOSTE` n'avait jamais rejoint `StatusEffect::TYPES` — donc le contrôle « les
      types se rangent sans reste » ne l'a pas vu, alors que c'est exactement ce qu'il existe
      pour voir. ARC-11b-b avait fermé la moitié du défaut (une liste **de test** qui
      vieillit) sans fermer l'autre (une liste **de référence** qui vieillit). On ne répare
      donc pas la liste mais la façon dont elle diverge : les constantes sont désormais
      énumérées **par réflexion**. *Une liste tenue à la main diverge de ses membres en
      silence.*
      (2) **Le malus d'un pacte n'atteignait jamais la formule.** ARC-15 l'a livré comme *la
      seule mécanique du canon qui rende un personnage mesurablement plus faible quelque
      part* ; il était lu, validé et compté au budget, et s'arrêtait là — parce que
      `CombatLeverScale::effectOf()` refusait tout total négatif. Le refus était **au mauvais
      endroit**, et son propre message le disait : « a **node** cannot invest -6 budget
      points » — on n'*achète* pas une puissance négative, ce qui est vrai à l'écriture d'un
      nœud (`SkillLeverReader` refuse déjà les points ≤ 0) et faux à la conversion d'un
      **total**, qui a pu être diminué depuis. *Un convertisseur convertit, il ne juge pas ce
      qu'on lui donne.* Le plafond se lit maintenant en valeur absolue — il borne l'ampleur
      d'un déplacement, dans les deux sens —, et le malus est branché : l'arbre paie le
      **net**, le personnage porte le **brut** de chaque côté.
      **Aucune valeur de jeu ne bouge** : la colonne naît vide sur les quinze statuts livrés,
      et aucun nœud livré ne porte encore de levier ni de pacte. Hors périmètre nommé : le
      donjon de groupe a son propre modèle (DON-02) et n'a pas de `Fight`, donc aucune posture
      ne s'y lit — à rouvrir avec ARC-19)*
- [x] **La conversion** (S) — échanger des PV contre des PM. Répare : le pyromancien paie
      deux fois. **Taux de change défavorable**, sinon convertir est toujours correct
      *(**ARC-18c — livré le 2026-08-18.** **Le taux se dérive, il ne se pose pas** : ARC-05b a
      établi que le temps d'attente est la seule monnaie commune aux quatre fonctions, et les
      deux ressources ont chacune leur curseur de régénération — 12 s par PV, 6 s par PM —,
      dont le rapport dit ce qu'un point de vie vaut en points de magie sans qu'on ait à en
      décider. C'est le **rapport** qui est figé, jamais le chiffre : déplacer un curseur
      déplace le taux, comme il déplace le calendrier d'ARC-06a. La pénalité est un facteur
      nommé plutôt qu'un second chiffre — la moitié —, si bien que *convertir rend la moitié de
      ce que le temps rendrait*, et un test refuse qu'on la ramène à 1,0 « pour que la forme
      serve enfin ».
      **Elle ne réduit pas la facture, elle la rend jouable**, et se tromper là-dessus serait
      se tromper de correction : le §9 octies.4 demande que le coût en PM du pyromancien
      descende ou que sa barre monte, pas qu'un bouton efface la différence. Ce que la
      conversion répare est autre chose — le relevé du §9 sexies le montre **en panne de PM au
      tour 8** alors qu'il lui reste des points de vie, et elle lui rend un choix à ce
      moment-là.
      **Deux garde-fous que le canon n'écrit pas**, et qui sont nécessaires : *la conversion ne
      tue jamais* (sans plancher, un geste qui coûte des PV peut coûter le dernier, et le
      joueur meurt **en lançant un sort**, d'une façon qu'aucun écran ne lui aura annoncée — le
      plancher est **un** PV et pas davantage : elle peut laisser à un coup de la mort, et
      c'est un pari qu'on laisse au joueur) ; et *l'arrondi va toujours contre celui qui
      convertit*, sans quoi la rentabilité dépendrait de la parité d'un nombre.
      **Elle se résout avant le coût, jamais après** — un geste de conversion sert précisément
      à payer un geste qu'on ne pourrait plus payer, et l'appliquer après exigerait d'avoir
      déjà les PM qu'on cherche à obtenir.
      **Défaut trouvé, du jalon précédent** : ARC-18b avait livré la posture **sans l'inscrire
      dans `GestureForm::implemented()`** — la liste ne coûte rien à oublier tant qu'aucun
      lecteur ne s'y adosse, ce qui est exactement pourquoi elle doit être tenue par un test.
      Les deux y entrent ici, et le cliquet ne va que dans un sens.
      **Aucune valeur de jeu ne bouge** : `life_cost` naît à 0 sur les 253 gestes livrés)*
- [x] **Le transfert** (M) — une part des dégâts des alliés revient sur soi. Répare :
      l'aggro est impossible sur une rencontre à PV partagés. Borné en pourcentage **et** en
      durée. Ally-side, donc il se multiplie (§9 quinquies)
      *(**ARC-18d — livré le 2026-08-18.** La forme qui répare le défaut le plus structurel des
      huit : *notre modèle ne peut pas avoir d'aggro*. La rencontre frappe le membre qui vient
      d'agir, il n'y a personne à provoquer — le transfert décide donc **qui paie**, sans jamais
      demander au monstre qui il vise, et **sans table de menace**.
      **Le total du coup ne bouge jamais** — *l'aggro ne réduit rien, elle déplace* (§13.4) :
      ce que les protecteurs prennent est retiré à la cible **et ajouté à eux**, ce qu'un test
      vérifie sur les deux moitiés ensemble. Vérifier la première seule laisserait passer la
      seule faute que le canon interdise explicitement à cette forme : devenir une réduction de
      dégâts déguisée.
      **Les deux bornes sont deux, et l'une sans l'autre ne suffit pas** : la part empêche
      qu'un allié devienne invulnérable (sans elle, la parade optimale d'un groupe serait un
      protecteur permanent), la durée empêche que le protecteur paie tout le combat (sans elle,
      le transfert ne serait pas un geste mais un état). La part est **la moitié**, et elle se
      **dérive** : le §13.4 borne déjà le déplacement de menace à « au plus la moitié », et lui
      donner une autre valeur ferait exister deux bornes pour une seule question.
      **L'anti-empilement est la règle qui rend la borne opposable** : deux protecteurs à 50 %
      ne retirent pas 100 % — *ce qui est transféré ne peut pas l'être deux fois*. Sans elle, la
      borne serait un plafond par personne et non par coup, donc elle s'annulerait elle-même dès
      qu'un groupe compte deux encaisses. Elle vit **au moment du coup** et non à la pose : deux
      gestes posés séparément ne peuvent pas savoir l'un de l'autre.
      **Défaut trouvé en ouvrant le jalon** : ***le donjon de groupe ne savait jouer que des
      gestes de dégâts*** — tout geste dont `damage` valait zéro retombait silencieusement sur
      l'attaque d'arme, si bien que le seul contenu de groupe du jeu n'acceptait aucun geste
      d'entretien ni d'encaisse, c'est-à-dire exactement les deux fonctions dont le canon dit
      qu'elles gagnent au groupe (§7 bis). Le transfert est le **premier geste non offensif que
      le donjon sache jouer** ; les autres restent à ouvrir.
      Il vit sur `GroupDungeonMember` et non sur un `StatusEffect`, et c'est un constat plutôt
      qu'un choix : le donjon a son propre modèle de combat (DON-02) — pas de `Fight`, donc pas
      de `FightStatusEffect` à déposer — et le transfert étant *par nature* une mécanique de
      groupe, il n'existe aujourd'hui aucun autre endroit où il aurait un sens.
      Deux garde-fous de plus : *un protecteur tombé ne protège plus* (sinon le groupe serait
      **plus** solide après une perte qu'avant) et *un protecteur ne se protège pas de lui-même*
      (sinon le geste le plus rentable du jeu serait de se transférer ses propres dégâts).
      **Aucune valeur de jeu ne bouge** : les deux colonnes naissent à zéro, et aucune fixture
      ne porte de statut `transfer`)*
- [x] **La charge** (M) — `generates` / `consumes` sur `Spell`, un compteur par rencontre.
      Répare : la mêlée n'a aucune raison d'aimer les longs combats. **Meurt avec la
      rencontre**
      *(**ARC-18e — livré le 2026-08-18.** La seule des huit formes dont la valeur **croît avec
      la durée du combat**, ce qui la met exactement là où la mêlée perd (§9 octies) : elle
      répare une rotation sans récompense, où les gestes se succèdent sans que rien ne
      s'accumule et où jouer bien ne se distingue pas de jouer au hasard.
      **Le garde-fou du canon est tenu par le rangement, pas par une routine** : la charge vit
      dans les **métadonnées du combat**, comme le registre des gestes d'ARC-06b — *le même
      endroit que ce qui n'a de sens que le temps d'une rencontre*. Elle n'a donc aucune
      colonne, et le jour où la rencontre s'efface elle s'efface avec. *Une remise à zéro qu'il
      faut penser à appeler finit par être oubliée* ; celle-ci n'existe pas parce qu'il n'y a
      rien à remettre.
      **Le plafond n'est pas dans le canon, et il est nécessaire** : sans lui la charge croîtrait
      linéairement avec la durée, si bien qu'un combat de quarante tours donnerait un geste
      quarante fois plus fort qu'au premier — ce n'est plus une ressource, c'est **une prime à la
      lenteur**, et elle irait à l'exact opposé de ce que la forme répare (*la mêlée doit aimer
      les longs combats, pas les provoquer*). Il vaut **cinq**, et il se dérive : la grille de
      reprise de la mêlée compte cinq crans (GAME_MATERIA §2.3 bis), donc une charge qui se
      remplit en cinq gestes tient **dans une rotation complète**.
      **Deux règles qui font de la charge une décision.** *Un geste qu'on ne peut pas payer ne
      se joue pas du tout* — il ne se joue pas en moins fort : un geste qui s'adapterait au
      compteur retirerait le choix, puisqu'il serait toujours correct de le lancer. Et *un geste
      ne peut pas à la fois générer et consommer* : il serait impossible à lire au moment de
      jouer (le joueur ne saurait pas s'il monte ou s'il dépense) et ses deux moitiés se
      neutraliseraient dès que le coût égale le gain — **la charge oppose deux gestes, elle n'en
      décore pas un seul**.
      Le refus se place au même rang que le carquois, **avant** la consommation des PM : un
      geste refusé ne doit rien coûter.
      **Aucune valeur de jeu ne bouge** : `charge_gain` et `charge_cost` naissent à 0 sur les 253
      gestes livrés)*
- [x] **Le différé** (M) — une file d'effets résolus en **tours de rencontre**. Répare :
      l'asynchronie n'est jamais un avantage. Seule forme qui l'exploite au lieu de la subir
      *(**ARC-18f — livré le 2026-08-18.** Le garde-fou du canon — *des tours, jamais des
      secondes* — n'est pas une commodité : dans un donjon où un tour peut durer des heures, une
      échéance en temps réel ferait exploser la bombe avant que le tour suivant n'ait été joué,
      ou trois tours trop tard selon la vitesse de connexion des autres. *Le geste dépendrait de
      la ponctualité d'inconnus plutôt que du combat.*
      **Chaque entrée porte son échéance, pas son compte à rebours** : un compte à rebours
      devrait être décrémenté à chaque tour — donc par quelqu'un, donc par un appel qu'on peut
      oublier —, quand une échéance se compare au tour courant et ne demande rien à personne.
      *Un état qui se lit ne dérive pas ; un état qu'il faut entretenir, si.*
      **Lire la file, c'est la consommer** : les séparer laisserait un appelant lire, agir, et
      oublier de vider — c'est-à-dire **une bombe qui explose à chaque tour jusqu'à la fin du
      combat**. La seule façon de ne pas écrire ce défaut est de rendre impossible de lire sans
      consommer. De même la comparaison d'échéance est **large** et non stricte : un tour peut
      être sauté, et une égalité stricte laisserait la bombe dans la file *pour toujours*, ni
      résolue ni effacée.
      **Trois garde-fous que le canon n'écrit pas.** *Attendre ne rapporte rien* (la correction 5
      transposée — sinon poser sa bombe au tour le plus lointain serait toujours correct, et le
      différé cesserait d'être un choix pour devenir un calcul) ; *le délai est borné en haut*
      (sans quoi un différé posé au tour 1 pour le tour 30 serait oublié de tout le monde, et un
      geste qu'on ne relie pas à sa cause n'est pas une mécanique mais du bruit) ; *il meurt avec
      la rencontre* (sinon un différé posé puis fui exploserait sur un monstre qui n'existait pas
      quand on a visé — garanti par le rangement, la file vivant dans les métadonnées du combat).
      **Le geste est calculé à la pose et non à l'échéance** : calculer plus tard ferait dépendre
      son résultat de l'état du monde deux tours après, c'est-à-dire d'une garde qu'on n'avait
      pas vue. Et les différés dus frappent **avant** le geste du tour : *sinon la bombe du tour 3
      s'appliquerait après le coup du tour 4, et le joueur ne pourrait plus lire sa propre
      séquence*.
      **Aucune valeur de jeu ne bouge** : `deferred_turns` naît à 0 — « tout de suite » — sur les
      253 gestes livrés)*
- [x] **L'ouverture** (M) — un geste posé depuis l'écran de zone, appliqué à la rencontre
      suivante. Répare : `tempo` n'a aucun effet modélisé. Coûte de l'**énergie d'action**,
      jamais un tour
      *(**ARC-18g — livré le 2026-08-18.** **Le garde-fou du canon est économique, pas
      ludique**, et c'est ce qui décide de tout le reste : un geste qui coûte un **tour** se paie
      dans la rencontre où on le joue, donc on le joue toujours si son effet dépasse celui d'une
      attaque ; un geste qui coûte de l'**énergie d'action** se paie sur la **journée**, et il
      entre alors en concurrence avec *un combat de plus* — la seule monnaie que le §9 septies
      reconnaisse.
      D'où **la dérivation du coût plutôt que son écriture** : il vaut un tiers de ce que coûte
      une chasse, si bien que la question posée au joueur est toujours la même et toujours
      lisible — *combien d'ouvertures est-ce que j'échange contre une rencontre ?* Écrire un
      chiffre à la main l'aurait décroché du jour où la chasse changera de prix. Plancher à 1 :
      *une ouverture gratuite serait posée avant chaque rencontre sans qu'on ait à y penser*,
      l'exact inverse de ce que le garde-fou cherche.
      **Trois règles que le canon n'écrit pas.** *Une seule ouverture en attente* — sans elle, la
      journée optimale consisterait à en poser dix avant d'engager, et l'ouverture cesserait
      d'être une préparation pour devenir un **stock** (la dérive que la charge évite en mourant
      avec la rencontre) ; une seconde **remplace** la première plutôt que d'être refusée, un
      refus étant plus surprenant qu'utile et l'énergie déjà dépensée perdue de toute façon.
      *La première rencontre la consomme, et elle seule* — sinon c'est un bonus permanent acheté
      une fois ; lire et consommer se font donc **en un seul geste**, comme la file des différés.
      *Elle ne se pose pas en combat* — c'est ce qui la distingue d'un geste ordinaire : posée
      pendant la rencontre, elle ne coûterait ni tour ni presque énergie et deviendrait le geste
      le moins cher du jeu.
      Elle vit **sur le joueur** et non dans un combat, et c'est la définition même de la forme :
      une ouverture se pose *hors* rencontre et attend la suivante — la ranger dans un combat
      serait une contradiction dans les termes.
      **Aucune valeur de jeu ne bouge** : `pending_opening` naît à 0)*
- [x] **Le familier** (M) — **arbitrage rendu (§13.3) : c'est un dépôt offensif, pas un
      acteur.**
      *(**ARC-18h — livré le 2026-08-18, et ARC-18 est complet.** `FamiliarLaw` applique la
      **correction 21**, la seule recalibration chiffrée que le canon ait faite sur lui-même —
      et qui **était déjà écrite quand elle n'a pas été appliquée** : la correction 5 (*la durée
      étale la valeur, elle ne l'augmente pas*) existait quand la première calibration a été
      posée. Sa valeur totale vaut donc **un tour d'attaque de son invocateur**, étalé sur sa
      durée, ce qui le met à l'équilibre quand le joueur est présent et à **+56 % sur six tours
      d'absence** : ***il ne vaut rien quand vous jouez, il vaut tout quand vous ne jouez pas***.
      **Sa valeur se dérive de l'attaque de son invocateur, jamais d'un chiffre en base** — un
      chiffre écrit vaudrait la même chose pour un débutant et pour un personnage fini, donc il
      serait dominant au jour 1 et décoratif au mois 3. *La borne est un rapport, pas un nombre.*
      L'affirmation du canon est rendue **calculable** plutôt que décrite (`contributionOver()`),
      et l'étalement passe par `DepositLaw` : une règle recopiée dérive de son original en
      silence.
      **Aucune valeur de jeu ne bouge** : aucune fixture ne porte de statut `familiar`)* Retirez le ciblage et il ne reste qu'une chose qui frappe à chaque tour
      pendant une durée : le critère d'admission du §13.1 impose donc de le traiter comme
      tel. On garde ce qui comptait — **il agit sur les tours où son invocateur est
      absent** — et la fiction entière ; on perd le ciblage, on économise un acteur, une IA
      et une cible. Mesuré : +2 % sur un commun, **+9 % sur une élite**, rendement ×2,4 le
      tour investi — il ne sert que sur les longues rencontres, comme *la charge*
- [x] **Sa valeur totale est fixée à ~1 tour d'attaque par invocation** (correction 21).
      La première calibration — 40 % du geste sur 6 tours, soit ×2,4 le tour investi —
      **était cassée en groupe** : le familier agit sur les tours de **la rencontre** quand
      son invocateur n'a que **les siens**, soit un taux de change de 4 pour 1. Mesuré,
      l'invocateur contribuait **+87 %** avec quatre invocations, et plus il invoquait plus
      il gagnait
- [x] **Règle générale à verrouiller** : *un dépôt **offensif** ne dépasse jamais un tour
      d'attaque par tour investi ; un dépôt **défensif** peut valoir davantage, parce que la
      barre de vie de sa cible l'écrête toute seule.* C'est ce qui autorise ×8,8 pour un soin
      de groupe et interdit ×2,4 pour des dégâts
- [x] Résultat visé : **à l'équilibre quand le joueur est présent** (solo comme en groupe),
      **+56 % sur six tours d'absence** — le familier ne vaut rien quand on joue et tout
      quand on ne joue pas. Le geste devient une décision : *je pose mon familier avant de
      fermer l'onglet*
- [x] Garde-fous du familier : meurt avec la rencontre · un seul à la fois · les passifs de
      l'arbre qualifient ses gestes (la double borne s'applique) · il ne mitige rien, ne
      protège personne, n'encaisse pas — **un invocateur en tissu reste aussi fragile qu'un
      mage**
- [ ] Les **sept refus** du §13.3 verrouillés par test là où c'est possible : aucune table de
      menace, aucun rôle nécessaire, aucun geste sans lecture `scope: soi`, aucun changement
      d'arme en combat, aucune ressource persistant entre deux rencontres, aucun tour
      supplémentaire, aucune montée en puissance entre les combats

### ARC-19 — L'aggro bornée (M | ★★★ | MOYENNE)
> **Prérequis ARC-20.** Tous les chiffres de ce jalon (147 PV, 144 encaissés, 120 sur
> 120) sont posés sur une barre de vie que **rien ne produit dans le code** (20 PV
> livrés) : ils se recalculent sur `VitalityLaw`. Livrer l'aggro avant la barre
> reviendrait à borner un transfert dont on ne sait pas ce qu'il transfère.
>
> GAME_ARCHETYPES §13.4. **Le refus du §13.3 est rouvert** : il reposait sur le modèle
> actuel du donjon (rencontre abstraite à PV partagés, aucune riposte). **DON-02/03 le
> change** — de vrais monstres, une vraie riposte —, et dès qu'une riposte existe, la
> question « qui la prend ? » se pose.
- [ ] **Par défaut, chacun encaisse la riposte de ses propres actions.** Un groupe sans tank
      fonctionne : c'est ce qui préserve « aucun rôle n'est nécessaire » (§7 bis)
- [ ] **Un geste de menace déplace au plus la moitié** de la riposte vers celui qui le pose,
      pour une durée. C'est la forme **transfert** (ARC-18), qui cesse d'être un
      contournement pour devenir le mécanisme lui-même. **C'est un dépôt** : il court même
      quand son lanceur est déconnecté (§7 bis) — sans quoi il ne servirait à rien dans un
      donjon dont les tours s'étalent sur des heures
- [ ] **La borne de 50 % n'est pas un choix de confort** : mesuré, au-delà le tank meurt quoi
      qu'il fasse, même en plaque (70 % → 165 encaissés sur 147 PV). En dessous de 30 %, le
      porteur de tissu reste au bord. L'intervalle utile est étroit et 50 % en est le centre
- [ ] **Il ne se maintient pas en permanence** : deux poses passent (tank 132/147), trois le
      tuent (149/147). Le tank **choisit ses fenêtres** — du jeu apparu tout seul, sans temps
      de reprise à inventer
- [ ] **Le calibrer comme une assurance, pas comme une mitigation** : le groupe n'économise
      que 15 % de dégâts, mais le porteur de tissu passe de **120 sur 120 (mort)** à **76 sur
      120 (vivant)**. Sa valeur est nulle quand tout va bien et totale quand quelqu'un allait
      tomber. *Le tank ne protège pas, il assure* — pendant exact du guérisseur qui provisionne
- [ ] **La table de menace reste refusée** : aucun score cumulé, aucune course au sommet,
      aucune perte d'aggro à gérer. Un geste, une part, une durée
- [ ] **Prérequis dans GAME_ITEMS — la fourchette de mitigation est mesurée (§2.2)** :
      **30 % minimum** pour que l'aggro passe, **50 % maximum** avant que le solo ne casse
      (c'est le point où la mitigation annule exactement la lenteur du tank : 14 tours
      contre 6), **cible ~40 %** → écart de PV effectifs ×2,3 / ×1,6 / ×1 entre plaque,
      cuir et tissu. Ce qui autorise une mitigation aussi forte, c'est la lenteur : la
      plaque ne rend pas invulnérable, elle rembourse le temps qu'on perd Mesuré : l'écart tank /
      tissu **par l'arbre seul** est de ×1,39 ; encaisser la part de quatre en demanderait
      ×4, soit **47 des 50 points de budget rien qu'en `guard`** — impossible, et le plafond
      du levier est à 15. **La mitigation d'un tank vient de son armure, pas de son arbre.**
      Avec la plaque à −28 %, l'aggro bornée à 50 % passe (144 encaissés sur 147 PV)
- [ ] **Vérifier que ça ne casse pas le solo** : une plaque à −28 % ferait du tank le
      meilleur solitaire si sa lenteur ne le rattrapait pas. Mesuré : 14 tours × 0,72 = 10
      tours-de-dégâts contre 6 pour l'archer — l'écart reste à l'avantage de l'assaut
- [ ] **Corriger `zone.dungeon.encounter_hp_per_member`** — le curseur livré (200) est
      calibré pour une rencontre **sans riposte**. Avec une riposte, 800 PV pour quatre font
      36 tours et 800 dégâts, contre un pool de groupe de 518 : **le soigneur devient
      obligatoire**, ce que le §7 bis interdit. À ~**110** — 120 laisse le porteur de tissu
      exactement à zéro (118 encaissés sur 120 PV), ce qui n'est pas une difficulté mais un
      fil du rasoir. Règle qui le remplace : *une
      rencontre de groupe se calibre sur le **pool de PV du groupe**, jamais sur un multiple
      du nombre de joueurs* — un multiple linéaire produit une difficulté qui ne dépend pas
      de la composition, et qui exige donc la meilleure
- [ ] Tests : un groupe sans tank et sans soigneur vient à bout d'une élite de son palier ;
      aucun score de menace cumulé ; la part déplacée ne dépasse jamais 50 %

### ARC-20 — La barre de vie : le Socle, la loi, et les cascades (L | ★★★ | HAUTE) ◐ → 3 sous-phases
> [../GAME_VITALITY.md](../GAME_VITALITY.md) (2026-08-18). **Le trou que personne
> n'avait nommé** : le personnage n'a pas de niveau, et **rien ne fait monter sa barre
> de vie**. Mesuré — `PlayerFactory::BASE_LIFE` vaut **20**, plafonné entre 26 et 40 PV
> une fois tout appris, quand `MonsterStatTemplate` fait **×80** de T1 à T4 et qu'une
> élite T4 frappe **110**. Le canon, lui, raisonne depuis le premier jour avec
> « joueur 120 PV » au palier 2 (§9 bis, §9 sexies, §9 octies) : **tous ses chiffres
> reposent sur une échelle que le code ne produit nulle part**, ce qui rend
> inatteignables **quatre des cinq seuils** d'ARC-17 — ils portent sur les dégâts subis.
>
> **Ce que l'étude des autres jeux a tranché** (GAME_VITALITY §1) : OSRS fait de la vie
> une compétence qui monte en combattant — refusé, elle récompense les rats (ARC-06b) ;
> PoE et Dofus la font acheter dans l'arbre — refusé, **c'est une taxe** que tout le
> monde paie ; Albion la met dans l'équipement — refusé, le marché déciderait de la
> survie, et le full loot qui l'équilibre là-bas nous est interdit (règle 11). **Ryzom**
> donne le véhicule (le pool dérive de la branche la plus avancée, **jamais de la
> somme**) et **FFXIV/GW2** le découpage (la base vient du contenu, l'archétype d'un
> multiplicateur qu'on n'achète pas — chez nous, **la ligne d'armure**).
>
> **La règle qui en sort, et qui tient tout le jalon** : *la progression verticale ne
> doit jamais être un choix ; seule la différenciation l'est.* C'est le Socle qui rend
> le levier `life` **facultatif** — sans lui, il deviendrait obligatoire dans les
> 24 arbres et le budget de 50 pb n'en compterait plus que 30.

#### ARC-20a — La loi, et rien d'autre (M) ✅
- [x] **`VitalityLaw`** : *la barre d'un joueur de palier n vaut ce qu'une élite de son
      palier lui prend en une rencontre entière* — `8 × MonsterStatTemplate::attackFor(n, Elite)`.
      Les 8 tours ne sont pas un chiffre de goût : c'est **le centre de la bande de durée
      d'une élite** déjà livrée dans `EncounterAnchor::TURN_BANDS`. La barre est définie
      par le **format d'une rencontre**, jamais par une table
- [x] **La dérivation retrouve ses propres références** — 17 % de la barre pour un commun
      sur quatre tours (bande « 16 à 26 % » du §9 octies), **100 %** pour une élite sur
      huit (mesuré « 102 à 129 % »), et **le rapport ne dépend pas du palier** puisque les
      deux membres dérivent de la même vie de commun. Test : *une dérivation qui rate sa
      propre référence ne dérive rien* (le critère d'ARC-17a)
- [x] **`MendingAnchor`**, frère symétrique d'`EncounterAnchor` : soin direct = **25 % de
      la barre du palier**, dépôt = **8 % par tour**. Sans lui, la grille des soins et
      celle des dégâts divergent au premier ajustement du bestiaire
- [x] **Aucune valeur de jeu ne bouge** — comme `EncounterAnchor`, `DailyAnchor` et
      `MonsterStatTemplate::attackFor()` avant elle, cette sous-phase rend une règle
      **calculable** pour qu'on mesure l'écart. Un test vérifie qu'aucune formule ne lit
      encore la loi, et documente que c'est voulu
- [x] **Constat à porter en cliquet** : un boss de palier 2 frappe **19,7 % de la barre
      par tour** — il tue en 5 tours quand sa bande en demande 12 à 20. *Un boss n'est pas
      un contenu solo, et la barre le dit sans qu'on ait à l'interdire*


> **ARC-20a — livré le 2026-08-18.** `VitalityLaw` et `MendingAnchor` posent les deux
> lois, et **aucune valeur de jeu ne bouge** — deux tests le tiennent, l'un par fichier
> (rien dans `PlayerFactory`, `PlayerEffectiveStatsCalculator` ni `LifeRegenManager` ne
> nomme la loi), l'autre sur `SpellApplicator` pour l'ancre des soins ; ils seront
> **retournés et pas supprimés** par ARC-20b/c, comme celui d'ARC-17a l'a été par ARC-17b.
>
> **Les huit tours se dérivent** de `EncounterAnchor::TURN_BANDS['elite']` au lieu de
> s'écrire : poser la constante ferait diverger la barre de la bande le jour où l'une des
> deux bouge, ce que ce jalon existe précisément pour empêcher.
>
> **La dérivation retrouve ses propres références** — 16 à 26 % de barre pour un commun
> sur une rencontre, et une élite qui tue en un nombre de tours qui **retombe dans sa
> propre bande** (8, dans 6-10). Ce second invariant n'est pas tautologique : un arrondi
> malheureux ou une grille d'attaque remaniée le perdraient en silence.
>
> **Le constat sur les boss entre en cliquet** : un boss tue en 5 à 6 tours quand sa bande
> en demande 12 à 20, à **tous les paliers**. *Un boss n'est pas un contenu solo, et la
> barre le dit sans qu'on ait eu à l'interdire.*
>
> **L'écart avec le livré entre en cliquet lui aussi** : `PlayerFactory::BASE_LIFE` (20)
> ne peut plus dépasser le plancher de la loi (96) — il peut se réduire, plus s'aggraver.
>
> **Trouvé en écrivant** : la loi doit **borner** hors des quatre paliers plutôt
> qu'extrapoler. Le palier 0 du bestiaire ne sert qu'aux mannequins, qui ne frappent pas ;
> lui donner une barre propre créerait un cinquième palier ne correspondant à aucun
> contenu — le défaut du §9 quater, celui qui avait éteint l'archer, transposé à la barre.

#### ARC-20b — Le Socle : un nœud visible **et** la loi (L)
- [ ] **Les deux, jamais l'un ou l'autre** : le nœud existe pour être **vu** (un palier de
      vie est un moment de la progression, pas une variable cachée), la valeur est
      **calculée** (une valeur écrite dans 24 arbres diverge au premier ajustement)
- [ ] **Sa forme** : une **porte**. 0 point, **0 pb**, aucun levier, aucun geste, aucun
      droit de port. **Gratuit parce qu'il n'est pas une récompense** — le faire payer en
      points en ferait un péage, en budget la taxe de PoE
- [ ] **Sémantique de maximum, jamais de somme.** C'est la seule forme qui survive à « le
      savoir n'est jamais borné » : un nœud additif à +100 PV donnerait **+3 200 PV** au
      joueur qui a mené les 32 arbres — le défaut exact de `Skill::life` aujourd'hui
- [ ] **Un Socle par palier 1, 2 et 3 dans chacun des 24 arbres de combat.** Le gabarit
      passe de 18 à **21 nœuds écrits** ; `PatronTreeContractTest` et le compte de budget
      sont à reprendre — le Socle pèse **0 pb**, donc les 50 pb ne bougent pas
- [ ] **Amendement à GAME_TREE_ANATOMY §2** : la liste des **six natures** répondait à
      *« qu'est-ce qu'un nœud donne à un build ? »* ; le Socle enregistre *ce que le
      personnage est devenu capable d'encaisser*, une question qu'elle ne posait pas. La
      règle qui la referme : *une septième nature n'est admise que si elle ne donne ni
      geste, ni levier, ni droit de port — sinon c'est l'une des six sous un autre nom*
- [ ] **Le plancher est porté par `PlayerFactory`, jamais par un arbre** : un personnage
      qui sort du tunnel, ou qui ne mène que des arbres de métier, a le palier 1 sans rien
      avoir appris (même principe que l'outil de palier 1 d'OBJ-06 et le plancher du
      jour 1 de GAME_MATERIA §3). **On ne peut pas se retrouver sans barre de vie**
- [ ] **Retirer `Skill::life`** — plat, cumulatif, hors budget, **écrit en dur** dans
      `Player::maxLife` par `SkillAcquiring` : la même fuite que les échelons de port de
      l'écart n° 5. Migration des personnages existants, et `SkillRespecManager` n'a plus
      de bonus plat à défaire — **un respec rend des points, jamais un palier**
- [ ] Tests : les invariants 1 à 6 et 11 de GAME_VITALITY §8 (aucun cumul, aucun coût,
      aucun levier, couverture des 24 arbres en **cliquet**, plancher inconditionnel,
      aucune source indexée sur le nombre d'arbres appris)

#### ARC-20c — Les cascades (M)
- [ ] **`LifeRegenManager` passe en pourcentage de la barre.** Livré, il régénère
      **12 secondes par point, en absolu** : le retour à pleine vie passe de 19 min au
      palier 1 à **2 h 56** au palier 4, et l'ancre en minutes d'attente de `DailyAnchor`
      explose. Invariant : *le temps de retour à plein ne dépend pas du palier*
- [ ] **La grille des soins appliquée** — sorts de soin et potions sur `MendingAnchor`.
      **L'obsolescence est une fonctionnalité** (un soin de palier 1 rend 2,7 % d'une barre
      de palier 4) ; la seule chose à garantir est le **plancher du jour 1** : l'accord
      d'entrée gratuit ouvre un soin de **son** palier, jamais un soin figé au palier 1
- [ ] **Effet de bord à encaisser, pas à corriger** : les potions deviennent une échelle de
      paliers comme les outils (OBJ-06). L'alchimiste a un produit **à chaque palier** au
      lieu d'un seul qui se périme — du contenu économique gratuit pour MET et ECO
- [ ] **Trancher `Item::protection`** : lu par `EquipmentSetResolver`, affiché sur la fiche
      d'inventaire, **et par aucune formule de combat**. Le brancher comme mitigation
      (ARC-19) ou le retirer — *un chiffre affiché sans effet est un mensonge d'interface*
- [ ] **`ReferenceCharacterFactory::maxLifeOf()` lit la loi** et non `BASE_LIFE` : c'est ce
      qui rend les cinq seuils d'ARC-17 mesurables, et la raison pour laquelle ce jalon
      passe **avant** la suite d'ARC-17 et **avant** ARC-19
- [ ] Tests : les invariants 7 à 10 et 12 de GAME_VITALITY §8 (la part qu'un commun retire,
      l'élite mortelle en solo, le temps de retour constant, la part qu'un soin rend, et
      **plus de la moitié de l'écart de PV effectifs vient de l'armure** — la décision 21
      du canon, enfin mesurable)

---

## Risques

| Risque | Parade |
|---|---|
| **ARC-03 est un refactor de formule de combat** — le genre de chantier qui casse silencieusement l'équilibrage | Un levier = **une place dans la formule**, écrite au §4 et vérifiée par test. Le convertisseur de taux de change est **unique** : un seul endroit à relire |
| **ARC-02 touche la même migration que MAT-01** (l'objet matéria) | Les livrer **ensemble**, jamais sur deux branches — même contrainte que BES-01/MAT-01 |
| **ARC-05 recalibre tout le combat** et peut invalider le contenu déjà écrit | On ne fixe pas des valeurs, on fixe des **ratios** (durée en tours) ; le simulateur de combat en test rend la régression visible avant la mise en ligne |
| Le vocabulaire de 13 leviers **enfle** au fil du contenu | L'ensemble est **fermé** : un levier neuf est une décision de moteur, instruite ici, jamais un ajout de fixture |
| Les munitions sont perçues comme une **taxe** sur l'archer | Trois garde-fous testés (§2) : plancher T1 PNJ, seules les munitions élémentaires se consomment, récupération par levier. Et l'archer a en échange le meilleur rendement par tour |
| La **fonction** se relit comme un retour des classes | Elle n'est jamais affichée, ne ferme aucun arbre et ne conditionne aucun port. C'est une contrainte d'auteur — le joueur n'en voit que la conséquence |
| Les **passifs conditionnels** se relisent comme des interdits de port | Une condition ne ferme rien : le mage en plaque existe toujours, il n'a pas le bonus. L'UI d'ARC-12 dit ce qu'on gagnerait à porter autre chose — jamais ce qui est refusé |
| Les **dépôts de groupe** rendent un rôle **obligatoire** en donjon | Garde-fou testé (ARC-11) : un groupe sans entretien met plus de tours et perd plus de PV, il ne rencontre pas un mur. Aucune rencontre ne suppose une composition |
| **La barre de vie devient une taxe** — le défaut mesuré de Path of Exile et de Dofus | Le **Socle** ne coûte ni point ni budget, et c'est ce qui rend le levier `life` facultatif. *La progression verticale n'est jamais un choix ; seule la différenciation l'est* (GAME_VITALITY §1.3) |
| Le **Socle** se relit comme un niveau de personnage déguisé | Il est **par arbre**, jamais global ; sa sémantique est le **maximum**, jamais la somme ; il ne s'affiche pas comme un rang, et il **n'ouvre aucun contenu** — il donne la survie, jamais la capacité de nuire |
| **L'aggro rend un rôle obligatoire** | Elle est **bornée à 50 %** et portée par un **geste**, jamais par une table : sans tank, chacun encaisse la sienne et le groupe passe. Un test l'exige (ARC-19) |
| **L'entretien casse l'équilibre solo** s'il ne paie rien | Le curseur de régénération des PM (ARC-17) est ce qui le borne. Sans lui, mesuré : 14 minutes d'attente par jour contre 99 à 142 pour les autres — il joue trois fois plus de contenu pour la même énergie d'action |
| **L'assaut n'a pas de raison d'exister** tant que la vitesse ne vaut rien | Les rencontres à fenêtre (ARC-17). Une chasse coûte 5 points d'énergie quel que soit le nombre de tours : sans contenu à fenêtre, tuer vite ne rapporte rien |
| La **suppression du plafond** ouvre la porte au personnage qui a tout appris | C'est le contrat des trois couches, et les conditions d'équipement le resserrent : on ne porte pas à la fois la plaque, le cuir, le bouclier, la dague et l'arc |
