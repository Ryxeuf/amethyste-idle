<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Race;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RaceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $human = new Race();
        $human->setSlug('human');
        $human->setName('Humain');
        $human->setDescription('Les humains sont la race la plus répandue. Polyvalents et adaptables, ils ne possèdent aucun bonus ni malus particulier.');
        $human->setStatModifiers(['life' => 0, 'energy' => 0, 'speed' => 0, 'hit' => 0]);
        $human->setAvailableAtCreation(true);
        $human->setCreatedAt(new \DateTime());
        $human->setUpdatedAt(new \DateTime());

        $manager->persist($human);
        $this->addReference('race_human', $human);

        $manager->flush();
    }
}
