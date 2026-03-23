<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use App\Enum\WeatherType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MobFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des mobs
        $mobs = [
            'ochu_1' => [
                'coordinates' => '14.16',
                'monster' => 'ochu',
            ],
            'zombie_1' => [
                'coordinates' => '17.2',
                'monster' => 'zombie',
            ],
            'zombie_2' => [
                'coordinates' => '6.5',
                'monster' => 'zombie',
            ],
            'skeleton_1' => [
                'coordinates' => '26.5',
                'monster' => 'skeleton',
            ],
            'taiju_1' => [
                'coordinates' => '24.22',
                'monster' => 'taiju',
            ],
            // Nouveaux mobs
            'goblin_1' => [
                'coordinates' => '10.8',
                'monster' => 'goblin',
            ],
            'goblin_2' => [
                'coordinates' => '12.10',
                'monster' => 'goblin',
            ],
            'troll_1' => [
                'coordinates' => '20.15',
                'monster' => 'troll',
            ],
            'dragon_1' => [
                'coordinates' => '30.30',
                'monster' => 'dragon',
            ],
            'werewolf_1' => [
                'coordinates' => '18.12',
                'monster' => 'werewolf',
                'nocturnal' => true,
            ],
            'banshee_1' => [
                'coordinates' => '22.8',
                'monster' => 'banshee',
                'nocturnal' => true,
            ],
            'griffin_1' => [
                'coordinates' => '28.18',
                'monster' => 'griffin',
            ],
            'minotaur_1' => [
                'coordinates' => '15.25',
                'monster' => 'minotaur',
            ],
            'gargoyle_1' => [
                'coordinates' => '8.20',
                'monster' => 'gargoyle',
            ],
            // === Mobs proches du spawn joueur (85.34) — zone de départ ===
            // Niveau 1 — très proches (2-5 cases)
            'slime_1' => [
                'coordinates' => '83.34',
                'monster' => 'slime',
            ],
            'slime_2' => [
                'coordinates' => '87.35',
                'monster' => 'slime',
            ],
            'slime_3' => [
                'coordinates' => '85.31',
                'monster' => 'slime',
            ],
            'giant_rat_1' => [
                'coordinates' => '82.33',
                'monster' => 'giant_rat',
            ],
            'giant_rat_2' => [
                'coordinates' => '88.36',
                'monster' => 'giant_rat',
            ],
            'bat_1' => [
                'coordinates' => '84.37',
                'monster' => 'bat',
            ],
            'bat_2' => [
                'coordinates' => '86.31',
                'monster' => 'bat',
            ],
            // Niveau 2 — distance moyenne (8-12 cases)
            'spider_1' => [
                'coordinates' => '78.30',
                'monster' => 'spider',
            ],
            'spider_2' => [
                'coordinates' => '92.38',
                'monster' => 'spider',
            ],
            'venom_snake_1' => [
                'coordinates' => '76.34',
                'monster' => 'venom_snake',
            ],
            'venom_snake_2' => [
                'coordinates' => '90.28',
                'monster' => 'venom_snake',
            ],
            'specter_1' => [
                'coordinates' => '80.40',
                'monster' => 'specter',
                'nocturnal' => true,
            ],
            'banshee_2' => [
                'coordinates' => '93.32',
                'monster' => 'banshee',
                'nocturnal' => true,
            ],
            // Niveau 3 — distance éloignée (15-20 cases)
            'fire_elemental_1' => [
                'coordinates' => '70.28',
                'monster' => 'fire_elemental',
            ],
            'fire_elemental_2' => [
                'coordinates' => '100.40',
                'monster' => 'fire_elemental',
            ],
            'werewolf_2' => [
                'coordinates' => '72.42',
                'monster' => 'werewolf',
                'nocturnal' => true,
            ],
            'troll_2' => [
                'coordinates' => '98.25',
                'monster' => 'troll',
            ],
            'gargoyle_2' => [
                'coordinates' => '68.34',
                'monster' => 'gargoyle',
            ],
            // Niveau 4-5 — loin du spawn (20-30 cases)
            'stone_golem_1' => [
                'coordinates' => '60.30',
                'monster' => 'stone_golem',
            ],
            'griffin_2' => [
                'coordinates' => '110.34',
                'monster' => 'griffin',
            ],
            'minotaur_2' => [
                'coordinates' => '65.45',
                'monster' => 'minotaur',
            ],
            'dragon_2' => [
                'coordinates' => '55.34',
                'monster' => 'dragon',
            ],
            // === Mobs météo-spécifiques ===
            'storm_elemental_1' => [
                'coordinates' => '75.36',
                'monster' => 'fire_elemental',
                'spawnWeather' => WeatherType::Storm,
            ],
            'fog_specter_1' => [
                'coordinates' => '88.40',
                'monster' => 'specter',
                'spawnWeather' => WeatherType::Fog,
            ],
            'rain_venom_snake_1' => [
                'coordinates' => '82.28',
                'monster' => 'venom_snake',
                'spawnWeather' => WeatherType::Rain,
            ],
            'snow_stone_golem_1' => [
                'coordinates' => '90.35',
                'monster' => 'stone_golem',
                'spawnWeather' => WeatherType::Snow,
            ],
        ];

        foreach ($mobs as $key => $data) {
            $monster = $this->getReference($data['monster'], Monster::class);

            $mob = new Mob();
            $mob->setMap($this->getReference('map_1', Map::class));
            $mob->setCoordinates($data['coordinates']);
            $mob->setMonster($monster);
            $mob->setLife($monster->getLife());
            $mob->setLevel($monster->getLevel());
            $mob->setNocturnal($data['nocturnal'] ?? false);
            $mob->setSpawnWeather($data['spawnWeather'] ?? null);
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
