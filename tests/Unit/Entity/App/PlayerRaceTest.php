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

    public function testPlayerWithHumanRaceHasNeutralModifiers(): void
    {
        $race = new Race();
        $race->setSlug('human');
        $race->setName('Humain');
        $race->setDescription('Race humaine');
        $race->setStatModifiers(['life' => 0, 'energy' => 0, 'speed' => 0, 'hit' => 0]);

        $player = new Player();
        $player->setMaxLife(100);
        $player->setMaxEnergy(50);
        $player->setSpeed(10);
        $player->setHit(50);
        $player->setRace($race);

        // Stats neutres : aucune modification attendue
        $this->assertSame(0, $race->getStatModifier('life'));
        $this->assertSame(0, $race->getStatModifier('energy'));
        $this->assertSame(0, $race->getStatModifier('speed'));
        $this->assertSame(0, $race->getStatModifier('hit'));
    }

    public function testStatModifiersAppliedCorrectly(): void
    {
        $race = new Race();
        $race->setSlug('elf');
        $race->setName('Elfe');
        $race->setDescription('Race elfique');
        $race->setStatModifiers(['life' => -5, 'energy' => 10, 'speed' => 3, 'hit' => 2]);

        $baseLife = 100;
        $baseEnergy = 50;
        $baseSpeed = 10;
        $baseHit = 50;

        $player = new Player();
        $player->setMaxLife($baseLife + $race->getStatModifier('life'));
        $player->setMaxEnergy($baseEnergy + $race->getStatModifier('energy'));
        $player->setSpeed($baseSpeed + $race->getStatModifier('speed'));
        $player->setHit($baseHit + $race->getStatModifier('hit'));
        $player->setRace($race);

        $this->assertSame(95, $player->getMaxLife());
        $this->assertSame(60, $player->getMaxEnergy());
        $this->assertSame(13, $player->getSpeed());
        $this->assertSame(52, $player->getHit());
    }
}
