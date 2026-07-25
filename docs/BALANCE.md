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
| `zone.energy.cost.event` | 10 | Cout pour rejoindre un evenement de zone (ZON-15, lu par `ZoneEventService`) |
| `zone.energy.cost.assault` | 10 | Cout d'un assaut contre un boss de zone (ZON-18, lu par `ZoneBossService`) |
| `zone.boss.assault_damage_factor` | 100 | Multiplicateur des degats d'assaut en % (100 = x1.0 de la stat d'attaque du joueur, ZON-18) |
| `zone.dungeon.turn_seconds` | 45 | Delai par tour d'un donjon de groupe ; au-dela, attaque de base auto (ZON-19, lu par `GroupDungeonCombatService`) |
| `zone.dungeon.encounter_hp_per_member` | 200 | PV de la rencontre partagee par membre du groupe (ZON-19) |
| `zone.dungeon.reward.base_gils` | 150 | Gils de base par membre a la reussite d'un donjon de groupe (ZON-20, lu par `GroupDungeonRewardService`) |
| `zone.dungeon.lockout.window_hours` | 24 | Fenetre glissante (heures) de comptage des reussites pour la decroissance (ZON-20) |
| `zone.dungeon.lockout.decay` | 0.5 | Facteur multiplicatif de la recompense par reussite recente du meme donjon (ZON-20) |
| `zone.dungeon.lockout.min_factor` | 0.25 | Plancher de la recompense decroissante (protection de l'economie, on prefere la decroissance au blocage sec, ZON-20) |

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
