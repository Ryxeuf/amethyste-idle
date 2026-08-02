<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * La faune des donjons (BES-03).
 *
 * `zones.yaml` est la source unique de la faune du graphe de zones ; les
 * donjons n'en relevent pas — leurs cartes ne sont la source d'aucune zone,
 * et leur peuplement suit le chemin des donjons (PLAN_DUNGEONS). C'est la
 * seule population qui survive a la suppression de `MobFixtures` : tout ce
 * qui vivait sur la carte de test (`map_1`, hors graphe) a rejoint les blocs
 * `mobs:` de `zones.yaml`.
 */
class DungeonMobFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $mobs = [
            // === Donjon : Racines de la foret (tache 84) ===
            'dungeon_ochu_1' => [
                'coordinates' => '5.5',
                'monster' => 'ochu',
                'map' => 'map_dungeon_racines',
            ],
            'dungeon_spider_1' => [
                'coordinates' => '8.10',
                'monster' => 'spider',
                'map' => 'map_dungeon_racines',
            ],
            'dungeon_taiju_1' => [
                'coordinates' => '12.8',
                'monster' => 'taiju',
                'map' => 'map_dungeon_racines',
            ],
            // Boss du donjon
            'dungeon_ancient_root_boss' => [
                'coordinates' => '15.15',
                'monster' => 'ancient_root',
                'map' => 'map_dungeon_racines',
            ],
        ];

        foreach ($mobs as $key => $data) {
            $monster = $this->getReference($data['monster'], Monster::class);

            $mob = new Mob();
            $mob->setMap($this->getReference($data['map'], Map::class));
            $mob->setCoordinates($data['coordinates']);
            $mob->setMonster($monster);
            $mob->setLife($monster->getLife());
            $mob->setTier($monster->getTier());
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
