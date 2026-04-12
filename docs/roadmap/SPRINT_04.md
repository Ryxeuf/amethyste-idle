## Sprint 4 — Progression & Narration

> **3 taches** | Priorite : **Haute** | Origine : Vague 8, Piste C
> Objectif : enrichir les arbres de talent, completer les recettes de craft, ajouter des quetes.
> Prerequis : Sprint 3 (armes, armures, sorts necessaires pour les recettes et talents)

---

### ~~147 — Arbres de talent combat (M | ★★★)~~ ✅
> Prerequis : ← 144 (sorts & materia tier 2-3)
- [x] 10-15 skills par branche combat : Soldat, Defenseur, Guerisseur, Druide, Necromancien, Pretre
- [x] Reequilibrage global : chaque branche doit avoir une profondeur comparable au Mineur (18 skills)
- [x] Skills de deblocage materia pour les sorts tier 2-3
- [x] Bonus passifs diversifies (degats, survie, soin, critique, vitesse)

### ~~145 — Recettes craft manquantes (L | ★★★)~~ ✅
> Prerequis : ← 142 (armes), ← 143 (armures)
- [x] Tanneur : 15 recettes (armures cuir, capes, accessoires)
- [x] Alchimiste : 11 recettes (potions, elixirs, poisons, onguents) — 6 sorts, 5 items, 9 recettes ajoutees
- [x] Joaillier : 15 recettes (anneaux, amulettes, gemmes, sertissage materia)
- [x] Forgeron : 10 recettes supplementaires (armes T2-T3, armures metal)
- [x] Progression de recettes par niveau de domaine

### 148 — Quetes secondaires & contenu narratif (M | ★★)
> Prerequis : ← 146 (PNJ), ← 140 ✅ (monstres tier 1)
- [x] 80+ quetes total (7 quetes ajoutees : 5 choix moral + 2 chasse, 73 → 80)
- [x] Quetes de zone : 6 quetes liees aux PNJ locaux (Foret, Mines, Marais, Montagne)
- [x] Quetes de faction (reputation) — 4 quetes introductives (Mages, Chevaliers, Ombres, Marchands) avec recompenses de reputation via 4 PNJ thematiques
- [x] Quetes de decouverte (explorer X zones, trouver Y objets caches)
- [x] Diversifier les types — **choix moral** : 5 quetes a choix moral avec consequences de reputation opposees (ReputationListener etendu pour appliquer la reputation du choix selectionne via QuestCompletedEvent::choiceMade)
- [ ] Diversifier les types : escorte, defend, puzzle (non traite)

---

### Definition of Done

- [ ] 6 branches combat avec 10-15 skills chacune
- [ ] 55+ recettes de craft reparties sur 4 metiers
- [ ] 30+ nouvelles quetes jouables
- [ ] Fixtures et tests d'integration a jour
