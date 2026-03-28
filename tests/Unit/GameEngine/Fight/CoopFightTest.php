<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\MobActionHandler;
use PHPUnit\Framework\TestCase;

class CoopFightTest extends TestCase
{
    private FightTurnResolver $turnResolver;

    protected function setUp(): void
    {
        $this->turnResolver = new FightTurnResolver();
    }

    public function testTurnOrderIncludesMultiplePlayers(): void
    {
        $fight = new Fight();
        $player1 = $this->createPlayerEntity(1, 'Alice', 20);
        $player2 = $this->createPlayerEntity(2, 'Bob', 15);
        $mob = $this->createMobEntity(1, 'Slime', 10);

        $fight->addPlayer($player1);
        $fight->addPlayer($player2);
        $fight->addMob($mob);

        $turnOrder = $this->turnResolver->getTurnOrder($fight);

        $this->assertCount(3, $turnOrder);
        // Sorted by speed desc: Alice (20), Bob (15), Slime (10)
        $this->assertSame('player_1', $turnOrder[0]['key']);
        $this->assertSame('player_2', $turnOrder[1]['key']);
        $this->assertSame('mob_1', $turnOrder[2]['key']);
    }

    public function testIsPlayerTurnReturnsTrueForCorrectPlayer(): void
    {
        $fight = new Fight();
        $fight->setCurrentTurnKey('player_1');

        $this->assertTrue($this->turnResolver->isPlayerTurn($fight, 1));
        $this->assertFalse($this->turnResolver->isPlayerTurn($fight, 2));
    }

    public function testIsCoopFightWithCurrentTurnKey(): void
    {
        $fight = new Fight();
        $fight->setCurrentTurnKey('player_1');

        // Not a world boss, has currentTurnKey -> is coop
        $this->assertTrue($fight->isCoopFight());
    }

    public function testIsNotCoopFightWithoutTurnKey(): void
    {
        $fight = new Fight();
        $this->assertFalse($fight->isCoopFight());
    }

    public function testInitializeCoopTurnsSetsFirstPlayerTurn(): void
    {
        $fight = new Fight();
        $player1 = $this->createPlayerEntity(1, 'Alice', 20);
        $player2 = $this->createPlayerEntity(2, 'Bob', 15);
        $mob = $this->createMobEntity(1, 'Slime', 10);

        $fight->addPlayer($player1);
        $fight->addPlayer($player2);
        $fight->addMob($mob);

        $mobHandler = $this->createMock(MobActionHandler::class);
        $result = $this->turnResolver->initializeCoopTurns($fight, $mobHandler);

        // Player 1 (fastest) should get the first turn
        $this->assertSame('player_1', $fight->getCurrentTurnKey());
        $this->assertSame('player_1', $result['nextKey']);
    }

    public function testInitializeCoopTurnsAutoResolvesMobsBeforeFirstPlayer(): void
    {
        $fight = new Fight();
        $player1 = $this->createPlayerEntity(1, 'Alice', 10);
        $mob = $this->createMobEntity(1, 'FastSlime', 20);

        $fight->addPlayer($player1);
        $fight->addMob($mob);

        $mobHandler = $this->createMock(MobActionHandler::class);
        $mobHandler->expects($this->once())
            ->method('doAction')
            ->with($fight)
            ->willReturn(['messages' => ['FastSlime attaque Alice !'], 'dangerAlert' => null]);

        $result = $this->turnResolver->initializeCoopTurns($fight, $mobHandler);

        $this->assertSame('player_1', $fight->getCurrentTurnKey());
        $this->assertContains('FastSlime attaque Alice !', $result['messages']);
    }

    public function testAdvanceCoopTurnSkipsMobsAndGoesToNextPlayer(): void
    {
        $fight = new Fight();
        $player1 = $this->createPlayerEntity(1, 'Alice', 30);
        $player2 = $this->createPlayerEntity(2, 'Bob', 20);
        $mob = $this->createMobEntity(1, 'Slime', 25);

        $fight->addPlayer($player1);
        $fight->addPlayer($player2);
        $fight->addMob($mob);

        // Turn order: Alice (30), Slime (25), Bob (20)
        // Current turn is Alice, after her action we advance:
        // Next is Slime (mob, auto-resolve), then Bob (player, stop)
        $fight->setCurrentTurnKey('player_1');

        $mobHandler = $this->createMock(MobActionHandler::class);
        $mobHandler->expects($this->once())
            ->method('doAction')
            ->willReturn(['messages' => ['Slime attacks!'], 'dangerAlert' => null]);

        $result = $this->turnResolver->advanceCoopTurn($fight, $mobHandler);

        $this->assertSame('player_2', $fight->getCurrentTurnKey());
        $this->assertSame('player_2', $result['nextKey']);
        $this->assertContains('Slime attacks!', $result['messages']);
    }

    public function testTimelineWithMultiplePlayers(): void
    {
        $fight = new Fight();
        $player1 = $this->createPlayerEntity(1, 'Alice', 20);
        $player2 = $this->createPlayerEntity(2, 'Bob', 15);
        $mob = $this->createMobEntity(1, 'Slime', 10);

        $fight->addPlayer($player1);
        $fight->addPlayer($player2);
        $fight->addMob($mob);

        $timeline = $this->turnResolver->getTimeline($fight, 2);

        // 3 participants x 2 rounds = 6 entries
        $this->assertCount(6, $timeline);
        $this->assertSame('player_1', $timeline[0]['key']);
        $this->assertSame('player_2', $timeline[1]['key']);
        $this->assertSame('mob_1', $timeline[2]['key']);
    }

    public function testDeadPlayerExcludedFromTurnOrder(): void
    {
        $fight = new Fight();
        $player1 = $this->createPlayerEntity(1, 'Alice', 20);
        $player2 = $this->createPlayerEntity(2, 'Bob', 15);
        $player2->setDiedAt(new \DateTime());
        $mob = $this->createMobEntity(1, 'Slime', 10);

        $fight->addPlayer($player1);
        $fight->addPlayer($player2);
        $fight->addMob($mob);

        $turnOrder = $this->turnResolver->getTurnOrder($fight);

        $this->assertCount(2, $turnOrder);
        $keys = array_column($turnOrder, 'key');
        $this->assertContains('player_1', $keys);
        $this->assertNotContains('player_2', $keys);
    }

    public function testFightVictoryWithAllMobsDead(): void
    {
        $fight = new Fight();
        $player1 = $this->createPlayerEntity(1, 'Alice', 20);
        $player2 = $this->createPlayerEntity(2, 'Bob', 15);
        $mob = $this->createMobEntity(1, 'Slime', 10);
        $mob->setDiedAt(new \DateTime());

        $fight->addPlayer($player1);
        $fight->addPlayer($player2);
        $fight->addMob($mob);

        $this->assertTrue($fight->isVictory());
        $this->assertTrue($fight->isTerminated());
        $this->assertFalse($fight->isDefeat());
    }

    public function testFightDefeatWithAllPlayersDead(): void
    {
        $fight = new Fight();
        $player1 = $this->createPlayerEntity(1, 'Alice', 20);
        $player1->setDiedAt(new \DateTime());
        $player2 = $this->createPlayerEntity(2, 'Bob', 15);
        $player2->setDiedAt(new \DateTime());
        $mob = $this->createMobEntity(1, 'Slime', 10);

        $fight->addPlayer($player1);
        $fight->addPlayer($player2);
        $fight->addMob($mob);

        $this->assertTrue($fight->isDefeat());
        $this->assertTrue($fight->isTerminated());
        $this->assertFalse($fight->isVictory());
    }

    private function createPlayerEntity(int $id, string $name, int $speed): Player
    {
        $player = new Player();
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($player, $id);
        $refName = new \ReflectionProperty(Player::class, 'name');
        $refName->setValue($player, $name);
        $player->setSpeed($speed);
        $player->setLife(100);
        $player->setMaxLife(100);

        return $player;
    }

    private function createMobEntity(int $id, string $name, int $speed): Mob
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getName')->willReturn($name);
        $monster->method('isBoss')->willReturn(false);
        $monster->method('getBossPhases')->willReturn(null);
        $monster->method('getSpeed')->willReturn($speed);
        $monster->method('getLife')->willReturn(50);
        $mob = new Mob();
        $ref = new \ReflectionProperty(Mob::class, 'id');
        $ref->setValue($mob, $id);
        $mob->setMonster($monster);
        $mob->setIsWorldBoss(false);
        $mob->setLife(50);

        return $mob;
    }
}
