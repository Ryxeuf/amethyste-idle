## Sprint 6 — Social & Economie

> **6 taches** | Priorite : **Moyenne** | Origine : Vague 9, Pistes B & C
> Objectif : enrichir les interactions sociales et l'economie du jeu.
> Prerequis : Sprint 5 (hotel des ventes pour certaines taches economiques)

---

### Piste A — Social avance

### ~~119 — Messagerie joueur a joueur (M | ★★)~~ ✅
> Prerequis : ∅
- [x] Entite `PrivateMessage` : sender, receiver, subject, body, readAt, createdAt
- [x] Page `/game/messages` : boite de reception, envoi, lu/non-lu
- [x] Notification Mercure SSE a la reception
- [x] Limite : 100 messages conserves par joueur
- [x] Blocage de joueur (ignore list)

### 121 — Systeme de reputation & karma (M | ★★)
> Prerequis : ← 120 ✅ (profil public joueur)
- [x] Score de reputation (incremente par quetes, aide groupe, evenements) — sous-phase 1 : quetes + succes
- [x] Titres de reputation (Novice → Respecte → Legendaire) — 6 paliers (Novice, Connu, Respecte, Honore, Illustre, Legendaire)
- [ ] Malus si comportement negatif (report systeme basique)
- [ ] Bonus reputation : acces a des quetes speciales, reductions PNJ

---

### Piste B — Contenu economique

### 122 — Metiers specialises (2e tier) (M | ★★)
> Prerequis : ← 145 (recettes craft)
- [ ] Specialisations de craft : Maitre Forgeron, Maitre Alchimiste, Maitre Joaillier
- [ ] Recettes exclusives par specialisation (items tier 4+)
- [ ] Bonus qualite +20% si specialise
- [ ] 1 specialisation par joueur (choix irreversible, deblocage a domain XP 500+)

### 123 — Encheres temporaires & ventes flash (S | ★)
> Prerequis : ← 116 (hotel des ventes)
- [ ] Type d'annonce "enchere" : prix de depart, increments, duree fixe
- [ ] Notification aux encherisseurs si depasses
- [ ] Ventes flash admin : items rares a prix reduit, duree limitee

### ~~124 — Taxes dynamiques & tresor regional (S | ★★)~~ ✅
> Prerequis : ← GCC ✅ (controle de cite)
- [x] Taux de taxe ajustable par la guilde controlante (1% a 10%)
- [x] Tresor regional visible par tous les membres
- [x] Investissements automatiques (buff zone) si tresor > seuil

### ~~125 — Gold sinks avances (S | ★★)~~ ✅
> Prerequis : ∅
- [x] Enchantement temporaire d'equipement (coute gils, duree limitee)
- [x] Renommage d'items (cosmetique, coute gils)
- [x] Transport rapide payant entre villes decouvertes
- [x] Repair d'equipement (degradation naturelle sur mort)

---

### Definition of Done

- [ ] Messagerie fonctionnelle avec notifications temps reel
- [ ] Systeme de reputation avec titres et bonus
- [ ] Specialisations de metier operationnelles
- [ ] Encheres et taxes dynamiques actives
- [ ] Gold sinks implementes et equilibres
