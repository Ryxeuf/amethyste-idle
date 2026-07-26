<?php

namespace App\Tests\Unit\Command;

use App\Command\SeasonTickCommand;
use App\Entity\App\InfluenceSeason;
use App\Enum\SeasonStatus;
use App\GameEngine\Guild\PrestigeTitleManager;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Season\RankingBaselineService;
use App\GameEngine\Season\SeasonRankingSnapshotService;
use App\GameEngine\Season\SeasonResolutionService;
use App\GameEngine\Season\SeasonRewardsManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SeasonTickCommandTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private SeasonManager&MockObject $seasonManager;
    private TownControlManager&MockObject $townControlManager;
    private PrestigeTitleManager&MockObject $prestigeTitleManager;
    private SeasonRankingSnapshotService&MockObject $rankingSnapshotService;
    private SeasonRewardsManager&MockObject $rewardsManager;
    private SeasonResolutionService&MockObject $resolutionService;
    private RankingBaselineService&MockObject $baselineService;
    private EntityRepository&MockObject $seasonRepo;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->seasonManager = $this->createMock(SeasonManager::class);
        $this->townControlManager = $this->createMock(TownControlManager::class);
        $this->prestigeTitleManager = $this->createMock(PrestigeTitleManager::class);
        $this->rankingSnapshotService = $this->createMock(SeasonRankingSnapshotService::class);
        $this->rewardsManager = $this->createMock(SeasonRewardsManager::class);
        $this->resolutionService = $this->createMock(SeasonResolutionService::class);
        $this->baselineService = $this->createMock(RankingBaselineService::class);
        $this->baselineService->method('capture')->willReturn(['kills' => 0, 'quests' => 0, 'xp' => 0]);
        $this->seasonRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->with(InfluenceSeason::class)
            ->willReturn($this->seasonRepo);

        $command = new SeasonTickCommand(
            $this->em,
            $this->seasonManager,
            $this->townControlManager,
            $this->prestigeTitleManager,
            $this->rankingSnapshotService,
            $this->rewardsManager,
            $this->resolutionService,
            $this->baselineService,
        );

        $app = new Application();
        $app->add($command);
        $this->tester = new CommandTester($app->find('app:season:tick'));
    }

    public function testEndExpiredSeasonAttributesControlAndEnds(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Active, '-1 day', '-1 hour');

        $callCount = 0;
        $this->seasonManager->method('getCurrentSeason')
            ->willReturnCallback(function () use ($season, &$callCount) {
                ++$callCount;

                // First call: season exists (for handleExpiredSeasons)
                // Second call: null (for handleScheduledSeasons, season was just ended)
                // Third call: null (for handleRush)
                return $callCount <= 1 ? $season : null;
            });

        $this->townControlManager->expects($this->once())
            ->method('attributeControl')
            ->with($season)
            ->willReturn(['foret-sombre' => 'Les Valeureux']);

        $this->seasonManager->expects($this->once())
            ->method('endSeason')
            ->with($season);

        $this->rankingSnapshotService->expects($this->once())
            ->method('snapshot')
            ->with($season)
            ->willReturn(['kills' => 3, 'quests' => 2, 'xp' => 1]);

        $this->rewardsManager->expects($this->once())
            ->method('awardPodium')
            ->with($season)
            ->willReturn(['kills' => 3, 'quests' => 2, 'xp' => 0]);

        // Ensure next season is created
        $nextSeason = $this->createSeason(2, SeasonStatus::Scheduled, '+1 day', '+29 days');
        $this->seasonRepo->method('findOneBy')
            ->willReturn(null);
        $this->seasonManager->method('getOrCreateNextSeason')
            ->willReturn($nextSeason);

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('terminée', $this->tester->getDisplay());
        $this->assertStringContainsString('Les Valeureux', $this->tester->getDisplay());
        $this->assertStringContainsString('Classement archivé', $this->tester->getDisplay());
        $this->assertStringContainsString('Titres du podium attribués', $this->tester->getDisplay());
        $this->assertStringContainsString('Références de classement figées', $this->tester->getDisplay());
    }

    /**
     * L'ordre de la cloture est porteur de sens (tache 132).
     *
     * Figer les references avant l'archivage remettrait tout le monde a zero
     * juste avant de mesurer : la saison qui s'acheve doit etre jugee sur la
     * reference de la precedente.
     */
    public function testBaselineIsCapturedAfterSnapshotAndRewards(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Active, '-1 day', '-1 hour');

        $callCount = 0;
        $this->seasonManager->method('getCurrentSeason')
            ->willReturnCallback(function () use ($season, &$callCount) {
                ++$callCount;

                return $callCount <= 1 ? $season : null;
            });

        $this->townControlManager->method('attributeControl')->willReturn([]);
        $this->seasonRepo->method('findOneBy')->willReturn(null);
        $this->seasonManager->method('getOrCreateNextSeason')
            ->willReturn($this->createSeason(2, SeasonStatus::Scheduled, '+1 day', '+29 days'));

        $order = [];
        $this->rankingSnapshotService->method('snapshot')
            ->willReturnCallback(function () use (&$order) {
                $order[] = 'snapshot';

                return ['kills' => 0, 'quests' => 0, 'xp' => 0];
            });
        $this->rewardsManager->method('awardPodium')
            ->willReturnCallback(function () use (&$order) {
                $order[] = 'rewards';

                return ['kills' => 0, 'quests' => 0, 'xp' => 0];
            });

        // Le mock de reference est deja arme dans setUp : on le remplace pour
        // enregistrer son rang dans la sequence.
        $baselineService = $this->createMock(RankingBaselineService::class);
        $baselineService->method('capture')
            ->willReturnCallback(function () use (&$order) {
                $order[] = 'baseline';

                return ['kills' => 0, 'quests' => 0, 'xp' => 0];
            });

        $command = new SeasonTickCommand(
            $this->em,
            $this->seasonManager,
            $this->townControlManager,
            $this->prestigeTitleManager,
            $this->rankingSnapshotService,
            $this->rewardsManager,
            $this->resolutionService,
            $baselineService,
        );
        $app = new Application();
        $app->add($command);
        (new CommandTester($app->find('app:season:tick')))->execute([]);

        $this->assertSame(['snapshot', 'rewards', 'baseline'], $order);
    }

    public function testDoesNotEndActiveSeasonBeforeEndsAt(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Active, '-7 days', '+21 days');

        $this->seasonManager->method('getCurrentSeason')
            ->willReturn($season);

        $this->townControlManager->expects($this->never())
            ->method('attributeControl');

        $this->seasonManager->expects($this->never())
            ->method('endSeason');

        // Has scheduled season
        $this->seasonRepo->method('findOneBy')
            ->willReturn($this->createSeason(2, SeasonStatus::Scheduled));

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
    }

    public function testStartsScheduledSeasonWhenStartDateReached(): void
    {
        // No active season
        $this->seasonManager->method('getCurrentSeason')
            ->willReturn(null);

        $scheduled = $this->createSeason(1, SeasonStatus::Scheduled, '-1 hour', '+27 days');

        $this->seasonRepo->method('findBy')
            ->willReturn([$scheduled]);
        $this->seasonRepo->method('findOneBy')
            ->willReturn($scheduled);

        $this->seasonManager->expects($this->once())
            ->method('startSeason')
            ->with($scheduled);

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('démarrée', $this->tester->getDisplay());
    }

    public function testDoesNotStartScheduledSeasonBeforeStartDate(): void
    {
        $this->seasonManager->method('getCurrentSeason')
            ->willReturn(null);

        $scheduled = $this->createSeason(1, SeasonStatus::Scheduled, '+2 days', '+30 days');

        $this->seasonRepo->method('findBy')
            ->willReturn([$scheduled]);
        $this->seasonRepo->method('findOneBy')
            ->willReturn($scheduled);

        $this->seasonManager->expects($this->never())
            ->method('startSeason');

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
    }

    public function testActivatesRushInLast72Hours(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Active, '-25 days', '+2 days');
        $season->setParameters(['multipliers' => ['craft' => 1.2]]);

        $this->seasonManager->method('getCurrentSeason')
            ->willReturn($season);

        // Has scheduled season
        $this->seasonRepo->method('findOneBy')
            ->willReturn($this->createSeason(2, SeasonStatus::Scheduled));

        $this->em->expects($this->once())->method('flush');

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $params = $season->getParameters();
        $this->assertTrue($params['rush_active']);
        $this->assertSame(1.5, $params['rush_multiplier']);
        $this->assertStringContainsString('Ruée', $this->tester->getDisplay());
    }

    public function testDoesNotReactivateRush(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Active, '-25 days', '+2 days');
        $season->setParameters(['rush_active' => true, 'rush_multiplier' => 1.5]);

        $this->seasonManager->method('getCurrentSeason')
            ->willReturn($season);

        $this->seasonRepo->method('findOneBy')
            ->willReturn($this->createSeason(2, SeasonStatus::Scheduled));

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringNotContainsString('Ruée', $this->tester->getDisplay());
    }

    public function testDoesNotActivateRushWhenNotInLast72Hours(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Active, '-7 days', '+21 days');

        $this->seasonManager->method('getCurrentSeason')
            ->willReturn($season);

        $this->seasonRepo->method('findOneBy')
            ->willReturn($this->createSeason(2, SeasonStatus::Scheduled));

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertNull($season->getParameters());
    }

    public function testCreatesNextSeasonWhenNoneScheduled(): void
    {
        $this->seasonManager->method('getCurrentSeason')
            ->willReturn(null);

        // No scheduled seasons at all
        $this->seasonRepo->method('findBy')
            ->willReturn([]);
        $this->seasonRepo->method('findOneBy')
            ->willReturn(null);

        $nextSeason = $this->createSeason(1, SeasonStatus::Scheduled, '+1 day', '+29 days');
        $this->seasonManager->expects($this->once())
            ->method('getOrCreateNextSeason')
            ->willReturn($nextSeason);

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('créée', $this->tester->getDisplay());
    }

    public function testRushMultiplierAppliedInGetMultiplier(): void
    {
        $season = new InfluenceSeason();
        $season->setName('Test');
        $season->setSlug('test');
        $season->setSeasonNumber(1);
        $season->setStartsAt(new \DateTime('-25 days'));
        $season->setEndsAt(new \DateTime('+2 days'));
        $season->setStatus(SeasonStatus::Active);
        $season->setParameters([
            'multipliers' => ['craft' => 1.2],
            'rush_multiplier' => 1.5,
        ]);
        $season->setCreatedAt(new \DateTime());
        $season->setUpdatedAt(new \DateTime());

        // craft: 1.2 * 1.5 = 1.8
        $this->assertEqualsWithDelta(1.8, $season->getMultiplier('craft'), 0.001);
        // mob_kill: 1.0 * 1.5 = 1.5
        $this->assertEqualsWithDelta(1.5, $season->getMultiplier('mob_kill'), 0.001);
    }

    private function createSeason(
        int $number,
        SeasonStatus $status,
        string $startsAtModifier = '-7 days',
        string $endsAtModifier = '+21 days',
    ): InfluenceSeason {
        $season = new InfluenceSeason();
        $season->setName(sprintf('Saison %d', $number));
        $season->setSlug(sprintf('saison-%d', $number));
        $season->setSeasonNumber($number);
        $season->setStartsAt(new \DateTime($startsAtModifier));
        $season->setEndsAt(new \DateTime($endsAtModifier));
        $season->setStatus($status);
        $season->setCreatedAt(new \DateTime());
        $season->setUpdatedAt(new \DateTime());

        return $season;
    }
}
