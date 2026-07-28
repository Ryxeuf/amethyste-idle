# Plan — Contenu des zones du jeu de base

> **Numérotation :** les jalons de **ce** document sont préfixés **ZON-** et reprennent la
> série existante à **ZON-30** (la campagne ZON-12→27 du pivot PBBG est close, hors
> ZON-26b). Pas de conflit avec GCC- / ECO- / NAR- / FOY- / RET-.

> Décline [../GAME_ZONES.md](../GAME_ZONES.md) (définitions actées le 2026-07-28) en
> jalons. Tout est de la **donnée** (YAML de zone, fixtures d'items, monstres) : aucun
> moteur neuf. Chaque jalon respecte la règle 8 de CLAUDE.md (jamais plus de ~200 lignes
> de données par passe).

## Vue d'ensemble

**7 jalons** (**ZON-30** à **ZON-36**), plus trois chantiers **référencés** qui vivent
dans leurs plans d'origine :

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| ZON-30 | Les Vallons d'Aubépine (zone neuve) | M | ∅ |
| ZON-31 | Les Dunes d'Ambre approfondies (ambre, os, gibier) | M | ∅ |
| ZON-32 | Signatures d'améthyste par zone (config) | S | ← ECO-21, ECO-22 |
| ZON-33 | Tests de conformité aux lois de zone | S | ‖ au fil des jalons |
| ZON-34 | La ligne du bois (domaine, essences, recettes) | M | → ECO-30 (charpentier) |
| ZON-35 | Harmonisation des récoltes (loi 9) | S | ← ECO-29 pour les épices |
| ZON-36 | Affinités élémentaires des ressources (loi 10) | S | ∅ (donnée pure) |
| ZON-37 | La régénération d'un filon devient un débit ✅ | M | ∅ — **prérequis du recalibrage** |

**Référencés, à exécuter dans leurs plans** :
- **ECO-24b** (PLAN_PLAYER_ECONOMY) — pose les filons de haut palier **selon la carte des
  minerais de GAME_ZONES §3** (sombracier → Mines, mithril → Crête, platine + 2e étain →
  Dunes, orichalque → Cité ; adamantite, métal étoilé, voidium réservés aux extensions).
- **ZON-26b** (Sprint 13) — populations déclarées du **Marais** et de la **Crête**, seules
  zones encore dépendantes de leur carte TMX.
- **Recalibrage global** (BALANCE §22.3) — s'applique aux filons neufs comme aux anciens.

```
ZON-30 → ZON-31 (indépendants, dans cet ordre de valeur)
ECO-21/22 → ZON-32
ZON-33 en continu
```

---

### ZON-30 — Les Vallons d'Aubépine (M | ★★★ | HAUTE)
> La seule zone neuve du jeu de base (GAME_ZONES §2.2). Comble le trou entre le hub et la
> Forêt, et donne au **dépeçage** son terrain d'apprentissage — la 4e ligne de récolte
> exigée à portée du hub (GAME_PROGRESSION §6a).
> Prérequis : ∅
- [ ] Items : `plant-wheat` (T0), `plant-flax` (T1 — **exclusivité de la zone**),
      `fish-perch` (T1) dans `fixtures/game/item/`
- [ ] Monstres : sanglier, cerf (gibier T1 dépeçable → `leather-raw`), renard ; réutiliser
      le loup existant en lisière ; **aucun nocturne agressif** (contraste voulu avec la
      Forêt)
- [ ] Zone `vallons-d-aubepine` dans `world_1.yaml` : type wilderness, région Plaines de
      l'Éveil, filons aux profils T0/T1 du calibrage (jamais un goulot), faune déclarée,
      2-3 PNJ de fond (fermière, meunier…)
- [ ] Connexions : Lumière ↔ Vallons (court), Vallons ↔ Forêt (court), Vallons ↔ Marais
      (moyen) ; position sur la carte du monde illustrée
- [ ] Une première recette de cuisine joueur consommant le blé (la chaîne pain cesse
      d'être PNJ-only) et une recette de tannerie consommant le lin
- [ ] Tests : import de zone, filons, loi transverse « chaque item de recette a une
      source »

### ZON-31 — Les Dunes d'Ambre approfondies (M | ★★ | HAUTE)
> La zone la plus pauvre du monde livré (un filon, quatre monstres) reçoit ses
> exclusivités (GAME_ZONES §2.7) : l'« Ambre » de la région devient une matière.
> Prérequis : ∅ (le platine et le 2e étain relèvent d'ECO-24b)
- [ ] Items : `ambre fossile` (réactif d'enchantement/joaillerie — **exclusivité**),
      `os` (l'autre moitié de la ligne du tanneur, si l'existant `leather-bone` ne
      suffit pas — auditer avant de créer)
- [ ] Filon d'ambre fossile (T3) + récolte d'os portée par la faune (dépeçage, pas de
      filon)
- [ ] Faune densifiée en gibier à os (2-3 espèces désertiques dépeçables)
- [ ] Au moins une recette consommant l'ambre fossile et une consommant l'os (une
      exclusivité sans débouché est un mensonge de level design)
- [ ] Tests : sources, débouchés, loi transverse

### ZON-32 — Signatures d'améthyste par zone (S | ★★ | MOYENNE)
> Traduit en config les tendances actées (GAME_ZONES §2, colonne « signature ») pour le
> tirage de pureté. **Une seule table déclarative**, pas de logique par zone.
> Prérequis : ← ECO-21 (bandes), ECO-22 (tirage)
- [ ] Table par zone : quantité relative (modificateur de taux de sous-produit) ×
      fourchette de bande × élément dominant — dans la config de zone, pas en dur
- [ ] **Lumière + Jardins : zéro améthyste** (canon §2.1 — la Voûte) ; cas testé
      explicitement
- [ ] Variations consignées : le Marais tire plus haut la nuit ; les Dunes et la Cité
      rendent par le butin, pas par la récolte
- [ ] Tests : signature appliquée au tirage, cas Lumière, cas nocturne du Marais

### ZON-33 — Tests de conformité aux lois de zone (S | ★★ | HAUTE)
> Les huit lois de GAME_ZONES §0 valent contrat. ‖ au fil des jalons.
- [ ] Loi 1 testée : chaque zone du monde de base a au moins une source exclusive
      (item ou monstre introuvable ailleurs)
- [ ] Loi 2 testée sur la ligne du métal : nombre de sources décroissant avec le palier
- [ ] Loi transverse re-vérifiée après chaque jalon : aucun item de recette sans source
- [ ] Le graphe reste connexe et la liaison Dunes → Cité existe tant que la Mer de Sel
      n'a pas rejoint l'Extension 1 (GAME_ZONES §1, note de graphe)

### ZON-34 — La ligne du bois (M | ★★★ | HAUTE)
> Décidé le 2026-07-28 (GAME_ZONES §3 bis) : la récolte du bois devient la cinquième
> récolte. Aujourd'hui l'arc et le bâton existent en items sans recette productrice, et
> aucune ressource bois n'existe — la ligne armes de bois + housing est sans matière.
> Le métier consommateur est **tranché** : le charpentier (ECO-30, Piste H de
> PLAN_PLAYER_ECONOMY), qui prend ce jalon en prérequis.
- [ ] Domaine `lumberjack` (Bûcheron) dans `DomainFixtures` + arbre de récolte (mêmes
      gabarits que mineur/herboriste)
- [ ] Items : 4 essences de la carte du bois (hêtre T0, chêne murmurant T2, bois tourbé
      T3, bois pétrifié T4)
- [ ] Filons `profession: woodcutting` aux zones de la carte (Vallons + Forêt pour le
      hêtre ; Forêt, Marais, Dunes pour les exclusifs), profils de palier standard
- [ ] Recettes : l'arc et le bâton existants gagnent une recette productrice ; au moins
      une recette par essence (une exclusivité sans débouché est un mensonge)
- [ ] Tests : sources, débouchés, loi transverse

### ZON-35 — Harmonisation des récoltes (S | ★★ | MOYENNE)
> Applique la loi 9 (GAME_ZONES §3 ter) : le compte d'un domaine suit les artisanats
> qu'il nourrit, échelle T0→T4 sans trou, tout a un débouché.
> Prérequis : ← ECO-29 (le cuisinier absorbe les herbes banales en épices)
- [ ] Purger les 5 plantes mortes (`dreamlily`, `sunblossom`, `thunderroot`,
      `whisperweed`, `wolfsbane`) — ou en réaffecter au plus 2 si un besoin réel existe
- [ ] Raccorder les 7 plantes sans débouché : banales → épices de cuisine (ECO-29),
      rares (spores, givrecoiffe, fruit du vide) → intrants d'alchimie haute
- [ ] Réveiller le **poisson-lune** en T4 : pêche nocturne rare du Marais (profil T4 du
      calibrage, tirage restreint à la nuit) ; purger `fish-baby-kraken`
- [ ] Vérifier l'échelle T0→T4 de chaque domaine après ZON-30/31/34 (le trou T1 du
      dépeçage se comble par le gibier des Vallons, celui du bois par le hêtre T0–T1)
- [ ] Tests : invariants de la loi 9 (échelle, débouché, plancher hub) par domaine

### ZON-36 — Affinités élémentaires des ressources (S | ★★ | MOYENNE)
> Applique la loi 10 (GAME_ZONES §3 ter, GAME_WORLD §2.2) : chaque ressource porte une
> affinité de flux, dérivée de la signature de sa zone source. **Donnée pure** — aucun
> système consommateur à construire ici.
> Prérequis : ∅
- [ ] Champ d'affinité déclaratif sur les items de ressource (fixtures YAML/PHP — même
      enum `Element` que les domaines, nullable : `null` = améthyste/hors périmètre)
- [ ] Application de la règle de dérivation (ligne par défaut, signature de zone en
      correction) sur toutes les ressources du jeu de base — table canonique de
      GAME_ZONES §3 ter
- [ ] L'améthyste reste **sans affinité** (substrat, canon §2.2) — cas testé
- [ ] Documentation : quels futurs systèmes liront la donnée (craft, cuisine, fusion,
      lectures) — pointeurs, pas d'implémentation
- [ ] Tests : toute ressource de récolte a une affinité ou un `null` justifié

---

## Risques

| Risque | Parade |
|---|---|
| Les Vallons cannibalisent la Forêt (deux zones T1 côte à côte) | Exclusivités disjointes : lin/blé/gibier contre herbes/ginseng/saumon — aucun item commun hors plancher |
| L'ambre fossile ou le lin sans débouché → exclusivité morte | Un débouché de recette est un critère d'acceptance des deux jalons |
| Les filons neufs posés à la main hors profils | Interdit — profils de palier de `world_1.yaml` uniquement (GAME_ZONES §6) |
| ECO-24b exécuté sans la carte des minerais | La carte (GAME_ZONES §3) est référencée comme entrée du jalon dans PLAN_PLAYER_ECONOMY |
