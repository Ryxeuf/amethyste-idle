## Sprint 8 — Energie & actions de zone

> **6 taches** (1 livree) | Priorite : **Haute** | Origine : Pivot PBBG ([docs/PIVOT_PBBG.md](../PIVOT_PBBG.md))
> Objectif : installer le rythme PBBG — energie regenerante, actions par zone (explorer, chasser, recolter), contenu declaratif.
> Prerequis : Sprint 7 (modele zone)

> **Principe directeur** : l'energie gate l'acces aux rencontres, JAMAIS le combat lui-meme. Les tours de combat restent gratuits et illimites une fois la rencontre engagee. Second regulateur : les PV (l'energie limite les tentatives, la vie fait payer les echecs). Curseurs a etalonner via `docs/BALANCE.md`, sans toucher au code.

> **Pivot PBBG** : ce sprint reutilise le numero de l'ancien Sprint 8 « Avatar: Backend & Carte » (✅ termine 10/10, trace dans `ROADMAP_DONE.md`). Le chantier avatar est clos par le pivot — voir `PLAN_AVATAR_SYSTEM.md`.

---

> **ZON-07 livree le 2026-07-24** (voir `ROADMAP_DONE.md`) : `Player.actionEnergy` (distincte de l'energie de combat), regeneration paresseuse via `ActionEnergyManager`, jauge sur l'ecran de zone, curseur `zone.energy.regen_seconds` en table `parameter`, section 8 de `docs/BALANCE.md`.

### ZON-08 — Action Explorer (M | ★★★)
> Prerequis : ← ZON-07
- [ ] Coute de l'energie ; tire un evenement selon la table de la zone : mob, filon, coffre, PNJ, evenement rare
- [ ] Rencontre mob → declenche le combat tour par tour existant (GameEngine/Fight inchange)
- [ ] Resultats affiches dans un journal d'exploration de la zone

### ZON-09 — Action Chasser (M | ★★★)
> Prerequis : ← ZON-07
- [ ] Coute de l'energie ; tables de mobs/loot par zone (bestiaire existant reutilise tel quel)
- [ ] Ciblage : chasser un type de mob deja rencontre dans la zone (lien bestiaire)

### ZON-10 — Recolte par zone & filons partages (M | ★★)
> Prerequis : ← ZON-07
- [ ] Actions de recolte par zone (herboristerie, minage, peche... selon les ressources de la zone)
- [ ] Filons partages : stock collectif par zone qui s'epuise et respawn (fenetre de tension cooperative)
- [ ] Reutilise les definitions de ressources/recettes existantes

### ZON-11 — Configuration declarative de zone (M | ★★★)
> Prerequis : ← ZON-08, ZON-09, ZON-10
- [ ] Format declaratif par zone : tables de rencontres, loot, ressources, actions, connexions
- [ ] Ajouter du contenu = ajouter de la donnee, pas du code (fixtures/YAML + import)
- [ ] Documentation du format dans `DOCUMENTATION.md`

### ZON-12 — Regulation par les PV (S | ★★)
> Prerequis : ← ZON-08
- [ ] Regeneration des PV en temps reel hors combat (formule dans `docs/BALANCE.md`)
- [ ] Sortir affaibli d'un combat impose d'attendre ou de consommer des soins
- [ ] Verifier que les soins existants (objets, sorts) s'integrent au modele

---

### Definition of Done

- [ ] L'energie gate explorer/chasser/recolter ; les tours de combat restent gratuits
- [ ] Chaque zone du World 1 a ses tables de rencontres/loot/ressources en donnees declaratives
- [ ] Les 4 curseurs (energie, PV, lockouts a venir, contribution) sont pilotables via `docs/BALANCE.md`
