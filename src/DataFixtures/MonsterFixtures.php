<?php

namespace App\DataFixtures;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MonsterFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des monstres
        $monsters = [
            'zombie' => [
                'name' => 'Zombie',
                'life' => 20,
                'level' => 1,
                'hit' => 80,
                'speed' => 5
            ],
            'skeleton' => [
                'name' => 'Squelette',
                'life' => 15,
                'level' => 1,
                'hit' => 85,
                'speed' => 8
            ],
            'ochu' => [
                'name' => 'Ochu',
                'life' => 30,
                'level' => 2,
                'hit' => 75,
                'speed' => 3
            ],
            'taiju' => [
                'name' => 'Taiju',
                'life' => 40,
                'level' => 3,
                'hit' => 70,
                'speed' => 4
            ]
        ];
        
        // Création d'un sort d'attaque de base pour les monstres
        $basicAttack = new Spell();
        $basicAttack->setName('Attaque de base');
        $basicAttack->setSlug('basic-attack');
        $basicAttack->setDescription('Attaque de base des monstres');
        $basicAttack->setDamage(3);
        $basicAttack->setHit(90);
        $basicAttack->setCritical(5);
        $basicAttack->setCreatedAt(new \DateTime());
        $basicAttack->setUpdatedAt(new \DateTime());
        
        $manager->persist($basicAttack);
        $this->addReference('basic_attack', $basicAttack);
        
        foreach ($monsters as $key => $data) {
            $monster = new Monster();
            $monster->setName($data['name']);
            $monster->setSlug($key);
            $monster->setLife($data['life']);
            $monster->setLevel($data['level']);
            $monster->setHit($data['hit']);
            $monster->setSpeed($data['speed']);
            $monster->setAttack($basicAttack);
            $monster->setCreatedAt(new \DateTime());
            $monster->setUpdatedAt(new \DateTime());
            
            $manager->persist($monster);
            $this->addReference($key, $monster);
        }
        
        $manager->flush();
    }
} 