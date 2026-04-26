<?php

namespace App\DataFixtures;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MonsterFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $monsters = [
            'slime' => [
                'name' => 'Gelée',
                'name_translations' => ['en' => 'Slime'],
                'life' => 12,
                'hit' => 70,
                'speed' => 3,
                'attack' => 'none_attack_1',
                'level' => 1,
                'difficulty' => 1,
                'aiPattern' => [
                    'spell_chance' => 0,
                ],
            ],
            'goblin' => [
                'name' => 'Gobelin',
                'name_translations' => ['en' => 'Goblin'],
                'life' => 18,
                'hit' => 75,
                'speed' => 8,
                'attack' => 'none_attack_1',
                'level' => 1,
                'difficulty' => 1,
            ],
            'bat' => [
                'name' => 'Chauve-souris',
                'name_translations' => ['en' => 'Bat'],
                'life' => 15,
                'hit' => 80,
                'speed' => 14,
                'attack' => 'none_attack_1',
                'level' => 1,
                'difficulty' => 1,
                'aiPattern' => [
                    'spell_chance' => 0,
                ],
            ],
            'giant_rat' => [
                'name' => 'Rat géant',
                'name_translations' => ['en' => 'Giant Rat'],
                'life' => 20,
                'hit' => 85,
                'speed' => 10,
                'attack' => 'sharp_blade',
                'level' => 1,
                'difficulty' => 1,
                'spells' => ['venomous_bite'],
                'aiPattern' => [
                    'spell_chance' => 20,
                ],
            ],
            'zombie' => [
                'name' => 'Zombie',
                'name_translations' => ['en' => 'Zombie'],
                'life' => 25,
                'hit' => 80,
                'speed' => 2,
                'attack' => 'none_attack_1',
                'level' => 1,
                'difficulty' => 1,
                'aiPattern' => [
                    'spell_chance' => 10,
                ],
            ],
            'skeleton' => [
                'name' => 'Squelette',
                'name_translations' => ['en' => 'Skeleton'],
                'life' => 35,
                'hit' => 80,
                'speed' => 5,
                'attack' => 'punishment',
                'level' => 2,
                'difficulty' => 2,
                'spells' => ['shadow_bolt'],
                'aiPattern' => [
                    'spell_chance' => 25,
                ],
            ],
            'spider' => [
                'name' => 'Araignée',
                'name_translations' => ['en' => 'Spider'],
                'life' => 28,
                'hit' => 85,
                'speed' => 9,
                'attack' => 'venomous_bite',
                'level' => 2,
                'difficulty' => 2,
                'spells' => ['poison_cloud', 'entangling_roots'],
                'aiPattern' => [
                    'spell_chance' => 35,
                ],
                'elementalResistances' => ['beast' => 0.3, 'fire' => -0.4],
            ],
            'venom_snake' => [
                'name' => 'Serpent venimeux',
                'name_translations' => ['en' => 'Venomous Snake'],
                'life' => 25,
                'hit' => 90,
                'speed' => 11,
                'attack' => 'venomous_bite',
                'level' => 2,
                'difficulty' => 2,
                'spells' => ['toxic_spores'],
                'aiPattern' => [
                    'spell_chance' => 30,
                ],
                'elementalResistances' => ['beast' => 0.2],
            ],
            'taiju' => [
                'name' => 'Taiju',
                'name_translations' => ['en' => 'Taiju'],
                'life' => 30,
                'hit' => 60,
                'speed' => 12,
                'attack' => 'liana_whip',
                'level' => 2,
                'difficulty' => 2,
                'spells' => ['natural_healing', 'thorn_burst'],
                'aiPattern' => [
                    'spell_chance' => 35,
                    'low_hp_heal' => ['threshold' => 40, 'action' => 'heal'],
                    'role' => 'healer',
                ],
            ],
            'specter' => [
                'name' => 'Spectre',
                'name_translations' => ['en' => 'Specter'],
                'life' => 32,
                'hit' => 90,
                'speed' => 8,
                'attack' => 'punishment',
                'level' => 2,
                'difficulty' => 2,
                'spells' => ['shadow_bolt', 'soul_drain'],
                'aiPattern' => [
                    'spell_chance' => 45,
                ],
                'elementalResistances' => ['dark' => 0.4, 'light' => -0.4],
            ],
            'banshee' => [
                'name' => 'Banshee',
                'name_translations' => ['en' => 'Banshee'],
                'life' => 35,
                'hit' => 90,
                'speed' => 7,
                'attack' => 'punishment',
                'level' => 2,
                'difficulty' => 2,
                'spells' => ['shadow_bolt', 'death_grip'],
                'aiPattern' => [
                    'spell_chance' => 50,
                ],
                'elementalResistances' => ['dark' => 0.5, 'light' => -0.5],
            ],
            'ochu' => [
                'name' => 'Ochu',
                'name_translations' => ['en' => 'Ochu'],
                'life' => 45,
                'hit' => 80,
                'speed' => 15,
                'attack' => 'liana_whip',
                'level' => 3,
                'difficulty' => 3,
                'spells' => ['poison_cloud', 'entangling_roots'],
                'aiPattern' => [
                    'spell_chance' => 40,
                    'low_hp_heal' => ['threshold' => 30, 'action' => 'heal'],
                ],
                'elementalResistances' => ['beast' => 0.5, 'fire' => -0.5],
            ],
            'werewolf' => [
                'name' => 'Loup-garou',
                'name_translations' => ['en' => 'Werewolf'],
                'life' => 55,
                'hit' => 80,
                'speed' => 12,
                'attack' => 'sharp_blade',
                'level' => 3,
                'difficulty' => 3,
                'spells' => ['venomous_bite'],
                'aiPattern' => [
                    'spell_chance' => 30,
                    'sequence' => ['attack', 'attack', 'spell'],
                    'danger_alert' => [
                        'threshold' => 25,
                        'message' => 'Le Loup-garou gronde de rage !',
                    ],
                ],
            ],
            'gargoyle' => [
                'name' => 'Gargouille',
                'name_translations' => ['en' => 'Gargoyle'],
                'life' => 55,
                'hit' => 80,
                'speed' => 9,
                'attack' => 'stone_throw',
                'level' => 3,
                'difficulty' => 3,
                'spells' => ['earth_spike', 'stone_spikes'],
                'aiPattern' => [
                    'spell_chance' => 35,
                ],
                'elementalResistances' => ['earth' => 0.4, 'air' => -0.3],
            ],
            'troll' => [
                'name' => 'Troll',
                'name_translations' => ['en' => 'Troll'],
                'life' => 80,
                'hit' => 70,
                'speed' => 3,
                'attack' => 'stone_throw',
                'level' => 3,
                'difficulty' => 3,
                'spells' => ['boulder_throw', 'earthquake'],
                'aiPattern' => [
                    'spell_chance' => 30,
                    'low_hp_heal' => ['threshold' => 30, 'action' => 'attack'],
                    'sequence' => ['attack', 'attack', 'spell'],
                    'danger_alert' => [
                        'threshold' => 30,
                        'message' => 'Le Troll entre en rage !',
                        'spell' => 'earthquake',
                    ],
                ],
                'elementalResistances' => ['earth' => 0.3, 'fire' => -0.3],
            ],
            'fire_elemental' => [
                'name' => 'Élémentaire de feu',
                'name_translations' => ['en' => 'Fire Elemental'],
                'life' => 60,
                'hit' => 85,
                'speed' => 10,
                'attack' => 'fire_ball',
                'level' => 3,
                'difficulty' => 3,
                'spells' => ['flame_rain', 'fire_wall', 'combustion'],
                'aiPattern' => [
                    'spell_chance' => 50,
                    'preferred_element' => 'fire',
                ],
                'elementalResistances' => ['fire' => 0.6, 'water' => -0.5],
            ],
            'griffin' => [
                'name' => 'Griffon',
                'name_translations' => ['en' => 'Griffin'],
                'life' => 65,
                'hit' => 85,
                'speed' => 15,
                'attack' => 'wind_lame',
                'level' => 4,
                'difficulty' => 4,
                'spells' => ['cyclone', 'air_slash', 'wind_blast'],
                'aiPattern' => [
                    'spell_chance' => 45,
                    'preferred_element' => 'air',
                ],
                'elementalResistances' => ['air' => 0.4, 'earth' => -0.3],
            ],
            'minotaur' => [
                'name' => 'Minotaure',
                'name_translations' => ['en' => 'Minotaur'],
                'life' => 90,
                'hit' => 75,
                'speed' => 6,
                'attack' => 'sword_10',
                'level' => 4,
                'difficulty' => 4,
                'spells' => ['iron_fist', 'blade_dance'],
                'aiPattern' => [
                    'spell_chance' => 35,
                    'sequence' => ['attack', 'spell', 'attack', 'attack'],
                    'danger_alert' => [
                        'threshold' => 25,
                        'message' => 'Le Minotaure baisse ses cornes !',
                    ],
                ],
            ],
            'stone_golem' => [
                'name' => 'Golem de pierre',
                'name_translations' => ['en' => 'Stone Golem'],
                'life' => 120,
                'hit' => 70,
                'speed' => 4,
                'attack' => 'stone_throw',
                'level' => 4,
                'difficulty' => 4,
                'spells' => ['boulder_throw', 'earthquake', 'stone_spikes'],
                'aiPattern' => [
                    'spell_chance' => 40,
                    'sequence' => ['attack', 'attack', 'spell', 'attack'],
                    'danger_alert' => [
                        'threshold' => 25,
                        'message' => 'Le Golem tremble violemment !',
                        'spell' => 'earthquake',
                    ],
                ],
                'elementalResistances' => ['earth' => 0.5, 'air' => -0.4, 'metal' => 0.3],
            ],
            // === Monstres tier 1 manquants (tâche 140) ===
            'wolf' => [
                'name' => 'Loup',
                'name_translations' => ['en' => 'Wolf'],
                'life' => 16,
                'hit' => 78,
                'speed' => 11,
                'attack' => 'sharp_blade',
                'level' => 1,
                'difficulty' => 1,
                'aiPattern' => [
                    'spell_chance' => 0,
                ],
                'elementalResistances' => ['beast' => 0.2, 'fire' => -0.3],
            ],
            'scorpion' => [
                'name' => 'Scorpion',
                'name_translations' => ['en' => 'Scorpion'],
                'life' => 22,
                'hit' => 82,
                'speed' => 7,
                'attack' => 'venomous_bite',
                'level' => 2,
                'difficulty' => 1,
                'spells' => ['toxic_spores'],
                'aiPattern' => [
                    'spell_chance' => 20,
                ],
                'elementalResistances' => ['beast' => 0.2, 'earth' => 0.2, 'fire' => -0.3],
            ],
            'beetle' => [
                'name' => 'Scarabée',
                'name_translations' => ['en' => 'Beetle'],
                'life' => 20,
                'hit' => 72,
                'speed' => 5,
                'attack' => 'none_attack_1',
                'level' => 1,
                'difficulty' => 1,
                'aiPattern' => [
                    'spell_chance' => 0,
                ],
                'elementalResistances' => ['earth' => 0.3, 'fire' => -0.3],
            ],
            'mushroom_golem' => [
                'name' => 'Golem champignon',
                'name_translations' => ['en' => 'Mushroom Golem'],
                'life' => 30,
                'hit' => 75,
                'speed' => 4,
                'attack' => 'none_attack_1',
                'level' => 2,
                'difficulty' => 2,
                'spells' => ['toxic_spores', 'poison_cloud'],
                'aiPattern' => [
                    'spell_chance' => 30,
                ],
                'elementalResistances' => ['beast' => 0.3, 'earth' => 0.2, 'fire' => -0.5],
            ],
            'ghost' => [
                'name' => 'Fantôme',
                'name_translations' => ['en' => 'Ghost'],
                'life' => 18,
                'hit' => 82,
                'speed' => 12,
                'attack' => 'punishment',
                'level' => 2,
                'difficulty' => 1,
                'spells' => ['shadow_bolt'],
                'aiPattern' => [
                    'spell_chance' => 25,
                ],
                'elementalResistances' => ['dark' => 0.3, 'light' => -0.4],
            ],
            // === Monstres élémentaires tier 1 (tâche 28) ===
            'salamander' => [
                'name' => 'Salamandre',
                'name_translations' => ['en' => 'Salamander'],
                'life' => 40,
                'hit' => 85,
                'speed' => 10,
                'attack' => 'fire_ball',
                'level' => 3,
                'difficulty' => 2,
                'spells' => ['combustion', 'fire_wall'],
                'aiPattern' => [
                    'spell_chance' => 40,
                    'preferred_element' => 'fire',
                ],
                'elementalResistances' => ['fire' => 0.5, 'water' => -0.5, 'earth' => -0.2],
            ],
            'undine' => [
                'name' => 'Ondine',
                'name_translations' => ['en' => 'Undine'],
                'life' => 30,
                'hit' => 80,
                'speed' => 9,
                'attack' => 'water_jet',
                'level' => 2,
                'difficulty' => 2,
                'spells' => ['frost_bolt', 'water_heal'],
                'aiPattern' => [
                    'spell_chance' => 45,
                    'low_hp_heal' => ['threshold' => 40, 'action' => 'heal'],
                    'preferred_element' => 'water',
                ],
                'elementalResistances' => ['water' => 0.5, 'fire' => -0.4, 'metal' => -0.2],
            ],
            'sylph' => [
                'name' => 'Sylphe',
                'name_translations' => ['en' => 'Sylph'],
                'life' => 35,
                'hit' => 90,
                'speed' => 16,
                'attack' => 'wind_lame',
                'level' => 4,
                'difficulty' => 3,
                'spells' => ['cyclone', 'air_slash', 'wind_blast'],
                'aiPattern' => [
                    'spell_chance' => 50,
                    'preferred_element' => 'air',
                ],
                'elementalResistances' => ['air' => 0.5, 'earth' => -0.5],
            ],
            'clay_golem' => [
                'name' => 'Golem d\'argile',
                'name_translations' => ['en' => 'Clay Golem'],
                'life' => 85,
                'hit' => 70,
                'speed' => 3,
                'attack' => 'stone_throw',
                'level' => 5,
                'difficulty' => 3,
                'spells' => ['earth_spike', 'boulder_throw', 'stone_spikes'],
                'aiPattern' => [
                    'spell_chance' => 35,
                    'sequence' => ['attack', 'attack', 'spell', 'attack'],
                    'danger_alert' => [
                        'threshold' => 25,
                        'message' => 'Le Golem d\'argile se fissure et gronde !',
                        'spell' => 'earthquake',
                    ],
                ],
                'elementalResistances' => ['earth' => 0.6, 'air' => -0.4, 'water' => -0.3],
            ],
            'rusty_automaton' => [
                'name' => 'Automate rouillé',
                'name_translations' => ['en' => 'Rusty Automaton'],
                'life' => 45,
                'hit' => 80,
                'speed' => 7,
                'attack' => 'iron_fist',
                'level' => 3,
                'difficulty' => 2,
                'spells' => ['blade_dance', 'steel_shield'],
                'aiPattern' => [
                    'spell_chance' => 30,
                    'sequence' => ['attack', 'spell', 'attack'],
                ],
                'elementalResistances' => ['metal' => 0.5, 'water' => -0.4, 'fire' => -0.3],
            ],
            'alpha_wolf' => [
                'name' => 'Loup alpha',
                'name_translations' => ['en' => 'Alpha Wolf'],
                'life' => 150,
                'hit' => 90,
                'speed' => 14,
                'attack' => 'sharp_blade',
                'level' => 4,
                'difficulty' => 4,
                'isBoss' => true,
                'spells' => ['venomous_bite', 'savage_charge', 'primordial_roar'],
                'aiPattern' => [
                    'spell_chance' => 45,
                    'preferred_element' => 'beast',
                    'summon' => [
                        'monster_slug' => 'wolf',
                        'count' => 1,
                        'max_per_fight' => 2,
                        'cooldown' => 3,
                        'level_offset' => -1,
                        'chance' => 30,
                    ],
                    'danger_alert' => [
                        'threshold' => 30,
                        'message' => 'Le Loup alpha hurle à la lune — sa meute répond !',
                        'spell' => 'primordial-roar',
                    ],
                ],
                'elementalResistances' => ['beast' => 0.5, 'fire' => -0.3, 'metal' => -0.3],
                'bossPhases' => [
                    [
                        'hpThreshold' => 100,
                        'name' => 'Phase 1 — Chef de meute',
                        'action' => 'attack',
                    ],
                    [
                        'hpThreshold' => 50,
                        'name' => 'Phase 2 — Hurlement sauvage',
                        'action' => 'spell',
                        'preferred_spell' => 'savage-charge',
                        'danger_message' => 'Le Loup alpha hurle — ses yeux brillent d\'une lueur féroce !',
                    ],
                    [
                        'hpThreshold' => 25,
                        'name' => 'Phase 3 — Rage de la meute',
                        'action' => 'spell',
                        'preferred_spell' => 'primordial-roar',
                        'danger_message' => 'Le Loup alpha se dresse, enragé — la forêt tremble sous ses crocs !',
                    ],
                ],
            ],
            'will_o_wisp' => [
                'name' => 'Feu follet',
                'name_translations' => ['en' => 'Will-o\'-the-Wisp'],
                'life' => 120,
                'hit' => 88,
                'speed' => 15,
                'attack' => 'holy_light',
                'level' => 3,
                'difficulty' => 3,
                'isBoss' => true,
                'spells' => ['sacred_light', 'light_aura', 'light_blessing', 'holy_nova'],
                'aiPattern' => [
                    'spell_chance' => 55,
                    'preferred_element' => 'light',
                    'low_hp_heal' => ['threshold' => 35, 'action' => 'heal'],
                    'danger_alert' => [
                        'threshold' => 30,
                        'message' => 'Le Feu follet pulse d\'une lumière aveuglante !',
                        'spell' => 'holy-nova',
                    ],
                ],
                'elementalResistances' => ['light' => 0.5, 'dark' => -0.5, 'water' => -0.2],
                'bossPhases' => [
                    [
                        'hpThreshold' => 100,
                        'name' => 'Phase 1 — Lueur mystérieuse',
                        'action' => 'spell',
                    ],
                    [
                        'hpThreshold' => 50,
                        'name' => 'Phase 2 — Éclat aveuglant',
                        'action' => 'spell',
                        'preferred_spell' => 'sacred-light',
                        'danger_message' => 'Le Feu follet irradie — une lumière brûlante envahit le marais !',
                    ],
                    [
                        'hpThreshold' => 25,
                        'name' => 'Phase 3 — Nova spectrale',
                        'action' => 'spell',
                        'preferred_spell' => 'holy-nova',
                        'danger_message' => 'Le Feu follet se concentre en un point de lumière pure — l\'explosion est imminente !',
                    ],
                ],
            ],
            'creeping_shadow' => [
                'name' => 'Ombre rampante',
                'name_translations' => ['en' => 'Creeping Shadow'],
                'life' => 180,
                'hit' => 88,
                'speed' => 8,
                'attack' => 'shadow_bolt',
                'level' => 5,
                'difficulty' => 4,
                'isBoss' => true,
                'spells' => ['soul_drain', 'death_grip', 'dark_harvest', 'dark_ritual'],
                'aiPattern' => [
                    'spell_chance' => 55,
                    'preferred_element' => 'dark',
                    'summon' => [
                        'monster_slug' => 'ghost',
                        'count' => 1,
                        'max_per_fight' => 2,
                        'cooldown' => 4,
                        'level_offset' => -1,
                        'chance' => 25,
                    ],
                    'danger_alert' => [
                        'threshold' => 30,
                        'message' => 'L\'Ombre rampante se dilate dans l\'obscurité — les ténèbres s\'épaississent !',
                        'spell' => 'dark-harvest',
                    ],
                ],
                'elementalResistances' => ['dark' => 0.6, 'light' => -0.5, 'fire' => -0.2],
                'bossPhases' => [
                    [
                        'hpThreshold' => 100,
                        'name' => 'Phase 1 — Éveil des ténèbres',
                        'action' => 'spell',
                    ],
                    [
                        'hpThreshold' => 50,
                        'name' => 'Phase 2 — Emprise mortelle',
                        'action' => 'spell',
                        'preferred_spell' => 'death-grip',
                        'danger_message' => 'L\'Ombre rampante s\'étend — des mains spectrales jaillissent du sol !',
                    ],
                    [
                        'hpThreshold' => 25,
                        'name' => 'Phase 3 — Moisson des âmes',
                        'action' => 'spell',
                        'preferred_spell' => 'dark-harvest',
                        'danger_message' => 'L\'Ombre rampante absorbe toute lumière — une vague de ténèbres déferle !',
                    ],
                ],
            ],
            // === Monstres tier 2 (tâche 47) — lvl 10-15 ===
            'wyvern' => [
                'name' => 'Wyverne',
                'name_translations' => ['en' => 'Wyvern'],
                'life' => 110,
                'hit' => 88,
                'speed' => 14,
                'attack' => 'wind_lame',
                'level' => 10,
                'difficulty' => 4,
                'spells' => ['cyclone', 'fire_ball', 'air_slash'],
                'aiPattern' => [
                    'spell_chance' => 45,
                    'preferred_element' => 'air',
                    'danger_alert' => [
                        'threshold' => 30,
                        'message' => 'La Wyverne déploie ses ailes et rugit !',
                    ],
                ],
                'elementalResistances' => ['air' => 0.4, 'fire' => 0.2, 'earth' => -0.4],
            ],
            'cursed_knight' => [
                'name' => 'Chevalier maudit',
                'name_translations' => ['en' => 'Cursed Knight'],
                'life' => 140,
                'hit' => 82,
                'speed' => 7,
                'attack' => 'sharp_blade',
                'level' => 12,
                'difficulty' => 4,
                'spells' => ['blade_dance', 'shadow_bolt', 'death_grip'],
                'aiPattern' => [
                    'spell_chance' => 40,
                    'sequence' => ['attack', 'attack', 'spell', 'attack', 'spell'],
                    'danger_alert' => [
                        'threshold' => 25,
                        'message' => 'Le Chevalier maudit lève son épée spectrale !',
                    ],
                ],
                'elementalResistances' => ['dark' => 0.4, 'metal' => 0.3, 'light' => -0.5, 'fire' => -0.3],
            ],
            'naga' => [
                'name' => 'Naga',
                'name_translations' => ['en' => 'Naga'],
                'life' => 120,
                'hit' => 90,
                'speed' => 11,
                'attack' => 'water_jet',
                'level' => 13,
                'difficulty' => 4,
                'spells' => ['frost_bolt', 'poison_cloud', 'water_heal'],
                'aiPattern' => [
                    'spell_chance' => 50,
                    'preferred_element' => 'water',
                    'low_hp_heal' => ['threshold' => 35, 'action' => 'heal'],
                ],
                'elementalResistances' => ['water' => 0.5, 'beast' => 0.2, 'fire' => -0.4, 'air' => -0.3],
            ],
            'crystal_golem' => [
                'name' => 'Golem de cristal',
                'name_translations' => ['en' => 'Crystal Golem'],
                'life' => 180,
                'hit' => 75,
                'speed' => 4,
                'attack' => 'stone_throw',
                'level' => 15,
                'difficulty' => 5,
                'spells' => ['earthquake', 'stone_spikes', 'sacred_light', 'boulder_throw'],
                'aiPattern' => [
                    'spell_chance' => 45,
                    'sequence' => ['attack', 'attack', 'spell', 'attack', 'attack', 'spell'],
                    'danger_alert' => [
                        'threshold' => 25,
                        'message' => 'Le Golem de cristal brille d\'une lumière aveuglante !',
                        'spell' => 'earthquake',
                    ],
                ],
                'elementalResistances' => ['earth' => 0.5, 'light' => 0.3, 'metal' => 0.2, 'air' => -0.5, 'dark' => -0.3],
            ],
            // === Monstres tier 2 avancés (tâche 65) — lvl 15-25 ===
            'corrupted_archdruid' => [
                'name' => 'Archidruide corrompu',
                'name_translations' => ['en' => 'Corrupted Archdruid'],
                'life' => 160,
                'hit' => 82,
                'speed' => 9,
                'attack' => 'liana_whip',
                'level' => 16,
                'difficulty' => 4,
                'spells' => ['natural_healing', 'poison_cloud', 'entangling_roots', 'dark_harvest'],
                'aiPattern' => [
                    'role' => 'healer',
                    'spell_chance' => 55,
                    'preferred_element' => 'beast',
                    'low_hp_heal' => ['threshold' => 45, 'action' => 'heal'],
                    'danger_alert' => [
                        'threshold' => 30,
                        'message' => 'L\'Archidruide corrompu canalise l\'énergie de la forêt morte !',
                        'spell' => 'dark_harvest',
                    ],
                ],
                'elementalResistances' => ['beast' => 0.5, 'dark' => 0.3, 'fire' => -0.5, 'light' => -0.3],
            ],
            'lesser_lich' => [
                'name' => 'Liche mineure',
                'name_translations' => ['en' => 'Lesser Lich'],
                'life' => 130,
                'hit' => 85,
                'speed' => 8,
                'attack' => 'necrotic_touch',
                'level' => 18,
                'difficulty' => 5,
                'spells' => ['shadow_bolt', 'death_grip', 'soul_drain', 'dark_ritual'],
                'aiPattern' => [
                    'spell_chance' => 60,
                    'preferred_element' => 'dark',
                    'summon' => [
                        'monster_slug' => 'skeleton',
                        'count' => 2,
                        'max_per_fight' => 3,
                        'cooldown' => 3,
                        'level_offset' => 0,
                    ],
                    'danger_alert' => [
                        'threshold' => 25,
                        'message' => 'La Liche mineure lève les bras et invoque les morts !',
                        'spell' => 'dark_ritual',
                    ],
                ],
                'elementalResistances' => ['dark' => 0.6, 'light' => -0.6, 'fire' => -0.3, 'beast' => -0.2],
            ],
            'swamp_hydra' => [
                'name' => 'Hydre des marais',
                'name_translations' => ['en' => 'Swamp Hydra'],
                'life' => 220,
                'hit' => 80,
                'speed' => 6,
                'attack' => 'water_jet',
                'level' => 20,
                'difficulty' => 5,
                'spells' => ['tidal_wave', 'poison_cloud', 'venomous_bite', 'frost_bolt'],
                'aiPattern' => [
                    'spell_chance' => 50,
                    'sequence' => ['attack', 'spell', 'attack', 'attack', 'spell', 'spell'],
                    'low_hp_heal' => ['threshold' => 30, 'action' => 'attack'],
                    'danger_alert' => [
                        'threshold' => 25,
                        'message' => 'L\'Hydre des marais rugit de ses trois têtes !',
                        'spell' => 'tidal_wave',
                    ],
                ],
                'elementalResistances' => ['water' => 0.5, 'beast' => 0.3, 'fire' => -0.4, 'air' => -0.3, 'metal' => -0.2],
            ],
            'abyssal_blacksmith' => [
                'name' => 'Forgeron abyssal',
                'name_translations' => ['en' => 'Abyssal Blacksmith'],
                'life' => 260,
                'hit' => 78,
                'speed' => 5,
                'attack' => 'iron_fist',
                'level' => 24,
                'difficulty' => 5,
                'spells' => ['blade_dance', 'fire_wall', 'steel_shield', 'shrapnel_burst'],
                'aiPattern' => [
                    'spell_chance' => 45,
                    'sequence' => ['attack', 'attack', 'spell', 'attack', 'attack', 'attack', 'spell'],
                    'danger_alert' => [
                        'threshold' => 20,
                        'message' => 'Le Forgeron abyssal frappe son enclume — une onde de choc se propage !',
                        'spell' => 'shrapnel_burst',
                    ],
                ],
                'elementalResistances' => ['metal' => 0.6, 'fire' => 0.4, 'water' => -0.5, 'air' => -0.3],
            ],
            // === Monstre invocateur (tâche 69) ===
            'necromancer' => [
                'name' => 'Nécromancien',
                'name_translations' => ['en' => 'Necromancer'],
                'life' => 40,
                'hit' => 75,
                'speed' => 6,
                'attack' => 'necrotic_touch',
                'level' => 3,
                'difficulty' => 3,
                'spells' => ['shadow_bolt', 'natural_healing'],
                'aiPattern' => [
                    'role' => 'summoner',
                    'spell_chance' => 40,
                    'low_hp_heal' => ['threshold' => 40, 'action' => 'heal'],
                    'summon' => [
                        'monster_slug' => 'skeleton',
                        'count' => 1,
                        'max_per_fight' => 2,
                        'cooldown' => 3,
                        'level_offset' => -1,
                        'chance' => 35,
                    ],
                ],
                'elementalResistances' => ['dark' => 0.4, 'light' => -0.5],
            ],
            // === Boss de zone (tâche 66) ===
            'forest_guardian' => [
                'name' => 'Gardien de la Forêt',
                'name_translations' => ['en' => 'Forest Guardian'],
                'life' => 400,
                'hit' => 82,
                'speed' => 9,
                'attack' => 'leaf_blade',
                'level' => 15,
                'difficulty' => 5,
                'isBoss' => true,
                'spells' => ['leaf_blade', 'entangling_roots', 'nature_fury', 'primordial_roar'],
                'aiPattern' => [
                    'spell_chance' => 55,
                    'preferred_element' => 'beast',
                    'danger_alert' => [
                        'threshold' => 50,
                        'message' => 'Le Gardien de la Forêt se redresse, ses yeux luisent d\'une lumière verte...',
                        'spell' => 'primordial-roar',
                    ],
                ],
                'elementalResistances' => [
                    'beast' => 0.5,
                    'earth' => 0.5,
                    'fire' => -0.5,
                    'metal' => -0.3,
                    'water' => 0.25,
                ],
                'bossPhases' => [
                    [
                        'hpThreshold' => 100,
                        'name' => 'Phase 1 — Éveil sylvestre',
                        'action' => 'spell',
                    ],
                    [
                        'hpThreshold' => 50,
                        'name' => 'Phase 2 — Fureur de la nature',
                        'action' => 'spell',
                        'preferred_spell' => 'primordial-roar',
                        'danger_message' => 'Le Gardien rugit — la forêt entière tremble de rage !',
                    ],
                ],
            ],
            'forge_lord' => [
                'name' => 'Seigneur de la Forge',
                'name_translations' => ['en' => 'Forge Lord'],
                'life' => 500,
                'hit' => 80,
                'speed' => 7,
                'attack' => 'iron_fist',
                'level' => 20,
                'difficulty' => 5,
                'isBoss' => true,
                'spells' => ['blade_dance', 'shrapnel_burst', 'shadow_bolt', 'metal_skin', 'dark_forge_blast'],
                'aiPattern' => [
                    'spell_chance' => 50,
                    'preferred_element' => 'metal',
                    'danger_alert' => [
                        'threshold' => 60,
                        'message' => 'Le Seigneur de la Forge frappe son enclume — l\'air vibre de chaleur...',
                        'spell' => 'blade-dance',
                    ],
                ],
                'elementalResistances' => [
                    'metal' => 0.6,
                    'dark' => 0.4,
                    'water' => -0.5,
                    'light' => -0.4,
                    'earth' => 0.2,
                ],
                'bossPhases' => [
                    [
                        'hpThreshold' => 100,
                        'name' => 'Phase 1 — Le Forgeron',
                        'action' => 'spell',
                    ],
                    [
                        'hpThreshold' => 60,
                        'name' => 'Phase 2 — Métal en fusion',
                        'action' => 'spell',
                        'preferred_spell' => 'blade-dance',
                        'danger_message' => 'Le Seigneur plonge ses bras dans le métal en fusion !',
                    ],
                    [
                        'hpThreshold' => 30,
                        'name' => 'Phase 3 — Forge obscure',
                        'action' => 'spell',
                        'preferred_spell' => 'dark-forge-blast',
                        'danger_message' => 'Les ténèbres envahissent la forge — le Seigneur libère sa puissance ultime !',
                    ],
                ],
            ],
            // === World boss (tâche 71) ===
            'ancient_wyrm' => [
                'name' => 'Wyrm Ancien',
                'name_translations' => ['en' => 'Ancient Wyrm'],
                'life' => 2000,
                'hit' => 95,
                'speed' => 8,
                'attack' => 'fire_ball',
                'level' => 30,
                'difficulty' => 5,
                'isBoss' => true,
                'spells' => ['dragon_breath', 'meteor_strike', 'volcanic_eruption', 'fire_nova'],
                'aiPattern' => [
                    'spell_chance' => 65,
                    'preferred_element' => 'fire',
                    'danger_alert' => [
                        'threshold' => 40,
                        'message' => 'Le Wyrm Ancien déploie ses ailes — la terre tremble sous sa fureur !',
                        'spell' => 'volcanic-eruption',
                    ],
                ],
                'elementalResistances' => [
                    'fire' => 0.8,
                    'dark' => 0.3,
                    'water' => -0.5,
                    'light' => -0.3,
                    'earth' => 0.2,
                ],
                'bossPhases' => [
                    [
                        'hpThreshold' => 100,
                        'name' => 'Phase 1 — Eveil',
                        'action' => 'spell',
                    ],
                    [
                        'hpThreshold' => 60,
                        'name' => 'Phase 2 — Souffle devastateur',
                        'action' => 'spell',
                        'preferred_spell' => 'dragon-breath',
                        'danger_message' => 'Le Wyrm crache un torrent de flammes anciennes !',
                    ],
                    [
                        'hpThreshold' => 30,
                        'name' => 'Phase 3 — Apocalypse',
                        'action' => 'spell',
                        'preferred_spell' => 'volcanic-eruption',
                        'danger_message' => 'Le Wyrm Ancien entre en rage — le ciel s\'embrase !',
                    ],
                ],
            ],
            'dragon' => [
                'name' => 'Dragon ancestral',
                'name_translations' => ['en' => 'Ancestral Dragon'],
                'life' => 250,
                'hit' => 90,
                'speed' => 12,
                'attack' => 'fire_ball',
                'level' => 5,
                'difficulty' => 5,
                'isBoss' => true,
                'spells' => ['dragon_breath', 'fire_nova', 'meteor_strike', 'volcanic_eruption'],
                'aiPattern' => [
                    'spell_chance' => 60,
                    'preferred_element' => 'fire',
                    'danger_alert' => [
                        'threshold' => 50,
                        'message' => 'Le Dragon prend une grande inspiration...',
                        'spell' => 'dragon-breath',
                    ],
                ],
                'elementalResistances' => [
                    'fire' => 0.75,
                    'water' => -0.50,
                    'earth' => 0.25,
                    'beast' => -0.25,
                ],
                'bossPhases' => [
                    [
                        'hpThreshold' => 100,
                        'name' => 'Phase 1 — Attaque',
                        'action' => 'spell',
                    ],
                    [
                        'hpThreshold' => 60,
                        'name' => 'Phase 2 — Souffle de feu',
                        'action' => 'spell',
                        'preferred_spell' => 'dragon-breath',
                        'danger_message' => 'Le Dragon se dresse de toute sa hauteur !',
                    ],
                    [
                        'hpThreshold' => 30,
                        'name' => 'Phase 3 — Rage',
                        'action' => 'spell',
                        'preferred_spell' => 'volcanic-eruption',
                        'danger_message' => 'Le Dragon rugit de rage — son corps tremble de fureur !',
                    ],
                ],
            ],
            // === Boss final : Gardien de la Convergence (tache 94 — Acte 3) ===
            'convergence_guardian' => [
                'name' => 'Gardien de la Convergence',
                'name_translations' => ['en' => 'Guardian of the Convergence'],
                'life' => 800,
                'hit' => 90,
                'speed' => 10,
                'attack' => 'holy_light',
                'level' => 30,
                'difficulty' => 5,
                'isBoss' => true,
                'spells' => ['convergence_pulse', 'amethyst_shatter', 'fragment_barrier', 'meteor_strike', 'shadow_bolt'],
                'aiPattern' => [
                    'spell_chance' => 60,
                    'preferred_element' => 'light',
                    'danger_alert' => [
                        'threshold' => 40,
                        'message' => 'Le Gardien absorbe l\'énergie des quatre fragments — le Nexus tremble !',
                        'spell' => 'convergence-pulse',
                    ],
                ],
                'elementalResistances' => [
                    'light' => 0.5,
                    'dark' => 0.3,
                    'fire' => 0.2,
                    'water' => 0.2,
                    'earth' => 0.2,
                    'beast' => -0.3,
                    'metal' => -0.2,
                ],
                'bossPhases' => [
                    [
                        'hpThreshold' => 100,
                        'name' => 'Phase 1 — Le Gardien s\'éveille',
                        'action' => 'spell',
                    ],
                    [
                        'hpThreshold' => 60,
                        'name' => 'Phase 2 — Résonance des fragments',
                        'action' => 'spell',
                        'preferred_spell' => 'convergence-pulse',
                        'danger_message' => 'Les quatre fragments résonnent — une lumière aveuglante envahit le Nexus !',
                    ],
                    [
                        'hpThreshold' => 30,
                        'name' => 'Phase 3 — Fracture de l\'Améthyste',
                        'action' => 'spell',
                        'preferred_spell' => 'amethyst-shatter',
                        'danger_message' => 'Le cristal d\'Améthyste se fissure — le Gardien libère toute sa puissance !',
                    ],
                ],
            ],
            // === Boss de donjon : Racines de la foret (tache 84) ===
            'ancient_root' => [
                'name' => 'Racine Ancienne',
                'name_translations' => ['en' => 'Ancient Root'],
                'life' => 350,
                'hit' => 75,
                'speed' => 7,
                'attack' => 'entangling_roots',
                'level' => 10,
                'difficulty' => 4,
                'isBoss' => true,
                'spells' => ['entangling_roots', 'nature_fury', 'poison_cloud', 'primordial_roar'],
                'aiPattern' => [
                    'spell_chance' => 50,
                    'preferred_element' => 'beast',
                    'danger_alert' => [
                        'threshold' => 40,
                        'message' => 'La Racine Ancienne s\'enfonce dans le sol... le sol tremble violemment !',
                        'spell' => 'primordial-roar',
                    ],
                ],
                'elementalResistances' => [
                    'beast' => 0.4,
                    'earth' => 0.5,
                    'fire' => -0.5,
                    'metal' => -0.3,
                    'water' => 0.3,
                ],
                'bossPhases' => [
                    [
                        'hpThreshold' => 100,
                        'name' => 'Phase 1 — Racines dormantes',
                        'action' => 'spell',
                    ],
                    [
                        'hpThreshold' => 60,
                        'name' => 'Phase 2 — Eveil corrompu',
                        'action' => 'spell',
                        'preferred_spell' => 'nature-fury',
                        'danger_message' => 'La Racine Ancienne se deploie — des spores toxiques envahissent l\'air !',
                    ],
                    [
                        'hpThreshold' => 30,
                        'name' => 'Phase 3 — Fureur primordiale',
                        'action' => 'spell',
                        'preferred_spell' => 'primordial-roar',
                        'danger_message' => 'La Racine Ancienne pousse un cri qui fait trembler les murs du donjon !',
                    ],
                ],
            ],
        ];

        foreach ($monsters as $key => $data) {
            $monster = new Monster();
            $monster->setName($data['name']);
            $monster->setSlug($key);
            $monster->setLife($data['life']);
            $monster->setHit($data['hit']);
            $monster->setSpeed($data['speed']);
            $monster->setLevel($data['level']);
            $monster->setDifficulty($data['difficulty'] ?? $data['level']);

            // Sort d'attaque de base
            $attackSpell = $this->getReference($data['attack'], Spell::class);
            $monster->setAttack($attackSpell);

            // Sorts additionnels
            if (isset($data['spells'])) {
                $spells = new ArrayCollection();
                foreach ($data['spells'] as $spellRef) {
                    $spells->add($this->getReference($spellRef, Spell::class));
                }
                $monster->setSpells($spells);
            }

            // AI pattern
            if (isset($data['aiPattern'])) {
                $monster->setAiPattern($data['aiPattern']);
            }

            // Resistances elementaires
            if (isset($data['elementalResistances'])) {
                $monster->setElementalResistances($data['elementalResistances']);
            }

            // Boss
            if (isset($data['isBoss']) && $data['isBoss']) {
                $monster->setIsBoss(true);
            }

            // Boss phases
            if (isset($data['bossPhases'])) {
                $monster->setBossPhases($data['bossPhases']);
            }

            // Traductions localisees du nom (EN/DE/...) — sous-phase 135 s3e.b
            if (isset($data['name_translations']) && is_array($data['name_translations'])) {
                $monster->setNameTranslations($data['name_translations']);
            }

            $monster->setCreatedAt(new \DateTime());
            $monster->setUpdatedAt(new \DateTime());

            $manager->persist($monster);
            $this->addReference($key, $monster);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SpellFixtures::class,
        ];
    }
}
