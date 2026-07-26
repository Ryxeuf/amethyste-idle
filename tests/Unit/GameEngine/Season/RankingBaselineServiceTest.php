<?php

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\PlayerRankingBaseline;
use App\Enum\RankingTab;
use App\GameEngine\Season\RankingBaselineService;
use App\Repository\DomainExperienceRepository;
use App\Repository\PlayerBestiaryRepository;
use App\Repository\PlayerQuestCompletedRepository;
use App\Repository\PlayerRankingBaselineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RankingBaselineServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerRankingBaselineRepository&MockObject $baselineRepository;
    private PlayerBestiaryRepository&MockObject $bestiaryRepository;
    private PlayerQuestCompletedRepository&MockObject $questRepository;
    private DomainExperienceRepository&MockObject $xpRepository;
    private RankingBaselineService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->baselineRepository = $this->createMock(PlayerRankingBaselineRepository::class);
        $this->bestiaryRepository = $this->createMock(PlayerBestiaryRepository::class);
        $this->questRepository = $this->createMock(PlayerQuestCompletedRepository::class);
        $this->xpRepository = $this->createMock(DomainExperienceRepository::class);

        $this->service = new RankingBaselineService(
            $this->entityManager,
            $this->baselineRepository,
            $this->bestiaryRepository,
            $this->questRepository,
            $this->xpRepository,
        );
    }

    private function player(int $id): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);

        return $player;
    }

    private function season(int $number): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setSeasonNumber($number);

        return $season;
    }

    /**
     * Le defaut que la tache 132 corrige : sans reference, le classement dit
     * « saisonnier » est le palmares de toute l'histoire du serveur.
     */
    public function testSeasonTotalIsTheProgressSinceTheBaseline(): void
    {
        $this->bestiaryRepository->method('sumKillsByPlayerId')->willReturn([7 => 1200, 9 => 340]);
        $this->baselineRepository->method('mapByPlayerId')->willReturn([7 => 1150]);

        // Le joueur 7 est un veteran : 1200 kills au total, mais 50 cette saison.
        // Le joueur 9 est nouveau : aucune reference, donc tout compte.
        $this->assertSame([7 => 50, 9 => 340], $this->service->currentSeasonTotals(RankingTab::Kills));
    }

    public function testInactivePlayersAreAbsentFromTheSeasonRanking(): void
    {
        $this->bestiaryRepository->method('sumKillsByPlayerId')->willReturn([7 => 1200]);
        $this->baselineRepository->method('mapByPlayerId')->willReturn([7 => 1200]);

        $this->assertSame([], $this->service->currentSeasonTotals(RankingTab::Kills));
    }

    /**
     * Les compteurs sources ne decroissent jamais. Un delta negatif signale une
     * anomalie, et le laisser passer ferait remonter un joueur inactif.
     */
    public function testNegativeDeltaIsDiscarded(): void
    {
        $this->questRepository->method('countQuestsByPlayerId')->willReturn([7 => 3]);
        $this->baselineRepository->method('mapByPlayerId')->willReturn([7 => 10]);

        $this->assertSame([], $this->service->currentSeasonTotals(RankingTab::Quests));
    }

    public function testEachTabReadsItsOwnCounter(): void
    {
        $this->baselineRepository->method('mapByPlayerId')->willReturn([]);
        $this->bestiaryRepository->method('sumKillsByPlayerId')->willReturn([1 => 10]);
        $this->questRepository->method('countQuestsByPlayerId')->willReturn([1 => 20]);
        $this->xpRepository->method('sumXpByPlayerId')->willReturn([1 => 30]);

        $this->assertSame([1 => 10], $this->service->currentSeasonTotals(RankingTab::Kills));
        $this->assertSame([1 => 20], $this->service->currentSeasonTotals(RankingTab::Quests));
        $this->assertSame([1 => 30], $this->service->currentSeasonTotals(RankingTab::Xp));
    }

    /**
     * Le cœur de la tache 132 : le tri se fait apres soustraction.
     *
     * Le veteran mene largement au cumul et se fait pourtant devancer par le
     * nouveau venu sur la saison. Trier sur le cumul puis tronquer aurait
     * reproduit le palmares historique.
     */
    public function testTopOfSeasonRanksOnTheSeasonNotOnTheLifetimeTotal(): void
    {
        $veteran = $this->player(7);
        $newcomer = $this->player(9);

        $this->bestiaryRepository->method('sumKillsByPlayerId')->willReturn([7 => 1200, 9 => 340]);
        $this->baselineRepository->method('mapByPlayerId')->willReturn([7 => 1150]);

        $playerRepository = $this->createMock(EntityRepository::class);
        $playerRepository->method('findBy')->willReturn([$veteran, $newcomer]);
        $this->entityManager->method('getRepository')->willReturn($playerRepository);

        $top = $this->service->topOfSeason(RankingTab::Kills, 50);

        $this->assertSame([
            ['player' => $newcomer, 'total' => 340],
            ['player' => $veteran, 'total' => 50],
        ], $top);
    }

    public function testTopOfSeasonHonoursTheLimit(): void
    {
        $this->bestiaryRepository->method('sumKillsByPlayerId')->willReturn([7 => 30, 9 => 20, 11 => 10]);
        $this->baselineRepository->method('mapByPlayerId')->willReturn([]);

        $playerRepository = $this->createMock(EntityRepository::class);
        $playerRepository->method('findBy')->willReturn([$this->player(7), $this->player(9)]);
        $this->entityManager->method('getRepository')->willReturn($playerRepository);

        $this->assertCount(2, $this->service->topOfSeason(RankingTab::Kills, 2));
    }

    public function testSeasonRankIsNullForAPlayerWhoDidNothingThisSeason(): void
    {
        $player = new Player();

        $this->bestiaryRepository->method('getTotalKills')->willReturn(1200);
        $this->baselineRepository->method('valueForPlayer')->willReturn(1200);

        $this->assertNull($this->service->currentSeasonRankFor($player, RankingTab::Kills));
    }

    public function testSeasonRankCountsOnlyPlayersAheadThisSeason(): void
    {
        $player = $this->player(7);

        $this->bestiaryRepository->method('getTotalKills')->willReturn(1200);
        $this->baselineRepository->method('valueForPlayer')->willReturn(1150);
        // 50 cette saison pour le joueur 7 ; un seul joueur fait mieux.
        $this->bestiaryRepository->method('sumKillsByPlayerId')->willReturn([7 => 1200, 9 => 340, 11 => 20]);
        $this->baselineRepository->method('mapByPlayerId')->willReturn([7 => 1150]);

        $this->assertSame(2, $this->service->currentSeasonRankFor($player, RankingTab::Kills));
    }

    public function testCaptureRewritesAnExistingBaseline(): void
    {
        $player = new Player();
        $existing = new PlayerRankingBaseline($player, RankingTab::Kills, 1150, 1);

        $this->bestiaryRepository->method('sumKillsByPlayerId')->willReturn([7 => 1200]);
        $this->questRepository->method('countQuestsByPlayerId')->willReturn([]);
        $this->xpRepository->method('sumXpByPlayerId')->willReturn([]);
        $this->baselineRepository->method('findIndexedByPlayerId')
            ->willReturnCallback(static fn (RankingTab $tab) => RankingTab::Kills === $tab ? [7 => $existing] : []);
        $this->entityManager->method('getRepository')->willReturn($this->createMock(EntityRepository::class));

        // Rien de neuf a persister : la reference existante est reecrite.
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $counts = $this->service->capture($this->season(2));

        $this->assertSame(['kills' => 1, 'quests' => 0, 'xp' => 0], $counts);
        $this->assertSame(1200, $existing->getValue());
        $this->assertSame(2, $existing->getSeasonNumber());
    }

    public function testCaptureCreatesABaselineForANewcomer(): void
    {
        $player = new Player();
        $playerRepository = $this->createMock(EntityRepository::class);
        $playerRepository->method('find')->with(9)->willReturn($player);

        $this->bestiaryRepository->method('sumKillsByPlayerId')->willReturn([9 => 340]);
        $this->questRepository->method('countQuestsByPlayerId')->willReturn([]);
        $this->xpRepository->method('sumXpByPlayerId')->willReturn([]);
        $this->baselineRepository->method('findIndexedByPlayerId')->willReturn([]);
        $this->entityManager->method('getRepository')->willReturn($playerRepository);

        $persisted = null;
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted = $entity;
            });

        $this->service->capture($this->season(2));

        $this->assertInstanceOf(PlayerRankingBaseline::class, $persisted);
        $this->assertSame(340, $persisted->getValue());
        $this->assertSame(RankingTab::Kills, $persisted->getTab());
        $this->assertSame(2, $persisted->getSeasonNumber());
    }

    public function testAVanishedPlayerIsSkippedRatherThanCrashing(): void
    {
        $playerRepository = $this->createMock(EntityRepository::class);
        $playerRepository->method('find')->willReturn(null);

        $this->bestiaryRepository->method('sumKillsByPlayerId')->willReturn([404 => 12]);
        $this->questRepository->method('countQuestsByPlayerId')->willReturn([]);
        $this->xpRepository->method('sumXpByPlayerId')->willReturn([]);
        $this->baselineRepository->method('findIndexedByPlayerId')->willReturn([]);
        $this->entityManager->method('getRepository')->willReturn($playerRepository);

        $this->entityManager->expects($this->never())->method('persist');

        $this->assertSame(['kills' => 0, 'quests' => 0, 'xp' => 0], $this->service->capture($this->season(2)));
    }

    public function testPlayerSeasonTotalSubtractsTheirOwnBaseline(): void
    {
        $player = new Player();

        $this->bestiaryRepository->method('getTotalKills')->with($player)->willReturn(1200);
        $this->baselineRepository->method('valueForPlayer')->with($player, RankingTab::Kills)->willReturn(1150);

        $this->assertSame(50, $this->service->currentSeasonTotalFor($player, RankingTab::Kills));
    }
}
