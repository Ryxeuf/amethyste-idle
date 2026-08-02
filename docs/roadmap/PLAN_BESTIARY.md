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
| BES-01 ✅ | Deux axes : `tier` et `rank` | M | ← MAT-01 (même migration) |
| BES-02 ✅ | Les stats dérivées du gabarit | M | ← BES-01 |
| BES-03 ✅ | La bascule du peuplement dans `zones.yaml` | M | ∅ |
| BES-04 ✅ | La faille du milieu et la Crête | S | ← BES-01, BES-03 |
| BES-05 ✅ | Le ménage du code mort | S | ∅ |
| BES-06 | Tests du plan | S | ‖ |

```
Piste A — Les échelles  : BES-01 → BES-02 → BES-04
Piste B — Le peuplement : BES-03 ↗          BES-05 ‖ ; BES-06 ‖
```

**Ordre conseillé.** BES-05 et BES-03 sont indépendants et sans risque : à passer
en premier. BES-01 est le pivot et doit voyager avec MAT-01.

---

## Piste A — Les échelles

### BES-01 — Deux axes : `tier` et `rank` (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> Trois échelles cohabitaient (`level` 1-40, `difficulty` 1-5, `isBoss`) et aucune
> ne disait la difficulté. Le joueur n'ayant **pas de niveau** (règle 6), l'échelle
> 1-40 ne se comparait à rien.
> Prérequis : ← MAT-01 ✅ — livré **à la suite** (jamais en parallèle), même entité
- [x] `tier` (T0…T4) remplace `level` — **repris de la zone**, jamais inventé :
      `zones.yaml` déclare désormais la clé `tier` par zone (portée par
      `Zone::tier`, importée), et le palier d'un monstre est celui de sa zone
      la plus basse ; écarts explicites : fond des Mines T4
      (`abyssal_blacksmith`, `forge_lord`)
- [x] `rank` (`Common` / `Elite` / `Boss`, enum `MonsterRank`) remplace
      `difficulty` **et** `isBoss` ; les 10 monstres `isBoss` deviennent
      `rank: 'boss'`, les élites reprennent l'ancienne difficulté 4-5 ;
      `Monster::isBoss()` reste comme **dérivé du rang** (porteur de
      `bossPhases`, du multiplicateur d'XP, de l'interdiction de fuite)
- [x] `MonsterItem::minDifficulty` devient `minRank` — les 4 lignes gatées
      passent à `elite`, et leurs 4 porteurs (griffon, minotaure, golem de
      pierre, troll) sont bien de rang élite
- [x] **Les 3 consommateurs de `level` recalibrés dans le même jalon** —
      `MateriaXpGranter` (10 × facteur de palier 1/3/8/18/32, élite ×2, boss
      ×5), `ReputationManager::getReputationAmount` (seuils par palier
      10/15/25/50), `GuildQuestManager` (`1 + tier`, et le critère Doctrine
      `findBy(['isBoss' => false])` devient `rank IN (common, elite)`) ; plus
      `GuildPointsListener` (1/1/2/4/7 par palier), hors liste mais même piège
- [x] `Mob::level` devient `Mob::tier` (recopie du monstre au spawn) ;
      l'invasion cesse de gonfler une échelle morte, l'invoqué vit au palier
      de son invocateur
- [x] Tests : deux axes seulement, palier cohérent avec la zone (55 espèces
      placées vérifiées contre `zones.yaml`), `bossPhases` réservées au rang
      boss (`MonsterTierRankTest`) ; recalibrages couverts par les tests des
      consommateurs

### BES-02 — Les stats dérivées du gabarit (M | ★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> Comme les filons ont des profils de palier et les matéria une dérivation depuis
> le sort, les stats d'un monstre se dérivent de sa case `tier × rank`.
- [x] Grille `life` de départ (GAME_BESTIARY §3) : 30/90/250 en T1 jusqu'à
      300/850/2400 en T4 — portée par `MonsterStatTemplate`, source unique
      (le rapport d'équilibrage la lit aussi). 61 monstres sur 65 dérivent
- [x] `hit` = 70 + 5 × palier, +5 pour `Elite` et `Boss` ; `speed` reste
      d'abord un **trait d'espèce** (la chauve-souris file, le zombie traîne) :
      la valeur déclarée est l'écart explicite, le gabarit sert de repli.
      Le nombre de sorts reste des données — aucune dérivation forcée
- [x] **L'écart au gabarit reste permis, mais explicite et commenté** — quatre
      écarts inscrits dans une liste fermée : les deux mannequins (valeurs
      pédagogiques ONB-11), le Gardien de la Forêt (boss de zone affronté en
      groupe, 400 PV) et le Premier Silence (l'ultime rencontre, 3200 PV)
- [x] Tests : dérivation obligatoire hors liste fermée, écarts commentés dans
      la fixture, aucun saut de ×4 entre paliers ni entre rangs
      (`MonsterStatTemplateTest`, `MonsterStatDerivationTest`)

### BES-04 — La faille du milieu et la Crête (S | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> Le monde se coupait en deux : départ 1-24, fin 26-38, avec un saut de vie de ×4
> et pour seul pont le Marais, qui n'existait que par le legacy.
- [x] Les 12 cases `palier × rang` sont remplies **par redistribution, aucun
      monstre créé** : T1 14/3/4, T2 9/6/2, T3 6/4/1, T4 6/4/1 — l'ochu, la
      sylphide et le grand cerf de l'aubépine deviennent les élites du T1 ;
      l'hydre devient le **boss du Marais** (le T2 n'avait aucun boss en
      zone) ; la harpie, l'ombre de givre et le basilic redeviennent le
      tout-venant du T4 (leurs paliers ont déjà la menace)
- [x] **La Crête** : la gargouille quitte les Mines pour la Crête seule
      (T3) ; la salamandre et le scorpion retournent au désert (T3) — en
      forêt ils doublonnaient le tout-venant du T1. Les stats suivent toutes
      seules : elles se dérivent du gabarit (BES-02)
- [x] Aucune espèce inaccessible (tenu par `FaunaSingleSourceTest`, BES-03),
      hors mannequins et boss narratifs réservés
- [x] Tests : `MonsterTierCoverageTest` — chaque palier T1-T4 porte au moins
      6 communs, 3 élites et 1 boss **atteignables** (comptés sur les
      placements réels, jamais sur le seul catalogue)

---

## Piste B — Le peuplement et le ménage

### BES-03 — La bascule du peuplement dans `zones.yaml` (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> 53 mobs déclarés en YAML, 116 dans `MobFixtures` (par coordonnées et carte,
> vestige d'avant le pivot ZON-21). Doctrine ZON-11 : « ajouter du contenu =
> ajouter de la donnée ici, pas du code ».
- [x] Remonter dans `zones.yaml` les espèces que seul le legacy plaçait — les
      restes ZON-26b avaient déjà déclaré le Marais et la Crête (donc le gros
      des 17) ; restaient **goblin et taiju** (la Forêt, T1) et **loup-garou
      et nécromancien** (la nuit du Marais, T2)
- [x] Le Marais et la Crête avaient déjà leur bloc `mobs:` (restes ZON-26b) ;
      le Quartier des Jardins étant T0, il reste **sans faune hostile**
- [x] Mobs de donjon préservés dans `DungeonMobFixtures` (les 4 des Racines
      de la forêt), hors graphe — et une garde : cette fixture n'admet que
      des cartes `map_dungeon_*`
- [x] `MobFixtures` **supprimé** (91 mobs par coordonnées, dont 87 sur la
      carte de test hors graphe) ; les tests d'intégration de combat cessent
      de chercher leurs mobs sur la carte du joueur
- [x] Tests : `FaunaSingleSourceTest` — le peuplement par coordonnées a
      disparu, la fixture de donjon ne place que sur des cartes de donjon,
      aucune espèce perdue (hors mannequins et boss narratifs réservés),
      et aucune zone ne place une espèce inexistante

### BES-05 — Le ménage du code mort (S | ★★ | MOYENNE) — ✅ LIVRÉ 2026-08-02
- [x] `HitChanceCalculator` supprimé (avec son test) : sa formule
      `spell.hit + (spell.level − target.level) × 2` rendait un monstre de
      haut niveau intouchable, mais **elle n'était appelée nulle part** — le
      calcul réel est `FightCalculator::hasAttackHit()`. Un piège de lecture,
      pas un bug d'équilibrage — et depuis BES-01, `target.level` n'existait
      même plus
- [x] Le balayage a trouvé **deux orphelins de plus** : `PlayerActionHandler`
      (l'orchestrateur à `AutowireIterator`, référencé nulle part — les
      contrôleurs de combat appellent leurs chemins directement) et
      `FightChecker`, que lui seul consommait. Supprimés tous les deux ;
      `MobDeathQueuing` a été épargné (subscriber auto-branché)
- [x] Le chemin de touche réel reste couvert : `FightCalculator::hasAttackHit`
      garde ses deux appelants (attaque de mob, sort de joueur) et ses tests
- [x] Les docs cessent de mentir : `DOCUMENTATION.md`, `GAME_DOMAINS` §
      stats et `PLAN_ARCHETYPES` pointent désormais le vrai calculateur

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
