<?php

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\PlayerSeasonRankingSnapshot;
use App\Enum\RankingTab;
use App\GameEngine\Season\SeasonRankingSnapshotService;
use App\Repository\DomainExperienceRepository;
use App\Repository\PlayerBestiaryRepository;
use App\Repository\PlayerQuestCompletedRepository;
use App\Repository\PlayerSeasonRankingSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SeasonRankingSnapshotServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private PlayerBestiaryRepository&MockObject $bestiaryRepo;
    private PlayerQuestCompletedRepository&MockObject $questRepo;
    private DomainExperienceRepository&MockObject $xpRepo;
    private PlayerSeasonRankingSnapshotRepository&MockObject $snapshotRepo;
    private SeasonRankingSnapshotService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->bestiaryRepo = $this->createMock(PlayerBestiaryRepository::class);
        $this->questRepo = $this->createMock(PlayerQuestCompletedRepository::class);
        $this->xpRepo = $this->createMock(DomainExperienceRepository::class);
        $this->snapshotRepo = $this->createMock(PlayerSeasonRankingSnapshotRepository::class);

        $this->service = new SeasonRankingSnapshotService(
            $this->em,
            $this->bestiaryRepo,
            $this->questRepo,
            $this->xpRepo,
            $this->snapshotRepo,
        );
    }

    public function testSnapshotPersistsTopRowsForEachTab(): void
    {
        $season = $this->createSeason();
        $p1 = $this->createPlayer(1, 'Alice');
        $p2 = $this->createPlayer(2, 'Bob');

        $this->snapshotRepo->method('countForSeason')->with($season)->willReturn(0);

        $this->bestiaryRepo->expects($this->once())
            ->method('findTopKillers')
            ->with(50)
            ->willReturn([
                ['player' => $p1, 'totalKills' => 120],
                ['player' => $p2, 'totalKills' => 80],
            ]);

        $this->questRepo->expects($this->once())
            ->method('findTopQuestCompleters')
            ->with(50)
            ->willReturn([
                ['player' => $p1, 'totalQuests' => 42],
            ]);

        $this->xpRepo->expects($this->once())
            ->method('findTopXpEarners')
            ->with(50)
            ->willReturn([
                ['player' => $p2, 'totalXp' => 9999],
            ]);

        $persisted = [];
        $this->em->expects($this->exactly(4))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $this->em->expects($this->once())->method('flush');

        $counts = $this->service->snapshot($season);

        $this->assertSame(
            [RankingTab::Kills->value => 2, RankingTab::Quests->value => 1, RankingTab::Xp->value => 1],
            $counts,
        );
        $this->assertCount(4, $persisted);

        $first = $persisted[0];
        $this->assertInstanceOf(PlayerSeasonRankingSnapshot::class, $first);
        $this->assertSame(RankingTab::Kills, $first->getTab());
        $this->assertSame(1, $first->getRank());
        $this->assertSame($p1, $first->getPlayer());
        $this->assertSame('Alice', $first->getPlayerName());
        $this->assertSame(120, $first->getTotalValue());

        $second = $persisted[1];
        $this->assertInstanceOf(PlayerSeasonRankingSnapshot::class, $second);
        $this->assertSame(2, $second->getRank());
        $this->assertSame(80, $second->getTotalValue());
    }

    public function testSnapshotIsIdempotentWhenAlreadyArchived(): void
    {
        $season = $this->createSeason();

        $this->snapshotRepo->method('countForSeason')->with($season)->willReturn(150);

        $this->bestiaryRepo->expects($this->never())->method('findTopKillers');
        $this->questRepo->expects($this->never())->method('findTopQuestCompleters');
        $this->xpRepo->expects($this->never())->method('findTopXpEarners');
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $counts = $this->service->snapshot($season);

        $this->assertSame([
            RankingTab::Kills->value => 0,
            RankingTab::Quests->value => 0,
            RankingTab::Xp->value => 0,
        ], $counts);
    }

    public function testSnapshotHandlesEmptyRankings(): void
    {
        $season = $this->createSeason();

        $this->snapshotRepo->method('countForSeason')->with($season)->willReturn(0);
        $this->bestiaryRepo->method('findTopKillers')->willReturn([]);
        $this->questRepo->method('findTopQuestCompleters')->willReturn([]);
        $this->xpRepo->method('findTopXpEarners')->willReturn([]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $counts = $this->service->snapshot($season);

        $this->assertSame([
            RankingTab::Kills->value => 0,
            RankingTab::Quests->value => 0,
            RankingTab::Xp->value => 0,
        ], $counts);
    }

    public function testSnapshotRespectsCustomLimit(): void
    {
        $season = $this->createSeason();

        $this->snapshotRepo->method('countForSeason')->with($season)->willReturn(0);

        $this->bestiaryRepo->expects($this->once())->method('findTopKillers')->with(10)->willReturn([]);
        $this->questRepo->expects($this->once())->method('findTopQuestCompleters')->with(10)->willReturn([]);
        $this->xpRepo->expects($this->once())->method('findTopXpEarners')->with(10)->willReturn([]);

        $this->service->snapshot($season, 10);
    }

    public function testEntityRejectsInvalidRank(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PlayerSeasonRankingSnapshot(
            $this->createSeason(),
            RankingTab::Kills,
            0,
            $this->createPlayer(1, 'Alice'),
            100,
        );
    }

    public function testEntityRejectsNegativeTotal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PlayerSeasonRankingSnapshot(
            $this->createSeason(),
            RankingTab::Kills,
            1,
            $this->createPlayer(1, 'Alice'),
            -1,
        );
    }

    private function createSeason(): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setName('Saison 1');
        $season->setSlug('saison-1');
        $season->setSeasonNumber(1);
        $season->setStartsAt(new \DateTime('-30 days'));
        $season->setEndsAt(new \DateTime());

        return $season;
    }

    private function createPlayer(int $id, string $name): Player
    {
        $player = new Player();
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($player, $id);
        $player->setName($name);

        return $player;
    }
}
