# Réserve d'extension — le hors-périmètre retiré du jeu de base

> **OBJ-02b** (2026-08-02, `PLAN_ITEMS.md`). `GAME_ZONES` §3 réserve l'adamantite et
> l'astrétal à l'**Extension 1**, le voidium à l'**Extension 2** — mais leurs définitions,
> sept recettes et trois spots vivaient dans le jeu de base, sans un seul filon. Une recette
> visible et éternellement infabricable est un mensonge d'interface : tout est retiré des
> fixtures et versé ici. **Ce fichier n'est chargé par rien** — c'est une réserve, pas du
> contenu livré.
>
> Nuance découverte en route : le **Grand élixir du maître alchimiste** ne butait que par la
> gemme prismatique. Il a été **réécrit dans le périmètre de la base** (la gemme enchantée
> remplace la prismatique) plutôt que retiré — sans lui, le fruit du vide (raccordé par
> ZON-35) redevenait une récolte sans débouché.

## Ce que les extensions doivent rétablir (dans cet ordre)

1. **Les filons d'abord** : veines de zone pour `ore-adamantite` (Extension 1 — Mer de
   Sel), `ore-starmetal` (Extension 1 — Glacier du Silence), `ore-voidium` (Extension 2).
2. Les **minerais** (définitions ci-dessous), avec leurs corrections d'affinité
   (`ore-starmetal: light`, `ore-voidium: dark` — l'adamantite garde la ligne du métal,
   faute de canon qui dise autre chose).
3. Les **recettes** — les nœuds d'arbre qui les citaient existent toujours et sont déclarés
   en dette dans `SkillRecipeConsistencyTest::RECIPES_TO_AUTHOR` ; les nœuds de minage
   (`miner_adamantite_xs` à 55 pts, `miner_starmetal_xs` à 65 pts, l'action voidium de
   `miner_master`) sont à recréer.
4. Chaque lingot d'extension **exige un intrant du jeu de base** (`ore-orichalcum`,
   `crafted-mithril-ingot`, `ore-darksteel`...) — c'est ce qui renvoie les vétérans dans
   les zones anciennes (GAME_WORLD §5.5). Ne pas casser cette propriété en « simplifiant »
   les ingrédients.

## Suivi ouvert côté base

La courbe de recettes a perdu ses derniers crans (niveaux 6-10 : il ne reste que le manteau
du maître tanneur et le grand élixir réécrit). Le haut de la chaîne d'artisanat est à
re-remplir **dans le périmètre de la base** — chantier à instruire avec `PLAN_ZONES` et la
carte des minerais. En attendant, `ore-orichalcum` (filon réel à la Cité ensevelie) est un
intrant sans débouché, exception nommée dans `HarvestHarmonyTest`.

## Les 3 minerais (extraits de `fixtures/game/item/ore.yaml`)

```yaml
ore_adamantite (extends item):
    id: 90
    name: 'Minerai d''adamantite'
    description: 'Le minerai le plus dur connu, capable de briser toute lame ordinaire.'
    type: 'resource'
    slug: 'ore-adamantite'
    price: 250
    rarity: 'epic'
    domain: '@miner'

  ore_starmetal (extends item):
    id: 91
    name: 'Astrétal'
    description: 'Un métal tombé des étoiles, parcouru d''éclats cosmiques violet-bleu.'
    type: 'resource'
    slug: 'ore-starmetal'
    price: 300
    rarity: 'epic'
    domain: '@miner'

  ore_voidium (extends item):
    id: 93
    name: 'Voidium'
    description: 'Un minerai noir-violet parcouru de particules du Vide. Le plus rare et le plus dangereux à extraire.'
    type: 'resource'
    slug: 'ore-voidium'
    price: 1500
    rarity: 'legendary'
    domain: '@miner'
```

## Les 7 objets de la chaîne (extraits de `src/DataFixtures/ItemFixtures.php`)

```php
            'crafted_adamantite_ingot' => [
                'name' => 'Lingot d\'adamantite',
                'name_translations' => ['en' => 'Adamantite Ingot'],
                'description' => 'Lingot d\'adamantite d\'une dureté inégalée',
                'type' => 'resource',
                'slug' => 'crafted-adamantite-ingot',
                'price' => 1650,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Epic,
            ],

            'crafted_orichalcum_ingot' => [
                'name' => 'Lingot d\'orichalque',
                'name_translations' => ['en' => 'Orichalcum Ingot'],
                'description' => 'Lingot d\'orichalque mythique aux reflets rouge doré',
                'type' => 'resource',
                'slug' => 'crafted-orichalcum-ingot',
                'price' => 3400,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Epic,
            ],

            'crafted_gem_prismatic' => [
                'name' => 'Gemme prismatique',
                'name_translations' => ['en' => 'Prismatic Gem'],
                'description' => 'Une gemme aux reflets arc-en-ciel, concentrant toutes les énergies élémentaires',
                'type' => 'resource',
                'slug' => 'crafted-gem-prismatic',
                'price' => 1750,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Legendary,
            ],

            'legendary_ring' => [
                'name' => 'Anneau prismatique',
                'name_translations' => ['en' => 'Prismatic Ring'],
                'description' => 'Un anneau d\'orichalque serti d\'une gemme prismatique, chef-d\'œuvre de joaillerie',
                'type' => 'gear',
                'slug' => 'legendary-ring',
                'gear_location' => Item::GEAR_LOCATION_RING_1,
                'price' => 8650,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Epic,
                'protection' => 5,
                'materiaSlots' => 3,
                'level' => 18,
                // ECO-08 : haut de gamme endgame — lie, donc hors de l'hotel des ventes.
                'bindType' => 'bind_on_pickup',
            ],

            'legendary_amulet' => [
                'name' => 'Amulette prismatique',
                'name_translations' => ['en' => 'Prismatic Amulet'],
                'description' => 'Une amulette d\'orichalque abritant une gemme prismatique, irradiant de puissance',
                'type' => 'gear',
                'slug' => 'legendary-amulet',
                'gear_location' => 'neck',
                'price' => 8650,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Epic,
                'protection' => 6,
                'materiaSlots' => 3,
                'level' => 18,
                // ECO-08 : haut de gamme endgame — lie, donc hors de l'hotel des ventes.
                'bindType' => 'bind_on_pickup',
            ],

            'masterwork_blade' => [
                'name' => 'Lame du maitre forgeron',
                'name_translations' => ['en' => 'Master Blacksmith\'s Blade'],
                'description' => 'Lame d\'orichalque trempee dans le sang d\'etoile, signature des plus grands forgerons.',
                'type' => 'gear',
                'slug' => 'masterwork-blade',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'price' => 14200,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 500,
                'rarity' => ItemRarity::Legendary,
                'materiaSlots' => 3,
                'level' => 20,
                // ECO-08 : haut de gamme endgame — lie, donc hors de l'hotel des ventes.
                'bindType' => 'bind_on_pickup',
            ],


            'masterwork_starforged_ring' => [
                'name' => 'Anneau du maitre joaillier',
                'name_translations' => ['en' => 'Master Jeweler\'s Ring'],
                'description' => 'Anneau d\'orichalque serti d\'une gemme prismatique parfaitement taillee, pulsant d\'energie pure.',
                'type' => 'gear',
                'slug' => 'masterwork-starforged-ring',
                'gear_location' => Item::GEAR_LOCATION_RING_1,
                'price' => 11000,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Legendary,
                'protection' => 8,
                'materiaSlots' => 4,
                'level' => 20,
                // ECO-08 : haut de gamme endgame — lie, donc hors de l'hotel des ventes.
                'bindType' => 'bind_on_pickup',
            ],
```

## Les 7 recettes (extraites de `src/DataFixtures/RecipeFixtures.php`)

```php
            'recipe_adamantite_ingot' => [
                'name' => 'Lingot d\'adamantite',
                'slug' => 'recipe-adamantite-ingot',
                'craft' => 'forgeron',
                'required_level' => 6,
                'ingredients' => [
                    ['slug' => 'ore-adamantite', 'quantity' => 3],
                    ['slug' => 'ore-darksteel', 'quantity' => 2],
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_adamantite_ingot',
                'crafting_time' => 20,
                'xp_reward' => 80,
                'description' => 'Fond l\'adamantite avec du sombracier pour un alliage indestructible.',
                'name_translations' => ['en' => 'Adamantite Ingot'],
            ],

            'recipe_orichalcum_ingot' => [
                'name' => 'Lingot d\'orichalque',
                'slug' => 'recipe-orichalcum-ingot',
                'craft' => 'forgeron',
                'required_level' => 8,
                'ingredients' => [
                    ['slug' => 'ore-orichalcum', 'quantity' => 3],
                    ['slug' => 'ore-starmetal', 'quantity' => 2],
                    ['slug' => 'crafted-adamantite-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_orichalcum_ingot',
                'crafting_time' => 25,
                'xp_reward' => 120,
                'description' => 'Le métal mythique des anciens, forgé avec l\'astrétal des étoiles.',
                'name_translations' => ['en' => 'Orichalcum Ingot'],
            ],

            'recipe_prismatic_gem' => [
                'name' => 'Gemme prismatique',
                'slug' => 'recipe-prismatic-gem',
                'craft' => 'joaillier',
                'required_level' => 8,
                'ingredients' => [
                    ['slug' => 'crafted-gem-enchanted', 'quantity' => 2],
                    ['slug' => 'ore-starmetal', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_gem_prismatic',
                'crafting_time' => 20,
                'xp_reward' => 80,
                'description' => 'Fusionne des gemmes enchantées avec de l\'astrétal pour créer un prisme multi-élémentaire.',
                'name_translations' => ['en' => 'Prismatic Gem'],
            ],

            'recipe_legendary_ring' => [
                'name' => 'Anneau prismatique',
                'slug' => 'recipe-legendary-ring',
                'craft' => 'joaillier',
                'required_level' => 10,
                'ingredients' => [
                    ['slug' => 'crafted-orichalcum-ingot', 'quantity' => 2],
                    ['slug' => 'crafted-gem-prismatic', 'quantity' => 1],
                ],
                'result_ref' => 'legendary_ring',
                'crafting_time' => 25,
                'xp_reward' => 120,
                'description' => 'Chef-d\'œuvre ultime : un anneau d\'orichalque abritant une gemme prismatique.',
                'name_translations' => ['en' => 'Prismatic Ring'],
            ],

            'recipe_legendary_amulet' => [
                'name' => 'Amulette prismatique',
                'slug' => 'recipe-legendary-amulet',
                'craft' => 'joaillier',
                'required_level' => 10,
                'ingredients' => [
                    ['slug' => 'crafted-orichalcum-ingot', 'quantity' => 2],
                    ['slug' => 'crafted-gem-prismatic', 'quantity' => 1],
                ],
                'result_ref' => 'legendary_amulet',
                'crafting_time' => 25,
                'xp_reward' => 120,
                'description' => 'Chef-d\'œuvre ultime : une amulette d\'orichalque irradiant de puissance prismatique.',
                'name_translations' => ['en' => 'Prismatic Amulet'],
            ],

            'recipe_masterwork_blade' => [
                'name' => 'Lame du maitre forgeron',
                'slug' => 'recipe-masterwork-blade',
                'craft' => 'forgeron',
                'required_level' => 10,
                'required_specialization' => CraftSpecialization::Forgeron,
                'ingredients' => [
                    ['slug' => 'crafted-orichalcum-ingot', 'quantity' => 3],
                    ['slug' => 'crafted-adamantite-ingot', 'quantity' => 2],
                    ['slug' => 'ore-starmetal', 'quantity' => 2],
                ],
                'result_ref' => 'masterwork_blade',
                'crafting_time' => 30,
                'xp_reward' => 200,
                'description' => 'Reservee aux Maitres Forgerons. Forge la lame ultime, signature des grands artisans.',
                'name_translations' => ['en' => 'Master Blacksmith\'s Blade'],
            ],


            'recipe_masterwork_starforged_ring' => [
                'name' => 'Anneau du maitre joaillier',
                'slug' => 'recipe-masterwork-starforged-ring',
                'craft' => 'joaillier',
                'required_level' => 10,
                'required_specialization' => CraftSpecialization::Joaillier,
                'ingredients' => [
                    ['slug' => 'crafted-orichalcum-ingot', 'quantity' => 2],
                    ['slug' => 'crafted-gem-prismatic', 'quantity' => 2],
                    ['slug' => 'ore-starmetal', 'quantity' => 2],
                ],
                'result_ref' => 'masterwork_starforged_ring',
                'crafting_time' => 30,
                'xp_reward' => 200,
                'description' => 'Reservee aux Maitres Joailliers. Cisele un anneau pulsant d\'energie pure.',
                'name_translations' => ['en' => 'Master Jeweler\'s Ring'],
            ],
```

## Les 3 spots legacy (extraits d'`ObjectLayerFixtures`, système d'avant le pivot)

`spot-adamantite-xs` (Filon d'adamantite, mines 68.2), `spot-starmetal-xs` (Météorite
d'astrétal, mines 70.2, nocturne), `spot-voidium-xs` (Fissure de voidium, mines 78.2,
nocturne). Le modèle actuel étant la veine de zone (`gather:` de `zones/*.yaml`), les
extensions déclareront des **veines**, pas des spots.
