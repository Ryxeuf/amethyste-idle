# Plan — Archétypes de combat et équilibrage des arbres

> **Numérotation :** jalons préfixés **ARC-** (Archetypes). Pas de conflit avec les autres
> préfixes.

> Décline [../GAME_ARCHETYPES.md](../GAME_ARCHETYPES.md) (proposition instruite du
> 2026-07-31) : les trois axes d'un domaine, la ressource par registre, le geste d'arme
> comme matéria, l'intention et la portée du geste, le vocabulaire fermé des leviers et
> leurs conditions d'équipement, le budget de puissance, la loi du dépôt, le gabarit et
> les six tests.
>
> **Ce plan ne réécrit pas la doctrine des arbres** — GAME_DOMAINS reste la loi
> (trois couches, double borne, équipement-build, 15 nœuds). Il lui donne ce qui lui
> manque pour que deux arbres ne se ressemblent pas : un axe, un vocabulaire, un budget.

## Vue d'ensemble

**12 jalons** (**ARC-01** à **ARC-12**) en 3 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| ARC-01 | La fonction, troisième axe du domaine (`Domain::role` + palettes) | S | ∅ |
| ARC-02 | Le registre du geste + premières matéria de technique | M | ← MAT-01, MAT-03 |
| ARC-03 | Les leviers : les passifs deviennent des pourcentages bornés | **L** | ← ARC-01 |
| ARC-04 | Les ressources par registre (munitions, temps de reprise) | M | ← ARC-02 |
| ARC-05 | L'ancre d'échelle : la durée d'un combat en tours | **L** | ← BES-01 |
| ARC-06 | L'échelle de coût des arbres, et le gain de points indexé au palier | M | ← BES-01 |
| ARC-07 | Les quatre arbres patrons, écrits au gabarit | M | ← ARC-03, 04, 06 |
| ARC-08 | Conversion mécanique des 20 autres arbres | M | ← ARC-03, ARC-07 |
| ARC-09 | Tests du plan (les 16 invariants) | S | ‖ |
| ARC-10 | Le plafond global de points — **tranché : suppression** | S | ∅ |
| ARC-11 | L'intention et la portée du geste, et la loi du dépôt | M | ← ARC-02 |
| ARC-12 | Les passifs conditionnels d'équipement | M | ← ARC-03 |

```
Piste A — Le modèle   : ARC-01 → ARC-03 → ARC-12 ; ARC-02 → ARC-04 ; ARC-02 → ARC-11
Piste B — L'échelle   : ARC-05 ‖ ARC-06 ‖ ARC-10
Piste C — Le contenu  : ARC-07 → ARC-08 ; ARC-09 ‖
```

**Quand.** ARC-02 se livre **avec** le chantier matéria (PLAN_MATERIA) : c'est la même
migration d'objet, et la scinder ferait deux passes sur les mêmes fixtures. ARC-05 croise
la revue du bestiaire (PLAN_BESTIARY) — la durée d'un combat est un contrat entre les PV
d'un monstre et la valeur d'un geste, on ne peut pas la fixer d'un seul côté.

---

### ARC-01 — La fonction, troisième axe (S | ★★★ | CRITIQUE)
> GAME_ARCHETYPES §1 et §5. Trois arbres d'eau × sorts occupent la même case : rien, dans
> le modèle, ne dit en quoi ils diffèrent.
- [ ] `DomainRole` (assaut / contrôle / entretien / encaisse) + `Domain::role`, avec
      migration ; les 24 domaines de combat rangés selon la grille du §10
- [ ] Les **palettes** en configuration (`config/game/domain_roles.yaml`) : **cinq leviers**
      par fonction dont un **principal exclusif** (`power`/`grip`/`mending`/`guard`), plus la
      **palette d'intentions** (§5.1) ; la règle des 80/20 exprimée en données, pas en code
- [ ] Aucun affichage joueur : la fonction est une contrainte d'auteur, pas une classe
- [ ] Tests : aucun triplet (élément, registre, fonction) en double ; tout domaine de
      combat a une fonction ; toute palette a cinq leviers ; deux palettes ne partagent
      jamais un levier principal, ni plus de deux secondaires

### ARC-02 — Le registre du geste, et les matéria de technique (M | ★★★ | CRITIQUE)
> GAME_ARCHETYPES §3. **Le prérequis dont deux archétypes sur quatre dépendent** : sans
> technique, un arbre de mêlée ou de distance ne qualifie aucune action.
- [ ] `Spell::register` (sorts / mêlée / distance), hérité par la matéria comme l'élément
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
      `CriticalCalculator`, `HitChanceCalculator`, `StatusEffectManager` consomment chacun
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
- [ ] **Munitions** : consommation par geste de registre distance, avec les trois
      garde-fous — flèche de base au **plancher T1 PNJ**, seules les munitions
      élémentaires et hautes se consomment vraiment, récupération par le levier `wind`
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
- [ ] Règle unique : *un geste de palier n retire ~25 % des PV d'un adversaire commun de
      palier n*
- [ ] Recalibrage conjoint des PV de monstre (croise **BES-01**, le gabarit `tier × rank`)
      et des valeurs de gestes
- [ ] Tests : un **simulateur de combat** en test — la durée moyenne par palier, pas un
      tableau de valeurs relu à la main. C'est la seule forme de test qui attrape une
      régression d'équilibrage
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
> ‖ au fil des jalons. Les 16 invariants de GAME_ARCHETYPES §12.
- [ ] Budget (50 pb), plafonds par levier, règle des 80/20
- [ ] Grille : une fonction par domaine, aucun triplet en double
- [ ] Gabarit : 15 nœuds, échelle de coût, 2 entrées à 0 point **qui sont des accords**
- [ ] Capstone : unique, conditionnel, 14 pb, condition atteignable au tour 2
- [ ] Registre : tout arbre ouvre au moins un geste de son registre
- [ ] Intentions : palette tenue, un `dégât` et un non-`dégât` par arbre, un geste de
      portée collective pour l'entretien et l'encaisse
- [ ] Dépôt : aucun geste de portée `le groupe` instantané ; durée en tours de rencontre
- [ ] Conditions : les cinq garde-fous du §4.3 ; aucun plafond global de points
- [ ] Règle 9 étendue au chemin des techniques ; aucun passif plat restant

### ARC-10 — Le plafond global de points (S | ★★ | MOYENNE)
> GAME_ARCHETYPES §11.2. `MAX_TOTAL_SKILL_POINTS = 500` contredit « le savoir n'est jamais
> borné » (GAME_DOMAINS §1) — et un seul arbre en consomme 465.
> **Tranché le 2026-07-31 : le plafond est supprimé.**
- [ ] Retirer `MAX_TOTAL_SKILL_POINTS`, le motif de refus `global_cap`, ses traductions
      FR/EN et le test qui l'exerce
- [ ] **Écrire dans GAME_DOMAINS §1 pourquoi** — sinon il reviendra : les trois bornes
      réelles sont l'énergie (rythme), le build (expression, DOM-02) et la spécialisation
      ou le patronage (identité). Un plafond de points ne borne que le temps de jeu, la
      seule chose que ce jeu a décidé de ne jamais punir
- [ ] Test : aucun refus d'acquisition ne dépend d'un total de points tous domaines
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
- [ ] **La durée se compte en tours de la rencontre**, jamais en temps réel ni en tours du
      lanceur : c'est le seul compteur que l'asynchronie ne dérègle pas
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
- [ ] Les **cinq garde-fous** verrouillés par test : aucune condition au palier 1 ; au moins
      2 des 7 passifs sans condition ; condition satisfaisable par ce que l'arbre débloque ;
      condition portée sur une **famille**, jamais sur une pièce nommée ni sur une rareté ;
      une condition ne ferme rien — elle récompense
- [ ] **UI** : l'écran des arbres dit ce qu'un nœud rapporterait *si la condition était
      remplie*, et ce qu'il faudrait porter. Un bonus silencieusement inactif est un bug
      d'interface, pas un choix de build
- [ ] Croise **OBJ** : les familles d'arme et lignes d'armure doivent être lisibles depuis
      l'objet (`EquipmentPortCatalog` les déclare déjà par famille)

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
| La **suppression du plafond** ouvre la porte au personnage qui a tout appris | C'est le contrat des trois couches, et les conditions d'équipement le resserrent : on ne porte pas à la fois la plaque, le cuir, le bouclier, la dague et l'arc |
