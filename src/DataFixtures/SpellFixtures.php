<?php

namespace App\DataFixtures;

use App\Entity\Game\Spell;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SpellFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $spellsData = $this->getSpellsData();
        
        // Création des sorts pour chaque élément
        foreach ($spellsData as $key => $data) {
            $spell = new Spell();
            $spell->setSlug($data['slug']);
            $spell->setDamage($data['damage']);
            $spell->setElement($data['element']);
            $spell->setHeal($data['heal']);
            $spell->setName($data['name']);
            $spell->setDescription($data['description']);
            $spell->setHit($data['hit'] ?? 90);
            $spell->setCreatedAt(new \DateTime());
            $spell->setUpdatedAt(new \DateTime());
            
            $manager->persist($spell);
            $this->addReference($key, $spell);
        }
        
        $manager->flush();
    }
    
    /**
     * Retourne les données des sorts
     * 
     * @return array
     */
    private function getSpellsData(): array
    {
        return [
            // Sorts de feu
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
            ],
            
            // Sorts de vie
            'life_heal' => [
                'slug' => 'life-heal',
                'damage' => null,
                'element' => 'life',
                'heal' => 5,
                'name' => 'Soin mineur',
                'description' => 'Un soin mineur qui fait du bien',
                'hit' => 100
            ],
            
            // Sorts de mort
            'punishment' => [
                'slug' => 'punishment',
                'damage' => 1,
                'element' => 'death',
                'heal' => null,
                'name' => 'Châtiment',
                'description' => 'Un sort de châtiment',
                'hit' => 90
            ],
            
            // Sorts de terre
            'stone_throw' => [
                'slug' => 'stone-throw',
                'damage' => 1,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Jet de cailloux',
                'description' => 'Un sort de jet de cailloux',
                'hit' => 90
            ],
            
            // Sorts de métal
            'sword_10' => [
                'slug' => 'slug-10',
                'damage' => 1,
                'element' => 'metal',
                'heal' => null,
                'name' => "Coup d'épée",
                'description' => "Un coup d'épée neutre",
                'hit' => 90
            ],
            'sharp_blade' => [
                'slug' => 'sharp-blade',
                'damage' => 1,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Lame tranchante',
                'description' => 'Un sort de lame tranchante',
                'hit' => 90
            ],
            
            // Sorts de nature
            'liana_whip' => [
                'slug' => 'liana-whip',
                'damage' => 1,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Fouet de liane',
                'description' => 'Un sort de fouet de liane',
                'hit' => 90
            ],
            
            // Sorts de vent
            'wind_lame' => [
                'slug' => 'wind-lame',
                'damage' => 1,
                'element' => 'wind',
                'heal' => null,
                'name' => "Lame d'air",
                'description' => "une lame d'air tranchante",
                'hit' => 90
            ],
            
            // Sorts sans élément
            'none_attack_1' => [
                'slug' => 'none-attack-1',
                'damage' => 1,
                'element' => 'none',
                'heal' => null,
                'name' => 'Attaque',
                'description' => 'Attaque 1',
                'hit' => 90
            ],
            'none_attack_2' => [
                'slug' => 'none-attack-2',
                'damage' => 2,
                'element' => 'none',
                'heal' => null,
                'name' => 'Attaque',
                'description' => 'Attaque 2',
                'hit' => 90
            ],
            'none_heal_2' => [
                'slug' => 'none-heal-2',
                'damage' => null,
                'element' => 'none',
                'heal' => 2,
                'name' => 'Soin',
                'description' => 'Soin 2',
                'hit' => 90
            ],
        ];
    }
} 