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
            // === Monstres élémentaires tier 1 (tâche 28) ===
            // Ondine (Eau, lvl 2) — distance moyenne du spawn
            'undine_1' => [
                'coordinates' => '79.38',
                'monster' => 'undine',
            ],
            'undine_2' => [
                'coordinates' => '91.30',
                'monster' => 'undine',
            ],
            // Feu follet (Lumière, lvl 2) — distance moyenne, nocturne
            'will_o_wisp_1' => [
                'coordinates' => '81.30',
                'monster' => 'will_o_wisp',
                'nocturnal' => true,
            ],
            'will_o_wisp_2' => [
                'coordinates' => '90.39',
                'monster' => 'will_o_wisp',
                'nocturnal' => true,
            ],
            // Salamandre (Feu, lvl 3) — distance éloignée
            'salamander_1' => [
                'coordinates' => '72.30',
                'monster' => 'salamander',
            ],
            'salamander_2' => [
                'coordinates' => '98.38',
                'monster' => 'salamander',
            ],
            // Automate rouillé (Métal, lvl 3) — distance éloignée
            'rusty_automaton_1' => [
                'coordinates' => '69.38',
                'monster' => 'rusty_automaton',
            ],
            'rusty_automaton_2' => [
                'coordinates' => '101.30',
                'monster' => 'rusty_automaton',
            ],
            // Sylphe (Air, lvl 4) — loin du spawn
            'sylph_1' => [
                'coordinates' => '63.32',
                'monster' => 'sylph',
            ],
            'sylph_2' => [
                'coordinates' => '107.36',
                'monster' => 'sylph',
            ],
            // Loup alpha (Bête, lvl 4) — loin du spawn, nocturne
            'alpha_wolf_1' => [
                'coordinates' => '62.40',
                'monster' => 'alpha_wolf',
                'nocturnal' => true,
            ],
            'alpha_wolf_2' => [
                'coordinates' => '108.28',
                'monster' => 'alpha_wolf',
            ],
            // Golem d'argile (Terre, lvl 5) — très loin du spawn
            'clay_golem_1' => [
                'coordinates' => '57.32',
                'monster' => 'clay_golem',
            ],
            'clay_golem_2' => [
                'coordinates' => '112.36',
                'monster' => 'clay_golem',
            ],
            // Ombre rampante (Ombre, lvl 5) — très loin du spawn, nocturne
            'creeping_shadow_1' => [
                'coordinates' => '56.40',
                'monster' => 'creeping_shadow',
                'nocturnal' => true,
            ],
            'creeping_shadow_2' => [
                'coordinates' => '113.28',
                'monster' => 'creeping_shadow',
                'nocturnal' => true,
            ],
            // === Monstres tier 2 (tâche 47) — lvl 10-15 ===
            // Wyverne (Air/Feu, lvl 10) — distance éloignée
            'wyvern_1' => [
                'coordinates' => '50.28',
                'monster' => 'wyvern',
            ],
            'wyvern_2' => [
                'coordinates' => '118.40',
                'monster' => 'wyvern',
            ],
            // Chevalier maudit (Dark/Métal, lvl 12) — très loin, nocturne
            'cursed_knight_1' => [
                'coordinates' => '48.38',
                'monster' => 'cursed_knight',
                'nocturnal' => true,
            ],
            'cursed_knight_2' => [
                'coordinates' => '120.30',
                'monster' => 'cursed_knight',
            ],
            // Naga (Eau/Bête, lvl 13) — très loin
            'naga_1' => [
                'coordinates' => '46.34',
                'monster' => 'naga',
            ],
            'naga_2' => [
                'coordinates' => '122.38',
                'monster' => 'naga',
            ],
            // Golem de cristal (Terre/Lumière, lvl 15) — zone la plus éloignée
            'crystal_golem_1' => [
                'coordinates' => '42.32',
                'monster' => 'crystal_golem',
            ],
            'crystal_golem_2' => [
                'coordinates' => '126.36',
                'monster' => 'crystal_golem',
            ],
            // === Groupe multi-mob avec soigneur (tâche 49) ===
            'skeleton_group_1' => [
                'coordinates' => '75.22',
                'monster' => 'skeleton',
                'groupTag' => 'necro_pack_1',
            ],
            'skeleton_group_2' => [
                'coordinates' => '76.22',
                'monster' => 'skeleton',
                'groupTag' => 'necro_pack_1',
            ],
            'necromancer_1' => [
                'coordinates' => '75.23',
                'monster' => 'necromancer',
                'groupTag' => 'necro_pack_1',
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
            $mob->setGroupTag($data['groupTag'] ?? null);
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
