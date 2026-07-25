## Sprint 13 — Consolidation post-pivot

> **6 taches** (ZON-22 → ZON-27), **3 livrees** | Priorite : **Critique** | Origine : dette identifiee a la cloture de la campagne ZON ([docs/ZON_CAMPAIGN_RECAP.md](../ZON_CAMPAIGN_RECAP.md) §4)
> Objectif : refermer les trous laisses par la suppression du code carte (ZON-21) — remettre en
> marche les systemes qui dependaient du deplacement, retablir la couverture de test, et donner au
> modele zone le volume de contenu qui justifie le pivot.
> Prerequis : Sprint 10 ✅ (campagne ZON-12→21 complete)

> **Pourquoi ce sprint est prioritaire** : le pivot avait supprime le *dispatcher* de
> `PlayerMovedEvent` sans rebrancher ses **6 abonnes** — plusieurs systemes de progression etaient
> inertes en production. Retabli par **ZON-22** ✅. Le garde-fou livre au passage a revele
> **4 autres evenements sans emetteur** (voir ZON-25), et la preparation de ZON-23 a montre que la
> **couche PNJ** (boutiques, dialogues) est devenue injoignable (voir **ZON-27**). Le pivot a coupe
> plus de fils qu'il n'y parait : ce sprint les rebranche un par un.

---

> **ZON-22 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) — decoupee en 2 sous-jalons :
> **a** (PR #627) `PlayerTraveledEvent` emis a chaque arrivee par `ZoneTravelService::settleArrival`,
> tutoriel (etape « deplacement » + libelle), `RegionDiscoveryTracker` (region derivee de la zone),
> `PlayerZoneSynchronizer` (desabonne du deplacement, conserve comme amorce + respawn).
> **b** exploration/escorte par zone (`PlayerQuestUpdater::updateExplored/updateEscort(Zone)`,
> cle `zone_slug` cible + `map_id` herite resolu via `Zone::sourceMap`), declencheur `explore` des
> quetes cachees, suppression de `PlayerMovedEvent`, garde-fou `DomainEventDispatchGuardTest`.
>
> **Suivi de contenu** (a traiter avec ZON-26) : les 25 objectifs d'exploration et 2 objectifs
> d'escorte des fixtures ciblent encore `map_id` + coordonnees. Ils fonctionnent via la compatibilite
> heritee, mais plusieurs points d'une meme carte se valident desormais ensemble (une zone est plus
> grossiere qu'une case). Migration vers `zone_slug` — et objectifs d'exploration plus fins adosses a
> l'action **Explorer** — a faire avec la densification du graphe.

> **ZON-23 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : `ZoneFlowTest` (ecran, ressources,
> connexions, action Explorer) et `ZoneCombatFlowTest` (Explorer jusqu'a la rencontre → UI de combat →
> attaque de base) remplacent les E2E carte supprimes par ZON-21a. `data-testid` stables poses sur
> l'ecran de zone, helper `resolvePendingFight()` pour l'etat partage entre tests, et fixtures joueur
> rattachees a une **vraie zone** (elles laissaient les joueurs sur la « Carte de test » heritee).
>
> **Non couvert, et pourquoi** :
> - **Boutique** : aucun E2E possible — plus aucun ecran ne mene a `/game/shop/{id}` (voir **ZON-27**).
> - **Chasser** : `HuntService::getHuntTargets` ne propose que des proies **deja connues du bestiaire**,
>   et aucune fixture ne renseigne d'entree de bestiaire. A rouvrir avec des fixtures de bestiaire.
> - **Demarrage effectif d'un voyage** : immobiliserait le joueur partage plusieurs minutes (liaison la
>   plus courte : 5 min). Deja couvert cote fonctionnel (`ZoneControllerTest`).

> **ZON-24 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : `mercure-streaming` s'abonne desormais
> a `chat/zone/<id>` (avec `MERCURE_ZONE_ID`) au lieu du topic supprime `map/move` ; metrique morte
> `authed_map_api_latency` remplacee par `authed_json_api_latency` dans `authenticated-gameplay` ;
> README de charge reecrit sur le modele zone (plus aucune route supprimee decrite) ;
> `LOAD_TESTING_BOTTLENECKS.md` requalifie — le jalon D devient **sans objet** (ses endpoints
> n'existent plus) et un jalon **Z** est ouvert : aucune mesure n'a encore ete faite sur le profil
> zone, c'est le prerequis de l'objectif « 200 joueurs » de la tache 134.

### ZON-25 — Residus carte & evenements orphelins (M | ★★ | HAUTE)
> Prerequis : ← ZON-22 ✅
> **Perimetre elargi** : le garde-fou livre avec ZON-22b (`DomainEventDispatchGuardTest`) a revele
> **4 evenements sans emetteur** en plus de `PlayerMovedEvent`. Ils sont **anterieurs au pivot**
> (aucun emetteur dans l'historique visible), mais deux ont un impact fonctionnel reel. Ils sont
> tolerés par une liste d'exceptions explicite (`KNOWN_ORPHANS`) a resorber ici.

- [ ] **`PnjDialogEvent` — impact reel** : seul declencheur de `QuestTalkToTrackingListener` →
      `PlayerQuestUpdater::updateTalkedTo`. Les objectifs de quete « parler a un PNJ » (type
      `talk_to`, quetes d'enquete) **ne progressent pas**. Emettre l'evenement depuis le dialogue PNJ
      de l'ecran de zone.
- [ ] **`FightLootedEvent` — impact reel** : seul declencheur de `FightCleaner::removeFight` (purge
      du combat apres butin) et de l'etape « inventaire » du tutoriel. A emettre depuis
      `/game/fight/loot`, ou retirer les deux abonnes si la purge se fait ailleurs.
- [ ] **`PlayerActionHitEvent` / `PlayerActionMissEvent`** : aucun emetteur **et** aucun abonne —
      code mort, a supprimer.
- [ ] Retirer de `KNOWN_ORPHANS` chaque evenement traite (le garde-fou echoue si un orphelin
      declare retrouve un emetteur, pour eviter une liste qui se perime en silence).
- [ ] `MobRepository::findByMapWithMonster` : sans appelant depuis le retrait de `/api/map/entities`
      — supprimer ou requalifier pour le modele zone
- [ ] Auditer les champs herites `Player::getX()` / `getY()` / `coordinates` : documenter comme
      champs morts (regle #7) ou planifier leur retrait par migration

### ZON-26 — Densification du graphe de zones (L | ★★★ | HAUTE)
> Prerequis : ← ZON-11 ✅ | Bloque : **128** (Acte 4)
> **Constat** : la promesse du pivot est « ajouter du contenu = ajouter de la donnee ». Le graphe
> actuel ne compte que **5 zones, 6 connexions et 10 filons** (`config/game/zones/world_1.yaml`) —
> le modele fonctionne mais le monde est vide, et la boucle energie/voyage n'a pas de terrain de jeu.
- [ ] Porter les zones restantes du World 1 (interieurs, zones secondaires) en configuration declarative
- [ ] Etoffer les tables `explore` / `gather` par zone (variete de rencontres, filons par profession)
- [ ] Densifier le graphe de connexions (routes alternatives, durees differenciees) — condition pour
      que la monture (tache 130) et le time-gating aient un sens
- [ ] Illustrations de zone + positions `map_x`/`map_y` sur la carte du monde (ZON-16)
- [ ] Etalonner les couts d'energie et les durees de voyage dans `docs/BALANCE.md` sur ce graphe elargi

### ZON-27 — Couche PNJ de zone (L | ★★★ | **HAUTE**)
> Prerequis : ∅ | Decouvert en preparant ZON-23
> **Constat** : la suppression du front carte (ZON-21a) a emporte les overlays PNJ (`dialog`,
> boutique) **sans les remplacer**. Consequences verifiees :
> - `/game/shop/{id}` existe et fonctionne, mais **aucun template du jeu n'y renvoie** : les boutiques
>   PNJ sont injoignables.
> - Aucune route de **dialogue PNJ** ne subsiste ; `PnjDialogEvent` n'a plus d'emetteur (cf. ZON-25),
>   donc les objectifs de quete `talk_to` (quetes d'enquete) ne progressent pas.
> - Les PNJ presents dans une zone ne sont exposes nulle part dans l'ecran de zone.
>
> **Recommandation** : exposer les PNJ presents dans l'ecran de zone (liste avec leurs actions —
> boutique, dialogue, quetes), en reutilisant l'entite `Pnj` et le rattachement de zone existants.
> C'est la brique qui rebranche d'un coup boutiques, dialogues et quetes `talk_to`.

- [ ] Lister les PNJ presents dans la zone courante sur `/game/zone` (nom, role, actions)
- [ ] Point d'entree **boutique** vers `/game/shop/{id}` pour les PNJ marchands (gating par la zone)
- [ ] Ecran/action de **dialogue PNJ** emettant `PnjDialogEvent` → debloque les quetes `talk_to`
- [ ] Couverture E2E : acces boutique depuis une zone ville (remplace l'ex-`ShopFlowTest`)
- [ ] Tests fonctionnels : PNJ d'une autre zone inaccessible

---

### Definition of Done

- [x] Quetes d'exploration, escorte, quetes cachees, tutoriel et decouverte de region fonctionnels
      en modele zone (ZON-22)
- [x] Boucle de jeu principale (zone → action → combat) couverte en E2E dans la CI (ZON-23)
- [ ] Aucun evenement de domaine sans emetteur — liste `KNOWN_ORPHANS` videe (ZON-25)
- [ ] PNJ joignables depuis la zone : boutiques et dialogues (ZON-27)
- [x] Scenarios k6 mesurant des routes reellement servies (ZON-24)
- [ ] World 1 jouable de bout en bout sur un graphe de zones dense (ZON-26)
