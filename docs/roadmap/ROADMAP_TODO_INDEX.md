# Roadmap a venir — Index

> Les taches detaillees sont reparties par vague (priorite) dans les fichiers ci-dessous.
> Derniere mise a jour : 2026-04-04
> **Bilan** : Vagues 1-6 terminees (103 taches), editeur de cartes termine (16 MED), guildes termine (20 GCC).
> **Nouvelle feuille de route** : Vagues 7-10 definissent les prochaines priorites.
> **Audit contenu** : le jeu dispose de systemes solides mais le contenu est a ~30% du necessaire. La Vague 8 (Contenu critique) a ete inseree pour combler ces lacunes avant l'economie.

---

## Legende

| Symbole | Signification |
|---------|---------------|
| S / M / L / XL | Complexite (Small < Medium < Large < XL) |
| ★★★ | Gain gameplay fort |
| ★★ | Gain gameplay moyen |
| ★ | Gain gameplay faible |
| ∅ | Aucun prerequis |
| ← XX | Depend de la tache XX |
| ‖ | Parallelisable avec les autres taches du meme bloc |

---

## Etat d'avancement global

| Vague | Taches | Statut |
|-------|--------|--------|
| Vague 1 — Fondations & Quick Wins | 25/25 | ✅ Terminee |
| Vague 2 — Systemes core | 20/20 | ✅ Terminee |
| Vague 3 — Contenu & enrichissement | 18/18 | ✅ Terminee |
| Vague 4 — Monde & systemes avances | 16/16 + 16 MED + 20 GCC | ✅ Terminee |
| Vague 5 — Endgame & contenu avance | 10/10 | ✅ Terminee |
| Vague 6 — Long terme & polish | 11/11 | ✅ Terminee |
| **Plan Testing (TST)** | **11/15** | **4 taches restantes** |
| **Vague 7 — Qualite, stabilisation & fondations UX** | **10/16** | **Prochaine priorite** |
| **Vague 8 — Contenu critique** | **0/9** | **Priorite haute** |
| **Vague 9 — Economie & social** | **0/10** | **A venir** |
| **Vague 10 — Monde vivant & endgame** | **0/8** | **Long terme** |

---

## Graphe de dependances (nouvelles taches)

```
PLAN TESTING (restant)
  TST-07 Integration quetes/progression  ← TST-04
  TST-09 Stabiliser E2E                 ← TST-01
  TST-10 Nouveaux tests E2E             ← TST-09
  ✅ TST-11 Reactiver E2E dans CI          ← TST-09
  ✅ TST-12 PHPStan niveau 6               ∅
  ✅ TST-13 Mutation testing (Infection)    ← TST-05

VAGUE 7 — QUALITE, STABILISATION & FONDATIONS UX (priorite absolue)
  ┌─ Piste A — Testing (TST restants)
  │   104 TST-07 Integration quetes     ← TST-04 ✅
  │   105 TST-09 Stabiliser E2E         ← TST-01 ✅
  │   106 TST-10 Nouveaux tests E2E     ← 105
  │   107 TST-11 E2E dans CI            ← 105 ✅
  │   108 TST-12 PHPStan niveau 6       ∅  ✅
  │   109 TST-13 Mutation testing       ← TST-05 ✅
  │
  ├─ Piste B — Stabilite & polish (‖)
  │   110 Correction bugs connus        ∅
  │   111 Equilibrage combat avance     ∅
  │   112 Optimisation requetes N+1     ∅
  │
  ├─ Piste C — UX & accessibilite (‖)
  │   113 Tutoriel / onboarding joueur  ∅
  │   114 Notifications in-game         ∅
  │   115 Journal de bord joueur        ∅
  │   136 Creation de personnage        ∅
  │
  └─ Piste D — Feedback visuels (‖)
      137 Feedback visuels combat       ∅
      138 Feedback progression          ∅
      139 Comparaison equip & QoL       ∅

VAGUE 8 — CONTENU CRITIQUE
  ┌─ Piste A — Bestiaire (sequentiel)
  │   140 Monstres tier 1 manquants     ∅
  │   141 Monstres tier 2-3 & boss      ← 140
  │
  ├─ Piste B — Equipement & sorts (‖)
  │   142 Armes variees par tier        ∅
  │   143 Armures & accessoires         ∅
  │   144 Sorts & materia tier 2-3      ∅
  │
  └─ Piste C — Metiers, PNJ & quetes (‖)
      145 Recettes craft manquantes     ← 142, 143
      146 PNJ & dialogues par zone      ∅
      147 Arbres de talent combat       ← 144
      148 Quetes secondaires            ← 146, 140

VAGUE 9 — ECONOMIE & SOCIAL
  ┌─ Piste A — Commerce (sequentiel)
  │   116 Hotel des ventes — entites    ∅
  │   117 Hotel des ventes — UI         ← 116
  │   118 Hotel des ventes — anti-exploit ← 117
  │
  ├─ Piste B — Social avance (‖)
  │   119 Messagerie joueur             ∅
  │   120 Profil public joueur          ∅
  │   121 Systeme de reputation         ← 120
  │
  └─ Piste C — Contenu economique (‖)
      122 Metiers specialises (2e tier)  ← 145
      123 Encheres temporaires           ← 116
      124 Taxes dynamiques regions       ← GCC
      125 Gold sinks avances             ∅

VAGUE 10 — MONDE VIVANT & ENDGAME
  ┌─ Piste A — Contenu monde (‖)
  │   128 Nouvelles zones (acte 4)       ← 94, 141
  │   129 Housing joueur                 ← 116
  │   130 Montures & deplacement rapide  ∅
  │
  ├─ Piste B — Events & live ops (‖)
  │   131 Events live / outils GM        ← 79
  │   132 Classement saisonnier global   ← 92
  │   133 Mini-jeux (peche, courses)     ∅
  │
  └─ Piste C — Technique (‖)
      134 Load testing & scaling         ∅
      135 Localisation i18n              ∅

PLAN AVATAR MODULAIRE (format 8x8, 38 taches)
  Voir : PLAN_AVATAR_SYSTEM.md
  ┌─ Phase 0 — Assets (prerequis)
  │   AVT-01..05 (inventaire, doc layout, organisation)
  │
  ├─ Phase 1 — SpriteAnimator (← Phase 0, ‖)
  │   AVT-06..09 (type avatar, multi-anim, positionnement)
  │
  ├─ Phase 2 — Composition layers (‖ Phase 1)
  │   AVT-10..12 (Composer, Cache, Factory)
  │
  ├─ Phase 3 — Backend (‖ Phases 1-2)
  │   AVT-13..18 (Player fields, hash, API)
  │
  ├─ Phase 4 — Integration map (← Phases 1-3)
  │   AVT-19..22 (Factory map, createAnimator, tests)
  │
  ├─ Phase 5 — Creation personnage (← Phase 4)
  │   AVT-23..26 (formulaire, preview, race)
  │
  ├─ Phase 6 — Equipement visible (← Phase 5)
  │   AVT-27..30 (avatarSheet items, Mercure)
  │
  └─ Phase 7 — Polish & animations (← Phase 6)
      AVT-31..38 (run, jump, paper doll, lazy load)
```

---

## Fichiers par vague (ordre de priorite)

1. [Vague 1 — Fondations & Quick Wins](ROADMAP_TODO_VAGUE_01.md) ✅
2. [Vague 2 — Systemes core completes](ROADMAP_TODO_VAGUE_02.md) ✅
3. [Vague 3 — Contenu & enrichissement](ROADMAP_TODO_VAGUE_03.md) ✅
4. [Vague 4 — Monde & systemes avances](ROADMAP_TODO_VAGUE_04.md) ✅
5. [Vague 5 — Endgame & contenu avance](ROADMAP_TODO_VAGUE_05.md) ✅
6. [Vague 6 — Long terme & polish final](ROADMAP_TODO_VAGUE_06.md) ✅
7. **[Vague 7 — Qualite, stabilisation & fondations UX](ROADMAP_TODO_VAGUE_07.md)** ← Prochaine
8. **[Vague 8 — Contenu critique](ROADMAP_TODO_VAGUE_08.md)** ← Priorite haute
9. [Vague 9 — Economie & social](ROADMAP_TODO_VAGUE_09.md)
10. [Vague 10 — Monde vivant & endgame](ROADMAP_TODO_VAGUE_10.md)

**Plans annexes :**
- [Controle de cite par les guildes](PLAN_GUILD_CITY_CONTROL.md) ✅
- [Editeur de cartes integre & generateur procedural](PLAN_MAP_EDITOR.md) ✅
- [Testing & qualite](PLAN_TESTING.md) — 4 taches restantes (integrees en Vague 7)
- [Systeme d'avatar modulaire (format 8x8)](PLAN_AVATAR_SYSTEM.md) — 38 taches (AVT-01 a AVT-38, 7 phases)
