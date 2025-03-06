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
            // Spot de ruby taille xs
            'spot-ruby-xs' => [
                'name' => '',
                'slug' => 'spot-ruby-xs',
                'type' => 'spot',
                'movement' => -1,
                'map' => 'map_1',
                'usable' => true,
                'coordinates' => '95.24',
                'actions' => [
                    [
                        'action' => 'harvest',
                        'distance' => 1
                    ]
                ],
                'items' => [
                    [
                        'slug' => 'ore-ruby',
                        'min' => 1,
                        'max' => 1
                    ]
                ]
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
            
            if (isset($data['usable'])) {
                $objectLayer->setUsable($data['usable']);
            }
            
            if (isset($data['coordinates'])) {
                $objectLayer->setCoordinates($data['coordinates']);
            }
            
            if (isset($data['actions'])) {
                $objectLayer->setActions($data['actions']);
            }
            
            if (isset($data['items'])) {
                $objectLayer->setItems($data['items']);
            }
            
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