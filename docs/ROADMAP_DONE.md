# Roadmap realisee — Amethyste-Idle

> Historique des phases completees. Ce fichier est la reference pour tout ce qui a ete implemente.
> Derniere mise a jour : 2026-04-24 (135 sous-phase 3e.c.domain.b — cablage du filter `localized_domain_title` dans les templates `/game/skills`)

---

## 135 — Localisation i18n sous-phase 3e.c.domain.b : cablage du filter `localized_domain_title` (2026-04-24)

> Suite directe de la sous-phase 3e.c.domain (infrastructure entite) : cable le filter Twig dans les templates des arbres de talent (`/game/skills`). L'entite `Domain` n'etant pas passee directement aux templates (les controllers construisent des DTOs `DomainModel` / `PlayerDomain`), le filter accepte a la fois l'entite brute et le DTO via un `public readonly Domain $entity` expose sur `DomainModel`. Aucun duplicat des traductions dans le DTO : le filter delegue toujours a `Domain::getLocalizedTitle`.
>
> Sous-phase independante des 7 PR i18n en vol : aucune ne touche les templates `game/skills/**`, ne modifie `DomainModel` ou n'ajoute un filter Twig similaire.

- [x] `src/Twig/DomainLocalizationExtension.php` (nouveau, 48 lignes) : filter `localized_domain_title(Domain|DomainModel|null): string` qui applique `Domain::getLocalizedTitle` avec la locale courante recuperee depuis `RequestStack` (fallback transparent sur `Domain::title` si RequestStack vide, domaine null ou traduction manquante). Nom distinct de `localized_name` (Item), `localized_monster_name` (Monster) et `localized_quest_name` (Quest) pour eviter la collision Twig entre filters typehintes sur entites differentes.
- [x] `src/Dto/Domain/DomainModel.php` (+3 lignes, 29 -> 32) : nouvelle propriete `public readonly Domain $entity` peuplee dans le constructeur, consommee uniquement par le filter `localized_domain_title` pour acceder aux traductions sans passer par le DTO. Aucun getter ajoute sur `DomainModel` : le filter gere la distinction `Domain` vs `DomainModel`.
- [x] `templates/game/skills/domain_info.html.twig` (2 occurrences) : block `title` + en-tete `h1` passent de `{{ domain.title }}` a `{{ domain|localized_domain_title }}`.
- [x] `templates/game/skills/index.html.twig` (4 occurrences) : chip horizontal mobile, sidebar groupee par element, sidebar domaines "Autres", en-tete `h2` du panneau actif passent de `{{ domain.title }}` a `{{ domain|localized_domain_title }}`.
- [x] `tests/Unit/Twig/DomainLocalizationExtensionTest.php` (nouveau, 6 cas, 93 lignes) : traduction matchee, acceptation d'un `DomainModel`, fallback base title si traduction absente, fallback base title si RequestStack vide, domaine null -> chaine vide, enregistrement du filter. Helper `forceDomainId` via Reflection pour instancier `DomainModel` sans persistance Doctrine.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.domain.b ajoutee sous la branche 3e.c + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` header met a jour la derniere sous-phase livree.

**Diff** : +48 lignes extension, +3 lignes DTO, +6 lignes templates, +93 lignes test, +roadmap = ~170 lignes totales (<300 budget). Les templates `templates/admin/**`, `templates/game/index.html.twig` (dashboard recap) et `templates/game/profile/show.html.twig` (experience par domaine) ne sont pas cables (hors scope sous-phase, seront traites plus tard). Les fixtures EN pour les titres de domaines suivront en sous-phase 3e.c.domain.c.

---

## 135 — Localisation i18n sous-phase 3e.c.domain : infrastructure multilingue des titres de domaines (2026-04-24)

> Extension du pattern i18n etabli (Item 3a, Monster 3e.a, Quest 3e.c.d.quest, Skill title 3e.c.skill — PR #464) a `Domain.title`. L'entite `Domain` materialise les arbres de talent (Guerrier, Mage, Artisan…) affiches dans `/game/skills` et dans les profils de joueur. Memes fallbacks gracieux, meme normalisation que les sous-phases precedentes. Totalement retrocompatible : aucun template ni controller modifie, les domaines existants conservent `title_translations = null`. Prepare le cablage futur dans les templates des arbres de talent.
>
> Sous-phase completement independante des 7 PR i18n en vol (PR #451 Item.description infra, PR #455 Spell.name infra, PR #458 Pnj.name infra, PR #464 Skill.title infra, PR #472 Spell.description infra, PR #456 quest tracking cable, PR #444 audit translation keys) : aucune ne touche `Domain.php`, aucune ne modifie la table `game_domains`. Colonne JSON additive, zero risque de conflit.

- [x] `migrations/Version20260424DomainTitleTranslations.php` (nouveau, 26 lignes) : `ALTER TABLE game_domains ADD COLUMN IF NOT EXISTS title_translations JSON DEFAULT NULL`. Idempotent, reversible via `DROP COLUMN IF EXISTS`. Miroir strict des migrations des sous-phases precedentes.
- [x] `src/Entity/Game/Domain.php` (+45 lignes, 236 -> 278, sous la limite de 400) : nouvelle propriete `?array $titleTranslations` (colonne Doctrine `json`, nullable). `getLocalizedTitle(?string $locale): string` avec fallback gracieux sur `$this->title` si locale nulle/vide, colonne nulle, locale absente ou valeur blanche/non-string. `getTitleTranslations(): array` (defaut `[]`). `setTitleTranslations(?array $translations): self` avec normalisation (cles vides filtrees, valeurs non-string ignorees via `is_string`, valeurs blanches filtrees via `trim`, compaction vers `null` si aucune entree valide). Fluent.
- [x] `tests/Unit/Entity/Game/DomainLocalizationTest.php` (nouveau, 7 cas, 89 lignes) : miroir strict de `QuestLocalizationTest` / `SkillLocalizationTest` adapte a `title` (fallback sans traductions, traduction matchee EN/DE, fallback sur locale absente, normalisation cles vides / valeurs blanches / valeurs non-string, reset via `null`, compaction vers `null` si seulement entrees invalides, defaut `[]`).
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.domain ajoutee sous la branche 3e.c + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.domain.

**Diff** : +45 lignes `Domain.php`, +26 lignes migration, +89 lignes test, +roadmap = ~165 lignes totales (<300 budget). Aucun template, controller ou service modifie. Les sous-phases de cablage (filter Twig `localized_domain_title` + templates arbres de talent) et de fixtures EN suivront.

---

## 134 — Load testing sous-phase 2a : scenario k6 `metrics-stress` (2026-04-24)

> Jalon 2 de la tache 134 (Sprint 12). Second scenario k6 apres `guest-browsing` (sous-phase 1). Focalise sur l'endpoint Prometheus `/metrics` pour isoler la latence de collecte Doctrine sous charge soutenue, sans think-time. Permet de detecter les regressions de requetes Doctrine (index manquant, N+1, `COUNT(*)` sans `WHERE` sur tables qui grossissent), la saturation du pool PostgreSQL et l'impact du volume de `Fight`/`Mob`/`Player` sur la latence /metrics.

- [x] `scripts/load-test/scenarios/metrics-stress.js` (nouveau, 131 lignes) : chaque VU tape en boucle sur `/metrics` (20 VUs par defaut). Metriques custom `metrics_collect_latency` (Trend) et `metrics_payload_fail` (Rate). Thresholds dedies plus stricts que le default (p95<500ms, p99<1.5s, http_req_failed<0.5%, metrics_payload_fail<1%). Check format Prometheus (`# HELP` present). Options k6 dediees avec `ramping-vus` configurable via env (VUS / DURATION / RAMP_UP / RAMP_DOWN). User-agent specifique `amethyste-idle-k6-metrics-stress/1.0` pour filtrer les logs serveur pendant un run. Export JSON via `K6_SUMMARY_EXPORT` (defaut `scripts/load-test/last-summary-metrics-stress.json`) pour integration CI ulterieure.
- [x] `scripts/load-test/README.md` (+~50 lignes) : section "Scenario : metrics-stress" documentant usage, cas d'usage (detection regression Doctrine, pool saturation, volume Fight/Mob), commande par defaut + run soutenu 3min staging, avertissement "ne pas lancer en production sans coordination" (zero think-time = charge multipliee), thresholds explicites, piste de diagnostic (`pg_stat_statements`, pool Doctrine, collectors a cacher via Redis ou snapshot asynchrone). Bloc structure + intro mis a jour pour mentionner le scenario.
- [x] Roadmap : `SPRINT_12.md` sous-phase 2a ajoutee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` met a jour l'avancement Sprint 12 avec `134 sous-phase 2a`.

**Diff** : +131 lignes scenario, +~50 lignes README + roadmap. Aucun code PHP touche, aucune entite, aucune migration, aucun template. Totalement independant des PR en vol (notamment toutes les sous-phases 135 i18n). Zero risque de conflit.

---

## AVT-30 — Gestion cote client des updates Mercure avatar (2026-04-24) ✅

> Boucle temps reel avatar fermee : quand le serveur publie `map/avatar` / `avatar_updated` (cf. AVT-29), les clients PixiJS abonnes invalident leur texture composite et recomposent l'avatar du joueur concerne sans reload. `map_pixi_controller` ajoute le topic a son `EventSource`, dispatche l'event dans `_handleMercureEvent()` et le passe a `_handleAvatarUpdatedEvent()` — une seule methode consolidee qui gere les deux pipelines (self vs autre joueur) via le meme flux : invalidation de l'ancien hash sur la LRU (`AvatarAnimatorFactory.invalidateAvatarHash`), creation d'un nouvel animator via `createFromAvatarPayload`, swap du sprite dans le container cible (`_playerMarker` pour soi, `_entitySprites[key].container` pour les autres), mise a jour de `_animatedEntities` pour le ticker et destruction de l'ancien animator. Pour le joueur local, on preserve `_playerDirection` ; pour les autres, `entry.avatarHash` est mis a jour pour autoriser la prochaine invalidation ciblee.
>
> Filtre `mapId` pour ignorer les events d'autres cartes. L'empreinte `avatarHash` est desormais memorisee sur chaque `_entitySprites[key]` (joueurs presents) afin de permettre une invalidation ciblee du cache LRU, evitant la fuite de textures composites obsoletes.

- [x] `assets/controllers/map_pixi_controller.js` : abonnement au topic `map/avatar`, dispatch `avatar_updated`, methode consolidee `_handleAvatarUpdatedEvent()` gerant self + autres joueurs
- [x] Stockage `avatarHash` dans `_entitySprites[key]` pour permettre l'invalidation ciblee de la LRU
- [x] Filtre `mapId` et guard `renderMode === 'avatar'` pour ignorer les events non pertinents
- [x] Tests : `AvatarUpdatedPublisherTest` etendu (verification `mapId` + `avatarUpdatedAt` dans le payload serveur)

---

## 135 — Localisation i18n sous-phase 3e.c.d.quest.j : fixtures EN pour les dernieres quetes (2026-04-24)

> Cloture de la couverture EN sur les fixtures de quetes. Extension directe des sous-phases 3e.c.d.quest.c / 3e.c.d.quest.f / 3e.c.d.quest.g / 3e.c.d.quest.h / 3e.c.d.quest.i. Apres cette sous-phase, **100% des quetes** des fixtures (base + events) affichent un nom et une description EN en locale anglaise, via le meme pattern `name_translations` / `description_translations` + filters Twig `localized_quest_name` / `localized_quest_description` cables en 3e.c.d.quest.b / 3e.c.d.quest.e.

- [x] `src/DataFixtures/QuestFixtures.php` (+2 lignes) : ajout de `name_translations` + `description_translations` sur `quest_puzzle_ancient_runes` (derniere quete non traduite listee en 3e.c.d.quest.i). Traduction : `The Ancient Runes` / `Claire the Sage has discovered ancient runes in a grimoire. She asks you: "What element nourishes the light and consumes the shadow?" Find the answer.`.
- [x] `src/DataFixtures/GameEventFixtures.php` (+10 lignes) : ajout de `name_translations` + `description_translations` sur les 2 quetes du Festival de la Lune (`quest_festival_hunt=Star Hunt`, `quest_festival_collect=Stellar Offering`) + cablage de la boucle `foreach ($eventQuests as ...)` pour appeler `setNameTranslations` / `setDescriptionTranslations` via le meme pattern que `QuestFixtures` (isset + is_array). Prepare le terrain pour traduire futures quetes d'events sans toucher au loader.
- [x] Aucune migration : infrastructure posee en 3e.c.d.quest (nom) et 3e.c.d.quest.d (description).
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.d.quest.j ajoutee et cochee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.d.quest.j.

**Diff** : +2 lignes `QuestFixtures.php`, +10 lignes `GameEventFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les tests existants de `Quest::setNameTranslations()` / `setDescriptionTranslations()` (`QuestLocalizationTest` + `QuestDescriptionLocalizationTest`, 14 cas cumules) couvrent deja exhaustivement la normalisation des champs. Zero impact FR (fallback transparent preserve) ; impact EN immediat sur la quete finale du puzzle runes (`/game/quests`, `/game`) et les 2 quetes du festival lors des evenements live.

---

## 135 — Localisation i18n sous-phase 3c.d : fixtures EN pour 27 plantes et herbes (2026-04-24)

> Extension directe des sous-phases 3c (35 items de debut de jeu), 3c.b (26 materia tier 1-3) et 3c.c (18 materiaux de craft). Cible : les 27 plantes et herbes, ingredients alchimiques des recettes de potions. Avant cette sous-phase, un joueur en locale EN voyait ces 27 plantes retomber sur leur nom FR via le fallback transparent de `Item::getLocalizedName()` ; apres, l'inventaire (onglet items), la boutique si exposee et les vues de craft/alchimie affichent ces plantes en anglais.

- [x] `src/DataFixtures/ItemFixtures.php` (+27 lignes, 4198 au total, sous la limite de 50 lignes ajoutees car ce fichier depassait deja 400 lignes avant cette tache) : ajout de `name_translations => ['en' => '...']` sur 27 entrees. Loader inchange (la delegation `Item::setNameTranslations()` existe deja depuis la sous-phase 3c).
- [x] Plantes communes (6) : `plant-lavender=Lavender`, `plant-mint=Mint`, `plant-sage=Sage`, `plant-thyme=Thyme`, `plant-rosemary=Rosemary`, `plant-chamomile=Chamomile`.
- [x] Plantes medicinales (6) : `plant-nettle=Nettle`, `plant-dandelion=Dandelion`, `plant-valerian=Valerian`, `plant-aloe-vera=Aloe Vera`, `plant-ginseng=Ginseng`, `plant-echinacea=Echinacea`.
- [x] Plantes toxiques (3) : `plant-mandrake=Mandrake`, `plant-nightshade=Nightshade`, `plant-wolfsbane=Wolfsbane`.
- [x] Plantes magiques (8) : `plant-moonflower=Moonflower`, `plant-sunblossom=Sunblossom`, `plant-dragonleaf=Dragonleaf`, `plant-frostcap=Frostcap`, `plant-ghostshroom=Ghostshroom`, `plant-thunderroot=Thunderroot`, `plant-whisperweed=Whisperweed`, `plant-dreamlily=Dreamlily`.
- [x] Plantes legendaires (2) : `plant-voidfruit=Voidfruit`, `plant-phoenixflower=Phoenix Flower`.
- [x] Ingredients marais / champignons (2, slugs sans prefixe `plant-`) : `poisonous-mushroom=Poisonous Mushroom`, `swamp-root=Swamp Root`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3c.d ajoutee et cochee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3c.d.

**Diff** : +27 lignes `ItemFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les items restants (equipements tier 2+, consommables alchimiste avances, recettes craft, fragments Acte 2+, items de boss) conservent leur nom FR comme fallback ; leurs traductions suivront dans des sous-phases ulterieures. Les tests existants de `Item::setNameTranslations()` (`ItemLocalizationTest`, 7 cas) couvrent deja exhaustivement la normalisation du champ. Zero impact FR (fallback transparent preserve) ; impact EN immediat sur `/game/inventory` (onglet items), `/game/shop` si une plante est exposee, et les vues de craft/alchimie via le filter `localized_name` cable en 3b.

---

## 135 — Localisation i18n sous-phase 3c.c : fixtures EN pour 18 materiaux de craft (2026-04-24)

> Extension directe des sous-phases 3c (35 items de debut de jeu) et 3c.b (26 materia tier 1-3). Cible : les 18 materiaux d'artisanat produits ou recoltes par les metiers (mineur, forgeron, tanneur, tisserand, alchimiste, joaillier), composants des recettes de craft tier 1-3 de la tache 145 Recettes craft manquantes. Avant cette sous-phase, un joueur en locale EN voyait ces 18 materiaux retomber sur leur nom FR via le fallback transparent de `Item::getLocalizedName()` ; apres, la boutique, l'inventaire, le bestiaire (table de loot) et les selecteurs de materia affichent ces materiaux en anglais.

- [x] `src/DataFixtures/ItemFixtures.php` (+18 lignes, 4171 au total, sous la limite de 50 lignes ajoutees car ce fichier depassait deja 400 lignes avant cette tache) : ajout de `name_translations => ['en' => '...']` sur 18 entrees. Loader inchange (la delegation `Item::setNameTranslations()` existe deja depuis la sous-phase 3c).
- [x] Minerais precieux (3) : `ore-ruby=Ruby`, `ore-diamond=Diamond`, `ore-emerald=Emerald`.
- [x] Lingots metalliques (7) : `crafted-bronze-ingot=Bronze Ingot`, `crafted-iron-ingot=Iron Ingot`, `crafted-cobalt-ingot=Cobalt Ingot`, `crafted-mithril-ingot=Mithril Ingot`, `crafted-adamantite-ingot=Adamantite Ingot`, `crafted-orichalcum-ingot=Orichalcum Ingot`, `crafted-gold-ingot=Gold Ingot`.
- [x] Materiaux tannerie / tisserand / alchimie (3) : `crafted-leather-strip=Leather Strip`, `crafted-cloth=Cloth`, `crafted-potion-base=Potion Base`.
- [x] Gemmes taillees joaillier (5) : `crafted-gem-basic=Cut Gem`, `crafted-gem-fine=Fine Gem`, `crafted-gem-rare=Rare Gem`, `crafted-gem-enchanted=Enchanted Gem`, `crafted-gem-prismatic=Prismatic Gem`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3c.c ajoutee et cochee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3c.c.

**Diff** : +18 lignes `ItemFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les items restants (equipements tier 2+, consommables alchimiste avances, herbes / plantes, recettes craft, fragments Acte 2+, items de boss) conservent leur nom FR comme fallback ; leurs traductions suivront dans des sous-phases ulterieures. Les tests existants de `Item::setNameTranslations()` (`ItemLocalizationTest`, 7 cas) couvrent deja exhaustivement la normalisation du champ. Zero impact FR (fallback transparent preserve) ; impact EN immediat sur `/game/shop`, `/game/inventory`, `/game/bestiary` (table de loot) et les selecteurs de materia via le filter `localized_name` cable en 3b.

---

## 135 — Localisation i18n sous-phase 3e.c.d.quest.i : fixtures EN pour 24 quetes supplementaires (2026-04-23)

> Extension directe des sous-phases 3e.c.d.quest.g (20 quetes Acte 1 secondaires + chaine Acte 2 "Les Fragments") et 3e.c.d.quest.h (15 quetes Acte 3 + dailies + steles de decouverte). Cible : 24 des 25 quetes restantes pour atteindre une couverture EN quasi-complete (24/25 = 96%) sur les noms et descriptions de quetes. Cible regroupe sept categories thematiques : decouverte multi-points (cartographe + pelerinage), zones secondaires (forets / mines / marais / montagne), faction intro (mages / chevaliers / ombres / marchands), chasse supplementaire (scorpions / gargouilles), choix moraux a consequences de reputation opposees (contrebandier / prisonnier / grimoire / ferme / relique), defense (foret / mines), escorte (marchand / refugies) et puzzle sphinx. La derniere quete `quest_puzzle_ancient_runes` est differee a une sous-phase ulterieure pour respecter la contrainte stricte de 50 lignes ajoutees max sur un fichier > 400 lignes (48 lignes ici).

- [x] `src/DataFixtures/QuestFixtures.php` (+48 lignes, 2054 au total, sous la limite de 50 lignes ajoutees car ce fichier depassait deja 400 lignes avant cette tache) : ajout de `name_translations => ['en' => '...']` + `description_translations => ['en' => '...']` sur 24 entrees. Loader inchange (la delegation `Quest::setNameTranslations()` / `Quest::setDescriptionTranslations()` existe deja depuis les sous-phases 3e.c.d.quest.c et 3e.c.d.quest.f).
- [x] Quetes de decouverte multi-points (2) : `quest_discovery_cartographer=Cartographer of the Forgotten Lands`, `quest_discovery_sacred_sites=Pilgrimage of the Sacred Sites`.
- [x] Quetes de zone secondaires (6) : `quest_zone_foret_meute=The Hungry Pack`, `quest_zone_foret_venin=Sentinel Against Venom`, `quest_zone_mines_automates=Malfunctioning Automatons`, `quest_zone_marais_prime=Bounty on the Undead`, `quest_zone_marais_appat=Poisoned Bait`, `quest_zone_montagne_aerienne=Aerial Threat`.
- [x] Quetes de faction intro (4) : `quest_faction_mages_intro=Arcane Echoes`, `quest_faction_chevaliers_intro=The Knight's Oath`, `quest_faction_ombres_intro=In the Shadow of the Goblins`, `quest_faction_marchands_intro=Safe Roads for the Guild`.
- [x] Quetes de chasse supplementaires (2) : `quest_hunt_scorpions=Scourge of the Sands`, `quest_hunt_gargoyles=The Stone Watchers`.
- [x] Quetes a choix moral (5) : `quest_moral_contrebandier=The Unmasked Smuggler`, `quest_moral_prisonnier=The Condemned Prisoner`, `quest_moral_grimoire=The Forbidden Grimoire`, `quest_moral_ferme_brulee=The Burned Farm`, `quest_moral_relique=The Relic of the Forgotten Temple`.
- [x] Quetes de defense (2) : `quest_defend_forest=Defense of the Dark Forest`, `quest_defend_mines=Save the Deep Mines`.
- [x] Quetes d'escorte (2) : `quest_escort_merchant=Escort the Wandering Merchant`, `quest_escort_refugee=Refugees from the Swamp`.
- [x] Quete puzzle (1) : `quest_puzzle_sphinx=The Stone Sphinx's Riddle`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.d.quest.i ajoutee et cochee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.d.quest.i.

**Diff** : +48 lignes `QuestFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Une seule quete restante (`quest_puzzle_ancient_runes`) conserve son contenu FR comme fallback ; sa traduction suivra dans une sous-phase ulterieure 3e.c.d.quest.j (2 lignes, finalisera 100% de la couverture EN sur les noms et descriptions de quetes). Les tests existants de `Quest::setNameTranslations()` et `Quest::setDescriptionTranslations()` (14 cas) couvrent deja exhaustivement la normalisation des champs. Zero impact FR (fallback transparent preserve) ; impact EN immediat sur `/game/quests` (onglets actifs / disponibles / completes / historique / dailies) et le tracking du dashboard `/game` pour ces 24 quetes via les filters `localized_quest_name` et `localized_quest_description` cables en 3e.c.d.quest.b/e.

---

## 135 — Localisation i18n sous-phase 3e.c.d.quest.h : fixtures EN pour 15 quetes supplementaires (2026-04-23)

> Extension directe de la sous-phase 3e.c.d.quest.g (20 quetes Acte 1 secondaires + chaine Acte 2 "Les Fragments"). Cible : l'integralite de la chaine narrative finale Acte 3 "La Convergence" (3 quetes), les 2 quetes enquete / defi boss cleanup Acte 2, les 2 quetes d'evenement (dailies Festival de la Lune + Nuit des Ombres), et les 8 quetes cachees de decouverte (exploration standard : steles, puits, cercle feerique, chene, autel, grotte, sanctuaire, obelisque). Total 15 quetes = 30 lignes ajoutees. Avant cette sous-phase, un joueur en locale EN voyait ces 15 quetes retomber sur leur nom/description FR via le fallback transparent ; apres, la narration finale de l'Acte 3 et tous les points de decouverte de la Plaine de l'Eveil / Foret des Murmures / Marais / Collines / Lande sont disponibles en anglais.

- [x] `src/DataFixtures/QuestFixtures.php` (+30 lignes, 2006 au total, sous la limite de 50 lignes ajoutees car ce fichier depassait deja 400 lignes avant cette tache) : ajout de `name_translations => ['en' => '...']` + `description_translations => ['en' => '...']` sur 15 entrees. Loader inchange (la delegation `Quest::setNameTranslations()` / `Quest::setDescriptionTranslations()` existe deja depuis les sous-phases 3e.c.d.quest.c et 3e.c.d.quest.f).
- [x] Acte 2 cleanup (2) : `quest_enquete_herboriste=The Missing Herbalist`, `quest_defi_gardien_foret=Guardian's Challenge`.
- [x] Chaine Acte 3 "La Convergence" (3) : `quest_acte3_appel=The Call of the Fragments`, `quest_acte3_gardien=Guardian of the Nexus`, `quest_acte3_epilogue=Epilogue`.
- [x] Quetes d'evenement (2) : `quest_event_lunar_hunt=Hunt Under the Moon`, `quest_event_shadow_purge=Shadow Purge`.
- [x] Quetes cachees de decouverte (8) : `quest_discovery_ancient_stele=Forgotten Stele`, `quest_discovery_forgotten_well=The Well of the Ancients`, `quest_discovery_fairy_ring=Fairy Ring`, `quest_discovery_hollow_oak=The Ancient Oak`, `quest_discovery_sunken_altar=Sunken Altar`, `quest_discovery_phospho_grotto=Phosphorescent Grotto`, `quest_discovery_wind_shrine=Wind Shrine`, `quest_discovery_shadow_obelisk=Shadow Obelisk`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.d.quest.h ajoutee et cochee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.d.quest.h.

**Diff** : +30 lignes `QuestFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les quetes restantes (~25 quetes : cartographe/pelerinage, quetes de guilde, quetes karma/Acte 4 steles cachees, Acte 2 fragments karma) conservent leur contenu FR comme fallback ; leurs traductions suivront dans des sous-phases ulterieures. Les tests existants de `Quest::setNameTranslations()` et `Quest::setDescriptionTranslations()` (14 cas) couvrent deja exhaustivement la normalisation des champs. Zero impact FR ; impact EN immediat sur `/game/quests` (onglets actifs / disponibles / completes / historique / dailies) et le tracking du dashboard `/game` pour ces 15 quetes via les filters `localized_quest_name` et `localized_quest_description` cables en 3e.c.d.quest.b/e.

---

## 135 — Localisation i18n sous-phase 3e.c.d.quest.g : fixtures EN pour 20 quetes supplementaires (2026-04-23)

> Consomme l'infrastructure des sous-phases 3e.c.d.quest (colonne `name_translations` sur `Quest`), 3e.c.d.quest.d (colonne `description_translations`), 3e.c.d.quest.b et 3e.c.d.quest.e (filters Twig cables dans le journal de quetes et le dashboard) en peuplant les traductions EN d'un second lot de quetes au-dela des 26 quetes de debut de jeu couvertes par 3e.c.d.quest.c et 3e.c.d.quest.f. Cible : les 5 quetes Acte 1 secondaires (dragon + 4 quetes cachees decouvertes en jeu) et l'integralite de la chaine narrative Acte 2 "Les Fragments" (15 quetes sur 4 regions : Forêt, Mines, Marais, Montagne). Avant cette sous-phase, un joueur en locale EN voyait ces 20 quetes retomber sur leur nom/description FR via le fallback transparent ; apres, la narration Acte 2 est entierement disponible en anglais des la prise de contact avec le Cristal d'Amethyste.

- [x] `src/DataFixtures/QuestFixtures.php` (+40 lignes, 1976 au total, sous la limite de 50 lignes ajoutees car ce fichier depassait deja 400 lignes avant cette tache) : ajout de `name_translations => ['en' => '...']` + `description_translations => ['en' => '...']` sur 20 entrees. Loader inchange (la delegation `Quest::setNameTranslations()` / `Quest::setDescriptionTranslations()` existe deja depuis les sous-phases 3e.c.d.quest.c et 3e.c.d.quest.f). Aucune normalisation specifique : les setters de l'entite filtrent deja les cles / valeurs invalides.
- [x] Acte 1 secondaires (5) : `quest_dragon_1=The Mountain Dragon`, `quest_hidden_secret_clearing=The Clearing's Secret`, `quest_hidden_rare_slime=The Golden Slime`, `quest_hidden_herb_lore=Ancestral Lore`, `quest_hidden_goblin_cache=The Goblin Hideout`.
- [x] Chaine Acte 2 "Les Fragments" — Forêt (4) : `quest_acte2_foret_murmures=The Whispers Grow Stronger`, `quest_acte2_foret_purification=Purify the Corruption`, `quest_acte2_foret_remede=The Ancestral Remedy`, `quest_acte2_foret_fragment=The Sylvan Shard`.
- [x] Chaine Acte 2 — Mines (4) : `quest_acte2_mines_tremblements=Underground Tremors`, `quest_acte2_mines_minerai=The Ancient Ore`, `quest_acte2_mines_forge=The Forge Lord`, `quest_acte2_mines_fragment=The Forge Shard`.
- [x] Chaine Acte 2 — Marais (4) : `quest_acte2_marais_brumes=The Mists Thicken`, `quest_acte2_marais_ingredients=Remedies of the Depths`, `quest_acte2_marais_gardiens=Guardians of the Dead Waters`, `quest_acte2_marais_fragment=The Mist Shard`.
- [x] Chaine Acte 2 — Montagne (3) : `quest_acte2_montagne_echos=Echoes from the Summit`, `quest_acte2_montagne_gardien=The Peak Guardian`, `quest_acte2_montagne_fragment=The Summit Shard`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.d.quest.g ajoutee et cochee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.d.quest.g.

**Diff** : +40 lignes `QuestFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les quetes restantes (~40 quetes : Acte 3 La Convergence, quetes de guilde, quetes karma, quetes cachees steles/cartographe) conservent leur contenu FR comme fallback ; leurs traductions suivront dans des sous-phases ulterieures. Les tests de `Quest::setNameTranslations()` et `Quest::setDescriptionTranslations()` (14 cas dans `QuestLocalizationTest` + `QuestDescriptionLocalizationTest`) couvrent deja exhaustivement la normalisation des champs ; la diff sur le loader etant deja couverte par les sous-phases 3e.c.d.quest.c et 3e.c.d.quest.f, aucun test dedie n'est necessaire. Zero impact FR (fallback transparent preserve) ; impact EN immediat sur `/game/quests` et le tracking du dashboard `/game` pour ces 20 quetes via les filters `localized_quest_name` et `localized_quest_description` cables en 3e.c.d.quest.b/e.

---

## 133 — Mini-jeux sous-phase 1b : bonus XP pour les captures parfaites de peche (2026-04-23)

> Extension de la sous-phase 1 (zone parfaite 45-55 preserve la durabilite de la canne). Ajoute une seconde recompense economique pour les captures parfaites : **+1 XP supplementaire** au domaine de recolte associe (2 XP au lieu de 1 sur un coup parfait). Cible de la sous-phase : renforcer la courbe de progression des joueurs qui maitrisent le timing du mini-jeu sans introduire de drop bonus ni d'effet de rarete (coherent avec la regle PvE cooperative). Le bonus reste multiplie par `GameEventBonusProvider::getXpMultiplier` comme les autres voies de recolte (butcher / harvest / fish), donc compatible avec les events `xp_bonus` globaux ou par map.

- [x] `src/Event/Map/FishingEvent.php` (+6 lignes) : nouveau parametre constructeur `bool $perfect = false` (optionnel, retrocompatible avec l'existant) et methode `isPerfect(): bool` qui combine defensivement le flag ET `isSuccess()` — un event parfait sans prise (defensif, ne devrait pas survenir) retombe sur `false` et ne grante aucun bonus.
- [x] `src/GameEngine/Job/FishingManager.php` (+1 / -1) : le `FishingEvent` final dispatche a la capture reussie passe desormais le flag `$perfect` deja calcule localement (`$tension >= PERFECT_MIN && $tension <= PERFECT_MAX`). Aucun changement sur les events d'echec (trop faible / trop fort) : leur flag reste `false` par defaut.
- [x] `src/GameEngine/Progression/DomainExperienceEvolver.php` (+2 / -1) : `experienceFromFishing` lit `$event->isPerfect()` et injecte `2` au lieu de `1` dans `increaseDomainExperience`. Aucun impact sur les autres handlers (butcher / harvest / item used) qui conservent leur montant `1`.
- [x] Tests unitaires : nouveau `tests/Unit/Event/Map/FishingEventTest.php` (3 cas : flag+catch, defaut sans flag, flag sans catch) qui isole la logique defensive de l'entite event. `tests/Unit/GameEngine/Job/FishingManagerTest.php` (+8 / -4) : les cas `testCompleteFishingNormalSuccessReducesDurability` et `testCompleteFishingPerfectPreservesDurability` utilisent maintenant `callback` pour asserter que l'event dispatche porte le bon flag `isPerfect()` (propagation bout-en-bout verifiee).
- [x] Roadmap : `SPRINT_11.md` tache 133 — ajout du nouveau bullet "Peche — bonus XP timing parfait (sous-phase 1b)" coche, avancement en-tete mis a jour. `ROADMAP_TODO_INDEX.md` : avancement Sprint 11 reflete la sous-phase 1b de 133.

**Diff** : ~20 lignes de code applicatif + ~70 lignes de tests + roadmap. Zero migration, zero nouvelle dependance, aucun changement client (le bonus transite par le pipeline XP existant visible dans l'onglet domaines). Retrocompatibilite stricte de `FishingEvent` (nouvelle position en 4e argument optionnelle, les autres dispatchs sans `perfect` preservent exactement l'ancien comportement). Independante de toutes les PR ouvertes 135/130/131/132/AVT.

---

## 135 — Localisation i18n sous-phase 3c.b : fixtures EN pour les noms des 26 materia tier 1-3 (2026-04-23)

> Consomme l'infrastructure des sous-phases 3a (colonne `name_translations` sur `Item`) et 3b (filter Twig `localized_name` cable dans les selecteurs de materia et l'inventaire) en peuplant les traductions EN des 26 materia existantes. Avant cette sous-phase, meme en locale EN, les templates materia (`inventory/materia/_slot_select*`, `inventory/items_list`) retombaient systematiquement sur `Item::name` (nom FR) car la colonne `name_translations` etait vide pour toutes les materia. Apres, les 26 materia affichent leur nom anglais des que la locale de session est `en`, et restent en francais avec `fr` (ou sur un fallback gracieux pour toute locale sans traduction). Extension directe de la sous-phase 3c (35 items de debut de jeu) au systeme materia, explicitement listee comme "couvert dans une sous-phase ulterieure" dans la description de 3c.

- [x] `src/DataFixtures/ItemFixtures.php` (+26 lignes, 4153 au total) : ajout de la cle `name_translations => ['en' => '...']` juste apres `name` sur les 26 entrees de materia. Aucune modification du loader (la delegation `Item::setNameTranslations()` existe deja depuis la sous-phase 3c). Aucune normalisation specifique : le setter de l'entite filtre deja les cles / valeurs invalides.
- [x] Tier 1 (10 materia) : `m1-life=Heal`, `m1-fire=Fireball`, `m1-flame=Flame`, `m2-fire=Fire` (slug legacy sur entree bas cout), `m3-fire=Rain of Fire`, `m1-earth=Stone Throw`, `m1-death=Punishment`, `m1-wind=Wind Blade`, `m1-metal=Sharp Blade`, `m1-nature=Vine Whip`.
- [x] Tier 2 (8 materia, 1 par element) : `m2-combustion=Combustion`, `m2-water=Frost Mist`, `m2-air=Chain Lightning`, `m2-earth=Stone Wall`, `m2-metal=Steel Riposte`, `m2-beast=Savage Bite`, `m2-light=Blessing`, `m2-dark=Vital Drain`.
- [x] Tier 3 (8 materia epiques, 1 par element) : `m3-fire-solar=Solar Burst`, `m3-water=Frost Maelstrom`, `m3-air=Thunderstorm`, `m3-earth=Crystal Quake`, `m3-metal=Orichalcum Blade`, `m3-beast=Primal Awakening`, `m3-light=Divine Grace`, `m3-dark=Shadow Covenant`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3c.b ajoutee et cochee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3c.b.

**Diff** : +26 lignes `ItemFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les items restants (equipements tier 2+, gemmes, recettes craft, fragments Acte 2+) conservent leur nom FR comme fallback ; leurs traductions suivront dans des sous-phases ulterieures. Les tests de `Item::setNameTranslations` (7 cas dans `ItemLocalizationTest`) couvrent deja exhaustivement la normalisation du champ ; la diff sur le loader etant deja couverte par la sous-phase 3c, aucun test dedie n'est necessaire. Zero impact FR (fallback transparent preserve) ; impact EN immediat sur l'inventaire materia, le selecteur de slot de materia (socketing) et `/game/shop` si une boutique expose une materia.

---

## 135 — Localisation i18n sous-phase 3e.c.d.quest.f : fixtures EN pour 26 descriptions de quetes de debut de jeu (2026-04-23)

> Consomme l'infrastructure des sous-phases 3e.c.d.quest.d (colonne `description_translations` sur `Quest`) et 3e.c.d.quest.e (filter Twig `localized_quest_description` cable dans le journal de quetes et le dashboard) en peuplant les traductions EN des descriptions des quetes visibles en debut de jeu. Avant cette sous-phase, meme en locale EN, les 2 templates cables retombaient systematiquement sur `Quest::description` (description FR) car la colonne `description_translations` etait vide pour toutes les quetes. Apres, les 26 memes quetes deja traduites en noms par la sous-phase 3e.c.d.quest.c affichent desormais leur description anglaise des que la locale de session est `en`, et restent en francais avec `fr` (ou sur un fallback gracieux pour toute locale sans traduction). Miroir strict de la sous-phase 3e.c.d.quest.c (qui couvrait `name`).

- [x] `src/DataFixtures/QuestFixtures.php` (+29 lignes, 1936 au total, sous la limite de 400 ligne car ce fichier la depassait deja avant cette tache) : le loader accepte desormais la cle optionnelle `description_translations` (array) dans chaque entree du tableau PHP de quetes, qui est relayee a `Quest::setDescriptionTranslations()` juste apres `setDescription()`. La normalisation (cles vides filtrees, valeurs non-string ignorees, compaction vers `null` si vide) est deja assuree par le setter cote entite — le loader se contente de passer le tableau tel quel (pattern identique a la cle `name_translations` ajoutee en sous-phase 3e.c.d.quest.c).
- [x] Quetes de monstres (8) : `quest_zombie_1`, `quest_skeleton_1`, `quest_taiju_1`, `quest_mushroom_1`, `quest_goblin_1`, `quest_troll_1`, `quest_werewolf_1`, `quest_banshee_griffin_1`.
- [x] Quetes utilitaires (4) : `quest_wood_collection`, `quest_deliver_mushroom`, `quest_explore_forest`, `quest_choice_alliance`.
- [x] Quetes quotidiennes (6) : `daily_kill_slimes`, `daily_kill_bats`, `daily_kill_spiders`, `daily_collect_herbs`, `daily_collect_ore`, `daily_kill_rats`.
- [x] Chaine "La Menace Rampante" (3) : `quest_chain_guard_1..3`.
- [x] Chaine tutoriel Acte 1 (5) : `quest_acte1_reveil`, `quest_acte1_premiers_pas`, `quest_acte1_bapteme_du_feu`, `quest_acte1_recolte`, `quest_acte1_cristal`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.d.quest.f ajoutee et cochee + ligne d'avancement mise a jour (inclut aussi 3e.c.d.quest.e). `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.d.quest.f.

**Diff** : +29 lignes `QuestFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les descriptions des ~60 autres quetes (Acte 2+, chaines cachees, dragons, quetes d'evenements, faction/reputation) conservent leur contenu FR comme fallback ; leurs traductions suivront dans des sous-phases ulterieures (par boucle thematique / acte). Les tests de l'entite `Quest::setDescriptionTranslations` (7 cas dans `QuestDescriptionLocalizationTest`) couvrent deja exhaustivement la normalisation du champ ; la diff sur le loader est de 3 lignes de delegation triviale (`isset` + `is_array` + appel setter), ne necessitant pas de test dedie. Zero impact FR (le fallback transparent de `Quest::getLocalizedDescription` preserve le rendu actuel) ; impact EN immediat sur `/game/quests` et `/game` dashboard (via le filter `localized_quest_description` cable en 3e.c.d.quest.e).

---

## 135 — Localisation i18n sous-phase 3e.c.d.quest.d : infrastructure multilingue pour les descriptions de quetes (2026-04-23)

> Etend le pattern des sous-phases 3a (Item `name`), 3e.a (Monster `name`), 3e.c.d.quest (Quest `name`) a `Quest.description`. Meme contrat, meme normalisation, memes fallbacks gracieux. Etape infra-only prealable au cablage futur dans le journal de quetes (`/game/quests`), les dialogues PNJ (offre / acceptation / completion) et l'ecran de tracking des objectifs. Totalement retrocompatible : aucun template, aucun controller et aucun fixture modifies ; toutes les quetes existantes conservent `description_translations = null` et continuent d'etre rendues via `quest.description`. La description etant un champ `text` (long, multiligne, souvent narratif), sa traduction se fera par fixtures / admin dans des sous-phases ulterieures.

- [x] Migration `migrations/Version20260423QuestDescriptionTranslations.php` : `ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS description_translations JSON DEFAULT NULL`. Idempotente, reversible via `DROP COLUMN IF EXISTS`. Miroir strict de `Version20260422QuestNameTranslations`.
- [x] `App\Entity\Game\Quest` (+45 lignes, total 388, sous la limite de 400) : nouvelle propriete `?array $descriptionTranslations` (colonne Doctrine `json`, nullable). Nouvelle methode `getLocalizedDescription(?string $locale): string` — fallback sur `$this->description` si `$locale` est `null`/vide, si `$this->descriptionTranslations` est `null`, si la locale n'est pas presente, ou si sa valeur est une chaine blanche ou non-string. Nouvelle methode `getDescriptionTranslations(): array<string, string>` (defaut `[]`). Nouveau setter `setDescriptionTranslations(?array $translations): self` avec normalisation : cles vides ignorees, valeurs non-string ou blanches ignorees, tableau final ramene a `null` si vide (stockage compact en DB). Retourne `$this` (fluent, aligne avec les autres methodes de l'entite).
- [x] Tests `tests/Unit/Entity/Game/QuestDescriptionLocalizationTest.php` (nouveau, 7 cas, 92 lignes) : miroir exact de `QuestLocalizationTest` (qui couvre `name`). Couvre fallback sans traductions (null/vide/FR/EN), traduction matchee EN/DE, fallback sur locale absente (es/ja), normalisation cles vides / valeurs blanches / valeurs non-string (entier 42), reset via `null`, compaction vers `null` si seulement entrees invalides, defaut `[]` de `getDescriptionTranslations`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.d.quest.d ajoutee et cochee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.d.quest.d.

**Diff** : +26 lignes migration + 45 lignes entite + 92 lignes tests + roadmap = ~180 lignes (budget 300 respecte). Aucun fichier nouveau ou existant ne depasse 400 lignes. Aucun template, controller, service ou fixture touche — sous-phase infra-only, parfaitement isolee des PRs ouvertes sur les autres sous-phases 3e.* et sur la sous-phase 3d (Item descriptions, PR #451). Prochaines etapes envisageables : cablage de `getLocalizedDescription` dans les vues qui rendent `{{ quest.description }}` (journal + dashboard + dialogues PNJ) et peuplement des fixtures EN pour les descriptions des quetes starter.

---

## 135 — Localisation i18n sous-phase 3e.b.b.suite : fixtures EN pour le reste du bestiaire (2026-04-22)

> Complete la sous-phase 3e.b.b (qui couvrait les monstres de niveaux 1-3) en peuplant les traductions EN des 22 monstres restants du bestiaire. Avant cette sous-phase, les monstres de niveau 3+ (necromancer, will_o_wisp), tous les monstres de niveau 4-5, et l'ensemble des boss d'Acte 1-3 (niveaux 10 a 30) retombaient sur le fallback FR meme en locale `en` dans les templates `/game/bestiary`, `/game/profile` (titres de chasseur), ecran de combat et bouton de depecage. Apres, le bestiaire est a 100% de parite FR/EN sur les noms affiches (47/47 monstres traduits).

- [x] `src/DataFixtures/MonsterFixtures.php` (+22 lignes, 1156 au total) : 22 entrees `name_translations => ['en' => '...']` ajoutees juste apres `name` (pattern identique aux 25 monstres deja traduits en 3e.b.b). Aucune migration, aucune modification de loader ou d'entite : la cle `name_translations` etait deja reconnue par le loader depuis 3e.b.b et normalisee par `Monster::setNameTranslations()`.
- [x] Niveau 3 (2) : `necromancer → Necromancer`, `will_o_wisp → Will-o'-the-Wisp`.
- [x] Niveau 4 (5) : `alpha_wolf → Alpha Wolf`, `griffin → Griffin`, `minotaur → Minotaur`, `stone_golem → Stone Golem`, `sylph → Sylph`.
- [x] Niveau 5 (3) : `clay_golem → Clay Golem`, `creeping_shadow → Creeping Shadow`, `dragon → Ancestral Dragon`.
- [x] Niveaux 10-18 (7) : `wyvern → Wyvern`, `cursed_knight → Cursed Knight`, `naga → Naga`, `crystal_golem → Crystal Golem`, `forest_guardian → Forest Guardian`, `corrupted_archdruid → Corrupted Archdruid`, `lesser_lich → Lesser Lich`.
- [x] Boss Acte 2-3 — niveaux 20-30 (5) : `forge_lord → Forge Lord`, `swamp_hydra → Swamp Hydra`, `abyssal_blacksmith → Abyssal Blacksmith`, `ancient_wyrm → Ancient Wyrm`, `convergence_guardian → Guardian of the Convergence`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.b.b.suite ajoutee et cochee + avancement mis a jour. `ROADMAP_TODO_INDEX.md` met a jour l'avancement 135.

**Diff** : +22 lignes `MonsterFixtures.php` + roadmap. Aucune migration, aucun template ni controller touche. Zero impact FR (le fallback transparent de `Monster::getLocalizedName` preserve le rendu actuel quand la locale est FR ou que la traduction est absente). Couverture des tests existants `MonsterLocalizationTest` (7 cas sur la normalisation du setter) conservee — la diff etant purement data, aucun test dedie n'est ajoute.

---

## 135 — Localisation i18n sous-phase 3c : fixtures EN pour 35 items de debut de jeu (2026-04-22)

> Consomme l'infrastructure des sous-phases 3a (colonne `name_translations` sur `Item`) et 3b (filter Twig `localized_name` cable dans les templates shop/inventaire/bestiaire/materia) en peuplant les traductions EN des items visibles en debut de jeu. Avant cette sous-phase, meme en locale EN, les 5 templates cables retombaient systematiquement sur `Item::name` (nom FR) car la colonne `name_translations` etait vide pour tous les items. Apres, les 35 items de debut de jeu affichent leur nom anglais des que la locale de session est `en`, et restent en francais avec `fr` (ou sur un fallback gracieux pour toute locale sans traduction). Miroir strict de la sous-phase 3e.b.b (fixtures EN monstres) et 3e.c.d.quest.c (fixtures EN quetes).

- [x] `src/DataFixtures/ItemFixtures.php` (+40 lignes, 4127 au total) : le loader accepte desormais la cle optionnelle `name_translations` (array) dans chaque entree du tableau PHP d'items, qui est relayee a `Item::setNameTranslations()` juste avant la pose des timestamps. La normalisation (cles vides filtrees, valeurs non-string ignorees, compaction vers `null` si vide) est deja assuree par le setter cote entite — le loader se contente de passer le tableau tel quel (pattern identique a `MonsterFixtures` et `QuestFixtures`).
- [x] Equipements starter (5) : `short_sword → Short Sword`, `long_sword → Long Sword`, `leather_boots → Leather Boots`, `leather_hat → Leather Hat`, `leather_armor → Leather Armor`.
- [x] Consommables / potions (9) : `life_potion → Healing Potion`, `healing_potion_small → Minor Healing Potion`, `healing_potion_medium → Healing Potion`, `healing_potion_major → Major Healing Potion`, `energy_potion_small → Minor Energy Potion`, `antidote → Antidote`, `bread → Bread`, `grilled_meat → Grilled Meat`, `stew → Stew`.
- [x] Parchemins (6) : `scroll_teleport → Teleport Scroll`, `scroll_xp_boost → Scroll of Knowledge`, `scroll_identification → Identification Scroll`, `ancient_scroll → Ancient Scroll`, `life_domain_parchment → Healing Apprenticeship`, `miner_domain_parchment → Mining Discovery`.
- [x] Outils (2) : `fishing_rod → Fishing Rod`, `pickaxe → Pickaxe`.
- [x] Equipement tier 1 (8) : `iron_sword → Iron Sword`, `wooden_shield → Wooden Shield`, `leather_helmet → Leather Helmet`, `magic_amulet → Magic Amulet`, `magic_ring → Magic Ring`, `bow → Bow`, `staff → Staff`, `dagger → Dagger`.
- [x] Divers (5) : `mushroom → Mushroom`, `beer_pint → Beer Mug`, `wood_log → Wood Log`, `herb_mint → Wild Mint`, `herb_lavender → Lavender`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3c cochee + detail d'implementation + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3c.

**Diff** : +40 lignes `ItemFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les ~240 autres items (materia tier 1+, equipements tier 2+, gemmes taillees, consommables alchimiste, plantes, fragments, recettes craft) conservent leur nom FR comme fallback ; leurs traductions suivront dans des sous-phases ulterieures (par tier / rarete / thematique). Les tests de l'entite `Item::setNameTranslations` (7 cas dans `ItemLocalizationTest`) couvrent deja exhaustivement la normalisation du champ ; la diff sur le loader est de 3 lignes de delegation triviale (`isset` + `is_array` + appel setter), ne necessitant pas de test dedie.

---

## 135 — Localisation i18n sous-phase 3e.c.d.quest.c : fixtures EN pour 26 quetes de debut de jeu (2026-04-22)

> Consomme l'infrastructure des sous-phases 3e.c.d.quest (colonne `name_translations` sur `Quest`) et 3e.c.d.quest.b (filter Twig `localized_quest_name` cable dans le journal de quetes et le dashboard) en peuplant les traductions EN des quetes visibles en debut de jeu. Avant cette sous-phase, meme en locale EN, les deux templates cables retombaient systematiquement sur `Quest::name` (nom FR) car la colonne `name_translations` etait vide pour toutes les quetes. Apres, les 26 quetes de debut de jeu affichent leur nom anglais des que la locale de session est `en`, et restent en francais avec `fr` (ou sur un fallback gracieux pour toute locale sans traduction). Miroir strict de la sous-phase 3e.b.b (fixtures EN monstres).

- [x] `src/DataFixtures/QuestFixtures.php` (+29 lignes, 1907 au total) : le loader accepte desormais la cle optionnelle `name_translations` (array) dans chaque entree du tableau PHP de quetes, qui est relayee a `Quest::setNameTranslations()` juste apres `setName()`. La normalisation (cles vides filtrees, valeurs non-string ignorees, compaction vers `null` si vide) est deja assuree par le setter cote entite — le loader se contente de passer le tableau tel quel (pattern identique a `MonsterFixtures`).
- [x] Quetes de monstres (8) : `quest_zombie_1 → Stop the Zombies`, `quest_skeleton_1 → Stop the Skeletons`, `quest_taiju_1 → The Menacing Taiju`, `quest_mushroom_1 → Mushroom Picking`, `quest_goblin_1 → Goblin Threat`, `quest_troll_1 → The Bridge Troll`, `quest_werewolf_1 → Nocturnal Howls`, `quest_banshee_griffin_1 → Creatures of the Night`.
- [x] Quetes utilitaires (4) : `quest_wood_collection → Budding Woodcutter`, `quest_deliver_mushroom → Mushroom Delivery`, `quest_explore_forest → Mapping the Forest`, `quest_choice_alliance → Contested Allegiance`.
- [x] Quetes quotidiennes (6) : `daily_kill_slimes → Slime Purge`, `daily_kill_bats → Bat Hunt`, `daily_kill_spiders → Unwanted Webs`, `daily_collect_herbs → Daily Harvest`, `daily_collect_ore → Ore for the Forge`, `daily_kill_rats → Field Rats`.
- [x] Chaine "La Menace Rampante" (3) : `quest_chain_guard_1 → The Creeping Menace - Part 1`, `quest_chain_guard_2 → The Creeping Menace - Part 2`, `quest_chain_guard_3 → The Creeping Menace - Part 3`.
- [x] Chaine tutoriel Acte 1 (5) : `quest_acte1_reveil → The Awakening — Awakening`, `quest_acte1_premiers_pas → The Awakening — First Steps`, `quest_acte1_bapteme_du_feu → The Awakening — Baptism by Fire`, `quest_acte1_recolte → The Awakening — Harvest`, `quest_acte1_cristal → The Awakening — The Amethyst Crystal`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.d.quest.c ajoutee et cochee + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.d.quest.c.

**Diff** : +29 lignes `QuestFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les ~60 autres quetes (dragons, chaines cachees, Acte 2+, quetes d'evenements, faction/reputation, defend/escort/puzzle) conservent leur nom FR comme fallback ; leurs traductions suivront dans des sous-phases ulterieures (par boucle thematique / acte). Les tests de l'entite `Quest::setNameTranslations` (7 cas dans `QuestLocalizationTest`) couvrent deja exhaustivement la normalisation du champ ; la diff sur le loader est de 3 lignes de delegation triviale (`isset` + `is_array` + appel setter), ne necessitant pas de test dedie.

---

## 135 — Localisation i18n sous-phase 3e.c.d.quest.b : cablage du filter `localized_quest_name` dans les templates (2026-04-22)

> Consomme l'infrastructure de la sous-phase 3e.c.d.quest (colonne `name_translations` + `Quest::getLocalizedName`) en exposant le nom traduit dans les templates qui rendent une entite `Quest` directement. Miroir strict du pattern etabli par 3b (Item) et 3e.b.a (Monster) : nouveau filter Twig dedie, cablage des templates en conservant le nom base comme fallback, aucun template legacy touche (`templates/old_game/**` et `templates/admin/**` restent hors scope). Zero impact FR (le fallback transparent de `Quest::getLocalizedName` preserve le rendu actuel) ; prepare l'impact EN immediat des qu'une sous-phase de fixtures (3e.c.d.quest.c a venir) peuplera `Quest.name_translations`.

- [x] `src/Twig/QuestLocalizationExtension.php` (nouveau, 46 lignes) : extension Twig qui expose le filter `localized_quest_name`. Applique `Quest::getLocalizedName` avec la locale courante recuperee depuis `RequestStack` (fallback transparent sur `Quest::name` si RequestStack vide, Quest null ou traduction manquante). Nom distinct de `localized_name` (Item) et `localized_monster_name` (Monster) pour eviter la collision Twig entre filters typehintes sur entites differentes. Defense en profondeur identique a `MonsterLocalizationExtension` (renvoie `''` si Quest null).
- [x] `templates/game/quest/index.html.twig` : 6 occurrences remplacees (4 `{{ quest.name }}` en-tetes de carte pour quetes en cours / quotidiennes actives / disponibles / chaines, 1 `{{ dq.quest.name }}` en-tete pour quotidiennes terminees, 1 `{{ quest.name }}` en-tete pour les quetes completees listees dans l'historique). Substitution mecanique (`quest|localized_quest_name` / `dq.quest|localized_quest_name`), aucun changement de structure, aucun changement de classes CSS, aucune ligne ajoutee ou supprimee (delta net 0 lignes). Non-regression FR garantie par le fallback transparent.
- [x] `templates/game/index.html.twig` : 1 occurrence remplacee (L143, carte de tracking des quetes actives affichee sur le dashboard `/game`). Substitution identique.
- [x] Tests `tests/Unit/Twig/QuestLocalizationExtensionTest.php` (nouveau, 5 cas, 74 lignes) : miroir exact de `MonsterLocalizationExtensionTest`. Couvre (1) traduction matchee en locale EN, (2) fallback sur `Quest::name` si la locale courante n'a pas de traduction, (3) fallback sur `Quest::name` si le `RequestStack` est vide (execution hors contexte HTTP), (4) retour de chaine vide pour un Quest null (defense en profondeur), (5) enregistrement correct du filter sous le nom `localized_quest_name`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.d.quest.b cochee + detail d'implementation + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.d.quest.b.

**Diff** : +46 lignes extension + 74 lignes tests + 7 lignes modifiees dans 2 templates + roadmap = ~140 lignes (budget 300 respecte). Aucun fichier nouveau ou existant ne depasse 400 lignes (quest/index.html.twig a 923 lignes, mais aucune ligne ajoutee : substitutions pures). Aucune migration, aucun controller, aucun service, aucun fixture modifie — sous-phase template-only, parfaitement isolee des PRs ouvertes sur les autres sous-phases 3e.*. La sous-phase suivante (3e.c.d.quest.c) peuplera `Quest.name_translations` avec les traductions EN des quetes visibles en debut de jeu (premieres chaines narratives + quetes journalieres).

---

## 135 — Localisation i18n sous-phase 3e.c.d.quest : infrastructure multilingue pour les noms de quetes (2026-04-22)

> Etend le pattern des sous-phases 3a (Item), 3e.a (Monster), 3e.c.a (Spell) et 3e.d (Pnj) a `Quest.name`. Meme contrat, meme normalisation, memes fallbacks gracieux. Ferme le sous-jalon 3e.c.d (Quest + Pnj) cote Quest — la moitie Pnj a ete livree en PR dediee sous le code 3e.d. Totalement retrocompatible : aucun template, aucun controller et aucun fixture modifies ; toutes les quetes existantes conservent `name_translations = null` et continuent d'etre rendues via `quest.name`. Prepare le cablage futur dans le journal de quetes (`/game/quests`), les dialogues PNJ qui proposent/acceptent des quetes et les ecrans de completion.

- [x] Migration `migrations/Version20260422QuestNameTranslations.php` : `ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL`. Idempotente, reversible via `DROP COLUMN IF EXISTS`. Miroir strict des migrations 3a / 3e.a / 3e.c.a / 3e.d.
- [x] `App\Entity\Game\Quest` (+46 lignes, total 343, sous la limite de 400) : nouvelle propriete `?array $nameTranslations` (colonne Doctrine `json`, nullable). Nouvelle methode `getLocalizedName(?string $locale): string` — fallback sur `$this->name` si `$locale` est `null`/vide, si `$this->nameTranslations` est `null`, si la locale n'est pas presente, ou si sa valeur est une chaine blanche ou non-string. Nouvelle methode `getNameTranslations(): array<string, string>` (defaut `[]`). Nouveau setter `setNameTranslations(?array $translations): self` avec normalisation : cles vides ignorees, valeurs non-string ou blanches ignorees, tableau final ramene a `null` si vide (stockage compact en DB). Retourne `$this` (fluent, aligne avec les autres entites de la tache 135).
- [x] Tests `tests/Unit/Entity/Game/QuestLocalizationTest.php` (nouveau, 7 cas, 88 lignes) : miroir exact de `MonsterLocalizationTest` / `SpellLocalizationTest` / `PnjLocalizationTest`. Couvre fallback sans traductions (null/vide/FR/EN), traduction matchee EN/DE, fallback sur locale absente (es/ja), normalisation cles vides / valeurs blanches / valeurs non-string (entier 42), reset via `null`, compaction vers `null` si seulement entrees invalides, defaut `[]` de `getNameTranslations`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.c.d.quest cochee + detail d'implementation + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.c.d.quest.

**Diff** : +26 lignes migration + 46 lignes entite + 88 lignes tests + roadmap = ~181 lignes (budget 300 respecte). Aucun fichier nouveau ou existant ne depasse 400 lignes. Aucun template, controller ou service touche — sous-phase infra-only, parfaitement isolee des PRs ouvertes sur les autres sous-phases 3e.*. La sous-phase suivante cablera `Quest::getLocalizedName` dans le journal de quetes (`templates/game/quest/**`), les dialogues PNJ (offre / acceptation / completion) et eventuellement le HUD de tracking de quetes actives.

---

## 135 — Localisation i18n sous-phase 3e.b.b : fixtures EN pour 24 monstres de niveaux 1-3 (2026-04-22)

> Consomme l'infrastructure des sous-phases 3e.a (colonne `name_translations` sur `Monster`) et 3e.b.a (filter Twig `localized_monster_name` cable dans bestiaire/profile/fight) en peuplant les traductions EN des monstres visibles en debut de jeu. Avant cette sous-phase, meme en locale EN, les trois templates cables retombaient systematiquement sur `Monster::name` (nom FR) car la colonne `name_translations` etait vide pour tous les monstres. Apres, les 24 monstres de niveaux 1-3 affichent leur nom anglais des que la locale de session est `en`, et restent en francais avec `fr` (ou sur un fallback gracieux pour toute locale sans traduction).

- [x] `src/DataFixtures/MonsterFixtures.php` (+29 lignes, 1156 au total) : le loader accepte desormais la cle optionnelle `name_translations` (array) dans chaque entree du tableau PHP de monstres, qui est relayee a `Monster::setNameTranslations()` juste avant la boucle `setCreatedAt` / `persist`. La normalisation (cles vides filtrees, valeurs non-string ignorees, compaction vers `null` si vide) est deja assuree par le setter cote entite — le loader se contente de passer le tableau tel quel.
- [x] Niveau 1 (7 monstres) : `slime → Slime`, `goblin → Goblin`, `bat → Bat`, `giant_rat → Giant Rat`, `zombie → Zombie`, `wolf → Wolf`, `beetle → Beetle`.
- [x] Niveau 2 (10 monstres) : `skeleton → Skeleton`, `spider → Spider`, `venom_snake → Venomous Snake`, `taiju → Taiju`, `specter → Specter`, `banshee → Banshee`, `scorpion → Scorpion`, `mushroom_golem → Mushroom Golem`, `ghost → Ghost`, `undine → Undine`.
- [x] Niveau 3 (7 monstres) : `ochu → Ochu`, `werewolf → Werewolf`, `gargoyle → Gargoyle`, `troll → Troll`, `fire_elemental → Fire Elemental`, `salamander → Salamander`, `rusty_automaton → Rusty Automaton`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e.b.b cochee + detail d'implementation + ligne d'avancement mise a jour. `ROADMAP_TODO_INDEX.md` : Sprint 12 met a jour l'avancement 135 avec la sous-phase 3e.b.b.

**Diff** : +29 lignes `MonsterFixtures.php` + roadmap. Aucune migration, aucune nouvelle entite, aucun template ni controller touche. Les 49 autres monstres (tier 4+, boss, donjons, acte 2+) conservent leur nom FR comme fallback ; leurs traductions suivront dans des sous-phases ulterieures (par boucle tier / rarete). Les tests de l'entite `Monster::setNameTranslations` (7 cas dans `MonsterLocalizationTest`) couvrent deja exhaustivement la normalisation du champ ; la diff sur le loader est de 4 lignes de delegation triviale (`isset` + `is_array` + appel setter), ne necessitant pas de test dedie.

---

## 135 — Localisation i18n sous-phase 3e.b.a : cablage du filter `localized_monster_name` dans les templates (2026-04-21)

> Consomme l'infrastructure posee par la sous-phase 3e.a (colonne `name_translations` + methode `Monster::getLocalizedName`) en cablant trois templates joueur pour afficher le nom de monstre localise selon la locale courante. Avant cette sous-phase, meme quand des traductions EN etaient presentes en base, les templates utilisaient `monster.name` qui retourne le nom FR de reference — la colonne ajoutee par 3e.a etait inerte. Apres, des que la sous-phase 3e.b.b livrera des fixtures EN, les 3 templates basculeront automatiquement en anglais lorsque la locale de session est `en`, et resteront en francais avec `fr` (ou sur un fallback gracieux pour toute locale sans traduction). Sous-phase volontairement limitee au cablage (pas de fixtures EN — a suivre en 3e.b.b) pour garder le diff minimal et respecter la convention des sous-phases de la tache 135.

- [x] `src/Twig/MonsterLocalizationExtension.php` (nouveau, 45 lignes) : extension Twig declarant le filter `localized_monster_name`. Prend un `?Monster $monster`, recupere la locale courante via `RequestStack::getCurrentRequest()->getLocale()` et delegue a `Monster::getLocalizedName($locale)`. Renvoie une chaine vide si `$monster === null` (defense en profondeur). Fallback transparent sur `Monster::name` si RequestStack est vide (context console / tests unitaires) ou si la locale est une chaine vide. Miroir strict de `ItemLocalizationExtension` (sous-phase 3b) — meme pattern, meme signature, meme fallback. Nom distinct (`localized_monster_name` vs `localized_name`) pour eviter la collision Twig entre filters typehintes sur entites differentes.
- [x] Templates cables avec `{{ monster|localized_monster_name }}` (3 fichiers, 5 occurrences) : `templates/game/bestiary/index.html.twig` (nom d'en-tete de la carte monstre + 2 usages dans `|trans({'%monster%': ...})` pour le tier_title), `templates/game/profile/show.html.twig` (liste des titres de chasseur sur le profil public), `templates/game/fight/index.html.twig` (bouton de depecage `game.fight.butcher` avec le nom du monstre). Le template `templates/game/quest/index.html.twig` n'est pas cable volontairement : `tracking.monsters` y est un tableau associatif (et non une entite `Monster`) assemble cote controller pour l'affichage du tracking de quete ; le cablage passera par la construction cote controller dans une sous-phase future (3e.b.c).
- [x] Tests `tests/Unit/Twig/MonsterLocalizationExtensionTest.php` (nouveau, 5 cas, ~75 lignes) : `testFilterReturnsTranslationMatchingCurrentLocale` (EN avec traduction -> Goblin), `testFilterFallsBackToBaseNameWhenTranslationMissing` (EN sans traduction -> fallback FR Gobelin), `testFilterFallsBackToBaseNameWhenRequestStackIsEmpty` (RequestStack vide -> fallback FR), `testFilterReturnsEmptyStringForNullMonster` (defense en profondeur), `testFilterIsRegistered` (meta-assertion sur `getFilters()`). Miroir strict de `ItemLocalizationExtensionTest`. Pure unit test (pas de kernel).
- [x] Roadmap : `SPRINT_12.md` sous-phase 3e decoupee pour faire apparaitre 3e.b.a (cablage, livree) et 3e.b.b (fixtures EN, a faire). `ROADMAP_TODO_INDEX.md` : mise a jour de la ligne Sprint 12 avec la nouvelle sous-phase.

**Diff** : ~45 lignes extension + ~75 lignes tests + 5 edits templates (5 occurrences) + roadmap. Aucun migration, aucun fixture, aucun controller ou service modifie. Isolation totale : prepare la sous-phase 3e.b.b (fixtures EN pour les noms de monstres) sans preempter ses decisions.

---

## 135 — Localisation i18n sous-phase 3e.a : infrastructure multilingue pour les noms de monstres (2026-04-21)

> Etend le pattern de la sous-phase 3a (colonne JSON + accesseurs localises) a `Monster.name`. Meme contrat, meme normalisation, memes fallbacks gracieux. Premiere sous-phase de la serie 3e (extension aux entites gameplay hors Item) : les sous-phases suivantes couvriront 3e.b (cablage bestiaire / loot / combat) puis 3e.c (Spell, Quest, Pnj). Totalement retrocompatible : aucun template ni controller modifie, les monstres existants conservent `name_translations = null` et continuent d'etre rendus via `monster.name`.

- [x] Migration `migrations/Version20260421MonsterNameTranslations.php` : `ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL`. Idempotente, reversible via `DROP COLUMN IF EXISTS`. Miroir strict de la migration ajoutee en sous-phase 3a pour `game_items`.
- [x] `App\Entity\Game\Monster` : nouvelle propriete `?array $nameTranslations` (colonne Doctrine `json`, nullable). Nouvelle methode `getLocalizedName(?string $locale): string` — fallback sur `$this->name` si `$locale` est `null`/vide, si `$this->nameTranslations` est `null`, si la locale n'est pas presente, ou si sa valeur est une chaine blanche ou non-string. Nouvelle methode `getNameTranslations(): array<string, string>` (defaut `[]`). Nouveau setter `setNameTranslations(?array $translations): self` avec normalisation : cles vides ignorees, valeurs non-string ou blanches ignorees, tableau final ramene a `null` si vide (stockage compact en DB). Retourne `$this` (fluent, aligne avec `Item::setNameTranslations`). Monster.php passe de 318 a 361 lignes (reste sous la limite de 400).
- [x] Tests `tests/Unit/Entity/Game/MonsterLocalizationTest.php` (7 cas, miroir exact de `ItemLocalizationTest`) : fallback sans traductions (null/vide/FR/EN), traduction matchee EN/DE, fallback sur locale absente (es/ja), normalisation cles/valeurs invalides, reset via `null`, compaction vers `null` quand toutes les entrees sont invalides, defaut `[]` sur un monstre neuf.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3 decoupee pour faire apparaitre 3e.a (livree), 3e.b (cablage bestiaire/loot/combat) et 3e.c (Spell/Quest/Pnj). `ROADMAP_TODO_INDEX.md` : avancement Sprint 12 met a jour la ligne 135.

**Diff** : ~43 lignes `Monster.php` + ~87 lignes tests + 26 lignes migration + roadmap. Aucun template touche, aucun fixture modifie, aucun controller ou service. Isolation totale : prepare la sous-phase 3e.b (cablage du bestiaire `templates/game/bestiary/index.html.twig` pour les noms d'en-tete et les fixtures EN) sans preempter ses decisions.

---

## 135 — Localisation i18n sous-phase 3b : cablage du filter `localized_name` dans les templates (2026-04-21)

> Consomme l'infrastructure posee par la sous-phase 3a (colonne `name_translations` + methode `Item::getLocalizedName`) en cablant cinq templates de jeu et deux controllers d'inventaire pour afficher le nom d'item localise selon la locale courante de la requete. Avant cette sous-phase, meme quand des traductions EN etaient presentes en base, tous les templates utilisaient `item.name` qui retourne le nom FR de reference — la colonne ajoutee par 3a etait inerte. Apres, des que la sous-phase 3c livrera des fixtures EN, les 5 templates basculeront automatiquement en anglais lorsque la locale de session est `en`, et resteront en francais avec `fr` (ou sur un fallback gracieux pour toute locale sans traduction).

- [x] `src/Twig/ItemLocalizationExtension.php` (nouveau, ~45 lignes) : extension Twig declarant le filter `localized_name`. Prend un `?Item $item`, recupere la locale courante via `RequestStack::getCurrentRequest()->getLocale()` et delegue a `Item::getLocalizedName($locale)`. Renvoie une chaine vide si `$item === null` (defense en profondeur pour les templates qui boucleraient sur une collection vide via `|first`). Fallback transparent sur `Item::name` si RequestStack est vide (context console / tests unitaires) ou si la locale est une chaine vide. Aucune dependance circulaire, aucun cache explicite (delegue au pipeline Twig).
- [x] Templates cables avec `{{ item|localized_name }}` (5 fichiers, 5 occurrences substantives) : `templates/game/shop/index.html.twig` (liste acheter + liste vendre), `templates/game/inventory/items_list.html.twig` (titre item), `templates/game/inventory/materia/_slot_select.html.twig` (titre panneau), `templates/game/inventory/materia/_slot_select_embed.html.twig` (label embed), `templates/game/bestiary/index.html.twig` (nom d'item dans la loot table revelee aux paliers de kills). Les attributs HTML `data-item-name` / `data-item-eq-name` (utilises par le JS de recherche / filtrage d'inventaire comme cles de lookup) conservent volontairement `item.name` non localise pour preserver l'invariant de cle stable.
- [x] `src/Controller/Game/Inventory/ItemsController.php` + `src/Controller/Game/Inventory/MateriaController.php` : pour ces deux controllers qui construisent un tableau associatif `['id' => ..., 'name' => ..., 'description' => ...]` destine au template (plutot que de passer directement les entites `Item`), la signature `__invoke()` accepte desormais un `Request $request`, `$locale = $request->getLocale()` est extrait hors boucle, et la cle `'name'` est remplie via `$genericItem->getLocalizedName($locale)` au lieu de `->getName()`. Pas d'impact sur les templates correspondants (`inventory/items/_list.html.twig`, `inventory/materia/_list.html.twig`) qui continuent de lire `item.name` tel quel.
- [x] Tests `tests/Unit/Twig/ItemLocalizationExtensionTest.php` (nouveau, 5 cas, ~70 lignes) : `testFilterReturnsTranslationMatchingCurrentLocale` (EN avec traduction -> Iron sword), `testFilterFallsBackToBaseNameWhenTranslationMissing` (EN sans traduction -> fallback FR), `testFilterFallsBackToBaseNameWhenRequestStackIsEmpty` (RequestStack vide -> fallback FR), `testFilterReturnsEmptyStringForNullItem` (defense en profondeur), `testFilterIsRegistered` (meta-assertion sur `getFilters()`). Aucun test n'instancie de conteneur Symfony ni de kernel — pure unit test sur l'extension.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3b cochee + detail implementation + ajout de la ligne de progression dans l'en-tete de la tache 135. `ROADMAP_TODO_INDEX.md` (reference des compteurs Sprint 12) : mise a jour.

**Diff** : ~45 lignes extension + ~70 lignes tests + 5 modifications de template (1 ligne chacune) + 2 controllers (3-4 lignes chacun) + roadmap. Aucune migration Doctrine, aucune entite modifiee, aucun fixture ajoute, aucune traduction Symfony touchee. Isolation stricte : tant que les items n'ont pas de `nameTranslations` peuples (sous-phase 3c a venir), le rendu visuel est strictement identique a l'existant (fallback sur `name`). Prepare la sous-phase 3c (fixtures EN) et la sous-phase 3d (extension du pattern a `description`).

---

## 135 — Localisation i18n sous-phase 3a : infrastructure multilingue pour les noms d'items (2026-04-21)

> Pose la premiere pierre de la sous-phase 3 "Contenu de jeu multilingue" en ajoutant une colonne JSON `name_translations` sur l'entite `Item` et une methode `getLocalizedName(?string $locale)` avec fallback gracieux. Infrastructure pure, aucun template ni controller modifie : les consommateurs existants continuent d'utiliser `item.name` (rendu FR par defaut) tant que la sous-phase 3b ne les aura pas cables. Totalement retrocompatible, aucun contenu de donnees ajoute (les items existants conservent `name_translations = null`).

- [x] Migration `migrations/Version20260421ItemNameTranslations.php` : `ALTER TABLE game_items ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL`. Idempotente, reversible via `DROP COLUMN IF EXISTS`.
- [x] `App\Entity\Game\Item` : nouvelle propriete `?array $nameTranslations` (colonne Doctrine `json`, nullable). Nouvelle methode `getLocalizedName(?string $locale): string` — fallback sur `$this->name` si `$locale` est `null`/vide, si `$this->nameTranslations` est `null`, si la locale n'est pas presente, ou si sa valeur est une chaine blanche. Nouvelle methode `getNameTranslations(): array<string, string>` (defaut `[]`). Nouveau setter `setNameTranslations(?array $translations): Item` avec normalisation : cles non-string ou vides ignorees, valeurs non-string ou blanches ignorees, tableau final ramene a `null` si vide (stockage compact en DB). Retourne `$this` (fluent, coherent avec les autres setters de l'entite).
- [x] Tests `tests/Unit/Entity/Game/ItemLocalizationTest.php` (7 cas) : `testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations` (null/vide/FR/EN tous sans traductions retournent le nom de base), `testGetLocalizedNameReturnsMatchingTranslation` (EN + DE lookup), `testGetLocalizedNameFallsBackWhenLocaleMissing` (ES/JA sans entree), `testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys` (cle vide, valeur blanche, valeur vide tous filtres), `testSetNameTranslationsWithNullResetsStorage`, `testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull` (compaction en `null`), `testGetNameTranslationsDefaultsToEmptyArray`.
- [x] Roadmap : `SPRINT_12.md` sous-phase 3 decoupee en 3a (livree), 3b (cablage templates) et 3c (fixtures EN). `ROADMAP_TODO_INDEX.md` avancement Sprint 12 met a jour la ligne 135.

**Diff** : ~65 lignes `Item.php` + ~75 lignes tests + 27 lignes migration + roadmap. Aucun template touche, aucun fixture modifie, aucun controller ou service. Isolation totale : debloque la sous-phase 2c (traduction EN des items) et la sous-phase 3b (cablage dans templates/helpers) sans preempter leurs decisions.

---

## 131 — Events live & outils GM sous-phase 2a : nouveau type `gathering_bonus` (2026-04-21)

> Premiere sous-phase de la tache 131 sous-phase 2 ("Types : spawn de boss special, buff global, quete ephemere"). Le "buff global" est materialise par un nouveau type d'evenement `TYPE_GATHERING_BONUS` qui multiplie temporairement la quantite de ressources recoltees via `GatheringManager::gather()`. Le pattern miroir `TYPE_XP_BONUS` / `TYPE_DROP_BONUS` est strictement reproduit (stacking multiplicatif, filtre global vs map-specific, parametre `multiplier` en JSON), ce qui permet a l'admin de lancer un "Festival de la recolte" aussi simplement qu'un "Festival de la Lune" (XP bonus) ou qu'une "Chasse abondante" (drop bonus). Les autres sous-types de la sous-phase 2 sont deja partiellement couverts : `TYPE_BOSS_SPAWN` par `WorldBossManager`, `TYPE_INVASION` par `InvasionManager`, et les bonus multiplicatifs globaux XP/drop/gathering couvrent la notion de "buff global". Seule la "quete ephemere" reste a concevoir (decision reportee : dependrait d'une evolution de l'entite `Quest` qui n'a pas d'impact urgent).

- [x] `App\Entity\App\GameEvent` : nouvelle constante `TYPE_GATHERING_BONUS = 'gathering_bonus'` + entree dans `getTypeLabel()` ("Bonus recolte").
- [x] `App\GameEngine\Event\GameEventBonusProvider::getGatheringMultiplier(?Map $map = null): float` : reutilise le meme `getMultiplier()` prive que les deux methodes existantes (resoud la filtration `status = ACTIVE` + `type = TYPE_GATHERING_BONUS` + match map_id ou event global). Retourne `1.0` en l'absence d'event actif.
- [x] `App\GameEngine\Gathering\GatheringManager` : nouvelle dependance `GameEventBonusProvider` (auto-wiring Symfony preserve la retrocompatibilite DI). `gather()` scale la quantite finale via `$quantity = max(1, (int) round($baseQuantity * $multiplier))`. Le comportement par defaut (multiplier=1.0) est strictement identique a l'ancien (round preserve l'entier, max garantit quantite >= 1).
- [x] `App\Controller\Admin\GameEventController::history()` : `TYPE_GATHERING_BONUS` ajoute a la whitelist du filtre `type`, donc les events de ce type apparaissent dans l'historique et peuvent etre filtres.
- [x] Templates admin : `admin/event/form.html.twig` expose le choix "Bonus recolte" a la creation/modification, `admin/event/index.html.twig` et `admin/event/history.html.twig` rendent un badge emeraude (`bg-emerald-900 text-emerald-300`) pour differencier le type. Filtre rapide "Bonus recolte" ajoute dans `history.html.twig`.
- [x] `App\DataFixtures\GameEventFixtures` : nouvel event `event_recolte_abondante` (scheduled +5d → +8d, multiplier 1.5) pour peupler les environnements de dev/test.
- [x] Tests `GameEventBonusProviderTest` etendus (10 cas au total, +2 nouveaux) : `testReturnsDefaultMultiplierWhenNoActiveEvents` couvre desormais les trois methodes, nouveau `testReturnsGatheringMultiplierFromActiveEvent` (multiplier=2.0) et `testGatheringMultiplierIndependentOfDropMultiplier` (independance stricte entre les deux types via `willReturnCallback` + `match($criteria['type'])`).
- [x] Roadmap : `SPRINT_11.md` 131 sous-phase 2a cochee + detail implementation + etat d'avancement mis a jour ; `ROADMAP_TODO_INDEX.md` a synchroniser (ajouter 131 sous-phase 2a).

**Diff** : ~40 lignes PHP (entite + provider + manager + controller) + ~10 lignes Twig + ~40 lignes de tests + ~10 lignes de fixtures + roadmap. Aucune migration Doctrine, aucune dependance ajoutee, aucune modification de signature cassante. Isolation stricte : meme si aucun event de type `gathering_bonus` n'est jamais cree, le code se comporte comme avant (multiplier=1.0 par defaut). Prepare l'integration future aux autres systemes de recolte hors `GatheringManager` : `HarvestManager` (minerai / plantes), `FishingManager` (mini-jeu de peche avec tension bar, sous-phase 1 de 133), `ButcheringManager` (depecage post-combat) sont independants et devront explicitement appeler `getGatheringMultiplier()` pour beneficier de l'effet.

---

## 133 — Mini-jeux sous-phase 1 : peche active avec zone parfaite (2026-04-21)

> Premiere sous-phase de la tache 133 (Mini-jeux). La boucle de base du mini-jeu de peche avec barre de tension oscillante (panel Stimulus + `FishingManager::completeFishing` + route `POST /api/gathering/fish`) existait deja en production (succes 30-70, echec <30 sans usure, echec >70 avec usure x2). Cette sous-phase introduit une **zone parfaite** 45-55 qui recompense la precision du joueur en preservant la durabilite de la canne a peche (aucune reduction) tout en lui donnant un feedback visuel distinct. Aucune nouvelle mecanique de drop ou de rarete introduite (coherent avec la regle PvE cooperative du projet).

- [x] `App\GameEngine\Job\FishingManager` : constantes publiques `SUCCESS_MIN = 30`, `SUCCESS_MAX = 70`, `PERFECT_MIN = 45`, `PERFECT_MAX = 55`. Detection `perfect = $tension >= PERFECT_MIN && $tension <= PERFECT_MAX` dans la branche succes. Si `perfect`, la canne n'est pas reduite (skip `$fishingRod->reduceDurability(1)`). Le flush final est conserve pour persister `setUsedAt` sur le spot.
- [x] Signature de retour etendue : `array{success: bool, perfect: bool, item: ?array, message: string}`. Ajout de la cle `perfect` dans toutes les branches (false sur fail low, fail high, no-rod ; true uniquement en zone parfaite). Message prefixe par `Parfait !` quand `perfect && caughtItem !== null`. Retrocompatible pour les consommateurs qui ignorent la nouvelle cle.
- [x] `templates/game/map/index.html.twig` : ajout d'une bande `bg-green-400/70` sur `left: 45%; width: 10%` dans la barre de tension, avec `title="{{ 'game.fishing.perfect_zone'|trans }}"` pour l'accessibilite. La bande se superpose a la zone de succes existante (`bg-green-700/50` sur 30-70) pour marquer visuellement la sous-zone parfaite.
- [x] `assets/controllers/fishing_controller.js` : le gestionnaire `catch()` lit `result.perfect` et applique `text-yellow-300 text-sm mt-2 font-bold` au message quand c'est vrai, `text-green-400 ...` sinon. Ne modifie pas la logique d'oscillation, ni la fermeture auto 2s.
- [x] Traductions FR + EN : `game.fishing.perfect_zone` (FR = "Zone parfaite — aucune usure de la canne", EN = "Perfect zone — no rod wear"). Parite stricte conservee.
- [x] Tests `tests/Unit/GameEngine/Job/FishingManagerTest.php` (nouveau, 6 cas) : `testCompleteFishingWithoutRodReturnsFailure` (no flush, no dispatch), `testCompleteFishingTooLowFails` (tension 10, no reduceDurability, dispatch unique), `testCompleteFishingTooHighDamagesRod` (tension 85, `reduceDurability(2)`, flush), `testCompleteFishingNormalSuccessReducesDurability` (tension 35, `reduceDurability(1)`, item retourne, message sans prefixe), `testCompleteFishingPerfectPreservesDurability` (tension 50, **never** reduceDurability, message prefixe `Parfait !`), `testCompleteFishingPerfectBoundariesAreInclusive` (tension 45 et 55 sont toutes les deux parfaites).
- [x] Roadmap : `SPRINT_11.md` 133 — premier bullet coche (mini-jeu timing existait deja, avec note d'implementation), troisieme bullet etendu avec sous-phase 1 livree ; `ROADMAP_TODO_INDEX.md` avancement Sprint 11 (133 sous-phase 1) mis a jour.

**Diff** : ~30 lignes FishingManager + ~190 lignes de tests + ~10 lignes Twig/JS + 2 lignes de traduction + roadmap. Aucune migration, aucun changement de schema, aucune dependance ajoutee. Isolation stricte : le mini-jeu reste fonctionnel a l'identique pour les joueurs qui tombent hors de la zone 45-55 (comportement 30-70 classique inchange). Prepare la sous-phase 2 (courses entre joueurs) et les futures recompenses specifiques par mini-jeu (bullet 3 sera completement ferme quand les courses et le depecage auront leur propre bonus).

---

## 131 — Events live & outils GM sous-phase 4 : annonce globale Mercure (2026-04-21)

> Ferme la sous-phase 4 de la tache 131. La chaine complete serveur->client existe deja depuis la tache 79 (2026-03-22) : `GameEventAnnouncementHandler` souscrit a `GameEventActivatedEvent::NAME`, publie sur le topic Mercure `event/announce` (payload `{topic, type: 'activated', event: {id, name, type, typeLabel, description, endsAt, mapId}}`), et `assets/controllers/event_notification_controller.js` affiche un toast + met a jour le HUD badge cote client. Le trigger manuel existait aussi deja via `GameEventController::toggle()` qui dispatche l'event sur transition `SCHEDULED -> ACTIVE`, mais le controller admin n'avait aucune couverture de test. Cette sous-phase ferme ce gap de non-regression sans toucher au code applicatif — garantit que la chaine "admin clique Activer -> annonce Mercure globale" ne se brisera pas silencieusement lors de futures evolutions du toggle.

- [x] `tests/Functional/Controller/Admin/GameEventControllerToggleTest.php` : nouveau fichier de tests unitaires dedies a `GameEventController::toggle()`, pattern container/twig mock aligne sur `GameEventHistoryControllerTest` et `AcquireControllerTest` (mock `ContainerInterface` qui expose `router`, `request_stack` avec session FlashBag, et `security.csrf.token_manager`).
- [x] 3 cas couverts : (1) `testToggleFromScheduledToActiveDispatchesActivatedEvent` — verifie que le CSRF valide + status SCHEDULED declenche `em->flush()`, dispatch d'un `GameEventActivatedEvent` portant l'entite cible avec le nom d'event `GameEventActivatedEvent::NAME`, log admin `toggle / GameEvent / 42 / "Test event → active"`, status final `ACTIVE`, redirect 302 ; (2) `testToggleFromActiveToCompletedDoesNotDispatchActivatedEvent` — ACTIVE -> COMPLETED declenche le flush mais jamais le dispatch (evite un double-fire de l'annonce a la cloture) ; (3) `testToggleWithInvalidCsrfDoesNothing` — CSRF rejete = no-op complet (aucun flush, aucun dispatch, aucun log, status inchange).
- [x] Zero modification de code applicatif : aucun diff sur `GameEventController`, `GameEventAnnouncementHandler`, templates ou configuration. Uniquement un nouveau fichier de tests (~150 lignes) qui documente le comportement existant et previent la regression.
- [x] Roadmap : `SPRINT_11.md` sous-phase 4 cochee + detail d'implementation + cross-reference tache 79 ; `ROADMAP_TODO_INDEX.md` avancement Sprint 11 mis a jour (131 sous-phases 3 + 4).

**Diff** : ~150 lignes (1 fichier de tests + mises a jour roadmap). Isolation totale : independante de la PR #429 (sous-phase 1 "Lancer maintenant") qui reutilisera exactement le meme pipeline `dispatch -> GameEventAnnouncementHandler -> Mercure`. Independante de la sous-phase 2 (types avances) qui s'appuiera aussi sur ce meme pipeline.

---

## 131 — Events live & outils GM sous-phase 3 : historique des events lances (2026-04-21)

> Expose dans l'admin une page dediee a l'historique des evenements termines ou annules. Avant cette sous-phase, un admin pouvait seulement filtrer le planificateur `/admin/events` par `?status=completed` ou `?status=cancelled` sur l'UI principale, sans vue consolidee, sans tri par date de fin, sans colonne "duree" et sans filtrage combine status + type. La page `/admin/events/history` comble ces lacunes et prepare la sous-phase 4 (annonces Mercure) en separant la consultation retrospective de la gestion en cours. Independante de la sous-phase 1 (PR #429 "Lancer maintenant") et de la sous-phase 2 (types avances) : ne touche aucune des actions existantes, ne modifie pas `index.html.twig` (evite les conflits de merge avec la PR #429).

- [x] `App\Controller\Admin\GameEventController::history()` : nouvelle action `GET /admin/events/history` (nom `admin_event_history`, methode GET uniquement). Construit un `QueryBuilder` filtrant sur `e.status IN (:pastStatuses)` avec pour parametre par defaut `[STATUS_COMPLETED, STATUS_CANCELLED]`. Accepte en query `status` (whitelist stricte `completed|cancelled`) et `type` (whitelist stricte des 5 constantes `TYPE_*` de `GameEvent`) — toute valeur hors whitelist est ignoree silencieusement, aucun DQL n'est construit pour eviter l'injection.
- [x] Tri par `e.endsAt DESC` (les events termines les plus recents apparaissent en premier). Pagination 25 lignes/page via `$request->query->getInt('page')` + `setFirstResult` / `setMaxResults`.
- [x] Template `templates/admin/event/history.html.twig` : en-tete avec retour vers `/admin/events`, deux rangees de filtres (status : Tous / Termines / Annules, type : Tous / Boss / Bonus XP / Bonus drop / Invasion / Personnalise), tableau a 7 colonnes (Nom / Type / Statut / Debut / Fin / Duree / Carte). Duree calculee cote template via `(event.endsAt.timestamp - event.startsAt.timestamp) / 3600` arrondie a 0.1h (protection plancher 0 pour les events corrompus avec `endsAt < startsAt`). Reutilise `admin/_pagination.html.twig`. Aucun lien ajoute dans `index.html.twig` — la navigation passe par la sidebar qui surligne `admin_event_*` quelle que soit la sous-route.
- [x] Tests `tests/Functional/Controller/Admin/GameEventHistoryControllerTest.php` (7 cas, pattern `DashboardControllerTest` avec mock `EntityManagerInterface` + `Environment` + `ContainerInterface`) : `testHistoryRendersHistoryTemplate` (template name + pagination par defaut), `testHistoryFiltersOnPastStatusesByDefault` (assertion sur le `where('e.status IN (:pastStatuses)')` + `setParameter('pastStatuses', [COMPLETED, CANCELLED])`), `testHistoryAppliesStatusFilterWhenProvided` (status=cancelled ajoute `andWhere('e.status = :status')`), `testHistoryIgnoresInvalidStatusFilter` (status=scheduled ne declenche aucun `andWhere` mais est renvoye tel quel au template pour surligner aucun onglet), `testHistoryAppliesTypeFilterWhenWhitelisted` (type=boss_spawn ajoute `andWhere('e.type = :type')`), `testHistoryIgnoresUnknownType` (type=not_a_real_type ne declenche aucun `andWhere`), `testHistoryComputesPaginationFromTotal` (total=60, page=2 -> `currentPage=2`, `totalPages=3=ceil(60/25)`).
- [x] Roadmap : `SPRINT_11.md` 131 sous-phase 3 cochee + detail implementation, avancement Sprint 11 met a jour la ligne 131. Compteurs `ROADMAP_TODO_INDEX.md` synchrones (Sprint 11 : 130 s1+s5, 131 s3, 132 s1+s2a+s2b+s3+s4a+s4b.1+s4b.2 livrees).

**Diff** : ~80 lignes PHP + ~85 lignes Twig + ~190 lignes de tests + ~20 lignes roadmap. Aucune migration Doctrine, aucune dependance ajoutee, aucun template existant modifie (evite les conflits avec la PR #429). Isolation stricte : meme si la PR #429 n'est jamais mergee, cette sous-phase reste fonctionnelle en production — elle n'utilise que des actions existantes (`toggle`, `cancel`, `delete`, changement de statut via `SeasonTickCommand` ou autres lifecycle subscribers).

---

## 135 — Localisation i18n sous-phase 2a : parite de cles FR/EN sur l'UI (2026-04-21)

> Complete la premiere passe de traduction anglaise de l'UI en comblant les cles presentes en FR mais manquantes en EN. Avant cette sous-phase, la page `/game/bestiary` et deux liens de navigation principaux (`Bestiaire`, `Artisanat`) affichaient leur identifiant de cle brute en anglais (`game.bestiary.title`, `game.nav.bestiary`, `game.nav.craft`, etc.) a cause du fallback Symfony. Apres, parite stricte entre les deux fichiers : 432 cles de chaque cote.

- [x] Audit systematique de `translations/messages.{fr,en}.json` via `flatten` + diff ensembliste : 19 cles manquantes en EN, 0 cle orpheline en FR.
- [x] Ajout dans `messages.en.json` : `game.nav.bestiary` = "Bestiary", `game.nav.craft` = "Crafting" (ordre aligne avec FR, inseres apres `settings`).
- [x] Ajout du bloc complet `game.bestiary.*` (17 cles) avec preservation des placeholders `%count%`, `%total%`, `%level%`, `%percent%`, `%kills%`, `%required%`, `%monster%` : `title`, `subtitle`, `discovered`, `total_kills`, `kills`, `tier_weaknesses`, `tier_loot`, `tier_title`, `weaknesses`, `loot_table`, `loot_probability`, `next_tier`, `empty`, `empty_hint`, `boss`, `level`, `difficulty`.
- [x] Verification : 432 cles FR = 432 cles EN (parite totale, diff ensembliste = ensemble vide).
- [x] Roadmap : `SPRINT_12.md` sous-phase 2 decoupee en 2a (livree), 2b (audit `debug:translation`) et 2c (contenu de jeu multilingue) ; avancement Sprint 12 mis a jour.

**Diff** : ~25 lignes ajoutees sur 1 fichier JSON, 0 modification PHP/Twig (les templates referencaient deja ces cles), 0 impact sur FR. Note : aucun test PHPUnit ajoute — la modification ne touche que des donnees de traduction validees syntaxiquement par `json.load()`.

---

## 132 — Classement saisonnier sous-phase 4b.2 : badges cosmetiques sur les titres de podium (2026-04-21)

> Materialise la promesse de "recompense cosmetique" de la sous-phase 4b. Chaque titre de podium (deja persiste depuis la sous-phase 4a) porte desormais un embleme visuel unique par onglet — epees croisees pour les chasseurs, etendard pour les aventuriers, etoile du savoir pour les savants — en plus du code couleur rang (or/argent/bronze) deja en place. Implementation materialisee au niveau du badge (pas d'infrastructure `PlayerItem`) pour eviter de creer des items cosmetiques fictifs et rester proportionnee au gain visuel.

- [x] Entite `App\Entity\App\PlayerSeasonReward` : nouveau champ `cosmeticIcon` (`VARCHAR(60)`, nullable, Doctrine), constructeur enrichi d'un parametre optionnel `?string $cosmeticIcon = null` (normalisation : chaine whitespace-only retombe sur `null`) + getter `getCosmeticIcon()`. Retrocompatible : tous les appels existants (`new PlayerSeasonReward(...)` sans icone) restent valides.
- [x] Migration idempotente `Version20260421SeasonRewardCosmeticIcon` : `ALTER TABLE player_season_reward ADD COLUMN IF NOT EXISTS cosmetic_icon VARCHAR(60) DEFAULT NULL`, `down` symetrique `DROP COLUMN IF EXISTS`.
- [x] Service `App\GameEngine\Season\SeasonRewardsManager` : nouveau mapping interne `COSMETIC_ICONS[tab][rank]` (9 identifiants `{hunter,adventurer,scholar}_{gold,silver,bronze}`). `awardTab()` resout l'icone via `resolveCosmeticIcon()` (fallback `null` si tab inconnu) et la passe au constructeur de `PlayerSeasonReward`. Convention de nommage : `<tab_theme>_<rank_metal>`.
- [x] Template `templates/game/ranking/index.html.twig` : badge des titres etendu. Le theme de l'icone est derive via `title.cosmeticIcon|default('')|split('_')|first`, puis mappe a un glyphe HTML dedie (`&#9876;` epees croisees / `&#9873;` etendard / `&#10070;` etoile a 4 branches) avec fallback sur l'etoile originale (`&#9733;`) pour les titres legacy sans icone. Tooltip etendu avec le label de l'embleme.
- [x] Template `templates/game/profile/show.html.twig` : meme logique appliquee au badge du profil public, convention visuelle unifiee avec `/game/rankings`.
- [x] Traductions FR/EN : `game.ranking.titles.cosmetic.{hunter,adventurer,scholar}` avec une courte description ("Embleme du chasseur (épées croisées)" / "Hunter's crest (crossed blades)", etc.) concatenee au tooltip principal via `—`.
- [x] Tests `SeasonRewardsManagerTest` etendus a 7 cas (etait 5) : le cas multi-onglets gagne 4 assertions sur `getCosmeticIcon()` (hunter_gold/silver/bronze + adventurer_gold), ajout de `testAwardPodiumAssignsScholarIconOnXpTab` (onglet xp rang 1 -> `scholar_gold`), `testEntityAcceptsCosmeticIconViaConstructor` (propagation directe du parametre), `testEntityNormalizesBlankCosmeticIconToNull` (chaine whitespace -> `null`).
- [x] Roadmap : `SPRINT_11.md` sous-phase 4b.2 cochee et detaillee, ligne d'avancement 132 mise a jour ; `ROADMAP_TODO_INDEX.md` a synchroniser (sous-phase 4b.2 livree).

**Diff** : ~180 lignes ajoutees sur 9 fichiers. Aucune mise a jour de fixtures ou d'inventaire. Independant de la PR 441 (Hall of Fame sous-phase 4b.1c) — les deux sous-phases partagent l'infrastructure `PlayerSeasonReward` + `PlayerSeasonRankingSnapshotRepository` mais ne se chevauchent pas.

---

## 132 — Classement saisonnier sous-phase 4b.1b : titres de podium sur le profil public (2026-04-20)

> Prolongement direct de la sous-phase 4b.1 : les titres saisonniers d'un joueur sont desormais visibles par tous les visiteurs de son profil public (`/game/player/{id}/profile`), pas uniquement sur son propre `/game/rankings`. Permet aux joueurs de mettre en valeur leurs accomplissements saisonniers aupres du reste de la communaute et complete l'affichage avant la sous-phase 4b.2 (recompenses cosmetiques / items exclusifs).

- [x] `PlayerProfileController::show` : injection de `PlayerSeasonRewardRepository`, nouveau parametre template `playerTitles = findByPlayer($targetPlayer)` (les titres sont ceux du joueur cible, pas du viewer, pour que tout visiteur voie les accomplissements du profil consulte).
- [x] `templates/game/profile/show.html.twig` : rangee de badges ajoutee dans l'en-tete du profil, sous le `prestigeTitle` (ou a sa place si absent). Reutilise exactement le meme code couleur (or/argent/bronze selon `title.rank`) et le meme tooltip (`game.ranking.titles.season_tooltip`) que `/game/rankings` pour unifier la convention visuelle — zero traduction supplementaire requise.
- [x] Tests `PlayerProfileControllerTest` : setup etendu avec mock `PlayerSeasonRewardRepository` injecte dans le constructeur (defaut `findByPlayer -> []` pour ne pas casser les tests existants). Deux nouveaux cas : `testShowPassesPlayerTitlesToTemplate` (un `findByPlayer($targetPlayer)` retourne 2 titres mockes -> propage au template via `playerTitles`) et `testShowPassesEmptyTitlesWhenPlayerHasNone` (defaut empty -> tableau vide dans le template).
- [x] Roadmap : `SPRINT_11.md` sous-phase 4b.1b cochee (detaillant l'implementation), `ROADMAP_TODO_INDEX.md` a mettre a jour si le compteur d'avancement Sprint 11 varie.

---

## 132 — Classement saisonnier sous-phase 4b.1 : affichage des titres de podium (2026-04-20)

> Expose dans l'UI les titres persistes par la sous-phase 4a. A l'ouverture de `/game/rankings`, le joueur voit desormais la liste de ses titres saisonniers (tous onglets confondus) sous forme de badges colores or/argent/bronze au-dessus du resume de rang. Premiere utilisation concrete de la table `player_season_reward`.

- [x] `RankingController` : injection de `PlayerSeasonRewardRepository`, ajout de `playerTitles = findByPlayer($player)` dans les parametres template (toutes saisons confondues, tri par `awardedAt` decroissant via le repository).
- [x] `templates/game/ranking/index.html.twig` : nouvelle section "Mes titres saisonniers" (visible uniquement si `playerTitles|length > 0`), badges `rounded-full` avec code couleur par rang (rang 1 = or / rang 2 = argent / rang 3 = bronze), libelle direct du `title.titleLabel`, tooltip `saison — onglet` via `title` HTML.
- [x] Traductions FR/EN : `game.ranking.titles.heading` ("Mes titres saisonniers" / "My seasonal titles"), `game.ranking.titles.season_tooltip` ("%season% — %tab%").
- [x] Tests `RankingControllerTest` : setup etendu avec mock `PlayerSeasonRewardRepository` (defaut `findByPlayer -> []` pour ne pas casser les 7 tests existants), nouveau test `testIndexPassesPlayerTitlesToTemplate` verifiant qu'un `PlayerSeasonReward` construit sur (saison mockee, player, Kills, 1, "Champion ... — Saison 1") est bien injecte dans `playerTitles`.
- [x] Roadmap : `SPRINT_11.md` sous-phase 4 decoupee en 4b.1 (livree) et 4b.2 (cosmetiques + items exclusifs restants), compteurs d'index mis a jour.

---

## 132 — Classement saisonnier sous-phase 4a : titres de podium de fin de saison (2026-04-20)

> Pose la fondation des recompenses de fin de saison. A partir des snapshots deja figes par la sous-phase 3, attribue un titre cosmetique au top-3 (Champion / Vice-champion / Troisieme) de chaque onglet de classement (kills / quests / xp). Les titres ne sont pas encore exposes dans l'UI — la sous-phase 4b couvrira l'affichage dans le profil + les cosmetiques / items exclusifs.

- [x] Entite `App\Entity\App\PlayerSeasonReward` (table `player_season_reward`, UNIQUE `season_id + tab + player_id`, index `(season_id)` et `(player_id)`, FK CASCADE vers `influence_season` et `player`). Colonnes : `tab` (enum string `RankingTab`), `rank_position` contrainte 1-3 via le constructeur (`InvalidArgumentException` si hors podium), `title_label` (VARCHAR 120, rejette chaine vide/whitespace), `awarded_at` (`datetime_immutable` stampe a la construction).
- [x] Repository `App\Repository\PlayerSeasonRewardRepository` : `countForSeason(InfluenceSeason)` (support idempotence), `findByPlayer(Player)` trie par date d'attribution decroissante (pour futur affichage historique).
- [x] Migration idempotente `Version20260420SeasonReward` : `CREATE TABLE IF NOT EXISTS`, `CREATE UNIQUE INDEX IF NOT EXISTS`, FK CASCADE via blocs `DO $$ BEGIN IF NOT EXISTS ...` (conforme CLAUDE.md piege Postgres).
- [x] Service `App\GameEngine\Season\SeasonRewardsManager::awardPodium(InfluenceSeason)` : lit les snapshots via `PlayerSeasonRankingSnapshotRepository::findBySeasonAndTab` pour chaque `RankingTab::cases()`, filtre les rangs 1 a 3 (constante `PODIUM_SIZE = 3`), persiste une `PlayerSeasonReward` par entree. Generation du libelle : `sprintf('%s %s — Saison %d', prefix[rank], tabLabel[tab], season.number)`, avec prefixes `Champion / Vice-champion / Troisieme` et labels `des chasseurs / des aventuriers / du savoir`. Idempotent via `countForSeason`. Retourne `['kills' => N, 'quests' => N, 'xp' => N]`.
- [x] Hook `SeasonTickCommand::handleExpiredSeasons` : injection du nouveau service, appel `awardPodium($activeSeason)` juste apres `snapshot($activeSeason)` et avant `endSeason`. Sortie console enrichie avec "Titres du podium attribués : %d kills, %d quêtes, %d XP.".
- [x] Tests `SeasonRewardsManagerTest` (5 cas) : `testAwardsPodiumForEachTabAndIgnoresBelowTopThree` (4 kills top-4 + 1 quest + 0 xp -> 4 persists, assertions libelles exacts incluant le rang 2/3), `testAwardPodiumIsIdempotentWhenAlreadyAwarded` (aucune lecture snapshot ni persist ni flush), `testAwardPodiumFlushesEvenWhenAllTabsEmpty` (flush meme sans persist), `testEntityRejectsRankOutsidePodium` (rang 4 = exception), `testEntityRejectsEmptyTitleLabel` (whitespace = exception).
- [x] `SeasonTickCommandTest::testEndExpiredSeasonAttributesControlAndEnds` etendu : mock `SeasonRewardsManager`, assertion `awardPodium` appele une fois, verification que "Titres du podium attribués" apparait dans la sortie.
- [x] Roadmap : `SPRINT_11.md` 132 sous-phase 4 decoupee en 4a (livree) et 4b (cosmetiques + affichage), `ROADMAP_TODO_INDEX.md` compteurs et note Sprint 11 a mettre a jour.

---

## 132 — Classement saisonnier sous-phase 3 : archivage a la fin de saison (2026-04-20)

> Quand une saison d'influence se termine, le top-N (par defaut 50) de chaque onglet du classement individuel (`kills` / `quests` / `xp`) est fige dans une table d'archive. Prepare la sous-phase 4 (recompenses de fin de saison) et permet plus tard l'exposition d'un historique consultable `/game/rankings?season=N`. Aucun changement UI dans cette sous-phase (le classement temps reel reste inchange et reste affiche par defaut).

- [x] Enum string `App\Enum\RankingTab` (`kills` / `quests` / `xp`) pour typer la colonne `tab` de l'archive.
- [x] Entite `App\Entity\App\PlayerSeasonRankingSnapshot` (table `player_season_ranking_snapshot`) : FK CASCADE vers `influence_season` et `player`, UNIQUE `season_id + tab + rank_position`, index `(season_id, tab)` + `player_id`. Colonnes : `rank_position` (>=1), `player_name` (nom fige au moment du snapshot, resiste au renommage), `total_value` en `bigint` (supporte de gros totaux XP), `snapshotted_at` en `datetime_immutable`. Constructeur strict (rejette rank<1 et totalValue<0 via `InvalidArgumentException`).
- [x] `App\Repository\PlayerSeasonRankingSnapshotRepository` : `findBySeasonAndTab(InfluenceSeason, RankingTab)` (lecture ordonnee par rang), `countForSeason(InfluenceSeason)` (support de l'idempotence).
- [x] Migration idempotente `Version20260420SeasonRankingSnapshot` : `CREATE TABLE IF NOT EXISTS`, `CREATE UNIQUE INDEX IF NOT EXISTS`, FK CASCADE via blocs `DO $$ BEGIN IF NOT EXISTS ...` (conforme CLAUDE.md piege Postgres).
- [x] Service `App\GameEngine\Season\SeasonRankingSnapshotService::snapshot(InfluenceSeason, int $limit = 50)` : pour chaque onglet, lit le top-N via les 3 repositories existants (`PlayerBestiaryRepository::findTopKillers`, `PlayerQuestCompletedRepository::findTopQuestCompleters`, `DomainExperienceRepository::findTopXpEarners`), persiste chaque ligne avec son rang 1-based, retourne le decompte `['kills' => N, 'quests' => N, 'xp' => N]`. Idempotent : si `countForSeason > 0`, retourne `[0, 0, 0]` sans rien persister.
- [x] Hook `SeasonTickCommand::handleExpiredSeasons` : injection du nouveau service, appel `snapshot($activeSeason)` entre `updateTitles` et `endSeason`. Sortie console enrichie avec "Classement archivé : %d kills, %d quêtes, %d XP.".
- [x] Tests unitaires `SeasonRankingSnapshotServiceTest` (6 cas) : `testSnapshotPersistsTopRowsForEachTab` (4 persists assertionnes avec rang / nom / valeur), `testSnapshotIsIdempotentWhenAlreadyArchived` (aucune lecture ni flush si `countForSeason > 0`), `testSnapshotHandlesEmptyRankings`, `testSnapshotRespectsCustomLimit` (passe la limite aux 3 repos), `testEntityRejectsInvalidRank`, `testEntityRejectsNegativeTotal`.
- [x] `SeasonTickCommandTest` etendu : mock `SeasonRankingSnapshotService`, assertion `snapshot($season)` appele une fois dans `testEndExpiredSeasonAttributesControlAndEnds`, verification que "Classement archivé" apparait dans la sortie.
- [x] Roadmap : `SPRINT_11.md` 132 sous-phase 3 cochee (avancement 3/4), `ROADMAP_TODO_INDEX.md` mis a jour.

---

## 130 — Montures & deplacement rapide sous-phase 5 : fast travel verrouille par decouverte (2026-04-20)

> Le service `GoldSinkManager::fastTravel` existait deja (cout 100 gils, teleporte vers la capitale d'une region) mais autorisait n'importe quelle region disposant d'une `capitalMap`. Cette sous-phase ajoute la mecanique de decouverte : un joueur ne peut se teleporter que vers les regions ou il a deja mis les pieds, ce qui transforme l'exploration en prerequis (et en valeur) du raccourci. Independante des sous-phases 2-4 (ownership / equipement de monture / animations) — peut etre livree avant que PR #428 ne merge.

- [x] Entite `App\Entity\App\PlayerVisitedRegion` (table `player_visited_region`, UNIQUE `player_id + region_id`, index `player_id`, FK CASCADE vers `player` et `region`, `first_visited_at` en `datetime_immutable`). Constructeur `__construct(Player, Region)` qui stamp `first_visited_at` immediatement.
- [x] `App\Repository\PlayerVisitedRegionRepository` : `hasVisited(Player, Region): bool` (COUNT scalaire) et `findVisitedRegionIds(Player): int[]` (DQL `IDENTITY(pvr.region)` avec hydratation legere pour le filtre des destinations).
- [x] Migration `Version20260420PlayerVisitedRegion` : `CREATE TABLE IF NOT EXISTS`, `CREATE UNIQUE INDEX IF NOT EXISTS`, FK CASCADE via blocs `DO $$ BEGIN IF NOT EXISTS ...` (idempotent, conforme CLAUDE.md piege Postgres).
- [x] `App\GameEngine\Region\RegionDiscoveryTracker` (subscriber `EventSubscriberInterface`) : enregistre la region courante a chaque `PlayerMovedEvent`. Methode publique `recordCurrentRegion(Player, bool $flush = true): bool` (no-op si pas de map, pas de region, ou deja visitee).
- [x] `GoldSinkManager` modifie : injection du nouveau repository ; `getAvailableDestinations(Player)` filtre desormais par regions visitees (en plus du filtre `capitalMap !== null` et `currentRegion !== r`) ; `fastTravel(Player, Region)` rejette explicitement les destinations non visitees avec le message "Vous devez d'abord decouvrir cette region a pied.".
- [x] Tests `GoldSinkManagerTest` etendus (7 cas existants + 4 nouveaux) : `testGetAvailableDestinationsKeepsOnlyVisitedRegionsExceptCurrent` (4 regions, garde uniquement la region 2 visitee, exclut current/non-visitee/sans-capitale), `testFastTravelRejectsUnvisitedRegion` (gils preserves, aucun flush), `testFastTravelSucceedsWhenDestinationVisited` (player.map mis a jour, coordonnees centrees, gils debites), `testFastTravelStillRejectsCurrentRegionEvenIfVisited` (court-circuite avant l'appel hasVisited).
- [x] Tests `RegionDiscoveryTrackerTest` (5 cas) : `testOnPlayerMovedPersistsNewVisit`, `testRecordCurrentRegionIsIdempotent`, `testRecordCurrentRegionDoesNothingWithoutRegion` (pas de map / map sans region), `testRecordCurrentRegionWithoutFlush`, `testSubscribedEventsListsPlayerMoved`.
- [x] Roadmap : `SPRINT_11.md` 130 sous-phase 5 cochee + detail implementation, `ROADMAP_TODO_INDEX.md` mis a jour.

---

## 132 — Classement saisonnier sous-phase 2b : classement XP totale (2026-04-20)

> Troisieme classement de `/game/rankings` : XP totale agregee sur toutes les `DomainExperience` d'un joueur (toutes les voies de talent). Permet de suivre les profils les plus investis en progression, sans violer la regle "pas de niveau global" (CLAUDE.md n°6) — seule la somme des XP par domaine est exposee.

- [x] `DomainExperienceRepository` etendu avec 3 methodes alignees sur `PlayerBestiaryRepository` / `PlayerQuestCompletedRepository` : `getTotalXpEarned(Player)` (SUM de.totalExperience, COALESCE 0), `findTopXpEarners(int)` (SUM + GROUP BY de.player, HAVING > 0, hydratation `Player` via un second `findBy`), `getPlayerXpRank(Player)` (comptage des joueurs strictement au-dessus du total courant).
- [x] `RankingController` : `DomainExperienceRepository` injecte, constante `TAB_XP` ajoutee a la whitelist `TABS`. Branche `elseif TAB_XP === $tab` qui route vers les nouvelles methodes ; fallback `TAB_KILLS` inchange pour les valeurs inconnues.
- [x] Template `game/ranking/index.html.twig` : nouvel onglet `xp` (3e lien dans le header), `valueKey` resolu dynamiquement (`totalQuests` / `totalXp` / `totalKills`), `colLabel` via `game.ranking.col.xp`. Resume du rang etend le `if/elseif/else` avec la cle `your_xp`.
- [x] Traductions FR/EN etendues : `tab.xp` ("Expérience" / "Experience"), `col.xp` ("XP totale" / "Total XP"), `your_xp` ("Votre XP totale : %count%" / "Your total XP: %count%"). JSON validee syntaxiquement.
- [x] Tests `RankingControllerTest` portes de 5 a 7 cas : ajout de `testIndexXpTabShowsXpRanking` (top XP + rang + total personnel, `findTopKillers` / `findTopQuestCompleters` jamais appeles) et `testIndexHandlesUnrankedPlayerInXpTab` (XP=0 => rank=null, total=0, tableau vide). Les tests existants kills/quests ajoutent `$domainExperienceRepository->expects($this->never())` sur `findTopXpEarners` pour verifier l'etancheite inter-onglets.
- [x] Roadmap : `SPRINT_11.md` sous-phase 2b cochee (avancement 2b livree), `ROADMAP_TODO_INDEX.md` mis a jour (compteur et note Sprint 11).

---

## 132 — Classement saisonnier sous-phase 2a : classement quetes completees (2026-04-20)

> Extension de `/game/rankings` avec un second classement : nombre de quetes completees all-time. La page expose desormais un systeme d'onglets (`?tab=kills` / `?tab=quests`) qui partage le meme chrome (rang joueur, top 50, zero state).

- [x] `PlayerQuestCompletedRepository` dedie (couple `#[ORM\Entity(repositoryClass: ...)]` sur `PlayerQuestCompleted`) avec 3 methodes miroir du repo bestiaire : `countQuestsCompleted(Player)`, `findTopQuestCompleters(int)` (COUNT + GROUP BY + hydratation `Player` via un second `findBy`), `getPlayerQuestRank(Player)` (comptage des joueurs strictement au-dessus).
- [x] `RankingController` accepte `?tab=kills|quests` (whitelist, fallback silencieux vers `kills` si valeur inconnue). Les cles du template sont normalisees (`topEntries`, `playerTotal`, `tab`) pour reutiliser le meme markup.
- [x] Template `game/ranking/index.html.twig` : onglets cliquables (`<a href>`), table unique dont le header et la valeur varient selon `tab` (helper Twig `attribute(entry, valueKey)`). Zero state et resume du rang partages entre onglets.
- [x] Traductions FR/EN etendues : `tab.quests`, `col.quests`, `your_quests`. `your_rank_none` generalise ("Non classe" / "Unranked" sans mention kills).
- [x] Tests `RankingControllerTest` etendus a 5 cas : default tab kills (appels bestiaire uniquement), tab quests (appels quest repo uniquement, bestiaire jamais touche), tab inconnu (fallback kills), redirection sans player (aucun appel repo), joueur non classe dans l'onglet quests.
- [x] Roadmap : `SPRINT_11.md` sous-phase 2a cochee, avancement remis a jour ; sous-phase 2b (XP gagnee) isolee comme prochain jalon.

---

## Sprint 1 — Stabilite & Onboarding ✅ Termine (2026-04-11)

### 110 — Correction bugs connus & dette technique (final) — Audit GitHub issues ✅
> Cloture de la tache 110 avec l'audit des issues GitHub ouvertes et la priorisation. Aucune issue ouverte a la date de l'audit, tous les bugs critiques historiques sont corriges et couverts par des tests.
- [x] Audit complet documente dans `docs/audits/GITHUB_ISSUES_AUDIT_2026-04.md`
- [x] Verification via MCP GitHub : 0 issue ouverte, 0 PR ouverte
- [x] Processus de priorisation documente (Critique / Haute / Moyenne / Basse)
- [x] Synthese de la dette technique PHPStan (niveau 6, baseline stabilisee)
- [x] Recap des 7 checks de coherence DB integres en CI
- [x] Sprint 1 **entierement cloture** (Tasks 110, 111, 113) — prochaine etape : Sprint 4 (Sprints 2 et 3 deja termines)

### 110 — Correction bugs connus & dette technique (partiel) — Bugs critiques gameplay ✅
> Correction de 4 bugs gameplay critiques : attaque basique, loot, quetes, IA mobs.
- [x] Basic attack dispatch `MobDeadEvent` et `PlayerDeadEvent` quand la cible meurt (loot, quetes, achievements, materia XP fonctionnent pour l'attaque basique)
- [x] Loot items transferes vers l'inventaire joueur dans `FightLootProceedController` (items selectionnes ajoutes au sac, non-selectionnes supprimes, items orphelins nettoyes avant suppression des mobs)
- [x] Compteur monstres quetes regulieres plafonne a `necessary` (alignement avec le comportement des quetes quotidiennes et des autres types de tracking)
- [x] Division par zero evitee dans `MobActionHandler` via methode `getHpPercent()` (6 occurrences securisees)
- [x] Test unitaire `testUpdateMobKilledCapsAtNecessary` ajoute, test `FightAttackControllerTest` mis a jour

### 113 — Tutoriel / onboarding nouveau joueur (partiel) — Infrastructure tutoriel ✅
> Systeme de tutoriel en 5 etapes (deplacement → combat → inventaire → quetes → craft) avec progression automatique par evenements, skip, et achievement.
- [x] Enum `TutorialStep` (5 etapes avec label, objectif, navigation sequentielle)
- [x] Champ `tutorial_step` (smallint nullable) sur Player + migration
- [x] `PlayerFactory` initialise le tutoriel pour les nouveaux joueurs (step 0)
- [x] `TutorialManager` : getCurrentStep, advance, advanceIfOnStep, skip, complete
- [x] `TutorialProgressListener` (EventSubscriber) : avance auto sur PlayerMovedEvent, MobDeadEvent, FightLootedEvent, QuestCompletedEvent, CraftEvent
- [x] `TutorialController` : endpoint skip (POST, CSRF) + endpoint status (GET, JSON)
- [x] `TutorialCompletedEvent` dispatche a la fin du tutoriel
- [x] Achievement "Premiers pas" (slug: tutorial-complete, 100 gils) + `AchievementTracker` ecoute TutorialCompletedEvent
- [x] `TutorialExtension` Twig : fonction `tutorial_current_step()` avec cache
- [x] Bandeau tutoriel dans le layout du jeu (objectif, progression, bouton Passer)
- [x] 11 tests unitaires `TutorialManagerTest` (avancement, skip, step matching, completion event)

### 113 — Tutoriel / onboarding (partiel) — PNJ tuteur avec dialogues contextuels ✅
> PNJ guide "Lyra la Guide" dans le Village de Lumiere avec dialogues adaptes a l'etape tutoriel courante du joueur.
- [x] Conditions `tutorial_step` et `tutorial_completed` dans `PnjDialogParser` (branching conditionnel base sur la progression tutoriel)
- [x] PNJ "Lyra la Guide" (healer, coordonnees 20.18 pres du spawn) dans `VillageHubPnjFixtures`
- [x] 13 sentences de dialogue contextuel : une branche par etape (deplacement, combat, inventaire, quetes, artisanat) + post-tutoriel + details d'aide
- [x] 6 tests unitaires `PnjDialogParserTutorialTest` (match step, fallthrough, multi-values, completed, in-progress, no player)

### 113 — Tutoriel / onboarding (partiel) — Indicateurs visuels pour guider le joueur ✅
> Highlights pulses sur les liens de navigation et lien d'action rapide dans la banniere tutoriel.
- [x] Stimulus controller `tutorial-highlight` : applique une animation pulsee sur les liens de nav pertinents selon l'etape tutoriel active
- [x] Attributs `data-tutorial-route` sur les liens de nav (Carte, Quetes, Artisanat) pour desktop et mobile
- [x] Animation CSS `tutorial-pulse` (halo violet) et `tutorial-arrow-bounce` (fleche directionnelle)
- [x] Lien d'action rapide dans la banniere tutoriel : raccourci vers la page cible (ex: "→ Aller a la Carte")
- [x] Bouton "Plus" mobile egalement highlight quand les Quetes ou l'Artisanat sont l'etape courante

### 110 — Correction bugs connus & dette technique (partiel) — Nettoyage PHPStan ✅
> Nettoyage du code mort et reduction de la baseline PHPStan (528 → 507 entrees, -21).
- [x] Ajout des return types manquants : `supports(): bool` sur 6 handlers de combat, `mobDied(): void`, `isMob()/isPlayer(): bool`, `mount(): void`
- [x] Typage des proprietes : `LocaleListener::$defaultLocale` (constructor promotion), `FightNotificationHandler::$notifications` (array typed)
- [x] Correction logique `ItemHelper` : remplacement `$array[$key] ?? null` par structure `isset()` + `return` explicite
- [x] Nettoyage de 8 entrees stale du baseline (attributs/proprietes/conditions inexistants dans le code)

---

## Sprint 2 — Bestiaire & PNJ

### Sprint 2 DoD — Tests d'integration combat monstres tier 2-3 ✅
> Couverture de non-regression pour les monstres et boss introduits par la tache 141. Tests integrations via `AbstractIntegrationTestCase` (vraie DB + fixtures, transaction par test).
- [x] `Tier23CombatIntegrationTest` : 7 tests couvrant le chargement, les resistances elementaires, les phases de boss et le flux de combat
- [x] Verification stats monstres tier 2-3 (level >= 3, difficulty >= 2, life > 0) pour 10 slugs (troll, werewolf, wyvern, cursed_knight, naga, crystal_golem, salamander, undine, sylph, clay_golem)
- [x] Verification resistances elementaires non vides pour les monstres tier 2 (wyvern, cursed_knight, naga, crystal_golem)
- [x] Verification des 3 phases (100/50/25% HP) et transitions pour les boss alpha_wolf, will_o_wisp, creeping_shadow
- [x] Comptage SQL : au moins 10 monstres niveau >= 3 exposes par les fixtures
- [x] Flux combat : `FightHandler::startFight()` contre wyvern persiste le Fight et lie player/mob
- [x] Flux combat : demarrer un combat contre alpha_wolf initialise `boss_phase_<mobId>` dans les metadata
- [x] Flux combat : `MobActionHandler::doAction()` execute le tour d'un will_o_wisp sans exception

### 146 — PNJ & dialogues par zone (complet) ✅
> 5 PNJ par zone d'aventure (20 total) avec dialogues ramifies, marchands et indications pour les joueurs.
- [x] Foret des murmures : 5 PNJ (Sylvain garde forestier, Elara herboriste/marchande, Thadeus ermite, Martin bucheron, Diane chasseuse)
- [x] Mines profondes : 5 PNJ (precedemment implementes)
- [x] Marais Brumeux : 5 PNJ (Morwen voyante, Fergus herboriste/marchand, Bran chasseur, Oswald pecheur, Isadora erudite)
- [x] Crete de Ventombre : 5 PNJ (precedemment implementes)
- [x] Dialogues ramifies avec conditions (domain_xp_min pour Ysolde et Agna)
- [x] Indicateurs visuels ! (quete dispo) et ? (quete en cours) deja implementes

---

## Sprint 4 — Progression & Narration

### 147 — Arbres de talent combat (complet) ✅
> 27 skills ajoutes pour aligner 6 branches combat sur la reference Mineur (18 skills chacune), avec materia unlock tier 2-3 et bonus passifs.
- [x] Soldat (metal) : +2 skills (16→18) — Barrage d'acier (steel-barrage), Ecrasement de titane (titanium-crush)
- [x] Defenseur (terre) : +5 skills (13→18) — Resistance naturelle (passif), Rempart de pierre (stonewall), Fissure (fissure), Seisme cristallin (crystal-quake), Lance d'obsidienne (obsidian-lance)
- [x] Guerisseur (eau) : +5 skills (13→18) — Empathie (passif), Source purificatrice (purifying-spring), Devotion totale (passif), Prison glaciale (glacial-prison), Maree abyssale (abyssal-tide)
- [x] Druide (bete) : +5 skills (13→18) — Instinct animal (passif), Regeneration sauvage (wild-regeneration), Griffes sauvages (claw-swipe), Ruee sauvage (stampede), Predateur supreme (apex-predator)
- [x] Necromancien (ombre) : +5 skills (13→18) — Volonte sombre (passif), Griffe d'ombre (shadow-claw), Pulse cauchemardesque (nightmare-pulse), Eruption du vide (void-eruption), Effondrement du vide (void-collapse)
- [x] Pretre (lumiere) : +5 skills (13→18) — Benediction passive (passif), Nova sacree (holy-nova), Purge (purge), Grace divine (divine-grace), Jugement celeste (celestial-judgment)

### 145 — Recettes craft manquantes (partiel) — Alchimiste : 9 recettes ajoutees ✅
> 9 recettes Alchimiste ajoutees (11 total), avec 6 sorts et 5 items consommables. Progression de niveau 1 a 5.
- [x] Base de potion (L1) : intermediaire de craft a partir de menthe et camomille
- [x] Onguent de guerison (L2) : soin + regeneration (aloe vera, camomille)
- [x] Potion de soin (L2) : recette pour la potion de soin existante (base + thym + sauge)
- [x] Potion d'energie (L2) : recette pour la potion d'energie existante (base + ginseng)
- [x] Fiole de poison (L3) : degats + poison (belladone, champignon venimeux)
- [x] Elixir de force (L3) : buff berserk (base + ginseng + mandragore)
- [x] Elixir de defense (L3) : buff bouclier (base + thym + valeriane)
- [x] Potion de soin majeure (L4) : recette pour la potion majeure existante (2x base + aloe + mandragore)
- [x] Elixir de vitalite (L5) : soin puissant (2x base + mandragore + ginseng)
- [x] Correction healing_potion_medium : ajout du spell pour compatibilite combat

### 145 — Recettes craft manquantes (complet) — Progression de recettes par niveau de domaine ✅
> Affichage des recettes verrouilees dans l'UI de craft avec indicateur de progression par niveau de domaine.
- [x] `CraftingManager::getLockedRecipes()` : retourne les recettes verrouilees triees par niveau requis
- [x] `CraftingManager::getNextUnlockInfo()` : retourne le prochain palier de deblocage (niveau, nombre de recettes)
- [x] `CraftingController` : transmet recettes verrouilees et infos de progression au template
- [x] Template `_recipe_card_locked.html.twig` : carte grisee avec cadenas et niveau requis
- [x] Template `index.html.twig` : bandeau de progression (prochain deblocage) + section recettes verrouilees
- [x] 4 tests unitaires : getLockedRecipes (filtre, vide), getNextUnlockInfo (donnees, null)

### 148 — Quetes secondaires & contenu narratif (partiel) — Quetes de zone liees aux PNJ ✅
> 6 quetes secondaires de zone liees a 6 PNJ existants sans quete, avec dialogues conditionnels (offre/progression/completion).
- [x] Foret : Diane la Chasseuse — "La meute affamee" (kill 3 loups + 1 alpha wolf, recompense: arc)
- [x] Foret : Sylvain le Garde forestier — "Sentinelle contre le venin" (kill 3 serpents + 2 scorpions, recompense: 2x antidote)
- [x] Mines : Durgan le Vieux Prospecteur — "Automates deregles" (kill 3 automates + 2 golems, recompense: 2x minerai argent)
- [x] Marais : Bran le Chasseur — "Prime sur les morts-vivants" (kill 4 zombies + 2 golems champignon, recompense: 3x antidote)
- [x] Marais : Oswald le Pecheur — "Appat empoisonne" (collect 5 champignons venimeux, recompense: 2x potion de soin)
- [x] Montagne : Kaelen l'Eclaireur — "Menace aerienne" (kill 3 griffons + 2 gargouilles, recompense: amulette d'argent)
- [x] Fixture ZoneQuestDialogFixtures : dialogues PNJ avec conditional_next (quete completee/active/disponible)

### 148 — Quetes secondaires & contenu narratif (partiel) — Quetes de faction (reputation) ✅
> 4 quetes introductives de faction (1 par faction) avec recompenses de reputation, attachees a 4 PNJ thematiquement lies. Leviers la pile ReputationListener existante via le champ `rewards['reputation']`.
- [x] Cercle des Mages : Antoine le Mage (pnj_18) — "Echos arcaniques" (kill 2 elementaires feu + 2 feux follets, +300 rep mages, potions)
- [x] Ordre des Chevaliers : Sebastien le Chevalier (pnj_24) — "Serment du Chevalier" (kill 3 squelettes + 2 zombies, +300 rep chevaliers, bouclier en bois)
- [x] Confrerie des Ombres : Aurelie l'Archere (pnj_17) — "Dans l'ombre des gobelins" (kill 4 gobelins, +300 rep ombres, potions)
- [x] Guilde des Marchands : Chloe l'Exploratrice (pnj_26) — "Routes sures pour la Guilde" (kill 3 araignees + 3 rats geants, +300 rep marchands, parchemins de teleport)
- [x] Fixture FactionQuestDialogFixtures : dialogues PNJ a 6 noeuds reutilisant le format ZoneQuestDialogFixtures (conditional_next par etat de quete)
- [x] Les quetes exposent `rewards['reputation']` recupere automatiquement par ReputationListener::onQuestCompleted (infra deja en place)

### 148 — Quetes secondaires & contenu narratif (partiel) — Quetes a choix moral + cap 80+ ✅
> 7 quetes ajoutees (5 choix moral + 2 chasse) portant le total a 80 quetes (cap atteint). Le systeme de reputation faction est etendu pour appliquer les consequences du choix selectionne.
- [x] `QuestCompletedEvent` enrichi d'un parametre optionnel `choiceMade` (compatibilite ascendante preservee)
- [x] `QuestController::complete` transmet le choix effectue a l'evenement
- [x] `ReputationListener::onQuestCompleted` lit desormais les gains/pertes de reputation depuis `choiceOutcome[choiceMade].bonusRewards.reputation` (base + choix cumules)
- [x] Support des variations negatives (`amount < 0`) : un choix peut faire perdre de la reputation
- [x] 5 quetes a choix moral avec factions opposees : `quest_moral_contrebandier` (Marchands/Ombres), `quest_moral_prisonnier` (Chevaliers/Ombres), `quest_moral_grimoire` (Mages/Marchands/Ombres), `quest_moral_ferme_brulee` (Marchands/Chevaliers), `quest_moral_relique` (3 choix : Mages/Chevaliers/Marchands)
- [x] 2 quetes de chasse simples : `quest_hunt_scorpions`, `quest_hunt_gargoyles`
- [x] Test unitaire `ReputationListenerTest` (4 cas : base seule, choix ignore sans choiceMade, choix avec positif+negatif, combinaison base+choix)

### 148 — Quetes secondaires & contenu narratif (partiel) — Quetes de decouverte ✅
> 10 quetes de decouverte ajoutees (8 cachees + 2 standard multi-points) couvrant les 5 zones d'aventure. Total : 69 quetes.
- [x] Plaine de l'Eveil : 2 quetes cachees (Stele oubliee, Puits des Anciens) — trigger sur exploration, recompense XP/gold/potions
- [x] Foret des Murmures : 2 quetes cachees (Cercle feerique, Chene millenaire) — auto-declenchees en explorant la foret
- [x] Marais Brumeux : 2 quetes cachees (Autel englouti, Grotte phosphorescente) — decouverte de lieux mystiques
- [x] Collines Venteuses : 1 quete cachee (Sanctuaire eolien) — exploration des hauteurs
- [x] Lande d'Ombre : 1 quete cachee (Obelisque d'ombre) — decouverte de ruines sombres
- [x] Quete standard "Cartographe des terres oubliees" : 5 points de repere a decouvrir (1 par zone)
- [x] Quete standard "Pelerinage des sites sacres" : 3 sites sacres entre Collines et Lande

### 148 — Quetes secondaires & contenu narratif (final) — Types escorte, defense, enigme ✅
> 3 nouveaux types de quetes (defend, escort, puzzle) avec tracking, listeners, UI et 6 quetes fixtures. Cloture Sprint 4.
- [x] Type `defend` : tuer X monstres dans une zone specifique (QuestDefendTrackingListener, MobDeadEvent + filtre map_id)
- [x] Type `escort` : atteindre une destination sur une carte (QuestEscortTrackingListener, PlayerMovedEvent + filtre destination)
- [x] Type `puzzle` : resoudre une enigme via reponse textuelle (endpoint POST puzzle-answer, validation answer_key)
- [x] QuestTrackingFormater : 3 nouveaux formatters (formatDefend, formatEscort, formatPuzzle)
- [x] PlayerQuestUpdater : 3 nouvelles methodes (updateDefend, updateEscort, updatePuzzle)
- [x] PlayerQuestHelper : types defend/escort/puzzle integres au calcul de progression
- [x] QuestGiverResolver : detection des 3 nouveaux types
- [x] Template quest/index.html.twig : affichage tracking, badges colores, filtres, formulaire enigme
- [x] 6 quetes fixtures : 2 defense (Foret/Mines), 2 escorte (Village/Montagne), 2 enigme (Sphinx/Runes)

---

## Sprint 3 — Arsenal & Magie

### 143 — Armures & accessoires par tier (partiel) — Accessoires (anneaux & amulettes) ✅
> 12 accessoires crees (6 anneaux + 6 amulettes) repartis sur 3 tiers avec effets elementaires et loot tables.
- [x] 6 anneaux : bone_ring (T1), silver_ring (T1), ruby_ring (T2 feu), sapphire_ring (T2 eau), emerald_ring (T3 terre), amethyst_ring (T3 ombre)
- [x] 6 amulettes : wooden_pendant (T1), bone_amulet (T1), fire_pendant (T2 feu), wind_pendant (T2 air), crystal_pendant (T3 lumiere), obsidian_pendant (T3 ombre)
- [x] Progression par rarete (common → uncommon/rare → epic) avec materia slots et effets elemental_damage_boost
- [x] Loot tables : 28 entrees reparties sur monstres T1 (goblin, zombie, wolf...), T2 (fire_elemental, salamander, undine, sylph...) et T3/boss (crystal_golem, lesser_lich, dragon...)

### 143 — Armures & accessoires par tier (complet) — Armures fer/mithril + sets ✅
> 13 armures creees (6 fer T2 + 7 mithril T3) avec progression cuir → fer → mithril, protection, et 2 equipment sets.
- [x] 6 armures fer T2 (Uncommon, lvl 8) : iron_chestplate, iron_greaves, iron_boots, iron_gauntlets, iron_pauldrons, iron_belt_reinforced (protection 3-8)
- [x] 7 armures mithril T3 (Rare, lvl 15) : mithril_helm, mithril_cuirass, mithril_greaves, mithril_sabatons, mithril_gauntlets, mithril_pauldrons, mithril_girdle (protection 6-14)
- [x] MAJ iron_helmet existant : ajout protection 6, rarity Uncommon, level 8
- [x] Set du Forgefer (Iron T2) : 7 pieces, bonus 2/4/6 (protection +4, vie +20, precision +8)
- [x] Set de Mithril (T3) : 7 pieces, bonus 2/4/6 (protection +8, vie +30, degats +10)
- [x] Loot tables : 25 entrees — fer sur monstres T2 (wolf, troll, werewolf...), mithril sur T3/boss (crystal_golem, lesser_lich, dragon...)

### 144 — Sorts & materia tier 2-3 (complet) — Sorts supplementaires + equilibrage synergies ✅
> 24 sorts supplementaires (3 par element, niveaux 2-4) couvrant les 8 elements, conçus pour les combos synergies. Test d'equilibrage validant les bornes des multiplicateurs.
- [x] 24 sorts : 3 par element (Feu, Eau, Air, Terre, Metal, Bete, Lumiere, Ombre) aux niveaux 2, 3 et 4
- [x] Sorts single-target (L2), AOE/controle (L3), puissants (L4) avec effets de statut varies (burn, freeze, poison, paralysis, silence, berserk, poison-strong)
- [x] Design oriente synergies : chaque sort est equilibre pour les combos elementaires existants (Steam, Sandstorm, Eclipse, Floral Explosion, Forge, Holy Blade, Primal Fury, Venomous Shadow)
- [x] Test SynergyBalanceTest : validation multiplicateurs dans [1.0-1.5], variance < 30%, couverture elements, output damage tiers 2-4, self-damage ≤ 10%

---

## Vague 7 — Qualite, stabilisation & fondations UX

### 110 — Correction bugs connus & dette technique (partiel) ✅
> Nettoyage de code mort et ajout de la validation de coherence DB en CI.
- [x] Nettoyage code mort detecte par PHPStan (LootGenerator variable inutilisee, MapModelTransformer code commente)
- [x] Verification coherence DB via `app:game:validate` en CI (step apres fixtures dans le job tests)

### TST-11 — Retirer continue-on-error E2E ✅
> Retrait de `continue-on-error: true` du job E2E dans la CI, les tests sont desormais stables.
- [x] Retirer `continue-on-error: true` du job E2E dans `.github/workflows/ci.yml`

### 111 — Equilibrage combat avance (partiel) — Rapport de combat ✅
> Ajout de la section « combat » au rapport d'equilibrage (`app:balance:report --section combat`).
- [x] Rapport d'equilibrage via commande admin : DPS moyen par tier, temps de combat, taux de mort
- [x] Taux de victoire/defaite/fuite par monstre (avec alertes si < 30% ou > 95%)
- [x] Duree moyenne des combats (en tours)
- [x] DPS des monstres par niveau (degats/combat et degats/tour)
- [x] DPS des joueurs par monstre (degats infliges aux mobs, par tour)
- [x] Alertes automatiques : ecart DPS joueur > 30% entre niveaux adjacents
- [x] Alertes automatiques : combats trop longs (> 20 tours en moyenne)
- [x] Top 10 morts joueurs les plus frequentes
- [x] Option `--days` pour filtrer la periode d'analyse (defaut: 30 jours)

### 111 — Equilibrage combat avance (partiel) — Equilibrage donjons ✅
> Reequilibrage difficulte vs recompenses des donjons (Normal/Heroique/Mythique).
- [x] Ajout `xpMultiplier()` dans `DungeonDifficulty` : Normal 1.0x, Heroique 1.5x, Mythique 2.5x
- [x] Ajustement `dropMultiplier()` : Heroique 1.25x → 1.5x, Mythique 1.5x → 2.0x
- [x] Stockage `difficulty_xp_multiplier` dans les metadata du combat (`FightHandler`)
- [x] Application du multiplicateur XP donjon dans `MateriaXpGranter` (stack avec boss x5)
- [x] Tests unitaires : `DungeonDifficultyTest`, `DungeonDifficultyScalingTest`, `MateriaXpGranterTest`

### 111 — Equilibrage combat avance (partiel) — Equilibrage world boss ✅
> Scaling dynamique des HP et du loot du world boss en fonction du nombre de joueurs actifs.
- [x] Scaling HP world boss : +35% par joueur additionnel (`world_boss_player_multiplier` en metadata)
- [x] Scaling HP proportionnel au join : maintien du ratio HP courant lors de l'arrivee d'un nouveau joueur
- [x] Scaling initial pour les groupes : si un groupe de N joueurs engage le boss, HP scalees des le depart
- [x] `Mob::getMaxLife()` integre le multiplicateur joueur (`world_boss_player_multiplier`)
- [x] Bonus loot par participant : +10% par joueur additionnel (cap 2.0x) dans `WorldBossLootDistributor`
- [x] Tests unitaires : scaling HP (join simple, ratio preserve, 3 joueurs progressifs), loot participant bonus

### 111 — Equilibrage combat avance (partiel) — Ajustement formules de degats ✅
> Normalisation des multiplicateurs de degats pour reduire les ecarts entre builds a < 30%.
- [x] Creation de `DamageMultiplierNormalizer` : soft cap avec rendements decroissants sur le stacking de bonus equipement et les synergies elementaires
- [x] Stacking additif des bonus d'equipement (element match + linked materia + gear) au lieu du stacking multiplicatif
- [x] Reduction des multiplicateurs de synergie pour ramener la plage de 1.2-2.5 a 1.2-1.5 (ecart 25% < 30%)
- [x] Eclipse : multiplicateur 2.5 → 1.5, self-damage 10% → 5% (equilibre risque/recompense)
- [x] Sandstorm 1.5 → 1.4, Floral Explosion 1.3 → 1.25, Forge 1.4 → 1.3, Holy Blade 1.6 → 1.5, Primal Fury 1.4 → 1.3, Venomous Shadow 1.5 → 1.35
- [x] Tests unitaires : `DamageMultiplierNormalizerTest` (soft cap, diminishing returns, variance < 30%)
- [x] Mise a jour des tests `ElementalSynergyCalculatorTest` pour les nouvelles valeurs

### 106 — Nouveaux tests E2E critiques (TST-10) ✅
> 3 nouvelles classes de tests E2E couvrant les parcours critiques : inventaire, carte et boutique.
- [x] `InventoryFlowTest` : equiper item → stats changent → desequiper → stats reviennent (4 tests)
- [x] `MapNavigationTest` : deplacement API → joueur bouge → changement de carte via portail (5 tests)
- [x] `ShopFlowTest` : acheter item → or diminue → item dans inventaire (4 tests)

### 107 — Reactiver E2E dans la CI (TST-11) ✅
> Reactivation du job E2E (Panther + Chrome headless) dans la CI GitHub Actions.
- [x] Decomenter le job `e2e` dans `.github/workflows/ci.yml`
- [x] Ajouter `continue-on-error: true` pour ne pas bloquer la CI pendant la stabilisation
- [x] Screenshots uploadees en artifact en cas d'echec

---

## Vague 7 — Qualite, stabilisation & fondations UX

### 105 — Stabiliser tests E2E existants (TST-09) ✅
> Stabilisation des 5 classes de tests E2E (16 methodes) pour les rendre fiables et deterministes.
- [x] Helpers `waitForPixi()` et `waitForTurbo()` dans `AbstractE2ETestCase` pour attentes asynchrones
- [x] Helpers `waitForSelector()`, `waitForUrlContaining()`, `waitForUrlNotContaining()` pour navigation robuste
- [x] Helper `apiFetch()` pour simplifier les appels API JS (fetch + JSON parse)
- [x] Helper `selectorExists()` pour verifications conditionnelles sans exception
- [x] Correction du `login()` avec attente de redirection post-authentification
- [x] Remplacement de tous les `waitFor('body')` generiques par des attentes specifiques
- [x] Verification des fixtures (positions mobs, quetes) et selecteurs CSS (tous valides)

### 108 — PHPStan niveau 6 + reduction baseline ✅ (TST-12)
> Passage de PHPStan niveau 5 a 6 avec correction des erreurs reelles du baseline.
- [x] Suppression de 10 services injectes mais jamais utilises (`property.onlyWritten`)
- [x] Correction de 17 null coalescing inutiles (`nullCoalesce.offset` / `nullCoalesce.expr`) dans MapApiController et autres
- [x] Correction des erreurs de type (`argument.type`) dans ItemHelper et ItemHitResolver
- [x] Suppression de code mort (PriorityQueue::getPosition, PlayerSpellHandler $gearHelper)
- [x] Fix FightCleaner @var tag, MateriaFusionManager comparaison impossible
- [x] Passage du niveau PHPStan de 5 a 6
- [x] Baseline regenere — erreurs restantes = annotations types generiques (niveau 6)

### 138 — Feedback progression & celebrations ✅
> Notifications et toasts celebratoires pour les evenements de progression du joueur.
- [x] Popup "Competence debloquee !" quand un palier de domaine est atteint (DomainLevelUpEvent + notification Mercure)
- [x] Banniere "Succes debloque" avec animation celebration (toast type `celebration` avec style violet/dore)
- [x] Toast craft reussi avec XP gagnee (notification `craft_success` via CraftEvent)
- [x] Notification visuelle Mercure pour les achievements et level-ups (mapping type-aware dans notification_center_controller)

### 139 — Comparaison d'equipement & QoL inventaire ✅
> Tooltip de comparaison avec delta stats + file de craft + timer reset quotidiennes.
- [x] Tooltip/modal de comparaison avant equipement (delta stats : DEF, materia slots, element)
- [x] Apercu de l'objet au hover (stats, rarete, description) — deja fonctionnel, enrichi avec comparaison
- [x] File d'attente de craft : input quantite (1-99) avec boutons +/-/Max, craft en lot via API JSON, feedback toast
- [x] Timer reset quetes quotidiennes : compte a rebours visible dans l'onglet Quotidiennes ("Prochain reset : HHhMMmSSs")

### 137 — Feedback visuels combat ✅
> Feedback visuels riches pour le combat : nombres flottants, barre de boss, notification synergie, auras status.
- [x] Nombres de degats flottants au-dessus des cibles (jaune degats, rouge critique, vert soin, gris miss)
- [x] Barre de vie boss prominente en haut de l'ecran (nom, phase, animation pulsante < 25% HP)
- [x] Notification synergie elementaire animee (banniere "SYNERGIE" avec label cyan/violet)
- [x] Auras d'effets de statut sur les cartes combattants (lueur coloree par type d'effet)
- [x] Champs `damage`/`heal`/`critical` ajoutes aux reponses JSON des controllers combat

### 108 — PHPStan niveau 6 + reduction baseline (TST-12) ✅
> Passage de PHPStan level 5 → 6. Correction de toutes les erreurs reelles du baseline.
- [x] Suppression de 9 services injectes non utilises (`property.onlyWritten`)
- [x] Correction de 17+ verifications `nullCoalesce.offset/expr` inutiles
- [x] Correction erreurs `alwaysTrue`, `alwaysFalse`, `property.notFound`, `argument.type`, `method.unused`, `varTag`
- [x] Passage du niveau PHPStan de 5 a 6
- [x] Baseline regenere : 0 erreurs reelles, 499 erreurs `missingType.*` (typage iterables/generics a corriger progressivement)

### 109 — Mutation testing avec Infection PHP (TST-13) ✅
> Mise en place du mutation testing sur les calculateurs de combat pour mesurer l'efficacite des tests.
- [x] Installation d'Infection PHP (`infection/infection`)
- [x] Configuration `infection.json5` sur `src/GameEngine/Fight/Calculator/` (zone critique)
- [x] Amelioration des tests existants pour tuer les mutants (107 tests, 432 assertions)
- [x] Resultats : MSI 79%, Covered MSI 80%, 108 mutants tues sur 141
- [x] Exclusion documentee des mutants equivalents (CastInt apres round())

### 112 — Optimisation requetes N+1 & performance DB ✅
> Profiling et correction des requetes N+1 critiques, ajout d'index manquants, cache memoire par requete.
- [x] Audit complet des N+1 : inventaire, combat, skills, synergies (30-100+ requetes eliminées par page)
- [x] InventoryRepository : JOIN FETCH PlayerItem + Item pour les pages inventaire/materia/equipement
- [x] DomainExperienceRepository : JOIN FETCH Domain + Skills pour la page talents
- [x] FightIndexController : chargement du combat via FightRepository::findWithRelations() au lieu de lazy-load
- [x] SynergyCalculator : cache memoire par requete (elimine findAll() duplique)
- [x] 11 index DB manquants : player, inventory, domain_experience, mob, pnj, object_layer, game_event
- [x] Suppression requete findAll() inutile dans Skill/IndexController
- [x] Benchmark performance : test PHPUnit verifiant que les routes critiques (game + API) repondent en < 200ms

### 104 — Tests integration quetes & progression (TST-07) ✅
> Tests d'integration avec vraie DB (pas de mocks) pour les flux critiques quetes et progression.
- [x] `QuestProgressionIntegrationTest` : accepter quete → tuer mob → objectif mis a jour → completion → recompense
- [x] `SkillProgressionIntegrationTest` : gagner XP domaine → competence deblocable → acquisition → stats mises a jour
- [x] Helper `setCurrentPlayer()` dans `AbstractIntegrationTestCase` pour injecter le joueur sans contexte HTTP

### 136 — Creation de personnage ✅
> Systeme complet de creation et selection de personnage, distinct de l'inscription du compte.
- [x] Ecran de creation post-inscription (`CharacterController::create()`) avec redirection auto si aucun Player
- [x] Choix du nom de personnage (validation unicite + filtrage mots interdits via `ForbiddenNameChecker`)
- [x] Choix de la race (parmi `Race.availableAtCreation = true`) avec apercu sprite
- [x] Affichage des bonus de stats par race (`statModifiers`) dans le formulaire
- [x] Limite configurable du nombre de personnages par compte (`app.max_players_per_user`, defaut : 1)
- [x] Selecteur de personnage au login si le joueur en possede plusieurs (`CharacterController::select()`)
- [x] Refactoring du `RegistrationController` : auto-login + redirection vers creation personnage
- [x] `LoginFormAuthenticator` : redirection intelligente (0 players → create, 1 → game, 2+ → select)

### 110 — Correction bugs connus & dette technique (partiel) ✅
> Sous-tache : verification coherence DB via `app:game:validate` en CI.
- [x] Ajout de l'etape `app:game:validate --env=test` dans le job `tests` de la CI (apres le chargement des fixtures)
- [x] 2 nouveaux checks dans `GameStateValidator` : `negative_domain_experience` (XP used > total ou valeurs negatives) et `equipped_items_wrong_location` (items equipes hors inventaire joueur)
- [x] Tests unitaires mis a jour pour couvrir les 7 checks

---

## Modernisation de la stack (2026-03-09) ✅

> Refonte complete de l'infrastructure technique.

| Tache | Detail |
|-------|--------|
| Migration Doctrine ORM 3.6 / DBAL 4.4 | 22 entites migrees, config nettoyee |
| Migration Tailwind CSS v3 → v4.1 | Config CSS-native, suppression tailwind.config.js |
| Suppression Node.js | Retrait complet de l'image Docker |
| Correction Mercure | URL dynamique, Turbo Streams active |
| Controller Stimulus Mercure | Remplacement du script brut move-listener.js |
| Refactoring deplacement | Suppression usleep(250ms), chemin complet en 1 event |
| Remplacement Typesense → PostgreSQL | Cache Symfony, suppression service Docker |
| Remplacement cron-bundle → Symfony Scheduler | Composant natif Symfony |
| Docker : 4 services → 2 services | Suppression typesense + worker async |

**Stack finale** : PHP 8.3 + Symfony 7.2.9 + FrankenPHP + PostgreSQL 16 + Doctrine ORM 3.6.2 + Tailwind v4.1 + Mercure SSE

---

## Phase 1 — Fondations techniques (2026-03-13) ✅

### 1.1 Pipeline Tiled ameliore ✅
- Import des Object Layers depuis TMX (mob_spawn, npc_spawn, portal, chest, harvest_spot)
- Validation automatique des maps (--validate)
- Auto-detection des tilesets et dimensions
- Support des proprietes personnalisees Tiled
- Mode dry-run (--dry-run)
- Statistiques detaillees (--stats)

### 1.2 Workflow de creation de cartes (partiel) ✅
- Conventions de layers documentees
- Commande d'import enrichie avec --sync-entities
- Commande de validation
- Systeme de portails (teleportation entre zones avec fade, particules, camera shake)

### 1.3 Systeme de sprites complet ✅
- SpriteAnimator format RPG Maker VX (3x4 single, 12x8 multi)
- Animation idle breathing (oscillation Y desynchronisee)
- Systeme d'emotes (!, ?, coeur, etoile, etc.)
- Etats d'animation (idle, walk, interact)

### 1.4 Boucle de jeu PixiJS ✅
- Ticker 60fps avec delta time, camera lerp
- Camera shake parametrable
- Cycle jour/nuit (overlay ambiant)
- Systeme de particules
- Fade transition pour changements de carte

### 1.5 Support mobile ✅
- Controles WASD/ZQSD + fleches
- Joystick virtuel 4 directions
- Retour haptique (vibration)
- Mode paysage CSS adaptatif
- Touch events unifies
- Responsive canvas (ResizeObserver)

### 1.6 Dialogues PNJ ✅
- Typewriter intelligent (pauses ponctuation)
- Navigation clavier (Espace/Entree/Echap)
- Animations slide-up/down
- Parser conditionnel (quest, has_item, domain_xp_min)
- Variables {{player_name}}, {{pnj_name}}
- Actions de choix (close, quest_offer, open_shop, next)
- Accessibilite ARIA

### 1.7 Performance ✅
- Tile sprite pool
- Entity container pool
- Spatial hash O(1)
- Texture cache (GID, couleur, sheet)
- Lazy loading + preload cells
- Pruning des cellules distantes
- Frame budget monitoring

### 1.8 Registre d'assets centralise ✅
- SpriteConfigProvider avec metadonnees
- Filtrage par categorie
- 30+ sprite sheets (7 joueurs, 12 monstres, 10 PNJ)

### 1.9 Accessibilite web ✅
- ARIA attributes (role, aria-label, aria-live)
- Hints clavier
- Backdrop blur pour lisibilite

### 1.10 Preview terrain et templates Tiled ✅
- Commande `app:terrain:preview --map=X` : genere un PNG a partir d'un fichier TMX
  - Support scale (0.25, 0.5, 1, 2), overlay collisions, overlay objets
  - Rendu complet multi-layers avec tous les tilesets
  - Mode `all` pour generer toutes les cartes d'un coup
- Templates de cartes Tiled pre-configures dans `terrain/templates/` :
  - `template_outdoor.tmx` — Zone exterieure 60x60 (4 tilesets, 5 layers, object group)
  - `template_indoor.tmx` — Interieur 20x20 (4 tilesets, 5 layers, object group)
  - `template_dungeon.tmx` — Grotte/donjon 60x30 (3 tilesets, 5 layers, object group)
  - Chaque template inclut les conventions de layers, les GID de reference, et des exemples d'objets commentes

---

## Phase 2 — Panel d'administration ✅

### 2.1 Infrastructure admin ✅
- Firewall admin (pattern /admin/*, role ROLE_ADMIN)
- Layout admin dedie avec sidebar
- Dashboard avec metriques cles
- Recherche et filtrage avec pagination

### 2.2 Gestion du contenu de jeu (CRUD complet) ✅
- Items, Monstres, Sorts, Competences, Domaines
- Quetes, PNJ, Recettes de craft, Tables de loot

### 2.3 Gestion des cartes ✅
- Visualisation des maps avec statistiques par zone
- Monitoring par zone (joueurs, mobs, PNJ)
- Gestion des spawns : placer/deplacer mobs et PNJ sur la carte via interface admin
- Gestion des portails : configurer les liens entre zones depuis l'admin (CRUD complet)
- Import de map : upload d'un fichier TMX depuis l'admin

### 2.4 Gestion des joueurs ✅
- Liste joueurs avec recherche et pagination
- Fiche joueur detaillee (stats, inventaire, quetes, progression)
- Actions admin (ban/unban, reset position, donner items/gils)
- Logs d'actions admin

### 2.5 Outils de maintenance ✅
- Mode maintenance activable depuis l'admin
- Logs d'administration
- Reload des fixtures selectif : commande CLI `app:fixtures:load-selective` (12 groupes : items, monsters, spells, skills, domains, mobs, pnjs, quests, maps, players, achievements, slots)
- Console Mercure : voir les topics connus, publier des messages de test depuis l'admin
- Planificateur d'evenements : entite GameEvent (boss_spawn, xp_bonus, drop_bonus, invasion, custom), CRUD admin, recurrence, filtrage par statut

---

## Game Design — Phases 1 a 13 ✅

### Phase GD-1 : Enum Element centralise ✅
- PHP 8.4 backed enum (none, fire, water, earth, air, light, dark, metal, beast)
- Migration des constantes ELEMENT_* vers l'enum

### Phase GD-2 : Systeme de race ✅
- Entite Race (slug, name, description, statModifiers)
- Race Humain (stats neutres)
- Assignation automatique a la creation

### Phase GD-3 : Spell niveau + valueType + calculators ✅
- Champs level et valueType (fixed/percent) sur Spell
- DamageCalculator, HitChanceCalculator, CriticalCalculator extraits

### Phase GD-4 : Side effects enrichis ✅
- StatusEffect avec category (buff/debuff/hot/dot) et frequency
- PlayerStatusEffect pour effets persistants hors combat
- StatusEffectManager avec tick selon frequence

### Phase GD-5 : Competences multi-domaines ✅ (BREAKING)
- Skill.domain ManyToOne → ManyToMany
- CrossDomainSkillResolver (auto-unlock, XP 100% par domaine)

### Phase GD-6 : Infrastructure 32 domaines + tous les arbres de talent ✅
- 32 domaines (24 combat + 4 recolte + 4 craft)
- 400+ competences avec 13-24 skills par domaine
- Sous-phases 6.A a 6.I toutes completees

### Phase GD-7 : Tout est un sort + Soulbound ✅
- boundToPlayer sur items
- use_spell comme norme d'action pour consommables
- Icone "lie" sur items bound en inventaire

### Phase GD-8 : Materia = Capacites de combat (partiel) ✅
- CombatCapacityResolver cree (sorts = materia equipees)
- Attaque arme TOUJOURS disponible gratuitement
- Bonus matching element slot/materia (+25% degats, +25% XP)

### Phase GD-9 : Inventaire groupement visuel ✅
### Phase GD-10 : Dashboard enrichi ✅
### Phase GD-11 : Bestiaire joueur ✅
### Phase GD-12 : Systeme de succes ✅
### Phase GD-13 : Mise a jour documentation ✅

---

## Combat enrichi — Elements deja implementes ✅
### Synergies elementaires ✅
### Materia Fusion ✅
### Materia XP ✅
### Statuts alteres (8/8) ✅
### Resistances elementaires par monstre ✅
### IA monstres — patterns et alertes ✅
### Boss — phases et cooldown ✅

---

## Vague 1 — Fondations & Quick Wins (2026-03-20)

### 01 — De-hardcoder les map IDs ✅
### 02 — Supprimer la commande CSS morte ✅
### 04 — Rate limiting API ✅
### 07 — Raretes d'equipement ✅
### 08 — Combat log frontend ✅
### 09 — Icones statuts timeline combat ✅
### 10 — Indicateur difficulte monstres ✅
### 12 — Recompenses de quetes completes ✅
### 14 — Respec basique ✅
### 24 — Notifications toast in-game ✅
### 25 — Boutiques PNJ fixtures ✅

---

## Tache 06 — Materia unlock verification (2026-03-21) ✅

> Verification `actions.materia.unlock` avant d'autoriser un sort en combat. Gain gameplay : ★★★

- [x] Methode `getUnlockedMateriaSpellSlugs(Player)` dans CombatSkillResolver (scan skills pour `materia.unlock`)
- [x] Methode `hasUnlockedMateriaSpell(Player, spellSlug)` dans CombatSkillResolver
- [x] Flag `locked` dans `CombatCapacityResolver::getEquippedMateriaSpells()` pour chaque sort
- [x] Validation dans `FightSpellController` : rejet des sorts verrouilles (HTTP 403)
- [x] `PlayerItemHelper::canEquipMateria()` verifie le skill unlock avant d'autoriser l'equipement
- [x] Template combat : sorts verrouilles grises (opacity-50, texte "Competence requise")
- [x] Tests unitaires CombatCapacityResolverTest (flag locked) et CombatSkillResolverMateriaTest

---

## 13 — Prerequis de quetes et chaines (2026-03-21) ✅

> Permet de creer des chaines de quetes Q1→Q2→Q3. Gain gameplay : ★★★

- [x] Ajout du champ `prerequisiteQuests` (JSON, nullable) sur l'entite Quest + migration PostgreSQL
- [x] Verification des prerequis dans `QuestController::accept()` (refus si prerequis non remplis)
- [x] Nouvelle condition `quest_prerequisites_met` dans `PnjDialogParser` pour les dialogues PNJ
- [x] Methode `getAvailableQuests()` dans `PlayerQuestHelper` (filtre par prerequis satisfaits)
- [x] Onglet "Disponibles" dans le journal de quetes (affiche les quetes acceptables)
- [x] Chaine de 3 quetes dans les fixtures : "La Menace Rampante" (gobelins → squelettes → troll)
- [x] Support admin : champ prerequis dans le formulaire de creation/edition de quetes

---

## Tache 15 — Consommables de base (2026-03-21) ✅

> Ajout de consommables fonctionnels : potions, nourritures et parchemins. Tous utilisables en combat et hors combat via le systeme de sorts existant.

- [x] 6 nouveaux sorts de consommables dans SpellFixtures (potion-heal-major, antidote-heal, bread-heal, grilled-meat-heal, stew-heal)
- [x] 5 potions/remedes : potion de soin majeure (heal 15, 200G), antidote (heal 3, 75G) + existants (mineure, moyenne, energie)
- [x] 3 nourritures : pain (heal 4, 15G), viande grillee (heal 8, 40G), ragout (heal 12, 80G)
- [x] 3 parchemins : teleportation (150G), savoir/XP boost (300G), identification (100G)
- [x] Tous les consommables fonctionnels ajoutes aux loot tables des monstres (par tier de difficulte)
- [x] Boutiques PNJ enrichies : Elise vend potions + antidote, Pierre vend nourritures

---

## Tache 19 — Profil joueur public (2026-03-21) ✅

> Page de profil public pour consulter les infos d'un autre joueur.

- [x] Route `GET /game/player/{id}/profile` : nom, classe, race, stats, domaines, succes, bestiaire
- [x] Template profil public avec stats (vie, energie, vitesse, precision), domaines et bonus, succes obtenus, titres de chasseur
- [x] Lien cliquable sur les noms de joueurs dans le chat (global, carte, messages prives) — Twig et Stimulus.js

---

## Tache 09 — Icones statuts timeline combat (2026-03-21) ✅

> Badges statut actifs sous chaque avatar dans la timeline combat.

- [x] Badges statut color-codes sous chaque avatar dans `_timeline.html.twig`
- [x] Icone emoji + tours restants (tooltip au survol)
- [x] 8 types supportes : poison, burn, freeze, paralysis, silence, regeneration, shield, berserk

---

## Tache 10 — Indicateur difficulte monstres (2026-03-21) ✅

> Affichage de la difficulte des monstres en etoiles.

- [x] Champ `difficulty` (int 1-5) sur l'entite Monster
- [x] Affichage en etoiles dans le template combat et le bestiaire
- [x] Difficulte renseignee dans MonsterFixtures pour les 25 monstres

---

## Tache 14 — Respec basique (2026-03-21) ✅

> Redistribution de tous les points de talent avec cout croissant.

- [x] Service `SkillRespecManager` : retire tous les skills, rembourse l'XP usee
- [x] Cout en gils (50 * nb skills * 1.25^respecCount), prix croissant
- [x] Champ `respecCount` sur Player + migration
- [x] Route POST `/game/skills/respec` + RespecController avec CSRF
- [x] Modale de confirmation dans la page /game/skills
- [x] Tests unitaires SkillRespecManagerTest

---

## Tache 20 — Horloge in-game & API temps (2026-03-21) ✅

> Systeme de temps in-game avec ratio configurable (1h reelle = 1 jour in-game).

- [x] `GameTimeService` : conversion temps reel → in-game (ratio configurable via `game.time_ratio`)
- [x] Methodes `getHour()`, `getMinute()`, `getTimeOfDay()` (dawn/day/dusk/night), `getSeason()`, `getDay()`
- [x] Parametre Symfony `game.time_ratio` dans `services.yaml`
- [x] Route API `GET /api/game/time` (heure, minute, periode, saison, jour, ratio)
- [x] `map_pixi_controller.js` utilise l'API au lieu du temps reel local
- [x] HUD discret sur la carte : heure in-game + icone saison (PixiJS Text overlay)
- [x] Extrapolation client-side entre les fetches API (re-sync toutes les 5 min)
- [x] 12 tests unitaires GameTimeServiceTest

---

## Tache 24 — Notifications toast in-game (2026-03-21) ✅

> Systeme de notifications toast generaliste pour toutes les actions du joueur.

- [x] Stimulus controller `toast_controller.js` : toasts empiles en bas-droite, auto-dismiss 4s
- [x] 4 types visuels : success (vert), error (rouge), warning (orange), info (bleu)
- [x] API globale `window.Toast.show(type, message)` pour JS
- [x] Integration flash messages Symfony (`addFlash`) → toasts automatiques
- [x] Container dans `game.html.twig` avec support safe-area mobile

## Tache 11 — Recompenses uniques de boss (2026-03-21) ✅

> Items legendaires exclusifs au boss Dragon ancestral, avec drop garanti dans la loot table.

- [x] 2 items legendaires boss-only dans ItemFixtures : Lame de croc draconique (arme feu), Plastron en ecailles de dragon (armure feu)
- [x] Loot table du Dragon configuree : epee a 15%, plastron a 10% de drop
- [x] Badge rarity Legendary (jaune dore) automatique dans inventaire et ecran de loot

## Tache 32 — Journal de quetes enrichi (2026-03-22) ✅

> Journal de quetes ameliore avec filtrage par type, PNJ donneur, et indicateurs de chaines.

- [x] Onglet "Disponibles" avec bouton "Accepter" et filtrage par type (combat, recolte, livraison, exploration)
- [x] Affichage du PNJ donneur de quete (nom + lien carte) pour chaque quete active/disponible
- [x] Indicateur de chaine "Quete X/Y" pour les quetes faisant partie d'une serie
- [x] Service QuestGiverResolver : resolution PNJ donneur via scan dialog JSON, detection type de quete, calcul position dans chaine

---

## 21 — GameEvent executor (2026-03-22) ✅

> Service d'execution automatique des GameEvent planifies. Socle de tout le contenu evenementiel (bonus XP/drop, world boss, invasions).

- [x] `GameEventExecutor` : scanne les SCHEDULED dont startsAt <= now, les passe ACTIVE
- [x] `GameEventBonusProvider` : expose les multiplicateurs XP et drop actifs (global ou par map)
- [x] Integration `MateriaXpGranter` : applique le bonus XP des events actifs
- [x] Integration `LootGenerator` : applique le bonus drop des events actifs
- [x] Commande `app:game-event:execute` + tache Scheduler (toutes les 60s)
- [x] Passage ACTIVE → COMPLETED quand endsAt < now
- [x] Recurrence : creation automatique du prochain event a la completion
- [x] Events schedules deja expires → marques COMPLETED directement
- [x] Tests unitaires : GameEventExecutorTest (5 tests), GameEventBonusProviderTest (6 tests)

---

## 05 — Consolidation craft : supprimer le systeme duplique (2026-03-22) ✅

> Deux systemes concurrents (CraftManager/CraftController + CraftingManager/CraftingController). CraftingManager conserve (plus complet : experimentation avec hints, 5 niveaux de qualite, decouverte par joueur). CraftManager supprime.

- [x] Audit des 2 systemes : CraftingManager retenu (meilleure experimentation, qualite 5 tiers, decouverte par joueur)
- [x] Suppression systeme redondant : CraftController, CraftManager, CraftQuality, CraftResult, CraftRecipe, CraftEvent, CraftRecipeController, CraftRecipeType, templates game/craft/ et admin/craft_recipe/
- [x] Mise a jour references : DashboardController (Recipe au lieu de CraftRecipe), DomainExperienceEvolver (retrait CraftEvent), RateLimitingSubscriber (routes unifiees), templates nav
- [x] Renommage routes CraftingController : game_crafting → app_game_craft (convention unifiee)
- [x] Migration pour supprimer la table game_craft_recipes
- [x] PHPStan OK, PHP-CS-Fixer OK

---

## 03 — Optimisation queries N+1 (2026-03-22) ✅

> Eager loading des relations Doctrine et index composites pour reduire les requetes N+1 sur les pages critiques.

- [x] MobRepository : eager load Monster+Spells+Attack+MonsterItems pour /api/map/entities
- [x] FightRepository : eager load Mob→Monster→Spells+MonsterItems pour le combat
- [x] PlayerBestiaryRepository : eager load Monster+MonsterItems+Item pour /game/bestiary
- [x] MapApiController : utilise MobRepository au lieu de findBy generique
- [x] FightChecker : utilise FightRepository.findWithRelations au lieu de find()
- [x] Index composites : idx_mob_map (mob.map_id), idx_player_map (player.map_id)
- [x] PHPStan OK, PHP-CS-Fixer OK

## 17 — Equipement tier 1 Starter (2026-03-22) ✅

> Set complet 7 pieces d'equipement starter (element None, rarete Common, sans prerequis de skill).

- [x] 7 pieces d'equipement : epee en bois, casque rouille, tunique rembourrée, jambieres en tissu, sandales usees, gants de travail, bouclier en bois
- [x] Prix bas (8-20 or), duree de vie 60 utilisations, aucun prerequis de competence
- [x] Ajout aux loot tables des monstres lvl 1 (slime, goblin, bat, giant_rat, zombie) avec probabilites 2-6%

## 39 — Limite points multi-domaine (2026-03-22) ✅

> Empeche de tout maxer, force des choix strategiques de build.

- [x] Constante `MAX_TOTAL_SKILL_POINTS = 500` dans `PlayerSkillHelper`
- [x] Verification dans `canAcquireSkill()` : somme des `usedExperience` de tous les domaines + cout du skill <= max
- [x] Methode `getTotalUsedPoints()` pour calculer le total utilise cross-domaine
- [x] Affichage barre de progression globale dans `/game/skills` (couleur adaptative : violet/orange/rouge)
- [x] Messages contextuels (alerte quand >= 80%, erreur quand limite atteinte)
- [x] 7 tests unitaires (sous la limite, a la limite exacte, au-dessus, deja acquis, constante)
- [x] PHPStan OK, PHP-CS-Fixer OK, 323 tests OK

## 16 — Materia complement — 8 nouvelles (2026-03-22) ✅

> 8 nouvelles materias tier 2 (1 par element), enrichit le combat de 10 → 18 materias.

- [x] 7 nouveaux sorts dans SpellFixtures : Brume glaciale (eau), Eclair en chaine (air), Mur de pierre (terre), Riposte d'acier (metal), Morsure sauvage (bete), Benediction (lumiere), Drain vital (ombre) + Combustion (feu, existait deja)
- [x] 8 nouveaux items materia tier 2 dans ItemFixtures (rarete Rare, level 2, prix 150-180 or, 10-15 utilisations)
- [x] 7 nouveaux skill unlock dans SkillFixtures : hydromancer, stormcaller, geomancer, soldier, hunter, paladin, assassin (berserker existait deja pour Combustion)
- [x] YAML materia.yaml mis a jour pour coherence
- [x] PHPStan OK, PHP-CS-Fixer OK

---

## 35 — Annonces Mercure evenements (2026-03-22) ✅

> Notification temps reel quand un GameEvent passe ACTIVE via Mercure SSE + affichage HUD.

- [x] Domain event `GameEventActivatedEvent` dispatche quand un GameEvent passe ACTIVE
- [x] Publisher Mercure `GameEventAnnouncementHandler` publie sur topic `event/announce`
- [x] `GameEventExecutor` dispatche l'event apres flush (activation automatique)
- [x] Admin toggle dispatche aussi l'event lors d'activation manuelle
- [x] API `GET /api/game/events/active` : liste les events actuellement actifs
- [x] Stimulus controller `event-notification` : souscrit Mercure, affiche toast, HUD badge avec dropdown
- [x] HUD dans `game.html.twig` : badge "Events" avec compteur et liste hover des events actifs
- [x] Tests unitaires : GameEventAnnouncementHandler, GameEventExecutor (dispatch event)
- [x] PHPStan OK, PHP-CS-Fixer OK, PHPUnit 318 tests OK

## 37 — Loot exclusif et rarete etendue (2026-03-22) ✅

> Enrichissement du systeme de loot : drops garantis, filtrage par difficulte, items legendaires exclusifs.

- [x] Champ `guaranteed` (bool) sur MonsterItem : drop garanti (100%) independamment de la probabilite
- [x] Champ `minDifficulty` (nullable int) sur MonsterItem : drop uniquement si difficulte monstre >= seuil
- [x] Migration SQL (ALTER TABLE game_monster_items ADD COLUMN guaranteed, min_difficulty)
- [x] LootGenerator mis a jour : gestion guaranteed (skip roll) + filtrage minDifficulty
- [x] 4 items legendaires crees : Anneau de serre de griffon, Heaume cornu du minotaure, Bouclier coeur de golem, Ceinture du roi troll
- [x] Drops legendaires garantis sur le boss Dragon (dragon_fang_blade, dragon_scale_armor)
- [x] Drops legendaires rares (3%) sur monstres haut niveau (griffon, minotaure, golem, troll) avec minDifficulty=3
- [x] Badge visuel legendaire deja operationnel (fond dore, bordure doree via inv-tooltip-rarity--legendary)

## 38 — Liste d'amis (2026-03-22) ✅

> Systeme complet de liste d'amis avec statut en ligne.

- [x] Entite Friendship (player, friend, status: pending/accepted/blocked, createdAt)
- [x] FriendshipManager : sendRequest, accept, decline, block, unfriend
- [x] Routes GET/POST /game/friends
- [x] Notification Mercure quand un ami se connecte

## 22 — Factions & reputation (2026-03-22) ✅

> Systeme de factions avec reputation et paliers.

- [x] Entite Faction : slug, name, description, icon
- [x] Entite PlayerFaction : player (ManyToOne), faction (ManyToOne), reputation (int)
- [x] Enum ReputationTier : Hostile, Inconnu, Neutre, Ami, Honore, Revere, Exalte
- [x] Calcul automatique du tier selon les seuils de reputation (0, 500, 2000, 5000, 10000, 20000)
- [x] Migration + fixtures 4 factions (Marchands, Chevaliers, Mages, Ombres)
- [x] Route /game/factions : liste des factions, reputation actuelle, palier, barre de progression
- [x] Traductions FR/EN completes

## 27 — Tracking quetes collect/craft (2026-03-22) ✅

> Correction du tracking des quetes de type collect et craft qui ne progressaient jamais.

- [x] QuestTrackingFormater : ajout formatCollect() et formatCraft() pour initialiser le tracking
- [x] PlayerQuestHelper::getPlayerQuestProgress() etendu pour traiter collect et craft
- [x] PlayerQuestUpdater : ajout updateItemCollected() et updateItemCrafted()
- [x] QuestCollectTrackingListener : ecoute SpotHarvestEvent et GatheringEvent
- [x] QuestCraftTrackingListener : ecoute CraftEvent
- [x] CraftEvent cree et dispatche dans CraftingManager apres craft reussi
- [x] SpotHarvestEvent enrichi avec les items recoltes (harvestedItems)
- [x] Templates quest/index et game/index mis a jour pour afficher progression collect/craft
- [x] Fixtures PlayerQuest mises a jour au nouveau format de tracking

---

### 41 — Indicateurs quetes sur PNJ (2026-03-23) ✅

> Indicateurs visuels (! ou ?) au-dessus des PNJ donneurs de quetes sur la carte PixiJS.

- [x] Service `PnjQuestIndicatorResolver` : resout l'indicateur (available/in_progress/null) par PNJ pour un joueur
- [x] Champ `questIndicator` ajoute dans `/api/map/entities` pour chaque PNJ
- [x] Rendu PixiJS : icone `!` jaune (quete disponible) ou `?` grise (quete en cours) au-dessus du sprite PNJ
- [x] Mise a jour dynamique a chaque rechargement des entites (acceptation/completion de quete)

### 36 — Gains et recompenses reputation (2026-03-23) ✅

> Systeme de gains de reputation (mobs tues, quetes completees) et recompenses par palier pour chaque faction.

- [x] `ReputationManager::addReputation(Player, Faction, amount)` : service core de gestion de reputation
- [x] `ReputationListener` : event subscriber sur `MobDeadEvent` et `QuestCompletedEvent`
- [x] Champ `faction` nullable sur `Monster` : monstres associes a une faction donnent de la rep
- [x] Support `rewards.reputation` JSON dans les quetes : gain de reputation configurable par quete
- [x] Entite `FactionReward` : faction, requiredTier, rewardType, rewardData JSON, label, description
- [x] Fixtures : 3 recompenses par faction (Ami, Honore, Exalte) — remises, bonus stats, bonus combat
- [x] Affichage recompenses debloquees/verrouillees sur la page factions
- [x] Migration SQL : table `game_faction_rewards` + colonne `faction_id` sur `game_monsters`

---

## 18 — Commandes chat slash (2026-03-19) ✅

> Systeme de chat avec commandes slash pour la communication entre joueurs.

- [x] `ChatCommandHandler` : detection et routage de 8 commandes (/whisper, /zone, /global, /emote, /who, /help + aliases)
- [x] `ChatManager` : envoi de messages sur 3 canaux (global, map, prive), rate limiting, sanitisation
- [x] `ChatController` : route POST `/game/chat/send`, delegation aux handlers
- [x] Stimulus controller `chat_controller.js` : UI multi-onglets, Mercure SSE temps reel, recherche joueur
- [x] Entite `ChatMessage` : channel, content, sender, recipient, soft delete pour moderation
- [x] 27 tests unitaires ChatCommandHandlerTest

---

## 26 — Recettes de craft fixtures (2026-03-23) ✅

> 10 recettes de craft couvrant les 4 professions d'artisanat : forge, tannerie, alchimie, joaillerie.

- [x] `RecipeFixtures` : 10 recettes de base (4 forge, 3 tannerie, 2 alchimie, 1 joaillerie)
- [x] 4 nouveaux items craftables : dague en fer, bouclier en fer, casque en fer, anneau de cuivre
- [x] Correction `CraftingController` : types de craft alignes sur les slugs de domaine (forgeron, tanneur, alchimiste, joaillier)
- [x] Correction template artisanat : labels francais corrects pour les onglets
- [x] Ingredients utilises : minerais (fer, cuivre), cuirs (brut, epais), plantes (menthe, sauge, lavande)

---

## 45 — Portraits de personnages (2026-03-23) ✅

> Amelioration visuelle des dialogues PNJ avec portraits et icones fallback par class_type.

- [x] Champ `portrait` (string, nullable) sur entite Pnj + migration PostgreSQL
- [x] API `/api/map/pnj/{id}/dialog` retourne `portrait` et `classType` dans la reponse JSON
- [x] Template dialogue Twig : element portrait a gauche du nom du PNJ
- [x] Stimulus `dialog_controller.js` : affichage portrait image ou icone fallback par class_type
- [x] 10 portraits configures dans PnjFixtures pour les PNJ narratifs principaux
- [x] Formulaire admin PNJ : champ portrait ajoute
- [x] Fallback : 10 icones emoji par class_type (villager, merchant, guard, noble, warrior, mage, healer, blacksmith, farmer, hunter)

---

## 23 — Tests fonctionnels controleurs (2026-03-23) ✅

> 18 tests fonctionnels couvrant les 5 controleurs gameplay sans couverture.

- [x] ShopControllerTest (5 tests) : achat OK, fonds insuffisants, item pas en boutique, boutique introuvable, vente soulbound refusee
- [x] InventoryControllerTest (5 tests) : equiper OK, equiper item introuvable, desequiper OK, desequiper introuvable, utiliser consommable (spell + decrementation usages)
- [x] AcquireControllerTest (3 tests) : acquisition OK, skill introuvable, domaine introuvable
- [x] BestiaryControllerTest (3 tests) : rendu avec donnees correctes, redirection sans joueur, zero decouvertes
- [x] AchievementControllerTest (2 tests) : rendu avec categories, comptage succes completes
- [x] Tous les tests existants (342 unit + 51 functional) toujours verts

## 43 — Tests integration events (2026-03-23) ✅

> 19 tests d'integration verifiant que les events declenchent correctement tous les listeners concernes.

- [x] MobDeadEventIntegrationTest (7 tests) : BestiaryListener + AchievementTracker + QuestMonsterTrackingListener + ReputationListener — triggers simultanes, joueurs morts ignores, pas de fight → early return, progression/completion succes, gain reputation
- [x] SpotHarvestEventIntegrationTest (5 tests) : DomainExperienceEvolver + QuestCollectTrackingListener — XP domaine + tracking quete, pas de domaine → skip XP, items vides, items multiples
- [x] QuestCompletedEventIntegrationTest (7 tests) : AchievementTracker + ReputationListener — progression succes + gain reputation, pas de recompense rep, faction inconnue, completion succes avec gils, succes deja complete, reputations multiples
- [x] PHPStan OK, PHP-CS-Fixer OK, 430 tests OK (hors E2E)

## 44 — Extraction services TerrainImport (2026-03-23) ✅

> Refactoring de `TerrainImportCommand` (674 lignes monolithiques) en 2 services reutilisables + commande legere.

- [x] Extraction `TmxParser` (`src/GameEngine/Terrain/TmxParser.php`) : parsing TMX/TSX → tilesets, layers, collision slugs, object groups
- [x] Extraction `EntitySynchronizer` (`src/GameEngine/Terrain/EntitySynchronizer.php`) : creation/mise a jour des entites (portails, mobs, spots, coffres) depuis les objets parses
- [x] Refactoring `TerrainImportCommand` pour deleguer entierement a `TmxParser` et `EntitySynchronizer`
- [x] PHPStan OK, PHP-CS-Fixer OK, 367 tests unitaires OK

## 31 — Types quetes livraison/exploration (2026-03-23) ✅

> Ajout de 2 nouveaux types de quetes : livraison (deliver) et exploration (explore).

- [x] Support `requirements.deliver` dans QuestTrackingFormater : {item_slug, pnj_id, quantity, name}
- [x] Support `requirements.explore` dans QuestTrackingFormater : {map_id, coordinates, name}
- [x] PlayerQuestUpdater : methodes updateDelivered() et updateExplored()
- [x] PlayerQuestHelper : calcul de progression incluant deliver et explore
- [x] QuestExploreTrackingListener : ecoute PlayerMovedEvent pour tracker l'exploration
- [x] Dispatch de PlayerMovedEvent dans PlayerMoveProcessor
- [x] Endpoint POST /game/quests/deliver/{pnjId} pour la livraison d'items
- [x] Action dialog quest_deliver : retrait items inventaire + maj tracking
- [x] Auto-injection pnj_id pour quest_deliver dans PnjDialogParser
- [x] Frontend dialog_controller.js : support action quest_deliver
- [x] InventoryHelper::removeItemBySlug() pour retirer items par slug
- [x] Template Twig : affichage tracking deliver et explore (quetes actives + disponibles)
- [x] 2 quetes fixtures : "Livraison de champignons" (deliver) + "Cartographier la foret" (explore)
- [x] Dialogs PNJ fixtures pour les 2 nouvelles quetes (Henri le Fermier, Mathilde la Cartographe)
- [x] 7 tests unitaires : deliver/explore dans PlayerQuestUpdater + QuestTrackingFormater
- [x] PHPStan OK, PHP-CS-Fixer OK, 379 tests OK

---

## 30 — Teleportation entre cartes (2026-03-23) ✅

> Infrastructure de portails pour voyager entre zones.

- [x] Entite Portal enrichie (sourceMap, targetMap, coordonnees, bidirectionnel)
- [x] PortalManager : teleport(Player, Portal) avec validation
- [x] Endpoint POST /api/map/teleport/{portalId}
- [x] Rendu visuel portails sur la carte PixiJS (cercles violets lumineux)
- [x] Transition visuelle (fade noir existant)
- [x] Topic Mercure map/teleport
- [x] Fixtures portails de test

## 33 — Impact gameplay jour/nuit (2026-03-23) ✅

> Donne une raison concrete au cycle jour/nuit : mobs nocturnes, spots de nuit, horaires boutiques.

- [x] Champ `nocturnal` (bool) sur Mob — mobs nocturnes n'apparaissent que de nuit
- [x] Filtre dans MobSpawnManager : exclure mobs nocturnes le jour, diurnes la nuit
- [x] Champ `nightOnly` (bool) sur HarvestSpot — plantes recoltables uniquement la nuit
- [x] Validation dans HarvestController
- [x] Champs `opensAt`/`closesAt` sur Pnj — horaires d'ouverture boutiques
- [x] Verification dans ShopController + message "La boutique est fermee"
- [x] Migration SQL (3 champs)

## 34 — Meteo backend & diffusion (2026-03-23) ✅

> Systeme meteo aleatoire pondere par saison, diffuse en temps reel via Mercure.

- [x] Enum PHP `WeatherType` : sunny, cloudy, rain, storm, fog, snow
- [x] Champ `currentWeather` + `weatherChangedAt` sur Map
- [x] `WeatherService` : changeWeather(Map) — tirage aleatoire pondere par saison
- [x] Commande Scheduler `app:weather:tick` (toutes les 15 min)
- [x] Route API `GET /api/map/weather`
- [x] Topic Mercure `map/weather` pour broadcast en temps reel
- [x] Migration SQL

## 40 — Synergies cross-domaine (2026-03-23) ✅

> Bonus explicites pour encourager le multi-domaine : combos actifs selon les domaines maitrises.

- [x] Entite `DomainSynergy` (domainA, domainB, bonusType, bonusValue, description)
- [x] Service `SynergyCalculator` : detecte les combos actifs (seuil 50 XP par domaine)
- [x] ~8 synergies fixtures (Feu+Metal=Forge ardente, Eau+Lumiere=Purification, etc.)
- [x] Affichage synergies actives dans /game/skills
- [x] Integration CombatSkillResolver : bonus de synergie appliques aux stats combat
- [x] Tests SynergyCalculator
- [x] Migration SQL

## 42 — Tests unitaires systemes core (2026-03-23) ✅

> Tests unitaires pour les systemes critiques sans couverture : shop, harvest, craft, quest progress.

- [x] Tests HarvestManager : recolte OK, skill manquant, cooldown actif, XP accordee
- [x] Tests CraftingManager : craft OK, ingredients manquants, skill manquant, item cree
- [x] Tests PlayerQuestUpdater : progression monster, collect, craft, completion
- [x] PHPStan OK, PHP-CS-Fixer OK

## 51 — Meteo impact gameplay (2026-03-23) ✅

> Bonus/malus elementaires selon la meteo active et monstres exclusifs par condition meteorologique.

- [x] Table de bonus/malus par meteo × element dans WeatherService
- [x] Modificateur applique dans DamageCalculator via WeatherService::getElementalModifier()
- [x] Champ `spawnWeather` (nullable) sur Mob — mobs exclusifs par meteo
- [x] Filtre dans MapApiController : mobs meteo-specifiques
- [x] Migration SQL (1 champ)

## 28 — Monstres tier 1 — 8 mobs elementaires (2026-03-23) ✅

> 8 monstres elementaires (un par element) niveaux 1-10, avec stats, AI, resistances, loot et succes.

- [x] 8 monstres : Salamandre (Feu/3), Ondine (Eau/2), Sylphe (Air/4), Golem d'argile (Terre/5), Automate rouille (Metal/3), Loup alpha (Bete/4), Feu follet (Lumiere/2), Ombre rampante (Ombre/5)
- [x] Stats, AI patterns et resistances elementaires pour chaque monstre
- [x] Tables de loot (5 drops par monstre, materia elementaire incluse)
- [x] 24 succes bestiaire (3 paliers × 8 monstres)
- [x] 16 mobs places sur la carte (2 par monstre, distances adaptees au niveau)

## 29 — Equipement tier 2 Intermediaire (2026-03-23) ✅

> Set complet 7 pieces × 4 variantes elementaires (Feu, Eau, Terre, Air) = 28 items, avec bonus +10% degats elementaires et loot tables.

- [x] 28 items : Epee, Bouclier, Casque, Plastron, Jambieres, Bottes, Gantelets × 4 elements
- [x] Bonus elementaire sur chaque piece (+10% degats de l'element via effet JSON)
- [x] Mecanique combat : GearHelper calcule le bonus elementaire de l'equipement porte, applique dans FightSpellController
- [x] Tables de loot : drops sur monstres Niveau 2-4 et elementaires tier 1 (probas 2-5%)

## 62 — Particules combat et recolte (2026-03-24) ✅

> Effets de particules visuels branches sur les evenements de combat et de recolte.

- [x] Particules DOM sur sort lance en combat (couleur selon l'element du sort)
- [x] Particules dorees sur coup critique (explosion avec particules blanches)
- [x] Champs `spellElement` et `critical` ajoutes a la reponse JSON de FightSpellController
- [x] Delai de 500ms avant rechargement pour laisser les particules visibles
- [x] Particules vertes PixiJS sur recolte reussie (dispatch event Stimulus harvest→map_pixi)
- [x] Particules dorees (etoiles XP) en complement de la recolte pour le gain de domaine

## 60 — Minimap PixiJS (2026-03-23) ✅

> Overlay minimap en coin haut-droit avec points colores representant les entites.

- [x] Container PixiJS fixe en coin haut-droit (150x150px), fond semi-transparent avec coins arrondis
- [x] Points colores : blanc=joueur, rouge=mobs, bleu=PNJ, jaune=spots recolte, violet=portails
- [x] Fond de terrain vert subtil depuis les cellules en cache
- [x] Viewport rectangle (zone visible) affiche en surbrillance blanche
- [x] Mise a jour throttlee (500ms) pour la performance
- [x] Toggle affichage avec la touche M

## 63 — Flash elementaire et animations combat (2026-03-23) ✅

> Effets visuels complementaires au combat : flash colore, shake camera, animations sprites.

- [x] Flash colore plein ecran sur degats elementaires (rouge=feu, bleu=eau, vert=bete, etc.)
- [x] Shake camera sur coups critiques (animation CSS sur le conteneur .game-page)
- [x] Animation de tremblement sur le sprite cible quand il recoit des degats
- [x] Fondu progressif du sprite a la mort d'un mob (desaturation + opacite reduite)

## 56 — Presets de build (2026-03-24) ✅

> Sauvegarde et chargement de configurations de skills (max 3 presets par joueur).

- [x] Entite `BuildPreset` (player, name, skillSlugs JSON, createdAt)
- [x] Migration SQL (table build_preset)
- [x] Service `BuildPresetManager` : save, load (respec + re-acquire), delete
- [x] `load()` = respec (cout en gils) + acquisition auto des skills du preset
- [x] Limite : 3 presets par joueur
- [x] Routes POST `/game/skills/presets/save`, `/game/skills/presets/{id}/load`, `/game/skills/presets/{id}/delete`
- [x] Section presets dans la page competences avec formulaire de sauvegarde et boutons Charger/Supprimer
- [x] Tests BuildPresetManager (save/load OK, limite atteinte, owner check, combat check)

## 61 — Barre d'action rapide (2026-03-24) ✅

> Raccourcis clavier/boutons en bas de l'ecran carte pour utiliser consommables.

- [x] Barre fixe en bas de l'ecran carte (6 slots) via Stimulus controller `quickbar_controller`
- [x] Picker modal pour selectionner les consommables depuis l'inventaire
- [x] Raccourcis clavier 1-6 pour activer un slot
- [x] Persistance des slots en localStorage
- [x] API `/api/quickbar/items` et `/api/quickbar/use/{id}` avec cooldown 1s

## 47 — Monstres tier 2 lvl 10-15 (2026-03-24) ✅

> 4 monstres intermediaires (lvl 10-15) avec stats, AI patterns, resistances, loot tables et succes bestiaire.

- [x] 4 monstres : Wyverne (Air/Feu/10), Chevalier maudit (Dark/Metal/12), Naga (Eau/Bete/13), Golem de cristal (Terre/Lumiere/15)
- [x] Stats, AI patterns et resistances elementaires pour chaque monstre
- [x] Tables de loot (8 drops par monstre, materia et equipement T2 inclus)
- [x] 12 succes bestiaire (3 paliers × 4 monstres)
- [x] 8 mobs places sur la carte (2 par monstre, zones eloignees du spawn)

## 54 — Quetes a choix (2026-03-24) ✅

> Embranchements narratifs : le joueur fait un choix en rendant une quete, ce qui influence les recompenses et les dialogues futurs.

- [x] Champ `choiceOutcome` (JSON, nullable) sur entite Quest : liste de choix possibles avec cle, label et bonus rewards
- [x] Champ `choiceMade` (string, nullable) sur entite PlayerQuestCompleted : stocke la cle du choix fait
- [x] Migration SQL (2 colonnes)
- [x] QuestController::complete() adapte : validation du choix, application des bonus rewards specifiques au choix
- [x] Methode privee `applyRewards()` extraite pour reutilisation (base + bonus)
- [x] Condition `quest_choice` dans PnjDialogParser : conditionner le dialogue selon le choix passe (format `{"questId": "choiceKey"}`)
- [x] Modal de choix dans le journal de quetes (bouton "Choisir & Rendre" au lieu de "Rendre")
- [x] Affichage du choix fait dans l'onglet "Terminees" du journal
- [x] Formulaire admin : champ `choiceOutcomeJson` pour editer les choix
- [x] 1 quete fixture "Allegeance contestee" : 2 branches (aide garde = bouclier, aide marchand = or + potions)
- [x] Dialogue PNJ conditionnel post-choix (Michel le Garde reagit differemment selon le choix)

---

## 49 — Monstres soigneurs / multi-mobs (2026-03-24) ✅

> Combat multi-mobs et IA soigneur. Les mobs peuvent former des groupes (groupTag) et combattre ensemble.
- [x] Champ `groupTag` (VARCHAR 50, nullable) sur l'entite Mob + migration
- [x] `FightHandler::startGroupFight()` : demarrer un combat avec plusieurs mobs
- [x] `PlayerMoveProcessor::resolveGroupMobs()` : engagement automatique du groupe quand un mob est rencontre
- [x] `MobActionHandler::doAction()` : tous les mobs vivants agissent a chaque tour
- [x] IA soigneur (`role: healer`) : cible l'allie mob le plus blesse (% PV < 70%)
- [x] `SpellApplicator` : supporte deja les heals mob→mob (CharacterInterface)
- [x] Template combat : boucle deja sur `fight.mobs` (multi-mob ready)
- [x] `FightFleeController` : fuite basee sur le mob le plus rapide, verifie tous les boss
- [x] `FightIndexController` : danger alert verifie tous les mobs vivants
- [x] Fixtures : monstre Necromancien (soigneur) + groupe 2 Squelettes + 1 Necromancien
- [x] 5 tests unitaires : multi-mob actions, mobs morts ignores, ciblage soigneur, auto-soin

---

## 55 — Quetes quotidiennes (2026-03-24) ✅

> Systeme de quetes quotidiennes avec rotation automatique.
- [x] Champs `isDaily` (bool) + `dailyPool` (string) sur Quest
- [x] Entite `PlayerDailyQuest` (player, quest, date, tracking, completedAt)
- [x] `DailyQuestService` : rotation, acceptation, progression, completion
- [x] `DailyQuestRotateCommand` : selection aleatoire de 3 quetes/jour
- [x] Symfony Scheduler : rotation quotidienne a 00h01
- [x] QuestController : routes daily/accept, daily/complete, daily/abandon
- [x] 6 quetes quotidiennes dans les fixtures (combat + recolte)
- [x] Section "Quotidiennes" dans le journal de quetes

---

## 52 — Guildes fondation (2026-03-24) ✅

> Systeme de guilde : creation, invitations, gestion des membres et rangs.
- [x] Entite `Guild` (name unique, tag 3-5 chars, description, leader)
- [x] Entite `GuildMember` (guild, player, rank enum, joinedAt) — unique par joueur
- [x] Entite `GuildInvitation` (guild, player, invitedBy)
- [x] Enum `GuildRank` (Leader, Officer, Member, Recruit) avec permissions
- [x] Migration PostgreSQL (3 tables + index + contraintes)
- [x] `GuildManager` : create (5000 gils), invite, accept, leave, kick, promote, demote
- [x] `GuildController` : page de guilde, creation, invitation, gestion membres
- [x] Template Twig avec formulaire creation, liste membres, actions par rang
- [x] Validation : nom unique, max 1 guilde/joueur, cout creation
- [x] 12 tests unitaires : creation, invitation, promotion, depart, kick

---

## 53 — Groupes de combat formation (2026-03-24) ✅

> Systeme de groupe (party) pour jouer ensemble. Base pour le combat coop et donjons futurs.
- [x] Entite `Party` (leader, maxSize: 4, membres)
- [x] Entite `PartyMember` (party, player, joinedAt) — unique par joueur
- [x] Entite `PartyInvitation` (party, player, invitedBy)
- [x] Migration PostgreSQL (3 tables + index + contraintes)
- [x] `PartyManager` : create, invite, accept, leave, kick, transfer leader, disband
- [x] Dissolution automatique si tous les membres partent
- [x] Transfert automatique de leadership si le chef quitte
- [x] `PartyController` : page de groupe, creation, invitation, gestion membres
- [x] Template Twig avec interface de groupe (membres, invitations, actions)
- [x] Lien "Groupe" dans la navigation du jeu
- [x] 13 tests unitaires : creation, invitation, depart, dissolution, transfert leadership

---

## 58 — Parsing zones/biomes Tiled (2026-03-24) ✅

> Peuplement de l'entite Area depuis les objets rectangulaires de type "zone"/"biome" dans Tiled.
- [x] Champs `biome`, `weather`, `music`, `light_level` sur l'entite `Area` + migration PostgreSQL
- [x] Champs bornes de zone `zone_x`, `zone_y`, `zone_width`, `zone_height` sur `Area`
- [x] `AreaSynchronizer` : filtre les objets zone/biome et upsert les Area en BDD
- [x] Exposition des zones dans `/api/map/config` (coordonnees, biome, meteo, musique)
- [x] Option `--sync-zones` dans `app:terrain:import`
- [x] 7 tests unitaires (AreaSynchronizer + TmxParser zones)

---

## 50 — Meteo effets visuels PixiJS (2026-03-24) ✅

> Effets visuels de meteo dans le renderer PixiJS (pluie, neige, orage, brouillard).
- [x] Ecoute du topic Mercure `map/weather` dans `map_pixi_controller.js`
- [x] Container de particules dedie (zIndex 400, au-dessus des entites, sous le HUD)
- [x] Effet pluie : particules tombantes bleues semi-transparentes
- [x] Effet neige : particules blanches lentes avec oscillation laterale
- [x] Effet orage : flash blanc intermittent + particules pluie
- [x] Effet brouillard : overlay blanc semi-transparent avec alpha pulse doux
- [x] Effet nuageux : leger assombrissement (overlay gris alpha 0.08)
- [x] Transition douce entre meteos (fade 2 secondes)

---

## 57 — Commande terrain:sync (2026-03-24) ✅

> Commande unifiee `app:terrain:sync` orchestrant tout le pipeline d'import Tiled.
- [x] `TerrainSyncCommand` : import TMX + sync entites + sync zones + rebuild Dijkstra + rapport diff
- [x] Integration Dijkstra post-import (regeneration du cache collisions)
- [x] Rapport diff (fichiers exportes, entites/zones synchronisees, Dijkstra maps regenerees)
- [x] Mise a jour de l'agent `.claude/commands/import-terrain.md`

---

## 73 — Guildes chat (2026-03-24) ✅

> Canal de communication dedie a la guilde via un nouveau topic Mercure.
- [x] `CHANNEL_GUILD` dans ChatMessage + relation `guild` (ManyToOne)
- [x] Migration PostgreSQL : colonne `guild_id` sur `chat_message`
- [x] Topic Mercure `chat/guild/{guildId}` dans ChatManager
- [x] Methodes `sendGuildMessage()` et `getGuildHistory()` dans ChatManager
- [x] Onglet "Guilde" dans le chat (template + Stimulus controller)
- [x] Couleur emerald pour les messages de guilde
- [x] Abonnement Mercure `chat/guild/{guildId}` cote client
- [x] Verification d'appartenance a la guilde avant envoi (ChatController + ChatCommandHandler)
- [x] Commande `/guild` (alias `/gu`) dans ChatCommandHandler

---

## 46 — Trame Acte 1 : L'Eveil (2026-03-24) ✅

> Tutoriel narratif. Chaine de 5 quetes guidant le joueur dans ses premieres actions.

- [x] Quete 1.1 "Reveil" : dialogue d'introduction avec Claire la Sage, explorer la place du village
- [x] Quete 1.2 "Premiers pas" : aller voir Gerard le Forgeron, recevoir une epee courte
- [x] Quete 1.3 "Bapteme du feu" : tuer 2 slimes dans la zone de depart
- [x] Quete 1.4 "Recolte" : collecter 3 champignons pour Marie la Herboriste
- [x] Quete 1.5 "Le Cristal d'Amethyste" : explorer la clairiere au sud, dialogue revelateur
- [x] Dialogues narratifs pour Claire la Sage (guide), Gerard le Forgeron, Marie la Herboriste
- [x] Recompenses progressives : gils, XP, epee courte, potions, parchemin herboristerie, materia Soin
- [x] Chaine de prerequis : Reveil → Premiers pas → Bapteme → Recolte → Cristal

---

## 59 — Tests E2E Panther (2026-03-24) ✅

> Tests de parcours complets multi-pages via Symfony Panther (Chrome headless).

- [x] Parcours combat : carte → engagement mob via API → combat → attaque en boucle → victoire/loot → retour carte
- [x] Parcours quete : page quetes → navigation onglets → accepter quete disponible → verifier suivi actif → abandonner
- [x] Parcours craft : inventaire → atelier → navigation onglets professions → affichage recettes → tentative fabrication
- [x] Tests UI combat : verification boutons action (attaque, sorts, objets, fuite), combattants visibles
- [x] Tests navigation craft : onglets professions, section experimentation, cartes de recettes

---

## 48 — Village central hub (2026-03-24) ✅

> Nouvelle carte "Village de Lumière" servant de hub principal entre les zones. Zone safe (aucun monstre).

- [x] Carte Tiled 40x40 (world-1-village-1.tmx) avec plaza centrale, batiments, chemins pavés
- [x] Entité Map "Village de Lumière" (map_2) dans MapFixtures
- [x] 6 PNJ hub : Aldric le Forgeron (armes/armures), Iris l'Alchimiste (potions), Marcellin le Marchand (outils/nourriture), Oriane la Maîtresse des Quêtes, Théodore le Banquier, Gareth le Garde
- [x] Dialogues PNJ avec boutiques, horaires d'ouverture, et substitution {{player_name}}
- [x] Portails bidirectionnels : carte principale (30.30) ↔ village (19.39/20.39)
- [x] Données d'area générées pour le rendu PixiJS (area_data.json + world-1-village-1.json)

---

## 79 — Événements bonus/festivals (2026-03-24) ✅

> Intégration des bonus xp_bonus et drop_bonus dans tous les systèmes de jeu, quêtes d'événement temporaires, et cosmétiques d'événement.

- [x] Intégrer `drop_bonus` dans LootGenerator (déjà fait en tâche 21)
- [x] Intégrer `xp_bonus` dans CraftingManager (multiplicateur sur l'XP de craft)
- [x] Intégrer `xp_bonus` dans DomainExperienceEvolver (gathering, fishing, butchering)
- [x] Quêtes d'événement : champ `gameEvent` sur Quest, filtrage automatique des quêtes expirées
- [x] Cosmétiques d'événement : flag `isCosmetic` sur Item, items décoratifs exclusifs
- [x] Fixtures : Festival des Étoiles (bonus XP x2, bonus drop x1.5, 2 quêtes, 2 cosmétiques)
- [x] Migration PostgreSQL idempotente
- [x] Tests unitaires : bonus XP crafting, quêtes d'événement actives/inactives, flag cosmétique

---

## Tâche 76 — Sets d'équipement (2026-03-25) ✅

> Bonus progressifs quand plusieurs pièces du même set sont portées simultanément.

- [x] Entité `EquipmentSet` (slug, name, description)
- [x] Entité `EquipmentSetBonus` (set, requiredPieces, bonusType, bonusValue)
- [x] Champ `equipmentSet` (ManyToOne, nullable) sur Item + migration PostgreSQL
- [x] Service `EquipmentSetResolver` : détecte les sets actifs depuis l'équipement du joueur
- [x] Bonus appliqués dans le combat via `CombatSkillResolver` (damage, heal, hit, critical, life, protection)
- [x] Affichage dans inventaire : pièces du set équipées, bonus actifs/inactifs, nom du set par pièce
- [x] Fixtures : 3 sets de base (Set du Gardien 2/3/4 pièces, Set de l'Ombre 2/3, Set du Veilleur 2/3)
- [x] Tests unitaires EquipmentSetResolver (7 tests)

---

## Tâche 77 — Effets ambiance par zone (2026-03-25) ✅

> Détection de la zone courante du joueur et application d'effets visuels dynamiques en frontend.

- [x] Charger les zones depuis l'API `/api/map/config` au chargement de la carte
- [x] Détecter la zone courante du joueur (point-in-rect) à chaque déplacement
- [x] Appliquer les effets par zone : overlay teinté par biome (forêt, marais, dark, etc.)
- [x] Particules ambiantes par biome (feuilles en forêt, bulles en marais, lucioles sombres, poussière)
- [x] Modificateur de lumière par zone (intégré au cycle jour/nuit)
- [x] Override météo par zone (ex: brouillard permanent en marais, orage dans la lande)
- [x] Transition fluide entre zones (fondu progressif overlay + lumière)
- [x] Re-détection après téléportation (portail vers nouvelle carte)
- [x] Fixtures : 6 zones (5 sur carte principale + 1 village) avec biomes, météo et niveaux de lumière

---

## Tâche 72 — Donjons entité & entrée (2026-03-25) ✅

> Structure de donjon instancié : entités, difficultés, cooldown et point d'entrée.

- [x] Enum `DungeonDifficulty` : Normal, Heroique, Mythique (multiplicateurs HP/dégâts, cooldowns 1h/4h/24h)
- [x] Entité `Dungeon` : slug, name, description, map (ManyToOne), minLevel, maxPlayers, lootPreview (JSON)
- [x] Entité `DungeonRun` : dungeon, player, difficulty, startedAt, completedAt
- [x] Migration PostgreSQL : tables `game_dungeons` + `dungeon_run` avec FK et index
- [x] `DungeonRunRepository` : findActiveRun, findLastCompletedRun, findPlayerHistory
- [x] `DungeonManager` : entrée avec vérifications (run actif, niveau requis, cooldown, combat), téléportation, complétion
- [x] `DungeonController` : liste des donjons, fiche donjon avec choix de difficulté, entrée POST
- [x] Templates Twig : liste des donjons, fiche détaillée avec sélection de difficulté et cooldowns
- [x] Fixtures : 1 donjon de test "Racines de la forêt" (minLevel 5, 1 joueur)

---

## Tache 69 — Monstres invocateurs (2026-03-25) ✅

> Monstres capables d'invoquer des renforts en cours de combat (max 2 par combat).

- [x] Action IA `summon` dans MobActionHandler (generateAction + executeSummon)
- [x] Creation de Mob en cours de combat (ajout a la Fight, insertion dans la timeline)
- [x] Limite d'invocation (MAX_SUMMONS_PER_FIGHT = 2)
- [x] FightTurnResolver : recalcul dynamique de la timeline (getTurnOrder itere fight.getMobs())
- [x] Fixtures : Necromancien invoque des Squelettes (aiPattern.summon avec cooldown, role: summoner)
- [x] Message de log specifique via CombatLogger.logSummon ("X invoque un Y !")
- [x] Champ `summoned` sur Mob : les mobs invoques ne droppent pas de loot
- [x] Type de log `TYPE_SUMMON` + `logSummon()` dans CombatLogger
- [x] Migration PostgreSQL idempotente
- [x] Tests unitaires (5 tests) : invocation, limite atteinte, proprietes mob, chance 0%, slug inconnu

---

## Tache 70 — Slots materia lies (2026-03-25) ✅

> Synergie entre slots adjacents : bonus +15% degats si les materia sockettees partagent le meme element.

- [x] Champ `linkedSlot` (OneToOne, nullable) sur l'entite Slot + migration PostgreSQL
- [x] Service `LinkedMateriaResolver` : detection synergie, multiplicateur de degats (1.15x)
- [x] Integration dans CombatCapacityResolver (champ `linkedBonus` dans getEquippedMateriaSpells)
- [x] Application du bonus dans FightSpellController (+15% degats)
- [x] Affichage visuel dans le template inventaire (badge "Lie", couleur cyan, connecteur ⟷)
- [x] Fixtures : slots lies automatiquement par paires sur equipements a 2+ slots
- [x] Tests unitaires LinkedMateriaResolverTest (10 tests)

---

## Tache 65 — Monstres tier 2 avances lvl 15-25 (2026-03-25) ✅

> 4 monstres intermediaires (lvl 15-25) avec IA complexe (soigneurs, invocateurs), loot tables et succes bestiaire.

- [x] **Archidruide corrompu** (lvl 16, diff 4) : soigneur nature/ombre, heal a 45% HP, sorts nature + dark_harvest
- [x] **Liche mineure** (lvl 18, diff 5) : invocateur dark, invoque 2 squelettes, sorts ombre + dark_ritual
- [x] **Hydre des marais** (lvl 20, diff 5) : tank eau/bete multi-attaque, sequence 6 coups, tidal_wave
- [x] **Forgeron abyssal** (lvl 24, diff 5) : tank metal/feu, tres resistant, shrapnel_burst + steel_shield
- [x] Loot tables pour les 4 monstres (potions, materia, equipement T2)
- [x] Placement sur la carte (8 mobs, zones eloignees 32-138 du spawn)
- [x] 12 succes bestiaire (3 paliers x 4 monstres : 10/50/100 kills)

---

## 74 — Guildes coffre partage (2026-03-25) ✅

> Inventaire collectif de guilde avec permissions par rang et tracabilite des actions.
- [x] Entite `GuildVault` (guild OneToOne, items OneToMany PlayerItem, maxSlots)
- [x] Entite `GuildVaultLog` (guild, player, action deposit/withdraw, item, quantity, createdAt)
- [x] Relation `guildVault` sur `PlayerItem` + relation `vault` sur `Guild`
- [x] Migration PostgreSQL : tables `guild_vault`, `guild_vault_log`, colonne `guild_vault_id` sur `player_item`
- [x] Permissions vault dans `GuildRank` : `canDeposit()` (tous), `canWithdraw()` (member+)
- [x] `GuildVaultManager` : deposit, withdraw, getOrCreateVault, getRecentLogs
- [x] Routes : `GET /game/guild/vault`, `POST .../deposit/{itemId}`, `POST .../withdraw/{itemId}`
- [x] Template vault avec affichage coffre, depot depuis sac, historique recent
- [x] Lien "Coffre de guilde" dans la page guilde
- [x] 12 tests unitaires : depot, retrait, permissions recruit/member, coffre plein, objet equipe/lie, item pas dans coffre

---

## 66 — Boss de zone (2026-03-25) ✅

> Deux boss avec mecaniques de phases, loot unique et succes associes.

- [x] **Gardien de la Foret** (lvl 15, diff 5, 400 HP) : boss Bete/Terre, 2 phases
  - Phase 1 — Eveil sylvestre : sorts forest_call, entangling_roots
  - Phase 2 — Fureur de la nature (< 50% HP) : sort signature primordial_roar (AoE + paralysie)
  - Resistances : bete/terre +50%, feu -50%, metal -30%
- [x] **Seigneur de la Forge** (lvl 20, diff 5, 500 HP) : boss Metal/Ombre, 3 phases
  - Phase 1 — Le Forgeron : sorts blade_dance, shrapnel_burst
  - Phase 2 — Metal en fusion (< 60% HP) : blade_dance preferee
  - Phase 3 — Forge obscure (< 30% HP) : sort signature dark_forge_blast (AoE + brulure)
  - Resistances : metal +60%, dark +40%, eau -50%, lumiere -40%
- [x] 2 sorts de boss : primordial_roar (Beast AoE, paralysie), dark_forge_blast (Metal AoE, brulure)
- [x] 4 items legendaires uniques :
  - Cuirasse d'ecorce ancestrale (Beast, protection 18, 2 slots materia)
  - Baton d'epines primordiales (Earth, arme mage, 2 slots materia)
  - Lame d'obsidienne du Seigneur (Metal, arme soldat, 2 slots materia)
  - Plastron de la forge obscure (Dark, protection 22, 2 slots materia)
- [x] Tables de loot avec drops garantis pour chaque boss
- [x] 2 succes : Gardien terrasse, Seigneur de la forge vaincu (avec titres)
- [x] Placement sur la carte (1 mob par boss, zones eloignees)

---

## 64 — Equipement tier 3 + slots materia (2026-03-25) ✅

> Set avance avec slots materia integres pour les builds endgame.

- [x] 28 items tier 3 : 7 pieces × 4 elements (Metal, Bete, Lumiere, Ombre)
  - Epees, boucliers, casques, plastrons, jambieres, bottes, gantelets
  - Rarete Epic, niveau 15, +15% degats elementaires
- [x] 1-2 slots materia sur chaque piece (2 pour armes/plastrons, 1 pour le reste)
- [x] Spell `none_attack_3` (damage 3) pour les armes tier 3
- [x] 4 equipment sets avec bonus progressifs (2/4/6 pieces) :
  - Acier Runique (Metal) : protection + degats + critique
  - Predateur Sauvage (Bete) : degats + critique + vie
  - Aurore Sacree (Lumiere) : soin + precision + vie
  - Abysses Eternelles (Ombre) : degats + soin + critique
- [x] Loot tables : drops T3 sur monstres lvl 15-25 et boss de zone

## 78 — Equilibrage & rapport (2026-03-25) ✅

> Commande CLI de rapport d'equilibrage et document de reference pour ajuster les stats du jeu.

- [x] Commande `app:balance:report` avec sections : monsters, items, drops, domains, spells, alerts
- [x] Courbe XP par domaine (cout unitaire vs cumul)
- [x] Stats monstres par palier (HP, degats, XP donne)
- [x] Table de drop rates par monstre et rarete
- [x] Alertes automatiques si desequilibre detecte (monstre trop fort/faible, drop rate aberrant, item sans prix)
- [x] Sort sans effet, sort gratuit surpuissant, domaine vide
- [x] Document de reference `docs/BALANCE.md` : courbe XP, bareme prix, degats attendus, seuils d'alerte

## 75 — PNJ routines (2026-03-26) ✅

> Les PNJ se deplacent selon un horaire in-game, animes sur la carte via Mercure.
- [x] Entite `PnjSchedule` (pnj, hour, coordinates, map) — table horaire du PNJ
- [x] Migration SQL
- [x] `PnjRoutineService` : deplace les PNJ selon l'heure in-game courante
- [x] Commande Scheduler `app:pnj:routine` (toutes les 5 min)
- [x] Topic Mercure `map/pnj-move` pour animer le deplacement cote client
- [x] Animation de marche du PNJ dans le renderer PixiJS (reutiliser SpriteAnimator)
- [x] Fixtures : 4 PNJ avec routines simples (maison - travail - taverne)
- [x] Gestion du cas ou un joueur parle a un PNJ qui se deplace

## 88 — Stock boutique & restock (2026-03-26) ✅

> Les boutiques PNJ ont desormais un stock limite qui se reapprovisionne periodiquement.
- [x] Colonne `shop_stock` (JSON) sur l'entite Pnj — stock, maxStock, restockInterval par item
- [x] Migration SQL
- [x] `ShopController::buy()` verifie le stock et le decremente a l'achat
- [x] Commande `app:shop:restock` (mode one-shot ou boucle) — reapprovisionne selon l'intervalle
- [x] Affichage du stock restant dans le template boutique (badge couleur, rupture)
- [x] Bouton Acheter desactive si stock = 0
- [x] Fixtures : stock initial pour toutes les boutiques (PnjFixtures + VillageHubPnjFixtures)

## 86 — Quetes de decouverte cachees (2026-03-26) ✅

> Quetes non visibles dans le journal tant que non declenchees. Se declenchent automatiquement via les actions du joueur.
- [x] Champ `isHidden` (bool) sur Quest + champ `triggerCondition` (JSON)
- [x] `HiddenQuestTriggerListener` : ecoute PlayerMoveEvent, SpotHarvestEvent, MobDeadEvent
- [x] Si condition remplie, creer automatiquement le PlayerQuest
- [x] 4 quetes cachees dans les fixtures (clairiere secrete, slime rare, herborisme, cache gobelin)

## 85 — Evenements aleatoires (2026-03-26) ✅

> Systeme d'evenements aleatoires pour dynamiser le monde avec des bonus temporaires.
- [x] `RandomEventGenerator` : selection ponderee parmi 3 templates (Aurore Mystique, Esprit du Marchand, Heure Doree)
- [x] Prevention des doublons : un seul evenement aleatoire actif a la fois
- [x] Commande `app:events:random` (probabilite 30%, option `--force`)
- [x] Scheduler : execution toutes les 30 minutes
- [x] Duree limitee 10-30 min, parametres `random_event: true` pour identification
- [x] Integration automatique via GameEventExecutor (activation, Mercure broadcast, completion)
- [x] HUD existant affiche les evenements sans modification frontend
- [x] 8 tests unitaires couvrant generation, probabilite, doublons, parametres

## 90 — Herbier & catalogue minier (2026-03-26) ✅

> Catalogue des ressources recoltees par le joueur, avec paliers de decouverte et completion.
- [x] Entite `PlayerResourceCatalog` (player, item, collectCount, firstCollectedAt) — paliers 5/25/50
- [x] Migration SQL
- [x] `ResourceCatalogListener` : ecoute SpotHarvestEvent et GatheringEvent pour tracker les recoltes
- [x] `PlayerResourceCatalogRepository` avec requetes optimisees
- [x] `ResourceCatalogController` : page `/game/catalog`
- [x] Template Twig avec badges paliers, barre de progression, infos revelees
- [x] Navigation : lien dans le dropdown Aventure et le drawer mobile
- [x] Traductions FR/EN

## GCC-07 — Influence — entites score & log (2026-03-26) ✅

> Tables de score et journal des gains d'influence pour le systeme de controle de cite par les guildes.
- [x] Enum `InfluenceActivityType` : mob_kill, craft, harvest, fishing, butchering, quest, challenge
- [x] Entite `GuildInfluence` : guild, region, season, points (UNIQUE guild+region+season, index ranking)
- [x] Entite `InfluenceLog` : guild, region, season, player, activityType, pointsEarned, details (JSON), createdAt
- [x] Migration PostgreSQL (2 tables avec FK et index)

## GCC-08 — InfluenceListener — hook events PvE (2026-03-26) ✅

> Coeur du moteur d'influence : ecoute les evenements PvE existants et attribue des points d'influence aux guildes.
- [x] `InfluenceManager` : calculatePoints (formules par type), addPoints (upsert GuildInfluence + insert InfluenceLog), awardInfluence (orchestrateur)
- [x] Region determinee via `player.map.region` (FK directe)
- [x] Multiplicateur saisonnier via `season.parameters.multipliers[activityType]`
- [x] `InfluenceListener` (EventSubscriber) : MobDeadEvent, CraftEvent, SpotHarvestEvent, FishingEvent, ButcheringEvent, QuestCompletedEvent
- [x] Ignore si joueur pas en guilde ou map sans region
- [x] Tests unitaires : 15 tests InfluenceManagerTest + 14 tests InfluenceListenerTest

## 87 — Types quetes avances : enquete et defi boss (2026-03-26) ✅

> Deux nouveaux types de quetes avec tracking complet et integration UI.
- [x] Type `enquete` (talk_to) : parler a plusieurs PNJ pour avancer, tracke via PnjDialogEvent
- [x] Type `boss_challenge` : vaincre un boss sous conditions (no_heal, solo, time_limit)
- [x] Conditions de defi trackees dans le combat (colonne metadata sur Fight)
- [x] QuestBossChallengeTrackingListener et QuestTalkToTrackingListener
- [x] QuestTrackingFormater : formatTalkTo() et formatBossChallenge()
- [x] 2 quetes fixtures : enquete herboriste (3 PNJ), defi gardien de la foret

## 83 — Invasions (2026-03-26) ✅

> Vagues de monstres cooperatives via GameEvent. Les joueurs collaborent pour repousser l'invasion.
- [x] `InvasionManager` (EventSubscriber) : spawn des mobs a l'activation, vagues progressives, cleanup a la fin
- [x] Vagues progressives : 3 vagues espacees de 2 min, difficulte croissante (+2 niveaux par vague)
- [x] `InvasionKillTracker` : ecoute MobDeadEvent, track les kills par joueur dans les params de l'event
- [x] Recompenses collectives proportionnelles aux kills si objectif atteint
- [x] `InvasionTickCommand` (`app:invasion:tick`) : avancement periodique des vagues
- [x] Notifications Mercure : invasion_start, invasion_progress, invasion_end, invasion_mob_spawn/despawn
- [x] Nettoyage automatique des mobs d'invasion a la fin de l'event
- [x] Fixture : invasion gobeline (3 vagues de 4 mobs, objectif 8 kills, recurrente)

## 89 — Enchantements temporaires (2026-03-26) ✅

> Alchimiste applique un buff temporaire sur une arme/armure equipee. Les bonus s'appliquent en combat.
- [x] Entite `EnchantmentDefinition` (slug, name, element, statBonuses, duration, ingredients, requiredLevel, cost)
- [x] Entite `Enchantment` (playerItem, definition, appliedAt, expiresAt, isExpired(), getRemainingSeconds())
- [x] Migration SQL (tables game_enchantment_definitions + enchantments)
- [x] Service `EnchantmentManager` : apply, canEnchant, remove, cleanExpired, getEnchantmentBonuses
- [x] Route POST `/game/craft/enchant` (necessite skill alchimiste + ingredients + gils)
- [x] Expiration automatique verifiee au debut de chaque combat (FightHandler)
- [x] Bonus d'enchantement integres dans FightSpellController et FightAttackController
- [x] Section enchantements dans la page Artisanat (template _enchantment.html.twig)
- [x] Fixtures : 4 enchantements (Tranchant de feu, Protection de glace, Robustesse tellurique, Precision lumineuse)
- [x] Tests EnchantmentManager (10 tests, 20 assertions)

---

## 71 — World boss spawn & combat (2026-03-26) ✅

> Boss mondial spawn via evenements, visible sur la carte, combat multi-joueurs avec loot a contribution.
- [x] GameEventExecutor traite `boss_spawn` → creer un Mob boss sur une map donnee (params JSON)
- [x] Afficher le world boss sur la carte avec un sprite/aura distinctif
- [x] Despawn automatique quand l'event expire (si non vaincu)
- [x] Permettre a plusieurs joueurs d'engager le meme Mob (Fight partage)
- [x] `ContributionTracker` : tracker les degats infliges par chaque joueur pendant le combat
- [x] Loot base sur la contribution (top 3 = loot garanti, autres = loot probabiliste)
- [x] Tests world boss : FightContributionTest, WorldBossLootDistributorTest, FightHandlerWorldBossTest (18 tests, 48 assertions)

## 97 — Parsing animations tiles (2026-03-26) ✅

> Les fichiers TSX contiennent des animations de tiles (eau, torches). Le backend les extrait et les expose dans l'API.
- [x] `TmxParser::parseTileAnimations()` : extraction des `<tile><animation>` depuis les TSX (tileId local + duration)
- [x] Stockage des animations dans les metadonnees terrain (cle `animations` par tileset)
- [x] Exposition dans `GET /api/map/config` via champ `tileAnimations` (GID global → frames + durations)
- [x] Test unitaire `TmxParserAnimationTest` (parsing avec et sans animations)

## 67 — Foret des murmures (2026-03-26) ✅

> Carte de contenu lvl 5-15 : foret 60x60 avec monstres, PNJ, spots de recolte et portails vers le hub.
- [x] Design TMX 60x60 genere proceduralement (arbres, clairieres, riviere, chemins)
- [x] Map entity `map_3` dans MapFixtures
- [x] 10 mobs adaptes lvl 5-15 (slime, spider, undine, ochu, venom_snake, sylph, alpha_wolf, salamander, will_o_wisp nocturne, creeping_shadow nocturne)
- [x] 3 PNJ : Sylvain le Garde forestier, Elara l'Herboriste (boutique potions), Thadeus l'Ermite
- [x] Portails bidirectionnels Village ↔ Foret (3 portails)
- [x] 6 spots de recolte (menthe, sauge, pissenlit/lavande, romarin, mandragore, peche riviere)

## 68 — Mines profondes (2026-03-26) ✅

> Carte de contenu lvl 10-25 : mines 60x30 avec tunnels, boss de mine, filons et PNJ.
- [x] Map entity `map_4` dans MapFixtures (60x30)
- [x] 11 mobs adaptes lvl 10-25 (stone_golem, rusty_automaton, clay_golem, crystal_golem, gargoyle, cursed_knight nocturne, abyssal_blacksmith, lesser_lich nocturne, groupe patrouille automates)
- [x] Boss de mine : Seigneur de la Forge (forge_lord) en salle profonde
- [x] 3 PNJ : Grimmur le Contremaître, Hilda l'Ingenieure (boutique potions + pioche), Noric le Marchand souterrain (boutique minerais)
- [x] Portails bidirectionnels Village ↔ Mines (3 portails)
- [x] 6 spots de recolte minerais (cuivre, fer x2, argent, or, rubis) repartis par profondeur

## MED-08 — Undo / Redo editeur de carte (2026-03-26) ✅

> Historique des modifications dans l'editeur de carte web (tiles, collisions, murs). 50 operations max.
- [x] Systeme d'historique integre au controller Stimulus (stack undo/redo, 50 ops)
- [x] Capture des changements par stroke (mousedown→mouseup = 1 entree)
- [x] Support tiles, collisions, murs et bucket fill
- [x] Raccourcis Ctrl+Z (undo) / Ctrl+Y ou Ctrl+Shift+Z (redo)
- [x] Boutons undo/redo dans la barre d'outils avec etat disabled
- [x] Reset historique apres sauvegarde ou annulation

## MED-16 — Export TMX & tests unitaires (2026-03-27) ✅

> Export des cartes creees dans l'editeur web vers le format Tiled (.tmx) pour validation externe.
- [x] Classe `TmxExporter` dans `src/GameEngine/Terrain/TmxExporter.php`
- [x] Export 5 layers (background, ground, decoration, overlay, collision) en CSV
- [x] Export objectgroup (portals, mob_spawn, harvest_spot, npc_spawn) avec coordonnees pixels
- [x] Route `GET /admin/maps/{id}/export-tmx` avec telechargement (Content-Disposition: attachment)
- [x] Bouton "Exporter TMX" dans la toolbar de l'editeur
- [x] 10 tests unitaires (27 assertions) : XML valide, attributs map, tilesets, layers, GIDs, collisions, borders, filename

## GCC-10 — Controle de ville — attribution fin de saison (2026-03-27) ✅

> Attribution du controle de region a la guilde gagnante a la fin d'une saison d'influence.
- [x] Entite `RegionControl` : region, guild (nullable), season, startedAt, endsAt (nullable). Index actif (region_id, ends_at)
- [x] `TownControlManager::attributeControl(InfluenceSeason)` : pour chaque region contestable, SELECT guild max points, cree RegionControl
- [x] Egalite : la guilde tenant conserve le controle
- [x] Aucune guilde / 0 points : region reste libre (guild = null)
- [x] `getControllingGuild(Region)` : retourne Guild ou null via controle actif (ends_at IS NULL)
- [x] Migration PostgreSQL `region_control` (3 FK, 3 index)
- [x] 8 tests unitaires (25 assertions) : winner unique, aucun influence, egalite tenant conserve, non-contestable ignore, 0 points, controle actif, pas de controle, fermeture ancien controle

## 84 — Donjons mecaniques & loot (2026-03-27) ✅

> Rend les donjons interessants avec des mecaniques propres et un boss final avec phases.
- [x] Mobs du donjon : spawns specifiques au DungeonRun, stats scalees selon difficulte
- [x] Boss de fin de donjon avec mecaniques de phase (indicateur de phase dans l'UI, log de transition de phase dans le combat log, tracking via metadata fight)
- [x] LootTable specifique donjon : items exclusifs par difficulte (utiliser minDifficulty de EG-5)
- [x] Completion du donjon : marquer DungeonRun completed, teleporter le joueur hors du donjon
- [x] Succes lies aux donjons (premier clear, clear Mythique, clear sans mort)

## 80 — Trame Acte 2 : Fragment Montagne (2026-03-27) ✅

> Dernier fragment de l'Acte 2. Chaine de 3 quetes sur la Crete de Ventombre (zone montagneuse lvl 15-25).
- [x] Carte map_6 Crete de Ventombre (50x50) + portails Village ↔ Montagne
- [x] Item cle Fragment du Sommet (quest-fragment-montagne, epic, bound)
- [x] Chaine de 3 quetes sequentielles : talk_to (Aldric l'Ancien), boss_challenge (Dragon ancestral), explore (Pic sacre)
- [x] 2 PNJ montagne : Aldric l'Ancien (ermite, lance la chaine), Seren la Guide (marchande)
- [x] Dialogues conditionnels Acte 2 pour Aldric (aiguillage par progression de quete)
- [x] 9 mobs sur map_6 (griffons, gargouilles, elementaires, minotaure, troll, dragon boss)
- [x] Les 4 fragments donnent chacun un item cle collectible (foret, mines, marais, montagne)

## 103 — Achievements caches & categories succes (2026-03-27) ✅

> Enrichissement du systeme de succes : achievements secrets et nouvelles categories.
- [x] Champ `hidden` sur l'entite Achievement + migration
- [x] Nouvelles categories : Recolte (gathering), Artisanat (craft), Secrets
- [x] Fixtures : 4 succes recolte, 4 succes craft, 7 succes secrets (mort, fuite, recolte 1000, craft 500, quetes 100)
- [x] AchievementTracker ecoute GatheringEvent, CraftEvent, PlayerDeadEvent, CombatFleeEvent
- [x] Nouvel evenement CombatFleeEvent dispatche depuis FightFleeController
- [x] Template : succes caches masques jusqu'a decouverte, affichage "???" et badge "Secret" ambre
- [x] Recompenses cachees invisibles tant que non completes

## 102 — Index DB composites (2026-03-27) ✅

> Index composites sur les tables critiques pour ameliorer les performances des requetes.
- [x] Index composite `(channel, created_at)` sur ChatMessage — listing pagine par canal
- [x] Index composite `(guild_id, created_at)` sur ChatMessage — chat de guilde
- [x] Index composite `(inventory_id, item_id)` sur PlayerItem — recherche d'items
- [x] Index composite `(player_id, expires_at)` sur PlayerStatusEffect — effets actifs
- [x] Deja en place : `(fight_id, turn)` sur FightLog, `(player_id, quest_id)` sur PlayerQuest, `(player_id, monster_id)` sur PlayerBestiary

## 93 — Quetes de guilde (2026-03-28) ✅

> Objectifs collectifs hebdomadaires pour les guildes. Tous les membres contribuent, les recompenses sont partagees.
- [x] Entite `GuildQuest` (guild, type kill/collect/craft, target, progress, goal, gilsReward, pointsReward, expiresAt)
- [x] Enum `GuildQuestType` (Kill, Collect, Craft)
- [x] `GuildQuestManager` : generation de 3 quetes hebdomadaires, suivi de progression, distribution des recompenses (gils repartis entre membres + points de guilde)
- [x] `GuildQuestListener` : ecoute MobDeadEvent, SpotHarvestEvent, CraftEvent pour progression collective
- [x] Route `GET /game/guild/quests` : liste quetes actives avec barres de progression + historique completees
- [x] Lien dans la page guilde vers les quetes
- [x] Migration PostgreSQL : table `guild_quest`
- [x] Tests unitaires : GuildQuestManagerTest (progression, completion, distribution gils), GuildQuestListenerTest (events)

## 95 — Saisonnalite & festivals (2026-03-28) ✅

> Contenu evenementiel saisonnier. Poids meteo ajustes par saison, entite Festival, 4 festivals de base, decorations saisonnieres PixiJS.
- [x] Detection de la saison reelle (printemps/ete/automne/hiver) dans `GameTimeService` (existait deja)
- [x] Poids meteo ajustes par saison dans `WeatherService` (neige x4 en hiver, orages x2.5 en ete, etc.)
- [x] Entite `Festival` (slug, name, season, startDay, endDay, rewards) + migration
- [x] 4 festivals de base (Fete du Renouveau, Solstice de Flamme, Moisson des Ames, Nuit Eternelle)
- [x] API `/api/game/time` enrichie avec les festivals actifs
- [x] Decorations saisonnieres PixiJS : petales (printemps), lucioles (ete), feuilles (automne) + HUD festival
- [x] Tests unitaires : FestivalTest, WeatherServiceSeasonTest

## 98 — Rendu tiles animees PixiJS (2026-03-28) ✅

> Remplacement de PIXI.Sprite par PIXI.AnimatedSprite pour les tiles animees (eau, torches, etc.). Les donnees d'animation etaient deja parsees (tache 97) et transmises par l'API `/api/map/config`.
- [x] Dans `_loadConfig()` : construction des textures de frames animees depuis `config.tileAnimations`
- [x] Dans `_renderCell()` : detection des tiles animees et creation de `PIXI.AnimatedSprite` avec frames/durations
- [x] Gestion du cycle d'animation via le ticker natif PixiJS (`animSprite.play()`)
- [x] Cleanup : arret et destruction des AnimatedSprite dans `_releaseSprite()` et `disconnect()`

## 99 — Transitions de zone (2026-03-28) ✅

> Fondu au noir lors des changements de carte et teleportations portail. Overlay PIXI.Graphics plein ecran avec animation alpha 0→1→0 via requestAnimationFrame.
- [x] Overlay noir plein ecran (PIXI.Graphics, zIndex 1000) cree opaque dans `_initPixi()`
- [x] Fade-in automatique apres chargement complet de la carte dans `connect()`
- [x] Fade-out sur teleportation portail (deja existant dans `_handlePortalTransition()`)
- [x] Fade-out avant navigation Turbo (listener `turbo:before-visit` avec `preventDefault` + reprise navigation)
- [x] Redimensionnement du fade overlay dans `_onResize()` + cleanup dans `disconnect()`

## 101 — Monitoring basique (2026-03-28) ✅

> Endpoints `/health` et `/metrics` (format Prometheus), listener de metriques HTTP, dashboard Grafana et alertes.
- [x] Endpoint `/health` verifiant BDD (latence), cache et hub Mercure
- [x] Service `MetricsCollector` : compteurs, jauges et histogrammes stockes en cache
- [x] Endpoint `/metrics` en format Prometheus (joueurs connectes, combats actifs, mobs vivants)
- [x] `RequestMetricsListener` : comptage requetes/s, temps de reponse, erreurs par code HTTP
- [x] Dashboard Grafana JSON (5 panels : req/s, latence p50/p95/p99, erreurs/min, joueurs, combats/mobs)
- [x] Regles d'alerte Prometheus (latence > 2s, erreurs > 5/min, health check KO)
- [x] Tests unitaires (HealthChecker, MetricsCollector, RequestMetricsListener)

## GCC-16 — Notifications Mercure influence (2026-03-28) ✅

> Notifications temps reel Mercure pour le systeme d'influence des guildes : gains de points batches, alertes de depassement, annonces de controle de ville.
- [x] `InfluenceMercurePublisher` : publication batchee (1/5min) via cache PSR-6, detection de depassement au classement
- [x] Topic `guild/influence/{guildId}` : notifications de points accumules avec details (activite, region, joueur)
- [x] Alerte `influence_overtake` : notification a la guilde depassee quand une rivale prend la tete
- [x] Annonce globale `guild/city_control` : diffusion des changements de controle en fin de saison
- [x] Controller Stimulus `influence_notification_controller.js` : souscription Mercure + toasts
- [x] Integration dans le layout de jeu (`game.html.twig`) avec ID de guilde via extension Twig
- [x] Tests existants adaptes au nouveau retour enrichi de `InfluenceManager::awardInfluence()`

## GCC-18 — Defis hebdomadaires — UI & notifications (2026-03-28) ✅

> Page dediee aux defis hebdomadaires de guilde avec barres de progression, historique des defis termines et notifications toast Mercure a la completion.
- [x] Route `GET /game/guild/challenges` dans `GuildController` : recupere les defis de la saison active avec progression de la guilde
- [x] Template `challenges.html.twig` : defis actifs avec barre de progression coloree, defis termines/expires
- [x] Bouton "Defis hebdomadaires" dans la page guilde principale
- [x] Publication Mercure `challenge_completed` dans `InfluenceMercurePublisher` declenchee par `ChallengeTracker`
- [x] Handler `_onChallengeCompleted` dans `influence_notification_controller.js` : toast de succes

## 100 — Sons basiques (2026-03-28) ✅

> Systeme audio procedural via Web Audio API. Sons d'interface, combat et exploration generes sans fichiers audio externes.
- [x] Module `SoundManager.js` : synthese procedurale (oscillateurs, bruit blanc, enveloppes)
- [x] 25+ sons : interface (click, hover, open, close, error, success, notification), combat (hit, critical, miss, spell, heal, death, victory, defeat, flee, shield, status, boss_phase), exploration (step, harvest, dialog, level_up, quest_complete, item_pickup)
- [x] Controller Stimulus `sound_controller.js` : integration globale (toasts, clics UI, events custom)
- [x] Sons de combat integres dans `fight/index.html.twig` (hit, critical, miss, spell, death, victory, defeat, flee)
- [x] Bouton mute + slider volume dans la page parametres
- [x] Persistance localStorage (muted + volume)

## TST-04 — AbstractIntegrationTestCase (2026-03-31) ✅

> Classe de base pour les tests d'integration avec vraie DB. Fondation pour TST-05 a TST-08.
- [x] Classe `AbstractIntegrationTestCase` etendant `KernelTestCase` dans `tests/Integration/`
- [x] Verification fixtures une seule fois par classe (`setUpBeforeClass`)
- [x] Transaction wrapping : `beginTransaction` dans `setUp`, `rollBack` dans `tearDown` (isolation entre tests)
- [x] Helpers : `getPlayer()`, `getUser()`, `getMob()`, `getMap()`, `createFight()`, `getService()`
- [x] Test de verification `AbstractIntegrationTestCaseTest.php` : 7 tests (boot kernel, player, user, map, mob, fight + rollback, getService)

## TST-14 — Assertions metier dans le GameEngine (2026-04-01) ✅

> Ajout de LogicException et gardes defensives dans les services critiques du GameEngine pour prevenir les etats impossibles au runtime.
- [x] `PlayerMoveProcessor` : joueur en combat → LogicException (deplacement interdit) + garde dans les appelants (MapApiController, Map.php)
- [x] `MobActionHandler` : mob avec 0 HP → LogicException (ne peut pas agir)
- [x] `SpellApplicator` : degats calcules < 0 → forces a 0 (jamais negatif)
- [x] `StatusEffectManager` : duree restante < 0 → corrigee a 0 (expiration immediate)
- [x] `FightTurnResolver::getTimeline` : combat sans participants → LogicException
- [x] Tests unitaires `BusinessAssertionsTest.php` : 7 tests couvrant les 5 assertions

## TST-15 — GameStateValidator (2026-04-01) ✅

> Commande Symfony de diagnostic qui verifie la coherence de l'etat du jeu en base de donnees.
- [x] Service `GameStateValidator` dans `src/GameEngine/Debug/` avec 5 verifications SQL et option `--check`
- [x] Commande `app:game:validate` affichant un rapport structure (OK / anomalies)
- [x] Check `ghost_fights` : joueurs references a un combat inexistant ou termine
- [x] Check `fights_without_living_mobs` : combats actifs sans mobs vivants
- [x] Check `orphaned_player_items` : PlayerItem orphelins (item_id null ou reference cassee)
- [x] Check `stale_active_quests` : quetes actives deja completees (doublon PlayerQuest + PlayerQuestCompleted)
- [x] Check `players_out_of_bounds` : joueurs hors limites de leur carte (coordonnees vs bounds des areas)
- [x] Tests unitaires : `GameStateValidatorTest.php` (10 tests) + `GameStateValidateCommandTest.php` (3 tests)

## TST-05B — Tests integration status effects (2026-04-01) ✅

> Tests d'integration des effets de statut en combat avec vraie DB (sous-tache B de TST-05).
- [x] `testPoisonTicksDamagePerTurn` : poison applique sur mob → degats chaque tour → expiration apres duree
- [x] `testSilencePreventsSpellCasting` : silence → isCharacterSilenced true → expiration → isCharacterSilenced false
- [x] `testEffectRefreshResetsDuration` : appliquer poison → consommer 1 tour → reappliquer → duree reset (pas de stacking)

## TST-05C — Tests integration cas limites combat (2026-04-01) ✅

> Tests d'integration des cas limites de combat avec vraie DB (sous-tache C de TST-05, complete TST-05).
- [x] `testPlayerWithNoWeaponCanStillAttack` : attaque de base sans arme → degats appliques (baseDamage = 3 + variance, independant de l'arme)
- [x] `testFleeFromCombat` : fuite reussie → joueur libere, repositionne sur lastCoordinates, combat et mobs supprimes
- [x] `testPlayerDeathInCombat` : joueur meurt → respawn avec 50% vie max, diedAt null, combat nettoye

## TST-08 — Testsuite Integration dans la CI (2026-04-01) ✅

> Ajout du testsuite Integration a la commande PHPUnit dans le pipeline CI.
- [x] Commande PHPUnit mise a jour : `--testsuite Unit,Functional,Integration`
- [x] Les 7 tests d'integration (3 events + 4 combat) executes dans la CI

## TST-06 — Tests integration status effects complet (2026-04-01) ✅

> Tests d'integration complets StatusEffectManager + SpellApplicator + FightTurnResolver ensemble avec vraie DB.
- [x] Application effet via sort (SpellApplicator) → verification FightStatusEffect en DB
- [x] Tick degats brulure a chaque tour + expiration
- [x] Tick soin regeneration avec cap max life
- [x] Poison frequency-based (tick tous les N tours)
- [x] Berserk : stat modifiers (+50% dmg, -30% def) + isCharacterBerserk
- [x] Freeze : stat modifier (-50% speed) + isCharacterFrozen
- [x] Aggregation de plusieurs effets (berserk + burn = +25% dmg net)
- [x] Expiration et suppression des FightStatusEffect de la DB
- [x] clearAllEffects en fin de combat
- [x] Berserk boost degats via SpellApplicator (pipeline complet)
- [x] Burn reduit degats du porteur (-25%)

## TST-03 — Couverture de code dans la CI (2026-04-02) ✅

> Ajout de la generation et publication du rapport de couverture de code dans le pipeline CI.
- [x] PHPUnit lance avec `--coverage-clover coverage.xml --coverage-text`
- [x] Pourcentage de couverture affiche dans les logs CI (`--coverage-text`)
- [x] Rapport Clover XML uploade en artifact GitHub (retention 30 jours)

## 115 — Journal de bord joueur (2026-04-02) ✅

> Page `/game/journal` avec historique chronologique des evenements du joueur.
- [x] Entite `PlayerJournalEntry` avec types (combat, quete, craft, recolte, donjon, progression)
- [x] Repository avec pagination, filtrage par type et rotation (max 200 entrees)
- [x] Controller `JournalController` avec page `/game/journal`
- [x] Template avec filtres par type, icones colorees, pagination
- [x] `JournalListener` : ecoute MobDead, PlayerDead, QuestCompleted, Craft, SpotHarvest, DungeonCompleted
- [x] Lien dans la navigation desktop et mobile
- [x] Migration PostgreSQL

## 114 — Centre de notifications in-game (2026-04-02) ✅

> Panel de notifications avec cloche, badge non-lues, persistance DB et push Mercure SSE.
- [x] Entite `PlayerNotification` modernisee (title, type string, icon, link, readAt)
- [x] Repository avec findRecent, countUnread, markAllAsRead, pruneOld (max 50)
- [x] `NotificationService` : creation + publication Mercure SSE temps reel
- [x] Controller `/game/notifications` (page complete, panel dropdown, API unread-count, mark-read)
- [x] Stimulus controller `notification_center` avec Mercure SSE et badge temps reel
- [x] Cloche de notification dans la navbar desktop (dropdown) et mobile (lien)
- [x] `NotificationEventSubscriber` : quete completee, succes debloque, mort du joueur
- [x] Evenement `AchievementCompletedEvent` dispatche depuis `AchievementTracker`
- [x] Twig extension `notification_unread_count()` pour le badge global
- [x] Migration PostgreSQL (upgrade table existante)

## 120 — Profil public joueur (2026-04-03) ✅

> Page profil enrichie avec equipement visible, succes en vedette (showcase top 5), et liens depuis la carte.
- [x] Equipement visible sur le profil (items equipes avec rarete et icones par slot)
- [x] Systeme de succes en vedette : champ `featured` sur PlayerAchievement, toggle via API, max 5
- [x] Stimulus controller `profile_achievement_toggle` pour gerer le toggle cote client
- [x] Lien "Voir le profil" depuis la carte PixiJS (tooltip desktop cliquable, banner mobile)
- [x] Migration PostgreSQL (ajout colonne `featured` a `player_achievements`)
- [x] Tests fonctionnels (show profil, toggle featured, limite max)

## 144 — Sorts & materia tier 2-3 (partiel) (2026-04-08) 🔧

> Ajout de materia épiques tier 3 (1 par élément) et comblement du déficit de materia pour Eau, Métal et Bête.
- [x] 8 nouveaux sorts tier 3 conçus pour materia (1 par élément : Feu, Eau, Air, Terre, Métal, Bête, Lumière, Ombre)
- [x] 8 materia épiques tier 3 (Epic, level 3) — une par élément avec sorts dédiés
- [x] 6 materia supplémentaires pour éléments sous-représentés (Eau ×2, Métal ×2, Bête ×2) utilisant des sorts existants
- [x] Couverture complète des 8 éléments en materia tier 2-3

## 144 — Sorts & materia tier 2-3 (partiel) (2026-04-09) 🔧

> 16 sorts tier 2-4 ajoutés (2 par élément) : zone/AoE, soins avancés, contrôle. Introduction des effets freeze, silence et berserk dans le système de sorts.
- [x] 7 sorts de zone AoE : inferno-wave (Feu), tempest-fury (Air), fissure (Terre), blade-storm (Métal), stampede (Bête), holy-nova (Lumière), void-eruption (Ombre)
- [x] 6 sorts de soin avancés : ember-ward (Feu+bouclier), purifying-spring (Eau+régén), breath-of-wind (Air), stonewall (Terre+bouclier fort), wild-regeneration (Bête+régén forte), holy-nova (Lumière hybride dégâts+soin)
- [x] 4 sorts de contrôle : glacial-prison (Eau, freeze), metal-silence (Métal, silence), purge (Lumière, silence), abyssal-fury (Ombre, berserk)
- [x] 3 effets de statut nouvellement utilisés : freeze (ralentissement), silence (blocage sorts), berserk (fureur +dégâts/-défense)
- [x] Eau portée à 20 sorts (comblage du déficit, était à 18)

## ~~142 — Armes variées par tier~~ ✅

> 15 armes ajoutées (5 types × 3 tiers) avec prérequis de compétences pour les tiers 2-3.
- [x] 5 types d'armes : hache (berserker), bâton (paladin), arc (archer), dague (assassin), lance (knight)
- [x] Tier 1 (common, lvl 1) : hachette rouillée, bâton de novice, arc court, dague ébréchée, pique en bois
- [x] Tier 2 (uncommon, lvl 5) : hache de guerre, bâton de cristal, arc long composite, dague de mithril, lance d'acier
- [x] Tier 3 (epic, lvl 15) : hache du berserker, bâton de l'archimage, arc du vent hurlant, lame de l'ombre, lance du chevalier céleste
- [x] Profils de stats distincts par type : damage (hache), magic_boost (bâton), precision_boost (arc), critical_boost (dague), damage+range (lance)
- [x] Raretés, prix et slots materia progressifs par tier
- [x] Descriptions thématiques en français
- [x] 12 skills « Maitrise des armes » (T2 + T3 par domaine : soldier, berserker, paladin, archer, assassin, knight)
- [x] Prérequis sur 18 armes T2/T3 (5 génériques + 4 épées élémentaires par tier)
- [x] Vérification des prérequis dans `EquipItemController` (blocage serveur + feedback visuel cadenas)
- [x] 5 tests unitaires `PlayerItemHelperCanEquipTest`

## 141 — Monstres tier 2-3 & boss manquants (2026-04-09) ✅

> Peuplement du Marais Brumeux (map_5) — zone lvl 8-18 avec 16 mobs répartis par difficulté progressive.
- [x] 3 mobs faciles en lisière nord : zombie, spider, venom_snake
- [x] 5 mobs intermédiaires dans les chemins marécageux : ochu ×2, mushroom_golem, undine, spider
- [x] 2 mobs avancés dans les eaux profondes sud : naga, corrupted_archdruid
- [x] 5 mobs nocturnes répartis : ghost, specter, banshee, will_o_wisp, creeping_shadow
- [x] 1 boss de zone : swamp_hydra (profondeurs sud-est)
- [x] Difficulté progressive nord→sud cohérente avec les PNJ existants (Morwen, Fergus, Bran)

> Patterns IA avancés : 3 boss de zone promus en vrais boss avec bossPhases, invocations et stats renforcées.
- [x] alpha_wolf (Forêt) : isBoss, 150 HP, 3 phases, invocation de loups, sort primordial_roar
- [x] will_o_wisp (Marais) : isBoss, 120 HP, 3 phases, self-heal, sorts light_blessing + holy_nova
- [x] creeping_shadow (Lande) : isBoss, 180 HP, 3 phases, invocation de fantômes, sort dark_ritual

## 146 — PNJ & dialogues par zone — sous-phase 1 (2026-04-10)

> 5 PNJ ajoutés dans 2 zones (Crête de Ventombre et Mines profondes) pour atteindre 5 PNJ/zone.
- [x] Crête de Ventombre (map_6) : 3 PNJ ajoutés (Tormund forgeron, Ysolde guérisseuse, Kaelen éclaireur)
- [x] Mines profondes (map_4) : 2 PNJ ajoutés (Agna guérisseuse, Durgan prospecteur)
- [x] 3 marchands avec stocks limités et horaires (Tormund, Ysolde, Agna)
- [x] Dialogues ramifiés avec conditions domain_xp_min (herboristerie pour Ysolde, minage pour Agna)
- [x] Lore contextuel : synergies élémentaires (Kaelen), cristaux résonnants (Durgan), forge volcanique (Tormund)

## 145 — Recettes craft manquantes — sous-phase Forgeron (2026-04-10)

> 10 recettes forgeron supplémentaires pour armes T2-T3 et armures métal.
- [x] 4 recettes armures fer T2 : plastron, jambières, bottes, gantelets (level 2-3, ingrédients fer + bronze)
- [x] 6 recettes armures mithril T3 : heaume, cuirasse, grèves, solerets, gantelets, épaulières (level 5-6, ingrédients mithril + platine)
- [x] Dépendance Game\ItemFixtures ajoutée dans RecipeFixtures pour les items YAML

## 145 — Recettes craft manquantes — sous-phase Tanneur (2026-04-10)

> 12 recettes tanneur ajoutées (total 15 recettes tanneur), 7 nouveaux items cuir T2/T3, 1 equipment set.
- [x] 5 recettes T1 : lanière de cuir (intermédiaire), gants, ceinture, épaulières, jambières
- [x] 6 recettes T2 cuir renforcé : plastron, bottes, gants, ceinture, épaulières, jambières (level 3-4, ingrédients cuir épais + lanières + os/crocs)
- [x] 1 recette T3 cuir exotique : plastron en fourrure de loup-garou (level 5)
- [x] 7 items cuir renforcé/exotique dans gear_item.yaml (uncommon T2 + rare T3)
- [x] Equipment set "Traqueur" (6 pièces cuir renforcé, bonus hit/critique/vie)

## 145 — Recettes craft manquantes — sous-phase Joaillier (2026-04-10)

> 15 recettes joaillier ajoutées (16 total avec copper_ring existant), 15 nouveaux items (5 gemmes intermédiaires + 10 bijoux). Progression de niveau 1 à 10, alignée sur le skill tree joaillier (15 skills).
- [x] 4 recettes T1 (level 1-2) : taille gemme brute, anneau de fer, amulette de fer, bracelet de fer
- [x] 4 recettes T2 (level 3-4) : taille gemme fine, anneau d'or serti, amulette d'or, couronne d'or
- [x] 4 recettes T3 (level 5-6) : taille gemme rare, enchantement de gemme, anneau de mithril serti, amulette de mithril
- [x] 1 recette T4 (level 8) : gemme prismatique (astrétal + gemmes enchantées)
- [x] 2 recettes T5 (level 10) : anneau prismatique, amulette prismatique (orichalque + gemme prismatique)
- [x] 5 items intermédiaires (crafted) : gemme taillée, gemme fine, gemme rare, gemme enchantée, gemme prismatique
- [x] 10 items bijoux (gear) : iron_ring, iron_amulet, iron_bracelet, gold_ring, gold_amulet, gold_crown, mithril_ring_jewel, mithril_amulet, legendary_ring, legendary_amulet
- [x] Tous les recipe slugs alignés avec les 15 skills du skill tree joaillier (SkillFixtures)

---

## Sprint 5 — Hotel des ventes

### 116 — Hotel des ventes — entites & backend (2026-04-12) ✅

> Systeme complet d'hotel des ventes : entites, service metier, taxe de mise en vente (5%) + taxe regionale, commande CRON d'expiration, migration PostgreSQL, tests unitaires.
- [x] Enum `AuctionStatus` (active, sold, expired, cancelled)
- [x] Entite `AuctionListing` : seller, playerItem, quantity, pricePerUnit, listingFee, status, expiresAt, regionTaxRate
- [x] Entite `AuctionTransaction` : listing, buyer, totalPrice, regionTaxAmount, purchasedAt
- [x] `AuctionManager` : createListing (taxe 5%, retrait item inventaire), buyListing (transfert item + gils, taxe regionale), cancelListing (retour item), expireListings (batch)
- [x] Commande CRON `app:auction:expire` avec mode loop et dry-run
- [x] Migration `Version20260412AuctionHouse` (tables auction_listing, auction_transaction, index)
- [x] 9 tests unitaires `AuctionManagerTest` (creation, achat, annulation, taxes, validations)

### 117 — Hotel des ventes — UI & recherche (2026-04-12) ✅

> Interface complete de l'hotel des ventes : navigation, recherche, filtres, mise en vente, achat, annulation, historique.
- [x] `AuctionListingRepository` : recherche paginee avec filtres (nom, type, rarete), listing par vendeur
- [x] `AuctionController` : 5 routes (index, my-listings, sell GET/POST, buy, cancel) avec CSRF et flash messages
- [x] Page `/game/auction` : liste paginee avec filtres (type, rarete, recherche texte), pagination, bouton acheter avec confirmation
- [x] Page `/game/auction/my-listings` : annonces actives avec annulation, historique avec statuts, revenus totaux
- [x] Page `/game/auction/sell` : selection d'item, saisie du prix, preview des frais (5%), validation
- [x] Lien "Hotel des ventes" dans la navigation desktop (dropdown Personnage) et mobile (drawer)

### 118 — Hotel des ventes — anti-exploit & economie (2026-04-12) ✅

> Protections anti-exploit pour l'hotel des ventes : prix min/max par rarete, limite d'annonces actives, cooldown d'annulation, logs de transactions et dashboard admin.
- [x] Prix min/max par rarete d'item (common: 1-10K, uncommon: 5-50K, rare: 50-500K, epic: 200-2M, legendary: 1K-10M, amethyst: 5K-50M) pour empecher les transferts deguises
- [x] Limite de 20 annonces actives simultanees par joueur
- [x] Cooldown de 5 min entre annulation d'annonce et creation d'une nouvelle
- [x] Champ `cancelledAt` sur `AuctionListing` + migration pour le tracking des annulations
- [x] Logs PSR structurees sur toutes les operations (creation, achat, annulation) avec details (seller/buyer, prix, taxes)
- [x] Dashboard admin `/admin/auction` : metriques cles (annonces totales, actives, ventes, volume, prix moyen), top 10 items vendus
- [x] Lien "Hotel des ventes" dans la sidebar admin
- [x] 8 tests unitaires (prix hors limites, limite atteinte, cooldown actif/expire, prix dans les bornes, cancelledAt set)
- [x] PHPStan niveau 6 et PHP-CS-Fixer propres

## Sprint 6 — Social & Economie

### 124 — Taxes dynamiques & tresor regional (2026-04-12) ✅

> Taux de taxe ajustable par la guilde controlante, transfert effectif des taxes vers le tresor de guilde, et investissements automatiques (buffs de zone) bases sur le niveau du tresor.
- [x] Transfert de la taxe regionale vers le tresor de la guilde controlante lors des achats a l'hotel des ventes (`AuctionManager.transferTaxToGuildTreasury`)
- [x] Route `POST /game/guild/tax/{regionSlug}` : taux ajustable par le chef/officier entre 1% et 10%, avec validations (controle de region, rang)
- [x] Affichage du taux de taxe actuel par region et formulaire d'ajustement (select + bouton) dans la page ameliorations
- [x] Systeme d'auto-buffs de zone bases sur le tresor : Benediction regionale (5K, +3% XP), Prosperite (15K, +5% recolte), Fortification (30K, +3% defense)
- [x] Indicateurs visuels (vert = actif, gris = inactif) pour chaque seuil d'auto-buff avec montant requis

### 125 — Gold sinks avances (2026-04-13) ✅

> Quatre mecaniques de depense de gils pour reguler l'economie du jeu.
- [x] Enchantement temporaire d'equipement : systeme deja fonctionnel via `EnchantmentManager` (gils + ingredients + duree temporaire)
- [x] Renommage d'items : champ `customName` sur `PlayerItem`, route POST `/game/services/rename/{id}`, cout 50 Gils, validation regex
- [x] Transport rapide payant : teleportation vers capitales de region, route POST `/game/services/travel`, cout 100 Gils, verifications (pas en combat/deplacement)
- [x] Reparation d'equipement : restauration de durabilite via POST `/game/services/repair/{id}`, cout base sur rarete ; degradation automatique de 10% sur defaite en combat
- [x] Page `/game/services` avec UI complete (transport, reparation, renommage) + liens navigation desktop et mobile
- [x] `GoldSinkManager` : service metier centralise avec toutes les formules de cout et validations
- [x] Tests unitaires : rename, repair, degradation, displayName (8 tests)

---

## Sprint 6 — Social & Economie

### 119 — Messagerie joueur a joueur (2026-04-13) ✅

> Systeme de messagerie privee (boite aux lettres in-game) entre joueurs, avec boite de reception, envoi, lu/non-lu, notifications Mercure SSE, limite de 100 messages, et blocage de joueur.
- [x] Entite `PrivateMessage` : sender, receiver, subject, body, readAt, createdAt + migration PostgreSQL
- [x] Champ `blocked_players` (JSON) sur `Player` avec methodes `blockPlayer()`, `unblockPlayer()`, `isPlayerBlocked()`
- [x] `PrivateMessageRepository` : inbox, sent, unread count, enforce limit (100 messages max par joueur)
- [x] `MessageController` : 6 actions (inbox, read, compose, send, delete, block/unblock) avec protection CSRF
- [x] Templates : boite de reception avec onglets (reception/envoyes), lecture de message avec reponse, formulaire de composition
- [x] `MessageExtension` Twig : fonction `message_unread_count()` avec cache pour badge navigation
- [x] Notification Mercure SSE sur topic `player/{id}/messages` a chaque nouveau message
- [x] Lien Messages dans le dropdown Social (desktop) et le drawer mobile avec icone enveloppe

### 121 — Systeme de reputation & karma (partiel, sous-phase 1) (2026-04-15) 🔧

> Fondations de la renommee globale du joueur (reputation joueur-monde, distincte des factions) : score cumulatif, 6 paliers de titres, progression via quetes et succes, affichage sur le profil public.
- [x] Enum `PlayerRenownTier` : 6 paliers (Novice 0, Connu 250, Respecte 1000, Honore 3000, Illustre 8000, Legendaire 20000) avec label francais, cssClass, `fromScore()`, `nextTier()`, `pointsToNextTier()`
- [x] Champ `renown_score` (integer, default 0) sur `Player` avec `getRenownScore`, `setRenownScore`, `addRenownScore` (borne a 0) + migration PostgreSQL `Version20260415PlayerRenownScore`
- [x] Service `PlayerRenownManager` : `addRenown` (flush + log changement de palier), `getTier`, `getPointsToNextTier`, barèmes par catégorie
- [x] `PlayerRenownListener` (EventSubscriber) : +5 pts par quete daily, +25 par quete narrative, +10 a 20 par succes selon la categorie (combat/quest/exploration/progression=20, craft/gathering/social=15, autres=10)
- [x] Affichage sur le profil public : palier avec classe CSS colorée, score actuel, points au palier suivant en tooltip
- [x] Tests unitaires : `PlayerRenownTierTest` (8 tests), `PlayerRenownManagerTest` (8 tests), `PlayerRenownListenerTest` (3 tests) — 19 tests au total
- [ ] Restant (sous-phase 4) : malus comportement negatif (report systeme)

### 121 — Systeme de reputation & karma (partiel, sous-phase 2) — Reductions marchand PNJ (2026-04-15) 🔧

> Bonus gameplay concret lie au palier de renommee : les marchands PNJ accordent une reduction croissante au joueur selon sa notoriete globale.
- [x] Methode `shopDiscount()` sur l'enum `PlayerRenownTier` : 0% (Novice), 2% (Connu), 4% (Respecte), 6% (Honore), 8% (Illustre), 10% (Legendaire)
- [x] Service `PlayerRenownDiscountProvider` : `getShopDiscount(Player)` et `combineDiscount(float, Player)` avec plafond combine (`MAX_COMBINED_DISCOUNT = 50%`) pour eviter le cumul abusif avec la reduction de guilde
- [x] `ShopController::index` : transmet `guildDiscount`, `renownDiscount`, `totalDiscount`, `renownTier` au template pour affichage
- [x] `ShopController::buy` : applique la reduction cumulee (guilde + renommee, plafond 50%) au cout total et enrichit le message avec le detail des reductions appliquees
- [x] Template `game/shop/index.html.twig` : banniere "Remise marchand" avec detail par source, prix barre si reduction, bouton/affordance recalcules sur le prix effectif
- [x] Tests unitaires `PlayerRenownDiscountProviderTest` (8 tests : bornes par palier, coherence enum, cumul, plafonnement, clamp negatif)
- [x] `ShopControllerTest::testBuyAppliesRenownDiscount` : verification end-to-end du prix applique et du message

### 121 — Systeme de reputation & karma (partiel, sous-phase 3) — Quetes gatees par la renommee (2026-04-15) 🔧

> Troisieme bonus lie a la renommee : certaines quetes speciales exigent un score de renommee minimum pour etre visibles et acceptees, offrant un contenu progressif reserve aux joueurs influents.
- [x] Champ `min_renown_score` (INTEGER, nullable) sur `Quest` + migration PostgreSQL `Version20260415QuestMinRenownScore` (`ADD COLUMN IF NOT EXISTS`)
- [x] Helpers sur `Quest` : `hasRenownRequirement()`, `isUnlockedForRenownScore(int)`, `getRequiredRenownTier(): ?PlayerRenownTier`
- [x] `PlayerQuestHelper::getAvailableQuests()` : filtre DQL `q.minRenownScore IS NULL OR q.minRenownScore <= :playerRenownScore` pour masquer les quetes verrouillees
- [x] `QuestController::accept()` : blocage avec message `Renommee insuffisante (X requis, vous avez Y)` si score insuffisant
- [x] Template `game/quest/index.html.twig` : badge ambre `Renommee : <palier>` avec la classe CSS du palier dans la liste des quetes disponibles
- [x] Tests unitaires `QuestRenownRequirementTest` (6 tests : defaut sans pre-requis, normalisation 0/negatif en null, blocage en dessous du seuil, palier correct pour seuil exact et score intermediaire)

### 121 — Systeme de reputation & karma (final, sous-phase 4) — Report systeme & malus renommee (2026-04-15) ✅

> Derniere sous-phase de la tache 121 : signalement basique entre joueurs avec moderation admin et malus de renommee a la validation. Cloture complete de la tache 121.
- [x] Enums `PlayerReportReason` (5 raisons : harcelement, spam, triche, contenu inapproprie, autre) et `PlayerReportStatus` (pending, accepted, rejected)
- [x] Entite `PlayerReport` (reporter, reportedPlayer, reason, description, status, renownMalusApplied, reviewedBy, reviewedAt) + migration PostgreSQL `Version20260415PlayerReport` avec FK CASCADE et 3 index
- [x] Repository `PlayerReportRepository` : `countRecentReports`, `findForAdmin`, `countForAdmin`
- [x] Service `PlayerReportManager` : `submitReport` (anti-self-report, cooldown 24h, max 1000 chars), `acceptReport` (applique malus -50 renommee via `PlayerRenownManager::addRenown`), `rejectReport`
- [x] Route `POST /game/player/{id}/report` (CSRF + flash messages) et formulaire de signalement integre au profil joueur (`templates/game/profile/show.html.twig`)
- [x] Controller admin `/admin/reports` (`PlayerReportController`, `ROLE_MODERATOR`) : liste filtree par statut, accept/reject avec `AdminLogger`
- [x] Template `templates/admin/report/index.html.twig` (table avec filtres pending/accepted/rejected/all + pagination) et lien sidebar admin
- [x] Tests unitaires `PlayerReportManagerTest` (9 tests : creation, anti-self-report, trim, validation longueur, cooldown, accept avec malus, idempotence, reject)

### 122 — Metiers specialises (partiel, sous-phase 1) — Fondations & bonus qualite (2026-04-15) 🔧

> Premiere sous-phase de la tache 122 : systeme de choix de specialisation irreversible (4 metiers) accompagne d'un bonus de chance d'amelioration de qualite au craft. Les recettes exclusives restent a faire en sous-phase 2.
- [x] Enum `CraftSpecialization` (4 cases : Forgeron, Tanneur, Alchimiste, Joaillier) avec `label()`, `craftSlug()`, `description()`
- [x] Champ `craft_specialization` (VARCHAR(20) nullable) sur `Player` + migration PostgreSQL `Version20260415PlayerCraftSpecialization` (`ADD COLUMN IF NOT EXISTS`) avec `getCraftSpecialization`, `setCraftSpecialization`, `hasCraftSpecialization`, `isSpecializedIn(string)`
- [x] Service `CraftSpecializationService` : `canChoose(Player)` (seuil `REQUIRED_DOMAIN_XP = 500` sur un domaine de craft, blocage si deja specialise), `choose(Player, spec)` (irreversible + flush), `getQualityBonusFor(Player, craft)` (+20% si la specialisation matche le craft), `getAvailableSpecializations()`
- [x] `QualityCalculator::calculateQuality(base, skillLevel, specializationBonus = 0)` integre le bonus (plafonne a 100% comme la chance de base)
- [x] `CraftingManager` passe `CraftSpecializationService::getQualityBonusFor($player, $recipe->getCraft())` au calculator lors du craft
- [x] Route `POST /game/craft/specialization` (CSRF `craft_specialization`) et bloc UI dans `templates/game/crafting/index.html.twig` : affichage du titre actuel (si specialise), boutons de choix avec confirmation irreversible (si eligible), ou message de seuil (si insuffisant)
- [x] Tests unitaires : `CraftSpecializationTest` (5 tests : cases, craft slug, label "Maitre", description, tryFrom), `CraftSpecializationServiceTest` (9 tests : list, canChoose seuil/above/already, choose succes/echec, bonus matching/mismatch/absent, XP domaine non-craft ignore), `QualityCalculatorTest` etendu (3 tests : bonus garantit upgrade, bonus 0 inchange, plafond legendary)

### 122 — Metiers specialises (sous-phase 2) — Recettes exclusives par specialisation (2026-04-15) ✅

> Seconde et derniere sous-phase de la tache 122 : les recettes exclusives sont desormais reservees aux maitres artisans correspondants. Quatre chefs-d'oeuvre de niveau 20 (un par specialisation) sont introduits comme recompense de specialisation.
- [x] Champ `required_specialization` (VARCHAR(20) nullable, `enumType: CraftSpecialization`) sur l'entite `Recipe` + migration PostgreSQL `Version20260415RecipeSpecializationExclusive` (`ADD COLUMN IF NOT EXISTS`) avec `getRequiredSpecialization`, `setRequiredSpecialization`, `isSpecializationExclusive`
- [x] `CraftingManager::getAvailableRecipes` et `getLockedRecipes` filtrent les recettes : une recette exclusive n'apparait dans les recettes disponibles que si la specialisation du joueur correspond, sinon elle bascule dans les verrouillees (raison `specialisation manquante` aux cotes du niveau)
- [x] Garde-fou serveur dans `CraftingManager::craft()` : refus avec message `Cette recette est reservee aux Maitres X.` si un joueur tente de fabriquer (URL directe) sans la specialisation requise
- [x] Quatre items Maitre tier 5 (rarete `Legendary`, niveau 20, 3-4 emplacements de materia) ajoutes dans `ItemFixtures` : `masterwork_blade` (arme, Forgeron), `masterwork_drakehide_cloak` (chest +12 protection, Tanneur), `masterwork_grand_elixir` (consommable, Alchimiste), `masterwork_starforged_ring` (anneau, Joaillier)
- [x] Quatre recettes exclusives dans `RecipeFixtures` (niveau 10, 200 XP recompense, ingredients tier 4+ : orichalque, adamantite, astretal, gemme prismatique, peau de drake) avec `required_specialization` mappee a l'enum `CraftSpecialization`
- [x] Templates `_recipe_card.html.twig` et `_recipe_card_locked.html.twig` : badge jaune `Maitre Forgeron/Tanneur/Alchimiste/Joaillier` affiche sur les cartes recettes exclusives (disponibles ou verrouillees)
- [x] Tests unitaires `CraftingManagerTest` etendus (5 tests) : recette exclusive masquee si pas de specialisation, visible si specialisation matche, masquee si mismatch, presente dans verrouillees si specialisation manque, refus de craft sans specialisation requise

### 123 — Encheres temporaires & ventes flash (partiel, sous-phase 1) — Type d'annonce "enchere" (2026-04-15) 🔧

> Premiere sous-phase de la tache 123 : ajout du type "enchere" a l'hotel des ventes, avec prix de depart, increment minimum et duree fixe (24h). Les Gils du bidder sont verrouilles en escrow et rembourses automatiquement lors d'une surenchere. A l'expiration avec bidder, l'enchere est finalisee (objet transfere au gagnant, Gils moins taxe au vendeur). Notifications Mercure et ventes flash admin restent a faire (sous-phases 2 et 3).
- [x] Enum `AuctionType` (2 cases : Fixed, Auction) avec `label()`
- [x] Extension `AuctionListing` : champs `type`, `minIncrement`, `currentBid`, `currentBidder` (Player nullable avec FK SET NULL) + helpers `isAuction()` et `getCurrentPrice()` + migration PostgreSQL `Version20260415AuctionBidding` (`ADD COLUMN IF NOT EXISTS`, index type + current_bidder_id)
- [x] `AuctionManager::createAuctionListing(seller, item, startingPrice, minIncrement, quantity)` : frais de mise en vente 5%, duree fixe `AUCTION_DURATION_HOURS = 24`, retrait de l'objet de l'inventaire
- [x] `AuctionManager::placeBid(bidder, listing, amount)` : escrow Gils, verification du minimum (`currentBid + minIncrement` ou `startingPrice * quantity`), remboursement auto du bidder precedent, blocage proprietaire/bidder courant/enchere expiree ou close
- [x] `AuctionManager::finalizeAuction(listing)` : transfert de l'objet au gagnant, transfert des Gils (moins taxe regionale) au vendeur, generation `AuctionTransaction` ; appelee automatiquement par `expireListings()` pour les encheres expirees avec bidder
- [x] `buyListing` rejette les encheres (message explicite), `cancelListing` rejette l'annulation si des mises sont en cours
- [x] UI : badge "Enchere" et formulaire de mise (input + bouton "Encherir") integres a la liste HdV ; option "Enchere" dans le formulaire de vente avec champ increment minimum et preview ajustee
- [x] Controller `AuctionController::bid` (route `POST /game/auction/bid/{id}`, CSRF `auction_bid_{id}`) et `sell` dispatche vers `createAuctionListing` si `listing_type=auction`
- [x] Tests unitaires `AuctionManagerTest` etendus (11 tests ajoutes) : creation enchere, mise valide, surenchere avec remboursement, increment minimum, mise initiale au startingPrice, proprietaire/plus offrant bloques, `buyListing` refuse sur enchere, finalisation avec/sans bidder, annulation bloquee avec mises en cours

### 123 — Encheres temporaires & ventes flash (partiel, sous-phase 2) — Notification aux encherisseurs depasses (2026-04-15) 🔧

> Deuxieme sous-phase de la tache 123 : lorsqu'un bidder est depasse par une nouvelle mise, il recoit une notification persistee (`PlayerNotification`) + Mercure SSE en temps reel. La notification precise le montant rembourse, le nom de l'objet et la nouvelle mise, et renvoie vers `/game/auction`. Reste la sous-phase 3 (ventes flash admin).
- [x] Injection de `NotificationService` dans `AuctionManager` (dependance obligatoire)
- [x] Methode privee `notifyOutbid(bidder, listing, refundedAmount, newBid)` appelee dans `placeBid` apres remboursement et flush, uniquement si un enchereur precedent existe
- [x] Notification de type `auction_outbid` avec icone `gavel`, titre "Enchere depassee" et lien `/game/auction` (format : "Votre mise de X Gils sur "Objet" a ete depassee (nouvelle mise : Y Gils). Vos Gils ont ete rembourses.")
- [x] Tests unitaires `AuctionManagerTest` etendus (2 tests ajoutes) : notification avec arguments exacts sur surenchere, aucune notification a la premiere mise ; mise a jour des 4 instanciations `new AuctionManager(...)` pour inclure le mock `NotificationService`

### 123 — Encheres temporaires & ventes flash (final, sous-phase 3) — Ventes flash admin (2026-04-15) ✅

> Troisieme et derniere sous-phase de la tache 123 : les administrateurs peuvent creer des "ventes flash" temporaires (items a prix reduit, duree courte 1 a 12 heures). Les ventes flash ignorent les frais de mise en vente, la limite d'annonces actives et les bornes de prix par rarete (prix libre). Clot la tache 123 **et** le Sprint 6 (6/6).
- [x] Enum `AuctionType::Flash` ajoute avec `label() = 'Vente flash'` (pas de migration necessaire : colonne `type VARCHAR(20)` deja presente depuis la sous-phase 1)
- [x] `AuctionListing::isFlash()` helper
- [x] `AuctionManager::createFlashSaleListing(admin, item, price, duration, qty)` : `listingFee=0`, duree `[FLASH_SALE_MIN_DURATION_HOURS=1, FLASH_SALE_MAX_DURATION_HOURS=12]` heures (defaut 2h), ignore `validatePriceLimits`/`validateActiveListingsLimit`/`validateCancelCooldown`, conserve la taxe regionale
- [x] `AuctionManager::cancelFlashSale(admin, listing)` : annulation admin sans cooldown, retour de l'objet au sac, garde-fous type/owner
- [x] `AuctionListingRepository::findActiveFlashSales()` pour alimenter le dashboard admin
- [x] `AuctionController` (admin) : routes `/admin/auction/flash/new` (GET + POST) et `/admin/auction/flash/{id}/cancel`, CSRF `admin_flash_sale` et `admin_flash_cancel_{id}`, selection depuis le sac du personnage admin
- [x] Templates : `admin/auction/flash_new.html.twig` (formulaire) + section "Ventes flash actives" dans `admin/auction/index.html.twig` (tableau + bouton "Nouvelle vente flash")
- [x] Badge "Flash" rose dans la liste HdV joueur (`game/auction/index.html.twig`) en plus du badge "Enchere" existant (placeBid/buyListing continuent de fonctionner : Flash != Auction, donc `buyListing` accepte)
- [x] Tests unitaires `AuctionManagerTest` etendus (8 tests ajoutes) : creation vente flash, ignore bornes de prix par rarete, ignore limite d'annonces actives, duree invalide rejettee, prix 0 rejette, annulation admin succes, annulation par non-proprietaire refusee, annulation sur listing non-flash refusee, `buyListing` accepte Flash (1000 Gils, 50 achat, seller admin recoit 50)

---

## Sprint 7 — Avatar: Fondations

### AVT-10 — Integrer AvatarTextureComposer.js (2026-04-15) ✅

> Integration dans le projet du blueprint `AvatarTextureComposer.js` issu du pack avatar (`data/amethyste-avatar-pack/assets/lib/avatar/`). Le composer empile les layers (body + outfit + hair + head + tints / alpha) sur une `PIXI.RenderTexture` pour produire une sprite sheet composite reutilisable par `SpriteAnimator`. Dormant tant que `AvatarAnimatorFactory` (AVT-12) ne l'utilise pas — aucune regression sur le pipeline legacy.
- [x] Fichier `assets/lib/avatar/AvatarTextureComposer.js` : classe ES module avec constructor `{ renderer }` (garde-fou si `renderer` manquant) et `compose({ baseTexture, layers })`
- [x] Compatibilite PixiJS v8 confirmee : `PIXI.RenderTexture.create({ width, height })`, `renderer.render({ container, target, clear })`, `container.destroy({ children: true })`
- [x] Support des proprietes optionnelles par layer (`tint`, `alpha`, `visible: false`)
- [x] Pas d'impact runtime : aucun import depuis le code existant, activation via AVT-12

### AVT-11 — Integrer AvatarSpriteSheetCache.js (2026-04-15) ✅

> Integration du cache LRU (128 entrees max) pour les textures composites d'avatar. La cle est un hash de composition genere cote backend (`AvatarHashGenerator`). Sur `set`/`get`, la cle est re-inseree en queue de `Map` pour preserver l'ordre MRU. En overflow, la texture la plus ancienne est detruite (`texture.destroy(true)`) et retiree.
- [x] Fichier `assets/lib/avatar/AvatarSpriteSheetCache.js` : classe ES module avec constructor `(maxEntries = 128)`, methodes `get`, `set`, `delete`, `clear`, getter `size`
- [x] Strategie LRU basee sur l'ordre d'insertion de `Map` (delete puis re-set pour rapprocher de la tete)
- [x] Nettoyage des textures remplacees / expulsees via `destroy(true)` (garde-fou si la texture ne possede pas la methode)
- [x] Pas d'impact runtime : consomme par `AvatarAnimatorFactory` (AVT-12) une fois cable

### AVT-01 — Inventorier les assets disponibles (2026-04-16) ✅

> Inventaire complet des assets personnages disponibles dans le projet, avec verification de la coherence de taille entre layers. Commande Symfony `app:avatar:inventory` creee pour automatiser l'audit (re-executable quand de nouveaux assets sont ajoutes). Document d'inventaire genere dans `docs/audits/AVATAR_ASSET_INVENTORY.md`.
- [x] Commande `app:avatar:inventory` : scanne 10 repertoires d'assets, classifie le format (single 3x4, multi 12x8, avatar 8x8), verifie la coherence des tailles, analyse l'ecart avec le systeme avatar 8x8
- [x] Inventaire : 248 fichiers scannes (69 Male, 91 Female, 28 Soldier, 47 Monster, 1 Boss, 6 Animal, 7 multi-sheets racine), tous en format RPG Maker VX (96x128, 32x32/frame)
- [x] Coherence : OK pour personnages (Male, Female, Soldier, Animal — tous 96x128), alertes sur monstres (6 tailles) et multi-sheets racine (3 tailles)
- [x] Constat avatar 8x8 : aucun asset au nouveau format disponible — les 4 repertoires `avatar/{body,hair,outfit,head}/` sont manquants, a creer quand le pack 8x8 sera acquis
- [x] Option `--export` pour generer automatiquement le rapport markdown

### AVT-02 — Documenter le layout exact du spritesheet (2026-04-16) ✅

> Specification complete du format de spritesheet avatar 8x8 : dimensions, grille, mapping des animations, composition multi-layers, ancrage sur la carte. Document de reference pour AVT-06 (SpriteAnimator type `avatar`) et la creation d'assets graphiques.
- [x] Taille totale : 512x512 px (base), taille par frame : 64x64 px — puissances de 2, optimal GPU
- [x] Mapping des animations : walk (rows 0-3) + stand/idle (rows 4-7) pour le sheet de base 8x8 ; animations etendues run/jump/push/pull en rows additionnelles (sheet extensible en hauteur)
- [x] Reference sheet annotee dans `docs/avatar-spritesheet-layout.md` : grille visuelle, cycle de marche 8 frames, convention de directions, code de reference JS pour `AVATAR_ANIMATIONS`
- [x] Specifications composition multi-layers : memes dimensions et layout pour chaque layer (body, outfit, hair, head_gear), z-order defini, support du tinting PixiJS
- [x] Comparaison legacy vs avatar, guide ancrage/scale sur tiles 32x32, specifications pour artistes

### AVT-05 — Mettre a jour ASSETS.md (2026-04-16) ✅

> Ajout de la section "Format avatar 8x8" dans ASSETS.md avec resume du layout documente en AVT-02. Correction des dimensions legacy erronees (72x128 → 96x128, 24x32 → 32x32). Mise a jour de la reference SpriteConfigProvider.
- [x] Section "Format avatar 8x8 (joueurs)" ajoutee : dimensions, grille, layers, lien vers la specification complete
- [x] Correction dimensions legacy : 72x128 → 96x128, 24x32 → 32x32 (confirmees par l'inventaire AVT-01)
- [x] Reference mise a jour : `MapApiController::getSpriteConfig()` → `SpriteConfigProvider`
- [x] Instructions ajout sprite separees : legacy (mob/PNJ) et avatar (joueur)

### AVT-06 — Ajouter le type `avatar` dans SpriteAnimator.js (2026-04-16) ✅

> Extension de `SpriteAnimator.js` pour supporter le nouveau format avatar 8x8 (64x64 par frame). Le type `avatar` coexiste avec les types legacy `single` et `multi` sans les affecter. Support complet de la grille 8 colonnes × N lignes avec detection automatique des animations disponibles selon la hauteur du spritesheet.
- [x] Constantes `AVATAR_ANIMATIONS` (walk, stand, run, jump, push, pull), `AVATAR_FRAME_SIZE` (64), `AVATAR_COLS` (8), `AVATAR_IDLE_FPS` (4) — mapping configurable exporte
- [x] `_computeFrameSize()` : branche avatar avec frame 64x64 fixe, 8 colonnes, totalRows auto-detecte depuis hauteur
- [x] `_buildFrames()` : construction de la grille complete totalRows × 8 colonnes pour avatar + appel `_detectAvailableAnimations()`
- [x] `_detectAvailableAnimations()` : detecte les animations disponibles (walk+stand pour 512px, +run pour 768px, etc.) selon la hauteur du spritesheet
- [x] `update()` : walk avatar utilise les 8 frames sequentiellement (vs pattern `[0,1,2,1]` legacy) ; idle avatar utilise l'animation stand a 4 FPS (pas de breathing programmatique)
- [x] `play()`, `stop()`, `setDirection()`, constructeur : gestion correcte du type avatar (stand/walk switching, frame initiale)
- [x] Getter `availableAnimations` expose pour AVT-07/AVT-12
- [x] `AVATAR_ANIMATIONS` exporte pour reutilisation en aval
- [x] Types legacy `single`/`multi` inchanges — aucune regression

### AVT-07 — Methode `setAnimation(name)` + animation courante (2026-04-16) ✅

> Ajout de la methode publique `setAnimation(name)` dans `SpriteAnimator.js` pour permettre le switch entre animations (walk, stand, run, jump, push, pull) sur les sprites de type `avatar`. Suppression du forcage `_currentAnimation = 'walk'` dans `play()` et `stop()` qui empechait toute persistance d'animation.
- [x] Methode `setAnimation(name)` : valide le type avatar, verifie la disponibilite de l'animation dans le spritesheet, reset l'animation si en cours de lecture, met a jour le sprite si a l'arret
- [x] Getter `currentAnimation` : expose le nom de l'animation active
- [x] `play()` : ne force plus `_currentAnimation = 'walk'` — respecte l'animation definie par `setAnimation()`
- [x] `stop()` : ne force plus `_currentAnimation = 'walk'` — l'animation persiste entre play/stop, l'idle reste sur `stand`
- [x] Animation par defaut : `walk` (compatibilite mouvement existant)

### AVT-08 — Adapter le positionnement dans le tile (2026-04-16) ✅

> Les frames avatar (64x64) sont 2x plus grandes que les tiles (32x32). Ajout d'un scale automatique dans le map controller pour que les sprites s'alignent correctement, et adaptation du positionnement emote pour utiliser les dimensions visuelles (apres scale).
- [x] Scale automatique `tileSize / frameWidth` applique dans `_createEntitySprite()` et `_createPlayerMarker()` quand la frame depasse la taille du tile
- [x] Ancrage centre-bas (0.5, 1) conserve — les pieds du personnage restent alignes sur le bas du tile
- [x] Positionnement emote ajuste pour utiliser les dimensions visuelles (`frameW * scale`, `frameH * scale`) au lieu des dimensions brutes
- [x] Types legacy (single/multi) non impactes — le scale n'est applique que si `frameWidth > tileSize`

### AVT-09 — Tests manuels : type avatar isole (2026-04-16) ✅

> Page de test admin (`/admin/avatar-test`) avec harness visuel PixiJS pour verifier le SpriteAnimator type `avatar` (8x8, 64x64) et la non-regression des types `single` et `multi`. Genere des spritesheets synthetiques via Canvas (labels animation/direction/frame), instancie les trois types cote a cote avec controles interactifs (animation, direction, play/stop), et execute une batterie de checks automatises (frame size, animations detectees, setAnimation, setDirection).
- [x] Controller `AvatarTestController` sur `/admin/avatar-test` avec lien sidebar
- [x] Generation synthetique de spritesheets : avatar 512x512 (8x8), single 96x128 (3x4), multi 384x256 (12x8)
- [x] Controles interactifs : switch animation (walk/stand), direction (4 dirs), play/stop pour chaque type
- [x] Batterie de 15+ checks automatises : frame dimensions, animations disponibles, setAnimation retour, non-regression single/multi
- [x] Types legacy (single/multi) testes en parallele : frame size 32x32, pas d'availableAnimations, setAnimation retourne false

### AVT-12 — Adapter AvatarAnimatorFactory.js (2026-04-16) ✅

> Adaptation du blueprint `AvatarAnimatorFactory.js` depuis `data/amethyste-avatar-pack/` vers `assets/lib/avatar/`. La factory fournit deux pipelines de creation de SpriteAnimator : legacy (spriteKey → type single/multi) pour les mobs et PNJ, et avatar (baseSheet + layers → texture composite → type avatar) pour les joueurs. Correction du type de sortie du blueprint original (`'single'` → `'avatar'`) pour exploiter le format 8x8 multi-animations.
- [x] `createFromAvatarPayload(avatarHash, avatarPayload)` compose les layers via `AvatarTextureComposer`, cache via `AvatarSpriteSheetCache`, retourne un `SpriteAnimator` avec `type: 'avatar'`
- [x] `createFromLegacySpriteKey(spriteKey)` identique au pipeline existant (type single/multi)
- [x] `invalidateAvatarHash(hash)` et `clear()` pour la gestion du cache
- [x] Deux pipelines coexistent : legacy pour mobs/PNJ, avatar pour joueurs — aucun impact sur le rendu existant

---

## Sprint 8 — Avatar: Backend & Carte

### AVT-13 — Ajouter les champs avatar sur Player (2026-04-16) ✅

> Ajout des 4 colonnes avatar sur l'entite Player : `avatarAppearance` (JSON nullable), `avatarHash` (string 64 nullable), `avatarVersion` (int default 1), `avatarUpdatedAt` (datetime_immutable nullable). Migration idempotente avec `ADD COLUMN IF NOT EXISTS`. Structure JSON documentee : `{ "body": "human_m_light", "hair": "short_01", "hairColor": "#d6b25e", "outfit": "starter_tunic" }`. Methode utilitaire `hasAvatar()` pour detecter si un joueur a un avatar configure. Le setter `setAvatarAppearance()` met a jour automatiquement `avatarUpdatedAt`.
- [x] 4 champs ORM sur Player : `avatarAppearance`, `avatarHash`, `avatarVersion`, `avatarUpdatedAt`
- [x] Migration `Version20260416PlayerAvatarAppearance` — ALTER TABLE idempotent + COMMENT pour datetime_immutable
- [x] Getters/setters + `hasAvatar()` utilitaire
- [x] Valeurs par defaut pour joueurs existants : NULL (apparence), 1 (version) — fallback legacy preserve
- [x] Tests unitaires `PlayerAvatarTest` : 8 cas couverts (defaults, setters, updatedAt, hasAvatar)

### AVT-14 — Integrer AvatarHashGenerator (2026-04-16) ✅

> Service PHP pur qui genere un hash SHA256 deterministe a partir de l'apparence du joueur et de ses layers visibles. Le hash sert de cle de cache pour le frontend (AvatarSpriteSheetCache) : meme apparence = meme hash = texture deja composee. Copie depuis le blueprint `data/amethyste-avatar-pack/`, enregistre automatiquement par l'autowiring Symfony.
- [x] Classe `App\Service\Avatar\AvatarHashGenerator` avec methode `generate(array $appearance, array $visibleLayers, string $formatVersion): string`
- [x] Hash deterministe : `ksort` sur appearance, `sort` sur layers, puis `hash('sha256', json_encode(...))`
- [x] Format version inclus dans le hash pour invalidation de cache lors de changements de format
- [x] Tests unitaires `AvatarHashGeneratorTest` : 8 cas couverts (SHA256 valide, determinisme, sensibilite aux changements, insensibilite a l'ordre des cles/layers, format version)

### AVT-16 — Ajouter `avatarSheet` sur Item (2026-04-16) ✅

> Champ `avatarSheet` (string 255, nullable) sur l'entite `Item` pour stocker le chemin vers le sprite sheet du layer visuel d'un item equipe. Permet au systeme avatar de savoir quel layer afficher quand le joueur porte un equipement (ex: `avatar/outfit/iron_armor.png`). Migration idempotente. Champ ajoute dans le formulaire admin.
- [x] Champ ORM `avatarSheet` (string 255, nullable) sur `Item`
- [x] Migration `Version20260416ItemAvatarSheet` — ALTER TABLE idempotent
- [x] Getter/setter `getAvatarSheet()` / `setAvatarSheet()`
- [x] Champ `avatarSheet` ajoute dans le formulaire admin `ItemType`

### AVT-15 — Integrer PlayerAvatarPayloadBuilder (2026-04-16) ✅

> Service PHP qui construit le payload avatar complet pour un joueur : extrait l'apparence depuis `Player::getAvatarAppearance()`, compose les layers visibles (gear equipe via `GearHelper`, cheveux, barbe, marques faciales) avec l'ordre de rendu correct, et genere le hash deterministe via `AvatarHashGenerator`. Retourne `null` si le joueur n'a pas d'avatar (fallback legacy).
- [x] `extractAppearance()` lit les vrais champs `Player::getAvatarAppearance()`
- [x] `buildVisibleLayers()` compose les layers dans l'ordre : gear (body) → hair → beard → faceMark → head gear
- [x] Integration `GearHelper::getEquippedGearByLocation()` + `Item::getAvatarSheet()` pour les layers d'equipement
- [x] Conversion `hairColor`/`beardColor` hex → int tint pour le rendu PixiJS
- [x] Tests unitaires `PlayerAvatarPayloadBuilderTest` : 7 cas couverts (no avatar, appearance, gear, gear sans sheet, ordre layers, beard/faceMark, determinisme hash)

### AVT-17 — Enrichir `/api/map/entities` avec avatar (2026-04-17) ✅

> L'endpoint `/api/map/entities` sert desormais les donnees avatar pour chaque joueur sur la carte. Les joueurs avec un avatar configuré recoivent `renderMode: 'avatar'`, `avatarHash` et le payload `avatar` (baseSheet + layers d'apparence). Les joueurs sans avatar gardent `renderMode: 'legacy'` avec `spriteKey` en fallback.
- [x] Injection de `PlayerAvatarPayloadBuilder` dans `MapApiController`
- [x] Ajout de `renderMode` sur chaque entite joueur (`'avatar'` ou `'legacy'`)
- [x] Ajout de `avatarHash` et `avatar` (baseSheet + layers) pour les joueurs avec avatar
- [x] Conservation de `spriteKey: 'player_default'` en fallback pour le pipeline legacy
- [x] Methode `buildForMapEntity()` : payload d'apparence sans gear (evite la dependance session GearHelper)
- [x] Tests unitaires : 4 nouveaux cas (no avatar, layers sans gear, pas d'appel GearHelper, determinisme hash)

### AVT-18 — Enrichir `/api/map/config` avec avatarCatalog (2026-04-17) ✅

> L'endpoint `/api/map/config` expose desormais un `avatarCatalog` structuré par catégorie (body, hair, beard, facemark, gear) listant toutes les sheets avatar disponibles pour le préchargement côté client. Le catalogue scanne dynamiquement le répertoire `assets/styles/images/avatar/` et requête les items avec `avatarSheet` en base.
- [x] Service `AvatarCatalogProvider` : scan filesystem par catégorie + requête items avec avatarSheet
- [x] Méthode `getAllSheetUrls()` : liste aplatie de toutes les URLs pour préchargement PixiJS
- [x] Injection dans `MapApiController::config()` et ajout de `avatarCatalog` à la réponse JSON
- [x] Tests unitaires `AvatarCatalogProviderTest` : 5 cas (catégories, scan body, répertoires manquants, gear sheets, flatten URLs)

### AVT-19 — Instancier AvatarAnimatorFactory dans le map controller (2026-04-17) ✅

> Le controller PixiJS de la carte instancie desormais `AvatarAnimatorFactory` après le chargement des textures. Les sheets avatar du catalogue (body, hair, beard, facemark, gear) sont préchargées au même titre que les sprites legacy. La factory est nettoyée proprement lors du `disconnect()`.
- [x] Import et instanciation de `AvatarAnimatorFactory` dans `map_pixi_controller.js`
- [x] Préchargement des sheets avatar depuis `avatarCatalog` (config API)
- [x] Nettoyage du cache avatar dans `disconnect()`

### AVT-20 — Dispatch avatar/legacy via `_createAnimatorForEntity()` (2026-04-17) ✅

> Le controller PixiJS dispatche desormais la creation d'animator entre le pipeline avatar (joueurs avec `renderMode === 'avatar'` + `avatar` payload + `avatarHash`) et le pipeline legacy (mobs, PNJ, joueurs sans avatar). `_createEntitySprite` recoit l'entite complete au lieu du seul `spriteKey`, ce qui permet d'inspecter `renderMode` et de choisir la branche appropriee. Les mobs et PNJ conservent strictement le chemin legacy.
- [x] Nouvelle methode `_createAnimatorForEntity(entity)` : dispatch renderMode avatar vs legacy
- [x] `_createEntitySprite(type, entity, label, meta)` : signature simplifiee, prend l'entite complete
- [x] `_loadEntities` passe l'entite brute (avec renderMode, avatar, avatarHash) au lieu de champs individuels
- [x] Fallback legacy automatique si la composition avatar echoue (factory absente, payload invalide)

### AVT-21 — Gerer le joueur local (self) via le pipeline avatar (2026-04-17) ✅

> Le joueur courant (self) utilise desormais le meme pipeline de rendu que les autres joueurs. `_loadEntities` capture l'entite self (au lieu de la skipper simplement) et la transmet a `_createPlayerMarker(selfEntity)`, qui delegue au dispatcher `_createAnimatorForEntity` pour choisir avatar ou legacy selon `renderMode`. La detection du changement d'equipement (nouveau `avatarHash`) invalide l'ancienne entree LRU du cache de texture composite, evitant que la version stale reste en memoire. Si aucune entite self n'est fournie (init initial), le fallback `player_default` reste en place.
- [x] `_loadEntities` capture le `selfEntity` et le passe a `_createPlayerMarker`
- [x] `_createPlayerMarker(selfEntity = null)` appelle `_createAnimatorForEntity(selfEntity)` si l'entite est fournie, sinon fallback legacy `player_default`
- [x] Suivi de `_selfAvatarHash` entre reloads et appel `AvatarAnimatorFactory.invalidateAvatarHash(oldHash)` a chaque changement d'equipement detecte
- [x] Reinitialisation de `_selfAvatarHash` dans `disconnect()`

### AVT-22 — Tests integration carte avatar/legacy (2026-04-17) ✅

> Tests d'integration `WebTestCase` qui verifient le payload JSON de `/api/map/entities` : joueur avec avatar → `renderMode=avatar` + `avatarHash` + `avatar.baseSheet` + layers ; joueur sans avatar → `renderMode=legacy` + `spriteKey=player_default` ; mobs et PNJ → pipeline legacy strict (spriteKey seul, aucun champ avatar). Cloture le Sprint 8 (Avatar : Backend & Carte).
- [x] `tests/Functional/Controller/Game/MapApiEntitiesTest.php` : 4 cas couverts (legacy player, avatar player, mobs legacy, PNJ legacy)
- [x] Isolation manuelle : restauration de l'avatar d'origine en `tearDown()` (pas de transaction auto)
- [x] Skip gracieux si fixtures absentes ou rayon sans mobs/PNJ
- [x] Validation rendu visuel (taille, z-order, emotes) : verification manuelle in-game (non testable en PHPUnit, relève du pipeline PixiJS)

### AVT-03 — Organiser les assets dans le projet (2026-04-17) ✅

> Creation de la structure d'accueil des sheets avatar 8x8 sous `assets/styles/images/avatar/{body,hair,outfit,head}/` avec `.gitkeep` pour le tracking git, et README documentant le format requis (512x512, 8x8, RGBA), la convention de nommage `{categorie}_{style}_{variante}.png`, le z-order de composition (body → outfit → hair → head_gear) et les regles d'alignement pixel-perfect. Debloque AVT-23 (formulaire creation), AVT-27 (avatarSheet sur items) et permet de demarrer AVT-04 des qu'un asset reel est fourni.
- [x] Structure : `body/`, `hair/`, `outfit/`, `head/` avec `.gitkeep` dans chaque sous-dossier
- [x] README pointant vers `docs/avatar-spritesheet-layout.md` pour la specification complete
- [x] Convention de nommage et z-order documentes a la racine du repertoire

### 130 — Montures & deplacement rapide (partiel, sous-phase 1) — Entite Mount + catalogue (2026-04-19) 🔧

> Premier jalon de la tache 130 du Sprint 11 (Monde vivant). Pose les fondations data pour le systeme de montures : entite, table, enum de types d'obtention, et un petit catalogue de 4 montures couvrant les trois sources d'obtention principales (achat, quete, drop). Sous-phase non invasive : aucun changement de logique gameplay, aucun nouveau endpoint, aucun impact sur l'UI. Les sous-phases suivantes ajouteront l'equipement cote joueur (relation `Player` -> `Mount`), le bonus de vitesse en deplacement, l'animation sprite en monture et la teleportation rapide entre villes decouvertes.
- [x] Entite `App\Entity\Game\Mount` (table `game_mounts`) avec slug unique, description, sprite sheet + icone, speedBonus (defaut 50), obtentionType enum string (`quest`/`drop`/`purchase`/`achievement`), gilCost nullable, requiredLevel (defaut 1), flag `enabled`, timestamps via `TimestampableEntity`
- [x] Migration `Version20260419MountCatalog` : `CREATE TABLE IF NOT EXISTS game_mounts` + index unique slug + index enabled (idempotent pour environnement deja provisionne)
- [x] `MountFixtures` : 4 montures de base — Cheval brun (achat, 2500 gils, lvl 5, +50%), Loup sauvage (quete, lvl 15, +60%), Chocobo jaune (quete, lvl 30, +75%), Sanglier colossal (drop rare, lvl 20, +40%)
- [x] Tests unitaires `MountTest` : 11 cas couvrant defaults, contraintes (speedBonus >= 0, obtentionType whitelist, gilCost >= 0 ou null, requiredLevel >= 1), setters fluents, toggle `enabled`
- [ ] Obtention via quete, drop rare, ou achat — catalogue pret (champ `obtentionType`), reste a brancher aux systemes de quetes / loot / boutique
- [ ] Vitesse de deplacement +50% quand monte — sous-phase 2
- [ ] Animation sprite monte sur la carte — sous-phase 3
- [ ] Teleportation rapide entre villes decouvertes — sous-phase 4

### 132 — Classement saisonnier global (partiel, sous-phase 1) — Page `/game/rankings` + top killers (2026-04-20) 🔧

> Premier jalon de la tache 132 du Sprint 11 (Monde vivant). Pose les fondations du systeme de classement en livrant une page `/game/rankings` read-only qui affiche le top 50 des joueurs par nombre total de mobs tues (all-time, toutes saisons confondues). Sous-phase non invasive : aucun changement de schema, aucune nouvelle entite, exploite `PlayerBestiary.killCount` deja alimente par le pipeline combat/bestiaire. Les sous-phases suivantes ajouteront des criteres supplementaires (XP gagnee, quetes completees), la saisonnalite (liee a `InfluenceSeason`) et les recompenses de fin de saison.
- [x] `RankingController` (route `GET /game/rankings`, nom `app_game_rankings`) : injection `PlayerHelper` + `PlayerBestiaryRepository`, garde `ROLE_USER`, redirige vers `app_game` si aucun personnage, rend `game/ranking/index.html.twig` avec `topKillers`, `playerRank`, `playerTotalKills`, `topLimit=50`
- [x] `PlayerBestiaryRepository::findTopKillers(int $limit = 50)` : DQL `GROUP BY pb.player + SUM(pb.killCount) + HAVING > 0`, re-hydrate les entites `Player` via un `findBy(['id' => $ids])` pour eviter le N+1 en listant les noms
- [x] `PlayerBestiaryRepository::getPlayerKillRank(Player)` : calcule le rang 1-based en comptant les joueurs ayant un total de kills strictement superieur (via DQL + `\count` en PHP pour contourner la limitation `HAVING + COUNT(DISTINCT)`). Retourne `null` si le joueur n'a aucun kill.
- [x] Template `templates/game/ranking/index.html.twig` : tableau trie (rang / nom / kills) avec highlight du joueur courant (`bg-purple-900/30`), icones medailles top 3, note sur la limite du top 50, onglets preparatoires (un seul onglet "Eliminations" actif pour la v1)
- [x] Menu de navigation : lien ajoute dans le dropdown Social desktop (icone barres verticales) + dans le drawer mobile, `social_routes` etend `app_game_rankings`
- [x] Traductions FR/EN : nouveau bloc `game.ranking.*` (title, subtitle, tab.kills, col.rank, col.player, col.kills, your_rank, your_rank_none, your_kills, empty, top_limit_note, you) + entree `game.nav.rankings`
- [x] Tests `tests/Functional/Controller/Game/RankingControllerTest.php` (3 cas) : rendu nominal avec top + rang joueur, redirection `302` si aucun player, joueur non classe (`playerRank=null`, `topKillers=[]`) — pattern container/twig mock identique a `BestiaryControllerTest`
- [x] Diff total : ~280 lignes ajoutees (controller + repo + template + tests + traductions + navigation), 0 ligne supprimee
- [ ] Classement individuel par XP gagnee / quetes completees (sous-phase 2 : ajouter criteres et onglets dedies)
- [ ] Saisonnalite liee a `InfluenceSeason` (sous-phase 3 : reset/archivage periodique du classement)
- [ ] Recompenses de fin de saison : titres, cosmetiques, items exclusifs (sous-phase 4)

### 135 — Localisation i18n (partiel, sous-phase 1) — Selecteur de langue securise (2026-04-20) 🔧

> Premier jalon de la tache 135 du Sprint 12 (Technique & i18n). Cable et securise le selecteur de langue dans `/game/settings` qui etait jusque-la un simple `<select>` cosmetique (aucune action backend, liste hardcodee avec `es` non supporte). Whiteliste les locales valides au niveau Symfony (`framework.yaml:enabled_locales`), valide strictement `LocaleController::changeLocale` contre cette whitelist (fallback `default_locale` en cas de valeur inconnue, empeche le stockage de payloads exotiques en session), contraint la route par regex (`[a-zA-Z_]{2,10}`), limite les methodes HTTP a GET/POST, et verifie que le referer pointe vers le meme host avant de rediriger (neutralise les tentatives d'open-redirect). Cote template, un controller Stimulus `locale-switcher` redirige vers `/change-locale/{locale}` au changement de selection ; l'option `selected` reflete `app.request.locale` pour que la valeur affichee corresponde a la locale active. L'entree `es` (Espanol) est retiree faute de traductions. Sous-phases suivantes couvriront la traduction EN exhaustive, le contenu de jeu multilingue (items, sorts) et l'extraction XLIFF si besoin.
- [x] `config/packages/framework.yaml` : `enabled_locales: ['fr', 'en']` expose `%kernel.enabled_locales%` aux services
- [x] `src/Controller/LocaleController.php` (~50 lignes, reecrit) : `#[Autowire('%kernel.enabled_locales%')]` + `#[Autowire('%kernel.default_locale%')]`, validation `in_array($locale, $this->enabledLocales, true)` avec fallback defaut, regex `locale => '[a-zA-Z_]{2,10}'` sur la route, methodes limitees a `GET`/`POST`, helper prive `isSafeReferer()` parsant l'URL et comparant le host
- [x] `templates/game/settings.html.twig` : suppression de l'option `es`, ajout de `selected` conditionnel sur l'option courante via `app.request.locale`, wrapper `data-controller="locale-switcher"` avec value `url-template-value` calculee par `path('app_change_locale', {locale: '__LOCALE__'})`, action `change->locale-switcher#change`
- [x] Nouveau `assets/controllers/locale_switcher_controller.js` (~15 lignes) : Stimulus controller avec `urlTemplateValue` et methode `change(event)` qui remplace `__LOCALE__` (encodeURIComponent) et effectue `window.location.href = url`
- [x] Tests unitaires `tests/Functional/Controller/LocaleControllerTest.php` (6 cas) : locale valide persistee en session, locale invalide fallback defaut, payload malveillant (`../../../etc/passwd`) neutralise, referer `http://localhost/...` suivi, referer externe `https://evil.example.com/...` ignore -> home, referer absent -> home
- [x] Diff total : ~140 lignes ajoutees (controller + tests + template/js/yaml), 0 ligne supprimee hors refactor controller
- [ ] Traduction EN prioritaire exhaustive — couverture JSON existante a auditer
- [ ] Contenu de jeu multilingue (noms items, descriptions sorts) — necessitera un systeme de traduction dedie (non entame)

### 134 — Load testing & scaling (partiel, sous-phase 1) — Infrastructure k6 + scenario guest-browsing (2026-04-19) 🔧

> Premier jalon de la tache 134 du Sprint 12 (Technique & i18n). Met en place l'infrastructure k6 pour tester la charge du serveur FrankenPHP + Symfony + PostgreSQL. Sous-phase non invasive : uniquement des scripts dans `scripts/load-test/`, aucun changement de code applicatif ou de schema. Le scenario `guest-browsing` simule des visiteurs anonymes parcourant `/`, `/login`, `/register`, `/demo`, `/health` et `/metrics` — l'endpoint `/metrics` declenche plusieurs `COUNT()` Doctrine sur `player`, `fight` et `mob`, premier candidat probable pour les optimisations de la sous-phase 2 (identification goulots d'etranglement). Les scenarios authentifies (login + carte + combat) et l'integration CI nightly seront couverts par les sous-phases suivantes.
- [x] `scripts/load-test/config.js` : env vars centralisees (BASE_URL, VUS, DURATION, RAMP_UP, RAMP_DOWN, THINK_TIME_MIN/MAX), thresholds globaux (p95<800ms, p99<2s, <1% erreurs, >95% checks), helper `rampingOptions()` pour scenario "ramp-up -> plateau -> ramp-down"
- [x] `scripts/load-test/scenarios/guest-browsing.js` : scenario 6 endpoints groupes (`landing`, `auth-pages`, `demo`, `monitoring`), checks par endpoint (status, Content-Type, body non vide, body Prometheus), metriques custom `html_page_fail` + `metrics_endpoint_latency`, export JSON via `handleSummary` pour integration CI
- [x] `scripts/load-test/README.md` : installation k6 (macOS/Debian/Docker), documentation des env vars et thresholds, instructions pour lancer le test cible 200 VUs / 5 min, section "Interpretation des resultats" orientee Doctrine / Mercure / FrankenPHP, roadmap sous-phases suivantes
- [ ] Identification goulots d'etranglement (DB, Mercure, FrankenPHP) — sous-phase 2
- [ ] Optimisations : connection pooling, cache Redis, horizontal scaling plan — sous-phase 3
- [ ] Objectif 200 joueurs simultanes sans degradation — sous-phase finale

### AVT-37 — Cache IndexedDB pour les textures composites d'avatar (2026-04-19) ✅

> Quatrieme tache du Sprint 10 (Avatar : Polish & Animations). Persiste les textures composites d'avatar entre sessions via IndexedDB, en complement du cache LRU memoire (AvatarSpriteSheetCache, 128 entrees). Au retour d'un joueur, les avatars deja composes sont re-hydrates depuis le disque avant la premiere frame, evitant la recomposition GPU complete (body + outfit + hair + head) que chaque `map/entities` declenchait historiquement. Invalidation automatique a chaque changement d'equipement (le backend recalcule `avatarHash`, le client appelle `invalidateAvatarHash` qui supprime memoire + disque). Defensif : si IndexedDB est indisponible, stale ou en erreur, chaque appel retombe silencieusement sur la composition synchrone via `AvatarTextureComposer` — aucun chemin rendu critique ne bloque sur le disque.
- [x] Nouveau module `assets/lib/avatar/AvatarTexturePersistentCache.js` (~170 lignes) : wrapper IndexedDB avec `get(hash)` / `set(hash, blob)` / `delete(hash)` / `clear()` tous async non-throwing, garde `indexedDB === undefined`, store `avatar_textures` keye par `hash`, valeur `{hash, blob, updatedAt}`, TTL 30 jours (purge a la lecture)
- [x] `AvatarAnimatorFactory` etendu : nouveau parametre constructor `persistentCache`, `prefetchFromPersistentCache(hashes)` deduplique et hydrate en parallele, `_persistCompositeAsync(hash, texture)` post-composition (`renderer.extract.canvas()` -> `toBlob('image/png')` -> `persistentCache.set()` fire-and-forget), `_textureFromBlob(blob)` via `createImageBitmap` + `PIXI.Texture.from` avec `scaleMode = 'nearest'`, `invalidateAvatarHash` / `clear` propagent sur le cache persistant
- [x] `map_pixi_controller._ensureAvatarSheetsForEntities` collecte les `avatarHash` des joueurs visibles et appelle `prefetchFromPersistentCache(hashes)` en parallele du chargement des sheets (Promise.all) — le hit IndexDB ne retarde jamais le hit sheet-loader
- [x] Graceful degradation : navigateur sans IndexedDB, quota depasse, DB corrompue -> `_disabled = true` + toutes les methodes retournent `null`/`false` silencieusement ; la composition synchrone existante continue de fonctionner
- [x] Diff total : +170 lignes nouveau module, +95 / 0 lignes `AvatarAnimatorFactory.js`, +22 / 0 lignes `map_pixi_controller.js`

### AVT-36 — Lazy loading intelligent des sheets avatar (2026-04-18) ✅

> Troisieme tache du Sprint 10 (Avatar : Polish & Animations). Supprime le preload systematique du catalogue avatar au boot (`config.avatarCatalog` body + hair + beard + facemark + gear) qui chargeait plusieurs dizaines de sheets meme quand un seul joueur etait visible. Les sheets sont desormais recuperees a la demande par `AvatarSheetLoader` (nouveau module dans `assets/lib/avatar/`), avec deduplication des appels concurrents et reutilisation du cache texture partage (`_spriteTextures`). Le helper `_ensureAvatarSheetsForEntities(players)` est appele au debut de `_loadEntities` pour recolter les sheets de tous les joueurs visibles (baseSheet + layers[].sheet des avatars `renderMode === 'avatar'`) et attendre leur chargement en parallele avant la composition des textures. Les joueurs qui arrivent ulterieurement passent par `map/respawn -> _loadEntities`, qui reapplique la meme logique. Les sprites legacy (mobs, PNJ, joueurs fallback) restent pre-charges via `spriteConfig` — aucune regression sur les entites non-avatar.
- [x] Nouveau module `assets/lib/avatar/AvatarSheetLoader.js` (~120 lignes) : `ensureSheet(path)` / `ensurePayload(payload)` / `ensurePayloads(payloads)` avec Map de promesses en cours pour deduper
- [x] Hook `onTextureLoaded` dans le loader : applique `scaleMode = 'nearest'` des qu'une sheet arrive (coherent avec le preload existant)
- [x] Suppression du bloc de preload avatar catalog dans `_initPixi()` (21 lignes retirees)
- [x] `_ensureAvatarSheetsForEntities(players)` appele au debut de `_loadEntities` (avant les `_createEntitySprite` / `_createPlayerMarker`)
- [x] Diff total : +48 / -23 lignes dans `assets/controllers/map_pixi_controller.js`, +122 lignes nouveau module
- [x] Graceful degradation inchangee : un layer dont la sheet echoue au chargement est silencieusement ignore par `AvatarAnimatorFactory.createFromAvatarPayload` (comportement deja existant)

### AVT-31 — Animation Run en mode sprint (Shift) (2026-04-18) ✅

> Deuxieme tache du Sprint 10 (Avatar : Polish & Animations). Exploite l'animation `run` livree par AVT-06/AVT-07 pour offrir un mode "sprint" declenche cote client : maintenir la touche Shift pendant un deplacement fait passer l'avatar en animation `run` et reduit le step delay a 60 % (plancher 30 ms) le temps de parcourir la trajectoire. L'etat `_sprintActive` est suivi via les handlers `keydown`/`keyup` (+ reset sur `blur` pour couvrir la perte de focus) et est capture une seule fois au debut de `_animateAlongPath` pour garantir une vitesse coherente sur tout le chemin meme si Shift est relache en cours de route. A la fin du deplacement, l'animator repasse sur `walk` + `stop()` pour que le prochain mouvement reprenne le cycle normal. Aucun changement cote serveur : l'API `/api/map/move` valide les cibles comme avant, seule la duree du tween client varie. Les sprites legacy (mobs, PNJ, joueurs en mode fallback) ne sont pas impactes — `SpriteAnimator.setAnimation` retourne `false` en dehors du type `avatar` et le guard saute silencieusement.
- [x] `_sprintActive` + `_sprintSpeedFactor` initialises dans `connect()`
- [x] Handlers `_handleKeyUp` (Shift relache), listener `blur` (reset), desinscription dans `disconnect()`
- [x] `_handleKeyDown` detecte Shift meme pendant une animation (pour que le mouvement suivant prenne en compte le nouvel etat)
- [x] `_animateAlongPath` : capture `sprinting` au debut, `setAnimation('run')` si actif, tween reduit, restauration `setAnimation('walk')` + `stop()` a la fin
- [x] Aucun impact sur les sprites legacy (guard `typeof setAnimation === 'function'` + retour `false` pour les types `single`/`multi`)
- [x] Diff total ~60 lignes ajoutees dans `assets/controllers/map_pixi_controller.js`

### AVT-32 — Animation Jump sur teleportation et changement de zone (2026-04-18) ✅

> Premiere tache du Sprint 10 (Avatar : Polish & Animations). Exploite l'animation `jump` deja livree par AVT-06 et AVT-07 pour donner du feedback visuel pendant les transitions de carte. `_handlePortalTransition` declenche desormais `setAnimation('jump')` + `play()` sur le `_playerAnimator` du joueur local juste avant le fade-out (300 ms) du portail, puis revient sur `walk` + `stop()` apres le fade-in. Deux helpers internes (`_playPlayerAvatarAnimation(name)`, `_restorePlayerAvatarWalkAnimation()`) sont conçus pour rester sans effet sur les sprites legacy : `SpriteAnimator.setAnimation` retourne `false` quand le type n'est pas `avatar`, et le helper sort silencieusement. La sous-tache "traversee d'obstacle" est laissee non couverte : aucun systeme de franchissement d'obstacles n'existe actuellement (a re-considerer si AVT-33 push/pull introduit des objets interactifs).
- [x] `_playPlayerAvatarAnimation(name)` : guard sur l'existence de `setAnimation`, declenche `play()` si l'animation est disponible, retourne un booleen
- [x] `_restorePlayerAvatarWalkAnimation()` : revient a `walk` puis `stop()` (le prochain mouvement reprendra le cycle de marche correctement)
- [x] `_handlePortalTransition` : appel jump avant `_fadeTransition(true)`, appel restore apres `_fadeTransition(false)`
- [x] Aucun impact sur les sprites legacy (mobs, PNJ) ni sur les sessions sans rendu avatar (rendu fallback red circle)
- [x] Diff total ~30 lignes ajoutees dans `assets/controllers/map_pixi_controller.js` (fichier deja > 400 lignes, modifications confinees)

### AVT-24 — Preview avatar temps reel a la creation de personnage (2026-04-18) ✅

> Ferme la boucle visuelle entre le formulaire de creation (AVT-23) et le rendu avatar : un panneau d'apercu 64x64 pixelise (affiche 128x128 via CSS) compose en direct le corps, la tenue et la coiffure teintee des qu'une option change. Approche "fallback : images statiques pre-generees" retenue (pas de PixiJS requis cote client) : un `<canvas>` Stimulus est peuple a partir de la frame stand-down (col 0, row 0) de chaque spritesheet Mana Seed, dans l'ordre body -> outfit -> hair. Le tint de cheveux reproduit fidelement le pipeline PixiJS (`sprite.tint`) via `globalCompositeOperation = 'multiply'` puis `destination-in` pour preserver le canal alpha. Debloque la Definition of Done Sprint 9 (cote creation de personnage) et completea visuellement le travail d'AVT-25 / AVT-26 (persistance apparence, race -> body).
- [x] Nouveau `assets/controllers/character_creator_controller.js` (Stimulus) — ecoute les changements des radios `body[]` / `outfit[]` / `hair[]` / `hairColor[]` via un seul listener `change` sur le formulaire
- [x] Cache interne d'images (`Map<url, Image>`) : les sheets deja chargees ne sont pas re-telechargees, et le canvas est re-rendu des qu'une image termine son chargement (`onload` one-shot)
- [x] Composition canvas 64x64 avec `imageSmoothingEnabled = false` (rendu pixelise) ; une frame 64x64 (col 0, row 0) par layer
- [x] Tint cheveux pipeline deux passes : multiply color + mask via `destination-in` (preserve l'alpha de la sheet originale)
- [x] Template `game/character/create.html.twig` : panneau d'apercu avec canvas 128x128 (CSS), `image-rendering: pixelated`, `data-controller="character-creator"` pose sur le `<form>` via `form_start`
- [x] Aucune modification PHP requise (le `data-sheet` a ete expose par AVT-23) — diff total ~130 lignes

### AVT-23 — Champs d'apparence dans le formulaire de creation de personnage (2026-04-17) ✅

> Ajoute les choix d'apparence a l'ecran de creation de personnage : body (skin tone), hair, hairColor et outfit de depart. Les listes body/hair/outfit/head sont alimentees dynamiquement depuis les sheets Mana Seed deposees dans `assets/styles/images/avatar/` via la nouvelle methode `AvatarCatalogProvider::getCreationChoices()`. Le `CharacterCreateType` expose chaque choix en `ChoiceType` expanded + une palette de 6 couleurs hex pour `hairColor`, et chaque option porte un attribut HTML `data-sheet` pointant vers l'asset — prerequis consommable par le futur controleur de preview temps reel (AVT-24) et par la persistance `avatarAppearance` a la creation (AVT-25). Debloque AVT-24, AVT-25, AVT-26.
- [x] `AvatarCatalogProvider::getCreationChoices()` scanne les 4 categories (body, hair, outfit, head) via la routine existante `scanCategory()`, renvoie `{slug, sheet}` pour chaque entree
- [x] `CharacterCreateType` etendu : 4 champs `ChoiceType` expanded (body/hair/outfit/head) + palette `HAIR_COLORS` (Blond, Chatain, Brun, Noir, Roux, Argent) synchronisee avec le tint applique par `PlayerAvatarPayloadBuilder`
- [x] Attribut `data-sheet` propage via `choice_attr` sur chaque option pour permettre au futur `character_creator_controller.js` (AVT-24) de composer la preview sans requete supplementaire
- [x] Template `game/character/create.html.twig` : macro `avatar_choice_group` (grille responsive 3-4 cols, preview 12x12 pixelated, etat `peer-checked`) + bloc dedie pour la palette hairColor (pastilles colorees)
- [x] Tests unitaires `AvatarCatalogProviderTest` : couvre les 4 categories, le scan outfit/head avec deux variantes, la robustesse quand les repertoires sont vides

### AVT-29 — Publication Mercure `map/avatar` quand le hash change (2026-04-17) ✅

> Publie un event temps reel sur le topic `map/avatar` a chaque fois que l'avatar d'un joueur change effectivement (recalcul du hash). Nouveau service `App\GameEngine\Realtime\Avatar\AvatarUpdatedPublisher` injecte dans `AvatarHashRecalculator` : quand `recalculate()` detecte un nouveau hash, il persiste la valeur puis emet un Update Mercure contenant le `playerId`, le `mapId`, le nouveau `avatarHash`, le payload complet (`renderMode`, `avatar.baseSheet`, `avatar.layers`) et l'horodatage `avatarUpdatedAt`. Les clients subscrits (autres joueurs sur la carte) peuvent ainsi invalider leur cache de texture et recomposer le rendu du joueur en temps reel — integration client couverte par AVT-30.
- [x] Nouveau service `App\GameEngine\Realtime\Avatar\AvatarUpdatedPublisher` (topic `map/avatar`, type `avatar_updated`)
- [x] `AvatarHashRecalculator::recalculate()` appelle `publish()` uniquement quand le hash change, garantissant le no-op lors d'un recalcul equivalent
- [x] Payload base sur `PlayerAvatarPayloadBuilder::buildForMapEntity()` pour coherence avec `/api/map/entities`
- [x] Tests unitaires : `AvatarUpdatedPublisherTest` (null payload, publication complete) et `AvatarHashRecalculatorTest` etendu (expectations `publish` couvertes sur 4 scenarios)

### AVT-28 — Recalcul automatique du hash avatar sur changement d'equipement (2026-04-17) ✅

> Couple le cycle d'equipement joueur au pipeline avatar. Nouveau service `AvatarHashRecalculator` qui, via `PlayerAvatarPayloadBuilder`, recalcule le `avatarHash` du joueur apres chaque appel a `GearSetter::setGear` / `unsetGear`. La methode `Player::setAvatarHash` est rendue idempotente : elle ne touche `avatarUpdatedAt` que si la valeur change effectivement, ce qui permettra a un futur subscriber Mercure (AVT-29) de n'emettre que les vraies mises a jour. Premier jalon Sprint 9 (Phase 6 — Equipement visible & Mercure).
- [x] Nouveau service `App\Service\Avatar\AvatarHashRecalculator` (build payload, compare, persist)
- [x] `GearSetter::setGear` et `unsetGear` appellent le recalculateur apres `flush` (skipped si l'inventaire n'a pas de joueur)
- [x] `Player::setAvatarHash` touche `avatarUpdatedAt` uniquement quand le hash change
- [x] Tests unitaires : `AvatarHashRecalculatorTest` (5 cas), `GearSetterTest` (4 cas), `PlayerAvatarTest` etendu (change/no-op)

### AVT-27 — Resolveur convention-based pour `avatarSheet` (2026-04-17) ✅

> Associe automatiquement chaque item d'equipement a son sprite sheet avatar (format 8x8) sans modifier la fixture `ItemFixtures.php` (4087 lignes). Le nouveau service `App\Service\Avatar\ItemAvatarSheetResolver` derive le chemin `/{avatar_base}/{gear_directory}/{slug}.png` depuis `Item.gearLocation` + `Item.slug`. Le champ explicite `Item.avatarSheet` reste prioritaire pour permettre des overrides custom. `PlayerAvatarPayloadBuilder::getGearLayer()` passe desormais par le resolveur au lieu d'appeler `getAvatarSheet()` directement, ce qui active instantanement l'affichage des layers d'equipement pour tous les items existants des que les sheets 8x8 sont livrees.
- [x] `src/Service/Avatar/ItemAvatarSheetResolver.php` : mapping `gearLocation → directory` (head, chest, leg, foot, hand, belt, shoulder, weapon_main, weapon_side)
- [x] `PlayerAvatarPayloadBuilder` injecte le resolveur et l'utilise dans `getGearLayer()`
- [x] `tests/Unit/Service/Avatar/ItemAvatarSheetResolverTest.php` : 9 cas (override explicite, chaine vide fallback, 9 locations visibles via dataProvider, 3 locations non-visibles neck/ring, item non-gear, gear sans location, gear avec slug vide)
- [x] `tests/Unit/Service/Avatar/PlayerAvatarPayloadBuilderTest.php` adapte pour injecter le resolveur

### AVT-04 — Alignement pixel-perfect des layers avatar (2026-04-17) ✅

> Livraison des premiers assets avatar et validation de l'alignement pixel-perfect. Integration du pack **Mana Seed Character Base (free demo)** sous `assets/styles/images/ManaSeedRPGStarterPack/character_base/char_a_p1/` et selection d'un set MVP de 19 fichiers sous `assets/styles/images/avatar/{body,hair,outfit,head}/`. Le layout Mana Seed natif (rows 0-3 = stand/push/pull/jump, rows 4-7 = walk/run, ordre directions down/up/left/right) a ete adopte sans reslice : tous les layers partagent par construction le meme canvas 512x512 et les memes ancrages. `docs/avatar-spritesheet-layout.md` et `assets/styles/images/avatar/README.md` reecrits pour refleter le nouveau mapping ; `SpriteAnimator` (type `avatar`) adapte : nouvelle structure `AVATAR_ANIMATIONS` avec `cols[]` explicites, `AVATAR_DIRECTION_ROW` propre au layout Mana Seed, breathing reactive pour le stand statique (1 frame). Cloture le Sprint 7 (Avatar : Fondations).
- [x] Pack Mana Seed integre (page 1 uniquement, pages ONE1-3 combat differees)
- [x] 19 sheets MVP : 4 body (`human_v00-v03`) + 5 outfit (`forester_v01-v05`) + 5 hair (`bob_v00-v04`) + 5 head (`pointy_v01-v05`)
- [x] Spec layout reecrite : stand/push/pull/jump rows 0-3, walk/run rows 4-7, directions down/up/left/right
- [x] `SpriteAnimator` : `AVATAR_ANIMATIONS` restructure avec `cols[]`, `AVATAR_DIRECTION_ROW` ajoute, breathing reactive pour avatar
- [x] Sprint 7 cloture : tous les jalons AVT-01 a AVT-12 completes
