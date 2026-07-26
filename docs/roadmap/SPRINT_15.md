## Sprint 15 — Commandes de craft (Piste C)

> **5 jalons** (ECO-05 → ECO-09), **1 livree** | Priorite : **Haute** | Origine : [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md) Piste C
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

### ECO-06 — Tableau de commandes regional public (M | ★★ | HAUTE)
> Canal anonyme : n'importe quel artisan qualifie peut prendre la commande.
> Prerequis : ← ECO-05
- [ ] Route/UI : liste des commandes ouvertes de la region (`findOpenInRegion` existe deja)
- [ ] Filtre par metier (`Recipe.craft`) / recette
- [ ] Prise en charge : verifie plan possede + `requiredLevel` + `requiredSpecialization`
- [ ] Une commande `claimed` est reservee a l'artisan (verrou anti-double-prise)
- [ ] Tests

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

---

### Definition of Done

- [x] Escrow des deux cotes, restitution integrale a l'annulation (ECO-05)
- [ ] Un artisan qualifie peut prendre et honorer une commande de sa region
- [ ] La commission est taxee comme une vente, au profit de la guilde controlante
- [ ] Les objets lies naissent lies a leur commanditaire, et la reputation d'artisan existe
- [ ] Escrow restitue automatiquement a l'expiration

---

### Suite (sprint ulterieur)

`ECO-10` → `ECO-13` (echoppes joueur, ← tache 129 housing), `ECO-15` (gold sinks),
`ECO-17` (tests). Detail dans [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md).
