# Les donjons — cadrage acté (2026-07-31)

> **Source de vérité** du modèle de donjon, de son contenu et de sa couverture.
> Constat chiffré : [GAME_DATA_AUDIT.md](GAME_DATA_AUDIT.md) §9.
>
> Adossé à [GAME_BESTIARY.md](GAME_BESTIARY.md) (le palier et le rang d'un
> monstre), [GAME_MATERIA.md](GAME_MATERIA.md) §4.3 (les donjons distribuent les
> matéria hautes) et [GAME_ZONES.md](GAME_ZONES.md) §2 (le palier de chaque zone,
> qui déclare déjà la Cité ensevelie comme « T4 — donjon »).
>
> Décliné en jalons dans [roadmap/PLAN_DUNGEONS.md](roadmap/PLAN_DUNGEONS.md).

---

## 1. Constat

**4 donjons**, deux modèles incompatibles, et aucun des deux ne tient.

### 1.1 Les deux donjons solo sont injouables

`racines-de-la-foret` et `nexus-de-la-convergence` reposent sur la mécanique de
carte navigable **supprimée par ZON-21**. Entrer téléporte le joueur sur une
`Map` aux coordonnées `1.1` — mais il n'y a plus de déplacement sur carte, et
`Player::currentZone` reste celle d'origine. La chaîne est rompue en trois
endroits :

| Maillon | État |
|---|---|
| Les 4 mobs de `map_dungeon_racines` | **sans zone** — aucune zone ne les déclare en `source_map`, et `ExploreService::resolveMob` cherche `findAvailableInZone()`. Introuvables. |
| L'écran `dungeon/show.html.twig` | un bouton « entrer », un lien retour. **Aucune action une fois dedans.** |
| `DungeonCompletionListener` | attend la mort d'un boss **sur la carte du donjon** — un combat qu'aucun chemin ne crée. |

Le plus grave : **`nexus-de-la-convergence` n'a aucun monstre.** C'est la fin de
l'arc narratif, elle exige les 4 fragments de quête, et elle est vide.

### 1.2 Les deux donjons de groupe fonctionnent, mais sont vides

`galeries-envahies` et `forges-noyees` tournent bien (modèle PBBG, ZON-20). Ce
qui les entoure est sain — **la récompense décroissante plutôt qu'un lockout
dur** est une bonne décision qu'on garde. Le contenu, lui, est un placeholder :

- **La rencontre n'est pas un monstre** : un sac de PV abstrait,
  `encounterHp = hpParMembre × nombreDeMembres`. Aucun `Monster`, aucun élément,
  aucune IA.
- **Le dégât d'un joueur est son `hit`** — `damage = max(1, $player->getHit())`.
  Ni arme, ni sort, ni matéria, ni équipement n'entrent dans le calcul.
- **La rencontre ne riposte jamais.** Aucun dégât aux joueurs, aucun statut
  d'échec : **un donjon de groupe ne peut pas être perdu.**
- **`currentStep` existe et n'est jamais avancé.** Le champ est là, la
  progression par étapes n'a jamais été écrite.
- **La récompense est en gils uniquement**, quand `lootPreview` promet
  « Équipement tier 2, Matéria commune, Composants d'artisanat ».

### 1.3 La couverture

4 donjons pour 12 zones, et les deux de groupe sont rattachés aux **deux zones
qui portent déjà tout le contenu** (Forêt, Mines). `racines-de-la-foret` (« sous
les racines de la forêt ») fait par ailleurs doublon avec `galeries-envahies`
(« sous les racines de la forêt, un boyau s'est effondré »).

`Dungeon::minLevel` est un faux nom : ce n'est pas un niveau — il n'y en a pas
(règle 6) — mais un seuil d'XP, via `minLevel × 100` recalculé à **trois endroits
distincts** (`DungeonManager` ×2, `ZoneController`).

---

## 2. Décision 1 — Un seul modèle de donjon

> **Tout donjon est rattaché à une zone du graphe et se joue depuis l'écran de
> zone.** La `Map` et les coordonnées disparaissent du modèle.

Les deux donjons solo sont **convertis** au modèle de zone avec `maxPlayers: 1`.
Ce n'est pas une perte d'intention : le solo reste possible, il passe simplement
par la même mécanique. Le Nexus de la Convergence redevient jouable, et la fin de
l'arc narratif cesse d'être un cul-de-sac.

Ce que la conversion supprime : `DungeonRun::originMap` / `originCoordinates`,
la téléportation, `DungeonCompletionListener` (la complétion est portée par le
run, pas par un événement de mort sur une carte), et l'écran `/game/dungeon`
séparé.

`Dungeon::minLevel` est renommé en **seuil d'XP explicite**, et le calcul
`× 100` est centralisé en un point au lieu de trois.

---

## 3. Décision 2 — Le contenu : des étapes, de vrais monstres, une riposte

### 3.1 Trois étapes, branchées sur le rang

`currentStep` cesse d'être inerte. Un donjon est une **suite de rencontres**, et
chacune est un vrai `Monster` dont le palier est celui de la zone :

| Étape | Rang de la rencontre |
|---|---|
| 1 | `Common` du palier |
| 2 | `Elite` du palier |
| 3 | `Boss` du palier |

C'est la grille de [GAME_BESTIARY.md](GAME_BESTIARY.md) §2 appliquée telle
quelle — le donjon ne définit pas ses propres créatures, il **puise dans la faune
de son palier**. Un donjon T4 se peuple donc tout seul le jour où le palier T4
est peuplé.

### 3.2 Le combat réutilise le build du joueur

Le dégât d'un joueur cesse d'être son `hit`. L'action d'un membre est **son
action réelle** : attaque de base de son arme, ou sort d'une matéria sertie
(`CombatCapacityResolver`). C'est ce qui rend le build pertinent en donjon — et
sans quoi la matéria, l'équipement et l'élément n'y servent à rien.

La boucle semi-synchrone est conservée : `turnOrder`, `turnDeadline`, et
l'action par défaut quand l'échéance passe (le tour manqué devient une attaque de
base). C'est ce qui fait tenir le modèle PBBG, et ça marche.

### 3.3 Un donjon peut être perdu

**La rencontre riposte.** Elle frappe un membre à chaque tour selon ses stats
dérivées ; quand tous les membres sont à terre, le run passe à `STATUS_FAILED` —
un statut qui n'existe pas aujourd'hui.

> Sans échec possible, il n'y a pas de donjon : il y a un bouton qui donne des
> gils au bout de N clics.

### 3.4 Le butin cesse de mentir

`lootPreview` promet de l'équipement et de la matéria ; le run ne distribue que
des gils. La distribution d'objets est ajoutée, **indexée sur le palier** :

| Palier du donjon | Matéria | Reste |
|---|---|---|
| T1 | m2 | équipement t1, matières du palier |
| T2 | m3 | équipement t2 |
| T3 | m3-m4 | équipement t2-t3 |
| T4 | **m4-m5** | équipement t3, matières rares |

C'est ce que [GAME_MATERIA.md](GAME_MATERIA.md) §4.3 attend des donjons : ils
sont le canal du **palier haut**, et la première raison mécanique d'y entrer.
`lootPreview` devient une **projection de la table réelle**, jamais un texte
libre — un aperçu qui ment est pire que pas d'aperçu.

---

## 4. Décision 3 — Un donjon par palier

Un donjon par palier de zone, dans **quatre zones distinctes**, avec un butin
indexé sur son palier :

| Palier | Zone | Donjon | Origine |
|---|---|---|---|
| T1 | Forêt des Murmures | Les Galeries envahies | existant |
| T2 | Mines profondes | Les Forges noyées | existant |
| T3 | Crête de Ventombre *(ou Dunes d'Ambre)* | **à écrire** | — |
| T4 | Cité ensevelie | Le Nexus de la Convergence | existant, converti |

Deux remarques :

- **La Cité ensevelie était déjà prévue pour ça.** `GAME_ZONES` §2 la déclare
  « T4 — donjon », et §3 y place l'orichalque comme « butin de donjon plus que
  filon ». Le Nexus s'y rattache sans rien inventer.
- **`racines-de-la-foret` est fusionné dans `galeries-envahies`.** Les deux
  racontent la même chose au même endroit ; garder les deux serait doubler le T1
  et laisser les trois autres paliers à découvert.

Le coût net est donc **un donjon à écrire**, pas quatre. Le T3 est le seul
palier sans ancrage, et c'est aussi celui dont la Crête a besoin (cf.
GAME_BESTIARY §1.3 : zone T3 à sommet T4 peuplée de monstres de niveaux 3 à 5).

---

## 5. Ce qui ne change pas

- **La récompense décroissante** (ZON-20) : chaque réussite du même donjon dans
  la fenêtre glissante réduit la récompense, avec un plancher. Pas de lockout
  dur, le joueur peut toujours rejouer. C'est une bonne décision, on la garde —
  et la décroissance s'étend à la table d'objets, pas seulement aux gils.
- **L'entrée ne coûte pas d'énergie** : le donjon reste le réservoir de contenu
  gratuit du modèle PBBG.
- **La boucle semi-synchrone** : ordre de tour, échéance, action par défaut.

---

## 6. Invariants testables

1. **Un seul modèle** — tout donjon a une `zone`, aucun n'a de `Map` ni de
   coordonnées d'origine.
2. **Tout donjon est complétable** — il existe un chemin depuis l'entrée jusqu'à
   la dernière étape, et la complétion est atteignable.
3. **Toute rencontre est un vrai monstre** — aucun sac de PV abstrait ; le palier
   de la rencontre est celui de la zone du donjon.
4. **Le build compte** — le dégât d'un joueur vient de son action réelle, jamais
   de son seul `hit`.
5. **Un donjon peut être perdu** — `STATUS_FAILED` existe et est atteignable.
6. **`lootPreview` ne ment pas** — il est dérivé de la table de butin réelle.
7. **Un donjon par palier** — T1 à T4 ont chacun exactement un donjon, dans des
   zones distinctes.
8. **Le seuil d'entrée est calculé en un seul endroit.**
