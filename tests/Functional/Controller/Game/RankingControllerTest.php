<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\RankingController;
use App\Entity\App\Player;
use App\Helper\PlayerHelper;
use App\Repository\PlayerBestiaryRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class RankingControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private PlayerBestiaryRepository&MockObject $bestiaryRepository;
    private RankingController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->bestiaryRepository = $this->createMock(PlayerBestiaryRepository::class);

        $this->controller = new RankingController(
            $this->playerHelper,
            $this->bestiaryRepository,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testIndexRendersTopKillersAndPlayerRank(): void
    {
        $player = $this->createMock(Player::class);
        $other = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $topKillers = [
            ['player' => $other, 'totalKills' => 200],
            ['player' => $player, 'totalKills' => 150],
        ];

        $this->bestiaryRepository->expects($this->once())
            ->method('findTopKillers')
            ->with(50)
            ->willReturn($topKillers);
        $this->bestiaryRepository->expects($this->once())
            ->method('getPlayerKillRank')
            ->with($player)
            ->willReturn(2);
        $this->bestiaryRepository->expects($this->once())
            ->method('getTotalKills')
            ->with($player)
            ->willReturn(150);

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($this->capturedTemplateParams);
        $this->assertSame($player, $this->capturedTemplateParams['player']);
        $this->assertSame($topKillers, $this->capturedTemplateParams['topKillers']);
        $this->assertSame(2, $this->capturedTemplateParams['playerRank']);
        $this->assertSame(150, $this->capturedTemplateParams['playerTotalKills']);
        $this->assertSame(50, $this->capturedTemplateParams['topLimit']);
    }

    public function testIndexRedirectsWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $this->bestiaryRepository->expects($this->never())->method('findTopKillers');
        $this->bestiaryRepository->expects($this->never())->method('getPlayerKillRank');

        $response = $this->controller->index();

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testIndexHandlesUnrankedPlayer(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->bestiaryRepository->method('findTopKillers')->willReturn([]);
        $this->bestiaryRepository->method('getPlayerKillRank')->willReturn(null);
        $this->bestiaryRepository->method('getTotalKills')->willReturn(0);

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->capturedTemplateParams['playerRank']);
        $this->assertSame(0, $this->capturedTemplateParams['playerTotalKills']);
        $this->assertSame([], $this->capturedTemplateParams['topKillers']);
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
