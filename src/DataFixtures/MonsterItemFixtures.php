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

            ['monster' => 'goblin', 'item' => 'mushroom', 'probability' => 50],
            ['monster' => 'goblin', 'item' => 'beer_pint', 'probability' => 30],
            ['monster' => 'goblin', 'item' => 'healing_potion_small', 'probability' => 15],
            ['monster' => 'goblin', 'item' => 'bread', 'probability' => 25],

            ['monster' => 'bat', 'item' => 'leather_skin_1', 'probability' => 40],
            ['monster' => 'bat', 'item' => 'mushroom', 'probability' => 25],

            ['monster' => 'giant_rat', 'item' => 'leather_skin_1', 'probability' => 60],
            ['monster' => 'giant_rat', 'item' => 'mushroom', 'probability' => 35],
            ['monster' => 'giant_rat', 'item' => 'healing_potion_small', 'probability' => 10],
            ['monster' => 'giant_rat', 'item' => 'bread', 'probability' => 15],

            ['monster' => 'zombie', 'item' => 'mushroom', 'probability' => 75],
            ['monster' => 'zombie', 'item' => 'leather_skin_1', 'probability' => 90],
            ['monster' => 'zombie', 'item' => 'pickaxe', 'probability' => 10],
            ['monster' => 'zombie', 'item' => 'antidote', 'probability' => 12],

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
        ];

        foreach ($monsterItems as $data) {
            $monsterItem = new MonsterItem();
            $monsterItem->setMonster($this->getReference($data['monster'], Monster::class));
            $monsterItem->setItem($this->getReference($data['item'], Item::class));
            $monsterItem->setProbability($data['probability']);
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
