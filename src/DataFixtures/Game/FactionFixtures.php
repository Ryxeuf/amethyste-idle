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
                // Slug herite : la loi de nommage (GAME_WORLD §1) ne tolere que
                // les slugs, le libelle porte le canon.
                'slug' => 'ombres',
                'name' => 'Confrérie des Ruelles',
                'name_translations' => ['en' => 'Brotherhood of the Alleys'],
                'description' => 'Un réseau clandestin de voleurs, espions et assassins. Bien que leur réputation soit douteuse, leur savoir-faire est inégalé pour ceux qui savent gagner leur confiance.',
                'description_translations' => ['en' => 'A clandestine network of thieves, spies and assassins. Although their reputation is questionable, their craftsmanship is unmatched for those who can earn their trust.'],
                'icon' => 'faction_ombres',
                'ref' => 'faction_ombres',
            ],
            [
                // FAC-04a — la cinquieme maison (GAME_WORLD §12.2). Elle etait
                // attendue partout : la paire de tension fonderie <-> mages, la
                // route de geste materia_melt et la consequence Hostile
                // buyback_floor_closed sont declarees depuis FAC-01/02/03 et
                // s'activent d'elles-memes le jour ou ce slug est seme.
                //
                // Point de conception tenu : la Fonderie n'est pas un empire et
                // ne conspire pas. Elle eclaire les cites, paie bien, et a
                // raison a court terme — c'est ce qui la rend impossible a
                // combattre. L'antagoniste du jeu est le Reflux ; elle est ce
                // qui l'accelere en ameliorant la vie de tout le monde.
                'slug' => 'fonderie',
                'name' => 'La Fonderie',
                'name_translations' => ['en' => 'The Foundry'],
                'description' => 'Le grand consortium qui brûle le cristal pour en tirer lumière, chaleur et force. Ses comptoirs paient bien, ses enseignes éclairent les villes, et ses gens sont sincèrement inquiets de voir des villages sans lumière. Chaque améthyste fondue disparaît pourtant du monde, à jamais.',
                'description_translations' => ['en' => 'The great consortium that burns crystal for light, warmth and power. Its counters pay well, its signs light the cities, and its people are sincerely worried about villages going dark. Yet every amethyst melted is gone from the world, forever.'],
                'icon' => 'faction_fonderie',
                'ref' => 'faction_fonderie',
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
