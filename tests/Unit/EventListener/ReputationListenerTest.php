<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\App\Player;
use App\Entity\Game\Quest;
use App\Enum\KarmaTitle;
use App\Event\Game\QuestCompletedEvent;
use App\EventListener\ReputationListener;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReputationListenerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ReputationListener $listener;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->listener = new ReputationListener($this->em);
    }

    public function testSubscribedEvents(): void
    {
        $events = ReputationListener::getSubscribedEvents();
        $this->assertArrayHasKey(QuestCompletedEvent::NAME, $events);
    }

    public function testRegularQuestGrantsFullReputation(): void
    {
        $player = new Player();
        $quest = $this->createMock(Quest::class);
        $quest->method('isDaily')->willReturn(false);

        $this->em->expects($this->once())->method('flush');

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest));

        $this->assertSame(ReputationListener::QUEST_REPUTATION_GAIN, $player->getReputationScore());
    }

    public function testDailyQuestGrantsReducedReputation(): void
    {
        $player = new Player();
        $quest = $this->createMock(Quest::class);
        $quest->method('isDaily')->willReturn(true);

        $this->em->expects($this->once())->method('flush');

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest));

        $this->assertSame(ReputationListener::DAILY_QUEST_REPUTATION_GAIN, $player->getReputationScore());
    }

    public function testReputationAccumulatesAcrossQuests(): void
    {
        $player = new Player();
        $regularQuest = $this->createMock(Quest::class);
        $regularQuest->method('isDaily')->willReturn(false);
        $dailyQuest = $this->createMock(Quest::class);
        $dailyQuest->method('isDaily')->willReturn(true);

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $regularQuest));
        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $regularQuest));
        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $dailyQuest));

        $expected = 2 * ReputationListener::QUEST_REPUTATION_GAIN + ReputationListener::DAILY_QUEST_REPUTATION_GAIN;
        $this->assertSame($expected, $player->getReputationScore());
    }

    public function testKarmaTitleProgresses(): void
    {
        $player = new Player();
        $this->assertSame(KarmaTitle::Novice, $player->getKarmaTitle());

        $player->setReputationScore(250);
        $this->assertSame(KarmaTitle::Connu, $player->getKarmaTitle());

        $player->setReputationScore(5000);
        $this->assertSame(KarmaTitle::Honore, $player->getKarmaTitle());
    }
}
