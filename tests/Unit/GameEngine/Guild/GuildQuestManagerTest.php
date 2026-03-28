<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
use App\Entity\App\GuildQuest;
use App\Entity\App\Player;
use App\Enum\GuildQuestType;
use App\Enum\GuildRank;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\GuildQuestManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GuildQuestManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private GuildManager&MockObject $guildManager;
    private GuildQuestManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->guildManager = $this->createMock(GuildManager::class);

        $this->manager = new GuildQuestManager($this->em, $this->guildManager);
    }

    public function testGetPlayerGuildDelegatesToGuildManager(): void
    {
        $player = $this->createMock(Player::class);
        $guild = $this->createGuild();

        $this->guildManager->expects($this->once())
            ->method('getPlayerGuild')
            ->with($player)
            ->willReturn($guild);

        $result = $this->manager->getPlayerGuild($player);
        $this->assertSame($guild, $result);
    }

    public function testGetPlayerGuildReturnsNullWhenNoGuild(): void
    {
        $player = $this->createMock(Player::class);

        $this->guildManager->method('getPlayerGuild')->willReturn(null);

        $this->assertNull($this->manager->getPlayerGuild($player));
    }

    public function testTrackProgressCompletesQuestWhenGoalReached(): void
    {
        $guild = $this->createGuild();

        $quest = new GuildQuest();
        $quest->setGuild($guild);
        $quest->setType(GuildQuestType::Kill);
        $quest->setTarget('slime');
        $quest->setTargetLabel('Slime');
        $quest->setGoal(10);
        $quest->setProgress(9);
        $quest->setGilsReward(1000);
        $quest->setPointsReward(20);
        $quest->setExpiresAt(new \DateTime('+7 days'));

        // Add a member to the guild for reward distribution
        $player = $this->createMock(Player::class);
        $player->expects($this->once())->method('addGils')->with(1000);

        $member = $this->createMock(GuildMember::class);
        $member->method('getPlayer')->willReturn($player);

        $guild->addMember($member);

        $this->mockQuestQuery([$quest]);
        $this->em->expects($this->once())->method('flush');

        $this->manager->trackProgress($guild, GuildQuestType::Kill, 'slime', 1);

        $this->assertTrue($quest->isCompleted());
        $this->assertNotNull($quest->getCompletedAt());
        $this->assertSame(10, $quest->getProgress());
        $this->assertSame(20, $guild->getPoints());
    }

    public function testTrackProgressDoesNotCompleteIfGoalNotReached(): void
    {
        $guild = $this->createGuild();

        $quest = new GuildQuest();
        $quest->setGuild($guild);
        $quest->setType(GuildQuestType::Kill);
        $quest->setTarget('slime');
        $quest->setTargetLabel('Slime');
        $quest->setGoal(10);
        $quest->setProgress(5);
        $quest->setGilsReward(1000);
        $quest->setPointsReward(20);
        $quest->setExpiresAt(new \DateTime('+7 days'));

        $this->mockQuestQuery([$quest]);
        $this->em->expects($this->once())->method('flush');

        $this->manager->trackProgress($guild, GuildQuestType::Kill, 'slime', 1);

        $this->assertFalse($quest->isCompleted());
        $this->assertSame(6, $quest->getProgress());
        $this->assertSame(0, $guild->getPoints());
    }

    public function testTrackProgressNoMatchingQuests(): void
    {
        $guild = $this->createGuild();

        $this->mockQuestQuery([]);
        $this->em->expects($this->never())->method('flush');

        $this->manager->trackProgress($guild, GuildQuestType::Kill, 'dragon', 1);
    }

    public function testGuildQuestEntityProgressPercent(): void
    {
        $quest = new GuildQuest();
        $quest->setGoal(50);

        $quest->setProgress(0);
        $this->assertSame(0.0, $quest->getProgressPercent());

        $quest->setProgress(25);
        $this->assertSame(50.0, $quest->getProgressPercent());

        $quest->setProgress(50);
        $this->assertSame(100.0, $quest->getProgressPercent());

        // Cannot exceed 100%
        $quest->setProgress(100);
        $this->assertSame(100.0, $quest->getProgressPercent());
    }

    public function testGuildQuestEntityStates(): void
    {
        $quest = new GuildQuest();
        $quest->setGoal(10);
        $quest->setExpiresAt(new \DateTime('+7 days'));

        // Active
        $this->assertTrue($quest->isActive());
        $this->assertFalse($quest->isCompleted());
        $this->assertFalse($quest->isExpired());

        // Completed
        $quest->setCompletedAt(new \DateTime());
        $this->assertFalse($quest->isActive());
        $this->assertTrue($quest->isCompleted());
        $this->assertFalse($quest->isExpired());

        // Expired
        $quest2 = new GuildQuest();
        $quest2->setGoal(10);
        $quest2->setExpiresAt(new \DateTime('-1 day'));
        $this->assertFalse($quest2->isActive());
        $this->assertFalse($quest2->isCompleted());
        $this->assertTrue($quest2->isExpired());
    }

    public function testGilsRewardDistribution(): void
    {
        $guild = $this->createGuild();

        $quest = new GuildQuest();
        $quest->setGuild($guild);
        $quest->setType(GuildQuestType::Craft);
        $quest->setTarget('iron-sword');
        $quest->setTargetLabel('Epee en fer');
        $quest->setGoal(5);
        $quest->setProgress(4);
        $quest->setGilsReward(600);
        $quest->setPointsReward(15);
        $quest->setExpiresAt(new \DateTime('+7 days'));

        // 3 members → 600/3 = 200 gils each
        $players = [];
        for ($i = 0; $i < 3; ++$i) {
            $player = $this->createMock(Player::class);
            $player->expects($this->once())->method('addGils')->with(200);
            $players[] = $player;

            $member = $this->createMock(GuildMember::class);
            $member->method('getPlayer')->willReturn($player);
            $guild->addMember($member);
        }

        $this->mockQuestQuery([$quest]);
        $this->em->expects($this->once())->method('flush');

        $this->manager->trackProgress($guild, GuildQuestType::Craft, 'iron-sword', 1);

        $this->assertTrue($quest->isCompleted());
        $this->assertSame(15, $guild->getPoints());
    }

    private function createGuild(): Guild
    {
        $leader = $this->createMock(Player::class);

        $guild = new Guild();
        $guild->setName('TestGuild');
        $guild->setTag('TG');
        $guild->setLeader($leader);

        return $guild;
    }

    /**
     * @param GuildQuest[] $results
     */
    private function mockQuestQuery(array $results): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getResult')->willReturn($results);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('createQueryBuilder')->willReturn($qb);

        $this->em->method('getRepository')
            ->with(GuildQuest::class)
            ->willReturn($repo);
    }
}
