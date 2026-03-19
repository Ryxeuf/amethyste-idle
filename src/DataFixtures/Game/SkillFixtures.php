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
            $this->getBerserkerSkills(),
            $this->getArtificerSkills(),
            $this->getHydromancerSkills(),
            $this->getHealerSkills(),
            $this->getTidecallerSkills(),
            $this->getSoldierSkills(),
            $this->getGeomancerSkills(),
            $this->getDefenderSkills(),
            $this->getGuardianSkills(),
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
                'title' => 'Materia : Boule de feu',
                'slug' => 'pyro-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Boule de feu',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-ball']],
            ],
            'pyro_apprenti_2' => [
                'title' => 'Materia : Flammeche',
                'slug' => 'pyro-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Flammeche',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'flame']],
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
                'title' => 'Materia : Mur de feu',
                'slug' => 'pyro-rang2-3',
                'description' => 'Permet d\'utiliser la materia Mur de feu',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-wall']],
                'requirements' => ['pyro_apprenti_2'],
            ],
            'pyro_rang2_4' => [
                'title' => 'Materia : Toucher brulant',
                'slug' => 'pyro-rang2-4',
                'description' => 'Permet d\'utiliser la materia Toucher brulant',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'burning-touch']],
                'requirements' => ['pyro_apprenti_2'],
            ],

            // Rang 3 (20-50 pts) — 4 skills
            'pyro_rang3_1' => [
                'title' => 'Materia : Pluie de flammes',
                'slug' => 'pyro-rang3-1',
                'description' => 'Permet d\'utiliser la materia Pluie de flammes (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'flame-rain']],
                'requirements' => ['pyro_rang2_1', 'pyro_rang2_2'],
            ],
            'pyro_rang3_2' => [
                'title' => 'Materia : Nova de feu',
                'slug' => 'pyro-rang3-2',
                'description' => 'Permet d\'utiliser la materia Nova de feu',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-nova']],
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
                'title' => 'Materia : Flamme du phenix',
                'slug' => 'pyro-rang3-4',
                'description' => 'Permet d\'utiliser la materia Flamme du phenix (degats + soin)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'phoenix-flame']],
                'requirements' => ['pyro_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'pyro_rang4_1' => [
                'title' => 'Materia : Inferno',
                'slug' => 'pyro-rang4-1',
                'description' => 'Permet d\'utiliser la materia Inferno — devastation totale',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'inferno']],
                'requirements' => ['pyro_rang3_1', 'pyro_rang3_2'],
            ],
            'pyro_rang4_2' => [
                'title' => 'Materia : Souffle du dragon',
                'slug' => 'pyro-rang4-2',
                'description' => 'Permet d\'utiliser la materia Souffle du dragon (AoE)',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'dragon-breath']],
                'requirements' => ['pyro_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'pyro_rang5_1' => [
                'title' => 'Materia : Eruption volcanique',
                'slug' => 'pyro-rang5-1',
                'description' => 'Permet d\'utiliser la materia Eruption volcanique',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'volcanic-eruption']],
                'requirements' => ['pyro_rang4_1', 'pyro_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // BERSERKER (feu) — 15 skills, rage et CaC devastateur
    // =========================================================================
    private function getBerserkerSkills(): array
    {
        $d = 'berserker';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'berserk_apprenti_1' => [
                'title' => 'Materia : Flamme de rage',
                'slug' => 'berserk-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Flamme de rage',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rage-flame']],
            ],
            'berserk_apprenti_2' => [
                'title' => 'Materia : Toucher brulant',
                'slug' => 'berserk-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Toucher brulant',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'burning-touch']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'berserk_rang2_1' => [
                'title' => 'Brutalite',
                'slug' => 'berserk-rang2-1',
                'description' => 'Augmente les degats physiques',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['berserk_apprenti_1'],
            ],
            'berserk_rang2_2' => [
                'title' => 'Coups sauvages',
                'slug' => 'berserk-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['berserk_apprenti_1'],
            ],
            'berserk_rang2_3' => [
                'title' => 'Materia : Vague de chaleur',
                'slug' => 'berserk-rang2-3',
                'description' => 'Permet d\'utiliser la materia Vague de chaleur',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'heat-wave']],
                'requirements' => ['berserk_apprenti_2'],
            ],
            'berserk_rang2_4' => [
                'title' => 'Peau epaisse',
                'slug' => 'berserk-rang2-4',
                'description' => 'Augmente les points de vie',
                'requiredPoints' => 15,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['berserk_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'berserk_rang3_1' => [
                'title' => 'Materia : Charge enflammee',
                'slug' => 'berserk-rang3-1',
                'description' => 'Permet d\'utiliser la materia Charge enflammee',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'berserk-charge']],
                'requirements' => ['berserk_rang2_1', 'berserk_rang2_2'],
            ],
            'berserk_rang3_2' => [
                'title' => 'Materia : Combustion',
                'slug' => 'berserk-rang3-2',
                'description' => 'Permet d\'utiliser la materia Combustion',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'combustion']],
                'requirements' => ['berserk_rang2_3'],
            ],
            'berserk_rang3_3' => [
                'title' => 'Rage interieure',
                'slug' => 'berserk-rang3-3',
                'description' => 'Augmente les degats et le critique',
                'requiredPoints' => 30,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['berserk_rang2_4'],
            ],
            'berserk_rang3_4' => [
                'title' => 'Materia : Fouet de feu',
                'slug' => 'berserk-rang3-4',
                'description' => 'Permet d\'utiliser la materia Fouet de feu',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-whip']],
                'requirements' => ['berserk_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'berserk_rang4_1' => [
                'title' => 'Materia : Frappe de furie',
                'slug' => 'berserk-rang4-1',
                'description' => 'Permet d\'utiliser la materia Frappe de furie — degats devastateurs',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fury-strike']],
                'requirements' => ['berserk_rang3_1', 'berserk_rang3_2'],
            ],
            'berserk_rang4_2' => [
                'title' => 'Materia : Frappe meteorique',
                'slug' => 'berserk-rang4-2',
                'description' => 'Permet d\'utiliser la materia Frappe meteorique',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'meteor-strike']],
                'requirements' => ['berserk_rang3_3', 'berserk_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'berserk_rang5_1' => [
                'title' => 'Materia : Furie sanguinaire',
                'slug' => 'berserk-rang5-1',
                'description' => 'Permet d\'utiliser la materia Furie sanguinaire',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'blood-fury']],
                'requirements' => ['berserk_rang4_1', 'berserk_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // ARTIFICIER (feu) — 15 skills, pieges et explosifs
    // =========================================================================
    private function getArtificerSkills(): array
    {
        $d = 'artificer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'artif_apprenti_1' => [
                'title' => 'Materia : Flammeche',
                'slug' => 'artif-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Flammeche',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'flame']],
            ],
            'artif_apprenti_2' => [
                'title' => 'Materia : Bouclier d\'etincelles',
                'slug' => 'artif-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'etincelles',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ember-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'artif_rang2_1' => [
                'title' => 'Materia : Piege incendiaire',
                'slug' => 'artif-rang2-1',
                'description' => 'Permet d\'utiliser la materia Piege incendiaire',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-trap']],
                'requirements' => ['artif_apprenti_1'],
            ],
            'artif_rang2_2' => [
                'title' => 'Precision mecanique',
                'slug' => 'artif-rang2-2',
                'description' => 'Augmente la precision des attaques',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['artif_apprenti_1'],
            ],
            'artif_rang2_3' => [
                'title' => 'Materia : Bombe flash',
                'slug' => 'artif-rang2-3',
                'description' => 'Permet d\'utiliser la materia Bombe flash (paralysie)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'flash-bomb']],
                'requirements' => ['artif_apprenti_2'],
            ],
            'artif_rang2_4' => [
                'title' => 'Blindage',
                'slug' => 'artif-rang2-4',
                'description' => 'Augmente les points de vie',
                'requiredPoints' => 15,
                'domain' => $d,
                'life' => 3,
                'requirements' => ['artif_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'artif_rang3_1' => [
                'title' => 'Materia : Mine explosive',
                'slug' => 'artif-rang3-1',
                'description' => 'Permet d\'utiliser la materia Mine explosive (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'explosive-mine']],
                'requirements' => ['artif_rang2_1', 'artif_rang2_2'],
            ],
            'artif_rang3_2' => [
                'title' => 'Materia : Nova de feu',
                'slug' => 'artif-rang3-2',
                'description' => 'Permet d\'utiliser la materia Nova de feu',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-nova']],
                'requirements' => ['artif_rang2_3'],
            ],
            'artif_rang3_3' => [
                'title' => 'Degats amplifies',
                'slug' => 'artif-rang3-3',
                'description' => 'Augmente les degats des pieges',
                'requiredPoints' => 30,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['artif_rang2_4'],
            ],
            'artif_rang3_4' => [
                'title' => 'Materia : Mur de feu',
                'slug' => 'artif-rang3-4',
                'description' => 'Permet d\'utiliser la materia Mur de feu',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'fire-wall']],
                'requirements' => ['artif_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'artif_rang4_1' => [
                'title' => 'Materia : Pluie de flammes',
                'slug' => 'artif-rang4-1',
                'description' => 'Permet d\'utiliser la materia Pluie de flammes (AoE)',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'flame-rain']],
                'requirements' => ['artif_rang3_1', 'artif_rang3_2'],
            ],
            'artif_rang4_2' => [
                'title' => 'Materia : Souffle du dragon',
                'slug' => 'artif-rang4-2',
                'description' => 'Permet d\'utiliser la materia Souffle du dragon',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'dragon-breath']],
                'requirements' => ['artif_rang3_3', 'artif_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'artif_rang5_1' => [
                'title' => 'Materia : Barrage d\'artillerie',
                'slug' => 'artif-rang5-1',
                'description' => 'Permet d\'utiliser la materia Barrage d\'artillerie',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'artillery-barrage']],
                'requirements' => ['artif_rang4_1', 'artif_rang4_2'],
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
                'title' => 'Materia : Frappe puissante',
                'slug' => 'soldier-apprentice',
                'description' => 'Permet d\'utiliser la materia Frappe puissante',
                'requiredPoints' => 0,
                'domain' => 'soldier',
                'actions' => ['materia' => ['unlock' => 'sharp-blade']],
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
                'title' => 'Materia : Charge',
                'slug' => 'soldier-materia-1',
                'description' => 'Permet d\'utiliser la materia Charge',
                'requiredPoints' => 10,
                'domain' => 'soldier',
                'actions' => ['materia' => ['unlock' => 'iron-fist']],
                'requirements' => ['soldier_apprentice'],
            ],
            'soldier_materia_2' => [
                'title' => 'Materia : Tourbillon d\'epee',
                'slug' => 'soldier-materia-2',
                'description' => "Permet d\'utiliser la materia Tourbillon d'epee (AoE)",
                'requiredPoints' => 25,
                'domain' => 'soldier',
                'actions' => ['materia' => ['unlock' => 'blade-dance']],
                'requirements' => ['soldier_materia_1', 'soldier_damage_1'],
            ],
        ];
    }

    // =========================================================================
    // HYDROMANCIEN (eau) — 13 skills, mage offensif eau/glace
    // =========================================================================
    private function getHydromancerSkills(): array
    {
        $d = 'hydromancer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'hydro_apprenti_1' => [
                'title' => 'Materia : Jet d\'eau',
                'slug' => 'hydro-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Jet d\'eau',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-jet']],
            ],
            'hydro_apprenti_2' => [
                'title' => 'Materia : Toucher glace',
                'slug' => 'hydro-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Toucher glace',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'frozen-touch']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'hydro_rang2_1' => [
                'title' => 'Efficacite de l\'eau',
                'slug' => 'hydro-rang2-1',
                'description' => 'Augmente les degats des sorts d\'eau',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['hydro_apprenti_1'],
            ],
            'hydro_rang2_2' => [
                'title' => 'Points faibles',
                'slug' => 'hydro-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['hydro_apprenti_1'],
            ],
            'hydro_rang2_3' => [
                'title' => 'Materia : Trait de givre',
                'slug' => 'hydro-rang2-3',
                'description' => 'Permet d\'utiliser la materia Trait de givre (paralysie)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'frost-bolt']],
                'requirements' => ['hydro_apprenti_2'],
            ],
            'hydro_rang2_4' => [
                'title' => 'Materia : Lance de glace',
                'slug' => 'hydro-rang2-4',
                'description' => 'Permet d\'utiliser la materia Lance de glace',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ice-lance']],
                'requirements' => ['hydro_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'hydro_rang3_1' => [
                'title' => 'Materia : Tempete de glace',
                'slug' => 'hydro-rang3-1',
                'description' => 'Permet d\'utiliser la materia Tempete de glace (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ice-storm']],
                'requirements' => ['hydro_rang2_1', 'hydro_rang2_2'],
            ],
            'hydro_rang3_2' => [
                'title' => 'Materia : Prison d\'eau',
                'slug' => 'hydro-rang3-2',
                'description' => 'Permet d\'utiliser la materia Prison d\'eau (paralysie)',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-prison']],
                'requirements' => ['hydro_rang2_3'],
            ],
            'hydro_rang3_3' => [
                'title' => 'Precision glaciale',
                'slug' => 'hydro-rang3-3',
                'description' => 'Augmente la precision des sorts d\'eau',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['hydro_rang2_4'],
            ],
            'hydro_rang3_4' => [
                'title' => 'Materia : Raz-de-maree',
                'slug' => 'hydro-rang3-4',
                'description' => 'Permet d\'utiliser la materia Raz-de-maree (AoE)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tidal-wave']],
                'requirements' => ['hydro_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'hydro_rang4_1' => [
                'title' => 'Materia : Maelstrom',
                'slug' => 'hydro-rang4-1',
                'description' => 'Permet d\'utiliser la materia Maelstrom — tourbillon devastateur',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'maelstrom']],
                'requirements' => ['hydro_rang3_1', 'hydro_rang3_2'],
            ],
            'hydro_rang4_2' => [
                'title' => 'Materia : Bulle protectrice',
                'slug' => 'hydro-rang4-2',
                'description' => 'Permet d\'utiliser la materia Bulle protectrice',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'bubble-shield']],
                'requirements' => ['hydro_rang3_3', 'hydro_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'hydro_rang5_1' => [
                'title' => 'Materia : Tsunami',
                'slug' => 'hydro-rang5-1',
                'description' => 'Permet d\'utiliser la materia Tsunami',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tsunami']],
                'requirements' => ['hydro_rang4_1', 'hydro_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // GUERISSEUR (eau) — 13 skills, soigneur complet
    // =========================================================================
    private function getHealerSkills(): array
    {
        $d = 'healer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'healer_materia_1' => [
                'title' => 'Materia : Soin mineur',
                'slug' => 'healer-materia-1',
                'description' => 'Permet d\'utiliser la materia Soin mineur',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-heal']],
            ],
            'healer_apprenti_2' => [
                'title' => 'Materia : Soin aquatique',
                'slug' => 'healer-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Soin aquatique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-heal']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'healer_heal_1' => [
                'title' => 'Main guerisseuse',
                'slug' => 'healer-heal-1',
                'description' => 'Augmente la puissance des soins',
                'requiredPoints' => 10,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['healer_materia_1'],
            ],
            'healer_rang2_2' => [
                'title' => 'Concentration',
                'slug' => 'healer-rang2-2',
                'description' => 'Augmente la precision des soins',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['healer_materia_1'],
            ],
            'healer_rang2_3' => [
                'title' => 'Materia : Vague de guerison',
                'slug' => 'healer-rang2-3',
                'description' => 'Permet d\'utiliser la materia Vague de guerison',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'healing-wave']],
                'requirements' => ['healer_apprenti_2'],
            ],
            'healer_rang2_4' => [
                'title' => 'Vitalite',
                'slug' => 'healer-rang2-4',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 15,
                'domain' => $d,
                'life' => 3,
                'requirements' => ['healer_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'healer_materia_2' => [
                'title' => 'Materia : Regeneration',
                'slug' => 'healer-materia-2',
                'description' => 'Permet d\'utiliser la materia Regeneration (HoT)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rejuvenation']],
                'requirements' => ['healer_heal_1', 'healer_rang2_2'],
            ],
            'healer_rang3_2' => [
                'title' => 'Materia : Voile de brume',
                'slug' => 'healer-rang3-2',
                'description' => 'Permet d\'utiliser la materia Voile de brume (soin + bouclier)',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mist-veil']],
                'requirements' => ['healer_rang2_3'],
            ],
            'healer_rang3_3' => [
                'title' => 'Materia : Afflux de vitalite',
                'slug' => 'healer-rang3-3',
                'description' => 'Permet d\'utiliser la materia Afflux de vitalite',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vitality-surge']],
                'requirements' => ['healer_rang2_4'],
            ],
            'healer_rang3_4' => [
                'title' => 'Materia : Bouclier de vie',
                'slug' => 'healer-rang3-4',
                'description' => 'Permet d\'utiliser la materia Bouclier de vie',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-shield']],
                'requirements' => ['healer_materia_2'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'healer_materia_3' => [
                'title' => 'Materia : Benediction divine',
                'slug' => 'healer-materia-3',
                'description' => 'Permet d\'utiliser la materia Benediction divine — soin puissant',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-blessing']],
                'requirements' => ['healer_materia_2', 'healer_rang3_2'],
            ],
            'healer_rang4_2' => [
                'title' => 'Materia : Benediction de l\'ocean',
                'slug' => 'healer-rang4-2',
                'description' => 'Permet d\'utiliser la materia Benediction de l\'ocean (regeneration)',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ocean-blessing']],
                'requirements' => ['healer_rang3_3', 'healer_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'healer_rang5_1' => [
                'title' => 'Materia : Benediction celeste',
                'slug' => 'healer-rang5-1',
                'description' => 'Permet d\'utiliser la materia Benediction celeste',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'celestial-blessing']],
                'requirements' => ['healer_materia_3', 'healer_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // MAREMANCIEN (eau) — 13 skills, maree et support hybride
    // =========================================================================
    private function getTidecallerSkills(): array
    {
        $d = 'tidecaller';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'tide_apprenti_1' => [
                'title' => 'Materia : Maree montante',
                'slug' => 'tide-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Maree montante',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rising-tide']],
            ],
            'tide_apprenti_2' => [
                'title' => 'Materia : Bouclier aquatique',
                'slug' => 'tide-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier aquatique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'aqua-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'tide_rang2_1' => [
                'title' => 'Force des marees',
                'slug' => 'tide-rang2-1',
                'description' => 'Augmente les degats des sorts d\'eau',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['tide_apprenti_1'],
            ],
            'tide_rang2_2' => [
                'title' => 'Materia : Torrent',
                'slug' => 'tide-rang2-2',
                'description' => 'Permet d\'utiliser la materia Torrent',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'torrent']],
                'requirements' => ['tide_apprenti_1'],
            ],
            'tide_rang2_3' => [
                'title' => 'Materia : Soin aquatique',
                'slug' => 'tide-rang2-3',
                'description' => 'Permet d\'utiliser la materia Soin aquatique',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-heal']],
                'requirements' => ['tide_apprenti_2'],
            ],
            'tide_rang2_4' => [
                'title' => 'Resilience marine',
                'slug' => 'tide-rang2-4',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 15,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['tide_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'tide_rang3_1' => [
                'title' => 'Materia : Raz-de-maree',
                'slug' => 'tide-rang3-1',
                'description' => 'Permet d\'utiliser la materia Raz-de-maree (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tidal-wave']],
                'requirements' => ['tide_rang2_1', 'tide_rang2_2'],
            ],
            'tide_rang3_2' => [
                'title' => 'Materia : Voile de brume',
                'slug' => 'tide-rang3-2',
                'description' => 'Permet d\'utiliser la materia Voile de brume (soin + bouclier)',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mist-veil']],
                'requirements' => ['tide_rang2_3'],
            ],
            'tide_rang3_3' => [
                'title' => 'Guerison des eaux',
                'slug' => 'tide-rang3-3',
                'description' => 'Augmente la puissance des soins',
                'requiredPoints' => 30,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['tide_rang2_4'],
            ],
            'tide_rang3_4' => [
                'title' => 'Materia : Prison d\'eau',
                'slug' => 'tide-rang3-4',
                'description' => 'Permet d\'utiliser la materia Prison d\'eau (paralysie)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'water-prison']],
                'requirements' => ['tide_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'tide_rang4_1' => [
                'title' => 'Materia : Tempete de glace',
                'slug' => 'tide-rang4-1',
                'description' => 'Permet d\'utiliser la materia Tempete de glace (AoE)',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ice-storm']],
                'requirements' => ['tide_rang3_1', 'tide_rang3_2'],
            ],
            'tide_rang4_2' => [
                'title' => 'Materia : Benediction de l\'ocean',
                'slug' => 'tide-rang4-2',
                'description' => 'Permet d\'utiliser la materia Benediction de l\'ocean (regeneration)',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ocean-blessing']],
                'requirements' => ['tide_rang3_3', 'tide_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'tide_rang5_1' => [
                'title' => 'Materia : Maelstrom',
                'slug' => 'tide-rang5-1',
                'description' => 'Permet d\'utiliser la materia Maelstrom',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'maelstrom']],
                'requirements' => ['tide_rang4_1', 'tide_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // GEOMANCIEN (terre) — 15 skills, DPS magique terre, degats de zone
    // =========================================================================
    private function getGeomancerSkills(): array
    {
        $d = 'geomancer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'geo_apprenti_1' => [
                'title' => 'Materia : Jet de cailloux',
                'slug' => 'geo-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Jet de cailloux',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-throw']],
            ],
            'geo_apprenti_2' => [
                'title' => 'Materia : Sables mouvants',
                'slug' => 'geo-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Sables mouvants',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'quicksand']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'geo_rang2_1' => [
                'title' => 'Force tellurique',
                'slug' => 'geo-rang2-1',
                'description' => 'Augmente les degats des sorts de terre',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['geo_apprenti_1'],
            ],
            'geo_rang2_2' => [
                'title' => 'Fissures precises',
                'slug' => 'geo-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['geo_apprenti_1'],
            ],
            'geo_rang2_3' => [
                'title' => 'Materia : Pic de terre',
                'slug' => 'geo-rang2-3',
                'description' => 'Permet d\'utiliser la materia Pic de terre',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earth-spike']],
                'requirements' => ['geo_apprenti_2'],
            ],
            'geo_rang2_4' => [
                'title' => 'Materia : Pics de pierre',
                'slug' => 'geo-rang2-4',
                'description' => 'Permet d\'utiliser la materia Pics de pierre',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-spikes']],
                'requirements' => ['geo_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'geo_rang3_1' => [
                'title' => 'Materia : Tremblement de terre',
                'slug' => 'geo-rang3-1',
                'description' => 'Permet d\'utiliser la materia Tremblement de terre (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earthquake']],
                'requirements' => ['geo_rang2_1', 'geo_rang2_2'],
            ],
            'geo_rang3_2' => [
                'title' => 'Materia : Glissement de terrain',
                'slug' => 'geo-rang3-2',
                'description' => 'Permet d\'utiliser la materia Glissement de terrain',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'landslide']],
                'requirements' => ['geo_rang2_3'],
            ],
            'geo_rang3_3' => [
                'title' => 'Precision minerale',
                'slug' => 'geo-rang3-3',
                'description' => 'Augmente la precision des sorts de terre',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['geo_rang2_4'],
            ],
            'geo_rang3_4' => [
                'title' => 'Materia : Lancer de rocher',
                'slug' => 'geo-rang3-4',
                'description' => 'Permet d\'utiliser la materia Lancer de rocher',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'boulder-throw']],
                'requirements' => ['geo_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'geo_rang4_1' => [
                'title' => 'Materia : Petrification',
                'slug' => 'geo-rang4-1',
                'description' => 'Permet d\'utiliser la materia Petrification — paralyse la cible',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'petrification']],
                'requirements' => ['geo_rang3_1', 'geo_rang3_2'],
            ],
            'geo_rang4_2' => [
                'title' => 'Materia : Lance de cristal',
                'slug' => 'geo-rang4-2',
                'description' => 'Permet d\'utiliser la materia Lance de cristal',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crystal-spear']],
                'requirements' => ['geo_rang3_3', 'geo_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'geo_rang5_1' => [
                'title' => 'Materia : Deplacement tectonique',
                'slug' => 'geo-rang5-1',
                'description' => 'Permet d\'utiliser la materia Deplacement tectonique',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tectonic-shift']],
                'requirements' => ['geo_rang4_1', 'geo_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // DEFENSEUR (terre) — 15 skills, tank absorption et murs
    // =========================================================================
    private function getDefenderSkills(): array
    {
        $d = 'defender';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'defender_apprenti_1' => [
                'title' => 'Materia : Parade',
                'slug' => 'defender-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Parade',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rock-armor']],
            ],
            'defender_apprenti_2' => [
                'title' => 'Materia : Bouclier terreux',
                'slug' => 'defender-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier terreux',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earth-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'defender_rang2_1' => [
                'title' => 'Constitution',
                'slug' => 'defender-rang2-1',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['defender_apprenti_1'],
            ],
            'defender_rang2_2' => [
                'title' => 'Riposte',
                'slug' => 'defender-rang2-2',
                'description' => 'Augmente les degats de contre-attaque',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['defender_apprenti_1'],
            ],
            'defender_rang2_3' => [
                'title' => 'Materia : Peau de pierre',
                'slug' => 'defender-rang2-3',
                'description' => 'Permet d\'utiliser la materia Peau de pierre — protection renforcee',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-skin']],
                'requirements' => ['defender_apprenti_2'],
            ],
            'defender_rang2_4' => [
                'title' => 'Materia : Pics de pierre',
                'slug' => 'defender-rang2-4',
                'description' => 'Permet d\'utiliser la materia Pics de pierre — riposte epineuse',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-spikes']],
                'requirements' => ['defender_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'defender_rang3_1' => [
                'title' => 'Materia : Mur de fer',
                'slug' => 'defender-rang3-1',
                'description' => 'Permet d\'utiliser la materia Mur de fer — defense ultime',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-wall']],
                'requirements' => ['defender_rang2_1', 'defender_rang2_2'],
            ],
            'defender_rang3_2' => [
                'title' => 'Materia : Force de la montagne',
                'slug' => 'defender-rang3-2',
                'description' => 'Permet d\'utiliser la materia Force de la montagne (degats + soin)',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mountain-strength']],
                'requirements' => ['defender_rang2_3'],
            ],
            'defender_rang3_3' => [
                'title' => 'Endurance de fer',
                'slug' => 'defender-rang3-3',
                'description' => 'Augmente les points de vie et la precision',
                'requiredPoints' => 30,
                'domain' => $d,
                'life' => 5,
                'hit' => 1,
                'requirements' => ['defender_rang2_4'],
            ],
            'defender_rang3_4' => [
                'title' => 'Materia : Croissance cristalline',
                'slug' => 'defender-rang3-4',
                'description' => 'Permet d\'utiliser la materia Croissance cristalline (armure)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crystal-growth']],
                'requirements' => ['defender_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'defender_rang4_1' => [
                'title' => 'Materia : Tremblement de terre',
                'slug' => 'defender-rang4-1',
                'description' => 'Permet d\'utiliser la materia Tremblement de terre — repousse les ennemis',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earthquake']],
                'requirements' => ['defender_rang3_1', 'defender_rang3_2'],
            ],
            'defender_rang4_2' => [
                'title' => 'Materia : Lancer de rocher',
                'slug' => 'defender-rang4-2',
                'description' => 'Permet d\'utiliser la materia Lancer de rocher — projectile lourd',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'boulder-throw']],
                'requirements' => ['defender_rang3_3', 'defender_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'defender_rang5_1' => [
                'title' => 'Materia : Petrification',
                'slug' => 'defender-rang5-1',
                'description' => 'Permet d\'utiliser la materia Petrification — defense absolue',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'petrification']],
                'requirements' => ['defender_rang4_1', 'defender_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // GARDIEN (terre) — 15 skills, tank/support, protection de groupe
    // =========================================================================
    private function getGuardianSkills(): array
    {
        $d = 'guardian';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'guardian_apprenti_1' => [
                'title' => 'Materia : Bouclier partage',
                'slug' => 'guardian-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Bouclier partage',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shared-shield']],
            ],
            'guardian_apprenti_2' => [
                'title' => 'Materia : Parade',
                'slug' => 'guardian-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Parade',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rock-armor']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'guardian_rang2_1' => [
                'title' => 'Robustesse',
                'slug' => 'guardian-rang2-1',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['guardian_apprenti_1'],
            ],
            'guardian_rang2_2' => [
                'title' => 'Materia : Benediction de la terre',
                'slug' => 'guardian-rang2-2',
                'description' => 'Permet d\'utiliser la materia Benediction de la terre (soin)',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earth-blessing']],
                'requirements' => ['guardian_apprenti_1'],
            ],
            'guardian_rang2_3' => [
                'title' => 'Materia : Bouclier terreux',
                'slug' => 'guardian-rang2-3',
                'description' => 'Permet d\'utiliser la materia Bouclier terreux',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'earth-shield']],
                'requirements' => ['guardian_apprenti_2'],
            ],
            'guardian_rang2_4' => [
                'title' => 'Vigilance',
                'slug' => 'guardian-rang2-4',
                'description' => 'Augmente la precision des protections',
                'requiredPoints' => 15,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['guardian_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'guardian_rang3_1' => [
                'title' => 'Materia : Peau de pierre',
                'slug' => 'guardian-rang3-1',
                'description' => 'Permet d\'utiliser la materia Peau de pierre — armure renforcee',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-skin']],
                'requirements' => ['guardian_rang2_1', 'guardian_rang2_2'],
            ],
            'guardian_rang3_2' => [
                'title' => 'Materia : Force de la montagne',
                'slug' => 'guardian-rang3-2',
                'description' => 'Permet d\'utiliser la materia Force de la montagne',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mountain-strength']],
                'requirements' => ['guardian_rang2_3'],
            ],
            'guardian_rang3_3' => [
                'title' => 'Protection innebreanlable',
                'slug' => 'guardian-rang3-3',
                'description' => 'Augmente les points de vie et le soin',
                'requiredPoints' => 30,
                'domain' => $d,
                'life' => 5,
                'heal' => 1,
                'requirements' => ['guardian_rang2_4'],
            ],
            'guardian_rang3_4' => [
                'title' => 'Materia : Mur de fer',
                'slug' => 'guardian-rang3-4',
                'description' => 'Permet d\'utiliser la materia Mur de fer (bouclier puissant)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-wall']],
                'requirements' => ['guardian_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'guardian_rang4_1' => [
                'title' => 'Materia : Croissance cristalline',
                'slug' => 'guardian-rang4-1',
                'description' => 'Permet d\'utiliser la materia Croissance cristalline — armure de cristal',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crystal-growth']],
                'requirements' => ['guardian_rang3_1', 'guardian_rang3_2'],
            ],
            'guardian_rang4_2' => [
                'title' => 'Materia : Lance de cristal',
                'slug' => 'guardian-rang4-2',
                'description' => 'Permet d\'utiliser la materia Lance de cristal — represailles',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crystal-spear']],
                'requirements' => ['guardian_rang3_3', 'guardian_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'guardian_rang5_1' => [
                'title' => 'Materia : Bastion',
                'slug' => 'guardian-rang5-1',
                'description' => 'Permet d\'utiliser la materia Bastion — protection ultime du groupe',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'bastion']],
                'requirements' => ['guardian_rang4_1', 'guardian_rang4_2'],
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
                'title' => 'Materia : Drain de vie',
                'slug' => 'necro-materia-1',
                'description' => 'Permet d\'utiliser la materia Drain de vie',
                'requiredPoints' => 0,
                'domain' => 'necromancer',
                'actions' => ['materia' => ['unlock' => 'soul-drain']],
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
                'title' => 'Materia : Malediction',
                'slug' => 'necro-materia-2',
                'description' => 'Permet d\'utiliser la materia Malediction (poison)',
                'requiredPoints' => 20,
                'domain' => 'necromancer',
                'actions' => ['materia' => ['unlock' => 'plague-strike']],
                'requirements' => ['necro_damage_1'],
            ],
            'necro_materia_3' => [
                'title' => 'Materia : Moisson sombre',
                'slug' => 'necro-materia-3',
                'description' => 'Permet d\'utiliser la materia Moisson sombre — drain massif',
                'requiredPoints' => 30,
                'domain' => 'necromancer',
                'actions' => ['materia' => ['unlock' => 'dark-harvest']],
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
                'title' => 'Materia : Liane',
                'slug' => 'druid-materia-1',
                'description' => 'Permet d\'utiliser la materia Liane',
                'requiredPoints' => 0,
                'domain' => 'druid',
                'actions' => ['materia' => ['unlock' => 'liana-whip']],
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
                'title' => 'Materia : Empoisonnement',
                'slug' => 'druid-materia-2',
                'description' => "Permet d'utiliser la materia Empoisonnement (DoT)",
                'requiredPoints' => 20,
                'domain' => 'druid',
                'actions' => ['materia' => ['unlock' => 'poison-cloud']],
                'requirements' => ['druid_heal_1'],
            ],
            'druid_materia_3' => [
                'title' => 'Materia : Appel de la foret',
                'slug' => 'druid-materia-3',
                'description' => "Permet d'utiliser la materia Appel de la foret",
                'requiredPoints' => 30,
                'domain' => 'druid',
                'actions' => ['materia' => ['unlock' => 'nature-wrath']],
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
                'title' => 'Materia : Lame d\'air',
                'slug' => 'storm-materia-1',
                'description' => 'Permet d\'utiliser la materia Lame d\'air',
                'requiredPoints' => 0,
                'domain' => 'stormcaller',
                'actions' => ['materia' => ['unlock' => 'wind-lame']],
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
                'title' => 'Materia : Tornade',
                'slug' => 'storm-materia-2',
                'description' => 'Permet d\'utiliser la materia Tornade',
                'requiredPoints' => 20,
                'domain' => 'stormcaller',
                'actions' => ['materia' => ['unlock' => 'tornado']],
                'requirements' => ['storm_hit_1'],
            ],
            'storm_materia_3' => [
                'title' => 'Materia : Ouragan',
                'slug' => 'storm-materia-3',
                'description' => 'Permet d\'utiliser la materia Ouragan — sort ultime',
                'requiredPoints' => 30,
                'domain' => 'stormcaller',
                'actions' => ['materia' => ['unlock' => 'hurricane']],
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
