<?php

namespace App\Tests\Unit\GameEngine\Tutorial;

use App\Entity\App\Player;
use App\Enum\TutorialStep;
use App\Event\Game\TutorialCompletedEvent;
use App\GameEngine\Tutorial\TutorialManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TutorialManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EventDispatcherInterface&MockObject $dispatcher;
    private TutorialManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->manager = new TutorialManager($this->em, $this->dispatcher);
    }

    public function testGetCurrentStepReturnsNullWhenCompleted(): void
    {
        $player = $this->createPlayer(null);

        $this->assertNull($this->manager->getCurrentStep($player));
    }

    public function testGetCurrentStepReturnsEnum(): void
    {
        $player = $this->createPlayer(TutorialStep::Combat->value);

        $this->assertSame(TutorialStep::Combat, $this->manager->getCurrentStep($player));
    }

    public function testIsCompletedWhenNull(): void
    {
        $player = $this->createPlayer(null);

        $this->assertTrue($this->manager->isCompleted($player));
    }

    public function testIsNotCompletedWhenInProgress(): void
    {
        $player = $this->createPlayer(TutorialStep::Movement->value);

        $this->assertFalse($this->manager->isCompleted($player));
    }

    public function testAdvanceMovesToNextStep(): void
    {
        $player = $this->createPlayer(TutorialStep::Movement->value);

        $this->em->expects($this->once())->method('flush');
        $this->dispatcher->expects($this->never())->method('dispatch');

        $result = $this->manager->advance($player);

        $this->assertTrue($result);
        $this->assertSame(TutorialStep::Combat->value, $player->getTutorialStep());
    }

    public function testAdvanceLastStepCompletesTutorial(): void
    {
        $player = $this->createPlayer(TutorialStep::Craft->value);

        $this->em->expects($this->once())->method('flush');
        $this->dispatcher->expects($this->once())->method('dispatch')
            ->with(
                $this->isInstanceOf(TutorialCompletedEvent::class),
                TutorialCompletedEvent::NAME,
            );

        $result = $this->manager->advance($player);

        $this->assertTrue($result);
        $this->assertNull($player->getTutorialStep());
    }

    public function testAdvanceReturnsFalseWhenAlreadyCompleted(): void
    {
        $player = $this->createPlayer(null);

        $this->assertFalse($this->manager->advance($player));
    }

    public function testAdvanceIfOnStepMatchingStep(): void
    {
        $player = $this->createPlayer(TutorialStep::Combat->value);

        $this->em->expects($this->once())->method('flush');

        $result = $this->manager->advanceIfOnStep($player, TutorialStep::Combat);

        $this->assertTrue($result);
        $this->assertSame(TutorialStep::Inventory->value, $player->getTutorialStep());
    }

    public function testAdvanceIfOnStepWrongStep(): void
    {
        $player = $this->createPlayer(TutorialStep::Movement->value);

        $result = $this->manager->advanceIfOnStep($player, TutorialStep::Combat);

        $this->assertFalse($result);
        $this->assertSame(TutorialStep::Movement->value, $player->getTutorialStep());
    }

    public function testSkipCompletesTutorial(): void
    {
        $player = $this->createPlayer(TutorialStep::Movement->value);

        $this->em->expects($this->once())->method('flush');
        $this->dispatcher->expects($this->once())->method('dispatch')
            ->with(
                $this->isInstanceOf(TutorialCompletedEvent::class),
                TutorialCompletedEvent::NAME,
            );

        $this->manager->skip($player);

        $this->assertNull($player->getTutorialStep());
    }

    public function testSkipDoesNothingWhenAlreadyCompleted(): void
    {
        $player = $this->createPlayer(null);

        $this->em->expects($this->never())->method('flush');
        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->manager->skip($player);
    }

    private function createPlayer(?int $tutorialStep): Player
    {
        $player = new Player();
        $player->setTutorialStep($tutorialStep);

        return $player;
    }
}
