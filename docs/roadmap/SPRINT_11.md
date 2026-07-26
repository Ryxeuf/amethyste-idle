## Sprint 11 — Monde vivant

> **6 taches** (129, 130, 131, 132 et 133 **terminees**, reste 128) | Priorite : **Basse** | Origine : Vague 10, Pistes A & B — adapte au pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : etendre le monde avec de nouvelles zones de contenu, du housing, des montures et des events live.
> Prerequis : Sprints 7-10 ✅ (modele zone, energie, evenements, contenu de groupe)

> **Menage 2026-07-25** : le detail des sous-phases **livrees** a ete retire de ce fichier
> (regle projet #13). Reference : [`ROADMAP_DONE.md`](../ROADMAP_DONE.md) ; forme d'origine
> conservee dans [`ARCHIVE_SPRINT_11_12.md`](ARCHIVE_SPRINT_11_12.md).

---

### Deja livre (resume)

| Tache | Etat | Livre |
|-------|------|-------|
| **130 — Montures & deplacement rapide** | ✅ 100 % | Catalogue `Mount` + UI `/game/mounts` (badge « Possedee »), ownership `PlayerMount`, obtention achat/quete/drop, activation + `speedBonus`, teleportation entre villes decouvertes, reduction du temps de voyage (`MountTravelSpeed`). Sous-phase 4b (rendu PixiJS) **annulee par le pivot**. |
| **131 — Events live & outils GM** | ✅ 100 % | Type `gathering_bonus` (+ extension aux 3 managers de recolte), historique `/admin/events/history`, annonce globale Mercure. |
| **132 — Classement saisonnier global** | ✅ 100 % | `/game/rankings` (kills / quetes / XP), archivage par `InfluenceSeason`, titres de podium + affichage (classement & profil), Hall of Fame `/game/rankings/history`, recompenses cosmetiques. |
| **133 — Mini-jeux** | ✅ 100 % | Peche active (mini-jeu de timing, zone parfaite, bonus XP, i18n). |

---

### Piste A — Contenu monde

### 128 — Nouvelles zones — Acte 4 (XL | ★★★)
> Prerequis : ← 94 (Acte 3 termine) ✅, ← 141 (monstres tier 2-3) ✅, ← ZON-11 (config declarative) ✅
> **Note post-pivot** : depend du volume de contenu de zone (cf. Sprint 13, ZON-26). Le graphe
> actuel ne compte que **5 zones / 6 connexions** — l'Acte 4 doit s'appuyer sur un World 1 densifie.
**Decoupage (regle #8)** — l'audit du 2026-07-26 a montre que le bestiaire s'arretait au
niveau 24 pour les creatures normales : livrer les zones d'abord aurait donne un Acte 4
peuple de faune de tier 1-3, indiscernable de l'Acte 1. Le contenu vient donc avant la carte.

- [x] **128a — Bestiaire tier 4** (niveau 26-40) : 9 creatures, deux biomes aux profils
      opposes (le desert use, la toundra fige), table `BALANCE.md` etendue, et un garde-fou
      sur les references de sort du contenu declaratif
- [x] **128b — Les 4 zones** de contenu via `config/game/zones/*.yaml` : Mer de Sel et Cite
      Ensevelie au sud, Pas de Givre et Glacier du Silence au nord. Deux bras longs qui ne se
      rejoignent pas. **Correctif au passage** : quatre plantes nourrissaient 8 recettes
      d'alchimie de niveau 2-10 sans qu'aucune zone ne les produise — la mandragore etait meme
      exigee par une quete. Elles rejoignent les **zones de depart**, pas l'Acte 4.
- [ ] **128c — Chaine de quetes Acte 4** (arc narratif dedie via `storyArc`, cf. NAR-01 ✅)
- [ ] **128d — Boss final Acte 4** (evenement de zone + `ZoneBoss`, cf. ZON-18 ✅)

### 129 — Housing joueur ✅ **TERMINE** (L | ★★) — 5 sous-jalons (regle #8)
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

**HOU-02 — Jardin : recolte passive** ✅ **livre le 2026-07-26** (voir `ROADMAP_DONE.md`)
- [x] 4 parcelles par demeure, creees a la demande
- [x] On seme **la plante elle-meme** (le jeu n'a pas d'objet graine) : 1 unite consommee,
      2 a 3 rendues en 3 h — le jardin multiplie lentement ce qu'on possede deja
- [x] Aucune energie, aucune presence : c'est ce qui le distingue de la recolte de zone
- [ ] Reste ouvert : calibrage du rendement et de la duree dans `BALANCE.md`

**HOU-03 — Visites** ✅ **livre le 2026-07-26** (voir `ROADMAP_DONE.md`)
- [x] Visite depuis le voisinage, **gatee sur la presence dans la zone** (regle #7)
- [x] Vue en lecture seule : le visiteur voit le jardin pousser, il n'y touche pas

**HOU-04 — Entretien** ✅ **livre le 2026-07-26** (voir `ROADMAP_DONE.md`)
- [x] 500 Gils / semaine (2 % du terrain), commande `app:house:rent`
- [x] Prelevement **automatique** tant que la bourse suit
- [x] Impaye → la demeure **dort** : jardin suspendu, **rien n'est confisque ni detruit**

**HOU-05 — Meubles, coffre & atelier** ✅ **livre le 2026-07-26** (voir `ROADMAP_DONE.md`)
- [x] Coffre et atelier **rassembles** dans la demeure (etat du `TYPE_BANK`, `CraftJob` en cours)
- [x] Ameublement : enum `HouseStyle` payant (gold sink cosmetique) + devise gratuite,
      tous deux visibles des visiteurs
- [ ] Reste ouvert : un vrai systeme de mobilier (objets, emplacements, rendu) est un chantier
      a lui seul, avec tout son contenu a inventer — le style en tient lieu

**Tache 129 : 5/5 — le housing est complet.** Prerequis d'ECO-10 (echoppes) leve.

### 130 — Montures & deplacement rapide (M | ★★) — ✅ **terminee**
> Prerequis : ← ZON-06 (voyage entre zones) ✅
- [x] **Transposer l'effet monture au modele zone** : `MountTravelSpeed` reduit le `travel_seconds`
      des connexions du graphe a partir de `Mount.speedBonus`, plafond a -50 %. `getEffectiveSpeed()`
      **supprime** (sans consommateur depuis ZON-21).

**Tache 130 : terminee.** Les montures livrees (achat/quete/drop) ont enfin un effet.

---

### Piste B — Events & live ops

### 131 — Events live & outils GM (M | ★★★) — ✅ **terminee**
> Prerequis : ← 79 ✅, ← ZON-15 (evenements de zone) ✅
- [x] Interface admin pour lancer des events en temps reel (bouton « Lancer maintenant »).
      **Etait deja livree** : route `admin_event_launch_now`, bouton, CSRF, garde de statut,
      conservation de la duree, `GameEventActivatedEvent`, journal admin et 4 tests fonctionnels.
      L'entree de roadmap etait perimee.
- [x] Type « quete ephemere » : `GameEvent::TYPE_EPHEMERAL_QUEST`, rattachement de quetes depuis
      le formulaire admin, retrait des journaux a la cloture (`EphemeralQuestWithdrawer`) et
      verrou a la remise. (Les types boss/invasion sont couverts par `ZoneBoss` / ZON-18 ✅)

**Tache 131 : terminee.**

### 132 — Classement saisonnier global (M | ★★) — ✅ **terminee**
> Prerequis : ← 92 ✅
> **La passe de verification a trouve un defaut de fond** : les trois classements agregent des
> compteurs **cumulatifs** (`PlayerBestiary::killCount`, quetes achevees, `DomainExperience`)
> sans aucune fenetre de saison. Le classement « saisonnier » etait le palmares de toute
> l'histoire du serveur, rearchive a chaque cloture sous une etiquette differente.
- [x] **132a — Reference de classement** : `PlayerRankingBaseline` (une ligne par joueur/onglet,
      reecrite a chaque cloture), `RankingBaselineService`, capture branchee en fin de
      `app:season:tick` **apres** archivage et titres. L'archive de fin de saison est desormais
      saisonniere.
- [x] **132b — Ecrans** : `/game/rankings` et `/api/v1/rankings` affichent le classement de la
      saison en cours (tete, total et rang), la saison est nommee, le Hall of Fame precise que
      ses valeurs sont saisonnieres.

**Tache 132 : terminee.**

### 133 — Mini-jeux (M | ★) — ✅ **terminee**
> Prerequis : ∅
- [x] Defis chrono asynchrones (ex-« courses entre joueurs », reformules par le pivot PBBG) :
      `TimeTrial` / `TimeTrialRun`, rallier une suite de zones dans l'ordre, tableau des
      meilleurs temps par parcours, ecran `/game/time-trials`, deux parcours sur le World 1.

**Tache 133 : terminee.**

---

### Definition of Done

- [ ] 4 nouvelles zones de contenu Acte 4 jouables
- [x] Housing fonctionnel avec visites et jardin passif (tache 129, HOU-01→05)
- [x] Montures reduisant le temps de voyage entre zones (tache 130)
- [x] Events live lancables depuis l'admin (tache 131)
- [x] Classement saisonnier operationnel (tache 132 — reellement saisonnier depuis 132a/b)
