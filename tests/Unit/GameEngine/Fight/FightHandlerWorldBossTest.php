<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\Handler\FightHandler;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Party\PartyManager;
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
    private PartyManager&MockObject $partyManager;
    private FightTurnResolver&MockObject $turnResolver;
    private MobActionHandler&MockObject $mobActionHandler;
    private FightHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->combatLogger = $this->createMock(CombatLogger::class);
        $this->enchantmentManager = $this->createMock(EnchantmentManager::class);
        $this->dungeonRunRepository = $this->createMock(DungeonRunRepository::class);
        $this->partyManager = $this->createMock(PartyManager::class);
        $this->turnResolver = $this->createMock(FightTurnResolver::class);
        $this->mobActionHandler = $this->createMock(MobActionHandler::class);

        $this->handler = new FightHandler(
            $this->em,
            new NullLogger(),
            $this->combatLogger,
            $this->enchantmentManager,
            $this->dungeonRunRepository,
            $this->partyManager,
            $this->turnResolver,
            $this->mobActionHandler,
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

    public function testJoinWorldBossFightScalesBossHp(): void
    {
        $monster = $this->createMock(\App\Entity\Game\Monster::class);
        $monster->method('getLife')->willReturn(1000);
        $monster->method('getName')->willReturn('World Boss');

        $mob = new Mob();
        $ref = new \ReflectionProperty(Mob::class, 'id');
        $ref->setValue($mob, 1);
        $mob->setMonster($monster);
        $mob->setIsWorldBoss(true);
        $mob->setLife(1000);

        $fight = new Fight();
        $fight->setId(1);
        $fight->addMob($mob);
        $mob->setFight($fight);

        $player1 = new Player();
        $refP1 = new \ReflectionProperty(Player::class, 'id');
        $refP1->setValue($player1, 1);
        $refName1 = new \ReflectionProperty(Player::class, 'name');
        $refName1->setValue($player1, 'Player1');
        $fight->addPlayer($player1);

        $player2 = new Player();
        $refP2 = new \ReflectionProperty(Player::class, 'id');
        $refP2->setValue($player2, 2);
        $refName2 = new \ReflectionProperty(Player::class, 'name');
        $refName2->setValue($player2, 'Player2');

        $this->handler->joinWorldBossFight($player2, $fight);

        // 2 players: multiplier = 1 + 0.35 * (2-1) = 1.35 → 1000 * 1.35 = 1350
        $this->assertSame(1350, $mob->getLife());
        $this->assertSame(1.35, $fight->getMetadataValue('world_boss_player_multiplier'));
    }

    public function testJoinWorldBossFightPreservesHpRatio(): void
    {
        $monster = $this->createMock(\App\Entity\Game\Monster::class);
        $monster->method('getLife')->willReturn(1000);
        $monster->method('getName')->willReturn('World Boss');

        $mob = new Mob();
        $ref = new \ReflectionProperty(Mob::class, 'id');
        $ref->setValue($mob, 1);
        $mob->setMonster($monster);
        $mob->setIsWorldBoss(true);
        $mob->setLife(500); // 50% HP

        $fight = new Fight();
        $fight->setId(1);
        $fight->addMob($mob);
        $mob->setFight($fight);

        $player1 = new Player();
        $refP1 = new \ReflectionProperty(Player::class, 'id');
        $refP1->setValue($player1, 1);
        $refName1 = new \ReflectionProperty(Player::class, 'name');
        $refName1->setValue($player1, 'Player1');
        $fight->addPlayer($player1);

        $player2 = new Player();
        $refP2 = new \ReflectionProperty(Player::class, 'id');
        $refP2->setValue($player2, 2);
        $refName2 = new \ReflectionProperty(Player::class, 'name');
        $refName2->setValue($player2, 'Player2');

        $this->handler->joinWorldBossFight($player2, $fight);

        // Boss was at 50% of 1000 = 500 HP. New max = 1350. 50% of 1350 = 675
        $this->assertSame(675, $mob->getLife());
    }

    public function testJoinWorldBossFightScalesProgressivelyFor3Players(): void
    {
        $monster = $this->createMock(\App\Entity\Game\Monster::class);
        $monster->method('getLife')->willReturn(1000);
        $monster->method('getName')->willReturn('World Boss');

        $mob = new Mob();
        $ref = new \ReflectionProperty(Mob::class, 'id');
        $ref->setValue($mob, 1);
        $mob->setMonster($monster);
        $mob->setIsWorldBoss(true);
        $mob->setLife(1000);

        $fight = new Fight();
        $fight->setId(1);
        $fight->addMob($mob);
        $mob->setFight($fight);

        $player1 = new Player();
        $refP1 = new \ReflectionProperty(Player::class, 'id');
        $refP1->setValue($player1, 1);
        $refName1 = new \ReflectionProperty(Player::class, 'name');
        $refName1->setValue($player1, 'Player1');
        $fight->addPlayer($player1);

        // Player 2 joins
        $player2 = new Player();
        $refP2 = new \ReflectionProperty(Player::class, 'id');
        $refP2->setValue($player2, 2);
        $refName2 = new \ReflectionProperty(Player::class, 'name');
        $refName2->setValue($player2, 'Player2');
        $this->handler->joinWorldBossFight($player2, $fight);
        $this->assertSame(1350, $mob->getLife());

        // Player 3 joins
        $player3 = new Player();
        $refP3 = new \ReflectionProperty(Player::class, 'id');
        $refP3->setValue($player3, 3);
        $refName3 = new \ReflectionProperty(Player::class, 'name');
        $refName3->setValue($player3, 'Player3');
        $this->handler->joinWorldBossFight($player3, $fight);

        // 3 players: multiplier = 1 + 0.35 * 2 = 1.70 → 1000 * 1.70 = 1700
        $this->assertSame(1700, $mob->getLife());
        $this->assertSame(1.7, $fight->getMetadataValue('world_boss_player_multiplier'));
    }

    private function createPlayer(int $id): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getName')->willReturn('Player' . $id);

        return $player;
    }
}
