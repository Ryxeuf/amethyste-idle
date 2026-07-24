<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Graphe de zones du World 1 (pivot PBBG, ZON-02).
 *
 * A ne pas confondre avec ZoneFixtures (sous-zones Tiled biome/meteo sur Area,
 * heritees de l'editeur de cartes). Ici, chaque Zone est un noeud du graphe de
 * monde PBBG et reprend une carte TMX existante (sourceMap) pour permettre la
 * migration des positions et spawns (ZON-03 / ZON-04). Topologie en etoile
 * autour du Village de Lumiere + liaisons laterales foret-marais et mines-crete.
 * Durees de voyage indicatives, a etalonner via docs/BALANCE.md.
 */
class ZoneGraphFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $definitions = [
            'zone_village' => [
                'slug' => 'village-de-lumiere',
                'name' => 'Village de Lumière',
                'nameEn' => 'Village of Light',
                'description' => 'Le hub central du World 1. Zone sure : commerces, artisans et voyageurs s\'y croisent.',
                'descriptionEn' => 'The central hub of World 1. Safe zone: shops, artisans and travelers cross paths here.',
                'type' => Zone::TYPE_CITY,
                'safe' => true,
                'mapRef' => 'map_2',
            ],
            'zone_foret' => [
                'slug' => 'foret-des-murmures',
                'name' => 'Forêt des murmures',
                'nameEn' => 'Whispering Forest',
                'description' => 'Arbres centenaires, clairieres et riviere. Premiers dangers pour les aventuriers.',
                'descriptionEn' => 'Ancient trees, clearings and a river. First dangers for adventurers.',
                'type' => Zone::TYPE_WILDERNESS,
                'safe' => false,
                'mapRef' => 'map_3',
            ],
            'zone_mines' => [
                'slug' => 'mines-profondes',
                'name' => 'Mines profondes',
                'nameEn' => 'Deep Mines',
                'description' => 'Tunnels et filons riches en minerai, mais les profondeurs sont hostiles.',
                'descriptionEn' => 'Tunnels and veins rich in ore, but the depths are hostile.',
                'type' => Zone::TYPE_WILDERNESS,
                'safe' => false,
                'mapRef' => 'map_4',
            ],
            'zone_marais' => [
                'slug' => 'marais-brumeux',
                'name' => 'Marais Brumeux',
                'nameEn' => 'Misty Swamp',
                'description' => 'Brume epaisse et eaux stagnantes ; des creatures corrompues rodent.',
                'descriptionEn' => 'Thick mist and stagnant waters; corrupted creatures lurk.',
                'type' => Zone::TYPE_WILDERNESS,
                'safe' => false,
                'mapRef' => 'map_5',
            ],
            'zone_crete' => [
                'slug' => 'crete-de-ventombre',
                'name' => 'Crête de Ventombre',
                'nameEn' => 'Shadowind Ridge',
                'description' => 'Pics balayes par des vents violents ; le sommet abrite les creatures les plus rudes du World 1.',
                'descriptionEn' => 'Peaks swept by violent winds; the summit shelters the toughest creatures of World 1.',
                'type' => Zone::TYPE_WILDERNESS,
                'safe' => false,
                'mapRef' => 'map_6',
            ],
        ];

        $zones = [];
        foreach ($definitions as $reference => $definition) {
            $zone = new Zone();
            $zone->setSlug($definition['slug']);
            $zone->setName($definition['name']);
            $zone->setNameTranslations(['en' => $definition['nameEn']]);
            $zone->setDescription($definition['description']);
            $zone->setDescriptionTranslations(['en' => $definition['descriptionEn']]);
            $zone->setType($definition['type']);
            $zone->setIsSafe($definition['safe']);
            $zone->setSourceMap($this->getReference($definition['mapRef'], Map::class));
            $zone->setCreatedAt(new \DateTime());
            $zone->setUpdatedAt(new \DateTime());

            $manager->persist($zone);
            $this->addReference($reference, $zone);
            $zones[$reference] = $zone;
        }

        // Liaisons bidirectionnelles (deux aretes par liaison, durees symetriques).
        $links = [
            ['zone_village', 'zone_foret', 300],
            ['zone_village', 'zone_marais', 420],
            ['zone_village', 'zone_mines', 600],
            ['zone_village', 'zone_crete', 900],
            ['zone_foret', 'zone_marais', 300],
            ['zone_mines', 'zone_crete', 480],
        ];

        foreach ($links as [$a, $b, $seconds]) {
            foreach ([[$a, $b], [$b, $a]] as [$from, $to]) {
                $connection = new ZoneConnection($zones[$from], $zones[$to], $seconds);
                $connection->setCreatedAt(new \DateTime());
                $connection->setUpdatedAt(new \DateTime());
                $manager->persist($connection);
            }
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
