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
        $recipes = $this->getRecipesData() + self::toolRecipesData();

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

    /**
     * OBJ-06 — les recettes d'outils du forgeron : acier et mithril, pour les
     * 12 types (GAME_ITEMS §4.2, point 3).
     *
     * Sur 4 paliers declares, seuls bronze et fer etaient atteignables :
     * l'outillage s'arretait au fer. L'acier et le mithril deviennent des
     * **crafts de forgeron** — un debouche recurrent, puisque l'outil s'use
     * (`durability`, `wearCraftTool`).
     *
     * Une recette d'outil ne s'ecrit pas, elle se derive : meme grille pour
     * les 12 types, seuls le nom et le resultat changent. L'acier est un
     * alliage (ECO-25 : lingot de fer + lingot de cobalt — il n'existe aucun
     * « lingot d'acier »), le mithril part de son lingot, et chaque palier
     * consomme un manche de charpentier — l'interdependance des metiers
     * (ECO-14) vaut aussi pour l'outillage.
     *
     * Publique et statique : le contrat (`CraftToolContractTest`) verifie la
     * derivation elle-meme, pas une copie.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function toolRecipesData(): array
    {
        $bases = [
            'pickaxe' => ['Pioche', 'Pickaxe'],
            'sickle' => ['Faucille', 'Sickle'],
            'fishing_rod' => ['Canne à pêche', 'Fishing Rod'],
            'skinning_knife' => ['Couteau de dépeçage', 'Skinning Knife'],
            'hammer' => ['Marteau de forge', 'Smithing Hammer'],
            'tanning_kit' => ['Kit de tannage', 'Tanning Kit'],
            'mortar' => ['Mortier d\'alchimie', 'Alchemy Mortar'],
            'chisel' => ['Burin de joaillier', 'Jeweler\'s Chisel'],
            'axe' => ['Hache', 'Axe'],
            'cookpot' => ['Marmite de cuisine', 'Cooking Pot'],
            'plane' => ['Varlope de charpentier', 'Carpenter\'s Plane'],
            'needle' => ['Aiguille de tailleur', 'Tailor\'s Needle'],
        ];

        $tiers = [
            'steel' => [
                'fr' => 'en acier',
                'en' => 'Steel',
                'required_level' => 4,
                'crafting_time' => 18,
                'xp_reward' => 50,
                'ingredients' => [
                    ['slug' => 'crafted-iron-ingot', 'quantity' => 1],
                    ['slug' => 'crafted-cobalt-ingot', 'quantity' => 1],
                    ['slug' => 'crafted-wood-haft', 'quantity' => 1],
                ],
                'description' => 'Allie le fer au cobalt sur un manche de hêtre : l\'outil tient le fil des matières dures.',
            ],
            'mithril' => [
                'fr' => 'en mithril',
                'en' => 'Mithril',
                'required_level' => 5,
                'crafting_time' => 24,
                'xp_reward' => 70,
                'ingredients' => [
                    ['slug' => 'crafted-mithril-ingot', 'quantity' => 1],
                    ['slug' => 'crafted-iron-ingot', 'quantity' => 1],
                    ['slug' => 'crafted-wood-haft', 'quantity' => 1],
                ],
                'description' => 'Monte le mithril sur une âme de fer : l\'outil le plus léger et le plus endurant qui soit.',
            ],
        ];

        $recipes = [];
        foreach ($bases as $type => [$fr, $en]) {
            $slugType = str_replace('_', '-', $type);
            foreach ($tiers as $tierSlug => $spec) {
                $recipes[sprintf('recipe_%s_%s', $type, $tierSlug)] = [
                    'name' => sprintf('%s %s', $fr, $spec['fr']),
                    'slug' => sprintf('recipe-%s-%s', $slugType, $tierSlug),
                    'craft' => 'forgeron',
                    'required_level' => $spec['required_level'],
                    'ingredients' => $spec['ingredients'],
                    'result_ref' => sprintf('%s_%s', $type, $tierSlug),
                    'crafting_time' => $spec['crafting_time'],
                    'xp_reward' => $spec['xp_reward'],
                    'description' => $spec['description'],
                    'name_translations' => ['en' => sprintf('%s %s', $spec['en'], $en)],
                ];
            }
        }

        return $recipes;
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
                    // ECO-31 : le cuir se coud **au fil de lin**. C'est le second
                    // debouche du lin des Vallons, a cote du tissu du tailleur —
                    // une exclusivite de zone dont un seul metier dependrait
                    // s'eteindrait avec lui.
                    ['slug' => 'plant-flax', 'quantity' => 1],
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
            // OBJ-07 : le debouche du champignon cote cuisine — le butin le
            // plus frequent du jeu cesse d'etre un poids mort d'inventaire.
            'recipe_mushroom_fricassee' => [
                'name' => 'Fricassée de champignons',
                'slug' => 'recipe-mushroom-fricassee',
                'craft' => 'cuisinier',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'mushroom', 'quantity' => 3],
                    ['slug' => 'plant-thyme', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_mushroom_fricassee',
                'crafting_time' => 4,
                'xp_reward' => 10,
                'description' => 'Saute au thym les champignons ramassés en chemin.',
                'name_translations' => ['en' => 'Mushroom Fricassee'],
            ],
            'recipe_potion_base' => [
                'name' => 'Base de potion',
                'slug' => 'recipe-potion-base',
                'craft' => 'alchimiste',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'plant-mint', 'quantity' => 2],
                    ['slug' => 'plant-chamomile', 'quantity' => 1],
                    // OBJ-07 : le champignon devient une matiere d'entree
                    // d'alchimie — le butin le plus frequent du jeu trouve un
                    // second debouche, sans toucher au prix de la base (33 + 5
                    // reste sous les 45 de la loi de valeur).
                    ['slug' => 'mushroom', 'quantity' => 1],
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
                    // ZON-35 : les spores fantomes du Marais se recoltaient sans
                    // que rien ne les consomme. Elles entrent la ou leur biome les
                    // destinait — la fiole de poison.
                    ['slug' => 'plant-ghostshroom', 'quantity' => 1],
                    // ZON-33 : la racine de marais. Une quete d'Acte II en
                    // demandait trois et aucune recette n'en voulait — l'inverse
                    // exact du defaut de ZON-35, et le meme silence. La fiole est
                    // la recette du Marais ; une racine gorgee d'eau stagnante y
                    // a sa place plus qu'ailleurs.
                    ['slug' => 'swamp-root', 'quantity' => 1],
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
                    // ECO-14, l'autre sens : la sortie du cuisinier doit avoir
                    // une demande. Un elixir de force se batit sur un fond
                    // nourrissant — le ragout du cuisinier en est un, et c'est
                    // le seul metier qui en fasse.
                    ['slug' => 'crafted-carp-stew', 'quantity' => 1],
                    // ZON-35 : la feuille de drake, jusqu'ici recoltee pour rien.
                    ['slug' => 'plant-dragonleaf', 'quantity' => 1],
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
                    // ZON-35 : la givrecoiffe de la Crete. Un bouclier de glace
                    // est ce que sa signature de zone promettait.
                    ['slug' => 'plant-frostcap', 'quantity' => 1],
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
                    // ZON-35 : la fleur de phenix. Une potion de soin majeure est
                    // le seul endroit du catalogue ou elle avait sa place.
                    ['slug' => 'plant-phoenixflower', 'quantity' => 1],
                ],
                'result_ref' => 'healing_potion_major',
                'crafting_time' => 10,
                'xp_reward' => 35,
                'description' => 'Prépare une puissante potion de soin à base de mandragore.',
                'name_translations' => ['en' => 'Major Healing Potion'],
            ],
            // ARC-20c-b : le barreau 4 de l'echelle de potions. L'alchimiste
            // a un produit a chaque palier au lieu d'un seul qui se perime.
            'recipe_healing_supreme' => [
                'name' => 'Potion de soin suprême',
                'slug' => 'recipe-healing-supreme',
                'craft' => 'alchimiste',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'crafted-potion-base', 'quantity' => 2],
                    ['slug' => 'plant-mandrake', 'quantity' => 1],
                    ['slug' => 'plant-phoenixflower', 'quantity' => 2],
                    ['slug' => 'crafted-gem-basic', 'quantity' => 1],
                ],
                'result_ref' => 'healing_potion_supreme',
                'crafting_time' => 14,
                'xp_reward' => 50,
                'description' => 'Distille une potion de soin du dernier palier, à la fleur de phénix.',
                'name_translations' => ['en' => 'Supreme Healing Potion'],
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
                    // ZON-35 : la fleur de lune, cueillie de nuit et jusqu'ici
                    // sans emploi.
                    ['slug' => 'plant-moonflower', 'quantity' => 1],
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
            // ZON-40 — le debouche de l'amethystite, et la fin d'un objet sans
            // recette. `amethyst_ring` existait dans les fixtures d'equipement
            // (400 gils, epique, element dark) sans qu'aucune recette ne le
            // produise ; l'amethystite, symetriquement, n'etait consommee par
            // rien. Les deux moities se manquaient.
            //
            // La composition suit la description de l'anneau au mot pres — « une
            // amethyste sombre sertie dans de l'obsidienne » : le sombracier
            // pour la monture, l'amethystite pour la pierre, une gemme fine pour
            // la taille (loi du palier non orphelin : une recette de niveau >= 3
            // consomme au moins un produit d'artisanat).
            //
            // Prix derive par la regle ECO-27 — cout des intrants + 10 x niveau :
            // 6 x 15 + 120 + 120 = 330, + 70 = 400, soit exactement le prix que
            // l'anneau portait deja. Rien a recalibrer.
            'recipe_amethyst_ring' => [
                'name' => 'Anneau d\'améthyste',
                'slug' => 'recipe-amethyst-ring',
                'craft' => 'joaillier',
                'required_level' => 7,
                'ingredients' => [
                    ['slug' => 'ore-amethyst-crystal', 'quantity' => 6],
                    ['slug' => 'ore-darksteel', 'quantity' => 1],
                    ['slug' => 'crafted-gem-fine', 'quantity' => 1],
                ],
                'result_ref' => 'amethyst_ring',
                'crafting_time' => 14,
                'xp_reward' => 45,
                'description' => 'Sertit une améthystite sombre dans une monture de sombracier.',
                'name_translations' => ['en' => 'Amethyst Ring'],
            ],
            // FAC-07 — la main du faussaire (GAME_WORLD § 12.4) : améthyste
            // Trouble + éclats d'une matéria brisée → une contrefaçon. Le
            // gate n'est PAS un arbre : le palier Révéré des Ruelles, tenu par
            // CraftingManager::isRecipeUnlocked via CounterfeitService. Le
            // résultat sort marqué contrefait (identifié — le faussaire
            // connaît son œuvre) ; son seul débouché est un contact PNJ.
            'recipe_forgers_hand' => [
                'name' => 'La main du faussaire',
                'slug' => 'recipe-forgers-hand',
                'craft' => 'joaillier',
                // Niveau 2 : le vrai gardien est le palier Revere des Ruelles,
                // pas le metier — et une recette >= 3 devrait consommer un
                // produit d'artisanat (ProductionChainTest), ce que la
                // contrefacon ne fait pas : elle se fabrique avec des restes.
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'ore-amethyst-crystal', 'quantity' => 3],
                    ['slug' => 'materia-shards', 'quantity' => 2],
                ],
                'result_ref' => 'materia_fire_ball',
                'crafting_time' => 20,
                'xp_reward' => 30,
                'description' => 'Un cristal trouble, des éclats, et un geste appris dans les Ruelles. Ce qui en sort chante juste — neuf fois.',
                'name_translations' => ['en' => 'The Forger\'s Hand'],
            ],
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
            // ZON-31 — le debouche de l'ambre fossile. Une exclusivite sans
            // debouche est un mensonge de level design : le filon existerait,
            // et rien n'en ferait rien.
            //
            // Le sceau consomme une gemme **taillee** et non brute : la loi
            // « aucune ligne plate verticalement » (ECO-27) veut qu'une recette
            // de niveau >= 3 morde sur un produit d'artisanat, sinon la matiere
            // de debut devient inutile des que les veterans montent.
            // =================================================================
            // ECO-29 — la table du cuisinier
            // =================================================================
            // Sept plats pour sept poissons. Avant ce jalon, **aucun poisson du
            // monde n'etait consomme par quoi que ce soit** : six filons de
            // peche, un arbre entier de competences, et rien au bout. C'etait
            // le trou le plus large de l'audit d'economie.
            //
            // Le pain sert de **liant vertical** : les plats de palier 3 et
            // au-dela le consomment, ce qui tient la loi d'ECO-27 (aucune ligne
            // plate) et donne au ble des Vallons une demande qui ne s'arrete
            // pas au premier jour.
            'recipe_bread' => [
                'name' => 'Pain de campagne',
                'slug' => 'recipe-bread',
                'craft' => 'cuisinier',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'plant-wheat', 'quantity' => 3],
                ],
                'result_ref' => 'crafted_bread',
                'crafting_time' => 3,
                'xp_reward' => 8,
                'description' => 'Cuit une miche au four : le blé des Vallons trouve enfin sa fin.',
                'name_translations' => ['en' => 'Country Bread'],
            ],
            // ZON-35 — le melange d'epices, second intermediaire vertical du
            // cuisinier a cote du pain. Il absorbe les **quatre** herbes banales
            // que rien ne consommait : pissenlit, ortie, romarin, echinacee.
            'recipe_spice_blend' => [
                'name' => 'Mélange d\'épices',
                'slug' => 'recipe-spice-blend',
                'craft' => 'cuisinier',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'plant-dandelion', 'quantity' => 2],
                    ['slug' => 'plant-nettle', 'quantity' => 2],
                    ['slug' => 'plant-rosemary', 'quantity' => 1],
                    ['slug' => 'plant-echinacea', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_spice_blend',
                'result_quantity' => 2,
                'crafting_time' => 5,
                'xp_reward' => 16,
                'description' => 'Pile ensemble ce qui pousse au bord des chemins : le goût vient de là.',
                'name_translations' => ['en' => 'Spice Blend'],
            ],
            'recipe_fish_skewer' => [
                'name' => 'Brochette du gué',
                'slug' => 'recipe-fish-skewer',
                'craft' => 'cuisinier',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'fish-perch', 'quantity' => 1],
                    ['slug' => 'fish-trout', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_fish_skewer',
                'crafting_time' => 3,
                'xp_reward' => 8,
                'description' => 'Enfile une perche et une truite sur la même branche.',
                'name_translations' => ['en' => 'Ford Skewer'],
            ],
            'recipe_carp_stew' => [
                'name' => 'Ragoût de carpe',
                'slug' => 'recipe-carp-stew',
                'craft' => 'cuisinier',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'fish-carp', 'quantity' => 2],
                    ['slug' => 'meat-game', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_carp_stew',
                'crafting_time' => 5,
                'xp_reward' => 14,
                'description' => 'Mijote la carpe des étangs avec le gibier de plaine.',
                'name_translations' => ['en' => 'Carp Stew'],
            ],
            'recipe_salmon_roast' => [
                'name' => 'Saumon rôti',
                'slug' => 'recipe-salmon-roast',
                'craft' => 'cuisinier',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'fish-salmon', 'quantity' => 2],
                    ['slug' => 'crafted-bread', 'quantity' => 1],
                    // ECO-14 : le fond de cuisson vient de l'alchimiste. Un
                    // metier qui ne consomme la sortie d'aucun autre produit un
                    // joueur autosuffisant — il n'a rien a acheter, donc rien a
                    // vendre non plus. Au palier 3, jamais a l'entree : croiser
                    // les metiers des le niveau 1 casserait le plancher T1.
                    ['slug' => 'crafted-potion-base', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_salmon_roast',
                'crafting_time' => 7,
                'xp_reward' => 22,
                'description' => 'Rôtit un saumon des rapides sur son lit de pain.',
                'name_translations' => ['en' => 'Roast Salmon'],
            ],
            'recipe_moonfish_plate' => [
                'name' => 'Poisson-lune en écailles',
                'slug' => 'recipe-moonfish-plate',
                'craft' => 'cuisinier',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'fish-moonfish', 'quantity' => 1],
                    ['slug' => 'crafted-bread', 'quantity' => 1],
                    // ZON-35 : les epices que le plat attendait depuis ECO-29.
                    ['slug' => 'crafted-spice-blend', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_moonfish_plate',
                'crafting_time' => 9,
                'xp_reward' => 30,
                'description' => 'Lève une à une les écailles irisées du poisson-lune.',
                'name_translations' => ['en' => 'Moonfish Platter'],
            ],
            'recipe_eel_dish' => [
                'name' => 'Anguille au poivre',
                'slug' => 'recipe-eel-dish',
                'craft' => 'cuisinier',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'fish-electric-eel', 'quantity' => 1],
                    ['slug' => 'crafted-bread', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_eel_dish',
                'crafting_time' => 11,
                'xp_reward' => 40,
                'description' => 'Apprête l\'anguille sans se faire mordre. Elle crépite encore.',
                'name_translations' => ['en' => 'Peppered Eel'],
            ],
            'recipe_kraken_feast' => [
                'name' => 'Festin de kraken',
                'slug' => 'recipe-kraken-feast',
                'craft' => 'cuisinier',
                'required_level' => 6,
                'ingredients' => [
                    ['slug' => 'fish-baby-kraken', 'quantity' => 1],
                    ['slug' => 'crafted-bread', 'quantity' => 2],
                    // ZON-35 : on n'assaisonne pas une tablee entiere avec rien.
                    ['slug' => 'crafted-spice-blend', 'quantity' => 2],
                ],
                'result_ref' => 'crafted_kraken_feast',
                'crafting_time' => 15,
                'xp_reward' => 60,
                'description' => 'Dresse un kraken juvénile pour toute une tablée.',
                'name_translations' => ['en' => 'Kraken Feast'],
            ],
            // =================================================================
            // ECO-30 — l'etabli du charpentier
            // =================================================================
            // ZON-34 a livre quatre essences **sans un seul debouche** : on
            // pouvait abattre le chene murmurant et n'avoir rien a en faire.
            // Chaque essence trouve ici sa fin — le hetre dans la planche, le
            // chene dans les armes de palier 2, la tourbe dans le necessaire et
            // l'arc de palier 3, le petrifie dans le baton de l'archimage.
            //
            // La planche joue pour le bois le role que la laniere joue pour le
            // cuir : un intermediaire bon marche que tout le reste traverse.
            // C'est ce qui tient la loi d'ECO-27 — aucune ligne plate — et ce
            // qui donne au hetre une demande proportionnelle a l'activite de
            // haut palier.
            'recipe_plank' => [
                'name' => 'Planche de hêtre',
                'slug' => 'recipe-plank',
                'craft' => 'charpentier',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'wood-beech', 'quantity' => 2],
                ],
                'result_ref' => 'crafted_plank',
                'result_quantity' => 2,
                'crafting_time' => 3,
                'xp_reward' => 8,
                'description' => 'Débite le hêtre droit de fil : le bois devient matière.',
                'name_translations' => ['en' => 'Beech Plank'],
            ],
            'recipe_wood_haft' => [
                'name' => 'Manche de bois',
                'slug' => 'recipe-wood-haft',
                'craft' => 'charpentier',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'crafted-plank', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_wood_haft',
                'result_quantity' => 2,
                'crafting_time' => 4,
                'xp_reward' => 12,
                'description' => 'Tourne et ponce un manche prêt à recevoir un fer.',
                'name_translations' => ['en' => 'Wooden Haft'],
            ],
            'recipe_t1_bow' => [
                'name' => 'Arc court',
                'slug' => 'recipe-t1-bow',
                'craft' => 'charpentier',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'crafted-plank', 'quantity' => 1],
                    ['slug' => 'wood-beech', 'quantity' => 1],
                ],
                'result_ref' => 't1_bow',
                'crafting_time' => 6,
                'xp_reward' => 18,
                'description' => 'Cintre une branche de hêtre en arc court.',
                'name_translations' => ['en' => 'Short Bow'],
            ],
            'recipe_t1_staff' => [
                'name' => 'Bâton de novice',
                'slug' => 'recipe-t1-staff',
                'craft' => 'charpentier',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'crafted-plank', 'quantity' => 1],
                ],
                'result_ref' => 't1_staff',
                'crafting_time' => 6,
                'xp_reward' => 18,
                'description' => 'Taille un bâton noueux qui canalise ce qu\'il peut.',
                'name_translations' => ['en' => 'Novice Staff'],
            ],
            'recipe_arrows' => [
                'name' => 'Flèches empennées',
                'slug' => 'recipe-arrows',
                'craft' => 'charpentier',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'crafted-plank', 'quantity' => 1],
                    // ZON-30 a pose les plumes de corbeau sans consommateur.
                    ['slug' => 'feather-raw', 'quantity' => 3],
                    // ECO-14 : la ligature vient du tanneur. Au palier 3, jamais
                    // a l'entree — croiser les metiers des le niveau 1 casserait
                    // le plancher T1 (ECO-02).
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_arrows',
                'result_quantity' => 10,
                'crafting_time' => 5,
                'xp_reward' => 20,
                'description' => 'Empenne dix flèches. Elles partent vite et ne reviennent pas.',
                'name_translations' => ['en' => 'Fletched Arrows'],
            ],
            'recipe_t2_bow' => [
                'name' => 'Arc long composite',
                'slug' => 'recipe-t2-bow',
                'craft' => 'charpentier',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'wood-whisperoak', 'quantity' => 3],
                    ['slug' => 'crafted-plank', 'quantity' => 1],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                ],
                'result_ref' => 't2_bow',
                'crafting_time' => 14,
                'xp_reward' => 45,
                'description' => 'Colle le chêne murmurant et la corne en un arc qui porte loin.',
                'name_translations' => ['en' => 'Composite Longbow'],
            ],
            'recipe_t2_staff' => [
                'name' => 'Bâton de cristal',
                'slug' => 'recipe-t2-staff',
                'craft' => 'charpentier',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'wood-whisperoak', 'quantity' => 3],
                    ['slug' => 'crafted-plank', 'quantity' => 1],
                    // La gemme au sommet vient du joaillier : le charpentier
                    // monte le bois, il ne taille pas la pierre.
                    ['slug' => 'crafted-gem-basic', 'quantity' => 1],
                ],
                'result_ref' => 't2_staff',
                'crafting_time' => 14,
                'xp_reward' => 45,
                'description' => 'Sertit un cristal au sommet d\'un chêne qui vibre encore.',
                'name_translations' => ['en' => 'Crystal Staff'],
            ],
            'recipe_furnishing_kit' => [
                'name' => 'Nécessaire d\'ameublement',
                'slug' => 'recipe-furnishing-kit',
                'craft' => 'charpentier',
                'required_level' => 5,
                'ingredients' => [
                    ['slug' => 'crafted-plank', 'quantity' => 6],
                    ['slug' => 'wood-peat', 'quantity' => 2],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                ],
                'result_ref' => 'crafted_furnishing_kit',
                'crafting_time' => 20,
                'xp_reward' => 70,
                'description' => 'Monte de quoi meubler une demeure entière, ferrures comprises.',
                'name_translations' => ['en' => 'Furnishing Kit'],
            ],
            'recipe_t3_bow' => [
                'name' => 'Arc du vent hurlant',
                'slug' => 'recipe-t3-bow',
                'craft' => 'charpentier',
                'required_level' => 6,
                'ingredients' => [
                    ['slug' => 'wood-peat', 'quantity' => 3],
                    ['slug' => 'crafted-plank', 'quantity' => 2],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                ],
                'result_ref' => 't3_bow',
                'crafting_time' => 25,
                'xp_reward' => 110,
                'description' => 'Un bois que l\'eau morte a durci, tendu jusqu\'à siffler.',
                'name_translations' => ['en' => 'Howling Wind Bow'],
            ],
            'recipe_t3_staff' => [
                'name' => 'Bâton de l\'archimage',
                'slug' => 'recipe-t3-staff',
                'craft' => 'charpentier',
                'required_level' => 7,
                'ingredients' => [
                    ['slug' => 'wood-petrified', 'quantity' => 2],
                    ['slug' => 'crafted-plank', 'quantity' => 1],
                    ['slug' => 'crafted-gem-basic', 'quantity' => 1],
                ],
                'result_ref' => 't3_staff',
                'crafting_time' => 30,
                'xp_reward' => 150,
                'description' => 'Grave un tronc de l\'âge précédent. Le bois se souvient mieux que nous.',
                'name_translations' => ['en' => 'Archmage Staff'],
            ],
            // =================================================================
            // ECO-31 — l'ouvroir du tailleur
            // =================================================================
            // Le trou le plus beant de l'audit d'equipement : sur 121 pieces,
            // **pas une robe**. Les domaines de sort s'habillaient en cuir et en
            // metal, et aucun metier ne les habillait. Onze recettes ouvrent la
            // categorie tissu et reveillent `crafted-cloth`, un objet livre de
            // longue date que **rien ne produisait ni ne consommait**.
            //
            // Le tissu est au tailleur ce que la planche est au charpentier et
            // la laniere au tanneur : l'intermediaire par lequel tout passe.
            // Toutes les recettes de la ligne le consomment, ce qui donne au lin
            // des Vallons une demande qui monte avec les paliers plutot que de
            // s'eteindre au premier (ECO-27).
            'recipe_cloth' => [
                'name' => 'Tissu',
                'slug' => 'recipe-cloth',
                'craft' => 'tailleur',
                'required_level' => 1,
                'ingredients' => [
                    ['slug' => 'plant-flax', 'quantity' => 3],
                ],
                'result_ref' => 'crafted_cloth',
                'result_quantity' => 2,
                'crafting_time' => 3,
                'xp_reward' => 8,
                'description' => 'Rouit, file et tisse le lin du gué : la toile naît enfin quelque part.',
                'name_translations' => ['en' => 'Cloth'],
            ],
            'recipe_linen_hood' => [
                'name' => 'Capuche de lin',
                'slug' => 'recipe-linen-hood',
                'craft' => 'tailleur',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 2],
                ],
                'result_ref' => 'linen_hood',
                'crafting_time' => 5,
                'xp_reward' => 15,
                'description' => 'Coupe et coud une capuche ample dans la toile écrue.',
                'name_translations' => ['en' => 'Linen Hood'],
            ],
            'recipe_linen_gloves' => [
                'name' => 'Mitaines de lin',
                'slug' => 'recipe-linen-gloves',
                'craft' => 'tailleur',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 2],
                ],
                'result_ref' => 'linen_gloves',
                'crafting_time' => 5,
                'xp_reward' => 15,
                'description' => 'Laisse les doigts libres : on ne trace pas un signe ganté de cuir.',
                'name_translations' => ['en' => 'Linen Mitts'],
            ],
            'recipe_linen_robe' => [
                'name' => 'Robe de lin',
                'slug' => 'recipe-linen-robe',
                'craft' => 'tailleur',
                'required_level' => 2,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 4],
                ],
                'result_ref' => 'linen_robe',
                'crafting_time' => 8,
                'xp_reward' => 25,
                'description' => 'La première robe du monde. Rien n\'y entrave le geste.',
                'name_translations' => ['en' => 'Linen Robe'],
            ],
            'recipe_fine_linen_hood' => [
                'name' => 'Capuche de lin fin',
                'slug' => 'recipe-fine-linen-hood',
                'craft' => 'tailleur',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 3],
                    // ECO-14 : la bordure vient du tanneur. Au palier 3, jamais a
                    // l'entree — croiser les metiers des le niveau 1 casserait le
                    // plancher T1 (ECO-02), et le mage doit pouvoir s'habiller
                    // seul au premier jour.
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'fine_linen_hood',
                'crafting_time' => 10,
                'xp_reward' => 30,
                'description' => 'Bat le lin jusqu\'à la soie, puis le borde de cuir souple.',
                'name_translations' => ['en' => 'Fine Linen Hood'],
            ],
            'recipe_fine_linen_gloves' => [
                'name' => 'Mitaines de lin fin',
                'slug' => 'recipe-fine-linen-gloves',
                'craft' => 'tailleur',
                'required_level' => 3,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 3],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 1],
                ],
                'result_ref' => 'fine_linen_gloves',
                'crafting_time' => 10,
                'xp_reward' => 30,
                'description' => 'Double le fil au bout des doigts, là où le geste use la toile.',
                'name_translations' => ['en' => 'Fine Linen Mitts'],
            ],
            'recipe_fine_linen_robe' => [
                'name' => 'Robe de lin fin',
                'slug' => 'recipe-fine-linen-robe',
                'craft' => 'tailleur',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 6],
                    ['slug' => 'crafted-leather-strip', 'quantity' => 2],
                ],
                'result_ref' => 'fine_linen_robe',
                'crafting_time' => 15,
                'xp_reward' => 45,
                'description' => 'Un lin si serré qu\'il tombe droit, et des coutures invisibles.',
                'name_translations' => ['en' => 'Fine Linen Robe'],
            ],
            'recipe_shadowsilk_hood' => [
                'name' => 'Capuche de soie d\'ombre',
                'slug' => 'recipe-shadowsilk-hood',
                'craft' => 'tailleur',
                'required_level' => 6,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 4],
                    // Le poil de loup-garou entre dans la trame : le tailleur
                    // cesse ici de travailler le seul lin.
                    ['slug' => 'leather-werewolf-fur', 'quantity' => 1],
                    ['slug' => 'crafted-gem-basic', 'quantity' => 1],
                ],
                'result_ref' => 'shadowsilk_hood',
                'crafting_time' => 20,
                'xp_reward' => 70,
                'description' => 'Tisse le lin serré avec du poil de loup-garou. Elle garde la lumière.',
                'name_translations' => ['en' => 'Shadowsilk Hood'],
            ],
            'recipe_shadowsilk_robe' => [
                'name' => 'Robe de soie d\'ombre',
                'slug' => 'recipe-shadowsilk-robe',
                'craft' => 'tailleur',
                'required_level' => 7,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 8],
                    ['slug' => 'leather-werewolf-fur', 'quantity' => 2],
                    ['slug' => 'crafted-gem-fine', 'quantity' => 1],
                ],
                'result_ref' => 'shadowsilk_robe',
                'crafting_time' => 28,
                'xp_reward' => 110,
                'description' => 'On la croit noire jusqu\'à la voir bouger.',
                'name_translations' => ['en' => 'Shadowsilk Robe'],
            ],
            'recipe_archivist_mantle' => [
                'name' => 'Mantelet de l\'archiviste',
                'slug' => 'recipe-archivist-mantle',
                'craft' => 'tailleur',
                'required_level' => 8,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 6],
                    ['slug' => 'leather-werewolf-fur', 'quantity' => 2],
                    ['slug' => 'crafted-gem-fine', 'quantity' => 1],
                ],
                'result_ref' => 'archivist_mantle',
                'crafting_time' => 30,
                'xp_reward' => 130,
                'description' => 'Des épaules doubles, cousues pour tenir des heures sans peser.',
                'name_translations' => ['en' => 'Archivist Mantle'],
            ],
            'recipe_archivist_robe' => [
                'name' => 'Robe de l\'archiviste',
                'slug' => 'recipe-archivist-robe',
                'craft' => 'tailleur',
                'required_level' => 9,
                'ingredients' => [
                    ['slug' => 'crafted-cloth', 'quantity' => 10],
                    ['slug' => 'leather-werewolf-fur', 'quantity' => 2],
                    // Le pendant exact du plastron de cuir enchante : meme
                    // palier, meme gemme du joaillier au coeur de la piece.
                    ['slug' => 'crafted-gem-enchanted', 'quantity' => 1],
                ],
                'result_ref' => 'archivist_robe',
                'crafting_time' => 40,
                'xp_reward' => 200,
                'description' => 'Une gemme enchantée cousue au col, et dix aunes de lin autour.',
                'name_translations' => ['en' => 'Archivist Robe'],
            ],
            'recipe_amber_seal' => [
                'name' => 'Sceau d\'ambre',
                'slug' => 'recipe-amber-seal',
                'craft' => 'joaillier',
                'required_level' => 4,
                'ingredients' => [
                    ['slug' => 'amber-fossil', 'quantity' => 2],
                    ['slug' => 'crafted-gem-basic', 'quantity' => 1],
                    ['slug' => 'ore-silver', 'quantity' => 1],
                ],
                'result_ref' => 'crafted_amber_seal',
                'crafting_time' => 10,
                'xp_reward' => 35,
                'description' => 'Sertit une gemme dans une larme d\'ambre du sud, cerclée d\'argent.',
                'name_translations' => ['en' => 'Amber Seal'],
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

            // === Recettes exclusives aux maitres artisans (task 122 sous-phase 2) ===
            // Chacune requiert la specialisation correspondante et un niveau eleve.
            // OBJ-02b : la joaillerie T4-T5 (gemme prismatique, bijoux
            // legendaires) et les autres recettes butant sur les minerais
            // d'extension sont versees a docs/EXTENSION_RESERVE.md. Le grand
            // elixir, lui, ne butait que par la gemme prismatique : il est
            // reecrit dans le perimetre de la base — sans lui, le fruit du
            // vide (raccorde par ZON-35) redevenait une recolte sans debouche.

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
                    // OBJ-02b : la gemme enchantee (joaillier, niveau 5)
                    // remplace la gemme prismatique partie a la reserve
                    // d'extension — le chainage inter-metiers est conserve.
                    ['slug' => 'crafted-gem-enchanted', 'quantity' => 1],
                    // ZON-35 : le fruit du vide, la plus rare du monde, au sommet
                    // de l'alchimie. C'est la seule place qui lui allait.
                    ['slug' => 'plant-voidfruit', 'quantity' => 1],
                ],
                'result_ref' => 'masterwork_grand_elixir',
                'crafting_time' => 25,
                'xp_reward' => 180,
                'description' => 'Reservee aux Maitres Alchimistes. Distille un elixir parfait infuse d\'energie enchantee.',
                'name_translations' => ['en' => 'Master Alchemist\'s Grand Elixir'],
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
                    // ECO-31 : un manteau se double. Le tailleur trouve ici son
                    // acheteur d'un autre metier — sans quoi il produirait sans
                    // que personne n'achete sa production (ECO-14).
                    ['slug' => 'crafted-cloth', 'quantity' => 2],
                ],
                'result_ref' => 'masterwork_drakehide_cloak',
                'crafting_time' => 30,
                'xp_reward' => 200,
                'description' => 'Reservee aux Maitres Tanneurs. Coud un manteau ouvrage dans la peau d\'un drake ancestral.',
                'name_translations' => ['en' => 'Master Leatherworker\'s Cloak'],
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
                    // ECO-30 : une hache est un fer **sur un bois**, et jusqu'ici
                    // le fer se passait du bois. C'est par ce manche que le
                    // charpentier a une demande hors de lui-meme (ECO-14).
                    ['slug' => 'crafted-wood-haft', 'quantity' => 1],
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
            // FAC-07 : la main du faussaire produit une materia du catalogue
            // derive — la reference `materia_fire_ball` doit exister avant.
            MateriaCatalogFixtures::class,
        ];
    }
}
