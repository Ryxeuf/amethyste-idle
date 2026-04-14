<?php

namespace App\EventListener;

use App\Event\Game\QuestCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Incremente le score de reputation globale du joueur
 * lors de la completion d'une quete.
 *
 * Les quetes journalieres accordent moins de reputation que les quetes regulieres
 * pour eviter le farming trivial.
 */
class ReputationListener implements EventSubscriberInterface
{
    /** Gain de reputation pour une quete reguliere. */
    public const QUEST_REPUTATION_GAIN = 20;

    /** Gain de reputation pour une quete journaliere (reduit). */
    public const DAILY_QUEST_REPUTATION_GAIN = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            QuestCompletedEvent::NAME => 'onQuestCompleted',
        ];
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $quest = $event->getQuest();
        $player = $event->getPlayer();

        $gain = $quest->isDaily() ? self::DAILY_QUEST_REPUTATION_GAIN : self::QUEST_REPUTATION_GAIN;

        $player->addReputationScore($gain);

        $this->entityManager->flush();
    }
}
