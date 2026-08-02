<?php

namespace App\DataFixtures;

use App\Entity\App\GameEvent;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class QuestFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des quêtes basées sur le contenu de pnj_quest.yaml
        $quests = [
            'quest_zombie_1' => [
                'name' => 'Sus aux zombies',
                'name_translations' => ['en' => 'Stop the Zombies'],
                'description' => 'Les zombies deviennent gênants dans la region, pourrais-tu m\'en débarrasser ? Je t\'apprendrai comment miner.',
                'description_translations' => ['en' => 'Zombies are becoming a nuisance in the region. Could you get rid of them for me? I will teach you how to mine.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Zombie',
                            'slug' => 'zombie',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'gold' => 1,
                    'items' => [
                        [
                            'item' => 3,
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'beer-pint',
                        ],
                        [
                            'item' => 10,
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'miner-domain-parchment',
                        ],
                    ],
                ],
            ],
            'quest_skeleton_1' => [
                'name' => 'Sus aux squelettes',
                'name_translations' => ['en' => 'Stop the Skeletons'],
                'description' => 'Les squelettes deviennent gênants dans la region, pourrais-tu m\'en débarrasser ?',
                'description_translations' => ['en' => 'Skeletons are becoming a nuisance in the region. Could you get rid of them for me?'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Squelette',
                            'slug' => 'skeleton',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'gold' => 1,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'beer-pint',
                        ],
                    ],
                ],
            ],
            'quest_taiju_1' => [
                'name' => 'Le Taiju menaçant',
                'name_translations' => ['en' => 'The Menacing Taiju'],
                'description' => 'Un Taiju dangereux a été aperçu dans la forêt. Éliminez-le pour assurer la sécurité des villageois.',
                'description_translations' => ['en' => 'A dangerous Taiju has been spotted in the forest. Eliminate it to ensure the villagers\' safety.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Taiju',
                            'slug' => 'taiju',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 50,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'liana-whip',
                        ],
                    ],
                ],
            ],
            'quest_mushroom_1' => [
                'name' => 'Cueillette de champignons',
                'name_translations' => ['en' => 'Mushroom Picking'],
                'description' => 'Récoltez 5 champignons pour l\'apothicaire du village.',
                'description_translations' => ['en' => 'Gather 5 mushrooms for the village apothecary.'],
                'requirements' => [
                    'collect' => [
                        'mushroom' => 5,
                    ],
                ],
                'rewards' => [
                    'xp' => 50,
                    'gold' => 30,
                    'items' => [
                        'materia_life_heal' => 1,
                    ],
                ],
            ],
            'quest_goblin_1' => [
                'name' => 'Menace gobeline',
                'name_translations' => ['en' => 'Goblin Threat'],
                'description' => 'Les gobelins pillent les fermes environnantes. Éliminez-en quelques-uns pour protéger les villageois.',
                'description_translations' => ['en' => 'Goblins are raiding the surrounding farms. Eliminate a few of them to protect the villagers.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Gobelin',
                            'slug' => 'goblin',
                            'count' => 3,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 75,
                    'gold' => 40,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'leather-boots',
                        ],
                    ],
                ],
            ],
            'quest_troll_1' => [
                'name' => 'Le troll du pont',
                'name_translations' => ['en' => 'The Bridge Troll'],
                'description' => 'Un troll a élu domicile sous le pont principal et empêche les marchands de passer. Débarrassez-vous de cette menace.',
                'description_translations' => ['en' => 'A troll has taken up residence under the main bridge and is blocking merchants from passing. Get rid of this threat.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Troll',
                            'slug' => 'troll',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 80,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'wooden-shield',
                        ],
                    ],
                ],
            ],
            'quest_werewolf_1' => [
                'name' => 'Hurlements nocturnes',
                'name_translations' => ['en' => 'Nocturnal Howls'],
                'description' => 'Des hurlements terrifiants résonnent dans la forêt les nuits de pleine lune. Traquez et éliminez le loup-garou responsable.',
                'description_translations' => ['en' => 'Terrifying howls echo through the forest on nights of the full moon. Track down and eliminate the werewolf responsible.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Loup-garou',
                            'slug' => 'werewolf',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 100,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'leather-armor',
                        ],
                    ],
                ],
            ],
            'quest_banshee_griffin_1' => [
                'name' => 'Créatures de la nuit',
                'name_translations' => ['en' => 'Creatures of the Night'],
                'description' => 'Des créatures mystérieuses terrorisent les voyageurs. Éliminez une banshee et un griffon pour sécuriser les routes.',
                'description_translations' => ['en' => 'Mysterious creatures are terrorizing travelers. Eliminate a banshee and a griffin to secure the roads.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Banshee',
                            'slug' => 'banshee',
                            'count' => 1,
                        ],
                        [
                            'name' => 'Griffon',
                            'slug' => 'griffin',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 150,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'magic-amulet',
                        ],
                    ],
                ],
            ],
            'quest_wood_collection' => [
                'name' => 'Bûcheron en herbe',
                'name_translations' => ['en' => 'Budding Woodcutter'],
                'description' => 'Le menuisier du village a besoin de bois pour ses créations. Récoltez des bûches pour l\'aider.',
                'description_translations' => ['en' => 'The village carpenter needs wood for his creations. Gather some logs to help him.'],
                'requirements' => [
                    'collect' => [
                        // OBJ-02 : la buche generique a disparu — le menuisier
                        // demande du hetre, l'essence d'entree de la ligne du
                        // bois (slug canonique, comme plant-mint plus bas).
                        'wood-beech' => 8,
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 45,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
            ],
            'quest_dragon_1' => [
                'name' => 'Le dragon de la montagne',
                'name_translations' => ['en' => 'The Mountain Dragon'],
                'description' => 'Un dragon terrorise la région depuis sa tanière dans la montagne. Cette quête est extrêmement dangereuse, mais la récompense est à la hauteur du risque.',
                'description_translations' => ['en' => 'A dragon has been terrorizing the region from its lair in the mountain. This quest is extremely dangerous, but the reward matches the risk.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Dragon',
                            'slug' => 'dragon',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 500,
                    'gold' => 300,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'iron-sword',
                        ],
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'iron-armor',
                        ],
                    ],
                ],
            ],
            // --- Quete de livraison ---
            'quest_deliver_mushroom' => [
                'name' => 'Livraison de champignons',
                'name_translations' => ['en' => 'Mushroom Delivery'],
                'description' => 'L\'herboriste a besoin de champignons frais pour ses remèdes. Récoltez-en et apportez-les-lui.',
                'description_translations' => ['en' => 'The herbalist needs fresh mushrooms for her remedies. Gather some and bring them to her.'],
                'requirements' => [
                    'deliver' => [
                        [
                            'item_slug' => 'mushroom',
                            'pnj_id' => 8,
                            'quantity' => 3,
                            'name' => 'Champignon',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 40,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
            ],
            // --- Quete d'exploration ---
            'quest_explore_forest' => [
                'name' => 'Cartographier la forêt',
                'name_translations' => ['en' => 'Mapping the Forest'],
                'description' => 'La cartographe du village a besoin que quelqu\'un explore la forêt pour compléter ses cartes. Rendez-vous aux coordonnées indiquées.',
                'description_translations' => ['en' => 'The village cartographer needs someone to explore the forest to complete her maps. Travel to the indicated coordinates.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'foret-des-murmures',
                            'name' => 'Clairière de la forêt',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 35,
                ],
            ],
            // --- Quete a choix ---
            'quest_choice_alliance' => [
                'name' => 'Allégeance contestée',
                'name_translations' => ['en' => 'Contested Allegiance'],
                'description' => 'Vous avez découvert un convoi abandonné contenant des ressources précieuses. Le garde et le marchand du village vous demandent chacun de leur remettre. À vous de choisir.',
                'description_translations' => ['en' => 'You have discovered an abandoned convoy containing valuable resources. The village guard and the merchant each ask you to hand them over. The choice is yours.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'village-de-lumiere',
                            'name' => 'Convoi abandonné',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 30,
                ],
                'choiceOutcome' => [
                    [
                        'key' => 'help_guard',
                        'label' => 'Remettre au garde',
                        'bonusRewards' => [
                            'gold' => 20,
                            'items' => [
                                ['genericItemSlug' => 'wooden-shield', 'count' => 1],
                            ],
                        ],
                    ],
                    [
                        'key' => 'help_merchant',
                        'label' => 'Remettre au marchand',
                        'bonusRewards' => [
                            'gold' => 80,
                            'items' => [
                                ['genericItemSlug' => 'life-potion', 'count' => 3],
                            ],
                        ],
                    ],
                ],
            ],
            // --- Quetes quotidiennes ---
            'daily_kill_slimes' => [
                'name' => 'Purge de slimes',
                'name_translations' => ['en' => 'Slime Purge'],
                'description' => 'Les slimes prolifèrent ! Éliminez-en quelques-uns pour garder les alentours propres.',
                'description_translations' => ['en' => 'Slimes are multiplying! Eliminate a few to keep the area clean.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Slime', 'slug' => 'slime', 'count' => 3],
                    ],
                ],
                'rewards' => [
                    'xp' => 30,
                    'gold' => 20,
                ],
                'isDaily' => true,
                'dailyPool' => 'combat',
            ],
            'daily_kill_bats' => [
                'name' => 'Chasse aux chauve-souris',
                'name_translations' => ['en' => 'Bat Hunt'],
                'description' => 'Les chauve-souris gênent les mineurs dans les grottes. Chassez-en quelques-unes.',
                'description_translations' => ['en' => 'Bats are bothering the miners in the caves. Hunt a few of them down.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Chauve-souris', 'slug' => 'bat', 'count' => 3],
                    ],
                ],
                'rewards' => [
                    'xp' => 30,
                    'gold' => 20,
                ],
                'isDaily' => true,
                'dailyPool' => 'combat',
            ],
            'daily_kill_spiders' => [
                'name' => 'Toiles indésirables',
                'name_translations' => ['en' => 'Unwanted Webs'],
                'description' => 'Les araignées bloquent les sentiers forestiers. Nettoyez le passage.',
                'description_translations' => ['en' => 'Spiders are blocking the forest paths. Clear the way.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Araignée', 'slug' => 'spider', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 35,
                    'gold' => 25,
                ],
                'isDaily' => true,
                'dailyPool' => 'combat',
            ],
            'daily_collect_herbs' => [
                'name' => 'Cueillette du jour',
                'name_translations' => ['en' => 'Daily Harvest'],
                'description' => 'L\'herboriste a besoin de plantes fraîches pour ses potions quotidiennes.',
                'description_translations' => ['en' => 'The herbalist needs fresh plants for her daily potions.'],
                'requirements' => [
                    'collect' => [
                        'plant-mint' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 25,
                    'gold' => 15,
                ],
                'isDaily' => true,
                'dailyPool' => 'recolte',
            ],
            'daily_collect_ore' => [
                'name' => 'Minerai pour la forge',
                'name_translations' => ['en' => 'Ore for the Forge'],
                'description' => 'Le forgeron a toujours besoin de minerai de cuivre. Rapportez-en de la mine.',
                'description_translations' => ['en' => 'The blacksmith always needs copper ore. Bring some back from the mine.'],
                'requirements' => [
                    'collect' => [
                        'ore-copper' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 25,
                    'gold' => 15,
                ],
                'isDaily' => true,
                'dailyPool' => 'recolte',
            ],
            'daily_kill_rats' => [
                'name' => 'Rats des champs',
                'name_translations' => ['en' => 'Field Rats'],
                'description' => 'Les rats géants envahissent les réserves. Éliminez-en avant qu\'ils ne dévorent tout.',
                'description_translations' => ['en' => 'Giant rats are invading the storehouses. Eliminate them before they devour everything.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Rat géant', 'slug' => 'giant_rat', 'count' => 3],
                    ],
                ],
                'rewards' => [
                    'xp' => 30,
                    'gold' => 20,
                ],
                'isDaily' => true,
                'dailyPool' => 'combat',
            ],
            // --- Chaine de quetes : La Menace Rampante (3 quetes) ---
            'quest_chain_guard_1' => [
                'name' => 'La Menace Rampante - Partie 1',
                'name_translations' => ['en' => 'The Creeping Menace - Part 1'],
                'description' => 'Le capitaine de la garde a remarqué une activité inhabituelle de gobelins près du village. Éliminez-en quelques-uns pour évaluer la menace.',
                'description_translations' => ['en' => 'The captain of the guard has noticed unusual goblin activity near the village. Eliminate a few to assess the threat.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Gobelin',
                            'slug' => 'goblin',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 50,
                    'gold' => 25,
                ],
                'prerequisiteQuests' => null,
            ],
            'quest_chain_guard_2' => [
                'name' => 'La Menace Rampante - Partie 2',
                'name_translations' => ['en' => 'The Creeping Menace - Part 2'],
                'description' => 'Les gobelins étaient des éclaireurs ! Le capitaine vous envoie éliminer les squelettes qu\'ils ont réveillés dans les ruines voisines.',
                'description_translations' => ['en' => 'The goblins were scouts! The captain sends you to eliminate the skeletons they awakened in the nearby ruins.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Squelette',
                            'slug' => 'skeleton',
                            'count' => 3,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 50,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush (needs ID of quest_chain_guard_1)
            ],
            'quest_chain_guard_3' => [
                'name' => 'La Menace Rampante - Partie 3',
                'name_translations' => ['en' => 'The Creeping Menace - Part 3'],
                'description' => 'Le vrai meneur est un troll qui contrôlait les gobelins et les squelettes. Mettez fin à cette menace une bonne fois pour toutes !',
                'description_translations' => ['en' => 'The real leader is a troll who was controlling the goblins and skeletons. Put an end to this threat once and for all!'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Troll',
                            'slug' => 'troll',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'wooden-shield',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush (needs ID of quest_chain_guard_2)
            ],
            // --- Chaîne narrative Acte 4 : Ce que le sel a garde (tache 128c) ---
            //
            // Premiere chaine ecrite **apres** le pivot, et premiere a cibler ses
            // etapes d'exploration par `zone_slug` plutot que par `map_id` +
            // coordonnees. La forme heritee marche encore — `PlayerQuestUpdater`
            // resout les deux — mais elle demande une carte d'origine, que les
            // zones de l'Acte 4 n'ont pas.
            //
            // L'arc suit le graphe : deux quetes au sud dans le sel, deux au nord
            // dans la glace, et une derniere qui les noue. Le joueur traverse donc
            // tout le monde connu entre la premiere et la derniere.
            'quest_acte4_appel_du_sel' => [
                'name' => 'Ce que le sel a garde — L\'appel',
                'name_translations' => ['en' => 'What the Salt Kept — The Call'],
                'description' => 'Les caravaniers des Dunes parlent d\'une croute blanche au sud, ou le sable cede la place au sel. Personne n\'en revient avec la meme histoire. Allez voir.',
                'description_translations' => ['en' => 'The caravanners of the Dunes speak of a white crust to the south, where sand gives way to salt. Nobody comes back with the same story. Go and see.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'mer-de-sel',
                            'name' => 'Mer de Sel',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 400,
                    'gold' => 200,
                ],
                'storyArc' => 'acte4',
                'arcOrder' => 1,
            ],
            'quest_acte4_ce_qui_rampe' => [
                'name' => 'Ce que le sel a garde — Ce qui rampe',
                'name_translations' => ['en' => 'What the Salt Kept — What Crawls'],
                'description' => 'La croute n\'est pas vide. Quelque chose y vit qui a appris a ne plus avoir soif, et qui ne compte pas partager. Faites de la place.',
                'description_translations' => ['en' => 'The crust is not empty. Something lives there that has learned to stop being thirsty, and does not intend to share. Make room.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Rodeur des sables',
                            'slug' => 'sand_stalker',
                            'count' => 8,
                        ],
                        [
                            'name' => 'Colosse de sel',
                            'slug' => 'salt_colossus',
                            'count' => 3,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 550,
                    'gold' => 280,
                ],
                'storyArc' => 'acte4',
                'arcOrder' => 2,
                // prerequisiteQuests set after flush (needs ID of quest_acte4_appel_du_sel)
            ],
            'quest_acte4_cite_sous_le_sable' => [
                'name' => 'Ce que le sel a garde — La cite sous le sable',
                'name_translations' => ['en' => 'What the Salt Kept — The City Beneath the Sand'],
                'description' => 'Des toits affleurent, plus loin. Quelqu\'un a vecu la, et quelque chose y est reste. Les spectres des dunes savent ce qu\'on a enterre — ils ne le diront pas de leur vivant.',
                'description_translations' => ['en' => 'Rooftops break the surface, further on. Someone lived there, and something stayed. The dune wraiths know what was buried — they will not tell it while they live.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'cite-ensevelie',
                            'name' => 'Cité Ensevelie',
                        ],
                    ],
                    'monsters' => [
                        [
                            'name' => 'Spectre des dunes',
                            'slug' => 'dune_wraith',
                            'count' => 6,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 700,
                    'gold' => 380,
                ],
                'storyArc' => 'acte4',
                'arcOrder' => 3,
                // prerequisiteQuests set after flush (needs ID of quest_acte4_ce_qui_rampe)
            ],
            'quest_acte4_par_dela_la_crete' => [
                'name' => 'Ce que le sel a garde — Par-dela la crete',
                'name_translations' => ['en' => 'What the Salt Kept — Beyond the Ridge'],
                'description' => 'Ce que la cite ensevelie a livre pointe vers le nord, tout au nord, au-dela de la Crete de Ventombre. Le col est garde par des meutes qui ne dorment pas.',
                'description_translations' => ['en' => 'What the buried city gave up points north, far north, beyond Windshadow Ridge. The pass is guarded by packs that do not sleep.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'pas-de-givre',
                            'name' => 'Pas de Givre',
                        ],
                    ],
                    'monsters' => [
                        [
                            'name' => 'Warg des glaces',
                            'slug' => 'frost_warg',
                            'count' => 8,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 800,
                    'gold' => 450,
                ],
                'storyArc' => 'acte4',
                'arcOrder' => 4,
                // prerequisiteQuests set after flush (needs ID of quest_acte4_cite_sous_le_sable)
            ],
            'quest_acte4_le_silence' => [
                'name' => 'Ce que le sel a garde — Le silence',
                'name_translations' => ['en' => 'What the Salt Kept — The Silence'],
                'description' => 'Le glacier ne fait aucun bruit, et c\'est ce qui inquiete. Ce qui dort sous la glace a le meme age que ce qui dormait sous le sel. Allez voir ce qui vous attend au bout.',
                'description_translations' => ['en' => 'The glacier makes no sound, and that is what worries. What sleeps beneath the ice is as old as what slept beneath the salt. Go and see what waits at the end.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'glacier-du-silence',
                            'name' => 'Glacier du Silence',
                        ],
                    ],
                    'monsters' => [
                        [
                            'name' => 'Drakan de rime',
                            'slug' => 'rime_drake',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 1000,
                    'gold' => 600,
                ],
                'storyArc' => 'acte4',
                'arcOrder' => 5,
                // prerequisiteQuests set after flush (needs ID of quest_acte4_par_dela_la_crete)
            ],
            // =================================================================
            // Acte I — la boucle, trois fois (ONB-12b)
            // =================================================================
            // GAME_ONBOARDING § 5.2 : dix etapes, trois tours de la meme boucle
            // — **parchemin → arbre → geste** — sur l'arme, la materia et la
            // recolte. La chaine heritee en comptait sept, commençait par le
            // voyage (le seul geste time-gate) et **ne mentionnait jamais la
            // materia**, c'est-a-dire ni la seule source d'actions de combat
            // (regle 10), ni le build du personnage.
            //
            // Deux principes tiennent la forme de ce bloc :
            //
            // 1. **A chaque tour, le choix est reel** — quelle arme, quel
            //    element, quel metier, ou partir. Il passe par `choiceOutcome`,
            //    qui existait deja : la quete propose, le joueur tranche a la
            //    remise, et la recompense suit son choix.
            // 2. **Une quete ne nomme jamais ce qu'elle ne peut pas savoir.**
            //    Le metier est choisi a l'etape 6 : les etapes 7 et 8 ne peuvent
            //    donc designer ni un objet a recolter, ni une recette. Elles
            //    constatent le **geste** (ONB-12a), et c'est ce qui empeche la
            //    chaine de choisir a la place du joueur.
            //
            // Les cles de reference sont conservees : `quest_acte1_cristal` est
            // la porte de l'acte 2 pour quatre fixtures de dialogue, et
            // `PnjFixtures` en designe cinq. Les renommer aurait casse tout cela
            // pour un gain nul.
            'quest_acte1_reveil' => [
                'name' => 'L\'Éveil — Le maître d\'armes',
                'name_translations' => ['en' => 'The Awakening — The Weapon Master'],
                'description' => 'Vous ouvrez les yeux au Fanal, sans aucun souvenir. Ici on appelle cela un Limpide : quelqu\'un sur qui rien ne s\'est encore déposé. Ysold, maîtresse d\'armes, ne vous demande pas d\'où vous venez — elle vous fait choisir. Une arme, et la voie qui apprend à la tenir.',
                'description_translations' => ['en' => 'You open your eyes at the Beacon, without any memory. Here they call that a Limpide: someone on whom nothing has settled yet. Ysold, mistress of arms, does not ask where you come from — she makes you choose. A weapon, and the path that teaches you to hold it.'],
                'requirements' => [
                    // `pnj_id` recale apres flush par `QuestChainFixtures`.
                    'talk_to' => [
                        [
                            'pnj_id' => 0,
                            'name' => 'Ysold, maîtresse d\'armes',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 20,
                    'gold' => 10,
                ],
                // Le premier vrai choix, et il est indolore : chaque option
                // remet **l'arme et le parchemin de l'arbre qui l'autorise**.
                // En separer les deux laisserait un joueur avec une arme qu'il
                // ne peut pas porter — le refus d'ONB-20b, sans le remede.
                'choiceOutcome' => [
                    [
                        'key' => 'sword',
                        'label' => 'L\'épée — la voie du soldat',
                        'bonusRewards' => ['items' => ['short-sword' => 1, 'soldier-domain-parchment' => 1]],
                    ],
                    [
                        'key' => 'axe',
                        'label' => 'La hache — la voie du berserker',
                        'bonusRewards' => ['items' => ['t1-axe' => 1, 'berserker-domain-parchment' => 1]],
                    ],
                    [
                        'key' => 'bow',
                        'label' => 'L\'arc — la voie de l\'archer',
                        'bonusRewards' => ['items' => ['t1-bow' => 1, 'archer-domain-parchment' => 1]],
                    ],
                    [
                        'key' => 'dagger',
                        'label' => 'La dague — la voie de l\'assassin',
                        'bonusRewards' => ['items' => ['t1-dagger' => 1, 'assassin-domain-parchment' => 1]],
                    ],
                    [
                        'key' => 'lance',
                        'label' => 'La lance — la voie du chevalier',
                        'bonusRewards' => ['items' => ['t1-lance' => 1, 'knight-domain-parchment' => 1]],
                    ],
                    [
                        'key' => 'staff',
                        'label' => 'Le bâton — la voie du paladin',
                        'bonusRewards' => ['items' => ['t1-staff' => 1, 'paladin-domain-parchment' => 1]],
                    ],
                ],
                'prerequisiteQuests' => null,
                'storyArc' => 'intro',
                'arcOrder' => 1,
            ],
            'quest_acte1_premiers_pas' => [
                'name' => 'L\'Éveil — Apprendre',
                'name_translations' => ['en' => 'The Awakening — Learning'],
                'description' => 'Le parchemin ouvre un arbre ; l\'arbre donne le nœud qui autorise votre arme ; le nœud vous laisse la porter. Lisez, apprenez, équipez. C\'est la boucle du jeu entier, et vous venez d\'en faire le premier tour.',
                'description_translations' => ['en' => 'The scroll opens a tree; the tree grants the node that allows your weapon; the node lets you wear it. Read, learn, equip. This is the loop of the whole game, and you have just completed its first turn.'],
                'requirements' => [
                    // Sans cible : les six options de l\'etape 1 sont toutes des
                    // armes, et nommer une famille reviendrait a annuler le
                    // choix qu\'on vient d\'offrir.
                    'gesture' => [
                        ['gesture' => 'equip_item', 'name' => 'Porter votre arme'],
                    ],
                ],
                'rewards' => [
                    'xp' => 30,
                    'gold' => 15,
                ],
                'storyArc' => 'intro',
                'arcOrder' => 2,
            ],
            'quest_acte1_bapteme_du_feu' => [
                'name' => 'L\'Éveil — Le mannequin',
                'name_translations' => ['en' => 'The Awakening — The Dummy'],
                'description' => 'Gareth a planté un mannequin sur la place. Il ne rend pas les coups — il tourne sur lui-même. Frappez-le jusqu\'à ce qu\'il tombe : personne ne perd contre un mannequin, et c\'est fait pour.',
                'description_translations' => ['en' => 'Gareth has planted a training dummy on the square. It does not strike back — it spins on itself. Hit it until it falls: nobody loses to a dummy, and that is the point.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Mannequin d\'entraînement',
                            'slug' => 'training_dummy_still',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 40,
                    'gold' => 20,
                    // ONB-12b : derivee de l\'arbre ouvert a l\'etape 1, jamais un
                    // objet fixe. On ne montre jamais une materia qu\'on ne peut
                    // pas utiliser — les points de l\'accord viennent avec.
                    'act_one_materia' => true,
                ],
                'storyArc' => 'intro',
                'arcOrder' => 3,
            ],
            'quest_acte1_accord' => [
                'name' => 'L\'Éveil — L\'accord',
                'name_translations' => ['en' => 'The Awakening — The Attunement'],
                'description' => 'Une matéria est un éclat d\'améthyste où le geste de quelqu\'un est resté lisible. Elle ne se porte donc pas : elle s\'accorde. Prenez le nœud d\'accord dans votre arbre, puis sertissez-la dans un emplacement de votre équipement. Deuxième tour de la boucle.',
                'description_translations' => ['en' => 'A materia is a shard of amethyst in which someone\'s gesture stayed legible. So it is not worn: it is attuned. Take the attunement node in your tree, then socket it into one of your equipment slots. Second turn of the loop.'],
                'requirements' => [
                    'gesture' => [
                        ['gesture' => 'socket_materia', 'name' => 'Sertir votre matéria'],
                    ],
                ],
                'rewards' => [
                    'xp' => 35,
                    'gold' => 20,
                ],
                'storyArc' => 'intro',
                'arcOrder' => 4,
            ],
            'quest_acte1_second_mannequin' => [
                'name' => 'L\'Éveil — Le second mannequin',
                'name_translations' => ['en' => 'The Awakening — The Second Dummy'],
                'description' => 'Celui-ci rend les coups. Faiblement, et il ne peut pas vous tuer — mais votre barre descendra, et c\'est ce qui apprend à quoi servent les soins. Lancez votre sort au moins une fois.',
                'description_translations' => ['en' => 'This one strikes back. Weakly, and it cannot kill you — but your bar will drop, and that is what teaches what healing is for. Cast your spell at least once.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Mannequin de passe d\'armes',
                            'slug' => 'training_dummy_sparring',
                            'count' => 1,
                        ],
                    ],
                    'gesture' => [
                        ['gesture' => 'cast_spell', 'name' => 'Lancer votre sort'],
                    ],
                ],
                'rewards' => [
                    'xp' => 45,
                    'gold' => 25,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
                'storyArc' => 'intro',
                'arcOrder' => 5,
            ],
            'quest_acte1_metier' => [
                'name' => 'L\'Éveil — Le métier',
                'name_translations' => ['en' => 'The Awakening — The Trade'],
                'description' => 'Lyra connaît les chemins qui partent du Fanal, et ce qu\'on y ramasse. Choisissez un métier de récolte : c\'est la décision la plus structurante de votre première semaine, et elle ne ferme aucune porte — elle en ouvre une.',
                'description_translations' => ['en' => 'Lyra knows the paths leaving the Beacon, and what one gathers along them. Choose a gathering trade: it is the most structuring decision of your first week, and it closes no door — it opens one.'],
                'requirements' => [
                    // `pnj_id` recale apres flush par `QuestChainFixtures`.
                    'talk_to' => [
                        [
                            'pnj_id' => 0,
                            'name' => 'Lyra, guide du Fanal',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 40,
                    'gold' => 20,
                ],
                // Les **cinq** recoltes, et elles sont toutes atteignables dans
                // le perimetre de l\'acte I (ONB-10). Un choix parmi cinq qui
                // deboucherait sur une seule recolte possible serait un faux
                // choix — et tout le monde deviendrait herboriste.
                'choiceOutcome' => [
                    [
                        'key' => 'herbalist',
                        'label' => 'Herboriste — les plantes',
                        'bonusRewards' => ['items' => ['herbalist-domain-parchment' => 1]],
                    ],
                    [
                        'key' => 'miner',
                        'label' => 'Mineur — les filons',
                        'bonusRewards' => ['items' => ['miner-domain-parchment' => 1]],
                    ],
                    [
                        'key' => 'fisherman',
                        'label' => 'Pêcheur — les eaux',
                        'bonusRewards' => ['items' => ['fisherman-domain-parchment' => 1]],
                    ],
                    [
                        'key' => 'lumberjack',
                        'label' => 'Bûcheron — les essences',
                        'bonusRewards' => ['items' => ['lumberjack-domain-parchment' => 1]],
                    ],
                    [
                        'key' => 'skinner',
                        'label' => 'Dépeceur — le gibier',
                        'bonusRewards' => ['items' => ['skinner-domain-parchment' => 1]],
                    ],
                ],
                'storyArc' => 'intro',
                'arcOrder' => 6,
            ],
            'quest_acte1_recolte' => [
                'name' => 'L\'Éveil — La récolte',
                'name_translations' => ['en' => 'The Awakening — The Harvest'],
                'description' => 'Votre parchemin lu, votre arbre ouvert, il reste le geste. Sortez du Fanal et récoltez. Explorer n\'est pas une corvée à cocher : c\'est ainsi qu\'on trouve où récolter.',
                'description_translations' => ['en' => 'Your scroll read, your tree open, the gesture remains. Leave the Beacon and gather. Exploring is not a chore to tick off: it is how one finds where to gather.'],
                'requirements' => [
                    // Sans cible : le metier vient d\'etre choisi a l\'etape 6, et
                    // nommer une plante ferait de ce choix une decoration.
                    'gesture' => [
                        ['gesture' => 'gather', 'count' => 3, 'name' => 'Récolter trois fois'],
                    ],
                ],
                'rewards' => [
                    'xp' => 40,
                    'gold' => 25,
                ],
                'storyArc' => 'intro',
                'arcOrder' => 7,
            ],
            'quest_acte1_premiere_potion' => [
                'name' => 'L\'Éveil — L\'atelier',
                'name_translations' => ['en' => 'The Awakening — The Workshop'],
                'description' => 'Fabriquez quelque chose avec ce que vous avez ramassé. Vous remarquerez une chose : l\'établi ne coûte aucune énergie. C\'est le premier geste gratuit du jeu, et ce n\'est pas un oubli.',
                'description_translations' => ['en' => 'Craft something with what you gathered. You will notice one thing: the workbench costs no energy. It is the first free gesture in the game, and it is no oversight.'],
                'requirements' => [
                    'gesture' => [
                        ['gesture' => 'craft_item', 'name' => 'Fabriquer un objet'],
                    ],
                ],
                'rewards' => [
                    'xp' => 45,
                    'gold' => 25,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
                'storyArc' => 'intro',
                'arcOrder' => 8,
            ],
            'quest_acte1_cristal' => [
                'name' => 'L\'Éveil — Le départ',
                'name_translations' => ['en' => 'The Awakening — The Departure'],
                'description' => 'Le Fanal est bâti sur la Voûte, et nul vivant ne dit y être entré. Trois chemins en partent, et aucun ne vous est imposé. Prenez-en un. Sachez seulement ceci : un voyage prend du temps réel, et c\'est la première attente que le jeu vous demandera.',
                'description_translations' => ['en' => 'The Beacon is built upon the Vault, and no living soul claims to have entered it. Three paths leave the village, and none is imposed on you. Take one. Know only this: a journey takes real time, and it is the first wait the game will ask of you.'],
                'requirements' => [
                    // Sans cible : trois destinations, aucune imposee. Un
                    // objectif d\'exploration nomme une zone et **additionne**
                    // celles qu\'on declare — il ferait donc des trois chemins
                    // une liste a cocher.
                    'gesture' => [
                        ['gesture' => 'travel', 'name' => 'Voyager vers une vraie zone'],
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 30,
                ],
                'storyArc' => 'intro',
                'arcOrder' => 9,
            ],
            'quest_acte1_guilde' => [
                'name' => 'L\'Éveil — L\'expédition',
                'name_translations' => ['en' => 'The Awakening — The Expedition'],
                'description' => 'Avant de fermer, envoyez-vous en expédition. Le personnage travaille pendant votre absence, et quelque chose vous attendra au retour. C\'est la leçon qui fait revenir demain.',
                'description_translations' => ['en' => 'Before you close, send yourself on an expedition. Your character works while you are away, and something will be waiting on your return. This is the lesson that brings you back tomorrow.'],
                'requirements' => [
                    'gesture' => [
                        ['gesture' => 'start_expedition', 'name' => 'Lancer une expédition'],
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 40,
                ],
                'storyArc' => 'intro',
                'arcOrder' => 10,
            ],
            // --- Quetes d'evenement de Saison 1 (arc `season_saison-1`, NAR-09) ---
            // Chaque quete est rattachee a un beat (GameEvent) : elle n'est
            // disponible que dans la fenetre temporelle de son beat (isEventActive).
            'quest_season1_amorce' => [
                'name' => 'Éveil — L\'appel des cloches',
                'name_translations' => ['en' => 'Awakening — The Call of the Bells'],
                'description' => 'Les cloches du Fanal sonnent l\'alerte. Rendez-vous sur la place pour entendre le héraut annoncer la menace de la saison.',
                'description_translations' => ['en' => 'The bells of the Beacon ring the alarm. Head to the square to hear the herald announce the season\'s threat.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'village-de-lumiere', 'name' => 'Place du village'],
                    ],
                ],
                'rewards' => ['xp' => 40, 'gold' => 20],
                'storyArc' => 'season_saison-1',
                'arcOrder' => 1,
                'gameEvent' => 'season1_beat_amorce',
            ],
            'quest_season1_montee' => [
                'name' => 'Éveil — Repousser les incursions',
                'name_translations' => ['en' => 'Awakening — Repel the Incursions'],
                'description' => 'La menace enfle. Chaque créature abattue affaiblit l\'ennemi et nourrit l\'effort des guildes pour le contrôle des régions. Éliminez des gobelins aux abords du village.',
                'description_translations' => ['en' => 'The threat swells. Every creature slain weakens the enemy and feeds the guilds\' push for regional control. Slay goblins around the village.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Gobelin', 'slug' => 'goblin', 'count' => 3],
                    ],
                ],
                'rewards' => ['xp' => 90, 'gold' => 45],
                'storyArc' => 'season_saison-1',
                'arcOrder' => 2,
                'gameEvent' => 'season1_beat_montee',
            ],
            'quest_season1_climax' => [
                'name' => 'Éveil — L\'assaut de la Faille',
                'name_translations' => ['en' => 'Awakening — Assault on the Rift'],
                'description' => 'La menace atteint son paroxysme. Rejoignez l\'assaut et affrontez le Gardien surgi de la Faille.',
                'description_translations' => ['en' => 'The threat reaches its peak. Join the assault and face the Guardian risen from the Rift.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Gardien de la Forêt', 'slug' => 'forest_guardian', 'count' => 1],
                    ],
                ],
                'rewards' => ['xp' => 180, 'gold' => 100],
                'storyArc' => 'season_saison-1',
                'arcOrder' => 3,
                'gameEvent' => 'season1_beat_climax',
            ],
            'quest_season1_resolution' => [
                'name' => 'Éveil — L\'accalmie',
                'name_translations' => ['en' => 'Awakening — The Lull'],
                'description' => 'La saison se referme. Parcourez les alentours apaisés et constatez le retour au calme — la guilde victorieuse en récoltera les honneurs.',
                'description_translations' => ['en' => 'The season closes. Walk the quieted surroundings and witness the return of calm — the victorious guild will reap the honors.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'foret-des-murmures', 'name' => 'Clairière du Cristal'],
                    ],
                ],
                'rewards' => ['xp' => 120, 'gold' => 70],
                'storyArc' => 'season_saison-1',
                'arcOrder' => 4,
                'gameEvent' => 'season1_beat_resolution',
            ],
            // --- Contenu de fond : chaine de zone « Foret des Murmures » (NAR-13) ---
            // Gabarit de chaine de zone : structure/objectifs/recompenses derives des
            // tables de la zone (herbes, faune), avec des noeuds ecrits a la main.
            // Non bloquant : gate par la decouverte (isHidden) puis la renommee
            // (minRenownScore) — jamais requis pour la progression systeme.
            'quest_bg_foret_rumeurs' => [
                'name' => 'Rumeurs sous les frondaisons',
                'name_translations' => ['en' => 'Rumors Beneath the Canopy'],
                'description' => 'Un bûcheron prétend que les herbes de la Forêt des Murmures murmurent la nuit. Récoltez-en une brassée pour lui prouver qu\'il n\'a pas rêvé.',
                'description_translations' => ['en' => 'A woodcutter claims the herbs of the Whispering Forest whisper at night. Gather a handful to prove he was not dreaming.'],
                'requirements' => [
                    'collect' => [
                        'plant-mint' => 5,
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 40,
                ],
                // Decouverte : revelee en explorant la foret.
                'isHidden' => true,
                'triggerCondition' => [
                    'type' => 'explore',
                    'zone_slug' => 'foret-des-murmures',
                ],
                'storyArc' => 'zone_foret-des-murmures',
                'arcOrder' => 1,
            ],
            'quest_bg_foret_meute' => [
                'name' => 'La meute des ombres',
                'name_translations' => ['en' => 'The Shadow Pack'],
                'description' => 'Les herbes ne murmuraient pas : elles prévenaient. Une meute de loups rôde au cœur de la forêt. Réduisez sa taille avant qu\'elle ne s\'approche du village.',
                'description_translations' => ['en' => 'The herbs were not whispering: they were warning. A wolf pack prowls the forest heart. Thin it before it nears the village.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Loup', 'slug' => 'wolf', 'count' => 4],
                    ],
                ],
                'rewards' => [
                    'xp' => 130,
                    'gold' => 80,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'life-potion'],
                    ],
                ],
                // Renommee : contenu de fond reserve aux aventuriers etablis.
                'minRenownScore' => 50,
                // prerequisiteQuests set after flush (← rumeurs)
                'storyArc' => 'zone_foret-des-murmures',
                'arcOrder' => 2,
            ],
            'quest_bg_foret_coeur' => [
                'name' => 'Le Cœur endormi',
                'name_translations' => ['en' => 'The Sleeping Heart'],
                'description' => 'En suivant la meute, vous découvrez un bosquet où le temps semble suspendu — et, en son centre, un golem de mousse veillant sur une graine ancienne. La forêt ne murmurait pas des rumeurs, mais un nom : le vôtre. Affrontez le gardien pour percer ce mystère.',
                'description_translations' => ['en' => 'Following the pack, you find a grove where time seems suspended — and, at its center, a moss golem guarding an ancient seed. The forest was not whispering rumors, but a name: yours. Face the guardian to uncover the mystery.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Golem de Champignons', 'slug' => 'mushroom_golem', 'count' => 1],
                    ],
                ],
                'rewards' => [
                    'xp' => 220,
                    'gold' => 140,
                    'items' => [
                        ['type' => 'stuff', 'count' => 1, 'genericItemSlug' => 'herbalist-domain-parchment'],
                    ],
                ],
                // Renommee superieure : aboutissement de la chaine de fond.
                'minRenownScore' => 100,
                // prerequisiteQuests set after flush (← meute)
                'storyArc' => 'zone_foret-des-murmures',
                'arcOrder' => 3,
            ],
            // --- Quetes cachees (decouverte) ---
            'quest_hidden_secret_clearing' => [
                'name' => 'Le secret de la clairiere',
                'name_translations' => ['en' => 'The Clearing\'s Secret'],
                'description' => 'En explorant une clairiere isolee, vous decouvrez des traces anciennes au sol. Quelque chose est enterre ici... Explorez les alentours pour trouver d\'autres indices.',
                'description_translations' => ['en' => 'While exploring a secluded clearing, you notice ancient marks on the ground. Something is buried here... Explore the surroundings to find more clues.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'foret-des-murmures',
                            'name' => 'Pierre gravee',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 50,
                ],
                'isHidden' => true,
                'triggerCondition' => [
                    'type' => 'explore',
                    'zone_slug' => 'foret-des-murmures',
                ],
            ],
            'quest_hidden_rare_slime' => [
                'name' => 'La gelee doree',
                'name_translations' => ['en' => 'The Golden Slime'],
                'description' => 'En eliminant une gelee, vous remarquez un etrange eclat dore dans ses restes. Les villageois parlent d\'une gelee rare au coeur brillant. Eliminez-en davantage pour la trouver.',
                'description_translations' => ['en' => 'While defeating a slime, you notice a strange golden glimmer in its remains. Villagers speak of a rare slime with a shining core. Slay more of them to find it.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Gelée',
                            'slug' => 'slime',
                            'count' => 5,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 75,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 3,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
                'isHidden' => true,
                'triggerCondition' => [
                    'type' => 'kill',
                    'monster_slug' => 'slime',
                ],
            ],
            'quest_hidden_herb_lore' => [
                'name' => 'Savoir ancestral',
                'name_translations' => ['en' => 'Ancestral Lore'],
                'description' => 'En recoltant des champignons, vous trouvez un vieux parchemin cachant une recette oubliee. Recoltez d\'autres ingredients pour la reconstituer.',
                'description_translations' => ['en' => 'While gathering mushrooms, you find an old parchment hiding a forgotten recipe. Gather more ingredients to reconstruct it.'],
                'requirements' => [
                    'collect' => [
                        'mushroom' => 8,
                    ],
                ],
                'rewards' => [
                    'xp' => 70,
                    'gold' => 40,
                    'items' => [
                        'materia_life_heal' => 1,
                    ],
                ],
                'isHidden' => true,
                'triggerCondition' => [
                    'type' => 'harvest',
                    'item_slug' => 'mushroom',
                ],
            ],
            'quest_hidden_goblin_cache' => [
                'name' => 'La planque des gobelins',
                'name_translations' => ['en' => 'The Goblin Hideout'],
                'description' => 'Sur le cadavre d\'un gobelin, vous trouvez une carte menant a une cache secrete. Eliminez d\'autres gobelins pour reunir les morceaux de la carte.',
                'description_translations' => ['en' => 'On a goblin\'s corpse, you find a map leading to a hidden cache. Defeat more goblins to gather the pieces of the map.'],
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Gobelin',
                            'slug' => 'goblin',
                            'count' => 4,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 100,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'leather-boots',
                        ],
                    ],
                ],
                'isHidden' => true,
                'triggerCondition' => [
                    'type' => 'kill',
                    'monster_slug' => 'goblin',
                ],
            ],
            // --- Chaîne narrative Acte 2 : Fragment Forêt (4 quêtes) ---
            'quest_acte2_foret_murmures' => [
                'name' => 'Les Fragments — Les Murmures s\'intensifient',
                'name_translations' => ['en' => 'The Fragments — The Whispers Grow Stronger'],
                'description' => 'Depuis que vous avez touché le Cristal d\'Améthyste, vous percevez des échos étranges venant de la Forêt des Murmures. Thadeus l\'Ermite, qui vit au nord de la forêt, pourrait avoir des réponses.',
                'description_translations' => ['en' => 'Since touching the Amethyst Crystal, you have been sensing strange echoes coming from the Whispering Forest. Thadeus the Hermit, who lives north of the forest, may have answers.'],
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 0, 'name' => 'Thadeus l\'Ermite'],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 40,
                ],
                // prerequisiteQuests set after flush (needs quest_acte1_cristal ID)
            ],
            'quest_acte2_foret_purification' => [
                'name' => 'Les Fragments — Purifier la Corruption',
                'name_translations' => ['en' => 'The Fragments — Purify the Corruption'],
                'description' => 'Thadeus a senti une corruption ancienne se réveiller dans la forêt. Des créatures corrompues rôdent près de l\'Arbre-Mère. Éliminez-les pour affaiblir la corruption.',
                'description_translations' => ['en' => 'Thadeus has sensed an ancient corruption awakening in the forest. Corrupted creatures prowl near the Mother-Tree. Eliminate them to weaken the corruption.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Ondine', 'slug' => 'undine', 'count' => 2],
                        ['name' => 'Ochu', 'slug' => 'ochu', 'count' => 2],
                        ['name' => 'Feu follet', 'slug' => 'will_o_wisp', 'count' => 1],
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 80,
                    'items' => [
                        ['type' => 'stuff', 'count' => 3, 'genericItemSlug' => 'life-potion'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_foret_remede' => [
                'name' => 'Les Fragments — Le Remède Ancestral',
                'name_translations' => ['en' => 'The Fragments — The Ancestral Remedy'],
                'description' => 'La corruption a été affaiblie, mais l\'Arbre-Mère reste malade. Thadeus a besoin de sauge et de mandragore pour préparer un remède ancestral. Récoltez-les et apportez-les à Elara l\'Herboriste qui saura les préparer.',
                'description_translations' => ['en' => 'The corruption has been weakened, but the Mother-Tree remains ill. Thadeus needs sage and mandrake to prepare an ancestral remedy. Gather them and bring them to Elara the Herbalist, who will know how to prepare them.'],
                'requirements' => [
                    'collect' => [
                        'plant-sage' => 3,
                        'plant-mandrake' => 2,
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 60,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'antidote'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_foret_fragment' => [
                'name' => 'Les Fragments — Le Fragment Sylvestre',
                'name_translations' => ['en' => 'The Fragments — The Sylvan Shard'],
                'description' => 'Le remède a guéri l\'Arbre-Mère. En remerciement, ses racines ont révélé un éclat de cristal vert enfoui depuis des siècles. Rendez-vous au cœur de la forêt pour le récupérer.',
                'description_translations' => ['en' => 'The remedy has healed the Mother-Tree. In gratitude, its roots have revealed a green crystal shard buried for centuries. Head to the heart of the forest to retrieve it.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'foret-des-murmures',
                            'name' => 'Racines de l\'Arbre-Mère',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        ['type' => 'quest', 'count' => 1, 'genericItemSlug' => 'quest-fragment-foret'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Chaîne narrative Acte 2 : Fragment Mines (4 quêtes) ---
            'quest_acte2_mines_tremblements' => [
                'name' => 'Les Fragments — Tremblements souterrains',
                'name_translations' => ['en' => 'The Fragments — Underground Tremors'],
                'description' => 'Depuis votre contact avec le Cristal d\'Améthyste, vous percevez des vibrations sourdes venant des Mines Profondes. Grimmur le Contremaître, posté à l\'entrée, pourrait en savoir plus.',
                'description_translations' => ['en' => 'Since your contact with the Amethyst Crystal, you sense dull vibrations coming from the Deep Mines. Grimmur the Foreman, stationed at the entrance, may know more.'],
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 0, 'name' => 'Grimmur le Contremaître'],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 40,
                ],
                // prerequisiteQuests set after flush (needs quest_acte1_cristal ID)
            ],
            'quest_acte2_mines_minerai' => [
                'name' => 'Les Fragments — Le Minerai Ancien',
                'name_translations' => ['en' => 'The Fragments — The Ancient Ore'],
                'description' => 'Grimmur a senti une énergie étrange émaner des filons profonds. Il vous demande de récolter du minerai de fer et de l\'or enfoui pour analyser la source de ces vibrations.',
                'description_translations' => ['en' => 'Grimmur has felt a strange energy emanating from the deep veins. He asks you to gather iron ore and buried gold to analyse the source of these vibrations.'],
                'requirements' => [
                    'collect' => [
                        'ore-iron' => 5,
                        'ore-gold' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 80,
                    'items' => [
                        ['type' => 'stuff', 'count' => 3, 'genericItemSlug' => 'healing-potion-small'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_mines_forge' => [
                'name' => 'Les Fragments — Le Seigneur de la Forge',
                'name_translations' => ['en' => 'The Fragments — The Forge Lord'],
                'description' => 'L\'énergie provient des profondeurs, là où règne le Seigneur de la Forge. Ce gardien devenu fou protège quelque chose d\'ancien. Vous devez le vaincre pour atteindre la source des vibrations.',
                'description_translations' => ['en' => 'The energy comes from the depths, where the Forge Lord reigns. This guardian turned mad protects something ancient. You must defeat him to reach the source of the vibrations.'],
                'requirements' => [
                    'boss_challenge' => [
                        [
                            'monster_slug' => 'forge_lord',
                            'name' => 'Seigneur de la Forge',
                            'conditions' => [
                                'solo' => true,
                            ],
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 120,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'life-potion'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_mines_fragment' => [
                'name' => 'Les Fragments — Le Fragment de la Forge',
                'name_translations' => ['en' => 'The Fragments — The Forge Shard'],
                'description' => 'La défaite du Seigneur de la Forge a révélé une fissure dans le mur de sa salle. Un éclat de cristal orangé pulse au fond, irradiant une chaleur ancienne. Récupérez-le.',
                'description_translations' => ['en' => 'The Forge Lord\'s defeat has revealed a crack in the wall of his chamber. An orange crystal shard pulses deep within, radiating ancient heat. Retrieve it.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'mines-profondes',
                            'name' => 'Salle secrète de la Forge',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        ['type' => 'quest', 'count' => 1, 'genericItemSlug' => 'quest-fragment-mines'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Chaîne narrative Acte 2 : Fragment Marais (4 quêtes) ---
            'quest_acte2_marais_brumes' => [
                'name' => 'Les Fragments — Les Brumes s\'épaississent',
                'name_translations' => ['en' => 'The Fragments — The Mists Thicken'],
                'description' => 'Depuis votre contact avec le Cristal d\'Améthyste, une brume surnaturelle semble vous appeler depuis le Marais Brumeux. Morwen la Voyante, qui vit à la lisière du marais, pourrait déchiffrer ces visions.',
                'description_translations' => ['en' => 'Since your contact with the Amethyst Crystal, a supernatural mist seems to call you from the Misty Swamp. Morwen the Seer, who lives on the edge of the swamp, may decipher these visions.'],
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 0, 'name' => 'Morwen la Voyante'],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 40,
                ],
                // prerequisiteQuests set after flush (needs quest_acte1_cristal ID)
            ],
            'quest_acte2_marais_ingredients' => [
                'name' => 'Les Fragments — Remèdes des Profondeurs',
                'name_translations' => ['en' => 'The Fragments — Remedies of the Depths'],
                'description' => 'Morwen a besoin d\'ingrédients spécifiques du marais pour préparer un onguent qui dissipera les brumes enchantées protégeant le cœur du marais. Récoltez des champignons vénéneux et des racines de marais.',
                'description_translations' => ['en' => 'Morwen needs specific swamp ingredients to prepare an ointment that will dispel the enchanted mists protecting the heart of the swamp. Gather poisonous mushrooms and swamp roots.'],
                'requirements' => [
                    'collect' => [
                        'poisonous-mushroom' => 4,
                        'swamp-root' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 130,
                    'gold' => 70,
                    'items' => [
                        ['type' => 'stuff', 'count' => 3, 'genericItemSlug' => 'antidote'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_marais_gardiens' => [
                'name' => 'Les Fragments — Les Gardiens des Eaux Mortes',
                'name_translations' => ['en' => 'The Fragments — Guardians of the Dead Waters'],
                'description' => 'L\'onguent a dissipé une partie de la brume, révélant des créatures anciennes qui protègent le passage vers le cœur du marais. Éliminez-les pour ouvrir la voie.',
                'description_translations' => ['en' => 'The ointment has dispelled part of the mist, revealing ancient creatures that protect the passage to the heart of the swamp. Eliminate them to open the way.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Banshee', 'slug' => 'banshee', 'count' => 3],
                        ['name' => 'Ochu', 'slug' => 'ochu', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 170,
                    'gold' => 90,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'life-potion'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_marais_fragment' => [
                'name' => 'Les Fragments — Le Fragment des Brumes',
                'name_translations' => ['en' => 'The Fragments — The Mist Shard'],
                'description' => 'Les gardiens vaincus, le chemin vers le cœur du marais est libre. Un éclat de cristal bleu-gris scintille au fond d\'un bassin d\'eau stagnante, enveloppé de vapeur glaciale. Récupérez-le.',
                'description_translations' => ['en' => 'With the guardians defeated, the path to the heart of the swamp is clear. A blue-grey crystal shard glimmers at the bottom of a stagnant pool, shrouded in icy vapor. Retrieve it.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'marais-brumeux',
                            'name' => 'Bassin des Brumes éternelles',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        ['type' => 'quest', 'count' => 1, 'genericItemSlug' => 'quest-fragment-marais'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Chaîne narrative Acte 2 : Fragment Montagne (3 quêtes) ---
            'quest_acte2_montagne_echos' => [
                'name' => 'Les Fragments — Les Échos du Sommet',
                'name_translations' => ['en' => 'The Fragments — Echoes from the Summit'],
                'description' => 'Depuis votre contact avec le Cristal d\'Améthyste, des visions de pics enneigés et de vents hurlants vous hantent. Aldric l\'Ancien, un ermite qui vit sur la Crête de Ventombre, pourrait comprendre ces échos.',
                'description_translations' => ['en' => 'Since your contact with the Amethyst Crystal, visions of snowy peaks and howling winds haunt you. Aldric the Elder, a hermit who lives on Stormwind Ridge, may understand these echoes.'],
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 0, 'name' => 'Aldric l\'Ancien'],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 40,
                ],
                // prerequisiteQuests set after flush (needs quest_acte1_cristal ID)
            ],
            'quest_acte2_montagne_gardien' => [
                'name' => 'Les Fragments — Le Gardien des Cimes',
                'name_translations' => ['en' => 'The Fragments — The Peak Guardian'],
                'description' => 'Aldric vous a révélé qu\'un fragment ancien est prisonnier du sommet, gardé par le Dragon ancestral qui sommeille dans sa tanière depuis des siècles. Il faut le vaincre pour accéder au pic sacré.',
                'description_translations' => ['en' => 'Aldric has revealed that an ancient fragment is trapped on the summit, guarded by the Ancestral Dragon that has slumbered in its lair for centuries. You must defeat it to reach the sacred peak.'],
                'requirements' => [
                    'boss_challenge' => [
                        [
                            'monster_slug' => 'dragon',
                            'name' => 'Dragon ancestral',
                            'conditions' => [
                                'solo' => true,
                            ],
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 120,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'life-potion'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_montagne_fragment' => [
                'name' => 'Les Fragments — Le Fragment du Sommet',
                'name_translations' => ['en' => 'The Fragments — The Summit Shard'],
                'description' => 'Le Dragon ancestral est vaincu. Le chemin vers le pic sacré est libre. Un éclat de cristal blanc brille au sommet, battu par les vents éternels. Grimpez et récupérez-le.',
                'description_translations' => ['en' => 'The Ancestral Dragon is defeated. The path to the sacred peak is clear. A white crystal shard shines at the summit, buffeted by eternal winds. Climb up and retrieve it.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'crete-de-ventombre',
                            'name' => 'Pic sacré de Ventombre',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        ['type' => 'quest', 'count' => 1, 'genericItemSlug' => 'quest-fragment-montagne'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Quêtes avancées : enquête et défi boss ---
            'quest_enquete_herboriste' => [
                'name' => 'L\'Herboriste disparue',
                'name_translations' => ['en' => 'The Missing Herbalist'],
                'description' => 'Marie la Herboriste a disparu. Interrogez Claire la Sage, Antoine le Mage et Élise la Guérisseuse pour retrouver sa trace.',
                'description_translations' => ['en' => 'Marie the Herbalist has disappeared. Question Claire the Wise, Antoine the Mage and Élise the Healer to pick up her trail.'],
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 16, 'name' => 'Claire la Sage'],
                        ['pnj_id' => 19, 'name' => 'Antoine le Mage'],
                        ['pnj_id' => 2, 'name' => 'Élise la Guérisseuse'],
                    ],
                ],
                'rewards' => [
                    'gold' => 80,
                    'xp' => 120,
                    'items' => [
                        ['genericItemSlug' => 'life-potion', 'count' => 3],
                    ],
                ],
            ],
            'quest_defi_gardien_foret' => [
                'name' => 'Défi du Gardien',
                'name_translations' => ['en' => 'Guardian\'s Challenge'],
                'description' => 'Prouvez votre valeur en vainquant le Gardien de la Forêt en solo, sans utiliser de soin et en moins de 5 minutes.',
                'description_translations' => ['en' => 'Prove your worth by defeating the Forest Guardian solo, without healing, in under 5 minutes.'],
                'requirements' => [
                    'boss_challenge' => [
                        [
                            'monster_slug' => 'forest_guardian',
                            'name' => 'Gardien de la Forêt',
                            'conditions' => [
                                'no_heal' => true,
                                'solo' => true,
                                'time_limit' => 300,
                            ],
                        ],
                    ],
                ],
                'rewards' => [
                    'gold' => 200,
                    'xp' => 300,
                    'items' => [
                        ['genericItemSlug' => 'life-potion', 'count' => 5],
                    ],
                ],
            ],
            // === Acte 3 : La Convergence (tache 94) ===
            'quest_acte3_appel' => [
                'name' => 'La Convergence — L\'Appel des Fragments',
                'name_translations' => ['en' => 'The Convergence — The Call of the Fragments'],
                'description' => 'Les quatre fragments resonnent dans votre sac, pulsant a l\'unisson. Claire la Sage pourrait savoir ce que cela signifie.',
                'description_translations' => ['en' => 'The four fragments resonate in your bag, pulsing in unison. Claire the Wise may know what this means.'],
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 0, 'name' => 'Claire la Sage'],
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 80,
                ],
                // prerequisiteQuests set after flush (needs all 4 fragment quest IDs)
            ],
            'quest_acte3_gardien' => [
                'name' => 'La Convergence — Le Gardien du Nexus',
                'name_translations' => ['en' => 'The Convergence — Guardian of the Nexus'],
                'description' => 'Les fragments vous guident vers le Nexus de la Convergence. Un gardien ancien protege le coeur du cristal d\'Amethyste. Vous devez le vaincre pour decouvrir la verite.',
                'description_translations' => ['en' => 'The fragments guide you toward the Nexus of Convergence. An ancient guardian protects the heart of the Amethyst Crystal. You must defeat it to uncover the truth.'],
                'requirements' => [
                    'boss_challenge' => [
                        [
                            'monster_slug' => 'convergence_guardian',
                            'name' => 'Gardien de la Convergence',
                            'conditions' => [
                                'solo' => true,
                            ],
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 500,
                    'gold' => 300,
                    'items' => [
                        ['type' => 'gear', 'count' => 1, 'genericItemSlug' => 'convergence-blade'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte3_epilogue' => [
                'name' => 'La Convergence — Epilogue',
                'name_translations' => ['en' => 'The Convergence — Epilogue'],
                'description' => 'Le Gardien est vaincu. Le cristal d\'Amethyste libere son secret ultime, mais son echo ne veut rien dire pour vous. Rapportez-le a Claire la Sage : elle attend cette reponse depuis le premier jour.',
                'description_translations' => ['en' => 'The Guardian is defeated. The Amethyst Crystal releases its final secret, but its echo means nothing to you. Bring it back to Claire the Wise: she has been waiting for this answer since day one.'],
                'requirements' => [
                    // ONB-15 : `pnj_id` recale apres flush par `QuestChainFixtures`.
                    // Le Nexus est un **donjon**, pas une zone : l'objectif visait
                    // une carte sans `Zone`, donc rien.
                    'talk_to' => [
                        [
                            'pnj_id' => 0,
                            'name' => 'Claire la Sage',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 300,
                    'gold' => 500,
                    'items' => [
                        ['type' => 'gear', 'count' => 1, 'genericItemSlug' => 'convergence-amulet'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Quêtes d'événement ---
            'quest_event_lunar_hunt' => [
                'name' => 'Chasse sous la Lune',
                'name_translations' => ['en' => 'Hunt Under the Moon'],
                'description' => 'Pendant le Festival de la Lune, les creatures nocturnes sont plus agitees. Eliminez des monstres pour gagner une recompense exclusive du festival.',
                'description_translations' => ['en' => 'During the Moon Festival, nocturnal creatures grow restless. Defeat monsters to earn an exclusive festival reward.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Zombie', 'slug' => 'zombie', 'count' => 3],
                        ['name' => 'Squelette', 'slug' => 'skeleton', 'count' => 3],
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 75,
                    'items' => [
                        ['genericItemSlug' => 'cosmetic-lunar-crown', 'count' => 1],
                    ],
                ],
                'gameEvent' => 'event_festival_lune',
            ],
            'quest_event_shadow_purge' => [
                'name' => 'Purge des Ombres',
                'name_translations' => ['en' => 'Shadow Purge'],
                'description' => 'La Nuit des Ombres attire des creatures malfaisantes. Repoussez-les et reclamez votre recompense.',
                'description_translations' => ['en' => 'The Night of Shadows draws evil creatures. Drive them back and claim your reward.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Gobelin', 'slug' => 'goblin', 'count' => 4],
                        ['name' => 'Troll', 'slug' => 'troll', 'count' => 1],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        ['genericItemSlug' => 'cosmetic-shadow-cloak', 'count' => 1],
                    ],
                ],
                'gameEvent' => 'event_nuit_ombres',
            ],
            // --- Quêtes de découverte (exploration cachée) ---
            // Plaine de l'Éveil
            'quest_discovery_ancient_stele' => [
                'name' => 'Stèle oubliée',
                'name_translations' => ['en' => 'Forgotten Stele'],
                'description' => 'En traversant les plaines, vous apercevez une stèle gravée de runes anciennes, à demi enfouie dans l\'herbe haute. Approchez-vous pour déchiffrer les inscriptions.',
                'description_translations' => ['en' => 'While crossing the plains, you spot a stele carved with ancient runes, half-buried in tall grass. Step closer to decipher the inscriptions.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'vallons-d-aubepine', 'name' => 'Stèle aux runes anciennes'],
                    ],
                ],
                'rewards' => ['xp' => 50, 'gold' => 30],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'zone_slug' => 'vallons-d-aubepine'],
            ],
            'quest_discovery_forgotten_well' => [
                'name' => 'Le puits des Anciens',
                'name_translations' => ['en' => 'The Well of the Ancients'],
                'description' => 'Un puits en ruine, à peine visible sous les herbes, garde encore l\'eau claire d\'une source souterraine oubliée. Examinez-le de plus près.',
                'description_translations' => ['en' => 'A ruined well, barely visible in the grass, still holds the clear water of a forgotten underground spring. Examine it more closely.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'vallons-d-aubepine', 'name' => 'Puits en ruine'],
                    ],
                ],
                'rewards' => [
                    'xp' => 55,
                    'gold' => 30,
                    'items' => [['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'life-potion']],
                ],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'zone_slug' => 'vallons-d-aubepine'],
            ],
            // Forêt des Murmures
            'quest_discovery_fairy_ring' => [
                'name' => 'Cercle féérique',
                'name_translations' => ['en' => 'Fairy Ring'],
                'description' => 'Un bourdonnement musical flotte dans l\'air. Des lucioles dansent en cercle autour d\'un anneau de champignons lumineux. Approchez-vous du centre du cercle.',
                'description_translations' => ['en' => 'A musical hum drifts through the air. Fireflies dance in a circle around a ring of glowing mushrooms. Step into the centre of the ring.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'foret-des-murmures', 'name' => 'Centre du cercle féérique'],
                    ],
                ],
                'rewards' => ['xp' => 70, 'gold' => 45],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'zone_slug' => 'foret-des-murmures'],
            ],
            'quest_discovery_hollow_oak' => [
                'name' => 'Le chêne millénaire',
                'name_translations' => ['en' => 'The Ancient Oak'],
                'description' => 'Un chêne immense et creux se dresse devant vous. Des gravures anciennes ornent l\'intérieur de son tronc. Explorez la cavité pour découvrir ce qu\'elle recèle.',
                'description_translations' => ['en' => 'A massive hollow oak stands before you. Ancient carvings adorn the inside of its trunk. Explore the cavity to uncover what it hides.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'foret-des-murmures', 'name' => 'Intérieur du chêne creux'],
                    ],
                ],
                'rewards' => ['xp' => 75, 'gold' => 50],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'zone_slug' => 'foret-des-murmures'],
            ],
            // Marais Brumeux
            'quest_discovery_sunken_altar' => [
                'name' => 'Autel englouti',
                'name_translations' => ['en' => 'Sunken Altar'],
                'description' => 'Sous les eaux stagnantes, vous distinguez un autel de pierre couvert de mousse et de symboles effacés. Pataugez jusqu\'à lui pour l\'examiner.',
                'description_translations' => ['en' => 'Beneath the stagnant waters, you make out a stone altar covered in moss and faded symbols. Wade out to it to examine it.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'marais-brumeux', 'name' => 'Autel de pierre immergé'],
                    ],
                ],
                'rewards' => ['xp' => 80, 'gold' => 55],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'zone_slug' => 'marais-brumeux'],
            ],
            'quest_discovery_phospho_grotto' => [
                'name' => 'Grotte phosphorescente',
                'name_translations' => ['en' => 'Phosphorescent Grotto'],
                'description' => 'Une lueur bleu-vert émane d\'une anfractuosité dans la roche. La grotte est tapissée de mousse luminescente. Explorez-la jusqu\'au fond.',
                'description_translations' => ['en' => 'A blue-green glow emanates from a crevice in the rock. The grotto is lined with luminescent moss. Explore it all the way to the back.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'mines-profondes', 'name' => 'Fond de la grotte lumineuse'],
                    ],
                ],
                'rewards' => ['xp' => 85, 'gold' => 50],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'zone_slug' => 'mines-profondes'],
            ],
            // Collines Venteuses
            'quest_discovery_wind_shrine' => [
                'name' => 'Sanctuaire éolien',
                'name_translations' => ['en' => 'Wind Shrine'],
                'description' => 'Le vent siffle entre des pierres dressées sur la colline. Un ancien sanctuaire dédié aux esprits du vent. Approchez-vous du menhir central.',
                'description_translations' => ['en' => 'The wind whistles between standing stones on the hill. An ancient shrine dedicated to the wind spirits. Approach the central menhir.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'crete-de-ventombre', 'name' => 'Menhir central du sanctuaire'],
                    ],
                ],
                'rewards' => ['xp' => 90, 'gold' => 60],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'zone_slug' => 'crete-de-ventombre'],
            ],
            // Lande d'Ombre
            'quest_discovery_shadow_obelisk' => [
                'name' => 'Obélisque d\'ombre',
                'name_translations' => ['en' => 'Shadow Obelisk'],
                'description' => 'Un obélisque noir se dresse dans la lande, pulsant d\'une énergie sombre. Des inscriptions décrivent un ancien rituel de protection. Déchiffrez-les.',
                'description_translations' => ['en' => 'A black obelisk stands in the moor, pulsing with dark energy. Inscriptions describe an ancient protective ritual. Decipher them.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'marais-brumeux', 'name' => 'Obélisque aux inscriptions sombres'],
                    ],
                ],
                'rewards' => ['xp' => 100, 'gold' => 70],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'zone_slug' => 'marais-brumeux'],
            ],
            // --- Quêtes de découverte (exploration standard multi-points) ---
            'quest_discovery_cartographer' => [
                'name' => 'Cartographe des terres oubliées',
                'name_translations' => ['en' => 'Cartographer of the Forgotten Lands'],
                'description' => 'La cartographe du village vous demande de relever cinq points de repère dans chaque zone pour compléter sa carte des terres oubliées.',
                'description_translations' => ['en' => 'The village cartographer asks you to mark five landmarks in each zone to complete her map of the forgotten lands.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'vallons-d-aubepine', 'name' => 'Cairn des Vallons'],
                        ['zone_slug' => 'foret-des-murmures', 'name' => 'Arbre-signal de la Forêt'],
                        ['zone_slug' => 'marais-brumeux', 'name' => 'Balise du Marais'],
                        ['zone_slug' => 'crete-de-ventombre', 'name' => 'Vigie de la Crête'],
                        ['zone_slug' => 'dunes-d-ambre', 'name' => 'Tour de guet des Dunes'],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 120,
                    'items' => [['type' => 'stuff', 'count' => 3, 'genericItemSlug' => 'life-potion']],
                ],
            ],
            'quest_discovery_sacred_sites' => [
                'name' => 'Pèlerinage des sites sacrés',
                'name_translations' => ['en' => 'Pilgrimage of the Sacred Sites'],
                'description' => 'Un érudit vous parle de trois anciens sites sacrés disseminés entre les Collines et la Lande. Retrouvez-les pour percer les mystères du passé.',
                'description_translations' => ['en' => 'A scholar tells you of three ancient sacred sites scattered between the Hills and the Moor. Find them to unlock the mysteries of the past.'],
                'requirements' => [
                    'explore' => [
                        ['zone_slug' => 'crete-de-ventombre', 'name' => 'Dolmen de la Crête'],
                        ['zone_slug' => 'dunes-d-ambre', 'name' => 'Cercle de pierres des Dunes'],
                        ['zone_slug' => 'mines-profondes', 'name' => 'Crypte ancienne'],
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 90,
                    'items' => [['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'healing-potion-small']],
                ],
            ],
            // --- Quêtes de zone secondaires ---
            'quest_zone_foret_meute' => [
                'name' => 'La meute affamée',
                'name_translations' => ['en' => 'The Hungry Pack'],
                'description' => 'Diane signale que les loups deviennent agressifs et s\'approchent des sentiers. Éliminez la meute et leur chef pour sécuriser la forêt.',
                'description_translations' => ['en' => 'Diane reports that wolves are growing aggressive and approaching the trails. Eliminate the pack and their leader to secure the forest.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Loup', 'slug' => 'wolf', 'count' => 3],
                        ['name' => 'Loup alpha', 'slug' => 'alpha_wolf', 'count' => 1],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 60,
                    'items' => [
                        ['type' => 'gear', 'count' => 1, 'genericItemSlug' => 'bow'],
                    ],
                ],
            ],
            'quest_zone_foret_venin' => [
                'name' => 'Sentinelle contre le venin',
                'name_translations' => ['en' => 'Sentinel Against Venom'],
                'description' => 'Des serpents venimeux et des scorpions infestent les chemins près de l\'entrée de la forêt. Sylvain demande de l\'aide pour les éliminer.',
                'description_translations' => ['en' => 'Venomous snakes and scorpions infest the paths near the forest entrance. Sylvain asks for help eliminating them.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Serpent venimeux', 'slug' => 'venom_snake', 'count' => 3],
                        ['name' => 'Scorpion', 'slug' => 'scorpion', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 70,
                    'gold' => 40,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'antidote'],
                    ],
                ],
            ],
            'quest_zone_mines_automates' => [
                'name' => 'Automates déréglés',
                'name_translations' => ['en' => 'Malfunctioning Automatons'],
                'description' => 'Les automates des galeries profondes sont devenus incontrôlables et menacent les mineurs. Durgan vous demande d\'en détruire quelques-uns pour rouvrir les passages.',
                'description_translations' => ['en' => 'The automatons in the deep galleries have become uncontrollable and threaten the miners. Durgan asks you to destroy a few to reopen the passages.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Automate rouillé', 'slug' => 'rusty_automaton', 'count' => 3],
                        ['name' => 'Golem de pierre', 'slug' => 'stone_golem', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 70,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'ore-silver'],
                    ],
                ],
            ],
            'quest_zone_marais_prime' => [
                'name' => 'Prime sur les morts-vivants',
                'name_translations' => ['en' => 'Bounty on the Undead'],
                'description' => 'Bran offre une récompense pour l\'élimination de morts-vivants et de golems champignon qui envahissent les sentiers du marais.',
                'description_translations' => ['en' => 'Bran offers a reward for eliminating undead and mushroom golems invading the swamp paths.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Zombie', 'slug' => 'zombie', 'count' => 4],
                        ['name' => 'Golem champignon', 'slug' => 'mushroom_golem', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 90,
                    'gold' => 55,
                    'items' => [
                        ['type' => 'stuff', 'count' => 3, 'genericItemSlug' => 'antidote'],
                    ],
                ],
            ],
            'quest_zone_marais_appat' => [
                'name' => 'Appât empoisonné',
                'name_translations' => ['en' => 'Poisoned Bait'],
                'description' => 'Oswald prépare un appât spécial pour attirer les gros poissons du marais. Il a besoin de champignons vénéneux que l\'on trouve dans les zones humides.',
                'description_translations' => ['en' => 'Oswald is preparing special bait to lure the swamp\'s largest fish. He needs poisonous mushrooms found in wetlands.'],
                'requirements' => [
                    'collect' => [
                        'poisonous-mushroom' => 5,
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 35,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'healing-potion-small'],
                    ],
                ],
            ],
            'quest_zone_montagne_aerienne' => [
                'name' => 'Menace aérienne',
                'name_translations' => ['en' => 'Aerial Threat'],
                'description' => 'Kaelen rapporte que les griffons et gargouilles bloquent les sentiers d\'altitude, empêchant toute reconnaissance. Éliminez-les pour rouvrir les voies.',
                'description_translations' => ['en' => 'Kaelen reports that griffins and gargoyles are blocking the high-altitude trails, preventing reconnaissance. Eliminate them to reopen the routes.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Griffon', 'slug' => 'griffin', 'count' => 3],
                        ['name' => 'Gargouille', 'slug' => 'gargoyle', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 90,
                    'items' => [
                        ['type' => 'gear', 'count' => 1, 'genericItemSlug' => 'silver-amulet'],
                    ],
                ],
            ],
            // --- Quetes de faction (reputation) ---
            'quest_faction_mages_intro' => [
                'name' => 'Échos arcaniques',
                'name_translations' => ['en' => 'Arcane Echoes'],
                'description' => 'Antoine le Mage, émissaire du Cercle des Mages, étudie les élémentaires de feu et les feux follets pour ses recherches. Rapportez des preuves de leur élimination pour gagner la confiance du Cercle.',
                'description_translations' => ['en' => 'Antoine the Mage, emissary of the Circle of Mages, studies fire elementals and will-o\'-the-wisps for his research. Bring proof of their elimination to gain the Circle\'s trust.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Élémentaire de feu', 'slug' => 'fire_elemental', 'count' => 2],
                        ['name' => 'Feu follet', 'slug' => 'will_o_wisp', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 60,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'crafted-potion-base'],
                    ],
                    'reputation' => [
                        ['faction_slug' => 'mages', 'amount' => 300],
                    ],
                ],
            ],
            'quest_faction_chevaliers_intro' => [
                'name' => 'Serment du Chevalier',
                'name_translations' => ['en' => 'The Knight\'s Oath'],
                'description' => 'Sébastien le Chevalier teste la valeur des aventuriers au nom de l\'Ordre des Chevaliers. Purgez les morts-vivants qui souillent nos terres pour prouver votre honneur.',
                'description_translations' => ['en' => 'Sebastien the Knight tests adventurers\' worth in the name of the Order of Knights. Purge the undead defiling our lands to prove your honor.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Squelette', 'slug' => 'skeleton', 'count' => 3],
                        ['name' => 'Zombie', 'slug' => 'zombie', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 110,
                    'gold' => 70,
                    'items' => [
                        ['type' => 'gear', 'count' => 1, 'genericItemSlug' => 'wooden-shield'],
                    ],
                    'reputation' => [
                        ['faction_slug' => 'chevaliers', 'amount' => 300],
                    ],
                ],
            ],
            'quest_faction_ombres_intro' => [
                'name' => 'Dans l\'ombre des gobelins',
                'name_translations' => ['en' => 'In the Shadow of the Goblins'],
                'description' => 'Aurélie l\'Archère travaille discrètement pour la Confrérie des Ruelles. Un camp de gobelins espionne les routes marchandes — éliminez leurs éclaireurs avant qu\'ils ne deviennent une menace.',
                'description_translations' => ['en' => 'Aurelie the Archer works discreetly for the Brotherhood of the Alleys. A goblin camp is spying on the trade routes — eliminate their scouts before they become a threat.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Gobelin', 'slug' => 'goblin', 'count' => 4],
                    ],
                ],
                'rewards' => [
                    'xp' => 90,
                    'gold' => 100,
                    'items' => [
                        ['type' => 'stuff', 'count' => 3, 'genericItemSlug' => 'healing-potion-small'],
                    ],
                    'reputation' => [
                        ['faction_slug' => 'ombres', 'amount' => 300],
                    ],
                ],
            ],
            'quest_faction_marchands_intro' => [
                'name' => 'Routes sûres pour la Guilde',
                'name_translations' => ['en' => 'Safe Roads for the Guild'],
                'description' => 'Chloé l\'Exploratrice cartographie les routes marchandes pour la Guilde des Marchands. Les araignées et les rats géants menacent les convois — débarrassez les sentiers pour rassurer les caravanes.',
                'description_translations' => ['en' => 'Chloe the Explorer maps the trade routes for the Merchants Guild. Spiders and giant rats threaten the convoys — clear the trails to reassure the caravans.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Araignée', 'slug' => 'spider', 'count' => 3],
                        ['name' => 'Rat géant', 'slug' => 'giant_rat', 'count' => 3],
                    ],
                ],
                'rewards' => [
                    'xp' => 90,
                    'gold' => 120,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'scroll-teleport'],
                    ],
                    'reputation' => [
                        ['faction_slug' => 'marchands', 'amount' => 300],
                    ],
                ],
            ],
            // --- Quetes a choix moral (consequences de reputation opposees) ---
            // --- Quetes de chasse supplementaires ---
            'quest_hunt_scorpions' => [
                'name' => 'Fléau des sables',
                'name_translations' => ['en' => 'Scourge of the Sands'],
                'description' => 'Les scorpions venimeux prolifèrent aux abords du désert et menacent les caravanes de passage. Chassez-en quelques-uns pour sécuriser la piste.',
                'description_translations' => ['en' => 'Venomous scorpions are proliferating at the desert\'s edge and threatening passing caravans. Hunt a few to secure the trail.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Scorpion', 'slug' => 'scorpion', 'count' => 4],
                    ],
                ],
                'rewards' => [
                    'xp' => 85,
                    'gold' => 55,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'antidote'],
                    ],
                ],
            ],
            'quest_hunt_gargoyles' => [
                'name' => 'Les veilleurs de pierre',
                'name_translations' => ['en' => 'The Stone Watchers'],
                'description' => 'D\'anciennes gargouilles se sont réveillées et attaquent les pèlerins dans les ruines. Mettez-les hors d\'état de nuire.',
                'description_translations' => ['en' => 'Ancient gargoyles have awakened and are attacking pilgrims in the ruins. Put them out of action.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Gargouille', 'slug' => 'gargoyle', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 130,
                    'gold' => 90,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'healing-potion-small'],
                    ],
                ],
            ],
            'quest_moral_contrebandier' => [
                'name' => 'Le contrebandier démasqué',
                'name_translations' => ['en' => 'The Unmasked Smuggler'],
                'description' => 'Vous avez surpris un contrebandier qui fournit les Ombres en artéfacts volés dans les caravanes marchandes. Il vous propose une part du butin pour le laisser filer. Dénoncer ou se taire ?',
                'description_translations' => ['en' => 'You caught a smuggler supplying the Shadows with artifacts stolen from merchant caravans. He offers you a share of the loot to let him go. Denounce him or stay silent?'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Gobelin', 'slug' => 'goblin', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 90,
                    'gold' => 40,
                ],
                'choiceOutcome' => [
                    [
                        'key' => 'denounce',
                        'label' => 'Dénoncer aux Marchands',
                        'bonusRewards' => [
                            'gold' => 30,
                            'reputation' => [
                                ['faction_slug' => 'marchands', 'amount' => 200],
                                ['faction_slug' => 'ombres', 'amount' => -100],
                            ],
                        ],
                    ],
                    [
                        'key' => 'accept_bribe',
                        'label' => 'Accepter la part du butin',
                        'bonusRewards' => [
                            'gold' => 120,
                            'items' => [
                                ['genericItemSlug' => 'scroll-teleport', 'count' => 2],
                            ],
                            'reputation' => [
                                ['faction_slug' => 'ombres', 'amount' => 150],
                                ['faction_slug' => 'marchands', 'amount' => -100],
                            ],
                        ],
                    ],
                ],
            ],
            'quest_moral_prisonnier' => [
                'name' => 'Le prisonnier condamné',
                'name_translations' => ['en' => 'The Condemned Prisoner'],
                'description' => 'Un déserteur des Chevaliers est enchaîné dans les geôles du village, accusé d\'avoir volé pour nourrir un orphelinat. Les Chevaliers exigent l\'exécution, les Ombres vous offrent une fortune pour l\'évader.',
                'description_translations' => ['en' => 'A Knight deserter is chained in the village dungeons, accused of stealing to feed an orphanage. The Knights demand execution; the Shadows offer you a fortune to free him.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'village-de-lumiere',
                            'name' => 'Geôles du village',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 110,
                    'gold' => 25,
                ],
                'choiceOutcome' => [
                    [
                        'key' => 'uphold_justice',
                        'label' => 'Respecter la sentence',
                        'bonusRewards' => [
                            'gold' => 40,
                            'items' => [
                                ['genericItemSlug' => 'wooden-shield', 'count' => 1],
                            ],
                            'reputation' => [
                                ['faction_slug' => 'chevaliers', 'amount' => 250],
                                ['faction_slug' => 'ombres', 'amount' => -75],
                            ],
                        ],
                    ],
                    [
                        'key' => 'free_prisoner',
                        'label' => 'Libérer le prisonnier',
                        'bonusRewards' => [
                            'gold' => 150,
                            'items' => [
                                ['genericItemSlug' => 'healing-potion-small', 'count' => 3],
                            ],
                            'reputation' => [
                                ['faction_slug' => 'ombres', 'amount' => 200],
                                ['faction_slug' => 'chevaliers', 'amount' => -150],
                            ],
                        ],
                    ],
                ],
            ],
            'quest_moral_grimoire' => [
                'name' => 'Le grimoire interdit',
                'name_translations' => ['en' => 'The Forbidden Grimoire'],
                'description' => 'Un vieux grimoire de magie noire a refait surface au fond d\'une grotte. L\'Ordre des Mages le veut pour l\'étudier à l\'abri, la Guilde des Marchands offre une somme pour l\'acquérir au marché noir.',
                'description_translations' => ['en' => 'An old grimoire of dark magic has resurfaced at the bottom of a cave. The Order of Mages wants it for safe study; the Merchants Guild offers a sum to acquire it on the black market.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Spectre', 'slug' => 'specter', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 130,
                    'gold' => 20,
                ],
                'choiceOutcome' => [
                    [
                        'key' => 'hand_to_mages',
                        'label' => 'Remettre à l\'Ordre des Mages',
                        'bonusRewards' => [
                            'xp' => 80,
                            'items' => [
                                ['genericItemSlug' => 'energy-potion-small', 'count' => 3],
                            ],
                            'reputation' => [
                                ['faction_slug' => 'mages', 'amount' => 250],
                                ['faction_slug' => 'marchands', 'amount' => -50],
                            ],
                        ],
                    ],
                    [
                        'key' => 'sell_black_market',
                        'label' => 'Vendre au marché noir',
                        'bonusRewards' => [
                            'gold' => 250,
                            'reputation' => [
                                ['faction_slug' => 'marchands', 'amount' => 150],
                                ['faction_slug' => 'mages', 'amount' => -200],
                                ['faction_slug' => 'ombres', 'amount' => 50],
                            ],
                        ],
                    ],
                ],
            ],
            'quest_moral_ferme_brulee' => [
                'name' => 'La ferme incendiée',
                'name_translations' => ['en' => 'The Burned Farm'],
                'description' => 'Une ferme isolée a été incendiée par des gobelins. La veuve du fermier implore de l\'aide pour reconstruire, mais le seigneur local refuse de payer et préfère envoyer les Chevaliers punir les coupables.',
                'description_translations' => ['en' => 'An isolated farm has been burned by goblins. The farmer\'s widow begs for help to rebuild, but the local lord refuses to pay and prefers to send the Knights to punish the culprits.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Gobelin', 'slug' => 'goblin', 'count' => 4],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 30,
                ],
                'choiceOutcome' => [
                    [
                        'key' => 'help_widow',
                        'label' => 'Financer la reconstruction',
                        'bonusRewards' => [
                            'xp' => 60,
                            'reputation' => [
                                ['faction_slug' => 'marchands', 'amount' => 100],
                                ['faction_slug' => 'chevaliers', 'amount' => -25],
                            ],
                        ],
                    ],
                    [
                        'key' => 'report_to_knights',
                        'label' => 'Rapporter aux Chevaliers',
                        'bonusRewards' => [
                            'gold' => 100,
                            'items' => [
                                ['genericItemSlug' => 'leather-boots', 'count' => 1],
                            ],
                            'reputation' => [
                                ['faction_slug' => 'chevaliers', 'amount' => 200],
                            ],
                        ],
                    ],
                ],
            ],
            'quest_moral_relique' => [
                'name' => 'La relique du temple oublié',
                'name_translations' => ['en' => 'The Relic of the Forgotten Temple'],
                'description' => 'Vous avez trouvé une relique sacrée dans un temple oublié. Les Mages souhaitent la percer à jour, les Chevaliers la veulent pour leur chapelle, et un antiquaire des Marchands propose une petite fortune pour l\'acquérir.',
                'description_translations' => ['en' => 'You found a sacred relic in a forgotten temple. The Mages wish to study it, the Knights want it for their chapel, and a Merchant antiquarian offers a small fortune to acquire it.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'foret-des-murmures',
                            'name' => 'Temple oublié',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 140,
                    'gold' => 35,
                ],
                'choiceOutcome' => [
                    [
                        'key' => 'give_mages',
                        'label' => 'Offrir aux Mages',
                        'bonusRewards' => [
                            'xp' => 100,
                            'items' => [
                                ['genericItemSlug' => 'energy-potion-small', 'count' => 2],
                            ],
                            'reputation' => [
                                ['faction_slug' => 'mages', 'amount' => 200],
                                ['faction_slug' => 'chevaliers', 'amount' => -50],
                            ],
                        ],
                    ],
                    [
                        'key' => 'give_knights',
                        'label' => 'Offrir aux Chevaliers',
                        'bonusRewards' => [
                            'items' => [
                                ['genericItemSlug' => 'wooden-shield', 'count' => 1],
                            ],
                            'reputation' => [
                                ['faction_slug' => 'chevaliers', 'amount' => 200],
                                ['faction_slug' => 'mages', 'amount' => -50],
                            ],
                        ],
                    ],
                    [
                        'key' => 'sell_antiquarian',
                        'label' => 'Vendre à l\'antiquaire',
                        'bonusRewards' => [
                            'gold' => 200,
                            'reputation' => [
                                ['faction_slug' => 'marchands', 'amount' => 150],
                                ['faction_slug' => 'mages', 'amount' => -100],
                                ['faction_slug' => 'chevaliers', 'amount' => -100],
                            ],
                        ],
                    ],
                ],
            ],

            // ── Defend quests ──
            'quest_defend_forest' => [
                'name' => 'Défense de la Forêt Sombre',
                'name_translations' => ['en' => 'Defense of the Dark Forest'],
                'description' => 'Des trolls envahissent la Forêt Sombre ! Repoussez-les en éliminant ceux qui rôdent dans la zone.',
                'description_translations' => ['en' => 'Trolls are invading the Dark Forest! Push them back by eliminating those roaming the area.'],
                'requirements' => [
                    'defend' => [
                        ['monster_slug' => 'troll', 'count' => 3, 'map_id' => 3, 'name' => 'Troll', 'zone_name' => 'Forêt Sombre'],
                    ],
                ],
                'rewards' => [
                    'gold' => 80,
                    'xp' => 150,
                ],
            ],
            'quest_defend_mines' => [
                'name' => 'Sauver les Mines Profondes',
                'name_translations' => ['en' => 'Save the Deep Mines'],
                'description' => 'Les golems de cristal ont pris le contrôle d\'une galerie. Défendez l\'entrée et éliminez-les avant qu\'ils ne s\'étendent davantage.',
                'description_translations' => ['en' => 'Crystal golems have taken control of a gallery. Defend the entrance and eliminate them before they spread further.'],
                'requirements' => [
                    'defend' => [
                        ['monster_slug' => 'crystal_golem', 'count' => 4, 'map_id' => 4, 'name' => 'Golem de cristal', 'zone_name' => 'Mines Profondes'],
                    ],
                ],
                'rewards' => [
                    'gold' => 100,
                    'xp' => 200,
                    'items' => [
                        ['genericItemSlug' => 'iron-ore', 'count' => 5],
                    ],
                ],
            ],

            // ── Escort quests ──
            'quest_escort_merchant' => [
                'name' => 'Escorter le marchand itinérant',
                'name_translations' => ['en' => 'Escort the Wandering Merchant'],
                'description' => 'Un marchand itinérant a besoin d\'être escorté jusqu\'au Village. Accompagnez-le en vous rendant au point de rendez-vous.',
                'description_translations' => ['en' => 'A wandering merchant needs to be escorted to the Village. Accompany him by reaching the rendezvous point.'],
                'requirements' => [
                    'escort' => [
                        ['destination_zone_slug' => 'village-de-lumiere', 'name' => 'Amener le marchand au Village'],
                    ],
                ],
                'rewards' => [
                    'gold' => 60,
                    'xp' => 100,
                ],
            ],
            'quest_escort_refugee' => [
                'name' => 'Réfugiés du Marais',
                'name_translations' => ['en' => 'Refugees from the Swamp'],
                'description' => 'Des villageois se sont perdus dans le Marais. Guidez-les jusqu\'à la sortie en atteignant le point d\'évacuation en Montagne.',
                'description_translations' => ['en' => 'Villagers are lost in the Swamp. Guide them out by reaching the evacuation point in the Mountains.'],
                'requirements' => [
                    'escort' => [
                        ['destination_zone_slug' => 'crete-de-ventombre', 'name' => 'Guider les réfugiés sur la Crête'],
                    ],
                ],
                'rewards' => [
                    'gold' => 90,
                    'xp' => 180,
                    'reputation' => [
                        ['faction_slug' => 'chevaliers', 'amount' => 100],
                    ],
                ],
            ],

            // ── Puzzle quests ──
            'quest_puzzle_sphinx' => [
                'name' => 'L\'Énigme du Sphinx de Pierre',
                'name_translations' => ['en' => 'The Stone Sphinx\'s Riddle'],
                'description' => 'Un sphinx de pierre bloque le passage dans les Mines. Il pose une énigme : "Je suis née du feu, façonnée par l\'eau, et je dors dans la terre. Qui suis-je ?" Parlez-lui et donnez la bonne réponse.',
                'description_translations' => ['en' => 'A stone sphinx blocks the passage in the Mines. It poses a riddle: "I was born of fire, shaped by water, and I sleep in the earth. Who am I?" Speak to it and give the right answer.'],
                'requirements' => [
                    'puzzle' => [
                        ['pnj_id' => 23, 'answer_key' => 'obsidienne', 'name' => 'Résoudre l\'énigme du Sphinx'],
                    ],
                ],
                'rewards' => [
                    'gold' => 50,
                    'xp' => 120,
                ],
            ],
            'quest_puzzle_ancient_runes' => [
                'name' => 'Les Runes Anciennes',
                'name_translations' => ['en' => 'The Ancient Runes'],
                'description' => 'Claire la Sage a découvert d\'anciennes runes dans un grimoire. Elle vous demande : "Quel est l\'élément qui nourrit la lumière et consume l\'ombre ?" Trouvez la réponse.',
                'description_translations' => ['en' => 'Claire the Sage has discovered ancient runes in a grimoire. She asks you: "What element nourishes the light and consumes the shadow?" Find the answer.'],
                'requirements' => [
                    'puzzle' => [
                        ['pnj_id' => 16, 'answer_key' => 'feu', 'name' => 'Déchiffrer les runes anciennes'],
                    ],
                ],
                'rewards' => [
                    'gold' => 40,
                    'xp' => 100,
                    'items' => [
                        ['genericItemSlug' => 'scroll-teleport', 'count' => 1],
                    ],
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // Le cercle de Lumiere — chaine des trois zones de depart
            // ══════════════════════════════════════════════════════════════
            //
            // L'arc `intro` apprend a se battre ; celui-ci apprend le **lieu**.
            // Une etape par zone, chacune sur ce que la zone a de propre : les
            // jardins du temple au village, la riviere et le vivier nocturne en
            // foret, les bassins et la patrouille d'automates dans la mine.
            //
            // Chaque objectif cite une ressource ou une creature reellement
            // presente dans la zone visee (cf. `config/game/zones/world_1.yaml`) :
            // un slug errone laisserait une quete acceptable dont l'objectif ne
            // se valide jamais.
            'quest_cercle_jardins' => [
                'name' => 'Le cercle de Lumière — Les jardins du temple',
                'name_translations' => ['en' => 'The Circle of Light — The Temple Gardens'],
                'description' => 'Fioline entretient les carrés du temple depuis trente ans. Elle vous laisse cueillir, à condition que vous appreniez à le faire proprement : du thym le long du mur sud, de la lavande sur la bordure.',
                'description_translations' => ['en' => 'Fioline has tended the temple beds for thirty years. She lets you forage, provided you learn to do it properly: thyme along the south wall, lavender on the border.'],
                'requirements' => [
                    'collect' => [
                        'plant-thyme' => 3,
                        'plant-lavender' => 2,
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 30,
                    'items' => [
                        ['type' => 'stuff', 'count' => 1, 'genericItemSlug' => 'crafted-potion-base'],
                    ],
                ],
                'storyArc' => 'cercle_lumiere',
                'arcOrder' => 1,
            ],
            'quest_cercle_foret' => [
                'name' => 'Le cercle de Lumière — Ce qui marche entre les arbres',
                'name_translations' => ['en' => 'The Circle of Light — What Walks Between the Trees'],
                'description' => 'Morrigane veille les lisières et ne s\'en cache pas : après le crépuscule, la forêt appartient à autre chose. Montez jusqu\'aux rapides, ramenez un saumon, et débarrassez le sentier des ossements qui s\'y relèvent.',
                'description_translations' => ['en' => 'Morrigane watches the forest edge and makes no secret of it: after dusk, the woods belong to something else. Climb to the rapids, bring back a salmon, and clear the path of the bones that rise there.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'foret-des-murmures',
                            'name' => 'Forêt des Murmures',
                        ],
                    ],
                    'collect' => [
                        'fish-salmon' => 1,
                    ],
                    'monsters' => [
                        [
                            'name' => 'Squelette',
                            'slug' => 'skeleton',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 60,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'healing-potion-small'],
                    ],
                ],
                'storyArc' => 'cercle_lumiere',
                'arcOrder' => 2,
                // prerequisiteQuests set after flush
            ],
            'quest_cercle_mines' => [
                'name' => 'Le cercle de Lumière — Sous le carreau',
                'name_translations' => ['en' => 'The Circle of Light — Below the Pithead'],
                'description' => 'Brida vous nourrit avant la descente, Kolm marche devant avec la lampe. Ce qu\'ils demandent en retour est simple : que la patrouille d\'automates cesse de tourner dans les galeries centrales, et qu\'on sache enfin ce qui vit dans les bassins noyés.',
                'description_translations' => ['en' => 'Brida feeds you before the descent, Kolm walks ahead with the lamp. What they ask in return is simple: that the automaton patrol stop circling the central galleries, and that someone finally learn what lives in the flooded pools.'],
                'requirements' => [
                    'explore' => [
                        [
                            'zone_slug' => 'mines-profondes',
                            'name' => 'Mines Profondes',
                        ],
                    ],
                    'collect' => [
                        'fish-electric-eel' => 1,
                    ],
                    'monsters' => [
                        [
                            'name' => 'Automate rouillé',
                            'slug' => 'rusty_automaton',
                            'count' => 3,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 110,
                    'items' => [
                        ['type' => 'stuff', 'count' => 1, 'genericItemSlug' => 'pickaxe-bronze'],
                    ],
                ],
                'storyArc' => 'cercle_lumiere',
                'arcOrder' => 3,
                // prerequisiteQuests set after flush
            ],

            // Journalieres adossees aux filons et populations ajoutes ci-dessus :
            // le pool `recolte` n'avait que la menthe et le cuivre, le pool
            // `combat` aucune cible souterraine.
            'daily_collect_thyme' => [
                'name' => 'Le carré du temple',
                'name_translations' => ['en' => 'The Temple Bed'],
                'description' => 'Fioline manque de thym pour les remèdes de la semaine. Le carré est juste derrière le temple.',
                'description_translations' => ['en' => 'Fioline is short of thyme for the week\'s remedies. The bed is right behind the temple.'],
                'requirements' => [
                    'collect' => [
                        'plant-thyme' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 25,
                    'gold' => 15,
                ],
                'isDaily' => true,
                'dailyPool' => 'recolte',
            ],
            'daily_kill_bats' => [
                'name' => 'Le plafond bouge',
                'name_translations' => ['en' => 'The Ceiling Moves'],
                'description' => 'Les chauves-souris ont repris les premières galeries et personne ne veut plus y porter de lampe. Dégagez le passage.',
                'description_translations' => ['en' => 'Bats have retaken the first galleries and nobody will carry a lamp there anymore. Clear the way.'],
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Chauve-souris', 'slug' => 'bat', 'count' => 4],
                    ],
                ],
                'rewards' => [
                    'xp' => 35,
                    'gold' => 25,
                ],
                'isDaily' => true,
                'dailyPool' => 'combat',
            ],
        ];

        foreach ($quests as $key => $data) {
            $quest = new Quest();
            $quest->setName($data['name']);
            if (isset($data['name_translations']) && is_array($data['name_translations'])) {
                $quest->setNameTranslations($data['name_translations']);
            }
            $quest->setDescription($data['description']);
            if (isset($data['description_translations']) && is_array($data['description_translations'])) {
                $quest->setDescriptionTranslations($data['description_translations']);
            }
            $quest->setRequirements($data['requirements']);
            $quest->setRewards($data['rewards']);
            if (isset($data['prerequisiteQuests'])) {
                $quest->setPrerequisiteQuests($data['prerequisiteQuests']);
            }
            if (isset($data['choiceOutcome'])) {
                $quest->setChoiceOutcome($data['choiceOutcome']);
            }
            if (isset($data['isDaily'])) {
                $quest->setIsDaily($data['isDaily']);
            }
            if (isset($data['dailyPool'])) {
                $quest->setDailyPool($data['dailyPool']);
            }
            if (isset($data['isHidden'])) {
                $quest->setIsHidden($data['isHidden']);
            }
            if (isset($data['triggerCondition'])) {
                $quest->setTriggerCondition($data['triggerCondition']);
            }
            if (isset($data['storyArc'])) {
                $quest->setStoryArc($data['storyArc']);
            }
            if (isset($data['arcOrder'])) {
                $quest->setArcOrder($data['arcOrder']);
            }
            if (isset($data['minRenownScore'])) {
                $quest->setMinRenownScore($data['minRenownScore']);
            }
            if (isset($data['gameEvent'])) {
                $quest->setGameEvent($this->getReference($data['gameEvent'], GameEvent::class));
            }
            $quest->setCreatedAt(new \DateTime());
            $quest->setUpdatedAt(new \DateTime());

            $manager->persist($quest);
            $this->addReference($key, $quest);
        }

        $manager->flush();

        // Set prerequisite quest IDs (needs IDs from flush)
        /** @var Quest $chainGuard1 */
        $chainGuard1 = $this->getReference('quest_chain_guard_1', Quest::class);
        /** @var Quest $chainGuard2 */
        $chainGuard2 = $this->getReference('quest_chain_guard_2', Quest::class);
        /** @var Quest $chainGuard3 */
        $chainGuard3 = $this->getReference('quest_chain_guard_3', Quest::class);

        $chainGuard2->setPrerequisiteQuests([$chainGuard1->getId()]);
        $chainGuard3->setPrerequisiteQuests([$chainGuard2->getId()]);

        // Acte 4 (tache 128c) : la chaine suit le graphe, du sel au sud a la
        // glace au nord. Chaque etape ouvre la suivante.
        $acte4 = [];
        foreach (['appel_du_sel', 'ce_qui_rampe', 'cite_sous_le_sable', 'par_dela_la_crete', 'le_silence'] as $step) {
            $acte4[] = $this->getReference('quest_acte4_' . $step, Quest::class);
        }
        for ($i = 1; $i < \count($acte4); ++$i) {
            $acte4[$i]->setPrerequisiteQuests([$acte4[$i - 1]->getId()]);
        }

        // Le cercle de Lumiere : une etape par zone de depart, dans l'ordre du
        // graphe (village -> foret -> mines).
        $cercle = [];
        foreach (['jardins', 'foret', 'mines'] as $step) {
            $cercle[] = $this->getReference('quest_cercle_' . $step, Quest::class);
        }
        for ($i = 1; $i < \count($cercle); ++$i) {
            $cercle[$i]->setPrerequisiteQuests([$cercle[$i - 1]->getId()]);
        }

        // Quest chains (Acte 1/2/3) and PNJ ID fixups are in QuestChainFixtures

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            GameEventFixtures::class,
            SeasonArcFixtures::class,
            MateriaCatalogFixtures::class,
            MapFixtures::class,
        ];
    }
}
