<?php

namespace App\DataFixtures;

use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class QuestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des quêtes basées sur le contenu de pnj_quest.yaml
        $quests = [
            'quest_zombie_1' => [
                'name' => 'Sus aux zombies',
                'description' => 'Les zombies deviennent gênants dans la region, pourrais-tu m\'en débarrasser ? Je t\'apprendrai comment miner.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Zombie',
                            'slug' => 'zombie',
                            'count' => 2
                        ]
                    ]
                ],
                'rewards' => [
                    'gold' => 1,
                    'items' => [
                        [
                            'item' => 3,
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'beer-pint'
                        ],
                        [
                            'item' => 10,
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'miner-domain-parchment'
                        ]
                    ]
                ]
            ],
            'quest_skeleton_1' => [
                'name' => 'Sus aux squelettes',
                'description' => 'Les squelettes deviennent gênants dans la region, pourrais-tu m\'en débarrasser ?',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Squelette',
                            'slug' => 'skeleton',
                            'count' => 2
                        ]
                    ]
                ],
                'rewards' => [
                    'gold' => 1,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'beer-pint'
                        ]
                    ]
                ]
            ],
            'quest_taiju_1' => [
                'name' => 'Le Taiju menaçant',
                'description' => 'Un Taiju dangereux a été aperçu dans la forêt. Éliminez-le pour assurer la sécurité des villageois.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Taiju',
                            'slug' => 'taiju',
                            'count' => 1
                        ]
                    ]
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 50,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'liana-whip'
                        ]
                    ]
                ]
            ],
            'quest_mushroom_1' => [
                'name' => 'Cueillette de champignons',
                'description' => 'Récoltez 5 champignons pour l\'apothicaire du village.',
                'requirements' => [
                    'collect' => [
                        'mushroom' => 5
                    ]
                ],
                'rewards' => [
                    'xp' => 50,
                    'gold' => 30,
                    'items' => [
                        'materia_soin' => 1
                    ]
                ]
            ],
            'quest_goblin_1' => [
                'name' => 'Menace gobeline',
                'description' => 'Les gobelins pillent les fermes environnantes. Éliminez-en quelques-uns pour protéger les villageois.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Gobelin',
                            'slug' => 'goblin',
                            'count' => 3
                        ]
                    ]
                ],
                'rewards' => [
                    'xp' => 75,
                    'gold' => 40,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'leather-boots'
                        ]
                    ]
                ]
            ],
            'quest_troll_1' => [
                'name' => 'Le troll du pont',
                'description' => 'Un troll a élu domicile sous le pont principal et empêche les marchands de passer. Débarrassez-vous de cette menace.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Troll',
                            'slug' => 'troll',
                            'count' => 1
                        ]
                    ]
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 80,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'wooden-shield'
                        ]
                    ]
                ]
            ],
            'quest_werewolf_1' => [
                'name' => 'Hurlements nocturnes',
                'description' => 'Des hurlements terrifiants résonnent dans la forêt les nuits de pleine lune. Traquez et éliminez le loup-garou responsable.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Loup-garou',
                            'slug' => 'werewolf',
                            'count' => 1
                        ]
                    ]
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 100,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'leather-armor'
                        ]
                    ]
                ]
            ],
            'quest_banshee_griffin_1' => [
                'name' => 'Créatures de la nuit',
                'description' => 'Des créatures mystérieuses terrorisent les voyageurs. Éliminez une banshee et un griffon pour sécuriser les routes.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Banshee',
                            'slug' => 'banshee',
                            'count' => 1
                        ],
                        [
                            'name' => 'Griffon',
                            'slug' => 'griffin',
                            'count' => 1
                        ]
                    ]
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 150,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'magic-amulet'
                        ]
                    ]
                ]
            ],
            'quest_wood_collection' => [
                'name' => 'Bûcheron en herbe',
                'description' => 'Le menuisier du village a besoin de bois pour ses créations. Récoltez des bûches pour l\'aider.',
                'requirements' => [
                    'collect' => [
                        'wood_log' => 8
                    ]
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 45,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion'
                        ]
                    ]
                ]
            ],
            'quest_dragon_1' => [
                'name' => 'Le dragon de la montagne',
                'description' => 'Un dragon terrorise la région depuis sa tanière dans la montagne. Cette quête est extrêmement dangereuse, mais la récompense est à la hauteur du risque.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Dragon',
                            'slug' => 'dragon',
                            'count' => 1
                        ]
                    ]
                ],
                'rewards' => [
                    'xp' => 500,
                    'gold' => 300,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'iron-sword'
                        ],
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'iron-armor'
                        ]
                    ]
                ]
            ]
        ];
        
        foreach ($quests as $key => $data) {
            $quest = new Quest();
            $quest->setName($data['name']);
            $quest->setDescription($data['description']);
            $quest->setRequirements($data['requirements']);
            $quest->setRewards($data['rewards']);
            $quest->setCreatedAt(new \DateTime());
            $quest->setUpdatedAt(new \DateTime());
            
            $manager->persist($quest);
            $this->addReference($key, $quest);
        }
        
        $manager->flush();
    }
} 