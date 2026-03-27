# Plan — Controle de cite par les guildes

> **Numerotation :** les jalons de **ce** document sont prefixes **GCC-** (Guild City Control). Ils n'entrent **pas** en conflit avec les numeros de la roadmap globale (`ROADMAP_TODO_VAGUE_*.md`). Les prerequis **roadmap globale** sont indiques en clair (ex. **38**, **48**, **52**). **GCC-01** est le decoupage fin de la tache globale **52** (guildes fondation).

> Systeme de competition PvE entre guildes pour le controle temporaire de villes.
> Les guildes s'affrontent indirectement via des activites PvE (combat, craft, recolte, quetes).
> Controle = 1 saison (4 semaines). Region = ensemble de cartes, ville = chef-lieu.

## Vue d'ensemble

**20 jalons** (**GCC-01** a **GCC-20**) organises en 7 pistes.
Prerequis roadmap globale : **38** (amis), **48** (hub), **52** (guildes fondation — couverte en detail par **GCC-01** a **GCC-04**).

| Code | Sujet (resume) |
|------|----------------|
| GCC-01 | Entites guilde & enum |
| GCC-02 | GuildManager |
| GCC-03 | Controller & templates |
| GCC-04 | Chat guilde Mercure |
| GCC-05 | Entites Region & Map |
| GCC-06 | Saisons d'influence |
| GCC-07 | Score & logs d'influence |
| GCC-08 | InfluenceListener / points PvE |
| GCC-09 | Anti-exploit |
| GCC-10 | Attribution controle fin de saison |
| GCC-11 | Taxe & reductions boutique |
| GCC-12 | Titres & cosmetiques |
| GCC-13 | Upgrades de ville |
| GCC-14 | Classement & API influence |
| GCC-15 | Indicateurs carte PixiJS |
| GCC-16 | Notifications Mercure |
| GCC-17 | Defis hebdo — entites |
| GCC-18 | Defis hebdo — UI |
| GCC-19 | Commande `app:season:tick` |
| GCC-20 | Tests unitaires plan |

```
Piste A — Guildes service & UI       : GCC-01 → GCC-02 → GCC-03 → GCC-04
Piste B — Regions & villes            : GCC-05 → GCC-06 → GCC-07
Piste C — Moteur d'influence          : GCC-08 → GCC-09, GCC-10
Piste D — Benefices du controle       : GCC-11, GCC-12, GCC-13
Piste E — Classement & visibilite     : GCC-14, GCC-15, GCC-16
Piste F — Defis & engagement          : GCC-17 → GCC-18
Piste G — Infrastructure & qualite    : GCC-19, GCC-20
```

---

## Piste A — Guildes : service & UI (sequentiel)

### GCC-01 — Guildes — entites & enum (S | ★★★ | CRITIQUE)
> Fondation du systeme. Prerequis roadmap globale : **38**
- [ ] Enum `GuildRank` : leader, officer, veteran, member
- [ ] Entite `Guild` : name (unique), slug (unique), tag (5 chars, unique), description, emblem (nullable), color (hex), maxMembers (default 20), gilsTreasury (default 0), leader (ManyToOne Player), createdAt/updatedAt
- [ ] Entite `GuildMember` : guild (ManyToOne), player (ManyToOne, UNIQUE), rank (GuildRank), joinedAt, contributionTotal (default 0)
- [ ] Migration PostgreSQL
- [ ] Fixtures : 3 guildes de test avec 2-3 membres chacune

### GCC-02 — Guildes — GuildManager service (S | ★★★ | HAUTE)
> Logique metier. Prerequis : ← GCC-01
- [ ] `GuildManager` : createGuild(Player, name, tag, color) — verifie unicite nom/tag
- [ ] invitePlayer, acceptInvite, promoteToOfficer/Veteran, demote
- [ ] kickMember (permissions leader/officer), transferLeadership, dissolveGuild
- [ ] Validation : 1 joueur = 1 guilde max, tag 3-5 chars alphanum
- [ ] Tests unitaires GuildManagerTest (8+ tests)

### GCC-03 — Guildes — controller & templates (S | ★★ | HAUTE)
> Interface utilisateur. Prerequis : ← GCC-02
- [ ] `GuildController` : routes /game/guild/*
- [ ] Pages : index (creer/rejoindre ou vue guilde), create, view/{slug}, members
- [ ] Actions POST : create, invite, accept, leave, kick, promote
- [ ] Lien dans la navigation principale

### GCC-04 — Guildes — chat guilde Mercure (S | ★★ | MOYENNE)
> Canal chat dedie. Prerequis : ← GCC-02
- [ ] Channel `guild` dans ChatManager + topic `chat/guild/{guildId}`
- [ ] Commande `/guild` ou `/g` dans ChatCommandHandler
- [ ] Onglet "Guilde" dans le panneau chat (Stimulus)
- [ ] Verification appartenance avant envoi/reception

---

## Piste B — Regions & villes (sequentiel)

### ~~GCC-05 — Regions & villes — entites (S | ★★★ | CRITIQUE)~~ ✅
> Zones contestables avec ville chef-lieu. Prerequis roadmap globale : **48** (hub), **GCC-01** (ou tache globale **52** une fois le socle guilde en place)
- [x] Entite `Region` : name, slug (unique), description, icon (nullable), taxRate (decimal, default 0.05), isContestable (bool, default true)
- [x] Relation Region → Map (OneToMany), champ `capitalMapId` (FK Map nullable)
- [x] Champ `region_id` (FK Region, nullable) sur `Map`
- [x] Migration + fixtures : 2-3 regions de test

### ~~GCC-06 — Saisons d'influence — entites & SeasonManager (S | ★★★ | CRITIQUE)~~ ✅
> Cycles de competition mensuels. Prerequis : ← GCC-05
- [x] Enum `SeasonStatus` : scheduled, active, completed
- [x] Entite `InfluenceSeason` : name, slug, seasonNumber, startsAt, endsAt, status, theme (nullable), parameters (JSON nullable)
- [x] `SeasonManager` : getCurrentSeason(), getOrCreateNextSeason(), startSeason(), endSeason()
- [x] Fixtures : 1 saison active de test

### ~~GCC-07 — Influence — entites score & log (S | ★★ | HAUTE)~~ ✅
> Tables de score et journal des gains. Prerequis : ← GCC-06
- [x] Enum `InfluenceActivityType` : mob_kill, craft, harvest, fishing, butchering, quest, challenge
- [x] Entite `GuildInfluence` : guild, region, season, points. UNIQUE (guild, region, season). Index (region, season, points DESC)
- [x] Entite `InfluenceLog` : guild, region, season, player, activityType, pointsEarned, details (JSON), createdAt
- [x] Migration (2 tables)

---

## Piste C — Moteur d'influence (sequentiel)

### ~~GCC-08 — InfluenceListener — hook events PvE (M | ★★★ | CRITIQUE)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.
- [x] `InfluenceManager::calculatePoints(Player, activityType, context)` — formules :
  - mob_kill: 5 + (mob_level × 2)
  - craft: 10 + (recipe_level × 5)
  - harvest: 3/item, fishing: 5/peche, butchering: 4/item
  - quest: 20 + (quest_tier × 10)
- [x] `addPoints(Guild, Region, Season, points, Player, activityType, details)` — upsert GuildInfluence + insert InfluenceLog
- [x] Region determinee via `player.map.region` (FK directe)
- [x] Multiplicateur saisonnier via `season.parameters.multipliers[activityType]`
- [x] `InfluenceListener` (EventSubscriber) : MobDeadEvent, CraftEvent, SpotHarvestEvent, FishingEvent, ButcheringEvent, QuestCompletedEvent
- [x] Ignore si joueur pas en guilde ou map sans region
- [x] Tests unitaires (12+ tests)

### GCC-09 — Anti-exploit — plafonds & diminishing returns (S | ★★ | HAUTE)
> Protections anti-farming. Prerequis : ← GCC-08
- [ ] Plafond journalier : 500 pts max/jour/joueur/region
- [ ] Diminishing returns : >10 kills meme monstre en 10min → points × 0.1
- [ ] Decroissance bas niveau : ecart domain XP vs mob_level > 10 → points ÷ 5
- [ ] Minimum 3 membres actifs (lastActivityAt < 7j) pour accumuler des points
- [ ] Constantes configurables dans services.yaml
- [ ] Tests unitaires (5 tests)

### ~~GCC-10 — Controle de ville — attribution fin de saison (S | ★★★ | CRITIQUE)~~ ✅
> Attribution du controle a la guilde gagnante. Prerequis : ← GCC-08
- [x] Entite `RegionControl` : region, guild (nullable), season, startedAt, endsAt (nullable). 1 controle actif/region (WHERE ends_at IS NULL)
- [x] `TownControlManager::attributeControl(InfluenceSeason)` : pour chaque region, SELECT guild max points, cree RegionControl
- [x] Egalite : la guilde tenant conserve le controle
- [x] Aucune guilde : region reste libre
- [x] `getControllingGuild(Region)` : retourne Guild ou null
- [x] Migration + tests (8 tests)

---

## Piste D — Benefices du controle (parallelisable)

### GCC-11 — Benefices economiques — taxe & reductions (S | ★★ | HAUTE)
> Prerequis : ← GCC-10
- [ ] Taxe commerciale : ShopController::buy() preleve region.taxRate (5%) → gilsTreasury de la guilde
- [ ] Reduction membre : -10% sur prix PNJ si joueur dans la guilde controlante
- [ ] `RegionBonusProvider` : getShopDiscount(Player, Map), getTaxAmount(int, Map)
- [ ] Tests (3 tests)

### GCC-12 — Benefices prestige — titres & cosmetiques (S | ★★ | MOYENNE)
> Prerequis : ← GCC-10
- [ ] Titre "Protecteur de [NomRegion]" auto-attribue aux membres controlants
- [ ] Tag "[TAG]" + couleur de guilde dans le chat
- [ ] Retrait automatique quand le controle change

### GCC-13 — Upgrades de ville — investissement tresor (M | ★★ | MOYENNE)
> Prerequis : ← GCC-11
- [ ] Entite `RegionUpgrade` : regionControl, upgradeSlug, level, costGils, activatedAt
- [ ] Upgrades : shop_discount (1-3), gathering_bonus (1-3), xp_bonus (1-2), monument (1)
- [ ] `RegionUpgradeManager` : canPurchase, purchase (deduit du tresor)
- [ ] Integration RegionBonusProvider
- [ ] Route POST /game/guild/upgrade/{slug} (leader/officer)
- [ ] Reset si changement de guilde controlante, conserve si meme guilde
- [ ] Migration + tests (4 tests)

---

## Piste E — Classement & visibilite (parallelisable)

### GCC-14 — Classement d'influence — page & API (S | ★★★ | HAUTE)
> Prerequis : ← GCC-08
- [ ] Route GET /game/guild/influence — classement par region (top 10, barre de progression)
- [ ] API JSON GET /api/guild/influence/{regionSlug}
- [ ] Onglet historique : saisons passees + vainqueurs
- [ ] Section "Ma guilde" : contribution, rang, top contributeurs internes

### GCC-15 — Indicateurs visuels carte PixiJS (M | ★★ | MOYENNE)
> Prerequis : ← GCC-10, GCC-14
- [ ] Extension `/api/map/entities` : champ `regionControl` {guildName, guildTag, guildColor}
- [ ] Sprite banniere guilde au centre de la ville chef-lieu (overlay PixiJS)
- [ ] Overlay couleur de guilde sur bords de carte (subtil)
- [ ] Stimulus controller `region_control_controller.js`
- [ ] Mise a jour dynamique via Mercure

### GCC-16 — Notifications Mercure influence (S | ★★ | MOYENNE)
> Prerequis : ← GCC-08
- [ ] Topic `guild/influence/{guildId}` — notifs points significatifs (batch 1/5min)
- [ ] Alerte depassement par une autre guilde
- [ ] Annonce globale `guild/city_control` changement de controle fin de saison
- [ ] Integration toasts existants

---

## Piste F — Defis & engagement (sequentiel)

### GCC-17 — Defis hebdomadaires — entites & tracker (S | ★★ | BASSE)
> Prerequis : ← GCC-08
- [ ] Entite `WeeklyChallenge` : season, title, description, activityType, criteria (JSON), bonusPoints, weekNumber, startsAt, endsAt
- [ ] Entite `GuildChallengeProgress` : guild, challenge, progress, completedAt. UNIQUE (guild, challenge)
- [ ] `ChallengeTracker` (EventSubscriber) : incremente progress, ajoute bonusPoints a completion
- [ ] Migration + fixtures (4 defis de test)

### GCC-18 — Defis hebdomadaires — UI & notifications (S | ★ | BASSE)
> Prerequis : ← GCC-17
- [ ] Section "Defis de la semaine" dans page guilde
- [ ] Barre de progression, notification toast a completion
- [ ] Historique des defis completes

---

## Piste G — Infrastructure & qualite (parallelisable)

### ~~GCC-19 — Commande CRON app:season:tick (S | ★★★ | HAUTE)~~ ✅
> Prerequis : ← GCC-06, GCC-10
- [x] Commande `app:season:tick` (Symfony Scheduler, 1x/jour)
- [x] Demarre saisons scheduled, termine saisons actives → attributeControl()
- [x] Auto-genere la prochaine saison si aucune scheduled
- [x] "Ruee des 3 derniers jours" : points × 1.5 dans les 72 dernieres heures
- [x] Tests (9 tests)

### GCC-20 — Tests unitaires controle de cite (M | ★★ | HAUTE)
> Prerequis : ← GCC-08, GCC-10
- [ ] Tests InfluenceManager, InfluenceListener (6 events × 2 cas)
- [ ] Tests anti-exploit, TownControlManager, RegionBonusProvider, SeasonManager
- [ ] Objectif : 30+ tests unitaires

---

## Ordre d'implementation recommande

```
Phase 1 (fondation)  : GCC-01 → GCC-02 → GCC-03
Phase 2 (regions)    : GCC-05 → GCC-06 → GCC-07
Phase 3 (moteur)     : GCC-08 → GCC-09 → GCC-10
Phase 4 (benefices)  : GCC-11, GCC-12, GCC-14, GCC-19  (parallelisable)
Phase 5 (polish)     : GCC-04, GCC-13, GCC-15, GCC-16, GCC-17, GCC-18, GCC-20
```
