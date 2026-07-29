## Sprint 13 — Consolidation post-pivot

> **8 taches** (ZON-22 → ZON-27, sous-jalons compris), **8/8 livrees — sprint termine** ✅ | Priorite : **Critique** | Origine : dette identifiee a la cloture de la campagne ZON ([docs/ZON_CAMPAIGN_RECAP.md](../ZON_CAMPAIGN_RECAP.md) §4)
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

> **ZON-26 sous-jalon a livre le 2026-07-25** (voir `ROADMAP_DONE.md`) : tables d'exploration et
> variance jour/nuit ajoutees aux Mines, au Marais et a la Crete (seule la Foret en avait) ; filons
> portes de 10 a 13 ; **anneau peripherique** ferme (8 connexions au lieu de 6), pour que contourner
> le hub devienne une alternative credible. Calibrage documente dans `BALANCE.md` §11.

> **ZON-26 sous-jalon b — mecanisme livre** ✅ : les blocs `mobs:` / `pnjs:` declaratifs sont
> consommes par `ZoneImporter` (ZON-26b-a/b) et les positions `map_x`/`map_y` sont posees sur les
> 12 zones — une zone se peuple et se place desormais par la donnee seule.
> Les restes — de pures **donnees** — sont transferes dans [PLAN_ZONES.md](PLAN_ZONES.md)
> (« Restes de donnees ») : blocs `mobs:`/`pnjs:` du Marais Brumeux et de la Crete de Ventombre,
> illustrations de zone (`Zone::illustrationPath` jamais lu ni renseigne).

> **ZON-27 sous-jalon b livre le 2026-07-25** (voir `ROADMAP_DONE.md`) : dialogue PNJ
> server-rendered (`/game/pnj/{id}/talk`), accessible depuis l'ecran de zone, emettant
> `PnjDialogEvent` — les objectifs de quete `talk_to` progressent a nouveau. Meme regle de zone que
> la boutique. **`KNOWN_ORPHANS` est desormais vide** : plus aucun evenement de domaine sans emetteur.
>
> **Hors perimetre** : les actions de choix autres que `open_shop` et l'avancee au noeud suivant
> (declenchement de quete, branchements conditionnels) ne sont pas cablees — le dialogue affiche le
> texte et enchaine les noeuds. A instruire avec le contenu narratif si le besoin se confirme.

---

### Definition of Done

- [x] Quetes d'exploration, escorte, quetes cachees, tutoriel et decouverte de region fonctionnels
      en modele zone (ZON-22)
- [x] Boucle de jeu principale (zone → action → combat) couverte en E2E dans la CI (ZON-23)
- [x] Aucun evenement de domaine sans emetteur — liste `KNOWN_ORPHANS` vide
- [x] PNJ joignables depuis la zone : boutiques (ZON-27a) et dialogues (ZON-27b)
- [x] Scenarios k6 mesurant des routes reellement servies (ZON-24)
- [x] World 1 sur un graphe dense : anneau + tables enrichies (ZON-26a) ; **population de zone
      declarative** (ZON-26b-a) — une zone se peuple desormais sans carte d'origine
- [x] `pnjs:` declaratifs (ZON-26b-b) : `Pnj::slug` comme cle d'idempotence, bloc `pnjs:` dans
      le format de zone, deux habitants livres sur les **Dunes d'Ambre** — zone sans carte
      d'origine. Les 7 fixtures historiques ne sont **pas** migrees : elles fonctionnent, et les
      reecrire serait du risque pur pour aucun gain. Les arbres de dialogue restent aux fixtures.

**Sprint 13 : 8/8 — la dette du pivot est soldee.**
