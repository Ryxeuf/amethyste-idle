<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\GameEvent;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerZoneEventParticipation;
use App\Entity\App\Zone;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\NotEnoughActionEnergyException;
use App\GameEngine\Zone\ZoneActionException;
use App\GameEngine\Zone\ZoneEventService;
use App\Repository\PlayerZoneEventParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ZoneEventServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private PlayerZoneEventParticipationRepository&MockObject $participationRepository;
    private ActionEnergyManager&MockObject $actionEnergyManager;
    private ZoneEventService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $this->parameterRepository],
        ]);

        $this->participationRepository = $this->createMock(PlayerZoneEventParticipationRepository::class);
        $this->actionEnergyManager = $this->createMock(ActionEnergyManager::class);

        $this->service = new ZoneEventService(
            $this->entityManager,
            $this->participationRepository,
            $this->actionEnergyManager,
        );
    }

    private function buildZone(int $id = 1): Zone
    {
        $zone = (new Zone())->setSlug('foret')->setName('Foret');
        $ref = new \ReflectionProperty(Zone::class, 'id');
        $ref->setValue($zone, $id);

        return $zone;
    }

    private function buildEvent(?Zone $zone, string $status = GameEvent::STATUS_ACTIVE): GameEvent
    {
        $event = new GameEvent();
        $event->setName('Invasion des gobelins');
        $event->setType(GameEvent::TYPE_INVASION);
        $event->setStatus($status);
        $event->setStartsAt(new \DateTime('-1 hour'));
        $event->setEndsAt(new \DateTime('+1 hour'));
        $event->setZone($zone);

        return $event;
    }

    private function buildPlayer(?Zone $zone): Player
    {
        $player = new Player();
        if (null !== $zone) {
            $player->setCurrentZone($zone);
        }

        return $player;
    }

    public function testGetEventCostFallsBackToDefault(): void
    {
        $this->assertSame(ZoneEventService::DEFAULT_COST, $this->service->getEventCost());
    }

    public function testGetEventCostReadsParameterOverride(): void
    {
        $parameter = new Parameter();
        $parameter->setName(ZoneEventService::PARAM_COST);
        $parameter->setValue('25');
        $this->parameterRepository->method('findOneBy')->willReturn($parameter);

        $this->assertSame(25, $this->service->getEventCost());
    }

    public function testJoinRecordsParticipationAndSpendsEnergy(): void
    {
        $zone = $this->buildZone();
        $player = $this->buildPlayer($zone);
        $event = $this->buildEvent($zone);

        $this->participationRepository->method('findOneForPlayerAndEvent')->willReturn(null);
        $this->actionEnergyManager->expects($this->once())->method('spend')
            ->with($player, ZoneEventService::DEFAULT_COST, false);

        $persisted = null;
        $this->entityManager->expects($this->once())->method('persist')
            ->willReturnCallback(function ($e) use (&$persisted) { $persisted = $e; });
        $this->entityManager->expects($this->once())->method('flush');

        $participation = $this->service->join($player, $event);

        $this->assertInstanceOf(PlayerZoneEventParticipation::class, $participation);
        $this->assertSame($participation, $persisted);
        $this->assertSame($player, $participation->getPlayer());
        $this->assertSame($event, $participation->getGameEvent());
    }

    public function testJoinRejectsNonZoneEvent(): void
    {
        $this->expectException(ZoneActionException::class);
        $this->service->join($this->buildPlayer($this->buildZone()), $this->buildEvent(null));
    }

    public function testJoinRejectsWhenNotPresent(): void
    {
        $eventZone = $this->buildZone(1);
        $otherZone = $this->buildZone(2);
        $player = $this->buildPlayer($otherZone);

        $this->expectException(ZoneActionException::class);
        $this->service->join($player, $this->buildEvent($eventZone));
    }

    public function testJoinRejectsWhenClosed(): void
    {
        $zone = $this->buildZone();
        $player = $this->buildPlayer($zone);
        $event = $this->buildEvent($zone, GameEvent::STATUS_SCHEDULED);
        $event->setStartsAt(new \DateTime('+1 hour'));
        $event->setEndsAt(new \DateTime('+2 hours'));

        $this->expectException(ZoneActionException::class);
        $this->service->join($player, $event);
    }

    public function testJoinRejectsWhenAlreadyJoined(): void
    {
        $zone = $this->buildZone();
        $player = $this->buildPlayer($zone);
        $event = $this->buildEvent($zone);

        $this->participationRepository->method('findOneForPlayerAndEvent')
            ->willReturn(new PlayerZoneEventParticipation($player, $event));

        $this->expectException(ZoneActionException::class);
        $this->service->join($player, $event);
    }

    public function testJoinPropagatesEnergyShortage(): void
    {
        $zone = $this->buildZone();
        $player = $this->buildPlayer($zone);
        $event = $this->buildEvent($zone);

        $this->participationRepository->method('findOneForPlayerAndEvent')->willReturn(null);
        $this->actionEnergyManager->method('spend')
            ->willThrowException(new NotEnoughActionEnergyException('game.zone.energy.error.not_enough'));

        $this->expectException(NotEnoughActionEnergyException::class);
        $this->service->join($player, $event);
    }
}
