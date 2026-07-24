## Sprint 9 — Time-gating, presence & evenements de zone

> **5 taches** | Priorite : **Haute** | Origine : Pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : le monde vit quand le joueur est deconnecte — expeditions, presence par zone, evenements annonces, carte du monde illustree.
> Prerequis : Sprint 8 (energie & actions de zone)

> **Pivot PBBG** : ce sprint reutilise le numero de l'ancien Sprint 9 « Avatar: Personnage & Equipement » (✅ termine 8/8 en mai 2026, trace dans `ROADMAP_DONE.md`). Le chantier avatar est clos par le pivot — voir `PLAN_AVATAR_SYSTEM.md`.

---

### ZON-13 — Expeditions time-gated (L | ★★★)
> Prerequis : ← ZON-11
- [ ] Envoyer son personnage en expedition N heures reelles dans une zone ; retour = butin a recuperer
- [ ] Tables de recompenses par zone/duree (config declarative ZON-11)
- [ ] Etat exclusif : pas de voyage/exploration/combat pendant une expedition
- [ ] Notification a la fin (in-game ; Mercure si connecte)

### ZON-14 — Presence par zone & chat de zone (M | ★★★)
> Prerequis : ← ZON-05
- [ ] Liste temps reel des joueurs presents dans la zone (base de la cooperation : groupes, commerce)
- [ ] Chat de zone via Mercure (topics par zone ; les topics de deplacement restent supprimes)
- [ ] Interactions rapides depuis la liste : profil, invitation groupe, commerce

### ZON-15 — Evenements de zone (M | ★★★)
> Prerequis : ← ZON-14
- [ ] Generaliser world bosses / invasions en evenements de zone annonces, a rejoindre dans un temps limite
- [ ] Adapter `WorldBossManager` / `WorldBossLootDistributor` au modele zone (fenetre temporelle, annonce Mercure)
- [ ] Rejoindre un evenement coute de l'energie (regle Sprint 8)

### ZON-16 — Carte du monde illustree (M | ★★)
> Prerequis : ← ZON-06
- [ ] Image map cliquable (illustration pixel art, pas de moteur de rendu) pour garder l'intuition geographique
- [ ] Zones decouvertes/verrouillees visibles ; clic = voyage via ZON-06
- [ ] Indicateurs : evenements de zone actifs, expeditions en cours

### ZON-17 — Cycle jour/nuit mecanique (S | ★)
> Prerequis : ← ZON-11
- [ ] Trancher la question ouverte du pivot : le cycle jour/nuit (cosmetique sur l'ancienne carte) devient mecanique
- [ ] Si retenu : tables de rencontres jour/nuit par zone (variante dans la config declarative)

---

### Definition of Done

- [ ] Un joueur peut lancer une expedition, se deconnecter, et recuperer son butin plus tard
- [ ] On voit qui est present dans sa zone et on peut discuter/cooperer
- [ ] Les world bosses fonctionnent comme evenements de zone annonces
- [ ] La carte du monde illustree remplace visuellement l'ancienne carte navigable
