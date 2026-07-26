## Sprint 11 — Monde vivant

> **6 taches** (2 quasi-livrees, 4 avec du reste-a-faire) | Priorite : **Basse** | Origine : Vague 10, Pistes A & B — adapte au pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : etendre le monde avec de nouvelles zones de contenu, du housing, des montures et des events live.
> Prerequis : Sprints 7-10 ✅ (modele zone, energie, evenements, contenu de groupe)

> **Menage 2026-07-25** : le detail des sous-phases **livrees** a ete retire de ce fichier
> (regle projet #13). Reference : [`ROADMAP_DONE.md`](../ROADMAP_DONE.md) ; forme d'origine
> conservee dans [`ARCHIVE_SPRINT_11_12.md`](ARCHIVE_SPRINT_11_12.md).

---

### Deja livre (resume)

| Tache | Etat | Livre |
|-------|------|-------|
| **130 — Montures & deplacement rapide** | 90 % | Catalogue `Mount` + UI `/game/mounts` (badge « Possedee »), ownership `PlayerMount`, obtention achat/quete/drop, activation + `speedBonus`, teleportation entre villes decouvertes. Sous-phase 4b (rendu PixiJS) **annulee par le pivot**. |
| **131 — Events live & outils GM** | 80 % | Type `gathering_bonus` (+ extension aux 3 managers de recolte), historique `/admin/events/history`, annonce globale Mercure. |
| **132 — Classement saisonnier global** | 95 % | `/game/rankings` (kills / quetes / XP), archivage par `InfluenceSeason`, titres de podium + affichage (classement & profil), Hall of Fame `/game/rankings/history`, recompenses cosmetiques. |
| **133 — Mini-jeux** | 50 % | Peche active (mini-jeu de timing, zone parfaite, bonus XP, i18n). |

---

### Piste A — Contenu monde

### 128 — Nouvelles zones — Acte 4 (XL | ★★★)
> Prerequis : ← 94 (Acte 3 termine) ✅, ← 141 (monstres tier 2-3) ✅, ← ZON-11 (config declarative) ✅
> **Note post-pivot** : depend du volume de contenu de zone (cf. Sprint 13, ZON-26). Le graphe
> actuel ne compte que **5 zones / 6 connexions** — l'Acte 4 doit s'appuyer sur un World 1 densifie.
- [ ] 4 nouvelles zones de contenu via la configuration declarative (`config/game/zones/*.yaml`)
- [ ] Nouveaux biomes : desert, tundra (illustrations de zone + tables dediees)
- [ ] Monstres tier 4 (level 30-40)
- [ ] Chaine de quetes Acte 4 (arc narratif dedie via `storyArc`, cf. NAR-01 ✅)
- [ ] Boss final Acte 4 (evenement de zone + `ZoneBoss`, cf. ZON-18 ✅)

### 129 — Housing joueur (L | ★★) — **decoupe en 5 sous-jalons** (regle #8)
> Prerequis : ← 116 (hotel des ventes) ✅
> **Note** : prerequis de **ECO-10** (echoppes joueur) — cf. [PLAN_PLAYER_ECONOMY.md](PLAN_PLAYER_ECONOMY.md).

**Constat d'audit (2026-07-26)** : le **coffre prive existe deja** — chaque personnage recoit un
inventaire `Inventory::TYPE_BANK` de 1000 emplacements a sa creation (`PlayerFactory`). L'item de
la roadmap est donc sans objet sous cette forme ; il devient « le coffre devient accessible
**depuis la demeure** ». Deux modeles reutilisables reperes : `PlayerExpedition` /
`ExpeditionService` (demarrer / attendre / recolter) pour le **jardin**, et `Zone::TYPE_INTERIOR`
pour rendre les demeures **visitables** par le graphe.

**HOU-01 — Terrain & demeure** ✅ **livre le 2026-07-26** (voir `ROADMAP_DONE.md`)
- [x] Zone residentielle `quartier-des-jardins` (declarative, a 60 s du hub)
- [x] `PlayerHouse` : une demeure par **personnage** (regle #12), rattachee a une zone (regle #7)
- [x] Achat **sur place**, 25 000 Gils = gold sink (GAME_PRINCIPLES §4.7)
- [x] Ecran de la demeure, renommage, liste du voisinage

**HOU-02 — Jardin : recolte passive** (M | ★★★)
- [ ] Parcelles plantables, rendement en temps reel sur le modele `PlayerExpedition`
- [ ] Recolte a la visite ; c'est le pilier PBBG de la tache

**HOU-03 — Visites** (S | ★★)
- [ ] Visiter la demeure d'un voisin depuis la zone residentielle
- [ ] Ce que le visiteur voit (nom, jardin) et ce qu'il ne touche pas

**HOU-04 — Entretien** (S | ★★)
- [ ] Loyer periodique = gold sink recurrent (GAME_PRINCIPLES §4.7), commande planifiee
- [ ] Que se passe-t-il si le loyer n'est pas paye (jamais de confiscation seche)

**HOU-05 — Meubles, coffre & atelier** (M | ★)
- [ ] Acces au coffre (`TYPE_BANK`) depuis la demeure
- [ ] Atelier : lien avec `CraftJob` (ECO-20c)
- [ ] Meubles : personnalisation visible par les visiteurs

### 130 — Montures & deplacement rapide (M | ★★) — **reste 1 item**
> Prerequis : ← ZON-06 (voyage entre zones) ✅
- [ ] **Transposer l'effet monture au modele zone** : la monture active reduit le `travel_seconds`
      des connexions du graphe (reutiliser `Mount.speedBonus`). `getEffectiveSpeed()` / `stepDelay`
      sont sans objet depuis la suppression de la carte (ZON-21).

---

### Piste B — Events & live ops

### 131 — Events live & outils GM (M | ★★★) — **reste 2 items**
> Prerequis : ← 79 ✅, ← ZON-15 (evenements de zone) ✅
- [ ] Interface admin pour lancer des events en temps reel (bouton « Lancer maintenant »)
- [ ] Type « quete ephemere » (les types boss/invasion sont couverts par `ZoneBoss` / ZON-18 ✅)

### 132 — Classement saisonnier global (M | ★★) — **quasi termine**
> Prerequis : ← 92 ✅
- [ ] Passe de verification de fin de saison sur donnees reelles (archivage + attribution des
      titres) — toutes les sous-phases fonctionnelles sont livrees.

### 133 — Mini-jeux (M | ★) — **reste 1 item**
> Prerequis : ∅
- [ ] Defis chrono asynchrones (ex-« courses entre joueurs », reformules par le pivot PBBG) :
      accomplir un parcours d'objectifs le plus vite possible, classement compare

---

### Definition of Done

- [ ] 4 nouvelles zones de contenu Acte 4 jouables
- [ ] Housing fonctionnel avec visites et jardin passif
- [ ] Montures reduisant le temps de voyage entre zones
- [ ] Events live lancables depuis l'admin
- [x] Classement saisonnier operationnel
