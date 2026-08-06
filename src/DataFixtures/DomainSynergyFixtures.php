<?php

namespace App\DataFixtures;

use App\Entity\Game\Domain;
use App\Entity\Game\DomainSynergy;
use App\Enum\AccointanceForm;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Les accointances : recompenser le melange sans le rendre obligatoire (ARC-16).
 *
 * GAME_ARCHETYPES § 9.7. Les huit paires livrees donnaient des **statistiques
 * plates** hors de tout arbre ; elles portent desormais une **forme**, et la
 * seule qui ait un lecteur : *ce qu'on porte pour l'un parle aussi pour l'autre*.
 *
 * **Aucune n'a ete supprimee, et c'est mesure plutot que commode** : les huit
 * paires sont toutes des couples ou l'un porte ce que l'autre ne porte pas —
 * un feu et une epee, une eau et une lumiere, un vent et un arc. La forme
 * `domain_expression` les traduit sans rien inventer : *l'epee du soldat fait
 * parler la pyromancie*. Aucune ne devient necessaire, aucune ne rend un point.
 */
class DomainSynergyFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $synergies = [
            [
                'domainA' => 'pyromancy',
                'domainB' => 'soldier',
                'name' => 'Forge ardente',
                'description' => 'Qui a forge aux cotes des soldats fait parler son feu une epee a la main.',
                'form' => AccointanceForm::DomainExpression,
            ],
            [
                'domainA' => 'hydromancer',
                'domainB' => 'paladin',
                'name' => 'Purification',
                'description' => 'L\'eau benie compte comme lumiere, et la lumiere comme eau : on officie avec ce qu\'on a.',
                'form' => AccointanceForm::DomainExpression,
            ],
            [
                'domainA' => 'stormcaller',
                'domainB' => 'archer',
                'name' => 'Oeil du faucon',
                'description' => 'Qui a lu le vent le lit encore un arc en main, sans materia d\'air au doigt.',
                'form' => AccointanceForm::DomainExpression,
            ],
            [
                'domainA' => 'assassin',
                'domainB' => 'hunter',
                'name' => 'Embuscade',
                'description' => 'La lame et l\'affut se comprennent : l\'arme de l\'un reveille l\'ecole de l\'autre.',
                'form' => AccointanceForm::DomainExpression,
            ],
            [
                'domainA' => 'geomancer',
                'domainB' => 'guardian',
                'name' => 'Rempart de pierre',
                'description' => 'La pierre qu\'on porte au bras vaut la pierre qu\'on sertit au doigt.',
                'form' => AccointanceForm::DomainExpression,
            ],
            [
                'domainA' => 'necromancer',
                'domainB' => 'healer',
                'name' => 'Drain vital',
                'description' => 'Les deux ecoles tiennent le meme fil : ce qui exprime l\'une exprime l\'autre.',
                'form' => AccointanceForm::DomainExpression,
            ],
            [
                'domainA' => 'berserker',
                'domainB' => 'defender',
                'name' => 'Fureur blindee',
                'description' => 'La hache et le bouclier s\'apprennent ensemble, et se parlent ensuite.',
                'form' => AccointanceForm::DomainExpression,
            ],
            [
                'domainA' => 'druid',
                'domainB' => 'priest',
                'name' => 'Harmonie naturelle',
                'description' => 'La sente et l\'autel se repondent : l\'un s\'exprime dans ce que l\'autre porte.',
                'form' => AccointanceForm::DomainExpression,
            ],
        ];

        foreach ($synergies as $data) {
            /** @var Domain $domainA */
            $domainA = $this->getReference($data['domainA'], Domain::class);
            /** @var Domain $domainB */
            $domainB = $this->getReference($data['domainB'], Domain::class);

            $synergy = new DomainSynergy();
            $synergy->setDomainA($domainA);
            $synergy->setDomainB($domainB);
            $synergy->setName($data['name']);
            $synergy->setDescription($data['description']);
            $synergy->setForm($data['form']);

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
