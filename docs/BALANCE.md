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
| Plaines de l'Eveil | Village de Lumiere (capitale), Foret des murmures | 5 % | Marche de depart : la demande, les nouveaux joueurs |
| Terres Sauvages | Mines profondes, Marais Brumeux, Crete de Ventombre | 8 % | Front pionnier : la matiere premiere rare |
| Sanctuaire de Lumiere | *(aucune carte)* | 0 % | Region declaree, non contestable, sans contenu a ce jour |

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
> palier inferieur. Un palier orphelin tue la demande en matiere de debut de jeu des que
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

### 21.3 Chaine cible (ligne du metal)

```
bronze (niv 1)  <- ore-copper x2 + ore-tin x2                     [inchangee : palier d'entree]
cobalt (niv 3)  <- ore-cobalt x3      + 1 lingot de bronze
mithril (niv 4) <- ore-mithril x3     + 1 lingot de cobalt        [ore-mithril = transmutation, §19]
adamantite (6)  <- ore-adamantite x3  + 1 lingot de mithril
orichalque (8)  <- ore-orichalcum x3  + 1 lingot d'adamantite
```

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
`ObjectLayer` T5, coordonnees `75.2`, Mines profondes). L'amethystite — sur laquelle
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

### 21.6 Suite

ECO-25 applique la chaine cible (§21.3) au coefficient 1. Avant cela, deux prealables
issus de §21.5 : unifier la source des minerais de haut palier (filon declare plutot que
spot herite), et repartir l'etain sur au moins une seconde zone.

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

### 22.3 Cible proposee

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
