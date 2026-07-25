## Sprint 8 — Energie & actions de zone

> **6 taches** (6 livrees ✅) | Priorite : **Haute** | Origine : Pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : installer le rythme PBBG — energie regenerante, actions par zone (explorer, chasser, recolter), contenu declaratif.
> Prerequis : Sprint 7 (modele zone)

> **Principe directeur** : l'energie gate l'acces aux rencontres, JAMAIS le combat lui-meme. Les tours de combat restent gratuits et illimites une fois la rencontre engagee. Second regulateur : les PV (l'energie limite les tentatives, la vie fait payer les echecs). Curseurs a etalonner via `docs/BALANCE.md`, sans toucher au code.

> **Pivot PBBG** : ce sprint reutilise le numero de l'ancien Sprint 8 « Avatar: Backend & Carte » (✅ termine 10/10, trace dans `ROADMAP_DONE.md`). Le chantier avatar est clos par le pivot — voir `PLAN_AVATAR_SYSTEM.md`.

---

> **ZON-07 livree le 2026-07-24** (voir `ROADMAP_DONE.md`) : `Player.actionEnergy` (distincte de l'energie de combat), regeneration paresseuse via `ActionEnergyManager`, jauge sur l'ecran de zone, curseur `zone.energy.regen_seconds` en table `parameter`, section 8 de `docs/BALANCE.md`.
> **ZON-08 livree le 2026-07-24** (voir `ROADMAP_DONE.md`) : `ExploreService` + `Zone.exploreConfig` declaratif (poids mob/coffre/filon/PNJ/rien, zone sure sans rencontre), rencontres via `FightHandler` existant, journal d'exploration (`TYPE_EXPLORATION`), bouton Explorer actif (⚡ cout via `parameter`). **La boucle PBBG est jouable.**
> **ZON-09 livree le 2026-07-24** (voir `ROADMAP_DONE.md`) : `HuntService` — action Chasser qui cible une proie precise (monstre deja rencontre au bestiaire et present dans la zone), coute `zone.energy.cost.hunt` puis engage le combat existant. `POST /game/zone/hunt/{id}`, bloc « Chasser une proie » sur l'ecran de zone (masque en zone sure).
> **ZON-10 livree le 2026-07-24** (voir `ROADMAP_DONE.md`) : `GatherService` + `Zone.gatherConfig` declaratif + entite `ZoneVein` (stock collectif partage par zone/ressource qui s'epuise et respawn). Action Recolter (`POST /game/zone/gather/{slug}`) — coute `zone.energy.cost.gather` (defaut 3), puise dans le filon, genere les items existants, journal `TYPE_GATHERING`. Bloc « Recolter un filon » sur l'ecran de zone (jauge stock/capacite, minuterie de respawn). **La boucle PBBG a ses trois actions (explorer/chasser/recolter).**
> **ZON-11 livree le 2026-07-24** (voir `ROADMAP_DONE.md`) : format declaratif YAML par zone (`config/game/zones/world_1.yaml`) — identite, type, `safe`, `explore` (rencontres/loot), `gather` (filons), `connections`. `ZoneDefinitionLoader` (chargement + validation) + `ZoneImporter` (upsert idempotent, non destructif) ; commande `app:zone:import` (`--file`, `--dry-run`). `ZoneGraphFixtures` rejoue le meme YAML (source de verite unique). Format documente dans `DOCUMENTATION.md` (section 7).
> **ZON-12 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : `LifeRegenManager` — regeneration paresseuse des PV hors combat (calquee sur `ActionEnergyManager`, curseur `zone.life.regen_seconds` defaut 12), ancree a la sortie de combat (`FightCleaner`/fuite/defaite) via `Player.lifeUpdatedAt`. Jauge PV sur l'ecran de zone, section 9 de `docs/BALANCE.md`. Les soins existants (objets/sorts) s'integrent sans modification. **Sprint 8 complet (6/6).**

---

### Definition of Done

- [ ] L'energie gate explorer/chasser/recolter ; les tours de combat restent gratuits
- [ ] Chaque zone du World 1 a ses tables de rencontres/loot/ressources en donnees declaratives
- [ ] Les 4 curseurs (energie, PV, lockouts a venir, contribution) sont pilotables via `docs/BALANCE.md`
