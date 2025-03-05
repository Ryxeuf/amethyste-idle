<?php

namespace App\DataFixtures;

use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\App\Inventory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PlayerItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des materias pour le joueur demo
        $playerMaterias = [
            'player_materia_soin_1' => [
                'generic_item' => 'materia_soin',
                'inventory' => 'inventory_materia',
                'nb_usages' => 10
            ],
            'player_materia_soin_2' => [
                'generic_item' => 'materia_soin',
                'inventory' => 'inventory_materia',
                'nb_usages' => 10
            ],
            'player_materia_stone_throw' => [
                'generic_item' => 'materia_stone_throw',
                'inventory' => 'inventory_materia',
                'nb_usages' => 20
            ],
            'player_materia_punishment' => [
                'generic_item' => 'materia_punishment',
                'inventory' => 'inventory_materia',
                'nb_usages' => 8
            ],
            'player_materia_liana_whip' => [
                'generic_item' => 'materia_liana_whip',
                'inventory' => 'inventory_materia',
                'nb_usages' => 15
            ],
            'player_materia_sharp_blade' => [
                'generic_item' => 'materia_sharp_blade',
                'inventory' => 'inventory_materia',
                'nb_usages' => 25
            ],
            'player_materia_wind_lame' => [
                'generic_item' => 'materia_wind_lame',
                'inventory' => 'inventory_materia',
                'nb_usages' => 20
            ],
            'player_materia_flame' => [
                'generic_item' => 'materia_flame',
                'inventory' => 'inventory_materia',
                'nb_usages' => 25
            ],
            'player_materia_flamer' => [
                'generic_item' => 'materia_flamer',
                'inventory' => 'inventory_materia',
                'nb_usages' => 15
            ],
            'player_materia_flame_rain' => [
                'generic_item' => 'materia_flame_rain',
                'inventory' => 'inventory_materia',
                'nb_usages' => 5
            ]
        ];
        
        // Création des objets pour le joueur demo
        $playerItems = [
            'player_mushroom_1' => [
                'generic_item' => 'mushroom',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
            ],
            'player_mushroom_2' => [
                'generic_item' => 'mushroom',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
            ],
            'player_leather_skin_1' => [
                'generic_item' => 'leather_skin_1',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
            ],
            'player_pickaxe' => [
                'generic_item' => 'pickaxe',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100
            ]
        ];
        
        // Création des materias pour le joueur
        foreach ($playerMaterias as $key => $data) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($this->getReference($data['generic_item'], Item::class));
            $playerItem->setInventory($this->getReference($data['inventory'], Inventory::class));
            $playerItem->setNbUsages($data['nb_usages']);
            $playerItem->setCreatedAt(new \DateTime());
            $playerItem->setUpdatedAt(new \DateTime());
            
            $manager->persist($playerItem);
            $this->addReference($key, $playerItem);
        }
        
        // Création des objets pour le joueur
        foreach ($playerItems as $key => $data) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($this->getReference($data['generic_item'], Item::class));
            $playerItem->setInventory($this->getReference($data['inventory'], Inventory::class));
            $playerItem->setNbUsages($data['nb_usages']);
            $playerItem->setCreatedAt(new \DateTime());
            $playerItem->setUpdatedAt(new \DateTime());
            
            $manager->persist($playerItem);
            $this->addReference($key, $playerItem);
        }
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            ItemFixtures::class,
            InventoryFixtures::class,
        ];
    }
} 