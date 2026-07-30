# Plan — Contenu des zones du jeu de base

> **Numérotation :** les jalons de **ce** document sont préfixés **ZON-** et reprennent la
> série existante à **ZON-30** (la campagne ZON-12→27 du pivot PBBG est close, hors
> ZON-26b). Pas de conflit avec GCC- / ECO- / NAR- / FOY- / RET-.

> Décline [../GAME_ZONES.md](../GAME_ZONES.md) (définitions actées le 2026-07-28) en
> jalons. Tout est de la **donnée** (YAML de zone, fixtures d'items, monstres) : aucun
> moteur neuf. Chaque jalon respecte la règle 8 de CLAUDE.md (jamais plus de ~200 lignes
> de données par passe).

## Vue d'ensemble

**Plan d'origine complet : 7 jalons** (**ZON-30** à **ZON-36**, **7/7 ✅ au 2026-07-29**),
plus **deux fixes livrés en chemin** (ZON-37, ZON-38 ✅), **deux jalons ouverts par l'audit
du 2026-07-29** (ZON-39, ZON-40) et trois chantiers **référencés** qui vivent dans leurs
plans d'origine :

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| ZON-30 ✅ | Les Vallons d'Aubépine (zone neuve) | M | ∅ |
| ZON-31 ✅ | Les Dunes d'Ambre approfondies (ambre, os, gibier) | M | ∅ |
| ZON-32 ✅ | Signatures d'améthyste par zone (config) | S | ← ECO-21, ECO-22 |
| ZON-33 ✅ | Tests de conformité aux lois de zone | S | ‖ au fil des jalons |
| ZON-34 ✅ | La ligne du bois (domaine, essences, filons) | M | → ECO-30 (charpentier) |
| ZON-35 ✅ | Harmonisation des récoltes (loi 9) | S | ← ECO-29 pour les épices |
| ZON-36 ✅ | Affinités élémentaires des ressources (loi 10) | S | ∅ (donnée pure) |
| ZON-37 ✅ | La régénération d'un filon devient un débit (fix) | M | ∅ — **prérequis du recalibrage** |
| ZON-38 ✅ | La récolte redevient observable (fix) | S | ∅ |
| ZON-39 | La loi de nommage rejoint les libellés | S | ∅ (donnée pure) |
| ZON-40 | Les signatures cessent d'être inertes | S | tranché : option (a), affleurements à poser |

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

### ZON-33 — Tests de conformité aux lois de zone ✅ (S | ★★ | HAUTE)
> Les huit lois de GAME_ZONES §0 valent contrat — 8 tests (`ZoneLawsTest`).
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> L'essentiel : la loi transverse **étendue aux quêtes** a débusqué deux items demandés
> sans source (champignon vénéneux, racine de marais — corrigés, et comptés dans les
> affinités de ZON-36) ; aucune loi ne s'appuie sur un préfixe de slug ; l'écart de
> graphe Dunes → Cité est **documenté plutôt que corrigé** (le monde ne rétrécit jamais).

### ZON-34 — La ligne du bois ✅ (M | ★★★ | HAUTE)
> Décidé le 2026-07-28 (GAME_ZONES §3 bis) : la récolte du bois devient la cinquième
> récolte. Aujourd'hui l'arc et le bâton existent en items sans recette productrice, et
> aucune ressource bois n'existe — la ligne armes de bois + housing est sans matière.
> Le métier consommateur est **tranché** : le charpentier (ECO-30, Piste H de
> PLAN_PLAYER_ECONOMY), qui prend ce jalon en prérequis.
**Livré le 2026-07-29** (la matière). Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> L'essentiel : domaine `lumberjack`, 4 essences (hêtre T0 → bois pétrifié T4), filons
> `woodcutting` avec gates de compétence ; recettes livrées par ECO-30 (charpentier).
> Les écarts d'origine (« pas d'emplacement de hache », arbre à 8 nœuds) sont **périmés** :
> **DOM-05** (PLAN_DOMAINS) a livré la hache et porté l'arbre à 15 nœuds.

### ZON-35 — Harmonisation des récoltes ✅ (S | ★★ | MOYENNE)
> Applique la loi 9 (GAME_ZONES §3 ter) — 8 tests (`HarvestHarmonyTest`).
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> L'essentiel : 5 plantes mortes purgées, 10 sans débouché raccordées, poisson-lune et
> kraken juvénile réveillés en T4, 7 prix recalculés (règle ECO-27). Écarts documentés
> dans GAME_ZONES §3 ter : le kraken reçoit une source plutôt qu'une purge (ECO-29 lui
> avait donné le festin), la restriction nocturne du poisson-lune est reportée (pas de
> fenêtre horaire sur `gather`), l'herboriste reste à 20 plantes au lieu de 8–12.

### ZON-36 — Affinités élémentaires des ressources ✅ (S | ★★ | MOYENNE)
> Applique la loi 10 (GAME_ZONES §3 ter, GAME_WORLD §2.2) : chaque ressource porte une
> affinité de flux, dérivée de la signature de sa zone source. **Donnée pure.**
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> L'essentiel : `Item::affinity` (distinct d'`element` — ce dont une matière est *faite*,
> pas ce qu'une arme *projette*) + `config/game/affinities.yaml`, la **règle** (défaut par
> ligne de récolte) et **25 corrections** — 23 à la livraison, 2 ajoutées par ZON-33
> (`poisonous-mushroom`, `swamp-root`). L'améthyste reste **sans affinité** (substrat,
> canon §2.2). 22 tests.

### ZON-39 — La loi de nommage rejoint les libellés ✅ (M | ★★ | MOYENNE)
> Requalifié S → M à la livraison : les trois lignes annoncées valaient **29 chaînes dans
> 13 fichiers**, plus 47 chaînes de capitalisation. Livré avec **NAR-20**, qui réécrit les
> mêmes textes. **Livré le 2026-07-30.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
>
> L'essentiel : le hub devient **le Fanal**, la région le **Sanctuaire de la Voûte**, la
> faction la **Confrérie des Ruelles** ; les slugs restent hérités. La loi vit désormais dans
> `NamingLaw`, dont les termes **dérivent** d'`Element::cases()` et de `Purity::cases()` —
> ajouter un flux étend la loi le jour même. C'est elle qui a fait sortir un quatrième
> contrevenant que l'audit n'avait pas vu : **Terres Sauvages** → **Marches Sauvages**.
> L'étymologie du Codex (« dressèrent le Village de Lumière comme un fanal ») est réécrite,
> pas substituée. 12 zones + cartes + régions + factions vérifiées par `NamingLawTest`.
>
> **FAC-06** (PLAN_FACTIONS) portait aussi le renommage de la Confrérie : c'est fait.

### ZON-40 — Les signatures cessent d'être inertes (S | ★★★ | HAUTE)
> **Constat (audit 2026-07-29).** 3 signatures sur 7 (Forêt = référence, Vallons, Marais)
> ne s'appliquent **jamais** : ces zones n'ont aucun filon dans le périmètre de pureté
> (préfixe `ore-`). La promesse phare — « le Marais nocturne est le premier endroit où un
> joueur d'Acte II voit du Pur » (GAME_ZONES §2.5, `night_weight_shift: 30`) — est
> **ininstanciable**. Symétriquement, la Mer de Sel et le Pas de Givre ont des filons
> `ore-` **sans** signature : ils tirent comme la référence, ce qui contredit GAME_ZONES
> §4. Les tests d'`AmethystSignatureTest` passent sur des zones synthétiques ; aucun test
> ne croise la table des signatures avec la carte réelle des filons.
- [x] **Décision tranchée le 2026-07-29 : option (a)** — poser un **affleurement
      d'améthyste** (`ore-amethyst-crystal`, faible capacité, dans l'esprit de la loi 8 :
      l'améthyste affleure, elle ne se mine pas en filon riche) dans la Forêt, les Vallons
      et le Marais, pour que leurs signatures — dont le **Pur nocturne du Marais** —
      s'appliquent enfin. Options écartées : (b) étendre le périmètre de pureté,
      (c) réviser la promesse
- [ ] Livrable dans tous les cas : un test croisant **signatures × filons réels** — une
      signature sans filon dans le périmètre, ou l'inverse, fait rougir la CI
- [ ] Livrable dans tous les cas : signatures pour les zones du Silence (Mer de Sel,
      Pas de Givre)

### Restes de données (ex-ZON-26b + promesses ouvertes)

- **Marais Brumeux** et **Crête de Ventombre** : seules zones encore sans blocs
  `mobs:`/`pnjs:` déclaratifs (dépendent de leur TMX) — transférés du Sprint 13 (ZON-26b).
- **Illustrations de zone** : `Zone::illustrationPath` existe mais n'est ni lu par le
  loader ni renseigné par les YAML.
- **La 3e source de cuivre promise aux Vallons** (GAME_ZONES §3 : « la troisième viendra
  avec ZON-30 ») n'a jamais été posée — ZON-30 est livré sans elle.

---

## Risques

| Risque | Parade |
|---|---|
| Les Vallons cannibalisent la Forêt (deux zones T1 côte à côte) | Exclusivités disjointes : lin/blé/gibier contre herbes/ginseng/saumon — aucun item commun hors plancher |
| L'ambre fossile ou le lin sans débouché → exclusivité morte | Un débouché de recette est un critère d'acceptance des deux jalons |
| Les filons neufs posés à la main hors profils | Interdit — profils de palier de `world_1.yaml` uniquement (GAME_ZONES §6) |
| ECO-24b exécuté sans la carte des minerais | La carte (GAME_ZONES §3) est référencée comme entrée du jalon dans PLAN_PLAYER_ECONOMY |
