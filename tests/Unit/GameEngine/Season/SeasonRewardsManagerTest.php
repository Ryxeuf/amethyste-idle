<?php

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\PlayerSeasonRankingSnapshot;
use App\Entity\App\PlayerSeasonReward;
use App\Enum\RankingTab;
use App\GameEngine\Season\SeasonRewardsManager;
use App\Repository\PlayerSeasonRankingSnapshotRepository;
use App\Repository\PlayerSeasonRewardRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SeasonRewardsManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private PlayerSeasonRankingSnapshotRepository&MockObject $snapshotRepo;
    private PlayerSeasonRewardRepository&MockObject $rewardRepo;
    private SeasonRewardsManager $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->snapshotRepo = $this->createMock(PlayerSeasonRankingSnapshotRepository::class);
        $this->rewardRepo = $this->createMock(PlayerSeasonRewardRepository::class);

        $this->service = new SeasonRewardsManager(
            $this->em,
            $this->snapshotRepo,
            $this->rewardRepo,
        );
    }

    public function testAwardsPodiumForEachTabAndIgnoresBelowTopThree(): void
    {
        $season = $this->createSeason(3);
        $p1 = $this->createPlayer(1, 'Alice');
        $p2 = $this->createPlayer(2, 'Bob');
        $p3 = $this->createPlayer(3, 'Carol');
        $p4 = $this->createPlayer(4, 'Dave');

        $this->rewardRepo->method('countForSeason')->with($season)->willReturn(0);

        $this->snapshotRepo->method('findBySeasonAndTab')
            ->willReturnCallback(function (InfluenceSeason $s, RankingTab $tab) use ($season, $p1, $p2, $p3, $p4) {
                $this->assertSame($season, $s);

                return match ($tab) {
                    RankingTab::Kills => [
                        $this->createSnapshot($season, $tab, 1, $p1, 120),
                        $this->createSnapshot($season, $tab, 2, $p2, 80),
                        $this->createSnapshot($season, $tab, 3, $p3, 50),
                        $this->createSnapshot($season, $tab, 4, $p4, 10),
                    ],
                    RankingTab::Quests => [
                        $this->createSnapshot($season, $tab, 1, $p2, 42),
                    ],
                    RankingTab::Xp => [],
                };
            });

        $persisted = [];
        $this->em->expects($this->exactly(4))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $this->em->expects($this->once())->method('flush');

        $counts = $this->service->awardPodium($season);

        $this->assertSame([
            RankingTab::Kills->value => 3,
            RankingTab::Quests->value => 1,
            RankingTab::Xp->value => 0,
        ], $counts);
        $this->assertCount(4, $persisted);

        $first = $persisted[0];
        $this->assertInstanceOf(PlayerSeasonReward::class, $first);
        $this->assertSame($p1, $first->getPlayer());
        $this->assertSame(RankingTab::Kills, $first->getTab());
        $this->assertSame(1, $first->getRank());
        $this->assertSame('Champion des chasseurs — Saison 3', $first->getTitleLabel());

        $this->assertSame('Vice-champion des chasseurs — Saison 3', $persisted[1]->getTitleLabel());
        $this->assertSame('Troisieme des chasseurs — Saison 3', $persisted[2]->getTitleLabel());
        $this->assertSame('Champion des aventuriers — Saison 3', $persisted[3]->getTitleLabel());
    }

    public function testAwardPodiumIsIdempotentWhenAlreadyAwarded(): void
    {
        $season = $this->createSeason(3);

        $this->rewardRepo->method('countForSeason')->with($season)->willReturn(9);

        $this->snapshotRepo->expects($this->never())->method('findBySeasonAndTab');
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $counts = $this->service->awardPodium($season);

        $this->assertSame([
            RankingTab::Kills->value => 0,
            RankingTab::Quests->value => 0,
            RankingTab::Xp->value => 0,
        ], $counts);
    }

    public function testAwardPodiumFlushesEvenWhenAllTabsEmpty(): void
    {
        $season = $this->createSeason(1);

        $this->rewardRepo->method('countForSeason')->with($season)->willReturn(0);
        $this->snapshotRepo->method('findBySeasonAndTab')->willReturn([]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $counts = $this->service->awardPodium($season);

        $this->assertSame([
            RankingTab::Kills->value => 0,
            RankingTab::Quests->value => 0,
            RankingTab::Xp->value => 0,
        ], $counts);
    }

    public function testEntityRejectsRankOutsidePodium(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PlayerSeasonReward(
            $this->createSeason(1),
            $this->createPlayer(1, 'Alice'),
            RankingTab::Kills,
            4,
            'Titre',
        );
    }

    public function testEntityRejectsEmptyTitleLabel(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PlayerSeasonReward(
            $this->createSeason(1),
            $this->createPlayer(1, 'Alice'),
            RankingTab::Kills,
            1,
            '   ',
        );
    }

    private function createSeason(int $number): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setName(sprintf('Saison %d', $number));
        $season->setSlug(sprintf('saison-%d', $number));
        $season->setSeasonNumber($number);
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

    private function createSnapshot(
        InfluenceSeason $season,
        RankingTab $tab,
        int $rank,
        Player $player,
        int $value,
    ): PlayerSeasonRankingSnapshot {
        return new PlayerSeasonRankingSnapshot($season, $tab, $rank, $player, $value);
    }
}
