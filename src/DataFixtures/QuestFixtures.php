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
                'requirements' => [
                    'collect' => [
                        'mushroom' => 5,
                    ],
                ],
                'rewards' => [
                    'xp' => 50,
                    'gold' => 30,
                    'items' => [
                        'materia_soin' => 1,
                    ],
                ],
            ],
            'quest_goblin_1' => [
                'name' => 'Menace gobeline',
                'name_translations' => ['en' => 'Goblin Threat'],
                'description' => 'Les gobelins pillent les fermes environnantes. Éliminez-en quelques-uns pour protéger les villageois.',
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
                'requirements' => [
                    'collect' => [
                        'wood_log' => 8,
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
                'description' => 'Un dragon terrorise la région depuis sa tanière dans la montagne. Cette quête est extrêmement dangereuse, mais la récompense est à la hauteur du risque.',
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
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '15.20',
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
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '10.6',
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
            // --- Chaîne narrative Acte 1 : L'Éveil (5 quêtes tutoriel) ---
            'quest_acte1_reveil' => [
                'name' => 'L\'Éveil — Réveil',
                'name_translations' => ['en' => 'The Awakening — Awakening'],
                'description' => 'Vous ouvrez les yeux dans un lieu inconnu, sans aucun souvenir. Une femme sage se tient devant vous. Explorez les alentours du village pour retrouver vos esprits.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '80.34',
                            'name' => 'Place du village',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 20,
                    'gold' => 10,
                ],
                'prerequisiteQuests' => null,
            ],
            'quest_acte1_premiers_pas' => [
                'name' => 'L\'Éveil — Premiers pas',
                'name_translations' => ['en' => 'The Awakening — First Steps'],
                'description' => 'Claire vous conseille d\'aller voir Gérard le Forgeron pour vous équiper. Rendez-vous à sa forge et recevez votre première arme.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '1.5',
                            'name' => 'Forge de Gérard',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 30,
                    'gold' => 15,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'short-sword',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte1_bapteme_du_feu' => [
                'name' => 'L\'Éveil — Baptême du feu',
                'name_translations' => ['en' => 'The Awakening — Baptism by Fire'],
                'description' => 'Gérard vous a remis une épée. Il est temps de prouver votre valeur ! Éliminez deux slimes qui rôdent aux abords du village.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Slime',
                            'slug' => 'slime',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 40,
                    'gold' => 20,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte1_recolte' => [
                'name' => 'L\'Éveil — Récolte',
                'name_translations' => ['en' => 'The Awakening — Harvest'],
                'description' => 'Marie la Herboriste a besoin de champignons pour préparer des remèdes. Récoltez-en dans les environs et rapportez-les-lui.',
                'requirements' => [
                    'collect' => [
                        'mushroom' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 35,
                    'gold' => 25,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'herbalist-domain-parchment',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte1_cristal' => [
                'name' => 'L\'Éveil — Le Cristal d\'Améthyste',
                'name_translations' => ['en' => 'The Awakening — The Amethyst Crystal'],
                'description' => 'Claire la Sage vous parle d\'un cristal mystérieux caché dans une clairière au sud. Trouvez-le : il pourrait détenir la clé de vos souvenirs perdus.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '85.50',
                            'name' => 'Clairière du Cristal',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 30,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'm1-life',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Quetes cachees (decouverte) ---
            'quest_hidden_secret_clearing' => [
                'name' => 'Le secret de la clairiere',
                'description' => 'En explorant une clairiere isolee, vous decouvrez des traces anciennes au sol. Quelque chose est enterre ici... Explorez les alentours pour trouver d\'autres indices.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '25.40',
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
                    'map_id' => 1,
                    'coordinates' => '20.38',
                ],
            ],
            'quest_hidden_rare_slime' => [
                'name' => 'La gelee doree',
                'description' => 'En eliminant une gelee, vous remarquez un etrange eclat dore dans ses restes. Les villageois parlent d\'une gelee rare au coeur brillant. Eliminez-en davantage pour la trouver.',
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
                'description' => 'En recoltant des champignons, vous trouvez un vieux parchemin cachant une recette oubliee. Recoltez d\'autres ingredients pour la reconstituer.',
                'requirements' => [
                    'collect' => [
                        'mushroom' => 8,
                    ],
                ],
                'rewards' => [
                    'xp' => 70,
                    'gold' => 40,
                    'items' => [
                        'materia_soin' => 1,
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
                'description' => 'Sur le cadavre d\'un gobelin, vous trouvez une carte menant a une cache secrete. Eliminez d\'autres gobelins pour reunir les morceaux de la carte.',
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
                'description' => 'Depuis que vous avez touché le Cristal d\'Améthyste, vous percevez des échos étranges venant de la Forêt des murmures. Thadeus l\'Ermite, qui vit au nord de la forêt, pourrait avoir des réponses.',
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
                'description' => 'Thadeus a senti une corruption ancienne se réveiller dans la forêt. Des créatures corrompues rôdent près de l\'Arbre-Mère. Éliminez-les pour affaiblir la corruption.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Ondine', 'slug' => 'forest_undine', 'count' => 2],
                        ['name' => 'Ochu', 'slug' => 'forest_ochu', 'count' => 2],
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
                'description' => 'La corruption a été affaiblie, mais l\'Arbre-Mère reste malade. Thadeus a besoin de sauge et de mandragore pour préparer un remède ancestral. Récoltez-les et apportez-les à Elara l\'Herboriste qui saura les préparer.',
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
                'description' => 'Le remède a guéri l\'Arbre-Mère. En remerciement, ses racines ont révélé un éclat de cristal vert enfoui depuis des siècles. Rendez-vous au cœur de la forêt pour le récupérer.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 3,
                            'coordinates' => '30.15',
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
                'description' => 'Depuis votre contact avec le Cristal d\'Améthyste, vous percevez des vibrations sourdes venant des Mines profondes. Grimmur le Contremaître, posté à l\'entrée, pourrait en savoir plus.',
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
                'description' => 'Grimmur a senti une énergie étrange émaner des filons profonds. Il vous demande de récolter du minerai de fer et de l\'or enfoui pour analyser la source de ces vibrations.',
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
                'description' => 'L\'énergie provient des profondeurs, là où règne le Seigneur de la Forge. Ce gardien devenu fou protège quelque chose d\'ancien. Vous devez le vaincre pour atteindre la source des vibrations.',
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
                'description' => 'La défaite du Seigneur de la Forge a révélé une fissure dans le mur de sa salle. Un éclat de cristal orangé pulse au fond, irradiant une chaleur ancienne. Récupérez-le.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 4,
                            'coordinates' => '55.5',
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
                'description' => 'Depuis votre contact avec le Cristal d\'Améthyste, une brume surnaturelle semble vous appeler depuis le Marais Brumeux. Morwen la Voyante, qui vit à la lisière du marais, pourrait déchiffrer ces visions.',
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
                'description' => 'Morwen a besoin d\'ingrédients spécifiques du marais pour préparer un onguent qui dissipera les brumes enchantées protégeant le cœur du marais. Récoltez des champignons vénéneux et des racines de marais.',
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
                'description' => 'L\'onguent a dissipé une partie de la brume, révélant des créatures anciennes qui protègent le passage vers le cœur du marais. Éliminez-les pour ouvrir la voie.',
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
                'description' => 'Les gardiens vaincus, le chemin vers le cœur du marais est libre. Un éclat de cristal bleu-gris scintille au fond d\'un bassin d\'eau stagnante, enveloppé de vapeur glaciale. Récupérez-le.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 5,
                            'coordinates' => '25.42',
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
                'description' => 'Depuis votre contact avec le Cristal d\'Améthyste, des visions de pics enneigés et de vents hurlants vous hantent. Aldric l\'Ancien, un ermite qui vit sur la Crête de Ventombre, pourrait comprendre ces échos.',
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
                'description' => 'Aldric vous a révélé qu\'un fragment ancien est prisonnier du sommet, gardé par le Dragon ancestral qui sommeille dans sa tanière depuis des siècles. Il faut le vaincre pour accéder au pic sacré.',
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
                'description' => 'Le Dragon ancestral est vaincu. Le chemin vers le pic sacré est libre. Un éclat de cristal blanc brille au sommet, battu par les vents éternels. Grimpez et récupérez-le.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 6,
                            'coordinates' => '25.5',
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
                'description' => 'Marie la Herboriste a disparu. Interrogez Claire la Sage, Antoine le Mage et Élise la Guérisseuse pour retrouver sa trace.',
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
                'description' => 'Prouvez votre valeur en vainquant le Gardien de la Forêt en solo, sans utiliser de soin et en moins de 5 minutes.',
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
                'description' => 'Les quatre fragments resonnent dans votre sac, pulsant a l\'unisson. Claire la Sage pourrait savoir ce que cela signifie.',
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
                'description' => 'Les fragments vous guident vers le Nexus de la Convergence. Un gardien ancien protege le coeur du cristal d\'Amethyste. Vous devez le vaincre pour decouvrir la verite.',
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
                'description' => 'Le Gardien est vaincu. Le cristal d\'Amethyste libere son secret ultime. Rendez-vous au coeur du Nexus pour decouvrir la verite sur votre passe.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 0,
                            'coordinates' => '15.15',
                            'name' => 'Coeur du Nexus',
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
                'description' => 'Pendant le Festival de la Lune, les creatures nocturnes sont plus agitees. Eliminez des monstres pour gagner une recompense exclusive du festival.',
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
                'description' => 'La Nuit des Ombres attire des creatures malfaisantes. Repoussez-les et reclamez votre recompense.',
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
                'description' => 'En traversant les plaines, vous apercevez une stèle gravée de runes anciennes, à demi enfouie dans l\'herbe haute. Approchez-vous pour déchiffrer les inscriptions.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '45.22', 'name' => 'Stèle aux runes anciennes'],
                    ],
                ],
                'rewards' => ['xp' => 50, 'gold' => 30],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'map_id' => 1, 'coordinates' => '42.18'],
            ],
            'quest_discovery_forgotten_well' => [
                'name' => 'Le puits des Anciens',
                'description' => 'Un puits en ruine, à peine visible sous les herbes, garde encore l\'eau claire d\'une source souterraine oubliée. Examinez-le de plus près.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '32.53', 'name' => 'Puits en ruine'],
                    ],
                ],
                'rewards' => [
                    'xp' => 55,
                    'gold' => 30,
                    'items' => [['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'life-potion']],
                ],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'map_id' => 1, 'coordinates' => '28.50'],
            ],
            // Forêt des Murmures
            'quest_discovery_fairy_ring' => [
                'name' => 'Cercle féérique',
                'description' => 'Un bourdonnement musical flotte dans l\'air. Des lucioles dansent en cercle autour d\'un anneau de champignons lumineux. Approchez-vous du centre du cercle.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '88.32', 'name' => 'Centre du cercle féérique'],
                    ],
                ],
                'rewards' => ['xp' => 70, 'gold' => 45],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'map_id' => 1, 'coordinates' => '85.28'],
            ],
            'quest_discovery_hollow_oak' => [
                'name' => 'Le chêne millénaire',
                'description' => 'Un chêne immense et creux se dresse devant vous. Des gravures anciennes ornent l\'intérieur de son tronc. Explorez la cavité pour découvrir ce qu\'elle recèle.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '108.22', 'name' => 'Intérieur du chêne creux'],
                    ],
                ],
                'rewards' => ['xp' => 75, 'gold' => 50],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'map_id' => 1, 'coordinates' => '105.18'],
            ],
            // Marais Brumeux
            'quest_discovery_sunken_altar' => [
                'name' => 'Autel englouti',
                'description' => 'Sous les eaux stagnantes, vous distinguez un autel de pierre couvert de mousse et de symboles effacés. Pataugez jusqu\'à lui pour l\'examiner.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '25.82', 'name' => 'Autel de pierre immergé'],
                    ],
                ],
                'rewards' => ['xp' => 80, 'gold' => 55],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'map_id' => 1, 'coordinates' => '22.78'],
            ],
            'quest_discovery_phospho_grotto' => [
                'name' => 'Grotte phosphorescente',
                'description' => 'Une lueur bleu-vert émane d\'une anfractuosité dans la roche. La grotte est tapissée de mousse luminescente. Explorez-la jusqu\'au fond.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '48.102', 'name' => 'Fond de la grotte lumineuse'],
                    ],
                ],
                'rewards' => ['xp' => 85, 'gold' => 50],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'map_id' => 1, 'coordinates' => '45.98'],
            ],
            // Collines Venteuses
            'quest_discovery_wind_shrine' => [
                'name' => 'Sanctuaire éolien',
                'description' => 'Le vent siffle entre des pierres dressées sur la colline. Un ancien sanctuaire dédié aux esprits du vent. Approchez-vous du menhir central.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '145.50', 'name' => 'Menhir central du sanctuaire'],
                    ],
                ],
                'rewards' => ['xp' => 90, 'gold' => 60],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'map_id' => 1, 'coordinates' => '140.45'],
            ],
            // Lande d'Ombre
            'quest_discovery_shadow_obelisk' => [
                'name' => 'Obélisque d\'ombre',
                'description' => 'Un obélisque noir se dresse dans la lande, pulsant d\'une énergie sombre. Des inscriptions décrivent un ancien rituel de protection. Déchiffrez-les.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '58.142', 'name' => 'Obélisque aux inscriptions sombres'],
                    ],
                ],
                'rewards' => ['xp' => 100, 'gold' => 70],
                'isHidden' => true,
                'triggerCondition' => ['type' => 'explore', 'map_id' => 1, 'coordinates' => '55.138'],
            ],
            // --- Quêtes de découverte (exploration standard multi-points) ---
            'quest_discovery_cartographer' => [
                'name' => 'Cartographe des terres oubliées',
                'description' => 'La cartographe du village vous demande de relever cinq points de repère dans chaque zone pour compléter sa carte des terres oubliées.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '30.30', 'name' => 'Cairn de la Plaine'],
                        ['map_id' => 1, 'coordinates' => '90.25', 'name' => 'Arbre-signal de la Forêt'],
                        ['map_id' => 1, 'coordinates' => '35.90', 'name' => 'Balise du Marais'],
                        ['map_id' => 1, 'coordinates' => '150.60', 'name' => 'Vigie des Collines'],
                        ['map_id' => 1, 'coordinates' => '60.150', 'name' => 'Tour de guet de la Lande'],
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
                'description' => 'Un érudit vous parle de trois anciens sites sacrés disseminés entre les Collines et la Lande. Retrouvez-les pour percer les mystères du passé.',
                'requirements' => [
                    'explore' => [
                        ['map_id' => 1, 'coordinates' => '135.80', 'name' => 'Dolmen des Collines'],
                        ['map_id' => 1, 'coordinates' => '100.135', 'name' => 'Cercle de pierres de la Lande'],
                        ['map_id' => 1, 'coordinates' => '70.165', 'name' => 'Crypte ancienne'],
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
                'description' => 'Diane signale que les loups deviennent agressifs et s\'approchent des sentiers. Éliminez la meute et leur chef pour sécuriser la forêt.',
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
                'description' => 'Des serpents venimeux et des scorpions infestent les chemins près de l\'entrée de la forêt. Sylvain demande de l\'aide pour les éliminer.',
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
                'description' => 'Les automates des galeries profondes sont devenus incontrôlables et menacent les mineurs. Durgan vous demande d\'en détruire quelques-uns pour rouvrir les passages.',
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
                'description' => 'Bran offre une récompense pour l\'élimination de morts-vivants et de golems champignon qui envahissent les sentiers du marais.',
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
                'description' => 'Oswald prépare un appât spécial pour attirer les gros poissons du marais. Il a besoin de champignons vénéneux que l\'on trouve dans les zones humides.',
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
                'description' => 'Kaelen rapporte que les griffons et gargouilles bloquent les sentiers d\'altitude, empêchant toute reconnaissance. Éliminez-les pour rouvrir les voies.',
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
                'description' => 'Antoine le Mage, émissaire du Cercle des Mages, étudie les élémentaires de feu et les feux follets pour ses recherches. Rapportez des preuves de leur élimination pour gagner la confiance du Cercle.',
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
                'description' => 'Sébastien le Chevalier teste la valeur des aventuriers au nom de l\'Ordre des Chevaliers. Purgez les morts-vivants qui souillent nos terres pour prouver votre honneur.',
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
                'description' => 'Aurélie l\'Archère travaille discrètement pour la Confrérie des Ombres. Un camp de gobelins espionne les routes marchandes — éliminez leurs éclaireurs avant qu\'ils ne deviennent une menace.',
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
                'description' => 'Chloé l\'Exploratrice cartographie les routes marchandes pour la Guilde des Marchands. Les araignées et les rats géants menacent les convois — débarrassez les sentiers pour rassurer les caravanes.',
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
                'description' => 'Les scorpions venimeux prolifèrent aux abords du désert et menacent les caravanes de passage. Chassez-en quelques-uns pour sécuriser la piste.',
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
                'description' => 'D\'anciennes gargouilles se sont réveillées et attaquent les pèlerins dans les ruines. Mettez-les hors d\'état de nuire.',
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
                'description' => 'Vous avez surpris un contrebandier qui fournit les Ombres en artéfacts volés dans les caravanes marchandes. Il vous propose une part du butin pour le laisser filer. Dénoncer ou se taire ?',
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
                'description' => 'Un déserteur des Chevaliers est enchaîné dans les geôles du village, accusé d\'avoir volé pour nourrir un orphelinat. Les Chevaliers exigent l\'exécution, les Ombres vous offrent une fortune pour l\'évader.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '12.8',
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
                'description' => 'Un vieux grimoire de magie noire a refait surface au fond d\'une grotte. L\'Ordre des Mages le veut pour l\'étudier à l\'abri, la Guilde des Marchands offre une somme pour l\'acquérir au marché noir.',
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
                'description' => 'Une ferme isolée a été incendiée par des gobelins. La veuve du fermier implore de l\'aide pour reconstruire, mais le seigneur local refuse de payer et préfère envoyer les Chevaliers punir les coupables.',
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
                'description' => 'Vous avez trouvé une relique sacrée dans un temple oublié. Les Mages souhaitent la percer à jour, les Chevaliers la veulent pour leur chapelle, et un antiquaire des Marchands propose une petite fortune pour l\'acquérir.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 3,
                            'coordinates' => '22.18',
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
                'description' => 'Des trolls envahissent la Forêt Sombre ! Repoussez-les en éliminant ceux qui rôdent dans la zone.',
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
                'description' => 'Les golems de cristal ont pris le contrôle d\'une galerie. Défendez l\'entrée et éliminez-les avant qu\'ils ne s\'étendent davantage.',
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
                'description' => 'Un marchand itinérant a besoin d\'être escorté jusqu\'au Village. Accompagnez-le en vous rendant au point de rendez-vous.',
                'requirements' => [
                    'escort' => [
                        ['destination_map_id' => 2, 'destination_coordinates' => '10.10', 'name' => 'Amener le marchand au Village'],
                    ],
                ],
                'rewards' => [
                    'gold' => 60,
                    'xp' => 100,
                ],
            ],
            'quest_escort_refugee' => [
                'name' => 'Réfugiés du Marais',
                'description' => 'Des villageois se sont perdus dans le Marais. Guidez-les jusqu\'à la sortie en atteignant le point d\'évacuation en Montagne.',
                'requirements' => [
                    'escort' => [
                        ['destination_map_id' => 6, 'destination_coordinates' => '5.5', 'name' => 'Guider les réfugiés en Montagne'],
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
                'description' => 'Un sphinx de pierre bloque le passage dans les Mines. Il pose une énigme : "Je suis née du feu, façonnée par l\'eau, et je dors dans la terre. Qui suis-je ?" Parlez-lui et donnez la bonne réponse.',
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
                'description' => 'Claire la Sage a découvert d\'anciennes runes dans un grimoire. Elle vous demande : "Quel est l\'élément qui nourrit la lumière et consume l\'ombre ?" Trouvez la réponse.',
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
        ];

        foreach ($quests as $key => $data) {
            $quest = new Quest();
            $quest->setName($data['name']);
            if (isset($data['name_translations']) && is_array($data['name_translations'])) {
                $quest->setNameTranslations($data['name_translations']);
            }
            $quest->setDescription($data['description']);
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

        // Quest chains (Acte 1/2/3) and PNJ ID fixups are in QuestChainFixtures

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            GameEventFixtures::class,
            MapFixtures::class,
        ];
    }
}
