<?php

namespace App\DataFixtures;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MonsterFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des monstres
        $monsters = [
            'zombie' => [
                'name' => 'Zombie',
                'life' => 10,
                'hit' => 80,
                'speed' => 2,
                'attack' => 'none_attack_1',
                'level' => 1
            ],
            'skeleton' => [
                'name' => 'Squelette',
                'life' => 20,
                'hit' => 80,
                'speed' => 5,
                'attack' => 'punishment',
                'level' => 1
            ],
            'ochu' => [
                'name' => 'Ochu',
                'life' => 20,
                'hit' => 80,
                'speed' => 15,
                'attack' => 'liana_whip',
                'level' => 2
            ],
            'taiju' => [
                'name' => 'Taiju',
                'life' => 10,
                'hit' => 60,
                'speed' => 12,
                'attack' => 'liana_whip',
                'level' => 3
            ]
        ];
        
        foreach ($monsters as $key => $data) {
            $monster = new Monster();
            $monster->setName($data['name']);
            $monster->setSlug($key);
            $monster->setLife($data['life']);
            $monster->setHit($data['hit']);
            $monster->setSpeed($data['speed']);
            $monster->setLevel($data['level']);
            
            // Récupération du sort d'attaque
            $attackSpell = $this->getReference($data['attack'], Spell::class);
            $monster->setAttack($attackSpell);
            
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