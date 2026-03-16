<?php

namespace App\Tests\Unit\GameEngine\Movement;

use App\Entity\App\Fight;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\GameEngine\Fight\Handler\FightHandler;
use App\GameEngine\Map\PortalDetector;
use App\GameEngine\Movement\PlayerMoveProcessor;
use App\GameEngine\Realtime\Map\MovedPlayerHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class PlayerMoveProcessorTest extends TestCase
{
    private EntityManagerInterface $em;
    private FightHandler $fightHandler;
    private PortalDetector $portalDetector;
    private MovedPlayerHandler $movedPlayerHandler;
    private PlayerMoveProcessor $processor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->fightHandler = $this->createMock(FightHandler::class);
        $this->portalDetector = $this->createMock(PortalDetector::class);
        $this->movedPlayerHandler = $this->createMock(MovedPlayerHandler::class);

        $this->processor = new PlayerMoveProcessor(
            $this->em,
            new NullLogger(),
            $this->movedPlayerHandler,
            $this->fightHandler,
            $this->portalDetector,
        );
    }

    public function testProcessMoveReturnEmptyWhenPlayerInFight(): void
    {
        $player = $this->createPlayerWithCoordinates('5.5');
        $fight = $this->createMock(Fight::class);
        $player->setFight($fight);

        $result = $this->processor->processMove($player, [['x' => 6, 'y' => 5]]);

        $this->assertSame([], $result);
        $this->assertNull($this->processor->getTriggeredFight());
    }

    public function testProcessMoveReturnEmptyWhenCellsEmpty(): void
    {
        $player = $this->createPlayerWithCoordinates('5.5');

        $result = $this->processor->processMove($player, []);

        $this->assertSame([], $result);
    }

    public function testProcessMoveTruncatesPathAtMobAndTriggersFight(): void
    {
        $player = $this->createPlayerWithCoordinates('5.5');
        $map = $this->createMock(Map::class);
        $player->setMap($map);

        $monster = new Monster();
        $monster->setSlug('zombie');
        $monster->setName('Zombie');

        $mob = $this->createMob('7.5', $monster);

        $mobRepo = $this->createMock(EntityRepository::class);
        $mobRepo->method('findBy')->willReturn([$mob]);
        $this->em->method('getRepository')->with(Mob::class)->willReturn($mobRepo);
        $this->em->method('flush');

        $fight = new Fight();
        $this->fightHandler->expects($this->once())
            ->method('startFight')
            ->with($player, $mob)
            ->willReturn($fight);

        $cells = [
            ['x' => 6, 'y' => 5],
            ['x' => 7, 'y' => 5],
            ['x' => 8, 'y' => 5],
        ];

        $result = $this->processor->processMove($player, $cells);

        // Le chemin doit s'arreter a la case du mob (7.5)
        $this->assertCount(2, $result);
        $this->assertSame(6, $result[0]['x']);
        $this->assertSame(7, $result[1]['x']);

        // Le combat doit etre accessible
        $this->assertSame($fight, $this->processor->getTriggeredFight());
        $this->assertNull($this->processor->getTriggeredPortal());
    }

    public function testProcessMoveWithNoMobChecksPortal(): void
    {
        $player = $this->createPlayerWithCoordinates('5.5');
        $map = $this->createMock(Map::class);
        $player->setMap($map);

        $mobRepo = $this->createMock(EntityRepository::class);
        $mobRepo->method('findBy')->willReturn([]);
        $this->em->method('getRepository')->with(Mob::class)->willReturn($mobRepo);
        $this->em->method('flush');

        $portal = $this->createMock(ObjectLayer::class);
        $this->portalDetector->method('detectPortal')->willReturn($portal);

        $this->fightHandler->expects($this->never())->method('startFight');

        $cells = [['x' => 6, 'y' => 5]];

        $result = $this->processor->processMove($player, $cells);

        $this->assertCount(1, $result);
        $this->assertNull($this->processor->getTriggeredFight());
        $this->assertSame($portal, $this->processor->getTriggeredPortal());
    }

    public function testProcessMoveUpdatesPlayerCoordinates(): void
    {
        $player = $this->createPlayerWithCoordinates('5.5');
        $map = $this->createMock(Map::class);
        $player->setMap($map);

        $mobRepo = $this->createMock(EntityRepository::class);
        $mobRepo->method('findBy')->willReturn([]);
        $this->em->method('getRepository')->with(Mob::class)->willReturn($mobRepo);
        $this->em->method('flush');

        $this->portalDetector->method('detectPortal')->willReturn(null);

        $cells = [
            ['x' => 6, 'y' => 5],
            ['x' => 7, 'y' => 5],
        ];

        $this->processor->processMove($player, $cells);

        $this->assertSame('7.5', $player->getCoordinates());
    }

    public function testTriggeredFightIsResetBetweenCalls(): void
    {
        $player = $this->createPlayerWithCoordinates('5.5');
        $map = $this->createMock(Map::class);
        $player->setMap($map);

        // Premier appel : avec mob
        $monster = new Monster();
        $monster->setSlug('zombie');
        $monster->setName('Zombie');
        $mob = $this->createMob('6.5', $monster);

        $mobRepo = $this->createMock(EntityRepository::class);
        $mobRepo->method('findBy')->willReturnOnConsecutiveCalls([$mob], []);
        $this->em->method('getRepository')->with(Mob::class)->willReturn($mobRepo);
        $this->em->method('flush');

        $fight = new Fight();
        $this->fightHandler->method('startFight')->willReturn($fight);

        $this->processor->processMove($player, [['x' => 6, 'y' => 5]]);
        $this->assertNotNull($this->processor->getTriggeredFight());

        // Deuxieme appel : sans mob (reset le fight du player pour le test)
        $player->setFight(null);
        $this->portalDetector->method('detectPortal')->willReturn(null);

        $this->processor->processMove($player, [['x' => 7, 'y' => 5]]);
        $this->assertNull($this->processor->getTriggeredFight());
    }

    private function createPlayerWithCoordinates(string $coords): Player
    {
        $player = new Player();
        $player->setCoordinates($coords);
        $player->setLastCoordinates($coords);
        $player->setName('TestPlayer');

        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($player, 1);

        return $player;
    }

    private function createMob(string $coords, Monster $monster): Mob
    {
        $mob = new Mob();
        $mob->setCoordinates($coords);
        $mob->setMonster($monster);

        return $mob;
    }
}
