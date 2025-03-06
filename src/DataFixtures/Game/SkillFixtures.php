<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Skill;
use App\Entity\Game\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\DomainFixtures;

class SkillFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $skillsData = $this->getSkillsData();
        
        // Première passe : création des compétences
        foreach ($skillsData as $reference => $data) {
            if ($reference === 'skill (template)') {
                continue;
            }
            
            $skill = new Skill();
            
            if (isset($data['slug'])) {
                $skill->setSlug((string)$data['slug']);
            }
            
            if (isset($data['title'])) {
                $skill->setTitle((string)$data['title']);
            }
            
            if (isset($data['description'])) {
                $skill->setDescription((string)$data['description']);
            }
            
            if (isset($data['requiredPoints'])) {
                $skill->setRequiredPoints((int)$data['requiredPoints']);
            }
            
            if (isset($data['domain'])) {
                $skill->setDomain($this->getReference($data['domain'], Domain::class));
            }
            
            if (isset($data['actions'])) {
                $skill->setActions($data['actions']);
            }
            
            $manager->persist($skill);
            
            $this->addReference($reference, $skill);
        }
        
        // Deuxième passe : définition des prérequis
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
            // Compétences de Pyromancie
            'pyro_materia_1' => [
                'title' => 'Apprenti pyromancien',
                'slug' => 'pyro-materia-1',
                'description' => "Permet d'utiliser les matéria de pyromancie de niveau 1",
                'requiredPoints' => 0,
                'domain' => 'pyromancy',
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
                'title' => 'Efficacité du feu',
                'slug' => 'pyro-damage-1',
                'description' => 'Augmente les dégâts des matéria de pyromancie',
                'requiredPoints' => 10,
                'domain' => 'pyromancy',
                'damage' => 1,
                'requirements' => ['pyro_materia_1'],
            ],
            'pyro_materia_2' => [
                'title' => 'Matéria niv 2',
                'slug' => 'pyro-materia-2',
                'description' => "Permet d'utiliser les matéria de pyromancie de niveau 2",
                'requiredPoints' => 20,
                'domain' => 'pyromancy',
                'requirements' => ['pyro_critical_1', 'pyro_damage_1'],
            ],
            'pyro_materia_3' => [
                'title' => 'Matéria niv 3',
                'slug' => 'pyro-materia-3',
                'description' => "Permet d'utiliser les matéria de pyromancie de niveau 3",
                'requiredPoints' => 30,
                'domain' => 'pyromancy',
                'requirements' => ['pyro_materia_2'],
            ],
            'pyro_hit_1' => [
                'title' => 'Chaude précision',
                'slug' => 'pyro-hit-1',
                'description' => "Augmente les chances de toucher",
                'requiredPoints' => 20,
                'hit' => 1,
                'domain' => 'pyromancy',
                'requirements' => ['pyro_materia_2'],
            ],
            
            // Compétences de Mineur
            'miner_ruby_xs' => [
                'slug' => 'miner-ruby-xs',
                'title' => "Minage du ruby débutant",
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-xs']]],
                'requiredPoints' => 0,
                'domain' => 'miner',
            ],
            'miner_ruby_s' => [
                'slug' => 'miner-ruby-s',
                'title' => "Minage du ruby apprenti",
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-s']]],
                'requiredPoints' => 10,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_xs'],
            ],
            'miner_ruby_m' => [
                'slug' => 'miner-ruby-m',
                'title' => "Minage du ruby avancé",
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-m']]],
                'requiredPoints' => 50,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_s'],
            ],
            'miner_ruby_l' => [
                'slug' => 'miner-ruby-l',
                'title' => "Minage du ruby avancé",
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-l']]],
                'requiredPoints' => 100,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_m'],
            ],
            'miner_ruby_xl' => [
                'slug' => 'miner-ruby-xl',
                'title' => "Minage du ruby expert",
                'description' => 'Permet de miner le ruby',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-xl']]],
                'requiredPoints' => 200,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_l'],
            ],
            'miner_iron_xs' => [
                'slug' => 'miner-iron-xs',
                'title' => "Minage du fer débutant",
                'description' => "Permet de miner le fer",
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-xs']]],
                'requiredPoints' => 10,
                'domain' => 'miner',
                'requirements' => ['miner_ruby_xs'],
            ],
            'miner_iron_s' => [
                'slug' => 'miner-iron-s',
                'title' => "Minage du fer apprenti",
                'description' => "Permet de miner le fer",
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-s']]],
                'requiredPoints' => 50,
                'domain' => 'miner',
                'requirements' => ['miner_iron_xs'],
            ],
            
            // Compétences de Défenseur
            'defender_materia_1' => [
                'title' => 'Apprenti défenseur',
                'slug' => 'defender-materia-1',
                'description' => "Permet d'utiliser les matéria de défenseur de niveau 1",
                'requiredPoints' => 0,
                'domain' => 'defender',
            ],
            
            // Compétences de Druide
            'druid_materia_1' => [
                'title' => 'Apprenti druide',
                'slug' => 'druid-materia-1',
                'description' => "Permet d'utiliser les matéria de druide de niveau 1",
                'requiredPoints' => 0,
                'domain' => 'druid',
            ],
            
            // Compétences de Guérisseur
            'healer_materia_1' => [
                'title' => 'Apprenti soigneur',
                'slug' => 'healer-materia-1',
                'description' => "Permet d'utiliser les matéria de soigneur de niveau 1",
                'requiredPoints' => 0,
                'domain' => 'healer',
            ],
            
            // Compétences d'Herboriste
            'herbalist_dandelion' => [
                'slug' => 'herbalist-dandelion-xs',
                'title' => "Récolte de pissenlit",
                'description' => 'Permet de récolter les pissenlits',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-dandelion-xs']]],
                'domain' => 'herbalist',
            ],
            'herbalist_mint' => [
                'slug' => 'herbalist-mint-xs',
                'title' => "Récolte de menthe",
                'description' => 'Permet de récolter la menthe',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-mint-xs']]],
                'domain' => 'herbalist',
            ],
            'herbalist_sage' => [
                'slug' => 'herbalist-sage-xs',
                'title' => "Récolte de sauge",
                'description' => 'Permet de récolter la sauge',
                'requiredPoints' => 10,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-sage-xs']]],
                'domain' => 'herbalist',
                'requirements' => ['herbalist_mint'],
            ],
            
            // Compétences de Nécromancien
            'necro_materia_1' => [
                'title' => 'Apprenti nécro',
                'slug' => 'necro-materia-1',
                'description' => "Permet d'utiliser les matéria de nécro de niveau 1",
                'requiredPoints' => 0,
                'domain' => 'necro',
            ],
            
            // Compétences de Soldat
            'soldier_apprentice' => [
                'title' => 'Apprenti soldat',
                'slug' => 'soldier-apprentice',
                'description' => 'Permet de savoir manier une épée',
                'requiredPoints' => 0,
                'domain' => 'soldier',
            ],
            'soldier_materia_1' => [
                'title' => "Swing de l'épée",
                'slug' => 'soldier-materia-1',
                'description' => "Permet d'utiliser les matéria de soldat de niveau 1",
                'requiredPoints' => 10,
                'domain' => 'soldier',
                'requirements' => ['soldier_apprentice'],
            ],
            
            // Compétences de Mage Blanc
            'white_wizard_materia_1' => [
                'title' => 'Apprenti mage blanc',
                'slug' => 'white-wizard-materia-1',
                'description' => "Permet d'utiliser les matéria de mage blanc de niveau 1",
                'requiredPoints' => 0,
                'domain' => 'white_wizard',
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