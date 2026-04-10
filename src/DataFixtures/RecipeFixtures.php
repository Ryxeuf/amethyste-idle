<?php

namespace App\DataFixtures;

use App\DataFixtures\Game\ItemFixtures as GameItemFixtures;
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

            // --- Forge T2 : Armures en fer ---
            'recipe_iron_chestplate' => [
                'name' => 'Plastron en fer',
                'slug' => 'recipe-iron-chestplate',
                'craft' => 'forgeron',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 5],
                    ['slug' => 'crafted-bronze-ingot', 'quantity' => 2],
                ],
                'result_ref' => 'iron_chestplate',
                'crafting_time' => 12,
                'xp_reward' => 35,
                'description' => 'Forge un plastron en fer massif, protection standard des soldats aguerris.',
            ],
            'recipe_iron_greaves' => [
                'name' => 'Jambières en fer',
                'slug' => 'recipe-iron-greaves',
                'craft' => 'forgeron',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 4],
                    ['slug' => 'crafted-bronze-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'iron_greaves',
                'crafting_time' => 10,
                'xp_reward' => 30,
                'description' => 'Forge des jambières en fer articulées pour protéger cuisses et genoux.',
            ],
            'recipe_iron_boots' => [
                'name' => 'Bottes en fer',
                'slug' => 'recipe-iron-boots',
                'craft' => 'forgeron',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 3],
                    ['slug' => 'ore-copper', 'quantity' => 2],
                ],
                'result_ref' => 'iron_boots',
                'crafting_time' => 8,
                'xp_reward' => 25,
                'description' => 'Forge des bottes renforcées de plaques de fer.',
            ],
            'recipe_iron_gauntlets' => [
                'name' => 'Gantelets en fer',
                'slug' => 'recipe-iron-gauntlets',
                'craft' => 'forgeron',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 3],
                    ['slug' => 'ore-copper', 'quantity' => 1],
                ],
                'result_ref' => 'iron_gauntlets',
                'crafting_time' => 8,
                'xp_reward' => 25,
                'description' => 'Forge des gantelets en fer aux jointures renforcées.',
            ],

            // --- Forge T3 : Armures en mithril ---
            'recipe_mithril_helm' => [
                'name' => 'Heaume de mithril',
                'slug' => 'recipe-mithril-helm',
                'craft' => 'forgeron',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 2],
                    ['slug' => 'ore-platinum', 'quantity' => 1],
                ],
                'result_ref' => 'mithril_helm',
                'crafting_time' => 15,
                'xp_reward' => 60,
                'description' => 'Forge un heaume de mithril aux reflets argentés, léger et résistant.',
            ],
            'recipe_mithril_cuirass' => [
                'name' => 'Cuirasse de mithril',
                'slug' => 'recipe-mithril-cuirass',
                'craft' => 'forgeron',
                'required_level' => 6,
                'ingredients' => [
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 4],
                    ['slug' => 'ore-platinum', 'quantity' => 2],
                ],
                'result_ref' => 'mithril_cuirass',
                'crafting_time' => 20,
                'xp_reward' => 80,
                'description' => 'Forge une cuirasse de mithril étincelante, presque aussi légère que le cuir.',
            ],
            'recipe_mithril_greaves' => [
                'name' => 'Grèves de mithril',
                'slug' => 'recipe-mithril-greaves',
                'craft' => 'forgeron',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 2],
                    ['slug' => 'ore-mithril', 'quantity' => 2],
                ],
                'result_ref' => 'mithril_greaves',
                'crafting_time' => 15,
                'xp_reward' => 60,
                'description' => 'Forge des grèves de mithril ouvragées, offrant mobilité et protection.',
            ],
            'recipe_mithril_sabatons' => [
                'name' => 'Solerets de mithril',
                'slug' => 'recipe-mithril-sabatons',
                'craft' => 'forgeron',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 2],
                    ['slug' => 'ore-mithril', 'quantity' => 1],
                ],
                'result_ref' => 'mithril_sabatons',
                'crafting_time' => 12,
                'xp_reward' => 55,
                'description' => 'Forge des solerets de mithril silencieux malgré leur solidité.',
            ],
            'recipe_mithril_gauntlets' => [
                'name' => 'Gantelets de mithril',
                'slug' => 'recipe-mithril-gauntlets',
                'craft' => 'forgeron',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 2],
                    ['slug' => 'ore-mithril', 'quantity' => 1],
                ],
                'result_ref' => 'mithril_gauntlets',
                'crafting_time' => 12,
                'xp_reward' => 55,
                'description' => 'Forge des gantelets de mithril aux articulations souples.',
            ],
            'recipe_mithril_pauldrons' => [
                'name' => 'Épaulières de mithril',
                'slug' => 'recipe-mithril-pauldrons',
                'craft' => 'forgeron',
                'required_level' => 6,
                'ingredients' => [
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 3],
                    ['slug' => 'ore-platinum', 'quantity' => 1],
                ],
                'result_ref' => 'mithril_pauldrons',
                'crafting_time' => 15,
                'xp_reward' => 65,
                'description' => 'Forge des épaulières de mithril ornées de motifs elfiques.',
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

            // --- Tannerie T1 : accessoires cuir basiques ---
            'recipe_leather_strip' => [
                'name' => 'Lanière de cuir',
                'slug' => 'recipe-leather-strip',
                'craft' => 'tanneur',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 2],
                ],
                'result_ref' => 'crafted_leather_strip',
                'result_quantity' => 2,
                'crafting_time' => 3,
                'xp_reward' => 8,
                'description' => 'Découpe et tanne le cuir brut en lanières utilisables.',
            ],
            'recipe_leather_gloves' => [
                'name' => 'Gants de cuir',
                'slug' => 'recipe-leather-gloves',
                'craft' => 'tanneur',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 2],
                ],
                'result_ref' => 'leather_gloves',
                'crafting_time' => 5,
                'xp_reward' => 15,
                'description' => 'Confectionne des gants de cuir souples et résistants.',
            ],
            'recipe_leather_belt' => [
                'name' => 'Ceinture de cuir',
                'slug' => 'recipe-leather-belt',
                'craft' => 'tanneur',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 2],
                ],
                'result_ref' => 'leather_belt',
                'crafting_time' => 4,
                'xp_reward' => 12,
                'description' => 'Confectionne une ceinture de cuir avec boucle en bronze.',
            ],
            'recipe_leather_shoulders' => [
                'name' => 'Épaulières de cuir',
                'slug' => 'recipe-leather-shoulders',
                'craft' => 'tanneur',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 3],
                ],
                'result_ref' => 'leather_shoulders',
                'crafting_time' => 7,
                'xp_reward' => 20,
                'description' => 'Confectionne des épaulières de cuir renforcées de rivets.',
            ],
            'recipe_leather_pants' => [
                'name' => 'Jambières de cuir',
                'slug' => 'recipe-leather-pants',
                'craft' => 'tanneur',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 3],
                    ['slug' => 'leather-thick', 'quantity' => 1],
                ],
                'result_ref' => 'leather_pants',
                'crafting_time' => 8,
                'xp_reward' => 22,
                'description' => 'Confectionne des jambières de cuir offrant souplesse et protection.',
            ],

            // --- Tannerie T2 : cuir renforcé ---
            'recipe_hardened_vest' => [
                'name' => 'Plastron de cuir renforcé',
                'slug' => 'recipe-hardened-vest',
                'craft' => 'tanneur',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'leather-thick', 'quantity' => 4],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                    ['slug' => 'leather-bone', 'quantity' => 1],
                ],
                'result_ref' => 'hardened_vest',
                'crafting_time' => 12,
                'xp_reward' => 35,
                'description' => 'Confectionne un plastron en cuir bouilli renforcé d\'os de monstre.',
            ],
            'recipe_hardened_boots' => [
                'name' => 'Bottes de cuir renforcé',
                'slug' => 'recipe-hardened-boots',
                'craft' => 'tanneur',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'leather-thick', 'quantity' => 2],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                    ['slug' => 'leather-bone', 'quantity' => 1],
                ],
                'result_ref' => 'hardened_boots',
                'crafting_time' => 10,
                'xp_reward' => 30,
                'description' => 'Confectionne des bottes en cuir épais avec semelles renforcées.',
            ],
            'recipe_hardened_gloves' => [
                'name' => 'Gants de cuir renforcé',
                'slug' => 'recipe-hardened-gloves',
                'craft' => 'tanneur',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'leather-thick', 'quantity' => 2],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                    ['slug' => 'leather-fang', 'quantity' => 1],
                ],
                'result_ref' => 'hardened_gloves',
                'crafting_time' => 10,
                'xp_reward' => 30,
                'description' => 'Confectionne des gants de cuir renforcés avec des crocs de bête.',
            ],
            'recipe_hardened_belt' => [
                'name' => 'Ceinture de cuir renforcé',
                'slug' => 'recipe-hardened-belt',
                'craft' => 'tanneur',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'leather-thick', 'quantity' => 2],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'hardened_belt',
                'crafting_time' => 8,
                'xp_reward' => 25,
                'description' => 'Confectionne une ceinture de cuir tanné épaisse et robuste.',
            ],
            'recipe_hardened_shoulders' => [
                'name' => 'Épaulières de cuir renforcé',
                'slug' => 'recipe-hardened-shoulders',
                'craft' => 'tanneur',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'leather-thick', 'quantity' => 3],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                    ['slug' => 'leather-bone', 'quantity' => 1],
                ],
                'result_ref' => 'hardened_shoulders',
                'crafting_time' => 12,
                'xp_reward' => 35,
                'description' => 'Confectionne des épaulières en cuir bouilli ornées de plaques d\'os.',
            ],
            'recipe_hardened_pants' => [
                'name' => 'Jambières de cuir renforcé',
                'slug' => 'recipe-hardened-pants',
                'craft' => 'tanneur',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'leather-thick', 'quantity' => 3],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                    ['slug' => 'leather-fang', 'quantity' => 1],
                ],
                'result_ref' => 'hardened_pants',
                'crafting_time' => 12,
                'xp_reward' => 35,
                'description' => 'Confectionne des jambières de cuir renforcé aux genoux protégés.',
            ],

            // --- Tannerie T3 : cuir exotique ---
            'recipe_exotic_leather_vest' => [
                'name' => 'Plastron de cuir exotique',
                'slug' => 'recipe-exotic-leather-vest',
                'craft' => 'tanneur',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'leather-werewolf-fur', 'quantity' => 2],
                    ['slug' => 'leather-thick', 'quantity' => 3],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                ],
                'result_ref' => 'exotic_leather_vest',
                'crafting_time' => 15,
                'xp_reward' => 50,
                'description' => 'Confectionne un plastron en fourrure de loup-garou d\'une résistance surnaturelle.',
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
            GameItemFixtures::class,
        ];
    }
}
