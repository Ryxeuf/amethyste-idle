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
            'pyromancy' => ['title' => 'Pyromancien', 'element' => 'fire'],
            'berserker' => ['title' => 'Berserker', 'element' => 'fire'],
            'artificer' => ['title' => 'Artificier', 'element' => 'fire'],
            // Eau
            'hydromancer' => ['title' => 'Hydromancien', 'element' => 'water'],
            'healer' => ['title' => 'Guérisseur', 'element' => 'water'],
            'tidecaller' => ['title' => 'Marémancien', 'element' => 'water'],
            // Air
            'stormcaller' => ['title' => 'Foudromancien', 'element' => 'air'],
            'archer' => ['title' => 'Archer', 'element' => 'air'],
            'wanderer' => ['title' => 'Vagabond', 'element' => 'air'],
            // Terre
            'geomancer' => ['title' => 'Géomancien', 'element' => 'earth'],
            'defender' => ['title' => 'Défenseur', 'element' => 'earth'],
            'guardian' => ['title' => 'Gardien', 'element' => 'earth'],
            // Métal
            'soldier' => ['title' => 'Soldat', 'element' => 'metal'],
            'knight' => ['title' => 'Chevalier', 'element' => 'metal'],
            'engineer' => ['title' => 'Ingénieur', 'element' => 'metal'],
            // Bête
            'hunter' => ['title' => 'Chasseur', 'element' => 'beast'],
            'tamer' => ['title' => 'Dompteur', 'element' => 'beast'],
            'druid' => ['title' => 'Druide', 'element' => 'beast'],
            // Lumière
            'paladin' => ['title' => 'Paladin', 'element' => 'light'],
            'priest' => ['title' => 'Prêtre', 'element' => 'light'],
            'inquisitor' => ['title' => 'Inquisiteur', 'element' => 'light'],
            // Ombre
            'assassin' => ['title' => 'Assassin', 'element' => 'dark'],
            'necromancer' => ['title' => 'Nécromancien', 'element' => 'dark'],
            'warlock' => ['title' => 'Sorcier', 'element' => 'dark'],
            // Récolte
            'miner' => ['title' => 'Mineur', 'element' => 'earth'],
            'herbalist' => ['title' => 'Herboriste', 'element' => 'beast'],
            'fisherman' => ['title' => 'Pêcheur', 'element' => 'water'],
            'skinner' => ['title' => 'Dépeceur', 'element' => 'beast'],
            // Craft
            'blacksmith' => ['title' => 'Forgeron', 'element' => 'metal'],
            'leatherworker' => ['title' => 'Tanneur', 'element' => 'beast'],
            'alchimist' => ['title' => 'Alchimiste', 'element' => 'water'],
            'jeweller' => ['title' => 'Joaillier', 'element' => 'earth'],
        ];

        foreach ($domains as $key => $data) {
            $domain = new Domain();
            $domain->setTitle($data['title']);
            $domain->setElement($data['element']);
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
