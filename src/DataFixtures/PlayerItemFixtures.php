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
            ],
            'player_beer_pint' => [
                'generic_item' => 'beer_pint',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
            ],
            'player_life_domain_parchment' => [
                'generic_item' => 'life_domain_parchment',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
            ],
            'player_miner_domain_parchment' => [
                'generic_item' => 'miner_domain_parchment',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
            ],
            'player_herbalist_domain_parchment' => [
                'generic_item' => 'herbalist_domain_parchment',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
            ],
            'player_fishing_rod' => [
                'generic_item' => 'fishing_rod',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
            ]
        ];

        // Création des potions pour le joueur demo
        $playerPotions = [];
        for ($i = 1; $i <= 5; $i++) {
            $playerPotions['player_life_potion_' . $i] = [
                'generic_item' => 'life_potion',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
            ];
        }
        
        // Création des équipements pour le joueur demo
        $playerGearItems = [
            'player_short_sword' => [
                'generic_item' => 'short_sword',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_long_sword_2' => [
                'generic_item' => 'long_sword',
                'inventory' => 'inventory_bag_2',
                'nb_usages' => 1,
            ],
            'player_leather_boots' => [
                'generic_item' => 'leather_boots',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_leather_armor' => [
                'generic_item' => 'leather_armor',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_leather_hat' => [
                'generic_item' => 'leather_hat',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1
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

        // Création des potions pour le joueur
        foreach ($playerPotions as $key => $data) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($this->getReference($data['generic_item'], Item::class));
            $playerItem->setInventory($this->getReference($data['inventory'], Inventory::class));
            $playerItem->setNbUsages($data['nb_usages']);
            $playerItem->setCreatedAt(new \DateTime());
            $playerItem->setUpdatedAt(new \DateTime());
            
            $manager->persist($playerItem);
            $this->addReference($key, $playerItem);
        }
        
        // Création des équipements pour le joueur
        foreach ($playerGearItems as $key => $data) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($this->getReference($data['generic_item'], Item::class));
            $playerItem->setInventory($this->getReference($data['inventory'], Inventory::class));
            $playerItem->setNbUsages($data['nb_usages']);
            if (isset($data['gear'])) {
                $playerItem->setGear($data['gear']);
            }
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