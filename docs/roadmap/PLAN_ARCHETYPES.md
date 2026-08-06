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

**19 jalons** (**ARC-01** à **ARC-19**) en 4 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| ARC-01 ✅ | La fonction, troisième axe du domaine (`Domain::role` + palettes) | S | ∅ |
| ARC-02 ✅ | Le registre du geste + premières matéria de technique | M → 2 sous-phases | ← MAT-01, MAT-03 |
| ARC-03 ✅ | Les leviers : les passifs deviennent des pourcentages bornés | **L** → 2 sous-phases | ← ARC-01 |
| ARC-04 ✅ | Les ressources par registre (munitions, temps de reprise) | M → 2 sous-phases | ← ARC-02 |
| ARC-05 ◐ | L'ancre d'échelle : la durée d'un combat en tours | **L** → 2 sous-phases | ← BES-01 |
| ARC-06 ✅ | L'échelle de coût des arbres, et le gain de points indexé au palier | M | ← BES-01 |
| ARC-07 | Les quatre arbres patrons, écrits au gabarit | M | ← ARC-03, 04, 06 |
| ARC-08 | Conversion mécanique des 20 autres arbres | M | ← ARC-03, ARC-07 |
| ARC-09 | Tests du plan (les 45 invariants) | S | ‖ |
| ARC-10 ✅ | Le plafond global de points — **tranché : suppression** | S | ∅ |
| ARC-11 | L'intention et la portée du geste, et la loi du dépôt | M | ← ARC-02 |
| ARC-12 ✅ | Les passifs conditionnels d'équipement | M | ← ARC-03 |
| ARC-13 | Les huit marques élémentaires | M | ← ARC-11 |
| ARC-14 | La fourche : une branche exclusive par arbre de combat | S | ← ARC-07 |
| ARC-15 ✅ | Le pacte : un malus rend du budget | S | ← ARC-03 |
| ARC-16 | Les accointances : la synergie donne de la souplesse, pas de la puissance | M | ← ARC-12 |
| ARC-17 | **Le simulateur d'équilibrage** (`app:balance:simulate`) — l'outil qui remplace les repères calculés à la main | M | ← ARC-05, ARC-07 |
| ARC-18 | Les formes de geste : huit mécaniques empruntées, chacune réparant un défaut mesuré | M | ← ARC-11 |
| ARC-19 | L'aggro bornée, et ce qu'elle exige de l'armure | M | ← DON-03, OBJ |

```
Piste A — Le modèle   : ARC-01 → ARC-03 → ARC-12 → ARC-16 ; ARC-03 → ARC-15
                        ARC-02 → ARC-04 ; ARC-02 → ARC-11 → ARC-13
Piste B — L'échelle   : ARC-05 → ARC-17 ; ARC-06 ‖ ARC-10
Piste C — Le contenu  : ARC-07 → ARC-08 ; ARC-07 → ARC-14 ; ARC-09 ‖
Piste D — Les formes  : ARC-11 → ARC-18 (a livrer par forme, jamais en bloc) ; ARC-19 ← DON-03
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

### ARC-07 — Les quatre arbres patrons (M | ★★★ | HAUTE)
> GAME_ARCHETYPES §9. Pyromancien (assaut), Guérisseur (entretien), Soldat (encaisse),
> Archer (assaut/distance) — un par fonction, trois registres.
- [ ] Les quatre arbres écrits au gabarit : 15 nœuds, 5 accords, 7 passifs, 2 échelons de
      port, 1 dormant
- [ ] Les accords choisis **par rôle dans le combat**, jamais par niveau de sort
- [ ] Les capstones conditionnels, condition atteignable au tour 2 avec les seuls accords
      d'entrée
- [ ] Ce sont les 4 domaines de combat **nourris en contenu** (GAME_PROGRESSION §7.1) : le
      contenu matéria, butin et donjon se concentre sur eux
- [ ] Tests : les 10 invariants du §12 passent sur les quatre

### ARC-08 — Les vingt autres arbres (M | ★★ | MOYENNE)
> GAME_ARCHETYPES §11.3. Conversion mécanique plutôt que réécriture — le jeu est en pur
> dev, aucune compatibilité n'est due.
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

### ARC-09 — Tests du plan (S | ★★ | HAUTE)
> ‖ au fil des jalons. Les 45 invariants de GAME_ARCHETYPES §12.
- [ ] Budget (50 pb), plafonds par levier, règle des 80/20
- [ ] Grille : une fonction par domaine, aucun triplet en double
- [ ] Gabarit : 15 nœuds, échelle de coût, 2 entrées à 0 point **qui sont des accords**
- [ ] Capstone : unique, conditionnel, 14 pb, condition atteignable au tour 2
- [ ] Registre : tout arbre ouvre au moins un geste de son registre
- [ ] Intentions : palette tenue, un `dégât` et un non-`dégât` par arbre, un geste de
      portée collective pour l'entretien et l'encaisse
- [ ] Dépôt : aucun geste de portée `le groupe` instantané ; durée en tours de rencontre
- [ ] Conditions : les cinq garde-fous du §4.3, et le **vocabulaire fermé** (12 entrées) ;
      aucun plafond global de points
- [ ] Marques : une par élément, appliquée par un accord d'entrée de chaque arbre
- [ ] Fourche : deux branches sans levier commun, chacune tenant les 50 pb
- [ ] Pacte : unique, borné, feuille, hors palette, jamais au palier 1
- [ ] Accointances : aucune ne rend un point de budget, un levier ou une statistique
- [ ] Exclusivité : chaque arbre ouvre au moins un accord que nul autre n'ouvre
- [ ] Règle 9 étendue au chemin des techniques ; aucun passif plat restant

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

### ARC-11 — L'intention, la portée, et la loi du dépôt (M → 2 sous-phases | ★★★ | HAUTE) ◐

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
> **ARC-11b-b — reste à faire** : *les leviers visent par intention* (`mending` ne touche
> que `soin`, `grip` que l'`entrave` — une fois sur le geste, jamais quinze fois dans
> quinze formules), et la palette d'intentions tenue par arbre, qui se mesure à
> l'écriture des arbres (ARC-07/08).
> GAME_ARCHETYPES §3.1 et §7 bis. **Ce jalon décide si le donjon de groupe a un sens.**
> Le combat de groupe est semi-synchrone (`GroupDungeonCombatService` : un joueur actif à
> la fois, 45 s par tour, tour d'un absent résolu tout seul) — un soin **réactif** y est
> une mécanique morte.
- [x] `SpellIntent` (dégât / soin / protection / amélioration / entrave) et `SpellScope`
      (soi / un allié / le groupe / une cible / plusieurs cibles) sur `Spell`, hérités par
      la matéria *(ARC-11a — colonnes **nullables** avec repli de dérivation
      (`SpellIntentDeriver`) : les 8 types de `StatusEffect` se rangent sans reste dans
      les 5 intentions. Aucune valeur de jeu ne bouge — les colonnes naissent vides)*
- [ ] Les leviers visent par **intention** : `mending` ne touche que `soin`, `grip` que
      l'`entrave`. Une fois sur le geste, jamais quinze fois dans quinze formules
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
- [ ] Les leviers visent par **intention** ; palette d'intentions tenue par arbre ; tout
      arbre ouvre au moins un `dégât` et au moins un non-`dégât` *(**ARC-11b-b** — se
      mesure à l'écriture des arbres, ARC-07/08)*
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

### ARC-13 — Les huit marques élémentaires (M → 2 sous-phases | ★★★ | HAUTE) ◐

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
> **ARC-13b-b — reste à faire** : le côté monstre (croise BES-01 — 21 monstres sur 65 ont
> un sort, 9 appliquent un statut, et `ward` est dans deux palettes sur quatre sans rien
> à quoi résister), et le catalogue public qui peut enfin dire à quoi sert un élément.
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
- [ ] **Côté monstre aussi** (correction du §9 ter) : mesuré, **21 monstres sur 65** ont un
      sort et **9 de ces sorts** appliquent un statut. `ward` figure dans deux palettes sur
      quatre et l'accord de dissipation du Guérisseur n'a rien à dissiper — une marque qui
      n'existe que dans un sens est un levier mort pour la moitié des fonctions. Croise
      **BES-01** (l'élément des monstres, prérequis de MAT-01)
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
- [ ] Le **catalogue public** (GAME_ONBOARDING §6) peut enfin dire à quoi sert un élément —
      sans jamais donner de valeur
- [ ] Tests : une marque et une seule par élément ; tout arbre de combat a un accord
      d'entrée qui applique la sienne ; aucune marque ne se cumule avec elle-même

### ARC-14 — La fourche (S | ★★★ | HAUTE)
> GAME_ARCHETYPES §6.1 bis. **Deux pyromanciens finis étaient identiques.** Le mécanisme
> existe déjà et n'a jamais servi au combat : `actions.specialization.branch`, le motif de
> refus `other_branch` et le respec de branche payant sont livrés (DOM-04, DOM-06).
- [ ] Palier 3 : **deux branches de deux passifs et d'un accord**, une seule apprenable —
      l'arbre écrit 18 nœuds et 60 pb, le personnage en apprend 15 et en porte 50
- [ ] **Chaque branche ouvre son geste**, et c'est ce qui décide si la fourche est un choix
      ou une décoration : mesuré au §9 bis, deux branches qui ne diffèrent que par leurs
      passifs produisent **le même combat, au tour près** (11 tours contre 11). Avec un
      accord par branche, elles se séparent de deux tours et de deux façons de jouer
- [ ] Une fourche peut opposer **deux contextes** (seul / en groupe), pas seulement deux
      dosages — c'est la forme la plus forte trouvée (§9.3), et elle n'oblige personne
- [ ] Les quatre arbres patrons ont leur fourche (§9.1→9.4) : Braise/Éclat,
      Ressac/Marée, Mur/Ligne mobile, Guet/Volée
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

### ARC-16 — Les accointances (M | ★★ | MOYENNE)
> GAME_ARCHETYPES §9.7. **Constat : les synergies livrées sont une fuite de budget.**
> `DomainSynergy` donne des statistiques plates (`damage +10`, `heal +15`) ajoutées dans
> `CombatSkillResolver` **hors** des 50 pb, hors des plafonds, hors des palettes.
- [ ] Refonte : une accointance ne donne **jamais** de puissance — quatre formes légales
      seulement (élargir ce qui satisfait une condition, ce qui exprime un domaine, ce
      qu'un emplacement accepte ; réduire un coût d'accès)
- [ ] Migration des synergies livrées vers ces formes, ou suppression
- [ ] Une accointance par paire, effet unique, **jamais nécessaire**
- [ ] Tests : aucune accointance ne rend un point de budget, un levier ou une statistique ;
      aucune recette, aucun palier, aucun contenu n'en dépend

### ARC-17 — Le simulateur d'équilibrage (M | ★★★ | HAUTE)
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

- [ ] **Entrées : les vraies données, jamais des constantes.** `Monster` (`tier`/`rank`
      après BES-01), `Item` (lignes d'armure et leur mitigation, armes, carquois),
      `Spell`/matéria (registre, intention, portée, coût, durée), `Skill` (leviers,
      conditions), `Domain` (élément × registre × fonction)
- [ ] **Builds de référence générés, jamais écrits à la main** : un par fonction × registre,
      arbre complet, équipement et matéria du palier. Écrits en dur, ils se périmeraient au
      premier changement de fixture — et c'est exactement ce qu'on cherche à détecter
- [ ] **Y compris un invocateur**, et joué **dans les deux modes — présent et absent**.
      C'est le premier build dont la puissance dépend de **la façon de jouer** et non de ce
      qu'on porte : aucune table statique ne peut le mesurer (§13.3, correction 21)
- [ ] **Cinq scénarios** : un commun · une élite · un boss *(la rencontre à fenêtre)* · une
      **journée** (14 communs + 2 tentatives) · un **donjon à quatre**, joué dans les quatre
      compositions (avec/sans tank × avec/sans soigneur)
- [ ] **Sorties** : la **table croisée** du §9 sexies (durée, PV restants, ressource
      dépensée, attente convertie en minutes) · l'**ancre de fonction** (écart entre le
      meilleur et le pire) · la **mortalité solo des élites** · la **matrice contexte ×
      fonction** du §9 septies.3
- [ ] **Déterministe** — graine fixée. Une CI qui clignote ne sert à rien, et un
      équilibrage qu'on ne peut pas reproduire n'est pas un équilibrage
- [ ] **Seuils tenus en CI** :
  - [ ] écart d'attente quotidienne entre le meilleur et le pire build **< ×1,5**
  - [ ] **une élite tue un joueur seul**, quel que soit son archétype (102-129 % de sa barre)
  - [ ] **un groupe sans tank ni soigneur vient à bout d'une élite de son palier** — sinon un
        rôle est devenu nécessaire, ce que le §7 bis interdit
  - [ ] aucun build hors des fourchettes de durée du §6.4 (commun 3-5, élite 6-10, boss 12-20)
  - [ ] aucune fonction dominante dans les **deux** colonnes de la matrice
- [ ] **Rapport archivé et daté** dans [../BALANCE.md](../BALANCE.md), pour comparer d'une
      passe à l'autre — c'est la trace qui rend une régression lisible
- [ ] **Les deux curseurs que le simulateur doit fixer** : la **régénération des PM** hors
      combat (~6 s/point, à confronter aux 12 s/PV livrés) et
      `zone.dungeon.encounter_hp_per_member` (200 → ~110). Ce sont eux qui décident de
      l'équilibre solo et de l'équilibre de groupe respectivement
- [ ] **Ce que le simulateur ne décide pas** : les règles (§0.2, colonne de gauche). Elles
      tiennent quelles que soient les valeurs, et une mesure qui les contredirait signalerait
      un bug de simulation avant un défaut de conception

### ARC-18 — Les formes de geste (L | ★★★ | MOYENNE)
> GAME_ARCHETYPES §13. Le vocabulaire d'intentions dit ce qu'un geste **fait** ; il ne dit
> rien de sa **forme**. C'est là que vivent les archétypes des autres MMO — un chasseur et
> un nécromancien ne diffèrent pas par leurs statistiques, mais parce qu'un **familier joue
> à leur place**. Huit formes retenues, **chacune réparant un défaut mesuré** (§13.2).
>
> **À livrer une forme à la fois**, jamais en bloc : chacune est un mécanisme de combat
> indépendant, et l'ordre ci-dessous va du moins cher au plus cher.
- [ ] **La riposte** (S) — être frappé rend des dégâts. Un point d'accroche à l'encaissement.
      Répare : le tank ne tue pas (14 tours contre 6). Ne s'applique jamais aux dégâts évités
- [ ] **La posture** (S) — un dépôt `scope: soi`, sans durée, **exclusif**. Répare : aucun
      choix à l'échelle d'une rencontre. En changer coûte le tour
- [ ] **La conversion** (S) — échanger des PV contre des PM. Répare : le pyromancien paie
      deux fois. **Taux de change défavorable**, sinon convertir est toujours correct
- [ ] **Le transfert** (M) — une part des dégâts des alliés revient sur soi. Répare :
      l'aggro est impossible sur une rencontre à PV partagés. Borné en pourcentage **et** en
      durée. Ally-side, donc il se multiplie (§9 quinquies)
- [ ] **La charge** (M) — `generates` / `consumes` sur `Spell`, un compteur par rencontre.
      Répare : la mêlée n'a aucune raison d'aimer les longs combats. **Meurt avec la
      rencontre**
- [ ] **Le différé** (M) — une file d'effets résolus en **tours de rencontre**. Répare :
      l'asynchronie n'est jamais un avantage. Seule forme qui l'exploite au lieu de la subir
- [ ] **L'ouverture** (M) — un geste posé depuis l'écran de zone, appliqué à la rencontre
      suivante. Répare : `tempo` n'a aucun effet modélisé. Coûte de l'**énergie d'action**,
      jamais un tour
- [ ] **Le familier** (M) — **arbitrage rendu (§13.3) : c'est un dépôt offensif, pas un
      acteur.** Retirez le ciblage et il ne reste qu'une chose qui frappe à chaque tour
      pendant une durée : le critère d'admission du §13.1 impose donc de le traiter comme
      tel. On garde ce qui comptait — **il agit sur les tours où son invocateur est
      absent** — et la fiction entière ; on perd le ciblage, on économise un acteur, une IA
      et une cible. Mesuré : +2 % sur un commun, **+9 % sur une élite**, rendement ×2,4 le
      tour investi — il ne sert que sur les longues rencontres, comme *la charge*
- [ ] **Sa valeur totale est fixée à ~1 tour d'attaque par invocation** (correction 21).
      La première calibration — 40 % du geste sur 6 tours, soit ×2,4 le tour investi —
      **était cassée en groupe** : le familier agit sur les tours de **la rencontre** quand
      son invocateur n'a que **les siens**, soit un taux de change de 4 pour 1. Mesuré,
      l'invocateur contribuait **+87 %** avec quatre invocations, et plus il invoquait plus
      il gagnait
- [ ] **Règle générale à verrouiller** : *un dépôt **offensif** ne dépasse jamais un tour
      d'attaque par tour investi ; un dépôt **défensif** peut valoir davantage, parce que la
      barre de vie de sa cible l'écrête toute seule.* C'est ce qui autorise ×8,8 pour un soin
      de groupe et interdit ×2,4 pour des dégâts
- [ ] Résultat visé : **à l'équilibre quand le joueur est présent** (solo comme en groupe),
      **+56 % sur six tours d'absence** — le familier ne vaut rien quand on joue et tout
      quand on ne joue pas. Le geste devient une décision : *je pose mon familier avant de
      fermer l'onglet*
- [ ] Garde-fous du familier : meurt avec la rencontre · un seul à la fois · les passifs de
      l'arbre qualifient ses gestes (la double borne s'applique) · il ne mitige rien, ne
      protège personne, n'encaisse pas — **un invocateur en tissu reste aussi fragile qu'un
      mage**
- [ ] Les **sept refus** du §13.3 verrouillés par test là où c'est possible : aucune table de
      menace, aucun rôle nécessaire, aucun geste sans lecture `scope: soi`, aucun changement
      d'arme en combat, aucune ressource persistant entre deux rencontres, aucun tour
      supplémentaire, aucune montée en puissance entre les combats

### ARC-19 — L'aggro bornée (M | ★★★ | MOYENNE)
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
| **L'aggro rend un rôle obligatoire** | Elle est **bornée à 50 %** et portée par un **geste**, jamais par une table : sans tank, chacun encaisse la sienne et le groupe passe. Un test l'exige (ARC-19) |
| **L'entretien casse l'équilibre solo** s'il ne paie rien | Le curseur de régénération des PM (ARC-17) est ce qui le borne. Sans lui, mesuré : 14 minutes d'attente par jour contre 99 à 142 pour les autres — il joue trois fois plus de contenu pour la même énergie d'action |
| **L'assaut n'a pas de raison d'exister** tant que la vitesse ne vaut rien | Les rencontres à fenêtre (ARC-17). Une chasse coûte 5 points d'énergie quel que soit le nombre de tours : sans contenu à fenêtre, tuer vite ne rapporte rien |
| La **suppression du plafond** ouvre la porte au personnage qui a tout appris | C'est le contrat des trois couches, et les conditions d'équipement le resserrent : on ne porte pas à la fois la plaque, le cuir, le bouclier, la dague et l'arc |
