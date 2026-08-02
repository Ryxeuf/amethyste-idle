# Plan — Les donjons

> **Numérotation :** jalons préfixés **DON-**. Pas de conflit avec les autres préfixes.

> Décline [../GAME_DUNGEONS.md](../GAME_DUNGEONS.md). Constat chiffré :
> [../GAME_DATA_AUDIT.md](../GAME_DATA_AUDIT.md) §9.
>
> **Pourquoi maintenant.** Les deux donjons solo sont **injouables** (ils
> reposent sur la mécanique de carte supprimée par ZON-21) et les deux donjons de
> groupe sont **vides** (une rencontre abstraite, sans riposte, où le build du
> joueur ne sert à rien). Le Nexus de la Convergence — la fin de l'arc narratif,
> qui exige les 4 fragments — n'a aucun monstre.
>
> **Dépendances.** DON-04 (le butin) est ce que **MAT-06** attend des donjons :
> ils sont le canal des matéria m4-m5. DON-03 (les rencontres) suppose **BES-01**
> livré, puisqu'une rencontre puise dans la faune de son palier.

## Vue d'ensemble

**6 jalons** (**DON-01** à **DON-06**) en 2 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| DON-01 | Un seul modèle : le donjon de zone | M | ∅ |
| DON-02 | Le combat rend le build pertinent | M | ← DON-01 |
| DON-03 | Les étapes et les vraies rencontres | M | ← DON-02, BES-01 |
| DON-04 | Le butin, et `lootPreview` qui ne ment plus | S | ← DON-03 ; ‖ MAT-06 |
| DON-05 | Un donjon par palier | S | ← DON-01 |
| DON-06 | Tests du plan | S | ‖ |

```
Piste A — Le modèle  : DON-01 → DON-02 → DON-03 → DON-04
Piste B — Le contenu : DON-01 → DON-05 ;  DON-06 ‖
```

**Ordre conseillé.** DON-01 est le pivot : tant que les deux modèles coexistent,
tout le reste se fait en double.

---

## Piste A — Le modèle et le combat

### DON-01 — Un seul modèle : le donjon de zone (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02 (en deux moitiés, règle 8)
> Les donjons solo reposent sur la carte navigable supprimée par ZON-21 : entrer
> téléporte en `1.1` sur une `Map`, les mobs de donjon n'ont **aucune zone** donc
> `ExploreService` ne les trouve jamais, et l'écran n'offre aucune action.
> Prérequis : ∅
- [x] `racines-de-la-foret` (→ Forêt des murmures) et `nexus-de-la-convergence`
      (→ Crête de Ventombre, la zone du fragment du Sommet) convertis au modèle
      de zone avec `maxPlayers: 1` — le solo passe par la même mécanique : un
      donjon à `maxPlayers: 1` se lance **seul, sans party**, l'écran de zone
      les propose (le filtre `maxPlayers > 1` de `findOfferedInZone` tombe), et
      la garde `not_group` disparaît avec sa clé i18n
- [x] **DON-01b** : le chemin solo mort supprimé — `DungeonRun` (entité,
      repository, table via migration), la téléportation, l'écran
      `/game/dungeon` (contrôleur + templates), `DungeonCompletionListener`,
      `DungeonCompletedEvent` et la difficulté solo (`DungeonDifficulty`, avec
      le succès Mythique devenu improgressable). `DungeonManager` aminci aux
      deux vérifications de prérequis que la voie unique consomme. **Rétrofit** :
      un `GroupDungeonCompletedEvent` par membre, émis à la distribution des
      récompenses — succès `dungeon_clear` et journal suivent la voie unique
- [x] Le calcul `× 100` centralisé : `Dungeon::getRequiredExperience()`
      remplace les trois recalculs (`DungeonManager` ×2, `ZoneController`)
- [x] Tests : aucun donjon sans zone, seuil calculé en un seul endroit, le
      chemin solo mort le reste (`DungeonModelTest`) ; lancement solo accepté
      seul, party de 2 refusée dans un donjon solo (`GroupDungeonServiceTest`)

### DON-02 — Le combat rend le build pertinent (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> Aujourd'hui `damage = max(1, $player->getHit())` : ni arme, ni sort, ni
> matéria, ni équipement n'entrent dans le calcul. Et la rencontre ne riposte
> jamais — **un donjon ne peut pas être perdu**.
> Prérequis : ← DON-01
>
> **Séquencement DON ↔ ARC tranché le 2026-08-02 : DON d'abord, pont assumé.**
> MAT étant livré (8/8), les sorts de matéria serties existent réellement :
> DON-02 branche les **vraies actions de sort** dès maintenant. Les builds de
> mêlée utilisent l'attaque de base jusqu'à **ARC-02** (« le geste d'arme
> devient une matéria ») — dette documentée, sur le modèle d'OBJ-04 pour le
> typage Technique. On ne réécrira pas le combat : ARC-02 ajoutera des actions
> au même résolveur, il ne changera pas le modèle.
- [x] L'action d'un membre est **son action réelle** (`DungeonActionResolver`) :
      le geste de l'arme équipée (le `spell` de la pièce) + les passifs de
      dégâts des arbres, ou un sort de matéria sertie
      (`CombatCapacityResolver`) avec le bonus d'accord d'élément — un sort
      verrouillé retombe sur l'attaque de base, mains nues = 1 (ONB-20a). Le
      contrôleur accepte un paramètre `spell`
- [x] **La rencontre riposte** : elle frappe le membre qui vient d'agir
      (`zone.dungeon.encounter_hit`, défaut 10 — les vraies stats arrivent
      avec les monstres de DON-03) ; `STATUS_FAILED` atteint quand tous les
      membres sont à terre, le tour saute les membres couchés, et un membre
      tombé hors donjon ne peut pas agir. **Recalibrage** :
      `encounter_hp_per_member` 200 → 120 (GAME_ARCHETYPES §7 bis — 200 était
      le réglage d'une rencontre sans riposte)
- [x] La boucle semi-synchrone conservée : `turnOrder`, `turnDeadline`, action
      par défaut à l'échéance — l'action par défaut est désormais la vraie
      attaque de base du build
- [x] Tests : le build modifie le dégât, la riposte s'applique, l'échec est
      atteignable, les membres à terre sont sautés
      (`GroupDungeonCombatServiceTest`, `DungeonActionResolverTest`)

### DON-03 — Les étapes et les vraies rencontres (M | ★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> `currentStep` existe sur l'entité et **n'est jamais avancé** ; la rencontre est
> un sac de PV abstrait sans `Monster`, sans élément, sans IA.
> Prérequis : ← DON-02, **BES-01** (le palier et le rang)
- [x] Trois étapes par donjon : `Common` → `Elite` → `Boss` **du palier de la
      zone** — `DungeonEncounterPicker` tire dans la faune **réellement
      placée** du palier (les espèces qu'un `Mob` zoné incarne, même
      définition que MAT-08), ce qui écarte d'office mannequins et boss
      narratifs réservés sans liste à entretenir ; repli sur les espèces
      livrées du palier, puis sur les curseurs historiques — un donjon ne
      refuse jamais de s'ouvrir
- [x] `currentStep` avance réellement (la rencontre tombée ouvre l'étape
      suivante, seule la chute du boss termine le run) ; un donjon T4 se
      peuple tout seul le jour où le palier T4 est peuplé
      (`DungeonFaunaCoverageTest` : les 4 paliers × 3 rangs servis)
- [x] La rencontre incarne son monstre : la barre est sa vie × la taille du
      groupe, la riposte est **son** coup (une élite frappe plus fort qu'un
      commun sans réglage spécial), l'écran dit qui l'on affronte (étape,
      nom — Twig + flux Mercure)
- [x] Tests : trois étapes franchies, rencontres au bon palier, aucun sac de
      PV, repli sans faune, la chute d'étape ne riposte pas

### DON-04 — Le butin, et `lootPreview` qui ne ment plus (S | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> `lootPreview` promet « Équipement tier 2, Matéria commune, Composants
> d'artisanat » ; le run ne distribue que des gils.
> Prérequis : ← DON-03 ; **converge avec MAT-06**
- [x] Table de butin indexée sur le palier : T1 → m2, T2 → m3, T3 → m3-m4,
      **T4 → m4-m5** (`MateriaLootTable::dungeonPick(tier)`) — le donjon T4
      reste le seul canal du m5, et l'invariant d'obtenabilité lit désormais
      les paliers des donjons réels (d'où DON-05 avancé avant ce jalon)
- [x] `lootPreview` **dérivé de la table réelle** : la colonne texte libre est
      supprimée (entité, fixtures, migration), l'écran de zone dérive
      l'aperçu de `dungeonPaliers()` — la même lecture que le tirage, donc
      impossible à désynchroniser
- [x] La décroissance de récompense (ZON-20) s'étend **à la table d'objets** :
      la chance de matéria suit `decay^n` **sans plancher** (les gils gardent
      le leur — prix de la participation, le sommet du catalogue n'en a pas)
- [x] Tests : aperçu == table réelle (même fonction), paliers respectés par
      palier de zone, montée en rare aux seuls T3-T4, décroissance des objets
      sans plancher

---

## Piste B — La couverture

### DON-05 — Un donjon par palier (S | ★★ | MOYENNE) — ✅ LIVRÉ 2026-08-02
> 4 donjons pour 12 zones, les deux de groupe rattachés aux deux zones qui
> portent déjà tout le contenu. **Avancé avant DON-04** : la table de butin
> indexée sur le palier exige un donjon T4, sans quoi m5 perdrait son canal
> (invariant 4 de GAME_MATERIA).
> Prérequis : ← DON-01
- [x] **`racines-de-la-foret` a fusionné dans `galeries-envahies`** : les deux
      racontaient la même chose au même endroit, et garder les deux doublait
      le T1 en laissant trois paliers à découvert. La carte de donjon reste
      (support de `DungeonMobFixtures`), le donjon disparaît
- [x] Répartition livrée — T1 Forêt (`galeries-envahies`), T2 Mines
      (`forges-noyees`), **T3 Crête de Ventombre : « Le Nid des rafales »**
      (le seul donjon écrit — groupe de 4, palier dont la Crête avait besoin),
      T4 Cité ensevelie (**le Nexus converti** — la fin de l'arc se mérite au
      bout du monde, et GAME_ZONES §2 déclarait déjà la Cité « T4 — donjon »)
- [x] Tests : un donjon par palier T1-T4, quatre zones distinctes, la fusion
      tient (`DungeonModelTest`)

---

## Piste — Le contrat

### DON-06 — Tests du plan (S | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02 — **plan donjons complet 6/6**
> ‖ au fil des jalons. Les 8 invariants de GAME_DUNGEONS §6, tenus par
> `DungeonsPlanContractTest` (l'index du contrat + le garde-fou qui manquait)
> et les tests nés des jalons.
- [x] Un seul modèle ; le chemin solo mort le reste (`DungeonModelTest`) ;
      tout donjon est complétable (`GroupDungeonCombatServiceTest`,
      `DungeonFaunaCoverageTest`)
- [x] Toute rencontre est un vrai monstre au palier de sa zone (DON-03)
- [x] Le build compte (`DungeonActionResolverTest`) ; un donjon peut être
      perdu (`STATUS_FAILED` atteint)
- [x] `lootPreview` ne ment pas — **le texte libre ne revient pas** (garde-fou
      porté par l'index : aucun `set/getLootPreview` dans `src/`, et l'écran
      dérive toujours de `dungeonPaliers`) ; un donjon par palier ; seuil
      calculé en un point (`DungeonModelTest`)

---

## Ce que ce plan ne touche pas

- **La récompense décroissante** (ZON-20) : bonne décision, conservée. Pas de
  lockout dur, le joueur peut toujours rejouer.
- **L'entrée gratuite en énergie** : le donjon reste le réservoir de contenu
  gratuit du modèle PBBG.
- **Les donjons d'extension** (le Silence, modèle expédition — `GAME_ZONES`) :
  hors périmètre de la base.
