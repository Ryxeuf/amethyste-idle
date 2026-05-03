<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\RankingHistoryController;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\PlayerSeasonRankingSnapshot;
use App\Enum\RankingTab;
use App\Helper\PlayerHelper;
use App\Repository\PlayerSeasonRankingSnapshotRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class RankingHistoryControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private PlayerSeasonRankingSnapshotRepository&MockObject $snapshotRepository;
    private RankingHistoryController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->snapshotRepository = $this->createMock(PlayerSeasonRankingSnapshotRepository::class);

        $this->controller = new RankingHistoryController(
            $this->playerHelper,
            $this->snapshotRepository,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testIndexBuildsPodiumsForArchivedSeasons(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $season1 = $this->createMock(InfluenceSeason::class);
        $season2 = $this->createMock(InfluenceSeason::class);

        $this->snapshotRepository->expects($this->once())
            ->method('findArchivedSeasons')
            ->with(10)
            ->willReturn([$season1, $season2]);

        $podium1Kills = [$this->createMock(PlayerSeasonRankingSnapshot::class)];
        $this->snapshotRepository->expects($this->exactly(6))
            ->method('findPodiumBySeasonAndTab')
            ->willReturnCallback(function (InfluenceSeason $season, RankingTab $tab, int $limit) use ($season1, $podium1Kills) {
                $this->assertSame(3, $limit);
                if ($season === $season1 && $tab === RankingTab::Kills) {
                    return $podium1Kills;
                }

                return [];
            });

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($this->capturedTemplateParams);
        $this->assertSame($player, $this->capturedTemplateParams['player']);
        $this->assertCount(2, $this->capturedTemplateParams['podiums']);
        $this->assertSame($season1, $this->capturedTemplateParams['podiums'][0]['season']);
        $this->assertSame($podium1Kills, $this->capturedTemplateParams['podiums'][0]['tabs']['kills']);
        $this->assertSame([], $this->capturedTemplateParams['podiums'][0]['tabs']['quests']);
        $this->assertSame([], $this->capturedTemplateParams['podiums'][0]['tabs']['xp']);
        $this->assertSame($season2, $this->capturedTemplateParams['podiums'][1]['season']);
    }

    public function testIndexPassesEmptyPodiumsWhenNoArchivedSeason(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->snapshotRepository->expects($this->once())
            ->method('findArchivedSeasons')
            ->willReturn([]);
        $this->snapshotRepository->expects($this->never())
            ->method('findPodiumBySeasonAndTab');

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame([], $this->capturedTemplateParams['podiums']);
    }

    public function testIndexRedirectsWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $this->snapshotRepository->expects($this->never())->method('findArchivedSeasons');

        $response = $this->controller->index();

        $this->assertEquals(302, $response->getStatusCode());
    }

    private function createContainer(): ContainerInterface&MockObject
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $twig = $this->createMock(TwigEnvironment::class);
        $twig->method('render')->willReturnCallback(function (string $view, array $params): string {
            $this->capturedTemplateParams = $params;

            return '<html></html>';
        });

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/game');

        $services = [
            'security.authorization_checker' => $authChecker,
            'twig' => $twig,
            'router' => $router,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
