<?php

namespace App\Tests\Unit\GameEngine\Tutorial;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Enum\TutorialStep;
use App\Event\Zone\PlayerTraveledEvent;
use App\GameEngine\Tutorial\TutorialManager;
use App\GameEngine\Tutorial\TutorialProgressListener;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TutorialProgressListenerTest extends TestCase
{
    private TutorialManager&MockObject $tutorialManager;
    private TutorialProgressListener $listener;

    protected function setUp(): void
    {
        $this->tutorialManager = $this->createMock(TutorialManager::class);
        $this->listener = new TutorialProgressListener(
            $this->tutorialManager,
            $this->createMock(EntityManagerInterface::class),
        );
    }

    public function testSubscribesToTravelInsteadOfMovement(): void
    {
        $events = TutorialProgressListener::getSubscribedEvents();

        // ZON-22 : l'etape « deplacement » est validee par un voyage de zone.
        $this->assertArrayHasKey(PlayerTraveledEvent::NAME, $events);
        $this->assertSame('onPlayerTraveled', $events[PlayerTraveledEvent::NAME]);
    }

    public function testTravelAdvancesTheMovementStep(): void
    {
        $player = new Player();
        $zone = (new Zone())->setSlug('foret-des-murmures')->setName('Forêt');

        $this->tutorialManager->expects($this->once())
            ->method('advanceIfOnStep')
            ->with($player, TutorialStep::Movement)
            ->willReturn(true);

        $this->listener->onPlayerTraveled(new PlayerTraveledEvent($player, $zone));
    }
}
