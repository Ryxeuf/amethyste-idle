# Plan — Arbres de domaine & équipement-build

> **Numérotation :** jalons préfixés **DOM-** (Domains). Pas de conflit avec les autres
> préfixes.

> Décline [../GAME_DOMAINS.md](../GAME_DOMAINS.md) (acté le 2026-07-28) : doctrine des
> trois couches, double borne élément × registre, équipement-build, gabarits, quatre
> arbres neufs. Le moteur est livré (`SkillAcquiring`, `SkillRespecManager`,
> `BuildPresetManager`, `CombatSkillResolver`) — ce plan **type des données et branche
> des bornes**, il ne réécrit pas de moteur.

## Vue d'ensemble

**8 jalons** (**DOM-01** à **DOM-08**) en 3 pistes — **plan d'origine complet 8/8 au
2026-07-29** —, plus **DOM-09**, ouvert par l'audit du 2026-07-29.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| DOM-01 ✅ | Passifs typés : élément × registre (refactor du format) | M | ∅ |
| DOM-02 ✅ | Activation par build (domaines actifs = sources portées) | M | ← DOM-01 |
| DOM-03 ✅ | Emplacements typés sur l'équipement (sort/technique/libre) | M | ∅ |
| DOM-04 ✅ | Spécialisation par arbre d'artisanat (migration) | S | ∅ |
| DOM-05 ✅ | Arbre du bûcheron | S | ← ZON-34 (le domaine) |
| DOM-06 ✅ | Arbres cuisinier, charpentier, tailleur | M → 1/arbre | ← ECO-29/30/31 (les domaines) |
| DOM-07 ✅ | Nœuds d'accord d'hybride dormants | S | ← DOM-01 |
| DOM-08 ✅ | Tests du plan | S | ‖ |
| DOM-09 | La borne sans fuite (audit 2026-07-29) | M | ← DOM-01, DOM-02 |
| DOM-10 | Les arbres retrouves (hors catalogue) | M | ← ONB-08 |

```
Piste A — Le système   : DOM-01 → DOM-02 ; DOM-03 ‖ ; DOM-04 ‖
Piste B — Les arbres   : DOM-05, DOM-06 (avec leurs jalons ZON/ECO)
Piste C — L'avenir     : DOM-07, DOM-08
```

**Quand.** Piste A est indépendante de tout chantier en cours et **précède** la Piste H
d'ECO (les arbres neufs se posent sur le format typé, pas sur l'ancien). DOM-05/06 se
livrent **avec** leurs jalons de domaine (ZON-34, ECO-29→31), pas avant.

---

### DOM-01 — Passifs typés : élément × registre ✅ (M | ★★★ | CRITIQUE)
> GAME_DOMAINS §2. Le refactor central : `damage`/`critical`/… plats deviennent des
> passifs bornés. « Critique +1 % » du pyromancien = sorts de feu uniquement.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] `CombatRegister` (sorts / mêlée / distance) + `Domain::register` : le domaine
      **est** la case élément × registre, et c'est lui qui porte la borne — les 130
      passifs livrés se typent d'un coup, sans une seule décision par nœud
- [x] `CombatScope` (élément × registre de l'action) et
      `CombatSkillResolver::getCombatBonuses($player, ?$scope)` — filtre effectif ;
      `FightSpellController` passe la portée du sort
- [x] Rétro-compat par une clause explicite : un nœud **sans domaine de combat**
      (récolte, artisanat, ou sans domaine) reste global. C'est elle qui permet de typer
      36 domaines sans relire 524 nœuds
- [x] Tests : 10 (`CombatSkillResolverScopeTest`) + 4 (`DomainRegisterTest`)

> **`life` échappe toujours à la borne, et c'est une décision.** Les points de vie maximum
> ne sont pas un geste : les borner ferait varier la barre de vie d'un tour à l'autre selon
> le sort choisi. Les quatre autres statistiques qualifient une action et se bornent avec
> elle.
>
> **Sans portée, rien n'est borné.** La fiche d'inventaire affiche un total, pas une action —
> `getCombatBonuses($player)` garde donc exactement le comportement d'avant. Montrer au
> joueur *ce qui s'applique vraiment* est le sujet de DOM-02 (l'écran de build).
>
> **Le registre de l'attaque de base n'a pas encore de consommateur, et ce n'est pas un
> oubli.** `FightAttackController::calculateDamage` vaut `3 + variance + enchantement` : il
> n'a **jamais** lu les passifs d'arbre. Leur y brancher une borne mêlée/distance aurait
> demandé d'inventer d'abord le registre de l'arme — ce que DOM-02 et DOM-03 posent. Les
> deux registres existent dans le modèle et sont testés ; leur porte d'entrée arrive avec
> celui à qui elle sert.
>
> **La grille 8 × 3 n'est pas pleine, et on ne l'a pas remplie de force.** Trois éléments
> (feu, air, bête) occupent leurs trois cases ; l'eau a trois domaines de sorts, le métal
> deux de mêlée. Étiqueter le « Guérisseur » en mêlée pour faire tenir la grille aurait
> menti sur ce qu'est le domaine. Le gain mécanique tient sans elle : un passif feu × mêlée
> ne sert plus un sort d'eau. Le remplissage est un sujet de contenu, pas de moteur.

### DOM-02 — Activation par build ✅ (M | ★★★ | CRITIQUE)
> GAME_DOMAINS §3. Un domaine n'est actif en combat que si le build porte une de ses
> sources. La borne est matérielle, jamais réglementaire.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] `BuildDomainResolver` : **une source par famille de registre** — une matéria de son
      élément pour une école de sort, une arme de son registre pour une école d'arme.
      Le registre de l'arme est celui de **son** domaine, déjà déclaré dans les fixtures
      (l'épée est au soldat, l'arc à l'archer)
- [x] Les deux bornes se cumulent dans `CombatSkillResolver` : le domaine doit convenir à
      l'action **et** être porté
- [x] Les **accords** ne passent jamais par là — `getUnlockedMateriaSpellSlugs` est
      inchangé, et un test l'exige nommément
- [x] UI : l'écran des arbres marque « Exprimé / Non exprimé » et **dit quoi porter**
- [x] Tests : 6 (`BuildDomainResolverTest`) + 5 (`CombatSkillResolverBuildTest`)

> **Deux sources, une par famille, et ce n'est pas une commodité.** Une école de sort
> s'exprime par la matière qu'on sertit, une école d'arme par l'arme qu'on tient. Exiger les
> deux de chacune aurait rendu le pyromancien dépendant d'un bâton qui n'existe pas dans son
> arbre, et le soldat d'une matéria de métal qui n'a rien à voir avec son épée.
>
> **L'épée de bois du débutant n'ouvre aucune école, et n'en ferme aucune.** Elle n'a pas de
> domaine : elle n'appartient à rien. Personne n'y perd — l'attaque de base ne lit pas les
> passifs (DOM-01), et la première matéria se sertit toujours (plancher jour 1).
>
> **Le changement de build hors combat était déjà tenu** : la borne se calcule à chaque
> action à partir de l'équipement porté, et l'équipement ne se change pas en combat. Ajouter
> un verrou dans `BuildPresetManager` aurait dupliqué une garantie que le modèle donne
> gratuitement.
>
> **La fiche d'inventaire n'est pas bornée non plus.** Elle montre ce qu'un joueur a
> *appris*, pas ce que sa tenue du moment exprime : l'y borner ferait baisser ses chiffres
> en rangeant une arme, sans que rien ne l'explique. C'est l'écran des arbres qui porte la
> distinction, et il la porte en toutes lettres.

### DOM-03 — Emplacements typés sur l'équipement ✅ (M | ★★★ | HAUTE)
> GAME_DOMAINS §3. La robe porte des emplacements de sort, la plaque des emplacements
> de technique. Donnée, pas moteur.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] `MateriaSlotType` (sort / technique / libre) + `Item::materiaSlotType` — **un type
      par pièce**, pas un par emplacement : les emplacements n'ont pas d'indice, et
      panacher aurait fait dépendre le sertissage de l'ordre des identifiants en base
- [x] Sertissage contrôlé dans `MateriaGearSetter`, avec une exception dédiée — et les
      **deux** chemins (écran et API v1) disent enfin *quoi*
- [x] Le genre d'une matéria est **dérivé** : celle qui accorde un sort est une matéria de
      sort. Le déclarer aurait permis de la contredire
- [x] La ligne tissu (ECO-31) porte ses emplacements de sort à partir du palier 2
- [x] Tests : 8 (`MateriaSlotTypeTest`) + 4 (`MateriaSlotTypingTest`)

> **Le `null` vaut « libre », et c'est ce qui rend le typage additif.** Les 121 pièces
> livrées se comportent exactement comme avant tant que personne ne les type — et le
> plancher jour 1 tient sans qu'on l'écrive pièce par pièce.
>
> **Le palier 1 du tissu reste libre, délibérément.** Le typer ne casserait rien
> *aujourd'hui* — toutes les matéria livrées sont des matéria de sort. C'est justement pour
> cela que le test existe : le jour où une matéria de technique arrivera, un débutant en
> robe de lin découvrirait qu'il ne peut pas la sertir, et rien n'aurait signalé la
> régression.
>
> **Aucune pièce n'est typée `technique`, et un test l'interdit.** Aucune matéria de
> technique n'existe : un tel emplacement occuperait une case du build en refusant tout ce
> qu'on peut lui présenter. Un mur sans porte est pire qu'un emplacement libre.
>
> **Le refus porte sur le sertissage, jamais sur le port** — c'est le premier garde-fou du
> §3, et le message le dit en toutes lettres (« la pièce reste portable »). Défaut trouvé au
> passage : l'API v1 n'avait aucun filet, et un refus métier y serait sorti en 500 muet.
>
> **Écarté : `materiaSlotConfig`.** Le champ JSON dormant aurait permis un type par
> emplacement, au prix d'un ordre de slots qui n'existe pas. Le canon parle de pièces, pas
> d'emplacements individuels.

### DOM-04 — Spécialisation par arbre ✅ (S | ★★ | HAUTE)
> GAME_DOMAINS §6. `Player.craftSpecialization` (singulier) → une spécialisation par
> arbre d'artisanat ; `Recipe.requiredSpecialization` la consomme déjà.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] `PlayerCraftSpecialization` — une ligne par `(player, craft)`, l'unicité portée par
      **le schéma** : un chemin de code qui l'oublierait ne pourrait pas la violer
- [x] `config/game/craft_branches.yaml` — 7 arbres × 2 branches, déclaratif. Le loader
      **refuse un arbre à moins de deux branches** : une branche unique n'est pas un choix
- [x] Respec de branche **payant** (2 500 gils, paramètre), le seul du jeu — le respec de
      points ordinaire reste doux
- [x] Migration de données : les joueurs déjà spécialisés reprennent leur métier et sa
      première branche ; la colonne héritée reste, le jeu ne la lit plus
- [x] Tests : 9 (`CraftBranchCatalogTest`) + 11 (`CraftSpecializationServiceTest`)

> **Le défaut corrigé n'était pas un manque : c'était une violation de la doctrine.** Le
> modèle livré fermait six arbres pour en ouvrir un — devenir Forgeron interdisait à jamais
> la maîtrise du Tanneur. C'est exactement l'exclusivité *entre* arbres que §1 refuse :
> « interdire un arbre serait interdire un geste ». Le renoncement se joue désormais **dans**
> l'arbre.
>
> **Le seuil se lit dans l'arbre concerné.** Il se lisait sur le meilleur des quatre : un
> joueur au seuil chez le forgeron pouvait se déclarer alchimiste sans avoir jamais touché
> un mortier.
>
> **Les trois métiers de la Piste H entrent dans l'enum.** Cuisinier, charpentier et
> tailleur avaient des arbres et des recettes, mais aucune façon de s'y spécialiser — le
> tailleur pouvait être le seul de la région à coudre des robes sans que rien ne le dise.
>
> **La migration impose une branche, et il n'y avait pas d'alternative** : l'ancienne valeur
> désignait un métier, pas une branche. Elle prend la première déclarée, et le respec existe
> précisément pour que ce choix imposé ne soit pas définitif. Le change reste un gain net.
> Un test verrouille la correspondance entre les sept valeurs de repli du SQL et le
> catalogue — la duplication est assumée, la divergence ne l'est pas.

### DOM-05 — Arbre du bûcheron ✅ (S | ★★ | HAUTE)
> Gabarit récolte (GAME_DOMAINS §5.2). Se livre **avec** ZON-34.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] L'arbre passe de 8 à **15 nœuds**, avec **deux entrées à 0 pt** : la matière, et
      l'outil qui la coupe
- [x] **La hache**, promise par ZON-34 et différée faute de métier à servir : type d'outil,
      quatre paliers (bronze → mithril), emplacement débloqué au nœud d'entrée, et deux
      étals qui la vendent
- [x] Repérage (3 nœuds), qualité (2), outils (3) — le rendement était déjà au gabarit
- [x] Tests : 8 (`LumberjackTreeTest`)

> **La promesse de ZON-34 est tenue mot pour mot.** Elle disait : « la hache demande un type
> d'outil, un bit d'équipement et un emplacement d'interface neufs — un changement de
> mécanisme, pas de données. Elle arrivera avec le charpentier, à qui elle sert. » Le
> charpentier existe depuis ECO-30, et les quatre essences ont un débouché.
>
> **La hache est exactement aussi utile que la pioche — ce qui mérite d'être dit.** Depuis le
> pivot, le chemin de récolte de zone (`GatherService`) ne demande **aucun** outil, à aucune
> profession : le contrôle vit dans `HarvestManager`, qui opère sur les `ObjectLayer` de la
> carte supprimée. La hache rejoint donc ses quatre sœurs au même niveau de réalité — déclarée,
> achetable, débloquée par l'arbre, et pas encore exigée. Rebrancher l'exigence est un
> changement de règle qui casserait tous les récolteurs actuels : hors de portée d'un jalon de
> données, et noté ici pour qu'on ne le redécouvre pas.
>
> **Pas de nœud de fatigue.** Le gabarit dit « rendement & fatigue » ; le vocabulaire d'actions
> n'a pas de fatigue (`yield`, `harvest`, `equip.tool`, `tool_slot.unlock`, `craft`). En
> inventer une aurait posé un paramètre que personne ne lit. Les quatre nœuds de rendement
> couvrent la part, et le plafond global reste respecté — un test vérifie que l'arbre ne
> sature pas à lui seul `MAX_BONUS_PERCENT`.

### DOM-06 — Arbres cuisinier, charpentier, tailleur ✅ (M | ★★★ | HAUTE)
> Gabarit artisanat (§5.3) + spécialisations terminales (§7). Se livrent **avec**
> ECO-29/30/31.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] Les trois arbres passent de 8 à **15 nœuds**, avec **deux entrées à 0 pt** chacun
- [x] Cuisinier : sens du feu, économie de garde-manger, conservation ; branches
      « table de fête / vivres de route »
- [x] Charpentier : fil d'équerre, chutes utiles, travail en lot, accord du canal ;
      branches « armes de trait / mobilier »
- [x] Tailleur : sertissage de tissu, doublure, lisière franche, teinture ; branches
      « robes de sort / tenues de travail »
- [x] **La branche devient une porte d'arbre** : `actions.specialization.branch` +
      un sixième motif de refus, `other_branch`, traduit FR/EN
- [x] Tests : 6 (`CraftTreeTemplateTest`) + 5 (`PlayerSkillBranchGateTest`)

> **C'est ici que DOM-04 et l'arbre se rencontrent.** Sans ce jalon, la spécialisation
> restait un bonus de qualité dont rien, dans ce que le joueur apprend, ne portait la
> trace — et les deux nœuds terminaux se seraient appris tous les deux, ce qui viderait
> le renoncement de son sens.
>
> **Le refus `other_branch` a une propriété que les cinq autres n'ont pas : il ne se lève
> pas en jouant.** Il se lève en *renonçant*, par le respec de branche, qui se paie. Le
> message le dit, sinon le joueur chercherait un prérequis qui n'existe pas.
>
> **Deux défauts muets trouvés en chemin.** `carpenter_true_grain` (30 pts) exigeait
> `carpenter_joinery` (50 pts), et `tailor_setting` (30) exigeait `tailor_needle` (50) : un
> nœud s'affichait accessible, le joueur avait les points, et l'apprentissage refusait sans
> dire pourquoi. Un test interdit désormais qu'un prérequis coûte plus cher que le nœud
> qu'il ouvre.
>
> **Deux entrées gratuites par arbre**, ce qui a rendu libres le manche du charpentier et la
> coupe de la toile du tailleur : tisser et tailler sont le même geste élémentaire, et faire
> payer le second faisait de l'entrée un couloir.

### DOM-07 — Nœuds d'hybride dormants ✅ (S | ★ | BASSE)
> GAME_DOMAINS §8. Une ligne de données par arbre de combat, inactive au lancement.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] `Skill::dormant` + migration + sixième motif de refus (`dormant`), traduit FR/EN
- [x] **24 accords réservés**, un par arbre de combat, **générés** depuis une table
      déclarative — le nœud est *le même* partout à l'élément près
- [x] Tests : couverts par `DomainPlanContractTest`

> **L'hybride n'est pas nommé, et c'est délibéré.** Le canon ne nomme que Magma et Inferno,
> pour le feu ; inventer les sept autres couples serait décider de la fusion avant qu'elle
> n'existe. Le nœud déclare son **élément parent** — la seule chose que la doctrine fixe — et
> l'enum `Element` n'accueillera ses composés qu'au jalon qui les rendra jouables.
>
> **Visible et refusé, plutôt qu'invisible.** Le cacher reviendrait à ne pas le poser ; un
> nœud qui apparaîtrait le jour d'une mise à jour se relirait comme un ajout, alors que c'est
> une porte qu'on savait là. Le refus passe **avant** les autres : un joueur qui a les points
> doit lire « pas encore ouvert », pas un motif qui lui ferait croire qu'il lui manque
> quelque chose.

### DOM-08 — Tests du plan ✅ (S | ★★ | HAUTE)
> ‖ au fil des jalons.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] Aucun sort actif par compétence — la garde de `CombatSkillResolver` étendue **côté
      données**, là où un nœud pourrait la rouvrir
- [x] Aucun passif non borné : tout nœud qui donne des statistiques appartient à un domaine
- [x] Le savoir jamais borné : l'acquisition ne lit **jamais** l'équipement
- [x] Plancher de sertissage T1 : les pièces d'entrée acceptent tout
- [x] L'accord d'hybride posé partout, ouvert nulle part — et jamais sur un métier
- [x] Tests : 6 (`DomainPlanContractTest`)

> **Un test de contrat n'a pas le droit d'être vacuous.** La première écriture cherchait un
> slug littéral d'accord dans les fixtures — ils sont générés, donc elle ne trouvait rien et
> passait en vérifiant le vide. C'est exactement la famille de défaut que ce fichier existe
> pour traquer : il lit désormais **le corps de la méthode qui porte la table**.

### DOM-09 — La borne sans fuite (M | ★★★ | HAUTE) ✅
> **Constat (audit 2026-07-29).** La double borne est livrée, mais quatre fuites lui
> échappent — trois dans les données, une dans le canon.
- [x] **Les 8 nœuds partagés** (`getSharedSkills()`, SkillFixtures ~l.5904-5973) portent
      des stats de combat (heal/life/critical/hit) via des domaines de **métier** → ils
      tombent dans la clause de rétro-compat de `CombatSkillResolver::skillAppliesTo()`
      et s'appliquent à **toute** action de combat, hors de la double borne. Les borner
      (ou acter qu'ils sont globaux) + durcir le test de contrat :
      `DomainPlanContractTest::testEverySkillWithCombatStatsBelongsToADomain` vérifie
      « a un domaine », pas « est borné »
- [◐] **Étendre les nœuds partagés** au bûcheron et aux 3 métiers de la Piste H
      (cuisinier/charpentier/tailleur), aujourd'hui hors synergies *(reporté à MET-03 :
      **on n'étend pas ce qu'on vient de fermer**. Les nœuds partagés ne portaient que des
      statistiques de combat ; les étendre à quatre métiers de plus aurait élargi la fuite
      au lieu de la refermer. MET-03 écrit le vocabulaire des neuf leviers de métier —
      l'extension s'y fera pour les douze arbres à la fois)*
- [x] **Données** : `t1/t2/t3_staff` rattachés au domaine paladin (light × melee) — un
      bâton, censé canaliser les sorts (GAME_DOMAINS §3), active la famille **mêlée**
      dans `BuildDomainResolver::carriedRegisters()`. À rattacher à un domaine de sorts
- [x] **Arbitrage Element** : `element: 'wood'` est porté par lumberjack et carpenter
      sans cas dans l'enum `Element` (inoffensif car hors combat, mais incohérent) ;
      GAME_DOMAINS §8 affirme que les composés sont « déjà actés » dans l'enum — **faux**.
      Trancher : ajouter les cas (wood + composés dormants) ou corriger le canon

> **DOM-09 — livré le 2026-08-19. La fuite était sept fois plus large que l'audit ne le
> disait.** L'audit avait nommé **8 nœuds partagés** ; la mesure en trouve **55**, sur les
> douze arbres de métier. Tous portaient des statistiques de combat, et
> `CombatSkillResolver::skillAppliesTo()` traite un nœud dont *aucun* domaine n'est un
> domaine de combat comme **non borné** — donc applicable à toute action, hors de la double
> borne, hors des 50 points de budget, hors des plafonds par levier. *Un système qui compte
> soigneusement 50 points et laisse une porte de service à +10 ne compte rien* : la phrase
> d'ARC-16a, et le même défaut sous un autre nom.
>
> **Le test disait « a un domaine », pas « est borné »** — et c'est exactement par là que la
> fuite passait : un nœud rattaché à quatre métiers le satisfaisait pleinement. La question
> juste se lit sur la base, jamais sur un texte source (`DomainBoundContractTest`).
>
> **Ce que la fermeture laisse derrière elle, et qui est nommé** : 24 nœuds vidés restent
> des **portes** (ils ouvrent la suite — *une porte n'est jamais une récompense*, la règle
> des échelons de port), et **9 sont des péages vides**, listés en cliquet. Ils attendent
> MET-03, seul jalon où un nœud d'artisanat peut porter un effet sans rouvrir la fuite.
>
> **Le bâton cesse d'activer la mêlée.** Le registre d'une arme se lisait sur
> `Item::domain->getRegister()`, c'est-à-dire sur un couple `élément × registre` : le bâton
> étant rattaché au paladin (lumière × **mêlée**), *porter un bâton faisait parler les
> passifs de corps à corps*. C'est le défaut que l'échelle de port avait corrigé du côté de
> l'apprentissage, resté ouvert du côté de l'objet — et la réponse est la même : ***c'est
> l'arme qui fixe le registre***, donc il se déclare sur la **famille**, une fois, et le
> loader refuse une famille d'arme sans registre.
>
> **L'arbitrage `wood` est rendu, et il est mesuré** : `Element::cases()` est parcouru par
> le butin de matéria, par les huit marques et par la loi de nommage. Un neuvième cas
> produirait une matéria de bois, une marque de bois et une règle de nommage pour un élément
> qu'aucun domaine de combat ne porte — la neuvième case que le §9 quater interdit. `wood`
> reste la **teinte** de deux métiers ; le canon (§8) est corrigé.

### DOM-10 — Les arbres retrouvés (M | ★★ | MOYENNE)
> Ouvert le 2026-07-29 par la doctrine du parchemin ([../GAME_ONBOARDING.md](../GAME_ONBOARDING.md)
> §6.4, arbitrage A17). Une fois l'accès aux arbres porté par un parchemin (**ONB-08**), une
> couche devient possible : des arbres **hors catalogue**, ouverts par une rencontre que
> **l'accomplissement** déclenche — le joueur qui a mené l'arbre du mineur à son dernier palier
> croise un vieux Nain à moitié changé en minerai, qui lui confie un parchemin de prospection
> que le registre ne mentionne pas.
>
> **Ce que ça répare** : aujourd'hui, **terminer un arbre ne donne rien** — le dernier palier
> est un cul-de-sac. Il devient une condition de rencontre.
>
> Prérequis : ← **ONB-08** (le parchemin ouvre un arbre) ; croise NAR (les rencontres) et
> PLAN_REPERTOIRE (cousin, à ne pas confondre : le Répertoire est **collectif** et porte sur la
> **matéria** ; ceci est **individuel** et porte sur les **domaines**)
- [ ] Catégorie d'arbre **hors registre** : absent du catalogue public, existant pour le joueur
      seulement après la rencontre
- [ ] Parchemin retrouvé **lié** (non échangeable) — l'unique exception aux quatre conditions
      du parchemin de registre. Ce qui circule entre joueurs est **l'information**, jamais
      l'objet : sans ça, le premier découvreur met le secret à l'hôtel des ventes et il meurt
      en deux jours
- [ ] Condition de rencontre = **un accomplissement**, jamais un tirage
- [ ] **Les cinq lois**, chacune verrouillée par un test :
  - [ ] **latéral, jamais vertical** (GAME_WORLD §12.3c) — des options, jamais de la puissance,
        sinon le joueur qui n'a pas croisé le Nain est mécaniquement derrière
  - [ ] **cumulatif, jamais manqué** (§12.3d) — la rencontre reste disponible indéfiniment pour
        quiconque remplit la condition. Pas de premier arrivé, pas de fenêtre, pas de date
  - [ ] **jamais nécessaire** — aucune recette, aucun palier, aucune quête normale n'en dépend
  - [ ] la condition est un accomplissement
  - [ ] le parchemin est lié
- [ ] Premier contenu : un arbre retrouvé, en preuve du mécanisme (le reste est du contenu)
- [ ] Tests : absence du catalogue ; rencontre rejouable par un second joueur ; non-échangeable ;
      aucune recette ni progression normale n'en dépend

---

## Risques

| Risque | Parade |
|---|---|
| **ONB-20 amende DOM-02 garde-fou 1** : le port de l'equipement (armes, armures, outils) passe par des nœuds d'arbre — le prerequis de competence cesse d'etre un cas reserve pour devenir la regle | Les nœuds de port sont des **points d'entree gratuits** (0 point) d'arbres ouverts par un parchemin accessible a tous, **partages** entre tous les arbres qui les enseignent, et une piece non portable dit **ou l'apprendre**. Le mage en plaque existe toujours — il a du l'apprendre. Cf. GAME_ONBOARDING §6.0 bis |
| **Le port palie pourrait ressembler a un peage** (un rang d'arbre qui n'apporte qu'un droit) | Le port est une **echelle** : echelon 1 gratuit a l'ouverture de l'arbre, echelons suivants chaines et **jamais seuls sur un palier** (ils accompagnent des passifs). Les competences `*_weapon_t2` → `t3` deja en base **sont** cette echelle et restent inchangees ; il manque l'echelon 1 (les armes T1 n'ont aucun prerequis) et les echelles d'armures et d'outils. Un butin trop evolue reste **vendable** — un revenu, pas une frustration |
| Le refactor DOM-01 casse l'équilibre livré | Rétro-compat « global » + migration par domaine, testée domaine par domaine |
| L'activation par build frustre (passifs « perdus ») | L'UI de build montre les domaines actifs ; les accords ne sont jamais perdus |
| Le typage des emplacements bloque un débutant | Garde-fou testé : emplacement libre sur tous les kits T1 |
| 36 arbres à mettre en conformité d'un coup | Non : gabarits opposables, mise en conformité progressive, domaines fréquentés d'abord |
