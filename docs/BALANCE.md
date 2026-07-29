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
| 26-32 | 900 - 2200 | 70 - 120 | 260 - 320 |
| 33-40 | 1500 - 3500 | 100 - 170 | 330 - 400 |
| Boss (any level) | x2 - x3 du palier | x1.5 du palier | x5 du normal |

**Tier 4 (26-40, tache 128a)** : le bestiaire s'arretait au niveau 24 pour les creatures
normales — les deux seules entrees a 30 etaient des boss. Les zones de l'Acte 4 auraient
donc ete peuplees de faune de tier 1-3, et un « Acte 4 » indiscernable de l'Acte 1.

Les deux biomes du palier tuent differemment, et c'est delibere : le **desert** use (venins,
sables mouvants, degats qui s'accumulent), la **toundra** fige (gel, entraves, gros coups
espaces). Un joueur qui s'equipe contre l'un se retrouve mal arme contre l'autre — la
resistance elementaire cesse d'etre un chiffre pour devenir un choix.

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

Ressource qui gate l'acces aux rencontres (explorer, chasser, recolter, rejoindre un evenement de zone). **Jamais le combat lui-meme** : les tours de combat restent gratuits et illimites (principe directeur du pivot, docs/PIVOT_PBBG.md). Distincte de l'energie de combat (`Player.energy`, cout des sorts).

### Curseurs (table `parameter`, lus par `ActionEnergyManager`)

| Cle | Defaut (code) | Effet |
|-----|---------------|-------|
| `zone.energy.regen_seconds` | 360 | Secondes par point regenere (360 s = 1 pt / 6 min = 240 pts/jour) |
| `zone.energy.cost.explore` | 5 | Cout d'une action Explorer (ZON-08, lu par `ExploreService`) |
| `zone.energy.cost.hunt` | 5 | Cout d'une action Chasser (ZON-09, lu par `HuntService`) |
| `zone.energy.cost.gather` | 3 | Cout d'une action Recolter (ZON-10, lu par `GatherService`) |
| `zone.energy.cost.event` | 10 | Cout pour rejoindre un evenement de zone (ZON-15, lu par `ZoneEventService`) |
| `zone.energy.cost.assault` | 10 | Cout d'un assaut contre un boss de zone (ZON-18, lu par `ZoneBossService`) |
| `zone.boss.assault_damage_factor` | 100 | Multiplicateur des degats d'assaut en % (100 = x1.0 de la stat d'attaque du joueur, ZON-18) |
| `zone.dungeon.turn_seconds` | 45 | Delai par tour d'un donjon de groupe ; au-dela, attaque de base auto (ZON-19, lu par `GroupDungeonCombatService`) |
| `zone.dungeon.encounter_hp_per_member` | 200 | PV de la rencontre partagee par membre du groupe (ZON-19) |
| `zone.dungeon.reward.base_gils` | 150 | Gils de base par membre a la reussite d'un donjon de groupe (ZON-20, lu par `GroupDungeonRewardService`) |
| `zone.dungeon.lockout.window_hours` | 24 | Fenetre glissante (heures) de comptage des reussites pour la decroissance (ZON-20) |
| `zone.dungeon.lockout.decay` | 0.5 | Facteur multiplicatif de la recompense par reussite recente du meme donjon (ZON-20) |
| `zone.dungeon.lockout.min_factor` | 0.25 | Plancher de la recompense decroissante (protection de l'economie, on prefere la decroissance au blocage sec, ZON-20) |

Le cout d'une **epreuve chronometree** n'a pas de curseur global : il est declare par epreuve (`TimeTrial.energy_cost`, defaut 5, lu par `TimeTrialService`) — le contenu se regle par la donnee.

### Plafond : 24 h de regeneration

`Player.maxActionEnergy` vaut **240** par defaut (`Player::DEFAULT_MAX_ACTION_ENERGY`), soit exactement 24 h de regeneration a 360 s/pt. C'est un champ par joueur, extensible plus tard via talents/equipement.

Le plafond couvre volontairement une journee entiere. A 100 (valeur d'origine, ZON-07), le plein etait atteint en 10 h et toute l'energie regeneree au-dela etait perdue :

| Rythme de connexion | Energie captee sur 240 | Perte |
|---|---|---|
| 1x / 24 h | 100 | **58 %** |
| 2x / 12 h | 200 | 17 % |
| 3x / 8 h | 240 | 0 % |

Le reglage imposait donc **~2,4 connexions par jour** pour ne rien gaspiller : il penalisait l'absence longue, a l'inverse de l'objectif de conception (ne pas contraindre la frequence de connexion). Regle de calibrage : **le plafond doit toujours couvrir au moins 24 h de regeneration**. Si `zone.energy.regen_seconds` change, le plafond doit suivre (`86400 / regen_seconds`).

Ce plafond ne donne rien de plus au joueur assidu : le budget quotidien reste 240 points pour tout le monde. Il le depense simplement plus tot dans sa journee.

**Filons partages (ZON-10)** : la capacite (`capacity`), le delai de respawn (`respawn_seconds`) et le rendement (`yield_min`/`yield_max`) de chaque ressource sont declares par zone dans `Zone.gatherConfig` (pas de curseur `parameter` global — le contenu se regle par la donnee). Defauts de code (`GatherService`) si absents : capacite 20, respawn 1800 s, rendement 1-2. Le stock est **collectif** (partage par tous les joueurs presents dans la zone) et se recharge entierement a la capacite une fois la fenetre de respawn ecoulee.

### Mecanique

- **Regeneration paresseuse** : calculee a la lecture (`refresh`), aucun cron. Le reliquat de temps est conserve entre deux lectures ; le timer demarre a la premiere depense depuis le plein.
- **Depense** (`spend`) : refuse si insuffisant (`NotEnoughActionEnergyException`), les actions affichent le cout.
- **Reperes** : 10 pts/h, 240 pts/jour, plein en 24 h. Un plein represente ~48 rencontres a 5 pts, ou 80 recoltes a 3 pts.
- **Aucune source d'energie hors du temps** : `setActionEnergy()` n'est appele que par `ActionEnergyManager`. Pas de potion, pas de repas, pas de recharge payante — et c'est deliberé. Une recharge achetable transformerait le regulateur d'equite en boutique (cf. docs/MONETIZATION.md, qui n'en prevoit pas).

### Ce qui ne coute PAS d'energie

Reciproque du principe directeur, a garder explicite pour ne pas facturer par inadvertance :

| Systeme | Regulateur a la place |
|---|---|
| Tours de combat | Aucun (principe directeur : le combat ne doit jamais etre penalise par sa duree) |
| Voyage entre zones | Temps reel du graphe — et c'est ce cout-temps qui porte l'arbitrage regional d'ECO-03 |
| Expeditions (lancer / recuperer) | Temps reel ; c'est l'outil du joueur peu disponible, le facturer taxerait l'absence |
| Craft | Temporisation de l'etabli (`readyAt`, ECO-20) |
| Donjon solo | Cooldown |
| Donjon de groupe | Lockout a recompense **decroissante** (`zone.dungeon.lockout.*`) |
| Economie (HV, commandes de craft, echoppes, services) | Gils et taxes regionales |
| Jardin / housing | Temps reel de croissance + loyers |
| Quetes, dailies, PNJ, arbres de talents, social | Rotation quotidienne, points de talent, aucun |

### Les trois couches de rythme

L'energie n'est qu'une des trois couches, et les separer est ce qui permet de servir le joueur peu disponible **et** le joueur investi sans que l'un penalise l'autre :

1. **Couche temps reel (offline-first)** — expeditions, craft temporise, jardin, loyers, commandes de craft, ventes HV. Ne coute pas d'energie, tourne sans le joueur. Deux passages courts par jour suffisent a faire tourner ces boucles.
2. **Couche energie (les tentatives)** — acces aux rencontres. Budget quotidien **egalitaire** : jouer plus n'en donne pas plus. C'est le garde-fou qui empeche l'absent de decrocher.
3. **Couche temps investi (illimitee)** — combat tactique, coordination de groupe, donjons, marche, influence de guilde. Plus on joue, plus on gagne, **sans consommer d'energie**.

Corollaire de calibrage : pour recompenser l'investissement, augmenter le **rendement par point** (butin, qualite, chance via talents et equipement) et etoffer la **couche 3**, jamais le nombre d'actions. Tout levier qui augmente le debit brut d'actions creuse l'ecart avec le joueur peu disponible, ce que les couches sont justement censees eviter.

### Rendement par point d'energie (`ActionYieldResolver`)

Mise en oeuvre du corollaire ci-dessus. Les bonus sont des **passifs de competence**
(regle absolue #9), cumulatifs sur l'ensemble des competences apprises.

| Categorie | Effet | Consommateur |
|-----------|-------|--------------|
| `gather_percent` | Quantite recoltee par action de recolte | `GatherService::computeYield()` |
| `chest_percent` | Gils d'un coffre trouve en explorant | `ExploreService::resolveChest()` |

Declaration dans `Skill.actions`, sous les **deux** formes qui coexistent dans les arbres livres :

```php
// Forme map (arbres de combat et de materia)
'actions' => ['yield' => ['gather_percent' => 10]]
// Forme liste de descripteurs (arbres de recolte et d'artisanat)
'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 10]]
```

**Regles de calibrage**

- **Plafond cumule de 100 %** (`ActionYieldResolver::MAX_BONUS_PERCENT`). Ce n'est pas
  une precaution de style : sans lui, un arbre assez long finit par rendre le plafond
  d'energie sans effet, et le joueur assidu retrouve par le rendement le debit que le
  budget quotidien lui refuse.
- **Arrondi au plus proche**, pas a l'inferieur : sur un rendement de 1 a 2 unites, un
  bonus de 10 % arrondi en bas ne se verrait jamais et le joueur paierait des points de
  talent pour rien.
- **Le stock partage borne le resultat** : le bonus augmente ce qu'une action rapporte,
  il ne permet pas de prendre plus que ce que le filon contient. Le stock reste le point
  de tension d'une ressource sur un serveur peuple.
- Une valeur negative est ignoree : elle signale une donnee fautive, pas un malus voulu.

**Limite connue** : le bonus de recolte n'est pas cloisonne par profession. Un joueur qui
maximise les quatre arbres de recolte (mineur, herboriste, pecheur, depeceur) cumule
74 % sur **toutes** les ressources, y compris celles des metiers qu'il ne pratique pas.
Le plafond borne la derive, mais le cloisonnement par `profession` (deja declare sur
chaque filon de `Zone.gatherConfig`) reste a faire.

### Les 4 curseurs du pivot

1. **Energie** (tentatives) — ce chapitre.
2. **PV** (echecs) — regen hors combat, ZON-12 (section 9).
3. **Lockouts** (donjons) — recompenses decroissantes, ZON-20.
4. **Contribution** (loot de groupe) — boss de zone asynchrones, ZON-18 : chaque assaut coute de l'energie et alimente `PlayerZoneEventParticipation.contribution` ; a 0 PV, le loot va aux contributeurs (top-3 = drops garantis + proba boostee, autres = probabiliste).

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

---

## 11. Graphe de zones du World 1 (pivot, ZON-26)

Le monde est un graphe declare dans `config/game/zones/world_1.yaml` — **source de verite unique**, partagee par la fixture `ZoneGraphFixtures` et la commande `app:zone:import`. Ajouter du contenu de zone = editer ce fichier.

### Topologie

Etoile autour du hub **+ anneau peripherique** (ZON-26) : les quatre zones sauvages sont reliees deux a deux, de sorte que contourner le hub soit une alternative credible au detour par le village.

| Liaison | Duree | Role |
|---------|-------|------|
| Village ↔ Foret | 5 min | Porte d'entree du contenu hostile |
| Village ↔ Marais | 7 min | |
| Village ↔ Mines | 10 min | |
| Village ↔ Crete | 15 min | Zone la plus rude, la plus eloignee du hub |
| Foret ↔ Marais | 5 min | Anneau |
| Foret ↔ Mines | 8 min | Anneau |
| Mines ↔ Crete | 8 min | Anneau |
| Marais ↔ Crete | 9 min | Anneau |

**Pourquoi un anneau** : avec une pure etoile, tout trajet passait par le hub et le choix d'itineraire n'existait pas — la reduction de temps de voyage des montures (tache 130) n'aurait rien eu a optimiser. Regle de calibrage : une liaison peripherique doit couter **moins** que la somme des deux branches par le hub, sinon elle ne sera jamais empruntee.

### Tables d'exploration

Chaque zone sauvage declare ses poids de rencontre (`explore.weights`) et sa variance nocturne (`explore.night`, ZON-17). Principes de calibrage :

- **Total libre** : les poids sont relatifs, ils n'ont pas a sommer a 100.
- **La nuit durcit** : le poids `mob` monte, `harvest` et `pnj` descendent, et les coffres paient mieux — le risque nocturne doit se voir dans le butin.
- **`night.mob_slugs`** restreint le vivier nocturne a un pool thematique (constructs aux Mines, creatures corrompues au Marais, creatures ailees a la Crete). Si aucun mob du pool n'est present dans la zone, le vivier complet est conserve : le filtre ne peut pas vider une zone.
- **Profil par zone** : Mines = recolte (`harvest` eleve), Marais = hostile et pauvre en butin, Crete = le plus hostile mais les meilleurs coffres.

### Filons

13 filons repartis sur les 4 zones sauvages, par profession (`mining`, `herbalism`, `fishing`). Regle : plus la ressource est rare, plus la **capacite** est faible et le **respawn** long (`ore-gold` : capacite 8, respawn 1 h). La capacite etant un stock **partage** entre joueurs, elle est aussi le levier de tension d'une ressource sur un serveur peuple.

---

## 12. Plancher T1 anti cold-start (economie joueur, ECO-02)

L'economie visee est une economie de **production joueur** : a terme, l'essentiel
de l'equipement vient d'autres joueurs. Le risque structurel d'un tel modele est le
**cold-start** — marche vide, ou ingredient que personne ne produit, et le nouveau
venu se retrouve bloque sans recours.

**La regle** : tout ingredient d'une recette de **premier palier** (`required_level: 1`)
doit etre accessible en solo, par au moins une source que le joueur controle seul —
filon de zone, boutique PNJ, butin de monstre, ou recompense de quete. Le garde-fou
est `tests/Integration/Economy/ColdStartFloorTest`.

### Les quatre etages du plancher

Un metier n'est accessible que si les **quatre** conditions sont reunies. L'audit
ECO-02 a trouve les quatre en defaut, chacune de facon silencieuse :

| Etage | Regle | Etat trouve par l'audit |
|-------|-------|-------------------------|
| Ingredients | Chaque ingredient T1 a une source solo | 7 recettes niv. 1 sur 13 irrealisables (`ore-tin`, `plant-chamomile`, `leather-raw` sans aucune source) |
| Outil equipable | Un skill accorde `equip.tool` pour l'outil du metier | **Aucun** des 4 arbres d'artisanat ne l'accordait — l'emplacement s'ouvrait, rien ne pouvait y entrer |
| Outil achetable | Cet outil est vendu par un PNJ **rattache a une zone** | Les outils n'etaient vendus que par un PNJ hors graphe, invisible depuis l'ecran de zone |
| Recette d'entree | Le skill d'entree debloque une recette qui **existe** | 2 metiers sur 4 pointaient vers un slug de recette inexistant |

### Calibrage du stock PNJ

Le plancher PNJ se vend **sans limite de stock** (`shopStock` non renseigne). C'est
deliberé : un stock fini se vide, et le joueur qui arrive apres se retrouve face a un
marche joueur qu'il ne peut pas encore alimenter. Le plancher n'a pas vocation a etre
competitif — il est volontairement au **prix fort et a la qualite minimale** (bronze),
pour que la production joueur reste plus interessante des le second palier.

### Reste a reconcilier

Les arbres de talent et les recettes ont ete ecrits separement : **35 slugs de recette
cites par des skills n'existent pas**, et **39 recettes livrees ne sont debloquees par
aucun skill**. ECO-02 n'a traite que le plancher (une porte d'entree par metier) ; la
reconciliation complete est un chantier a part.

---

## 13. Marches regionaux (economie joueur, ECO-03)

L'hotel des ventes est **segmente par region** (decision D13). Une annonce
appartient au marche ou elle a ete deposee, et on n'accede a un marche qu'en
s'y rendant.

### Pourquoi strict

Un marche global taxe aurait laisse la geographie sans effet : un seul prix
partout, aucun arbitrage, et le graphe de zones sans role economique. Avec la
segmentation, le **transport n'est pas un systeme a part** — c'est le temps de
voyage du graphe, deja paye en energie et en minutes. Rien a ajouter pour que la
distance ait un cout.

### Decoupage livre

| Region | Cartes | Taxe | Role |
|--------|--------|------|------|
| Plaines de l'Eveil | le Fanal (capitale), Foret des murmures | 5 % | Marche de depart : la demande, les nouveaux joueurs |
| Terres Sauvages | Mines profondes, Marais Brumeux, Crete de Ventombre | 8 % | Front pionnier : la matiere premiere rare |
| Sanctuaire de la Voute | *(aucune carte)* | 0 % | Region declaree, non contestable, sans contenu a ce jour |

**L'ecart de taxe est le levier d'arbitrage** : la matiere se recolte au nord (8 %),
la demande est au sud (5 %). Vendre sur place est immediat mais coute plus cher ;
porter la marchandise aux Plaines rapporte davantage, au prix du voyage. Regle de
calibrage : l'ecart de taxe entre deux regions doit rester **inferieur** a la marge
qu'un joueur peut esperer sur le trajet, sinon personne ne transporte jamais rien.

Avant ECO-03, une seule region portait des cartes : un joueur en foret, aux mines,
au marais ou sur la crete n'appartenait a **aucune** region — la taxe regionale y
etait donc nulle et aucune guilde ne percevait quoi que ce soit sur ses ventes.

### Exceptions

Les **ventes flash** sont un canal systeme (promotion serveur, cree par un admin) :
elles portent une region pour la taxe mais restent visibles et achetables partout.
Segmenter une promotion serveur la reduirait a une fraction des joueurs.

Les joueurs **hors region** (personnage pas encore rattache a une zone du graphe)
forment un marche a part, entre eux. Ce n'est pas un marche global de repli : sans
cette symetrie, un personnage hors graphe verrait l'integralite des marches.

---

## 14. Taxe de l'hotel des ventes (economie joueur, ECO-04)

La taxe regionale de l'HV branche le marche sur le controle de cite (decision D4).
Quatre parties peuvent toucher aux Gils d'une vente : l'acheteur, le vendeur, la
guilde controlante, et le neant.

### Repartition

| Montant | Formule |
|---------|---------|
| Taxe | `floor(prix × taux de la region)` |
| Revenu du vendeur | `prix − taxe` — **toujours**, quel que soit l'acheteur |
| Ristourne membre | `min(taxe, floor(prix × 10 %))` si l'acheteur est membre de la guilde controlante |
| Prix paye par l'acheteur | `prix − ristourne` |
| Tresor de la guilde | `taxe − ristourne` |
| Gils detruits | `taxe` si **aucune** guilde ne controle la region |

### Deux invariants

**Le vendeur ne depend jamais de l'identite de l'acheteur.** Il touche toujours
`prix − taxe`. Sans cela son revenu varierait selon l'appartenance de guilde de
l'acheteur — impossible a anticiper au moment de fixer un prix, et une source de
frustration opaque.

**La ristourne est plafonnee par la taxe.** La guilde ne peut reverser que ce
qu'elle preleve. Au-dela, la remise se financerait sur le tresor a chaque
transaction : une fuite, pas un avantage. Consequence de calibrage : dans une
region a faible taux (Plaines, 5 %), la ristourne membre plafonne a 5 % et non
10 % — l'avantage d'adherer croit avec le taux que la guilde impose.

### Le gold sink

Une region sans guilde controlante **detruit** la taxe. Les Gils sont retires a
l'acheteur et ne sont pas verses au vendeur : sans guilde pour les recevoir, ils
sortent du jeu. C'est deliberé et journalise explicitement — les rendre au vendeur
ferait de la taxe une illusion d'affichage, et supprimerait le seul gold sink
adosse au volume d'echange entre joueurs.

Corollaire de game design : **conquerir une region convertit un gold sink en
revenu de guilde**. C'est l'incitation economique du controle de cite, la ou GCC
n'offrait jusqu'ici que des bonus de zone.

### Le cas des encheres

La ristourne ne peut pas etre deduite au moment de la mise : l'issue de l'enchere
n'est pas connue, et les Gils sont deja verrouilles en escrow. Elle est donc
**rendue au gagnant** a la finalisation. Le montant reellement paye est conserve
sur la transaction (`member_rebate_amount`) plutot que recalcule : le taux, le
controle de la region et l'appartenance de l'acheteur peuvent tous changer apres
coup, et la detection d'anomalies (ECO-16) a besoin du montant consenti, pas d'une
reconstitution.

---

## 15. Chaine de production entre metiers (economie joueur, ECO-14)

Une economie de production joueur ne tient que si les metiers ont besoin les uns
des autres. **Un metier autosuffisant produit un joueur autosuffisant** : il n'a
rien a acheter, donc rien a vendre non plus, et le marche se vide.

### L'etat trouve par l'audit

Trois metiers sur quatre etaient **entierement autosuffisants** — forgeron,
tanneur, et alchimiste a une seule recette de niveau 10 pres, donc en pratique.
Seul le joaillier consommait la production d'un autre metier, et seulement a
partir du **niveau 6**. Un joueur pouvait monter un metier complet du niveau 1 au
niveau 10 sans jamais acheter quoi que ce soit a personne.

### La chaine livree

| Metier | Consomme | Est consomme par |
|--------|----------|------------------|
| Forgeron | tanneur (niv 2) | tanneur, joaillier |
| Tanneur | alchimiste (niv 2), forgeron (niv 3) | forgeron, joaillier |
| Joaillier | tanneur (niv 4), alchimiste (niv 5), forgeron (niv 6) | alchimiste |
| Alchimiste | joaillier (niv 5) | tanneur, joaillier |

Les six liaisons sont thematiquement evidentes, jamais arbitraires — c'est la
condition pour qu'un joueur les accepte plutot que de les subir :

- **Bouclier de fer** ← laniere de cuir : les enarmes. Un bouclier sans sangle ne
  se porte pas.
- **Armure de cuir** ← base de potion : le bain de tannage. On ne travaille pas
  une peau sans chimie.
- **Plastron de cuir renforce** ← lingot de bronze : les rivets. « Renforce »
  veut dire renforce **par du metal**.
- **Amulette d'or** ← laniere de cuir : le cordon. Une amulette se porte au cou.
- **Enchantement de gemme** ← base de potion : le bain d'enchantement.
- **Elixir de vitalite** ← gemme brute taillee : la gemme catalytique.

### Deux regles de calibrage

**Jamais au palier d'entree.** Croiser les metiers au niveau 1 rendrait le premier
craft dependant d'un autre joueur — exactement le cold-start que le plancher T1
(§12) existe pour empecher. La premiere dependance apparait au **niveau 2**.

**Quantite 1 aux paliers bas.** L'interdependance doit creer de la demande, pas de
la friction. Un ingredient externe en quantite 1 au niveau 2-3 se troque ou
s'achete sans bloquer la progression ; au-dela, il devient un mur.

Le garde-fou est `tests/Integration/Economy/CraftInterdependenceTest`, qui verifie
les deux sens (chaque metier consomme **et** est consomme) ainsi que l'immunite du
palier d'entree.

---

## 16. Garde-fous anti-abus de l'hotel des ventes (economie joueur, ECO-16a)

Les canaux d'echange entre joueurs sont la surface d'attaque naturelle d'une
economie de production. Ces regles se posent **avant** l'ouverture des canaux
suivants (commandes de craft, echoppes) : une economie qu'on assainit apres coup
oblige a arbitrer entre corriger l'exploit et spolier les joueurs de bonne foi.

### Regle 1 — pas de commerce entre personnages d'un meme compte

Le jeu autorise plusieurs personnages par compte (regle projet #12). L'hotel des
ventes ne refusait que la vente **a soi-meme**, comparee par identifiant de
personnage : deux personnages d'un meme joueur pouvaient s'echanger objets et
Gils librement, et surtout **inscrire au marche des prix qu'aucune transaction
reelle n'a valides**. C'est la faille la plus simple a exploiter et la plus
destructrice pour un historique de prix.

La regle n'a aucun faux positif : l'appartenance de compte est un fait, pas une
heuristique.

### Regle 2 — plafond d'echanges par couple de joueurs

| Parametre | Defaut | Effet |
|-----------|--------|-------|
| `pairTransactionCap` | 10 | ventes conclues entre deux joueurs sur la fenetre |
| `pairWindowHours` | 24 | largeur de la fenetre glissante |

Le plafond porte sur le **couple**, pas sur le joueur : il ne gene pas un joueur
qui commerce largement, seulement celui qui commerce toujours avec la **meme**
personne — la signature du blanchiment entre complices, que le controle de compte
ne peut pas attraper. Le comptage est bidirectionnel : un aller-retour est
precisement le motif recherche.

Un plafond a `0` **desactive** la regle plutot que de tout bloquer : c'est le
comportement attendu d'un seuil de configuration mis a zero, et cela evite qu'une
mauvaise valeur ne ferme le marche entier.

### Exception : les ventes flash

Le vendeur y est l'administration. Leur appliquer les regles anti-blanchiment
reviendrait a plafonner une promotion serveur.

### Ou s'applique le controle

A l'**achat** et a la **mise**, jamais a la finalisation d'enchere : celle-ci est
declenchee par l'expiration et non par un joueur — y refuser l'operation
laisserait l'objet et les Gils bloques indefiniment.

### Escrow — etat des lieux

L'escrow de l'hotel des ventes etait deja complet et le reste : l'objet quitte
l'inventaire au depot et revient au vendeur a l'annulation comme a l'expiration ;
les Gils d'une mise sont verrouilles chez l'encherisseur et rembourses a la
surenchere. ECO-16a n'a rien eu a construire de ce cote. Le retour d'objet a **l'expiration** est
desormais couvert lui aussi. Le blocage n'etait pas la testabilite mais la **place**
de la requete : elle vivait dans `AuctionManager`, seul endroit du service a en
construire une, la ou les sept autres lectures d'annonce etaient deja dans le depot.
Deplacee dans `AuctionListingRepository::findExpirable()`, le chemin se teste en
unitaire sans simuler quoi que ce soit de Doctrine.

---

## 17. Arbres de talent et recettes (ECO-18)

Les arbres de talent et les recettes ont ete ecrits separement et jamais croises.
Le defaut est **totalement silencieux** : un skill qui cite un slug de recette
inexistant s'apprend normalement, le joueur depense ses points, et aucune recette
n'apparait. Symetriquement, une recette qu'aucun skill ne debloque est du contenu
livre mais inatteignable.

### Etat trouve par l'audit

| Sens | Avant | Apres |
|------|-------|-------|
| Slugs cites par un skill sans recette livree | 33 | 17 (ECO-18) → **0** (ECO-19) |
| Recettes livrees qu'aucun skill ne debloque | 37 | 1 (ECO-18) → **0** (ECO-19) |
| Paliers d'outils d'artisanat equipables | bronze seul | bronze → fer → acier → mithril |

### Ce qui a ete recable, et ce qui ne l'a pas ete

Les rattachements **evidents** ont ete faits : un skill dont le titre decrit deja
l'objet a ete branche sur la recette correspondante (« Forge de plaques » →
plastron, jambieres, casque, gantelets, bottes de fer ; « Maitre forgeron » → lame
du maitre ; « Alliages speciaux » → les quatre lingots ; etc.).

Les **17 slugs restants** correspondaient a du contenu qui n'existait pas : aucune
recette d'acier, de cuir de dragon, de carquois, de pierre a aiguiser, ni d'elixir
de vitesse ou de transmutation n'avait jamais ete ecrite. Elles ont ete ecrites en
**ECO-19** (voir §19), et `recipe-poison-vial` a rejoint le nœud « Concentration
alchimique ». Les deux listes d'exception sont vides.

### Progression des outils d'artisanat

`equip.tool` suit desormais le meme motif que les arbres de recolte : le palier
s'ouvre au rang qui le merite.

| Metier | bronze | fer | acier | mithril |
|--------|--------|-----|-------|---------|
| Forgeron | 0 pt | 10 pts | 25 pts | 60 pts |
| Tanneur | 0 pt | 10 pts | 25 pts | 60 pts |
| Alchimiste | 0 pt | 10 pts | 25 pts | 60 pts |
| Joaillier | 0 pt | 10 pts | 25 pts | 50 pts |

Avant ECO-18, seul le bronze etait equipable : un artisan de 150 points travaillait
avec le meme outil qu'au premier point depense.

### Le garde-fou

`tests/Integration/Economy/SkillRecipeConsistencyTest` croise les deux jeux de
donnees **dans les deux sens**, verifie qu'un outil vendu par un PNJ est toujours
equipable, et — surtout — verifie que **les exceptions declarees en sont encore**.
Sans ce dernier controle, les listes de dette survivraient au probleme qu'elles
decrivent.

---

## 18. Journal economique et moderation (ECO-16b)

Les regles d'ECO-16a (§16) refusent ce qui est **certainement** abusif : commerce
entre personnages d'un meme compte, echanges repetes entre deux joueurs. Restent
les cas qui ne se prouvent pas a la transaction et ne se voient qu'a l'echelle.
Ceux-la ne se bloquent pas, ils **se donnent a voir**.

### Trois signaux, aucune preuve

| Signal | Ce qu'il montre | Ce qu'il ne prouve pas |
|--------|-----------------|------------------------|
| Couples les plus actifs | Ce qui se passe **sous** le plafond d'ECO-16a | Deux amis peuvent commercer beaucoup |
| Prix aberrants (×5 vs moyenne de l'objet) | Une vente hors de toute logique de marche | Un objet rare bien negocie en produit un aussi |
| Volume quotidien (14 j) | Un pic soudain | Un evenement serveur en produit un aussi |

Le seuil de detection d'un prix aberrant exige **au moins 3 ventes** de l'objet
sur la fenetre : en dessous, la moyenne n'a aucune valeur de reference et le
signal serait du bruit.

Aucun de ces signaux ne declenche d'action automatique. C'est deliberé : une
sanction automatique sur un signal ambigu punit d'abord les joueurs atypiques.

### Deux sanctions, deux echelles

**Annulation d'annonce** — l'objet revient au vendeur et **la mise en cours est
remboursee**. La moderation retire une annonce ; elle ne confisque pas les Gils
d'un encherisseur qui n'a rien fait. C'est la seule difference de fond avec
l'annulation par le vendeur, qu'une enchere en cours bloque : une annonce
frauduleuse doit pouvoir disparaitre meme si quelqu'un a mise dessus.

**Suspension d'acces au marche** — le bannissement de compte existe deja mais
coupe tout. Un joueur qui truque des prix doit pouvoir continuer a jouer pendant
que le marche lui est ferme. La suspension **expire d'elle-meme** : une sanction
qu'il faut penser a lever finit par ne jamais l'etre.

La suspension ferme le marche entier, ventes flash comprises — c'est l'acces au
canal qui est suspendu, pas seulement le commerce entre joueurs.

---

## 19. Le palier acier et les paliers manquants (ECO-19)

ECO-18 avait rattache tout ce qui pouvait l'etre ; restaient **17 slugs cites par
des skills sans qu'aucune recette ne porte le nom**, et **15 objets resultats
inexistants**. Un joueur pouvait acheter le skill « Forge d'acier » et ne rien voir
apparaitre dans son etabli.

### Ce qui a ete ecrit

| Metier | Recettes | Paliers |
|--------|----------|---------|
| Forgeron | cotte de mailles de fer, epee de fer, pierre a aiguiser, dague / epee / cotte / plastron d'acier, hache d'acier, harnois lourd | 1 → 7 |
| Tanneur | carquois de cuir, carquois renforce, plastron et bottes d'ecailles de dragon, plastron enchante | 3 → 8 |
| Alchimiste | potion d'energie standard, elixir de vitesse, transmutation en mithril | 3 → 7 |

### Trois regles de calibrage

**L'acier ne passe pas par un lingot.** Ajouter un `crafted-steel-ingot` aurait cree
une recette qu'aucun skill ne debloque — exactement le defaut qu'ECO-18 venait de
corriger. L'acier se forge donc directement : `ore-iron` + `ore-cobalt`, le cobalt
jouant le role du carbone.

**Chaque palier ≥ 2 porte une dependance croisee quand le theme la rend evidente**
(ECO-14) : la poignee de cuir d'une epee, l'huile d'affutage d'une pierre, le
cercle de bronze d'un carquois, la gemme de visee d'un harnois. La seule recette de
niveau 1 (`recipe-iron-chainmail`) n'utilise que du minerai brut — le palier
d'entree doit rester realisable en solo (ECO-02).

**La transmutation produit du mithril.** C'est le choix le plus structurant du
jalon : `ore-mithril` est consomme par cinq recettes de forge et de joaillerie mais
**aucun filon du monde livre n'en donne**. Plutot que d'ajouter un filon — ce qui
aurait banalise le materiau le plus rare — la transmutation alchimique en devient la
**seule source**, au prix de trois lingots d'argent, deux d'or et une gemme rare.
Le mithril devient ainsi un produit d'artisanat, pas de recolte : il oblige un
forgeron a passer par un alchimiste, et donne a l'alchimie un role economique au
palier haut qu'elle n'avait pas.

> **Amende par ECO-24b (2026-07-28)** : la transmutation n'est plus la **seule** source.
> La carte des minerais ([GAME_ZONES.md](GAME_ZONES.md) §3) pose un filon de mithril T4 au
> sommet de la Crete de Ventombre — « le metal que le vent a mis a nu ». Ce qui motivait
> le choix d'ECO-19 tient toujours : le mithril reste rare (profil T4, source unique) et
> l'alchimie garde son debouche de haut palier, en **seconde** voie. Ce qui a change,
> c'est le constat de depart — « aucun filon du monde livre n'en donne » etait vrai des
> filons declares, mais deux spots `ObjectLayer` de mithril existaient bel et bien sur
> `map_4` : la premiere phrase de ce paragraphe decrivait la moitie du monde que le jalon
> regardait.

---

## 20. Masse monetaire et inflation (economie joueur, ECO-15)

ECO-15 demandait une « alerte d'inflation, ratio entrees/sorties de Gils ». Mesurer ce
**flux** supposerait que toute creation et toute destruction de Gils passe par un point
unique — or **26 fichiers** appellent `addGils()` ou `removeGils()` directement. Les
canaliser serait une refonte, et une refonte ne mesure rien tant qu'elle n'est pas finie.

Le **stock** repond a la meme question sans toucher a un seul appelant : l'inflation, c'est
la masse monetaire qui gonfle. Il a meme un avantage que le flux n'a pas — il est
naturellement insensible a la velocite. Cent ventes entre joueurs deplacent des Gils sans
en creer un seul, et ne bougent donc pas la mesure d'un centieme.

### Ce que la masse comprend

| Poste | Source | Pourquoi |
|-------|--------|----------|
| Bourses des joueurs | `player.gils` | le gros du stock |
| Tresors de guilde | `guild.gils_treasury` | taxe d'HV percue, cotisations |
| Caisses d'echoppe | `player_shop.vault_gils` | ventes non encore relevees |
| **Escrow** | `auction_listing.current_bid` (actives), `craft_order.commission` (ouvertes ou prises en charge) | **sortis d'une bourse, pas encore arrives dans une autre** |

L'escrow est le poste qu'on oublie. Ces Gils ont quitte la bourse du joueur sans etre
detruits : ils vivent comme un nombre sur une enchere ou une commande, et ressortiront a la
resolution. Les omettre ferait lire une **deflation a chaque fois que le marche se
remplit**.

Cote enchere, la condition retenue n'est pas le **type** de l'annonce mais la presence
d'une mise : dans `AuctionManager::placeBid()`, poser `current_bid` et retirer les Gils du
misant sont le meme geste. Un type futur qui accepterait des mises est compte sans qu'on ait
a y penser.

### Pourquoi « par personnage »

Le total brut ne dit rien : il monte quand la population monte, et ce n'est pas de
l'inflation. Une masse par tete qui gonfle signifie que les robinets versent plus que les
puits n'absorbent.

Le seuil d'alerte est de **±15 % par semaine** (`GilsSupplyService::WEEKLY_ALERT_PERCENT`).
Il est calibre **a partir de rien** — aucune mesure n'existait au moment de l'ecrire. C'est
un point de depart declare, a corriger des que la premiere semaine de releves est lisible,
pas une valeur derivee.

La deflation declenche la meme alerte : une masse qui fond aussi vite qu'elle gonflerait
signale des puits trop gourmands, pas une economie saine.

### Robinets (creation de Gils)

| Source | Ou |
|--------|-----|
| Coffres d'exploration de zone | `ExploreService` (`chest_gils_min`/`max`, § 10) |
| Expeditions | `ExpeditionService` |
| Recompenses de quete | `QuestController` |
| Donjons de groupe | `GroupDungeonRewardService` (`zone.dungeon.reward.base_gils` = 150) |
| Succes | `AchievementTracker` |
| Quetes de guilde | `GuildQuestManager` (reparti entre les membres) |
| Invasions | `InvasionManager` |
| Vente d'objet a un PNJ | `ShopController` (ratio de vente 0,3 — § 3) |

### Puits (destruction de Gils)

| Sortie | Montant | Ou |
|--------|---------|-----|
| Reparation d'equipement | 10 × multiplicateur de rarete | `GoldSinkManager` |
| Voyage rapide | 100 de base | `GoldSinkManager` |
| Renommage d'objet | 50 | `GoldSinkManager` |
| Achat de terrain | 25 000 | `HousingManager` |
| Loyer de demeure | 500 / periode | `HousingManager` |
| Loyer d'echoppe | 1 000 / 7 jours | `ShopRentService` |
| Etal de place | 5 000, escalade | `ShopStallService` |
| Creation de guilde | 5 000 | `GuildManager` |
| Enchantement | cout de la definition | `EnchantmentManager` |
| Reinitialisation de talents | cout progressif | `SkillRespecManager` |
| Monture | prix de la monture | `MountPurchaseService` |
| Achat d'objet a un PNJ | prix boutique | `ShopController` |
| **Taxe d'HV en region sans guilde** | taux de region | `AuctionManager` (§ 14) |

Le dernier est le seul puits **adosse au volume d'echange entre joueurs** : il grandit
quand l'economie tourne, la ou tous les autres sont des couts fixes ou par acte. Conquerir
une region le convertit en revenu de guilde (§ 14) — le puits se referme, et c'est
deliberé.

### Comment lire la mesure

```bash
docker compose exec php php bin/console app:economy:snapshot            # releve (quotidien, 00h10)
docker compose exec php php bin/console app:economy:snapshot --dry-run  # mesure sans ecrire
docker compose exec php php bin/console app:balance:report -s economy   # repartition + tendance
```

Le releve est **planifie**, pas calcule a la demande : les Gils du passe ne sont consignes
nulle part, une tendance ne se reconstitue pas apres coup. Il tourne **apres** le tick de
saison (00h05) — les recompenses de cloture doivent etre versees avant qu'on compte, sinon
la masse saute d'un cran une fois par saison sans qu'aucun robinet ait coule.

Tant qu'il n'y a qu'un seul releve, aucune tendance n'est affichee. Inventer un zero de
depart ferait apparaitre une inflation infinie au premier jour.

---

## 21. Chaine de production par paliers — audit (ECO-24)

> Livrable d'**ECO-24**. Cartographie des 82 recettes livrees, a la recherche des
> **paliers orphelins** : une recette de haut palier dont aucun intrant ne vient d'un
> palier inferieur.
>
> *Note (2026-07-29) : les compteurs de cette section datent de l'audit — le catalogue
> compte desormais **114 recettes** (ECO-29/30/31 + ZON-35).* Un palier orphelin tue la demande en matiere de debut de jeu des que
> les joueurs atteignent le haut de l'echelle — c'est le mecanisme du **creux du milieu**
> (cf. [GAME_WORLD.md](GAME_WORLD.md) §5.5).

### 21.1 Etat des lieux

| Categorie | Nombre | Lecture |
|---|---:|---|
| Recettes chainees (consomment une sortie d'une autre recette) | **54** | La chaine horizontale est saine |
| Orphelines de niveau 1-2 | **22** | **Voulu** — le palier d'entree doit rester realisable en solo (ECO-02) |
| **Orphelines de niveau >= 3** | **6** | **Le defaut a corriger** |

La conclusion est meilleure qu'attendu : la chaine est batie **horizontalement** (les
biens finis consomment bien des intermediaires) mais **plate verticalement** — les
intermediaires ne se consomment pas entre eux.

### 21.2 Les six paliers orphelins

| Metier | Niveau | Recette | Intrants actuels |
|---|---:|---|---|
| Forgeron | 3 | `recipe_cobalt_ingot` | `ore-cobalt` seul |
| Forgeron | 4 | `recipe_steel_chainmail` | `ore-iron`, `ore-cobalt` |
| Forgeron | 6 | `recipe_adamantite_ingot` | `ore-adamantite`, `ore-darksteel` |
| Forgeron | 8 | `recipe_orichalcum_ingot` | `ore-orichalcum`, `ore-starmetal` |
| Alchimiste | 3 | `recipe_poison_vial` | `plant-nightshade`, `poisonous-mushroom` |
| Tanneur | 10 | `recipe_masterwork_drakehide_cloak` | trois cuirs bruts |

**Quatre des six sont l'echelle de raffinage du metal elle-meme.** C'est la ou tout se
joue : un lingot d'orichalque de niveau 8 ne doit rien, aujourd'hui, a ce qu'un debutant
extrait. Le cas du tanneur (niveau 10, trois cuirs bruts) est le plus voyant du lot.

**Le precedent existe deja dans le code** : `recipe_mithril_ingot` n'est *pas* orpheline,
parce qu'ECO-19 a fait de la transmutation alchimique la seule source de `ore-mithril`
(cf. §19). Le mithril est donc deja un produit d'artisanat et non de recolte — c'est
exactement la forme visee, appliquee a un seul palier.

### 21.3 Chaine cible (ligne du metal) — ✅ **appliquee le 2026-07-28 (ECO-25)**

```
bronze (niv 1)  <- ore-copper x2 + ore-tin x2                     [inchangee : palier d'entree]
fer (niv 2)     <- ore-iron x3        + 1 lingot de bronze        [barreau ajoute par ECO-25]
cobalt (niv 3)  <- ore-cobalt x3      + 1 lingot de fer
mithril (niv 4) <- ore-mithril x3 + ore-platinum x1 + 1 lingot de cobalt
adamantite (6)  <- ore-adamantite x3 + ore-darksteel x2 + 1 lingot de mithril
orichalque (8)  <- ore-orichalcum x3 + ore-starmetal x2 + 1 lingot d'adamantite
```

**Ecart assume : le cobalt se chaine sur le fer, pas sur le bronze.** La chaine cible
d'origine sautait le fer parce que `crafted-iron-ingot` etait un **objet mort** — ni
producteur, ni consommateur (§21.7) — et qu'on ne chaine pas sur ce qui n'existe pas.
ECO-25 le reveille : la fonte du fer, le geste le plus banal d'une forge, manquait au jeu.
L'echelle devient **continue** (bronze → fer → cobalt → mithril → adamantite → orichalque),
le bronze garde un consommateur, et l'ordre des niveaux (1 → 2 → 3) est respecte.

Le **platine** et le **sombracier** restent dans leurs recettes : ECO-24b-a les a poses en
filons precisement pour que le sommet du jeu de base exige un commerce nord-sud, et qu'un
lingot d'extension doive quelque chose au jeu de base (§21.5).

### 21.4 Le coefficient est le seul reglage qui compte

Le risque de la mecanique est l'inverse de celui qu'elle corrige : **ecraser les filons
de debut sous la demande de fin de jeu**. Il se regle entierement par le nombre d'unites
du palier inferieur exigees a chaque etage, parce que l'effet est **multiplicatif** sur
la profondeur de la chaine.

Debit soutenu des filons concernes (`R = capacity x 3600 / respawn_seconds`) :
cuivre **176 u/h** (deux filons : Mines T0 + Dunes T1), etain **96 u/h** (un seul filon,
Mines T0), cobalt **32 u/h** (un seul filon, Crete T2).

| Coefficient | Ce qu'un lingot d'orichalque entraine | Plafond monde | Goulot |
|---:|---|---|---|
| **1** | 1 lingot de bronze = **2 cuivre + 2 etain** | 48 orichalque/h (etain), **10,7/h (cobalt)** | **cobalt** — palier intermediaire |
| 2 | 16 lingots de bronze = **32 cuivre + 32 etain** | 3 orichalque/h (etain), 1,3/h (cobalt) | cobalt, mais l'etain devient critique |

**Decision : coefficient 1 a chaque etage, jamais 2.** A 1, l'entrainement sur la matiere
de base reste faible (2 cuivre par lingot de fin de chaine) alors que la demande devient
**permanente** — c'est exactement la propriete recherchee. A 2, la chaine consomme 16 fois
plus et la matiere de debut devient le goulot du jeu.

**Et le goulot reel n'est pas la matiere de base** : c'est le **cobalt**, palier
intermediaire a filon unique. C'est une bonne nouvelle de conception — le point de tension
tombe au milieu de l'echelle, la ou l'on veut precisement garder de l'activite.

### 21.5 Deux defauts decouverts en chemin

> **Statut (2026-07-28, ECO-24b livre)** : les defauts **a** et **b** sont corriges pour le
> jeu de base. Cinq filons poses selon la carte des minerais
> ([GAME_ZONES.md](GAME_ZONES.md) §3) — sombracier (Mines, fond, T4), mithril (Crete,
> sommet, T4), platine et 2e etain (Dunes, T3/T0), orichalque (Cite ensevelie, T4).
> `ore-adamantite` et `ore-starmetal` restent sans source **a dessein** : la carte les
> reserve a l'Extension 1. La loi transverse est verrouillee par
> `OreSourceReferenceTest`, dont la liste de reserves d'extension doit rester courte et
> se vider a mesure que les extensions sortent.
>
> **Ce que la mesure a montre en plus** : le chemin herite n'etait pas seulement « hors du
> modele calibre », il etait **injouable**. Ni `/game/harvest/{spotId}` ni
> `/api/gathering/harvest/{spotId}` n'est cite par un gabarit ou un controleur Stimulus
> depuis le pivot ; l'ecran de zone se contente de **compter** les points d'interet. La
> moitie haute de la ligne du metal — donc les lingots d'adamantite et d'orichalque —
> etait donc hors d'atteinte, en silence. Meme famille qu'ECO-02 et ECO-19.
>
> **Resolu par ECO-24c (2026-07-28)** : la premiere des deux issues ci-dessous est retenue —
> le gate vit dans le modele declaratif (`requires_skill:` sur un filon de `world_1.yaml`), et
> `GatherService` refuse avant la depense d'energie. Quatre filons de haut palier sont gates
> (sombracier, mithril, platine, orichalque) ; l'adamantite et le metal etoile n'ayant pas de
> filon dans le jeu de base, leurs deux competences restent sans porte a garder jusqu'a
> l'Extension 1. La seconde issue — reconvertir ces competences en bonus de rendement — est
> ecartee : elle aurait supprime le seul endroit du jeu ou un arbre de recolte ouvre une porte,
> et la « zone comme gate » ne tient pas, la Crete et les Mines etant accessibles des les
> premieres heures. Detail dans [ROADMAP_DONE.md](ROADMAP_DONE.md).
>
> **Etat d'origine, conserve pour reference** : les six competences hautes de l'arbre du mineur
> gatent des `spot-*` d'`ObjectLayer` (`miner-mithril-xs`, `miner-platinum-xs`,
> `miner-darksteel-xs`, `miner-adamantite-xs`, `miner-starmetal-xs`,
> `miner-orichalcum-xs`). Or `GatherService` **n'a aucun gate de competence** : il rend
> les filons d'une zone sans jamais consulter le joueur. Un filon declare est donc
> accessible a quiconque a l'energie, et ces six competences sont **decoratives** — elles
> ne survivent que dans l'ecran d'information de domaine. Deux issues possibles : porter
> le gate dans le modele declaratif (`requires_skill:` sur un filon), ou assumer que la
> zone **est** le gate (survivre au Glacier vaut bien un palier de competence) et
> reconvertir ces competences en bonus de rendement. Le choix n'appartient pas a ECO-24b.

**a) Deux systemes de recolte coexistent.** Les filons calibres vivent dans
`config/game/zones/*.yaml` (`ZoneVein`, vitalite partagee, paliers T0-T4). Mais
`ore-mithril`, `ore-platinum`, `ore-darksteel`, `ore-adamantite`, `ore-starmetal` et
`ore-orichalcum` **n'ont aucun filon declare** : ils n'existent que comme `ObjectLayer`
(spots de l'ancien systeme de carte, sur `map_4` / Mines profondes), encore servis par
`HarvestController` et exposes par `ZoneController`.

Consequence a trancher avant d'aller plus loin : **le haut de la ligne du metal echappe au
modele calibre**. La purete (ECO-22) se tire depuis la vitalite d'un `ZoneVein`, et la
Paleur (FOY-11) se calcule par `ZoneVein` — ni l'une ni l'autre ne couvre ces minerais.

**a bis) La ressource-titre du jeu n'a aucun filon.** `ore-amethyst-crystal` existe comme
objet, mais sa **seule** source au monde est un spot herite (`spot-amethystite-xs`,
`ObjectLayer` T5, coordonnees `75.2`, Mines profondes). L'amethyste — sur laquelle
reposent la materia (regle 10) *et* le postulat du monde ([GAME_WORLD.md](GAME_WORLD.md) §1)
— est donc hors du modele calibre, presente en un seul point, et servie par le chemin
herite. A trancher au moment de definir les ressources de zone : source unique et rare, ou
**presente partout, la purete seule variant selon le lieu**.

**b) L'etain n'a qu'un seul filon dans le monde.** Le cuivre en a deux (Mines, Dunes),
l'etain un seul, et le bronze exige les deux a parts egales. Regle de conception qui en
decoule :

> **Une matiere de base doit etre presente dans beaucoup de zones ; une matiere de haut
> palier dans tres peu.** Raretes inversees. C'est ce qui dilue la demande de fin de jeu
> sur la carte au lieu de l'ecraser sur une zone, et ce qui pousse les joueurs vers
> l'exterieur.

### 21.6 bis Prix de reference et cout propage (ECO-27, 2026-07-28)

**Le constat.** Croisement des 84 recettes d'alors (au 2026-07-29 : **114 recettes** —
ECO-29/30/31 + ZON-35) avec le prix de reference de leurs intrants :
**28 recettes rendaient un objet valant moins que sa matiere**, de 0,35 (lingot d'orichalque :
750 pour 2 150 de minerai) a 0,98. Raffiner faisait perdre de l'argent, **d'autant plus qu'on
montait** — l'inverse exact d'une economie ou la production est le metier des joueurs.

Le defaut **preexistait** : les prix n'avaient jamais ete derives d'un cout. ECO-25 ne l'a pas
cree, il l'a rendu mesurable — et l'a aggrave mecaniquement, en ajoutant a chaque palier un
intrant chaine que le prix ne refletait pas.

**La regle appliquee** : `prix = cout des intrants + 10 x niveau de recette`, arrondi (au pas de
5 sous 100, de 10 sous 1 000, de 50 au-dela). 43 objets recalibres, point fixe atteint en deux
passes.

**Pourquoi une marge additive, et non un pourcentage.** C'est le meme piege que le coefficient de
chainage (§21.4), et il se manifeste de la meme facon : **une marge en pourcentage compose sur la
profondeur**. A +20 % par palier, la lame de maitre passait de 3 500 a **21 700** — non parce
qu'elle contient plus, mais parce que la marge s'applique six fois. La marge additive est bornee
par construction : chaque palier ajoute une fois le travail de l'artisan, jamais un facteur.

**Les matieres premieres sont hors regle.** Le prix d'un minerai vient de sa rarete, pas de la
recette qui peut accessoirement le produire. Soumettre `ore-mithril` a la loi l'aurait aligne sur
le cout de sa transmutation alchimique — qui est une **seconde voie** (§19), pas la source de sa
valeur — et le filon de la Crete aurait suivi.

**Consequence a surveiller : le robinet de Gils.** Les boutiques PNJ rachetent a **30 %** du prix
de reference (`ShopController`). Multiplier par 3 a 5 la valeur des objets de haut palier
multiplie d'autant ce rachat. Le levier n'est pas le prix — qui dit maintenant la verite sur ce
que contient l'objet — mais **le taux de rachat**, a retendre si la masse monetaire (§20) derive.
Ce jalon ne le touche pas : il corrige d'abord ce qui etait faux, et nomme ce qu'il faudra
regarder.

### 21.6 Suite

ECO-25 applique la chaine cible (§21.3) au coefficient 1. Avant cela, deux prealables
issus de §21.5 : unifier la source des minerais de haut palier (filon declare plutot que
spot herite), et repartir l'etain sur au moins une seconde zone.

### 21.7 Second passage d'audit (2026-07-28) — les chaines cassees hors metal

Le premier audit suivait la ligne du metal. Un second passage, croisant **toutes** les
recettes avec **toutes** les sources (filons, spots herites, butin de monstres, boutiques
PNJ), a revele que chaque metier a un bout de chaine casse :

- **La peche entiere est sans debouche.** Aucune des 82 recettes ne consomme un poisson
  (6 especes). Le pecheur ne peut que vendre au PNJ. **Tranche** : le metier de cuisinier
  (ECO-29, Piste H) devient son debouche. **Solde le 2026-07-29** par ECO-29 : le
  cuisinier consomme chaque poisson — **8 recettes** avec le melange d'epices de ZON-35 —
  et un test transverse verrouille qu'aucun poisson ne redevienne orphelin.
- **Aucune armure tissu n'existe.** Sur les 121 items d'equipement, pas une robe ni une
  piece orientee magie : les domaines de sort s'habillent en cuir et en metal, et aucun
  metier ne les habille. **Tranche** : le tailleur (ECO-31) cree la categorie tissu
  depuis le lin des Vallons ; l'item mort `crafted-cloth` s'y reveille. **Solde le
  2026-07-29** : dix pieces sur quatre paliers, aux memes emplacements que la serie cuir
  et sur l'axe inverse (§ 23.12).
- **La joaillerie ne taille aucune gemme.** `recipe-cut-gem-basic` consomme du **cuivre**,
  la fine de l'**argent**, la rare du **mithril**. Les trois gemmes brutes du monde
  (rubis, emeraude, diamant) ne sont consommees par **rien** — trois filons declares
  produisent des items sans usage. A corriger avec ECO-25.
- **Le tanneur mid/haut est incraftable.** 12 recettes consomment quatre matieres
  (`leather-bone`, `leather-fang`, `leather-dragon-scale`, `leather-werewolf-fur`)
  qu'**aucun monstre ne lache** — seuls des objets finis aux noms proches existent en
  butin. Series « durcie », « dragon » et cape de maitre irrealisables. A corriger par
  les tables de butin (ECO-24b etendu).
- **Le lingot de cobalt n'a aucun consommateur** (produit par `recipe-cobalt-ingot`,
  consomme par rien) — a raccorder avec ECO-25.
- **La ligne du bois n'existe pas.** L'arc et le baton existent en items (butin, PNJ)
  mais aucune recette ne les produit, et aucune ressource bois n'existe. Decision du
  2026-07-28 : creer la recolte du bois (GAME_ZONES §3 bis, jalon ZON-34). **Solde le
  2026-07-29** : ZON-34 a livre la matiere, ECO-30 le charpentier qui la consomme — les
  six armes de bois se fabriquent, et chacune des quatre essences a un debouche (§23.11).
- **Items morts** (ni source ni usage) : 5 plantes (`dreamlily`, `sunblossom`,
  `thunderroot`, `whisperweed`, `wolfsbane`), 2 poissons (`moonfish` en spot herite
  seul, `baby-kraken` sans source), `crafted-iron-ingot`, `crafted-gold-ingot`,
  `crafted-cloth`, `leather-skin-1/2` (doublons ECO-02). A purger ou a reveiller par
  les jalons concernes. **`crafted-cloth` est reveille** par ECO-31 : il est desormais
  produit par le tailleur et consomme par onze recettes, dont une du tanneur.
  **Solde le 2026-07-29** par ZON-35 : les 5 plantes sont purgees, les 2 poissons
  recoivent un filon de palier 4 (§ 23.13). Plus aucune matiere recoltable n'est sans
  debouche, et plus aucune plante ou poisson declare n'est sans source.

---

## 22. Calibrage des filons face a la population reelle

> Consequence de la cible de population actee ([GAME_WORLD.md](GAME_WORLD.md) §13.4) :
> **~50 joueurs actifs quotidiens** comme base de calibrage. Le calibrage actuel a ete pose
> sans cible chiffree ; confronte a celle-ci, il se revele surdimensionne d'un ordre de
> grandeur.

### 22.1 Ce que le monde soutient aujourd'hui

34 filons declares dans `config/game/zones/world_1.yaml`. Debit soutenu
`R = capacity x 3600 / respawn_seconds`, et charge soutenable a 20 recoltes/jour/joueur :

| Palier | Filons | R (u/h) | Recolteurs soutenus **par filon** |
|---|---:|---:|---:|
| T0 fondation (72 / 45 min) | 7 | 96,0 | **115** |
| T1 commun (60 / 45 min) | 10 | 80,0 | **96** |
| T2 peu commun (32 / 60 min) | 8 | 32,0 | **38** |
| T3 rare (24 / 90 min) | 8 | 16,0 | **19** |
| T4 epique (22 / 180 min) | 1 | 7,3 | **9** |

**Total monde : ~1 863 u/h, soit environ 2 200 recolteurs reguliers.**

### 22.2 La charge reelle

En supposant que la moitie des joueurs recolte regulierement :

| Joueurs/jour | Recolteurs | Charge du monde | Effet |
|---:|---:|---:|---|
| 30 | 15 | **0,7 %** | vitalite jamais entamee |
| **50** | **25** | **1,1 %** | vitalite jamais entamee |
| 100 | 50 | 2,2 % | vitalite jamais entamee |
| 200 | 100 | 4,5 % | vitalite jamais entamee |
| 400 | 200 | 8,9 % | vitalite a peine effleuree |

**Toute la couche de rarete est inerte.** La vitalite d'un filon ne descend jamais, donc :
la purete (ECO-22) est toujours au maximum, la **Paleur est mecaniquement impossible**
(FOY-11), l'incitation a s'etaler sur les filons voisins n'existe pas, et la restauration
payee au tresor (FOY-12) n'a rien a reparer. Trois jalons concus pour un monde sous tension
tourneraient a vide.

### 22.3 Cible proposee — ✅ **appliquee le 2026-07-28**

> **Livree.** Les 39 filons de `config/game/zones/world_1.yaml` sont passes aux cinq
> profils recalibres ; chaque palier atterrit a moins de 4 % de sa cible. Le levier
> retenu est la **periode**, pas la capacite : les tampons sont verrouilles par le bas
> (GAME_ZONE_ACTIONS §6.5, contrainte 1 — resister a la rafale d'un joueur seul), et les
> etrangler ferait revenir le defaut de la calibration d'origine.
>
> | Palier | capacite | periode | 1 unite / | R (u/h) | recolteurs (cible) |
> |---|---:|---:|---:|---:|---:|
> | T0 | 72 | 7 h | 5 min 50 | 10,3 | 12,3 (12) |
> | T1 | 60 | 9 h | 9 min | 6,7 | 8,0 (8) |
> | T2 | 32 | 8 h | 15 min | 4,0 | 4,8 (5) |
> | T3 | 24 | 10 h | 25 min | 2,4 | 2,9 (3) |
> | T4 | 22 | 18 h | 49 min | 1,2 | 1,5 (1,5) |
>
> **Ce recalibrage n'etait applicable qu'apres ZON-37.** Sous l'ancien moteur — repousse
> tout-ou-rien declenchee au zero seulement — ces memes chiffres auraient rendu un filon
> T0 vide **muet pendant sept heures** avant de resurgir plein d'un coup. La repousse
> etant desormais continue, la meme division du debit produit une tension **douce** : le
> rendement baisse quand le filon est presse, et remonte tout seul.
>
> Le facteur de monde (FOY-17b) met ces capacites a l'echelle de la population reelle :
> ce calibrage est celui de `W = 1`.

Le bon reglage ne s'exprime pas en capacite absolue mais en **nombre de recolteurs qu'un
filon soutient**. A 50 joueurs quotidiens, on veut que les filons frequentes montrent une
tension visible sans jamais bloquer personne (rappel : **une recolte n'echoue jamais**, seul
le rendement varie — cf. GAME_ZONE_ACTIONS §6.6).

| Palier | Soutenus aujourd'hui | Cible a 50 DAU | Facteur |
|---|---:|---:|---:|
| T0 fondation | 115 | **~12** | ÷10 |
| T1 commun | 96 | **~8** | ÷12 |
| T2 peu commun | 38 | **~5** | ÷8 |
| T3 rare | 19 | **~3** | ÷6 |
| T4 epique | 9 | **~1,5** | ÷6 |

T0 reste le plus genereux : il gate le plancher T1 de l'economie (cuivre, etain, menthe,
truite) et **ne doit jamais etre un goulot**, meme sous tension.

### 22.4 Calibrage dynamique — le facteur de monde

Refixer des constantes obligerait a retoucher 34 filons, les quotas et les seuils a chaque
palier de croissance : une corvee qui ne serait jamais faite, et qui casserait le design en
silence. Le calibrage doit donc etre **dynamique**. Mais mal concu, il annule exactement la
tension qu'il sert.

#### L'invariant a servir

> **Le temps qu'il faut pour faire monter un foyer, et la tension ressentie sur un filon,
> doivent etre les memes a 50 joueurs et a 500.**

Tout le reste en decoule. Ce n'est pas « le monde grossit », c'est « l'experience reste
constante quand la population change ».

#### Le piege : ne jamais boucler sur la pression locale

Si la capacite d'un filon montait avec le nombre de gens qui l'exploitent, le filon
**donnerait plus a mesure qu'on le presse** — la rarete s'annulerait toute seule, et la
vitalite deviendrait un affichage. Regle absolue :

| Doit etre dynamique | Ne doit **jamais** l'etre |
|---|---|
| L'**ampleur** du monde, indexee sur la population **globale** | La reponse d'un filon a sa **propre** frequentation |
| Capacite des filons, seuils de foyer, quotas de Crue | Vitalite, purete, Paleur — ce sont les **signaux de jeu** |

Le facteur global est **aveugle au comportement local**. C'est ce qui garantit que la
concurrence sur un filon reste une vraie concurrence.

#### Ce qui bouge, et ce qui ne bouge pas

> **Le rythme du monde ne change pas ; seule son ampleur change.**

On multiplie la **`capacity`** d'un filon par le facteur de monde `W`, et on laisse
**`respawn_seconds` fixe**. Le debit suit mecaniquement (`R = capacity x 3600 / respawn`),
mais la cadence de repousse — le rythme de la maree, en fiction — reste la meme pour tout le
monde. Un serveur plus peuple a des filons plus **gros**, pas plus **rapides**.

| Grandeur | Echelle |
|---|---|
| `capacity` des filons | x W |
| Seuils de sediment d'un foyer | x W — c'est ce qui garde constant le **temps** de montee |
| Quotas de Crue | paliers de population (§ GAME_WORLD 13.4) |
| `respawn_seconds` | **fixe** |
| Vitalite, purete, Paleur | **jamais** — ce sont les signaux |

#### Trois garde-fous

1. **Par paliers, pas en continu.** `W` prend des valeurs discretes (0,5 / 0,75 / 1 / 1,5 / 2
   / 3…) rattachees a des bandes de population. Un reglage qui glisse en permanence rend le
   savoir du prospecteur — qu'on a rendu monnayable — impossible a constituer.
2. **Asymetrique.** Monte vite, redescend lentement, sur une moyenne glissante d'une maree.
   Une baisse passagere de frequentation ne doit jamais retrecir le monde sous les pieds des
   joueurs presents.
3. **Annonce, jamais silencieux.** Un changement de `W` ne survient qu'a une bascule de maree
   et s'inscrit au journal de monde : *la Concorde s'etend*. Meme methode que la Crue — une
   necessite technique devient un evenement du monde plutot qu'un ajustement subi.

Et un **verrou manuel** : l'admin doit pouvoir figer `W`. Pour un evenement, pour un test, et
pour le jour ou la valeur automatique aura tort.

#### Anti-abus

`W` se calcule sur la **meme definition de joueur actif** que le quota de Crue, et passe par
les garde-fous existants (`InfluenceAntiExploit`) : une ferme de comptes secondaires ne doit
pas pouvoir gonfler le monde.

**A faire** : le recalibrage de base (§ 22.3) fixe `W = 1` a ~50 joueurs quotidiens. Il touche
les 34 filons et invalide le tableau de paliers en tete de `config/game/zones/world_1.yaml`.
Il precede FOY-11 (Paleur) et ECO-22 (purete a la recolte) — sans lui, les deux jalons
livreraient du code sans effet observable.

### 22.5 Compter la population — la charge, pas les tetes

Le facteur de monde (§ 22.4) et les quotas de Crue reposent sur « la population active ».
Encore faut-il la definir, car c'est le **denominateur de tout le dimensionnement**.

#### Ce qui ne marche pas

**Les comptes inscrits** : un compte cree puis abandonne pese autant qu'un joueur quotidien.
Le monde grossirait sur du vide.

**Le proxy actuel.** `InfluenceAntiExploit::hasMinimumActiveMembers()` compte les membres dont
le `Player.updatedAt` est plus recent que 7 jours, et le commentaire du code l'admet :
*« Utilise le updatedAt du Player comme proxy d'activite »*. Il n'existe aujourd'hui **aucun
champ `lastActivityAt`** dans le modele. Ce proxy est acceptable pour un garde-fou binaire
(« la guilde a-t-elle au moins 3 membres vivants ? ») ; il ne l'est pas pour dimensionner le
monde, car `updatedAt` est un champ de cycle de vie Doctrine — il bouge des qu'une ecriture
touche la ligne, y compris une ecriture **systeme** (regeneration, respawn, backfill), et
**une seule connexion suffit a compter pendant sept jours**.

**Un simple decompte de tetes**, meme corrige, reste faux : cinquante joueurs quotidiens et
deux cents joueurs hebdomadaires donnent le meme nombre, et exercent une pression totalement
differente sur les filons.

#### Ce qu'on mesure a la place

> **La population effective se deduit de l'energie depensee, pas des connexions.**

L'energie est la ressource rare fondamentale du jeu : toute action qui pese sur le monde
(explorer, recolter, combattre) en consomme, et **se connecter n'en consomme pas**.

```
Charge de monde C     = energie totale depensee par tous les joueurs sur la maree ecoulee
Population effective  = C / (energie d'un joueur regulier sur une maree)
```

Un « joueur regulier » depense environ 60 % de sa regeneration quotidienne, soit ~150 points
par jour et ~4 200 sur une maree de 28 jours. La population effective est donc le nombre de
joueurs reguliers **equivalents**, pas le nombre de comptes.

#### Pourquoi c'est immunise contre le multi-compte

C'est la propriete qui emporte la decision. Un decompte de tetes se gonfle avec des comptes
secondaires ; une mesure de **charge**, non — parce qu'un joueur qui fait tourner trois
comptes a fond **exerce reellement la pression de trois joueurs** sur les filons. Le monde
doit donc bien se dimensionner pour trois. Il n'y a plus rien a exploiter : on ne peut pas
gonfler le monde sans produire exactement la charge pour laquelle il se dimensionne.

#### Ce qu'il faut ajouter au modele

| Besoin | Solution |
|---|---|
| Dimensionnement du monde (W, quotas) | **`WorldLoadService`** : somme de l'energie depensee sur la maree → population effective |
| Garde-fou binaire (min. membres actifs) | **`Player.lastActivityAt`** explicite, mis a jour **a la depense d'energie** — remplace le proxy `updatedAt` |

#### Cadence et amorcage

- **Contraction : uniquement a une bascule de maree** (28 jours), pour ne jamais retrecir le
  monde sous les pieds des joueurs presents.
- **Expansion : possible a n'importe quel tick quotidien** si la charge franchit un palier.
  C'est l'asymetrie de § 22.4 — monte vite, redescend lentement — et elle compte pour un
  jeune serveur qui grandit : attendre 28 jours pour ouvrir le monde serait trop lent.
- **Plancher de W** et **periode de grace au lancement** : les premieres marees ne contractent
  jamais. Un serveur qui demarre a cinq joueurs ne doit pas se refermer sur eux.



---

## 23. Chiffrage des foyers — seuils, decroissance, echeanciers (2026-07-28)

> L'entree numerique de la Piste A du plan des foyers (FOY-01 → FOY-03) et de la Crue
> (FOY-08). Tout est calibre a **W = 1** (~50 joueurs actifs quotidiens, § 22.5) ; le
> facteur de monde met les seuils a l'echelle (§ 22.4), jamais les taux. Tout est de la
> **donnee** (`config/game/settlements.yaml`) : ces valeurs sont un point de depart a
> retendre en observant le serveur, pas des constantes.
>
> **Etat (2026-07-28)** : ces valeurs sont **livrees** dans
> `config/game/settlements.yaml` avec FOY-01 et validees a la lecture par
> `SettlementDefinitionLoader` — seuils strictement croissants, taux bornes, indice de
> chaque ligne de la table verifie, et seuil de rendements decroissants force **sous** le
> plafond journalier. Un parametre faux echoue donc au chargement, pas six semaines plus
> tard sur un ecran de zone. Le **consommateur** de la table (`sediment`, `decay`,
> `type`) arrive avec FOY-02 et FOY-03 ; a ce stade, seul le `seed` est joue.

### 23.1 L'unite : le grain de sediment

Un **grain** est la trace qu'une action laisse dans un foyer. La table de depot est
declarative :

| Action (event existant) | Indice | Grains |
|---|---|---:|
| Kill (`MobDeadEvent`) | `war` | 1,7 |
| Recolte / peche / depecage (`SpotHarvestEvent`, `FishingEvent`, `ButcheringEvent`) | `trade` | 1 |
| Craft (`CraftEvent`) | `trade` | 1 |
| Evenement de zone | selon l'evenement | 3,3 |
| Vente HV conclue dans la region | `trade` | 1 par 100 gils, plafonne a 5 |
| Quete achevee (`QuestCompletedEvent`) | `lore` | 5 |
| Entree de Codex / premiere visite | `lore` | 5 |
| Materia **lue** chez les Lecteurs | `rite` | 20 |
| Participation a un beat de maree | `rite` | 10 |
| **Passage** (traversee de zone, `ZoneTravelService`) | reparti | 0,2 |

> **Etat du branchement (FOY-02, 2026-07-28)** : sept lignes de ce tableau sont livrees
> et deposent reellement — kill, recolte, peche, depecage, craft, quete, passage. Les
> trois autres (vente conclue a l'HV, materia lue chez les Lecteurs, participation a un
> beat de maree) n'ont pas encore de point d'accroche et **ne figurent pas** dans
> `config/game/settlements.yaml` : `SettlementSedimentWiringTest` interdit qu'une ligne y
> soit chiffree sans etre appelee, precisement pour qu'un depot muet ne puisse pas
> exister. Elles entreront dans le fichier avec leur listener.

> **Le grain est pondere par l'energie du geste** *(corrige le 2026-07-29, playtest
> papier F2)* : **1 grain par ~3 energie depensee** — recolte (3 e) = 1, kill (5 e) = 1,7,
> evenement (10 e) = 3,3. Sans cette ponderation, un guerrier deposait ~40 % de moins
> qu'un recolteur a budget egal : les Bastions montaient structurellement plus lentement
> que les Comptoirs, et l'indice `war` mondial restait chroniquement le plus bas (la
> rotation des marees aurait tire Battue sur Battue). La regle : le sediment mesure le
> temps vecu, et le temps vecu se mesure en energie.

Reperes de flux (§ 1 : 50-80 gestes/jour a barre pleine selon leur nature ; § 22.5 : le
joueur regulier depense ~62 % de sa barre) — les flux en grains restent inchanges par la
ponderation (30-50 grains/jour), puisqu'ils se calculent desormais en energie :

- **Joueur regulier** : ~30 grains/jour dans sa zone principale.
- **Joueur assidu focalise** : ~50 grains/jour.
- **Plafond anti-exploit** (`InfluenceAntiExploit`) : **60 grains/jour/joueur/foyer**,
  rendements decroissants au-dela de 40. Le grind ne bat jamais la regularite.
- **Zone de pur transit** (20 traversees/jour) : ~4 grains/jour — assez pour tenir un
  Campement (§ 23.3), c'est le levier 4 de GAME_WORLD § 5.5, chiffre.

### 23.2 La decroissance : -2 % par jour

Chaque indice perd **2 % de son stock par jour** (tick quotidien, `app:settlement:tick`).
Demi-vie ≈ 35 jours — un peu plus d'une maree : un foyer delaisse une maree entiere
descend visiblement, un foyer delaisse une semaine ne s'effondre pas.

Consequence structurante : le stock d'equilibre d'un foyer vaut **50 x son flux
quotidien**. Le rang d'un foyer est donc, a terme, une photographie de sa frequentation
reelle — exactement l'indice d'activite decroissant emprunte a EVE
(GAME_INSPIRATIONS § 2.4).

### 23.3 Les seuils de rang (somme des quatre indices, W = 1)

| Rang | Seuil | Pour le tenir (flux) | Ce que ca veut dire a 50 j/j |
|---|---:|---:|---|
| Ruine (0) | — | — | l'etat de depart d'une zone neuve |
| Campement (1) | 150 | 3 grains/j | 2 joueurs y jouent 2-3 jours, ou du simple transit |
| Hameau (2) | 1 200 | 24 grains/j | 1 regulier dedie + du passage |
| Bourg (3) | 8 000 | 160 grains/j | ~6 reguliers constants ; **l'effort d'une maree pour une guilde de 12** |
| Cite (4) | 25 000 | 500 grains/j | ~17 reguliers dedies — n'existe qu'avec la croissance (quota a 120 actifs) |
| Metropole (5) | 60 000 | 1 200 grains/j | ~40 reguliers dedies — le projet d'un serveur qui a reussi (quota a 300) |

Verification d'echeancier (flux constant F, stock(t) = 50F x (1 - e^(-0,02t))) :

- 12 joueurs focalises (480 grains/j) atteignent **le Bourg en ~24 jours** — le 1er
  marche du serveur est **l'evenement de la premiere maree**, dispute entre 2-3 guildes
  (GAME_WORLD § 13.4). C'est l'ancre de tout le chiffrage.
- 8 reguliers non coordonnes (240/j) plafonnent vers 12 000 : Hameau solide, Bourg hors
  de portee sans coordination. **Le Bourg exige une guilde** ; c'est voulu (Acte III).
- Un regulier isole (30/j) tient un Hameau a lui seul a l'equilibre (1 500) : un joueur
  fidele **suffit a faire vivre un petit foyer** ; c'est voulu aussi (« on compte sur
  moi » commence la).

### 23.4 Le type : hysteresis chiffree

Le type (Comptoir/Bastion/Athenee/Sanctuaire) s'installe quand l'indice dominant depasse
le second de **25 %**, **tenu une maree entiere** (28 jours glissants). Il ne se perd que
si un autre indice le depasse dans les memes conditions. En dessous du rang Hameau, pas
de type : un Campement n'a pas encore d'identite.

### 23.5 Seed du monde livre (decision A — rien n'est retro-gate)

| Zone | Rang seed | Stock seed | Pourquoi |
|---|---|---:|---|
| Foret des murmures | Hameau (2) | 2 000 | PNJ, quetes d'acte, la zone-ecole |
| Mines profondes | Hameau (2) | 2 000 | PNJ, ateliers, le coeur industriel |
| Marais brumeux | Campement (1) | 400 | PNJ d'acte, pas de bourg constitue |
| Crete de Ventombre | Campement (1) | 400 | idem |
| Dunes d'Ambre | Campement (1) | 300 | deux habitants, un caravanserail |
| Vallons d'Aubepine (ZON-30) | **Ruine (0)** | 0 | zone neuve : **tout est a batir** — le premier chantier collectif offert aux joueurs |
| le Fanal + Jardins | *pas de foyer* | — | la Voute (§ 3.4) |
| Cite ensevelie | *pas de foyer* | — | donjon (GAME_ZONES § 2.8) |
| Mer de Sel, Pas de Givre, Glacier | *pas de foyer* | — | le Silence / Ext. 1 (§ 4.3) |

Le seed est **narratif, pas protecteur** : si personne ne frequente les Mines, leur foyer
redescendra — mais les services *existants* (PNJ, boutiques) ne ferment jamais
(garde-fou FOY-05). La decroissance ne retire que ce que les joueurs ont bati.

### 23.6 Regression et reascension (FOY-10, chiffre)

- **Annonce** : un foyer qui passera sous un seuil au tick de maree est signale **une
  maree a l'avance** (« l'etiage guette X ») — jamais de retrogradation surprise.
- **Plancher de maree** : au plus **un rang perdu par maree**, quel que soit le deficit.
- **Reascension acceleree** : tant que `rank < highestRank`, les depots comptent
  **double**. Rebatir est deux fois plus rapide que batir — le patrimoine, c'est de la
  memoire, pas des murs.

### 23.7 La Crue : l'echelle actee, rappel

L'echelle d'ouverture est celle de GAME_WORLD § 13.4 (1er Bourg a 40 actifs → Metropole
a 300), mesuree en **population effective** (§ 22.5). Elle remplace toute notion de
« quota de base » anterieure : a 50 actifs, le monde a droit a **un** Bourg de foyer, et
c'est l'unique enjeu territorial du serveur — exactement la tension voulue. Periode de
grace au lancement : pendant les **deux premieres marees**, aucune retrogradation et
aucune contraction de W (§ 22.4).

### 23.8 La Paleur et son chantier de restauration (FOY-11 / FOY-12)

**La Paleur** (`paleness:` dans `settlements.yaml`) se mesure **par filon** et compare ce
qu'on a pris dans la journee au debit soutenu du filon, `R = capacity x 86400 /
respawn_seconds`. Au-dessus de la pression 1, elle monte de **0,08 par point de pression** ;
sous 1, elle rend **0,04 par jour** ; elle est bornee a **0,60** — un filon pali n'est jamais
sterile. Elle se voit a partir de **0,10** et rabat la bande de purete au clair a partir de
**0,30**.

**Le chantier de restauration** (`restoration:`) coute **90 Gils par point de Paleur**
(1 point = 0,01), dure **5 jours** et ajoute **0,04/jour** a la recuperation naturelle.

| Paleur du filon | Cout du chantier | Ce que 5 jours de chantier en retirent |
|---|---|---|
| 0,10 (juste visible) | 900 Gils | 0,10 → 0 (le naturel y suffisait presque) |
| 0,30 (bande rabattue) | 2 700 Gils | 0,30 → 0 en 4 jours au lieu de 8 |
| 0,60 (plafond) | 5 400 Gils | 0,60 → **0,20** — le filon reste marque |

**Les trois bornes du chiffrage**, chacune tenue par le loader :

1. **Reparer reste plus lent qu'abimer** : 0,04 + 0,04 = 0,08 au mieux, soit exactement ce
   qu'ajoute un jour a pression 2. Le bonus **n'entre pas** dans la branche de montee, donc un
   filon presse ne se repare pas, meme paye.
2. **On n'achete pas un monde propre** : au plafond, un chantier complet laisse 0,20 — il faut
   arreter de presser pour finir la guerison.
3. **Le prix est lineaire**, sans palier : un joueur doit pouvoir predire ce que lui coutera
   d'attendre un jour de plus. A 5 400 Gils, le pire cas vaut l'ordre de grandeur du premier
   palier de tresor de guilde (5 000) : une depense qu'une guilde etablie decide, jamais une
   qu'elle subit.

### 23.9 Les ateliers de doctrine (FOY-13)

Une guilde paie **6 000 Gils** pour orienter un foyer, et la doctrine tient **28 jours** (une
maree). Les deux ateliers sont exclusifs, et leurs effets s'opposent :

| | Fonderie | Lecteurs |
|---|---|---|
| Rendement de recolte | **+15 %** | — |
| Sediment `lore` | — | **+50 %** |
| Montee de la Paleur | **×1,5** | **×0,5** |

**Ce que ca vaut, en pratique.** Un filon presse a pression 2 gagne 0,08 de Paleur par jour ;
sous Fonderie, 0,12 ; sous Lecteurs, 0,04 — soit exactement la vitesse a laquelle il se refait
quand on le laisse. Un serveur qui choisit les Lecteurs peut donc presser un filon a pression 2
**indefiniment** sans qu'il palisse ; c'est le prix a payer pour +0 % de rendement et une ville
qui monte plus vite vers l'Athenee.

**Le facteur ne touche jamais la recuperation** : un atelier oriente ce qu'on fait au filon, pas
la vitesse a laquelle le monde se repare tout seul. Sans cette borne, la Fonderie punirait aussi
ceux qui s'abstiennent.

**Trois bornes tenues par le loader** : le multiplicateur de la Fonderie doit depasser 1, celui
des Lecteurs rester dessous, et chaque atelier apporter quelque chose. Deux boutons qui font la
meme chose ne sont pas un choix, et un atelier sans effet est un cout sec.

### 23.10 Le seuil des marees consequence (FOY-15)

`paleness_threshold: 6` (dans `config/game/consequence_tides.yaml`) : nombre de filons portant une
Paleur **visible** (>= 0,10) qu'il faut compter en fin de maree pour que la Paleur preempte le
creneau suivant.

Le monde livre compte une trentaine de filons. A 6, il faut donc qu'un cinquieme d'entre eux porte
une trace — c'est-a-dire une exploitation organisee sur **plusieurs zones**, et non une ruee d'un
soir sur un filon. C'est coherent avec le principe de FOY-11 : *la Paleur est une consequence du
succes, jamais du passage* (GAME_WORLD § 3.5).

**L'Appel de la Crue n'a pas de seuil**, et c'est deliberé : il se declenche sur une **variation**
du nombre de places libres, pas sur un etat. Un seuil sur l'etat l'aurait fait sonner des la
premiere maree, quand toutes les places sont libres.

### 23.11 Les prix du charpentier (ECO-30)

La ligne du bois est la premiere livree **apres** ECO-27, donc la premiere dont chaque prix est
derive plutot que constate. La regle appliquee est celle du jalon : `prix = cout + 10 x niveau`,
arrondi au pas de 5 sous 100.

| Objet | Niv. | Cout des intrants | Prix |
|---|---:|---:|---:|
| Planche de hetre (x2) | 1 | 8 (hetre x2) | 15 l'unite |
| Manche de bois (x2) | 2 | 15 (planche) | 20 l'unite |
| Fleches empennees (x10) | 3 | 44 | 8 l'unite |
| Necessaire d'ameublement | 5 | 240 | 290 |

**Les fleches echappent volontairement a la formule.** Appliquee telle quelle a un lot de dix, elle
aurait donne une fleche a 32 Gils — le prix d'un objet durable pour un consommable qu'on brule par
poignees. Le lot vaut 80 pour 44 de matiere : la marge existe, et l'unite reste dans l'ordre de
grandeur de ce qu'un archer consomme sans compter.

**Les six armes de bois gardent leur prix d'origine.** Elles existaient deja au butin et en
boutique ; les rehausser pour coller au cout de fabrication aurait deplace le plancher T1 (ECO-02)
pour une raison de calibrage d'artisanat. Le controle qui compte est tenu dans l'autre sens :
aucune ne coute plus cher a fabriquer qu'elle ne vaut (t1-bow 19 pour 22, t3-staff 265 pour 450).

**Le necessaire d'ameublement vaut 290 et remplace jusqu'a 8 000 Gils** de style Bourgeois. L'ecart
est le sujet, pas un defaut de calibrage : l'ameublement paye en monnaie est un gold sink, et un
sink doit toujours etre battu par la voie joueur — sinon personne ne crafte, et le metier n'existe
que sur le papier.

### 23.12 La ligne du tissu (ECO-31)

Le pendant chiffre de la serie cuir. Les prix suivent la regle d'ECO-27 — `prix = cout + 10 x
niveau`, arrondi au pas de 5 sous 100, de 10 sous 1 000, de 50 au-dela.

| Piece | Niv. | Cout des intrants | Prix |
|---|---:|---:|---:|
| Tissu (x2) | 1 | 27 (lin x3) | 15 l'unite |
| Capuche / mitaines de lin | 2 | 30 | 50 |
| Robe de lin | 2 | 60 | 80 |
| Capuche / mitaines de lin fin | 3 | 65 | 95 |
| Robe de lin fin | 4 | 130 | 170 |
| Capuche de soie d'ombre | 6 | 240 | 300 |
| Robe de soie d'ombre | 7 | 540 | 610 |
| Mantelet de l'archiviste | 8 | 510 | 590 |
| Robe de l'archiviste | 9 | 1 120 | 1 200 |

**L'axe du metier, chiffre.** A palier egal, une piece de tissu protege environ **un tiers** de
ce que protege son equivalent de cuir, et rend en echange un bonus de puissance magique. La robe
de l'archiviste (protection 7, magie 48) se lit contre le plastron de cuir enchante (protection
20) : meme palier, meme rarete, meme gemme du joaillier au coeur de la piece, deux facons
opposees de survivre a un combat.

**Le premier palier ne coute que de la toile**, et c'est la borne qui compte : trois pieces
accessibles au niveau 2 sans acheter quoi que ce soit a un autre joueur. Le croisement avec le
tanneur commence au palier 3 (ECO-14), jamais avant — le plancher T1 du lanceur de sorts (ECO-02)
prime sur l'interdependance.

**Le lin des Vallons alimente deux metiers**, la toile du tailleur et le fil qui coud les
jambieres du tanneur. C'est le seul intrant du monde dans ce cas, et c'est deliberé : une
exclusivite de zone dont un seul metier dependrait s'eteindrait avec lui.

### 23.13 Les recoltes harmonisees (ZON-35)

Le compte par domaine apres le jalon, contre la cible de la loi 9 (GAME_ZONES § 3 ter) :

| Domaine | Matieres | Cible | Ecart |
|---|---:|---:|---|
| Mineur | 14 | 10–13 | gabarit de reference, inchange |
| Herboriste | 20 | 8–12 | **assume** — voir ci-dessous |
| Pecheur | 7 | 5–6 | +2 volontaires : les deux orphelins de palier 4 |
| Bucheron | 4 | 4–6 | dans la cible |

**L'ecart de l'herboriste.** La cible de 8–12 a ete ecrite quand l'herboriste ne nourrissait qu'un
metier. Le cuisinier (ECO-29) en a fait deux, et la loi dit elle-meme que le compte suit les
artisanats nourris. Descendre a 12 aurait demande de supprimer huit plantes qui ont **toutes un
filon**, dont trois — givrecoiffe, spores fantomes, fruit du vide — que la loi 10 du meme document
cite nommement comme exemples canoniques d'affinite elementaire. Le vrai sujet de l'audit n'etait
pas le nombre : c'etait que douze des 22 ne servaient a rien. Il n'en reste aucune.

**Les sept prix recalcules**, apres ajout d'un intrant a des recettes existantes. Sans cette passe,
sept recettes seraient devenues destructrices de valeur — le defaut exact qu'ECO-27 a corrige, et
la meme regle le repare : `prix = cout + 10 x niveau`.

| Recette | Niv. | Nouveau cout | Ancien prix | Nouveau prix |
|---|---:|---:|---:|---:|
| Fiole de poison | 3 | 193 | 170 | 220 |
| Elixir de force | 3 | 270 | 240 | 300 |
| Elixir de defense | 3 | 152 | 120 | 180 |
| Potion de soin majeure | 4 | 300 | 220 | 340 |
| Elixir de vitalite | 5 | 320 | 300 | 370 |
| Poisson-lune en ecailles | 4 | 110 | 100 | 150 |
| Festin de kraken | 6 | 290 | 250 | 350 |

**Les deux prises de palier 4** suivent le profil T4 du recalibrage (§ 22.3) : capacite 22, repousse
64 800 s, soit 1,2 unite/heure et ~1,5 recolteur regulier soutenu. C'est le debit le plus faible du
monde, et c'est voulu : ce sont les deux matieres qui ouvrent les deux recettes de plus haut palier
du cuisinier.

---

## 24. Chantiers d'equilibrage ouverts (2026-07-29)

Issus du playtest sur papier du premier mois ([PLAYTEST_PAPIER_MOIS_1.md](PLAYTEST_PAPIER_MOIS_1.md)) :

0. **Appliquer la ponderation du grain** (§23.1, F2 — **action immediate, une ligne de
   config**) : la table de depot de `config/game/settlements.yaml` passe du grain
   uniforme au grain pondere par l'energie du geste (kill 1,7, evenement 3,3). FOY-02
   etant livre sur la table uniforme, c'est une correction de donnees, pas de moteur.
   **Constat d'audit (2026-07-29)** : la ponderation n'est **toujours pas** appliquee —
   la table `sediment:` de `config/game/settlements.yaml` est encore uniforme
   (`mob_kill: grains: 1` au lieu de 1,7). `SedimentRule::$grains` est un float : la
   valeur passe sans migration. Le biais Comptoir/Bastion decrit en §23.1 est donc
   toujours actif. Reste l'**action immediate n°1**.

1. **La passe post-arbres.** Les arbres de domaine (GAME_DOMAINS, DOM-01+) apporteront des
   passifs de **reduction de cout d'energie** et de **reduction de temps** (craft, voyage ?)
   sur les gestes de leur domaine. Chaque reduction agrandit le budget effectif d'un
   veteran : les reperes de GAME_PROGRESSION §5, le calibrage des filons (§22) et les flux
   de grains (§23) devront etre re-verifies quand ces passifs existeront. A faire **apres**
   DOM-01/02, avant tout lancement public.

2. **L'equilibrage des combats.** La regeneration de l'energie de combat (les PM — pool
   `Player.energy`, distinct de l'energie d'action) n'a jamais ete calibree contre les
   chaines de combats reelles : un chasseur qui enchaine les kills est-il borne par ses PM
   avant son energie d'action ? Le playtest papier ne peut pas le voir (V3). Chantier
   complet : regen des PM entre et pendant les combats, couts des sorts par palier,
   duree moyenne d'un combat en tours, et l'interaction avec les passifs de cout des
   arbres (point 1). A instrumenter en jeu.

3. **Seuils de foyer × facteur de monde `W` — arbitrage a trancher** (§24.3, constat
   d'audit du 2026-07-29). La doc promet a trois endroits que les seuils de rang d'un
   foyer sont multiplies par `W` : la note d'heritage FOY-17b → FOY-08 de
   [PLAN_SETTLEMENTS.md](roadmap/PLAN_SETTLEMENTS.md), l'en-tete de
   `config/game/settlements.yaml`, et le tableau d'application du §22.4 ci-dessus.
   **Le code ne le fait pas** : aucun appel a `WorldScaleService` cote Settlement
   (`src/GameEngine/Settlement/`). Effet : sur un monde a `W` eleve, les filons donnent
   plus (capacite × W, branchee) mais les seuils de foyer n'ont pas bouge — les foyers
   montent **plus vite** que le calibrage §23.3, et le « temps de montee constant » que
   ces trois mentions garantissent est faux. A trancher explicitement : **implementer la
   multiplication** (dette de code a solder), ou **acter le renoncement** et corriger les
   trois mentions. **Decision utilisateur requise.**

4. **Calibrage du rendement et de la duree du jardin** (herite du Sprint 11, tache 129 /
   HOU-02) : 1 unite semee rend 2 a 3 en 3 h, sans energie ni presence — un rendement pose
   sans chiffrage, jamais confronte au budget d'energie (§8) ni aux prix de reference
   (§21.6 bis). A chiffrer ici.
