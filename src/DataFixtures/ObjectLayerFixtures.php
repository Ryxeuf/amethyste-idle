<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\ObjectLayer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ObjectLayerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $harvestSpots = [
            // =====================
            // MINAGE — Minerais
            // =====================
            'spot-ruby-xs' => [
                'name' => 'Petit filon de rubis',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '95.24',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 30,
                'items' => [['slug' => 'ore-ruby', 'min' => 1, 'max' => 2]],
            ],
            'spot-ruby-s' => [
                'name' => 'Filon de rubis',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '97.26',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 60,
                'items' => [['slug' => 'ore-ruby', 'min' => 1, 'max' => 3]],
            ],
            'spot-ruby-m' => [
                'name' => 'Filon de rubis riche',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '100.28',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 120,
                'items' => [['slug' => 'ore-ruby', 'min' => 2, 'max' => 4]],
            ],
            'spot-ruby-l' => [
                'name' => 'Gros filon de rubis',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '103.30',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 300,
                'items' => [['slug' => 'ore-ruby', 'min' => 3, 'max' => 5]],
            ],
            'spot-ruby-xl' => [
                'name' => 'Filon de rubis légendaire',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '106.32',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 1800,
                'items' => [['slug' => 'ore-ruby', 'min' => 4, 'max' => 7]],
            ],
            'spot-iron-xs' => [
                'name' => 'Petit filon de fer',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '90.20',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 30,
                'items' => [['slug' => 'ore-iron', 'min' => 1, 'max' => 2]],
            ],
            'spot-iron-s' => [
                'name' => 'Filon de fer',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '92.22',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 60,
                'items' => [['slug' => 'ore-iron', 'min' => 1, 'max' => 3]],
            ],
            'spot-copper-xs' => [
                'name' => 'Petit filon de cuivre',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '88.18',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 30,
                'items' => [['slug' => 'ore-copper', 'min' => 1, 'max' => 3]],
            ],
            'spot-silver-xs' => [
                'name' => 'Petit filon d\'argent',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '108.34',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 300,
                'nightOnly' => true,
                'items' => [['slug' => 'ore-silver', 'min' => 1, 'max' => 2]],
            ],
            'spot-gold-xs' => [
                'name' => 'Petit filon d\'or',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '110.36',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 300,
                'items' => [['slug' => 'ore-gold', 'min' => 1, 'max' => 1]],
            ],

            // =====================
            // HERBORISTERIE — Plantes
            // =====================
            'spot-mint-xs' => [
                'name' => 'Touffe de menthe',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '80.15',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 30,
                'items' => [['slug' => 'plant-mint', 'min' => 1, 'max' => 3]],
            ],
            'spot-sage-xs' => [
                'name' => 'Plant de sauge',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '82.17',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 60,
                'items' => [['slug' => 'plant-sage', 'min' => 1, 'max' => 2]],
            ],
            'spot-dandelion-xs' => [
                'name' => 'Pissenlit sauvage',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '78.13',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 20,
                'items' => [['slug' => 'plant-mint', 'min' => 1, 'max' => 2]],
            ],
            'spot-lavender-xs' => [
                'name' => 'Buisson de lavande',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '84.19',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 30,
                'items' => [['slug' => 'plant-lavender', 'min' => 1, 'max' => 2]],
            ],
            'spot-thyme-xs' => [
                'name' => 'Touffe de thym',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '86.21',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 30,
                'items' => [['slug' => 'plant-thyme', 'min' => 1, 'max' => 2]],
            ],
            'spot-rosemary-xs' => [
                'name' => 'Branche de romarin',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '76.11',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 60,
                'items' => [['slug' => 'plant-rosemary', 'min' => 1, 'max' => 2]],
            ],
            'spot-mandrake-xs' => [
                'name' => 'Mandragore rare',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '74.9',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 1800,
                'nightOnly' => true,
                'items' => [['slug' => 'plant-mandrake', 'min' => 1, 'max' => 1]],
            ],

            // =====================
            // PÊCHE — Points de pêche
            // =====================
            'spot-fishing-xs' => [
                'name' => 'Point de pêche calme',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '70.40',
                'requiredToolType' => 'fishing_rod',
                'respawnDelay' => 30,
                'items' => [['slug' => 'fish-trout', 'min' => 1, 'max' => 1, 'difficulty' => 30]],
            ],
            'spot-fishing-s' => [
                'name' => 'Point de pêche du lac',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '72.42',
                'requiredToolType' => 'fishing_rod',
                'respawnDelay' => 60,
                'items' => [
                    ['slug' => 'fish-trout', 'min' => 1, 'max' => 1, 'difficulty' => 40],
                    ['slug' => 'fish-salmon', 'min' => 1, 'max' => 1, 'difficulty' => 50],
                ],
            ],
            'spot-fishing-m' => [
                'name' => 'Point de pêche profond',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '74.44',
                'requiredToolType' => 'fishing_rod',
                'respawnDelay' => 120,
                'items' => [
                    ['slug' => 'fish-salmon', 'min' => 1, 'max' => 1, 'difficulty' => 50],
                    ['slug' => 'fish-carp', 'min' => 1, 'max' => 1, 'difficulty' => 55],
                ],
            ],
            'spot-fishing-rare' => [
                'name' => 'Point de pêche mystérieux',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '76.46',
                'requiredToolType' => 'fishing_rod',
                'respawnDelay' => 300,
                'items' => [
                    ['slug' => 'fish-moonfish', 'min' => 1, 'max' => 1, 'difficulty' => 70],
                    ['slug' => 'fish-electric-eel', 'min' => 1, 'max' => 1, 'difficulty' => 75],
                ],
            ],
        ];

        foreach ($harvestSpots as $slug => $data) {
            $objectLayer = new ObjectLayer();
            $objectLayer->setName($data['name']);
            $objectLayer->setSlug($slug);
            $objectLayer->setType($data['type']);
            $objectLayer->setMovement(0);
            $objectLayer->setMap($this->getReference('map_1', Map::class));
            $objectLayer->setUsable(true);
            $objectLayer->setCoordinates($data['coordinates']);
            $objectLayer->setActions([['action' => 'harvest', 'distance' => 1]]);
            $objectLayer->setItems($data['items']);
            $objectLayer->setRespawnDelay($data['respawnDelay']);
            $objectLayer->setRequiredToolType($data['requiredToolType']);
            $objectLayer->setNightOnly($data['nightOnly'] ?? false);
            $objectLayer->setCreatedAt(new \DateTime());
            $objectLayer->setUpdatedAt(new \DateTime());

            $manager->persist($objectLayer);
            $this->addReference($slug, $objectLayer);
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
