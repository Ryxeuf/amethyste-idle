# Roadmap realisee — Amethyste-Idle

> Historique des phases completees. Ce fichier est la reference pour tout ce qui a ete implemente.
> Derniere mise a jour : 2026-03-22

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

## 36 — Gains et recompenses reputation (2026-03-22) ✅

> Systeme complet de gains de reputation (quetes, mobs) et recompenses par palier de faction.

- [x] Entite `FactionReward` : faction, requiredTier (enum ReputationTier), rewardType, rewardData JSON, name, description
- [x] Service `ReputationManager::addReputation(Player, Faction, amount)` : cree ou met a jour PlayerFaction
- [x] `ReputationGainListener` (EventSubscriber) : ecoute MobDeadEvent (+rep si monstre a une faction) et QuestCompletedEvent (+rep via rewards JSON `faction_reputation`)
- [x] Champs `faction` (ManyToOne nullable) et `factionReputationReward` (int nullable) sur Monster
- [x] Migration SQL : table game_faction_rewards + colonnes faction_id/faction_reputation_reward sur game_monsters
- [x] Fixtures : 12 recompenses (3 par faction x 4 factions) aux paliers Ami, Honore, Exalte (discount, recipe_unlock, item, zone_access)
- [x] 4 monstres associes a des factions (squelette→Ombres, spectre→Ombres, elementaire de feu→Mages, minotaure→Chevaliers)
- [x] 3 quetes avec reputation en recompense (squelettes→Ombres, troll→Marchands+Chevaliers, dragon→Chevaliers+Mages)
- [x] Page /game/factions enrichie : affichage des recompenses debloquees/verrouillees par palier
- [x] PHPStan OK, PHP-CS-Fixer OK