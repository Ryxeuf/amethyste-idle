<?php

namespace App\GameEngine\Event;

use App\Entity\App\GameEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Generates random world events to add dynamism.
 *
 * Selects an event type based on configurable weights,
 * creates a time-limited GameEvent, and lets the existing
 * GameEventExecutor handle activation and Mercure broadcast.
 */
class RandomEventGenerator
{
    /**
     * Event templates with weight (probability), duration range, and parameters.
     *
     * @var array<string, array{weight: int, name: string, description: string, type: string, durationMin: int, durationMax: int, parameters: array}>
     */
    private const EVENT_TEMPLATES = [
        'aurora' => [
            'weight' => 40,
            'name' => 'Aurore Mystique',
            'description' => 'Une aurore surnaturelle illumine le ciel, energisant les aventuriers. Tous les gains d\'XP sont augmentes de 50% !',
            'type' => GameEvent::TYPE_XP_BONUS,
            'durationMin' => 10,
            'durationMax' => 30,
            'parameters' => ['multiplier' => 1.5],
        ],
        'merchant' => [
            'weight' => 35,
            'name' => 'Esprit du Marchand',
            'description' => 'Un vent de fortune souffle sur le monde. Les monstres lachent davantage de butin !',
            'type' => GameEvent::TYPE_DROP_BONUS,
            'durationMin' => 15,
            'durationMax' => 30,
            'parameters' => ['multiplier' => 1.5],
        ],
        'golden_hour' => [
            'weight' => 25,
            'name' => 'Heure Doree',
            'description' => 'Les astres s\'alignent brievement, doublant les gains d\'XP pendant un court instant !',
            'type' => GameEvent::TYPE_XP_BONUS,
            'durationMin' => 10,
            'durationMax' => 15,
            'parameters' => ['multiplier' => 2.0],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Attempt to generate a random event.
     *
     * @param int $chancePercent probability (0-100) that an event is created
     *
     * @return GameEvent|null the created event, or null if skipped
     */
    public function tryGenerate(int $chancePercent = 30): ?GameEvent
    {
        if ($this->hasActiveRandomEvent()) {
            $this->logger->debug('[RandomEventGenerator] Skipped — a random event is already active or scheduled.');

            return null;
        }

        if (random_int(1, 100) > $chancePercent) {
            $this->logger->debug('[RandomEventGenerator] Skipped — probability check failed.');

            return null;
        }

        $template = $this->pickTemplate();
        $event = $this->createEvent($template);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $this->logger->info(sprintf(
            '[RandomEventGenerator] Created random event "%s" (type: %s, duration: %d min)',
            $event->getName(),
            $event->getType(),
            $event->getStartsAt()->diff($event->getEndsAt())->i,
        ));

        return $event;
    }

    /**
     * Pick a random event template based on weights.
     *
     * @return array<string, mixed>
     */
    private function pickTemplate(): array
    {
        $totalWeight = 0;
        foreach (self::EVENT_TEMPLATES as $tpl) {
            $totalWeight += $tpl['weight'];
        }

        $roll = random_int(1, $totalWeight);
        $cumulative = 0;

        foreach (self::EVENT_TEMPLATES as $tpl) {
            $cumulative += $tpl['weight'];
            if ($roll <= $cumulative) {
                return $tpl;
            }
        }

        // Fallback (should never reach here)
        return self::EVENT_TEMPLATES['aurora'];
    }

    /**
     * @param array<string, mixed> $template
     */
    private function createEvent(array $template): GameEvent
    {
        $durationMinutes = random_int($template['durationMin'], $template['durationMax']);

        $now = new \DateTime();
        $endsAt = (clone $now)->modify(sprintf('+%d minutes', $durationMinutes));

        $event = new GameEvent();
        $event->setName($template['name']);
        $event->setType($template['type']);
        $event->setDescription($template['description']);
        $event->setStatus(GameEvent::STATUS_SCHEDULED);
        $event->setStartsAt($now);
        $event->setEndsAt($endsAt);
        $event->setParameters(array_merge($template['parameters'], [
            'random_event' => true,
        ]));
        $event->setCreatedAt(new \DateTime());
        $event->setUpdatedAt(new \DateTime());

        return $event;
    }

    /**
     * Check if there is already an active or scheduled random event.
     */
    private function hasActiveRandomEvent(): bool
    {
        $events = $this->entityManager->getRepository(GameEvent::class)->findBy([
            'status' => [GameEvent::STATUS_ACTIVE, GameEvent::STATUS_SCHEDULED],
        ]);

        foreach ($events as $event) {
            $params = $event->getParameters();
            if (isset($params['random_event']) && $params['random_event'] === true) {
                return true;
            }
        }

        return false;
    }
}
