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
                $domains = is_array($data['domain']) ? $data['domain'] : [$data['domain']];
                foreach ($domains as $domainRef) {
                    $skill->addDomain($this->getReference($domainRef, Domain::class));
                }
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
            if (!isset($data['requirements'])) {
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
        return array_merge(
            $this->getPyromancySkills(),
            $this->getSoldierSkills(),
            $this->getHealerSkills(),
            $this->getDefenderSkills(),
            $this->getNecromancerSkills(),
            $this->getDruidSkills(),
            $this->getStormcallerSkills(),
            $this->getMinerSkills(),
            $this->getHerbalistSkills(),
        );
    }

    // =========================================================================
    // PYROMANCIE (feu) — 15 skills, domaine modele complet
    // =========================================================================
    private function getPyromancySkills(): array
    {
        $d = 'pyromancy';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'pyro_apprenti_1' => [
                'title' => 'Apprenti pyromancien',
                'slug' => 'pyro-apprenti-1',
                'description' => 'Debloque le sort Boule de feu',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'fire-ball']],
            ],
            'pyro_apprenti_2' => [
                'title' => 'Initiation au feu',
                'slug' => 'pyro-apprenti-2',
                'description' => 'Debloque le sort Flammeche',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'flame']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'pyro_rang2_1' => [
                'title' => 'Points faibles',
                'slug' => 'pyro-rang2-1',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['pyro_apprenti_1'],
            ],
            'pyro_rang2_2' => [
                'title' => 'Efficacite du feu',
                'slug' => 'pyro-rang2-2',
                'description' => 'Augmente les degats de pyromancie',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['pyro_apprenti_1'],
            ],
            'pyro_rang2_3' => [
                'title' => 'Mur de feu',
                'slug' => 'pyro-rang2-3',
                'description' => 'Debloque le sort Mur de feu',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'fire-wall']],
                'requirements' => ['pyro_apprenti_2'],
            ],
            'pyro_rang2_4' => [
                'title' => 'Toucher brulant',
                'slug' => 'pyro-rang2-4',
                'description' => 'Debloque le sort Toucher brulant',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'burning-touch']],
                'requirements' => ['pyro_apprenti_2'],
            ],

            // Rang 3 (20-50 pts) — 4 skills
            'pyro_rang3_1' => [
                'title' => 'Pluie de flammes',
                'slug' => 'pyro-rang3-1',
                'description' => 'Debloque le sort Pluie de flammes (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'flame-rain']],
                'requirements' => ['pyro_rang2_1', 'pyro_rang2_2'],
            ],
            'pyro_rang3_2' => [
                'title' => 'Nova de feu',
                'slug' => 'pyro-rang3-2',
                'description' => 'Debloque le sort Nova de feu',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'fire-nova']],
                'requirements' => ['pyro_rang2_3'],
            ],
            'pyro_rang3_3' => [
                'title' => 'Chaude precision',
                'slug' => 'pyro-rang3-3',
                'description' => 'Augmente la precision des sorts de feu',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['pyro_rang2_4'],
            ],
            'pyro_rang3_4' => [
                'title' => 'Flamme du phenix',
                'slug' => 'pyro-rang3-4',
                'description' => 'Debloque le sort Flamme du phenix (degats + soin)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'phoenix-flame']],
                'requirements' => ['pyro_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'pyro_rang4_1' => [
                'title' => 'Inferno',
                'slug' => 'pyro-rang4-1',
                'description' => 'Debloque le sort Inferno — devastation totale',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'inferno']],
                'requirements' => ['pyro_rang3_1', 'pyro_rang3_2'],
            ],
            'pyro_rang4_2' => [
                'title' => 'Souffle du dragon',
                'slug' => 'pyro-rang4-2',
                'description' => 'Debloque le sort Souffle du dragon (AoE)',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'dragon-breath']],
                'requirements' => ['pyro_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'pyro_rang5_1' => [
                'title' => 'Eruption volcanique',
                'slug' => 'pyro-rang5-1',
                'description' => 'Debloque le sort ultime Eruption volcanique',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['combat' => ['spell_slug' => 'volcanic-eruption']],
                'requirements' => ['pyro_rang4_1', 'pyro_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // SOLDAT (metal) — 4 skills existants conserves
    // =========================================================================
    private function getSoldierSkills(): array
    {
        return [
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
        ];
    }

    // =========================================================================
    // GUERISSEUR (eau) — 4 skills existants conserves
    // =========================================================================
    private function getHealerSkills(): array
    {
        return [
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
        ];
    }

    // =========================================================================
    // DEFENSEUR (terre) — 4 skills existants conserves
    // =========================================================================
    private function getDefenderSkills(): array
    {
        return [
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
        ];
    }

    // =========================================================================
    // NECROMANCIEN (ombre) — adapte de 'necro' vers 'necromancer'
    // =========================================================================
    private function getNecromancerSkills(): array
    {
        return [
            'necro_materia_1' => [
                'title' => 'Apprenti necromancien',
                'slug' => 'necro-materia-1',
                'description' => 'Debloque le sort Drain de vie',
                'requiredPoints' => 0,
                'domain' => 'necromancer',
                'actions' => ['combat' => ['spell_slug' => 'soul-drain']],
            ],
            'necro_damage_1' => [
                'title' => 'Energie sombre',
                'slug' => 'necro-damage-1',
                'description' => 'Augmente les degats des sorts de mort',
                'requiredPoints' => 10,
                'domain' => 'necromancer',
                'damage' => 1,
                'requirements' => ['necro_materia_1'],
            ],
            'necro_materia_2' => [
                'title' => 'Malediction',
                'slug' => 'necro-materia-2',
                'description' => 'Debloque le sort Malediction (poison)',
                'requiredPoints' => 20,
                'domain' => 'necromancer',
                'actions' => ['combat' => ['spell_slug' => 'plague-strike']],
                'requirements' => ['necro_damage_1'],
            ],
            'necro_materia_3' => [
                'title' => 'Moisson sombre',
                'slug' => 'necro-materia-3',
                'description' => 'Debloque la Moisson sombre — drain massif',
                'requiredPoints' => 30,
                'domain' => 'necromancer',
                'actions' => ['combat' => ['spell_slug' => 'dark-harvest']],
                'requirements' => ['necro_materia_2'],
            ],
        ];
    }

    // =========================================================================
    // DRUIDE (bete) — 4 skills existants conserves
    // =========================================================================
    private function getDruidSkills(): array
    {
        return [
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
        ];
    }

    // =========================================================================
    // FOUDROMANCIEN (air) — remplace white_wizard
    // =========================================================================
    private function getStormcallerSkills(): array
    {
        return [
            'storm_materia_1' => [
                'title' => 'Apprenti foudromancien',
                'slug' => 'storm-materia-1',
                'description' => 'Debloque le sort Lame d\'air',
                'requiredPoints' => 0,
                'domain' => 'stormcaller',
                'actions' => ['combat' => ['spell_slug' => 'wind-lame']],
            ],
            'storm_hit_1' => [
                'title' => 'Precision du vent',
                'slug' => 'storm-hit-1',
                'description' => 'Augmente la precision des sorts',
                'requiredPoints' => 10,
                'domain' => 'stormcaller',
                'hit' => 2,
                'requirements' => ['storm_materia_1'],
            ],
            'storm_materia_2' => [
                'title' => 'Tornade',
                'slug' => 'storm-materia-2',
                'description' => 'Debloque le sort Tornade',
                'requiredPoints' => 20,
                'domain' => 'stormcaller',
                'actions' => ['combat' => ['spell_slug' => 'tornado']],
                'requirements' => ['storm_hit_1'],
            ],
            'storm_materia_3' => [
                'title' => 'Ouragan',
                'slug' => 'storm-materia-3',
                'description' => 'Debloque le sort Ouragan — sort ultime',
                'requiredPoints' => 30,
                'domain' => 'stormcaller',
                'actions' => ['combat' => ['spell_slug' => 'hurricane']],
                'requirements' => ['storm_materia_2'],
            ],
        ];
    }

    // =========================================================================
    // MINEUR (terre/recolte) — skills existants conserves
    // =========================================================================
    private function getMinerSkills(): array
    {
        return [
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
        ];
    }

    // =========================================================================
    // HERBORISTE (bete/recolte) — skills existants conserves
    // =========================================================================
    private function getHerbalistSkills(): array
    {
        return [
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
