## Sprint 14 — Economie joueur (socle)

> **6 jalons** (ECO-01 → ECO-04, ECO-14, ECO-16), **1 livree** | Priorite : **Haute** | Origine : [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md), decline de [GAME_PRINCIPLES.md](../GAME_PRINCIPLES.md) §4
> Objectif : poser le socle de l'economie de production joueur — liaison des objets, plancher T1
> anti cold-start, et hotel des ventes **regional** branche sur le controle de cite.
> Prerequis : Sprint 5 ✅ (HV), GCC ✅ (controle de cite), Sprints 7-10 ✅ (modele zone / regions)

> **Pourquoi maintenant** : c'est le plus gros chantier structure encore **entierement a faire**
> (17 jalons ECO, 0 livre), et le seul qui donne au monde persistant sa profondeur systemique — la
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

### ECO-02 — Plancher T1 PNJ & kit d'onboarding (S | ★★ | **CRITIQUE**)
> Anti cold-start : aucun joueur jamais hard-bloque par un marche joueur vide.
> Prerequis : ← ECO-01
- [ ] Audit : lister le T1 de survie (outils de base, potions/consommables de base)
- [ ] Garantir la disponibilite PNJ **ou** le loot garanti du tutoriel pour ce T1
- [ ] Marquer le T1 comme echangeable (`BindType::None`)
- [ ] Verifier qu'un nouveau joueur peut progresser jusqu'au premier palier de craft **sans**
      dependre d'un autre joueur (l'arc d'intro NAR-03/04 sert de scenario de reference)

### ECO-03 — HV regional — segmentation par region (M | ★★★ | HAUTE)
> La geographie compte : arbitrage, transport = temps de voyage.
> Prerequis : ← modele zone ✅, socle HV (Sprint 5) ✅
- [ ] `AuctionListing` rattache a une region (via la zone du vendeur au moment du depot)
- [ ] Recherche/consultation HV filtree par region (marche local par defaut)
- [ ] Decision a acter : segmentation stricte vs marche global taxe (cf. GAME_PRINCIPLES §6)
- [ ] Transport de marchandises entre regions = cout de voyage/energie (reutilise le graphe)
- [ ] Tests

### ECO-04 — Taxe HV → tresor de guilde controlante (S | ★★ | HAUTE)
> Branche l'HV sur le controle de cite (le champ `region_tax_rate` existe deja).
> Prerequis : ← ECO-03, GCC-10/GCC-11 ✅
- [ ] A la vente, `region_tax_rate` prelevee → `gilsTreasury` de la guilde controlante
      (reutiliser `RegionBonusProvider` / la logique de taxe GCC-11)
- [ ] Reduction membre appliquee si acheteur dans la guilde controlante (coherence GCC)
- [ ] Aucune guilde controlante → taxe conservee comme gold sink (destruction de gils)
- [ ] Tests

### ECO-14 — Interdependance des metiers (S | ★★ | MOYENNE)
> Aucun metier autosuffisant : chaque metier consomme la sortie d'un autre.
> Prerequis : ∅ (parallelisable) — pose les bases de la demande avant les commandes de craft (ECO-05+)
- [ ] Audit des recettes : identifier les metiers autosuffisants
- [ ] Reequilibrer les `ingredients` pour croiser les metiers
- [ ] Documenter la chaine de production dans `docs/BALANCE.md`

### ECO-16 — Moderation economique (S | ★★ | HAUTE)
> Anti price-fixing, farm par alts, RMT — a poser **avant** l'ouverture des canaux joueur.
> Prerequis : ← ECO-03
- [ ] Escrow systeme garanti sur tous les canaux (HV d'abord, commandes/echoppes ensuite)
- [ ] Reutiliser les patterns anti-exploit influence (plafonds, diminishing returns)
- [ ] Journal/analytics des transactions pour detection d'anomalies
- [ ] Outils de moderation admin (annulation de listing, suspension)

---

### Definition of Done

- [x] `BindType` en place, vente d'objet lie impossible a l'hotel des ventes (ECO-01) ;
      reste a etendre le garde-fou aux echoppes quand elles existeront (ECO-10)
- [ ] Un nouveau joueur atteint le premier palier de craft sans dependre d'un autre joueur
- [ ] HV segmente par region, taxe reversee a la guilde controlante (ou detruite)
- [ ] Chaine de production documentee, aucun metier autosuffisant
- [ ] Escrow + journalisation des transactions operationnels

---

### Suite (sprint ulterieur)

`ECO-05` → `ECO-09` (commandes de craft — **pilier endgame**, produit le stuff lie),
`ECO-10` → `ECO-13` (echoppes joueur, ← tache 129 housing), `ECO-15` (gold sinks), `ECO-17` (tests).
Detail dans [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md).
