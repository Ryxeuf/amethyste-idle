<?php

namespace App\GameEngine\Quest;

use App\Event\CraftEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuestCraftTrackingListener implements EventSubscriberInterface
{
    public function __construct(private readonly PlayerQuestUpdater $playerQuestUpdater)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CraftEvent::NAME => 'onCraft',
        ];
    }

    public function onCraft(CraftEvent $event): void
    {
        $this->playerQuestUpdater->updateItemCrafted(
            $event->getResultItem()->getSlug(),
            $event->getQuantity()
        );
    }
}
