## Sprint 7 — Modele zone : Fondations

> **6 taches** | Priorite : **Critique** | Origine : Pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : geler la carte navigable, poser le graphe de zones et y migrer les joueurs et les donnees du monde.

> **Pivot PBBG** : ce sprint reutilise le numero de l'ancien Sprint 7 « Avatar: Fondations » (✅ termine 12/12 le 2026-04-17, trace dans `ROADMAP_DONE.md`). Le chantier avatar est clos par le pivot — voir `PLAN_AVATAR_SYSTEM.md`.

---

Taches livrees le 2026-07-24 (details dans `ROADMAP_DONE.md`) :

- **ZON-02** — Entites Zone & ZoneConnection + graphe World 1 (5 zones, 12 aretes)
- **ZON-03** — Player.currentZone + PlayerZoneSynchronizer + backfill + CLAUDE.md §7
- **ZON-04** — Zone sur Mob/Pnj/ObjectLayer + WorldEntityZoneListener + `app:zone:audit`
- **ZON-05** — Ecran `/game/zone` (identite, actions conditionnees, connexions, joueurs presents, points d'interet)
- **ZON-06** — Voyage time-gated (ZoneTravelService, PlayerVisitedZone, POST travel + UI)
- **ZON-01** — Gel de la carte derriere le feature flag `map_frozen` (desactive par defaut ; activation prevue apres le Sprint 8 pour ne pas couper la boucle de jeu)

---

### Definition of Done

- [x] La carte PixiJS n'est plus accessible ni chargee quand `map_frozen` est actif (code gele, non supprime)
- [x] Tout joueur a une zone courante ; plus aucune NOUVELLE logique ne depend des coordonnees `x.y` (regle CLAUDE.md §7)
- [x] On navigue de zone en zone avec un cout en temps reel
- [x] Mobs/PNJ/ressources rattaches aux zones du World 1 (audit : 0 orpheline)

---

**Statut : ✅ Sprint 7 termine (2026-07-24)** — Voir `docs/ROADMAP_DONE.md` (ZON-01 a ZON-06). Prochaine etape : Sprint 8 (energie & actions de zone), puis activation du gel `map_frozen`.
