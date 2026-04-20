<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\RankingController;
use App\Entity\App\Player;
use App\Helper\PlayerHelper;
use App\Repository\DomainExperienceRepository;
use App\Repository\PlayerBestiaryRepository;
use App\Repository\PlayerQuestCompletedRepository;
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
    private PlayerBestiaryRepository&MockObject $bestiaryRepository;
    private PlayerQuestCompletedRepository&MockObject $questCompletedRepository;
    private DomainExperienceRepository&MockObject $domainExperienceRepository;
    private RankingController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->bestiaryRepository = $this->createMock(PlayerBestiaryRepository::class);
        $this->questCompletedRepository = $this->createMock(PlayerQuestCompletedRepository::class);
        $this->domainExperienceRepository = $this->createMock(DomainExperienceRepository::class);

        $this->controller = new RankingController(
            $this->playerHelper,
            $this->bestiaryRepository,
            $this->questCompletedRepository,
            $this->domainExperienceRepository,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testIndexDefaultTabShowsKillsRanking(): void
    {
        $player = $this->createMock(Player::class);
        $other = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $topKillers = [
            ['player' => $other, 'totalKills' => 200],
            ['player' => $player, 'totalKills' => 150],
        ];

        $this->bestiaryRepository->expects($this->once())
            ->method('findTopKillers')->with(50)->willReturn($topKillers);
        $this->bestiaryRepository->expects($this->once())
            ->method('getPlayerKillRank')->with($player)->willReturn(2);
        $this->bestiaryRepository->expects($this->once())
            ->method('getTotalKills')->with($player)->willReturn(150);

        $this->questCompletedRepository->expects($this->never())->method('findTopQuestCompleters');
        $this->domainExperienceRepository->expects($this->never())->method('findTopXpEarners');

        $response = $this->controller->index(new Request());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($this->capturedTemplateParams);
        $this->assertSame('kills', $this->capturedTemplateParams['tab']);
        $this->assertSame($player, $this->capturedTemplateParams['player']);
        $this->assertSame($topKillers, $this->capturedTemplateParams['topEntries']);
        $this->assertSame(2, $this->capturedTemplateParams['playerRank']);
        $this->assertSame(150, $this->capturedTemplateParams['playerTotal']);
        $this->assertSame(50, $this->capturedTemplateParams['topLimit']);
    }

    public function testIndexQuestsTabShowsQuestRanking(): void
    {
        $player = $this->createMock(Player::class);
        $other = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $topQuests = [
            ['player' => $other, 'totalQuests' => 42],
            ['player' => $player, 'totalQuests' => 17],
        ];

        $this->questCompletedRepository->expects($this->once())
            ->method('findTopQuestCompleters')->with(50)->willReturn($topQuests);
        $this->questCompletedRepository->expects($this->once())
            ->method('getPlayerQuestRank')->with($player)->willReturn(2);
        $this->questCompletedRepository->expects($this->once())
            ->method('countQuestsCompleted')->with($player)->willReturn(17);

        $this->bestiaryRepository->expects($this->never())->method('findTopKillers');
        $this->domainExperienceRepository->expects($this->never())->method('findTopXpEarners');

        $response = $this->controller->index(new Request(['tab' => 'quests']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('quests', $this->capturedTemplateParams['tab']);
        $this->assertSame($topQuests, $this->capturedTemplateParams['topEntries']);
        $this->assertSame(2, $this->capturedTemplateParams['playerRank']);
        $this->assertSame(17, $this->capturedTemplateParams['playerTotal']);
    }

    public function testIndexUnknownTabFallsBackToKills(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->bestiaryRepository->expects($this->once())->method('findTopKillers')->willReturn([]);
        $this->bestiaryRepository->method('getPlayerKillRank')->willReturn(null);
        $this->bestiaryRepository->method('getTotalKills')->willReturn(0);
        $this->questCompletedRepository->expects($this->never())->method('findTopQuestCompleters');

        $response = $this->controller->index(new Request(['tab' => 'guilds']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('kills', $this->capturedTemplateParams['tab']);
    }

    public function testIndexRedirectsWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $this->bestiaryRepository->expects($this->never())->method('findTopKillers');
        $this->questCompletedRepository->expects($this->never())->method('findTopQuestCompleters');

        $response = $this->controller->index(new Request());

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testIndexXpTabShowsXpRanking(): void
    {
        $player = $this->createMock(Player::class);
        $other = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $topXp = [
            ['player' => $other, 'totalXp' => 12500],
            ['player' => $player, 'totalXp' => 9800],
        ];

        $this->domainExperienceRepository->expects($this->once())
            ->method('findTopXpEarners')->with(50)->willReturn($topXp);
        $this->domainExperienceRepository->expects($this->once())
            ->method('getPlayerXpRank')->with($player)->willReturn(2);
        $this->domainExperienceRepository->expects($this->once())
            ->method('getTotalXpEarned')->with($player)->willReturn(9800);

        $this->bestiaryRepository->expects($this->never())->method('findTopKillers');
        $this->questCompletedRepository->expects($this->never())->method('findTopQuestCompleters');

        $response = $this->controller->index(new Request(['tab' => 'xp']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('xp', $this->capturedTemplateParams['tab']);
        $this->assertSame($topXp, $this->capturedTemplateParams['topEntries']);
        $this->assertSame(2, $this->capturedTemplateParams['playerRank']);
        $this->assertSame(9800, $this->capturedTemplateParams['playerTotal']);
    }

    public function testIndexHandlesUnrankedPlayerInXpTab(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->domainExperienceRepository->method('findTopXpEarners')->willReturn([]);
        $this->domainExperienceRepository->method('getPlayerXpRank')->willReturn(null);
        $this->domainExperienceRepository->method('getTotalXpEarned')->willReturn(0);

        $response = $this->controller->index(new Request(['tab' => 'xp']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('xp', $this->capturedTemplateParams['tab']);
        $this->assertNull($this->capturedTemplateParams['playerRank']);
        $this->assertSame(0, $this->capturedTemplateParams['playerTotal']);
        $this->assertSame([], $this->capturedTemplateParams['topEntries']);
    }

    public function testIndexHandlesUnrankedPlayerInQuestsTab(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->questCompletedRepository->method('findTopQuestCompleters')->willReturn([]);
        $this->questCompletedRepository->method('getPlayerQuestRank')->willReturn(null);
        $this->questCompletedRepository->method('countQuestsCompleted')->willReturn(0);

        $response = $this->controller->index(new Request(['tab' => 'quests']));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->capturedTemplateParams['playerRank']);
        $this->assertSame(0, $this->capturedTemplateParams['playerTotal']);
        $this->assertSame([], $this->capturedTemplateParams['topEntries']);
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
