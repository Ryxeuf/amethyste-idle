## Sprint 14 — Economie joueur (socle)

> **9 jalons** (ECO-01 → ECO-04, ECO-14, ECO-16a/b, ECO-18, ECO-19), **9 livrees** — sprint complet | Priorite : **Haute** | Origine : [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md), decline de [GAME_PRINCIPLES.md](../GAME_PRINCIPLES.md) §4
> Objectif : poser le socle de l'economie de production joueur — liaison des objets, plancher T1
> anti cold-start, et hotel des ventes **regional** branche sur le controle de cite.
> Prerequis : Sprint 5 ✅ (HV), GCC ✅ (controle de cite), Sprints 7-10 ✅ (modele zone / regions)

> **Pourquoi maintenant** : c'etait le plus gros chantier structure encore **entierement a faire**
> (17 jalons ECO — **tous livres depuis** : ECO-01 → ECO-17 ✅, Sprints 14/15 et suites), et le
> seul qui donne au monde persistant sa profondeur systemique — la
> raison d'etre affichee du pivot PBBG. La narration (NAR, 14/14 ✅) et le modele zone (ZON, 21/21 ✅)
> sont termines ; l'economie est le pilier restant.
>
> Ce sprint couvre les **Pistes A, B** du plan + les jalons transverses immediatement utiles.
> Les Pistes C (commandes de craft), D (echoppes) et le reste de E font l'objet d'un sprint suivant —
> les echoppes dependent du housing (tache 129, Sprint 11).

---

> **ECO-01 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : enum `BindType`
> (`none` / `bind_on_equip` / `bind_on_pickup`), colonne `game_items.bind_type` remplacant le booleen
> `bound_to_player` (migration avec backfill), liaison au premier equipement dans `GearSetter`, et
> **garde-fou cote service** dans `AuctionManager::createListing` — le formulaire filtrait deja les
> objets liables, mais rien n'empechait une requete forgee de mettre en vente un objet lie.
>
> **Decision** : la liaison effective reste portee par `PlayerItem::boundToPlayerId` (deja en place
> et deja consommee par `isExchangeable()`), plutot que d'ajouter un flag `bound` redondant.

> **ECO-02 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : l'audit a trouve l'artisanat
> **entierement inaccessible**, par quatre defauts independants et tous silencieux — 7 recettes
> de niveau 1 sur 13 sans ingredient obtenable, aucun skill d'artisanat n'accordant `equip.tool`,
> aucun outil de craft vendu par un PNJ visible depuis une zone, et 2 metiers sur 4 dont le skill
> d'entree pointait vers une recette inexistante. Les quatre etages du plancher sont desormais
> poses et verrouilles par `ColdStartFloorTest`.

> **ECO-03 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : segmentation **stricte** actee
> (decision **D13**), `AuctionListing.region` figee au depot, marche local par defaut, garde-fou
> de service sur l'achat et la mise. Le transport n'est pas un systeme a part : c'est le voyage.
> Deux corrections de fond au passage — la region se lisait sur `Player::map`, que le pivot ne met
> plus a jour (la taxe suivait le vendeur au lieu de rester au marche), et **quatre cartes sur six
> n'appartenaient a aucune region**, ce qui rendait la segmentation sans objet.

> **ECO-04 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : `AuctionSettlement` porte la
> repartition des Gils d'une vente, sous deux invariants — le vendeur ne depend jamais de
> l'identite de l'acheteur, et la ristourne membre est plafonnee par la taxe percue. Le gold sink
> existait deja mais **par accident** (les Gils se perdaient faute de destinataire) : il est
> desormais explicite, journalise et verrouille par un test.

> **ECO-14 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : l'audit trouvait **trois metiers sur
> quatre autosuffisants**, le quatrieme ne dependant d'un autre qu'a partir du niveau 6. Six
> liaisons croisees ajoutees (niveaux 2 a 5), toutes thematiquement evidentes, **aucune au palier
> d'entree** — croiser au niveau 1 aurait casse le plancher anti cold-start d'ECO-02.

> **ECO-18 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : recablages evidents faits — slugs
> fantomes **33 → 17**, recettes orphelines **37 → 1** — et progression complete des outils
> d'artisanat (bronze → fer → acier → mithril, seul le bronze etant equipable auparavant).
> `SkillRecipeConsistencyTest` croise les deux jeux de donnees dans les deux sens et verifie que
> les exceptions declarees en sont encore.

> **ECO-16a livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : les **regles**. Commerce entre
> personnages d'un meme compte refuse — le jeu autorise plusieurs personnages (regle #12) et l'HV
> ne comparait que l'identifiant de personnage, laissant un joueur blanchir objets et Gils et
> inscrire au marche des prix qu'aucune transaction reelle n'a valides. Plafond d'echanges par
> **couple** de joueurs, configurable. Escrow audite : il etait deja complet ; seul le retour
> d'objet a l'expiration reste sans test, repris en integration avec ECO-16b.

> **ECO-16b livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : journal economique avec trois
> signaux d'anomalie (couples les plus actifs, prix aberrants, volume quotidien), annulation
> d'annonce par la moderation avec remboursement de la mise en cours, et **suspension d'acces au
> marche** qui expire d'elle-meme — sanction proportionnee la ou seul le bannissement de compte
> existait.
>
> Le retour d'objet a **l'expiration** est couvert depuis le 2026-07-26 : la requete a ete
> deplacee dans `AuctionListingRepository::findExpirable()`, ou elle aurait du etre des le depart.
>
> **Reste ouvert (auditer l'escrow des commandes et des echoppes)** : transfere dans
> [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md) (« Suivis ouverts (herites des sprints clos) »).

> **ECO-19 livree le 2026-07-25** (voir `ROADMAP_DONE.md`) : les **17 recettes** manquantes et
> les **15 objets** resultats ecrits, `recipe-poison-vial` rattache au nœud « Concentration
> alchimique ». Les deux listes d'exception de `SkillRecipeConsistencyTest` sont **vides** :
> plus aucun slug fantome, plus aucune recette orpheline.
>
> **Choix structurant** : la transmutation alchimique devient la **seule source de mithril** du
> monde livre — aucun filon n'en donne, et cinq recettes en consomment. Le mithril devient un
> produit d'artisanat plutot que de recolte.

---

### Definition of Done

- [x] `BindType` en place, vente d'objet lie impossible a l'hotel des ventes (ECO-01) ;
      reste a etendre le garde-fou aux echoppes quand elles existeront (ECO-10)
- [x] Un nouveau joueur atteint le premier palier de craft sans dependre d'un autre joueur (ECO-02) ;
      la porte d'entree de chaque metier est ouverte, la profondeur des arbres reste a reconcilier (ECO-18)
- [x] HV segmente par region, taxe reversee a la guilde controlante — ou detruite quand
      aucune guilde ne controle la region (ECO-03, ECO-04)
- [x] Chaine de production documentee, aucun metier autosuffisant (ECO-14)
- [x] Escrow operationnel, regles anti-abus posees (ECO-16a), journal economique et outils
      de moderation livres (ECO-16b) ; le retour d'objet a l'expiration est couvert
- [x] Arbres de talent et recettes reconcilies dans les deux sens (ECO-18, ECO-19)

---

### Suite (sprint ulterieur)

`ECO-05` → `ECO-09` (commandes de craft — **pilier endgame**, produit le stuff lie),
`ECO-10` → `ECO-13` (echoppes joueur, ← tache 129 housing), `ECO-15` (gold sinks), `ECO-17` (tests).
Detail dans [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md).
