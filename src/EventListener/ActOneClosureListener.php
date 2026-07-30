<?php

namespace App\EventListener;

use App\Event\Game\QuestCompletedEvent;
use App\Event\Game\TutorialCompletedEvent;
use App\GameEngine\Progression\HomeSettlementResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * La cloture de l'acte I constate le foyer d'attache (ONB-13).
 *
 * **Le declencheur est la derniere etape de l'arc, pas une quete nommee.** Un
 * slug ecrit ici serait un second endroit ou vit l'ordre de la chaine : le jour
 * ou NAR-20 en reecrit les libelles, ou ou une onziemme etape s'ajoute, la
 * cloture se declencherait au mauvais moment sans que rien ne le dise. Le rang
 * dans l'arc est la seule donnee qui reste vraie.
 */
class ActOneClosureListener implements EventSubscriberInterface
{
    /**
     * Le rang de la derniere etape de l'arc `intro` (GAME_ONBOARDING § 5.2).
     */
    public const CLOSING_STEP = 10;

    public function __construct(
        private readonly HomeSettlementResolver $homeSettlementResolver,
        private readonly EventDispatcherInterface $eventDispatcher,
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

        if ('intro' !== $quest->getStoryArc() || self::CLOSING_STEP !== $quest->getArcOrder()) {
            return;
        }

        $this->homeSettlementResolver->claim($event->getPlayer());

        // ONB-14 : le tutoriel se termine **avec l'arc**, et pas ailleurs.
        // `TutorialManager` faisait avancer un compteur parallele et emettait
        // cet evenement de son cote ; le succes `tutorial-complete` pouvait donc
        // tomber sans que l'acte I soit fini.
        $this->eventDispatcher->dispatch(
            new TutorialCompletedEvent($event->getPlayer()),
            TutorialCompletedEvent::NAME,
        );
    }
}
