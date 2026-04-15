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

### ~~121 — Systeme de reputation & karma (M | ★★)~~ ✅
> Prerequis : ← 120 ✅ (profil public joueur)
- [x] Score de reputation (incremente par quetes, aide groupe, evenements) — sous-phase 1 : quetes + succes
- [x] Titres de reputation (Novice → Respecte → Legendaire) — 6 paliers (Novice, Connu, Respecte, Honore, Illustre, Legendaire)
- [x] Malus si comportement negatif (report systeme basique) — entite `PlayerReport`, formulaire profil joueur, admin `/admin/reports`, malus -50 renommee a la validation
- [x] Bonus reputation : acces a des quetes speciales (champ `minRenownScore` sur `Quest`, filtrage `getAvailableQuests`, blocage `accept`, badge palier dans le template)
- [x] Bonus reputation : reductions marchand PNJ (0% Novice → 10% Legendaire, cumul plafonne avec guilde)

---

### Piste B — Contenu economique

### ~~122 — Metiers specialises (2e tier) (M | ★★)~~ ✅
> Prerequis : ← 145 (recettes craft)
- [x] Specialisations de craft : Maitre Forgeron, Maitre Alchimiste, Maitre Joaillier (+ Maitre Tanneur pour symetrie) — enum `CraftSpecialization`, sous-phase 1
- [x] Recettes exclusives par specialisation (items tier 4+) — champ `requiredSpecialization` sur `Recipe`, filtrage dans `CraftingManager::getAvailableRecipes/getLockedRecipes`, garde-fou dans `craft()`, 4 items + 4 recettes Maitre (lame, manteau drake, grand elixir, anneau astral), badges dans templates, sous-phase 2
- [x] Bonus qualite +20% si specialise — `QualityCalculator` etend `calculateQuality()` avec parametre `specializationBonus`, sous-phase 1
- [x] 1 specialisation par joueur (choix irreversible, deblocage a domain XP 500+) — champ `craftSpecialization` sur Player, `CraftSpecializationService::canChoose/choose`, UI dans la page craft, sous-phase 1

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
