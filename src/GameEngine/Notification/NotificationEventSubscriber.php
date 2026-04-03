<?php

namespace App\GameEngine\Notification;

use App\Event\CraftEvent;
use App\Event\Fight\PlayerDeadEvent;
use App\Event\Game\AchievementCompletedEvent;
use App\Event\Game\DomainLevelUpEvent;
use App\Event\Game\QuestCompletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            QuestCompletedEvent::NAME => 'onQuestCompleted',
            AchievementCompletedEvent::NAME => 'onAchievementCompleted',
            PlayerDeadEvent::NAME => 'onPlayerDead',
            DomainLevelUpEvent::NAME => 'onDomainLevelUp',
            CraftEvent::NAME => 'onCraft',
        ];
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $player = $event->getPlayer();
        $quest = $event->getQuest();

        $this->notificationService->notify(
            $player,
            'quest',
            'Quete terminee !',
            sprintf('Vous avez termine la quete "%s".', $quest->getName()),
            link: '/game/quests',
        );
    }

    public function onAchievementCompleted(AchievementCompletedEvent $event): void
    {
        $player = $event->getPlayer();
        $achievement = $event->getAchievement();

        $this->notificationService->notify(
            $player,
            'achievement',
            'Succes debloque !',
            sprintf('Vous avez debloque le succes "%s".', $achievement->getTitle()),
            link: '/game/achievements',
        );
    }

    public function onPlayerDead(PlayerDeadEvent $event): void
    {
        $player = $event->getPlayer();

        $this->notificationService->notify(
            $player,
            'system',
            'Vous etes tombe au combat',
            'Vous avez ete vaincu et ramene au dernier point de sauvegarde.',
        );
    }

    public function onDomainLevelUp(DomainLevelUpEvent $event): void
    {
        $player = $event->getPlayer();
        $domain = $event->getDomain();
        $newLevel = $event->getNewLevel();

        $this->notificationService->notify(
            $player,
            'domain_level',
            'Competence debloquee !',
            sprintf('%s atteint le niveau %d !', $domain->getTitle(), $newLevel),
            link: '/game/skills',
        );
    }

    public function onCraft(CraftEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getResultItem();
        $quantity = $event->getQuantity();
        $xp = $event->getRecipe()->getXpReward();

        $this->notificationService->notify(
            $player,
            'craft_success',
            'Craft reussi !',
            sprintf('%s x%d fabrique (+%d XP)', $item->getName(), $quantity, $xp),
            link: '/game/crafting',
        );
    }
}
