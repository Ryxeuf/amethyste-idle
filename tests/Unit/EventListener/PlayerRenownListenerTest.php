<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\App\Player;
use App\Entity\Game\Achievement;
use App\Entity\Game\Quest;
use App\Event\Game\AchievementCompletedEvent;
use App\Event\Game\QuestCompletedEvent;
use App\EventListener\PlayerRenownListener;
use App\GameEngine\Renown\PlayerRenownManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayerRenownListenerTest extends TestCase
{
    private PlayerRenownManager&MockObject $renownManager;
    private PlayerRenownListener $listener;

    protected function setUp(): void
    {
        $this->renownManager = $this->createMock(PlayerRenownManager::class);
        $this->listener = new PlayerRenownListener($this->renownManager);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = PlayerRenownListener::getSubscribedEvents();

        $this->assertArrayHasKey(QuestCompletedEvent::NAME, $events);
        $this->assertArrayHasKey(AchievementCompletedEvent::NAME, $events);
    }

    public function testOnQuestCompletedGrantsRenown(): void
    {
        $player = new Player();
        $quest = $this->createMock(Quest::class);
        $quest->method('getId')->willReturn(42);
        $quest->method('isDaily')->willReturn(false);

        $this->renownManager->expects($this->once())
            ->method('getQuestRewardAmount')
            ->with(false)
            ->willReturn(25);

        $this->renownManager->expects($this->once())
            ->method('addRenown')
            ->with($player, 25, 'quest:42');

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest));
    }

    public function testOnAchievementCompletedGrantsRenown(): void
    {
        $player = new Player();
        $achievement = $this->createMock(Achievement::class);
        $achievement->method('getSlug')->willReturn('first_kill');
        $achievement->method('getCategory')->willReturn('combat');

        $this->renownManager->expects($this->once())
            ->method('getAchievementRewardAmount')
            ->with('combat')
            ->willReturn(20);

        $this->renownManager->expects($this->once())
            ->method('addRenown')
            ->with($player, 20, 'achievement:first_kill');

        $this->listener->onAchievementCompleted(new AchievementCompletedEvent($player, $achievement));
    }
}
