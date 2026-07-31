# Plan — Archétypes de combat et équilibrage des arbres

> **Numérotation :** jalons préfixés **ARC-** (Archetypes). Pas de conflit avec les autres
> préfixes.

> Décline [../GAME_ARCHETYPES.md](../GAME_ARCHETYPES.md) (proposition instruite du
> 2026-07-31 ; **§9 bis** à **§9 quinquies** en déroulent **quatre** exemples complets — un
> soldat du jour 3 au mois 3, un guérisseur seul puis en donjon, un archer sur une journée
> de jeu, un hydromancien mesuré en tours volés — qui ont produit **treize** des corrections
> listées ci-dessous, dont **aucune** ne portait sur un pourcentage) : les trois axes d'un domaine, la ressource par registre, le geste d'arme
> comme matéria, l'intention et la portée du geste, les marques élémentaires, le
> vocabulaire fermé des leviers et de leurs conditions d'équipement, le budget de
> puissance avec la fourche et le pacte, la loi du dépôt, les accointances, le gabarit et
> les six tests.
>
> **Ce plan ne réécrit pas la doctrine des arbres** — GAME_DOMAINS reste la loi
> (trois couches, double borne, équipement-build, 15 nœuds). Il lui donne ce qui lui
> manque pour que deux arbres ne se ressemblent pas : un axe, un vocabulaire, un budget.

## Vue d'ensemble

**16 jalons** (**ARC-01** à **ARC-16**) en 3 pistes.

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
| ARC-09 | Tests du plan (les 30 invariants) | S | ‖ |
| ARC-10 | Le plafond global de points — **tranché : suppression** | S | ∅ |
| ARC-11 | L'intention et la portée du geste, et la loi du dépôt | M | ← ARC-02 |
| ARC-12 | Les passifs conditionnels d'équipement | M | ← ARC-03 |
| ARC-13 | Les huit marques élémentaires | M | ← ARC-11 |
| ARC-14 | La fourche : une branche exclusive par arbre de combat | S | ← ARC-07 |
| ARC-15 | Le pacte : un malus rend du budget | S | ← ARC-03 |
| ARC-16 | Les accointances : la synergie donne de la souplesse, pas de la puissance | M | ← ARC-12 |

```
Piste A — Le modèle   : ARC-01 → ARC-03 → ARC-12 → ARC-16 ; ARC-03 → ARC-15
                        ARC-02 → ARC-04 ; ARC-02 → ARC-11 → ARC-13
Piste B — L'échelle   : ARC-05 ‖ ARC-06 ‖ ARC-10
Piste C — Le contenu  : ARC-07 → ARC-08 ; ARC-07 → ARC-14 ; ARC-09 ‖
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

### ARC-01 — La fonction, troisième axe (S | ★★★ | CRITIQUE)
> GAME_ARCHETYPES §1 et §5. Trois arbres d'eau × sorts occupent la même case : rien, dans
> le modèle, ne dit en quoi ils diffèrent.
- [ ] `DomainRole` (assaut / contrôle / entretien / encaisse) + `Domain::role`, avec
      migration ; les 24 domaines de combat rangés selon la grille du §10
- [ ] Les **palettes** en configuration (`config/game/domain_roles.yaml`) : **cinq leviers**
      par fonction dont un **principal exclusif** (`power`/`grip`/`mending`/`guard`), plus la
      **palette d'intentions** (§5.1) ; la règle des 80/20 exprimée en données, pas en code
- [ ] Plafonds des principaux à **20** (`power`, `mending`, `grip`), `guard` à **15**
      (correction du §9 quinquies : à 18, le capstone consommant 14 pb, un arbre de contrôle
      ne pouvait acheter son levier principal nulle part ailleurs qu'à son sommet)
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
- [ ] **Munitions** : consommation par geste de registre distance, avec les **cinq**
      garde-fous du §2 — flèche de base au **plancher T1 PNJ** et **pleinement jouable**,
      la munition élémentaire **remplace** l'élément du geste sans le donner, récupération
      par `wind`, **prime fixe et plafonnée** pour une meilleure munition, et **capacité de
      carquois** avec un prix calibré sur le revenu du jour
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
> ‖ au fil des jalons. Les 30 invariants de GAME_ARCHETYPES §12.
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
