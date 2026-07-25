## Sprint 13 — Consolidation post-pivot

> **5 taches** (ZON-22 → ZON-26) | Priorite : **Critique** | Origine : dette identifiee a la cloture de la campagne ZON ([docs/ZON_CAMPAIGN_RECAP.md](../ZON_CAMPAIGN_RECAP.md) §4)
> Objectif : refermer les trous laisses par la suppression du code carte (ZON-21) — remettre en
> marche les systemes qui dependaient du deplacement, retablir la couverture de test, et donner au
> modele zone le volume de contenu qui justifie le pivot.
> Prerequis : Sprint 10 ✅ (campagne ZON-12→21 complete)

> **Pourquoi ce sprint est prioritaire** : le pivot a supprime le *dispatcher* de `PlayerMovedEvent`
> sans rebrancher ses **6 abonnes**. Plusieurs systemes de progression sont donc **inertes en
> production** (voir ZON-22). C'est une regression fonctionnelle, pas du confort.

---

### ZON-22 — Rebrancher les systemes orphelins de `PlayerMovedEvent` (M | ★★★ | **CRITIQUE**)
> Prerequis : ← ZON-21 ✅
> **Constat** : `App\Event\Map\PlayerMovedEvent` n'a **plus aucun dispatcher** depuis ZON-21b, mais
> conserve 6 abonnes. Tout ce qui se declenchait « en se deplacant » ne se declenche plus.
> Cote zone, seul `ZoneVisitedEvent` existe — et il n'est emis qu'a la **premiere decouverte**
> d'une zone (`ZoneTravelService::markZoneVisited`), pas a chaque voyage.

- [ ] Emettre un evenement de voyage a **chaque** deplacement de zone (`PlayerTraveledEvent`,
      dispatche par `ZoneTravelService::travel`), distinct de `ZoneVisitedEvent` (premiere visite)
- [ ] `QuestExploreTrackingListener` → tracker l'exploration **par zone** (`PlayerQuestUpdater::updateExplored`
      prend aujourd'hui `mapId` + coordonnees : basculer sur la zone) ; sinon les quetes d'exploration
      ne progressent plus
- [ ] `QuestEscortTrackingListener` → progression d'escorte a l'arrivee dans la zone cible
- [ ] `HiddenQuestTriggerListener` → declencheur `explore` sur l'arrivee en zone (les branches
      `MobDeadEvent` / `SpotHarvestEvent` restent valides)
- [ ] `TutorialProgressListener` → etape « se deplacer » remappee sur le voyage de zone
- [ ] `RegionDiscoveryTracker` → decouverte de region derivee de la zone d'arrivee
- [ ] `PlayerZoneSynchronizer` → statuer : le sync `carte → zone` n'a plus de sens comme source de
      verite (regle projet #7). Conserver uniquement la branche `PlayerRespawnedEvent` (toujours
      dispatchee) ou retirer le service
- [ ] Supprimer `PlayerMovedEvent` (et `App\Event\Map\` si vide) une fois les abonnes migres
- [ ] Tests : un test par systeme rebranche + un test garde-fou « aucun evenement sans dispatcher »

### ZON-23 — Couverture E2E « zone » (M | ★★ | HAUTE)
> Prerequis : ← ZON-22
> **Constat** : les E2E Panther Map/Combat/Shop ont ete **supprimes** avec ZON-21a (ils pilotaient le
> canvas PixiJS) sans remplacement. Il ne reste que 4 scenarios E2E : `Authentication`, `Craft`,
> `Inventory`, `Quest` — la boucle de jeu principale (zone → action → combat) n'est plus couverte.
- [ ] `ZoneFlowTest` : arrivee sur `/game/zone`, voyage via une connexion, cout d'energie, arrivee
- [ ] `ZoneCombatFlowTest` : action Chasser/Explorer → declenchement de combat → tour → butin
- [ ] `ZoneShopFlowTest` : acces boutique depuis une zone ville (remplace l'ex-E2E Shop)
- [ ] Reintegration dans la suite E2E de la CI

### ZON-24 — Realigner les scenarios de charge sur le modele zone (S | ★★ | HAUTE)
> Prerequis : ← ZON-21 ✅ | Bloque : **134** (objectif 200 joueurs)
> **Constat** : `scripts/load-test/` mesure encore un profil carte — le scenario `mercure-streaming`
> s'abonne par defaut au topic **`map/move`** (supprime), et le README decrit `/game/map`,
> `/api/map/cells`, `/api/map/entities`.
- [ ] `mercure-streaming` : topic par defaut → `zone/<id>/event` (ou `chat/zone/<id>`)
- [ ] `authenticated-gameplay` : boucle carte → boucle zone (voyage, explorer, chasser, inventaire)
- [ ] Mise a jour de `scripts/load-test/README.md` (sections carte) et des seuils associes
- [ ] Note de correspondance dans `docs/LOAD_TESTING_BOTTLENECKS.md` (jalon D refermé sur la carte :
      requalifier les mesures qui portaient sur les APIs map)

### ZON-25 — Nettoyage des residus carte (S | ★ | MOYENNE)
> Prerequis : ← ZON-22
- [ ] `MobRepository::findByMapWithMonster` : sans appelant depuis le retrait de `/api/map/entities`
      — supprimer ou requalifier pour le modele zone
- [ ] Auditer les champs herites `Player::getX()` / `getY()` / `coordinates` : documenter comme
      champs morts (regle #7) ou planifier leur retrait par migration
- [ ] Verifier qu'aucun autre evenement/service du domaine `Map` n'est orphelin (meme methode que
      ZON-22 : dispatcher vs abonnes)

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

---

### Definition of Done

- [ ] Aucun evenement de domaine sans dispatcher ; quetes d'exploration, escorte, tutoriel et
      decouverte de region fonctionnels en modele zone
- [ ] Boucle de jeu principale (zone → action → combat → butin) couverte en E2E dans la CI
- [ ] Scenarios k6 mesurant des routes reellement servies
- [ ] World 1 jouable de bout en bout sur un graphe de zones dense
