<?php

namespace App\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Entity\App\Map;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides active bonus multipliers from GameEvents.
 * Used by LootGenerator and MateriaXpGranter to apply event bonuses.
 */
class GameEventBonusProvider
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Get the XP multiplier from active xp_bonus events.
     * Returns 1.0 if no active bonus.
     */
    public function getXpMultiplier(?Map $map = null): float
    {
        return $this->getMultiplier(GameEvent::TYPE_XP_BONUS, $map);
    }

    /**
     * Get the drop multiplier from active drop_bonus events.
     * Returns 1.0 if no active bonus.
     */
    public function getDropMultiplier(?Map $map = null): float
    {
        return $this->getMultiplier(GameEvent::TYPE_DROP_BONUS, $map);
    }

    /**
     * Get the gathering multiplier from active gathering_bonus events.
     * Applies to non-combat resource gathering (mining, herboristerie, fishing, skinning).
     * Returns 1.0 if no active bonus.
     */
    public function getGatheringMultiplier(?Map $map = null): float
    {
        return $this->getMultiplier(GameEvent::TYPE_GATHERING_BONUS, $map);
    }

    private function getMultiplier(string $type, ?Map $map): float
    {
        $events = $this->entityManager->getRepository(GameEvent::class)->findBy([
            'status' => GameEvent::STATUS_ACTIVE,
            'type' => $type,
        ]);

        $multiplier = 1.0;

        foreach ($events as $event) {
            $eventMap = $event->getMap();

            // Global event (no map restriction) or event on the same map
            if ($eventMap === null || ($map !== null && $eventMap->getId() === $map->getId())) {
                $params = $event->getParameters();
                $eventMultiplier = $params['multiplier'] ?? 1.0;
                $multiplier *= (float) $eventMultiplier;
            }
        }

        return $multiplier;
    }
}
