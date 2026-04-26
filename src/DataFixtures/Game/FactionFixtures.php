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
                'name_translations' => ['en' => 'Merchants Guild'],
                'description' => 'Une puissante guilde commerciale qui contrôle les routes marchandes du royaume. Ses membres bénéficient de prix avantageux et d\'un accès à des marchandises rares.',
                'description_translations' => ['en' => 'A powerful merchant guild that controls the trade routes of the realm. Its members enjoy favorable prices and access to rare goods.'],
                'icon' => 'faction_marchands',
                'ref' => 'faction_marchands',
            ],
            [
                'slug' => 'chevaliers',
                'name' => 'Ordre des Chevaliers',
                'name_translations' => ['en' => 'Order of Knights'],
                'description' => 'L\'ordre militaire le plus prestigieux du royaume. Ses chevaliers protègent les faibles et combattent les ténèbres. Rejoindre leurs rangs ouvre l\'accès à un équipement martial d\'exception.',
                'description_translations' => ['en' => 'The most prestigious military order in the realm. Its knights protect the weak and fight the darkness. Joining their ranks unlocks access to exceptional martial gear.'],
                'icon' => 'faction_chevaliers',
                'ref' => 'faction_chevaliers',
            ],
            [
                'slug' => 'mages',
                'name' => 'Cercle des Mages',
                'name_translations' => ['en' => 'Circle of Mages'],
                'description' => 'Une assemblée de mages et d\'érudits qui étudient les arcanes et les mystères du monde. Leurs connaissances permettent d\'accéder à de puissantes materia et recettes alchimiques.',
                'description_translations' => ['en' => 'An assembly of mages and scholars who study the arcane and the mysteries of the world. Their knowledge grants access to powerful materia and alchemical recipes.'],
                'icon' => 'faction_mages',
                'ref' => 'faction_mages',
            ],
            [
                'slug' => 'ombres',
                'name' => 'Confrérie des Ombres',
                'name_translations' => ['en' => 'Brotherhood of Shadows'],
                'description' => 'Un réseau clandestin de voleurs, espions et assassins. Bien que leur réputation soit douteuse, leur savoir-faire est inégalé pour ceux qui savent gagner leur confiance.',
                'description_translations' => ['en' => 'A clandestine network of thieves, spies and assassins. Although their reputation is questionable, their craftsmanship is unmatched for those who can earn their trust.'],
                'icon' => 'faction_ombres',
                'ref' => 'faction_ombres',
            ],
        ];

        foreach ($factions as $data) {
            $faction = new Faction();
            $faction->setSlug($data['slug']);
            $faction->setName($data['name']);
            $faction->setNameTranslations($data['name_translations'] ?? null);
            $faction->setDescription($data['description']);
            $faction->setDescriptionTranslations($data['description_translations'] ?? null);
            $faction->setIcon($data['icon']);
            $faction->setCreatedAt(new \DateTime());
            $faction->setUpdatedAt(new \DateTime());

            $manager->persist($faction);
            $this->addReference($data['ref'], $faction);
        }

        $manager->flush();
    }
}
