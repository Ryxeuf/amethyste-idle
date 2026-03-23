<?php

namespace App\DataFixtures;

use App\Entity\Game\Domain;
use App\Entity\Game\DomainSynergy;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DomainSynergyFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $synergies = [
            [
                'domainA' => 'pyromancy',
                'domainB' => 'soldier',
                'name' => 'Forge ardente',
                'description' => 'La maitrise du feu et du metal forge des armes devastatrices.',
                'bonusType' => 'damage',
                'bonusValue' => 10,
            ],
            [
                'domainA' => 'hydromancer',
                'domainB' => 'paladin',
                'name' => 'Purification',
                'description' => 'L\'eau et la lumiere combinees amplifient les soins.',
                'bonusType' => 'heal',
                'bonusValue' => 15,
            ],
            [
                'domainA' => 'stormcaller',
                'domainB' => 'archer',
                'name' => 'Oeil du faucon',
                'description' => 'Le vent guide les projectiles avec une precision surnaturelle.',
                'bonusType' => 'hit',
                'bonusValue' => 10,
            ],
            [
                'domainA' => 'assassin',
                'domainB' => 'hunter',
                'name' => 'Embuscade',
                'description' => 'Les techniques furtives et la connaissance de la faune multiplient les coups critiques.',
                'bonusType' => 'critical',
                'bonusValue' => 8,
            ],
            [
                'domainA' => 'geomancer',
                'domainB' => 'guardian',
                'name' => 'Rempart de pierre',
                'description' => 'La terre et la protection fusionnent en une defense infranchissable.',
                'bonusType' => 'life',
                'bonusValue' => 20,
            ],
            [
                'domainA' => 'necromancer',
                'domainB' => 'healer',
                'name' => 'Drain vital',
                'description' => 'Maitriser la mort et la guerison permet de voler la force vitale des ennemis.',
                'bonusType' => 'damage',
                'bonusValue' => 8,
            ],
            [
                'domainA' => 'berserker',
                'domainB' => 'defender',
                'name' => 'Fureur blindee',
                'description' => 'La rage du berserker couplee a la resistance du defenseur est un equilibre mortel.',
                'bonusType' => 'damage',
                'bonusValue' => 6,
            ],
            [
                'domainA' => 'druid',
                'domainB' => 'priest',
                'name' => 'Harmonie naturelle',
                'description' => 'La communion avec la nature et la foi divine amplifient toute guerison.',
                'bonusType' => 'heal',
                'bonusValue' => 12,
            ],
        ];

        foreach ($synergies as $data) {
            /** @var Domain $domainA */
            $domainA = $this->getReference($data['domainA']);
            /** @var Domain $domainB */
            $domainB = $this->getReference($data['domainB']);

            $synergy = new DomainSynergy();
            $synergy->setDomainA($domainA);
            $synergy->setDomainB($domainB);
            $synergy->setName($data['name']);
            $synergy->setDescription($data['description']);
            $synergy->setBonusType($data['bonusType']);
            $synergy->setBonusValue($data['bonusValue']);

            $manager->persist($synergy);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
        ];
    }
}
