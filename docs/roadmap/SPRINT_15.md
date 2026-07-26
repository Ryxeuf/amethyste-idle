## Sprint 15 — Commandes de craft (Piste C)

> **7 jalons** (ECO-05 → ECO-09, ECO-20), **3 livrees** | Priorite : **Haute** | Origine : [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md) Piste C
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

### ECO-07b — Commande directe (S | ★★ | HAUTE)
> Le client cible un artisan precis, au lieu de publier au tableau.
> Prerequis : ← ECO-07a
- [ ] `CraftOrder.targetCrafter` : la commande n'est prenable que par lui
- [ ] La commande directe **ne parait pas** au tableau public (sinon elle serait les deux)
- [ ] Ecran « commandes qui me sont adressees » + choix de l'artisan au depot
- [ ] Tests

### ECO-08 — Bind-on-pickup via commande & reputation d'artisan (M | ★★★ | HAUTE)
> Le seul canal produisant du stuff lie ; l'objet nait lie au commanditaire.
> Prerequis : ← ECO-07
- [ ] Un `result` marque `bind_on_pickup` produit par commande est lie au **commanditaire**
- [ ] Reputation d'artisan : livrer des commandes l'augmente (visibilite + tarifs)
- [ ] Classement/recherche des artisans par metier et reputation
- [ ] Tests (liaison au bon joueur, montee de reputation)

### ECO-09 — Anti-abus commandes (S | ★★ | MOYENNE)
> Expiration, annulation, restitution propre de l'escrow.
> Prerequis : ← ECO-05
- [ ] Expiration commande non prise → restitution materiaux + commission (`findExpirable` et
      `releaseEscrow` existent deja, reste la commande planifiee)
- [x] Annulation par le client tant que `open` (non `claimed`) — livre avec ECO-05
- [ ] Non-livraison dans le delai apres `claimed` → liberation + penalite reputation artisan
- [ ] Plafonds anti-farm : etendre `AuctionAntiExploit` au canal des commandes (ECO-16b l'avait
      laisse ouvert, ce canal n'existant pas encore)
- [ ] Tests

### ECO-20 — Donnees d'artisanat declaratives qui ne gardent rien (M | ★★★ | HAUTE)
> **Trois defauts trouves pendant ECO-06 et ECO-07.** Ni des regressions ni des dettes de
> conception : des regles ecrites dans les donnees, affichees au joueur, et jamais branchees.
> Prerequis : ∅ (independant de la Piste C, mais il en conditionne le sens)

**1. Les deblocages de recettes des arbres**
- [ ] `PlayerActionHelper::getActions()` ne traite specifiquement que `tool_slot.unlock` (lit `slot`)
      et `equip.tool` (lit `slugs`) ; **toute autre cle lit `spots`**. Une action `craft` porte
      `recipes` → elle contribue un tableau **vide**. Ajouter la branche `craft` (lecture de
      `recipes`) + un `getUnlockedRecipeSlugs()`
- [ ] Aucun code de `src/` ne lit `action == 'craft'`. `CraftingController` filtre les recettes
      via le seul `CraftingManager::getAvailableRecipes()` (niveau de metier + specialisation) :
      **les ~60 nœuds d'arbre qui « debloquent » des recettes ne debloquent rien aujourd'hui**
- [ ] Decider et appliquer la regle : soit le skill devient un **prerequis reel** (et il faut
      verifier qu'aucune recette ne devienne inatteignable — ECO-18/19 ont reconcilie les slugs,
      pas les chemins), soit les `actions.craft` sont **retirees des fixtures** et le niveau de
      metier reste le seul gardien assume
- [ ] Repercuter la decision dans `CraftOrderManager::assertQualified()` (aligne aujourd'hui sur
      `CraftingManager`, faute de gardien cote skills) et dans `docs/GAME_PRINCIPLES.md`
- [ ] Tests : un garde-fou du type `SkillRecipeConsistencyTest` verifiant que chaque recette citee
      par un skill reste atteignable par un chemin d'arbre valide

**2. `Recipe.craftingTime` a l'etabli** (trouve pendant ECO-07a)
- [ ] Le champ est affiche dans `_recipe_card.html.twig` et `_recipe_card_locked.html.twig`
      (« Temps : 5s ») et **aucun code ne le lit** : `CraftingManager::craft()` consomme et
      produit dans la meme requete. Le craft direct est instantane
- [ ] ECO-07a l'a rendu reel **cote commandes** (`CraftOrder.readyAt`) : l'etabli et les
      commandes ont donc desormais deux regimes de temps differents
- [ ] Decider : temporiser aussi l'etabli (ce qui touche la boucle de jeu de l'ecran
      d'artisanat, d'ou le refus de l'elargir a ECO-07a) ou retirer l'affichage trompeur

**3. La qualite de craft ne survit pas au craft** (trouve pendant ECO-07a)
- [ ] `QualityCalculator` calcule une qualite, `CraftingManager::craft()` la met dans son
      message de retour, et **`PlayerItem` n'a pas de champ qualite** : elle est perdue
- [ ] Consequence directe : `CraftOrder.minQuality` est **inapplicable** — ECO-07a ne le
      verifie pas, faute de pouvoir lire la qualite du resultat
- [ ] Persister la qualite sur `PlayerItem` (et la refleter sur les stats) ou retirer les deux
      champs

---

### Definition of Done

- [x] Escrow des deux cotes, restitution integrale a l'annulation (ECO-05)
- [x] Un artisan qualifie peut **prendre** une commande de sa region (ECO-06)
- [x] Un artisan qualifie peut **honorer** une commande de sa region (ECO-07a)
- [x] La commission est taxee comme une vente, au profit de la guilde controlante (ECO-07a)
- [ ] Les objets lies naissent lies a leur commanditaire, et la reputation d'artisan existe
- [ ] Escrow restitue automatiquement a l'expiration
- [ ] La qualification d'un artisan repose sur un gardien **branche** (ECO-20)

---

### Suite (sprint ulterieur)

`ECO-10` → `ECO-13` (echoppes joueur, ← tache 129 housing), `ECO-15` (gold sinks),
`ECO-17` (tests). Detail dans [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md).
