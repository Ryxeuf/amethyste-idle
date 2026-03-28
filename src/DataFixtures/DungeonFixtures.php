<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\Game\Dungeon;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DungeonFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $map = $this->getReference('map_dungeon_racines', Map::class);

        $dungeon = new Dungeon();
        $dungeon->setSlug('racines-de-la-foret');
        $dungeon->setName('Racines de la foret');
        $dungeon->setDescription('Un reseau de galeries souterraines envahi par des racines corrompues. Les creatures qui y rodent sont devenues hostiles, et une menace plus ancienne sommeille dans les profondeurs.');
        $dungeon->setMap($map);
        $dungeon->setMinLevel(5);
        $dungeon->setMaxPlayers(1);
        $dungeon->setLootPreview(['Equipement tier 2', 'Materia rare', 'Potions avancees']);
        $dungeon->setCreatedAt(new \DateTime());
        $dungeon->setUpdatedAt(new \DateTime());

        $manager->persist($dungeon);
        $this->addReference('dungeon_racines', $dungeon);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
}
