# Guide d'equilibrage — Amethyste-Idle

Document de reference pour ajuster les stats du jeu. Genere et maintenu en parallele de la commande `app:balance:report`.

---

## 1. Commande CLI

```bash
docker compose exec php php bin/console app:balance:report
docker compose exec php php bin/console app:balance:report --section=monsters
docker compose exec php php bin/console app:balance:report --section=items
docker compose exec php php bin/console app:balance:report --section=drops
docker compose exec php php bin/console app:balance:report --section=domains
docker compose exec php php bin/console app:balance:report --section=spells
docker compose exec php php bin/console app:balance:report --section=alerts
```

---

## 2. Courbe de progression XP par domaine

| Palier skill | Cout XP unitaire attendu | Cumul XP approximatif |
|--------------|--------------------------|----------------------|
| Tier 1 (1-3) | 5 - 15 | 5 - 30 |
| Tier 2 (4-6) | 20 - 40 | 50 - 150 |
| Tier 3 (7-9) | 50 - 80 | 200 - 500 |
| Tier 4 (10+) | 100 - 150 | 500 - 1200 |

**Sources d'XP domaine :** chaque action de recolte, peche, depecage, ou utilisation d'item de domaine donne **1 XP** (multiplie par les bonus d'evenements).

**XP materia (combat) :** `BASE_XP_PER_KILL (10) * niveau_monstre * multiplicateur_boss (x5 si boss) * bonus_events`. Un bonus de +25% s'applique si l'element de la materia correspond au slot.

---

## 3. Bareme des prix boutique

| Type d'item | Fourchette achat (gils) | Ratio vente |
|-------------|-------------------------|-------------|
| Consommable (potion, antidote) | 20 - 100 | 30% |
| Ressource (herb, minerai) | 5 - 50 | 30% |
| Equipement tier 1 | 50 - 200 | 30% |
| Equipement tier 2 | 200 - 800 | 30% |
| Equipement tier 3 | 800 - 3000 | 30% |
| Materia | 100 - 500 | 30% |
| Outil (pioche, faucille) | 50 - 500 | 30% |

**Formule de vente :** `max(1, floor(prix_achat * 0.3))`

Les items `boundToPlayer = true` (soulbound) ne peuvent pas etre vendus.

---

## 4. Degats et HP attendus par palier de monstre

| Niveau monstre | HP attendu | Degats attaque | XP donne |
|----------------|-----------|----------------|----------|
| 1-3 | 15 - 80 | 3 - 10 | 10 - 30 |
| 4-7 | 60 - 250 | 8 - 20 | 40 - 70 |
| 8-12 | 150 - 500 | 15 - 35 | 80 - 120 |
| 13-18 | 300 - 900 | 25 - 50 | 130 - 180 |
| 19-25 | 500 - 1500 | 40 - 80 | 190 - 250 |
| Boss (any level) | x2 - x3 du palier | x1.5 du palier | x5 du normal |

**Formule XP monstre :** `10 * niveau` (normal) ou `10 * niveau * 5` (boss).

---

## 5. Taux de drop par rarete

| Rarete | Probabilite typique | Commentaire |
|--------|---------------------|-------------|
| Common | 40% - 90% | Drop frequent, ressources de base |
| Uncommon | 15% - 40% | Materiau utile, consommable |
| Rare | 5% - 15% | Equipement, materia |
| Epic | 1% - 5% | Boss drop, recompense de zone |
| Legendary | 0.5% - 2% | Boss uniquement, tres rare |
| Amethyst | 0.1% - 0.5% | Exceptionnellement rare |

Les items `guaranteed = true` droppent toujours (100%), independamment de la probabilite.

Le champ `minDifficulty` sur `MonsterItem` permet de restreindre un drop aux instances haute difficulte du monstre.

---

## 6. Seuils d'alerte automatique

La commande `app:balance:report` detecte automatiquement les anomalies :

| Alerte | Condition |
|--------|-----------|
| Monstre 0 HP | `life <= 0` |
| Monstre 0 degats | Attaque principale avec `damage = 0` |
| HP hors fourchette | HP < `niveau * 10` ou HP > `niveau * 80` (x3 pour boss) |
| Item sans prix | Equipement ou consommable avec `price = null` |
| Equipement sans rarete | Gear piece avec `rarity = null` |
| Drop inutile | Probabilite <= 0% (non garanti) |
| Monstre sans drop | Aucune entree `MonsterItem` |
| Sort vide | Ni degats, ni soin, ni effet de statut |
| Sort gratuit surpuissant | 0 energie + > 30 degats fixes |
| Domaine vide | Aucune competence associee |

---

## 7. Equilibrage : bonnes pratiques

1. **Courbe de puissance** : la progression doit etre ressentie regulierement. Un joueur gagnant un niveau de monstre (+1) devrait noter une difference tangible en HP et degats.

2. **Gold sinks** : maintenir un equilibre entre les entrees d'or (drops, quetes) et les sorties (boutiques, craft, respec). Le ratio vente/achat de 30% est le principal gold sink.

3. **Diversite du loot** : chaque monstre devrait dropper au minimum 2-3 items differents pour rendre le farming interessant.

4. **Energie des sorts** : les sorts puissants doivent couter proportionnellement plus d'energie pour eviter le spam.

5. **Difficulte progressive** : les zones de haut niveau doivent etre inaccessibles sans equipement adequat, mais pas frustrantes avec le bon build.
