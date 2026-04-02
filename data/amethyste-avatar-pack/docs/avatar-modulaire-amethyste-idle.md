# Intégration d’un système d’avatar modulaire dans `amethyste-idle`

## Objectif

Ajouter un système d’avatar modulaire pour les **joueurs** sans casser le pipeline existant pour les **mobs** et **PNJ**.

L’idée adaptée au repo est :

- **conserver** `SpriteAnimator.js` et le format legacy RPG Maker **3x4** pour tout l’existant ;
- **composer dynamiquement** une sprite sheet finale pour les joueurs ;
- **réutiliser ensuite `SpriteAnimator`** sur cette texture composite, pour éviter de dupliquer la logique d’animation ;
- **mettre en cache** la sprite sheet composite côté client via PixiJS ;
- **stocker côté serveur uniquement l’état** (apparence + équipement + hash), pas des PNG finaux par défaut.

---

## Constat spécifique au repo

### Frontend
Le repo utilise :
- **Symfony AssetMapper / importmap**
- **ES modules**
- **PixiJS**
- **Stimulus**

Il ne faut donc **pas partir sur une archi Vite / TypeScript / texture packer build-only** pour le MVP.

### Animation actuelle
Le rendu de map repose déjà sur :
- `assets/controllers/map_pixi_controller.js`
- `assets/lib/SpriteAnimator.js`

Et `SpriteAnimator.js` attend des sprite sheets **RPG Maker VX/MV** :
- `single` = **3 colonnes x 4 lignes**
- `multi` = **12 colonnes x 8 lignes**
- directions : `down`, `left`, `right`, `up`
- cycle de marche : colonnes `0,1,2` avec `1` comme idle

### Format d’assets déjà documenté
Le repo documente déjà les sprites personnages dans `ASSETS.md` au format :
- **72x128** pour un personnage seul
- **24x32** par frame
- layout RPG Maker classique

### Backend actuel
Aujourd’hui, l’API map renvoie encore :
- `config.sprites` dans `/api/map/config`
- `spriteKey: 'player_default'` pour les joueurs dans `/api/map/entities`

Donc l’intégration la plus propre consiste à :
1. garder `spriteKey` pour le legacy ;
2. ajouter un **payload avatar** pour les joueurs ;
3. faire évoluer `map_pixi_controller.js` pour savoir construire un animator à partir :
   - soit d’un `spriteKey` legacy,
   - soit d’un `avatar` composite.

---

## Décision d’architecture recommandée

## 1. Ne pas remplacer le pipeline existant
Ne change pas le comportement de :
- mobs
- PNJ
- sprites legacy
- sprites editor/admin

Ils continuent à utiliser `SpriteAnimator` + `spriteKey`.

## 2. Ajouter un pipeline parallèle pour les joueurs
Pour les joueurs :
- on construit une **sprite sheet composite complète** au format **72x128**
- on crée ensuite un `SpriteAnimator` standard avec cette texture

En pratique :

`layers modulaires -> texture composite 72x128 -> SpriteAnimator existant`

C’est le point clé :  
**tu n’as pas besoin d’écrire un nouveau système d’animation complet**.

---

## Pourquoi cette approche est la bonne pour ce repo

### Avantages
- zéro rupture pour les sprites existants ;
- très peu de modifications dans `map_pixi_controller.js` ;
- pas besoin de changer les contrôles, le mouvement, la direction, les emotes ;
- le cache final se fait naturellement sur une **texture unique** par avatar ;
- compatible avec ton système de map Pixi actuel.

### Inconvénient assumé
Le composite est fait sur des **sheets entières 72x128** et pas frame par frame.

Dans ton cas, ce n’est pas un défaut :  
c’est justement ce qui rend l’intégration simple.

---

## Format d’assets recommandé pour le MVP

Pour être réellement plug-and-play dans `amethyste-idle`, je recommande de **rester sur le format actuel** :

- **sheet complète par layer**
- **72x128**
- **3x4**
- **24x32 par frame**
- parfaitement aligné entre toutes les couches

Exemples :

```text
assets/styles/images/avatar/
  base/
    human_m_light_01.png
    human_f_light_01.png

  hair/
    short_01.png
    ponytail_01.png

  beard/
    short_beard_01.png

  face/
    scar_eye_left_01.png
    tattoo_line_01.png

  gear/
    head/
      leather_cap_01.png
      iron_helm_01.png
    chest/
      tunic_01.png
      iron_armor_01.png
    belt/
      belt_01.png
    leg/
      pants_01.png
      plate_legs_01.png
    foot/
      boots_01.png
    shoulder/
      shoulder_iron_01.png
    main_weapon/
      sword_iron_01.png
      staff_oak_01.png
    side_weapon/
      shield_wood_01.png

  tools/
    tool_pickaxe_bronze.png
    tool_sickle_iron.png
```

---

## Mapping des slots adapté à ton modèle actuel

Le repo a déjà des constantes de gear dans `Item`.

Pour le MVP avatar, je te conseille d’aligner directement la couche visuelle sur les slots **déjà présents** :

### Slots visuels à supporter d’abord
- `head`
- `chest`
- `belt`
- `leg`
- `foot`
- `shoulder`
- `main_weapon`
- `side_weapon`

### Slots cosmétiques hors équipement
- `body`
- `hair`
- `beard`
- `face_mark`

### Outils
Comme le repo a déjà `TOOL_GEAR_LOCATIONS`, tu peux prévoir ensuite :
- `tool_pickaxe`
- `tool_sickle`
- `tool_fishing_rod`
- etc.

Mais pour un MVP visuel sur la map, je recommande de commencer par :
- main weapon
- off hand / side weapon
- head
- chest
- leg
- foot

---

## Ce qu’il faut stocker côté serveur

## Ne pas stocker le PNG final comme source de vérité
La vérité métier doit rester :
- l’apparence
- les choix cosmétiques
- l’équipement actuel

### Ajouter sur `Player`
Je recommande d’ajouter au minimum :

- `avatarAppearance` : JSON nullable
- `avatarHash` : string(64)
- `avatarVersion` : int
- `avatarUpdatedAt` : datetime

Exemple de structure :

```json
{
  "body": "human_m_light_01",
  "hair": "short_01",
  "hairColor": "#d6b25e",
  "beard": "short_beard_01",
  "beardColor": "#d6b25e",
  "eyesColor": "#4da3ff",
  "faceMark": "scar_eye_left_01"
}
```

### Pourquoi un `avatarHash`
Le hash doit dépendre de :
- l’apparence
- l’équipement visible
- une version de format

Exemple :

```text
sha256(
  appearance_json_normalized
  + visible_equipment_keys
  + asset_revision
  + "avatar-v1"
)
```

Ce hash sert à :
- invalider le cache proprement ;
- identifier une texture composite ;
- envoyer les mises à jour Mercure seulement quand le look change réellement.

---

## Ce qu’il faut mettre en cache

## Recommandation
### Oui au cache mémoire client
Oui, très pertinent.

Chaque navigateur peut conserver :
- `avatarHash -> PIXI.RenderTexture`

### Non au PNG persistant serveur pour le MVP
Je ne recommande pas de générer et sauvegarder des PNG finaux côté serveur dans un premier temps.

Pourquoi :
- invalidation plus compliquée ;
- risque de multiplier les fichiers inutiles ;
- il faut gérer les versions d’assets ;
- chaque client peut de toute façon recomposer en local et mettre en cache.

### Phase 2 optionnelle
Plus tard, tu pourras envisager :
- cache navigateur `IndexedDB`
- ou cache serveur “warm” pour avatars les plus fréquents

Mais pas nécessaire pour lancer le système.

---

## Flux recommandé

## Création / changement d’équipement
1. le joueur modifie son apparence ou son équipement ;
2. le backend recalcule `avatarHash` ;
3. le backend persiste :
   - apparence
   - hash
   - version
4. le backend publie un événement Mercure `player.avatar.updated`

## Côté client
1. `map_pixi_controller.js` reçoit l’état joueur ;
2. si `avatarHash` inconnu du cache :
   - compose la sprite sheet 72x128
   - crée un `SpriteAnimator`
   - met la texture composite en cache
3. si `avatarHash` connu :
   - réutilise la texture composite directement

---

## Évolution backend recommandée

## 1. Étendre `/api/map/entities`
Aujourd’hui, les joueurs ont tous `spriteKey: 'player_default'`.

Pour les joueurs, renvoie plutôt :

```json
{
  "id": 123,
  "name": "Rémy",
  "x": 42,
  "y": 18,
  "self": false,
  "spriteKey": "player_default",
  "renderMode": "avatar",
  "avatarHash": "4a96c1...",
  "avatar": {
    "baseSheet": "/assets/styles/images/avatar/base/human_m_light_01.png",
    "layers": [
      { "sheet": "/assets/styles/images/avatar/hair/short_01.png", "tint": 14004702 },
      { "sheet": "/assets/styles/images/avatar/beard/short_beard_01.png", "tint": 14004702 },
      { "sheet": "/assets/styles/images/avatar/face/scar_eye_left_01.png" },
      { "sheet": "/assets/styles/images/avatar/gear/chest/iron_armor_01.png" },
      { "sheet": "/assets/styles/images/avatar/gear/leg/plate_legs_01.png" },
      { "sheet": "/assets/styles/images/avatar/gear/foot/boots_01.png" },
      { "sheet": "/assets/styles/images/avatar/gear/main_weapon/sword_iron_01.png" },
      { "sheet": "/assets/styles/images/avatar/gear/side_weapon/shield_wood_01.png" }
    ]
  }
}
```

### Compatibilité
- `renderMode = legacy` ou absence de `avatar` => comportement actuel
- `renderMode = avatar` => pipeline modulaire

## 2. Étendre `/api/map/config`
Je recommande d’ajouter un catalogue d’assets avatar :

```json
{
  "sprites": { "...": "..." },
  "avatarCatalog": {
    "version": "2026-04-02",
    "sheets": [
      "/assets/styles/images/avatar/base/human_m_light_01.png",
      "/assets/styles/images/avatar/hair/short_01.png",
      "/assets/styles/images/avatar/beard/short_beard_01.png",
      "/assets/styles/images/avatar/gear/chest/iron_armor_01.png"
    ]
  }
}
```

### Pourquoi
Le contrôleur map peut alors :
- précharger les sheets avatar les plus courantes au chargement de carte ;
- lazy loader le reste seulement si nécessaire.

---

## Évolution frontend recommandée

## Principe
`map_pixi_controller.js` garde le rôle principal, mais délègue la construction des avatars à une factory dédiée.

### À ajouter
- `assets/lib/avatar/AvatarSpriteSheetCache.js`
- `assets/lib/avatar/AvatarTextureComposer.js`
- `assets/lib/avatar/AvatarAnimatorFactory.js`

### Rôle de chaque fichier
- `AvatarSpriteSheetCache` : LRU cache des composites
- `AvatarTextureComposer` : stack de layers 72x128 -> RenderTexture
- `AvatarAnimatorFactory` :
  - crée un `SpriteAnimator` legacy à partir d’un `spriteKey`
  - ou compose un avatar puis crée un `SpriteAnimator`

---

## Changement ciblé dans `map_pixi_controller.js`

## Avant
```js
_createAnimator(spriteKey) {
    const cfg = this._spriteConfig[spriteKey];
    if (!cfg) return null;

    const texture = this._spriteTextures[cfg.sheet];
    if (!texture) return null;

    return new SpriteAnimator({
        texture,
        type: cfg.type || 'single',
        charIndex: cfg.charIndex || 0,
    });
}
```

## Après
Le contrôleur doit accepter :
- `spriteKey` legacy
- `avatarPayload`

Exemple d’intention :

```js
_createAnimatorForEntity(entity) {
    if (entity.renderMode === 'avatar' && entity.avatar) {
        return this._avatarAnimatorFactory.createFromAvatarPayload(entity.avatarHash, entity.avatar);
    }

    return this._avatarAnimatorFactory.createFromLegacySpriteKey(entity.spriteKey);
}
```

Et dans `_createEntitySprite()` / `_createPlayerMarker()`, remplacer l’appel actuel par cette factory.

---

## Pourquoi réutiliser `SpriteAnimator`
Parce que ton moteur map a déjà :
- directions
- marche
- idle
- baseY
- emotes
- intégration dans les containers Pixi

Donc le bon niveau d’abstraction n’est pas de remplacer `SpriteAnimator`, mais de lui fournir **une texture déjà composite**.

---

## Personnalisation visuelle

## Oui, tu peux mémoriser une apparence paramétrique
C’est même ce qu’il faut faire.

### À stocker
- sheet de base
- style de cheveux
- couleur de cheveux
- barbe
- couleur de barbe
- couleur des yeux
- cicatrice / tatouage
- race / silhouette si besoin

### À ne pas stocker en masse
- pas une image par variante de cheveux teints ;
- pas une image par combinaison d’armure complète ;
- pas un PNG par look comme source principale.

Le cache composite doit être une **optimisation**, pas la vérité du jeu.

---

## Ordre des couches recommandé

Le plus simple pour le MVP, si toutes les sheets sont déjà dessinées dans le bon ordre visuel, est :

1. body
2. leg
3. foot
4. chest
5. belt
6. shoulder
7. hair_back si tu en as
8. head
9. beard
10. face_mark
11. main_weapon
12. side_weapon
13. hair_front si tu en as

### Important
Sur un RPG Maker 3x4 en petite taille, le plus simple est souvent de **dessiner les layers déjà adaptés à leur ordre final**, plutôt que d’essayer de faire du tri ultra-fin par frame.

---

## Où placer la logique backend

Je recommande :

```text
src/Service/Avatar/
  AvatarHashGenerator.php
  AvatarAppearanceResolver.php
  AvatarVisibleEquipmentResolver.php
  PlayerAvatarPayloadBuilder.php
```

### Rôles
- `AvatarHashGenerator` : hash stable
- `AvatarAppearanceResolver` : lit les colonnes JSON du joueur
- `AvatarVisibleEquipmentResolver` : extrait l’équipement visible depuis les `PlayerItem`
- `PlayerAvatarPayloadBuilder` : construit le payload envoyé à `/api/map/entities`

---

## Mapping visuel équipement -> layer

Comme ton système métier a déjà une logique d’équipement, fais une table de mapping dédiée :

```php
[
    Item::GEAR_LOCATION_HEAD => 'head',
    Item::GEAR_LOCATION_CHEST => 'chest',
    Item::GEAR_LOCATION_BELT => 'belt',
    Item::GEAR_LOCATION_LEG => 'leg',
    Item::GEAR_LOCATION_FOOT => 'foot',
    Item::GEAR_LOCATION_SHOULDER => 'shoulder',
    Item::GEAR_LOCATION_MAIN_WEAPON => 'main_weapon',
    Item::GEAR_LOCATION_SIDE_WEAPON => 'side_weapon',
]
```

Cela évite de coupler directement le nommage gameplay au nommage de fichier.

---

## Mercure : point important pour le multijoueur

Comme le projet utilise déjà Mercure, il faut aussi prévoir un message dédié à l’avatar.

### Exemple
Quand un joueur change d’équipement visible :
- publication d’un événement :
  - `type: player_avatar_updated`
  - `playerId`
  - `avatarHash`
  - `avatar`

Le client map peut alors :
- invalider l’ancien sprite de ce joueur ;
- recréer l’animator avec la nouvelle texture composite ;
- sans recharger toute la liste d’entités.

---

## Stratégie de migration

## Phase 1 — MVP propre
- uniquement pour les **joueurs**
- format **72x128 / 3x4**
- composition locale en Pixi
- cache mémoire LRU
- pas de PNG persistant serveur
- pas de recoloration complexe sauf cheveux/barbe éventuels

## Phase 2
- lazy load intelligent des sheets avatar
- cache IndexedDB
- Mercure avatar update
- outils visibles
- apparences raciales différentes

## Phase 3
- options cosmétiques premium
- variantes plus détaillées
- gestion “paper doll” dans l’écran d’inventaire / création personnage

---

## Risques à éviter

### 1. Vouloir passer tout le jeu en 32x32 frame d’un coup
Ton repo actuel est structuré autour du format RPG Maker 24x32 pour les personnages.
Ne fais pas cette migration en même temps que l’avatar modulaire.

### 2. Stocker des PNG finaux partout trop tôt
Tu vas te battre avec l’invalidation, les versions et le stockage.

### 3. Introduire une factory async partout d’un coup
Pour le MVP, précharge un catalogue raisonnable et garde le chemin principal simple.

### 4. Rendre les layers trop atomiques
En petite taille, trop de micro-couches = bruit visuel + maintenance lourde.

---

## Checklist d’implémentation

## Backend
- [ ] ajouter les colonnes `avatarAppearance`, `avatarHash`, `avatarVersion`, `avatarUpdatedAt` sur `Player`
- [ ] créer `AvatarHashGenerator`
- [ ] créer `PlayerAvatarPayloadBuilder`
- [ ] enrichir `/api/map/entities` pour les joueurs
- [ ] enrichir `/api/map/config` avec `avatarCatalog`

## Frontend
- [ ] ajouter `AvatarSpriteSheetCache.js`
- [ ] ajouter `AvatarTextureComposer.js`
- [ ] ajouter `AvatarAnimatorFactory.js`
- [ ] instancier la factory dans `map_pixi_controller.js`
- [ ] remplacer `_createAnimator()` par `_createAnimatorForEntity()`
- [ ] gérer le joueur local avec le même pipeline
- [ ] ajouter invalidation du cache sur changement de hash

## Assets
- [ ] créer un body de base `72x128`
- [ ] créer 3 à 5 coiffures
- [ ] créer 1 barbe
- [ ] créer 1 face mark
- [ ] créer 1 casque
- [ ] créer 2 torses
- [ ] créer 2 jambes
- [ ] créer 1 bottes
- [ ] créer 1 arme main droite
- [ ] créer 1 bouclier

---

## Verdict

Pour **amethyste-idle**, la meilleure solution n’est pas un paper doll totalement nouveau, mais :

**un composite de sheets legacy 72x128, mis en cache côté client, puis animé par le `SpriteAnimator` déjà existant.**

C’est :
- cohérent avec le repo ;
- raisonnable à implémenter ;
- performant pour le web ;
- extensible ensuite.
