# Le bestiaire — cadrage acté (2026-07-31)

> **Source de vérité** des échelles de monstre, de leur peuplement et de la
> courbe de danger du monde. Constat chiffré :
> [GAME_DATA_AUDIT.md](GAME_DATA_AUDIT.md) §6.
>
> Adossé à [GAME_ZONES.md](GAME_ZONES.md) §2 (le palier de chaque zone, **déjà
> déclaré**) et à [GAME_MATERIA.md](GAME_MATERIA.md) §4.2 (l'élément du monstre,
> prérequis du butin dérivé).
>
> Décliné en jalons dans [roadmap/PLAN_BESTIARY.md](roadmap/PLAN_BESTIARY.md).

---

## 1. Constat

**65 monstres.** Trois échelles cohabitent, et aucune ne dit la difficulté.

| Échelle | Sert à | Ne sert pas à |
|---|---|---|
| `level` (1-40, avec trous) | XP de matéria, réputation, quêtes de guilde, affichage | **la difficulté** |
| `difficulty` (1-5) | le gate de butin (`MonsterItem::minDifficulty`) | la difficulté ressentie |
| `life` / `hit` / `speed` / sorts / IA | **la difficulté réelle** | — |
| `isBoss` (bool, 10 monstres) | multiplicateur d'XP, `bossPhases` | un rang à part entière — il manque « élite » |

**Le joueur n'a pas de niveau** (règle 6). Une échelle de 1 à 40 côté monstre ne
se compare donc à rien : c'est un vestige d'un jeu à niveaux.

> **Piège à retirer.** `HitChanceCalculator` porte une quatrième règle —
> `hitChance = spell.hit + (spell.level − target.level) × 2` — qui rendrait un
> monstre de niveau 30+ presque intouchable (15 à 35 % de touche). **Elle n'est
> appelée nulle part.** Le calcul réel est `FightCalculator::hasAttackHit()`, où
> le niveau du monstre n'entre pas. Ce n'est pas un bug d'équilibrage, c'est un
> piège pour qui lit le code : la classe est **supprimée**.

### 1.1 Le monde se coupe en deux

| Bloc | Zones | Niveaux | Vie |
|---|---|---|---|
| Départ | Vallons, Forêt, Dunes, Mines | 1-24 | 11-250 |
| Fin | Mer de Sel, Cité ensevelie, Pas-de-Givre, Glacier | 26-38 | 950-3200 |

Un saut de vie de **×4** d'un bloc à l'autre, sans palier intermédiaire. Le seul
pont est le Marais (1-20) — et il n'existe **que par le mécanisme legacy**.

### 1.2 Deux mécanismes de peuplement, et le legacy porte le milieu

53 mobs sont déclarés dans `zones.yaml`, 116 dans `MobFixtures` (par coordonnées
et carte, hérité d'avant le pivot ZON-21). **17 espèces ne sont placées que par
le legacy** — dragon, minotaure, griffon, troll, hydre des marais (niv 20),
wyverne (niv 10), naga (niv 13), archidruide corrompu (niv 16)… c'est-à-dire
précisément ce qui manque au graphe déclaratif. Trois zones (Marais, Crête,
Quartier des Jardins) n'ont aucun bloc `mobs:` et en dépendent entièrement.

### 1.3 La richesse ne suit pas le danger

La **Crête de Ventombre** est une zone T3 à sommet T4 (cobalt, mithril) peuplée
de monstres de niveaux 3 à 5 — les plus faibles du jeu. On y récolte le métal le
plus rare du monde de base sans rien risquer.

---

## 2. Décision 1 — Deux axes, orthogonaux et lisibles

`level` et `difficulty` sont remplacés par deux axes qui disent chacun une chose,
et une seule :

| Axe | Question | Valeurs |
|---|---|---|
| **`tier`** | *Où vit la créature ?* | T0 … T4 |
| **`rank`** | *Qu'est-ce que c'est ?* | `Common` · `Elite` · `Boss` |

### 2.1 Le palier reprend celui de la zone — il ne s'invente pas

`GAME_ZONES` §2 déclare **déjà** un palier par zone. Le palier d'un monstre est
celui de la zone où il vit :

| Zone | Palier |
|---|---|
| Le Fanal, Quartier des Jardins | T0 — sûr, **aucune faune hostile** |
| Vallons d'Aubépine, Forêt des Murmures | T1 |
| Mines profondes, Marais brumeux | T2 (fond des Mines : T4) |
| Crête de Ventombre, Dunes d'Ambre | T3 (sommet de la Crête : T4) |
| Cité ensevelie | T4 |

C'est le même vocabulaire que les profils de filon (`zones/world_1.yaml`), que
les bandes de pureté et que les rangs de foyer. **Un seul langage de palier pour
tout le monde**, au lieu de trois échelles qui ne se parlent pas.

### 2.2 Le rang absorbe `difficulty` et `isBoss`

Le booléen `isBoss` ne distingue que deux états ; il manquait le cran du milieu.
`rank` les remplace tous les deux :

| Rang | Ce que c'est | Butin |
|---|---|---|
| `Common` | le tout-venant d'une zone | matières et matéria de son palier |
| `Elite` | la rencontre qui fait hésiter | + une chance de palier supérieur |
| `Boss` | l'événement — garde `bossPhases` | garanti, plus le rare de la zone |

`MonsterItem::minDifficulty` devient `minRank`. Les 10 monstres déjà marqués
`isBoss` deviennent `rank: Boss` — la donnée existante n'est pas perdue.

### 2.3 Ce que le changement d'échelle impose

Trois calculs lisent `level` aujourd'hui et changent d'ordre de grandeur en
passant de 1-40 à T0-T4. **Ils doivent être recalibrés dans le même jalon**,
sans quoi l'XP de matéria est divisée par huit du jour au lendemain :

| Consommateur | Aujourd'hui | Devient |
|---|---|---|
| `MateriaXpGranter` | `BASE_XP_PER_KILL × level` | fonction de (`tier`, `rank`) |
| `ReputationManager::getReputationAmount` | seuils à 20 / 10 / 5 | seuils par palier |
| `GuildQuestManager` | `1 + level / 10` | multiplicateur par palier |

Les autres usages (`MobGenerator`, `ZoneImporter`, `InvasionManager`,
`WorldBossManager`, `BestiaryController`) ne font que recopier ou afficher la
valeur : ils suivent sans recalibrage.

---

## 3. Décision 2 — Les stats se dérivent du palier et du rang

Comme les filons ont des **profils de palier** et les matéria une **dérivation
depuis le sort**, les stats d'un monstre ne s'écrivent pas à la main : elles se
dérivent de sa case `tier × rank`. C'est ce qui rend la courbe lisible et
l'équilibrage possible.

Grille de départ (`life`), à valider en jeu — progression ×~2,2 par palier,
×~3 par rang, calée sur les extrêmes observés (T1 commun ~30, T4 boss ~2400) :

| palier | `Common` | `Elite` | `Boss` |
|---|---:|---:|---:|
| T1 | 30 | 90 | 250 |
| T2 | 70 | 200 | 550 |
| T3 | 150 | 420 | 1 100 |
| T4 | 300 | 850 | 2 400 |

`hit` : 70 + 5 × palier, +5 pour `Elite` et `Boss`. `speed` et le nombre de sorts
suivent la même logique de gabarit.

> **La dérivation est un défaut, pas une prison.** Un monstre peut s'écarter de
> son gabarit quand la fiction l'exige — mais l'écart est alors **explicite et
> commenté**, comme les corrections d'`affinities.yaml`. Ce qui est interdit,
> c'est qu'il n'y ait pas de gabarit du tout.

### 3.1 La faille du milieu se referme

Aucun palier ne doit être vide. Cible minimale par palier peuplé (T1 à T4) :
**6 communs, 3 élites, 1 boss**. Les 65 monstres livrés suffisent largement à
remplir les 12 cases — **c'est un problème de répartition, pas de contenu** : les
17 espèces du milieu de gamme existent déjà et ne sont qu'inaccessibles au graphe.

### 3.2 L'élément

Prérequis déjà acté par [GAME_MATERIA.md](GAME_MATERIA.md) §4.2 (**MAT-01**) :
aucun des 65 monstres ne porte d'élément, ce qui rend impossible le butin de
matéria dérivé et laisse la capacité raciale de l'Orc sans rien à lire. Le
présent plan en dépend et ne le refait pas.

---

## 4. Décision 3 — Un seul mécanisme de peuplement

> **`zones.yaml` devient la source de vérité unique de la faune.**
> `MobFixtures` est supprimé.

C'est la doctrine ZON-11, écrite en tête de `config/game/zones/world_1.yaml` :
*« Ajouter du contenu = ajouter de la donnée ici, pas du code. »* Le peuplement
par coordonnées et par carte est un vestige d'avant le pivot PBBG : les
coordonnées ne servent plus à rien depuis que la position d'un joueur est sa zone
(règle 7).

Ce que la bascule doit préserver :

- Les **17 espèces** que seul le legacy place — c'est tout le milieu de gamme.
- Les **3 zones** sans bloc `mobs:` (Marais, Crête, Quartier des Jardins) ;
  le Quartier des Jardins étant T0, il reste **sans faune hostile**.
- Les mobs de donjon (`map_dungeon_racines`), qui ne relèvent pas du graphe de
  zones et suivent le chemin des donjons.

Une fois la bascule faite, `Zone::sourceMap` n'a plus à porter la faune, et
`WorldEntityZoneListener` n'a plus à la déduire d'une carte.

---

## 5. Ce que ce cadrage ne traite pas

- **Les PNJ.** 33 PNJ (21 avec dialogues, 12 figures de fond), et **5 zones sur
  12 sans aucun PNJ** : Quartier des Jardins, Mer de Sel, Cité ensevelie,
  Pas-de-Givre, Glacier du Silence. Le sujet dépend de ce que chaque zone doit
  raconter : **reporté à la revue des zones** (`PLAN_ZONES`).
  Une dette à consigner au passage : `PnjFixtures` configure les boutiques **par
  index numérique** (`0 =>`, `1 =>`, `4 =>`…) sur une liste définie ailleurs —
  insérer un PNJ décale silencieusement toutes les boutiques suivantes.
- **Les donjons**, traités à part.
- **L'équilibrage fin** du combat : la grille du §3 est un point de départ, pas
  une table validée.

---

## 6. Invariants testables

1. **Deux axes, pas quatre** — `level`, `difficulty` et `isBoss` ont disparu au
   profit de `tier` et `rank`.
2. **Le palier suit la zone** — le palier d'un monstre est celui de la zone où il
   est placé (sauf écart explicite : fond des Mines, sommet de la Crête).
3. **T0 est sûr** — aucune faune hostile dans une zone de palier T0.
4. **Aucun palier vide** — chaque palier T1 à T4 porte au moins 6 communs,
   3 élites et 1 boss.
5. **Les stats suivent le gabarit** — tout écart à la grille `tier × rank` est
   explicite et commenté.
6. **Une seule source de faune** — aucun monstre n'est placé hors de
   `zones.yaml`, hors donjons.
7. **Aucune espèce inaccessible** — tout monstre livré est atteignable, hors
   mannequins d'entraînement et boss narratifs réservés.
8. **Tout monstre porte un élément** (MAT-01).
