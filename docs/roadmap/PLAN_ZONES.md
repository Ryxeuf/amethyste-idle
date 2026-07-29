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
| ZON-30 ✅ | Les Vallons d'Aubépine (zone neuve) | M | ∅ |
| ZON-31 ✅ | Les Dunes d'Ambre approfondies (ambre, os, gibier) | M | ∅ |
| ZON-32 ✅ | Signatures d'améthyste par zone (config) | S | ← ECO-21, ECO-22 |
| ZON-33 | Tests de conformité aux lois de zone | S | ‖ au fil des jalons |
| ZON-34 ✅ | La ligne du bois (domaine, essences, filons) | M | → ECO-30 (charpentier) |
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
ZON-30 ✅ → ZON-31 ✅ (indépendants, dans cet ordre de valeur)
ECO-21/22 → ZON-32 ✅
ZON-33 en continu
```

---

### ZON-30 — Les Vallons d'Aubépine ✅ (M | ★★★ | HAUTE)
> La seule zone neuve du jeu de base (GAME_ZONES §2.2). Comble le trou entre le hub et la
> Forêt, et donne au **dépeçage** son terrain d'apprentissage.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Deux items de plus que prévu, et pourquoi.** `meat-game` et `feather-raw` n'étaient pas
> dans la liste, mais la faune actée les produit (viande → cuisinier, plumes → flèches). Les
> poser maintenant est délibéré : la loi « chaque item de recette a une source » se tient plus
> facilement quand la source précède la recette que l'inverse — ECO-29 et ECO-30 trouveront
> leurs intrants déjà sourcés.
>
> **Reporté à ZON-30b, avec sa raison** : les deux recettes joueur (cuisine du blé, tannerie
> du lin). Elles supposent des arbres de métier — le cuisinier n'existe pas encore (ECO-29), et
> greffer une recette de pain sur l'alchimiste pour tenir une case aurait créé exactement le
> genre de rattachement qu'il faudrait défaire ensuite.
>
> **Écarté** : `zone_line` pour les Vallons. La ligne agricole n'a pas d'atelier en face, comme
> `wood` et `amethyst` attendent les leurs. Le fichier prévoit ce cas explicitement — une zone
> absente de `zone_line` n'apporte que son rang.

### ZON-31 — Les Dunes d'Ambre approfondies ✅ (M | ★★ | HAUTE)
> La zone la plus pauvre du monde livré (un filon, quatre monstres) reçoit ses
> exclusivités (GAME_ZONES §2.7) : l'« Ambre » de la région devient une matière.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **L'audit demandé a tranché contre la création.** `leather-bone` existe, a quatre recettes
> consommatrices et des sources (squelettes, morts-vivants, grandes créatures). Il ne manquait
> pas un item : il manquait une **faune du désert qui en rende**. Créer un second os aurait
> dédoublé une ligne déjà servie — exactement le défaut qu'ECO-02 avait eu à défaire pour
> `leather-skin-1/2`.
>
> **Une seule recette au lieu de deux**, et c'est la conséquence directe de l'audit : l'os a
> déjà ses débouchés, seul l'ambre en manquait. Le sceau d'ambre (joaillier niveau 4) consomme
> une gemme **taillée**, pour que la recette morde sur un produit d'artisanat comme la loi
> d'ECO-27 l'exige au-delà du niveau 3.

### ZON-32 — Signatures d'améthyste par zone ✅ (S | ★★ | MOYENNE)
> Traduit en config les tendances actées (GAME_ZONES §2, colonne « signature ») pour le
> tirage de pureté. **Une seule table déclarative**, pas de logique par zone.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> **Une colonne sur trois livrée, et c'est délibéré.** La table actée en compte trois —
> quantité, bande, élément. Seule la **bande** a un consommateur aujourd'hui (le tirage
> d'ECO-22). La *quantité relative* suppose un sous-produit d'améthyste à la récolte qui
> n'existe pas, et l'*élément dominant* est le sujet de ZON-36. Les déclarer maintenant
> aurait posé deux paramètres que personne ne lit — exactement ce que le contrat du pilier
> territorial (FOY-16) interdit désormais.
>
> **Le signe déplace les poids, jamais le plafond.** « Trouble dominante » et « Pure
> fréquente » décrivent une distribution, pas une borne. Un signe qui aurait touché le
> plafond aurait laissé la Crête rendre du parfait sur un filon éreinté — effaçant d'un
> seul geste la vitalité (ZON-37), la Pâleur (FOY-11) et l'Affleurement (RET-06).
>
> **Le cas du Fanal n'est pas une signature à zéro** — ce serait dire « peu ». C'est
> l'absence de tout filon du périmètre de pureté, et c'est ce que le test verrouille.

### ZON-33 — Tests de conformité aux lois de zone (S | ★★ | HAUTE)
> Les huit lois de GAME_ZONES §0 valent contrat. ‖ au fil des jalons.
- [ ] Loi 1 testée : chaque zone du monde de base a au moins une source exclusive
      (item ou monstre introuvable ailleurs)
- [ ] Loi 2 testée sur la ligne du métal : nombre de sources décroissant avec le palier
- [ ] Loi transverse re-vérifiée après chaque jalon : aucun item de recette sans source
- [ ] Le graphe reste connexe et la liaison Dunes → Cité existe tant que la Mer de Sel
      n'a pas rejoint l'Extension 1 (GAME_ZONES §1, note de graphe)

### ZON-34 — La ligne du bois ✅ (M | ★★★ | HAUTE)
> Décidé le 2026-07-28 (GAME_ZONES §3 bis) : la récolte du bois devient la cinquième
> récolte. Aujourd'hui l'arc et le bâton existent en items sans recette productrice, et
> aucune ressource bois n'existe — la ligne armes de bois + housing est sans matière.
> Le métier consommateur est **tranché** : le charpentier (ECO-30, Piste H de
> PLAN_PLAYER_ECONOMY), qui prend ce jalon en prérequis.
**Livré le 2026-07-29** (la matière). Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] Domaine `lumberjack` (Bûcheron) + arbre de récolte, 8 nœuds
- [x] Items : 4 essences (hêtre T0, chêne murmurant T2, bois tourbé T3, bois pétrifié T4)
- [x] Filons `profession: woodcutting` — Vallons + Forêt pour le hêtre, Forêt/Marais/Dunes
      pour les exclusifs, profils de palier standard, gate de compétence sur les trois
      exclusivités
- [ ] **Recettes — reporté à ECO-30 (charpentier).** Elles n'appartiennent à personne
      aujourd'hui : le charpentier est le métier *tranché* de cette ligne, et greffer une
      recette d'arc sur le forgeron pour tenir la case aurait créé un rattachement à
      défaire ensuite. Même arbitrage qu'aux Vallons (ZON-30).
- [x] Tests : sources, paliers, gates, loi des biomes sans arbres

> **Écart assumé : pas d'emplacement de hache.** Les quatre autres arbres de récolte
> ouvrent un outil ; celui-ci non. La hache demande un type d'outil, un bit d'équipement
> et un emplacement d'interface neufs — un changement de **mécanisme**, pas de données.
> Elle arrivera avec le charpentier, à qui elle sert. Livrer l'arbre sans elle donne la
> matière tout de suite ; poser la hache d'abord aurait donné un outil sans rien à couper.

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
