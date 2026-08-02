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

### DON-02 — Le combat rend le build pertinent (M | ★★★ | HAUTE)
> Aujourd'hui `damage = max(1, $player->getHit())` : ni arme, ni sort, ni
> matéria, ni équipement n'entrent dans le calcul. Et la rencontre ne riposte
> jamais — **un donjon ne peut pas être perdu**.
> Prérequis : ← DON-01
- [ ] L'action d'un membre devient **son action réelle** : attaque de base de son
      arme, ou sort d'une matéria sertie (`CombatCapacityResolver`)
- [ ] **La rencontre riposte** : elle frappe un membre à chaque tour selon ses
      stats ; ajouter `STATUS_FAILED`, atteint quand tous les membres sont à terre
- [ ] Conserver la boucle semi-synchrone qui marche : `turnOrder`,
      `turnDeadline`, action par défaut quand l'échéance passe
- [ ] Tests : le build modifie le dégât, la riposte s'applique, l'échec est
      atteignable

### DON-03 — Les étapes et les vraies rencontres (M | ★★ | HAUTE)
> `currentStep` existe sur l'entité et **n'est jamais avancé** ; la rencontre est
> un sac de PV abstrait sans `Monster`, sans élément, sans IA.
> Prérequis : ← DON-02, **BES-01** (le palier et le rang)
- [ ] Trois étapes par donjon : `Common` → `Elite` → `Boss` **du palier de la
      zone** — le donjon ne définit pas ses créatures, il **puise dans la faune
      de son palier**
- [ ] `currentStep` avance réellement ; un donjon T4 se peuple tout seul le jour
      où le palier T4 est peuplé
- [ ] Tests : trois étapes franchies, rencontres au bon palier, aucun sac de PV

### DON-04 — Le butin, et `lootPreview` qui ne ment plus (S | ★★★ | HAUTE)
> `lootPreview` promet « Équipement tier 2, Matéria commune, Composants
> d'artisanat » ; le run ne distribue que des gils.
> Prérequis : ← DON-03 ; **converge avec MAT-06**
- [ ] Table de butin indexée sur le palier : T1 → m2, T2 → m3, T3 → m3-m4,
      **T4 → m4-m5** — c'est ce que `GAME_MATERIA` §4.3 attend des donjons
- [ ] `lootPreview` **dérivé de la table réelle**, jamais un texte libre : un
      aperçu qui ment est pire que pas d'aperçu
- [ ] La décroissance de récompense (ZON-20) s'étend **à la table d'objets**, pas
      seulement aux gils
- [ ] Tests : `lootPreview` == table réelle, paliers de matéria respectés,
      décroissance appliquée aux objets

---

## Piste B — La couverture

### DON-05 — Un donjon par palier (S | ★★ | MOYENNE)
> 4 donjons pour 12 zones, les deux de groupe rattachés aux deux zones qui
> portent déjà tout le contenu.
> Prérequis : ← DON-01
- [ ] **Fusionner `racines-de-la-foret` dans `galeries-envahies`** : les deux
      racontent la même chose au même endroit (« sous les racines de la forêt »),
      et garder les deux doublerait le T1 en laissant trois paliers à découvert
- [ ] Répartition cible — T1 Forêt (existant), T2 Mines (existant), **T3 Crête de
      Ventombre ou Dunes d'Ambre (à écrire)**, T4 Cité ensevelie (le Nexus
      converti)
- [ ] **La Cité ensevelie était déjà prévue pour ça** : `GAME_ZONES` §2 la
      déclare « T4 — donjon » et §3 y place l'orichalque comme « butin de donjon
      plus que filon »
- [ ] Coût net : **un donjon à écrire**, celui du T3 — le palier dont la Crête a
      justement besoin (cf. GAME_BESTIARY §1.3)
- [ ] Tests : un donjon par palier T1-T4, quatre zones distinctes

---

## Piste — Le contrat

### DON-06 — Tests du plan (S | ★★★ | HAUTE)
> ‖ au fil des jalons. Les 8 invariants de GAME_DUNGEONS §6.
- [ ] Un seul modèle ; tout donjon est complétable
- [ ] Toute rencontre est un vrai monstre au palier de sa zone
- [ ] Le build compte ; un donjon peut être perdu
- [ ] `lootPreview` ne ment pas ; un donjon par palier ; seuil calculé en un point

---

## Ce que ce plan ne touche pas

- **La récompense décroissante** (ZON-20) : bonne décision, conservée. Pas de
  lockout dur, le joueur peut toujours rejouer.
- **L'entrée gratuite en énergie** : le donjon reste le réservoir de contenu
  gratuit du modèle PBBG.
- **Les donjons d'extension** (le Silence, modèle expédition — `GAME_ZONES`) :
  hors périmètre de la base.
