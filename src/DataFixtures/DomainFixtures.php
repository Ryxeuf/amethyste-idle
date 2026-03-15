<?php

namespace App\DataFixtures;

use App\Entity\Game\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DomainFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des domaines
        $domains = [
            'pyromancy' => 'Pyromancie',
            'soldier' => 'Soldat',
            'healer' => 'Soigneur',
            'defender' => 'Défenseur',
            'necro' => 'Nécro',
            'white_wizard' => 'Mage blanc',
            'druid' => 'Druide',
            'fisherman' => 'Pêcheur',
            'miner' => 'Mineur',
            'herbalist' => 'Herboriste',
            'skinner' => 'Dépeceur',
            'blacksmith' => 'Forgeron',
            'leatherworker' => 'Tanneur',
            'alchimist' => 'Alchimiste',
            'jeweller' => 'Joaillier',
        ];

        foreach ($domains as $key => $title) {
            $domain = new Domain();
            $domain->setTitle($title);
            $domain->setRandomSeed(rand(1, 1000));
            $domain->setGraphHeight(rand(5, 10));
            $domain->setCreatedAt(new \DateTime());
            $domain->setUpdatedAt(new \DateTime());

            $manager->persist($domain);
            $this->addReference($key, $domain);
        }

        $manager->flush();
    }
}
