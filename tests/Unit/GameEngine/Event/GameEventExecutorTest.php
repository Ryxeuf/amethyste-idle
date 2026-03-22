<?php

namespace App\Tests\Unit\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\GameEngine\Event\GameEventExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class GameEventExecutorTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $repository;
    private GameEventExecutor $executor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->with(GameEvent::class)->willReturn($this->repository);

        $this->executor = new GameEventExecutor($this->em, new NullLogger());
    }

    public function testActivatesScheduledEventWhenStartTimeReached(): void
    {
        $event = $this->createEvent(
            status: GameEvent::STATUS_SCHEDULED,
            startsAt: new \DateTime('-10 minutes'),
            endsAt: new \DateTime('+50 minutes'),
        );

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => match ($criteria['status']) {
                GameEvent::STATUS_SCHEDULED => [$event],
                GameEvent::STATUS_ACTIVE => [],
                default => [],
            },
        );

        $this->em->expects($this->once())->method('flush');

        $result = $this->executor->execute();

        $this->assertSame(GameEvent::STATUS_ACTIVE, $event->getStatus());
        $this->assertSame(1, $result['activated']);
        $this->assertSame(0, $result['completed']);
    }

    public function testCompletesExpiredActiveEvent(): void
    {
        $event = $this->createEvent(
            status: GameEvent::STATUS_ACTIVE,
            startsAt: new \DateTime('-2 hours'),
            endsAt: new \DateTime('-10 minutes'),
        );

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => match ($criteria['status']) {
                GameEvent::STATUS_SCHEDULED => [],
                GameEvent::STATUS_ACTIVE => [$event],
                default => [],
            },
        );

        $this->em->expects($this->once())->method('flush');

        $result = $this->executor->execute();

        $this->assertSame(GameEvent::STATUS_COMPLETED, $event->getStatus());
        $this->assertSame(0, $result['activated']);
        $this->assertSame(1, $result['completed']);
    }

    public function testSkipsScheduledEventNotYetStarted(): void
    {
        $event = $this->createEvent(
            status: GameEvent::STATUS_SCHEDULED,
            startsAt: new \DateTime('+1 hour'),
            endsAt: new \DateTime('+2 hours'),
        );

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => match ($criteria['status']) {
                GameEvent::STATUS_SCHEDULED => [$event],
                GameEvent::STATUS_ACTIVE => [],
                default => [],
            },
        );

        $result = $this->executor->execute();

        $this->assertSame(GameEvent::STATUS_SCHEDULED, $event->getStatus());
        $this->assertSame(0, $result['activated']);
    }

    public function testCompletesScheduledEventAlreadyPastEndTime(): void
    {
        $event = $this->createEvent(
            status: GameEvent::STATUS_SCHEDULED,
            startsAt: new \DateTime('-3 hours'),
            endsAt: new \DateTime('-1 hour'),
        );

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => match ($criteria['status']) {
                GameEvent::STATUS_SCHEDULED => [$event],
                GameEvent::STATUS_ACTIVE => [],
                default => [],
            },
        );

        $result = $this->executor->execute();

        $this->assertSame(GameEvent::STATUS_COMPLETED, $event->getStatus());
    }

    public function testCreatesRecurringEventOnCompletion(): void
    {
        $event = $this->createEvent(
            status: GameEvent::STATUS_ACTIVE,
            startsAt: new \DateTime('-2 hours'),
            endsAt: new \DateTime('-10 minutes'),
            recurring: true,
            recurrenceInterval: 1440, // 24h
            type: GameEvent::TYPE_XP_BONUS,
        );
        $event->setParameters(['multiplier' => 2]);
        $event->setName('Bonus XP quotidien');

        $this->repository->method('findBy')->willReturnCallback(
            fn (array $criteria) => match ($criteria['status']) {
                GameEvent::STATUS_SCHEDULED => [],
                GameEvent::STATUS_ACTIVE => [$event],
                default => [],
            },
        );

        $this->em->expects($this->once())->method('persist')
            ->with($this->callback(function (GameEvent $newEvent) use ($event): bool {
                $this->assertSame(GameEvent::STATUS_SCHEDULED, $newEvent->getStatus());
                $this->assertSame($event->getName(), $newEvent->getName());
                $this->assertSame($event->getType(), $newEvent->getType());
                $this->assertSame($event->getParameters(), $newEvent->getParameters());
                $this->assertTrue($newEvent->isRecurring());
                $this->assertSame(1440, $newEvent->getRecurrenceInterval());
                $this->assertGreaterThan($event->getEndsAt(), $newEvent->getStartsAt());

                return true;
            }));

        $result = $this->executor->execute();

        $this->assertSame(1, $result['completed']);
        $this->assertSame(1, $result['recurring']);
    }

    public function testNoFlushWhenNoChanges(): void
    {
        $this->repository->method('findBy')->willReturn([]);
        $this->em->expects($this->never())->method('flush');

        $result = $this->executor->execute();

        $this->assertSame(0, $result['activated']);
        $this->assertSame(0, $result['completed']);
        $this->assertSame(0, $result['recurring']);
    }

    private function createEvent(
        string $status,
        \DateTime $startsAt,
        \DateTime $endsAt,
        bool $recurring = false,
        ?int $recurrenceInterval = null,
        string $type = GameEvent::TYPE_XP_BONUS,
    ): GameEvent {
        $event = new GameEvent();
        $event->setName('Test Event');
        $event->setType($type);
        $event->setStatus($status);
        $event->setStartsAt($startsAt);
        $event->setEndsAt($endsAt);
        $event->setRecurring($recurring);
        $event->setRecurrenceInterval($recurrenceInterval);

        return $event;
    }
}
