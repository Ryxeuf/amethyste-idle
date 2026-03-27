<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\Handler\FightHandler;
use App\Repository\DungeonRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class FightHandlerWorldBossTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private CombatLogger&MockObject $combatLogger;
    private EnchantmentManager&MockObject $enchantmentManager;
    private DungeonRunRepository&MockObject $dungeonRunRepository;
    private FightHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->combatLogger = $this->createMock(CombatLogger::class);
        $this->enchantmentManager = $this->createMock(EnchantmentManager::class);
        $this->dungeonRunRepository = $this->createMock(DungeonRunRepository::class);

        $this->handler = new FightHandler(
            $this->em,
            new NullLogger(),
            $this->combatLogger,
            $this->enchantmentManager,
            $this->dungeonRunRepository,
        );
    }

    public function testJoinWorldBossFightAddsPlayerToFight(): void
    {
        $fight = new Fight();
        $fight->setId(1);

        $player1 = $this->createPlayer(1);
        $fight->addPlayer($player1);

        $player2 = $this->createPlayer(2);

        $this->em->expects($this->exactly(2))->method('flush');
        $this->combatLogger->expects($this->once())->method('logPlayerJoined')
            ->with($fight, $player2);

        $this->handler->joinWorldBossFight($player2, $fight);

        $this->assertCount(2, $fight->getPlayers());
        $this->assertTrue($fight->getPlayers()->contains($player2));
    }

    public function testJoinWorldBossFightSetsPlayerFight(): void
    {
        $fight = new Fight();
        $fight->setId(1);

        $player = new Player();
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($player, 1);
        $refName = new \ReflectionProperty(Player::class, 'name');
        $refName->setValue($player, 'TestPlayer');
        $player->setIsMoving(true);

        $this->handler->joinWorldBossFight($player, $fight);

        $this->assertSame($fight, $player->getFight());
        $this->assertFalse($player->isMoving());
    }

    public function testStartFightWithWorldBossCreatesSharedFight(): void
    {
        $monster = $this->createMock(\App\Entity\Game\Monster::class);
        $monster->method('getName')->willReturn('World Boss');

        $mob = new Mob();
        $ref = new \ReflectionProperty(Mob::class, 'id');
        $ref->setValue($mob, 1);
        $mob->setMonster($monster);
        $mob->setIsWorldBoss(true);

        $player = new Player();
        $refP = new \ReflectionProperty(Player::class, 'id');
        $refP->setValue($player, 1);
        $refName = new \ReflectionProperty(Player::class, 'name');
        $refName->setValue($player, 'Player1');

        $this->em->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(Fight::class));

        $fight = $this->handler->startFight($player, $mob);

        $this->assertTrue($fight->isWorldBossFight());
        $this->assertCount(1, $fight->getPlayers());
        $this->assertCount(1, $fight->getMobs());
    }

    private function createPlayer(int $id): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getName')->willReturn('Player' . $id);

        return $player;
    }
}
