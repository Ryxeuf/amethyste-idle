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
            $this->getArcherSkills(),
            $this->getWandererSkills(),
            $this->getMinerSkills(),
            $this->getHerbalistSkills(),
            $this->getFishermanSkills(),
            $this->getSkinnerSkills(),
            $this->getBlacksmithSkills(),
            $this->getLeatherworkerSkills(),
            $this->getAlchimistSkills(),
            $this->getJewellerSkills(),
            $this->getSharedSkills(),
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
    // FOUDROMANCIEN (air) — 13 skills, mage foudre/vent offensif
    // =========================================================================
    private function getStormcallerSkills(): array
    {
        $d = 'stormcaller';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'storm_materia_1' => [
                'title' => 'Materia : Lame d\'air',
                'slug' => 'storm-materia-1',
                'description' => 'Permet d\'utiliser la materia Lame d\'air',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-lame']],
            ],
            'storm_apprenti_2' => [
                'title' => 'Materia : Bourrasque',
                'slug' => 'storm-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bourrasque',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'gust']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'storm_hit_1' => [
                'title' => 'Precision du vent',
                'slug' => 'storm-hit-1',
                'description' => 'Augmente la precision des sorts d\'air',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['storm_materia_1'],
            ],
            'storm_rang2_2' => [
                'title' => 'Efficacite de l\'air',
                'slug' => 'storm-rang2-2',
                'description' => 'Augmente les degats des sorts d\'air',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['storm_materia_1'],
            ],
            'storm_materia_2' => [
                'title' => 'Materia : Tornade',
                'slug' => 'storm-materia-2',
                'description' => 'Permet d\'utiliser la materia Tornade',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tornado']],
                'requirements' => ['storm_apprenti_2'],
            ],
            'storm_rang2_4' => [
                'title' => 'Materia : Souffle du vent',
                'slug' => 'storm-rang2-4',
                'description' => 'Permet d\'utiliser la materia Souffle du vent',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-blast']],
                'requirements' => ['storm_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'storm_rang3_1' => [
                'title' => 'Materia : Cyclone',
                'slug' => 'storm-rang3-1',
                'description' => 'Permet d\'utiliser la materia Cyclone (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'cyclone']],
                'requirements' => ['storm_hit_1', 'storm_rang2_2'],
            ],
            'storm_rang3_2' => [
                'title' => 'Materia : Faux de vent',
                'slug' => 'storm-rang3-2',
                'description' => 'Permet d\'utiliser la materia Faux de vent',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-scythe']],
                'requirements' => ['storm_materia_2'],
            ],
            'storm_rang3_3' => [
                'title' => 'Oeil du cyclone',
                'slug' => 'storm-rang3-3',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 30,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['storm_rang2_4'],
            ],
            'storm_rang3_4' => [
                'title' => 'Materia : Mur de vent',
                'slug' => 'storm-rang3-4',
                'description' => 'Permet d\'utiliser la materia Mur de vent (degats + soin)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-wall']],
                'requirements' => ['storm_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'storm_rang4_1' => [
                'title' => 'Materia : Tempete',
                'slug' => 'storm-rang4-1',
                'description' => 'Permet d\'utiliser la materia Tempete — devastation aerienne',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tempest']],
                'requirements' => ['storm_rang3_1', 'storm_rang3_2'],
            ],
            'storm_rang4_2' => [
                'title' => 'Materia : Lame de vide',
                'slug' => 'storm-rang4-2',
                'description' => 'Permet d\'utiliser la materia Lame de vide',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vacuum-blade']],
                'requirements' => ['storm_rang3_3', 'storm_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'storm_materia_3' => [
                'title' => 'Materia : Ouragan',
                'slug' => 'storm-materia-3',
                'description' => 'Permet d\'utiliser la materia Ouragan',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'hurricane']],
                'requirements' => ['storm_rang4_1', 'storm_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // ARCHER (air) — 13 skills, tir a distance et vent
    // =========================================================================
    private function getArcherSkills(): array
    {
        $d = 'archer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'archer_apprenti_1' => [
                'title' => 'Materia : Tir precis',
                'slug' => 'archer-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Tir precis',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'precise-shot']],
            ],
            'archer_apprenti_2' => [
                'title' => 'Materia : Ruee d\'air',
                'slug' => 'archer-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Ruee d\'air',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'air-dash']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'archer_rang2_1' => [
                'title' => 'Oeil de faucon',
                'slug' => 'archer-rang2-1',
                'description' => 'Augmente la precision des tirs',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['archer_apprenti_1'],
            ],
            'archer_rang2_2' => [
                'title' => 'Fleche aceree',
                'slug' => 'archer-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['archer_apprenti_1'],
            ],
            'archer_rang2_3' => [
                'title' => 'Materia : Tranchant aerien',
                'slug' => 'archer-rang2-3',
                'description' => 'Permet d\'utiliser la materia Tranchant aerien',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'air-slash']],
                'requirements' => ['archer_apprenti_2'],
            ],
            'archer_rang2_4' => [
                'title' => 'Materia : Point de pression',
                'slug' => 'archer-rang2-4',
                'description' => 'Permet d\'utiliser la materia Point de pression',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'pressure-point']],
                'requirements' => ['archer_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'archer_rang3_1' => [
                'title' => 'Materia : Tir critique',
                'slug' => 'archer-rang3-1',
                'description' => 'Permet d\'utiliser la materia Tir critique',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'critical-shot']],
                'requirements' => ['archer_rang2_1', 'archer_rang2_2'],
            ],
            'archer_rang3_2' => [
                'title' => 'Materia : Courant d\'air',
                'slug' => 'archer-rang3-2',
                'description' => 'Permet d\'utiliser la materia Courant d\'air (degats + soin)',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'air-current']],
                'requirements' => ['archer_rang2_3'],
            ],
            'archer_rang3_3' => [
                'title' => 'Concentration mortelle',
                'slug' => 'archer-rang3-3',
                'description' => 'Augmente les degats et le critique',
                'requiredPoints' => 30,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['archer_rang2_4'],
            ],
            'archer_rang3_4' => [
                'title' => 'Materia : Pluie de fleches',
                'slug' => 'archer-rang3-4',
                'description' => 'Permet d\'utiliser la materia Pluie de fleches (AoE)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'arrow-rain']],
                'requirements' => ['archer_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'archer_rang4_1' => [
                'title' => 'Materia : Faux de vent',
                'slug' => 'archer-rang4-1',
                'description' => 'Permet d\'utiliser la materia Faux de vent',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-scythe']],
                'requirements' => ['archer_rang3_1', 'archer_rang3_2'],
            ],
            'archer_rang4_2' => [
                'title' => 'Materia : Lame de vide',
                'slug' => 'archer-rang4-2',
                'description' => 'Permet d\'utiliser la materia Lame de vide',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vacuum-blade']],
                'requirements' => ['archer_rang3_3', 'archer_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'archer_rang5_1' => [
                'title' => 'Materia : Fleche perforante',
                'slug' => 'archer-rang5-1',
                'description' => 'Permet d\'utiliser la materia Fleche perforante',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'piercing-arrow']],
                'requirements' => ['archer_rang4_1', 'archer_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // VAGABOND (air) — 13 skills, support vitesse et evasion
    // =========================================================================
    private function getWandererSkills(): array
    {
        $d = 'wanderer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'wander_apprenti_1' => [
                'title' => 'Materia : Hate',
                'slug' => 'wander-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Hate',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'haste']],
            ],
            'wander_apprenti_2' => [
                'title' => 'Materia : Bouclier de vent',
                'slug' => 'wander-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier de vent',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'wander_rang2_1' => [
                'title' => 'Agilite du vent',
                'slug' => 'wander-rang2-1',
                'description' => 'Augmente la precision des attaques',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['wander_apprenti_1'],
            ],
            'wander_rang2_2' => [
                'title' => 'Vitesse du vagabond',
                'slug' => 'wander-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['wander_apprenti_1'],
            ],
            'wander_rang2_3' => [
                'title' => 'Materia : Brise guerisseuse',
                'slug' => 'wander-rang2-3',
                'description' => 'Permet d\'utiliser la materia Brise guerisseuse',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'healing-breeze']],
                'requirements' => ['wander_apprenti_2'],
            ],
            'wander_rang2_4' => [
                'title' => 'Endurance du voyageur',
                'slug' => 'wander-rang2-4',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 15,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['wander_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'wander_rang3_1' => [
                'title' => 'Materia : Mirage',
                'slug' => 'wander-rang3-1',
                'description' => 'Permet d\'utiliser la materia Mirage (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'mirage']],
                'requirements' => ['wander_rang2_1', 'wander_rang2_2'],
            ],
            'wander_rang3_2' => [
                'title' => 'Materia : Courant d\'air',
                'slug' => 'wander-rang3-2',
                'description' => 'Permet d\'utiliser la materia Courant d\'air',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'air-current']],
                'requirements' => ['wander_rang2_3'],
            ],
            'wander_rang3_3' => [
                'title' => 'Souffle revitalisant',
                'slug' => 'wander-rang3-3',
                'description' => 'Augmente la puissance des soins',
                'requiredPoints' => 30,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['wander_rang2_4'],
            ],
            'wander_rang3_4' => [
                'title' => 'Materia : Benediction du vent',
                'slug' => 'wander-rang3-4',
                'description' => 'Permet d\'utiliser la materia Benediction du vent',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-blessing']],
                'requirements' => ['wander_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'wander_rang4_1' => [
                'title' => 'Materia : Mur de vent',
                'slug' => 'wander-rang4-1',
                'description' => 'Permet d\'utiliser la materia Mur de vent (degats + soin)',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wind-wall']],
                'requirements' => ['wander_rang3_1', 'wander_rang3_2'],
            ],
            'wander_rang4_2' => [
                'title' => 'Materia : Tempete',
                'slug' => 'wander-rang4-2',
                'description' => 'Permet d\'utiliser la materia Tempete',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'tempest']],
                'requirements' => ['wander_rang3_3', 'wander_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'wander_rang5_1' => [
                'title' => 'Materia : Zephyr',
                'slug' => 'wander-rang5-1',
                'description' => 'Permet d\'utiliser la materia Zephyr',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'zephyr']],
                'requirements' => ['wander_rang4_1', 'wander_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // MINEUR (terre/recolte) — 15 skills, extraction de minerais
    // =========================================================================
    private function getMinerSkills(): array
    {
        $d = 'miner';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'miner_ruby_xs' => [
                'slug' => 'miner-ruby-xs',
                'title' => 'Minage du rubis debutant',
                'description' => 'Permet de miner les filons de rubis basiques',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-xs']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'miner_iron_xs' => [
                'slug' => 'miner-iron-xs',
                'title' => 'Minage du fer debutant',
                'description' => 'Permet de miner les filons de fer basiques',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-xs']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'miner_ruby_s' => [
                'slug' => 'miner-ruby-s',
                'title' => 'Minage du rubis apprenti',
                'description' => 'Permet de miner les filons de rubis apprenti',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['miner_ruby_xs'],
            ],
            'miner_iron_s' => [
                'slug' => 'miner-iron-s',
                'title' => 'Minage du fer apprenti',
                'description' => 'Permet de miner les filons de fer apprenti',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['miner_iron_xs'],
            ],
            'miner_efficiency_1' => [
                'slug' => 'miner-efficiency-1',
                'title' => 'Pioche affutee',
                'description' => 'Augmente la vitesse d\'extraction des minerais',
                'requiredPoints' => 15,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['miner_ruby_xs'],
            ],
            'miner_gold_xs' => [
                'slug' => 'miner-gold-xs',
                'title' => 'Minage de l\'or debutant',
                'description' => 'Permet de miner les filons d\'or basiques',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-gold-xs']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['miner_iron_xs'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'miner_ruby_m' => [
                'slug' => 'miner-ruby-m',
                'title' => 'Minage du rubis avance',
                'description' => 'Permet de miner les filons de rubis avances',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-m']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['miner_ruby_s', 'miner_efficiency_1'],
            ],
            'miner_iron_m' => [
                'slug' => 'miner-iron-m',
                'title' => 'Minage du fer avance',
                'description' => 'Permet de miner les filons de fer avances',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-m']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['miner_iron_s'],
            ],
            'miner_gold_s' => [
                'slug' => 'miner-gold-s',
                'title' => 'Minage de l\'or apprenti',
                'description' => 'Permet de miner les filons d\'or apprenti',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-gold-s']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['miner_gold_xs'],
            ],
            'miner_yield_1' => [
                'slug' => 'miner-yield-1',
                'title' => 'Filon genereux',
                'description' => 'Chance de doubler les minerais extraits',
                'requiredPoints' => 40,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['miner_ruby_m'],
            ],
            'miner_iron_l' => [
                'slug' => 'miner-iron-l',
                'title' => 'Minage du fer expert',
                'description' => 'Permet de miner les filons de fer rares',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-l']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['miner_iron_m'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'miner_ruby_l' => [
                'slug' => 'miner-ruby-l',
                'title' => 'Minage du rubis expert',
                'description' => 'Permet de miner les filons de rubis rares',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-l']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['miner_ruby_m', 'miner_iron_m'],
            ],
            'miner_gold_m' => [
                'slug' => 'miner-gold-m',
                'title' => 'Minage de l\'or avance',
                'description' => 'Permet de miner les filons d\'or avances',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-gold-m']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['miner_gold_s', 'miner_yield_1'],
            ],
            'miner_deep_vein' => [
                'slug' => 'miner-deep-vein',
                'title' => 'Veines profondes',
                'description' => 'Augmente la quantite de minerais extraits des filons rares',
                'requiredPoints' => 100,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['miner_iron_l'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'miner_master' => [
                'slug' => 'miner-master',
                'title' => 'Maitre mineur',
                'description' => 'Maitrise absolue du minage — acces aux filons legendaires',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-ruby-xl', 'spot-iron-xl', 'spot-gold-xl']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['miner_ruby_l', 'miner_gold_m'],
            ],
        ];
    }

    // =========================================================================
    // HERBORISTE (bete/recolte) — 15 skills, cueillette de plantes
    // =========================================================================
    private function getHerbalistSkills(): array
    {
        $d = 'herbalist';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'herbalist_dandelion' => [
                'slug' => 'herbalist-dandelion-xs',
                'title' => 'Recolte de pissenlit',
                'description' => 'Permet de recolter les pissenlits basiques',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-dandelion-xs']]],
                'domain' => $d,
            ],
            'herbalist_mint' => [
                'slug' => 'herbalist-mint-xs',
                'title' => 'Recolte de menthe',
                'description' => 'Permet de recolter la menthe basique',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-mint-xs']]],
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'herbalist_sage' => [
                'slug' => 'herbalist-sage-xs',
                'title' => 'Recolte de sauge',
                'description' => 'Permet de recolter la sauge',
                'requiredPoints' => 10,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-sage-xs']]],
                'domain' => $d,
                'requirements' => ['herbalist_mint'],
            ],
            'herbalist_dandelion_s' => [
                'slug' => 'herbalist-dandelion-s',
                'title' => 'Recolte de pissenlit apprenti',
                'description' => 'Permet de recolter les pissenlits de qualite superieure',
                'requiredPoints' => 10,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-dandelion-s']]],
                'domain' => $d,
                'requirements' => ['herbalist_dandelion'],
            ],
            'herbalist_keen_eye' => [
                'slug' => 'herbalist-keen-eye',
                'title' => 'Oeil aiguise',
                'description' => 'Augmente les chances de trouver des plantes rares',
                'requiredPoints' => 15,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['herbalist_dandelion'],
            ],
            'herbalist_chamomile' => [
                'slug' => 'herbalist-chamomile-xs',
                'title' => 'Recolte de camomille',
                'description' => 'Permet de recolter la camomille',
                'requiredPoints' => 20,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-chamomile-xs']]],
                'domain' => $d,
                'requirements' => ['herbalist_mint'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'herbalist_sage_s' => [
                'slug' => 'herbalist-sage-s',
                'title' => 'Recolte de sauge apprenti',
                'description' => 'Permet de recolter la sauge de qualite superieure',
                'requiredPoints' => 25,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-sage-s']]],
                'domain' => $d,
                'requirements' => ['herbalist_sage', 'herbalist_keen_eye'],
            ],
            'herbalist_lavender' => [
                'slug' => 'herbalist-lavender-xs',
                'title' => 'Recolte de lavande',
                'description' => 'Permet de recolter la lavande',
                'requiredPoints' => 30,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-lavender-xs']]],
                'domain' => $d,
                'requirements' => ['herbalist_chamomile'],
            ],
            'herbalist_gentle_hands' => [
                'slug' => 'herbalist-gentle-hands',
                'title' => 'Mains delicates',
                'description' => 'Ameliore la qualite des plantes recoltees',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['herbalist_dandelion_s'],
            ],
            'herbalist_mint_m' => [
                'slug' => 'herbalist-mint-m',
                'title' => 'Recolte de menthe avance',
                'description' => 'Permet de recolter la menthe rare',
                'requiredPoints' => 40,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-mint-m']]],
                'domain' => $d,
                'requirements' => ['herbalist_sage_s'],
            ],
            'herbalist_chamomile_s' => [
                'slug' => 'herbalist-chamomile-s',
                'title' => 'Recolte de camomille apprenti',
                'description' => 'Permet de recolter la camomille de qualite superieure',
                'requiredPoints' => 50,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-chamomile-s']]],
                'domain' => $d,
                'requirements' => ['herbalist_lavender'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'herbalist_rare_plants' => [
                'slug' => 'herbalist-rare-plants',
                'title' => 'Connaissance des plantes rares',
                'description' => 'Permet de recolter les plantes rares de toutes les regions',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-sage-m', 'spot-chamomile-m', 'spot-lavender-m']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['herbalist_sage_s', 'herbalist_lavender'],
            ],
            'herbalist_bountiful' => [
                'slug' => 'herbalist-bountiful',
                'title' => 'Recolte abondante',
                'description' => 'Chance de doubler la quantite de plantes recoltees',
                'requiredPoints' => 80,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['herbalist_gentle_hands', 'herbalist_mint_m'],
            ],
            'herbalist_preservation' => [
                'slug' => 'herbalist-preservation',
                'title' => 'Conservation des plantes',
                'description' => 'Les plantes recoltees conservent mieux leurs proprietes',
                'requiredPoints' => 100,
                'domain' => $d,
                'heal' => 2,
                'requirements' => ['herbalist_chamomile_s'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'herbalist_master' => [
                'slug' => 'herbalist-master',
                'title' => 'Maitre herboriste',
                'description' => 'Maitrise absolue de l\'herboristerie — acces aux plantes legendaires',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-dandelion-xl', 'spot-mint-xl', 'spot-sage-xl']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['herbalist_rare_plants', 'herbalist_bountiful'],
            ],
        ];
    }

    // =========================================================================
    // PECHEUR (eau/recolte) — 15 skills, peche en milieu aquatique
    // =========================================================================
    private function getFishermanSkills(): array
    {
        $d = 'fisherman';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'fisher_trout_xs' => [
                'slug' => 'fisher-trout-xs',
                'title' => 'Peche de la truite debutant',
                'description' => 'Permet de pecher la truite dans les eaux calmes',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-xs']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'fisher_carp_xs' => [
                'slug' => 'fisher-carp-xs',
                'title' => 'Peche de la carpe debutant',
                'description' => 'Permet de pecher la carpe dans les etangs',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-carp-xs']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'fisher_trout_s' => [
                'slug' => 'fisher-trout-s',
                'title' => 'Peche de la truite apprenti',
                'description' => 'Permet de pecher la truite de qualite superieure',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['fisher_trout_xs'],
            ],
            'fisher_carp_s' => [
                'slug' => 'fisher-carp-s',
                'title' => 'Peche de la carpe apprenti',
                'description' => 'Permet de pecher la carpe de qualite superieure',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-carp-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['fisher_carp_xs'],
            ],
            'fisher_patience' => [
                'slug' => 'fisher-patience',
                'title' => 'Patience du pecheur',
                'description' => 'Augmente les chances de capture des poissons',
                'requiredPoints' => 15,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['fisher_trout_xs'],
            ],
            'fisher_salmon_xs' => [
                'slug' => 'fisher-salmon-xs',
                'title' => 'Peche du saumon debutant',
                'description' => 'Permet de pecher le saumon dans les rivieres',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-salmon-xs']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['fisher_carp_xs'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'fisher_trout_m' => [
                'slug' => 'fisher-trout-m',
                'title' => 'Peche de la truite avance',
                'description' => 'Permet de pecher la truite rare',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-m']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['fisher_trout_s', 'fisher_patience'],
            ],
            'fisher_carp_m' => [
                'slug' => 'fisher-carp-m',
                'title' => 'Peche de la carpe avance',
                'description' => 'Permet de pecher la carpe doree',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-carp-m']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['fisher_carp_s'],
            ],
            'fisher_salmon_s' => [
                'slug' => 'fisher-salmon-s',
                'title' => 'Peche du saumon apprenti',
                'description' => 'Permet de pecher le saumon de qualite superieure',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-salmon-s']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['fisher_salmon_xs'],
            ],
            'fisher_lucky_catch' => [
                'slug' => 'fisher-lucky-catch',
                'title' => 'Prise chanceuse',
                'description' => 'Chance de pecher un poisson supplementaire',
                'requiredPoints' => 40,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['fisher_trout_m'],
            ],
            'fisher_bait_mastery' => [
                'slug' => 'fisher-bait-mastery',
                'title' => 'Maitrise des appats',
                'description' => 'Les appats sont plus efficaces pour attirer les gros poissons',
                'requiredPoints' => 50,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['fisher_salmon_s'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'fisher_deep_sea' => [
                'slug' => 'fisher-deep-sea',
                'title' => 'Peche en eaux profondes',
                'description' => 'Permet de pecher dans les eaux profondes et les lacs',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-l', 'spot-carp-l']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['fisher_trout_m', 'fisher_carp_m'],
            ],
            'fisher_salmon_m' => [
                'slug' => 'fisher-salmon-m',
                'title' => 'Peche du saumon avance',
                'description' => 'Permet de pecher le saumon royal',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-salmon-m']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['fisher_salmon_s', 'fisher_lucky_catch'],
            ],
            'fisher_ocean' => [
                'slug' => 'fisher-ocean',
                'title' => 'Peche en haute mer',
                'description' => 'Permet de pecher les poissons des eaux oceaniques',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-salmon-l']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['fisher_bait_mastery'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'fisher_master' => [
                'slug' => 'fisher-master',
                'title' => 'Maitre pecheur',
                'description' => 'Maitrise absolue de la peche — acces aux poissons legendaires',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-xl', 'spot-carp-xl', 'spot-salmon-xl']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['fisher_deep_sea', 'fisher_salmon_m'],
            ],
        ];
    }

    // =========================================================================
    // DEPECEUR (bete/recolte) — 15 skills, depecage de creatures
    // =========================================================================
    private function getSkinnerSkills(): array
    {
        $d = 'skinner';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'skinner_hide_xs' => [
                'slug' => 'skinner-hide-xs',
                'title' => 'Depecage de cuir brut',
                'description' => 'Permet de depecer les creatures basiques pour obtenir du cuir brut',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-xs']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'skinner_bone_xs' => [
                'slug' => 'skinner-bone-xs',
                'title' => 'Collecte d\'os',
                'description' => 'Permet de recuperer les os des creatures vaincues',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-bone-xs']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'skinner_hide_s' => [
                'slug' => 'skinner-hide-s',
                'title' => 'Depecage de cuir apprenti',
                'description' => 'Permet de depecer les creatures pour obtenir du cuir fin',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['skinner_hide_xs'],
            ],
            'skinner_bone_s' => [
                'slug' => 'skinner-bone-s',
                'title' => 'Collecte d\'os apprenti',
                'description' => 'Permet de recuperer des os de qualite superieure',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-bone-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['skinner_bone_xs'],
            ],
            'skinner_precision' => [
                'slug' => 'skinner-precision',
                'title' => 'Lame precise',
                'description' => 'Augmente la precision du depecage',
                'requiredPoints' => 15,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['skinner_hide_xs'],
            ],
            'skinner_fang_xs' => [
                'slug' => 'skinner-fang-xs',
                'title' => 'Extraction de crocs',
                'description' => 'Permet de recuperer les crocs et griffes des creatures',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-fang-xs']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['skinner_bone_xs'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'skinner_hide_m' => [
                'slug' => 'skinner-hide-m',
                'title' => 'Depecage de cuir avance',
                'description' => 'Permet de depecer les creatures puissantes pour obtenir du cuir epais',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-m']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['skinner_hide_s', 'skinner_precision'],
            ],
            'skinner_bone_m' => [
                'slug' => 'skinner-bone-m',
                'title' => 'Collecte d\'os avance',
                'description' => 'Permet de recuperer des os rares',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-bone-m']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['skinner_bone_s'],
            ],
            'skinner_fang_s' => [
                'slug' => 'skinner-fang-s',
                'title' => 'Extraction de crocs apprenti',
                'description' => 'Permet de recuperer des crocs de creatures puissantes',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-fang-s']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['skinner_fang_xs'],
            ],
            'skinner_yield' => [
                'slug' => 'skinner-yield',
                'title' => 'Depecage minutieux',
                'description' => 'Chance de recuperer des materiaux supplementaires',
                'requiredPoints' => 40,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['skinner_hide_m'],
            ],
            'skinner_scale_xs' => [
                'slug' => 'skinner-scale-xs',
                'title' => 'Extraction d\'ecailles',
                'description' => 'Permet de recuperer les ecailles des creatures reptiliennes',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-scale-xs']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['skinner_fang_s'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'skinner_exotic' => [
                'slug' => 'skinner-exotic',
                'title' => 'Depecage de creatures exotiques',
                'description' => 'Permet de depecer les creatures rares et exotiques',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-l', 'spot-bone-l']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['skinner_hide_m', 'skinner_bone_m'],
            ],
            'skinner_fang_m' => [
                'slug' => 'skinner-fang-m',
                'title' => 'Extraction de crocs avance',
                'description' => 'Permet de recuperer des crocs et griffes rares',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-fang-m']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['skinner_fang_s', 'skinner_yield'],
            ],
            'skinner_scale_s' => [
                'slug' => 'skinner-scale-s',
                'title' => 'Extraction d\'ecailles avance',
                'description' => 'Permet de recuperer des ecailles de creatures puissantes',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-scale-s']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['skinner_scale_xs'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'skinner_master' => [
                'slug' => 'skinner-master',
                'title' => 'Maitre depeceur',
                'description' => 'Maitrise absolue du depecage — acces aux materiaux legendaires',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-xl', 'spot-bone-xl', 'spot-fang-xl']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['skinner_exotic', 'skinner_fang_m'],
            ],
        ];
    }

    // =========================================================================
    // FORGERON (metal/craft) — 15 skills, forge d'armes et armures
    // =========================================================================
    private function getBlacksmithSkills(): array
    {
        $d = 'blacksmith';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'smith_dagger' => [
                'slug' => 'smith-dagger',
                'title' => 'Forge de dagues',
                'description' => 'Permet de forger des dagues en fer',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-dagger']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'smith_chainmail' => [
                'slug' => 'smith-chainmail',
                'title' => 'Forge de cottes de mailles',
                'description' => 'Permet de forger des cottes de mailles basiques',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-chainmail']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'smith_sword' => [
                'slug' => 'smith-sword',
                'title' => 'Forge d\'epees',
                'description' => 'Permet de forger des epees en fer',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-sword']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['smith_dagger'],
            ],
            'smith_shield' => [
                'slug' => 'smith-shield',
                'title' => 'Forge de boucliers',
                'description' => 'Permet de forger des boucliers en fer',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-shield']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['smith_chainmail'],
            ],
            'smith_temper' => [
                'slug' => 'smith-temper',
                'title' => 'Trempe amelioree',
                'description' => 'Augmente la qualite des objets forges',
                'requiredPoints' => 15,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['smith_dagger'],
            ],
            'smith_plate' => [
                'slug' => 'smith-plate',
                'title' => 'Forge de plaques',
                'description' => 'Permet de forger des pieces d\'armure en plaques',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-plate']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['smith_chainmail'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'smith_steel_weapons' => [
                'slug' => 'smith-steel-weapons',
                'title' => 'Forge d\'acier — armes',
                'description' => 'Permet de forger des armes en acier',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-steel-sword', 'recipe-steel-dagger']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['smith_sword', 'smith_temper'],
            ],
            'smith_steel_armor' => [
                'slug' => 'smith-steel-armor',
                'title' => 'Forge d\'acier — armures',
                'description' => 'Permet de forger des armures en acier',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-steel-chainmail', 'recipe-steel-plate']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['smith_shield', 'smith_plate'],
            ],
            'smith_whetstone' => [
                'slug' => 'smith-whetstone',
                'title' => 'Forge de pierres a aiguiser',
                'description' => 'Permet de forger des pierres a aiguiser pour ameliorer les armes',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-whetstone']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['smith_temper'],
            ],
            'smith_reinforcement' => [
                'slug' => 'smith-reinforcement',
                'title' => 'Renforcement',
                'description' => 'Augmente la solidite des equipements forges',
                'requiredPoints' => 40,
                'domain' => $d,
                'life' => 3,
                'requirements' => ['smith_steel_weapons'],
            ],
            'smith_axe' => [
                'slug' => 'smith-axe',
                'title' => 'Forge de haches',
                'description' => 'Permet de forger des haches en acier',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-steel-axe']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['smith_steel_armor'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'smith_mithril' => [
                'slug' => 'smith-mithril',
                'title' => 'Forge de mithril',
                'description' => 'Permet de forger des equipements en mithril',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-mithril-sword', 'recipe-mithril-plate']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['smith_steel_weapons', 'smith_steel_armor'],
            ],
            'smith_heavy_armor' => [
                'slug' => 'smith-heavy-armor',
                'title' => 'Forge d\'armures lourdes',
                'description' => 'Permet de forger des armures lourdes en acier renforce',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-heavy-steel-plate']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['smith_whetstone', 'smith_reinforcement'],
            ],
            'smith_alloy' => [
                'slug' => 'smith-alloy',
                'title' => 'Alliages speciaux',
                'description' => 'Permet de creer des alliages aux proprietes uniques',
                'requiredPoints' => 100,
                'domain' => $d,
                'damage' => 2,
                'requirements' => ['smith_axe'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'smith_master' => [
                'slug' => 'smith-master',
                'title' => 'Maitre forgeron',
                'description' => 'Maitrise absolue de la forge — acces aux recettes legendaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-legendary-sword', 'recipe-legendary-plate']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['smith_mithril', 'smith_heavy_armor'],
            ],
        ];
    }

    // =========================================================================
    // TANNEUR (bete/craft) — 15 skills, travail du cuir et des peaux
    // =========================================================================
    private function getLeatherworkerSkills(): array
    {
        $d = 'leatherworker';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'leather_light_armor' => [
                'slug' => 'leather-light-armor',
                'title' => 'Travail du cuir brut',
                'description' => 'Permet de confectionner des armures legeres en cuir brut',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-vest']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'leather_gloves' => [
                'slug' => 'leather-gloves',
                'title' => 'Confection de gants',
                'description' => 'Permet de confectionner des gants en cuir',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-gloves']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'leather_boots' => [
                'slug' => 'leather-boots',
                'title' => 'Confection de bottes',
                'description' => 'Permet de confectionner des bottes en cuir',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-boots']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['leather_light_armor'],
            ],
            'leather_belt' => [
                'slug' => 'leather-belt',
                'title' => 'Confection de ceintures',
                'description' => 'Permet de confectionner des ceintures en cuir',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-belt']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['leather_gloves'],
            ],
            'leather_tanning' => [
                'slug' => 'leather-tanning',
                'title' => 'Tannage ameliore',
                'description' => 'Ameliore la qualite du cuir tanne',
                'requiredPoints' => 15,
                'domain' => $d,
                'life' => 2,
                'requirements' => ['leather_light_armor'],
            ],
            'leather_quiver' => [
                'slug' => 'leather-quiver',
                'title' => 'Confection de carquois',
                'description' => 'Permet de confectionner des carquois en cuir',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-quiver']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['leather_gloves'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'leather_hardened' => [
                'slug' => 'leather-hardened',
                'title' => 'Cuir renforce',
                'description' => 'Permet de confectionner des armures en cuir renforce',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-hardened-vest', 'recipe-hardened-boots']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['leather_boots', 'leather_tanning'],
            ],
            'leather_accessories' => [
                'slug' => 'leather-accessories',
                'title' => 'Accessoires en cuir',
                'description' => 'Permet de confectionner des accessoires avances',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-hardened-belt', 'recipe-hardened-gloves']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['leather_belt', 'leather_quiver'],
            ],
            'leather_supple' => [
                'slug' => 'leather-supple',
                'title' => 'Cuir souple',
                'description' => 'Augmente la souplesse des equipements en cuir',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['leather_tanning'],
            ],
            'leather_exotic_hide' => [
                'slug' => 'leather-exotic-hide',
                'title' => 'Travail des peaux exotiques',
                'description' => 'Permet de travailler les cuirs de creatures rares',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-exotic-leather-vest']]],
                'requiredPoints' => 40,
                'domain' => $d,
                'requirements' => ['leather_hardened'],
            ],
            'leather_reinforced_quiver' => [
                'slug' => 'leather-reinforced-quiver',
                'title' => 'Carquois renforce',
                'description' => 'Permet de confectionner des carquois renforces en cuir epais',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-hardened-quiver']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['leather_accessories'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'leather_dragon_hide' => [
                'slug' => 'leather-dragon-hide',
                'title' => 'Travail du cuir de dragon',
                'description' => 'Permet de confectionner des equipements en cuir de dragon',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-dragon-vest', 'recipe-dragon-boots']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['leather_hardened', 'leather_accessories'],
            ],
            'leather_resilience' => [
                'slug' => 'leather-resilience',
                'title' => 'Resilience du cuir',
                'description' => 'Les equipements en cuir conferes accordent des bonus de vie',
                'requiredPoints' => 80,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['leather_supple', 'leather_exotic_hide'],
            ],
            'leather_enchanted' => [
                'slug' => 'leather-enchanted',
                'title' => 'Cuir enchante',
                'description' => 'Permet de travailler des cuirs impregnes de magie',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-enchanted-vest']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['leather_reinforced_quiver'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'leather_master' => [
                'slug' => 'leather-master',
                'title' => 'Maitre tanneur',
                'description' => 'Maitrise absolue de la tannerie — acces aux recettes legendaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-legendary-vest', 'recipe-legendary-boots']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['leather_dragon_hide', 'leather_resilience'],
            ],
        ];
    }

    // =========================================================================
    // ALCHIMISTE (eau/craft) — 15 skills, potions et elixirs
    // =========================================================================
    private function getAlchimistSkills(): array
    {
        $d = 'alchimist';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'alchi_health_pot' => [
                'slug' => 'alchi-health-pot',
                'title' => 'Potion de soin mineure',
                'description' => 'Permet de brasser des potions de soin mineures',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-health-potion-minor']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'alchi_energy_pot' => [
                'slug' => 'alchi-energy-pot',
                'title' => 'Potion d\'energie mineure',
                'description' => 'Permet de brasser des potions d\'energie mineures',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-energy-potion-minor']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'alchi_health_pot_2' => [
                'slug' => 'alchi-health-pot-2',
                'title' => 'Potion de soin standard',
                'description' => 'Permet de brasser des potions de soin standard',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-health-potion-standard']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['alchi_health_pot'],
            ],
            'alchi_energy_pot_2' => [
                'slug' => 'alchi-energy-pot-2',
                'title' => 'Potion d\'energie standard',
                'description' => 'Permet de brasser des potions d\'energie standard',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-energy-potion-standard']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['alchi_energy_pot'],
            ],
            'alchi_distillation' => [
                'slug' => 'alchi-distillation',
                'title' => 'Distillation amelioree',
                'description' => 'Augmente l\'efficacite des potions brassees',
                'requiredPoints' => 15,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['alchi_health_pot'],
            ],
            'alchi_antidote' => [
                'slug' => 'alchi-antidote',
                'title' => 'Preparation d\'antidotes',
                'description' => 'Permet de brasser des antidotes contre les poisons',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-antidote']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['alchi_energy_pot'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'alchi_health_pot_3' => [
                'slug' => 'alchi-health-pot-3',
                'title' => 'Potion de soin superieure',
                'description' => 'Permet de brasser des potions de soin superieures',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-health-potion-superior']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['alchi_health_pot_2', 'alchi_distillation'],
            ],
            'alchi_buff_pot' => [
                'slug' => 'alchi-buff-pot',
                'title' => 'Elixir de force',
                'description' => 'Permet de brasser des elixirs augmentant les degats',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-strength-elixir']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['alchi_energy_pot_2'],
            ],
            'alchi_resist_pot' => [
                'slug' => 'alchi-resist-pot',
                'title' => 'Elixir de resistance',
                'description' => 'Permet de brasser des elixirs de resistance elementaire',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-resistance-elixir']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['alchi_antidote'],
            ],
            'alchi_concentration' => [
                'slug' => 'alchi-concentration',
                'title' => 'Concentration alchimique',
                'description' => 'Chance de brasser une potion bonus',
                'requiredPoints' => 40,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['alchi_health_pot_3'],
            ],
            'alchi_speed_pot' => [
                'slug' => 'alchi-speed-pot',
                'title' => 'Elixir de vitesse',
                'description' => 'Permet de brasser des elixirs augmentant la vitesse',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-speed-elixir']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['alchi_resist_pot'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'alchi_grand_potions' => [
                'slug' => 'alchi-grand-potions',
                'title' => 'Grandes potions',
                'description' => 'Permet de brasser des potions de grande puissance',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-health-potion-grand', 'recipe-energy-potion-grand']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['alchi_health_pot_3', 'alchi_buff_pot'],
            ],
            'alchi_transmutation' => [
                'slug' => 'alchi-transmutation',
                'title' => 'Transmutation',
                'description' => 'Permet de transmuter des ingredients en materiaux rares',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-transmute-rare']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['alchi_resist_pot', 'alchi_concentration'],
            ],
            'alchi_purity' => [
                'slug' => 'alchi-purity',
                'title' => 'Purete alchimique',
                'description' => 'Augmente la puissance de toutes les potions brassees',
                'requiredPoints' => 100,
                'domain' => $d,
                'heal' => 2,
                'hit' => 1,
                'requirements' => ['alchi_speed_pot'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'alchi_master' => [
                'slug' => 'alchi-master',
                'title' => 'Maitre alchimiste',
                'description' => 'Maitrise absolue de l\'alchimie — acces aux recettes legendaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-legendary-potion', 'recipe-philosophers-stone']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['alchi_grand_potions', 'alchi_transmutation'],
            ],
        ];
    }

    // =========================================================================
    // JOAILLIER (terre/craft) — 15 skills, gemmes et sertissage de materia
    // =========================================================================
    private function getJewellerSkills(): array
    {
        $d = 'jeweller';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'jewel_cut_basic' => [
                'slug' => 'jewel-cut-basic',
                'title' => 'Taille de gemmes brutes',
                'description' => 'Permet de tailler des gemmes brutes en pierres utilisables',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-cut-gem-basic']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'jewel_ring_basic' => [
                'slug' => 'jewel-ring-basic',
                'title' => 'Fabrication d\'anneaux simples',
                'description' => 'Permet de fabriquer des anneaux en metal basique',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-ring']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'jewel_amulet_basic' => [
                'slug' => 'jewel-amulet-basic',
                'title' => 'Fabrication d\'amulettes',
                'description' => 'Permet de fabriquer des amulettes basiques',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-amulet']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['jewel_ring_basic'],
            ],
            'jewel_cut_fine' => [
                'slug' => 'jewel-cut-fine',
                'title' => 'Taille de gemmes fines',
                'description' => 'Permet de tailler des gemmes de qualite fine',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-cut-gem-fine']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['jewel_cut_basic'],
            ],
            'jewel_polish' => [
                'slug' => 'jewel-polish',
                'title' => 'Polissage de gemmes',
                'description' => 'Augmente la qualite des gemmes taillees',
                'requiredPoints' => 15,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['jewel_cut_basic'],
            ],
            'jewel_bracelet' => [
                'slug' => 'jewel-bracelet',
                'title' => 'Fabrication de bracelets',
                'description' => 'Permet de fabriquer des bracelets en metal et gemmes',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-bracelet']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['jewel_ring_basic'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'jewel_cut_rare' => [
                'slug' => 'jewel-cut-rare',
                'title' => 'Taille de gemmes rares',
                'description' => 'Permet de tailler des gemmes rares aux proprietes magiques',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-cut-gem-rare']]],
                'requiredPoints' => 25,
                'domain' => $d,
                'requirements' => ['jewel_cut_fine', 'jewel_polish'],
            ],
            'jewel_ring_gold' => [
                'slug' => 'jewel-ring-gold',
                'title' => 'Fabrication d\'anneaux en or',
                'description' => 'Permet de fabriquer des anneaux en or sertis de gemmes',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-gold-ring', 'recipe-gold-amulet']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['jewel_amulet_basic'],
            ],
            'jewel_filigree' => [
                'slug' => 'jewel-filigree',
                'title' => 'Filigrane',
                'description' => 'Maitrise du filigrane — ameliore la qualite des bijoux fabriques',
                'requiredPoints' => 30,
                'domain' => $d,
                'damage' => 1,
                'heal' => 1,
                'requirements' => ['jewel_bracelet'],
            ],
            'jewel_crown' => [
                'slug' => 'jewel-crown',
                'title' => 'Fabrication de couronnes',
                'description' => 'Permet de fabriquer des couronnes ornees de gemmes',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-gold-crown']]],
                'requiredPoints' => 40,
                'domain' => $d,
                'requirements' => ['jewel_bracelet'],
            ],
            'jewel_enchant' => [
                'slug' => 'jewel-enchant',
                'title' => 'Enchantement de gemmes',
                'description' => 'Permet d\'enchanter les gemmes pour leur conferer des proprietes magiques',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-enchant-gem']]],
                'requiredPoints' => 50,
                'domain' => $d,
                'requirements' => ['jewel_cut_rare'],
            ],

            // Rang 4 (60-100 pts) — 3 skills
            'jewel_masterwork' => [
                'slug' => 'jewel-masterwork',
                'title' => 'Bijoux d\'exception',
                'description' => 'Permet de creer des bijoux d\'exception aux stats elevees',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-mithril-ring', 'recipe-mithril-amulet']]],
                'requiredPoints' => 60,
                'domain' => $d,
                'requirements' => ['jewel_cut_rare', 'jewel_ring_gold'],
            ],
            'jewel_gem_elemental' => [
                'slug' => 'jewel-gem-elemental',
                'title' => 'Taille de gemmes elementaires',
                'description' => 'Maitrise de la taille de gemmes infusees d\'energie elementaire',
                'requiredPoints' => 80,
                'domain' => $d,
                'damage' => 2,
                'heal' => 2,
                'requirements' => ['jewel_filigree', 'jewel_crown'],
            ],
            'jewel_prismatic' => [
                'slug' => 'jewel-prismatic',
                'title' => 'Gemmes prismatiques',
                'description' => 'Permet de creer des gemmes multi-elementaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-prismatic-gem']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['jewel_enchant'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'jewel_master' => [
                'slug' => 'jewel-master',
                'title' => 'Maitre joaillier',
                'description' => 'Maitrise absolue de la joaillerie — acces aux gemmes et bijoux legendaires',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-legendary-ring', 'recipe-legendary-amulet']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'requirements' => ['jewel_masterwork', 'jewel_gem_elemental'],
            ],
        ];
    }

    // =========================================================================
    // COMPETENCES PARTAGEES — multi-domaines (recolte + craft)
    // =========================================================================
    private function getSharedSkills(): array
    {
        return [
            // Premiers soins — partage entre tous les domaines de recolte
            'shared_first_aid' => [
                'slug' => 'shared-first-aid',
                'title' => 'Premiers soins',
                'description' => 'Permet de se soigner legerement pendant les activites de recolte',
                'requiredPoints' => 10,
                'domain' => ['miner', 'herbalist', 'fisherman', 'skinner'],
                'heal' => 1,
            ],
            // Endurance — partage entre tous les domaines de recolte
            'shared_endurance' => [
                'slug' => 'shared-endurance',
                'title' => 'Endurance du recolteur',
                'description' => 'Augmente les points de vie pendant les activites de recolte',
                'requiredPoints' => 20,
                'domain' => ['miner', 'herbalist', 'fisherman', 'skinner'],
                'life' => 3,
                'requirements' => ['shared_first_aid'],
            ],
            // Sens du terrain — partage entre mineur et herboriste
            'shared_terrain_sense' => [
                'slug' => 'shared-terrain-sense',
                'title' => 'Sens du terrain',
                'description' => 'Augmente les chances de trouver des ressources rares en explorant',
                'requiredPoints' => 30,
                'domain' => ['miner', 'herbalist'],
                'critical' => 1,
                'requirements' => ['shared_endurance'],
            ],
            // Instinct de survie — partage entre depeceur et pecheur
            'shared_survival' => [
                'slug' => 'shared-survival',
                'title' => 'Instinct de survie',
                'description' => 'Ameliore les chances de recolte dans les zones dangereuses',
                'requiredPoints' => 30,
                'domain' => ['skinner', 'fisherman'],
                'hit' => 2,
                'requirements' => ['shared_endurance'],
            ],
            // Efficacite artisanale — partage entre tous les domaines de craft
            'shared_craft_efficiency' => [
                'slug' => 'shared-craft-efficiency',
                'title' => 'Efficacite artisanale',
                'description' => 'Reduit les chances d\'echec lors de la fabrication d\'objets',
                'requiredPoints' => 10,
                'domain' => ['blacksmith', 'leatherworker', 'alchimist', 'jeweller'],
                'hit' => 1,
            ],
            // Economie de materiaux — partage entre tous les domaines de craft
            'shared_material_saving' => [
                'slug' => 'shared-material-saving',
                'title' => 'Economie de materiaux',
                'description' => 'Chance de ne pas consommer certains materiaux lors du craft',
                'requiredPoints' => 20,
                'domain' => ['blacksmith', 'leatherworker', 'alchimist', 'jeweller'],
                'critical' => 1,
                'requirements' => ['shared_craft_efficiency'],
            ],
            // Masterwork — partage entre forgeron et tanneur
            'shared_masterwork' => [
                'slug' => 'shared-masterwork',
                'title' => 'Maitre artisan',
                'description' => 'Augmente la qualite globale des equipements fabriques',
                'requiredPoints' => 30,
                'domain' => ['blacksmith', 'leatherworker'],
                'damage' => 1,
                'life' => 2,
                'requirements' => ['shared_material_saving'],
            ],
            // Savoir alchimique — partage entre alchimiste et joaillier
            'shared_arcane_craft' => [
                'slug' => 'shared-arcane-craft',
                'title' => 'Savoir arcanique',
                'description' => 'Augmente la puissance des objets a proprietes magiques fabriques',
                'requiredPoints' => 30,
                'domain' => ['alchimist', 'jeweller'],
                'heal' => 1,
                'critical' => 1,
                'requirements' => ['shared_material_saving'],
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
