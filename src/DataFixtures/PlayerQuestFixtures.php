<?php

namespace App\DataFixtures;

use App\Entity\App\Player;
use App\Entity\App\PlayerQuest;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PlayerQuestFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des quêtes pour les joueurs
        $playerQuests = [
            [
                'player' => 'player_demo',
                'quest' => 'quest_zombie_1',
                'tracking' => [
                    'monsters' => [
                        ['count' => 2, 'necessary' => 2, 'slug' => 'zombie', 'name' => 'Zombie'],
                    ],
                ],
            ],
            [
                'player' => 'player_demo',
                'quest' => 'quest_mushroom_1',
                'tracking' => [
                    'collect' => [
                        'mushroom' => 3,
                    ],
                ],
            ],
            [
                'player' => 'player_demo_2',
                'quest' => 'quest_taiju_1',
                'tracking' => [
                    'monsters' => [
                        ['count' => 0, 'necessary' => 1, 'slug' => 'taiju', 'name' => 'Taiju'],
                    ],
                ],
            ],
        ];

        foreach ($playerQuests as $data) {
            $playerQuest = new PlayerQuest();
            $playerQuest->setPlayer($this->getReference($data['player'], Player::class));
            $playerQuest->setQuest($this->getReference($data['quest'], Quest::class));
            $playerQuest->setTracking($data['tracking']);
            $playerQuest->setCreatedAt(new \DateTime());
            $playerQuest->setUpdatedAt(new \DateTime());

            $manager->persist($playerQuest);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PlayerFixtures::class,
            QuestFixtures::class,
        ];
    }
}
