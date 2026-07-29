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
            ->setAvailableAtCreation(true);

        $this->assertInstanceOf(Race::class, $result);
        $this->assertSame('elf', $race->getSlug());
    }
}
