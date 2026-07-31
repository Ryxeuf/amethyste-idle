# Plan — Le bestiaire

> **Numérotation :** jalons préfixés **BES-**. Pas de conflit avec les autres préfixes.

> Décline [../GAME_BESTIARY.md](../GAME_BESTIARY.md). Constat chiffré :
> [../GAME_DATA_AUDIT.md](../GAME_DATA_AUDIT.md) §6.
>
> **Ce que ce plan n'est pas.** Il ne crée quasiment aucun monstre. Les 65 espèces
> livrées suffisent à remplir les 12 cases `palier × rang` : la faille du milieu
> (rien entre les niveaux 5 et 26 dans le graphe de zones) est un **problème de
> répartition**, pas de contenu — les 17 espèces concernées existent et ne sont
> qu'inaccessibles au graphe déclaratif.
>
> **Dépendance** : BES-02 (les stats dérivées) suppose MAT-01 livré — l'élément
> du monstre. Les deux touchent l'entité `Monster` et sa migration : **à livrer
> ensemble ou à la suite**, jamais en parallèle sur deux branches.

## Vue d'ensemble

**6 jalons** (**BES-01** à **BES-06**) en 2 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| BES-01 | Deux axes : `tier` et `rank` | M | ← MAT-01 (même migration) |
| BES-02 | Les stats dérivées du gabarit | M | ← BES-01 |
| BES-03 | La bascule du peuplement dans `zones.yaml` | M | ∅ |
| BES-04 | La faille du milieu et la Crête | S | ← BES-01, BES-03 |
| BES-05 | Le ménage du code mort | S | ∅ |
| BES-06 | Tests du plan | S | ‖ |

```
Piste A — Les échelles  : BES-01 → BES-02 → BES-04
Piste B — Le peuplement : BES-03 ↗          BES-05 ‖ ; BES-06 ‖
```

**Ordre conseillé.** BES-05 et BES-03 sont indépendants et sans risque : à passer
en premier. BES-01 est le pivot et doit voyager avec MAT-01.

---

## Piste A — Les échelles

### BES-01 — Deux axes : `tier` et `rank` (M | ★★★ | HAUTE)
> Trois échelles cohabitent (`level` 1-40, `difficulty` 1-5, `isBoss`) et aucune
> ne dit la difficulté. Le joueur n'ayant **pas de niveau** (règle 6), l'échelle
> 1-40 ne se compare à rien.
> Prérequis : ← MAT-01 — **même migration d'entité**, à livrer ensemble
- [ ] `tier` (T0…T4) remplace `level` — **repris de la zone**, jamais inventé :
      `GAME_ZONES` §2 déclare déjà le palier de chaque zone
- [ ] `rank` (`Common` / `Elite` / `Boss`) remplace `difficulty` **et** `isBoss` ;
      les 10 monstres déjà marqués `isBoss` deviennent `rank: Boss`
- [ ] `MonsterItem::minDifficulty` devient `minRank`
- [ ] **Recalibrer les 3 consommateurs de `level` dans le même jalon**, sinon
      l'XP de matéria est divisée par huit : `MateriaXpGranter`
      (`BASE_XP_PER_KILL × level`), `ReputationManager::getReputationAmount`
      (seuils 20/10/5), `GuildQuestManager` (`1 + level / 10`)
- [ ] Tests : deux axes seulement, palier cohérent avec la zone, recalibrage

### BES-02 — Les stats dérivées du gabarit (M | ★★ | HAUTE)
> Comme les filons ont des profils de palier et les matéria une dérivation depuis
> le sort, les stats d'un monstre se dérivent de sa case `tier × rank`.
> Prérequis : ← BES-01
- [ ] Grille `life` de départ (GAME_BESTIARY §3) : 30/90/250 en T1 jusqu'à
      300/850/2400 en T4 — ×~2,2 par palier, ×~3 par rang
- [ ] `hit` = 70 + 5 × palier, +5 pour `Elite` et `Boss` ; `speed` et le nombre
      de sorts au même principe
- [ ] **L'écart au gabarit reste permis, mais explicite et commenté** — sur le
      modèle des corrections d'`affinities.yaml`. Ce qui est interdit, c'est
      l'absence de gabarit
- [ ] Tests : dérivation, écarts déclarés, aucun saut de ×4 entre deux paliers

### BES-04 — La faille du milieu et la Crête (S | ★★★ | HAUTE)
> Le monde se coupe en deux : départ 1-24, fin 26-38, avec un saut de vie de ×4
> et pour seul pont le Marais, qui n'existe que par le legacy.
> Prérequis : ← BES-01, BES-03
- [ ] Répartir les 65 espèces sur les 12 cases `palier × rang` — **cible
      minimale par palier peuplé : 6 communs, 3 élites, 1 boss**
- [ ] **La Crête de Ventombre** : zone T3 à sommet T4 (cobalt, mithril) peuplée
      de monstres de niveaux 3 à 5. On y récolte le métal le plus rare du monde
      de base sans rien risquer — la faune doit rejoindre le palier de la zone
- [ ] Vérifier qu'aucune espèce ne devient inaccessible, hors mannequins
      d'entraînement et boss narratifs réservés (`ancient_wyrm`,
      `convergence_guardian`, `the_first_silence`)
- [ ] Tests : aucun palier vide, richesse et danger alignés par zone

---

## Piste B — Le peuplement et le ménage

### BES-03 — La bascule du peuplement dans `zones.yaml` (M | ★★★ | HAUTE)
> 53 mobs déclarés en YAML, 116 dans `MobFixtures` (par coordonnées et carte,
> vestige d'avant le pivot ZON-21). Doctrine ZON-11 : « ajouter du contenu =
> ajouter de la donnée ici, pas du code ».
> Prérequis : ∅
- [ ] Remonter dans `zones.yaml` les **17 espèces que seul le legacy place** —
      dragon, minotaure, griffon, troll, hydre des marais, wyverne, naga,
      archidruide corrompu… c'est tout le milieu de gamme
- [ ] Donner un bloc `mobs:` aux **3 zones qui n'en ont pas** — Marais et Crête ;
      le Quartier des Jardins étant T0, il reste **sans faune hostile**
- [ ] Préserver les mobs de donjon (`map_dungeon_racines`), hors graphe de zones
- [ ] Supprimer `MobFixtures` et les coordonnées héritées ; `Zone::sourceMap`
      n'a plus à porter la faune
- [ ] Tests : aucun monstre placé hors `zones.yaml` (hors donjons), aucune
      espèce perdue à la bascule

### BES-05 — Le ménage du code mort (S | ★★ | MOYENNE)
> Prérequis : ∅
- [ ] Supprimer `HitChanceCalculator` : sa formule
      `spell.hit + (spell.level − target.level) × 2` rendrait un monstre de
      niveau 30+ intouchable (15 à 35 % de touche), mais **elle n'est appelée
      nulle part** — le calcul réel est `FightCalculator::hasAttackHit()`. Ce
      n'est pas un bug d'équilibrage, c'est un piège de lecture
- [ ] Vérifier qu'aucun autre calcul de combat n'est orphelin
- [ ] Tests : aucune régression sur le chemin de touche réel

---

## Piste — Le contrat

### BES-06 — Tests du plan (S | ★★★ | HAUTE)
> ‖ au fil des jalons. Les 8 invariants de GAME_BESTIARY §6.
- [ ] Deux axes, pas quatre ; le palier suit la zone ; T0 est sûr
- [ ] Aucun palier vide ; les stats suivent le gabarit
- [ ] Une seule source de faune ; aucune espèce inaccessible
- [ ] Tout monstre porte un élément (couvert par MAT-01)

---

## Renvois

- **Les PNJ** ne sont pas traités ici : 33 PNJ livrés, **5 zones sur 12 sans
  aucun PNJ** (Quartier des Jardins, Mer de Sel, Cité ensevelie, Pas-de-Givre,
  Glacier du Silence). Le sujet dépend de ce que chaque zone doit raconter —
  **reporté à [PLAN_ZONES.md](PLAN_ZONES.md)**. Dette à consigner :
  `PnjFixtures` configure les boutiques **par index numérique** sur une liste
  définie ailleurs, donc insérer un PNJ décale toutes les boutiques suivantes.
- **L'élément des monstres** est porté par **MAT-01** ([PLAN_MATERIA.md](PLAN_MATERIA.md)).
- **Les donjons** sont traités à part.
