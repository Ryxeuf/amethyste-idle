<?php

namespace App\DataFixtures;

use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Game\Domain;

class ItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des objets de type materia
        $materias = [
            'materia_soin' => [
                'name' => 'Materia de soin',
                'description' => 'Une materia permettant de soigner les blessures',
                'element' => Item::ELEMENT_LIGHT,
                'price' => 100,
                'level' => 1,
                'domain' => 'healer',
                'space' => 1,
                'energy_cost' => 10,
                'nb_usages' => 10
            ],
            'materia_stone_throw' => [
                'name' => 'Materia Jet de pierre',
                'description' => 'Une materia permettant de lancer des pierres',
                'element' => Item::ELEMENT_EARTH,
                'price' => 50,
                'level' => 1,
                'domain' => 'soldier',
                'space' => 1,
                'energy_cost' => 5,
                'nb_usages' => 20
            ],
            'materia_punishment' => [
                'name' => 'Materia Châtiment',
                'description' => 'Une materia infligeant un châtiment divin',
                'element' => Item::ELEMENT_LIGHT,
                'price' => 150,
                'level' => 2,
                'domain' => 'white_wizard',
                'space' => 1,
                'energy_cost' => 15,
                'nb_usages' => 8
            ],
            'materia_liana_whip' => [
                'name' => 'Materia Fouet de lianes',
                'description' => 'Une materia invoquant des lianes fouettantes',
                'element' => Item::ELEMENT_EARTH,
                'price' => 120,
                'level' => 2,
                'domain' => 'druid',
                'space' => 1,
                'energy_cost' => 12,
                'nb_usages' => 15
            ],
            'materia_sharp_blade' => [
                'name' => 'Materia Lame acérée',
                'description' => 'Une materia créant une lame tranchante',
                'element' => Item::ELEMENT_NONE,
                'price' => 100,
                'level' => 1,
                'domain' => 'soldier',
                'space' => 1,
                'energy_cost' => 8,
                'nb_usages' => 25
            ],
            'materia_wind_lame' => [
                'name' => 'Materia Lame de vent',
                'description' => 'Une materia créant une lame de vent',
                'element' => Item::ELEMENT_AIR,
                'price' => 130,
                'level' => 2,
                'domain' => 'druid',
                'space' => 1,
                'energy_cost' => 10,
                'nb_usages' => 20
            ],
            'materia_flame' => [
                'name' => 'Materia Flamme',
                'description' => 'Une materia créant une flamme',
                'element' => Item::ELEMENT_FIRE,
                'price' => 100,
                'level' => 1,
                'domain' => 'pyromancy',
                'space' => 1,
                'energy_cost' => 8,
                'nb_usages' => 25
            ],
            'materia_flamer' => [
                'name' => 'Materia Lance-flammes',
                'description' => 'Une materia projetant des flammes',
                'element' => Item::ELEMENT_FIRE,
                'price' => 150,
                'level' => 2,
                'domain' => 'pyromancy',
                'space' => 1,
                'energy_cost' => 15,
                'nb_usages' => 15
            ],
            'materia_flame_rain' => [
                'name' => 'Materia Pluie de feu',
                'description' => 'Une materia invoquant une pluie de feu',
                'element' => Item::ELEMENT_FIRE,
                'price' => 200,
                'level' => 3,
                'domain' => 'pyromancy',
                'space' => 1,
                'energy_cost' => 25,
                'nb_usages' => 5
            ]
        ];
        
        // Création des objets de type stuff
        $stuffs = [
            'mushroom' => [
                'name' => 'Champignon',
                'description' => 'Un champignon comestible',
                'element' => Item::ELEMENT_NONE,
                'price' => 5,
                'level' => 1,
                'domain' => 'herbalist',
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'leather_skin_1' => [
                'name' => 'Peau de cuir',
                'description' => 'Une peau de cuir de qualité médiocre',
                'element' => Item::ELEMENT_NONE,
                'price' => 10,
                'level' => 1,
                'domain' => 'skinner',
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'pickaxe' => [
                'name' => 'Pioche',
                'description' => 'Une pioche pour miner',
                'element' => Item::ELEMENT_NONE,
                'price' => 50,
                'level' => 1,
                'domain' => 'miner',
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 100
            ]
        ];
        
        // Création des objets de type materia
        foreach ($materias as $key => $data) {
            $item = new Item();
            $item->setName($data['name']);
            $item->setSlug($key);
            $item->setDescription($data['description']);
            $item->setType(Item::TYPE_MATERIA);
            $item->setElement($data['element']);
            $item->setPrice($data['price']);
            $item->setLevel($data['level']);
            $item->setDomain($this->getReference($data['domain'], Domain::class));
            $item->setSpace($data['space']);
            $item->setEnergyCost($data['energy_cost']);
            $item->setNbUsages($data['nb_usages']);
            $item->setCreatedAt(new \DateTime());
            $item->setUpdatedAt(new \DateTime());
            
            $manager->persist($item);
            $this->addReference($key, $item);
        }
        
        // Création des objets de type stuff
        foreach ($stuffs as $key => $data) {
            $item = new Item();
            $item->setName($data['name']);
            $item->setSlug($key);
            $item->setDescription($data['description']);
            $item->setType(Item::TYPE_STUFF);
            $item->setElement($data['element']);
            $item->setPrice($data['price']);
            $item->setLevel($data['level']);
            $item->setDomain($this->getReference($data['domain'], Domain::class));
            $item->setSpace($data['space']);
            $item->setEnergyCost($data['energy_cost']);
            $item->setNbUsages($data['nb_usages']);
            $item->setCreatedAt(new \DateTime());
            $item->setUpdatedAt(new \DateTime());
            
            $manager->persist($item);
            $this->addReference($key, $item);
        }
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
        ];
    }
} 