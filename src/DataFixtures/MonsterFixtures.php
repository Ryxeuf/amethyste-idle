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
            ],
            // Nouveaux monstres médiévaux fantastiques
            'goblin' => [
                'name' => 'Gobelin',
                'life' => 8,
                'hit' => 75,
                'speed' => 8,
                'attack' => 'none_attack_1',
                'level' => 1
            ],
            'troll' => [
                'name' => 'Troll',
                'life' => 35,
                'hit' => 70,
                'speed' => 3,
                'attack' => 'stone_throw',
                'level' => 3
            ],
            'dragon' => [
                'name' => 'Dragon',
                'life' => 50,
                'hit' => 85,
                'speed' => 10,
                'attack' => 'fire_ball',
                'level' => 5
            ],
            'werewolf' => [
                'name' => 'Loup-garou',
                'life' => 25,
                'hit' => 80,
                'speed' => 12,
                'attack' => 'sharp_blade',
                'level' => 3
            ],
            'banshee' => [
                'name' => 'Banshee',
                'life' => 15,
                'hit' => 90,
                'speed' => 7,
                'attack' => 'punishment',
                'level' => 2
            ],
            'griffin' => [
                'name' => 'Griffon',
                'life' => 30,
                'hit' => 85,
                'speed' => 15,
                'attack' => 'wind_lame',
                'level' => 4
            ],
            'minotaur' => [
                'name' => 'Minotaure',
                'life' => 40,
                'hit' => 75,
                'speed' => 6,
                'attack' => 'sword_10',
                'level' => 4
            ],
            'gargoyle' => [
                'name' => 'Gargouille',
                'life' => 28,
                'hit' => 80,
                'speed' => 9,
                'attack' => 'stone_throw',
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