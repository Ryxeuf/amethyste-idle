<?php

namespace App\EventListener;

use App\Event\Game\AchievementCompletedEvent;
use App\Event\Game\QuestCompletedEvent;
use App\GameEngine\Renown\PlayerRenownManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Accorde de la renommee joueur a la completion d'une quete ou d'un succes.
 */
class PlayerRenownListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly PlayerRenownManager $renownManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            QuestCompletedEvent::NAME => 'onQuestCompleted',
            AchievementCompletedEvent::NAME => 'onAchievementCompleted',
        ];
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $quest = $event->getQuest();
        $amount = $this->renownManager->getQuestRewardAmount($quest->isDaily());
        $this->renownManager->addRenown(
            $event->getPlayer(),
            $amount,
            sprintf('quest:%d', $quest->getId()),
        );
    }

    public function onAchievementCompleted(AchievementCompletedEvent $event): void
    {
        $achievement = $event->getAchievement();
        $amount = $this->renownManager->getAchievementRewardAmount($achievement->getCategory());
        $this->renownManager->addRenown(
            $event->getPlayer(),
            $amount,
            sprintf('achievement:%s', $achievement->getSlug()),
        );
    }
}
