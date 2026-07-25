<?php

namespace App\GameEngine\Codex;

use App\Entity\Game\CodexEntry;
use App\Event\Zone\ZoneVisitedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Debloque les entrees de Codex `zone_visit` a la premiere decouverte d'une zone (NAR-05).
 */
class CodexZoneVisitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CodexUnlockService $codexUnlockService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ZoneVisitedEvent::NAME => 'onZoneVisited',
        ];
    }

    public function onZoneVisited(ZoneVisitedEvent $event): void
    {
        $this->codexUnlockService->unlockByTrigger(
            $event->getPlayer(),
            CodexEntry::UNLOCK_ZONE_VISIT,
            $event->getZone()->getSlug(),
        );
    }
}
