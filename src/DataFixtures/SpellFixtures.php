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
            'inferno' => [
                'slug' => 'inferno',
                'damage' => 7,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Inferno',
                'description' => 'Un brasier dévastateur qui consume tout sur son passage',
                'hit' => 85
            ],
            'fire_wall' => [
                'slug' => 'fire-wall',
                'damage' => 4,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Mur de feu',
                'description' => 'Crée un mur de flammes infranchissable',
                'hit' => 95
            ],
            'fire_nova' => [
                'slug' => 'fire-nova',
                'damage' => 6,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Nova de feu',
                'description' => 'Une explosion de feu qui se propage dans toutes les directions',
                'hit' => 85
            ],
            'phoenix_flame' => [
                'slug' => 'phoenix-flame',
                'damage' => 5,
                'element' => 'fire',
                'heal' => 2,
                'name' => 'Flamme du phénix',
                'description' => 'Des flammes régénératrices qui brûlent et soignent à la fois',
                'hit' => 90
            ],
            'meteor_strike' => [
                'slug' => 'meteor-strike',
                'damage' => 8,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Frappe météorique',
                'description' => 'Invoque un météore enflammé qui s\'écrase sur l\'ennemi',
                'hit' => 75
            ],
            'burning_touch' => [
                'slug' => 'burning-touch',
                'damage' => 2,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Toucher brûlant',
                'description' => 'Un simple contact qui inflige des brûlures',
                'hit' => 100
            ],
            'heat_wave' => [
                'slug' => 'heat-wave',
                'damage' => 3,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Vague de chaleur',
                'description' => 'Une onde de chaleur qui affaiblit les ennemis',
                'hit' => 95
            ],
            'dragon_breath' => [
                'slug' => 'dragon-breath',
                'damage' => 6,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Souffle du dragon',
                'description' => 'Un souffle de feu dévastateur',
                'hit' => 85
            ],
            'volcanic_eruption' => [
                'slug' => 'volcanic-eruption',
                'damage' => 10,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Éruption volcanique',
                'description' => 'Déchaîne la puissance d\'un volcan sur les ennemis',
                'hit' => 70
            ],
            'ember_shield' => [
                'slug' => 'ember-shield',
                'damage' => 1,
                'element' => 'fire',
                'heal' => 3,
                'name' => 'Bouclier d\'étincelles',
                'description' => 'Un bouclier de braises qui protège et brûle les attaquants',
                'hit' => 100
            ],
            'fire_whip' => [
                'slug' => 'fire-whip',
                'damage' => 4,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Fouet de feu',
                'description' => 'Un fouet enflammé qui frappe à distance',
                'hit' => 90
            ],
            'combustion' => [
                'slug' => 'combustion',
                'damage' => 3,
                'element' => 'fire',
                'heal' => null,
                'name' => 'Combustion',
                'description' => 'Enflamme instantanément la cible',
                'hit' => 95
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
            'rejuvenation' => [
                'slug' => 'rejuvenation',
                'damage' => null,
                'element' => 'life',
                'heal' => 8,
                'name' => 'Régénération',
                'description' => 'Un sort de régénération qui restaure progressivement la santé',
                'hit' => 100
            ],
            'divine_blessing' => [
                'slug' => 'divine-blessing',
                'damage' => null,
                'element' => 'life',
                'heal' => 12,
                'name' => 'Bénédiction divine',
                'description' => 'Une puissante bénédiction qui soigne les blessures graves',
                'hit' => 100
            ],
            'healing_wave' => [
                'slug' => 'healing-wave',
                'damage' => null,
                'element' => 'life',
                'heal' => 7,
                'name' => 'Vague de guérison',
                'description' => 'Une vague d\'énergie curative qui soigne les blessures',
                'hit' => 100
            ],
            'life_transfer' => [
                'slug' => 'life-transfer',
                'damage' => 2,
                'element' => 'life',
                'heal' => 4,
                'name' => 'Transfert de vie',
                'description' => 'Transfère l\'énergie vitale de l\'ennemi vers soi',
                'hit' => 90
            ],
            'holy_light' => [
                'slug' => 'holy-light',
                'damage' => 3,
                'element' => 'life',
                'heal' => 3,
                'name' => 'Lumière sacrée',
                'description' => 'Une lumière divine qui soigne les alliés et blesse les ennemis',
                'hit' => 95
            ],
            'vitality_surge' => [
                'slug' => 'vitality-surge',
                'damage' => null,
                'element' => 'life',
                'heal' => 10,
                'name' => 'Afflux de vitalité',
                'description' => 'Un puissant afflux d\'énergie vitale qui restaure la santé',
                'hit' => 100
            ],
            'life_shield' => [
                'slug' => 'life-shield',
                'damage' => null,
                'element' => 'life',
                'heal' => 6,
                'name' => 'Bouclier de vie',
                'description' => 'Crée un bouclier protecteur qui absorbe les dégâts',
                'hit' => 100
            ],
            'angelic_touch' => [
                'slug' => 'angelic-touch',
                'damage' => null,
                'element' => 'life',
                'heal' => 4,
                'name' => 'Toucher angélique',
                'description' => 'Un toucher qui apaise les blessures',
                'hit' => 100
            ],
            'purification' => [
                'slug' => 'purification',
                'damage' => 1,
                'element' => 'life',
                'heal' => 5,
                'name' => 'Purification',
                'description' => 'Purifie le corps et l\'esprit, éliminant les afflictions',
                'hit' => 100
            ],
            'celestial_blessing' => [
                'slug' => 'celestial-blessing',
                'damage' => null,
                'element' => 'life',
                'heal' => 15,
                'name' => 'Bénédiction céleste',
                'description' => 'Une bénédiction des cieux qui restaure une grande quantité de santé',
                'hit' => 95
            ],
            'life_burst' => [
                'slug' => 'life-burst',
                'damage' => null,
                'element' => 'life',
                'heal' => 9,
                'name' => 'Explosion de vie',
                'description' => 'Une explosion d\'énergie vitale qui soigne instantanément',
                'hit' => 100
            ],
            'divine_intervention' => [
                'slug' => 'divine-intervention',
                'damage' => 5,
                'element' => 'life',
                'heal' => 5,
                'name' => 'Intervention divine',
                'description' => 'Une intervention des dieux qui soigne et protège',
                'hit' => 90
            ],
            'healing_touch' => [
                'slug' => 'healing-touch',
                'damage' => null,
                'element' => 'life',
                'heal' => 3,
                'name' => 'Toucher guérisseur',
                'description' => 'Un simple toucher qui soigne les blessures légères',
                'hit' => 100
            ],
            'life_bloom' => [
                'slug' => 'life-bloom',
                'damage' => null,
                'element' => 'life',
                'heal' => 6,
                'name' => 'Floraison de vie',
                'description' => 'Une énergie vitale qui se répand progressivement',
                'hit' => 100
            ],
            'sacred_light' => [
                'slug' => 'sacred-light',
                'damage' => 4,
                'element' => 'life',
                'heal' => 2,
                'name' => 'Lumière sacrée',
                'description' => 'Un rayon de lumière divine qui purifie et soigne',
                'hit' => 95
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
            'soul_drain' => [
                'slug' => 'soul-drain',
                'damage' => 3,
                'element' => 'death',
                'heal' => 1,
                'name' => 'Drain d\'âme',
                'description' => 'Aspire l\'énergie vitale de la cible pour se soigner',
                'hit' => 85
            ],
            'death_touch' => [
                'slug' => 'death-touch',
                'damage' => 4,
                'element' => 'death',
                'heal' => null,
                'name' => 'Toucher mortel',
                'description' => 'Un contact qui inflige une douleur intense',
                'hit' => 80
            ],
            'shadow_bolt' => [
                'slug' => 'shadow-bolt',
                'damage' => 5,
                'element' => 'death',
                'heal' => null,
                'name' => 'Éclair d\'ombre',
                'description' => 'Un projectile d\'énergie sombre qui frappe la cible',
                'hit' => 85
            ],
            'life_leech' => [
                'slug' => 'life-leech',
                'damage' => 4,
                'element' => 'death',
                'heal' => 2,
                'name' => 'Sangsue vitale',
                'description' => 'Absorbe l\'énergie vitale de la cible',
                'hit' => 90
            ],
            'death_grip' => [
                'slug' => 'death-grip',
                'damage' => 3,
                'element' => 'death',
                'heal' => null,
                'name' => 'Emprise de la mort',
                'description' => 'Une main spectrale qui saisit et blesse la cible',
                'hit' => 95
            ],
            'shadow_wave' => [
                'slug' => 'shadow-wave',
                'damage' => 6,
                'element' => 'death',
                'heal' => null,
                'name' => 'Vague d\'ombre',
                'description' => 'Une vague d\'énergie sombre qui balaye la zone',
                'hit' => 80
            ],
            'necrotic_touch' => [
                'slug' => 'necrotic-touch',
                'damage' => 2,
                'element' => 'death',
                'heal' => null,
                'name' => 'Toucher nécrotique',
                'description' => 'Un toucher qui provoque la nécrose des tissus',
                'hit' => 100
            ],
            'dark_harvest' => [
                'slug' => 'dark-harvest',
                'damage' => 7,
                'element' => 'death',
                'heal' => 3,
                'name' => 'Moisson sombre',
                'description' => 'Récolte l\'énergie vitale des ennemis affaiblis',
                'hit' => 75
            ],
            'soul_rip' => [
                'slug' => 'soul-rip',
                'damage' => 8,
                'element' => 'death',
                'heal' => null,
                'name' => 'Déchirure d\'âme',
                'description' => 'Déchire l\'âme de la cible, causant une douleur intense',
                'hit' => 70
            ],
            'death_nova' => [
                'slug' => 'death-nova',
                'damage' => 6,
                'element' => 'death',
                'heal' => null,
                'name' => 'Nova de mort',
                'description' => 'Une explosion d\'énergie mortelle qui se propage',
                'hit' => 80
            ],
            'shadow_mend' => [
                'slug' => 'shadow-mend',
                'damage' => 1,
                'element' => 'death',
                'heal' => 5,
                'name' => 'Guérison des ombres',
                'description' => 'Utilise l\'énergie sombre pour soigner les blessures',
                'hit' => 95
            ],
            'plague_strike' => [
                'slug' => 'plague-strike',
                'damage' => 3,
                'element' => 'death',
                'heal' => null,
                'name' => 'Frappe pestilentielle',
                'description' => 'Infecte la cible avec une maladie débilitante',
                'hit' => 90
            ],
            'death_coil' => [
                'slug' => 'death-coil',
                'damage' => 5,
                'element' => 'death',
                'heal' => null,
                'name' => 'Spirale de mort',
                'description' => 'Une spirale d\'énergie nécrotique qui frappe la cible',
                'hit' => 85
            ],
            'soul_siphon' => [
                'slug' => 'soul-siphon',
                'damage' => 4,
                'element' => 'death',
                'heal' => 4,
                'name' => 'Siphon d\'âme',
                'description' => 'Siphonne l\'essence vitale de la cible',
                'hit' => 85
            ],
            'dark_ritual' => [
                'slug' => 'dark-ritual',
                'damage' => 2,
                'element' => 'death',
                'heal' => 6,
                'name' => 'Rituel sombre',
                'description' => 'Un rituel qui sacrifie une partie de sa force pour se soigner',
                'hit' => 100
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
            'earthquake' => [
                'slug' => 'earthquake',
                'damage' => 4,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Tremblement de terre',
                'description' => 'Provoque un séisme qui déstabilise les ennemis',
                'hit' => 85
            ],
            'rock_armor' => [
                'slug' => 'rock-armor',
                'damage' => 0,
                'element' => 'earth',
                'heal' => 3,
                'name' => 'Armure de roche',
                'description' => 'Crée une protection rocheuse qui absorbe les dégâts',
                'hit' => 100
            ],
            'boulder_throw' => [
                'slug' => 'boulder-throw',
                'damage' => 6,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Lancer de rocher',
                'description' => 'Lance un énorme rocher sur l\'ennemi',
                'hit' => 80
            ],
            'earth_spike' => [
                'slug' => 'earth-spike',
                'damage' => 3,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Pic de terre',
                'description' => 'Un pic rocheux jaillit du sol et transperce l\'ennemi',
                'hit' => 90
            ],
            'stone_spikes' => [
                'slug' => 'stone-spikes',
                'damage' => 4,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Pics de pierre',
                'description' => 'Fait jaillir plusieurs pics de pierre acérés du sol',
                'hit' => 88
            ],
            'landslide' => [
                'slug' => 'landslide',
                'damage' => 5,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Glissement de terrain',
                'description' => 'Déclenche un glissement de terrain qui ensevelit les ennemis',
                'hit' => 85
            ],
            'stone_skin' => [
                'slug' => 'stone-skin',
                'damage' => 0,
                'element' => 'earth',
                'heal' => 5,
                'name' => 'Peau de pierre',
                'description' => 'Transforme la peau en pierre, offrant une protection accrue',
                'hit' => 100
            ],
            'earth_shield' => [
                'slug' => 'earth-shield',
                'damage' => 1,
                'element' => 'earth',
                'heal' => 4,
                'name' => 'Bouclier terreux',
                'description' => 'Un bouclier de terre qui protège et contre-attaque',
                'hit' => 100
            ],
            'crystal_spear' => [
                'slug' => 'crystal-spear',
                'damage' => 7,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Lance de cristal',
                'description' => 'Une lance de cristal tranchante qui transperce les armures',
                'hit' => 85
            ],
            'quicksand' => [
                'slug' => 'quicksand',
                'damage' => 2,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Sables mouvants',
                'description' => 'Transforme le sol en sables mouvants qui engloutissent l\'ennemi',
                'hit' => 95
            ],
            'stone_wall' => [
                'slug' => 'stone-wall',
                'damage' => 0,
                'element' => 'earth',
                'heal' => 6,
                'name' => 'Mur de pierre',
                'description' => 'Érige un mur de pierre infranchissable',
                'hit' => 100
            ],
            'earth_blessing' => [
                'slug' => 'earth-blessing',
                'damage' => 0,
                'element' => 'earth',
                'heal' => 8,
                'name' => 'Bénédiction de la terre',
                'description' => 'Invoque la bénédiction de la terre pour soigner et protéger',
                'hit' => 100
            ],
            'mountain_strength' => [
                'slug' => 'mountain-strength',
                'damage' => 3,
                'element' => 'earth',
                'heal' => 3,
                'name' => 'Force de la montagne',
                'description' => 'Puise dans la force des montagnes pour renforcer le corps',
                'hit' => 95
            ],
            'crystal_growth' => [
                'slug' => 'crystal-growth',
                'damage' => 4,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Croissance cristalline',
                'description' => 'Des cristaux acérés poussent rapidement et blessent l\'ennemi',
                'hit' => 90
            ],
            'tectonic_shift' => [
                'slug' => 'tectonic-shift',
                'damage' => 8,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Déplacement tectonique',
                'description' => 'Provoque un violent déplacement des plaques tectoniques',
                'hit' => 75
            ],
            'petrification' => [
                'slug' => 'petrification',
                'damage' => 5,
                'element' => 'earth',
                'heal' => null,
                'name' => 'Pétrification',
                'description' => 'Transforme partiellement l\'ennemi en pierre',
                'hit' => 80
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
            'metal_storm' => [
                'slug' => 'metal-storm',
                'damage' => 5,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Tempête métallique',
                'description' => 'Une pluie de fragments métalliques acérés',
                'hit' => 80
            ],
            'iron_fist' => [
                'slug' => 'iron-fist',
                'damage' => 3,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Poing de fer',
                'description' => 'Un coup puissant avec un poing métallique',
                'hit' => 95
            ],
            'blade_dance' => [
                'slug' => 'blade-dance',
                'damage' => 6,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Danse des lames',
                'description' => 'Une série de coups rapides avec des lames flottantes',
                'hit' => 85
            ],
            'steel_shield' => [
                'slug' => 'steel-shield',
                'damage' => 0,
                'element' => 'metal',
                'heal' => 4,
                'name' => 'Bouclier d\'acier',
                'description' => 'Un bouclier métallique qui offre une protection solide',
                'hit' => 100
            ],
            'razor_edge' => [
                'slug' => 'razor-edge',
                'damage' => 7,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Lame rasoir',
                'description' => 'Une lame si fine qu\'elle coupe presque tout',
                'hit' => 80
            ],
            'metal_skin' => [
                'slug' => 'metal-skin',
                'damage' => 1,
                'element' => 'metal',
                'heal' => 5,
                'name' => 'Peau métallique',
                'description' => 'Transforme la peau en métal pour une protection accrue',
                'hit' => 100
            ],
            'shrapnel_burst' => [
                'slug' => 'shrapnel-burst',
                'damage' => 4,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Explosion d\'éclats',
                'description' => 'Une explosion qui projette des éclats métalliques dans toutes les directions',
                'hit' => 85
            ],
            'iron_maiden' => [
                'slug' => 'iron-maiden',
                'damage' => 8,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Vierge de fer',
                'description' => 'Emprisonne l\'ennemi dans une cage de pointes métalliques',
                'hit' => 75
            ],
            'chain_lightning' => [
                'slug' => 'chain-lightning',
                'damage' => 5,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Chaîne d\'éclairs',
                'description' => 'Des chaînes métalliques chargées d\'électricité frappent l\'ennemi',
                'hit' => 85
            ],
            'metallic_regeneration' => [
                'slug' => 'metallic-regeneration',
                'damage' => 0,
                'element' => 'metal',
                'heal' => 6,
                'name' => 'Régénération métallique',
                'description' => 'Utilise des particules métalliques pour réparer les blessures',
                'hit' => 100
            ],
            'blade_barrier' => [
                'slug' => 'blade-barrier',
                'damage' => 3,
                'element' => 'metal',
                'heal' => 3,
                'name' => 'Barrière de lames',
                'description' => 'Crée une barrière de lames tournoyantes qui protège et attaque',
                'hit' => 90
            ],
            'silver_bolt' => [
                'slug' => 'silver-bolt',
                'damage' => 6,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Carreau d\'argent',
                'description' => 'Un projectile d\'argent pur qui transperce les défenses',
                'hit' => 85
            ],
            'crushing_weight' => [
                'slug' => 'crushing-weight',
                'damage' => 7,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Poids écrasant',
                'description' => 'Une masse métallique qui s\'abat sur l\'ennemi',
                'hit' => 80
            ],
            'magnetic_pull' => [
                'slug' => 'magnetic-pull',
                'damage' => 2,
                'element' => 'metal',
                'heal' => null,
                'name' => 'Attraction magnétique',
                'description' => 'Attire violemment les objets métalliques vers l\'ennemi',
                'hit' => 95
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
            'thorn_burst' => [
                'slug' => 'thorn-burst',
                'damage' => 3,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Explosion d\'épines',
                'description' => 'Des épines jaillissent du sol et blessent les ennemis',
                'hit' => 85
            ],
            'natural_healing' => [
                'slug' => 'natural-healing',
                'damage' => null,
                'element' => 'nature',
                'heal' => 4,
                'name' => 'Guérison naturelle',
                'description' => 'Les forces de la nature soignent les blessures',
                'hit' => 100
            ],
            'poison_cloud' => [
                'slug' => 'poison-cloud',
                'damage' => 4,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Nuage empoisonné',
                'description' => 'Un nuage de spores toxiques qui empoisonne l\'ennemi',
                'hit' => 85
            ],
            'entangling_roots' => [
                'slug' => 'entangling-roots',
                'damage' => 2,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Racines enchevêtrées',
                'description' => 'Des racines surgissent du sol et immobilisent l\'ennemi',
                'hit' => 95
            ],
            'nature_wrath' => [
                'slug' => 'nature-wrath',
                'damage' => 6,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Courroux de la nature',
                'description' => 'Déchaîne la fureur de la nature sur l\'ennemi',
                'hit' => 80
            ],
            'leaf_blade' => [
                'slug' => 'leaf-blade',
                'damage' => 5,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Lame feuille',
                'description' => 'Une lame tranchante formée de feuilles acérées',
                'hit' => 90
            ],
            'forest_embrace' => [
                'slug' => 'forest-embrace',
                'damage' => null,
                'element' => 'nature',
                'heal' => 7,
                'name' => 'Étreinte de la forêt',
                'description' => 'L\'énergie de la forêt enveloppe et soigne les blessures',
                'hit' => 100
            ],
            'venomous_bite' => [
                'slug' => 'venomous-bite',
                'damage' => 3,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Morsure venimeuse',
                'description' => 'Une morsure qui injecte un venin puissant',
                'hit' => 90
            ],
            'wild_growth' => [
                'slug' => 'wild-growth',
                'damage' => 1,
                'element' => 'nature',
                'heal' => 5,
                'name' => 'Croissance sauvage',
                'description' => 'Des plantes médicinales poussent rapidement et soignent les blessures',
                'hit' => 100
            ],
            'nature_fury' => [
                'slug' => 'nature-fury',
                'damage' => 8,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Fureur naturelle',
                'description' => 'Libère toute la puissance destructrice de la nature',
                'hit' => 75
            ],
            'thorn_shield' => [
                'slug' => 'thorn-shield',
                'damage' => 2,
                'element' => 'nature',
                'heal' => 3,
                'name' => 'Bouclier d\'épines',
                'description' => 'Un bouclier d\'épines qui protège et blesse les attaquants',
                'hit' => 100
            ],
            'toxic_spores' => [
                'slug' => 'toxic-spores',
                'damage' => 4,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Spores toxiques',
                'description' => 'Libère des spores hautement toxiques dans l\'air',
                'hit' => 85
            ],
            'nature_blessing' => [
                'slug' => 'nature-blessing',
                'damage' => null,
                'element' => 'nature',
                'heal' => 10,
                'name' => 'Bénédiction de la nature',
                'description' => 'La bénédiction de la nature qui restaure la vitalité',
                'hit' => 100
            ],
            'vine_snare' => [
                'slug' => 'vine-snare',
                'damage' => 3,
                'element' => 'nature',
                'heal' => null,
                'name' => 'Piège de vignes',
                'description' => 'Des vignes surgissent et s\'enroulent autour de l\'ennemi',
                'hit' => 90
            ],
            'primal_surge' => [
                'slug' => 'primal-surge',
                'damage' => 5,
                'element' => 'nature',
                'heal' => 5,
                'name' => 'Afflux primordial',
                'description' => 'Un afflux d\'énergie primordiale qui soigne et blesse',
                'hit' => 85
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
            'tornado' => [
                'slug' => 'tornado',
                'damage' => 4,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Tornade',
                'description' => 'Une violente tornade qui emporte tout sur son passage',
                'hit' => 80
            ],
            'wind_shield' => [
                'slug' => 'wind-shield',
                'damage' => 0,
                'element' => 'wind',
                'heal' => 2,
                'name' => 'Bouclier de vent',
                'description' => 'Un tourbillon protecteur qui dévie les attaques',
                'hit' => 100
            ],
            'gust' => [
                'slug' => 'gust',
                'damage' => 2,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Bourrasque',
                'description' => 'Une puissante bourrasque qui repousse l\'ennemi',
                'hit' => 95
            ],
            'air_dash' => [
                'slug' => 'air-dash',
                'damage' => 1,
                'element' => 'wind',
                'heal' => 1,
                'name' => 'Ruée d\'air',
                'description' => 'Permet de se déplacer rapidement sur un coussin d\'air',
                'hit' => 100
            ],
            'air_slash' => [
                'slug' => 'air-slash',
                'damage' => 5,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Tranchant aérien',
                'description' => 'Une lame d\'air comprimé qui tranche l\'ennemi',
                'hit' => 85
            ],
            'cyclone' => [
                'slug' => 'cyclone',
                'damage' => 6,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Cyclone',
                'description' => 'Un puissant cyclone qui déchire tout sur son passage',
                'hit' => 80
            ],
            'wind_blast' => [
                'slug' => 'wind-blast',
                'damage' => 3,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Souffle du vent',
                'description' => 'Une rafale concentrée qui frappe l\'ennemi',
                'hit' => 90
            ],
            'healing_breeze' => [
                'slug' => 'healing-breeze',
                'damage' => null,
                'element' => 'wind',
                'heal' => 5,
                'name' => 'Brise guérisseuse',
                'description' => 'Une douce brise qui apaise et soigne les blessures',
                'hit' => 100
            ],
            'vacuum_blade' => [
                'slug' => 'vacuum-blade',
                'damage' => 7,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Lame de vide',
                'description' => 'Une lame créée par le vide qui tranche tout',
                'hit' => 75
            ],
            'wind_wall' => [
                'slug' => 'wind-wall',
                'damage' => 1,
                'element' => 'wind',
                'heal' => 4,
                'name' => 'Mur de vent',
                'description' => 'Un mur de vent qui protège et repousse les attaques',
                'hit' => 100
            ],
            'hurricane' => [
                'slug' => 'hurricane',
                'damage' => 8,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Ouragan',
                'description' => 'Un ouragan dévastateur qui ravage tout',
                'hit' => 70
            ],
            'air_current' => [
                'slug' => 'air-current',
                'damage' => 2,
                'element' => 'wind',
                'heal' => 3,
                'name' => 'Courant d\'air',
                'description' => 'Un courant d\'air revigorant qui soigne et protège',
                'hit' => 95
            ],
            'pressure_point' => [
                'slug' => 'pressure-point',
                'damage' => 4,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Point de pression',
                'description' => 'Concentre l\'air sur un point précis pour causer des dégâts internes',
                'hit' => 90
            ],
            'wind_scythe' => [
                'slug' => 'wind-scythe',
                'damage' => 6,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Faux de vent',
                'description' => 'Une faux d\'air tranchante qui fauche les ennemis',
                'hit' => 85
            ],
            'tempest' => [
                'slug' => 'tempest',
                'damage' => 5,
                'element' => 'wind',
                'heal' => null,
                'name' => 'Tempête',
                'description' => 'Une violente tempête qui frappe de toutes parts',
                'hit' => 85
            ],
            'wind_blessing' => [
                'slug' => 'wind-blessing',
                'damage' => null,
                'element' => 'wind',
                'heal' => 7,
                'name' => 'Bénédiction du vent',
                'description' => 'La bénédiction des vents qui soigne et revigore',
                'hit' => 100
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