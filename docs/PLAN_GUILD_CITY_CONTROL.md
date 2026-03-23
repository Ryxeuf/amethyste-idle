# Plan — Controle de cite par les guildes

> Systeme de competition PvE entre guildes pour le controle temporaire de villes.
> Les guildes s'affrontent indirectement via des activites PvE (combat, craft, recolte, quetes).
> Controle = 1 saison (4 semaines). Region = ensemble de cartes, ville = chef-lieu.

## Vue d'ensemble

**19 taches** (52, 64-82) organisees en 7 pistes.
Depend de la Vague 2 (tache 52 = fondation minimale).

```
Piste A — Guildes service & UI      : 52 → 64 → 65 → 66
Piste B — Regions & villes           : 67 → 68 → 69
Piste C — Moteur d'influence         : 70 → 71, 72
Piste D — Benefices du controle      : 73, 74, 75
Piste E — Classement & visibilite    : 76, 77, 78
Piste F — Defis & engagement         : 79 → 80
Piste G — Infrastructure & qualite   : 81, 82
```

---

## Piste A — Guildes : service & UI (sequentiel)

### 52 — Guildes — entites & enum (S | ★★★ | CRITIQUE)
> Fondation du systeme. Prerequis : ← 38
- [ ] Enum `GuildRank` : leader, officer, veteran, member
- [ ] Entite `Guild` : name (unique), slug (unique), tag (5 chars, unique), description, emblem (nullable), color (hex), maxMembers (default 20), gilsTreasury (default 0), leader (ManyToOne Player), createdAt/updatedAt
- [ ] Entite `GuildMember` : guild (ManyToOne), player (ManyToOne, UNIQUE), rank (GuildRank), joinedAt, contributionTotal (default 0)
- [ ] Migration PostgreSQL
- [ ] Fixtures : 3 guildes de test avec 2-3 membres chacune

### 64 — Guildes — GuildManager service (S | ★★★ | HAUTE)
> Logique metier. Prerequis : ← 52
- [ ] `GuildManager` : createGuild(Player, name, tag, color) — verifie unicite nom/tag
- [ ] invitePlayer, acceptInvite, promoteToOfficer/Veteran, demote
- [ ] kickMember (permissions leader/officer), transferLeadership, dissolveGuild
- [ ] Validation : 1 joueur = 1 guilde max, tag 3-5 chars alphanum
- [ ] Tests unitaires GuildManagerTest (8+ tests)

### 65 — Guildes — controller & templates (S | ★★ | HAUTE)
> Interface utilisateur. Prerequis : ← 64
- [ ] `GuildController` : routes /game/guild/*
- [ ] Pages : index (creer/rejoindre ou vue guilde), create, view/{slug}, members
- [ ] Actions POST : create, invite, accept, leave, kick, promote
- [ ] Lien dans la navigation principale

### 66 — Guildes — chat guilde Mercure (S | ★★ | MOYENNE)
> Canal chat dedie. Prerequis : ← 64
- [ ] Channel `guild` dans ChatManager + topic `chat/guild/{guildId}`
- [ ] Commande `/guild` ou `/g` dans ChatCommandHandler
- [ ] Onglet "Guilde" dans le panneau chat (Stimulus)
- [ ] Verification appartenance avant envoi/reception

---

## Piste B — Regions & villes (sequentiel)

### 67 — Regions & villes — entites (S | ★★★ | CRITIQUE)
> Zones contestables avec ville chef-lieu. Prerequis : ← 48, 52
- [ ] Entite `Region` : name, slug (unique), description, icon (nullable), taxRate (decimal, default 0.05), isContestable (bool, default true)
- [ ] Relation Region → Map (OneToMany), champ `capitalMapId` (FK Map nullable)
- [ ] Champ `region_id` (FK Region, nullable) sur `Map`
- [ ] Migration + fixtures : 2-3 regions de test

### 68 — Saisons d'influence — entites & SeasonManager (S | ★★★ | CRITIQUE)
> Cycles de competition mensuels. Prerequis : ← 67
- [ ] Enum `SeasonStatus` : scheduled, active, completed
- [ ] Entite `InfluenceSeason` : name, slug, seasonNumber, startsAt, endsAt, status, theme (nullable), parameters (JSON nullable)
- [ ] `SeasonManager` : getCurrentSeason(), getOrCreateNextSeason(), startSeason(), endSeason()
- [ ] Fixtures : 1 saison active de test

### 69 — Influence — entites score & log (S | ★★ | HAUTE)
> Tables de score et journal des gains. Prerequis : ← 68
- [ ] Enum `InfluenceActivityType` : mob_kill, craft, harvest, fishing, butchering, quest, challenge
- [ ] Entite `GuildInfluence` : guild, region, season, points. UNIQUE (guild, region, season). Index (region, season, points DESC)
- [ ] Entite `InfluenceLog` : guild, region, season, player, activityType, pointsEarned, details (JSON), createdAt
- [ ] Migration (2 tables)

---

## Piste C — Moteur d'influence (sequentiel)

### 70 — InfluenceListener — hook events PvE (M | ★★★ | CRITIQUE)
> Coeur du systeme : ecoute les events existants et attribue des points. Prerequis : ← 69
- [ ] `InfluenceManager::calculatePoints(Player, activityType, context)` — formules :
  - mob_kill: 5 + (mob_level × 2)
  - craft: 10 + (recipe_level × 5)
  - harvest: 3/item, fishing: 5/peche, butchering: 4/item
  - quest: 20 + (quest_tier × 10)
- [ ] `addPoints(Guild, Region, Season, points, Player, activityType, details)` — upsert GuildInfluence + insert InfluenceLog
- [ ] Region determinee via `player.map.region` (FK directe)
- [ ] Multiplicateur saisonnier via `season.parameters.multipliers[activityType]`
- [ ] `InfluenceListener` (EventSubscriber) : MobDeadEvent, CraftEvent, SpotHarvestEvent, FishingEvent, ButcheringEvent, QuestCompletedEvent
- [ ] Ignore si joueur pas en guilde ou map sans region
- [ ] Tests unitaires (12+ tests)

### 71 — Anti-exploit — plafonds & diminishing returns (S | ★★ | HAUTE)
> Protections anti-farming. Prerequis : ← 70
- [ ] Plafond journalier : 500 pts max/jour/joueur/region
- [ ] Diminishing returns : >10 kills meme monstre en 10min → points × 0.1
- [ ] Decroissance bas niveau : ecart domain XP vs mob_level > 10 → points ÷ 5
- [ ] Minimum 3 membres actifs (lastActivityAt < 7j) pour accumuler des points
- [ ] Constantes configurables dans services.yaml
- [ ] Tests unitaires (5 tests)

### 72 — Controle de ville — attribution fin de saison (S | ★★★ | CRITIQUE)
> Attribution du controle a la guilde gagnante. Prerequis : ← 70
- [ ] Entite `RegionControl` : region, guild (nullable), season, startedAt, endsAt (nullable). 1 controle actif/region (WHERE ends_at IS NULL)
- [ ] `TownControlManager::attributeControl(InfluenceSeason)` : pour chaque region, SELECT guild max points, cree RegionControl
- [ ] Egalite : la guilde tenant conserve le controle
- [ ] Aucune guilde : region reste libre
- [ ] `getControllingGuild(Region)` : retourne Guild ou null
- [ ] Migration + tests (5+ tests)

---

## Piste D — Benefices du controle (parallelisable)

### 73 — Benefices economiques — taxe & reductions (S | ★★ | HAUTE)
> Prerequis : ← 72
- [ ] Taxe commerciale : ShopController::buy() preleve region.taxRate (5%) → gilsTreasury de la guilde
- [ ] Reduction membre : -10% sur prix PNJ si joueur dans la guilde controlante
- [ ] `RegionBonusProvider` : getShopDiscount(Player, Map), getTaxAmount(int, Map)
- [ ] Tests (3 tests)

### 74 — Benefices prestige — titres & cosmetiques (S | ★★ | MOYENNE)
> Prerequis : ← 72
- [ ] Titre "Protecteur de [NomRegion]" auto-attribue aux membres controlants
- [ ] Tag "[TAG]" + couleur de guilde dans le chat
- [ ] Retrait automatique quand le controle change

### 75 — Upgrades de ville — investissement tresor (M | ★★ | MOYENNE)
> Prerequis : ← 73
- [ ] Entite `RegionUpgrade` : regionControl, upgradeSlug, level, costGils, activatedAt
- [ ] Upgrades : shop_discount (1-3), gathering_bonus (1-3), xp_bonus (1-2), monument (1)
- [ ] `RegionUpgradeManager` : canPurchase, purchase (deduit du tresor)
- [ ] Integration RegionBonusProvider
- [ ] Route POST /game/guild/upgrade/{slug} (leader/officer)
- [ ] Reset si changement de guilde controlante, conserve si meme guilde
- [ ] Migration + tests (4 tests)

---

## Piste E — Classement & visibilite (parallelisable)

### 76 — Classement d'influence — page & API (S | ★★★ | HAUTE)
> Prerequis : ← 70
- [ ] Route GET /game/guild/influence — classement par region (top 10, barre de progression)
- [ ] API JSON GET /api/guild/influence/{regionSlug}
- [ ] Onglet historique : saisons passees + vainqueurs
- [ ] Section "Ma guilde" : contribution, rang, top contributeurs internes

### 77 — Indicateurs visuels carte PixiJS (M | ★★ | MOYENNE)
> Prerequis : ← 72, 76
- [ ] Extension `/api/map/entities` : champ `regionControl` {guildName, guildTag, guildColor}
- [ ] Sprite banniere guilde au centre de la ville chef-lieu (overlay PixiJS)
- [ ] Overlay couleur de guilde sur bords de carte (subtil)
- [ ] Stimulus controller `region_control_controller.js`
- [ ] Mise a jour dynamique via Mercure

### 78 — Notifications Mercure influence (S | ★★ | MOYENNE)
> Prerequis : ← 70
- [ ] Topic `guild/influence/{guildId}` — notifs points significatifs (batch 1/5min)
- [ ] Alerte depassement par une autre guilde
- [ ] Annonce globale `guild/city_control` changement de controle fin de saison
- [ ] Integration toasts existants

---

## Piste F — Defis & engagement (sequentiel)

### 79 — Defis hebdomadaires — entites & tracker (S | ★★ | BASSE)
> Prerequis : ← 70
- [ ] Entite `WeeklyChallenge` : season, title, description, activityType, criteria (JSON), bonusPoints, weekNumber, startsAt, endsAt
- [ ] Entite `GuildChallengeProgress` : guild, challenge, progress, completedAt. UNIQUE (guild, challenge)
- [ ] `ChallengeTracker` (EventSubscriber) : incremente progress, ajoute bonusPoints a completion
- [ ] Migration + fixtures (4 defis de test)

### 80 — Defis hebdomadaires — UI & notifications (S | ★ | BASSE)
> Prerequis : ← 79
- [ ] Section "Defis de la semaine" dans page guilde
- [ ] Barre de progression, notification toast a completion
- [ ] Historique des defis completes

---

## Piste G — Infrastructure & qualite (parallelisable)

### 81 — Commande CRON app:season:tick (S | ★★★ | HAUTE)
> Prerequis : ← 68, 72
- [ ] Commande `app:season:tick` (Symfony Scheduler, 1x/jour)
- [ ] Demarre saisons scheduled, termine saisons actives → attributeControl()
- [ ] Auto-genere la prochaine saison si aucune scheduled
- [ ] "Ruee des 3 derniers jours" : points × 1.5 dans les 72 dernieres heures
- [ ] Tests (4 tests)

### 82 — Tests unitaires controle de cite (M | ★★ | HAUTE)
> Prerequis : ← 70, 72
- [ ] Tests InfluenceManager, InfluenceListener (6 events × 2 cas)
- [ ] Tests anti-exploit, TownControlManager, RegionBonusProvider, SeasonManager
- [ ] Objectif : 30+ tests unitaires

---

## Ordre d'implementation recommande

```
Phase 1 (fondation)  : 52 → 64 → 65
Phase 2 (regions)    : 67 → 68 → 69
Phase 3 (moteur)     : 70 → 71 → 72
Phase 4 (benefices)  : 73, 74, 76, 81  (parallelisable)
Phase 5 (polish)     : 66, 75, 77, 78, 79, 80, 82
```
