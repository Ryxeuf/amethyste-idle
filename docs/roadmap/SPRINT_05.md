## Sprint 5 — Hotel des ventes

> **3 taches** | Priorite : **Moyenne** | Origine : Vague 9, Piste A
> Objectif : implementer le systeme d'hotel des ventes complet (backend, UI, anti-exploit).
> Prerequis : Sprint 4 recommande (contenu a vendre)

---

### 116 — Hotel des ventes — entites & backend (L | ★★★)
> Prerequis : ∅
- [ ] Entite `AuctionListing` : seller, item, quantity, pricePerUnit, expiresAt, status (active/sold/expired/cancelled)
- [ ] Entite `AuctionTransaction` : listing, buyer, totalPrice, purchasedAt
- [ ] `AuctionManager` : createListing, buyListing, cancelListing, expireListings
- [ ] Taxe de mise en vente (5% du prix), taxe regionale si region controlee par une guilde
- [ ] Commande CRON `app:auction:expire` pour expirer les annonces depassees
- [ ] Migration + fixtures de test

### 117 — Hotel des ventes — UI & recherche (M | ★★★)
> Prerequis : ← 116
- [ ] Page `/game/auction` : liste paginee avec filtres (type, rarete, prix min/max, nom)
- [ ] Recherche full-text sur le nom des items
- [ ] Page "Mes ventes" : annonces actives, historique, revenus
- [ ] Formulaire de mise en vente depuis l'inventaire (bouton "Vendre")
- [ ] Confirmation d'achat avec resume (prix + taxes)

### 118 — Hotel des ventes — anti-exploit & economie (S | ★★)
> Prerequis : ← 117
- [ ] Prix minimum et maximum par rarete (eviter les transferts deguises)
- [ ] Limite d'annonces actives par joueur (20 max)
- [ ] Cooldown entre annulation et remise en vente (5 min)
- [ ] Logs des transactions pour audit admin
- [ ] Dashboard admin : volume d'echanges, prix moyens par item

---

### Definition of Done

- [ ] Hotel des ventes fonctionnel (creation, achat, expiration)
- [ ] Interface utilisateur complete avec recherche
- [ ] Protections anti-exploit actives
- [ ] Tests d'integration du flux complet
