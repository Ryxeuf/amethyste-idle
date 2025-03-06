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
        $itemsData = $this->getItemsData();
        
        foreach ($itemsData as $key => $data) {
            $item = new Item();
            
            if (isset($data['name'])) {
                $item->setName($data['name']);
            }
            
            if (isset($data['slug'])) {
                $item->setSlug($data['slug']);
            } else {
                $item->setSlug($key);
            }
            
            if (isset($data['description'])) {
                $item->setDescription($data['description']);
            }
            
            if (isset($data['type'])) {
                $item->setType($data['type']);
            }
            
            if (isset($data['element'])) {
                $item->setElement($data['element']);
            }
            
            if (isset($data['price'])) {
                $item->setPrice($data['price']);
            }
            
            if (isset($data['level'])) {
                $item->setLevel($data['level']);
            } else {
                $item->setLevel(1);
            }
            
            if (isset($data['domain'])) {
                $item->setDomain($this->getReference($data['domain'], Domain::class));
            }
            
            if (isset($data['space'])) {
                $item->setSpace($data['space']);
            }
            
            if (isset($data['energy_cost'])) {
                $item->setEnergyCost($data['energy_cost']);
            }
            
            if (isset($data['nb_usages'])) {
                $item->setNbUsages($data['nb_usages']);
            }
            
            if (isset($data['gear_location'])) {
                $item->setGearLocation($data['gear_location']);
            }
            
            if (isset($data['spell'])) {
                $item->setSpell($this->getReference($data['spell'], Spell::class));
            }
            
            if (isset($data['effect'])) {
                $item->setEffect($data['effect']);
            }
            
            $item->setCreatedAt(new \DateTime());
            $item->setUpdatedAt(new \DateTime());
            
            $manager->persist($item);
            $this->addReference($key, $item);
        }
        
        $manager->flush();
    }
    
    private function getItemsData(): array
    {
        return [
            // Matérias
            'materia_soin' => [
                'name' => 'Soin',
                'type' => 'materia',
                'slug' => 'm1-life',
                'element' => 'life',
                'description' => 'Matéria contenant un sort de soin',
                'spell' => 'life_heal',
                'domain' => 'healer',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 10,
                'nb_usages' => 10
            ],
            'materia_fire_ball' => [
                'name' => 'Boule de feu',
                'description' => 'Matéria contenant un sort de boule de feu',
                'type' => 'materia',
                'element' => 'fire',
                'spell' => 'fire_ball',
                'slug' => 'm1-fire',
                'domain' => 'pyromancy',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 10,
                'nb_usages' => 10
            ],
            'materia_flame' => [
                'name' => 'Flamme',
                'description' => 'Matéria contenant un sort de flamme',
                'type' => 'materia',
                'element' => 'fire',
                'spell' => 'flame',
                'slug' => 'm1-flame',
                'domain' => 'pyromancy',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 8,
                'nb_usages' => 25
            ],
            'materia_flamer' => [
                'name' => 'Feu',
                'description' => 'Matéria contenant un sort de feu',
                'type' => 'materia',
                'element' => 'fire',
                'spell' => 'flamer',
                'slug' => 'm2-fire',
                'domain' => 'pyromancy',
                'price' => 150,
                'space' => 1,
                'energy_cost' => 15,
                'nb_usages' => 15
            ],
            'materia_flame_rain' => [
                'name' => 'Pluie de feu',
                'description' => 'Matéria contenant un sort de pluie de feu',
                'type' => 'materia',
                'element' => 'fire',
                'spell' => 'flame_rain',
                'slug' => 'm3-fire',
                'domain' => 'pyromancy',
                'price' => 200,
                'space' => 1,
                'energy_cost' => 25,
                'nb_usages' => 5
            ],
            'materia_stone_throw' => [
                'name' => 'Jet de cailloux',
                'description' => 'Matéria contenant un sort de jet de cailloux',
                'type' => 'materia',
                'element' => 'earth',
                'spell' => 'stone_throw',
                'slug' => 'm1-earth',
                'domain' => 'soldier',
                'price' => 50,
                'space' => 1,
                'energy_cost' => 5,
                'nb_usages' => 20
            ],
            'materia_punishment' => [
                'name' => 'Châtiment',
                'description' => 'Matéria contenant un sort de châtiment',
                'type' => 'materia',
                'element' => 'death',
                'spell' => 'punishment',
                'slug' => 'm1-death',
                'domain' => 'necro',
                'price' => 150,
                'space' => 1,
                'energy_cost' => 15,
                'nb_usages' => 8
            ],
            'materia_wind_lame' => [
                'name' => "Lame d'air",
                'description' => "Matéria contenant un sort de lame d'air",
                'type' => 'materia',
                'element' => 'wind',
                'spell' => 'wind_lame',
                'slug' => 'm1-wind',
                'domain' => 'druid',
                'price' => 130,
                'space' => 1,
                'energy_cost' => 10,
                'nb_usages' => 20
            ],
            'materia_sharp_blade' => [
                'name' => 'Lame tranchante',
                'description' => 'Matéria contenant un sort de lame tranchante',
                'type' => 'materia',
                'element' => 'metal',
                'spell' => 'sharp_blade',
                'slug' => 'm1-metal',
                'domain' => 'soldier',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 8,
                'nb_usages' => 25
            ],
            'materia_liana_whip' => [
                'name' => 'Fouet de liane',
                'description' => 'Matéria contenant un sort de fouet de liane',
                'type' => 'materia',
                'element' => 'nature',
                'spell' => 'liana_whip',
                'slug' => 'm1-nature',
                'domain' => 'druid',
                'price' => 120,
                'space' => 1,
                'energy_cost' => 12,
                'nb_usages' => 15
            ],
            
            // Équipements
            'short_sword' => [
                'name' => 'Epée courte',
                'description' => 'Une épée courte de bonne facture',
                'type' => 'gear',
                'spell' => 'none_attack_1',
                'slug' => 'short-sword',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'price' => 50,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 100
            ],
            'long_sword' => [
                'name' => 'Epée longue',
                'description' => 'Une épée longue de bonne facture',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 'long-sword',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'price' => 100,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 100
            ],
            'leather_boots' => [
                'name' => 'Bottes en cuir',
                'description' => 'Des bottes en cuir confortables',
                'type' => 'gear',
                'slug' => 'leather-boots',
                'gear_location' => Item::GEAR_LOCATION_FEET,
                'domain' => 'soldier',
                'price' => 30,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 100
            ],
            
            // Objets divers
            'life_potion' => [
                'name' => 'Potion de soin',
                'description' => 'Une bonne potion de soin',
                'type' => 'stuff',
                'spell' => 'none_heal_2',
                'slug' => 'life-potion',
                'effect' => '{"action":"use_spell", "slug":"life-heal" }',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'fishing_rod' => [
                'name' => 'Canne à pèche',
                'description' => 'Une canne à pèche pour attraper de la friture',
                'type' => 'stuff',
                'slug' => 'fishing-rod',
                'domain' => 'fisherman',
                'price' => 40,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 50
            ],
            'beer_pint' => [
                'name' => 'Chope de bière',
                'description' => 'Une chope de bière pour boire',
                'type' => 'stuff',
                'slug' => 'beer-pint',
                'price' => 5,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'mushroom' => [
                'name' => 'Champignon',
                'description' => 'Un champignon, mais est-il comestible ?',
                'type' => 'stuff',
                'slug' => 'mushroom',
                'price' => 5,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'pickaxe' => [
                'name' => 'Pioche',
                'description' => 'Permet de casser des cailloux',
                'type' => 'stuff',
                'slug' => 'pickaxe',
                'domain' => 'miner',
                'price' => 50,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 100
            ],
            'wood_log' => [
                'name' => 'Buche de bois',
                'description' => 'Une bûche de bois',
                'type' => 'stuff',
                'slug' => 'wood-log',
                'price' => 8,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'leather_skin_1' => [
                'name' => 'Peau de cuir',
                'description' => 'Une peau de cuir brute',
                'type' => 'stuff',
                'slug' => 'leather-skin-1',
                'price' => 10,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'leather_skin_2' => [
                'name' => 'Peau de cuir',
                'description' => 'Une peau de cuir brute',
                'type' => 'stuff',
                'slug' => 'leather-skin-2',
                'price' => 10,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'life_domain_parchment' => [
                'name' => 'Apprentissage des soins',
                'description' => 'Permet de devenir apprenti soigneur',
                'type' => 'stuff',
                'slug' => 'life-domain-parchment',
                'effect' => '{"action":"learn_skill", "slug":"healer-materia-1" }',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'miner_domain_parchment' => [
                'name' => 'Découverte du minage',
                'description' => 'Permet de devenir apprenti mineur',
                'type' => 'stuff',
                'slug' => 'miner-domain-parchment',
                'effect' => '{"action":"learn_skill", "slug":"miner-ruby-xs" }',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'herbalist_domain_parchment' => [
                'name' => "Découverte de l'herborisme",
                'description' => 'Permet de devenir apprenti herboriste',
                'type' => 'stuff',
                'slug' => 'herbalist-domain-parchment',
                'effect' => '{"action":"learn_skill", "slug":"herbalist-mint-xs" }',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            
            // Minerais
            'ore_ruby' => [
                'name' => 'Ruby',
                'description' => 'Minerai de ruby',
                'type' => 'ore',
                'slug' => 'ore-ruby',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'ore_iron' => [
                'name' => 'Fer',
                'description' => 'Minerai de fer',
                'type' => 'ore',
                'slug' => 'ore-iron',
                'price' => 10,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            
            // Plantes
            'plant_mint' => [
                'name' => 'Menthe',
                'description' => 'Plante de Menthe',
                'type' => 'plant',
                'slug' => 'plant-mint',
                'price' => 5,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'plant_sage' => [
                'name' => 'Sauge',
                'description' => 'Plante de Sauge',
                'type' => 'plant',
                'slug' => 'plant-sage',
                'price' => 8,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
        ];
    }
    
    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
            SpellFixtures::class,
        ];
    }
} 