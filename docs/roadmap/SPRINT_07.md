## Sprint 7 — Modele zone : Fondations

> **6 taches** (5 livrees) | Priorite : **Critique** | Origine : Pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : geler la carte navigable, poser le graphe de zones et y migrer les joueurs et les donnees du monde.
> Prerequis : ∅ (demarre immediatement — le pivot recentre tout le dev sur ce chantier)
> Avancement : ZON-02 a ZON-06 ✅ (2026-07-24, voir `ROADMAP_DONE.md`)

> **Pivot PBBG** : ce sprint reutilise le numero de l'ancien Sprint 7 « Avatar: Fondations » (✅ termine 12/12 le 2026-04-17, trace dans `ROADMAP_DONE.md`). Le chantier avatar est clos par le pivot — voir `PLAN_AVATAR_SYSTEM.md`.

---

### Phase 1 — Gel de la carte (avant suppression)

### ZON-01 — Geler la carte navigable (M | ★★★)
> Prerequis : ∅
- [ ] Retirer les routes/menus vers `/game/map` (redirection vers l'ecran de zone une fois ZON-05 livre)
- [ ] Ne plus charger `map_pixi_controller.js` ni le bundle PixiJS cote client
- [ ] Ne PAS supprimer le code : gel avant suppression (extraction propre des reutilisables, nettoyage en ZON-21)
- [ ] Suspendre les publications Mercure `map/move` / `map/respawn`

---

### Phase 2 — Graphe de zones

> ZON-02 (entites Zone & ZoneConnection + seed World 1), ZON-03 (Player.currentZone + PlayerZoneSynchronizer + backfill + CLAUDE.md §7), ZON-04 (zone sur Mob/Pnj/ObjectLayer + WorldEntityZoneListener + `app:zone:audit`), ZON-05 (ecran `/game/zone`) et ZON-06 (voyage time-gated : ZoneTravelService, PlayerVisitedZone, POST travel + UI) livrees le 2026-07-24 — retirees du sprint, voir `ROADMAP_DONE.md`.

---

### Definition of Done

- [ ] La carte PixiJS n'est plus accessible ni chargee (code gele, non supprime)
- [ ] Tout joueur a une zone courante ; plus aucune logique ne depend des coordonnees `x.y`
- [ ] On navigue de zone en zone avec un cout en temps reel
- [ ] Mobs/PNJ/ressources rattaches aux zones du World 1
