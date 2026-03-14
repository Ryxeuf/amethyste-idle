<?php

namespace App\DataFixtures\Game;

use App\DataFixtures\DomainFixtures;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SkillFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $skillsData = $this->getSkillsData();

        // Premiere passe : creation des competences
        foreach ($skillsData as $reference => $data) {
            if ($reference === 'skill (template)') {
                continue;
            }

            $skill = new Skill();

            if (isset($data['slug'])) {
                $skill->setSlug((string) $data['slug']);
            }

            if (isset($data['title'])) {
                $skill->setTitle((string) $data['title']);
            }

            if (isset($data['description'])) {
                $skill->setDescription((string) $data['description']);
            }

            if (isset($data['requiredPoints'])) {
                $skill->setRequiredPoints((int) $data['requiredPoints']);
            }

            if (isset($data['domain'])) {
                $skill->setDomain($this->getReference($data['domain'], Domain::class));
            }

            if (isset($data['actions'])) {
                $skill->setActions($data['actions']);
            }

            if (isset($data['damage'])) {
                $skill->setDamage((int) $data['damage']);
            }
            if (isset($data['heal'])) {
                $skill->setHeal((int) $data['heal']);
            }
            if (isset($data['hit'])) {
                $skill->setHit((int) $data['hit']);
            }
            if (isset($data['critical'])) {
                $skill->setCritical((int) $data['critical']);
            }
            if (isset($data['life'])) {
                $skill->setLife((int) $data['life']);
            }

            $manager->persist($skill);

            $this->addReference($reference, $skill);
        }

        // Deuxieme passe : definition des prerequis
        foreach ($skillsData as $reference => $data) {
            if ($reference === 'skill (template)' || !isset($data['requirements'])) {
                continue;
            }

            /** @var Skill $skill */
            $skill = $this->getReference($reference, Skill::class);

            foreach ($data['requirements'] as $requirementRef) {
                $skill->addRequirement($this->getReference($requirementRef, Skill::class));
            }
        }

        $manager->flush();
    }

    private function getSkillsData(): array
    {
        return [
            // =====================================================
            // PYROMANCIE — Boule de feu > Pluie de flammes > Inferno
            // =====================================================
            'pyro_materia_1' => [
                'title' => 'Apprenti pyromancien',
                'slug' => 'pyro-materia-1',
                'description' => 'Debloque le sort Boule de feu',
                'requiredPoints' => 0,
                'domain' => 'pyromancy',
                'actions' => ['combat' => ['spell_slug' => 'fire-ball']],
            ],
            'pyro_critical_1' => [
                'title' => 'Points faibles',
                'slug' => 'pyro-critical-1',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => 'pyromancy',
                'critical' => 1,
                'requirements' => ['pyro_materia_1'],
            ],
            'pyro_damage_1' => [
                'title' => 'Efficacite du feu',
                'slug' => 'pyro-damage-1',
                'description' => 'Augmente les degats de pyromancie',
                'requiredPoints' => 10,
                'domain' => 'pyromancy',
                'damage' => 1,
                'requirements' => ['pyro_materia_1'],
            ],
            'pyro_materia_2' => [
                'title' => 'Pluie de flammes',
                'slug' => 'pyro-materia-2',
                'description' => 'Debloque le sort Pluie de flammes (AoE)',
                'requiredPoints' => 20,
                'domain' => 'pyromancy',
                'actions' => ['combat' => ['spell_slug' => 'flame-rain']],
                'requirements' => ['pyro_critical_1', 'pyro_damage_1'],
            ],
            'pyro_materia_3' => [
                'title' => 'Inferno',
                'slug' => 'pyro-materia-3',
                'description' => 'Debloque le sort Inferno — devastation totale',
                'requiredPoints' => 30,
                'domain' => 'pyromancy',
                'actions' => ['combat' => ['spell_slug' => 'inferno']],
                'requirements' => ['pyro_materia_2'],
            ],
            'pyro_hit_1' => [
                'title' => 'Chaude precision',
                'slug' => 'pyro-hit-1',
                'description' => 'Augmente les chances de toucher',
                'requiredPoints' => 20,
                'hit' => 1,
                'domain' => 'pyromancy',
                'requirements' => ['pyro_materia_2'],
            ],

            // =====================================================
            // SOLDAT — Frappe puissante > Charge > Tourbillon d'epee
            // =====================================================
            'soldier_apprentice' => [
                'title' => 'Apprenti soldat',
                'slug' => 'soldier-apprentice',
                'description' => 'Debloque la Frappe puissante',
                'requiredPoints' => 0,
                'domain' => 'soldier',
                'actions' => ['combat' => ['spell_slug' => 'sharp-blade']],
            ],
            'soldier_damage_1' => [
                'title' => 'Force brute',
                'slug' => 'soldier-damage-1',
                'description' => 'Augmente les degats physiques',
                'requiredPoints' => 10,
                'domain' => 'soldier',
                'damage' => 1,
                'requirements' => ['soldier_apprentice'],
            ],
            'soldier_materia_1' => [
                'title' => 'Charge',
                'slug' => 'soldier-materia-1',
                'description' => 'Debloque le sort Charge',
                'requiredPoints' => 10,
                'domain' => 'soldier',
                'actions' => ['combat' => ['spell_slug' => 'iron-fist']],
                'requirements' => ['soldier_apprentice'],
            ],
            'soldier_materia_2' => [
                'title' => 'Tourbillon d\'epee',
                'slug' => 'soldier-materia-2',
                'description' => "Debloque le Tourbillon d'epee (AoE)",
                'requiredPoints' => 25,
                'domain' => 'soldier',
                'actions' => ['combat' => ['spell_slug' => 'blade-dance']],
                'requirements' => ['soldier_materia_1', 'soldier_damage_1'],
            ],

            // =====================================================
            // SOIGNEUR — Soin mineur > Regeneration > Benediction
            // =====================================================
            'healer_materia_1' => [
                'title' => 'Apprenti soigneur',
                'slug' => 'healer-materia-1',
                'description' => 'Debloque le sort Soin mineur',
                'requiredPoints' => 0,
                'domain' => 'healer',
                'actions' => ['combat' => ['spell_slug' => 'life-heal']],
            ],
            'healer_heal_1' => [
                'title' => 'Main guerisseuse',
                'slug' => 'healer-heal-1',
                'description' => 'Augmente la puissance des soins',
                'requiredPoints' => 10,
                'domain' => 'healer',
                'heal' => 1,
                'requirements' => ['healer_materia_1'],
            ],
            'healer_materia_2' => [
                'title' => 'Regeneration',
                'slug' => 'healer-materia-2',
                'description' => 'Debloque le sort Regeneration (HoT)',
                'requiredPoints' => 20,
                'domain' => 'healer',
                'actions' => ['combat' => ['spell_slug' => 'rejuvenation']],
                'requirements' => ['healer_heal_1'],
            ],
            'healer_materia_3' => [
                'title' => 'Benediction divine',
                'slug' => 'healer-materia-3',
                'description' => 'Debloque la Benediction divine — soin puissant',
                'requiredPoints' => 30,
                'domain' => 'healer',
                'actions' => ['combat' => ['spell_slug' => 'divine-blessing']],
                'requirements' => ['healer_materia_2'],
            ],

            // =====================================================
            // DEFENSEUR — Parade > Bouclier magique > Mur de fer
            // =====================================================
            'defender_materia_1' => [
                'title' => 'Apprenti defenseur',
                'slug' => 'defender-materia-1',
                'description' => 'Debloque le sort Parade',
                'requiredPoints' => 0,
                'domain' => 'defender',
                'actions' => ['combat' => ['spell_slug' => 'rock-armor']],
            ],
            'defender_life_1' => [
                'title' => 'Constitution',
                'slug' => 'defender-life-1',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => 'defender',
                'life' => 5,
                'requirements' => ['defender_materia_1'],
            ],
            'defender_materia_2' => [
                'title' => 'Bouclier magique',
                'slug' => 'defender-materia-2',
                'description' => 'Debloque le Bouclier magique — protection renforcee',
                'requiredPoints' => 20,
                'domain' => 'defender',
                'actions' => ['combat' => ['spell_slug' => 'stone-skin']],
                'requirements' => ['defender_life_1'],
            ],
            'defender_materia_3' => [
                'title' => 'Mur de fer',
                'slug' => 'defender-materia-3',
                'description' => 'Debloque le Mur de fer — defense ultime',
                'requiredPoints' => 30,
                'domain' => 'defender',
                'actions' => ['combat' => ['spell_slug' => 'stone-wall']],
                'requirements' => ['defender_materia_2'],
            ],

            // =====================================================
            // NECROMANCIEN — Drain de vie > Malediction > Moisson sombre
            // =====================================================
            'necro_materia_1' => [
                'title' => 'Apprenti necro',
                'slug' => 'necro-materia-1',
                'description' => 'Debloque le sort Drain de vie',
                'requiredPoints' => 0,
                'domain' => 'necro',
                'actions' => ['combat' => ['spell_slug' => 'soul-drain']],
            ],
            'necro_damage_1' => [
                'title' => 'Energie sombre',
                'slug' => 'necro-damage-1',
                'description' => 'Augmente les degats des sorts de mort',
                'requiredPoints' => 10,
                'domain' => 'necro',
                'damage' => 1,
                'requirements' => ['necro_materia_1'],
            ],
            'necro_materia_2' => [
                'title' => 'Malediction',
                'slug' => 'necro-materia-2',
                'description' => 'Debloque le sort Malediction (poison)',
                'requiredPoints' => 20,
                'domain' => 'necro',
                'actions' => ['combat' => ['spell_slug' => 'plague-strike']],
                'requirements' => ['necro_damage_1'],
            ],
            'necro_materia_3' => [
                'title' => 'Moisson sombre',
                'slug' => 'necro-materia-3',
                'description' => 'Debloque la Moisson sombre — drain massif',
                'requiredPoints' => 30,
                'domain' => 'necro',
                'actions' => ['combat' => ['spell_slug' => 'dark-harvest']],
                'requirements' => ['necro_materia_2'],
            ],

            // =====================================================
            // DRUIDE — Liane > Empoisonnement > Appel de la foret
            // =====================================================
            'druid_materia_1' => [
                'title' => 'Apprenti druide',
                'slug' => 'druid-materia-1',
                'description' => 'Debloque le sort Liane',
                'requiredPoints' => 0,
                'domain' => 'druid',
                'actions' => ['combat' => ['spell_slug' => 'liana-whip']],
            ],
            'druid_heal_1' => [
                'title' => 'Symbiose naturelle',
                'slug' => 'druid-heal-1',
                'description' => 'Augmente la puissance des soins de nature',
                'requiredPoints' => 10,
                'domain' => 'druid',
                'heal' => 1,
                'requirements' => ['druid_materia_1'],
            ],
            'druid_materia_2' => [
                'title' => 'Empoisonnement',
                'slug' => 'druid-materia-2',
                'description' => "Debloque l'Empoisonnement (DoT)",
                'requiredPoints' => 20,
                'domain' => 'druid',
                'actions' => ['combat' => ['spell_slug' => 'poison-cloud']],
                'requirements' => ['druid_heal_1'],
            ],
            'druid_materia_3' => [
                'title' => 'Appel de la foret',
                'slug' => 'druid-materia-3',
                'description' => "Debloque l'Appel de la foret — devastation naturelle",
                'requiredPoints' => 30,
                'domain' => 'druid',
                'actions' => ['combat' => ['spell_slug' => 'nature-wrath']],
                'requirements' => ['druid_materia_2'],
            ],

            // =====================================================
            // MAGE BLANC — Lumiere > Purification > Jugement sacre
            // =====================================================
            'white_wizard_materia_1' => [
                'title' => 'Apprenti mage blanc',
                'slug' => 'white-wizard-materia-1',
                'description' => 'Debloque le sort Lumiere',
                'requiredPoints' => 0,
                'domain' => 'white_wizard',
                'actions' => ['combat' => ['spell_slug' => 'holy-light']],
            ],
            'white_wizard_hit_1' => [
                'title' => 'Eclat divin',
                'slug' => 'white-wizard-hit-1',
                'description' => 'Augmente la precision des sorts',
                'requiredPoints' => 10,
                'domain' => 'white_wizard',
                'hit' => 2,
                'requirements' => ['white_wizard_materia_1'],
            ],
            'white_wizard_materia_2' => [
                'title' => 'Purification',
                'slug' => 'white-wizard-materia-2',
                'description' => 'Debloque le sort Purification',
                'requiredPoints' => 20,
                'domain' => 'white_wizard',
                'actions' => ['combat' => ['spell_slug' => 'purification']],
                'requirements' => ['white_wizard_hit_1'],
            ],
            'white_wizard_materia_3' => [
                'title' => 'Jugement sacre',
                'slug' => 'white-wizard-materia-3',
                'description' => 'Debloque le Jugement sacre — sort ultime',
                'requiredPoints' => 30,
                'domain' => 'white_wizard',
                'actions' => ['combat' => ['spell_slug' => 'divine-intervention']],
                'requirements' => ['white_wizard_materia_2'],
            ],

            // =====================================================
            // MINEUR (inchange)
            // =====================================================
            'miner_ruby_xs' => [
                'slug' => 'miner-ruby-xs',
                'title' => 'Minage du ruby debutant',
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-xs']]],
                'requiredPoints' => 0,
                'domain' => 'miner',
            ],
            'miner_ruby_s' => [
                'slug' => 'miner-ruby-s',
                'title' => 'Minage du ruby apprenti',
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-s']]],
                'requiredPoints' => 10,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_xs'],
            ],
            'miner_ruby_m' => [
                'slug' => 'miner-ruby-m',
                'title' => 'Minage du ruby avance',
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-m']]],
                'requiredPoints' => 50,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_s'],
            ],
            'miner_ruby_l' => [
                'slug' => 'miner-ruby-l',
                'title' => 'Minage du ruby avance',
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-l']]],
                'requiredPoints' => 100,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_m'],
            ],
            'miner_ruby_xl' => [
                'slug' => 'miner-ruby-xl',
                'title' => 'Minage du ruby expert',
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-xl']]],
                'requiredPoints' => 200,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_l'],
            ],
            'miner_iron_xs' => [
                'slug' => 'miner-iron-xs',
                'title' => 'Minage du fer debutant',
                'description' => 'Permet de miner le fer',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-xs']]],
                'requiredPoints' => 10,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_xs'],
            ],
            'miner_iron_s' => [
                'slug' => 'miner-iron-s',
                'title' => 'Minage du fer apprenti',
                'description' => 'Permet de miner le fer',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-s']]],
                'requiredPoints' => 50,
                'domain' => 'miner',
                'requirements' => ['miner_iron_xs'],
            ],

            // =====================================================
            // HERBORISTE (inchange)
            // =====================================================
            'herbalist_dandelion' => [
                'slug' => 'herbalist-dandelion-xs',
                'title' => 'Recolte de pissenlit',
                'description' => 'Permet de recolter les pissenlits',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-dandelion-xs']]],
                'domain' => 'herbalist',
            ],
            'herbalist_mint' => [
                'slug' => 'herbalist-mint-xs',
                'title' => 'Recolte de menthe',
                'description' => 'Permet de recolter la menthe',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-mint-xs']]],
                'domain' => 'herbalist',
            ],
            'herbalist_sage' => [
                'slug' => 'herbalist-sage-xs',
                'title' => 'Recolte de sauge',
                'description' => 'Permet de recolter la sauge',
                'requiredPoints' => 10,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-sage-xs']]],
                'domain' => 'herbalist',
                'requirements' => ['herbalist_mint'],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
        ];
    }
}
