<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Faction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FactionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $factions = [
            [
                'slug' => 'marchands',
                'name' => 'Guilde des Marchands',
                'description' => 'Une puissante guilde commerciale qui contrôle les routes marchandes du royaume. Ses membres bénéficient de prix avantageux et d\'un accès à des marchandises rares.',
                'icon' => 'faction_marchands',
                'ref' => 'faction_marchands',
            ],
            [
                'slug' => 'chevaliers',
                'name' => 'Ordre des Chevaliers',
                'description' => 'L\'ordre militaire le plus prestigieux du royaume. Ses chevaliers protègent les faibles et combattent les ténèbres. Rejoindre leurs rangs ouvre l\'accès à un équipement martial d\'exception.',
                'icon' => 'faction_chevaliers',
                'ref' => 'faction_chevaliers',
            ],
            [
                'slug' => 'mages',
                'name' => 'Cercle des Mages',
                'description' => 'Une assemblée de mages et d\'érudits qui étudient les arcanes et les mystères du monde. Leurs connaissances permettent d\'accéder à de puissantes materia et recettes alchimiques.',
                'icon' => 'faction_mages',
                'ref' => 'faction_mages',
            ],
            [
                'slug' => 'ombres',
                'name' => 'Confrérie des Ombres',
                'description' => 'Un réseau clandestin de voleurs, espions et assassins. Bien que leur réputation soit douteuse, leur savoir-faire est inégalé pour ceux qui savent gagner leur confiance.',
                'icon' => 'faction_ombres',
                'ref' => 'faction_ombres',
            ],
        ];

        foreach ($factions as $data) {
            $faction = new Faction();
            $faction->setSlug($data['slug']);
            $faction->setName($data['name']);
            $faction->setDescription($data['description']);
            $faction->setIcon($data['icon']);
            $faction->setCreatedAt(new \DateTime());
            $faction->setUpdatedAt(new \DateTime());

            $manager->persist($faction);
            $this->addReference($data['ref'], $faction);
        }

        $manager->flush();
    }
}
