<?php

namespace App\Tests\Unit\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Entity\App\Map;
use App\GameEngine\Event\GameEventBonusProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GameEventBonusProviderTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $repository;
    private GameEventBonusProvider $provider;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->with(GameEvent::class)->willReturn($this->repository);

        $this->provider = new GameEventBonusProvider($this->em);
    }

    public function testReturnsDefaultMultiplierWhenNoActiveEvents(): void
    {
        $this->repository->method('findBy')->willReturn([]);

        $this->assertSame(1.0, $this->provider->getXpMultiplier());
        $this->assertSame(1.0, $this->provider->getDropMultiplier());
    }

    public function testReturnsXpMultiplierFromActiveEvent(): void
    {
        $event = $this->createBonusEvent(GameEvent::TYPE_XP_BONUS, ['multiplier' => 2]);

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => $criteria['type'] === GameEvent::TYPE_XP_BONUS ? [$event] : [],
        );

        $this->assertSame(2.0, $this->provider->getXpMultiplier());
    }

    public function testReturnsDropMultiplierFromActiveEvent(): void
    {
        $event = $this->createBonusEvent(GameEvent::TYPE_DROP_BONUS, ['multiplier' => 1.5]);

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => $criteria['type'] === GameEvent::TYPE_DROP_BONUS ? [$event] : [],
        );

        $this->assertSame(1.5, $this->provider->getDropMultiplier());
    }

    public function testStacksMultipleActiveEvents(): void
    {
        $event1 = $this->createBonusEvent(GameEvent::TYPE_XP_BONUS, ['multiplier' => 2]);
        $event2 = $this->createBonusEvent(GameEvent::TYPE_XP_BONUS, ['multiplier' => 1.5]);

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => $criteria['type'] === GameEvent::TYPE_XP_BONUS ? [$event1, $event2] : [],
        );

        $this->assertSame(3.0, $this->provider->getXpMultiplier());
    }

    public function testIgnoresMapSpecificEventWhenOnDifferentMap(): void
    {
        $eventMap = $this->createMock(Map::class);
        $eventMap->method('getId')->willReturn(1);

        $playerMap = $this->createMock(Map::class);
        $playerMap->method('getId')->willReturn(2);

        $event = $this->createBonusEvent(GameEvent::TYPE_XP_BONUS, ['multiplier' => 2], $eventMap);

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => $criteria['type'] === GameEvent::TYPE_XP_BONUS ? [$event] : [],
        );

        $this->assertSame(1.0, $this->provider->getXpMultiplier($playerMap));
    }

    public function testAppliesMapSpecificEventWhenOnSameMap(): void
    {
        $map = $this->createMock(Map::class);
        $map->method('getId')->willReturn(1);

        $event = $this->createBonusEvent(GameEvent::TYPE_XP_BONUS, ['multiplier' => 2], $map);

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => $criteria['type'] === GameEvent::TYPE_XP_BONUS ? [$event] : [],
        );

        $this->assertSame(2.0, $this->provider->getXpMultiplier($map));
    }

    public function testGlobalEventAppliesRegardlessOfMap(): void
    {
        $playerMap = $this->createMock(Map::class);
        $playerMap->method('getId')->willReturn(5);

        $event = $this->createBonusEvent(GameEvent::TYPE_XP_BONUS, ['multiplier' => 1.5], null);

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => $criteria['type'] === GameEvent::TYPE_XP_BONUS ? [$event] : [],
        );

        $this->assertSame(1.5, $this->provider->getXpMultiplier($playerMap));
    }

    private function createBonusEvent(string $type, array $parameters, ?Map $map = null): GameEvent
    {
        $event = new GameEvent();
        $event->setName('Test Bonus');
        $event->setType($type);
        $event->setStatus(GameEvent::STATUS_ACTIVE);
        $event->setStartsAt(new \DateTime('-1 hour'));
        $event->setEndsAt(new \DateTime('+1 hour'));
        $event->setParameters($parameters);
        $event->setMap($map);

        return $event;
    }
}
