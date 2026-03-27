<?php

namespace App\DataFixtures;

use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\MonsterItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MonsterItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $monsterItems = [
            // --- Niveau 1 : Gelée, Gobelin, Chauve-souris, Rat géant, Zombie ---
            ['monster' => 'slime', 'item' => 'mushroom', 'probability' => 60],
            ['monster' => 'slime', 'item' => 'healing_potion_small', 'probability' => 10],
            ['monster' => 'slime', 'item' => 'bread', 'probability' => 20],
            ['monster' => 'slime', 'item' => 'starter_gloves', 'probability' => 4],
            ['monster' => 'slime', 'item' => 'starter_boots', 'probability' => 3],

            ['monster' => 'goblin', 'item' => 'mushroom', 'probability' => 50],
            ['monster' => 'goblin', 'item' => 'beer_pint', 'probability' => 30],
            ['monster' => 'goblin', 'item' => 'healing_potion_small', 'probability' => 15],
            ['monster' => 'goblin', 'item' => 'bread', 'probability' => 25],
            ['monster' => 'goblin', 'item' => 'wooden_sword', 'probability' => 5],
            ['monster' => 'goblin', 'item' => 'starter_helmet', 'probability' => 4],

            ['monster' => 'bat', 'item' => 'leather_skin_1', 'probability' => 40],
            ['monster' => 'bat', 'item' => 'mushroom', 'probability' => 25],
            ['monster' => 'bat', 'item' => 'starter_legs', 'probability' => 3],

            ['monster' => 'giant_rat', 'item' => 'leather_skin_1', 'probability' => 60],
            ['monster' => 'giant_rat', 'item' => 'mushroom', 'probability' => 35],
            ['monster' => 'giant_rat', 'item' => 'healing_potion_small', 'probability' => 10],
            ['monster' => 'giant_rat', 'item' => 'bread', 'probability' => 15],
            ['monster' => 'giant_rat', 'item' => 'starter_chest', 'probability' => 3],
            ['monster' => 'giant_rat', 'item' => 'starter_shield', 'probability' => 2],

            ['monster' => 'zombie', 'item' => 'mushroom', 'probability' => 75],
            ['monster' => 'zombie', 'item' => 'leather_skin_1', 'probability' => 90],
            ['monster' => 'zombie', 'item' => 'pickaxe', 'probability' => 10],
            ['monster' => 'zombie', 'item' => 'antidote', 'probability' => 12],
            ['monster' => 'zombie', 'item' => 'wooden_sword', 'probability' => 6],
            ['monster' => 'zombie', 'item' => 'starter_helmet', 'probability' => 5],
            ['monster' => 'zombie', 'item' => 'starter_chest', 'probability' => 4],

            // --- Niveau 2 : Squelette, Araignée, Serpent, Taiju, Spectre, Banshee ---
            ['monster' => 'skeleton', 'item' => 'leather_skin_2', 'probability' => 50],
            ['monster' => 'skeleton', 'item' => 'short_sword', 'probability' => 8],
            ['monster' => 'skeleton', 'item' => 'healing_potion_small', 'probability' => 20],
            ['monster' => 'skeleton', 'item' => 'bread', 'probability' => 15],

            ['monster' => 'spider', 'item' => 'leather_skin_1', 'probability' => 70],
            ['monster' => 'spider', 'item' => 'mushroom', 'probability' => 40],
            ['monster' => 'spider', 'item' => 'energy_potion_small', 'probability' => 12],
            ['monster' => 'spider', 'item' => 'antidote', 'probability' => 20],

            ['monster' => 'venom_snake', 'item' => 'leather_skin_1', 'probability' => 65],
            ['monster' => 'venom_snake', 'item' => 'healing_potion_small', 'probability' => 20],
            ['monster' => 'venom_snake', 'item' => 'antidote', 'probability' => 30],

            ['monster' => 'taiju', 'item' => 'mushroom', 'probability' => 80],
            ['monster' => 'taiju', 'item' => 'wood_log', 'probability' => 50],
            ['monster' => 'taiju', 'item' => 'healing_potion_small', 'probability' => 15],
            ['monster' => 'taiju', 'item' => 'grilled_meat', 'probability' => 10],

            ['monster' => 'specter', 'item' => 'ancient_scroll', 'probability' => 8],
            ['monster' => 'specter', 'item' => 'energy_potion_small', 'probability' => 25],
            ['monster' => 'specter', 'item' => 'scroll_identification', 'probability' => 5],

            ['monster' => 'banshee', 'item' => 'ancient_scroll', 'probability' => 12],
            ['monster' => 'banshee', 'item' => 'energy_potion_small', 'probability' => 30],
            ['monster' => 'banshee', 'item' => 'healing_potion_small', 'probability' => 20],
            ['monster' => 'banshee', 'item' => 'scroll_identification', 'probability' => 8],

            // --- Niveau 3 : Ochu, Loup-garou, Gargouille, Troll, Élémentaire ---
            ['monster' => 'ochu', 'item' => 'mushroom', 'probability' => 90],
            ['monster' => 'ochu', 'item' => 'wood_log', 'probability' => 60],
            ['monster' => 'ochu', 'item' => 'healing_potion_medium', 'probability' => 10],
            ['monster' => 'ochu', 'item' => 'antidote', 'probability' => 15],
            ['monster' => 'ochu', 'item' => 'grilled_meat', 'probability' => 12],

            ['monster' => 'werewolf', 'item' => 'leather_skin_2', 'probability' => 80],
            ['monster' => 'werewolf', 'item' => 'short_sword', 'probability' => 12],
            ['monster' => 'werewolf', 'item' => 'healing_potion_small', 'probability' => 25],
            ['monster' => 'werewolf', 'item' => 'grilled_meat', 'probability' => 20],

            ['monster' => 'gargoyle', 'item' => 'leather_skin_2', 'probability' => 50],
            ['monster' => 'gargoyle', 'item' => 'pickaxe', 'probability' => 15],
            ['monster' => 'gargoyle', 'item' => 'energy_potion_small', 'probability' => 20],
            ['monster' => 'gargoyle', 'item' => 'scroll_teleport', 'probability' => 5],

            ['monster' => 'troll', 'item' => 'leather_skin_2', 'probability' => 70],
            ['monster' => 'troll', 'item' => 'wood_log', 'probability' => 50],
            ['monster' => 'troll', 'item' => 'long_sword', 'probability' => 5],
            ['monster' => 'troll', 'item' => 'healing_potion_medium', 'probability' => 15],
            ['monster' => 'troll', 'item' => 'stew', 'probability' => 8],

            ['monster' => 'fire_elemental', 'item' => 'materia_fire_ball', 'probability' => 8],
            ['monster' => 'fire_elemental', 'item' => 'energy_potion_small', 'probability' => 30],
            ['monster' => 'fire_elemental', 'item' => 'healing_potion_small', 'probability' => 20],
            ['monster' => 'fire_elemental', 'item' => 'scroll_xp_boost', 'probability' => 3],

            // --- Niveau 4 : Griffon, Minotaure, Golem ---
            ['monster' => 'griffin', 'item' => 'leather_skin_2', 'probability' => 75],
            ['monster' => 'griffin', 'item' => 'healing_potion_medium', 'probability' => 20],
            ['monster' => 'griffin', 'item' => 'ancient_scroll', 'probability' => 10],
            ['monster' => 'griffin', 'item' => 'healing_potion_major', 'probability' => 8],
            ['monster' => 'griffin', 'item' => 'stew', 'probability' => 12],

            ['monster' => 'minotaur', 'item' => 'long_sword', 'probability' => 10],
            ['monster' => 'minotaur', 'item' => 'leather_armor', 'probability' => 8],
            ['monster' => 'minotaur', 'item' => 'healing_potion_medium', 'probability' => 25],
            ['monster' => 'minotaur', 'item' => 'healing_potion_major', 'probability' => 10],
            ['monster' => 'minotaur', 'item' => 'grilled_meat', 'probability' => 15],

            ['monster' => 'stone_golem', 'item' => 'pickaxe', 'probability' => 30],
            ['monster' => 'stone_golem', 'item' => 'materia_stone_throw', 'probability' => 10],
            ['monster' => 'stone_golem', 'item' => 'healing_potion_medium', 'probability' => 20],
            ['monster' => 'stone_golem', 'item' => 'iron_sword', 'probability' => 5],
            ['monster' => 'stone_golem', 'item' => 'scroll_teleport', 'probability' => 8],

            // --- Monstres tier 2 (tâche 47) — lvl 10-15 ---
            // Wyverne (Air/Feu, lvl 10)
            ['monster' => 'wyvern', 'item' => 'leather_skin_2', 'probability' => 70],
            ['monster' => 'wyvern', 'item' => 'healing_potion_medium', 'probability' => 25],
            ['monster' => 'wyvern', 'item' => 'healing_potion_major', 'probability' => 10],
            ['monster' => 'wyvern', 'item' => 'materia_wind_lame', 'probability' => 6],
            ['monster' => 'wyvern', 'item' => 'materia_fire_ball', 'probability' => 5],
            ['monster' => 'wyvern', 'item' => 'stew', 'probability' => 15],
            ['monster' => 'wyvern', 'item' => 't2_air_sword', 'probability' => 5],
            ['monster' => 'wyvern', 'item' => 't2_fire_helmet', 'probability' => 4],

            // Chevalier maudit (Dark/Métal, lvl 12)
            ['monster' => 'cursed_knight', 'item' => 'leather_skin_2', 'probability' => 60],
            ['monster' => 'cursed_knight', 'item' => 'long_sword', 'probability' => 8],
            ['monster' => 'cursed_knight', 'item' => 'iron_sword', 'probability' => 5],
            ['monster' => 'cursed_knight', 'item' => 'healing_potion_medium', 'probability' => 25],
            ['monster' => 'cursed_knight', 'item' => 'healing_potion_major', 'probability' => 12],
            ['monster' => 'cursed_knight', 'item' => 'materia_vital_drain', 'probability' => 5],
            ['monster' => 'cursed_knight', 'item' => 'materia_steel_riposte', 'probability' => 5],
            ['monster' => 'cursed_knight', 'item' => 'ancient_scroll', 'probability' => 10],

            // Naga (Eau/Bête, lvl 13)
            ['monster' => 'naga', 'item' => 'leather_skin_2', 'probability' => 65],
            ['monster' => 'naga', 'item' => 'healing_potion_medium', 'probability' => 30],
            ['monster' => 'naga', 'item' => 'healing_potion_major', 'probability' => 10],
            ['monster' => 'naga', 'item' => 'antidote', 'probability' => 25],
            ['monster' => 'naga', 'item' => 'materia_frost_mist', 'probability' => 6],
            ['monster' => 'naga', 'item' => 'materia_savage_bite', 'probability' => 4],
            ['monster' => 'naga', 'item' => 't2_water_sword', 'probability' => 5],
            ['monster' => 'naga', 'item' => 't2_water_shield', 'probability' => 4],

            // Golem de cristal (Terre/Lumière, lvl 15)
            ['monster' => 'crystal_golem', 'item' => 'leather_skin_2', 'probability' => 75],
            ['monster' => 'crystal_golem', 'item' => 'healing_potion_major', 'probability' => 20],
            ['monster' => 'crystal_golem', 'item' => 'healing_potion_medium', 'probability' => 30],
            ['monster' => 'crystal_golem', 'item' => 'materia_stone_throw', 'probability' => 8],
            ['monster' => 'crystal_golem', 'item' => 'materia_light_blessing', 'probability' => 5],
            ['monster' => 'crystal_golem', 'item' => 'scroll_xp_boost', 'probability' => 6],
            ['monster' => 'crystal_golem', 'item' => 'scroll_teleport', 'probability' => 8],
            ['monster' => 'crystal_golem', 'item' => 't2_earth_shield', 'probability' => 5],
            ['monster' => 'crystal_golem', 'item' => 't2_earth_chest', 'probability' => 4],

            // --- Monstres tier 2 avancés (tâche 65) — lvl 15-25 ---
            // Archidruide corrompu (Bête/Ombre, lvl 16)
            ['monster' => 'corrupted_archdruid', 'item' => 'leather_skin_2', 'probability' => 70],
            ['monster' => 'corrupted_archdruid', 'item' => 'healing_potion_major', 'probability' => 20],
            ['monster' => 'corrupted_archdruid', 'item' => 'healing_potion_medium', 'probability' => 30],
            ['monster' => 'corrupted_archdruid', 'item' => 'antidote', 'probability' => 25],
            ['monster' => 'corrupted_archdruid', 'item' => 'materia_savage_bite', 'probability' => 6],
            ['monster' => 'corrupted_archdruid', 'item' => 'materia_vital_drain', 'probability' => 5],
            ['monster' => 'corrupted_archdruid', 'item' => 't2_earth_chest', 'probability' => 5],
            ['monster' => 'corrupted_archdruid', 'item' => 't2_earth_gloves', 'probability' => 4],

            // Liche mineure (Ombre, lvl 18)
            ['monster' => 'lesser_lich', 'item' => 'ancient_scroll', 'probability' => 25],
            ['monster' => 'lesser_lich', 'item' => 'healing_potion_major', 'probability' => 25],
            ['monster' => 'lesser_lich', 'item' => 'energy_potion_small', 'probability' => 30],
            ['monster' => 'lesser_lich', 'item' => 'materia_vital_drain', 'probability' => 7],
            ['monster' => 'lesser_lich', 'item' => 'materia_light_blessing', 'probability' => 4],
            ['monster' => 'lesser_lich', 'item' => 'scroll_xp_boost', 'probability' => 8],
            ['monster' => 'lesser_lich', 'item' => 't2_air_helmet', 'probability' => 5],
            ['monster' => 'lesser_lich', 'item' => 't2_air_boots', 'probability' => 4],

            // Hydre des marais (Eau/Bête, lvl 20)
            ['monster' => 'swamp_hydra', 'item' => 'leather_skin_2', 'probability' => 80],
            ['monster' => 'swamp_hydra', 'item' => 'healing_potion_major', 'probability' => 30],
            ['monster' => 'swamp_hydra', 'item' => 'healing_potion_medium', 'probability' => 35],
            ['monster' => 'swamp_hydra', 'item' => 'antidote', 'probability' => 30],
            ['monster' => 'swamp_hydra', 'item' => 'materia_frost_mist', 'probability' => 7],
            ['monster' => 'swamp_hydra', 'item' => 'materia_savage_bite', 'probability' => 5],
            ['monster' => 'swamp_hydra', 'item' => 'scroll_teleport', 'probability' => 10],
            ['monster' => 'swamp_hydra', 'item' => 't2_water_sword', 'probability' => 6],
            ['monster' => 'swamp_hydra', 'item' => 't2_water_chest', 'probability' => 5],

            // Forgeron abyssal (Métal/Feu, lvl 24)
            ['monster' => 'abyssal_blacksmith', 'item' => 'leather_skin_2', 'probability' => 75],
            ['monster' => 'abyssal_blacksmith', 'item' => 'healing_potion_major', 'probability' => 35],
            ['monster' => 'abyssal_blacksmith', 'item' => 'iron_sword', 'probability' => 12],
            ['monster' => 'abyssal_blacksmith', 'item' => 'materia_steel_riposte', 'probability' => 8],
            ['monster' => 'abyssal_blacksmith', 'item' => 'materia_fire_ball', 'probability' => 6],
            ['monster' => 'abyssal_blacksmith', 'item' => 'scroll_xp_boost', 'probability' => 10],
            ['monster' => 'abyssal_blacksmith', 'item' => 't2_fire_sword', 'probability' => 6],
            ['monster' => 'abyssal_blacksmith', 'item' => 't2_fire_chest', 'probability' => 5],
            ['monster' => 'abyssal_blacksmith', 'item' => 't2_fire_shield', 'probability' => 5],

            // --- Boss de zone : Gardien de la Forêt (tâche 66) ---
            ['monster' => 'forest_guardian', 'item' => 'healing_potion_major', 'probability' => 80],
            ['monster' => 'forest_guardian', 'item' => 'energy_potion_small', 'probability' => 60],
            ['monster' => 'forest_guardian', 'item' => 'ancient_scroll', 'probability' => 40],
            ['monster' => 'forest_guardian', 'item' => 'scroll_xp_boost', 'probability' => 15],
            ['monster' => 'forest_guardian', 'item' => 'materia_savage_bite', 'probability' => 20],
            // Drops uniques garantis
            ['monster' => 'forest_guardian', 'item' => 'guardian_bark_armor', 'probability' => 12, 'guaranteed' => true],
            ['monster' => 'forest_guardian', 'item' => 'guardian_thorn_staff', 'probability' => 12, 'guaranteed' => true],

            // --- Boss de zone : Seigneur de la Forge (tâche 66) ---
            ['monster' => 'forge_lord', 'item' => 'healing_potion_major', 'probability' => 80],
            ['monster' => 'forge_lord', 'item' => 'energy_potion_small', 'probability' => 60],
            ['monster' => 'forge_lord', 'item' => 'ancient_scroll', 'probability' => 50],
            ['monster' => 'forge_lord', 'item' => 'scroll_xp_boost', 'probability' => 15],
            ['monster' => 'forge_lord', 'item' => 'materia_steel_riposte', 'probability' => 20],
            // Drops uniques garantis
            ['monster' => 'forge_lord', 'item' => 'forgelord_obsidian_blade', 'probability' => 10, 'guaranteed' => true],
            ['monster' => 'forge_lord', 'item' => 'forgelord_dark_plate', 'probability' => 10, 'guaranteed' => true],

            // --- Boss : Dragon ---
            ['monster' => 'dragon', 'item' => 'materia_fire_ball', 'probability' => 40],
            ['monster' => 'dragon', 'item' => 'materia_flame_rain', 'probability' => 25],
            ['monster' => 'dragon', 'item' => 'iron_sword', 'probability' => 20],
            ['monster' => 'dragon', 'item' => 'healing_potion_medium', 'probability' => 80],
            ['monster' => 'dragon', 'item' => 'ancient_scroll', 'probability' => 50],
            ['monster' => 'dragon', 'item' => 'leather_armor', 'probability' => 15],
            ['monster' => 'dragon', 'item' => 'healing_potion_major', 'probability' => 40],
            ['monster' => 'dragon', 'item' => 'stew', 'probability' => 30],
            ['monster' => 'dragon', 'item' => 'scroll_xp_boost', 'probability' => 10],
            ['monster' => 'dragon', 'item' => 'scroll_teleport', 'probability' => 20],
            // Récompenses légendaires boss-only (drop garanti)
            ['monster' => 'dragon', 'item' => 'dragon_fang_blade', 'probability' => 15, 'guaranteed' => true],
            ['monster' => 'dragon', 'item' => 'dragon_scale_armor', 'probability' => 10, 'guaranteed' => true],

            // --- Monstres élémentaires tier 1 (tâche 28) ---
            // Salamandre (Feu, lvl 3)
            ['monster' => 'salamander', 'item' => 'mushroom', 'probability' => 40],
            ['monster' => 'salamander', 'item' => 'healing_potion_small', 'probability' => 20],
            ['monster' => 'salamander', 'item' => 'grilled_meat', 'probability' => 15],
            ['monster' => 'salamander', 'item' => 'materia_fire_ball', 'probability' => 6],
            ['monster' => 'salamander', 'item' => 'energy_potion_small', 'probability' => 15],

            // Ondine (Eau, lvl 2)
            ['monster' => 'undine', 'item' => 'mushroom', 'probability' => 50],
            ['monster' => 'undine', 'item' => 'healing_potion_small', 'probability' => 25],
            ['monster' => 'undine', 'item' => 'antidote', 'probability' => 20],
            ['monster' => 'undine', 'item' => 'materia_frost_mist', 'probability' => 6],
            ['monster' => 'undine', 'item' => 'energy_potion_small', 'probability' => 12],

            // Sylphe (Air, lvl 4)
            ['monster' => 'sylph', 'item' => 'leather_skin_2', 'probability' => 50],
            ['monster' => 'sylph', 'item' => 'healing_potion_medium', 'probability' => 15],
            ['monster' => 'sylph', 'item' => 'ancient_scroll', 'probability' => 8],
            ['monster' => 'sylph', 'item' => 'materia_wind_lame', 'probability' => 5],
            ['monster' => 'sylph', 'item' => 'scroll_teleport', 'probability' => 6],

            // Golem d'argile (Terre, lvl 5)
            ['monster' => 'clay_golem', 'item' => 'leather_skin_2', 'probability' => 60],
            ['monster' => 'clay_golem', 'item' => 'healing_potion_medium', 'probability' => 20],
            ['monster' => 'clay_golem', 'item' => 'pickaxe', 'probability' => 25],
            ['monster' => 'clay_golem', 'item' => 'materia_stone_throw', 'probability' => 8],
            ['monster' => 'clay_golem', 'item' => 'stew', 'probability' => 10],

            // Automate rouillé (Métal, lvl 3)
            ['monster' => 'rusty_automaton', 'item' => 'leather_skin_2', 'probability' => 45],
            ['monster' => 'rusty_automaton', 'item' => 'healing_potion_small', 'probability' => 20],
            ['monster' => 'rusty_automaton', 'item' => 'short_sword', 'probability' => 8],
            ['monster' => 'rusty_automaton', 'item' => 'materia_steel_riposte', 'probability' => 5],
            ['monster' => 'rusty_automaton', 'item' => 'energy_potion_small', 'probability' => 18],

            // Loup alpha (Bête, lvl 4)
            ['monster' => 'alpha_wolf', 'item' => 'leather_skin_2', 'probability' => 75],
            ['monster' => 'alpha_wolf', 'item' => 'grilled_meat', 'probability' => 30],
            ['monster' => 'alpha_wolf', 'item' => 'healing_potion_small', 'probability' => 20],
            ['monster' => 'alpha_wolf', 'item' => 'materia_savage_bite', 'probability' => 5],
            ['monster' => 'alpha_wolf', 'item' => 'healing_potion_medium', 'probability' => 10],

            // Feu follet (Lumière, lvl 2)
            ['monster' => 'will_o_wisp', 'item' => 'mushroom', 'probability' => 45],
            ['monster' => 'will_o_wisp', 'item' => 'energy_potion_small', 'probability' => 30],
            ['monster' => 'will_o_wisp', 'item' => 'healing_potion_small', 'probability' => 15],
            ['monster' => 'will_o_wisp', 'item' => 'materia_light_blessing', 'probability' => 6],
            ['monster' => 'will_o_wisp', 'item' => 'scroll_identification', 'probability' => 8],

            // Ombre rampante (Ombre, lvl 5)
            ['monster' => 'creeping_shadow', 'item' => 'ancient_scroll', 'probability' => 15],
            ['monster' => 'creeping_shadow', 'item' => 'healing_potion_medium', 'probability' => 20],
            ['monster' => 'creeping_shadow', 'item' => 'energy_potion_small', 'probability' => 25],
            ['monster' => 'creeping_shadow', 'item' => 'materia_vital_drain', 'probability' => 6],
            ['monster' => 'creeping_shadow', 'item' => 'scroll_xp_boost', 'probability' => 4],

            // --- Équipement Tier 2 élémentaire (tâche 29) ---
            // Monstres Niveau 2
            ['monster' => 'skeleton', 'item' => 't2_earth_sword', 'probability' => 3],
            ['monster' => 'skeleton', 'item' => 't2_earth_helmet', 'probability' => 3],
            ['monster' => 'spider', 'item' => 't2_earth_gloves', 'probability' => 3],
            ['monster' => 'venom_snake', 'item' => 't2_water_boots', 'probability' => 3],
            ['monster' => 'venom_snake', 'item' => 't2_water_gloves', 'probability' => 3],
            ['monster' => 'venom_snake', 'item' => 't2_water_legs', 'probability' => 3],
            ['monster' => 'taiju', 'item' => 't2_earth_chest', 'probability' => 2],
            ['monster' => 'taiju', 'item' => 't2_earth_legs', 'probability' => 3],
            ['monster' => 'specter', 'item' => 't2_air_gloves', 'probability' => 3],
            ['monster' => 'specter', 'item' => 't2_air_helmet', 'probability' => 2],
            ['monster' => 'banshee', 'item' => 't2_air_chest', 'probability' => 2],
            ['monster' => 'banshee', 'item' => 't2_air_boots', 'probability' => 3],

            // Monstres Niveau 3
            ['monster' => 'ochu', 'item' => 't2_earth_shield', 'probability' => 4],
            ['monster' => 'ochu', 'item' => 't2_earth_boots', 'probability' => 4],
            ['monster' => 'werewolf', 'item' => 't2_fire_legs', 'probability' => 4],
            ['monster' => 'werewolf', 'item' => 't2_fire_gloves', 'probability' => 4],
            ['monster' => 'gargoyle', 'item' => 't2_air_shield', 'probability' => 4],
            ['monster' => 'gargoyle', 'item' => 't2_air_legs', 'probability' => 4],
            ['monster' => 'troll', 'item' => 't2_earth_sword', 'probability' => 3],
            ['monster' => 'troll', 'item' => 't2_earth_chest', 'probability' => 3],
            ['monster' => 'fire_elemental', 'item' => 't2_fire_sword', 'probability' => 5],
            ['monster' => 'fire_elemental', 'item' => 't2_fire_chest', 'probability' => 4],
            ['monster' => 'fire_elemental', 'item' => 't2_fire_helmet', 'probability' => 4],

            // Monstres élémentaires tier 1 — drops thématiques
            ['monster' => 'salamander', 'item' => 't2_fire_boots', 'probability' => 5],
            ['monster' => 'salamander', 'item' => 't2_fire_shield', 'probability' => 4],
            ['monster' => 'undine', 'item' => 't2_water_sword', 'probability' => 5],
            ['monster' => 'undine', 'item' => 't2_water_helmet', 'probability' => 4],
            ['monster' => 'undine', 'item' => 't2_water_shield', 'probability' => 4],
            ['monster' => 'sylph', 'item' => 't2_air_sword', 'probability' => 5],
            ['monster' => 'sylph', 'item' => 't2_air_chest', 'probability' => 4],
            ['monster' => 'clay_golem', 'item' => 't2_earth_gloves', 'probability' => 5],
            ['monster' => 'clay_golem', 'item' => 't2_earth_legs', 'probability' => 4],
            ['monster' => 'clay_golem', 'item' => 't2_earth_shield', 'probability' => 4],

            // Niveau 4 — toutes les variantes accessibles
            ['monster' => 'griffin', 'item' => 't2_air_sword', 'probability' => 5],
            ['monster' => 'griffin', 'item' => 't2_air_helmet', 'probability' => 5],
            ['monster' => 'minotaur', 'item' => 't2_fire_sword', 'probability' => 5],
            ['monster' => 'minotaur', 'item' => 't2_fire_chest', 'probability' => 5],
            ['monster' => 'stone_golem', 'item' => 't2_earth_sword', 'probability' => 5],
            ['monster' => 'stone_golem', 'item' => 't2_earth_shield', 'probability' => 5],

            // --- Équipement Tier 3 élémentaire (tâche 64) ---
            // Golem de cristal (Terre/Lumière, lvl 15) → T3 Light
            ['monster' => 'crystal_golem', 'item' => 't3_light_helmet', 'probability' => 3],
            ['monster' => 'crystal_golem', 'item' => 't3_light_shield', 'probability' => 2],

            // Archidruide corrompu (Bête/Ombre, lvl 16) → T3 Beast
            ['monster' => 'corrupted_archdruid', 'item' => 't3_beast_chest', 'probability' => 3],
            ['monster' => 'corrupted_archdruid', 'item' => 't3_beast_gloves', 'probability' => 2],

            // Liche mineure (Ombre, lvl 18) → T3 Dark
            ['monster' => 'lesser_lich', 'item' => 't3_dark_helmet', 'probability' => 3],
            ['monster' => 'lesser_lich', 'item' => 't3_dark_boots', 'probability' => 2],

            // Hydre des marais (Eau/Bête, lvl 20) → T3 Beast
            ['monster' => 'swamp_hydra', 'item' => 't3_beast_sword', 'probability' => 3],
            ['monster' => 'swamp_hydra', 'item' => 't3_beast_legs', 'probability' => 2],

            // Forgeron abyssal (Métal/Feu, lvl 24) → T3 Metal
            ['monster' => 'abyssal_blacksmith', 'item' => 't3_metal_sword', 'probability' => 3],
            ['monster' => 'abyssal_blacksmith', 'item' => 't3_metal_chest', 'probability' => 2],
            ['monster' => 'abyssal_blacksmith', 'item' => 't3_metal_shield', 'probability' => 2],

            // Wyvern (Air/Feu, lvl 10) → T3 Light
            ['monster' => 'wyvern', 'item' => 't3_light_boots', 'probability' => 2],

            // Chevalier maudit (Ombre/Métal, lvl 12) → T3 Dark + Metal
            ['monster' => 'cursed_knight', 'item' => 't3_dark_sword', 'probability' => 2],
            ['monster' => 'cursed_knight', 'item' => 't3_metal_legs', 'probability' => 2],

            // Naga (Eau/Bête, lvl 13) → T3 Beast
            ['monster' => 'naga', 'item' => 't3_beast_boots', 'probability' => 2],

            // Boss : Gardien de la Forêt → T3 Light & Beast
            ['monster' => 'forest_guardian', 'item' => 't3_light_sword', 'probability' => 5],
            ['monster' => 'forest_guardian', 'item' => 't3_beast_shield', 'probability' => 4],

            // Boss : Seigneur de la Forge → T3 Metal & Dark
            ['monster' => 'forge_lord', 'item' => 't3_metal_helmet', 'probability' => 5],
            ['monster' => 'forge_lord', 'item' => 't3_dark_chest', 'probability' => 4],

            // Boss : Dragon → T3 variés
            ['monster' => 'dragon', 'item' => 't3_dark_sword', 'probability' => 4],
            ['monster' => 'dragon', 'item' => 't3_light_chest', 'probability' => 3],
            ['monster' => 'dragon', 'item' => 't3_metal_gloves', 'probability' => 3],

            // --- Drops légendaires rares sur monstres de haut niveau ---
            ['monster' => 'griffin', 'item' => 'griffin_talon_ring', 'probability' => 3, 'minDifficulty' => 3],
            ['monster' => 'minotaur', 'item' => 'minotaur_horn_helm', 'probability' => 3, 'minDifficulty' => 3],
            ['monster' => 'stone_golem', 'item' => 'golem_heart_shield', 'probability' => 3, 'minDifficulty' => 3],
            ['monster' => 'troll', 'item' => 'troll_king_belt', 'probability' => 3, 'minDifficulty' => 3],

            // --- Boss de donjon : Racine Ancienne (tache 84) ---
            ['monster' => 'ancient_root', 'item' => 'healing_potion_major', 'probability' => 80],
            ['monster' => 'ancient_root', 'item' => 'energy_potion_small', 'probability' => 60],
            ['monster' => 'ancient_root', 'item' => 'ancient_scroll', 'probability' => 40],
            ['monster' => 'ancient_root', 'item' => 'materia_stone_throw', 'probability' => 15],
            // Drop garanti du boss
            ['monster' => 'ancient_root', 'item' => 'guardian_thorn_staff', 'probability' => 10, 'guaranteed' => true],
        ];

        foreach ($monsterItems as $data) {
            $monsterItem = new MonsterItem();
            $monsterItem->setMonster($this->getReference($data['monster'], Monster::class));
            $monsterItem->setItem($this->getReference($data['item'], Item::class));
            $monsterItem->setProbability($data['probability']);
            $monsterItem->setGuaranteed($data['guaranteed'] ?? false);
            $monsterItem->setMinDifficulty($data['minDifficulty'] ?? null);
            $monsterItem->setCreatedAt(new \DateTime());
            $monsterItem->setUpdatedAt(new \DateTime());

            $manager->persist($monsterItem);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MonsterFixtures::class,
            ItemFixtures::class,
        ];
    }
}
