<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Race;
use PHPUnit\Framework\TestCase;

class RaceTest extends TestCase
{
    public function testCreateRaceWithDefaultValues(): void
    {
        $race = new Race();
        $race->setSlug('human');
        $race->setName('Humain');
        $race->setDescription('Race humaine');

        $this->assertSame('human', $race->getSlug());
        $this->assertSame('Humain', $race->getName());
        $this->assertSame('Race humaine', $race->getDescription());
        $this->assertNull($race->getSpriteSheet());
        $this->assertTrue($race->isAvailableAtCreation());
        $this->assertSame('Humain', (string) $race);
    }

    public function testStatModifiersDefaultToNeutral(): void
    {
        $race = new Race();

        $expected = ['life' => 0, 'energy' => 0, 'speed' => 0, 'hit' => 0];
        $this->assertSame($expected, $race->getStatModifiers());
    }

    public function testGetStatModifierReturnsCorrectValue(): void
    {
        $race = new Race();
        $race->setStatModifiers(['life' => 5, 'energy' => -2, 'speed' => 0, 'hit' => 3]);

        $this->assertSame(5, $race->getStatModifier('life'));
        $this->assertSame(-2, $race->getStatModifier('energy'));
        $this->assertSame(0, $race->getStatModifier('speed'));
        $this->assertSame(3, $race->getStatModifier('hit'));
    }

    public function testGetStatModifierReturnsZeroForUnknownStat(): void
    {
        $race = new Race();

        $this->assertSame(0, $race->getStatModifier('unknown'));
    }

    public function testSetStatModifiers(): void
    {
        $race = new Race();
        $modifiers = ['life' => 10, 'energy' => 5, 'speed' => -3, 'hit' => 2];
        $race->setStatModifiers($modifiers);

        $this->assertSame($modifiers, $race->getStatModifiers());
    }

    public function testSetSpriteSheet(): void
    {
        $race = new Race();
        $race->setSpriteSheet('human_sprite.png');

        $this->assertSame('human_sprite.png', $race->getSpriteSheet());
    }

    public function testAvailableAtCreation(): void
    {
        $race = new Race();

        $this->assertTrue($race->isAvailableAtCreation());

        $race->setAvailableAtCreation(false);
        $this->assertFalse($race->isAvailableAtCreation());
    }

    public function testFluentSetters(): void
    {
        $race = new Race();

        $result = $race->setSlug('elf')
            ->setName('Elfe')
            ->setDescription('Race elfique')
            ->setSpriteSheet('elf.png')
            ->setStatModifiers(['life' => -2, 'energy' => 5, 'speed' => 3, 'hit' => 1])
            ->setAvailableAtCreation(true);

        $this->assertInstanceOf(Race::class, $result);
        $this->assertSame('elf', $race->getSlug());
    }
}
