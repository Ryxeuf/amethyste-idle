<?php

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\PlayerSeasonRankingSnapshot;
use App\Enum\RankingTab;
use App\GameEngine\Season\RankingBaselineService;
use App\GameEngine\Season\SeasonRankingSnapshotService;
use App\Repository\PlayerSeasonRankingSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SeasonRankingSnapshotServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private RankingBaselineService&MockObject $baselineService;
    private PlayerSeasonRankingSnapshotRepository&MockObject $snapshotRepo;
    private SeasonRankingSnapshotService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->baselineService = $this->createMock(RankingBaselineService::class);
        $this->snapshotRepo = $this->createMock(PlayerSeasonRankingSnapshotRepository::class);

        $this->service = new SeasonRankingSnapshotService(
            $this->em,
            $this->baselineService,
            $this->snapshotRepo,
        );
    }

    public function testSnapshotPersistsTopRowsForEachTab(): void
    {
        $season = $this->createSeason();
        $p1 = $this->createPlayer(1, 'Alice');
        $p2 = $this->createPlayer(2, 'Bob');

        $this->snapshotRepo->method('countForSeason')->with($season)->willReturn(0);

        // Les valeurs archivees sont celles de la saison, pas le cumul (tache 132).
        $this->baselineService->expects($this->exactly(3))
            ->method('topOfSeason')
            ->willReturnMap([
                [RankingTab::Kills, 50, [
                    ['player' => $p1, 'total' => 120],
                    ['player' => $p2, 'total' => 80],
                ]],
                [RankingTab::Quests, 50, [
                    ['player' => $p1, 'total' => 42],
                ]],
                [RankingTab::Xp, 50, [
                    ['player' => $p2, 'total' => 9999],
                ]],
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

        $this->baselineService->expects($this->never())->method('topOfSeason');
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
        $this->baselineService->method('topOfSeason')->willReturn([]);

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

        $this->baselineService->expects($this->exactly(3))
            ->method('topOfSeason')
            ->willReturnMap([
                [RankingTab::Kills, 10, []],
                [RankingTab::Quests, 10, []],
                [RankingTab::Xp, 10, []],
            ]);

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
