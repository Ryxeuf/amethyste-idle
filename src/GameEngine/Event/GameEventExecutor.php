<?php

namespace App\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Event\Game\GameEventActivatedEvent;
use App\Event\Game\GameEventCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Scans GameEvents and transitions their status:
 * - SCHEDULED → ACTIVE when startsAt <= now
 * - ACTIVE → COMPLETED when endsAt < now
 * - Handles recurrence by creating the next occurrence on completion.
 */
class GameEventExecutor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Execute a full scan: activate scheduled events, complete expired ones.
     *
     * @return array{activated: int, completed: int, recurring: int}
     */
    public function execute(): array
    {
        $now = new \DateTime();

        [$activatedEvents, $scheduledChanges] = $this->activateScheduledEvents($now);
        [$completedEvents, $completed, $recurring] = $this->completeExpiredEvents($now);

        if ($scheduledChanges > 0 || $completed > 0) {
            $this->entityManager->flush();
        }

        foreach ($activatedEvents as $gameEvent) {
            $this->eventDispatcher->dispatch(
                new GameEventActivatedEvent($gameEvent),
                GameEventActivatedEvent::NAME,
            );
        }

        foreach ($completedEvents as $gameEvent) {
            $this->eventDispatcher->dispatch(
                new GameEventCompletedEvent($gameEvent),
                GameEventCompletedEvent::NAME,
            );
        }

        return [
            'activated' => \count($activatedEvents),
            'completed' => $completed,
            'recurring' => $recurring,
        ];
    }

    /**
     * @return array{GameEvent[], int} [activated events, total changes count]
     */
    private function activateScheduledEvents(\DateTime $now): array
    {
        $scheduled = $this->entityManager->getRepository(GameEvent::class)->findBy([
            'status' => GameEvent::STATUS_SCHEDULED,
        ]);

        $activated = [];
        $changes = 0;
        foreach ($scheduled as $event) {
            if ($event->getStartsAt() <= $now && $event->getEndsAt() > $now) {
                $event->setStatus(GameEvent::STATUS_ACTIVE);
                $activated[] = $event;
                ++$changes;

                $this->logger->info(sprintf(
                    '[GameEventExecutor] Activated event "%s" (type: %s)',
                    $event->getName(),
                    $event->getType(),
                ));
            } elseif ($event->getEndsAt() <= $now) {
                // Event was scheduled but already past its end — mark completed directly
                $event->setStatus(GameEvent::STATUS_COMPLETED);
                ++$changes;

                $this->logger->info(sprintf(
                    '[GameEventExecutor] Completed (skipped) event "%s" — already past end time',
                    $event->getName(),
                ));
            }
        }

        return [$activated, $changes];
    }

    /**
     * @return array{GameEvent[], int, int} [completed events, completed count, recurring count]
     */
    private function completeExpiredEvents(\DateTime $now): array
    {
        $active = $this->entityManager->getRepository(GameEvent::class)->findBy([
            'status' => GameEvent::STATUS_ACTIVE,
        ]);

        $completedEvents = [];
        $completed = 0;
        $recurring = 0;
        foreach ($active as $event) {
            if ($event->getEndsAt() <= $now) {
                $event->setStatus(GameEvent::STATUS_COMPLETED);
                $completedEvents[] = $event;
                ++$completed;

                $this->logger->info(sprintf(
                    '[GameEventExecutor] Completed event "%s" (type: %s)',
                    $event->getName(),
                    $event->getType(),
                ));

                if ($event->isRecurring() && $event->getRecurrenceInterval() !== null) {
                    $this->createNextOccurrence($event);
                    ++$recurring;
                }
            }
        }

        return [$completedEvents, $completed, $recurring];
    }

    private function createNextOccurrence(GameEvent $previous): void
    {
        $intervalMinutes = $previous->getRecurrenceInterval();
        $interval = new \DateInterval(sprintf('PT%dM', $intervalMinutes));

        $duration = $previous->getStartsAt()->diff($previous->getEndsAt());

        $nextStart = (clone $previous->getEndsAt())->add($interval);
        $nextEnd = (clone $nextStart)->add($duration);

        $next = new GameEvent();
        $next->setName($previous->getName());
        $next->setType($previous->getType());
        $next->setDescription($previous->getDescription());
        $next->setStatus(GameEvent::STATUS_SCHEDULED);
        $next->setStartsAt($nextStart);
        $next->setEndsAt($nextEnd);
        $next->setParameters($previous->getParameters());
        $next->setRecurring(true);
        $next->setRecurrenceInterval($intervalMinutes);
        $next->setMap($previous->getMap());

        $this->entityManager->persist($next);

        $this->logger->info(sprintf(
            '[GameEventExecutor] Created recurring event "%s" starting %s',
            $next->getName(),
            $nextStart->format('Y-m-d H:i'),
        ));
    }
}
