<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\World;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MapFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création de la carte de test
        $map = new Map();
        $map->setName('Carte de test');
        $map->setWorld($this->getReference('world_1', World::class));
        $map->setAreaWidth(60);
        $map->setAreaHeight(60);
        $map->setCreatedAt(new \DateTime());
        $map->setUpdatedAt(new \DateTime());
        
        $manager->persist($map);
        $this->addReference('map_1', $map);
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            WorldFixtures::class,
        ];
    }
} 