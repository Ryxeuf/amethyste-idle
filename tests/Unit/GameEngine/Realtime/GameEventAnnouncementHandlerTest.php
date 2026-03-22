<?php

namespace App\Tests\Unit\GameEngine\Realtime;

use App\Entity\App\GameEvent;
use App\Event\Game\GameEventActivatedEvent;
use App\GameEngine\Realtime\GameEventAnnouncementHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class GameEventAnnouncementHandlerTest extends TestCase
{
    public function testPublishesMercureUpdateOnActivation(): void
    {
        $hub = $this->createMock(HubInterface::class);

        $gameEvent = new GameEvent();
        $ref = new \ReflectionProperty(GameEvent::class, 'id');
        $ref->setValue($gameEvent, 42);
        $gameEvent->setName('Bonus XP weekend');
        $gameEvent->setType(GameEvent::TYPE_XP_BONUS);
        $gameEvent->setDescription('Double XP pour tous !');
        $gameEvent->setStatus(GameEvent::STATUS_ACTIVE);
        $gameEvent->setStartsAt(new \DateTime('-10 minutes'));
        $gameEvent->setEndsAt(new \DateTime('+50 minutes'));

        $hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) {
                $data = json_decode($update->getData(), true);

                return $data['topic'] === 'event/announce'
                    && $data['type'] === 'activated'
                    && $data['event']['name'] === 'Bonus XP weekend';
            }));

        $handler = new GameEventAnnouncementHandler($hub, new NullLogger());
        $handler->onGameEventActivated(new GameEventActivatedEvent($gameEvent));
    }

    public function testSubscribesToCorrectEvent(): void
    {
        $events = GameEventAnnouncementHandler::getSubscribedEvents();

        $this->assertArrayHasKey(GameEventActivatedEvent::NAME, $events);
        $this->assertSame('onGameEventActivated', $events[GameEventActivatedEvent::NAME]);
    }
}
