<?php

namespace App\GameEngine\Tutorial;

use App\Entity\App\Player;
use App\Enum\TutorialStep;
use App\Event\Game\TutorialCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TutorialManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getCurrentStep(Player $player): ?TutorialStep
    {
        $step = $player->getTutorialStep();

        return null !== $step ? TutorialStep::tryFrom($step) : null;
    }

    public function isCompleted(Player $player): bool
    {
        return null === $player->getTutorialStep();
    }

    public function isInTutorial(Player $player): bool
    {
        return null !== $player->getTutorialStep();
    }

    public function advanceIfOnStep(Player $player, TutorialStep $expectedStep): bool
    {
        $current = $this->getCurrentStep($player);

        if (null === $current || $current !== $expectedStep) {
            return false;
        }

        return $this->advance($player);
    }

    public function advance(Player $player): bool
    {
        $current = $this->getCurrentStep($player);

        if (null === $current) {
            return false;
        }

        $next = $current->next();

        if (null === $next) {
            $this->complete($player);

            return true;
        }

        $player->setTutorialStep($next->value);
        $this->entityManager->flush();

        return true;
    }

    public function skip(Player $player): void
    {
        if ($this->isCompleted($player)) {
            return;
        }

        $this->complete($player);
    }

    private function complete(Player $player): void
    {
        $player->setTutorialStep(null);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new TutorialCompletedEvent($player),
            TutorialCompletedEvent::NAME,
        );
    }
}
