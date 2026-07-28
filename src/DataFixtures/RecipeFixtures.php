<?php

namespace App\DataFixtures;

use App\DataFixtures\Game\ItemFixtures as GameItemFixtures;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Enum\CraftSpecialization;
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

            if (isset($data['required_specialization'])) {
                $recipe->setRequiredSpecialization($data['required_specialization']);
            }

            if (isset($data['name_translations']) && is_array($data['name_translations'])) {
                $recipe->setNameTranslations($data['name_translations']);
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
                'name_translations' => ['en' => 'Iron Dagger'],
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
                'name_translations' => ['en' => 'Short Sword'],
            ],
            'recipe_iron_shield' => [
                'name' => 'Bouclier en fer',
                'slug' => 'recipe-iron-shield',
                'craft' => 'forgeron',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 4],
                    ['slug' => 'ore-copper', 'quantity' => 2],
                    // ECO-14 : les enarmes. Premier point ou le forgeron depend
                    // du tanneur — un bouclier sans sangle ne se porte pas.
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'iron_shield',
                'crafting_time' => 10,
                'xp_reward' => 25,
                'description' => 'Forge un bouclier en fer solide.',
                'name_translations' => ['en' => 'Iron Shield'],
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
                'name_translations' => ['en' => 'Iron Helmet'],
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
                'name_translations' => ['en' => 'Bronze Ingot'],
            ],
            // ECO-25 — le barreau manquant de l'echelle. `crafted-iron-ingot`
            // existait comme objet sans producteur ni consommateur : la fonte du
            // fer, le geste le plus banal d'une forge, n'existait pas. C'est lui
            // qui rend l'echelle **continue** — bronze, fer, cobalt, mithril —
            // et qui fait qu'un lingot de haut palier doit quelque chose au
            // cuivre d'un debutant.
            'recipe_iron_ingot' => [
                'name' => 'Lingot de fer',
                'slug' => 'recipe-iron-ingot',
                'craft' => 'forgeron',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 3],
                    ['slug' => 'crafted-bronze-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_iron_ingot',
                'crafting_time' => 7,
                'xp_reward' => 18,
                'description' => 'Fond le fer sur un lit de bronze pour en tirer un lingot regulier.',
                'name_translations' => ['en' => 'Iron Ingot'],
            ],
            'recipe_cobalt_ingot' => [
                'name' => 'Lingot de cobalt',
                'slug' => 'recipe-cobalt-ingot',
                'craft' => 'forgeron',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'ore-cobalt', 'quantity' => 3],
                    ['slug' => 'crafted-iron-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_cobalt_ingot',
                'crafting_time' => 10,
                'xp_reward' => 30,
                'description' => 'Fond le cobalt en un lingot d\'un bleu profond.',
                'name_translations' => ['en' => 'Cobalt Ingot'],
            ],
            'recipe_mithril_ingot' => [
                'name' => 'Lingot de mithril',
                'slug' => 'recipe-mithril-ingot',
                'craft' => 'forgeron',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'ore-mithril', 'quantity' => 3],
                    ['slug' => 'ore-platinum', 'quantity' => 1],
                    ['slug' => 'crafted-cobalt-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_mithril_ingot',
                'crafting_time' => 15,
                'xp_reward' => 50,
                'description' => 'Forger le mithril requiert un savoir-faire exceptionnel.',
                'name_translations' => ['en' => 'Mithril Ingot'],
            ],
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
                'name_translations' => ['en' => 'Iron Chestplate'],
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
                'name_translations' => ['en' => 'Iron Greaves'],
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
                'name_translations' => ['en' => 'Iron Boots'],
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
                'name_translations' => ['en' => 'Iron Gauntlets'],
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
                'name_translations' => ['en' => 'Mithril Helm'],
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
                'name_translations' => ['en' => 'Mithril Cuirass'],
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
                'name_translations' => ['en' => 'Mithril Greaves'],
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
                'name_translations' => ['en' => 'Mithril Sabatons'],
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
                'name_translations' => ['en' => 'Mithril Gauntlets'],
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
                'name_translations' => ['en' => 'Mithril Pauldrons'],
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
                'name_translations' => ['en' => 'Leather Boots'],
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
                'name_translations' => ['en' => 'Leather Hat'],
            ],
            'recipe_leather_armor' => [
                'name' => 'Armure de cuir',
                'slug' => 'recipe-leather-armor',
                'craft' => 'tanneur',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 4],
                    ['slug' => 'leather-thick', 'quantity' => 1],
                    // ECO-14 : le bain de tannage. Le tanneur ne travaille pas
                    // une peau sans chimie — il depend de l'alchimiste.
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                ],
                'result_ref' => 'leather_armor',
                'crafting_time' => 10,
                'xp_reward' => 25,
                'description' => 'Confectionne une armure de cuir résistante.',
                'name_translations' => ['en' => 'Leather Armor'],
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
                'name_translations' => ['en' => 'Leather Strip'],
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
                'name_translations' => ['en' => 'Leather Gloves'],
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
                'name_translations' => ['en' => 'Leather Belt'],
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
                'name_translations' => ['en' => 'Leather Pauldrons'],
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
                'name_translations' => ['en' => 'Leather Greaves'],
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
                    // ECO-14 : les rivets. Un plastron « renforce » l'est par du
                    // metal — le tanneur depend du forgeron.
                    ['slug' => 'crafted-bronze-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'hardened_vest',
                'crafting_time' => 12,
                'xp_reward' => 35,
                'description' => 'Confectionne un plastron en cuir bouilli renforcé d\'os de monstre.',
                'name_translations' => ['en' => 'Hardened Leather Vest'],
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
                'name_translations' => ['en' => 'Hardened Leather Boots'],
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
                'name_translations' => ['en' => 'Hardened Leather Gloves'],
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
                'name_translations' => ['en' => 'Hardened Leather Belt'],
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
                'name_translations' => ['en' => 'Hardened Leather Pauldrons'],
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
                'name_translations' => ['en' => 'Hardened Leather Greaves'],
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
                'name_translations' => ['en' => 'Exotic Leather Vest'],
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
                'name_translations' => ['en' => 'Minor Healing Potion'],
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
                'name_translations' => ['en' => 'Antidote'],
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
                'name_translations' => ['en' => 'Potion Base'],
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
                'name_translations' => ['en' => 'Healing Salve'],
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
                'name_translations' => ['en' => 'Healing Potion'],
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
                'name_translations' => ['en' => 'Minor Energy Potion'],
            ],
            'recipe_poison_vial' => [
                'name' => 'Fiole de poison',
                'slug' => 'recipe-poison-vial',
                'craft' => 'alchimiste',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'plant-nightshade', 'quantity' => 2],
                    ['slug' => 'poisonous-mushroom', 'quantity' => 1],
                    // ECO-25 — un poison se dilue. La fiole partait de plantes
                    // brutes au niveau 3, sans rien devoir a la paillasse d'en
                    // dessous ; la base de potion (niveau 1) est ce support.
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                ],
                'result_ref' => 'poison_vial',
                'crafting_time' => 7,
                'xp_reward' => 22,
                'description' => 'Concentre des toxines végétales dans une fiole fragile.',
                'name_translations' => ['en' => 'Poison Vial'],
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
                'name_translations' => ['en' => 'Strength Elixir'],
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
                'name_translations' => ['en' => 'Defense Elixir'],
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
                'name_translations' => ['en' => 'Major Healing Potion'],
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
                    // ECO-14 : la gemme catalytique. L'alchimiste ne dependait du
                    // joaillier qu'au niveau 10 — donc, en pratique, jamais.
                    ['slug' => 'crafted-gem-basic', 'quantity' => 1],
                ],
                'result_ref' => 'elixir_vitality',
                'crafting_time' => 12,
                'xp_reward' => 40,
                'description' => 'Un élixir de mandragore et ginseng d\'une puissance exceptionnelle.',
                'name_translations' => ['en' => 'Vitality Elixir'],
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
                'name_translations' => ['en' => 'Copper Ring'],
            ],

            // --- Joaillerie T1 : bases (niveau 1-2) ---
            'recipe_cut_gem_basic' => [
                'name' => 'Taille de gemme brute',
                'slug' => 'recipe-cut-gem-basic',
                'craft' => 'joaillier',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'ore-copper', 'quantity' => 2],
                    ['slug' => 'ore-tin', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_gem_basic',
                'crafting_time' => 5,
                'xp_reward' => 12,
                'description' => 'Taille et polit une gemme brute pour la rendre utilisable en joaillerie.',
                'name_translations' => ['en' => 'Cut Gem'],
            ],
            'recipe_iron_ring' => [
                'name' => 'Anneau de fer',
                'slug' => 'recipe-iron-ring',
                'craft' => 'joaillier',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 3],
                ],
                'result_ref' => 'iron_ring',
                'crafting_time' => 6,
                'xp_reward' => 15,
                'description' => 'Façonne un anneau de fer sobre et résistant.',
                'name_translations' => ['en' => 'Iron Ring'],
            ],
            'recipe_iron_amulet' => [
                'name' => 'Amulette de fer',
                'slug' => 'recipe-iron-amulet',
                'craft' => 'joaillier',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 3],
                    ['slug' => 'crafted-gem-basic', 'quantity' => 1],
                ],
                'result_ref' => 'iron_amulet',
                'crafting_time' => 8,
                'xp_reward' => 20,
                'description' => 'Sertit une gemme taillée dans un médaillon de fer.',
                'name_translations' => ['en' => 'Iron Amulet'],
            ],
            'recipe_iron_bracelet' => [
                'name' => 'Bracelet de fer',
                'slug' => 'recipe-iron-bracelet',
                'craft' => 'joaillier',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 2],
                    ['slug' => 'ore-copper', 'quantity' => 1],
                ],
                'result_ref' => 'iron_bracelet',
                'crafting_time' => 7,
                'xp_reward' => 18,
                'description' => 'Forge un bracelet de fer massif avec rivets de cuivre.',
                'name_translations' => ['en' => 'Iron Bracelet'],
            ],

            // --- Joaillerie T2 : or et gemmes fines (niveau 3-4) ---
            'recipe_cut_gem_fine' => [
                'name' => 'Taille de gemme fine',
                'slug' => 'recipe-cut-gem-fine',
                'craft' => 'joaillier',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'ore-silver', 'quantity' => 2],
                    ['slug' => 'crafted-gem-basic', 'quantity' => 1],
                    // ECO-25 — les trois gemmes brutes du monde (rubis, emeraude,
                    // diamant) n'etaient consommees par **rien** : trois filons
                    // declares produisaient un objet sans usage. Elles entrent
                    // ici, la ou le joaillier voyage deja — le palier d'entree
                    // (`recipe-cut-gem-basic`, niveau 1) reste sur du metal,
                    // parce qu'aucune gemme n'affleure pres du hub.
                    ['slug' => 'ore-ruby', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_gem_fine',
                'crafting_time' => 8,
                'xp_reward' => 25,
                'description' => 'Affine une gemme basique avec des outils d\'argent pour révéler sa clarté.',
                'name_translations' => ['en' => 'Fine Gem'],
            ],
            // ECO-25 — second item mort reveille. Le joaillier fondait son or
            // directement dans la piece finie ; le lingot lui donne son palier
            // intermediaire, et un debouche au lingot de fer du forgeron
            // (aucun metier n'est autosuffisant, D-WoW § 4.6).
            'recipe_gold_ingot' => [
                'name' => 'Lingot d\'or',
                'slug' => 'recipe-gold-ingot',
                'craft' => 'joaillier',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'ore-gold', 'quantity' => 3],
                    ['slug' => 'crafted-iron-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_gold_ingot',
                'crafting_time' => 9,
                'xp_reward' => 26,
                'description' => 'Coule l\'or dans un moule de fer pour en tirer un lingot de bijoutier.',
                'name_translations' => ['en' => 'Gold Ingot'],
            ],
            'recipe_gold_ring' => [
                'name' => 'Anneau d\'or serti',
                'slug' => 'recipe-gold-ring',
                'craft' => 'joaillier',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'ore-gold', 'quantity' => 3],
                    ['slug' => 'crafted-gem-fine', 'quantity' => 1],
                ],
                'result_ref' => 'gold_ring',
                'crafting_time' => 10,
                'xp_reward' => 30,
                'description' => 'Façonne un anneau d\'or et y sertit une gemme fine.',
                'name_translations' => ['en' => 'Jeweled Gold Ring'],
            ],
            'recipe_gold_amulet' => [
                'name' => 'Amulette d\'or',
                'slug' => 'recipe-gold-amulet',
                'craft' => 'joaillier',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'ore-gold', 'quantity' => 3],
                    ['slug' => 'crafted-gem-fine', 'quantity' => 1],
                    // ECO-14 : le cordon. Une amulette se porte au cou — le
                    // joaillier depend du tanneur des le milieu de palier, et
                    // plus seulement du forgeron au niveau 6.
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'gold_amulet',
                'crafting_time' => 10,
                'xp_reward' => 35,
                'description' => 'Cisèle une amulette d\'or ornée d\'une gemme fine éclatante.',
                'name_translations' => ['en' => 'Gold Amulet'],
            ],
            'recipe_gold_crown' => [
                'name' => 'Couronne d\'or',
                'slug' => 'recipe-gold-crown',
                'craft' => 'joaillier',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'crafted-gold-ingot', 'quantity' => 2],
                    ['slug' => 'crafted-gem-fine', 'quantity' => 2],
                ],
                'result_ref' => 'gold_crown',
                'crafting_time' => 15,
                'xp_reward' => 45,
                'description' => 'Forge une couronne d\'or majestueuse ornée de gemmes fines.',
                'name_translations' => ['en' => 'Gold Crown'],
            ],

            // --- Joaillerie T3 : mithril et gemmes rares (niveau 5-6) ---
            'recipe_cut_gem_rare' => [
                'name' => 'Taille de gemme rare',
                'slug' => 'recipe-cut-gem-rare',
                'craft' => 'joaillier',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'ore-mithril', 'quantity' => 1],
                    ['slug' => 'crafted-gem-fine', 'quantity' => 2],
                    ['slug' => 'ore-emerald', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_gem_rare',
                'crafting_time' => 12,
                'xp_reward' => 40,
                'description' => 'Taille une gemme rare aux propriétés magiques avec des outils de mithril.',
                'name_translations' => ['en' => 'Rare Gem'],
            ],
            'recipe_enchant_gem' => [
                'name' => 'Enchantement de gemme',
                'slug' => 'recipe-enchant-gem',
                'craft' => 'joaillier',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'crafted-gem-rare', 'quantity' => 1],
                    ['slug' => 'ore-platinum', 'quantity' => 1],
                    // ECO-14 : le bain d'enchantement. Le joaillier depend de
                    // l'alchimiste — et la boucle se referme, chaque metier
                    // consommant la sortie d'un autre.
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                    ['slug' => 'ore-diamond', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_gem_enchanted',
                'crafting_time' => 15,
                'xp_reward' => 50,
                'description' => 'Imprègne une gemme rare d\'énergie magique via un rituel de platine.',
                'name_translations' => ['en' => 'Enchanted Gem'],
            ],
            'recipe_mithril_ring' => [
                'name' => 'Anneau de mithril serti',
                'slug' => 'recipe-mithril-ring',
                'craft' => 'joaillier',
                'required_level' => 6,
                'ingredients' => [
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 2],
                    ['slug' => 'crafted-gem-rare', 'quantity' => 1],
                ],
                'result_ref' => 'mithril_ring_jewel',
                'crafting_time' => 15,
                'xp_reward' => 60,
                'description' => 'Façonne un anneau de mithril d\'exception et y sertit une gemme rare.',
                'name_translations' => ['en' => 'Jeweled Mithril Ring'],
            ],
            'recipe_mithril_amulet' => [
                'name' => 'Amulette de mithril',
                'slug' => 'recipe-mithril-amulet',
                'craft' => 'joaillier',
                'required_level' => 6,
                'ingredients' => [
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 2],
                    ['slug' => 'crafted-gem-rare', 'quantity' => 1],
                ],
                'result_ref' => 'mithril_amulet',
                'crafting_time' => 15,
                'xp_reward' => 60,
                'description' => 'Cisèle une amulette de mithril vibrant d\'énergie arcanique.',
                'name_translations' => ['en' => 'Mithril Amulet'],
            ],

            // --- Joaillerie T4 : gemmes prismatiques (niveau 7-8) ---
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

            // --- Joaillerie T5 : bijoux légendaires (niveau 9-10) ---
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

            // === Recettes exclusives aux maitres artisans (task 122 sous-phase 2) ===
            // Chacune requiert la specialisation correspondante et un niveau eleve.

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

            'recipe_masterwork_drakehide_cloak' => [
                'name' => 'Manteau du maitre tanneur',
                'slug' => 'recipe-masterwork-drakehide-cloak',
                'craft' => 'tanneur',
                'required_level' => 10,
                'required_specialization' => CraftSpecialization::Tanneur,
                'ingredients' => [
                    ['slug' => 'leather-dragon-scale', 'quantity' => 4],
                    ['slug' => 'leather-werewolf-fur', 'quantity' => 2],
                    ['slug' => 'leather-bone', 'quantity' => 2],
                    // ECO-25 — l'orpheline la plus voyante de l'audit : trois
                    // cuirs bruts pour une piece de niveau 10. Les lanieres sont
                    // ce qui tient un manteau, et elles se taillent au niveau 1 :
                    // le sommet du tanneur doit desormais quelque chose a son
                    // premier geste.
                    ['slug' => 'crafted-leather-strip', 'quantity' => 4],
                ],
                'result_ref' => 'masterwork_drakehide_cloak',
                'crafting_time' => 30,
                'xp_reward' => 200,
                'description' => 'Reservee aux Maitres Tanneurs. Coud un manteau ouvrage dans la peau d\'un drake ancestral.',
                'name_translations' => ['en' => 'Master Leatherworker\'s Cloak'],
            ],

            'recipe_masterwork_grand_elixir' => [
                'name' => 'Grand elixir du maitre alchimiste',
                'slug' => 'recipe-masterwork-grand-elixir',
                'craft' => 'alchimiste',
                'required_level' => 10,
                'required_specialization' => CraftSpecialization::Alchimiste,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 3],
                    ['slug' => 'plant-mandrake', 'quantity' => 3],
                    ['slug' => 'plant-ginseng', 'quantity' => 2],
                    ['slug' => 'crafted-gem-prismatic', 'quantity' => 1],
                ],
                'result_ref' => 'masterwork_grand_elixir',
                'crafting_time' => 25,
                'xp_reward' => 180,
                'description' => 'Reservee aux Maitres Alchimistes. Distille un elixir parfait infuse d\'energie prismatique.',
                'name_translations' => ['en' => 'Master Alchemist\'s Grand Elixir'],
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

            // ── ECO-19 : recettes citees par les arbres de talent ──────────────
            // Ces slugs etaient debloques par des skills sans qu'aucune recette
            // ne porte le nom. Le skill s'apprenait, le joueur depensait ses
            // points, et rien n'apparaissait dans son etabli.
            //
            // Calibrage : quantite croissante avec le palier, et une dependance
            // croisee des que le theme la rend evidente (ECO-14). La seule
            // recette de niveau 1 n'utilise que du minerai brut — le palier
            // d'entree doit rester realisable en solo (ECO-02).

            // --- Forge : chaine du fer a l'acier ---
            'recipe_iron_chainmail' => [
                'name' => 'Cotte de mailles en fer',
                'slug' => 'recipe-iron-chainmail',
                'craft' => 'forgeron',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 4],
                ],
                'result_ref' => 'iron_chainmail',
                'crafting_time' => 10,
                'xp_reward' => 20,
                'description' => 'Rivete un a un des milliers d\'anneaux de fer.',
                'name_translations' => ['en' => 'Iron Chainmail'],
            ],
            'recipe_iron_sword' => [
                'name' => 'Epee en fer',
                'slug' => 'recipe-iron-sword',
                'craft' => 'forgeron',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 4],
                    // La poignee : une lame sans prise ne se tient pas.
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'iron_sword',
                'crafting_time' => 12,
                'xp_reward' => 25,
                'description' => 'Forge une epee en fer bien equilibree.',
                'name_translations' => ['en' => 'Iron Sword'],
            ],
            'recipe_whetstone' => [
                'name' => 'Pierre a aiguiser',
                'slug' => 'recipe-whetstone',
                'craft' => 'forgeron',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 1],
                    // L'huile d'affutage vient de l'alchimiste.
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                ],
                'result_ref' => 'whetstone',
                'crafting_time' => 6,
                'xp_reward' => 18,
                'description' => 'Taille un grain fin et l\'imbibe d\'huile.',
                'name_translations' => ['en' => 'Whetstone'],
            ],
            'recipe_steel_dagger' => [
                'name' => 'Dague en acier',
                'slug' => 'recipe-steel-dagger',
                'craft' => 'forgeron',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 3],
                    ['slug' => 'ore-cobalt', 'quantity' => 1],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'steel_dagger',
                'crafting_time' => 14,
                'xp_reward' => 40,
                'description' => 'Trempe une lame courte a haute teneur en carbone.',
                'name_translations' => ['en' => 'Steel Dagger'],
            ],
            'recipe_steel_sword' => [
                'name' => 'Epee en acier',
                'slug' => 'recipe-steel-sword',
                'craft' => 'forgeron',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 5],
                    ['slug' => 'ore-cobalt', 'quantity' => 2],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'steel_sword',
                'crafting_time' => 18,
                'xp_reward' => 50,
                'description' => 'L\'arme de reference d\'un forgeron confirme.',
                'name_translations' => ['en' => 'Steel Sword'],
            ],
            'recipe_steel_chainmail' => [
                'name' => 'Cotte de mailles en acier',
                'slug' => 'recipe-steel-chainmail',
                'craft' => 'forgeron',
                'required_level' => 4,
                // ECO-25 — l'acier est un **alliage**, pas un tas de minerai.
                // La recette partait du brut alors qu'elle est de niveau 4 :
                // elle ne devait donc rien a la forge d'en dessous.
                'ingredients' => [
                    ['slug' => 'crafted-iron-ingot', 'quantity' => 2],
                    ['slug' => 'crafted-cobalt-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'steel_chainmail',
                'crafting_time' => 18,
                'xp_reward' => 50,
                'description' => 'Les memes anneaux que le fer, mais trempes.',
                'name_translations' => ['en' => 'Steel Chainmail'],
            ],
            'recipe_steel_plate' => [
                'name' => 'Plastron d\'acier',
                'slug' => 'recipe-steel-plate',
                'craft' => 'forgeron',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 6],
                    ['slug' => 'ore-cobalt', 'quantity' => 3],
                    ['slug' => 'crafted-bronze-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'steel_plate',
                'crafting_time' => 22,
                'xp_reward' => 60,
                'description' => 'Martele une plaque d\'une seule piece.',
                'name_translations' => ['en' => 'Steel Plate'],
            ],
            'recipe_steel_axe' => [
                'name' => 'Hache d\'acier',
                'slug' => 'recipe-steel-axe',
                'craft' => 'forgeron',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 4],
                    ['slug' => 'ore-cobalt', 'quantity' => 2],
                    // Le manche est ligature de cuir sur toute sa longueur.
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                ],
                'result_ref' => 'steel_axe',
                'crafting_time' => 20,
                'xp_reward' => 65,
                'description' => 'Lourde, mal equilibree, devastatrice.',
                'name_translations' => ['en' => 'Steel Axe'],
            ],
            'recipe_heavy_steel_plate' => [
                'name' => 'Harnois d\'acier lourd',
                'slug' => 'recipe-heavy-steel-plate',
                'craft' => 'forgeron',
                'required_level' => 7,
                'ingredients' => [
                    ['slug' => 'ore-iron', 'quantity' => 8],
                    ['slug' => 'ore-cobalt', 'quantity' => 4],
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 1],
                    // Gemme de visee sertie dans la visiere.
                    ['slug' => 'crafted-gem-fine', 'quantity' => 1],
                ],
                'result_ref' => 'heavy_steel_plate',
                'crafting_time' => 35,
                'xp_reward' => 120,
                'description' => 'Une armure complete, taillee pour tenir la ligne.',
                'name_translations' => ['en' => 'Heavy Steel Plate'],
            ],

            // --- Tannerie : carquois, cuir de dragon, cuir enchante ---
            'recipe_leather_quiver' => [
                'name' => 'Carquois de cuir',
                'slug' => 'recipe-leather-quiver',
                'craft' => 'tanneur',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'leather-raw', 'quantity' => 3],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'leather_quiver',
                'crafting_time' => 10,
                'xp_reward' => 30,
                'description' => 'Coud un carquois souple qui garde les empennages secs.',
                'name_translations' => ['en' => 'Leather Quiver'],
            ],
            'recipe_hardened_quiver' => [
                'name' => 'Carquois renforce',
                'slug' => 'recipe-hardened-quiver',
                'craft' => 'tanneur',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'leather-thick', 'quantity' => 3],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                    // Cercle de bronze : la piece qui l'empeche de plier.
                    ['slug' => 'crafted-bronze-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'hardened_quiver',
                'crafting_time' => 16,
                'xp_reward' => 70,
                'description' => 'Cuir bouilli cercle de bronze.',
                'name_translations' => ['en' => 'Hardened Quiver'],
            ],
            'recipe_dragon_vest' => [
                'name' => 'Plastron d\'ecailles de dragon',
                'slug' => 'recipe-dragon-vest',
                'craft' => 'tanneur',
                'required_level' => 7,
                'ingredients' => [
                    ['slug' => 'leather-dragon-scale', 'quantity' => 4],
                    ['slug' => 'leather-thick', 'quantity' => 2],
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 1],
                ],
                'result_ref' => 'dragon_vest',
                'crafting_time' => 40,
                'xp_reward' => 150,
                'description' => 'Assemble les ecailles en tuiles se recouvrant.',
                'name_translations' => ['en' => 'Dragonscale Vest'],
            ],
            'recipe_dragon_boots' => [
                'name' => 'Bottes d\'ecailles de dragon',
                'slug' => 'recipe-dragon-boots',
                'craft' => 'tanneur',
                'required_level' => 7,
                'ingredients' => [
                    ['slug' => 'leather-dragon-scale', 'quantity' => 2],
                    ['slug' => 'leather-thick', 'quantity' => 2],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                ],
                'result_ref' => 'dragon_boots',
                'crafting_time' => 30,
                'xp_reward' => 120,
                'description' => 'Coud des semelles d\'ecailles sur des lanieres.',
                'name_translations' => ['en' => 'Dragonscale Boots'],
            ],
            'recipe_enchanted_vest' => [
                'name' => 'Plastron de cuir enchante',
                'slug' => 'recipe-enchanted-vest',
                'craft' => 'tanneur',
                'required_level' => 8,
                'ingredients' => [
                    ['slug' => 'leather-werewolf-fur', 'quantity' => 3],
                    // La gemme enchassee et le bain runique : deux metiers.
                    ['slug' => 'crafted-gem-enchanted', 'quantity' => 1],
                    ['slug' => 'crafted-potion-base', 'quantity' => 2],
                ],
                'result_ref' => 'enchanted_vest',
                'crafting_time' => 45,
                'xp_reward' => 200,
                'description' => 'Tanne la fourrure dans un bain runique.',
                'name_translations' => ['en' => 'Enchanted Vest'],
            ],

            // --- Alchimie : paliers manquants ---
            'recipe_energy_potion_standard' => [
                'name' => 'Potion d\'energie standard',
                'slug' => 'recipe-energy-potion-standard',
                'craft' => 'alchimiste',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                    ['slug' => 'plant-ginseng', 'quantity' => 2],
                    ['slug' => 'plant-lavender', 'quantity' => 1],
                ],
                'result_ref' => 'energy_potion_standard',
                'crafting_time' => 12,
                'xp_reward' => 35,
                'description' => 'Le palier au-dessus de la fiole mineure.',
                'name_translations' => ['en' => 'Standard Energy Potion'],
            ],
            'recipe_speed_elixir' => [
                'name' => 'Elixir de vitesse',
                'slug' => 'recipe-speed-elixir',
                'craft' => 'alchimiste',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 2],
                    ['slug' => 'plant-mint', 'quantity' => 3],
                    // La gemme sert de catalyseur, comme pour l'elixir de vitalite.
                    ['slug' => 'crafted-gem-basic', 'quantity' => 1],
                ],
                'result_ref' => 'speed_elixir',
                'crafting_time' => 20,
                'xp_reward' => 80,
                'description' => 'Accelere le porteur — et la note se paie apres.',
                'name_translations' => ['en' => 'Speed Elixir'],
            ],
            'recipe_transmute_rare' => [
                'name' => 'Transmutation en mithril',
                'slug' => 'recipe-transmute-rare',
                'craft' => 'alchimiste',
                'required_level' => 7,
                'ingredients' => [
                    ['slug' => 'ore-silver', 'quantity' => 3],
                    ['slug' => 'ore-gold', 'quantity' => 2],
                    ['slug' => 'crafted-gem-rare', 'quantity' => 1],
                ],
                // ECO-24b : ce n'est plus la **seule** source de mithril. La
                // carte des minerais (GAME_ZONES §3) en pose un filon T4 au
                // sommet de la Crete de Ventombre. La transmutation reste la
                // seconde voie, et c'est ce qui garde a l'alchimie le role
                // economique de haut palier que lui donnait ECO-19.
                'result_ref' => 'ore_mithril',
                'result_quantity' => 2,
                'crafting_time' => 40,
                'xp_reward' => 160,
                'description' => 'Transmute les metaux nobles en mithril brut.',
                'name_translations' => ['en' => 'Mithril Transmutation'],
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
