# Audit des donnees de jeu — 2026-07-31

> **Statut** : etat des lieux, pas un plan. Il mesure ce que le monde livre
> contient reellement, sans rien decider. Les decisions se prennent apres, et
> se declinent dans les plans annexes (`docs/roadmap/PLAN_*.md`).
>
> Contexte : le jeu est en **pur dev**, aucune donnee de production, aucune
> session a preserver. Toute donnee peut etre supprimee, renumerotee ou
> refondue sans migration de compatibilite.
>
> Methode : lecture statique des fixtures et des fichiers `config/game/`
> (Docker indisponible dans la session d'audit). Les chiffres sont ceux des
> **sources de verite chargees**, pas ceux d'une base peuplee.

---

## 1. Ce qui est charge, et ce qui ne l'est plus

Le repertoire `fixtures/` a la racine ressemble a une source de verite. Il n'en
est une que **partiellement**, et rien ne le signale a la lecture.

| Fichier / dossier | Charge par | Etat |
|---|---|---|
| `fixtures/game/item/*.yaml` | `Game\ItemFixtures` (Finder, `notName('item.yaml')`) | **vivant** — 120 objets |
| `fixtures/game/equipment_set.yaml` | `Game\EquipmentSetFixtures` | **vivant** |
| `fixtures/area_data.json` | `AreaFixtures` | **vivant** |
| `fixtures/domain.yaml` | — | **mort** — 15 domaines, contre 36 dans `DomainFixtures.php` |
| `fixtures/game/skill/*.yaml` | — | **mort** — 46 nœuds, contre 551 dans `Game\SkillFixtures` |
| `fixtures/game/spell/*.yaml` | — | **mort** — 14 sorts, contre 253 dans `SpellFixtures` |
| `fixtures/game/monster/monster.yaml` | — | **mort** — 4 monstres, contre 65 |
| `fixtures/{mob,pnj,player,player_item,player_quest,inventory,slot,user,world,map,monster_item}.yaml` | — | **mort** |

**Le piege** : `fixtures/game/item/` est vivant alors que `fixtures/game/skill/`
est mort, dans le meme arbre, au meme format. Un contributeur qui edite
`fixtures/domain.yaml` pour ajouter un domaine ne verra jamais son effet.

Nuance sur `Finder->notName('item.yaml')` : le filtre est un **nom exact**.
`gear_item.yaml` (57 objets) est donc bien charge — ce que la lecture rapide du
code suggere l'inverse.

---

## 2. Domaines de competence

**36 domaines**, tous pourvus d'un arbre. C'est la couche la plus saine du jeu.

| Registre | Domaines |
|---|---|
| Combat (24) | 8 elements x 3 cases — Feu : pyromancien / berserker / artificier · Eau : hydromancien / guerisseur / maremancien · Air : foudromancien / archer / vagabond · Terre : geomancien / defenseur / gardien · Metal : soldat / chevalier / ingenieur · Bete : chasseur / dompteur / druide · Lumiere : paladin / pretre / inquisiteur · Ombre : assassin / necromancien / sorcier |
| Recolte (5) | mineur, herboriste, pecheur, depeceur, bucheron |
| Artisanat (7) | forgeron, tanneur, alchimiste, joaillier, cuisinier, charpentier, tailleur |

**551 nœuds** : 543 repartis en 36 arbres (13 a 18 nœuds chacun, gabarit tenu)
+ 8 nœuds partages.

Le catalogue public (`config/game/domain_catalog.yaml`) couvre bien les 36.

**Rien a creer ici.** Le trou n'est pas dans les arbres — il est dans ce que
leurs nœuds promettent (cf. §3).

---

## 3. Le trou principal : la chaine competence → materia → sort

La regle du projet (CLAUDE.md #9 et #10) impose une chaine a trois maillons :
un nœud d'arbre **debloque** une materia (`actions.materia.unlock`), le joueur
doit **posseder** cette materia, puis la **socketter**. La chaine est cassee aux
deux extremites.

| Maillon | Mesure |
|---|---|
| Nœuds d'arbre portant un `materia.unlock` | **200 slugs distincts** |
| Materia livrees comme objet | **68** |
| Materia dont le sort correspond a un `unlock` | **61** |
| Materia obtenables quelque part dans le monde | **10** |
| **Materia a la fois debloquables et obtenables** | **10** |

### 3.1 — 139 nœuds sur 200 promettent une materia qui n'existe pas

Le joueur depense des points, lit « Materia : Souffle de givre », et il n'y a
aucun objet a trouver. Exemples (liste complete reconstructible par le script
d'audit) : `ambush`, `bastion`, `chain-lightning`, `divine-judgment`,
`hurricane`, `landslide`, `miracle`, `petrification`, `provocation`, `riposte`,
`smite`, `stampede`, `tsunami`, `zephyr`…

### 3.2 — 58 materia sur 68 n'ont aucune source

Ni filon, ni butin, ni recette, ni boutique, ni quete. Les **10** obtenables :

```
materia_soin  (quete acte I)
materia_fire_ball, materia_flame_rain, materia_frost_mist,
materia_light_blessing, materia_savage_bite, materia_steel_riposte,
materia_stone_throw, materia_vital_drain, materia_wind_lame   (butin de monstre)
```

### 3.3 — 7 materia ne sont debloquees par aucun nœud

`flamer`, `frost-maelstrom`, `orichalcum-blade`, `primal-awakening`,
`shadow-covenant`, `solar-burst`, `thunder-storm`.

### 3.4 — Consequence sur les sorts

**253 sorts** livres. **68** sont portes par une materia, **80** sont utilises
par des monstres. **146 sorts ne sont ni l'un ni l'autre** (une partie est
legitime : effets de consommables `bread_heal`, `potion_heal_major`, attaques de
base `bare_hands`, `none_attack_2`).

En pratique, un joueur qui monte n'importe lequel des 24 arbres de combat
dispose du meme reservoir de **10 sorts**.

---

## 4. Objets

**419 objets** au total (299 en PHP, 120 en YAML).

### 4.1 — Taxonomie

Le champ `Item::type` est une chaine libre. Douze valeurs coexistent, dont
plusieurs se recouvrent :

| type | n |
|---|---|
| `gear` | 178 |
| `materia` | 68 |
| `stuff` | 31 |
| `crafted` | 28 |
| `plant` | 24 |
| `resource` | 7 |
| `quest` | 6 |
| `weapon` | 4 |
| `ore` | 3 |
| `food` | 3 |
| `potion` | 2 |
| `herb` | 2 |

Recouvrements a trancher : `weapon` vs `gear` · `herb` vs `plant` ·
`ore`/`resource`/`plant` vs une categorie « matiere premiere » unique ·
`crafted` (qui decrit une **provenance**, pas une nature) · `food`/`potion` vs
`stuff`.

### 4.2 — 109 objets sans aucune source

| type | n | exemples |
|---|---|---|
| materia | 58 | cf. §3.2 |
| outils (`tool.yaml`) | 21 | `axe_*`, `chisel_*`, `hammer_*`, `mortar_*`, `pickaxe_*`, `sickle_*`, `skinning_knife_*`, `tanning_kit_*` en bronze/fer/acier/mithril |
| gear | 16 | `t1_axe`, `t1_dagger`, `t1_lance`, `t2_axe`, `t3_dagger`, `convergence_blade`, `magic_amulet`, `wooden_shield`… |
| stuff | 5 | `fishing_rod`, `magic_crystal`, `leather_skin_1/2`, parchemins de domaine orphelins |
| food / herb / weapon / quest | 9 | `food_apple`, `food_bread`, `food_cheese`, `herb_lavender`, `herb_mint`, `bow`, `dagger`, `staff`, 2 objets de quete |

Les **outils** sont le cas le plus structurant : toute une echelle
bronze → fer → acier → mithril existe pour 9 familles d'outils, et **aucune**
n'est fabricable, achetable ni trouvable. Le joueur ne peut pas progresser en
outillage de recolte.

`ore_adamantite`, `ore_starmetal`, `ore_voidium` : minerais de haut palier
declares, sans filon. Coherent avec la carte des minerais de `GAME_ZONES.md`
(la base s'arrete au mithril), mais alors ces objets ne devraient pas etre
livres — ou bien les trois lignes doivent trouver leur source.

Doublons averes : `herb_lavender` / `herb_mint` contre `plant-lavender` /
`plant-mint` (ceux-ci recoltables) · `leather_skin_1/2` contre
`leather_raw` / `leather_thick` (deja diagnostique par ECO-02, les doublons
n'ont pas ete supprimes) · `food_bread` contre `bread`.

---

## 5. Ressources et economie

### 5.1 — Filons

**55 filons** repartis sur 12 zones, calibres proprement sur les profils de
palier T0→T4 + A (recalibrage du 2026-07-28).

| profession | filons |
|---|---|
| herbalism | 24 |
| mining | 19 |
| fishing | 7 |
| woodcutting | 5 |
| skinning | 0 — normal, le cuir vient du butin |

Desequilibre a arbitrer : l'herboristerie porte 44 % des filons du monde,
la peche et le bucheronnage 22 % a eux deux.

### 5.2 — Recettes

**115 recettes** pour 7 metiers d'artisanat.

| metier | recettes |
|---|---|
| forgeron | 30 |
| tanneur | 21 |
| joaillier | 20 |
| alchimiste | 15 |
| tailleur | 11 |
| charpentier | 10 |
| cuisinier | 8 |

Repartition par niveau requis : 18 (n1), 23 (n2), 20 (n3), 15 (n4), 13 (n5),
8 (n6), 7 (n7), 4 (n8), 1 (n9), 6 (n10). La courbe s'effondre apres le
niveau 5 : les trois derniers paliers portent 11 recettes sur 115.

### 5.3 — Repartition par zone

| zone | type | sur | filons | mobs declares | pnjs YAML |
|---|---|---|---|---|---|
| village-de-lumiere (Le Fanal) | city | oui | 2 | 0 | 3 |
| foret-des-murmures | wilderness | non | 13 | 17 | 2 |
| mines-profondes | wilderness | non | 6 | 11 | 2 |
| marais-brumeux | wilderness | non | 11 | **0** | 0 |
| crete-de-ventombre | wilderness | non | 4 | **0** | 0 |
| quartier-des-jardins | city | oui | **0** | 0 | 0 |
| dunes-d-ambre | wilderness | non | 5 | 6 | 2 |
| mer-de-sel | wilderness | non | 2 | 4 | 0 |
| cite-ensevelie | wilderness | non | 3 | 3 | 0 |
| pas-de-givre | wilderness | non | 2 | 3 | 0 |
| glacier-du-silence | wilderness | non | 2 | 4 | 0 |
| vallons-d-aubepine | wilderness | non | 5 | 5 | 3 |
| **total** | | | **55** | **53** | **12** |

`marais-brumeux` et `crete-de-ventombre` n'ont pas de bloc `mobs:` dans le YAML
et dependent encore du chemin legacy `Mob.map` via `Zone::sourceMap`
(`MobFixtures`, 116 entrees). Deux mecanismes de peuplement coexistent.

---

## 6. Monstres

**65 monstres**, dont 2 mannequins d'entrainement (ONB-11).

### 6.1 — Courbe de niveau

| niveau | 1 | 2 | 3 | 4 | 5 | 10 | 12 | 13 | 15 | 16 | 18 | 20 | 24 | 26 | 28 | 30 | 32 | 33 | 35 | 36 | 38 | 40 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| n | 11 | 11 | 10 | 6 | 4 | 2 | 1 | 1 | 2 | 1 | 1 | 2 | 1 | 1 | 1 | 4 | 1 | 1 | 1 | 1 | 1 | 1 |

**42 monstres sur 65 tiennent dans les niveaux 1 a 5.** Au-dela, la courbe est
un fil : trou complet entre 5 et 10, puis un monstre par palier. La progression
n'a pas de matiere apres le premier acte.

### 6.2 — Elements

**Aucun monstre ne porte d'element.** 44 sur 65 declarent des
`elementalResistances`, utilisees par `DamageCalculator`. Consequence directe :
la capacite raciale de l'Orc — « lire l'element d'un monstre des la premiere
rencontre » (`GAME_ONBOARDING`) — n'a rien a lire.

### 6.3 — Placement

43 monstres places via `zones/world_1.yaml`, 45 via `MobFixtures`.
**5 ne sont places nulle part** : `ancient_wyrm`, `convergence_guardian`,
`the_first_silence`, plus les 2 mannequins (normal : dresses a la volee par
`TrainingFightLauncher`).

### 6.4 — Butin

130 objets distincts figurent dans une table de butin, pour 65 monstres.

---

## 7. PNJ

| source | n |
|---|---|
| `VillageHubPnjFixtures` (Fanal, avec dialogues + quetes) | 7 |
| `zones/world_1.yaml` (figures de fond, `pnjs:`) | 12 |
| `PnjFixtures` + fixtures de zone (Foret / Mines / Marais / Montagne) | ~93 entrees de configuration |

Boutiques : **38 objets distincts** vendus. Le plancher T1 PNJ
(`GAME_PRINCIPLES`) n'est tenu que sur une poignee de lignes.

Cinq zones sur douze n'ont **aucun PNJ declare** : `marais-brumeux`,
`crete-de-ventombre`, `quartier-des-jardins`, `mer-de-sel`, `cite-ensevelie`,
`pas-de-givre`, `glacier-du-silence`.

---

## 8. Quetes

**108 quetes**, par type : `stuff` 35, `gear` 12, `explore` 10, `quest` 4,
`kill` 2, `harvest` 1 (le reste sans type explicite).

Recompenses en objets : 33 objets distincts, majoritairement des **parchemins
de domaine** (archer, assassin, berserker, pecheur, herboriste, chevalier,
bucheron, mineur, paladin, depeceur, soldat) et les armes T1.

Une seule materia est distribuee par quete : `materia_soin`.

---

## 9. Donjons

**4 donjons.**

| slug | zone | joueurs | acces |
|---|---|---|---|
| `racines-de-la-foret` | hors graphe | 1 | XP ≥ 500 (`minLevel 5 x 100`) |
| `nexus-de-la-convergence` | hors graphe | 1 | XP ≥ 2500 + les 4 fragments |
| `galeries-envahies` | foret-des-murmures | groupe | — |
| `forges-noyees` | mines-profondes | groupe | — |

Deux donjons de groupe pour douze zones. Les deux donjons solo sont
**hors graphe de zones** : ils ne sont accessibles que depuis `/game/dungeon`,
alors que l'ecran de zone ne propose que ceux dont `maxPlayers > 1`.

`Dungeon::minLevel` est un reliquat de nommage : il ne designe pas un niveau
(il n'y en a pas, cf. CLAUDE.md #6) mais un seuil d'XP total, via
`minLevel * 100` calcule en trois endroits distincts (`DungeonManager` x2,
`ZoneController`).

---

## 10. Recapitulatif des ecarts

| # | Ecart | Portee |
|---|---|---|
| 1 | 139 nœuds d'arbre sur 200 debloquent une materia inexistante | **bloquant** |
| 2 | 58 materia sur 68 sans aucune source ; 10 sorts jouables pour 24 arbres | **bloquant** |
| 3 | 21 outils de recolte (4 paliers x 9 familles) sans source | fort |
| 4 | Courbe de monstres : 42/65 aux niveaux 1-5, rien entre 5 et 10 | fort |
| 5 | Aucun monstre ne porte d'element (capacite orque inerte) | fort |
| 6 | `fixtures/domain.yaml`, `skill/`, `spell/`, `monster/` morts mais lisibles | fort |
| 7 | Taxonomie `Item::type` : 12 valeurs qui se recouvrent | moyen |
| 8 | 7 zones sur 12 sans PNJ ; `quartier-des-jardins` sans aucun contenu | moyen |
| 9 | Deux mecanismes de peuplement de zone (`zones.yaml` vs `MobFixtures`) | moyen |
| 10 | Doublons d'objets (`herb_*`, `leather_skin_*`, `food_bread`) | moyen |
| 11 | Filons : 44 % d'herboristerie, 9 % de bucheronnage | moyen |
| 12 | Recettes : 11 sur 115 au-dela du niveau 5 | moyen |
| 13 | 2 donjons de groupe pour 12 zones ; 2 donjons solo hors graphe | a arbitrer |
