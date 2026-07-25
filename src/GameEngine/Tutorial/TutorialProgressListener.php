<?php

namespace App\GameEngine\Tutorial;

use App\Entity\App\Fight;
use App\Enum\TutorialStep;
use App\Event\CraftEvent;
use App\Event\Fight\FightLootedEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\Event\Zone\PlayerTraveledEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TutorialProgressListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly TutorialManager $tutorialManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerTraveledEvent::NAME => 'onPlayerTraveled',
            MobDeadEvent::NAME => 'onMobDead',
            FightLootedEvent::NAME => 'onFightLooted',
            QuestCompletedEvent::NAME => 'onQuestCompleted',
            CraftEvent::NAME => 'onCraft',
        ];
    }

    /**
     * L'etape « deplacement » du tutoriel est validee par un voyage de zone
     * depuis le pivot PBBG (ZON-22), la carte navigable ayant disparu.
     */
    public function onPlayerTraveled(PlayerTraveledEvent $event): void
    {
        $this->tutorialManager->advanceIfOnStep($event->getPlayer(), TutorialStep::Movement);
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $fight = $event->getMob()->getFight();
        if (!$fight) {
            return;
        }

        $players = $fight->getPlayers();
        if ($players->isEmpty()) {
            return;
        }

        $this->tutorialManager->advanceIfOnStep($players->first(), TutorialStep::Combat);
    }

    public function onFightLooted(FightLootedEvent $event): void
    {
        $fight = $this->entityManager->getRepository(Fight::class)->find($event->getFightId());
        if (!$fight) {
            return;
        }

        $players = $fight->getPlayers();
        if ($players->isEmpty()) {
            return;
        }

        $this->tutorialManager->advanceIfOnStep($players->first(), TutorialStep::Inventory);
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $this->tutorialManager->advanceIfOnStep($event->getPlayer(), TutorialStep::Quests);
    }

    public function onCraft(CraftEvent $event): void
    {
        $this->tutorialManager->advanceIfOnStep($event->getPlayer(), TutorialStep::Craft);
    }
}
