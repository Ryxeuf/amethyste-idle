## Sprint 11 — Monde vivant

> **6 taches** | Priorite : **Basse** | Origine : Vague 10, Pistes A & B
> Objectif : etendre le monde avec de nouvelles zones, du housing, des montures et des events live.
> Prerequis : Sprints 1-6 recommandes (contenu de base complet)

---

### Piste A — Contenu monde

### 128 — Nouvelles zones — Acte 4 (XL | ★★★)
> Prerequis : ← 94 (Acte 3 termine), ← 141 (monstres tier 2-3)
- [ ] 4 nouvelles cartes generees via l'editeur
- [ ] Nouveaux biomes : desert, tundra
- [ ] Monstres tier 4 (level 30-40)
- [ ] Chaine de quetes Acte 4
- [ ] Boss final Acte 4

### 129 — Housing joueur (L | ★★)
> Prerequis : ← 116 (hotel des ventes)
- [ ] Terrain achetable dans une zone dediee
- [ ] Maison personnalisable : meubles, coffre prive, atelier de craft
- [ ] Visitabilite : les joueurs peuvent visiter les maisons des autres
- [ ] Jardin : recolte passive (plantes poussent en temps reel)

### 130 — Montures & deplacement rapide (M | ★★)
> Prerequis : ∅
> Avancement : sous-phases 1 (catalogue de montures) et 5 (fast travel verrouille par decouverte) livrees. Sous-phases 2-4 (ownership, vitesse, animations) restent a faire.
- [x] Entite `Mount` : slug, name, speedBonus, sprite — entite `App\Entity\Game\Mount` (table `game_mounts`) avec slug unique, description, sprite sheet + icone, speedBonus (defaut 50), obtentionType enum (`quest`/`drop`/`purchase`/`achievement`), gilCost, requiredLevel, flag enabled + timestamps. Migration `Version20260419MountCatalog`. Fixtures de base (4 montures : cheval brun, loup sauvage, chocobo jaune, sanglier colossal) couvrant les 3 types d'obtention principaux. Tests unitaires validant les contraintes (speedBonus >= 0, obtentionType whitelist, gilCost >= 0 ou null, requiredLevel >= 1).
- [ ] Obtention via quete, drop rare, ou achat — catalogue pret (champ `obtentionType`), reste a brancher aux systemes de quetes / loot / boutique
- [ ] Vitesse de deplacement +50% quand monte
- [ ] Animation sprite monte sur la carte
- [x] Teleportation rapide entre villes decouvertes (cout en gils) — sous-phase 5 livree (2026-04-20). Le service `GoldSinkManager::fastTravel` (deja existant) est desormais conditionne par la decouverte de la region cible : nouvelle entite `App\Entity\App\PlayerVisitedRegion` (table `player_visited_region`, UNIQUE `player_id + region_id`, FK CASCADE), repository `PlayerVisitedRegionRepository` (`hasVisited` + `findVisitedRegionIds`), subscriber `App\GameEngine\Region\RegionDiscoveryTracker` qui enregistre la region courante a chaque `PlayerMovedEvent` (idempotent). `GoldSinkManager::getAvailableDestinations` filtre desormais par regions visitees ; `fastTravel` refuse explicitement les regions inconnues avec un message dedie. Migration `Version20260420PlayerVisitedRegion` (idempotente). Tests : `RegionDiscoveryTrackerTest` (5 cas), extension de `GoldSinkManagerTest` (4 nouveaux cas). Independante des sous-phases 2-4 (ownership, vitesse, animations).

---

### Piste B — Events & live ops

### 131 — Events live & outils GM (M | ★★★)
> Prerequis : ← 79 (evenements bonus)
- [ ] Interface admin pour lancer des events en temps reel
- [ ] Types : spawn de boss special, buff global, quete ephemere
- [ ] Historique des events lances
- [ ] Annonce globale via Mercure SSE

### 132 — Classement saisonnier global (M | ★★)
> Prerequis : ← 92 (classement guildes)
> Avancement : sous-phases 1, 2a, 2b, 3, 4a, 4b.1 et 4b.2 livrees (page `/game/rankings` + onglets kills / quetes / XP totale + archivage a la fin de saison + titres de podium top-3 + affichage des titres + badges cosmetiques). Sous-phase 4b.1c (Hall of Fame multi-saisons) reste en cours en parallele.
- [x] Page `/game/rankings` avec onglets (individuel) — route `app_game_rankings` (`GET /game/rankings`), controller `RankingController` (injection `PlayerHelper` + `PlayerBestiaryRepository`). Template `game/ranking/index.html.twig` affiche le top 50 par mobs tues (tableau avec rang, nom joueur, total kills) + le rang du joueur courant (`getPlayerKillRank`) + son compteur personnel. Lien ajoute dans le dropdown Social (desktop) et dans le drawer mobile. Traductions FR/EN ajoutees (`game.ranking.*`, `game.nav.rankings`). Tests `RankingControllerTest` (3 cas : top + rang joueur, redirection sans player, joueur non classe). Aucun prerequis bloquant — le classement exploite `PlayerBestiaryRepository::findTopKillers` (DQL group by + sum kill_count) sans dependre du systeme de saisons guilde.
- [x] Classement individuel — quetes completees (sous-phase 2a) — nouveau `PlayerQuestCompletedRepository` (methodes `findTopQuestCompleters` / `countQuestsCompleted` / `getPlayerQuestRank` basees sur COUNT + GROUP BY sur `player_quest_completed`). `RankingController` accepte `?tab=kills|quests` (fallback `kills` si tab inconnu) et choisit le dataset approprie. Template refactore autour de `topEntries` / `playerTotal` / `tab` ; les onglets sont des liens ancrant le parametre de query. Traductions FR/EN etendues (`tab.quests`, `col.quests`, `your_quests`, simplification de `your_rank_none`). Tests `RankingControllerTest` etendus (5 cas : default tab kills, tab quests, tab inconnu, redirection, joueur non classe dans quests).
- [x] Classement individuel — XP totale gagnee (sous-phase 2b) — `DomainExperienceRepository` etendu avec `findTopXpEarners` / `getPlayerXpRank` / `getTotalXpEarned` (DQL SUM(de.totalExperience) + GROUP BY de.player, meme pattern que kills/quests). `RankingController` accepte desormais `?tab=xp` (injection `DomainExperienceRepository`, TAB_XP ajoute a la whitelist). Template introduit un 3e onglet `xp`, `valueKey='totalXp'` et `col.xp` ; helper translator `your_xp` pour le resume personnel. Traductions FR/EN etendues (`tab.xp`, `col.xp`, `your_xp`). Tests `RankingControllerTest` portes a 7 cas : les 5 existants + `testIndexXpTabShowsXpRanking` (top XP + rang + total personnel) + `testIndexHandlesUnrankedPlayerInXpTab` (XP=0 retourne rank=null et tableau vide). Le classement agrege toutes les `DomainExperience` d'un joueur (toutes les voies de talent) : permet d'identifier les profils les plus investis en theorycrafting et couvre a la fois combat et craft, en coherence avec l'absence de niveau global (regle CLAUDE.md n°6).
- [x] Saisonnalite : lier le classement a `InfluenceSeason` (reset/archivage par saison) — sous-phase 3 livree (2026-04-20). Nouvelle entite `App\Entity\App\PlayerSeasonRankingSnapshot` (table `player_season_ranking_snapshot`, UNIQUE `season + tab + rank_position`, FK CASCADE vers `influence_season` et `player`) qui fige un top-N (par defaut 50) par onglet (`kills` / `quests` / `xp`) a la fin de chaque saison. Enum string `App\Enum\RankingTab`. Nouveau service `App\GameEngine\Season\SeasonRankingSnapshotService::snapshot(InfluenceSeason, ?int $limit)` : lit les top-N via les 3 repositories existants (`PlayerBestiaryRepository::findTopKillers`, `PlayerQuestCompletedRepository::findTopQuestCompleters`, `DomainExperienceRepository::findTopXpEarners`), persiste chaque ligne avec son rang, son `player`, son nom fige et la valeur totale. Idempotent (no-op si un snapshot existe deja pour la saison). Hook branche dans `SeasonTickCommand::handleExpiredSeasons` entre `updateTitles` et `endSeason`, summary affiche dans la sortie console. Migration idempotente `Version20260420SeasonRankingSnapshot`. Tests unitaires : `SeasonRankingSnapshotServiceTest` (6 cas : persistance top-N par onglet, idempotence, classements vides, limite custom, rang invalide, valeur negative) + extension de `SeasonTickCommandTest::testEndExpiredSeasonAttributesControlAndEnds` pour asserter l'appel au snapshot.
- [x] Recompenses de fin de saison — titres de podium (sous-phase 4a) — nouvelle entite `App\Entity\App\PlayerSeasonReward` (table `player_season_reward`, UNIQUE `season + tab + player`, FK CASCADE vers `influence_season` et `player`). Rang contraint `1-3` (top-3 uniquement) et label valide non vide dans le constructeur. Service `App\GameEngine\Season\SeasonRewardsManager::awardPodium(InfluenceSeason)` : lit les snapshots top-N via `PlayerSeasonRankingSnapshotRepository::findBySeasonAndTab`, filtre les rangs 1 a 3 pour chacun des 3 onglets (kills / quests / xp), genere un label `"Champion des chasseurs — Saison {N}"` / `"Vice-champion ..."` / `"Troisieme ..."` selon le rang et persiste une ligne par (saison, onglet, joueur). Idempotent via `PlayerSeasonRewardRepository::countForSeason`. Hook branche dans `SeasonTickCommand::handleExpiredSeasons` juste apres le snapshot et avant `endSeason`, summary affiche dans la sortie console. Migration idempotente `Version20260420SeasonReward`. Tests : `SeasonRewardsManagerTest` (5 cas : podium multi-onglets avec filtrage rang > 3, idempotence, onglets vides, rejet rang hors podium, rejet label vide) + extension de `SeasonTickCommandTest::testEndExpiredSeasonAttributesControlAndEnds` pour asserter l'appel a `awardPodium` et la ligne de summary.
- [~] Recompenses avancees : cosmetiques, items exclusifs, affichage des titres — sous-phase 4b
  - [x] Affichage des titres de podium sur la page classement (sous-phase 4b.1) — livree 2026-04-20. `PlayerSeasonRewardRepository` injecte dans `RankingController`, nouveau parametre template `playerTitles = findByPlayer($player)`. Template `game/ranking/index.html.twig` ajoute une section "Mes titres saisonniers" (visible uniquement si le joueur a au moins un titre) listant les badges rang 1/2/3 avec code couleur (or/argent/bronze), tooltip `saison — onglet`. Traductions FR/EN (`game.ranking.titles.heading`, `game.ranking.titles.season_tooltip`). Test `RankingControllerTest::testIndexPassesPlayerTitlesToTemplate` ajoute (8 cas au total), tous les tests existants adaptes au nouveau mock `PlayerSeasonRewardRepository` (defaut `findByPlayer -> []`).
  - [x] Affichage des titres de podium sur le profil public (sous-phase 4b.1b) — livree 2026-04-20. `PlayerSeasonRewardRepository` injecte dans `PlayerProfileController::show`, nouveau parametre template `playerTitles = findByPlayer($targetPlayer)` (note : les titres du joueur cible, pas du viewer). Template `game/profile/show.html.twig` ajoute une rangee de badges sous le `prestigeTitle` dans l'en-tete du profil, reutilisant exactement le meme code couleur et le meme tooltip que sur `/game/rankings` (convention unifiee). Visibles par tous les visiteurs. Tests `PlayerProfileControllerTest` etendus (5 cas au total : +`testShowPassesPlayerTitlesToTemplate` qui asserte que `findByPlayer` est appele sur le targetPlayer et que le tableau est propage au template, +`testShowPassesEmptyTitlesWhenPlayerHasNone` qui couvre le cas empty). Tous les tests existants adaptes au nouveau mock.
  - [x] Recompenses cosmetiques / items exclusifs (sous-phase 4b.2) — livree 2026-04-21. Nouveau champ `cosmeticIcon` (VARCHAR(60) nullable) sur `PlayerSeasonReward` + migration idempotente `Version20260421SeasonRewardCosmeticIcon`. Constructeur de l'entite accepte un parametre optionnel (chaine vide normalisee en `null`). `SeasonRewardsManager` expose un mapping interne `COSMETIC_ICONS[tab][rank]` (9 identifiants : `hunter_gold/silver/bronze`, `adventurer_gold/silver/bronze`, `scholar_gold/silver/bronze`) et passe l'icone resolu au constructeur lors de l'attribution du podium. Templates `game/ranking/index.html.twig` et `game/profile/show.html.twig` remplacent l'etoile generique par un glyphe HTML dedie (epees croisees `&#9876;` pour hunter, etendard `&#9873;` pour adventurer, etoile a 4 branches `&#10070;` pour scholar, fallback `&#9733;`) et etendent le tooltip avec le label de l'embleme. Traductions FR/EN (`game.ranking.titles.cosmetic.{hunter,adventurer,scholar}`). Tests : `SeasonRewardsManagerTest` etendu (+3 cas : icones kills/quests/xp, fallback xp scholar, normalisation constructeur, blank -> null), total 7 cas, retrocompatibilite preservee (`new PlayerSeasonReward(...)` sans icone reste valide). Aucune modification d'inventaire/items concrete : la recompense "cosmetique" est materialisee visuellement sur les badges de titres, independante de l'infrastructure `PlayerItem`.

### 133 — Mini-jeux (peche amelioree, courses) (M | ★)
> Prerequis : ∅
- [ ] Peche active : mini-jeu timing (barre de progression, fenetre de clic)
- [ ] Courses entre joueurs : parcours avec chrono, classement
- [ ] Recompenses specifiques par mini-jeu

---

### Definition of Done

- [ ] 4 nouvelles cartes Acte 4 jouables
- [ ] Housing fonctionnel avec visites
- [ ] Montures obtensibles et utilisables
- [ ] Events live lancables depuis l'admin
- [ ] Classement saisonnier operationnel
