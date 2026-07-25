## Sprint 13 — Consolidation post-pivot

> **6 taches** (ZON-22 → ZON-27), **5 livrees** (ZON-27 : sous-jalon a livre) | Priorite : **Critique** | Origine : dette identifiee a la cloture de la campagne ZON ([docs/ZON_CAMPAIGN_RECAP.md](../ZON_CAMPAIGN_RECAP.md) §4)
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

> **ZON-25 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : `FightLootedEvent` est desormais emis
> par `FightLootProceedController` — l'etape « inventaire » du tutoriel etait bloquee. `FightCleaner`
> supprime (son nettoyage etait deja fait en ligne par le controleur) et son **ancrage de regen des
> PV** deplace la ou le joueur quitte le combat : la victoire suivie du butin etait le seul chemin de
> sortie qui l'omettait, les PV perdus se regeneraient d'un coup. `MobRepository::findByMapWithMonster`
> supprimee. Garde-fou corrige : `PlayerActionHitEvent` / `PlayerActionMissEvent` etaient des **faux
> positifs** (classes parentes, jamais instanciees mais etendues par des evenements bien emis) — le
> test exclut desormais les classes parentes automatiquement.
>
> **Audit des coordonnees heritees** : `Player::coordinates` n'est plus une reference de position
> (regle #7), mais les coordonnees restent **utilisees** par les systemes qui placent des entites sur
> les cartes support (routines PNJ, spawn de world boss, invasions, donjons, recolte). Leur retrait
> est donc une migration a part entiere, pas un nettoyage : **non fait ici**, a instruire avec ZON-26.
>
> **Report vers ZON-27** : `PnjDialogEvent` reste sans emetteur — il en faut un ecran de dialogue PNJ,
> qui n'existe plus. Seul orphelin encore tolere par `KNOWN_ORPHANS`.

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

> **ZON-27 sous-jalon a livre le 2026-07-25** (voir `ROADMAP_DONE.md`) : les PNJ presents dans la
> zone sont exposes sur `/game/zone`, avec un point d'entree **boutique** pour les marchands
> (respectant les horaires d'ouverture). `ShopController` refuse desormais un PNJ d'une autre zone.
> E2E `ZoneShopFlowTest` (remplace l'ex-`ShopFlowTest`). Les boutiques etaient injoignables depuis
> ZON-21a.

### ZON-27 — Couche PNJ de zone : dialogue (sous-jalon b) (M | ★★★ | HAUTE)
> Prerequis : ← ZON-27a ✅
> Reste le volet **dialogue**, qui debloque `PnjDialogEvent` (dernier orphelin) et les objectifs de
> quete `talk_to` (quetes d'enquete), aujourd'hui sans aucun moyen de progresser.
- [ ] Ecran/action de dialogue PNJ depuis l'ecran de zone (reutiliser `Pnj::getDialog()`)
- [ ] Emission de `PnjDialogEvent` → `QuestTalkToTrackingListener` → `updateTalkedTo`
- [ ] Retrait de `PnjDialogEvent` de `KNOWN_ORPHANS` (le garde-fou echoue s'il retrouve un emetteur)
- [ ] Tests : progression d'un objectif `talk_to`, dialogue d'un PNJ hors zone refuse

---

### Definition of Done

- [x] Quetes d'exploration, escorte, quetes cachees, tutoriel et decouverte de region fonctionnels
      en modele zone (ZON-22)
- [x] Boucle de jeu principale (zone → action → combat) couverte en E2E dans la CI (ZON-23)
- [~] Aucun evenement de domaine sans emetteur — reste `PnjDialogEvent`, traite par ZON-27
- [~] PNJ joignables depuis la zone : boutiques ✅ (ZON-27a), dialogues a faire (ZON-27b)
- [x] Scenarios k6 mesurant des routes reellement servies (ZON-24)
- [ ] World 1 jouable de bout en bout sur un graphe de zones dense (ZON-26)
