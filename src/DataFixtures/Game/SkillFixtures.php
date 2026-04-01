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
            $this->getKnightSkills(),
            $this->getEngineerSkills(),
            $this->getGeomancerSkills(),
            $this->getDefenderSkills(),
            $this->getGuardianSkills(),
            $this->getNecromancerSkills(),
            $this->getDruidSkills(),
            $this->getHunterSkills(),
            $this->getTamerSkills(),
            $this->getStormcallerSkills(),
            $this->getArcherSkills(),
            $this->getWandererSkills(),
            $this->getPaladinSkills(),
            $this->getPriestSkills(),
            $this->getInquisitorSkills(),
            $this->getAssassinSkills(),
            $this->getWarlockSkills(),
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
    // SOLDAT (metal) — 15 skills, DPS CaC combos d'armes
    // =========================================================================
    private function getSoldierSkills(): array
    {
        $d = 'soldier';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'soldier_apprenti_1' => [
                'title' => 'Materia : Frappe puissante',
                'slug' => 'soldier-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Frappe puissante',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'sharp-blade']],
            ],
            'soldier_apprenti_2' => [
                'title' => 'Materia : Attraction magnetique',
                'slug' => 'soldier-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Attraction magnetique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'magnetic-pull']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'soldier_rang2_1' => [
                'title' => 'Force brute',
                'slug' => 'soldier-rang2-1',
                'description' => 'Augmente les degats physiques',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['soldier_apprenti_1'],
            ],
            'soldier_rang2_2' => [
                'title' => 'Precision martiale',
                'slug' => 'soldier-rang2-2',
                'description' => 'Augmente la precision des attaques',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['soldier_apprenti_1'],
            ],
            'soldier_rang2_3' => [
                'title' => 'Materia : Charge',
                'slug' => 'soldier-rang2-3',
                'description' => 'Permet d\'utiliser la materia Charge',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'iron-fist']],
                'requirements' => ['soldier_apprenti_2'],
            ],
            'soldier_rang2_4' => [
                'title' => 'Materia : Explosion d\'eclats',
                'slug' => 'soldier-rang2-4',
                'description' => 'Permet d\'utiliser la materia Explosion d\'eclats',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shrapnel-burst']],
                'requirements' => ['soldier_apprenti_2'],
            ],
            'soldier_materia_t2' => [
                'title' => 'Materia : Riposte d\'acier',
                'slug' => 'soldier-materia-t2',
                'description' => 'Permet d\'utiliser la materia Riposte d\'acier (contre-attaque)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'steel-riposte']],
                'requirements' => ['soldier_apprenti_1'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'soldier_rang3_1' => [
                'title' => 'Materia : Tourbillon d\'epee',
                'slug' => 'soldier-rang3-1',
                'description' => 'Permet d\'utiliser la materia Tourbillon d\'epee (AoE)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'blade-dance']],
                'requirements' => ['soldier_rang2_1', 'soldier_rang2_2'],
            ],
            'soldier_rang3_2' => [
                'title' => 'Materia : Tempete metallique',
                'slug' => 'soldier-rang3-2',
                'description' => 'Permet d\'utiliser la materia Tempete metallique',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'metal-storm']],
                'requirements' => ['soldier_rang2_3'],
            ],
            'soldier_rang3_3' => [
                'title' => 'Coups critiques',
                'slug' => 'soldier-rang3-3',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 30,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['soldier_rang2_4'],
            ],
            'soldier_rang3_4' => [
                'title' => 'Materia : Carreau d\'argent',
                'slug' => 'soldier-rang3-4',
                'description' => 'Permet d\'utiliser la materia Carreau d\'argent',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'silver-bolt']],
                'requirements' => ['soldier_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'soldier_rang4_1' => [
                'title' => 'Materia : Lame rasoir',
                'slug' => 'soldier-rang4-1',
                'description' => 'Permet d\'utiliser la materia Lame rasoir — coupe devastatrice',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'razor-edge']],
                'requirements' => ['soldier_rang3_1', 'soldier_rang3_2'],
            ],
            'soldier_rang4_2' => [
                'title' => 'Materia : Poids ecrasant',
                'slug' => 'soldier-rang4-2',
                'description' => 'Permet d\'utiliser la materia Poids ecrasant',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crushing-weight']],
                'requirements' => ['soldier_rang3_3', 'soldier_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'soldier_rang5_1' => [
                'title' => 'Materia : Vierge de fer',
                'slug' => 'soldier-rang5-1',
                'description' => 'Permet d\'utiliser la materia Vierge de fer',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'iron-maiden']],
                'requirements' => ['soldier_rang4_1', 'soldier_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // CHEVALIER (metal) — 15 skills, tank lourd contre-attaque
    // =========================================================================
    private function getKnightSkills(): array
    {
        $d = 'knight';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'knight_apprenti_1' => [
                'title' => 'Materia : Provocation',
                'slug' => 'knight-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Provocation',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'provocation']],
            ],
            'knight_apprenti_2' => [
                'title' => 'Materia : Bouclier d\'acier',
                'slug' => 'knight-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'acier',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'steel-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'knight_rang2_1' => [
                'title' => 'Constitution de fer',
                'slug' => 'knight-rang2-1',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['knight_apprenti_1'],
            ],
            'knight_rang2_2' => [
                'title' => 'Materia : Riposte',
                'slug' => 'knight-rang2-2',
                'description' => 'Permet d\'utiliser la materia Riposte',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'riposte']],
                'requirements' => ['knight_apprenti_1'],
            ],
            'knight_rang2_3' => [
                'title' => 'Materia : Peau metallique',
                'slug' => 'knight-rang2-3',
                'description' => 'Permet d\'utiliser la materia Peau metallique',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'metal-skin']],
                'requirements' => ['knight_apprenti_2'],
            ],
            'knight_rang2_4' => [
                'title' => 'Endurance du chevalier',
                'slug' => 'knight-rang2-4',
                'description' => 'Augmente la puissance des soins recus',
                'requiredPoints' => 15,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['knight_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'knight_rang3_1' => [
                'title' => 'Materia : Barriere de lames',
                'slug' => 'knight-rang3-1',
                'description' => 'Permet d\'utiliser la materia Barriere de lames (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'blade-barrier']],
                'requirements' => ['knight_rang2_1', 'knight_rang2_2'],
            ],
            'knight_rang3_2' => [
                'title' => 'Materia : Regeneration metallique',
                'slug' => 'knight-rang3-2',
                'description' => 'Permet d\'utiliser la materia Regeneration metallique',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'metallic-regeneration']],
                'requirements' => ['knight_rang2_3'],
            ],
            'knight_rang3_3' => [
                'title' => 'Armure epaisse',
                'slug' => 'knight-rang3-3',
                'description' => 'Augmente les points de vie et la precision',
                'requiredPoints' => 30,
                'domain' => $d,
                'life' => 5,
                'hit' => 1,
                'requirements' => ['knight_rang2_4'],
            ],
            'knight_rang3_4' => [
                'title' => 'Materia : Chaine d\'eclairs',
                'slug' => 'knight-rang3-4',
                'description' => 'Permet d\'utiliser la materia Chaine d\'eclairs',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'chain-lightning']],
                'requirements' => ['knight_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'knight_rang4_1' => [
                'title' => 'Materia : Poids ecrasant',
                'slug' => 'knight-rang4-1',
                'description' => 'Permet d\'utiliser la materia Poids ecrasant — ecrasement brutal',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'crushing-weight']],
                'requirements' => ['knight_rang3_1', 'knight_rang3_2'],
            ],
            'knight_rang4_2' => [
                'title' => 'Materia : Vierge de fer',
                'slug' => 'knight-rang4-2',
                'description' => 'Permet d\'utiliser la materia Vierge de fer',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'iron-maiden']],
                'requirements' => ['knight_rang3_3', 'knight_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'knight_rang5_1' => [
                'title' => 'Materia : Forteresse d\'acier',
                'slug' => 'knight-rang5-1',
                'description' => 'Permet d\'utiliser la materia Forteresse d\'acier',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'steel-fortress']],
                'requirements' => ['knight_rang4_1', 'knight_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // INGENIEUR (metal) — 15 skills, support technique constructions
    // =========================================================================
    private function getEngineerSkills(): array
    {
        $d = 'engineer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'engi_apprenti_1' => [
                'title' => 'Materia : Attraction magnetique',
                'slug' => 'engi-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Attraction magnetique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'magnetic-pull']],
            ],
            'engi_apprenti_2' => [
                'title' => 'Materia : Bouclier d\'acier',
                'slug' => 'engi-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'acier',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'steel-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'engi_rang2_1' => [
                'title' => 'Precision mecanique',
                'slug' => 'engi-rang2-1',
                'description' => 'Augmente la precision des attaques',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['engi_apprenti_1'],
            ],
            'engi_rang2_2' => [
                'title' => 'Materia : Tourelle',
                'slug' => 'engi-rang2-2',
                'description' => 'Permet d\'utiliser la materia Tourelle',
                'requiredPoints' => 10,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'turret']],
                'requirements' => ['engi_apprenti_1'],
            ],
            'engi_rang2_3' => [
                'title' => 'Materia : Explosion d\'eclats',
                'slug' => 'engi-rang2-3',
                'description' => 'Permet d\'utiliser la materia Explosion d\'eclats',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shrapnel-burst']],
                'requirements' => ['engi_apprenti_2'],
            ],
            'engi_rang2_4' => [
                'title' => 'Blindage renforce',
                'slug' => 'engi-rang2-4',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 15,
                'domain' => $d,
                'life' => 3,
                'requirements' => ['engi_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'engi_rang3_1' => [
                'title' => 'Materia : Automate reparateur',
                'slug' => 'engi-rang3-1',
                'description' => 'Permet d\'utiliser la materia Automate reparateur',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'repair-bot']],
                'requirements' => ['engi_rang2_1', 'engi_rang2_2'],
            ],
            'engi_rang3_2' => [
                'title' => 'Materia : Barriere de lames',
                'slug' => 'engi-rang3-2',
                'description' => 'Permet d\'utiliser la materia Barriere de lames (degats + soin)',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'blade-barrier']],
                'requirements' => ['engi_rang2_3'],
            ],
            'engi_rang3_3' => [
                'title' => 'Ameliorations mecaniques',
                'slug' => 'engi-rang3-3',
                'description' => 'Augmente les degats et les soins',
                'requiredPoints' => 30,
                'domain' => $d,
                'damage' => 1,
                'heal' => 1,
                'requirements' => ['engi_rang2_4'],
            ],
            'engi_rang3_4' => [
                'title' => 'Materia : Chaine d\'eclairs',
                'slug' => 'engi-rang3-4',
                'description' => 'Permet d\'utiliser la materia Chaine d\'eclairs',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'chain-lightning']],
                'requirements' => ['engi_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'engi_rang4_1' => [
                'title' => 'Materia : Tempete metallique',
                'slug' => 'engi-rang4-1',
                'description' => 'Permet d\'utiliser la materia Tempete metallique',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'metal-storm']],
                'requirements' => ['engi_rang3_1', 'engi_rang3_2'],
            ],
            'engi_rang4_2' => [
                'title' => 'Materia : Regeneration metallique',
                'slug' => 'engi-rang4-2',
                'description' => 'Permet d\'utiliser la materia Regeneration metallique',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'metallic-regeneration']],
                'requirements' => ['engi_rang3_3', 'engi_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'engi_rang5_1' => [
                'title' => 'Materia : Engin de siege',
                'slug' => 'engi-rang5-1',
                'description' => 'Permet d\'utiliser la materia Engin de siege',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'siege-engine']],
                'requirements' => ['engi_rang4_1', 'engi_rang4_2'],
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
            'hydro_materia_t2' => [
                'title' => 'Materia : Brume glaciale',
                'slug' => 'hydro-materia-t2',
                'description' => 'Permet d\'utiliser la materia Brume glaciale (paralysie)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'frost-mist']],
                'requirements' => ['hydro_apprenti_1'],
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
            'geo_materia_t2' => [
                'title' => 'Materia : Mur de pierre',
                'slug' => 'geo-materia-t2',
                'description' => 'Permet d\'utiliser la materia Mur de pierre (protection)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'stone-shield']],
                'requirements' => ['geo_apprenti_1'],
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
    // NECROMANCIEN (ombre) — 13 skills, drain de vie et maledictions
    // =========================================================================
    private function getNecromancerSkills(): array
    {
        $d = 'necromancer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'necro_materia_1' => [
                'title' => 'Materia : Drain de vie',
                'slug' => 'necro-materia-1',
                'description' => 'Permet d\'utiliser la materia Drain de vie',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'soul-drain']],
            ],
            'necro_apprenti_2' => [
                'title' => 'Materia : Toucher necrotique',
                'slug' => 'necro-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Toucher necrotique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'necrotic-touch']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'necro_damage_1' => [
                'title' => 'Energie sombre',
                'slug' => 'necro-damage-1',
                'description' => 'Augmente les degats des sorts de mort',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['necro_materia_1'],
            ],
            'necro_rang2_2' => [
                'title' => 'Corruption',
                'slug' => 'necro-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['necro_materia_1'],
            ],
            'necro_materia_2' => [
                'title' => 'Materia : Malediction',
                'slug' => 'necro-materia-2',
                'description' => 'Permet d\'utiliser la materia Malediction (poison)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'plague-strike']],
                'requirements' => ['necro_apprenti_2'],
            ],
            'necro_rang2_4' => [
                'title' => 'Materia : Sangsue vitale',
                'slug' => 'necro-rang2-4',
                'description' => 'Permet d\'utiliser la materia Sangsue vitale (drain)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-leech']],
                'requirements' => ['necro_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'necro_rang3_1' => [
                'title' => 'Materia : Eclair d\'ombre',
                'slug' => 'necro-rang3-1',
                'description' => 'Permet d\'utiliser la materia Eclair d\'ombre',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-bolt']],
                'requirements' => ['necro_damage_1', 'necro_rang2_2'],
            ],
            'necro_materia_3' => [
                'title' => 'Materia : Moisson sombre',
                'slug' => 'necro-materia-3',
                'description' => 'Permet d\'utiliser la materia Moisson sombre — drain massif',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'dark-harvest']],
                'requirements' => ['necro_materia_2'],
            ],
            'necro_rang3_3' => [
                'title' => 'Maitrise necrotique',
                'slug' => 'necro-rang3-3',
                'description' => 'Augmente la precision des sorts de mort',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['necro_rang2_4'],
            ],
            'necro_rang3_4' => [
                'title' => 'Materia : Rituel sombre',
                'slug' => 'necro-rang3-4',
                'description' => 'Permet d\'utiliser la materia Rituel sombre (sacrifice + soin)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'dark-ritual']],
                'requirements' => ['necro_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'necro_rang4_1' => [
                'title' => 'Materia : Dechirure d\'ame',
                'slug' => 'necro-rang4-1',
                'description' => 'Permet d\'utiliser la materia Dechirure d\'ame — degats massifs',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'soul-rip']],
                'requirements' => ['necro_rang3_1', 'necro_materia_3'],
            ],
            'necro_rang4_2' => [
                'title' => 'Materia : Spirale de mort',
                'slug' => 'necro-rang4-2',
                'description' => 'Permet d\'utiliser la materia Spirale de mort',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-coil']],
                'requirements' => ['necro_rang3_3', 'necro_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'necro_rang5_1' => [
                'title' => 'Materia : Nova de mort',
                'slug' => 'necro-rang5-1',
                'description' => 'Permet d\'utiliser la materia Nova de mort',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-nova']],
                'requirements' => ['necro_rang4_1', 'necro_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // DRUIDE (bete) — 13 skills, healer/support nature
    // =========================================================================
    private function getDruidSkills(): array
    {
        $d = 'druid';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'druid_apprenti_1' => [
                'title' => 'Materia : Liane',
                'slug' => 'druid-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Liane',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'liana-whip']],
            ],
            'druid_apprenti_2' => [
                'title' => 'Materia : Guerison naturelle',
                'slug' => 'druid-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Guerison naturelle',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'natural-healing']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'druid_rang2_1' => [
                'title' => 'Symbiose naturelle',
                'slug' => 'druid-rang2-1',
                'description' => 'Augmente la puissance des soins de nature',
                'requiredPoints' => 10,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['druid_apprenti_1'],
            ],
            'druid_rang2_2' => [
                'title' => 'Affinite naturelle',
                'slug' => 'druid-rang2-2',
                'description' => 'Augmente la precision des sorts de nature',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['druid_apprenti_1'],
            ],
            'druid_rang2_3' => [
                'title' => 'Materia : Empoisonnement',
                'slug' => 'druid-rang2-3',
                'description' => 'Permet d\'utiliser la materia Empoisonnement (DoT)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'poison-cloud']],
                'requirements' => ['druid_apprenti_2'],
            ],
            'druid_rang2_4' => [
                'title' => 'Materia : Bouclier d\'epines',
                'slug' => 'druid-rang2-4',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'epines',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'thorn-shield']],
                'requirements' => ['druid_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'druid_rang3_1' => [
                'title' => 'Materia : Etreinte de la foret',
                'slug' => 'druid-rang3-1',
                'description' => 'Permet d\'utiliser la materia Etreinte de la foret (soin puissant)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'forest-embrace']],
                'requirements' => ['druid_rang2_1', 'druid_rang2_2'],
            ],
            'druid_rang3_2' => [
                'title' => 'Materia : Croissance sauvage',
                'slug' => 'druid-rang3-2',
                'description' => 'Permet d\'utiliser la materia Croissance sauvage (soin + degats)',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wild-growth']],
                'requirements' => ['druid_rang2_3'],
            ],
            'druid_rang3_3' => [
                'title' => 'Communion vegetale',
                'slug' => 'druid-rang3-3',
                'description' => 'Augmente les soins et la vitalite',
                'requiredPoints' => 30,
                'domain' => $d,
                'heal' => 1,
                'life' => 3,
                'requirements' => ['druid_rang2_4'],
            ],
            'druid_rang3_4' => [
                'title' => 'Materia : Appel de la foret',
                'slug' => 'druid-rang3-4',
                'description' => 'Permet d\'utiliser la materia Appel de la foret (AoE)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-wrath']],
                'requirements' => ['druid_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'druid_rang4_1' => [
                'title' => 'Materia : Benediction de la nature',
                'slug' => 'druid-rang4-1',
                'description' => 'Permet d\'utiliser la materia Benediction de la nature — soin surpuissant',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-blessing']],
                'requirements' => ['druid_rang3_1', 'druid_rang3_2'],
            ],
            'druid_rang4_2' => [
                'title' => 'Materia : Afflux primordial',
                'slug' => 'druid-rang4-2',
                'description' => 'Permet d\'utiliser la materia Afflux primordial (soin + degats)',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'primal-surge']],
                'requirements' => ['druid_rang3_3', 'druid_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'druid_rang5_1' => [
                'title' => 'Materia : Fureur naturelle',
                'slug' => 'druid-rang5-1',
                'description' => 'Permet d\'utiliser la materia Fureur naturelle',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-fury']],
                'requirements' => ['druid_rang4_1', 'druid_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // CHASSEUR (bete) — 13 skills, DPS distance pieges et pistage
    // =========================================================================
    private function getHunterSkills(): array
    {
        $d = 'hunter';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'hunter_apprenti_1' => [
                'title' => 'Materia : Appel du faucon',
                'slug' => 'hunter-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Appel du faucon',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'falcon-strike']],
            ],
            'hunter_apprenti_2' => [
                'title' => 'Materia : Morsure venimeuse',
                'slug' => 'hunter-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Morsure venimeuse',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'venomous-bite']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'hunter_rang2_1' => [
                'title' => 'Oeil de pisteur',
                'slug' => 'hunter-rang2-1',
                'description' => 'Augmente la precision des tirs',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['hunter_apprenti_1'],
            ],
            'hunter_rang2_2' => [
                'title' => 'Instinct de chasseur',
                'slug' => 'hunter-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['hunter_apprenti_1'],
            ],
            'hunter_rang2_3' => [
                'title' => 'Materia : Piege a ours',
                'slug' => 'hunter-rang2-3',
                'description' => 'Permet d\'utiliser la materia Piege a ours (paralysie)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'bear-trap']],
                'requirements' => ['hunter_apprenti_2'],
            ],
            'hunter_rang2_4' => [
                'title' => 'Materia : Piege de vignes',
                'slug' => 'hunter-rang2-4',
                'description' => 'Permet d\'utiliser la materia Piege de vignes',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vine-snare']],
                'requirements' => ['hunter_apprenti_2'],
            ],
            'hunter_materia_t2' => [
                'title' => 'Materia : Morsure sauvage',
                'slug' => 'hunter-materia-t2',
                'description' => 'Permet d\'utiliser la materia Morsure sauvage',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'savage-bite']],
                'requirements' => ['hunter_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'hunter_rang3_1' => [
                'title' => 'Materia : Tir empoisonne',
                'slug' => 'hunter-rang3-1',
                'description' => 'Permet d\'utiliser la materia Tir empoisonne (poison)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'poison-arrow']],
                'requirements' => ['hunter_rang2_1', 'hunter_rang2_2'],
            ],
            'hunter_rang3_2' => [
                'title' => 'Materia : Spores toxiques',
                'slug' => 'hunter-rang3-2',
                'description' => 'Permet d\'utiliser la materia Spores toxiques',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'toxic-spores']],
                'requirements' => ['hunter_rang2_3'],
            ],
            'hunter_rang3_3' => [
                'title' => 'Traque mortelle',
                'slug' => 'hunter-rang3-3',
                'description' => 'Augmente les degats et le critique',
                'requiredPoints' => 30,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['hunter_rang2_4'],
            ],
            'hunter_rang3_4' => [
                'title' => 'Materia : Lame feuille',
                'slug' => 'hunter-rang3-4',
                'description' => 'Permet d\'utiliser la materia Lame feuille',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'leaf-blade']],
                'requirements' => ['hunter_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'hunter_rang4_1' => [
                'title' => 'Materia : Explosion d\'epines',
                'slug' => 'hunter-rang4-1',
                'description' => 'Permet d\'utiliser la materia Explosion d\'epines',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'thorn-burst']],
                'requirements' => ['hunter_rang3_1', 'hunter_rang3_2'],
            ],
            'hunter_rang4_2' => [
                'title' => 'Materia : Fureur naturelle',
                'slug' => 'hunter-rang4-2',
                'description' => 'Permet d\'utiliser la materia Fureur naturelle',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-fury']],
                'requirements' => ['hunter_rang3_3', 'hunter_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'hunter_rang5_1' => [
                'title' => 'Materia : Chasse en meute',
                'slug' => 'hunter-rang5-1',
                'description' => 'Permet d\'utiliser la materia Chasse en meute (AoE)',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'pack-hunt']],
                'requirements' => ['hunter_rang4_1', 'hunter_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // DOMPTEUR (bete) — 13 skills, tank/invocateur familiers
    // =========================================================================
    private function getTamerSkills(): array
    {
        $d = 'tamer';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'tamer_apprenti_1' => [
                'title' => 'Materia : Lien bestial',
                'slug' => 'tamer-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Lien bestial',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'beast-bond']],
            ],
            'tamer_apprenti_2' => [
                'title' => 'Materia : Bouclier d\'epines',
                'slug' => 'tamer-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Bouclier d\'epines',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'thorn-shield']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'tamer_rang2_1' => [
                'title' => 'Lien renforce',
                'slug' => 'tamer-rang2-1',
                'description' => 'Augmente la puissance des soins du familier',
                'requiredPoints' => 10,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['tamer_apprenti_1'],
            ],
            'tamer_rang2_2' => [
                'title' => 'Instinct animal',
                'slug' => 'tamer-rang2-2',
                'description' => 'Augmente la precision des attaques',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['tamer_apprenti_1'],
            ],
            'tamer_rang2_3' => [
                'title' => 'Materia : Racines enchevetrees',
                'slug' => 'tamer-rang2-3',
                'description' => 'Permet d\'utiliser la materia Racines enchevetrees (paralysie)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'entangling-roots']],
                'requirements' => ['tamer_apprenti_2'],
            ],
            'tamer_rang2_4' => [
                'title' => 'Constitution bestiale',
                'slug' => 'tamer-rang2-4',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 15,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['tamer_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'tamer_rang3_1' => [
                'title' => 'Materia : Charge sauvage',
                'slug' => 'tamer-rang3-1',
                'description' => 'Permet d\'utiliser la materia Charge sauvage',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'savage-charge']],
                'requirements' => ['tamer_rang2_1', 'tamer_rang2_2'],
            ],
            'tamer_rang3_2' => [
                'title' => 'Materia : Croissance sauvage',
                'slug' => 'tamer-rang3-2',
                'description' => 'Permet d\'utiliser la materia Croissance sauvage (soin + degats)',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'wild-growth']],
                'requirements' => ['tamer_rang2_3'],
            ],
            'tamer_rang3_3' => [
                'title' => 'Carapace epaisse',
                'slug' => 'tamer-rang3-3',
                'description' => 'Augmente les points de vie et les soins',
                'requiredPoints' => 30,
                'domain' => $d,
                'life' => 5,
                'heal' => 1,
                'requirements' => ['tamer_rang2_4'],
            ],
            'tamer_rang3_4' => [
                'title' => 'Materia : Liane',
                'slug' => 'tamer-rang3-4',
                'description' => 'Permet d\'utiliser la materia Liane',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'liana-whip']],
                'requirements' => ['tamer_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'tamer_rang4_1' => [
                'title' => 'Materia : Afflux primordial',
                'slug' => 'tamer-rang4-1',
                'description' => 'Permet d\'utiliser la materia Afflux primordial (soin + degats)',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'primal-surge']],
                'requirements' => ['tamer_rang3_1', 'tamer_rang3_2'],
            ],
            'tamer_rang4_2' => [
                'title' => 'Materia : Benediction de la nature',
                'slug' => 'tamer-rang4-2',
                'description' => 'Permet d\'utiliser la materia Benediction de la nature',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'nature-blessing']],
                'requirements' => ['tamer_rang3_3', 'tamer_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'tamer_rang5_1' => [
                'title' => 'Materia : Rugissement alpha',
                'slug' => 'tamer-rang5-1',
                'description' => 'Permet d\'utiliser la materia Rugissement alpha',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'alpha-roar']],
                'requirements' => ['tamer_rang4_1', 'tamer_rang4_2'],
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
            'storm_materia_t2' => [
                'title' => 'Materia : Éclair en chaîne',
                'slug' => 'storm-materia-t2',
                'description' => 'Permet d\'utiliser la materia Éclair en chaîne (AoE)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'air-chain-lightning']],
                'requirements' => ['storm_materia_1'],
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
    // PALADIN (lumiere) — 13 skills, guerrier sacre tank/healer
    // =========================================================================
    private function getPaladinSkills(): array
    {
        $d = 'paladin';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'paladin_apprenti_1' => [
                'title' => 'Materia : Frappe sacree',
                'slug' => 'paladin-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Frappe sacree',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'sacred-strike']],
            ],
            'paladin_apprenti_2' => [
                'title' => 'Materia : Aura de lumiere',
                'slug' => 'paladin-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Aura de lumiere',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'light-aura']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'paladin_rang2_1' => [
                'title' => 'Bras du jugement',
                'slug' => 'paladin-rang2-1',
                'description' => 'Augmente les degats des attaques sacrees',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['paladin_apprenti_1'],
            ],
            'paladin_rang2_2' => [
                'title' => 'Constitution sacree',
                'slug' => 'paladin-rang2-2',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 10,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['paladin_apprenti_1'],
            ],
            'paladin_rang2_3' => [
                'title' => 'Materia : Lumiere',
                'slug' => 'paladin-rang2-3',
                'description' => 'Permet d\'utiliser la materia Lumiere (degats + soin)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'holy-light']],
                'requirements' => ['paladin_apprenti_2'],
            ],
            'paladin_rang2_4' => [
                'title' => 'Materia : Toucher guerisseur',
                'slug' => 'paladin-rang2-4',
                'description' => 'Permet d\'utiliser la materia Toucher guerisseur',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'healing-touch']],
                'requirements' => ['paladin_apprenti_2'],
            ],
            'paladin_materia_t2' => [
                'title' => 'Materia : Benediction',
                'slug' => 'paladin-materia-t2',
                'description' => 'Permet d\'utiliser la materia Benediction (soin)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'light-blessing']],
                'requirements' => ['paladin_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'paladin_rang3_1' => [
                'title' => 'Materia : Lumiere sacree',
                'slug' => 'paladin-rang3-1',
                'description' => 'Permet d\'utiliser la materia Lumiere sacree (degats + soin)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'sacred-light']],
                'requirements' => ['paladin_rang2_1', 'paladin_rang2_2'],
            ],
            'paladin_rang3_2' => [
                'title' => 'Materia : Bouclier de vie',
                'slug' => 'paladin-rang3-2',
                'description' => 'Permet d\'utiliser la materia Bouclier de vie',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-shield']],
                'requirements' => ['paladin_rang2_3'],
            ],
            'paladin_rang3_3' => [
                'title' => 'Precision divine',
                'slug' => 'paladin-rang3-3',
                'description' => 'Augmente la precision des attaques sacrees',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['paladin_rang2_4'],
            ],
            'paladin_rang3_4' => [
                'title' => 'Materia : Purification',
                'slug' => 'paladin-rang3-4',
                'description' => 'Permet d\'utiliser la materia Purification',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'purification']],
                'requirements' => ['paladin_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'paladin_rang4_1' => [
                'title' => 'Materia : Benediction divine',
                'slug' => 'paladin-rang4-1',
                'description' => 'Permet d\'utiliser la materia Benediction divine — soin puissant',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-blessing']],
                'requirements' => ['paladin_rang3_1', 'paladin_rang3_2'],
            ],
            'paladin_rang4_2' => [
                'title' => 'Materia : Explosion de vie',
                'slug' => 'paladin-rang4-2',
                'description' => 'Permet d\'utiliser la materia Explosion de vie',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-burst']],
                'requirements' => ['paladin_rang3_3', 'paladin_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'paladin_rang5_1' => [
                'title' => 'Materia : Jugement divin',
                'slug' => 'paladin-rang5-1',
                'description' => 'Permet d\'utiliser la materia Jugement divin',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-judgment']],
                'requirements' => ['paladin_rang4_1', 'paladin_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // PRETRE (lumiere) — 13 skills, healer pur
    // =========================================================================
    private function getPriestSkills(): array
    {
        $d = 'priest';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'priest_apprenti_1' => [
                'title' => 'Materia : Priere',
                'slug' => 'priest-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Priere',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'prayer']],
            ],
            'priest_apprenti_2' => [
                'title' => 'Materia : Toucher angelique',
                'slug' => 'priest-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Toucher angelique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'angelic-touch']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'priest_rang2_1' => [
                'title' => 'Grace divine',
                'slug' => 'priest-rang2-1',
                'description' => 'Augmente la puissance des soins',
                'requiredPoints' => 10,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['priest_apprenti_1'],
            ],
            'priest_rang2_2' => [
                'title' => 'Concentration sacree',
                'slug' => 'priest-rang2-2',
                'description' => 'Augmente la precision des soins',
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['priest_apprenti_1'],
            ],
            'priest_rang2_3' => [
                'title' => 'Materia : Vague de guerison',
                'slug' => 'priest-rang2-3',
                'description' => 'Permet d\'utiliser la materia Vague de guerison',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'healing-wave']],
                'requirements' => ['priest_apprenti_2'],
            ],
            'priest_rang2_4' => [
                'title' => 'Materia : Floraison de vie',
                'slug' => 'priest-rang2-4',
                'description' => 'Permet d\'utiliser la materia Floraison de vie',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-bloom']],
                'requirements' => ['priest_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'priest_rang3_1' => [
                'title' => 'Materia : Regeneration',
                'slug' => 'priest-rang3-1',
                'description' => 'Permet d\'utiliser la materia Regeneration (HoT)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'rejuvenation']],
                'requirements' => ['priest_rang2_1', 'priest_rang2_2'],
            ],
            'priest_rang3_2' => [
                'title' => 'Materia : Afflux de vitalite',
                'slug' => 'priest-rang3-2',
                'description' => 'Permet d\'utiliser la materia Afflux de vitalite',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vitality-surge']],
                'requirements' => ['priest_rang2_3'],
            ],
            'priest_rang3_3' => [
                'title' => 'Vitalite du pretre',
                'slug' => 'priest-rang3-3',
                'description' => 'Augmente les points de vie maximum',
                'requiredPoints' => 30,
                'domain' => $d,
                'life' => 5,
                'requirements' => ['priest_rang2_4'],
            ],
            'priest_rang3_4' => [
                'title' => 'Materia : Transfert de vie',
                'slug' => 'priest-rang3-4',
                'description' => 'Permet d\'utiliser la materia Transfert de vie',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-transfer']],
                'requirements' => ['priest_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'priest_rang4_1' => [
                'title' => 'Materia : Benediction celeste',
                'slug' => 'priest-rang4-1',
                'description' => 'Permet d\'utiliser la materia Benediction celeste — soin ultime',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'celestial-blessing']],
                'requirements' => ['priest_rang3_1', 'priest_rang3_2'],
            ],
            'priest_rang4_2' => [
                'title' => 'Materia : Benediction divine',
                'slug' => 'priest-rang4-2',
                'description' => 'Permet d\'utiliser la materia Benediction divine',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-blessing']],
                'requirements' => ['priest_rang3_3', 'priest_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'priest_rang5_1' => [
                'title' => 'Materia : Miracle',
                'slug' => 'priest-rang5-1',
                'description' => 'Permet d\'utiliser la materia Miracle',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'miracle']],
                'requirements' => ['priest_rang4_1', 'priest_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // INQUISITEUR (lumiere) — 13 skills, DPS magique sacre
    // =========================================================================
    private function getInquisitorSkills(): array
    {
        $d = 'inquisitor';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'inquis_apprenti_1' => [
                'title' => 'Materia : Chatiment sacre',
                'slug' => 'inquis-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Chatiment sacre',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'smite']],
            ],
            'inquis_apprenti_2' => [
                'title' => 'Materia : Lumiere',
                'slug' => 'inquis-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Lumiere',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'holy-light']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'inquis_rang2_1' => [
                'title' => 'Colere divine',
                'slug' => 'inquis-rang2-1',
                'description' => 'Augmente les degats des sorts sacres',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['inquis_apprenti_1'],
            ],
            'inquis_rang2_2' => [
                'title' => 'Fanatisme',
                'slug' => 'inquis-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['inquis_apprenti_1'],
            ],
            'inquis_rang2_3' => [
                'title' => 'Materia : Lumiere sacree',
                'slug' => 'inquis-rang2-3',
                'description' => 'Permet d\'utiliser la materia Lumiere sacree',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'sacred-light']],
                'requirements' => ['inquis_apprenti_2'],
            ],
            'inquis_rang2_4' => [
                'title' => 'Materia : Purification',
                'slug' => 'inquis-rang2-4',
                'description' => 'Permet d\'utiliser la materia Purification',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'purification']],
                'requirements' => ['inquis_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'inquis_rang3_1' => [
                'title' => 'Materia : Feu sacre',
                'slug' => 'inquis-rang3-1',
                'description' => 'Permet d\'utiliser la materia Feu sacre',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'holy-fire']],
                'requirements' => ['inquis_rang2_1', 'inquis_rang2_2'],
            ],
            'inquis_rang3_2' => [
                'title' => 'Materia : Explosion de vie',
                'slug' => 'inquis-rang3-2',
                'description' => 'Permet d\'utiliser la materia Explosion de vie',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-burst']],
                'requirements' => ['inquis_rang2_3'],
            ],
            'inquis_rang3_3' => [
                'title' => 'Oeil inquisiteur',
                'slug' => 'inquis-rang3-3',
                'description' => 'Augmente la precision des sorts sacres',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['inquis_rang2_4'],
            ],
            'inquis_rang3_4' => [
                'title' => 'Materia : Transfert de vie',
                'slug' => 'inquis-rang3-4',
                'description' => 'Permet d\'utiliser la materia Transfert de vie',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-transfer']],
                'requirements' => ['inquis_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'inquis_rang4_1' => [
                'title' => 'Materia : Jugement sacre',
                'slug' => 'inquis-rang4-1',
                'description' => 'Permet d\'utiliser la materia Jugement sacre — degats + soin',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-intervention']],
                'requirements' => ['inquis_rang3_1', 'inquis_rang3_2'],
            ],
            'inquis_rang4_2' => [
                'title' => 'Materia : Afflux de vitalite',
                'slug' => 'inquis-rang4-2',
                'description' => 'Permet d\'utiliser la materia Afflux de vitalite',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vitality-surge']],
                'requirements' => ['inquis_rang3_3', 'inquis_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'inquis_rang5_1' => [
                'title' => 'Materia : Sentence divine',
                'slug' => 'inquis-rang5-1',
                'description' => 'Permet d\'utiliser la materia Sentence divine',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'divine-sentence']],
                'requirements' => ['inquis_rang4_1', 'inquis_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // ASSASSIN (ombre) — 13 skills, furtivite et critiques
    // =========================================================================
    private function getAssassinSkills(): array
    {
        $d = 'assassin';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'assassin_apprenti_1' => [
                'title' => 'Materia : Embuscade',
                'slug' => 'assassin-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Embuscade',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'ambush']],
            ],
            'assassin_apprenti_2' => [
                'title' => 'Materia : Toucher necrotique',
                'slug' => 'assassin-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Toucher necrotique',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'necrotic-touch']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'assassin_rang2_1' => [
                'title' => 'Lame dans l\'ombre',
                'slug' => 'assassin-rang2-1',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['assassin_apprenti_1'],
            ],
            'assassin_rang2_2' => [
                'title' => 'Frappe vicieuse',
                'slug' => 'assassin-rang2-2',
                'description' => 'Augmente les degats des attaques',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['assassin_apprenti_1'],
            ],
            'assassin_rang2_3' => [
                'title' => 'Materia : Toucher mortel',
                'slug' => 'assassin-rang2-3',
                'description' => 'Permet d\'utiliser la materia Toucher mortel',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-touch']],
                'requirements' => ['assassin_apprenti_2'],
            ],
            'assassin_rang2_4' => [
                'title' => 'Materia : Sangsue vitale',
                'slug' => 'assassin-rang2-4',
                'description' => 'Permet d\'utiliser la materia Sangsue vitale (drain)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'life-leech']],
                'requirements' => ['assassin_apprenti_2'],
            ],
            'assassin_materia_t2' => [
                'title' => 'Materia : Drain vital',
                'slug' => 'assassin-materia-t2',
                'description' => 'Permet d\'utiliser la materia Drain vital (absorption)',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'vital-drain']],
                'requirements' => ['assassin_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'assassin_rang3_1' => [
                'title' => 'Materia : Eclair d\'ombre',
                'slug' => 'assassin-rang3-1',
                'description' => 'Permet d\'utiliser la materia Eclair d\'ombre',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-bolt']],
                'requirements' => ['assassin_rang2_1', 'assassin_rang2_2'],
            ],
            'assassin_rang3_2' => [
                'title' => 'Materia : Emprise de la mort',
                'slug' => 'assassin-rang3-2',
                'description' => 'Permet d\'utiliser la materia Emprise de la mort',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-grip']],
                'requirements' => ['assassin_rang2_3'],
            ],
            'assassin_rang3_3' => [
                'title' => 'Precision assassine',
                'slug' => 'assassin-rang3-3',
                'description' => 'Augmente la precision des attaques furtives',
                'requiredPoints' => 30,
                'domain' => $d,
                'hit' => 2,
                'requirements' => ['assassin_rang2_4'],
            ],
            'assassin_rang3_4' => [
                'title' => 'Materia : Siphon d\'ame',
                'slug' => 'assassin-rang3-4',
                'description' => 'Permet d\'utiliser la materia Siphon d\'ame (drain)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'soul-siphon']],
                'requirements' => ['assassin_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'assassin_rang4_1' => [
                'title' => 'Materia : Coup mortel',
                'slug' => 'assassin-rang4-1',
                'description' => 'Permet d\'utiliser la materia Coup mortel — degats devastateurs',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'deadly-strike']],
                'requirements' => ['assassin_rang3_1', 'assassin_rang3_2'],
            ],
            'assassin_rang4_2' => [
                'title' => 'Materia : Nova de mort',
                'slug' => 'assassin-rang4-2',
                'description' => 'Permet d\'utiliser la materia Nova de mort (AoE)',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-nova']],
                'requirements' => ['assassin_rang3_3', 'assassin_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'assassin_rang5_1' => [
                'title' => 'Materia : Danse des ombres',
                'slug' => 'assassin-rang5-1',
                'description' => 'Permet d\'utiliser la materia Danse des ombres',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-dance']],
                'requirements' => ['assassin_rang4_1', 'assassin_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // SORCIER (ombre) — 13 skills, maledictions et debuffs
    // =========================================================================
    private function getWarlockSkills(): array
    {
        $d = 'warlock';

        return [
            // Rang 1 (0 pts) — 2 skills d'entree
            'warlock_apprenti_1' => [
                'title' => 'Materia : Malefice',
                'slug' => 'warlock-apprenti-1',
                'description' => 'Permet d\'utiliser la materia Malefice',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'hex']],
            ],
            'warlock_apprenti_2' => [
                'title' => 'Materia : Chatiment',
                'slug' => 'warlock-apprenti-2',
                'description' => 'Permet d\'utiliser la materia Chatiment',
                'requiredPoints' => 0,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'punishment']],
            ],

            // Rang 2 (10-20 pts) — 4 skills
            'warlock_rang2_1' => [
                'title' => 'Puissance maudite',
                'slug' => 'warlock-rang2-1',
                'description' => 'Augmente les degats des sorts sombres',
                'requiredPoints' => 10,
                'domain' => $d,
                'damage' => 1,
                'requirements' => ['warlock_apprenti_1'],
            ],
            'warlock_rang2_2' => [
                'title' => 'Regard mauvais',
                'slug' => 'warlock-rang2-2',
                'description' => 'Augmente les chances de coup critique',
                'requiredPoints' => 10,
                'domain' => $d,
                'critical' => 1,
                'requirements' => ['warlock_apprenti_1'],
            ],
            'warlock_rang2_3' => [
                'title' => 'Materia : Emprise de la mort',
                'slug' => 'warlock-rang2-3',
                'description' => 'Permet d\'utiliser la materia Emprise de la mort',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-grip']],
                'requirements' => ['warlock_apprenti_2'],
            ],
            'warlock_rang2_4' => [
                'title' => 'Materia : Guerison des ombres',
                'slug' => 'warlock-rang2-4',
                'description' => 'Permet d\'utiliser la materia Guerison des ombres',
                'requiredPoints' => 15,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-mend']],
                'requirements' => ['warlock_apprenti_2'],
            ],

            // Rang 3 (25-50 pts) — 4 skills
            'warlock_rang3_1' => [
                'title' => 'Materia : Terreur',
                'slug' => 'warlock-rang3-1',
                'description' => 'Permet d\'utiliser la materia Terreur (paralysie)',
                'requiredPoints' => 25,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'terror']],
                'requirements' => ['warlock_rang2_1', 'warlock_rang2_2'],
            ],
            'warlock_rang3_2' => [
                'title' => 'Materia : Rituel sombre',
                'slug' => 'warlock-rang3-2',
                'description' => 'Permet d\'utiliser la materia Rituel sombre (sacrifice + soin)',
                'requiredPoints' => 30,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'dark-ritual']],
                'requirements' => ['warlock_rang2_3'],
            ],
            'warlock_rang3_3' => [
                'title' => 'Canalisation sombre',
                'slug' => 'warlock-rang3-3',
                'description' => 'Augmente la puissance des soins sombres',
                'requiredPoints' => 30,
                'domain' => $d,
                'heal' => 1,
                'requirements' => ['warlock_rang2_4'],
            ],
            'warlock_rang3_4' => [
                'title' => 'Materia : Vague d\'ombre',
                'slug' => 'warlock-rang3-4',
                'description' => 'Permet d\'utiliser la materia Vague d\'ombre (AoE)',
                'requiredPoints' => 40,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'shadow-wave']],
                'requirements' => ['warlock_rang3_1'],
            ],

            // Rang 4 (60-100 pts) — 2 skills
            'warlock_rang4_1' => [
                'title' => 'Materia : Spirale de mort',
                'slug' => 'warlock-rang4-1',
                'description' => 'Permet d\'utiliser la materia Spirale de mort',
                'requiredPoints' => 60,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'death-coil']],
                'requirements' => ['warlock_rang3_1', 'warlock_rang3_2'],
            ],
            'warlock_rang4_2' => [
                'title' => 'Materia : Siphon d\'ame',
                'slug' => 'warlock-rang4-2',
                'description' => 'Permet d\'utiliser la materia Siphon d\'ame (drain)',
                'requiredPoints' => 80,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'soul-siphon']],
                'requirements' => ['warlock_rang3_3', 'warlock_rang3_4'],
            ],

            // Rang 5 (150+ pts) — 1 skill ultime
            'warlock_rang5_1' => [
                'title' => 'Materia : Pacte sombre',
                'slug' => 'warlock-rang5-1',
                'description' => 'Permet d\'utiliser la materia Pacte sombre',
                'requiredPoints' => 150,
                'domain' => $d,
                'actions' => ['materia' => ['unlock' => 'dark-pact']],
                'requirements' => ['warlock_rang4_1', 'warlock_rang4_2'],
            ],
        ];
    }

    // =========================================================================
    // MINEUR (terre/recolte) — extraction de minerais, 5 tiers de progression
    // T1 Cuivre/Étain/Fer → T2 Argent/Or/Cobalt → T3 Mithril/Platine/Sombracier
    // → T4 Adamantite/Astrétal/Orichalque → T5 Améthystite/Voidium
    // =========================================================================
    private function getMinerSkills(): array
    {
        $d = 'miner';

        return [
            // =================================================================
            // RANG 1 (0 pts) — T1 Commun : Cuivre, Étain, Fer
            // =================================================================
            'miner_copper_xs' => [
                'slug' => 'miner-copper-xs',
                'title' => 'Minage du cuivre',
                'description' => 'Permet de miner les filons de cuivre et debloque l\'emplacement de pioche',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-copper-xs', 'spot-copper-s']], ['action' => 'tool_slot.unlock', 'slot' => 'pickaxe'], ['action' => 'equip.tool', 'slugs' => ['pickaxe-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'miner_tin_xs' => [
                'slug' => 'miner-tin-xs',
                'title' => 'Minage de l\'etain',
                'description' => 'Permet de miner les filons d\'etain',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-tin-xs', 'spot-tin-s']]],
                'requiredPoints' => 0,
                'domain' => $d,
                'requirements' => ['miner_copper_xs'],
            ],
            'miner_iron_xs' => [
                'slug' => 'miner-iron-xs',
                'title' => 'Minage du fer debutant',
                'description' => 'Permet de miner les filons de fer basiques et d\'utiliser une pioche en fer',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-iron-xs', 'spot-iron-s']], ['action' => 'equip.tool', 'slugs' => ['pickaxe-iron']]],
                'requiredPoints' => 5,
                'domain' => $d,
                'requirements' => ['miner_copper_xs'],
            ],

            // =================================================================
            // RANG 2 (10-20 pts) — T2 Peu commun : Argent, Or, Cobalt
            // =================================================================
            'miner_efficiency_1' => [
                'slug' => 'miner-efficiency-1',
                'title' => 'Pioche affutee',
                'description' => 'Augmente la vitesse d\'extraction et permet d\'utiliser une pioche en acier',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['pickaxe-steel']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'hit' => 1,
                'requirements' => ['miner_iron_xs'],
            ],
            'miner_silver_xs' => [
                'slug' => 'miner-silver-xs',
                'title' => 'Minage de l\'argent',
                'description' => 'Permet de miner les filons d\'argent',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-silver-xs', 'spot-silver-s']]],
                'requiredPoints' => 10,
                'domain' => $d,
                'requirements' => ['miner_iron_xs'],
            ],
            'miner_gold_xs' => [
                'slug' => 'miner-gold-xs',
                'title' => 'Minage de l\'or debutant',
                'description' => 'Permet de miner les filons d\'or basiques',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-gold-xs', 'spot-gold-s']]],
                'requiredPoints' => 15,
                'domain' => $d,
                'requirements' => ['miner_silver_xs'],
            ],
            'miner_cobalt_xs' => [
                'slug' => 'miner-cobalt-xs',
                'title' => 'Minage du cobalt',
                'description' => 'Permet de miner les filons de cobalt, un minerai d\'un bleu profond',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-cobalt-xs', 'spot-cobalt-s']]],
                'requiredPoints' => 20,
                'domain' => $d,
                'requirements' => ['miner_efficiency_1'],
            ],

            // =================================================================
            // RANG 3 (25-45 pts) — T3 Rare : Mithril, Platine, Sombracier
            // =================================================================
            'miner_yield_1' => [
                'slug' => 'miner-yield-1',
                'title' => 'Filon genereux',
                'description' => 'Chance de doubler les minerais extraits',
                'requiredPoints' => 25,
                'domain' => $d,
                'critical' => 2,
                'requirements' => ['miner_gold_xs'],
            ],
            'miner_mithril_xs' => [
                'slug' => 'miner-mithril-xs',
                'title' => 'Minage du mithril',
                'description' => 'Permet de miner les filons de mithril legendaire',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-mithril-xs', 'spot-mithril-s']]],
                'requiredPoints' => 30,
                'domain' => $d,
                'requirements' => ['miner_cobalt_xs', 'miner_yield_1'],
            ],
            'miner_platinum_xs' => [
                'slug' => 'miner-platinum-xs',
                'title' => 'Minage du platine',
                'description' => 'Permet de miner les filons de platine d\'une purete exceptionnelle',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-platinum-xs']]],
                'requiredPoints' => 35,
                'domain' => $d,
                'requirements' => ['miner_mithril_xs'],
            ],
            'miner_deep_vein' => [
                'slug' => 'miner-deep-vein',
                'title' => 'Veines profondes',
                'description' => 'Permet d\'utiliser une pioche en mithril et augmente les rendements',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['pickaxe-mithril']]],
                'requiredPoints' => 40,
                'domain' => $d,
                'damage' => 1,
                'critical' => 1,
                'requirements' => ['miner_mithril_xs'],
            ],
            'miner_darksteel_xs' => [
                'slug' => 'miner-darksteel-xs',
                'title' => 'Minage du sombracier',
                'description' => 'Permet de miner les filons de sombracier dans les profondeurs',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-darksteel-xs']]],
                'requiredPoints' => 45,
                'domain' => $d,
                'requirements' => ['miner_deep_vein'],
            ],

            // =================================================================
            // RANG 4 (55-80 pts) — T4 Épique : Adamantite, Astrétal, Orichalque
            // =================================================================
            'miner_adamantite_xs' => [
                'slug' => 'miner-adamantite-xs',
                'title' => 'Minage de l\'adamantite',
                'description' => 'Permet de miner les filons d\'adamantite, le minerai le plus dur',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-adamantite-xs']]],
                'requiredPoints' => 55,
                'domain' => $d,
                'requirements' => ['miner_darksteel_xs', 'miner_platinum_xs'],
            ],
            'miner_starmetal_xs' => [
                'slug' => 'miner-starmetal-xs',
                'title' => 'Minage de l\'astretal',
                'description' => 'Permet de miner les filons d\'astretal, un metal tombe des etoiles',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-starmetal-xs']]],
                'requiredPoints' => 65,
                'domain' => $d,
                'requirements' => ['miner_adamantite_xs'],
            ],
            'miner_yield_2' => [
                'slug' => 'miner-yield-2',
                'title' => 'Filon prodigieux',
                'description' => 'Augmente encore les chances de doubler les minerais rares',
                'requiredPoints' => 70,
                'domain' => $d,
                'critical' => 3,
                'hit' => 2,
                'requirements' => ['miner_adamantite_xs'],
            ],
            'miner_orichalcum_xs' => [
                'slug' => 'miner-orichalcum-xs',
                'title' => 'Minage de l\'orichalque',
                'description' => 'Permet de miner les filons d\'orichalque, le metal mythique des anciens',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-orichalcum-xs']]],
                'requiredPoints' => 80,
                'domain' => $d,
                'requirements' => ['miner_starmetal_xs'],
            ],

            // =================================================================
            // RANG 5 (100-150 pts) — T5 Légendaire : Améthystite, Voidium
            // =================================================================
            'miner_amethystite_xs' => [
                'slug' => 'miner-amethystite-xs',
                'title' => 'Minage de l\'amethystite',
                'description' => 'Permet de miner les cristaux d\'amethystite, la gemme signature d\'Amethyste',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-amethystite-xs']]],
                'requiredPoints' => 100,
                'domain' => $d,
                'requirements' => ['miner_orichalcum_xs', 'miner_yield_2'],
            ],
            'miner_master' => [
                'slug' => 'miner-master',
                'title' => 'Maitre mineur',
                'description' => 'Maitrise absolue du minage — acces aux filons de voidium et bonus ultimes',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-voidium-xs']]],
                'requiredPoints' => 150,
                'domain' => $d,
                'damage' => 2,
                'critical' => 2,
                'hit' => 1,
                'requirements' => ['miner_amethystite_xs'],
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
                'description' => 'Permet de recolter les pissenlits basiques et debloque l\'emplacement de faucille',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-dandelion-xs']], ['action' => 'tool_slot.unlock', 'slot' => 'sickle'], ['action' => 'equip.tool', 'slugs' => ['sickle-bronze']]],
                'domain' => $d,
            ],
            'herbalist_mint' => [
                'slug' => 'herbalist-mint-xs',
                'title' => 'Recolte de menthe',
                'description' => 'Permet de recolter la menthe basique et d\'utiliser une faucille en fer',
                'requiredPoints' => 0,
                'actions' => [['action' => 'harvest', 'spots' => ['spot-mint-xs']], ['action' => 'equip.tool', 'slugs' => ['sickle-iron']]],
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
                'description' => 'Augmente les chances de trouver des plantes rares et permet d\'utiliser une faucille en acier',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['sickle-steel']]],
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
                'description' => 'Chance de doubler la quantite de plantes recoltees et permet d\'utiliser une faucille en mithril',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['sickle-mithril']]],
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
                'description' => 'Permet de pecher la truite dans les eaux calmes et debloque l\'emplacement de canne a peche',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-xs']], ['action' => 'tool_slot.unlock', 'slot' => 'fishing_rod'], ['action' => 'equip.tool', 'slugs' => ['fishing-rod-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'fisher_carp_xs' => [
                'slug' => 'fisher-carp-xs',
                'title' => 'Peche de la carpe debutant',
                'description' => 'Permet de pecher la carpe dans les etangs et d\'utiliser une canne a peche en fer',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-carp-xs']], ['action' => 'equip.tool', 'slugs' => ['fishing-rod-iron']]],
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
                'description' => 'Augmente les chances de capture des poissons et permet d\'utiliser une canne a peche en acier',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['fishing-rod-steel']]],
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
                'description' => 'Permet de pecher dans les eaux profondes et les lacs et d\'utiliser une canne a peche en mithril',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-trout-l', 'spot-carp-l']], ['action' => 'equip.tool', 'slugs' => ['fishing-rod-mithril']]],
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
                'description' => 'Permet de depecer les creatures basiques pour obtenir du cuir brut et debloque l\'emplacement de couteau de depecage',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-xs']], ['action' => 'tool_slot.unlock', 'slot' => 'skinning_knife'], ['action' => 'equip.tool', 'slugs' => ['skinning-knife-bronze']]],
                'requiredPoints' => 0,
                'domain' => $d,
            ],
            'skinner_bone_xs' => [
                'slug' => 'skinner-bone-xs',
                'title' => 'Collecte d\'os',
                'description' => 'Permet de recuperer les os des creatures vaincues et d\'utiliser un couteau en fer',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-bone-xs']], ['action' => 'equip.tool', 'slugs' => ['skinning-knife-iron']]],
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
                'description' => 'Augmente la precision du depecage et permet d\'utiliser un couteau en acier',
                'actions' => [['action' => 'equip.tool', 'slugs' => ['skinning-knife-steel']]],
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
                'description' => 'Permet de depecer les creatures rares et exotiques et d\'utiliser un couteau en mithril',
                'actions' => [['action' => 'harvest', 'spots' => ['spot-hide-l', 'spot-bone-l']], ['action' => 'equip.tool', 'slugs' => ['skinning-knife-mithril']]],
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
                'description' => 'Permet de forger des dagues en fer et debloque l\'emplacement de marteau de forge',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-iron-dagger']], ['action' => 'tool_slot.unlock', 'slot' => 'hammer']],
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
                'description' => 'Permet de confectionner des armures legeres en cuir brut et debloque l\'emplacement de kit de tannage',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-leather-vest']], ['action' => 'tool_slot.unlock', 'slot' => 'tanning_kit']],
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
                'description' => 'Permet de brasser des potions de soin mineures et debloque l\'emplacement de mortier d\'alchimie',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-health-potion-minor']], ['action' => 'tool_slot.unlock', 'slot' => 'mortar']],
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
                'description' => 'Permet de tailler des gemmes brutes en pierres utilisables et debloque l\'emplacement de burin de joaillier',
                'actions' => [['action' => 'craft', 'recipes' => ['recipe-cut-gem-basic']], ['action' => 'tool_slot.unlock', 'slot' => 'chisel']],
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
