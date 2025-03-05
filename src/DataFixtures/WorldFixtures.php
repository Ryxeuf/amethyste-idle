<?php

namespace App\DataFixtures;

use App\Entity\App\World;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WorldFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création du monde de démo
        $world = new World();
        $world->setName('Demo world');
        $world->setCreatedAt(new \DateTime());
        $world->setUpdatedAt(new \DateTime());
        
        $manager->persist($world);
        $this->addReference('world_1', $world);
        
        $manager->flush();
    }
} 