<?php

namespace App\DataFixtures;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class InventoryFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Inventaire sac du joueur demo
        $inventoryBag = new Inventory();
        $inventoryBag->setPlayer($this->getReference('player_demo', Player::class));
        $inventoryBag->setGold(200);
        $inventoryBag->setType(1);
        $inventoryBag->setSize(100);
        $inventoryBag->setCreatedAt(new \DateTime());
        $inventoryBag->setUpdatedAt(new \DateTime());

        $manager->persist($inventoryBag);
        $this->addReference('inventory_bag', $inventoryBag);

        // Inventaire materia du joueur demo
        $inventoryMateria = new Inventory();
        $inventoryMateria->setPlayer($this->getReference('player_demo', Player::class));
        $inventoryMateria->setType(2);
        $inventoryMateria->setSize(100);
        $inventoryMateria->setCreatedAt(new \DateTime());
        $inventoryMateria->setUpdatedAt(new \DateTime());

        $manager->persist($inventoryMateria);
        $this->addReference('inventory_materia', $inventoryMateria);

        // Inventaire banque du joueur demo
        $inventoryBank = new Inventory();
        $inventoryBank->setPlayer($this->getReference('player_demo', Player::class));
        $inventoryBank->setType(3);
        $inventoryBank->setSize(500);
        $inventoryBank->setCreatedAt(new \DateTime());
        $inventoryBank->setUpdatedAt(new \DateTime());

        $manager->persist($inventoryBank);
        $this->addReference('inventory_bank', $inventoryBank);

        // Inventaire sac du joueur demo 2
        $inventoryBag2 = new Inventory();
        $inventoryBag2->setPlayer($this->getReference('player_demo_2', Player::class));
        $inventoryBag2->setGold(200);
        $inventoryBag2->setType(1);
        $inventoryBag2->setSize(100);
        $inventoryBag2->setCreatedAt(new \DateTime());
        $inventoryBag2->setUpdatedAt(new \DateTime());

        $manager->persist($inventoryBag2);
        $this->addReference('inventory_bag_2', $inventoryBag2);

        // Inventaire materia du joueur demo 2
        $inventoryMateria2 = new Inventory();
        $inventoryMateria2->setPlayer($this->getReference('player_demo_2', Player::class));
        $inventoryMateria2->setType(2);
        $inventoryMateria2->setSize(100);
        $inventoryMateria2->setCreatedAt(new \DateTime());
        $inventoryMateria2->setUpdatedAt(new \DateTime());

        $manager->persist($inventoryMateria2);
        $this->addReference('inventory_materia_2', $inventoryMateria2);

        // Inventaire banque du joueur demo 2
        $inventoryBank2 = new Inventory();
        $inventoryBank2->setPlayer($this->getReference('player_demo_2', Player::class));
        $inventoryBank2->setType(3);
        $inventoryBank2->setSize(500);
        $inventoryBank2->setCreatedAt(new \DateTime());
        $inventoryBank2->setUpdatedAt(new \DateTime());

        $manager->persist($inventoryBank2);
        $this->addReference('inventory_bank_2', $inventoryBank2);

        // Inventaire sac du joueur Remy
        $inventoryBagRemy = new Inventory();
        $inventoryBagRemy->setPlayer($this->getReference('player_remy', Player::class));
        $inventoryBagRemy->setGold(0);
        $inventoryBagRemy->setType(1);
        $inventoryBagRemy->setSize(100000);
        $inventoryBagRemy->setCreatedAt(new \DateTime());
        $inventoryBagRemy->setUpdatedAt(new \DateTime());

        $manager->persist($inventoryBagRemy);
        $this->addReference('inventory_bag_remy', $inventoryBagRemy);

        // Inventaire materia du joueur Remy
        $inventoryMateriaRemy = new Inventory();
        $inventoryMateriaRemy->setPlayer($this->getReference('player_remy', Player::class));
        $inventoryMateriaRemy->setType(2);
        $inventoryMateriaRemy->setSize(100000);
        $inventoryMateriaRemy->setCreatedAt(new \DateTime());
        $inventoryMateriaRemy->setUpdatedAt(new \DateTime());

        $manager->persist($inventoryMateriaRemy);
        $this->addReference('inventory_materia_remy', $inventoryMateriaRemy);

        // Inventaire banque du joueur Remy
        $inventoryBankRemy = new Inventory();
        $inventoryBankRemy->setPlayer($this->getReference('player_remy', Player::class));
        $inventoryBankRemy->setType(3);
        $inventoryBankRemy->setSize(100000);
        $inventoryBankRemy->setCreatedAt(new \DateTime());
        $inventoryBankRemy->setUpdatedAt(new \DateTime());

        $manager->persist($inventoryBankRemy);
        $this->addReference('inventory_bank_remy', $inventoryBankRemy);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PlayerFixtures::class,
        ];
    }
}
