## Vague 9 — Economie & social

> **10 taches** pour enrichir l'economie et les interactions sociales.
> Prerequis : Vague 8 (contenu critique) recommandee avant.

---

### Piste A — Commerce entre joueurs (sequentiel)

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

### Piste B — Social avance (parallelisable)

### 119 — Messagerie joueur a joueur (M | ★★)
> Prerequis : ∅
- [ ] Entite `PrivateMessage` : sender, receiver, subject, body, readAt, createdAt
- [ ] Page `/game/messages` : boite de reception, envoi, lu/non-lu
- [ ] Notification Mercure SSE a la reception
- [ ] Limite : 100 messages conserves par joueur
- [ ] Blocage de joueur (ignore list)

### 120 — Profil public joueur (S | ★★) ✅
> Prerequis : ∅
- [x] Page `/game/player/{id}/profile` : avatar, race, statistiques, equipement visible, domaines
- [x] Succes affiches (selection par le joueur, top 5)
- [x] Guilde, titre, date d'inscription
- [x] Lien "Voir le profil" depuis le chat et la carte

### 121 — Systeme de reputation & karma (M | ★★)
> Prerequis : ← 120
- [ ] Score de reputation (incremente par quetes, aide groupe, evenements)
- [ ] Titres de reputation (Novice → Respecte → Legendaire)
- [ ] Malus si comportement negatif (report systeme basique)
- [ ] Bonus reputation : acces a des quetes speciales, reductions PNJ

---

### Piste C — Contenu economique (parallelisable)

### 122 — Metiers specialises (2e tier) (M | ★★)
> Prerequis : ← 145
- [ ] Specialisations de craft : Maitre Forgeron, Maitre Alchimiste, Maitre Joaillier
- [ ] Recettes exclusives par specialisation (items tier 4+)
- [ ] Bonus qualite +20% si specialise
- [ ] 1 specialisation par joueur (choix irreversible, deblocage a domain XP 500+)

### 123 — Encheres temporaires & ventes flash (S | ★)
> Prerequis : ← 116
- [ ] Type d'annonce "enchere" : prix de depart, increments, duree fixe
- [ ] Notification aux encherisseurs si depasses
- [ ] Ventes flash admin : items rares a prix reduit, duree limitee

### 124 — Taxes dynamiques & tresor regional (S | ★★)
> Prerequis : ← GCC ✅
- [ ] Taux de taxe ajustable par la guilde controlante (1% a 10%)
- [ ] Tresor regional visible par tous les membres
- [ ] Investissements automatiques (buff zone) si tresor > seuil

### 125 — Gold sinks avances (S | ★★)
> Prerequis : ∅
- [ ] Enchantement temporaire d'equipement (coute gils, duree limitee)
- [ ] Renommage d'items (cosmetique, coute gils)
- [ ] Transport rapide payant entre villes decouvertes
- [ ] Repair d'equipement (degradation naturelle sur mort)

---
