<?php

namespace App\DataFixtures;

use App\Entity\Game\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DomainFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $domains = [
            // Feu
            'pyromancy' => ['title' => 'Pyromancien', 'element' => 'fire', 'title_translations' => ['en' => 'Pyromancer']],
            'berserker' => ['title' => 'Berserker', 'element' => 'fire', 'title_translations' => ['en' => 'Berserker']],
            'artificer' => ['title' => 'Artificier', 'element' => 'fire', 'title_translations' => ['en' => 'Artificer']],
            // Eau
            'hydromancer' => ['title' => 'Hydromancien', 'element' => 'water', 'title_translations' => ['en' => 'Hydromancer']],
            'healer' => ['title' => 'Guérisseur', 'element' => 'water', 'title_translations' => ['en' => 'Healer']],
            'tidecaller' => ['title' => 'Marémancien', 'element' => 'water', 'title_translations' => ['en' => 'Tidecaller']],
            // Air
            'stormcaller' => ['title' => 'Foudromancien', 'element' => 'air', 'title_translations' => ['en' => 'Stormcaller']],
            'archer' => ['title' => 'Archer', 'element' => 'air', 'title_translations' => ['en' => 'Archer']],
            'wanderer' => ['title' => 'Vagabond', 'element' => 'air', 'title_translations' => ['en' => 'Wanderer']],
            // Terre
            'geomancer' => ['title' => 'Géomancien', 'element' => 'earth', 'title_translations' => ['en' => 'Geomancer']],
            'defender' => ['title' => 'Défenseur', 'element' => 'earth', 'title_translations' => ['en' => 'Defender']],
            'guardian' => ['title' => 'Gardien', 'element' => 'earth', 'title_translations' => ['en' => 'Guardian']],
            // Métal
            'soldier' => ['title' => 'Soldat', 'element' => 'metal', 'title_translations' => ['en' => 'Soldier']],
            'knight' => ['title' => 'Chevalier', 'element' => 'metal', 'title_translations' => ['en' => 'Knight']],
            'engineer' => ['title' => 'Ingénieur', 'element' => 'metal', 'title_translations' => ['en' => 'Engineer']],
            // Bête
            'hunter' => ['title' => 'Chasseur', 'element' => 'beast', 'title_translations' => ['en' => 'Hunter']],
            'tamer' => ['title' => 'Dompteur', 'element' => 'beast', 'title_translations' => ['en' => 'Tamer']],
            'druid' => ['title' => 'Druide', 'element' => 'beast', 'title_translations' => ['en' => 'Druid']],
            // Lumière
            'paladin' => ['title' => 'Paladin', 'element' => 'light', 'title_translations' => ['en' => 'Paladin']],
            'priest' => ['title' => 'Prêtre', 'element' => 'light', 'title_translations' => ['en' => 'Priest']],
            'inquisitor' => ['title' => 'Inquisiteur', 'element' => 'light', 'title_translations' => ['en' => 'Inquisitor']],
            // Ombre
            'assassin' => ['title' => 'Assassin', 'element' => 'dark', 'title_translations' => ['en' => 'Assassin']],
            'necromancer' => ['title' => 'Nécromancien', 'element' => 'dark', 'title_translations' => ['en' => 'Necromancer']],
            'warlock' => ['title' => 'Sorcier', 'element' => 'dark', 'title_translations' => ['en' => 'Warlock']],
            // Récolte
            'miner' => ['title' => 'Mineur', 'element' => 'earth', 'title_translations' => ['en' => 'Miner']],
            'herbalist' => ['title' => 'Herboriste', 'element' => 'beast', 'title_translations' => ['en' => 'Herbalist']],
            'fisherman' => ['title' => 'Pêcheur', 'element' => 'water', 'title_translations' => ['en' => 'Fisherman']],
            'skinner' => ['title' => 'Dépeceur', 'element' => 'beast', 'title_translations' => ['en' => 'Skinner']],
            // Craft
            'blacksmith' => ['title' => 'Forgeron', 'element' => 'metal', 'title_translations' => ['en' => 'Blacksmith']],
            'leatherworker' => ['title' => 'Tanneur', 'element' => 'beast', 'title_translations' => ['en' => 'Leatherworker']],
            'alchimist' => ['title' => 'Alchimiste', 'element' => 'water', 'title_translations' => ['en' => 'Alchemist']],
            'jeweller' => ['title' => 'Joaillier', 'element' => 'earth', 'title_translations' => ['en' => 'Jeweler']],
        ];

        foreach ($domains as $key => $data) {
            $domain = new Domain();
            $domain->setTitle($data['title']);
            $domain->setElement($data['element']);
            if (isset($data['title_translations']) && is_array($data['title_translations'])) {
                $domain->setTitleTranslations($data['title_translations']);
            }
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
