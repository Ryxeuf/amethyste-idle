<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\App\PlayerVisitedZone;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\GameEngine\Zone\ZoneTravelService;
use App\Repository\PlayerVisitedZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ZoneTravelServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerVisitedZoneRepository&MockObject $visitedZoneRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private ZoneTravelService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->visitedZoneRepository = $this->createMock(PlayerVisitedZoneRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new ZoneTravelService($this->entityManager, $this->visitedZoneRepository, $this->eventDispatcher);
    }

    private function buildZone(string $slug): Zone
    {
        return (new Zone())->setSlug($slug)->setName(ucfirst($slug));
    }

    private function buildPlayerIn(Zone $zone): Player
    {
        $player = new Player();
        $player->setCurrentZone($zone);

        return $player;
    }

    public function testStartTravelSetsDestinationAndArrival(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $connection = new ZoneConnection($from, $to, 300);

        $this->entityManager->expects($this->once())->method('flush');

        $arrivesAt = $this->service->startTravel($player, $connection);

        $this->assertSame($to, $player->getTravelToZone());
        $this->assertSame($arrivesAt, $player->getTravelArrivesAt());
        $this->assertEqualsWithDelta(time() + 300, $arrivesAt->getTimestamp(), 2);
        $this->assertSame($from, $player->getCurrentZone(), 'Le joueur reste dans sa zone tant que le voyage n\'est pas arrive.');
    }

    public function testInstantConnectionArrivesImmediately(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('taverne');
        $player = $this->buildPlayerIn($from);
        $connection = new ZoneConnection($from, $to, 0);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(PlayerVisitedZone::class));
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->startTravel($player, $connection);

        $this->assertSame($to, $player->getCurrentZone());
        $this->assertFalse($player->isTraveling());
        $this->assertNull($player->getTravelArrivesAt());
    }

    public function testRefusesWhenAlreadyTraveling(): void
    {
        $from = $this->buildZone('village');
        $player = $this->buildPlayerIn($from);
        $player->setTravelToZone($this->buildZone('foret'));
        $player->setTravelArrivesAt(new \DateTimeImmutable('+5 minutes'));

        $this->expectExceptionMessage('game.zone.travel.error.already_traveling');
        $this->service->startTravel($player, new ZoneConnection($from, $this->buildZone('mines'), 60));
    }

    public function testRefusesDuringFight(): void
    {
        $from = $this->buildZone('village');
        $player = $this->buildPlayerIn($from);
        $player->setFight($this->createMock(Fight::class));

        $this->expectExceptionMessage('game.zone.travel.error.in_fight');
        $this->service->startTravel($player, new ZoneConnection($from, $this->buildZone('foret'), 60));
    }

    public function testRefusesConnectionFromAnotherZone(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('village'));
        $elsewhere = new ZoneConnection($this->buildZone('mines'), $this->buildZone('crete'), 60);

        $this->expectExceptionMessage('game.zone.travel.error.wrong_origin');
        $this->service->startTravel($player, $elsewhere);
    }

    public function testRefusesDisabledConnection(): void
    {
        $from = $this->buildZone('village');
        $player = $this->buildPlayerIn($from);
        $connection = (new ZoneConnection($from, $this->buildZone('foret'), 60))->setEnabled(false);

        $this->expectExceptionMessage('game.zone.travel.error.unavailable');
        $this->service->startTravel($player, $connection);
    }

    public function testRefusesUndiscoveredFastLink(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('crete');
        $player = $this->buildPlayerIn($from);
        $connection = (new ZoneConnection($from, $to, 60))->setRequiresDiscovery(true);

        $this->visitedZoneRepository->method('hasVisited')->with($player, $to)->willReturn(false);

        $this->expectExceptionMessage('game.zone.travel.error.not_discovered');
        $this->service->startTravel($player, $connection);
    }

    public function testAllowsFastLinkTowardsVisitedZone(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('crete');
        $player = $this->buildPlayerIn($from);
        $connection = (new ZoneConnection($from, $to, 120))->setRequiresDiscovery(true);

        $this->visitedZoneRepository->method('hasVisited')->willReturn(true);

        $this->service->startTravel($player, $connection);

        $this->assertSame($to, $player->getTravelToZone());
    }

    public function testSettleArrivalIsNoOpBeforeArrivalTime(): void
    {
        $from = $this->buildZone('village');
        $player = $this->buildPlayerIn($from);
        $player->setTravelToZone($this->buildZone('foret'));
        $player->setTravelArrivesAt(new \DateTimeImmutable('+10 minutes'));

        $this->assertNull($this->service->settleArrival($player));
        $this->assertTrue($player->isTraveling());
        $this->assertSame($from, $player->getCurrentZone());
    }

    public function testSettleArrivalMovesPlayerAndRecordsDiscovery(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $player->setTravelToZone($to);
        $player->setTravelArrivesAt(new \DateTimeImmutable('-1 second'));

        $this->visitedZoneRepository->method('hasVisited')->willReturn(false);
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(PlayerVisitedZone::class));
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertSame($to, $this->service->settleArrival($player));
        $this->assertSame($to, $player->getCurrentZone());
        $this->assertFalse($player->isTraveling());
        $this->assertNull($player->getTravelArrivesAt());
    }

    public function testSettleArrivalReturnsNullWhenNotTraveling(): void
    {
        $this->assertNull($this->service->settleArrival($this->buildPlayerIn($this->buildZone('village'))));
    }

    public function testMarkZoneVisitedIsIdempotent(): void
    {
        $zone = $this->buildZone('village');
        $player = $this->buildPlayerIn($zone);

        $this->visitedZoneRepository->method('hasVisited')->willReturn(true);
        $this->entityManager->expects($this->never())->method('persist');

        $this->service->markZoneVisited($player, $zone);
    }
}
