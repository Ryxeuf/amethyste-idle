<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MobFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des mobs
        $mobs = [
            'ochu_1' => [
                'coordinates' => '14.16',
                'monster' => 'ochu',
            ],
            'zombie_1' => [
                'coordinates' => '17.2',
                'monster' => 'zombie',
            ],
            'zombie_2' => [
                'coordinates' => '6.5',
                'monster' => 'zombie',
            ],
            'skeleton_1' => [
                'coordinates' => '26.5',
                'monster' => 'skeleton',
            ],
            'taiju_1' => [
                'coordinates' => '24.22',
                'monster' => 'taiju',
            ],
            // Nouveaux mobs
            'goblin_1' => [
                'coordinates' => '10.8',
                'monster' => 'goblin',
            ],
            'goblin_2' => [
                'coordinates' => '12.10',
                'monster' => 'goblin',
            ],
            'troll_1' => [
                'coordinates' => '20.15',
                'monster' => 'troll',
            ],
            'dragon_1' => [
                'coordinates' => '30.30',
                'monster' => 'dragon',
            ],
            'werewolf_1' => [
                'coordinates' => '18.12',
                'monster' => 'werewolf',
            ],
            'banshee_1' => [
                'coordinates' => '22.8',
                'monster' => 'banshee',
            ],
            'griffin_1' => [
                'coordinates' => '28.18',
                'monster' => 'griffin',
            ],
            'minotaur_1' => [
                'coordinates' => '15.25',
                'monster' => 'minotaur',
            ],
            'gargoyle_1' => [
                'coordinates' => '8.20',
                'monster' => 'gargoyle',
            ],
        ];

        foreach ($mobs as $key => $data) {
            $monster = $this->getReference($data['monster'], Monster::class);

            $mob = new Mob();
            $mob->setMap($this->getReference('map_1', Map::class));
            $mob->setCoordinates($data['coordinates']);
            $mob->setMonster($monster);
            $mob->setLife($monster->getLife());
            $mob->setLevel($monster->getLevel());
            $mob->setCreatedAt(new \DateTime());
            $mob->setUpdatedAt(new \DateTime());

            $manager->persist($mob);
            $this->addReference($key, $mob);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
            MonsterFixtures::class,
        ];
    }
}
