<?php

namespace App\DataFixtures;

use App\Entity\Game\Spell;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SpellFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des sorts de feu
        $fireSpells = [
            'fire_ball' => [
                'slug' => 'fire-ball',
                'damage' => 2,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Boule de feu',
                'description' => 'Une boule de feu pour tout cramer',
                'hit' => 90
            ],
            'flame' => [
                'slug' => 'flame',
                'damage' => 1,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Flammèche',
                'description' => 'Un sort de flammèche',
                'hit' => 90
            ],
            'flamer' => [
                'slug' => 'flamer',
                'damage' => 3,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Feu',
                'description' => 'Un sort de feu',
                'hit' => 90
            ],
            'flame_rain' => [
                'slug' => 'flame-rain',
                'damage' => 5,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Pluie de feu',
                'description' => 'Un sort de pluie de feu',
                'hit' => 90
            ]
        ];
        
        // Création des sorts de vie
        $lifeSpells = [
            'heal' => [
                'slug' => 'heal',
                'damage' => 0,
                'element' => 'life',
                'heal' => 5,
                'name' => 'Soin',
                'description' => 'Un sort de soin',
                'hit' => 100
            ]
        ];
        
        // Création des sorts
        foreach ($fireSpells as $key => $data) {
            $spell = new Spell();
            $spell->setSlug($data['slug']);
            $spell->setDamage($data['damage']);
            $spell->setElement($data['element']);
            $spell->setHeal($data['heal']);
            $spell->setName($data['name']);
            $spell->setDescription($data['description']);
            $spell->setHit($data['hit']);
            $spell->setCreatedAt(new \DateTime());
            $spell->setUpdatedAt(new \DateTime());
            
            $manager->persist($spell);
            $this->addReference($key, $spell);
        }
        
        foreach ($lifeSpells as $key => $data) {
            $spell = new Spell();
            $spell->setSlug($data['slug']);
            $spell->setDamage($data['damage']);
            $spell->setElement($data['element']);
            $spell->setHeal($data['heal']);
            $spell->setName($data['name']);
            $spell->setDescription($data['description']);
            $spell->setHit($data['hit']);
            $spell->setCreatedAt(new \DateTime());
            $spell->setUpdatedAt(new \DateTime());
            
            $manager->persist($spell);
            $this->addReference($key, $spell);
        }
        
        $manager->flush();
    }
} 