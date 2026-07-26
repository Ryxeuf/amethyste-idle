## Sprint 15 — Commandes de craft (Piste C)

> **8 jalons** (ECO-05 → ECO-09, ECO-20), **7 livrees + ECO-20a/b** | Priorite : **Haute** | Origine : [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md) Piste C
> Objectif : le **troisieme canal d'echange**, et le seul qui produise du stuff **lie**.
> Prerequis : Sprint 14 ✅ (socle economie joueur complet, 9/9)

> **Pourquoi c'est le pilier endgame** : l'hotel des ventes echange des commodites — tout le monde
> peut vendre la meme chose, et le prix est la seule variable. La commande de craft echange un
> **service** : le commanditaire apporte matiere et argent, l'artisan apporte le plan et le
> savoir-faire. C'est le seul canal ou la competence d'un joueur a une valeur nommee, et le seul
> qui produise du bind-on-pickup (decision **D5**).

---

> **ECO-05 livree le 2026-07-26** (voir `ROADMAP_DONE.md`) : entite `CraftOrder`, enum
> `CraftOrderStatus`, et **escrow des deux cotes des la creation** — les materiaux quittent
> l'inventaire, la commission quitte la bourse. La couverture des materiaux est verifiee **au
> depot** et non a l'execution : un artisan qui prend une commande doit pouvoir la realiser.

> **ECO-06 livree le 2026-07-26** (voir `ROADMAP_DONE.md`) : tableau regional public,
> `claimOrder()` avec verrou anti-double-prise, et une **qualification alignee sur le seul gardien
> reel** (niveau de metier + specialisation). L'audit a montre que les deblocages de recettes des
> arbres de talent **ne gardent rien** — d'ou le jalon **ECO-20** ci-dessous.

> **ECO-07a livree le 2026-07-26** (voir `ROADMAP_DONE.md`) : execution, livraison et
> repartition de la commission. `craftingTime` devient une **attente reelle** — il n'etait
> applique nulle part jusqu'ici. `AuctionSettlement` est reutilise tel quel : un canal qui
> taxerait differemment deviendrait le canal ou l'on evite la taxe de l'autre.

> **ECO-07b livree le 2026-07-26** (voir `ROADMAP_DONE.md`) : `CraftOrder.targetCrafter`, exclusion
> du tableau public, section « commandes qui vous sont adressees » dans l'atelier. Les refus de la
> prise en charge sont appliques **au depot** : sans cela, le commanditaire immobiliserait son
> escrow pour une commande que l'artisan vise ne pourra jamais prendre.

> **ECO-08a livree le 2026-07-26** (voir `ROADMAP_DONE.md`) : l'objet nait lie **au
> commanditaire**, pose explicitement a la livraison — `InventoryHelper` lie au joueur de la
> session, qui est l'artisan. Six resultats de haut palier (un par metier) passent
> `bind_on_pickup`, sans quoi le mecanisme n'aurait rien a lier.

> **ECO-08b livree le 2026-07-26** (voir `ROADMAP_DONE.md`) : `CrafterReputation` **par metier**,
> gagnee a la seule livraison et ponderee par le palier de la recette. Ecran de classement des
> artisans, qui donne son sens a la commande directe — sans lui, nommer un artisan supposerait de
> le connaitre deja.

> **ECO-09 livree le 2026-07-26** (voir `ROADMAP_DONE.md`) : commande `app:craft-order:expire`,
> fenetre de livraison de 24 h comptee depuis la prise en charge, sanction de reputation en cas de
> non-livraison, et plafond par couple commanditaire/artisan. `findExpirable()` et
> `releaseEscrow()` existaient depuis ECO-05 **sans que rien ne les appelle** : l'escrow n'avait
> aucune sortie automatique.

### ECO-20 — Donnees d'artisanat declaratives qui ne gardent rien (M | ★★★ | HAUTE)
> **Trois defauts trouves pendant ECO-06 et ECO-07.** Ni des regressions ni des dettes de
> conception : des regles ecrites dans les donnees, affichees au joueur, et jamais branchees.
> Prerequis : ∅ (independant de la Piste C, mais il en conditionne le sens)

**1. Les deblocages de recettes des arbres** — ✅ **livre le 2026-07-26** (ECO-20b, option A)
- [x] `PlayerActionHelper` lit enfin le champ `recipes` des actions `craft` ; le champ lu depend
      desormais explicitement de la cle (`slot` / `slugs` / `recipes` / `spots`)
- [x] `CraftingManager::isRecipeUnlocked()` : trois gardiens — niveau de metier, specialisation,
      **plan appris**. Filtrage de l'ecran **et** verification a l'execution de `craft()`, qui ne
      controlait meme pas le niveau
- [x] `CraftOrderManager::assertQualified()` s'appuie enfin sur le vrai gardien
- [x] `RecipeUnlockCatalog` : une recette qu'aucun arbre ne revendique reste gatee par les deux
      premiers gardiens seulement — brancher le gardien ne doit jamais rendre une recette
      inatteignable
- [x] Compensation : `respec_count` remis a zero, le respec existant permettant de se reorienter
      au tarif de base
- [x] Fixtures : les joueurs de demo recoivent les nœuds d'entree des quatre metiers (gratuits,
      0 point) — sans eux, un personnage ne voit plus **aucune** recette
- [ ] **Suivi onboarding** : rien dans le tutoriel n'oriente un nouveau joueur vers ces nœuds
      d'entree. Ils sont gratuits, donc personne n'est bloque, mais l'artisanat n'est plus
      decouvert par accident — a rapprocher du plancher T1 d'ECO-02

**2. `Recipe.craftingTime` a l'etabli** (trouve pendant ECO-07a)
- [ ] Le champ est affiche dans `_recipe_card.html.twig` et `_recipe_card_locked.html.twig`
      (« Temps : 5s ») et **aucun code ne le lit** : `CraftingManager::craft()` consomme et
      produit dans la meme requete. Le craft direct est instantane
- [ ] ECO-07a l'a rendu reel **cote commandes** (`CraftOrder.readyAt`) : l'etabli et les
      commandes ont donc desormais deux regimes de temps differents
- [ ] Decider : temporiser aussi l'etabli (ce qui touche la boucle de jeu de l'ecran
      d'artisanat, d'ou le refus de l'elargir a ECO-07a) ou retirer l'affichage trompeur

**3. La qualite de craft ne survit pas au craft** — ✅ **livre le 2026-07-26** (ECO-20a)
- [x] `PlayerItem.craftQuality` : la qualite calculee est conservee, a l'etabli **et** sur
      commande, par la meme formule (`CraftingManager::computeQuality()`)
- [x] `CraftOrder.minQuality` devient applicable : une piece sous le seuil est **retravaillee**,
      pas refusee, et le champ est enfin expose au depot
- [ ] Reste ouvert : refleter la qualite sur les **stats** de l'objet (changement d'equilibrage,
      volontairement hors de ce jalon)

---

### Definition of Done

- [x] Escrow des deux cotes, restitution integrale a l'annulation (ECO-05)
- [x] Un artisan qualifie peut **prendre** une commande de sa region (ECO-06)
- [x] Un artisan qualifie peut **honorer** une commande de sa region (ECO-07a)
- [x] La commission est taxee comme une vente, au profit de la guilde controlante (ECO-07a)
- [x] Les objets lies naissent lies a leur commanditaire (ECO-08a)
- [x] La reputation d'artisan existe (ECO-08b)
- [x] Escrow restitue automatiquement a l'expiration (ECO-09)
- [x] La qualification d'un artisan repose sur un gardien **branche** (ECO-20b)

---

### Suite (sprint ulterieur)

`ECO-10` → `ECO-13` (echoppes joueur, ← tache 129 housing), `ECO-15` (gold sinks),
`ECO-17` (tests). Detail dans [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md).
