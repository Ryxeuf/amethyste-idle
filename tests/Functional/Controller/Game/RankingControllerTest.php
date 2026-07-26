<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\RankingController;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\PlayerSeasonReward;
use App\Enum\RankingTab;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Season\RankingBaselineService;
use App\Helper\PlayerHelper;
use App\Repository\PlayerSeasonRewardRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class RankingControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private RankingBaselineService&MockObject $baselineService;
    private SeasonManager&MockObject $seasonManager;
    private PlayerSeasonRewardRepository&MockObject $seasonRewardRepository;
    private RankingController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->baselineService = $this->createMock(RankingBaselineService::class);
        $this->seasonManager = $this->createMock(SeasonManager::class);
        $this->seasonRewardRepository = $this->createMock(PlayerSeasonRewardRepository::class);
        $this->seasonRewardRepository->method('findByPlayer')->willReturn([]);

        $this->controller = new RankingController(
            $this->playerHelper,
            $this->baselineService,
            $this->seasonManager,
            $this->seasonRewardRepository,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testIndexDefaultTabShowsKillsRanking(): void
    {
        $player = $this->createMock(Player::class);
        $other = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $top = [
            ['player' => $other, 'total' => 200],
            ['player' => $player, 'total' => 150],
        ];

        $this->baselineService->expects($this->once())
            ->method('topOfSeason')->with(RankingTab::Kills, 50)->willReturn($top);
        $this->baselineService->expects($this->once())
            ->method('currentSeasonRankFor')->with($player, RankingTab::Kills)->willReturn(2);
        $this->baselineService->expects($this->once())
            ->method('currentSeasonTotalFor')->with($player, RankingTab::Kills)->willReturn(150);

        $response = $this->controller->index(new Request());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($this->capturedTemplateParams);
        $this->assertSame('kills', $this->capturedTemplateParams['tab']);
        $this->assertSame($player, $this->capturedTemplateParams['player']);
        $this->assertSame($top, $this->capturedTemplateParams['topEntries']);
        $this->assertSame(2, $this->capturedTemplateParams['playerRank']);
        $this->assertSame(150, $this->capturedTemplateParams['playerTotal']);
        $this->assertSame(50, $this->capturedTemplateParams['topLimit']);
    }

    public function testIndexQuestsTabQueriesTheQuestTab(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $top = [['player' => $player, 'total' => 17]];

        $this->baselineService->expects($this->once())
            ->method('topOfSeason')->with(RankingTab::Quests, 50)->willReturn($top);
        $this->baselineService->method('currentSeasonRankFor')->with($player, RankingTab::Quests)->willReturn(2);
        $this->baselineService->method('currentSeasonTotalFor')->with($player, RankingTab::Quests)->willReturn(17);

        $response = $this->controller->index(new Request(['tab' => 'quests']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('quests', $this->capturedTemplateParams['tab']);
        $this->assertSame($top, $this->capturedTemplateParams['topEntries']);
        $this->assertSame(2, $this->capturedTemplateParams['playerRank']);
        $this->assertSame(17, $this->capturedTemplateParams['playerTotal']);
    }

    public function testIndexXpTabQueriesTheXpTab(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->baselineService->expects($this->once())
            ->method('topOfSeason')->with(RankingTab::Xp, 50)->willReturn([]);
        $this->baselineService->method('currentSeasonRankFor')->willReturn(null);
        $this->baselineService->method('currentSeasonTotalFor')->willReturn(0);

        $response = $this->controller->index(new Request(['tab' => 'xp']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('xp', $this->capturedTemplateParams['tab']);
    }

    public function testIndexUnknownTabFallsBackToKills(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->baselineService->expects($this->once())
            ->method('topOfSeason')->with(RankingTab::Kills, 50)->willReturn([]);
        $this->baselineService->method('currentSeasonRankFor')->willReturn(null);
        $this->baselineService->method('currentSeasonTotalFor')->willReturn(0);

        $response = $this->controller->index(new Request(['tab' => 'guilds']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('kills', $this->capturedTemplateParams['tab']);
    }

    public function testIndexRedirectsWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $this->baselineService->expects($this->never())->method('topOfSeason');

        $response = $this->controller->index(new Request());

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testIndexHandlesUnrankedPlayer(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->baselineService->method('topOfSeason')->willReturn([]);
        $this->baselineService->method('currentSeasonRankFor')->willReturn(null);
        $this->baselineService->method('currentSeasonTotalFor')->willReturn(0);

        $response = $this->controller->index(new Request(['tab' => 'xp']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->capturedTemplateParams['playerRank']);
        $this->assertSame(0, $this->capturedTemplateParams['playerTotal']);
        $this->assertSame([], $this->capturedTemplateParams['topEntries']);
    }

    /**
     * La saison est nommee a l'ecran : sans elle, « classement de la saison »
     * reste une affirmation que le joueur ne peut pas verifier.
     */
    public function testIndexPassesTheCurrentSeasonToTheTemplate(): void
    {
        $player = $this->createMock(Player::class);
        $season = $this->createMock(InfluenceSeason::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->seasonManager->method('getCurrentSeason')->willReturn($season);

        $this->baselineService->method('topOfSeason')->willReturn([]);
        $this->baselineService->method('currentSeasonRankFor')->willReturn(null);
        $this->baselineService->method('currentSeasonTotalFor')->willReturn(0);

        $this->controller->index(new Request());

        $this->assertSame($season, $this->capturedTemplateParams['season']);
    }

    public function testIndexToleratesNoActiveSeason(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->seasonManager->method('getCurrentSeason')->willReturn(null);

        $this->baselineService->method('topOfSeason')->willReturn([]);
        $this->baselineService->method('currentSeasonRankFor')->willReturn(null);
        $this->baselineService->method('currentSeasonTotalFor')->willReturn(0);

        $response = $this->controller->index(new Request());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->capturedTemplateParams['season']);
    }

    public function testIndexPassesPlayerTitlesToTemplate(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $season = $this->createMock(InfluenceSeason::class);
        $reward = new PlayerSeasonReward($season, $player, RankingTab::Kills, 1, 'Champion des chasseurs — Saison 1');

        $repo = $this->createMock(PlayerSeasonRewardRepository::class);
        $repo->expects($this->once())->method('findByPlayer')->with($player)->willReturn([$reward]);

        $controller = new RankingController(
            $this->playerHelper,
            $this->baselineService,
            $this->seasonManager,
            $repo,
        );
        $controller->setContainer($this->createContainer());

        $this->baselineService->method('topOfSeason')->willReturn([]);
        $this->baselineService->method('currentSeasonRankFor')->willReturn(null);
        $this->baselineService->method('currentSeasonTotalFor')->willReturn(0);

        $response = $controller->index(new Request());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame([$reward], $this->capturedTemplateParams['playerTitles']);
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
