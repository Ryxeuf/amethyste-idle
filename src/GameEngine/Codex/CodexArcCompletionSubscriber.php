<?php

namespace App\GameEngine\Codex;

use App\Entity\Game\CodexEntry;
use App\Event\Game\QuestCompletedEvent;
use App\Repository\PlayerQuestCompletedRepository;
use App\Repository\QuestRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Debloque les entrees de Codex `arc_completed` quand un joueur termine la
 * derniere quete d'un arc narratif (NAR-05). Le declenchement n'a lieu que
 * lorsque toutes les quetes de l'arc sont completees.
 */
class CodexArcCompletionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CodexUnlockService $codexUnlockService,
        private readonly QuestRepository $questRepository,
        private readonly PlayerQuestCompletedRepository $playerQuestCompletedRepository,
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
        $storyArc = $event->getQuest()->getStoryArc();
        if ($storyArc === null) {
            return;
        }

        $arcQuests = $this->questRepository->findByStoryArc($storyArc);
        if ($arcQuests === []) {
            return;
        }

        $completed = $this->playerQuestCompletedRepository->countCompletedInArc($event->getPlayer(), $storyArc);
        if ($completed < \count($arcQuests)) {
            return;
        }

        $this->codexUnlockService->unlockByTrigger($event->getPlayer(), CodexEntry::UNLOCK_ARC_COMPLETED, $storyArc);
    }
}
