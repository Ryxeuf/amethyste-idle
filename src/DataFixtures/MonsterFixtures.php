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
                'life' => 18,
                'hit' => 75,
                'speed' => 8,
                'attack' => 'none_attack_1',
                'level' => 1,
                'difficulty' => 1,
            ],
            'bat' => [
                'name' => 'Chauve-souris',
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
            // === Monstres élémentaires tier 1 (tâche 28) ===
            'salamander' => [
                'name' => 'Salamandre',
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
                'life' => 50,
                'hit' => 88,
                'speed' => 14,
                'attack' => 'sharp_blade',
                'level' => 4,
                'difficulty' => 3,
                'spells' => ['venomous_bite', 'savage_charge'],
                'aiPattern' => [
                    'spell_chance' => 35,
                    'sequence' => ['attack', 'attack', 'spell'],
                    'danger_alert' => [
                        'threshold' => 30,
                        'message' => 'Le Loup alpha hurle à la lune !',
                    ],
                ],
                'elementalResistances' => ['beast' => 0.5, 'fire' => -0.3, 'metal' => -0.3],
            ],
            'will_o_wisp' => [
                'name' => 'Feu follet',
                'life' => 22,
                'hit' => 85,
                'speed' => 15,
                'attack' => 'holy_light',
                'level' => 2,
                'difficulty' => 2,
                'spells' => ['sacred_light', 'light_aura'],
                'aiPattern' => [
                    'spell_chance' => 50,
                    'preferred_element' => 'light',
                ],
                'elementalResistances' => ['light' => 0.5, 'dark' => -0.5],
            ],
            'creeping_shadow' => [
                'name' => 'Ombre rampante',
                'life' => 60,
                'hit' => 85,
                'speed' => 8,
                'attack' => 'shadow_bolt',
                'level' => 5,
                'difficulty' => 3,
                'spells' => ['soul_drain', 'death_grip', 'dark_harvest'],
                'aiPattern' => [
                    'spell_chance' => 55,
                    'preferred_element' => 'dark',
                    'danger_alert' => [
                        'threshold' => 30,
                        'message' => 'L\'Ombre rampante se dilate dans l\'obscurité !',
                    ],
                ],
                'elementalResistances' => ['dark' => 0.6, 'light' => -0.5, 'fire' => -0.2],
            ],
            // === Monstres tier 2 (tâche 47) — lvl 10-15 ===
            'wyvern' => [
                'name' => 'Wyverne',
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
            // === Monstre soigneur multi-mob (tâche 49) ===
            'necromancer' => [
                'name' => 'Nécromancien',
                'life' => 40,
                'hit' => 75,
                'speed' => 6,
                'attack' => 'necrotic_touch',
                'level' => 3,
                'difficulty' => 3,
                'spells' => ['shadow_bolt', 'natural_healing'],
                'aiPattern' => [
                    'role' => 'healer',
                    'spell_chance' => 40,
                    'low_hp_heal' => ['threshold' => 40, 'action' => 'heal'],
                    'summon' => [
                        'monster_slug' => 'skeleton',
                        'max_summons' => 2,
                        'chance' => 35,
                    ],
                ],
                'elementalResistances' => ['dark' => 0.4, 'light' => -0.5],
            ],
            'dragon' => [
                'name' => 'Dragon ancestral',
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
