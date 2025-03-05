<?php

namespace App\DataFixtures;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Map;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ObjectLayerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des objets de la forêt
        $forestObjects = [
            'forest-object-96-160' => [
                'name' => 'Feu de camp',
                'slug' => 'forest-96-160',
                'type' => 'other',
                'movement' => -1,
                'map' => 'map_1'
            ],
            'forest-object-96-160-1' => [
                'name' => 'Feu de camp',
                'slug' => 'forest-96-160',
                'type' => 'other',
                'movement' => -1,
                'map' => 'map_1'
            ],
            'forest-object-288-96' => [
                'name' => '',
                'slug' => 'forest-288-96',
                'type' => 'other',
                'movement' => -1,
                'map' => 'map_1'
            ]
        ];
        
        // Création des objets de la forêt
        foreach ($forestObjects as $key => $data) {
            $objectLayer = new ObjectLayer();
            $objectLayer->setName($data['name']);
            $objectLayer->setSlug($data['slug']);
            $objectLayer->setType($data['type']);
            $objectLayer->setMovement($data['movement']);
            $objectLayer->setMap($this->getReference($data['map'], Map::class));
            $objectLayer->setCreatedAt(new \DateTime());
            $objectLayer->setUpdatedAt(new \DateTime());
            
            $manager->persist($objectLayer);
            $this->addReference($key, $objectLayer);
        }
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
} 