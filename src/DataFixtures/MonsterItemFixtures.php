<?php

namespace App\DataFixtures;

use App\Entity\Game\MonsterItem;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MonsterItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des items pour les monstres
        $monsterItems = [
            'zombie_mushroom' => [
                'monster' => 'zombie',
                'item' => 'mushroom',
                'probability' => 75
            ],
            'zombie_leather_skin_1' => [
                'monster' => 'zombie',
                'item' => 'leather_skin_1',
                'probability' => 90
            ],
            'zombie_pickaxe' => [
                'monster' => 'zombie',
                'item' => 'pickaxe',
                'probability' => 10
            ]
        ];
        
        // Création des associations monster-item
        foreach ($monsterItems as $key => $data) {
            $monsterItem = new MonsterItem();
            $monsterItem->setMonster($this->getReference($data['monster'], Monster::class));
            $monsterItem->setItem($this->getReference($data['item'], Item::class));
            $monsterItem->setProbability($data['probability']);
            $monsterItem->setCreatedAt(new \DateTime());
            $monsterItem->setUpdatedAt(new \DateTime());
            
            $manager->persist($monsterItem);
            $this->addReference($key, $monsterItem);
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