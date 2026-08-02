<?php

namespace App\DataFixtures;

use App\DataFixtures\Game\SkillFixtures;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\Enum\BindType;
use App\Enum\Element;
use App\Enum\ItemRarity;
use App\Enum\MateriaSlotType;
use App\GameEngine\Economy\ResourceAffinityCatalog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly ResourceAffinityCatalog $affinities,
    ) {
    }

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

            $item->setRarity($data['rarity'] ?? $this->inferRarity($data));

            if (isset($data['protection'])) {
                $item->setProtection($data['protection']);
            }

            if (isset($data['materiaSlots'])) {
                $item->setMateriaSlots($data['materiaSlots']);
            }

            // DOM-03 : ce que la piece accepte. Absent = libre, et c'est ce qui
            // rend le typage additif : une piece non typee se comporte
            // exactement comme avant le jalon.
            if (isset($data['materiaSlotType'])) {
                $item->setMateriaSlotType($data['materiaSlotType']);
            }

            if (isset($data['bindType'])) {
                $item->setBindType(BindType::from($data['bindType']));
            } elseif (isset($data['boundToPlayer'])) {
                // Forme heritee : booleen valant « lie des l'obtention » (ECO-01).
                $item->setBindType(BindType::fromLegacyFlag((bool) $data['boundToPlayer']));
            }

            // Traductions localisees du nom (EN/DE/...) — sous-phase 135 s3c
            if (isset($data['name_translations']) && is_array($data['name_translations'])) {
                $item->setNameTranslations($data['name_translations']);
            }

            // Traductions localisees de la description (EN/DE/...) — sous-phase 135 s3d
            if (isset($data['description_translations']) && is_array($data['description_translations'])) {
                $item->setDescriptionTranslations($data['description_translations']);
            }

            // ZON-36 : l'affinite n'est jamais ecrite item par item. Elle se
            // **derive** de la table declarative — la ligne de recolte, corrigee
            // par la signature de la zone source. L'ecrire ici aurait duplique
            // cinquante valeurs et fait de la loi 10 une liste.
            $item->setAffinity($this->affinities->affinityOf($item->getSlug()));

            $item->setCreatedAt(new \DateTime());
            $item->setUpdatedAt(new \DateTime());

            $manager->persist($item);
            $this->addReference($key, $item);
        }

        // Second pass: set skill requirements on items
        foreach ($itemsData as $key => $data) {
            if (!isset($data['requirements'])) {
                continue;
            }

            /** @var Item $item */
            $item = $this->getReference($key, Item::class);

            foreach ($data['requirements'] as $skillRef) {
                $item->addRequirement($this->getReference($skillRef, Skill::class));
            }
        }

        $manager->flush();
    }

    private function getItemsData(): array
    {
        return [
            // Les materia ne vivent plus ici : le catalogue entier se DERIVE
            // des nœuds `actions.materia.unlock` et des sorts, par
            // `MateriaCatalogFixtures` (MAT-03). Une materia ecrite a la main
            // est un bug — cf. GAME_MATERIA §2.1.
            'short_sword' => [
                'name' => 'Epée courte',
                'name_translations' => ['en' => 'Short Sword'],
                'description' => 'Une épée courte de bonne facture',
                'type' => 'gear',
                'spell' => 'none_attack_1',
                'slug' => 'short-sword',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'price' => 50,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'materiaSlots' => 1,
                // ONB-12b : l'epee de palier 1 est `short-sword`, pas `t1-sword` —
                // la famille n'en a pas. Elle etait donc la **seule** dont
                // l'echelon de port restait inerte (ONB-20b), et c'est l'arme
                // que l'acte I met le plus souvent entre les mains.
                'requirements' => ['port_sword'],
            ],
            'long_sword' => [
                'name' => 'Epée longue',
                'name_translations' => ['en' => 'Long Sword'],
                'description' => 'Une épée longue de bonne facture',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 'long-sword',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'price' => 100,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'rarity' => ItemRarity::Uncommon,
                'materiaSlots' => 1,
            ],
            'leather_boots' => [
                'name' => 'Bottes en cuir',
                'name_translations' => ['en' => 'Leather Boots'],
                'description' => 'Des bottes en cuir confortables',
                'type' => 'gear',
                'slug' => 'leather-boots',
                'gear_location' => Item::GEAR_LOCATION_FEET,
                'domain' => 'soldier',
                'price' => 30,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'materiaSlots' => 1,
            ],
            'leather_hat' => [
                'name' => 'Chapeau de cuir',
                'name_translations' => ['en' => 'Leather Hat'],
                'description' => 'Un chapeau de cuir standard de manufacture classique',
                'type' => 'gear',
                'slug' => 'leather-hat',
                'gear_location' => Item::GEAR_LOCATION_HEAD,
                'price' => 25,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'materiaSlots' => 1,
            ],
            'leather_armor' => [
                'name' => 'Armure de cuir',
                'name_translations' => ['en' => 'Leather Armor'],
                'description' => 'Une armure de cuir de vache simple',
                'type' => 'gear',
                'slug' => 'leather-armor',
                'gear_location' => Item::GEAR_LOCATION_CHEST,
                'price' => 95,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'materiaSlots' => 1,
            ],

            // Objets divers
            'life_potion' => [
                'name' => 'Potion de soin',
                'name_translations' => ['en' => 'Healing Potion'],
                'description' => 'Une bonne potion de soin',
                'description_translations' => ['en' => 'A solid healing potion'],
                'type' => 'stuff',
                'spell' => 'none_heal_2',
                'slug' => 'life-potion',
                'effect' => '{"action":"use_spell", "slug":"life-heal" }',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'fishing_rod' => [
                'name' => 'Canne à pèche',
                'name_translations' => ['en' => 'Fishing Rod'],
                'description' => 'Une canne à pèche pour attraper de la friture',
                'description_translations' => ['en' => 'A fishing rod to catch small fish'],
                'type' => 'stuff',
                'slug' => 'fishing-rod',
                'domain' => 'fisherman',
                'price' => 40,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 50,
            ],
            'beer_pint' => [
                'name' => 'Chope de bière',
                'name_translations' => ['en' => 'Beer Mug'],
                'description' => 'Une chope de bière pour boire',
                'type' => 'stuff',
                'slug' => 'beer-pint',
                'price' => 5,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'mushroom' => [
                'name' => 'Champignon',
                'name_translations' => ['en' => 'Mushroom'],
                'description' => 'Un champignon, mais est-il comestible ?',
                'type' => 'stuff',
                'slug' => 'mushroom',
                'price' => 5,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'life_domain_parchment' => [
                'name' => 'Apprentissage des soins',
                'name_translations' => ['en' => 'Healing Apprenticeship'],
                'description' => 'Permet de devenir apprenti soigneur',
                'type' => 'stuff',
                'slug' => 'life-domain-parchment',
                // ONB-08 — le parchemin ouvre l'arbre, il n'accorde plus une
                // competence precise. Le slug vise le domaine, tel que
                // `Domain::getSlug()` le derive du titre (accents compris).
                'effect' => '{"action":"open_domain", "slug":"guérisseur" }',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'miner_domain_parchment' => [
                'name' => 'Découverte du minage',
                'name_translations' => ['en' => 'Mining Discovery'],
                'description' => 'Permet de devenir apprenti mineur',
                'type' => 'stuff',
                'slug' => 'miner-domain-parchment',
                'effect' => '{"action":"open_domain", "slug":"mineur" }',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'ancient_scroll' => [
                'name' => 'Parchemin ancien',
                'name_translations' => ['en' => 'Ancient Scroll'],
                'description' => 'Un parchemin mystérieux couvert de symboles arcanes',
                'type' => 'stuff',
                'slug' => 'ancient-scroll',
                'price' => 250,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"random_spell_knowledge", "chance":0.5}',
                'rarity' => ItemRarity::Rare,
            ],
            'healing_potion_small' => [
                'name' => 'Potion de soin mineure',
                'name_translations' => ['en' => 'Minor Healing Potion'],
                'description' => 'Restaure une petite quantité de points de vie',
                'type' => 'stuff',
                'slug' => 'healing-potion-small',
                'price' => 50,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"heal", "amount":20}',
                'nb_usages' => 1,
            ],
            'healing_potion_medium' => [
                'name' => 'Potion de soin',
                'name_translations' => ['en' => 'Healing Potion'],
                'description' => 'Restaure une quantité modérée de points de vie',
                'type' => 'stuff',
                'slug' => 'healing-potion-medium',
                'spell' => 'potion_heal_medium_spell',
                'effect' => '{"action":"use_spell","slug":"potion-heal-medium"}',
                'price' => 110,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'energy_potion_small' => [
                'name' => "Potion d'énergie mineure",
                'name_translations' => ['en' => 'Minor Energy Potion'],
                'description' => "Restaure une petite quantité d'énergie",
                'type' => 'stuff',
                'slug' => 'energy-potion-small',
                'price' => 120,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"restore_energy", "amount":15}',
                'nb_usages' => 1,
            ],

            // --- Consommables de base (tache 15) ---

            // Potions
            'healing_potion_major' => [
                'name' => 'Potion de soin majeure',
                'name_translations' => ['en' => 'Major Healing Potion'],
                'description' => 'Restaure une grande quantité de points de vie',
                'type' => 'stuff',
                'slug' => 'healing-potion-major',
                // ZON-35 : le prix suit les intrants ajoutes (regle d'ECO-27,
                // prix = cout + 10 x niveau).
                'price' => 340,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'potion_heal_major',
                'effect' => '{"action":"use_spell", "slug":"potion-heal-major"}',
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'antidote' => [
                'name' => 'Antidote',
                'name_translations' => ['en' => 'Antidote'],
                'description' => 'Un remède qui purifie le corps des poisons et soigne légèrement',
                'type' => 'stuff',
                'slug' => 'antidote',
                'price' => 75,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'antidote_heal',
                'effect' => '{"action":"use_spell", "slug":"antidote-heal"}',
                'nb_usages' => 1,
            ],

            // Nourritures
            'bread' => [
                'name' => 'Pain',
                'name_translations' => ['en' => 'Bread'],
                'description' => 'Un bon morceau de pain frais qui redonne des forces',
                'type' => 'stuff',
                'slug' => 'bread',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'bread_heal',
                'effect' => '{"action":"use_spell", "slug":"bread-heal"}',
                'nb_usages' => 1,
            ],
            'grilled_meat' => [
                'name' => 'Viande grillée',
                'name_translations' => ['en' => 'Grilled Meat'],
                'description' => 'Une pièce de viande grillée à point, nourrissante et savoureuse',
                'type' => 'stuff',
                'slug' => 'grilled-meat',
                'price' => 40,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'grilled_meat_heal',
                'effect' => '{"action":"use_spell", "slug":"grilled-meat-heal"}',
                'nb_usages' => 1,
            ],
            'stew' => [
                'name' => 'Ragoût',
                'name_translations' => ['en' => 'Stew'],
                'description' => 'Un copieux ragoût de légumes et de viande, idéal pour se remettre d\'aplomb',
                'type' => 'stuff',
                'slug' => 'stew',
                'price' => 80,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'stew_heal',
                'effect' => '{"action":"use_spell", "slug":"stew-heal"}',
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],

            // Parchemins
            'scroll_teleport' => [
                'name' => 'Parchemin de téléportation',
                'name_translations' => ['en' => 'Teleport Scroll'],
                'description' => 'Un parchemin magique qui ramène instantanément au point de résurrection',
                'type' => 'stuff',
                'slug' => 'scroll-teleport',
                'price' => 150,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'scroll_xp_boost' => [
                'name' => 'Parchemin de savoir',
                'name_translations' => ['en' => 'Scroll of Knowledge'],
                'description' => 'Un parchemin ancien qui augmente temporairement l\'expérience gagnée',
                'type' => 'stuff',
                'slug' => 'scroll-xp-boost',
                'price' => 300,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Rare,
            ],
            'scroll_identification' => [
                'name' => 'Parchemin d\'identification',
                'name_translations' => ['en' => 'Identification Scroll'],
                'description' => 'Révèle les propriétés cachées d\'un objet mystérieux',
                'type' => 'stuff',
                'slug' => 'scroll-identification',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],

            'iron_sword' => [
                'name' => 'Épée en fer',
                'name_translations' => ['en' => 'Iron Sword'],
                'description' => 'Une épée en fer bien équilibrée',
                'type' => 'gear',
                'gear_location' => 'hand',
                'slug' => 'iron-sword',
                'materiaSlots' => 1,
                'price' => 150,
                'space' => 3,
                'energy_cost' => 5,
                'nb_usages' => 200,
                'effect' => '{"action":"damage", "amount":15}',
                'rarity' => ItemRarity::Uncommon,
            ],
            'wooden_shield' => [
                'name' => 'Bouclier en bois',
                'name_translations' => ['en' => 'Wooden Shield'],
                'description' => 'Un bouclier en bois renforcé avec du métal',
                'type' => 'gear',
                'gear_location' => 'hand',
                'slug' => 'wooden-shield',
                'price' => 120,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'effect' => '{"action":"defense_boost", "amount":10}',
                'materiaSlots' => 1,
            ],
            'leather_helmet' => [
                'name' => 'Casque en cuir',
                'name_translations' => ['en' => 'Leather Helmet'],
                'description' => 'Un casque en cuir offrant une protection légère',
                'type' => 'gear',
                'gear_location' => 'head',
                'slug' => 'leather-helmet',
                'price' => 75,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'effect' => '{"action":"defense_boost", "amount":5}',
                'materiaSlots' => 1,
            ],
            'magic_amulet' => [
                'name' => 'Amulette magique',
                'name_translations' => ['en' => 'Magic Amulet'],
                'description' => 'Une amulette qui amplifie les pouvoirs magiques',
                'type' => 'gear',
                'gear_location' => 'neck',
                'slug' => 'magic-amulet',
                'price' => 250,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'effect' => '{"action":"magic_boost", "amount":15}',
                'rarity' => ItemRarity::Rare,
                'materiaSlots' => 1,
            ],
            'magic_ring' => [
                'name' => 'Anneau magique',
                'name_translations' => ['en' => 'Magic Ring'],
                'description' => 'Un anneau qui augmente la puissance magique',
                'type' => 'gear',
                'gear_location' => 'finger',
                'slug' => 'magic-ring',
                'price' => 200,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'effect' => '{"action":"magic_boost", "amount":10}',
                'rarity' => ItemRarity::Rare,
                'materiaSlots' => 1,
            ],
            'bow' => [
                'name' => 'Arc',
                'name_translations' => ['en' => 'Bow'],
                'description' => 'Un arc en bois permettant des attaques à distance',
                'type' => 'gear',
                'gear_location' => 'hand',
                'slug' => 'bow',
                'materiaSlots' => 1,
                'price' => 120,
                'space' => 3,
                'energy_cost' => 8,
                'nb_usages' => 150,
                'effect' => '{"action":"ranged_damage", "amount":12}',
            ],
            'staff' => [
                'name' => 'Bâton',
                'name_translations' => ['en' => 'Staff'],
                'description' => 'Un bâton en bois qui amplifie la magie',
                'type' => 'gear',
                'gear_location' => 'hand',
                'slug' => 'staff',
                'materiaSlots' => 1,
                'price' => 100,
                'space' => 3,
                'energy_cost' => 5,
                'nb_usages' => 200,
                'effect' => '{"action":"magic_boost", "amount":20}',
            ],
            'dagger' => [
                'name' => 'Dague',
                'name_translations' => ['en' => 'Dagger'],
                'description' => 'Une dague légère et rapide',
                'type' => 'gear',
                'gear_location' => 'hand',
                'slug' => 'dagger',
                'materiaSlots' => 1,
                'price' => 80,
                'space' => 1,
                'energy_cost' => 3,
                'nb_usages' => 150,
                'effect' => '{"action":"damage", "amount":8, "speed":2}',
            ],
            'magic_crystal' => [
                'name' => 'Cristal magique',
                'name_translations' => ['en' => 'Magic Crystal'],
                'description' => 'Un cristal qui pulse avec une énergie mystérieuse',
                'type' => 'stuff',
                'slug' => 'magic-crystal',
                'price' => 200,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 5,
                'effect' => '{"action":"random_element_boost", "amount":10}',
                'rarity' => ItemRarity::Epic,
            ],
            'herbalist_domain_parchment' => [
                'name' => "Découverte de l'herborisme",
                'name_translations' => ['en' => 'Herbalism Discovery'],
                'description' => 'Permet de devenir apprenti herboriste',
                'type' => 'stuff',
                'slug' => 'herbalist-domain-parchment',
                'effect' => '{"action":"open_domain", "slug":"herboriste" }',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],

            // Minerais
            'ore_ruby' => [
                'name' => 'Ruby',
                'name_translations' => ['en' => 'Ruby'],
                'description' => 'Minerai de ruby',
                'type' => 'resource',
                'slug' => 'ore-ruby',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            // ore-iron et ore-gold sont désormais dans fixtures/game/item/ore.yaml
            'ore_diamond' => [
                'name' => 'Diamant',
                'name_translations' => ['en' => 'Diamond'],
                'description' => 'Pierre précieuse d\'une pureté exceptionnelle',
                'type' => 'resource',
                'slug' => 'ore-diamond',
                'price' => 50,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Rare,
            ],
            'ore_emerald' => [
                'name' => 'Émeraude',
                'name_translations' => ['en' => 'Emerald'],
                'description' => 'Pierre précieuse d\'un vert éclatant',
                'type' => 'resource',
                'slug' => 'ore-emerald',
                'price' => 45,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Rare,
            ],
            'crafted_bronze_ingot' => [
                'name' => 'Lingot de bronze',
                'name_translations' => ['en' => 'Bronze Ingot'],
                'description' => 'Alliage de cuivre et d\'étain, base de l\'artisanat débutant',
                'type' => 'resource',
                'slug' => 'crafted-bronze-ingot',
                'price' => 35,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'crafted_iron_ingot' => [
                'name' => 'Lingot de fer',
                'name_translations' => ['en' => 'Iron Ingot'],
                'description' => 'Lingot de fer raffiné prêt à être forgé',
                'type' => 'resource',
                'slug' => 'crafted-iron-ingot',
                'price' => 85,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'crafted_cobalt_ingot' => [
                'name' => 'Lingot de cobalt',
                'name_translations' => ['en' => 'Cobalt Ingot'],
                'description' => 'Lingot de cobalt d\'un bleu profond, très résistant',
                'type' => 'resource',
                'slug' => 'crafted-cobalt-ingot',
                'price' => 220,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'crafted_mithril_ingot' => [
                'name' => 'Lingot de mithril',
                'name_translations' => ['en' => 'Mithril Ingot'],
                'description' => 'Lingot de mithril d\'une légèreté extraordinaire',
                'type' => 'resource',
                'slug' => 'crafted-mithril-ingot',
                'price' => 600,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Rare,
            ],
            'crafted_gold_ingot' => [
                'name' => 'Lingot d\'or',
                'name_translations' => ['en' => 'Gold Ingot'],
                'description' => 'Lingot d\'or raffiné prêt à être forgé',
                'type' => 'resource',
                'slug' => 'crafted-gold-ingot',
                'price' => 210,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'crafted_leather_strip' => [
                'name' => 'Lanière de cuir',
                'name_translations' => ['en' => 'Leather Strip'],
                'description' => 'Lanière de cuir tannée et traitée',
                'type' => 'resource',
                'slug' => 'crafted-leather-strip',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'crafted_cloth' => [
                'name' => 'Tissu',
                'name_translations' => ['en' => 'Cloth'],
                'description' => 'Morceau de tissu de qualité',
                'type' => 'resource',
                'slug' => 'crafted-cloth',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'crafted_potion_base' => [
                'name' => 'Base de potion',
                'name_translations' => ['en' => 'Potion Base'],
                'description' => 'Solution de base pour la création de potions',
                'type' => 'resource',
                'slug' => 'crafted-potion-base',
                'price' => 45,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],

            // --- Gemmes taillées joaillier (intermédiaires craft) ---
            'crafted_gem_basic' => [
                'name' => 'Gemme taillée',
                'name_translations' => ['en' => 'Cut Gem'],
                'description' => 'Une gemme brute polie et taillée, prête à être sertie',
                'type' => 'resource',
                'slug' => 'crafted-gem-basic',
                'price' => 30,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'crafted_gem_fine' => [
                'name' => 'Gemme fine',
                'name_translations' => ['en' => 'Fine Gem'],
                'description' => 'Une gemme taillée avec précision, d\'une clarté remarquable',
                'type' => 'resource',
                'slug' => 'crafted-gem-fine',
                'price' => 120,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            // ZON-31 — le debouche de l'ambre. Une exclusivite sans debouche
            // est un mensonge de level design : le filon existerait, et rien
            // n'en ferait rien.
            //
            // Prix : cout (2x60 + 30 + 20 = 170) + 10 x niveau 4 = 210, la
            // regle d'ECO-27.
            'crafted_amber_seal' => [
                'name' => 'Sceau d\'ambre',
                'name_translations' => ['en' => 'Amber Seal'],
                'description' => 'Une gemme prise dans l\'ambre et cerclée d\'argent. On y lit un insecte plus vieux que les cités.',
                'type' => 'resource',
                'slug' => 'crafted-amber-seal',
                'price' => 210,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            'crafted_gem_rare' => [
                'name' => 'Gemme rare',
                'name_translations' => ['en' => 'Rare Gem'],
                'description' => 'Une gemme d\'exception aux reflets magiques',
                'type' => 'resource',
                'slug' => 'crafted-gem-rare',
                'price' => 420,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Rare,
            ],
            'crafted_gem_enchanted' => [
                'name' => 'Gemme enchantée',
                'name_translations' => ['en' => 'Enchanted Gem'],
                'description' => 'Une gemme imprégnée d\'énergie magique, elle luit faiblement',
                'type' => 'resource',
                'slug' => 'crafted-gem-enchanted',
                'price' => 670,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Epic,
            ],

            // --- Consommables alchimiste (recettes craft) ---
            'poison_vial' => [
                'name' => 'Fiole de poison',
                'name_translations' => ['en' => 'Poison Vial'],
                'description' => 'Un poison concentré dans une fiole fragile, inflige des dégâts et empoisonne',
                'type' => 'stuff',
                'slug' => 'poison-vial',
                'spell' => 'poison_vial_spell',
                'effect' => '{"action":"use_spell","slug":"poison-vial"}',
                // ZON-35 puis ZON-33 : le prix suit les intrants ajoutes (regle
                // d'ECO-27, prix = cout + 10 x niveau). La racine de marais porte
                // le cout a 215 ; sans reprise, la fiole se rapprochait a 5 gils
                // de detruire de la valeur.
                'price' => 245,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'elixir_force' => [
                'name' => 'Élixir de force',
                'name_translations' => ['en' => 'Strength Elixir'],
                'description' => 'Un élixir puissant qui décuple temporairement la force de frappe',
                'type' => 'stuff',
                'slug' => 'elixir-force',
                'spell' => 'elixir_force_spell',
                'effect' => '{"action":"use_spell","slug":"elixir-force"}',
                // ECO-29 : le ragout du cuisinier (40) entre dans le cout, et le
                // prix suit la regle d'ECO-27 — 210 + 10 x 3.
                // ZON-35 : le prix suit les intrants ajoutes (regle d'ECO-27,
                // prix = cout + 10 x niveau).
                'price' => 300,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'elixir_defense' => [
                'name' => 'Élixir de défense',
                'name_translations' => ['en' => 'Defense Elixir'],
                'description' => 'Un élixir qui forme un bouclier magique autour du buveur',
                'type' => 'stuff',
                'slug' => 'elixir-defense',
                'spell' => 'elixir_defense_spell',
                'effect' => '{"action":"use_spell","slug":"elixir-defense"}',
                // ZON-35 : le prix suit les intrants ajoutes (regle d'ECO-27,
                // prix = cout + 10 x niveau).
                'price' => 180,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'onguent_healing' => [
                'name' => 'Onguent de guérison',
                'name_translations' => ['en' => 'Healing Salve'],
                'description' => 'Un baume cicatrisant qui régénère les blessures progressivement',
                'type' => 'stuff',
                'slug' => 'onguent-healing',
                'spell' => 'onguent_healing_spell',
                'effect' => '{"action":"use_spell","slug":"onguent-healing"}',
                'price' => 90,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
            ],
            'elixir_vitality' => [
                'name' => 'Élixir de vitalité',
                'name_translations' => ['en' => 'Vitality Elixir'],
                'description' => 'Un puissant élixir qui revigore profondément le corps et restaure la santé',
                'type' => 'stuff',
                'slug' => 'elixir-vitality',
                'spell' => 'elixir_vitality_spell',
                'effect' => '{"action":"use_spell","slug":"elixir-vitality"}',
                // ZON-35 : le prix suit les intrants ajoutes (regle d'ECO-27,
                // prix = cout + 10 x niveau).
                'price' => 370,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Rare,
            ],
            // Plantes
            //
            // ZON-30 — les deux cultures des Vallons d'Aubepine. Elles ne sont
            // pas des herbes : ce sont les premieres matieres **agricoles** du
            // monde, et elles ouvrent deux lignes que rien n'alimentait.
            'plant_wheat' => [
                'name' => 'Blé',
                'name_translations' => ['en' => 'Wheat'],
                'description' => 'Une gerbe de blé des carrés d\'Aubépine. Le grenier du monde tient dans une poignée.',
                'type' => 'resource',
                'slug' => 'plant-wheat',
                // T0, au niveau du cuir brut : c'est la matiere la plus commune
                // du monde, et son prix doit le dire.
                'price' => 4,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            // L'**exclusivite** des Vallons (GAME_ZONES § 2.2, loi 1) : le lin
            // est la fibre du tanneur et du textile, et aucune autre zone n'en
            // produira. C'est ce qui ramenera un veteran dans une zone d'Acte I.
            'plant_flax' => [
                'name' => 'Lin',
                'name_translations' => ['en' => 'Flax'],
                'description' => 'Une brassée de lin des linières du gué. Filé, il devient fil ; roui, il devient toile.',
                'type' => 'resource',
                'slug' => 'plant-flax',
                'price' => 9,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            // Matieres animales des Vallons (ZON-30). Elles ont depuis trouve
            // leur metier : la viande au cuisinier (ECO-29), les plumes au
            // charpentier (ECO-30). Les poser **avant** eux etait delibere — la
            // loi « chaque item de recette a une source » se tient plus
            // facilement quand la source precede la recette que l'inverse.
            'meat_game' => [
                'name' => 'Viande de gibier',
                'name_translations' => ['en' => 'Game Meat'],
                'description' => 'Une pièce de gibier fraîchement levée, à cuisiner sans attendre.',
                'type' => 'resource',
                'slug' => 'meat-game',
                'price' => 6,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            'feather_raw' => [
                'name' => 'Plumes de corbeau',
                'name_translations' => ['en' => 'Crow Feathers'],
                'description' => 'Une poignée de plumes noires, raides comme il faut pour empenner.',
                'type' => 'resource',
                'slug' => 'feather-raw',
                'price' => 3,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            // =================================================================
            // ECO-29 — la table du cuisinier
            // =================================================================
            // Sept plats, et leur raison d'exister tient en une ligne : **les
            // sept poissons du monde n'etaient consommes par rien**. Six
            // filons de peche, un arbre entier de competences, et pas une
            // recette au bout — le defaut le plus large de l'audit d'economie.
            //
            // Les plats **soignent**, ils ne buffent pas. Les « buffs
            // temporaires modestes » du jalon demandent un canal de bonus par
            // consommable qui n'existe pas ; les plats reprennent donc les
            // sorts de soin deja poses, par palier. Inventer le canal ici
            // aurait fait de ce jalon un chantier de mecanique alors qu'il
            // repare un trou de contenu.
            'crafted_bread' => [
                'name' => 'Pain de campagne',
                'name_translations' => ['en' => 'Country Bread'],
                'description' => 'Une miche cuite au four du Vieux Moulin. Le blé des Vallons trouve enfin sa fin.',
                'type' => 'resource',
                'slug' => 'crafted-bread',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'bread_heal',
                'effect' => '{"action":"use_spell", "slug":"bread-heal"}',
                'nb_usages' => 1,
            ],
            'crafted_fish_skewer' => [
                'name' => 'Brochette du gué',
                'name_translations' => ['en' => 'Ford Skewer'],
                'description' => 'Une perche et une truite enfilées sur la même branche. La première chose qu\'un pêcheur apprend à cuire.',
                'type' => 'resource',
                'slug' => 'crafted-fish-skewer',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'bread_heal',
                'effect' => '{"action":"use_spell", "slug":"bread-heal"}',
                'nb_usages' => 1,
            ],
            'crafted_carp_stew' => [
                'name' => 'Ragoût de carpe',
                'name_translations' => ['en' => 'Carp Stew'],
                'description' => 'Carpe des étangs et gibier de plaine dans le même pot. Ce que le marais et le bocage font ensemble.',
                'type' => 'resource',
                'slug' => 'crafted-carp-stew',
                'price' => 40,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'grilled_meat_heal',
                'effect' => '{"action":"use_spell", "slug":"grilled-meat-heal"}',
                'nb_usages' => 1,
            ],
            'crafted_salmon_roast' => [
                'name' => 'Saumon rôti',
                'name_translations' => ['en' => 'Roast Salmon'],
                'description' => 'Un saumon des rapides rôti sur son lit de pain. Le plat des jours où l\'on a bien pêché.',
                'type' => 'resource',
                'slug' => 'crafted-salmon-roast',
                // Le fond d'alchimiste (45) entre dans le cout : 85 + 10 x 3.
                'price' => 120,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'grilled_meat_heal',
                'effect' => '{"action":"use_spell", "slug":"grilled-meat-heal"}',
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'crafted_moonfish_plate' => [
                'name' => 'Poisson-lune en écailles',
                'name_translations' => ['en' => 'Moonfish Platter'],
                'description' => 'Les écailles irisées se soulèvent une à une. On mange lentement, et on s\'en souvient.',
                'type' => 'resource',
                'slug' => 'crafted-moonfish-plate',
                // ZON-35 : le prix suit les intrants ajoutes (regle d'ECO-27,
                // prix = cout + 10 x niveau).
                'price' => 150,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'stew_heal',
                'effect' => '{"action":"use_spell", "slug":"stew-heal"}',
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'crafted_eel_dish' => [
                'name' => 'Anguille au poivre',
                'name_translations' => ['en' => 'Peppered Eel'],
                'description' => 'Elle crépite encore dans l\'assiette. Ceux qui l\'ont pêchée disent que c\'est normal.',
                'type' => 'resource',
                'slug' => 'crafted-eel-dish',
                'price' => 130,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'stew_heal',
                'effect' => '{"action":"use_spell", "slug":"stew-heal"}',
                'nb_usages' => 1,
                'rarity' => ItemRarity::Rare,
            ],
            // ZON-35 — les epices, reportees par ECO-29 faute de ce jalon.
            //
            // Quatre herbes banales — pissenlit, ortie, romarin, echinacee — se
            // recoltaient dans quatre zones **sans qu'une seule recette ne les
            // consomme**. Elles n'etaient pas mortes (elles avaient un filon),
            // elles etaient inutiles, ce qui est pire : le joueur les ramasse,
            // remplit son sac, et decouvre au comptoir qu'elles ne valent que
            // leur prix de vente.
            //
            // Le melange est ce qui les absorbe toutes les quatre d'un coup, et
            // il donne au cuisinier son second intermediaire vertical a cote du
            // pain (loi 9, GAME_ZONES § 3 ter).
            'crafted_spice_blend' => [
                'name' => 'Mélange d\'épices',
                'name_translations' => ['en' => 'Spice Blend'],
                'description' => 'Pissenlit séché, ortie, romarin et échinacée, pilés ensemble. Ce qui poussait au bord des chemins devient du goût.',
                'type' => 'resource',
                'slug' => 'crafted-spice-blend',
                'price' => 50,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            'crafted_kraken_feast' => [
                'name' => 'Festin de kraken',
                'name_translations' => ['en' => 'Kraken Feast'],
                'description' => 'Un kraken juvénile et deux miches. Il en faut deux : le reste de la table en veut aussi.',
                'type' => 'resource',
                'slug' => 'crafted-kraken-feast',
                // ZON-35 : le prix suit les intrants ajoutes (regle d'ECO-27,
                // prix = cout + 10 x niveau).
                'price' => 350,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'stew_heal',
                'effect' => '{"action":"use_spell", "slug":"stew-heal"}',
                'nb_usages' => 1,
                'rarity' => ItemRarity::Rare,
            ],
            // =================================================================
            // ZON-34 — les quatre essences de la ligne du bois
            // =================================================================
            // Aucune ressource bois n'existait : la ligne entiere (armes de
            // bois, mobilier) etait sans matiere, et `wood-log` — une buche de
            // decor sans filon ni recette — en tenait lieu (GAME_ZONES § 3 bis).
            //
            // Raretes inversees respectees : le hetre a **deux** sources et ne
            // doit jamais etre un goulot ; les trois autres sont chacune
            // l'exclusivite d'une zone forestiere.
            'wood_beech' => [
                'name' => 'Bois de hêtre',
                'name_translations' => ['en' => 'Beech Wood'],
                'description' => 'Un rondin de hêtre clair, droit de fil. Le bois qu\'on trouve partout et dont tout part.',
                'type' => 'resource',
                'slug' => 'wood-beech',
                // T0, au niveau du ble et du cuir brut : la matiere la plus
                // commune de sa ligne.
                'price' => 4,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            'wood_whisperoak' => [
                'name' => 'Chêne murmurant',
                'name_translations' => ['en' => 'Whispering Oak'],
                'description' => 'Une branche de l\'arbre qui donne son nom à la forêt. Elle vibre encore quand on la tient.',
                'type' => 'resource',
                'slug' => 'wood-whisperoak',
                'price' => 22,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            'wood_peat' => [
                'name' => 'Bois tourbé',
                'name_translations' => ['en' => 'Peat Wood'],
                'description' => 'Un bois noirci par l\'eau morte du marais. Il ne pourrit plus : il a déjà fini de le faire.',
                'type' => 'resource',
                'slug' => 'wood-peat',
                'price' => 55,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            'wood_petrified' => [
                'name' => 'Bois pétrifié',
                'name_translations' => ['en' => 'Petrified Wood'],
                'description' => 'Un tronc de l\'âge précédent, changé en pierre par le sable. On y compte encore les cernes.',
                'type' => 'resource',
                'slug' => 'wood-petrified',
                'price' => 110,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            // =================================================================
            // ECO-30 — l'établi du charpentier
            // =================================================================
            // ZON-34 avait posé quatre essences **sans un seul débouché** : on
            // pouvait abattre le chêne murmurant et n'avoir rien à en faire. Ces
            // quatre objets sont ce que le bois devient.
            //
            // La planche joue pour le bois le rôle que la lanière joue pour le
            // cuir : un intermédiaire bon marché que tout le reste du métier
            // traverse. C'est ce qui donne au hêtre une demande proportionnelle
            // à l'activité de haut palier, et non un plancher qui s'éteint.
            'crafted_plank' => [
                'name' => 'Planche de hêtre',
                'name_translations' => ['en' => 'Beech Plank'],
                'description' => 'Une planche débitée droit de fil, séchée et rabotée. Tout ce que le charpentier fait passe par elle.',
                'type' => 'resource',
                'slug' => 'crafted-plank',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            // Le manche est la piece que le charpentier vend au **forgeron** : une
            // hache est un fer sur un bois, et jusqu'ici le fer se passait du
            // bois. C'est par lui que le metier a une demande hors de lui-meme
            // (ECO-14).
            'crafted_wood_haft' => [
                'name' => 'Manche de bois',
                'name_translations' => ['en' => 'Wooden Haft'],
                'description' => 'Un manche tourné et poncé, prêt à recevoir un fer. Le forgeron ne sait pas les faire.',
                'type' => 'resource',
                'slug' => 'crafted-wood-haft',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            // Le consommable perpetuel du metier. Un artisan qui ne produit que
            // du durable voit sa demande s'eteindre le jour ou chacun a son arc ;
            // la fleche, elle, se depense.
            'crafted_arrows' => [
                'name' => 'Flèches empennées',
                'name_translations' => ['en' => 'Fletched Arrows'],
                'description' => 'Une botte de flèches empennées de plumes de corbeau. Elles partent vite et ne reviennent pas.',
                'type' => 'resource',
                'slug' => 'crafted-arrows',
                'price' => 8,
                'space' => 1,
                'energy_cost' => 0,
                'spell' => 'arrow_volley_spell',
                'effect' => '{"action":"use_spell", "slug":"arrow-volley"}',
                'nb_usages' => 1,
            ],
            // L'ameublement d'une demeure (HOU-05) se payait **uniquement** en
            // Gils : un cosmetique que rien de joueur ne produisait. Le necessaire
            // le rend fabricable — le charpentier meuble les maisons des autres,
            // et le gold sink garde sa voie marchande pour qui n'a pas d'artisan
            // sous la main.
            'crafted_furnishing_kit' => [
                'name' => 'Nécessaire d\'ameublement',
                'name_translations' => ['en' => 'Furnishing Kit'],
                'description' => 'Un lot de meubles en pièces détachées, avec les ferrures et la notice. Il ne reste qu\'à monter.',
                'type' => 'resource',
                'slug' => 'crafted-furnishing-kit',
                'price' => 290,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Uncommon,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            // ZON-31 — l'exclusivite des Dunes d'Ambre. « L'Ambre » de la region
            // cesse d'etre un nom de lieu pour devenir une matiere : de la
            // resine de l'age precedent, conservee par un temps tari
            // (GAME_ZONES § 2.7). Reactif d'enchantement et de joaillerie.
            //
            // Prix T3, sous le platine (100) : c'est un reactif, pas un metal.
            'amber_fossil' => [
                'name' => 'Ambre fossile',
                'name_translations' => ['en' => 'Fossil Amber'],
                'description' => 'Une larme de résine durcie par un âge entier. Ce qu\'elle a pris au monde, elle le garde.',
                'type' => 'resource',
                'slug' => 'amber-fossil',
                'price' => 60,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient"}',
            ],
            'plant_lavender' => [
                'name' => 'Lavande',
                'name_translations' => ['en' => 'Lavender'],
                'description' => 'Plante aromatique aux propriétés calmantes',
                'type' => 'resource',
                'slug' => 'plant-lavender',
                'price' => 12,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["calming_potion", "sleep_potion"]}',
            ],
            'plant_mint' => [
                'name' => 'Menthe',
                'name_translations' => ['en' => 'Mint'],
                'description' => 'Plante aromatique rafraîchissante',
                'type' => 'resource',
                'slug' => 'plant-mint',
                'price' => 10,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["energy_potion", "healing_potion"]}',
            ],
            'plant_sage' => [
                'name' => 'Sauge',
                'name_translations' => ['en' => 'Sage'],
                'description' => 'Plante médicinale aux propriétés purifiantes',
                'type' => 'resource',
                'slug' => 'plant-sage',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["purification_potion", "antidote"]}',
            ],
            'plant_thyme' => [
                'name' => 'Thym',
                'name_translations' => ['en' => 'Thyme'],
                'description' => 'Plante aromatique aux propriétés antiseptiques',
                'type' => 'resource',
                'slug' => 'plant-thyme',
                'price' => 12,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["healing_potion", "protection_potion"]}',
            ],
            'plant_rosemary' => [
                'name' => 'Romarin',
                'name_translations' => ['en' => 'Rosemary'],
                'description' => 'Plante aromatique stimulante pour la mémoire',
                'type' => 'resource',
                'slug' => 'plant-rosemary',
                'price' => 14,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["memory_potion", "focus_potion"]}',
            ],
            'plant_chamomile' => [
                'name' => 'Camomille',
                'name_translations' => ['en' => 'Chamomile'],
                'description' => 'Plante médicinale aux propriétés apaisantes',
                'type' => 'resource',
                'slug' => 'plant-chamomile',
                'price' => 13,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["calming_potion", "sleep_potion"]}',
            ],
            'plant_nettle' => [
                'name' => 'Ortie',
                'name_translations' => ['en' => 'Nettle'],
                'description' => 'Plante médicinale fortifiante',
                'type' => 'resource',
                'slug' => 'plant-nettle',
                'price' => 8,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["strength_potion", "vitality_potion"]}',
            ],
            'plant_dandelion' => [
                'name' => 'Pissenlit',
                'name_translations' => ['en' => 'Dandelion'],
                'description' => 'Plante médicinale aux propriétés détoxifiantes',
                'type' => 'resource',
                'slug' => 'plant-dandelion',
                'price' => 7,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["detox_potion", "purification_potion"]}',
            ],
            'plant_valerian' => [
                'name' => 'Valériane',
                'name_translations' => ['en' => 'Valerian'],
                'description' => 'Plante médicinale aux propriétés sédatives',
                'type' => 'resource',
                'slug' => 'plant-valerian',
                'price' => 18,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["sleep_potion", "tranquility_potion"]}',
            ],
            'plant_mandrake' => [
                'name' => 'Mandragore',
                'name_translations' => ['en' => 'Mandrake'],
                'description' => 'Plante mystique aux puissantes propriétés magiques',
                'type' => 'resource',
                'slug' => 'plant-mandrake',
                'price' => 50,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["invisibility_potion", "transformation_potion"]}',
                'rarity' => ItemRarity::Rare,
            ],
            'plant_nightshade' => [
                'name' => 'Belladone',
                'name_translations' => ['en' => 'Nightshade'],
                'description' => 'Plante toxique utilisée avec précaution',
                'type' => 'resource',
                'slug' => 'plant-nightshade',
                'price' => 35,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["poison", "paralysis_potion"]}',
            ],
            // ZON-35 — cinq plantes purgees ici : `plant-dreamlily`,
            // `plant-sunblossom`, `plant-thunderroot`, `plant-whisperweed` et
            // `plant-wolfsbane`. Elles n'avaient **ni filon ni recette** : ni
            // source, ni debouche, ni raison. Les garder « au cas ou » gonflait
            // le compte de l'herboriste sans rien offrir, et un catalogue qui
            // ment sur sa taille finit par etre calibre sur ce mensonge (loi 9,
            // GAME_ZONES § 3 ter).
            'plant_aloe_vera' => [
                'name' => 'Aloe Vera',
                'name_translations' => ['en' => 'Aloe Vera'],
                'description' => 'Plante médicinale aux propriétés cicatrisantes',
                'type' => 'resource',
                'slug' => 'plant-aloe-vera',
                'price' => 20,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["healing_potion", "burn_remedy"]}',
            ],
            'plant_ginseng' => [
                'name' => 'Ginseng',
                'name_translations' => ['en' => 'Ginseng'],
                'description' => 'Plante médicinale énergisante',
                'type' => 'resource',
                'slug' => 'plant-ginseng',
                'price' => 25,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["energy_potion", "vitality_potion"]}',
            ],
            'plant_echinacea' => [
                'name' => 'Échinacée',
                'name_translations' => ['en' => 'Echinacea'],
                'description' => 'Plante médicinale renforçant les défenses naturelles',
                'type' => 'resource',
                'slug' => 'plant-echinacea',
                'price' => 22,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["immunity_potion", "resistance_potion"]}',
            ],
            // Plantes magiques et exotiques
            'plant_moonflower' => [
                'name' => 'Fleur de Lune',
                'name_translations' => ['en' => 'Moonflower'],
                'description' => 'Plante rare qui ne fleurit que sous la pleine lune',
                'type' => 'resource',
                'slug' => 'plant-moonflower',
                'price' => 75,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["night_vision_potion", "lunar_blessing_potion"]}',
                'rarity' => ItemRarity::Uncommon,
            ],
            'plant_dragonleaf' => [
                'name' => 'Feuille de Dragon',
                'name_translations' => ['en' => 'Dragonleaf'],
                'description' => 'Plante rare aux propriétés ignifuges',
                'type' => 'resource',
                'slug' => 'plant-dragonleaf',
                'price' => 85,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["fire_breath_potion", "dragon_scale_potion"]}',
                'rarity' => ItemRarity::Rare,
            ],
            'plant_frostcap' => [
                'name' => 'Chapeau de Givre',
                'name_translations' => ['en' => 'Frostcap'],
                'description' => 'Champignon qui pousse dans les régions glaciales',
                'type' => 'resource',
                'slug' => 'plant-frostcap',
                'price' => 65,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["frost_resistance_potion", "ice_breath_potion"]}',
            ],
            'plant_ghostshroom' => [
                'name' => 'Champignon Fantôme',
                'name_translations' => ['en' => 'Ghostshroom'],
                'description' => 'Champignon translucide qui brille dans l\'obscurité',
                'type' => 'resource',
                'slug' => 'plant-ghostshroom',
                'price' => 60,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["invisibility_potion", "spirit_vision_potion"]}',
            ],
            'plant_voidfruit' => [
                'name' => 'Fruit du Néant',
                'name_translations' => ['en' => 'Voidfruit'],
                'description' => 'Fruit étrange qui semble absorber la lumière',
                'type' => 'resource',
                'slug' => 'plant-voidfruit',
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["void_protection_potion", "shadow_form_potion"]}',
                'rarity' => ItemRarity::Epic,
            ],
            'plant_phoenixflower' => [
                'name' => 'Fleur de Phénix',
                'name_translations' => ['en' => 'Phoenix Flower'],
                'description' => 'Fleur rare qui renaît de ses cendres',
                'type' => 'resource',
                'slug' => 'plant-phoenixflower',
                'price' => 120,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["resurrection_potion", "eternal_flame_potion"]}',
                'rarity' => ItemRarity::Legendary,
            ],
            // Plantes de marais
            'plant_poisonous_mushroom' => [
                'name' => 'Champignon Vénéneux',
                'name_translations' => ['en' => 'Poisonous Mushroom'],
                'description' => 'Champignon toxique des marais, utilisable en alchimie avancée',
                'type' => 'resource',
                'slug' => 'poisonous-mushroom',
                'price' => 18,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["poison_antidote", "toxin_concentrate"]}',
            ],
            'plant_swamp_root' => [
                'name' => 'Racine de Marais',
                'name_translations' => ['en' => 'Swamp Root'],
                'description' => 'Racine noueuse gorgée d\'eau stagnante, prisée des herboristes',
                'type' => 'resource',
                'slug' => 'swamp-root',
                'price' => 22,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"crafting_ingredient", "potions":["swamp_cure", "fog_resistance_potion"]}',
            ],
            'food_cheese' => [
                'name' => 'Fromage',
                'name_translations' => ['en' => 'Cheese'],
                'description' => 'Un morceau de fromage savoureux',
                'type' => 'stuff',
                'slug' => 'food-cheese',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"restore_energy", "amount":15}',
                'nb_usages' => 1,
            ],
            'food_apple' => [
                'name' => 'Pomme',
                'name_translations' => ['en' => 'Apple'],
                'description' => 'Une pomme juteuse et sucrée',
                'type' => 'stuff',
                'slug' => 'food-apple',
                'price' => 5,
                'space' => 1,
                'energy_cost' => 0,
                'effect' => '{"action":"restore_energy", "amount":5}',
                'nb_usages' => 1,
            ],
            'quest_item_ancient_key' => [
                'name' => 'Clé ancienne',
                'name_translations' => ['en' => 'Ancient Key'],
                'description' => 'Une clé mystérieuse qui semble très ancienne',
                'type' => 'stuff',
                'slug' => 'quest-ancient-key',
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                // OBJ-01 : un objet de quete est un `stuff` lie — le type
                // `quest` n'existe plus, la liaison porte la distinction.
                'boundToPlayer' => true,
            ],
            'quest_item_magic_gem' => [
                'name' => 'Gemme magique',
                'name_translations' => ['en' => 'Magic Gem'],
                'description' => 'Une gemme qui brille d\'une lueur étrange',
                'type' => 'stuff',
                'slug' => 'quest-magic-gem',
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'boundToPlayer' => true,
            ],

            // Fragments Acte 2
            'quest_item_fragment_foret' => [
                'name' => 'Fragment Sylvestre',
                'name_translations' => ['en' => 'Sylvan Fragment'],
                'description' => 'Un éclat de cristal vert pulsant d\'énergie ancienne. Il vibre au rythme des murmures de la forêt. L\'un des quatre fragments nécessaires pour percer le mystère de l\'Améthyste.',
                'type' => 'stuff',
                'slug' => 'quest-fragment-foret',
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Epic,
                'boundToPlayer' => true,
            ],
            'quest_item_fragment_mines' => [
                'name' => 'Fragment de la Forge',
                'name_translations' => ['en' => 'Forge Fragment'],
                'description' => 'Un éclat de cristal orangé irradiant une chaleur ancienne. Il pulse au rythme des marteaux fantômes de la forge oubliée. L\'un des quatre fragments nécessaires pour percer le mystère de l\'Améthyste.',
                'type' => 'stuff',
                'slug' => 'quest-fragment-mines',
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Epic,
                'boundToPlayer' => true,
            ],
            'quest_item_fragment_marais' => [
                'name' => 'Fragment des Brumes',
                'name_translations' => ['en' => 'Mist Fragment'],
                'description' => 'Un éclat de cristal bleu-gris suintant une vapeur glaciale. Il pulse au rythme des courants invisibles du marais. L\'un des quatre fragments nécessaires pour percer le mystère de l\'Améthyste.',
                'type' => 'stuff',
                'slug' => 'quest-fragment-marais',
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Epic,
                'boundToPlayer' => true,
            ],

            'quest_item_fragment_montagne' => [
                'name' => 'Fragment du Sommet',
                'name_translations' => ['en' => 'Summit Fragment'],
                'description' => 'Un éclat de cristal blanc strié de veines argentées, glacé au toucher. Il vibre au rythme des vents qui balaient les cimes. L\'un des quatre fragments nécessaires pour percer le mystère de l\'Améthyste.',
                'type' => 'stuff',
                'slug' => 'quest-fragment-montagne',
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Epic,
                'boundToPlayer' => true,
            ],

            // === Recompenses Acte 3 — La Convergence (tache 94) ===
            'convergence_blade' => [
                'name' => 'Lame de la Convergence',
                'name_translations' => ['en' => 'Convergence Blade'],
                'description' => 'Une epee forgee dans le cristal d\'amethyste purifie. Les quatre fragments fusionnes resonnent dans la lame, lui conferant une puissance qui transcende les elements.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 'convergence-blade',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'price' => 0,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Amethyst,
                'materiaSlots' => 3,
                'boundToPlayer' => true,
                'level' => 25,
            ],
            'convergence_amulet' => [
                'name' => 'Amulette de la Convergence',
                'name_translations' => ['en' => 'Convergence Amulet'],
                'description' => 'Un pendentif taille dans le coeur du cristal d\'amethyste. Il pulse d\'une lumiere douce qui renforce les capacites magiques de son porteur.',
                'type' => 'gear',
                'slug' => 'convergence-amulet',
                'gear_location' => Item::GEAR_LOCATION_NECK,
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Amethyst,
                'materiaSlots' => 3,
                'boundToPlayer' => true,
                'level' => 25,
            ],

            // Nouvelles Matérias - Feu
            'wooden_sword' => [
                'name' => 'Épée en bois',
                'name_translations' => ['en' => 'Wooden Sword'],
                'description' => 'Une épée taillée dans du bois dur. Pas très tranchante, mais ça fait le travail.',
                'type' => 'gear',
                'spell' => 'none_attack_1',
                'slug' => 'wooden-sword',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'price' => 15,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
            ],
            'starter_helmet' => [
                'name' => 'Casque rouillé',
                'name_translations' => ['en' => 'Rusty Helmet'],
                'description' => 'Un vieux casque en fer rongé par la rouille. Mieux que rien.',
                'type' => 'gear',
                'slug' => 'starter-helmet',
                'gear_location' => Item::GEAR_LOCATION_HEAD,
                'price' => 12,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
            ],
            'starter_chest' => [
                'name' => 'Tunique rembourrée',
                'name_translations' => ['en' => 'Padded Tunic'],
                'description' => 'Une tunique en lin rembourrée de paille. Protection minimale mais confortable.',
                'type' => 'gear',
                'slug' => 'starter-chest',
                'gear_location' => Item::GEAR_LOCATION_CHEST,
                'price' => 20,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
            ],
            'starter_legs' => [
                'name' => 'Jambières en tissu',
                'name_translations' => ['en' => 'Cloth Leggings'],
                'description' => 'Des jambières en tissu épais, rapiécées à plusieurs endroits.',
                'type' => 'gear',
                'slug' => 'starter-legs',
                'gear_location' => Item::GEAR_LOCATION_LEG,
                'price' => 10,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
            ],
            'starter_boots' => [
                'name' => 'Sandales usées',
                'name_translations' => ['en' => 'Worn Sandals'],
                'description' => 'Des sandales de cuir usées par le temps. Au moins, elles tiennent encore.',
                'type' => 'gear',
                'slug' => 'starter-boots',
                'gear_location' => Item::GEAR_LOCATION_FOOT,
                'price' => 8,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
            ],
            'starter_gloves' => [
                'name' => 'Gants de travail',
                'name_translations' => ['en' => 'Work Gloves'],
                'description' => 'Des gants en cuir épais, conçus pour le travail manuel mais utiles au combat.',
                'type' => 'gear',
                'slug' => 'starter-gloves',
                'gear_location' => Item::GEAR_LOCATION_HAND,
                'price' => 8,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
            ],
            'starter_shield' => [
                'name' => 'Bouclier en bois',
                'name_translations' => ['en' => 'Wooden Shield'],
                'description' => 'Un bouclier rudimentaire en planches de bois clouées ensemble.',
                'type' => 'gear',
                'slug' => 'starter-shield',
                'gear_location' => Item::GEAR_LOCATION_SIDE_WEAPON,
                'price' => 18,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
            ],

            // === Armes variées par tier (Hache, Bâton, Arc, Dague, Lance) ===
            // Complètent les épées existantes avec 5 types d'armes aux profils de stats distincts.
            // Hache : puissance brute — Bâton : boost magique — Arc : précision
            // Dague : critique élevé — Lance : équilibrée, portée

            // --- Tier 1 — Armes de base (common, niveau 1) ---

            't1_axe' => [
                'name' => 'Hachette rouillée',
                'name_translations' => ['en' => 'Rusty Hatchet'],
                'description' => 'Une hachette lourde rongée par la rouille. Frappe fort malgré son état.',
                'type' => 'gear',
                'spell' => 'none_attack_1',
                'slug' => 't1-axe',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'berserker',
                'price' => 20,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
                'effect' => '{"action":"damage","amount":2}',
                'requirements' => ['port_axe'],
            ],
            't1_staff' => [
                'name' => 'Bâton de novice',
                'name_translations' => ['en' => 'Novice Staff'],
                'description' => 'Un bâton en bois noueux qui canalise faiblement la magie ambiante.',
                'type' => 'gear',
                'spell' => 'none_attack_1',
                'slug' => 't1-staff',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'paladin',
                'price' => 18,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
                'effect' => '{"action":"magic_boost","amount":5}',
                'requirements' => ['port_staff'],
            ],
            't1_bow' => [
                'name' => 'Arc court',
                'name_translations' => ['en' => 'Short Bow'],
                'description' => 'Un arc court en bois souple. Précis à courte portée.',
                'type' => 'gear',
                'spell' => 'none_attack_1',
                'slug' => 't1-bow',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'archer',
                'price' => 22,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
                'effect' => '{"action":"precision_boost","amount":5}',
                'requirements' => ['port_bow'],
            ],
            't1_crossbow' => [
                'name' => 'Arbalète d\'atelier',
                'name_translations' => ['en' => 'Workshop Crossbow'],
                'description' => 'Un fût de bois, un étrier, une corde tressée. Elle frappe fort et se recharge lentement.',
                'type' => 'gear',
                'spell' => 'none_attack_1',
                'slug' => 't1-crossbow',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'artificer',
                'price' => 26,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
                'effect' => '{"action":"damage","amount":2}',
                'requirements' => ['port_crossbow'],
            ],
            't1_dagger' => [
                'name' => 'Dague ébréchée',
                'name_translations' => ['en' => 'Chipped Dagger'],
                'description' => 'Une dague légère au fil irrégulier. Rapide mais fragile.',
                'type' => 'gear',
                'spell' => 'none_attack_1',
                'slug' => 't1-dagger',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'assassin',
                'price' => 15,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 50,
                'materiaSlots' => 1,
                'effect' => '{"action":"critical_boost","amount":5}',
                'requirements' => ['port_dagger'],
            ],
            't1_lance' => [
                'name' => 'Pique en bois',
                'name_translations' => ['en' => 'Wooden Pike'],
                'description' => 'Une longue pique au fer de lance grossier. Tient l\'ennemi à distance.',
                'type' => 'gear',
                'spell' => 'none_attack_1',
                'slug' => 't1-lance',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'knight',
                'price' => 20,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 60,
                'materiaSlots' => 1,
                'effect' => '{"action":"damage","amount":1,"range":2}',
                'requirements' => ['port_lance'],
            ],

            // --- Tier 2 — Armes de qualité (uncommon, niveau 5) ---

            't2_axe' => [
                'name' => 'Hache de guerre',
                'name_translations' => ['en' => 'War Axe'],
                'description' => 'Une hache de guerre en acier trempé. Sa lourde tête inflige des coups dévastateurs.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 't2-axe',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'berserker',
                'price' => 200,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
                'effect' => '{"action":"damage","amount":8}',
                'requirements' => ['berserk_weapon_t2'],
            ],
            't2_staff' => [
                'name' => 'Bâton de cristal',
                'name_translations' => ['en' => 'Crystal Staff'],
                'description' => 'Un bâton orné d\'un cristal luminescent qui amplifie les flux magiques.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 't2-staff',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'paladin',
                'price' => 180,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
                'materiaSlotType' => MateriaSlotType::Spell,
                'effect' => '{"action":"magic_boost","amount":15}',
                'requirements' => ['paladin_weapon_t2'],
            ],
            't2_bow' => [
                'name' => 'Arc long composite',
                'name_translations' => ['en' => 'Composite Longbow'],
                'description' => 'Un arc composite en bois et corne, d\'une précision remarquable à longue portée.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 't2-bow',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'archer',
                'price' => 190,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
                'effect' => '{"action":"precision_boost","amount":12}',
                'requirements' => ['archer_weapon_t2'],
            ],
            't2_crossbow' => [
                'name' => 'Arbalète de rempart',
                'name_translations' => ['en' => 'Rampart Crossbow'],
                'description' => 'Lourde, lente à réarmer, et capable de traverser une porte. Les guetteurs des foyers ne jurent que par elle.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 't2-crossbow',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'artificer',
                'price' => 210,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 1,
                'effect' => '{"action":"damage","amount":7}',
                'requirements' => ['artificer_weapon_t2'],
            ],
            't2_dagger' => [
                'name' => 'Dague de mithril',
                'name_translations' => ['en' => 'Mithril Dagger'],
                'description' => 'Une dague fine en mithril, presque invisible dans l\'ombre. Frappe les points vitaux.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 't2-dagger',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'assassin',
                'price' => 170,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 120,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
                'effect' => '{"action":"critical_boost","amount":12}',
                'requirements' => ['assassin_weapon_t2'],
            ],
            't2_lance' => [
                'name' => 'Lance d\'acier',
                'name_translations' => ['en' => 'Steel Lance'],
                'description' => 'Une lance en acier poli, parfaitement équilibrée entre portée et puissance.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 't2-lance',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'knight',
                'price' => 195,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
                'effect' => '{"action":"damage","amount":5,"range":2}',
                'requirements' => ['knight_weapon_t2'],
            ],

            // --- Tier 3 — Armes avancées (epic, niveau 15) ---

            't3_axe' => [
                'name' => 'Hache du berserker',
                'name_translations' => ['en' => 'Berserker Axe'],
                'description' => 'Une hache colossale imprégnée de rage ancestrale. Chaque coup fait trembler le sol.',
                'type' => 'gear',
                'spell' => 'none_attack_3',
                'slug' => 't3-axe',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'berserker',
                'price' => 480,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 200,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
                'effect' => '{"action":"damage","amount":18}',
                'requirements' => ['berserk_weapon_t3'],
            ],
            't3_staff' => [
                'name' => 'Bâton de l\'archimage',
                'name_translations' => ['en' => 'Archmage Staff'],
                'description' => 'Un bâton ancien gravé de runes arcanes. Le cristal à son sommet pulse d\'énergie pure.',
                'type' => 'gear',
                'spell' => 'none_attack_3',
                'slug' => 't3-staff',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'paladin',
                'price' => 450,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 200,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
                'materiaSlotType' => MateriaSlotType::Spell,
                'effect' => '{"action":"magic_boost","amount":30}',
                'requirements' => ['paladin_weapon_t3'],
            ],

            // OBJ-03 : la grille d'equipement neutre — les 56 pieces
            // elementaires (4 elements x 7 formes x 2 paliers) fusionnent en
            // une piece par forme et par palier. La piece ne porte plus
            // d'element : le build vit dans les emplacements de materia
            // (GAME_ITEMS §3.2), jamais dans le vestiaire.
            't2_sword' => [
                'name' => 'Épée trempée',
                'name_translations' => ['en' => 'Tempered Sword'],
                'description' => 'Une lame d\'acier trempé, équilibrée pour les combats prolongés.',
                'type' => 'gear',
                'slug' => 't2-sword',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'spell' => 'none_attack_2',
                'domain' => 'soldier',
                'price' => 180,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
            ],
            't2_shield' => [
                'name' => 'Bouclier trempé',
                'name_translations' => ['en' => 'Tempered Shield'],
                'description' => 'Un bouclier d\'acier trempé qui a déjà arrêté bien des coups.',
                'type' => 'gear',
                'slug' => 't2-shield',
                'gear_location' => Item::GEAR_LOCATION_SIDE_WEAPON,
                'price' => 150,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
            ],
            't2_helmet' => [
                'name' => 'Heaume trempé',
                'name_translations' => ['en' => 'Tempered Helm'],
                'description' => 'Un heaume d\'acier trempé, simple et sûr.',
                'type' => 'gear',
                'slug' => 't2-helmet',
                'gear_location' => Item::GEAR_LOCATION_HEAD,
                'price' => 120,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
            ],
            't2_chest' => [
                'name' => 'Cuirasse trempée',
                'name_translations' => ['en' => 'Tempered Cuirass'],
                'description' => 'Une cuirasse d\'acier trempé, la pièce maîtresse du vétéran.',
                'type' => 'gear',
                'slug' => 't2-chest',
                'gear_location' => Item::GEAR_LOCATION_CHEST,
                'price' => 160,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
            ],
            't2_legs' => [
                'name' => 'Jambières trempées',
                'name_translations' => ['en' => 'Tempered Greaves'],
                'description' => 'Des jambières d\'acier trempé, souples aux genoux.',
                'type' => 'gear',
                'slug' => 't2-legs',
                'gear_location' => Item::GEAR_LOCATION_LEG,
                'price' => 100,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
            ],
            't2_boots' => [
                'name' => 'Bottes trempées',
                'name_translations' => ['en' => 'Tempered Boots'],
                'description' => 'Des bottes renforcées d\'acier trempé.',
                'type' => 'gear',
                'slug' => 't2-boots',
                'gear_location' => Item::GEAR_LOCATION_FOOT,
                'price' => 90,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
            ],
            't2_gloves' => [
                'name' => 'Gants trempés',
                'name_translations' => ['en' => 'Tempered Gloves'],
                'description' => 'Des gants renforcés d\'acier trempé.',
                'type' => 'gear',
                'slug' => 't2-gloves',
                'gear_location' => Item::GEAR_LOCATION_HAND,
                'price' => 100,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 5,
                'materiaSlots' => 2,
            ],
            't3_sword' => [
                'name' => 'Épée d\'élite',
                'name_translations' => ['en' => 'Elite Sword'],
                'description' => 'Une lame forgée pour les champions des zones dangereuses.',
                'type' => 'gear',
                'slug' => 't3-sword',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'spell' => 'none_attack_3',
                'domain' => 'soldier',
                'price' => 450,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
            ],
            't3_shield' => [
                'name' => 'Bouclier d\'élite',
                'name_translations' => ['en' => 'Elite Shield'],
                'description' => 'Un bouclier de facture supérieure, digne des grandes expéditions.',
                'type' => 'gear',
                'slug' => 't3-shield',
                'gear_location' => Item::GEAR_LOCATION_SIDE_WEAPON,
                'price' => 380,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
            ],
            't3_helmet' => [
                'name' => 'Heaume d\'élite',
                'name_translations' => ['en' => 'Elite Helm'],
                'description' => 'Un heaume de facture supérieure, ajusté au combattant.',
                'type' => 'gear',
                'slug' => 't3-helmet',
                'gear_location' => Item::GEAR_LOCATION_HEAD,
                'price' => 320,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
            ],
            't3_chest' => [
                'name' => 'Cuirasse d\'élite',
                'name_translations' => ['en' => 'Elite Cuirass'],
                'description' => 'Une cuirasse de facture supérieure, la marque des vétérans du T3.',
                'type' => 'gear',
                'slug' => 't3-chest',
                'gear_location' => Item::GEAR_LOCATION_CHEST,
                'price' => 420,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
            ],
            't3_legs' => [
                'name' => 'Jambières d\'élite',
                'name_translations' => ['en' => 'Elite Greaves'],
                'description' => 'Des jambières de facture supérieure, articulées avec soin.',
                'type' => 'gear',
                'slug' => 't3-legs',
                'gear_location' => Item::GEAR_LOCATION_LEG,
                'price' => 300,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
            ],
            't3_boots' => [
                'name' => 'Bottes d\'élite',
                'name_translations' => ['en' => 'Elite Boots'],
                'description' => 'Des bottes de facture supérieure, faites pour durer.',
                'type' => 'gear',
                'slug' => 't3-boots',
                'gear_location' => Item::GEAR_LOCATION_FOOT,
                'price' => 280,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
            ],
            't3_gloves' => [
                'name' => 'Gants d\'élite',
                'name_translations' => ['en' => 'Elite Gloves'],
                'description' => 'Des gants de facture supérieure, précis au doigt près.',
                'type' => 'gear',
                'slug' => 't3-gloves',
                'gear_location' => Item::GEAR_LOCATION_HAND,
                'price' => 300,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
            ],
            't3_bow' => [
                'name' => 'Arc du vent hurlant',
                'name_translations' => ['en' => 'Howling Wind Bow'],
                'description' => 'Un arc elfique dont les flèches sifflent comme le vent. Touche sa cible à coup sûr.',
                'type' => 'gear',
                'spell' => 'none_attack_3',
                'slug' => 't3-bow',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'archer',
                'price' => 460,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 200,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
                'effect' => '{"action":"precision_boost","amount":25}',
                'requirements' => ['archer_weapon_t3'],
            ],
            't3_crossbow' => [
                'name' => 'Arbalète à contrepoids',
                'name_translations' => ['en' => 'Counterweight Crossbow'],
                'description' => 'Une mécanique d\'atelier montée sur un fût d\'acier. Elle met un tour à s\'armer, et ce tour se voit sur la cible.',
                'type' => 'gear',
                'spell' => 'none_attack_3',
                'slug' => 't3-crossbow',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'artificer',
                'price' => 500,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 200,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 2,
                'effect' => '{"action":"damage","amount":16}',
                'requirements' => ['artificer_weapon_t3'],
            ],
            't3_dagger' => [
                'name' => 'Lame de l\'ombre',
                'name_translations' => ['en' => 'Shadow Blade'],
                'description' => 'Une dague forgée dans l\'obscurité absolue. Invisible et mortelle, elle trouve toujours la faille.',
                'type' => 'gear',
                'spell' => 'none_attack_3',
                'slug' => 't3-dagger',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'assassin',
                'price' => 440,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 180,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
                'effect' => '{"action":"critical_boost","amount":25}',
                'requirements' => ['assassin_weapon_t3'],
            ],
            't3_lance' => [
                'name' => 'Lance du chevalier céleste',
                'name_translations' => ['en' => 'Celestial Knight Lance'],
                'description' => 'Une lance légendaire au fer étincelant. Ceux qui la brandissent sont craints sur tout le champ de bataille.',
                'type' => 'gear',
                'spell' => 'none_attack_3',
                'slug' => 't3-lance',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'knight',
                'price' => 470,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 200,
                'rarity' => ItemRarity::Epic,
                'level' => 15,
                'materiaSlots' => 3,
                'effect' => '{"action":"damage","amount":12,"range":2}',
                'requirements' => ['knight_weapon_t3'],
            ],

            // --- Récompenses uniques de boss ---

            'dragon_fang_blade' => [
                'name' => 'Lame de croc draconique',
                'name_translations' => ['en' => 'Dragon Fang Blade'],
                'description' => 'Une épée forgée à partir d\'un croc du Dragon ancestral. La lame irradie une chaleur intense.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 'dragon-fang-blade',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'price' => 500,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'materiaSlots' => 2,
            ],
            'dragon_scale_armor' => [
                'name' => 'Plastron en écailles de dragon',
                'name_translations' => ['en' => 'Dragon Scale Armor'],
                'description' => 'Un plastron taillé dans les écailles du Dragon ancestral. Résiste à la chaleur la plus extrême.',
                'type' => 'gear',
                'slug' => 'dragon-scale-armor',
                'gear_location' => Item::GEAR_LOCATION_CHEST,
                'price' => 600,
                'space' => 4,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'materiaSlots' => 2,
            ],
            'griffin_talon_ring' => [
                'name' => 'Anneau de serre de griffon',
                'name_translations' => ['en' => 'Griffin Talon Ring'],
                'description' => 'Un anneau forgé autour d\'une serre de griffon. Confère une vitesse surnaturelle à son porteur.',
                'type' => 'gear',
                'slug' => 'griffin-talon-ring',
                'gear_location' => Item::GEAR_LOCATION_RING_1,
                'price' => 400,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'materiaSlots' => 2,
            ],
            'minotaur_horn_helm' => [
                'name' => 'Heaume cornu du minotaure',
                'name_translations' => ['en' => 'Minotaur Horn Helm'],
                'description' => 'Un heaume massif orné des cornes brisées d\'un minotaure alpha. Inspire la terreur.',
                'type' => 'gear',
                'slug' => 'minotaur-horn-helm',
                'gear_location' => Item::GEAR_LOCATION_HEAD,
                'price' => 450,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'materiaSlots' => 2,
            ],
            'golem_heart_shield' => [
                'name' => 'Bouclier cœur de golem',
                'name_translations' => ['en' => 'Golem Heart Shield'],
                'description' => 'Un bouclier taillé dans le noyau cristallin d\'un golem de pierre. Presque indestructible.',
                'type' => 'gear',
                'slug' => 'golem-heart-shield',
                'gear_location' => Item::GEAR_LOCATION_SIDE_WEAPON,
                'price' => 500,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'materiaSlots' => 2,
            ],
            'troll_king_belt' => [
                'name' => 'Ceinture du roi troll',
                'name_translations' => ['en' => 'Troll King Belt'],
                'description' => 'Une ceinture massive en cuir de troll, renforcée de plaques de métal. Accorde une régénération accrue.',
                'type' => 'gear',
                'slug' => 'troll-king-belt',
                'gear_location' => Item::GEAR_LOCATION_BELT,
                'price' => 350,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'materiaSlots' => 2,
            ],

            // --- Récompenses uniques de boss de zone (tâche 66) ---

            'guardian_bark_armor' => [
                'name' => 'Cuirasse d\'écorce ancestrale',
                'name_translations' => ['en' => 'Ancestral Bark Cuirass'],
                'description' => 'Une cuirasse vivante taillée dans l\'écorce du Gardien de la Forêt. Des runes végétales pulsent à sa surface.',
                'type' => 'gear',
                'slug' => 'guardian-bark-armor',
                'gear_location' => Item::GEAR_LOCATION_CHEST,
                'price' => 800,
                'space' => 4,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'protection' => 18,
                'materiaSlots' => 2,
            ],
            'guardian_thorn_staff' => [
                'name' => 'Bâton d\'épines primordiales',
                'name_translations' => ['en' => 'Primordial Thorn Staff'],
                'description' => 'Un bâton noueux arraché au cœur du Gardien. Des épines acérées y poussent encore.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 'guardian-thorn-staff',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'druid',
                'price' => 750,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'materiaSlots' => 2,
                'materiaSlotType' => MateriaSlotType::Spell,
            ],
            'forgelord_obsidian_blade' => [
                'name' => 'Lame d\'obsidienne du Seigneur',
                'name_translations' => ['en' => 'Forge Lord\'s Obsidian Blade'],
                'description' => 'Une épée forgée dans les ténèbres par le Seigneur de la Forge. L\'obsidienne vibre d\'une énergie sombre.',
                'type' => 'gear',
                'spell' => 'none_attack_2',
                'slug' => 'forgelord-obsidian-blade',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'price' => 900,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'materiaSlots' => 2,
            ],
            'forgelord_dark_plate' => [
                'name' => 'Plastron de la forge obscure',
                'name_translations' => ['en' => 'Dark Forge Plate'],
                'description' => 'Un plastron massif en métal sombre, imprégné de l\'essence des ténèbres de la forge.',
                'type' => 'gear',
                'slug' => 'forgelord-dark-plate',
                'gear_location' => Item::GEAR_LOCATION_CHEST,
                'price' => 950,
                'space' => 4,
                'energy_cost' => 0,
                'nb_usages' => 300,
                'rarity' => ItemRarity::Legendary,
                'protection' => 22,
                'materiaSlots' => 2,
            ],

            // --- Items craftables (tâche 26) ---
            // ── ECO-19 : armes et consommables cites par les arbres de talent ──
            // Les skills les debloquaient deja ; ni la recette ni l'objet
            // n'existaient, et le skill s'apprenait donc sans rien produire.
            'steel_dagger' => [
                'name' => 'Dague en acier',
                'name_translations' => ['en' => 'Steel Dagger'],
                'description' => 'Une lame d\'acier trempe, plus fine et plus vive que le fer',
                'type' => 'gear',
                'slug' => 'steel-dagger',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'spell' => 'none_attack_1',
                'price' => 180,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 180,
                'materiaSlots' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'steel_sword' => [
                'name' => 'Epee en acier',
                'name_translations' => ['en' => 'Steel Sword'],
                'description' => 'L\'arme de reference d\'un forgeron confirme : equilibree, durable, sans fioriture',
                'type' => 'gear',
                'slug' => 'steel-sword',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'spell' => 'none_attack_1',
                'price' => 320,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 200,
                'materiaSlots' => 1,
                'rarity' => ItemRarity::Uncommon,
            ],
            'steel_axe' => [
                'name' => 'Hache d\'acier',
                'name_translations' => ['en' => 'Steel Axe'],
                'description' => 'Lourde, mal equilibree, devastatrice — elle ne pardonne ni au bois ni a l\'os',
                'type' => 'gear',
                'slug' => 'steel-axe',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'spell' => 'none_attack_1',
                'price' => 420,
                'space' => 3,
                'energy_cost' => 0,
                'nb_usages' => 220,
                'materiaSlots' => 2,
                'rarity' => ItemRarity::Rare,
            ],
            'whetstone' => [
                'name' => 'Pierre a aiguiser',
                'name_translations' => ['en' => 'Whetstone'],
                'description' => 'Un grain fin et un peu d\'huile : de quoi rendre son mordant a une lame fatiguee',
                'type' => 'stuff',
                'slug' => 'whetstone',
                'price' => 85,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 10,
            ],
            'energy_potion_standard' => [
                'name' => 'Potion d\'energie standard',
                'name_translations' => ['en' => 'Standard Energy Potion'],
                'description' => 'Le palier au-dessus de la fiole mineure, pour une journee entiere d\'expedition',
                'type' => 'stuff',
                'slug' => 'energy-potion-standard',
                'price' => 140,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"energy", "amount":40}',
                'rarity' => ItemRarity::Uncommon,
            ],
            'speed_elixir' => [
                'name' => 'Elixir de vitesse',
                'name_translations' => ['en' => 'Speed Elixir'],
                'description' => 'Le temps ne ralentit pas ; c\'est vous qui allez plus vite, et ca se paie apres',
                'type' => 'stuff',
                'slug' => 'speed-elixir',
                'price' => 260,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'effect' => '{"action":"speed_boost", "amount":15}',
                'rarity' => ItemRarity::Rare,
            ],
            'iron_dagger' => [
                'name' => 'Dague en fer',
                'name_translations' => ['en' => 'Iron Dagger'],
                'description' => 'Une dague en fer légère et tranchante, idéale pour les coups rapides',
                'type' => 'gear',
                'slug' => 'iron-dagger',
                'gear_location' => Item::GEAR_LOCATION_MAIN_WEAPON,
                'domain' => 'soldier',
                'spell' => 'none_attack_1',
                'price' => 60,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'materiaSlots' => 1,
            ],
            'iron_shield' => [
                'name' => 'Bouclier en fer',
                'name_translations' => ['en' => 'Iron Shield'],
                'description' => 'Un bouclier solide en fer forgé, offrant une bonne protection',
                'type' => 'gear',
                'slug' => 'iron-shield',
                'gear_location' => Item::GEAR_LOCATION_SIDE_WEAPON,
                'price' => 90,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'materiaSlots' => 1,
            ],
            'iron_helmet' => [
                'name' => 'Casque en fer',
                'name_translations' => ['en' => 'Iron Helmet'],
                'description' => 'Un casque en fer robuste qui protège bien la tête',
                'type' => 'gear',
                'slug' => 'iron-helmet',
                'gear_location' => Item::GEAR_LOCATION_HEAD,
                'price' => 120,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => 150,
                'rarity' => ItemRarity::Uncommon,
                'level' => 8,
                'protection' => 6,
                'materiaSlots' => 2,
            ],
            'copper_ring' => [
                'name' => 'Anneau de cuivre',
                'name_translations' => ['en' => 'Copper Ring'],
                'description' => 'Un anneau simple en cuivre poli',
                'type' => 'gear',
                'slug' => 'copper-ring',
                'gear_location' => Item::GEAR_LOCATION_RING_1,
                'price' => 40,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 100,
                'materiaSlots' => 1,
            ],

            // === Bijoux joaillier (résultats craft) ===
            'iron_ring' => [
                'name' => 'Anneau de fer',
                'name_translations' => ['en' => 'Iron Ring'],
                'description' => 'Un anneau de fer solide, sobre mais efficace',
                'type' => 'gear',
                'slug' => 'iron-ring',
                'gear_location' => Item::GEAR_LOCATION_RING_1,
                'price' => 55,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'protection' => 1,
                'materiaSlots' => 1,
                'level' => 2,
            ],
            'iron_amulet' => [
                'name' => 'Amulette de fer',
                'name_translations' => ['en' => 'Iron Amulet'],
                'description' => 'Une amulette de fer aux motifs simples, protectrice des voyageurs',
                'type' => 'gear',
                'slug' => 'iron-amulet',
                'gear_location' => 'neck',
                'price' => 80,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'protection' => 2,
                'materiaSlots' => 1,
                'level' => 3,
            ],
            'iron_bracelet' => [
                'name' => 'Bracelet de fer',
                'name_translations' => ['en' => 'Iron Bracelet'],
                'description' => 'Un bracelet de fer massif porté au poignet comme signe de force',
                'type' => 'gear',
                'slug' => 'iron-bracelet',
                'gear_location' => Item::GEAR_LOCATION_RING_2,
                'price' => 50,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'protection' => 1,
                'materiaSlots' => 1,
                'level' => 3,
            ],
            'gold_ring' => [
                'name' => 'Anneau d\'or serti',
                'name_translations' => ['en' => 'Jeweled Gold Ring'],
                'description' => 'Un anneau d\'or orné d\'une gemme fine, symbole de prospérité',
                'type' => 'gear',
                'slug' => 'gold-ring',
                'gear_location' => Item::GEAR_LOCATION_RING_1,
                'price' => 240,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Uncommon,
                'protection' => 2,
                'materiaSlots' => 2,
                'level' => 6,
            ],
            'gold_amulet' => [
                'name' => 'Amulette d\'or',
                'name_translations' => ['en' => 'Gold Amulet'],
                'description' => 'Une amulette d\'or finement ciselée, rayonnante de noblesse',
                'type' => 'gear',
                'slug' => 'gold-amulet',
                'gear_location' => 'neck',
                'price' => 270,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Uncommon,
                'protection' => 3,
                'materiaSlots' => 2,
                'level' => 7,
            ],
            'gold_crown' => [
                'name' => 'Couronne d\'or',
                'name_translations' => ['en' => 'Gold Crown'],
                'description' => 'Une couronne d\'or ornée de gemmes fines, digne d\'un roi',
                'type' => 'gear',
                'slug' => 'gold-crown',
                'gear_location' => Item::GEAR_LOCATION_HEAD,
                'price' => 700,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Rare,
                'protection' => 5,
                'materiaSlots' => 2,
                'level' => 10,
            ],
            'mithril_ring_jewel' => [
                'name' => 'Anneau de mithril serti',
                'name_translations' => ['en' => 'Jeweled Mithril Ring'],
                'description' => 'Un anneau de mithril d\'une légèreté surnaturelle, serti d\'une gemme rare',
                'type' => 'gear',
                'slug' => 'mithril-ring-jewel',
                'gear_location' => Item::GEAR_LOCATION_RING_1,
                'price' => 1700,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Rare,
                'protection' => 3,
                'materiaSlots' => 2,
                'level' => 12,
            ],
            'mithril_amulet' => [
                'name' => 'Amulette de mithril',
                'name_translations' => ['en' => 'Mithril Amulet'],
                'description' => 'Une amulette de mithril aux reflets argentés, vibrant d\'énergie',
                'type' => 'gear',
                'slug' => 'mithril-amulet',
                'gear_location' => 'neck',
                'price' => 1700,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Rare,
                'protection' => 4,
                'materiaSlots' => 2,
                'level' => 12,
            ],

            // === Tier 4+ : Chefs-d'oeuvre exclusifs aux maitres artisans (task 122) ===
            // Chacun necessite la specialisation correspondante pour etre fabrique.

            // Maitre Forgeron — lame de maitre

            // Maitre Tanneur — manteau de maitre
            'masterwork_grand_elixir' => [
                'name' => 'Grand elixir du maitre alchimiste',
                'name_translations' => ['en' => 'Master Alchemist\'s Grand Elixir'],
                'description' => 'Distillation parfaite de mandragore, ginseng et essence prismatique. Restaure pleinement le porteur.',
                'type' => 'stuff',
                'slug' => 'masterwork-grand-elixir',
                'spell' => 'elixir_vitality_spell',
                'effect' => '{"action":"use_spell","slug":"elixir-vitality"}',
                'price' => 2200,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => 1,
                'rarity' => ItemRarity::Legendary,
                // ECO-08 : haut de gamme endgame — lie, donc hors de l'hotel des ventes.
                'bindType' => 'bind_on_pickup',
            ],
            'masterwork_drakehide_cloak' => [
                'name' => 'Manteau du maitre tanneur',
                'name_translations' => ['en' => 'Master Leatherworker\'s Cloak'],
                'description' => 'Cape ouvragee dans la peau d\'un drake ancestral, souple comme la soie et solide comme l\'acier.',
                'type' => 'gear',
                'slug' => 'masterwork-drakehide-cloak',
                'gear_location' => Item::GEAR_LOCATION_CHEST,
                'price' => 3200,
                'space' => 2,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Legendary,
                'protection' => 12,
                'materiaSlots' => 3,
                'level' => 20,
                // ECO-08 : haut de gamme endgame — lie, donc hors de l'hotel des ventes.
                'bindType' => 'bind_on_pickup',
            ],

            // Maitre Alchimiste — elixir supreme

            // Maitre Joaillier — anneau de maitre

            // === Équipement Tier 2 — Variantes élémentaires (Feu, Eau, Terre, Air) ===
            // Chaque pièce octroie +10% de dégâts de l'élément correspondant.
            // 7 pièces × 4 éléments = 28 items.

            // --- Épées (main_weapon) ---

            // --- Boucliers (side_weapon) ---

            // --- Casques (head) ---

            // --- Plastrons (chest) ---

            // --- Jambières (leg) ---

            // --- Bottes (foot) ---

            // --- Gantelets (hand) ---

            // === Équipement Tier 3 — Variantes élémentaires (Métal, Bête, Lumière, Ombre) ===
            // Chaque pièce octroie +15% de dégâts de l'élément correspondant + 1-2 slots materia.
            // 7 pièces × 4 éléments = 28 items. Rareté Epic, niveau 15.

            // --- Épées (main_weapon) ---

            // --- Boucliers (side_weapon) ---

            // --- Casques (head) ---

            // --- Plastrons (chest) ---

            // --- Jambières (leg) ---

            // --- Bottes (foot) ---

            // --- Gantelets (hand) ---

            // Cosmétiques d'événement — Festival de la Lune
            'cosmetic_lunar_crown' => [
                'name' => 'Couronne lunaire',
                'name_translations' => ['en' => 'Lunar Crown'],
                'description' => 'Une couronne argentee qui brille sous la lumiere de la lune. Recompense exclusive du Festival de la Lune.',
                'type' => 'gear',
                'slug' => 'cosmetic-lunar-crown',
                'gear_location' => Item::GEAR_LOCATION_HEAD,
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Epic,
                'level' => 1,
                'boundToPlayer' => true,
                'materiaSlots' => 1,
            ],

            // Cosmétiques d'événement — La Nuit des Ombres
            'cosmetic_shadow_cloak' => [
                'name' => 'Cape des ombres',
                'name_translations' => ['en' => 'Shadow Cloak'],
                'description' => 'Une cape tissee dans l\'obscurite elle-meme. Recompense exclusive de la Nuit des Ombres.',
                'type' => 'gear',
                'slug' => 'cosmetic-shadow-cloak',
                'gear_location' => Item::GEAR_LOCATION_SHOULDER,
                'price' => 0,
                'space' => 1,
                'energy_cost' => 0,
                'nb_usages' => -1,
                'rarity' => ItemRarity::Epic,
                'level' => 1,
                'boundToPlayer' => true,
                'materiaSlots' => 1,
            ],
        ];
    }

    /**
     * Infère la rareté d'un item à partir de son slug/type quand non spécifiée explicitement.
     */
    private function inferRarity(array $data): ItemRarity
    {
        $slug = $data['slug'] ?? '';
        $type = $data['type'] ?? '';

        // Matérias : rareté basée sur le tier (m1=uncommon, m2=rare, m3=epic, m4+=legendary)
        if ($type === 'materia') {
            if (str_starts_with($slug, 'm5-') || str_starts_with($slug, 'm4-')) {
                return ItemRarity::Legendary;
            }
            if (str_starts_with($slug, 'm3-')) {
                return ItemRarity::Epic;
            }
            if (str_starts_with($slug, 'm2-')) {
                return ItemRarity::Rare;
            }

            return ItemRarity::Uncommon;
        }

        // Objets de quête sont toujours rares
        if ($type === 'quest') {
            return ItemRarity::Rare;
        }

        return ItemRarity::Common;
    }

    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
            SpellFixtures::class,
            SkillFixtures::class,
        ];
    }
}
