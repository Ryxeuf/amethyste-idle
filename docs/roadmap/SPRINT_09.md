## Sprint 9 — Time-gating, presence & evenements de zone

> **5 taches** (5 livrees ✅) | Priorite : **Haute** | Origine : Pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : le monde vit quand le joueur est deconnecte — expeditions, presence par zone, evenements annonces, carte du monde illustree.
> Prerequis : Sprint 8 (energie & actions de zone)

> **Pivot PBBG** : ce sprint reutilise le numero de l'ancien Sprint 9 « Avatar: Personnage & Equipement » (✅ termine 8/8 en mai 2026, trace dans `ROADMAP_DONE.md`). Le chantier avatar est clos par le pivot — voir `PLAN_AVATAR_SYSTEM.md`.

---

> **ZON-13 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : entite `PlayerExpedition` (une par joueur, contrainte UNIQUE) + `ExpeditionService` — envoi en expedition N heures reelles dans la zone courante (paliers courte/moyenne/longue, curseurs `zone.expedition.duration.*`), resolution paresseuse, butin a recuperer au retour. Recompenses **derivees des tables declaratives de la zone** (coffre `exploreConfig` + filons `gatherConfig`) mises a l'echelle par la duree. Etat exclusif (bloque explorer/chasser/recolter/voyager). Notification de fin via `NotificationService` (in-game + Mercure `player/<id>/notifications`). Section 10 de `docs/BALANCE.md`.

> **ZON-14 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : canal de chat `zone` (nouveau `ChatMessage::CHANNEL_ZONE` + FK `zone_id`), `ChatManager::sendZoneMessage`/`getZoneHistory`, topic Mercure `chat/zone/<id>`. Panneau de chat temps reel + liste de presence avec interactions rapides (profil, invitation groupe) sur l'ecran de zone (`zone_chat_controller.js`), endpoint `GET /game/zone/presence`. Le « commerce » rapide reste hors perimetre (aucun systeme de troc joueur-joueur en place ; a introduire par une tache economie dediee).

> **ZON-15 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : couche « evenements de zone » — `GameEvent.zone` FK, `ZoneEventService` (evenements actifs par zone dans leur fenetre, `join` qui prleve `zone.energy.cost.event` et enregistre une `PlayerZoneEventParticipation` avec champ `contribution` preparant ZON-18), annonce Mercure `zone/<id>/event` (`ZoneEventAnnouncementHandler`). Surface sur l'ecran de zone (bloc evenement + bouton Rejoindre), selecteur de zone dans le formulaire admin d'evenements. Curseur BALANCE section 8. Le combat asynchrone du boss (assauts a l'energie, loot a la contribution) est generalise en **ZON-18**.

> **ZON-16 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : carte du monde illustree `/game/world-map` — rendu SVG schematique (aucun moteur de rendu type PixiJS), zones placees via `Zone.mapX`/`mapY` (declaratif dans `world_1.yaml`, ZON-11), aretes = connexions du graphe. Zones decouvertes cliquables (clic vers une zone adjacente = voyage ZON-06), zones non decouvertes masquees (« ??? »). Indicateurs : evenement de zone actif (ZON-15), expedition en cours (ZON-13). Lien dans la nav Aventure.

> **ZON-17 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : **decision — le cycle jour/nuit devient mecanique** (variante RICHE). `GameTimeService::getPhase()`/`isNight()` (jour 6h-18h, nuit 18h-6h) au-dessus du cycle cosmetique. Variante declarative `explore.night` par zone (surcharge `weights`, `chest_gils_*`, pool de rencontres nocturne dedie `mob_slugs`) — rencontres ET loot varient. Evenements de zone (ZON-15) filtrables par phase via `parameters['phase']`. Indicateur jour/nuit sur l'ecran de zone. Exemple nocturne sur `foret-des-murmures` (morts-vivants + coffres plus riches la nuit).

---

### Definition of Done

- [ ] Un joueur peut lancer une expedition, se deconnecter, et recuperer son butin plus tard
- [ ] On voit qui est present dans sa zone et on peut discuter/cooperer
- [ ] Les world bosses fonctionnent comme evenements de zone annonces
- [ ] La carte du monde illustree remplace visuellement l'ancienne carte navigable
