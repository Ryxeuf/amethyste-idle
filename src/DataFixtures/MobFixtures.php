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
            // === Monstres tier 1 manquants (tâche 140) — zone de départ ===
            'wolf_1' => [
                'coordinates' => '81.36',
                'monster' => 'wolf',
            ],
            'wolf_2' => [
                'coordinates' => '89.33',
                'monster' => 'wolf',
            ],
            'beetle_1' => [
                'coordinates' => '84.32',
                'monster' => 'beetle',
            ],
            'beetle_2' => [
                'coordinates' => '87.37',
                'monster' => 'beetle',
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
            'scorpion_1' => [
                'coordinates' => '77.32',
                'monster' => 'scorpion',
            ],
            'scorpion_2' => [
                'coordinates' => '93.36',
                'monster' => 'scorpion',
            ],
            'mushroom_golem_1' => [
                'coordinates' => '79.36',
                'monster' => 'mushroom_golem',
            ],
            'mushroom_golem_2' => [
                'coordinates' => '91.32',
                'monster' => 'mushroom_golem',
            ],
            'ghost_1' => [
                'coordinates' => '80.40',
                'monster' => 'ghost',
                'nocturnal' => true,
            ],
            'ghost_2' => [
                'coordinates' => '93.32',
                'monster' => 'ghost',
                'nocturnal' => true,
            ],
            'specter_1' => [
                'coordinates' => '80.42',
                'monster' => 'specter',
                'nocturnal' => true,
            ],
            'banshee_2' => [
                'coordinates' => '93.30',
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
            // === Monstres tier 2 avancés (tâche 65) — lvl 15-25 ===
            // Archidruide corrompu (lvl 16) — zone très éloignée
            'corrupted_archdruid_1' => [
                'coordinates' => '40.28',
                'monster' => 'corrupted_archdruid',
            ],
            'corrupted_archdruid_2' => [
                'coordinates' => '128.40',
                'monster' => 'corrupted_archdruid',
            ],
            // Liche mineure (lvl 18) — zone extrême, nocturne
            'lesser_lich_1' => [
                'coordinates' => '38.40',
                'monster' => 'lesser_lich',
                'nocturnal' => true,
            ],
            'lesser_lich_2' => [
                'coordinates' => '130.28',
                'monster' => 'lesser_lich',
                'nocturnal' => true,
            ],
            // Hydre des marais (lvl 20) — zone extrême
            'swamp_hydra_1' => [
                'coordinates' => '35.34',
                'monster' => 'swamp_hydra',
            ],
            'swamp_hydra_2' => [
                'coordinates' => '134.34',
                'monster' => 'swamp_hydra',
            ],
            // Forgeron abyssal (lvl 24) — zone la plus éloignée
            'abyssal_blacksmith_1' => [
                'coordinates' => '32.30',
                'monster' => 'abyssal_blacksmith',
            ],
            'abyssal_blacksmith_2' => [
                'coordinates' => '138.38',
                'monster' => 'abyssal_blacksmith',
            ],
            // === Boss de zone (tâche 66) ===
            // Gardien de la Forêt — zone forestière (coords éloignées)
            'forest_guardian_1' => [
                'coordinates' => '45.40',
                'monster' => 'forest_guardian',
            ],
            // Seigneur de la Forge — zone mine (coords très éloignées)
            'forge_lord_1' => [
                'coordinates' => '135.30',
                'monster' => 'forge_lord',
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

            // === Forêt des murmures (map_3) — zone lvl 5-15 ===
            // Mobs faciles près de l'entrée sud
            'forest_slime_1' => [
                'coordinates' => '30.52',
                'monster' => 'slime',
                'map' => 'map_3',
            ],
            'forest_spider_1' => [
                'coordinates' => '25.48',
                'monster' => 'spider',
                'map' => 'map_3',
            ],
            // Mobs intermédiaires — clairière centrale et chemins
            'forest_undine_1' => [
                'coordinates' => '35.35',
                'monster' => 'undine',
                'map' => 'map_3',
            ],
            'forest_ochu_1' => [
                'coordinates' => '15.20',
                'monster' => 'ochu',
                'map' => 'map_3',
            ],
            'forest_venom_snake_1' => [
                'coordinates' => '35.45',
                'monster' => 'venom_snake',
                'map' => 'map_3',
            ],
            'forest_sylph_1' => [
                'coordinates' => '25.15',
                'monster' => 'sylph',
                'map' => 'map_3',
            ],
            // Mobs avancés — zones reculées
            'forest_alpha_wolf_1' => [
                'coordinates' => '40.45',
                'monster' => 'alpha_wolf',
                'map' => 'map_3',
            ],
            'forest_salamander_1' => [
                'coordinates' => '10.45',
                'monster' => 'salamander',
                'map' => 'map_3',
            ],
            // Monstres tier 1 manquants (tâche 140) — forêt
            'forest_wolf_1' => [
                'coordinates' => '28.50',
                'monster' => 'wolf',
                'map' => 'map_3',
            ],
            'forest_beetle_1' => [
                'coordinates' => '32.48',
                'monster' => 'beetle',
                'map' => 'map_3',
            ],
            'forest_scorpion_1' => [
                'coordinates' => '20.40',
                'monster' => 'scorpion',
                'map' => 'map_3',
            ],
            'forest_mushroom_golem_1' => [
                'coordinates' => '30.30',
                'monster' => 'mushroom_golem',
                'map' => 'map_3',
            ],
            'forest_ghost_1' => [
                'coordinates' => '40.25',
                'monster' => 'ghost',
                'map' => 'map_3',
                'nocturnal' => true,
            ],
            // Mobs nocturnes
            'forest_will_o_wisp_1' => [
                'coordinates' => '45.10',
                'monster' => 'will_o_wisp',
                'map' => 'map_3',
                'nocturnal' => true,
            ],
            'forest_creeping_shadow_1' => [
                'coordinates' => '50.35',
                'monster' => 'creeping_shadow',
                'map' => 'map_3',
                'nocturnal' => true,
            ],

            // === Mines profondes (map_4) — zone lvl 10-25 ===
            // Entrée des mines — mobs lvl 10-12
            'mines_stone_golem_1' => [
                'coordinates' => '10.25',
                'monster' => 'stone_golem',
                'map' => 'map_4',
            ],
            'mines_rusty_automaton_1' => [
                'coordinates' => '15.22',
                'monster' => 'rusty_automaton',
                'map' => 'map_4',
            ],
            'mines_clay_golem_1' => [
                'coordinates' => '8.18',
                'monster' => 'clay_golem',
                'map' => 'map_4',
            ],
            // Tunnels centraux — mobs lvl 13-18
            'mines_crystal_golem_1' => [
                'coordinates' => '28.15',
                'monster' => 'crystal_golem',
                'map' => 'map_4',
            ],
            'mines_gargoyle_1' => [
                'coordinates' => '35.12',
                'monster' => 'gargoyle',
                'map' => 'map_4',
            ],
            'mines_cursed_knight_1' => [
                'coordinates' => '30.20',
                'monster' => 'cursed_knight',
                'map' => 'map_4',
                'nocturnal' => true,
            ],
            // Salles profondes — mobs lvl 18-24
            'mines_abyssal_blacksmith_1' => [
                'coordinates' => '45.10',
                'monster' => 'abyssal_blacksmith',
                'map' => 'map_4',
            ],
            'mines_lesser_lich_1' => [
                'coordinates' => '50.8',
                'monster' => 'lesser_lich',
                'map' => 'map_4',
                'nocturnal' => true,
            ],
            // Groupe multi-mob minier
            'mines_automaton_group_1' => [
                'coordinates' => '40.15',
                'monster' => 'rusty_automaton',
                'map' => 'map_4',
                'groupTag' => 'mine_patrol_1',
            ],
            'mines_automaton_group_2' => [
                'coordinates' => '41.15',
                'monster' => 'rusty_automaton',
                'map' => 'map_4',
                'groupTag' => 'mine_patrol_1',
            ],
            // Boss de mine — Seigneur de la Forge
            'mines_forge_lord_1' => [
                'coordinates' => '55.5',
                'monster' => 'forge_lord',
                'map' => 'map_4',
            ],
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

            // === Crête de Ventombre (map_6) — zone montagneuse lvl 15-25 ===
            // Mobs d'entrée — base de la montagne
            'mountain_griffin_1' => [
                'coordinates' => '20.42',
                'monster' => 'griffin',
                'map' => 'map_6',
            ],
            'mountain_gargoyle_1' => [
                'coordinates' => '30.38',
                'monster' => 'gargoyle',
                'map' => 'map_6',
            ],
            'mountain_fire_elemental_1' => [
                'coordinates' => '15.35',
                'monster' => 'fire_elemental',
                'map' => 'map_6',
            ],
            // Mobs intermédiaires — sentiers escarpés
            'mountain_griffin_2' => [
                'coordinates' => '35.28',
                'monster' => 'griffin',
                'map' => 'map_6',
            ],
            'mountain_gargoyle_2' => [
                'coordinates' => '18.22',
                'monster' => 'gargoyle',
                'map' => 'map_6',
            ],
            'mountain_fire_elemental_2' => [
                'coordinates' => '28.18',
                'monster' => 'fire_elemental',
                'map' => 'map_6',
            ],
            // Mobs avancés — proches du sommet
            'mountain_minotaur_1' => [
                'coordinates' => '22.15',
                'monster' => 'minotaur',
                'map' => 'map_6',
            ],
            'mountain_troll_1' => [
                'coordinates' => '30.12',
                'monster' => 'troll',
                'map' => 'map_6',
            ],
            // Boss — Dragon ancestral au sommet
            'mountain_dragon_1' => [
                'coordinates' => '25.8',
                'monster' => 'dragon',
                'map' => 'map_6',
            ],
        ];

        foreach ($mobs as $key => $data) {
            $monster = $this->getReference($data['monster'], Monster::class);

            $mob = new Mob();
            $mob->setMap($this->getReference($data['map'] ?? 'map_1', Map::class));
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
