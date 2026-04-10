<?php

namespace App\DataFixtures;

use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RecipeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $recipes = $this->getRecipesData();

        foreach ($recipes as $key => $data) {
            $recipe = new Recipe();
            $recipe->setName($data['name']);
            $recipe->setSlug($data['slug']);
            $recipe->setCraft($data['craft']);
            $recipe->setRequiredLevel($data['required_level'] ?? 1);
            $recipe->setIngredients($data['ingredients']);
            $recipe->setResult($this->getReference($data['result_ref'], Item::class));
            $recipe->setResultQuantity($data['result_quantity'] ?? 1);
            $recipe->setCraftingTime($data['crafting_time'] ?? 5);
            $recipe->setXpReward($data['xp_reward'] ?? 10);
            $recipe->setDescription($data['description'] ?? null);
            $recipe->setCreatedAt(new \DateTime());
            $recipe->setUpdatedAt(new \DateTime());

            if (isset($data['quality'])) {
                $recipe->setQuality($data['quality']);
            }

            $manager->persist($recipe);
            $this->addReference($key, $recipe);
        }

        $manager->flush();
    }

    private function getRecipesData(): array
    {
        return [
            // --- Forge (forgeron) ---
            'recipe_iron_dagger' => [
                'name' => 'Dague en fer',
                'slug' => 'recipe-iron-dagger',
                'craft' => 'forgeron',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 2],
                ],
                'result_ref' => 'iron_dagger',
                'crafting_time' => 5,
                'xp_reward' => 15,
                'description' => 'Forge une dague en fer tranchante.',
            ],
            'recipe_short_sword' => [
                'name' => 'Epée courte',
                'slug' => 'recipe-short-sword',
                'craft' => 'forgeron',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 3],
                    ['slug' => 'ore-copper', 'quantity' => 1],
                ],
                'result_ref' => 'short_sword',
                'crafting_time' => 8,
                'xp_reward' => 20,
                'description' => 'Forge une épée courte équilibrée.',
            ],
            'recipe_iron_shield' => [
                'name' => 'Bouclier en fer',
                'slug' => 'recipe-iron-shield',
                'craft' => 'forgeron',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 4],
                    ['slug' => 'ore-copper', 'quantity' => 2],
                ],
                'result_ref' => 'iron_shield',
                'crafting_time' => 10,
                'xp_reward' => 25,
                'description' => 'Forge un bouclier en fer solide.',
            ],
            'recipe_iron_helmet' => [
                'name' => 'Casque en fer',
                'slug' => 'recipe-iron-helmet',
                'craft' => 'forgeron',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 3],
                ],
                'result_ref' => 'iron_helmet',
                'crafting_time' => 8,
                'xp_reward' => 20,
                'description' => 'Forge un casque en fer protecteur.',
            ],

            // --- Lingots de forge ---
            'recipe_bronze_ingot' => [
                'name' => 'Lingot de bronze',
                'slug' => 'recipe-bronze-ingot',
                'craft' => 'forgeron',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'ore-copper', 'quantity' => 2],
                    ['slug' => 'ore-tin', 'quantity' => 2],
                ],
                'result_ref' => 'crafted_bronze_ingot',
                'crafting_time' => 5,
                'xp_reward' => 10,
                'description' => 'Allie cuivre et étain pour forger un lingot de bronze.',
            ],
            'recipe_cobalt_ingot' => [
                'name' => 'Lingot de cobalt',
                'slug' => 'recipe-cobalt-ingot',
                'craft' => 'forgeron',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'ore-cobalt', 'quantity' => 3],
                ],
                'result_ref' => 'crafted_cobalt_ingot',
                'crafting_time' => 10,
                'xp_reward' => 30,
                'description' => 'Fond le cobalt en un lingot d\'un bleu profond.',
            ],
            'recipe_mithril_ingot' => [
                'name' => 'Lingot de mithril',
                'slug' => 'recipe-mithril-ingot',
                'craft' => 'forgeron',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'ore-mithril', 'quantity' => 3],
                    ['slug' => 'ore-platinum', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_mithril_ingot',
                'crafting_time' => 15,
                'xp_reward' => 50,
                'description' => 'Forger le mithril requiert un savoir-faire exceptionnel.',
            ],
            'recipe_adamantite_ingot' => [
                'name' => 'Lingot d\'adamantite',
                'slug' => 'recipe-adamantite-ingot',
                'craft' => 'forgeron',
                'required_level' => 6,
                'ingredients' => [
                    ['slug' => 'ore-adamantite', 'quantity' => 3],
                    ['slug' => 'ore-darksteel', 'quantity' => 2],
                ],
                'result_ref' => 'crafted_adamantite_ingot',
                'crafting_time' => 20,
                'xp_reward' => 80,
                'description' => 'Fond l\'adamantite avec du sombracier pour un alliage indestructible.',
            ],
            'recipe_orichalcum_ingot' => [
                'name' => 'Lingot d\'orichalque',
                'slug' => 'recipe-orichalcum-ingot',
                'craft' => 'forgeron',
                'required_level' => 8,
                'ingredients' => [
                    ['slug' => 'ore-orichalcum', 'quantity' => 3],
                    ['slug' => 'ore-starmetal', 'quantity' => 2],
                ],
                'result_ref' => 'crafted_orichalcum_ingot',
                'crafting_time' => 25,
                'xp_reward' => 120,
                'description' => 'Le métal mythique des anciens, forgé avec l\'astrétal des étoiles.',
            ],

            // --- Tannerie (tanneur) ---
            'recipe_leather_boots' => [
                'name' => 'Bottes en cuir',
                'slug' => 'recipe-leather-boots',
                'craft' => 'tanneur',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 2],
                ],
                'result_ref' => 'leather_boots',
                'crafting_time' => 5,
                'xp_reward' => 15,
                'description' => 'Confectionne des bottes en cuir confortables.',
            ],
            'recipe_leather_hat' => [
                'name' => 'Chapeau de cuir',
                'slug' => 'recipe-leather-hat',
                'craft' => 'tanneur',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 2],
                ],
                'result_ref' => 'leather_hat',
                'crafting_time' => 5,
                'xp_reward' => 15,
                'description' => 'Confectionne un chapeau de cuir protecteur.',
            ],
            'recipe_leather_armor' => [
                'name' => 'Armure de cuir',
                'slug' => 'recipe-leather-armor',
                'craft' => 'tanneur',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 4],
                    ['slug' => 'leather-thick', 'quantity' => 1],
                ],
                'result_ref' => 'leather_armor',
                'crafting_time' => 10,
                'xp_reward' => 25,
                'description' => 'Confectionne une armure de cuir résistante.',
            ],

            // --- Alchimie (alchimiste) ---
            'recipe_healing_potion' => [
                'name' => 'Potion de soin',
                'slug' => 'recipe-healing-potion',
                'craft' => 'alchimiste',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'plant-mint', 'quantity' => 2],
                    ['slug' => 'plant-sage', 'quantity' => 1],
                ],
                'result_ref' => 'healing_potion_small',
                'crafting_time' => 4,
                'xp_reward' => 12,
                'description' => 'Prépare une potion de soin à partir de plantes médicinales.',
            ],
            'recipe_antidote' => [
                'name' => 'Antidote',
                'slug' => 'recipe-antidote',
                'craft' => 'alchimiste',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'plant-sage', 'quantity' => 2],
                    ['slug' => 'plant-lavender', 'quantity' => 1],
                ],
                'result_ref' => 'antidote',
                'crafting_time' => 6,
                'xp_reward' => 18,
                'description' => 'Prépare un antidote purifiant contre les poisons.',
            ],

            // --- Alchimie supplémentaire (alchimiste) ---
            'recipe_potion_base' => [
                'name' => 'Base de potion',
                'slug' => 'recipe-potion-base',
                'craft' => 'alchimiste',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'plant-mint', 'quantity' => 2],
                    ['slug' => 'plant-chamomile', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_potion_base',
                'crafting_time' => 3,
                'xp_reward' => 8,
                'description' => 'Prépare une base de potion à partir de plantes fraîches.',
            ],
            'recipe_onguent_healing' => [
                'name' => 'Onguent de guérison',
                'slug' => 'recipe-onguent-healing',
                'craft' => 'alchimiste',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'plant-aloe-vera', 'quantity' => 2],
                    ['slug' => 'plant-chamomile', 'quantity' => 1],
                ],
                'result_ref' => 'onguent_healing',
                'crafting_time' => 5,
                'xp_reward' => 15,
                'description' => 'Un baume cicatrisant qui régénère les blessures.',
            ],
            'recipe_healing_medium' => [
                'name' => 'Potion de soin',
                'slug' => 'recipe-healing-medium',
                'craft' => 'alchimiste',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                    ['slug' => 'plant-thyme', 'quantity' => 2],
                    ['slug' => 'plant-sage', 'quantity' => 1],
                ],
                'result_ref' => 'healing_potion_medium',
                'crafting_time' => 6,
                'xp_reward' => 18,
                'description' => 'Prépare une potion de soin modérée à partir d\'une base et de plantes.',
            ],
            'recipe_energy_potion' => [
                'name' => 'Potion d\'énergie',
                'slug' => 'recipe-energy-potion',
                'craft' => 'alchimiste',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                    ['slug' => 'plant-ginseng', 'quantity' => 2],
                ],
                'result_ref' => 'energy_potion_small',
                'crafting_time' => 5,
                'xp_reward' => 15,
                'description' => 'Prépare une potion d\'énergie revigorante au ginseng.',
            ],
            'recipe_poison_vial' => [
                'name' => 'Fiole de poison',
                'slug' => 'recipe-poison-vial',
                'craft' => 'alchimiste',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'plant-nightshade', 'quantity' => 2],
                    ['slug' => 'poisonous-mushroom', 'quantity' => 1],
                ],
                'result_ref' => 'poison_vial',
                'crafting_time' => 7,
                'xp_reward' => 22,
                'description' => 'Concentre des toxines végétales dans une fiole fragile.',
            ],
            'recipe_elixir_force' => [
                'name' => 'Élixir de force',
                'slug' => 'recipe-elixir-force',
                'craft' => 'alchimiste',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                    ['slug' => 'plant-ginseng', 'quantity' => 2],
                    ['slug' => 'plant-mandrake', 'quantity' => 1],
                ],
                'result_ref' => 'elixir_force',
                'crafting_time' => 8,
                'xp_reward' => 25,
                'description' => 'Infuse la puissance de la mandragore dans un élixir de rage.',
            ],
            'recipe_elixir_defense' => [
                'name' => 'Élixir de défense',
                'slug' => 'recipe-elixir-defense',
                'craft' => 'alchimiste',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                    ['slug' => 'plant-thyme', 'quantity' => 2],
                    ['slug' => 'plant-valerian', 'quantity' => 1],
                ],
                'result_ref' => 'elixir_defense',
                'crafting_time' => 8,
                'xp_reward' => 25,
                'description' => 'Mélange des plantes fortifiantes pour créer un bouclier alchimique.',
            ],
            'recipe_healing_major' => [
                'name' => 'Potion de soin majeure',
                'slug' => 'recipe-healing-major',
                'craft' => 'alchimiste',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 2],
                    ['slug' => 'plant-aloe-vera', 'quantity' => 2],
                    ['slug' => 'plant-mandrake', 'quantity' => 1],
                ],
                'result_ref' => 'healing_potion_major',
                'crafting_time' => 10,
                'xp_reward' => 35,
                'description' => 'Prépare une puissante potion de soin à base de mandragore.',
            ],
            'recipe_elixir_vitality' => [
                'name' => 'Élixir de vitalité',
                'slug' => 'recipe-elixir-vitality',
                'craft' => 'alchimiste',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 2],
                    ['slug' => 'plant-mandrake', 'quantity' => 2],
                    ['slug' => 'plant-ginseng', 'quantity' => 1],
                ],
                'result_ref' => 'elixir_vitality',
                'crafting_time' => 12,
                'xp_reward' => 40,
                'description' => 'Un élixir de mandragore et ginseng d\'une puissance exceptionnelle.',
            ],
            // --- Joaillerie (joaillier) ---
            'recipe_copper_ring' => [
                'name' => 'Anneau de cuivre',
                'slug' => 'recipe-copper-ring',
                'craft' => 'joaillier',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'ore-copper', 'quantity' => 3],
                ],
                'result_ref' => 'copper_ring',
                'crafting_time' => 6,
                'xp_reward' => 15,
                'description' => 'Façonne un anneau de cuivre simple mais élégant.',
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            ItemFixtures::class,
        ];
    }
}
