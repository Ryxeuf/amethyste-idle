<?php

namespace App\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Entity\App\PlayerQuest;
use App\Entity\Game\Quest;
use App\Event\Game\GameEventCompletedEvent;
use App\GameEngine\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Retire les quetes ephemeres des journaux quand leur evenement s'acheve
 * (tache 131).
 *
 * Une quete liee a un `GameEvent` etait deja invisible et inacceptable hors
 * periode (`PlayerQuestHelper::getAvailableQuests`, `QuestController::accept`).
 * Mais rien ne s'occupait de celles **deja acceptees** : elles restaient dans
 * le journal indefiniment, et pouvaient etre rendues des mois apres la fin de
 * l'evenement. « Ephemere » etait applique a l'entree, jamais a la sortie.
 *
 * Le retrait ne touche pas `PlayerQuestCompleted` : ce qui a ete rendu pendant
 * l'evenement est acquis. Seul l'inacheve disparait.
 */
class EphemeralQuestWithdrawer implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GameEventCompletedEvent::NAME => 'onEventCompleted',
        ];
    }

    public function onEventCompleted(GameEventCompletedEvent $event): void
    {
        $this->withdraw($event->getGameEvent());
    }

    /**
     * @return int nombre de quetes retirees
     */
    public function withdraw(GameEvent $gameEvent): int
    {
        $quests = $this->entityManager->getRepository(Quest::class)->findBy(['gameEvent' => $gameEvent]);
        if ([] === $quests) {
            return 0;
        }

        $inProgress = $this->entityManager->getRepository(PlayerQuest::class)->findBy(['quest' => $quests]);
        if ([] === $inProgress) {
            return 0;
        }

        foreach ($inProgress as $playerQuest) {
            $this->entityManager->remove($playerQuest);
        }

        $this->entityManager->flush();

        // La notification part apres le flush : prevenir d'un retrait qui
        // n'aurait pas abouti serait pire que ne rien dire.
        foreach ($inProgress as $playerQuest) {
            $this->notificationService->notify(
                $playerQuest->getPlayer(),
                'quest',
                'Quete ephemere terminee',
                sprintf('« %s » a pris fin avec l\'evenement « %s ».', $playerQuest->getQuest()->getName(), $gameEvent->getName()),
                'clock',
            );
        }

        $this->logger->info('Ephemeral quests withdrawn', [
            'event' => $gameEvent->getName(),
            'withdrawn' => \count($inProgress),
        ]);

        return \count($inProgress);
    }
}
