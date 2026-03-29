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

            // =====================
            // FORÊT DES MURMURES — Herboristerie & Pêche
            // =====================
            'forest-spot-mint' => [
                'name' => 'Herbes médicinales',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '14.38',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 45,
                'items' => [['slug' => 'herb-mint', 'min' => 1, 'max' => 2]],
                'map' => 'map_3',
            ],
            'forest-spot-sage' => [
                'name' => 'Buisson de sauge',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '16.42',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 60,
                'items' => [['slug' => 'herb-sage', 'min' => 1, 'max' => 2]],
                'map' => 'map_3',
            ],
            'forest-spot-dandelion' => [
                'name' => 'Fleurs sauvages',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '33.28',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 30,
                'items' => [
                    ['slug' => 'herb-dandelion', 'min' => 1, 'max' => 2],
                    ['slug' => 'herb-lavender', 'min' => 1, 'max' => 1],
                ],
                'map' => 'map_3',
            ],
            'forest-spot-rosemary' => [
                'name' => 'Romarin des bois',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '20.25',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 60,
                'items' => [['slug' => 'herb-rosemary', 'min' => 1, 'max' => 2]],
                'map' => 'map_3',
            ],
            'forest-spot-mandrake' => [
                'name' => 'Racines anciennes',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '25.10',
                'requiredToolType' => 'sickle',
                'respawnDelay' => 180,
                'items' => [['slug' => 'herb-mandrake', 'min' => 1, 'max' => 1]],
                'map' => 'map_3',
            ],
            'forest-spot-fishing' => [
                'name' => 'Rivière forestière',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '46.30',
                'requiredToolType' => 'fishing_rod',
                'respawnDelay' => 45,
                'items' => [
                    ['slug' => 'fish-trout', 'min' => 1, 'max' => 1, 'difficulty' => 35],
                    ['slug' => 'fish-salmon', 'min' => 1, 'max' => 1, 'difficulty' => 50],
                ],
                'map' => 'map_3',
            ],

            // =====================
            // MINES PROFONDES — Filons de minerai
            // =====================
            'mines-spot-copper' => [
                'name' => 'Filon de cuivre',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '12.22',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 45,
                'items' => [['slug' => 'ore-copper', 'min' => 1, 'max' => 3]],
                'map' => 'map_4',
            ],
            'mines-spot-iron-1' => [
                'name' => 'Filon de fer brut',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '18.20',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 60,
                'items' => [['slug' => 'ore-iron', 'min' => 1, 'max' => 3]],
                'map' => 'map_4',
            ],
            'mines-spot-iron-2' => [
                'name' => 'Filon de fer profond',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '32.14',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 60,
                'items' => [['slug' => 'ore-iron', 'min' => 2, 'max' => 4]],
                'map' => 'map_4',
            ],
            'mines-spot-silver' => [
                'name' => "Filon d'argent",
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '38.10',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 120,
                'items' => [['slug' => 'ore-silver', 'min' => 1, 'max' => 2]],
                'map' => 'map_4',
            ],
            'mines-spot-gold' => [
                'name' => "Filon d'or enfoui",
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '46.6',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 300,
                'items' => [['slug' => 'ore-gold', 'min' => 1, 'max' => 2]],
                'map' => 'map_4',
            ],
            'mines-spot-ruby' => [
                'name' => 'Filon de rubis caché',
                'type' => ObjectLayer::TYPE_HARVEST_SPOT,
                'coordinates' => '52.4',
                'requiredToolType' => 'pickaxe',
                'respawnDelay' => 600,
                'items' => [['slug' => 'ore-ruby', 'min' => 1, 'max' => 2]],
                'map' => 'map_4',
            ],
        ];

        foreach ($harvestSpots as $slug => $data) {
            $objectLayer = new ObjectLayer();
            $objectLayer->setName($data['name']);
            $objectLayer->setSlug($slug);
            $objectLayer->setType($data['type']);
            $objectLayer->setMovement(0);
            $objectLayer->setMap($this->getReference($data['map'] ?? 'map_1', Map::class));
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

        // =====================
        // PORTAILS — Village ↔ Carte principale (bidirectionnels)
        // =====================
        $map1 = $this->getReference('map_1', Map::class);
        $map2 = $this->getReference('map_2', Map::class);
        $map3 = $this->getReference('map_3', Map::class);
        $map4 = $this->getReference('map_4', Map::class);
        $map6 = $this->getReference('map_6', Map::class);

        $portals = [
            // Depuis carte principale → village (entrée sud du village)
            'portal-to-village' => [
                'name' => 'Portail vers le Village de Lumière',
                'map' => $map1,
                'coordinates' => '30.30',
                'destinationMapId' => $map2->getId(),
                'destinationCoordinates' => '19.37',
            ],
            // Depuis village → carte principale (sortie sud du village)
            'portal-village-exit-1' => [
                'name' => 'Sortie du village',
                'map' => $map2,
                'coordinates' => '19.39',
                'destinationMapId' => $map1->getId(),
                'destinationCoordinates' => '30.31',
            ],
            'portal-village-exit-2' => [
                'name' => 'Sortie du village',
                'map' => $map2,
                'coordinates' => '20.39',
                'destinationMapId' => $map1->getId(),
                'destinationCoordinates' => '31.31',
            ],

            // === Forêt des murmures ↔ Village (bidirectionnels) ===
            // Village → Forêt (sortie est du village)
            'portal-village-to-forest' => [
                'name' => 'Chemin vers la Forêt des murmures',
                'map' => $map2,
                'coordinates' => '38.20',
                'destinationMapId' => $map3->getId(),
                'destinationCoordinates' => '30.56',
            ],
            // Forêt → Village (entrée sud de la forêt)
            'portal-forest-to-village' => [
                'name' => 'Retour au Village de Lumière',
                'map' => $map3,
                'coordinates' => '29.57',
                'destinationMapId' => $map2->getId(),
                'destinationCoordinates' => '37.20',
            ],
            'portal-forest-to-village-2' => [
                'name' => 'Retour au Village de Lumière',
                'map' => $map3,
                'coordinates' => '30.57',
                'destinationMapId' => $map2->getId(),
                'destinationCoordinates' => '37.20',
            ],

            // === Mines profondes ↔ Village (bidirectionnels) ===
            // Village → Mines (sortie ouest du village)
            'portal-village-to-mines' => [
                'name' => 'Descente vers les Mines profondes',
                'map' => $map2,
                'coordinates' => '1.20',
                'destinationMapId' => $map4->getId(),
                'destinationCoordinates' => '3.27',
            ],
            // Mines → Village (sortie sud des mines)
            'portal-mines-to-village' => [
                'name' => 'Retour au Village de Lumière',
                'map' => $map4,
                'coordinates' => '2.28',
                'destinationMapId' => $map2->getId(),
                'destinationCoordinates' => '2.20',
            ],
            'portal-mines-to-village-2' => [
                'name' => 'Retour au Village de Lumière',
                'map' => $map4,
                'coordinates' => '3.28',
                'destinationMapId' => $map2->getId(),
                'destinationCoordinates' => '2.20',
            ],

            // === Crête de Ventombre ↔ Village (bidirectionnels) ===
            // Village → Montagne (sortie nord du village)
            'portal-village-to-mountain' => [
                'name' => 'Sentier vers la Crête de Ventombre',
                'map' => $map2,
                'coordinates' => '20.1',
                'destinationMapId' => $map6->getId(),
                'destinationCoordinates' => '25.48',
            ],
            // Montagne → Village (entrée sud de la montagne)
            'portal-mountain-to-village' => [
                'name' => 'Retour au Village de Lumière',
                'map' => $map6,
                'coordinates' => '25.49',
                'destinationMapId' => $map2->getId(),
                'destinationCoordinates' => '20.2',
            ],
            'portal-mountain-to-village-2' => [
                'name' => 'Retour au Village de Lumière',
                'map' => $map6,
                'coordinates' => '26.49',
                'destinationMapId' => $map2->getId(),
                'destinationCoordinates' => '20.2',
            ],
        ];

        foreach ($portals as $portalSlug => $data) {
            $portal = new ObjectLayer();
            $portal->setName($data['name']);
            $portal->setSlug($portalSlug);
            $portal->setType(ObjectLayer::TYPE_PORTAL);
            $portal->setMovement(0);
            $portal->setMap($data['map']);
            $portal->setUsable(true);
            $portal->setCoordinates($data['coordinates']);
            $portal->setActions([['action' => 'portal', 'distance' => 0]]);
            $portal->setItems(null);
            $portal->setDestinationMapId($data['destinationMapId']);
            $portal->setDestinationCoordinates($data['destinationCoordinates']);
            $portal->setNightOnly(false);
            $portal->setCreatedAt(new \DateTime());
            $portal->setUpdatedAt(new \DateTime());

            $manager->persist($portal);
            $this->addReference($portalSlug, $portal);
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
