## Sprint 15 — Commandes de craft (Piste C)

> **6 jalons** (ECO-05 → ECO-09, ECO-20), **2 livrees** | Priorite : **Haute** | Origine : [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md) Piste C
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

### ECO-07 — Execution de commande & commande directe (M | ★★★ | HAUTE)
> Le craft consomme l'escrow, respecte le time-gating, livre au client.
> Prerequis : ← ECO-06
- [ ] Execution : consomme les materiaux **en escrow** (pas ceux de l'artisan), applique
      `craftingTime` (time-gating reel), produit `result`
- [ ] Livraison : objet au commanditaire, commission (moins taxe region) a l'artisan
- [ ] **Commande directe** : le client cible un artisan precis
- [ ] Taxe de region sur la commission → guilde controlante (coherence ECO-04, reutiliser
      `AuctionSettlement` ou son equivalent)
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

### ECO-20 — Les deblocages de recettes des arbres ne gardent rien (M | ★★★ | HAUTE)
> **Defaut trouve pendant ECO-06.** Ni une regression ni une dette de conception : un gardien
> ecrit dans les donnees et jamais branche.
> Prerequis : ∅ (independant de la Piste C, mais il en conditionne le sens)
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

---

### Definition of Done

- [x] Escrow des deux cotes, restitution integrale a l'annulation (ECO-05)
- [x] Un artisan qualifie peut **prendre** une commande de sa region (ECO-06)
- [ ] Un artisan qualifie peut **honorer** une commande de sa region
- [ ] La commission est taxee comme une vente, au profit de la guilde controlante
- [ ] Les objets lies naissent lies a leur commanditaire, et la reputation d'artisan existe
- [ ] Escrow restitue automatiquement a l'expiration
- [ ] La qualification d'un artisan repose sur un gardien **branche** (ECO-20)

---

### Suite (sprint ulterieur)

`ECO-10` → `ECO-13` (echoppes joueur, ← tache 129 housing), `ECO-15` (gold sinks),
`ECO-17` (tests). Detail dans [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md).
