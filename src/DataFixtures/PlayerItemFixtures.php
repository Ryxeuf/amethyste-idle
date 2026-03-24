<?php

namespace App\DataFixtures;

use App\Entity\App\Inventory;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
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
                'nb_usages' => 10,
            ],
            'player_materia_soin_2' => [
                'generic_item' => 'materia_soin',
                'inventory' => 'inventory_materia',
                'nb_usages' => 10,
            ],
            'player_materia_stone_throw' => [
                'generic_item' => 'materia_stone_throw',
                'inventory' => 'inventory_materia',
                'nb_usages' => 20,
            ],
            'player_materia_punishment' => [
                'generic_item' => 'materia_punishment',
                'inventory' => 'inventory_materia',
                'nb_usages' => 8,
            ],
            'player_materia_liana_whip' => [
                'generic_item' => 'materia_liana_whip',
                'inventory' => 'inventory_materia',
                'nb_usages' => 15,
            ],
            'player_materia_sharp_blade' => [
                'generic_item' => 'materia_sharp_blade',
                'inventory' => 'inventory_materia',
                'nb_usages' => 25,
            ],
            'player_materia_wind_lame' => [
                'generic_item' => 'materia_wind_lame',
                'inventory' => 'inventory_materia',
                'nb_usages' => 20,
            ],
            'player_materia_flame' => [
                'generic_item' => 'materia_flame',
                'inventory' => 'inventory_materia',
                'nb_usages' => 25,
            ],
            'player_materia_flamer' => [
                'generic_item' => 'materia_flamer',
                'inventory' => 'inventory_materia',
                'nb_usages' => 15,
            ],
            'player_materia_flame_rain' => [
                'generic_item' => 'materia_flame_rain',
                'inventory' => 'inventory_materia',
                'nb_usages' => 5,
            ],
        ];

        // Création des objets pour le joueur demo
        $playerItems = [
            'player_mushroom_1' => [
                'generic_item' => 'mushroom',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_mushroom_2' => [
                'generic_item' => 'mushroom',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_leather_skin_1' => [
                'generic_item' => 'leather_skin_1',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_pickaxe' => [
                'generic_item' => 'pickaxe',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100,
            ],
            'player_beer_pint' => [
                'generic_item' => 'beer_pint',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_life_domain_parchment' => [
                'generic_item' => 'life_domain_parchment',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_miner_domain_parchment' => [
                'generic_item' => 'miner_domain_parchment',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_herbalist_domain_parchment' => [
                'generic_item' => 'herbalist_domain_parchment',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
            'player_fishing_rod' => [
                'generic_item' => 'fishing_rod',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ],
        ];

        // Création des potions pour le joueur demo
        $playerPotions = [];
        for ($i = 1; $i <= 5; ++$i) {
            $playerPotions['player_life_potion_' . $i] = [
                'generic_item' => 'life_potion',
                'inventory' => 'inventory_bag',
                'nb_usages' => 1,
            ];
        }

        // Création des équipements pour le joueur demo
        $playerGearItems = [
            'player_short_sword' => [
                'generic_item' => 'short_sword',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100,
            ],
            'player_long_sword_2' => [
                'generic_item' => 'long_sword',
                'inventory' => 'inventory_bag_2',
                'nb_usages' => 100,
            ],
            'player_leather_boots' => [
                'generic_item' => 'leather_boots',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100,
            ],
            'player_leather_armor' => [
                'generic_item' => 'leather_armor',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100,
            ],
            'player_leather_hat' => [
                'generic_item' => 'leather_hat',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100,
            ],
            'player_iron_shield' => [
                'generic_item' => 'iron_shield',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100,
            ],
            'player_leather_gloves' => [
                'generic_item' => 'leather_gloves',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100,
            ],
            'player_bronze_ring' => [
                'generic_item' => 'bronze_ring',
                'inventory' => 'inventory_bag',
                'nb_usages' => -1,
            ],
            'player_silver_amulet' => [
                'generic_item' => 'silver_amulet',
                'inventory' => 'inventory_bag',
                'nb_usages' => -1,
            ],
            'player_leather_shoulders' => [
                'generic_item' => 'leather_shoulders',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100,
            ],
            'player_leather_pants' => [
                'generic_item' => 'leather_pants',
                'inventory' => 'inventory_bag',
                'nb_usages' => 100,
            ],
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

        // === Items pour le joueur Remy (un exemplaire de chaque) ===
        $remyBagItems = [
            // Stuff
            'life_potion', 'fishing_rod', 'beer_pint', 'mushroom', 'pickaxe',
            'wood_log', 'leather_skin_1', 'leather_skin_2',
            'life_domain_parchment', 'miner_domain_parchment', 'herbalist_domain_parchment',
            // Ressources - Minerais
            'ore_ruby', 'ore_iron', 'ore_copper', 'ore_silver', 'ore_gold', 'ore_mithril', 'ore_amethyst_crystal',
            // Ressources - Plantes
            'plant_mint', 'plant_sage', 'plant_lavender', 'plant_thyme', 'plant_rosemary', 'plant_mandrake', 'plant_moonflower',
            // Ressources - Poissons
            'fish_trout', 'fish_salmon', 'fish_carp', 'fish_moonfish', 'fish_electric_eel', 'fish_baby_kraken',
            // Ressources - Cuirs
            'leather_raw', 'leather_thick', 'leather_dragon_scale', 'leather_werewolf_fur', 'leather_bone', 'leather_fang',
            // Outils
            'pickaxe_bronze', 'pickaxe_iron', 'pickaxe_steel', 'pickaxe_mithril',
            'sickle_bronze', 'sickle_iron', 'sickle_steel', 'sickle_mithril',
            'fishing_rod_bronze', 'fishing_rod_iron', 'fishing_rod_steel', 'fishing_rod_mithril',
            'skinning_knife_bronze', 'skinning_knife_iron', 'skinning_knife_steel', 'skinning_knife_mithril',
        ];

        foreach ($remyBagItems as $itemRef) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($this->getReference($itemRef, Item::class));
            $playerItem->setInventory($this->getReference('inventory_bag_remy', Inventory::class));
            $genericItem = $this->getReference($itemRef, Item::class);
            $playerItem->setNbUsages($genericItem->getNbUsages());
            if ($genericItem->getDurability() !== null) {
                $playerItem->setCurrentDurability($genericItem->getDurability());
            }
            $playerItem->setCreatedAt(new \DateTime());
            $playerItem->setUpdatedAt(new \DateTime());
            $manager->persist($playerItem);
            $this->addReference('remy_' . $itemRef, $playerItem);
        }

        // Équipements pour Remy
        $remyGearItems = [
            'short_sword', 'long_sword', 'leather_boots', 'leather_armor', 'leather_hat',
            'iron_shield', 'leather_gloves', 'leather_belt', 'bronze_ring',
            'silver_amulet', 'leather_shoulders', 'leather_pants',
        ];

        foreach ($remyGearItems as $itemRef) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($this->getReference($itemRef, Item::class));
            $playerItem->setInventory($this->getReference('inventory_bag_remy', Inventory::class));
            $genericItem = $this->getReference($itemRef, Item::class);
            $playerItem->setNbUsages($genericItem->getNbUsages());
            $playerItem->setCreatedAt(new \DateTime());
            $playerItem->setUpdatedAt(new \DateTime());
            $manager->persist($playerItem);
            $this->addReference('remy_' . $itemRef, $playerItem);
        }

        // Materias pour Remy
        $remyMateriaItems = [
            'materia_soin', 'materia_fire_ball', 'materia_flame', 'materia_flamer', 'materia_flame_rain',
            'materia_wind_lame', 'materia_stone_throw', 'materia_punishment', 'materia_liana_whip',
            'materia_sharp_blade', 'materia_combustion', 'materia_frost_mist', 'materia_air_chain_lightning',
            'materia_stone_shield', 'materia_steel_riposte', 'materia_savage_bite', 'materia_light_blessing',
            'materia_vital_drain',
        ];

        foreach ($remyMateriaItems as $itemRef) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($this->getReference($itemRef, Item::class));
            $playerItem->setInventory($this->getReference('inventory_materia_remy', Inventory::class));
            $genericItem = $this->getReference($itemRef, Item::class);
            $playerItem->setNbUsages($genericItem->getNbUsages());
            $playerItem->setCreatedAt(new \DateTime());
            $playerItem->setUpdatedAt(new \DateTime());
            $manager->persist($playerItem);
            $this->addReference('remy_' . $itemRef, $playerItem);
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
