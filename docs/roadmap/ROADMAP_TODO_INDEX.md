# Roadmap a venir — Index

> Les taches detaillees sont reparties par **sprint** dans les fichiers ci-dessous.
> Derniere mise a jour : 2026-04-27 (135 sous-phase 3c.u — 28 equipements elementaires tier 2 = 4 elements x 7 slots, 249/279 items traduits = 89%)
> **Bilan** : Vagues 1-6 terminees (103 taches), editeur de cartes termine (16 MED), guildes termine (20 GCC).
> **Sprints 1, 2, 3, 4, 5 et 6 termines.** Sprint 7 (Avatar : Fondations) en cours — 11/12 taches completees (AVT-04 bloque sur livraison d'assets). Sprint 8 (Avatar : Backend & Carte) — 10/10 taches completees. Sprint 9 (Avatar : Personnage & Equipement) — 6/8 taches completees (AVT-24 ajoute, AVT-30 client Mercure avatar). Sprint 10 (Avatar : Polish) — 4/8 taches completees (AVT-32 jump animation, AVT-31 run animation via sprint Shift, AVT-36 lazy loading, AVT-37 cache IndexedDB). Sprint 11 (Monde vivant) — 130 sous-phases 1 + 5 livrees (catalogue Mount + fast travel verrouille par decouverte de region), 131 sous-phases 2a + 3 + 4 livrees (nouveau type `gathering_bonus` pour boost temporaire de la recolte + historique des events lances via `/admin/events/history` + couverture test de la chaine admin toggle -> annonce Mercure globale), 132 sous-phases 1 + 2a + 2b + 3 + 4a + 4b.1 + 4b.1b livrees (page `/game/rankings` avec onglets kills + quetes completees + XP totale, archivage a la fin de saison, titres de podium top-3 attribues a la fin de saison + affichage des titres sur la page classement + affichage sur le profil public), 133 sous-phase 1 livree (peche : zone parfaite 45-55 preserve la durabilite de la canne). Sprint 12 (Technique & i18n) — 134 sous-phases 1 + 2a + 2b + 2c + 2d + 3a + 3b + 3c + 3d livrees (infra k6 + 4 scenarios + recueil goulots/plan d'optimisation + indexes /metrics + cache TTL collectors /metrics jalon C complet + suppression produit cartesien findByMapWithMonster + partial index fight.in_progress), 135 sous-phases 1 + 2a + 3a + 3b + 3c + 3e.a + 3e.b.a + 3e.b.b + 3e.c.d.quest + 3e.c.d.quest.b + 3e.c.d.quest.c livrees (selecteur de langue securise + parite de cles FR/EN sur l'UI + infrastructure multilingue des noms d'items + cablage du filter Twig `localized_name` dans les templates shop/inventaire/bestiaire + fixtures EN pour 35 items de debut de jeu + infrastructure multilingue pour les noms de monstres + cablage du filter `localized_monster_name` dans les templates bestiaire/profile/fight + fixtures EN pour 24 monstres de niveaux 1-3 + infrastructure multilingue pour les noms de quetes + cablage du filter `localized_quest_name` dans les templates du journal de quetes et du dashboard + fixtures EN pour 26 quetes de debut de jeu).
> **Organisation sprint** : les anciennes vagues 7-10 + le plan avatar ont ete reorganises en 12 sprints focuses.

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

| Phase | Taches | Statut |
|-------|--------|--------|
| Vague 1 — Fondations & Quick Wins | 25/25 | ✅ Terminee |
| Vague 2 — Systemes core | 20/20 | ✅ Terminee |
| Vague 3 — Contenu & enrichissement | 18/18 | ✅ Terminee |
| Vague 4 — Monde & systemes avances | 16/16 + 16 MED + 20 GCC | ✅ Terminee |
| Vague 5 — Endgame & contenu avance | 10/10 | ✅ Terminee |
| Vague 6 — Long terme & polish | 11/11 | ✅ Terminee |
| Plan Testing (TST) | 11/15 | ✅ Quasi-termine (integre en Sprint 1) |

---

## Sprints a venir

| Sprint | Theme | Taches | Priorite | Statut |
|--------|-------|--------|----------|--------|
| **Sprint 1** | Stabilite & Onboarding | 3 | Critique | ✅ **Termine** |
| **Sprint 2** | Bestiaire & PNJ | 2 | Haute | ✅ **Termine** |
| **Sprint 3** | Arsenal & Magie | 3 | Haute | ✅ **Termine** |
| **Sprint 4** | Progression & Narration | 3 | Haute | ✅ **Termine** |
| **Sprint 5** | Hotel des ventes | 3 | Moyenne | ✅ **Termine** |
| **Sprint 6** | Social & Economie | 6/6 | Moyenne | ✅ **Termine** |
| **Sprint 7** | Avatar: Fondations | 11/12 | Moyenne | En cours |
| **Sprint 8** | Avatar: Backend & Carte | 10/10 | Moyenne | ✅ **Termine** |
| **Sprint 9** | Avatar: Personnage & Equipement | 6/8 | Moyenne | En cours |
| **Sprint 10** | Avatar: Polish & Animations | 4/8 | Basse | En cours |
| **Sprint 11** | Monde vivant | 6 | Basse | En cours (130 sous-phases 1 + 5, 131 sous-phases 2a + 3 + 4, 132 sous-phases 1 + 2a + 2b + 3 + 4a + 4b.1 + 4b.2, 133 sous-phases 1 + 1b) |
| **Sprint 12** | Technique & i18n | 2 | Basse | En cours (134 sous-phases 1 + 2a + 2b + 2c + 2d + 3a + 3b + 3c + 3d, 135 sous-phases 1 + 2a + 3a + 3b + 3c + 3c.b + 3c.c + 3c.d + 3c.e + 3e.a + 3e.b.a + 3e.b.b + 3e.b.b.suite + 3e.c.d.quest + 3e.c.d.quest.b + 3e.c.d.quest.c + 3e.c.d.quest.d + 3e.c.d.quest.e + 3e.c.d.quest.f + 3e.c.d.quest.g + 3e.c.d.quest.h + 3e.c.d.quest.i + 3e.c.d.quest.j + 3e.c.domain + 3e.c.domain.b + 3e.c.domain.c + 3e.c.achievement + 3e.c.achievement.b + 3e.c.achievement.c + 3e.f + 3e.f.b) |

**Total restant : 44 taches** (hors plan avatar interne, reference dans PLAN_AVATAR_SYSTEM.md)
**Avancement** : Sprints 1 (3), 2 (2), 3 (3), 4 (3), 5 (3), 6 (6) et 8 (10) termines + Sprint 7 (11/12 en cours) + Sprint 9 (6/8 en cours) + Sprint 10 (4/8 en cours) = 51 taches completes / initial 66+

---

## Graphe de dependances (sprints)

```
SPRINT 1 — STABILITE & ONBOARDING (priorite absolue)
  110 Correction bugs              ∅
  111 Equilibrage combat           ∅
  113 Tutoriel / onboarding        ∅

SPRINT 2 — BESTIAIRE & PNJ
  141 Monstres tier 2-3 & boss    ← 140 ✅
  146 PNJ & dialogues par zone    ∅

SPRINT 3 — ARSENAL & MAGIE (‖ Sprint 2)
  142 Armes variees par tier      ∅
  143 Armures & accessoires       ∅
  144 Sorts & materia tier 2-3    ∅

SPRINT 4 — PROGRESSION & NARRATION (← Sprints 2 & 3)
  147 Arbres de talent combat     ← 144
  145 Recettes craft manquantes   ← 142, 143
  148 Quetes secondaires          ← 146, 140 ✅

SPRINT 5 — HOTEL DES VENTES (← Sprint 4 recommande)
  116 HdV — entites & backend     ∅
  117 HdV — UI & recherche        ← 116
  118 HdV — anti-exploit          ← 117

SPRINT 6 — SOCIAL & ECONOMIE (← Sprint 5)
  119 Messagerie joueur            ∅
  121 Reputation & karma           ← 120 ✅
  122 Metiers specialises          ← 145
  123 Encheres temporaires         ← 116
  124 Taxes dynamiques             ← GCC ✅
  125 Gold sinks avances           ∅

SPRINT 7 — AVATAR: FONDATIONS (‖ Sprints 1-6)
  AVT-01..05 Phase 0 Assets       ∅
  AVT-06..09 Phase 1 Animator     ← AVT-02
  AVT-10..12 Phase 2 Composition  ← AVT-06

SPRINT 8 — AVATAR: BACKEND & CARTE (← Sprint 7)
  AVT-13..18 Phase 3 Backend      ∅ / ← AVT-13..14
  AVT-19..22 Phase 4 Integration  ← AVT-12, AVT-17

SPRINT 9 — AVATAR: PERSONNAGE & EQUIPEMENT (← Sprint 8)
  AVT-23..26 Phase 5 Creation     ← AVT-13
  AVT-27..30 Phase 6 Mercure      ← AVT-15, AVT-16

SPRINT 10 — AVATAR: POLISH (← Sprint 9)
  AVT-31..38 Phase 7 Animations   ← AVT-07, AVT-20

SPRINT 11 — MONDE VIVANT (← Sprints 4-5)
  128 Nouvelles zones Acte 4      ← 94, 141
  129 Housing joueur               ← 116
  130 Montures                     ∅
  131 Events live                  ← 79
  132 Classement saisonnier        ← 92
  133 Mini-jeux                    ∅

SPRINT 12 — TECHNIQUE & I18N (‖ tout)
  134 Load testing & scaling       ∅
  135 Localisation i18n            ∅
```

---

## Parallelisation des sprints

```
Critique ─── Sprint 1 ──┐
                         ├── Sprint 2 ──┐
                         ├── Sprint 3 ──┤── Sprint 4 ──── Sprint 5 ──── Sprint 6
                         │              │
                         │              └── Sprint 11 (monde vivant)
                         │
Independant ─ Sprint 7 ──── Sprint 8 ──── Sprint 9 ──── Sprint 10
                         │
                         └── Sprint 12 (technique, parallelisable a tout moment)
```

---

## Fichiers par sprint

1. [Sprint 1 — Stabilite & Onboarding](SPRINT_01.md) ✅
2. [Sprint 2 — Bestiaire & PNJ](SPRINT_02.md) ✅
3. [Sprint 3 — Arsenal & Magie](SPRINT_03.md) ✅
4. [Sprint 4 — Progression & Narration](SPRINT_04.md) ✅
5. [Sprint 5 — Hotel des ventes](SPRINT_05.md) ✅
6. [Sprint 6 — Social & Economie](SPRINT_06.md) ✅
7. **[Sprint 7 — Avatar: Fondations](SPRINT_07.md)** ← Prochain
8. [Sprint 8 — Avatar: Backend & Carte](SPRINT_08.md)
9. [Sprint 9 — Avatar: Personnage & Equipement](SPRINT_09.md)
10. [Sprint 10 — Avatar: Polish & Animations](SPRINT_10.md)
11. [Sprint 11 — Monde vivant](SPRINT_11.md)
12. [Sprint 12 — Technique & i18n](SPRINT_12.md)

**Plans annexes :**
- [Controle de cite par les guildes](PLAN_GUILD_CITY_CONTROL.md) ✅
- [Editeur de cartes integre & generateur procedural](PLAN_MAP_EDITOR.md) ✅
- [Testing & qualite](PLAN_TESTING.md) — quasi-termine (taches restantes integrees en Sprint 1)
- [Systeme d'avatar modulaire (format 8x8)](PLAN_AVATAR_SYSTEM.md) — 38 taches detaillees (Sprints 7-10)
