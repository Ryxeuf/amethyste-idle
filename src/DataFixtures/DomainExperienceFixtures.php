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