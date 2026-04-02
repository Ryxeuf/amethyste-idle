## Vague 9 — Monde vivant & endgame

> **8 taches** long terme pour un monde vivant et rejouable.
> Prerequis : Vague 8 (economie & social) recommandee avant.

---

### Piste A — Contenu monde (parallelisable)

### 128 — Nouvelles zones — Acte 4 (XL | ★★★)
> Prerequis : ← 94 (Acte 3 termine)
- [ ] 4 nouvelles cartes generees via l'editeur
- [ ] Nouveaux biomes : desert, tundra
- [ ] Monstres tier 4 (level 30-40)
- [ ] Chaine de quetes Acte 4
- [ ] Boss final Acte 4

### 129 — Housing joueur (L | ★★)
> Prerequis : ← 116 (hotel des ventes)
- [ ] Terrain achetable dans une zone dediee
- [ ] Maison personnalisable : meubles, coffre prive, atelier de craft
- [ ] Visitabilite : les joueurs peuvent visiter les maisons des autres
- [ ] Jardin : recolte passive (plantes poussent en temps reel)

### 130 — Montures & deplacement rapide (M | ★★)
> Prerequis : ∅
- [ ] Entite `Mount` : slug, name, speedBonus, sprite
- [ ] Obtention via quete, drop rare, ou achat
- [ ] Vitesse de deplacement +50% quand monte
- [ ] Animation sprite monte sur la carte
- [ ] Teleportation rapide entre villes decouvertes (cout en gils)

---

### Piste B — Events & live ops (parallelisable)

### 131 — Events live & outils GM (M | ★★★)
> Prerequis : ← 79 (evenements bonus)
- [ ] Interface admin pour lancer des events en temps reel
- [ ] Types : spawn de boss special, buff global, quete ephemere
- [ ] Historique des events lances
- [ ] Annonce globale via Mercure SSE

### 132 — Classement saisonnier global (S | ★★)
> Prerequis : ← 92 (classement guildes)
- [ ] Classement individuel : XP gagnee, mobs tues, quetes completees par saison
- [ ] Recompenses de fin de saison : titres, cosmetiques, items exclusifs
- [ ] Page `/game/rankings` avec onglets (individuel, guilde)

### 133 — Mini-jeux (peche amélioree, courses) (M | ★)
> Prerequis : ∅
- [ ] Peche active : mini-jeu timing (barre de progression, fenetre de clic)
- [ ] Courses entre joueurs : parcours avec chrono, classement
- [ ] Recompenses specifiques par mini-jeu

---

### Piste C — Technique (parallelisable)

### 134 — Load testing & scaling (M | ★★)
> Prerequis : ∅
- [ ] Script k6/Locust pour simuler 100+ joueurs simultanes
- [ ] Identification goulots d'etranglement (DB, Mercure, FrankenPHP)
- [ ] Optimisations : connection pooling, cache Redis, horizontal scaling plan
- [ ] Objectif : 200 joueurs simultanes sans degradation

### 135 — Localisation i18n (M | ★)
> Prerequis : ∅
- [ ] Extraction des chaines via Symfony Translation (xliff)
- [ ] Traduction EN prioritaire (UI, items, quetes, dialogues)
- [ ] Selecteur de langue dans les parametres joueur
- [ ] Contenu de jeu multilingue (noms items, descriptions sorts)

---
