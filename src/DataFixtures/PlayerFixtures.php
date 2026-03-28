<?php

namespace App\DataFixtures;

use App\DataFixtures\Game\RaceFixtures;
use App\DataFixtures\Game\SkillFixtures;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\Game\Race;
use App\Entity\Game\Skill;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PlayerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Joueur pour l'admin remy
        $playerRemy = new Player();
        $playerRemy->setName('Rémy');
        $playerRemy->setLife(20);
        $playerRemy->setMaxLife(20);
        $playerRemy->setEnergy(80);
        $playerRemy->setMaxEnergy(100);
        $playerRemy->setMap($this->getReference('map_1', Map::class));
        $playerRemy->setCoordinates('85.34');
        $playerRemy->setLastCoordinates('85.34');
        $playerRemy->setUser($this->getReference('user_remy', User::class));
        $playerRemy->setClassType('admin');
        $playerRemy->setRace($this->getReference('race_human', Race::class));
        $playerRemy->setGils(100000);
        $playerRemy->setCreatedAt(new \DateTime());
        $playerRemy->setUpdatedAt(new \DateTime());
        $playerRemy->addSkill($this->getReference('pyro_apprenti_1', Skill::class));
        $playerRemy->addSkill($this->getReference('soldier_apprenti_1', Skill::class));
        // Skills métier rang 1 (débloquent les emplacements d'outils)
        $playerRemy->addSkill($this->getReference('miner_ruby_xs', Skill::class));
        $playerRemy->addSkill($this->getReference('herbalist_dandelion', Skill::class));
        $playerRemy->addSkill($this->getReference('fisher_trout_xs', Skill::class));
        $playerRemy->addSkill($this->getReference('skinner_hide_xs', Skill::class));
        $playerRemy->addSkill($this->getReference('smith_dagger', Skill::class));
        $playerRemy->addSkill($this->getReference('leather_light_armor', Skill::class));
        $playerRemy->addSkill($this->getReference('alchi_health_pot', Skill::class));
        $playerRemy->addSkill($this->getReference('jewel_cut_basic', Skill::class));
        // Déblocage des 8 emplacements d'outils
        $playerRemy->setUnlockedToolSlots([
            'pickaxe', 'sickle', 'fishing_rod', 'skinning_knife',
            'hammer', 'tanning_kit', 'mortar', 'chisel',
        ]);
        $manager->persist($playerRemy);
        $this->addReference('player_remy', $playerRemy);

        // Création du joueur demo
        $playerDemo = new Player();
        $playerDemo->setName('Player demo');
        $playerDemo->setLife(20);
        $playerDemo->setMaxLife(20);
        $playerDemo->setEnergy(80);
        $playerDemo->setMaxEnergy(100);
        $playerDemo->setMap($this->getReference('map_1', Map::class));
        $playerDemo->setCoordinates('85.35');
        $playerDemo->setLastCoordinates('85.35');
        $playerDemo->setUser($this->getReference('user_demo', User::class));
        $playerDemo->setClassType('demo');
        $playerDemo->setRace($this->getReference('race_human', Race::class));
        $playerDemo->setCreatedAt(new \DateTime());
        $playerDemo->setUpdatedAt(new \DateTime());

        // Ajout des compétences
        $playerDemo->addSkill($this->getReference('pyro_apprenti_1', Skill::class));
        $playerDemo->addSkill($this->getReference('soldier_apprenti_1', Skill::class));
        // Skills métier de base (pioche + canne)
        $playerDemo->addSkill($this->getReference('miner_ruby_xs', Skill::class));
        $playerDemo->addSkill($this->getReference('fisher_trout_xs', Skill::class));
        $playerDemo->setUnlockedToolSlots(['pickaxe', 'fishing_rod']);

        $manager->persist($playerDemo);
        $this->addReference('player_demo', $playerDemo);

        // Création du joueur demo 2
        $playerDemo2 = new Player();
        $playerDemo2->setName('Player demo 2');
        $playerDemo2->setLife(20);
        $playerDemo2->setMaxLife(20);
        $playerDemo2->setEnergy(80);
        $playerDemo2->setMaxEnergy(100);
        $playerDemo2->setMap($this->getReference('map_1', Map::class));
        $playerDemo2->setCoordinates('85.36');
        $playerDemo2->setLastCoordinates('85.36');
        $playerDemo2->setUser($this->getReference('user_demo_2', User::class));
        $playerDemo2->setClassType('demo-2');
        $playerDemo2->setRace($this->getReference('race_human', Race::class));
        $playerDemo2->setCreatedAt(new \DateTime());
        $playerDemo2->setUpdatedAt(new \DateTime());

        // Ajout des compétences
        $playerDemo2->addSkill($this->getReference('pyro_apprenti_1', Skill::class));
        $playerDemo2->addSkill($this->getReference('soldier_apprenti_1', Skill::class));

        $manager->persist($playerDemo2);
        $this->addReference('player_demo_2', $playerDemo2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            MapFixtures::class,
            SkillFixtures::class,
            RaceFixtures::class,
        ];
    }
}
