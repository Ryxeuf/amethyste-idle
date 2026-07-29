<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Player;
use App\Entity\Game\Race;
use PHPUnit\Framework\TestCase;

class PlayerRaceTest extends TestCase
{
    public function testPlayerCanHaveRace(): void
    {
        $race = new Race();
        $race->setSlug('human');
        $race->setName('Humain');
        $race->setDescription('Race humaine');

        $player = new Player();
        $player->setRace($race);

        $this->assertSame($race, $player->getRace());
        $this->assertSame('human', $player->getRace()->getSlug());
    }

    public function testPlayerRaceIsNullableByDefault(): void
    {
        $player = new Player();

        $this->assertNull($player->getRace());
    }

    /**
     * ONB-07 : un peuple ne porte plus aucun chiffre.
     *
     * Ces deux tests remplacent `testPlayerWithHumanRaceHasNeutralModifiers` et
     * `testStatModifiersAppliedCorrectly`, qui verifiaient precisement le
     * mecanisme retire — un Elfe a `-5 vie / +10 energie / +3 vitesse`. Ce que
     * l'on garantit desormais est l'inverse : attacher un peuple ne deplace
     * aucune statistique.
     */
    public function testAttachingAPeopleMovesNoStatistic(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setMaxEnergy(50);
        $player->setSpeed(10);
        $player->setHit(50);

        $before = [$player->getMaxLife(), $player->getMaxEnergy(), $player->getSpeed(), $player->getHit()];

        $player->setRace($this->race('elf', 'Elfe'));

        $after = [$player->getMaxLife(), $player->getMaxEnergy(), $player->getSpeed(), $player->getHit()];

        $this->assertSame($before, $after);
    }

    /**
     * Le peuple n'expose plus de modificateurs : la methode n'existe pas, et
     * ce test le dit explicitement plutot que de laisser une absence muette.
     */
    public function testARaceExposesNoStatModifierAtAll(): void
    {
        $this->assertFalse(
            method_exists($this->race('dwarf', 'Nain'), 'getStatModifiers'),
            'Un peuple ne doit porter aucun chiffre (ONB-07).',
        );
    }

    private function race(string $slug, string $name): Race
    {
        return (new Race())
            ->setSlug($slug)
            ->setName($name)
            ->setDescription('Peuple de test');
    }
}
