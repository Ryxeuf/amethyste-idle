<?php

namespace App\DataFixtures;

use App\Entity\Game\Skill;
use App\Entity\Game\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SkillFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création de la compétence Pyro Materia 1
        $pyroMateria1 = new Skill();
        $pyroMateria1->setTitle('Pyro Materia 1');
        $pyroMateria1->setSlug('pyro-materia-1');
        $pyroMateria1->setDescription('Une materia de feu de niveau 1');
        $pyroMateria1->setRequiredPoints(10);
        $pyroMateria1->setDomain($this->getReference('pyromancy', Domain::class));
        $pyroMateria1->setDamage(5);
        $pyroMateria1->setHit(80);
        $pyroMateria1->setCritical(10);
        $pyroMateria1->setLife(0);
        $pyroMateria1->setHeal(0);
        $pyroMateria1->setActions(['type' => 'attack', 'element' => 'fire']);
        $pyroMateria1->setCreatedAt(new \DateTime());
        $pyroMateria1->setUpdatedAt(new \DateTime());
        
        $manager->persist($pyroMateria1);
        $this->addReference('pyro_materia_1', $pyroMateria1);
        
        // Création de la compétence Soldat Apprenti
        $soldierApprentice = new Skill();
        $soldierApprentice->setTitle('Soldat Apprenti');
        $soldierApprentice->setSlug('soldier-apprentice');
        $soldierApprentice->setDescription('Compétence de base du soldat');
        $soldierApprentice->setRequiredPoints(5);
        $soldierApprentice->setDomain($this->getReference('soldier', Domain::class));
        $soldierApprentice->setDamage(3);
        $soldierApprentice->setHit(90);
        $soldierApprentice->setCritical(5);
        $soldierApprentice->setLife(10);
        $soldierApprentice->setHeal(0);
        $soldierApprentice->setActions(['type' => 'attack', 'element' => 'physical']);
        $soldierApprentice->setCreatedAt(new \DateTime());
        $soldierApprentice->setUpdatedAt(new \DateTime());
        
        $manager->persist($soldierApprentice);
        $this->addReference('soldier_apprentice', $soldierApprentice);
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
        ];
    }
} 