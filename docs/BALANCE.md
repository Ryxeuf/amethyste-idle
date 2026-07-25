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

---

## 8. Energie d'action PBBG (pivot, ZON-07)

Ressource qui gate l'acces aux rencontres (explorer, chasser, recolter, voyager, rejoindre un evenement de zone). **Jamais le combat lui-meme** : les tours de combat restent gratuits et illimites (principe directeur du pivot, docs/PIVOT_PBBG.md). Distincte de l'energie de combat (`Player.energy`, cout des sorts).

### Curseurs (table `parameter`, lus par `ActionEnergyManager`)

| Cle | Defaut (code) | Effet |
|-----|---------------|-------|
| `zone.energy.regen_seconds` | 360 | Secondes par point regenere (360 s = 1 pt / 6 min = 240 pts/jour) |
| `zone.energy.cost.explore` | 5 | Cout d'une action Explorer (ZON-08, lu par `ExploreService`) |
| `zone.energy.cost.hunt` | 5 | Cout d'une action Chasser (ZON-09, lu par `HuntService`) |
| `zone.energy.cost.gather` | 3 | Cout d'une action Recolter (ZON-10, lu par `GatherService`) |

`Player.maxActionEnergy` (defaut 100) est un champ par joueur : extensible plus tard via talents/equipement.

**Filons partages (ZON-10)** : la capacite (`capacity`), le delai de respawn (`respawn_seconds`) et le rendement (`yield_min`/`yield_max`) de chaque ressource sont declares par zone dans `Zone.gatherConfig` (pas de curseur `parameter` global — le contenu se regle par la donnee). Defauts de code (`GatherService`) si absents : capacite 20, respawn 1800 s, rendement 1-2. Le stock est **collectif** (partage par tous les joueurs presents dans la zone) et se recharge entierement a la capacite une fois la fenetre de respawn ecoulee.

### Mecanique

- **Regeneration paresseuse** : calculee a la lecture (`refresh`), aucun cron. Le reliquat de temps est conserve entre deux lectures ; le timer demarre a la premiere depense depuis le plein.
- **Depense** (`spend`) : refuse si insuffisant (`NotEnoughActionEnergyException`), les actions affichent le cout.
- **Reperes** : plein en 10 h a 360 s/pt. Couts indicatifs a etalonner en Sprint 8 : explorer 5, chasser 5, recolter 3, voyager 0 (le voyage coute du temps, pas de l'energie — a re-evaluer), evenement de zone 10.

### Les 4 curseurs du pivot

1. **Energie** (tentatives) — ce chapitre.
2. **PV** (echecs) — regen hors combat, ZON-12 (section 9).
3. **Lockouts** (donjons) — recompenses decroissantes, ZON-20.
4. **Contribution** (loot de groupe) — boss de zone, ZON-18.

## 9. Regeneration des PV hors combat (pivot, ZON-12)

Deuxieme regulateur du pivot PBBG : l'energie limite les **tentatives** (section 8), les PV font payer les **echecs**. Sortir affaibli d'un combat (victoire couteuse, fuite, respawn a 50 %) impose d'attendre la regeneration ou de consommer des **soins** (objets/sorts existants, inchanges) avant de repartir a pleine puissance. **Jamais pendant le combat** : les PV y restent geres par le combat tour par tour ; la regen hors combat ne s'applique qu'a un joueur vivant, hors combat et blesse.

### Curseur (table `parameter`, lu par `LifeRegenManager`)

| Cle | Defaut (code) | Effet |
|-----|---------------|-------|
| `zone.life.regen_seconds` | 12 | Secondes par point de vie regenere (12 s/PV = 100 PV en 20 min, 200 PV en 40 min) |

### Mecanique

- **Regeneration paresseuse** : calculee a la lecture (`LifeRegenManager::refresh`, appele sur l'ecran de zone), aucun cron. Le reliquat de temps est conserve entre deux lectures.
- **Ancre a la sortie de combat** (`anchor`) : `Player.lifeUpdatedAt` est remis a maintenant a chaque sortie de combat (victoire via `FightCleaner`, fuite, defaite/respawn). Sans cette ancre, le temps ecoule depuis le dernier plein *anterieur* au combat compterait comme regen — un joueur plein avant combat guerirait instantanement en sortie.
- **Bornes** : ne regenere pas en combat (`getFight() !== null`), ni un joueur mort (`isDead()` — il passe par le respawn), ni au plein (`life >= maxLife`, l'ancre suit alors « maintenant »).
- **Repere** : recuperation complete d'un joueur a 0/max en `maxLife * 12 s`. Curseur a etalonner : plus la regen est lente, plus les soins et la prudence prennent de la valeur.

## 10. Expeditions time-gated (pivot, ZON-13)

Le joueur envoie son personnage explorer une zone pendant N heures reelles ; au retour, un butin l'attend (a recuperer). **Etat exclusif** : pendant l'expedition, plus de voyage, ni d'exploration/chasse/recolte, ni de combat. Time-gated en temps reel, resolu paresseusement au chargement de l'ecran de zone (aucun cron par joueur). Une seule expedition par joueur a la fois (`LifeRegenManager`/`ActionEnergyManager` restent actifs, la regen des PV continue pendant l'attente).

### Curseurs (table `parameter`, lus par `ExpeditionService`)

| Cle | Defaut (code) | Effet |
|-----|---------------|-------|
| `zone.expedition.duration.short` | 3600 | Duree du palier « courte » en secondes (1 h) |
| `zone.expedition.duration.medium` | 14400 | Duree du palier « moyenne » (4 h) |
| `zone.expedition.duration.long` | 43200 | Duree du palier « longue » (12 h) |

### Recompenses (derivees des tables declaratives de zone, ZON-11)

Pas de table de butin dediee : les recompenses **reprennent la donnee de zone existante**, mise a l'echelle par la duree (`heures = duree / 3600`).

- **Gils** : la fourchette « coffre » de `Zone.exploreConfig` (`chest_gils_min`/`chest_gils_max`), tiree une fois par heure.
- **Objets** : un filon de `Zone.gatherConfig` tire au hasard par heure, avec son rendement declare (`yield_min`/`yield_max`). Une zone sans filon ne rapporte que des gils.

Regler le butin d'une expedition = ajuster la donnee de zone (coffre/filons), pas le code. **Zones eligibles** : toute zone non sure (`safe: false`) — les cites/hubs n'ont pas d'expedition.

### Notification de fin

A la fin (heure de retour passee), la resolution paresseuse emet une notification `NotificationService` (persistee in-game + poussee Mercure `player/<id>/notifications` si le joueur est connecte), une seule fois (`PlayerExpedition.notifiedAt`). Le butin se recupere ensuite via le bouton dedie sur l'ecran de zone.
