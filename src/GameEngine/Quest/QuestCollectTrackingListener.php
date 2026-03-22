<?php

namespace App\GameEngine\Quest;

use App\Event\GatheringEvent;
use App\Event\Map\SpotHarvestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuestCollectTrackingListener implements EventSubscriberInterface
{
    public function __construct(private readonly PlayerQuestUpdater $playerQuestUpdater)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SpotHarvestEvent::NAME => 'onSpotHarvest',
            GatheringEvent::NAME => 'onGathering',
        ];
    }

    public function onSpotHarvest(SpotHarvestEvent $event): void
    {
        foreach ($event->getHarvestedItems() as $playerItem) {
            $this->playerQuestUpdater->updateItemCollected(
                $playerItem->getGenericItem()->getSlug()
            );
        }
    }

    public function onGathering(GatheringEvent $event): void
    {
        $this->playerQuestUpdater->updateItemCollected(
            $event->getItem()->getSlug(),
            $event->getQuantity()
        );
    }
}
