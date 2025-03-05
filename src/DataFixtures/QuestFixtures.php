<?php

namespace App\DataFixtures;

use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class QuestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des quêtes
        $quests = [
            'quest_zombie_1' => [
                'name' => 'Éliminer les zombies',
                'description' => 'Éliminez 5 zombies qui rôdent autour du village.',
                'requirements' => [
                    'kill' => [
                        'zombie' => 5
                    ]
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 50,
                    'items' => [
                        'mushroom' => 2
                    ]
                ]
            ],
            'quest_taiju_1' => [
                'name' => 'Le Taiju solitaire',
                'description' => 'Un Taiju a été aperçu près de la forêt. Éliminez-le avant qu\'il ne cause des dégâts.',
                'requirements' => [
                    'kill' => [
                        'taiju' => 1
                    ]
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        'leather_skin_1' => 1
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