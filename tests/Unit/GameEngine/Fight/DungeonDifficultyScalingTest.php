<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DungeonDifficultyScalingTest extends TestCase
{
    private function createMonsterMock(int $life = 100): Monster&MockObject
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getLife')->willReturn($life);

        return $monster;
    }

    public function testMobMaxLifeWithoutFightReturnsBaseLife(): void
    {
        $monster = $this->createMonsterMock(100);

        $mob = new Mob();
        $mob->setMonster($monster);
        $mob->setLife(100);

        $this->assertSame(100, $mob->getMaxLife());
    }

    public function testMobMaxLifeWithNormalDifficultyReturnsBaseLife(): void
    {
        $monster = $this->createMonsterMock(100);

        $fight = new Fight();

        $mob = new Mob();
        $mob->setMonster($monster);
        $mob->setLife(100);
        $mob->setFight($fight);

        $this->assertSame(100, $mob->getMaxLife());
    }

    public function testMobMaxLifeWithHeroicDifficultyIsScaled(): void
    {
        $monster = $this->createMonsterMock(100);

        $fight = new Fight();
        $fight->setMetadataValue('difficulty_multiplier', 1.5);

        $mob = new Mob();
        $mob->setMonster($monster);
        $mob->setLife(150);
        $mob->setFight($fight);

        $this->assertSame(150, $mob->getMaxLife());
    }

    public function testMobMaxLifeWithMythicDifficultyIsScaled(): void
    {
        $monster = $this->createMonsterMock(100);

        $fight = new Fight();
        $fight->setMetadataValue('difficulty_multiplier', 2.5);

        $mob = new Mob();
        $mob->setMonster($monster);
        $mob->setLife(250);
        $mob->setFight($fight);

        $this->assertSame(250, $mob->getMaxLife());
    }

    public function testFightMetadataStoresDifficultyMultipliers(): void
    {
        $fight = new Fight();
        $fight->setMetadataValue('difficulty_multiplier', 2.5);
        $fight->setMetadataValue('difficulty_damage_multiplier', 1.75);
        $fight->setMetadataValue('difficulty_drop_multiplier', 1.5);

        $this->assertSame(2.5, $fight->getMetadataValue('difficulty_multiplier'));
        $this->assertSame(1.75, $fight->getMetadataValue('difficulty_damage_multiplier'));
        $this->assertSame(1.5, $fight->getMetadataValue('difficulty_drop_multiplier'));
    }

    public function testFightMetadataDefaultsToOneWhenNotSet(): void
    {
        $fight = new Fight();

        $this->assertSame(1.0, $fight->getMetadataValue('difficulty_multiplier', 1.0));
        $this->assertSame(1.0, $fight->getMetadataValue('difficulty_damage_multiplier', 1.0));
        $this->assertSame(1.0, $fight->getMetadataValue('difficulty_drop_multiplier', 1.0));
    }
}
