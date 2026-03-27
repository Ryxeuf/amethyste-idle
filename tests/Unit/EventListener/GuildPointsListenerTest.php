<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\App\Fight;
use App\Entity\App\Guild;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Quest;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\EventListener\GuildPointsListener;
use App\GameEngine\Guild\GuildManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GuildPointsListenerTest extends TestCase
{
    private GuildManager&MockObject $guildManager;
    private EntityManagerInterface&MockObject $em;
    private GuildPointsListener $listener;

    protected function setUp(): void
    {
        $this->guildManager = $this->createMock(GuildManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->listener = new GuildPointsListener(
            $this->guildManager,
            $this->em,
        );
    }

    public function testSubscribedEvents(): void
    {
        $events = GuildPointsListener::getSubscribedEvents();

        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertArrayHasKey(QuestCompletedEvent::NAME, $events);
    }

    public function testOnMobDeadAwardsGuildPoints(): void
    {
        $guild = new Guild();
        $guild->setName('TestGuild');
        $guild->setTag('TG');

        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);

        $mob = $this->createMobWithFight(10, [$player]);

        $this->guildManager->method('getPlayerGuild')
            ->with($player)
            ->willReturn($guild);

        $this->em->expects($this->once())->method('flush');

        $this->listener->onMobDead(new MobDeadEvent($mob));

        // level 10 => 1 + floor(10/5) = 3 points
        $this->assertSame(3, $guild->getPoints());
    }

    public function testOnMobDeadNoGuildNoPoints(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);

        $mob = $this->createMobWithFight(5, [$player]);

        $this->guildManager->method('getPlayerGuild')
            ->willReturn(null);

        $this->em->expects($this->once())->method('flush');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadSkipsSummonedMobs(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(true);

        $this->guildManager->expects($this->never())->method('getPlayerGuild');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadSkipsDeadPlayers(): void
    {
        $guild = new Guild();
        $guild->setName('TestGuild');
        $guild->setTag('TG');

        $deadPlayer = $this->createMock(Player::class);
        $deadPlayer->method('isDead')->willReturn(true);

        $mob = $this->createMobWithFight(10, [$deadPlayer]);

        $this->guildManager->expects($this->never())->method('getPlayerGuild');
        $this->em->expects($this->once())->method('flush');

        $this->listener->onMobDead(new MobDeadEvent($mob));

        $this->assertSame(0, $guild->getPoints());
    }

    public function testOnQuestCompletedAwardsGuildPoints(): void
    {
        $guild = new Guild();
        $guild->setName('TestGuild');
        $guild->setTag('TG');

        $player = $this->createMock(Player::class);
        $quest = $this->createMock(Quest::class);

        $this->guildManager->method('getPlayerGuild')
            ->with($player)
            ->willReturn($guild);

        $this->em->expects($this->once())->method('flush');

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest));

        $this->assertSame(5, $guild->getPoints());
    }

    public function testOnQuestCompletedNoGuild(): void
    {
        $player = $this->createMock(Player::class);
        $quest = $this->createMock(Quest::class);

        $this->guildManager->method('getPlayerGuild')
            ->willReturn(null);

        $this->em->expects($this->once())->method('flush');

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest));
    }

    public function testPointsAccumulate(): void
    {
        $guild = new Guild();
        $guild->setName('TestGuild');
        $guild->setTag('TG');

        $player = $this->createMock(Player::class);
        $quest = $this->createMock(Quest::class);

        $this->guildManager->method('getPlayerGuild')
            ->willReturn($guild);

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest));
        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest));

        $this->assertSame(10, $guild->getPoints());
    }

    /**
     * @param Player[] $players
     */
    private function createMobWithFight(int $level, array $players): Mob&MockObject
    {
        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));

        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(false);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getLevel')->willReturn($level);

        return $mob;
    }
}
