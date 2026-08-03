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
| ARC-02 ◐ | Le registre du geste + premières matéria de technique | M → 2 sous-phases | ← MAT-01, MAT-03 |
| ARC-03 | Les leviers : les passifs deviennent des pourcentages bornés | **L** | ← ARC-01 |
| ARC-04 | Les ressources par registre (munitions, temps de reprise) | M | ← ARC-02 |
| ARC-05 | L'ancre d'échelle : la durée d'un combat en tours | **L** | ← BES-01 |
| ARC-06 | L'échelle de coût des arbres, et le gain de points indexé au palier | M | ← BES-01 |
| ARC-07 | Les quatre arbres patrons, écrits au gabarit | M | ← ARC-03, 04, 06 |
| ARC-08 | Conversion mécanique des 20 autres arbres | M | ← ARC-03, ARC-07 |
| ARC-09 | Tests du plan (les 45 invariants) | S | ‖ |
| ARC-10 ✅ | Le plafond global de points — **tranché : suppression** | S | ∅ |
| ARC-11 | L'intention et la portée du geste, et la loi du dépôt | M | ← ARC-02 |
| ARC-12 | Les passifs conditionnels d'équipement | M | ← ARC-03 |
| ARC-13 | Les huit marques élémentaires | M | ← ARC-11 |
| ARC-14 | La fourche : une branche exclusive par arbre de combat | S | ← ARC-07 |
| ARC-15 | Le pacte : un malus rend du budget | S | ← ARC-03 |
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

### ARC-02 — Le registre du geste, et les matéria de technique (M → 2 sous-phases | ★★★ | CRITIQUE) ◐

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
> **ARC-02b — reste à faire** : le lot de techniques (les 5 accords des arbres
> Soldat et Archer du §9, dérivés selon GAME_MATERIA §2.1), le typage
> plaque → `technique` / cuir → mixte, et le retournement du test de DOM-03
> (`testNoPieceDeclaresASocketNothingCanFill` : il interdit aujourd'hui une
> pièce typée `technique`, il vérifiera qu'aucun emplacement n'est un mur sans
> porte). Plus les deux invariants qui en dépendent — toute matéria a un
> registre, tout arbre ouvre au moins un geste du sien.
> GAME_ARCHETYPES §3. **Le prérequis dont deux archétypes sur quatre dépendent** : sans
> technique, un arbre de mêlée ou de distance ne qualifie aucune action.
- [x] `Spell::register` (sorts / mêlée / distance), hérité par la matéria comme l'élément *(ARC-02a)*
- [ ] Un premier lot de **matéria de technique** — les 5 accords des arbres Soldat et
      Archer du §9, dérivés selon la grille de GAME_MATERIA §2.1
- [ ] Lever le report de **DOM-03** : plaque → `technique`, cuir → mixte. Le test qui
      interdit aujourd'hui une pièce typée `technique` change de sens : il vérifie
      désormais qu'aucun emplacement n'est un mur sans porte (il existe au moins une
      matéria sertissable pour chaque type d'emplacement livré)
- [ ] La règle 9 reste intacte : l'arbre accorde, il ne donne pas — un test le verrouille
      sur le nouveau chemin
- [ ] Tests : toute matéria a un registre ; tout arbre ouvre au moins un geste de son
      registre (invariant 7)

### ARC-03 — Les leviers (L | ★★★ | CRITIQUE)
> GAME_ARCHETYPES §4. Le refactor central : cinq entiers plats → **quinze** leviers en
> pourcentage, avec taux de change et plafonds.
- [ ] `CombatLever` (15 valeurs, dont `dodge` et `recovery`) + `Skill::levers` : une liste
      `(levier, points de budget, condition ?)` remplaçant `damage`/`heal`/`hit`/`critical`/`life`
- [ ] **Une place et une seule par levier dans la formule** — `DamageCalculator`,
      `CriticalCalculator`, `FightCalculator` (le jet de touche), `StatusEffectManager` consomment chacun
      les leviers qui les concernent, et le taux de change vit dans **un seul** convertisseur
- [ ] `thrift` et `wind` se convertissent selon la **ressource du registre** (§4, note 1)
- [ ] `life` et `recovery` restent hors de la double borne (décision DOM-01, inchangée),
      en pourcentage des PV de base
- [ ] **`dodge` avant tout calcul, `guard` après résistance** : deux places distinctes dans
      la formule — c'est ce qui distingue le cuir de la plaque autrement que par un chiffre
- [ ] Tests : plafond par levier ; somme = 50 pb par arbre ; règle des 80/20 ; aucun passif
      plat sur un nœud de domaine de combat

### ARC-04 — Les ressources par registre (M | ★★ | HAUTE)
> GAME_ARCHETYPES §2. Trois registres qui coûtent la même chose ne sont qu'un registre.
- [ ] **Le carquois, pas les munitions** (arbitrage rendu au §9 septies) : **aucun coût
      récurrent en gils**. Le carquois est une **pièce d'équipement durable** (charpentier),
      possédée en plusieurs exemplaires — un par élément —, exactement comme un mage possède
      plusieurs matéria. Il **se vide dans la rencontre et se ramasse après** : la ressource
      du registre distance devient **intra-rencontre**, comme les PM
- [ ] **La régénération des PM hors combat** — le curseur qui décide de tout l'équilibre
      solo, et qui n'existe pas (BALANCE §24.2, ouvert depuis 2026-07-29). Mesuré : sans
      lui le guérisseur paie **14 minutes** d'attente par jour quand les autres en paient
      99 à 142 ; avec un curseur calibré (~6 s/point contre 12 s/PV), les six builds se
      tiennent entre **99 et 179 minutes**. Symétrie à tenir : *les PV paient les coups
      reçus, les PM paient les gestes faits, et les deux se rechargent en temps réel*
- [ ] **L'élément vient de la matéria, la munition le remplace** (correction du §9 quater).
      Une première rédaction le faisait porter par la munition seule : une flèche ordinaire
      produisait alors une action **sans élément**, donc hors de la case du domaine, donc
      **sans aucun passif d'arbre**. Le filet de sécurité éteignait l'archétype
- [ ] **La prime de munition** : mesuré, sans elle l'archer paie **90 à 230 gils par jour**
      pour **+1,8 %** de dégâts face à un pyromancien qui ne paie rien. Prime fixe, indexée
      sur le palier, jamais cumulative — un choix d'allocation, pas un axe de progression
- [ ] **La capacité de carquois** : sans elle, `wind` — **18 % du budget d'un arbre** — rend
      **12 gils par jour**. Avec elle, 13,5 % d'un carquois de 20 valent 2,7 tirs de plus
      par rencontre, et la contrainte ne mord que sur les **longues** rencontres
- [ ] **Le prix se calibre contre le revenu quotidien**, jamais contre la valeur du geste :
      ~10 % du revenu du jour pour l'ordinaire, ~25 % un jour où l'archer choisit
      l'élémentaire. Repère actuel : un coffre d'exploration rend 2 à 12 gils (BALANCE §10)
- [ ] **Temps de reprise** : `Spell::cooldown` (déjà au modèle, sans consommateur) branché
      sur les techniques de mêlée
- [ ] Les PM restent la ressource des sorts, inchangés
- [ ] Croise ECO : les flèches en lot sont une branche du **charpentier** (DOM-06) — le
      débouché existe déjà, il n'y a pas de recette à inventer
- [ ] Tests : un archer sans munition élémentaire garde un geste jouable (jamais un mur) ;
      aucune technique de mêlée sans reprise au-delà du geste d'entrée

### ARC-05 — L'ancre d'échelle (L | ★★★ | HAUTE)
> GAME_ARCHETYPES §6.4. Les gestes valent 1 à 12 points ; les monstres ont 11 à 3 200 PV.
> Des pourcentages posés sur des nombres qui n'ont pas de rapport entre eux ne veulent rien
> dire.
- [ ] Fixer la **durée cible en tours** (commun 3-5, élite 6-10, boss 12-20) et en dériver
      les valeurs, plutôt que l'inverse
- [ ] **La seconde ancre** (correction du §9 ter) : le **coût d'une rencontre en ressource,
      rapporté au budget du jour**. Mesuré, un Soldat et un Guérisseur tiennent onze tours
      tous les deux et sortent avec une barre comparable — mais l'un n'a rien dépensé et
      l'autre a vidé 108 PM sur 120. Sur les ~16 combats d'une journée, ils n'ont rien à
      voir. C'est cette ancre qui donne leur sens à `thrift` et `wind` : ils agrandissent
      une **journée**, pas un combat
- [ ] Calibrer en consequence la **régénération des PM hors combat** — chantier deja ouvert
      en BALANCE §24.2, et qui n'avait pas d'archetype a servir
- [ ] Règle unique : *un geste de palier n retire ~25 % des PV d'un adversaire commun de
      palier n*
- [ ] Recalibrage conjoint des PV de monstre (croise **BES-01**, le gabarit `tier × rank`)
      et des valeurs de gestes
- [ ] Tests : un **simulateur de combat** en test — la durée moyenne par palier, pas un
      tableau de valeurs relu à la main. C'est la seule forme de test qui attrape une
      régression d'équilibrage
- [ ] **Il doit produire la table croisée du §9 sexies**, pas des durées isolées : c'est
      la comparaison des six builds qui a revele le desequilibre, aucun exercice individuel
      ne pouvait le voir
- [ ] **L'ancre de fonction** (§9 sexies.3) : a arbre complet et equipement egal, les quatre
      fonctions enchainent le meme nombre de rencontres par jour et en sortent dans un etat
      comparable. Ce qui diffère, c'est **comment on paie**
- [ ] **Le palier des accords suit la fonction** : assaut au palier plein, controle /
      entretien / encaisse un palier en dessous (~ −25 %). Mesuré : ramène l'ecart de
      « 9 tours contre 11 » à « 7 tours contre 11-14 », sans qu'aucun levier ne bouge
- [ ] **Trancher la valeur de la vitesse** (§9 sexies.4) — une chasse coute 5 points
      d'energie quel que soit le nombre de tours, donc tuer vite ne rapporte **rien** en
      solo. Option recommandee : les **rencontres à fenêtre** (un boss se termine en 12-20
      tours ou pas du tout). Consequence a porter dans GAME_DUNGEONS et GAME_BESTIARY : un
      boss doit avoir assez de PV pour qu'un archetype lent n'en vienne pas a bout
- [ ] Consigner le résultat dans [../BALANCE.md](../BALANCE.md) (§4 est aujourd'hui
      aspirationnel : les fixtures ne le suivent pas)

### ARC-06 — L'échelle de coût et le gain de points (M | ★★ | HAUTE)
> GAME_ARCHETYPES §6.2. Un arbre coûte 465 points pour un plafond global de 500.
- [ ] Échelle **0 / 10 / 25 / 50 / 100** (+ 150 dormant) sur les 24 arbres de combat
- [ ] **Le gain de points suit le palier de l'adversaire** : T1 0,25 · T2 0,5 · T3 1 ·
      T4 2 (paliers de GAME_BESTIARY). On ne monte pas un arbre en tapant des rats
- [ ] Vérifier le calendrier visé (entrée jour 1 · palier 1 semaine 1 · palier 2
      semaines 3-4 · palier 3 semaines 6-8 · capstone mois 3) contre le budget d'énergie
      réel de GAME_PROGRESSION §5
- [ ] Tests : coût de chaque nœud dans l'échelle ; coût total d'un arbre = 390 points

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

### ARC-11 — L'intention, la portée, et la loi du dépôt (M | ★★★ | HAUTE)
> GAME_ARCHETYPES §3.1 et §7 bis. **Ce jalon décide si le donjon de groupe a un sens.**
> Le combat de groupe est semi-synchrone (`GroupDungeonCombatService` : un joueur actif à
> la fois, 45 s par tour, tour d'un absent résolu tout seul) — un soin **réactif** y est
> une mécanique morte.
- [ ] `SpellIntent` (dégât / soin / protection / amélioration / entrave) et `SpellScope`
      (soi / un allié / le groupe / une cible / plusieurs cibles) sur `Spell`, hérités par
      la matéria
- [ ] Les leviers visent par **intention** : `mending` ne touche que `soin`, `grip` que
      l'`entrave`. Une fois sur le geste, jamais quinze fois dans quinze formules
- [ ] **Les gestes déposés** : un effet de portée `le groupe` pose une **durée** sur les
      alliés — régénération, absorption, amélioration — et elle court **que le lanceur soit
      connecté ou non**
- [ ] **L'asymétrie du donjon semi-synchrone**, à écrire noir sur blanc (correction du
      §9 quinquies) : un effet posé sur les **alliés** se multiplie par leur nombre (×8,8 à
      quatre), un effet posé sur l'**ennemi** ne se multiplie pas (×0,9) — un seul joueur
      agit par tour. Entretien et encaisse gagnent au groupe ; assaut et contrôle n'y
      gagnent rien. Ne pas équilibrer le contrôle comme un soutien
- [ ] **Toute `protection` porte une durée**, quelle que soit sa portée (correction du
      §9 bis) : une garde qui ne couvre que le tour où elle est jouée punit l'encaisse de
      se défendre — il perd en dégâts exactement ce qu'il gagne en survie
- [ ] **La durée se compte en tours de la rencontre**, jamais en temps réel ni en tours du
      lanceur : c'est le seul compteur que l'asynchronie ne dérègle pas
- [ ] **La durée étale la valeur, elle ne l'augmente pas** (correction du §9 ter) : la
      valeur totale d'un dépôt est fixée par le palier de la matéria. Mesuré, un dépôt de
      10 tours sur quatre alliés vaut **14,7 tours d'attaque** — à ce prix, un groupe sans
      entretien devient non viable, ce que le garde-fou interdit
- [ ] Le même geste en `scope: soi` doit rester jouable en solo — un archétype, pas deux
- [ ] Le garde-fou : **aucun rôle n'est nécessaire**. Un groupe sans entretien met plus de
      tours et perd plus de PV ; il ne rencontre pas un mur. Exiger un rôle, c'est exiger
      une présence
- [ ] Tests : aucun geste `le groupe` instantané ; palette d'intentions tenue par arbre ;
      tout arbre ouvre au moins un `dégât` et au moins un non-`dégât`

### ARC-12 — Les passifs conditionnels d'équipement (M | ★★★ | HAUTE)
> GAME_ARCHETYPES §4.3. C'est ce qui fait que **l'équipement est le build** au lieu d'être
> un total — la promesse de GAME_DOMAINS §3, qui n'avait jamais eu de quoi la tenir.
- [ ] `SkillCondition` sur un nœud passif : famille d'arme, ligne d'armure, bouclier porté,
      main gauche libre, deux armes — plus les conditions de combat déjà utilisées par les
      capstones
- [ ] **Multiplicateurs d'effet** : ×1,0 sans condition, **×1,4** condition de build,
      **×2,0** condition de combat. Le budget compte l'effet **moyen**, pas l'effet affiché ;
      les plafonds restent exprimés en points de budget et ne bougent pas
- [ ] **Le multiplicateur suit la fréquence mesurée, pas la famille** (correction du
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

### ARC-13 — Les huit marques élémentaires (M | ★★★ | HAUTE)
> GAME_ARCHETYPES §1.1. **Trois pièces déjà écrites du système en dépendent** : le
> capstone d'assaut (« contre une cible qui porte votre marque »), le levier `grip`
> (« les statuts appliqués ») et la palette de contrôle (deux accords d'`entrave`).
> Sans les marques, aucune des trois n'a d'objet.
- [ ] Une marque par élément — Brûlure, Trempé, Déséquilibre, Alourdi, Entaille,
      Traqué, Révélé, Aveuglé — déclarées comme `StatusEffect` et rattachées à `Element`
- [ ] **Aucune entrave à un tour** (correction du §9 quinquies) : durée ≥ 2 tours, ou marque
      portée par un geste de dégât. Démonstration : en duel, échanger un de ses tours contre
      un tour adverse laisse les dégâts subis **rigoureusement identiques** (101 dans les
      quatre cas mesurés) — une entrave d'un tour est un nœud mort
- [ ] **Côté monstre aussi** (correction du §9 ter) : mesuré, **21 monstres sur 65** ont un
      sort et **9 de ces sorts** appliquent un statut. `ward` figure dans deux palettes sur
      quatre et l'accord de dissipation du Guérisseur n'a rien à dissiper — une marque qui
      n'existe que dans un sens est un levier mort pour la moitié des fonctions. Croise
      **BES-01** (l'élément des monstres, prérequis de MAT-01)
- [ ] **Un des deux accords d'entrée de chaque arbre l'applique** : c'est ce qui rend le
      capstone atteignable au jour 1
- [ ] La marque **se rafraîchit, elle ne se cumule pas** avec elle-même ; deux marques
      différentes coexistent sans règle spéciale
- [ ] Les 15 statuts livrés se rangent : ceux qui deviennent des marques, ceux qui restent
      des effets ordinaires (poison, régénération, bouclier…)
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

### ARC-15 — Le pacte (S | ★★ | MOYENNE)
> GAME_ARCHETYPES §6.5. La seule mécanique du document qui rende un personnage
> **mesurablement plus faible** quelque part — sans elle, tous les builds sont des
> additions.
- [ ] Un nœud peut porter un **malus** ; sa valeur au taux de change s'ajoute au budget du
      nœud. Un arbre porte jusqu'à 60 pb de bonus et 10 pb de malus, somme inchangée
- [ ] Les **six règles** verrouillées par test : un seul pacte par arbre ; jamais au
      palier 1 ; malus **hors palette** ; permanent, inconditionnel, une seule stat ; nœud
      **feuille** (aucun nœud ne l'exige) ; plafonds par levier toujours tenus
- [ ] UI : le malus affiché **avant** l'apprentissage, et le net après (§8 bis)
- [ ] Tests : les six règles ; et qu'aucun pacte ne permette de dépasser un plafond de levier

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
