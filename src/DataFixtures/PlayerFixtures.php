<?php

namespace App\DataFixtures;

use App\DataFixtures\Game\RaceFixtures;
use App\DataFixtures\Game\SkillFixtures;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\Zone;
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
        // Position de reference du joueur = sa zone (regle projet #7). Les
        // fixtures laissaient les joueurs rattaches a la seule « Carte de test »,
        // heritee de l'ere carte : ils demarraient donc sans zone, et l'ecran de
        // zone se rabattait sur le hub. On les place explicitement (ZON-23).
        $zones = $manager->getRepository(Zone::class);
        // Compte de test/admin : en pleine nature, pour que les actions de zone
        // (chasser, recolter) soient exercables — c'est le compte utilise par les
        // tests E2E.
        $wilderness = $zones->findOneBy(['slug' => 'foret-des-murmures']);
        // Comptes de demonstration : au hub, comme un nouveau joueur.
        $hub = $zones->findOneBy(['slug' => 'village-de-lumiere']);

        // Joueur pour l'admin remy
        $playerRemy = new Player();
        $playerRemy->setName('Rémy');
        $playerRemy->setLife(20);
        $playerRemy->setMaxLife(20);
        $playerRemy->setEnergy(80);
        $playerRemy->setMaxEnergy(100);
        $playerRemy->setMap($this->getReference('map_1', Map::class));
        if ($wilderness instanceof Zone) {
            $playerRemy->setCurrentZone($wilderness);
        }
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
        if ($hub instanceof Zone) {
            $playerDemo->setCurrentZone($hub);
        }
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
        if ($hub instanceof Zone) {
            $playerDemo2->setCurrentZone($hub);
        }
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
            ZoneGraphFixtures::class,
        ];
    }
}
