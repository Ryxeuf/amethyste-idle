<?php

namespace App\GameEngine\Event;

use App\Entity\App\GameEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

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

        $activated = $this->activateScheduledEvents($now);
        [$completed, $recurring] = $this->completeExpiredEvents($now);

        if ($activated > 0 || $completed > 0) {
            $this->entityManager->flush();
        }

        return [
            'activated' => $activated,
            'completed' => $completed,
            'recurring' => $recurring,
        ];
    }

    private function activateScheduledEvents(\DateTime $now): int
    {
        $scheduled = $this->entityManager->getRepository(GameEvent::class)->findBy([
            'status' => GameEvent::STATUS_SCHEDULED,
        ]);

        $count = 0;
        foreach ($scheduled as $event) {
            if ($event->getStartsAt() <= $now && $event->getEndsAt() > $now) {
                $event->setStatus(GameEvent::STATUS_ACTIVE);
                ++$count;

                $this->logger->info(sprintf(
                    '[GameEventExecutor] Activated event "%s" (type: %s)',
                    $event->getName(),
                    $event->getType(),
                ));
            } elseif ($event->getEndsAt() <= $now) {
                // Event was scheduled but already past its end — mark completed directly
                $event->setStatus(GameEvent::STATUS_COMPLETED);
                ++$count;

                $this->logger->info(sprintf(
                    '[GameEventExecutor] Completed (skipped) event "%s" — already past end time',
                    $event->getName(),
                ));
            }
        }

        return $count;
    }

    /**
     * @return array{int, int} [completed count, recurring count]
     */
    private function completeExpiredEvents(\DateTime $now): array
    {
        $active = $this->entityManager->getRepository(GameEvent::class)->findBy([
            'status' => GameEvent::STATUS_ACTIVE,
        ]);

        $completed = 0;
        $recurring = 0;
        foreach ($active as $event) {
            if ($event->getEndsAt() <= $now) {
                $event->setStatus(GameEvent::STATUS_COMPLETED);
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

        return [$completed, $recurring];
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
