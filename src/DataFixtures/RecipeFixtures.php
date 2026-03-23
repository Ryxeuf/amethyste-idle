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
