<?php

namespace App\Tests\Unit\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\GameEngine\Event\RandomEventGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class RandomEventGeneratorTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $repository;
    private RandomEventGenerator $generator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->with(GameEvent::class)->willReturn($this->repository);

        $this->generator = new RandomEventGenerator($this->em, new NullLogger());
    }

    public function testGenerateCreatesEventWhenNoActiveRandomEvent(): void
    {
        $this->repository->method('findBy')->willReturn([]);

        $this->em->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(GameEvent::class));
        $this->em->expects($this->once())->method('flush');

        // Force 100% chance
        $event = $this->generator->tryGenerate(100);

        $this->assertNotNull($event);
        $this->assertSame(GameEvent::STATUS_SCHEDULED, $event->getStatus());
        $this->assertNotNull($event->getParameters());
        $this->assertTrue($event->getParameters()['random_event']);
        $this->assertContains($event->getType(), [GameEvent::TYPE_XP_BONUS, GameEvent::TYPE_DROP_BONUS]);
    }

    public function testGenerateSkipsWhenRandomEventAlreadyActive(): void
    {
        $existingEvent = new GameEvent();
        $existingEvent->setName('Existing');
        $existingEvent->setStatus(GameEvent::STATUS_ACTIVE);
        $existingEvent->setStartsAt(new \DateTime('-10 minutes'));
        $existingEvent->setEndsAt(new \DateTime('+20 minutes'));
        $existingEvent->setParameters(['random_event' => true]);

        $this->repository->method('findBy')->willReturn([$existingEvent]);

        $this->em->expects($this->never())->method('persist');

        $event = $this->generator->tryGenerate(100);

        $this->assertNull($event);
    }

    public function testGenerateSkipsWhenProbabilityFails(): void
    {
        $this->repository->method('findBy')->willReturn([]);
        $this->em->expects($this->never())->method('persist');

        // 0% chance — always fails
        $event = $this->generator->tryGenerate(0);

        $this->assertNull($event);
    }

    public function testGeneratedEventHasValidTimeRange(): void
    {
        $this->repository->method('findBy')->willReturn([]);

        $event = $this->generator->tryGenerate(100);

        $this->assertNotNull($event);
        $this->assertGreaterThan($event->getStartsAt(), $event->getEndsAt());

        $diff = $event->getStartsAt()->diff($event->getEndsAt());
        $durationMinutes = ($diff->h * 60) + $diff->i;

        // Duration should be between 10 and 30 minutes
        $this->assertGreaterThanOrEqual(10, $durationMinutes);
        $this->assertLessThanOrEqual(30, $durationMinutes);
    }

    public function testGeneratedEventIsNotRecurring(): void
    {
        $this->repository->method('findBy')->willReturn([]);

        $event = $this->generator->tryGenerate(100);

        $this->assertNotNull($event);
        $this->assertFalse($event->isRecurring());
    }

    public function testNonRandomEventDoesNotBlockGeneration(): void
    {
        $manualEvent = new GameEvent();
        $manualEvent->setName('Manual Festival');
        $manualEvent->setStatus(GameEvent::STATUS_ACTIVE);
        $manualEvent->setStartsAt(new \DateTime('-1 day'));
        $manualEvent->setEndsAt(new \DateTime('+1 day'));
        $manualEvent->setParameters(['multiplier' => 2.0]); // No random_event flag

        $this->repository->method('findBy')->willReturn([$manualEvent]);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $event = $this->generator->tryGenerate(100);

        $this->assertNotNull($event);
    }

    public function testGeneratedEventHasMultiplierParameter(): void
    {
        $this->repository->method('findBy')->willReturn([]);

        $event = $this->generator->tryGenerate(100);

        $this->assertNotNull($event);
        $params = $event->getParameters();
        $this->assertArrayHasKey('multiplier', $params);
        $this->assertGreaterThanOrEqual(1.5, $params['multiplier']);
    }

    public function testGeneratedEventHasKnownName(): void
    {
        $this->repository->method('findBy')->willReturn([]);

        $knownNames = ['Aurore Mystique', 'Esprit du Marchand', 'Heure Doree'];

        $event = $this->generator->tryGenerate(100);

        $this->assertNotNull($event);
        $this->assertContains($event->getName(), $knownNames);
    }
}
