<?php

namespace App\DataFixtures;

use App\Entity\App\Pnj;
use App\Entity\App\Map;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PnjFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création du PNJ de démo
        $pnjDemo = new Pnj();
        $pnjDemo->setName('Hello World');
        $pnjDemo->setLife(1);
        $pnjDemo->setMaxLife(1);
        $pnjDemo->setMap($this->getReference('map_1', Map::class));
        $pnjDemo->setCoordinates('2.12');
        $pnjDemo->setClassType('demo');
        
        // Dialogue du PNJ
        $dialog = [
            [
                'next' => 1,
                'text' => 'Bien le bonjour'
            ],
            [
                'conditional_next' => [
                    [
                        'next' => 4,
                        'next_condition' => [
                            'quest_not' => [1]
                        ]
                    ],
                    [
                        'next' => 2,
                        'next_condition' => [
                            'quest' => [1]
                        ]
                    ],
                    [
                        'next' => 3
                    ]
                ],
                'text' => 'Bienvenue dans le monde d\'améthyste, je m\'appelle Hello'
            ],
            [
                'text' => 'Merci d\'avoir tué ces zombies pour moi'
            ],
            [
                'text' => 'As-tu tué tous les zombies pour moi ?'
            ],
            [
                'text' => 'J\'ai un problème de zombies envahissants, pourrais tu m\'en débarrasser ?',
                'choices' => [
                    [
                        'text' => 'Oui',
                        'data' => [
                            'quest' => 1
                        ],
                        'action' => 'quest_offer'
                    ],
                    [
                        'text' => 'Non',
                        'action' => 'close'
                    ]
                ]
            ]
        ];
        
        $pnjDemo->setDialog($dialog);
        $pnjDemo->setCreatedAt(new \DateTime());
        $pnjDemo->setUpdatedAt(new \DateTime());
        
        $manager->persist($pnjDemo);
        $this->addReference('pnj_demo', $pnjDemo);
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
} 