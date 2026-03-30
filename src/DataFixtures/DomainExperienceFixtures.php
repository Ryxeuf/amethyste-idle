<?php

namespace App\DataFixtures;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DomainExperienceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Expérience de domaine pour le joueur demo
        $demoPyrotechny = new DomainExperience();
        $demoPyrotechny->setPlayer($this->getReference('player_demo', Player::class));
        $demoPyrotechny->setDomain($this->getReference('pyromancy', Domain::class));
        $demoPyrotechny->setTotalExperience(100);
        $demoPyrotechny->setUsedExperience(0);
        $demoPyrotechny->setCreatedAt(new \DateTime());
        $demoPyrotechny->setUpdatedAt(new \DateTime());

        $manager->persist($demoPyrotechny);
        $this->addReference('demo_pyrotechny', $demoPyrotechny);

        // Expérience de domaine pour le joueur demo (soldat)
        $demoSoldier = new DomainExperience();
        $demoSoldier->setPlayer($this->getReference('player_demo', Player::class));
        $demoSoldier->setDomain($this->getReference('soldier', Domain::class));
        $demoSoldier->setTotalExperience(0);
        $demoSoldier->setUsedExperience(0);
        $demoSoldier->setCreatedAt(new \DateTime());
        $demoSoldier->setUpdatedAt(new \DateTime());

        $manager->persist($demoSoldier);
        $this->addReference('demo_soldier', $demoSoldier);

        // Expérience de domaine pour le joueur demo (mineur)
        $demoMiner = new DomainExperience();
        $demoMiner->setPlayer($this->getReference('player_demo', Player::class));
        $demoMiner->setDomain($this->getReference('miner', Domain::class));
        $demoMiner->setTotalExperience(100);
        $demoMiner->setUsedExperience(0);
        $demoMiner->setCreatedAt(new \DateTime());
        $demoMiner->setUpdatedAt(new \DateTime());

        $manager->persist($demoMiner);
        $this->addReference('demo_miner', $demoMiner);

        // Expérience de domaine pour le joueur demo (herboriste)
        $demoHerbalist = new DomainExperience();
        $demoHerbalist->setPlayer($this->getReference('player_demo', Player::class));
        $demoHerbalist->setDomain($this->getReference('herbalist', Domain::class));
        $demoHerbalist->setTotalExperience(100);
        $demoHerbalist->setUsedExperience(0);
        $demoHerbalist->setCreatedAt(new \DateTime());
        $demoHerbalist->setUpdatedAt(new \DateTime());

        $manager->persist($demoHerbalist);
        $this->addReference('demo_herbalist', $demoHerbalist);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PlayerFixtures::class,
            DomainFixtures::class,
        ];
    }
}
