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
            'ancient_scroll' => [
                'name' => 'Parchemin ancien',
                'description' => 'Un parchemin mystérieux couvert de symboles arcanes',
                'type' => 'stuff',
                'slug' => 'ancient-scroll',
                'price' => 250,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"random_spell_knowledge", "chance":0.5}'
            ],
            'healing_potion_small' => [
                'name' => 'Potion de soin mineure',
                'description' => 'Restaure une petite quantité de points de vie',
                'type' => 'potion',
                'slug' => 'healing-potion-small',
                'price' => 50,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"heal", "amount":20}',
                'nb_usages' => 1
            ],
            'healing_potion_medium' => [
                'name' => 'Potion de soin',
                'description' => 'Restaure une quantité modérée de points de vie',
                'type' => 'potion',
                'slug' => 'healing-potion-medium',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"heal", "amount":50}',
                'nb_usages' => 1
            ],
            'energy_potion_small' => [
                'name' => "Potion d'énergie mineure",
                'description' => "Restaure une petite quantité d'énergie",
                'type' => 'potion',
                'slug' => 'energy-potion-small',
                'price' => 60,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"restore_energy", "amount":15}',
                'nb_usages' => 1
            ],
            'leather_boots' => [
                'name' => 'Bottes en cuir',
                'description' => 'Des bottes en cuir confortables et résistantes',
                'type' => 'gear',
                'gear_location' => 'feet',
                'slug' => 'leather-boots',
                'price' => 80,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'effect' => '{"action":"stat_boost", "stat":"speed", "amount":5}'
            ],
            'iron_sword' => [
                'name' => 'Épée en fer',
                'description' => 'Une épée en fer bien équilibrée',
                'type' => 'weapon',
                'gear_location' => 'hand',
                'slug' => 'iron-sword',
                'price' => 150,
                'space' => 3,
                'energy_cost' => 5,
                'nb_usages' => 200,
                'effect' => '{"action":"damage", "amount":15}'
            ],
            'wooden_shield' => [
                'name' => 'Bouclier en bois',
                'description' => 'Un bouclier en bois renforcé avec du métal',
                'type' => 'gear',
                'gear_location' => 'hand',
                'slug' => 'wooden-shield',
                'price' => 120,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'effect' => '{"action":"defense_boost", "amount":10}'
            ],
            'leather_helmet' => [
                'name' => 'Casque en cuir',
                'description' => 'Un casque en cuir offrant une protection légère',
                'type' => 'gear',
                'gear_location' => 'head',
                'slug' => 'leather-helmet',
                'price' => 75,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'effect' => '{"action":"defense_boost", "amount":5}'
            ],
            'leather_armor' => [
                'name' => 'Armure en cuir',
                'description' => 'Une armure en cuir offrant une protection légère',
                'type' => 'gear',
                'gear_location' => 'body',
                'slug' => 'leather-armor',
                'price' => 150,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 120,
                'effect' => '{"action":"defense_boost", "amount":12}'
            ],
            'iron_helmet' => [
                'name' => 'Casque en fer',
                'description' => 'Un casque en fer offrant une bonne protection',
                'type' => 'gear',
                'gear_location' => 'head',
                'slug' => 'iron-helmet',
                'price' => 180,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 200,
                'effect' => '{"action":"defense_boost", "amount":15}'
            ],
            'iron_armor' => [
                'name' => 'Armure en fer',
                'description' => 'Une armure en fer offrant une bonne protection',
                'type' => 'gear',
                'gear_location' => 'body',
                'slug' => 'iron-armor',
                'price' => 300,
                'space' => 4,
                'energy_cost' => 0,
                'nb_usages' => 250,
                'effect' => '{"action":"defense_boost", "amount":25}'
            ],
            'magic_amulet' => [
                'name' => 'Amulette magique',
                'description' => 'Une amulette qui amplifie les pouvoirs magiques',
                'type' => 'gear',
                'gear_location' => 'neck',
                'slug' => 'magic-amulet',
                'price' => 250,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'effect' => '{"action":"magic_boost", "amount":15}'
            ],
            'magic_ring' => [
                'name' => 'Anneau magique',
                'description' => 'Un anneau qui augmente la puissance magique',
                'type' => 'gear',
                'gear_location' => 'finger',
                'slug' => 'magic-ring',
                'price' => 200,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'effect' => '{"action":"magic_boost", "amount":10}'
            ],
            'bow' => [
                'name' => 'Arc',
                'description' => 'Un arc en bois permettant des attaques à distance',
                'type' => 'weapon',
                'gear_location' => 'hand',
                'slug' => 'bow',
                'price' => 120,
                'space' => 3,
                'energy_cost' => 8,
                'nb_usages' => 150,
                'effect' => '{"action":"ranged_damage", "amount":12}'
            ],
            'staff' => [
                'name' => 'Bâton',
                'description' => 'Un bâton en bois qui amplifie la magie',
                'type' => 'weapon',
                'gear_location' => 'hand',
                'slug' => 'staff',
                'price' => 100,
                'space' => 3,
                'energy_cost' => 5,
                'nb_usages' => 200,
                'effect' => '{"action":"magic_boost", "amount":20}'
            ],
            'dagger' => [
                'name' => 'Dague',
                'description' => 'Une dague légère et rapide',
                'type' => 'weapon',
                'gear_location' => 'hand',
                'slug' => 'dagger',
                'price' => 80,
                'space' => 1,
                'energy_cost' => 3,
                'nb_usages' => 150,
                'effect' => '{"action":"damage", "amount":8, "speed":2}'
            ],
            'herb_mint' => [
                'name' => 'Menthe sauvage',
                'description' => 'Une herbe aromatique aux propriétés curatives',
                'type' => 'herb',
                'slug' => 'herb-mint',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'herb_lavender' => [
                'name' => 'Lavande',
                'description' => 'Une herbe parfumée aux propriétés apaisantes',
                'type' => 'herb',
                'slug' => 'herb-lavender',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'magic_crystal' => [
                'name' => 'Cristal magique',
                'description' => 'Un cristal qui pulse avec une énergie mystérieuse',
                'type' => 'stuff',
                'slug' => 'magic-crystal',
                'price' => 200,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 5,
                'effect' => '{"action":"random_element_boost", "amount":10}'
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
                'price' => 12,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'ore_gold' => [
                'name' => 'Or',
                'description' => 'Minerai d\'or précieux',
                'type' => 'ore',
                'slug' => 'ore-gold',
                'price' => 30,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'ore_diamond' => [
                'name' => 'Diamant',
                'description' => 'Pierre précieuse d\'une pureté exceptionnelle',
                'type' => 'ore',
                'slug' => 'ore-diamond',
                'price' => 50,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'ore_emerald' => [
                'name' => 'Émeraude',
                'description' => 'Pierre précieuse d\'un vert éclatant',
                'type' => 'ore',
                'slug' => 'ore-emerald',
                'price' => 45,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'crafted_iron_ingot' => [
                'name' => 'Lingot de fer',
                'description' => 'Lingot de fer raffiné prêt à être forgé',
                'type' => 'crafted',
                'slug' => 'crafted-iron-ingot',
                'price' => 25,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'crafted_gold_ingot' => [
                'name' => 'Lingot d\'or',
                'description' => 'Lingot d\'or raffiné prêt à être forgé',
                'type' => 'crafted',
                'slug' => 'crafted-gold-ingot',
                'price' => 60,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'crafted_leather_strip' => [
                'name' => 'Lanière de cuir',
                'description' => 'Lanière de cuir tannée et traitée',
                'type' => 'crafted',
                'slug' => 'crafted-leather-strip',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'crafted_cloth' => [
                'name' => 'Tissu',
                'description' => 'Morceau de tissu de qualité',
                'type' => 'crafted',
                'slug' => 'crafted-cloth',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'crafted_potion_base' => [
                'name' => 'Base de potion',
                'description' => 'Solution de base pour la création de potions',
                'type' => 'crafted',
                'slug' => 'crafted-potion-base',
                'price' => 30,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            
            // Plantes
            'plant_lavender' => [
                'name' => 'Lavande',
                'description' => 'Plante aromatique aux propriétés calmantes',
                'type' => 'plant',
                'slug' => 'plant-lavender',
                'price' => 12,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["calming_potion", "sleep_potion"]}'
            ],
            'plant_mint' => [
                'name' => 'Menthe',
                'description' => 'Plante aromatique rafraîchissante',
                'type' => 'plant',
                'slug' => 'plant-mint',
                'price' => 10,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["energy_potion", "healing_potion"]}'
            ],
            'plant_sage' => [
                'name' => 'Sauge',
                'description' => 'Plante médicinale aux propriétés purifiantes',
                'type' => 'plant',
                'slug' => 'plant-sage',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["purification_potion", "antidote"]}'
            ],
            'plant_thyme' => [
                'name' => 'Thym',
                'description' => 'Plante aromatique aux propriétés antiseptiques',
                'type' => 'plant',
                'slug' => 'plant-thyme',
                'price' => 12,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["healing_potion", "protection_potion"]}'
            ],
            'plant_rosemary' => [
                'name' => 'Romarin',
                'description' => 'Plante aromatique stimulante pour la mémoire',
                'type' => 'plant',
                'slug' => 'plant-rosemary',
                'price' => 14,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["memory_potion", "focus_potion"]}'
            ],
            'plant_chamomile' => [
                'name' => 'Camomille',
                'description' => 'Plante médicinale aux propriétés apaisantes',
                'type' => 'plant',
                'slug' => 'plant-chamomile',
                'price' => 13,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["calming_potion", "sleep_potion"]}'
            ],
            'plant_nettle' => [
                'name' => 'Ortie',
                'description' => 'Plante médicinale fortifiante',
                'type' => 'plant',
                'slug' => 'plant-nettle',
                'price' => 8,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["strength_potion", "vitality_potion"]}'
            ],
            'plant_dandelion' => [
                'name' => 'Pissenlit',
                'description' => 'Plante médicinale aux propriétés détoxifiantes',
                'type' => 'plant',
                'slug' => 'plant-dandelion',
                'price' => 7,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["detox_potion", "purification_potion"]}'
            ],
            'plant_valerian' => [
                'name' => 'Valériane',
                'description' => 'Plante médicinale aux propriétés sédatives',
                'type' => 'plant',
                'slug' => 'plant-valerian',
                'price' => 18,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["sleep_potion", "tranquility_potion"]}'
            ],
            'plant_mandrake' => [
                'name' => 'Mandragore',
                'description' => 'Plante mystique aux puissantes propriétés magiques',
                'type' => 'plant',
                'slug' => 'plant-mandrake',
                'price' => 50,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["invisibility_potion", "transformation_potion"]}'
            ],
            'plant_nightshade' => [
                'name' => 'Belladone',
                'description' => 'Plante toxique utilisée avec précaution',
                'type' => 'plant',
                'slug' => 'plant-nightshade',
                'price' => 35,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["poison", "paralysis_potion"]}'
            ],
            'plant_wolfsbane' => [
                'name' => 'Aconit',
                'description' => 'Plante toxique aux propriétés mystiques',
                'type' => 'plant',
                'slug' => 'plant-wolfsbane',
                'price' => 40,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["lycanthropy_cure", "protection_potion"]}'
            ],
            'plant_aloe_vera' => [
                'name' => 'Aloe Vera',
                'description' => 'Plante médicinale aux propriétés cicatrisantes',
                'type' => 'plant',
                'slug' => 'plant-aloe-vera',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["healing_potion", "burn_remedy"]}'
            ],
            'plant_ginseng' => [
                'name' => 'Ginseng',
                'description' => 'Plante médicinale énergisante',
                'type' => 'plant',
                'slug' => 'plant-ginseng',
                'price' => 25,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["energy_potion", "vitality_potion"]}'
            ],
            'plant_echinacea' => [
                'name' => 'Échinacée',
                'description' => 'Plante médicinale renforçant les défenses naturelles',
                'type' => 'plant',
                'slug' => 'plant-echinacea',
                'price' => 22,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["immunity_potion", "resistance_potion"]}'
            ],
            // Plantes magiques et exotiques
            'plant_moonflower' => [
                'name' => 'Fleur de Lune',
                'description' => 'Plante rare qui ne fleurit que sous la pleine lune',
                'type' => 'plant',
                'slug' => 'plant-moonflower',
                'price' => 75,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["night_vision_potion", "lunar_blessing_potion"]}'
            ],
            'plant_sunblossom' => [
                'name' => 'Fleur Solaire',
                'description' => 'Plante qui absorbe l\'énergie du soleil',
                'type' => 'plant',
                'slug' => 'plant-sunblossom',
                'price' => 70,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["fire_resistance_potion", "solar_energy_potion"]}'
            ],
            'plant_dragonleaf' => [
                'name' => 'Feuille de Dragon',
                'description' => 'Plante rare aux propriétés ignifuges',
                'type' => 'plant',
                'slug' => 'plant-dragonleaf',
                'price' => 85,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["fire_breath_potion", "dragon_scale_potion"]}'
            ],
            'plant_frostcap' => [
                'name' => 'Chapeau de Givre',
                'description' => 'Champignon qui pousse dans les régions glaciales',
                'type' => 'plant',
                'slug' => 'plant-frostcap',
                'price' => 65,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["frost_resistance_potion", "ice_breath_potion"]}'
            ],
            'plant_ghostshroom' => [
                'name' => 'Champignon Fantôme',
                'description' => 'Champignon translucide qui brille dans l\'obscurité',
                'type' => 'plant',
                'slug' => 'plant-ghostshroom',
                'price' => 60,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["invisibility_potion", "spirit_vision_potion"]}'
            ],
            'plant_thunderroot' => [
                'name' => 'Racine Tonnerre',
                'description' => 'Racine qui accumule l\'énergie électrique',
                'type' => 'plant',
                'slug' => 'plant-thunderroot',
                'price' => 80,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["lightning_resistance_potion", "shock_potion"]}'
            ],
            'plant_whisperweed' => [
                'name' => 'Herbe Murmurante',
                'description' => 'Plante qui émet de légers murmures',
                'type' => 'plant',
                'slug' => 'plant-whisperweed',
                'price' => 55,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["telepathy_potion", "animal_speech_potion"]}'
            ],
            'plant_dreamlily' => [
                'name' => 'Lys des Rêves',
                'description' => 'Fleur qui influence les rêves',
                'type' => 'plant',
                'slug' => 'plant-dreamlily',
                'price' => 90,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["dream_walking_potion", "prophetic_vision_potion"]}'
            ],
            'plant_voidfruit' => [
                'name' => 'Fruit du Néant',
                'description' => 'Fruit étrange qui semble absorber la lumière',
                'type' => 'plant',
                'slug' => 'plant-voidfruit',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["void_protection_potion", "shadow_form_potion"]}'
            ],
            'plant_phoenixflower' => [
                'name' => 'Fleur de Phénix',
                'description' => 'Fleur rare qui renaît de ses cendres',
                'type' => 'plant',
                'slug' => 'plant-phoenixflower',
                'price' => 120,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["resurrection_potion", "eternal_flame_potion"]}'
            ],
            'food_bread' => [
                'name' => 'Pain',
                'description' => 'Un pain frais et nourrissant',
                'type' => 'food',
                'slug' => 'food-bread',
                'price' => 10,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"restore_energy", "amount":10}',
                'nb_usages' => 1
            ],
            'food_cheese' => [
                'name' => 'Fromage',
                'description' => 'Un morceau de fromage savoureux',
                'type' => 'food',
                'slug' => 'food-cheese',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"restore_energy", "amount":15}',
                'nb_usages' => 1
            ],
            'food_apple' => [
                'name' => 'Pomme',
                'description' => 'Une pomme juteuse et sucrée',
                'type' => 'food',
                'slug' => 'food-apple',
                'price' => 5,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"restore_energy", "amount":5}',
                'nb_usages' => 1
            ],
            'quest_item_ancient_key' => [
                'name' => 'Clé ancienne',
                'description' => 'Une clé mystérieuse qui semble très ancienne',
                'type' => 'quest',
                'slug' => 'quest-ancient-key',
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1
            ],
            'quest_item_magic_gem' => [
                'name' => 'Gemme magique',
                'description' => 'Une gemme qui brille d\'une lueur étrange',
                'type' => 'quest',
                'slug' => 'quest-magic-gem',
                'price' => 0,
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