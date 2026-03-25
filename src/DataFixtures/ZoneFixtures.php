<?php

namespace App\DataFixtures;

use App\Entity\App\Area;
use App\Entity\App\Map;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ZoneFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $map1 = $this->getReference('map_1', Map::class);

        // Map 1: 3x3 chunks of 60x60 = 180x180 tiles total
        // Define 5 logical zones covering different regions
        $zones = [
            [
                'name' => 'Plaine de l\'Éveil',
                'slug' => 'plaine-eveil',
                'biome' => 'plains',
                'weather' => null,
                'music' => null,
                'lightLevel' => 1.0,
                'x' => 0, 'y' => 0, 'width' => 60, 'height' => 60,
            ],
            [
                'name' => 'Forêt des Murmures',
                'slug' => 'foret-murmures',
                'biome' => 'forest',
                'weather' => null,
                'music' => null,
                'lightLevel' => 0.75,
                'x' => 60, 'y' => 0, 'width' => 60, 'height' => 60,
            ],
            [
                'name' => 'Marais Brumeux',
                'slug' => 'marais-brumeux',
                'biome' => 'swamp',
                'weather' => 'fog',
                'music' => null,
                'lightLevel' => 0.6,
                'x' => 0, 'y' => 60, 'width' => 60, 'height' => 60,
            ],
            [
                'name' => 'Collines Venteuses',
                'slug' => 'collines-venteuses',
                'biome' => 'hills',
                'weather' => null,
                'music' => null,
                'lightLevel' => 1.1,
                'x' => 120, 'y' => 0, 'width' => 60, 'height' => 120,
            ],
            [
                'name' => 'Lande d\'Ombre',
                'slug' => 'lande-ombre',
                'biome' => 'dark',
                'weather' => 'storm',
                'music' => null,
                'lightLevel' => 0.4,
                'x' => 0, 'y' => 120, 'width' => 120, 'height' => 60,
            ],
        ];

        foreach ($zones as $data) {
            $area = new Area();
            $area->setName($data['name']);
            $area->setSlug($data['slug']);
            $area->setCoordinates('0.0');
            $area->setFullData(json_encode(['cells' => []]));
            $area->setMap($map1);
            $area->setBiome($data['biome']);
            $area->setWeather($data['weather']);
            $area->setMusic($data['music']);
            $area->setLightLevel($data['lightLevel']);
            $area->setZoneX($data['x']);
            $area->setZoneY($data['y']);
            $area->setZoneWidth($data['width']);
            $area->setZoneHeight($data['height']);
            $area->setCreatedAt(new \DateTime());
            $area->setUpdatedAt(new \DateTime());

            $manager->persist($area);
        }

        // Map 2 (Village): single safe zone
        $map2 = $this->getReference('map_2', Map::class);
        $village = new Area();
        $village->setName('Village de Lumière');
        $village->setSlug('village-lumiere');
        $village->setCoordinates('0.0');
        $village->setFullData(json_encode(['cells' => []]));
        $village->setMap($map2);
        $village->setBiome('village');
        $village->setWeather('sunny');
        $village->setMusic(null);
        $village->setLightLevel(1.0);
        $village->setZoneX(0);
        $village->setZoneY(0);
        $village->setZoneWidth(40);
        $village->setZoneHeight(40);
        $village->setCreatedAt(new \DateTime());
        $village->setUpdatedAt(new \DateTime());

        $manager->persist($village);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
}
