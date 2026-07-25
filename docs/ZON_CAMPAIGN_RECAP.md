# Récapitulatif — Campagne ZON (pivot PBBG)

> **Version** : 1.0 — 2026-07-25
> **Périmètre** : jalons **ZON-12 → ZON-21** (Sprints 8 → 10 du pivot PBBG)
> **Nature** : document de synthèse versionné produit en fin de campagne. Source de vérité détaillée : [`ROADMAP_DONE.md`](ROADMAP_DONE.md) (par jalon) et l'historique git (14 PR, #611 → #624).

---

## 1. Contexte & objectif

Le jeu a **pivoté d'un MMORPG à carte navigable tile-par-tile (rendu PixiJS + pathfinding)** vers un **PBBG (Persistent Browser-Based Game) en modèle « zone »** : le monde est un **graphe de zones** ; la position d'un joueur est sa `Zone` courante (`Player::currentZone`), pas des coordonnées ; les écrans sont **server-rendered** (Twig + Stimulus + Turbo), avec Mercure SSE comme confort temps réel.

Cette campagne réalise la dernière tranche du pivot :
- **Sprint 8–9** (ZON-12 → ZON-17) : régulation, activités time-gated, présence/chat, événements, carte du monde illustrée, cycle jour/nuit.
- **Sprint 10** (ZON-18 → ZON-21) : PvE coopératif en modèle zone (boss asynchrones, donjons semi-synchrones) puis **décommission définitive du code carte**.

Le tout a été mené **de façon autonome** : une branche + une PR par jalon (ou sous-jalon), CI suivie jusqu'au vert, merge, puis enchaînement sur le point suivant.

---

## 2. Jalons livrés

| Jalon | PR | Livré |
|-------|----|-------|
| **ZON-12** | #611 | Régulation par les PV (régénération hors combat, paresseuse) |
| **ZON-13** | #612 | Expéditions time-gated (récompenses à la durée) |
| **ZON-14** | #613 | Présence & chat de zone (Mercure `chat/zone/<id>`) |
| **ZON-15** | #614 | Événements de zone (`GameEvent` activables) |
| **ZON-16** | #615 | Carte du monde **illustrée** (graphe de zones, `/game/world-map`) |
| **ZON-17** | #616 | Cycle jour/nuit mécanique (`GameTimeService`) |
| **ZON-18** | #617 | Boss de zone asynchrone (`ZoneBoss`, loot à la contribution) |
| **ZON-19** | #618, #619, #620 | Donjon de groupe semi-synchrone (3 sous-PR : modèle & formation, boucle de combat, Mercure temps réel) |
| **ZON-20** | #621 | Lockouts & récompenses **décroissantes** de donjon de groupe |
| **ZON-21** | #622, #623, #624 | Suppression du code carte (3 sous-PR : front, backend runtime, éditeur admin + terrain) |

### ZON-18 — Boss de zone asynchrone
Entité `ZoneBoss` (pool de PV partagé, 1:1 avec un `GameEvent` de zone). `ZoneBossService::assault` : chaque joueur présent dépense de l'énergie d'action et inflige des dégâts basés sur sa stat d'attaque, sans présence simultanée. À 0 PV, loot distribué à la contribution (généralisation de `WorldBossLootDistributor` au modèle zone).

### ZON-19 — Donjon de groupe semi-synchrone (XL, découpé en 3 sous-PR)
- **s.1 — Modèle & formation** : `GroupDungeonRun` + `GroupDungeonMember` (instantané des membres figé à la formation), `GroupDungeonService::launch` réutilise le système `Party` existant.
- **s.2 — Boucle de combat** : `GroupDungeonCombatService`, rencontre à PV partagés, ordre de tour des membres, **délai par tour résolu paresseusement** (action par défaut = attaque de base auto), aucune présence simultanée requise.
- **s.3 — Mercure temps réel** : `GroupDungeonCombatPublisher` publie l'état sur `dungeon/run/<id>` ; contrôleur Stimulus `group-dungeon` rafraîchit la bannière sans recharger. Confort quand le groupe est en ligne, sans casser le modèle semi-synchrone.

### ZON-20 — Lockouts & récompenses décroissantes
Première récompense concrète à la réussite d'un donjon de groupe : des gils, avec **décroissance** plutôt que blocage sec. Chaque réussite répétée du même donjon dans une fenêtre glissante (24 h par défaut) réduit la récompense (facteur 0.5, plancher 0.25). Entité `GroupDungeonClear`, `GroupDungeonRewardService`, curseurs BALANCE.

### ZON-21 — Suppression du code carte (L, découpé en 3 sous-PR)
- **a — Carte navigable (front)** : contrôleurs JS PixiJS/avatar, `SpriteAnimator`, bundle PixiJS (sorti de l'importmap), vue `/game/map` + composant Twig, harness admin `avatar_test` ; toute la navigation reroutée vers `/game/zone`.
- **b — Services runtime (backend)** : endpoints `/api/map/*`, pathfinding Dijkstra, `PlayerMoveProcessor`, publishers `Realtime/Map`, flag `map_frozen`/`MapFreeze`, rate limiter `api_move`.
- **c — Éditeur admin + terrain + doc** : éditeur de carte admin (dont `MapEditorController`, 1161 lignes), moteur `GameEngine/Terrain` (parser TMX, générateur procédural, 8 biomes), commandes `app:terrain:*`, entité `Tileset` (+ migration DROP), dossier `terrain/`, `docs/TILED_GUIDE.md` ; documentation (`CLAUDE.md`, `AGENTS.md`, `DOCUMENTATION.md`) mise à jour vers le modèle zone.

---

## 3. Décisions de conception prises

Décisions tranchées en autonomie pendant la campagne (l'utilisateur ayant délégué la décision et demandé à être conseillé) :

1. **ZON-17 — cycle jour/nuit « mécanique »** : le cycle influence des effets de jeu (spawns/événements nocturnes), pas seulement l'esthétique — conformément au choix « mécanique riche ».

2. **ZON-19 découpé en 3 sous-PR** : jalon XL avec dépendances internes (modèle → combat → temps réel) ; découpage pour des PR commitables/testables indépendamment (règle projet #8).

3. **Modèle donjon strictement semi-synchrone** : la résolution des tours est **paresseuse** (compute-on-read, aucun cron par joueur) ; Mercure n'est qu'un confort d'affichage, jamais une dépendance à une présence simultanée.

4. **ZON-20 — récompenses décroissantes plutôt que lockout dur** : conforme à la directive roadmap « préférer la décroissance au blocage sec ». Aucun cooldown bloquant par défaut ; un lockout dur reste ajoutable ultérieurement via un curseur si besoin.

5. **ZON-21 découpé en 3 sous-PR** (front → backend runtime → éditeur admin + terrain) : le code carte formait un ensemble large et couplé ; le découpage a permis de garder chaque PR cohérente et la suite de tests verte à chaque étape, plutôt qu'un unique PR à haut risque.

6. **Tests E2E carte supprimés plutôt que réécrits** (ZON-21a) : les tests E2E Panther Map/Combat/Shop pilotaient le canvas PixiJS — c.-à-d. précisément le code retiré. Le combat et la boutique conservent leur couverture **fonctionnelle** ; une couverture **E2E zone** est recommandée en suivi.

7. **`SpriteConfigProvider` relocalisé au lieu d'être supprimé** (ZON-21b) : les aperçus de sprites de l'admin (PNJ, monstre, joueur) l'utilisent encore → déplacé de `GameEngine/Map` vers `GameEngine/Sprite`.

8. **`PlayerMovedEvent` conservé mais dormant** (ZON-21b) : événement domaine écouté par la zone/les quêtes/le tutoriel, mais **dispatché uniquement par la carte** (déjà gelée en prod). Suppression du dispatcher sans régression live ; le rebranchement de ses écouteurs sur des événements de zone est laissé en **suivi** (chantier distinct).

9. **Entités partagées conservées** : `Map`, `Area`, `ObjectLayer`, `Pnj`, `Mob`, `QueueRespawnMob`, `CellHelper`, `MapCellValidator` restent — utilisées par le modèle zone, le combat et le gathering. Seul le code de **rendu/navigation/édition** de carte a été retiré.

10. **Entité `Tileset` supprimée avec migration `DROP TABLE`** (ZON-21c) : purement carte, aucune FK entrante vérifiée. La `down()` recrée la table pour la réversibilité.

11. **Guard CI d'asset mis à jour** (ZON-21a) : la validation Docker vérifiait la présence de `pixi-bundle.js` dans l'image → remplacée par `app.js` (entrypoint AssetMapper).

---

## 4. Suivis identifiés (hors périmètre, non bloquants)

- **Couverture E2E « zone »** : remplacer les E2E carte supprimés par des scénarios pilotant les actions de zone (explorer/chasser → combat, voyage, boutique).
- **`PlayerMovedEvent` dormant** : décider de rebrancher ses écouteurs (zone-sync legacy, quêtes explore/escort, tutoriel, découverte de région) sur des événements de zone existants, ou de les retirer.
- **Rafraîchissement des scénarios de charge k6** : les sections « carte » de `scripts/load-test/README.md` décrivent encore l'ancien profil (topic `map/move`, API map) — à réaligner sur le modèle zone lors d'une passe load-test dédiée. Le scénario `authenticated-gameplay` est déjà repointé sur `/game/zone`.
- **Résidu legacy `MobRepository::findByMapWithMonster`** : méthode de requête devenue sans appelant après le retrait de `/api/map/entities` (nettoyage mineur possible).

---

## 5. Méthodologie & validation

- **Une branche + une PR par jalon** (`claude/zon-XX-...`), sous-PR quand le jalon était trop volumineux (`-a/-b/-c`, `-19b/-19c`).
- **Enchaînement autonome** : suivi de CI via webhooks + check-ins programmés ; merge (squash) au vert ; enchaînement sur le jalon suivant.
- **Boucle de validation locale répliquant la CI** avant chaque push : suite PHPUnit (Unit+Functional+Integration), PHPStan niveau 5, PHP-CS-Fixer, `lint:container`, `app:game:validate`, `asset-map:compile`, et validation SQL des migrations en PostgreSQL. Les E2E Panther (nécessitant un navigateur) ont été validés en CI.
- **Cartographie préalable** des suppressions (ZON-21) via exploration exhaustive des dépendances entrantes, pour distinguer le code carte pur du code partagé avec le modèle zone/combat/gathering.

---

## 6. État final

- ✅ **Sprint 10 terminé** (4/4 tâches) ; **campagne ZON-12 → ZON-21 complète**.
- ✅ **Plus aucun code carte navigable dans le dépôt** : PixiJS hors importmap, endpoints `/api/map/*` supprimés, éditeur admin et moteur terrain retirés, `terrain/` archivé (récupérable dans l'historique git pour un éventuel projet Zelda-like séparé).
- ✅ **PvE coopératif en modèle zone** : boss asynchrones (loot à la contribution) et donjons de groupe semi-synchrones (lockout par décroissance).
- ✅ Documentation (`CLAUDE.md`, `AGENTS.md`, `DOCUMENTATION.md`, `ROADMAP_DONE.md`) alignée sur le modèle zone.
