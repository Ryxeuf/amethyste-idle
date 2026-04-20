## Sprint 11 — Monde vivant

> **6 taches** | Priorite : **Basse** | Origine : Vague 10, Pistes A & B
> Objectif : etendre le monde avec de nouvelles zones, du housing, des montures et des events live.
> Prerequis : Sprints 1-6 recommandes (contenu de base complet)

---

### Piste A — Contenu monde

### 128 — Nouvelles zones — Acte 4 (XL | ★★★)
> Prerequis : ← 94 (Acte 3 termine), ← 141 (monstres tier 2-3)
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
> Avancement : sous-phases 1 + 2a livrees (catalogue + fondation ownership). Sous-phases 2b-4 restent a faire.
- [x] Entite `Mount` : slug, name, speedBonus, sprite — entite `App\Entity\Game\Mount` (table `game_mounts`) avec slug unique, description, sprite sheet + icone, speedBonus (defaut 50), obtentionType enum (`quest`/`drop`/`purchase`/`achievement`), gilCost, requiredLevel, flag enabled + timestamps. Migration `Version20260419MountCatalog`. Fixtures de base (4 montures : cheval brun, loup sauvage, chocobo jaune, sanglier colossal) couvrant les 3 types d'obtention principaux. Tests unitaires validant les contraintes (speedBonus >= 0, obtentionType whitelist, gilCost >= 0 ou null, requiredLevel >= 1).
- [ ] Obtention via quete, drop rare, ou achat — **Sous-phase 2a livree (2026-04-20)** : fondation ownership. Entite `App\Entity\App\PlayerMount` (table `player_mount`, UNIQUE player_id+mount_id, FK CASCADE vers `player` et `game_mounts`) avec champ `source` enum (quest/drop/purchase/achievement/admin) et `acquired_at`. Repository `PlayerMountRepository` (`findByPlayer`, `findOneByPlayerAndMount`, `playerOwnsMount`). Service `App\GameEngine\Mount\MountAcquisitionService::grantMount` commun a toutes les sources d'acquisition (rejette si monture desactivee ou deja possedee via `MountAlreadyOwnedException`). Migration `Version20260420PlayerMount`. Tests unitaires (`PlayerMountTest`, `MountAcquisitionServiceTest`). Reste a brancher aux systemes quete / loot / boutique (sous-phase 2b).
- [ ] Vitesse de deplacement +50% quand monte
- [ ] Animation sprite monte sur la carte
- [ ] Teleportation rapide entre villes decouvertes (cout en gils)

---

### Piste B — Events & live ops

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

### 133 — Mini-jeux (peche amelioree, courses) (M | ★)
> Prerequis : ∅
- [ ] Peche active : mini-jeu timing (barre de progression, fenetre de clic)
- [ ] Courses entre joueurs : parcours avec chrono, classement
- [ ] Recompenses specifiques par mini-jeu

---

### Definition of Done

- [ ] 4 nouvelles cartes Acte 4 jouables
- [ ] Housing fonctionnel avec visites
- [ ] Montures obtensibles et utilisables
- [ ] Events live lancables depuis l'admin
- [ ] Classement saisonnier operationnel
