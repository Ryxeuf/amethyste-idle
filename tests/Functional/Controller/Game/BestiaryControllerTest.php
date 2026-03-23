<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\BestiaryController;
use App\Entity\App\Player;
use App\Entity\App\PlayerBestiary;
use App\Entity\Game\Monster;
use App\Helper\PlayerHelper;
use App\Repository\PlayerBestiaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class BestiaryControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private PlayerBestiaryRepository&MockObject $bestiaryRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private BestiaryController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->bestiaryRepository = $this->createMock(PlayerBestiaryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->controller = new BestiaryController(
            $this->playerHelper,
            $this->bestiaryRepository,
            $this->entityManager,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testIndexRendersWithCorrectData(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $entry1 = $this->createMock(PlayerBestiary::class);
        $entry2 = $this->createMock(PlayerBestiary::class);
        $entry3 = $this->createMock(PlayerBestiary::class);

        $this->bestiaryRepository->method('findByPlayer')->with($player)->willReturn([$entry1, $entry2, $entry3]);
        $this->bestiaryRepository->method('getTotalKills')->with($player)->willReturn(150);

        $monsterRepo = $this->createMock(EntityRepository::class);
        $monsterRepo->method('count')->willReturn(25);
        $this->entityManager->method('getRepository')->with(Monster::class)->willReturn($monsterRepo);

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($this->capturedTemplateParams);
        $this->assertEquals(3, $this->capturedTemplateParams['discoveredCount']);
        $this->assertEquals(25, $this->capturedTemplateParams['totalMonsters']);
        $this->assertEquals(150, $this->capturedTemplateParams['totalKills']);
        $this->assertCount(3, $this->capturedTemplateParams['entries']);
        $this->assertSame($player, $this->capturedTemplateParams['player']);
    }

    public function testIndexNoPlayerRedirects(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $response = $this->controller->index();

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testIndexWithNoDiscoveredMonstersShowsZero(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->bestiaryRepository->method('findByPlayer')->willReturn([]);
        $this->bestiaryRepository->method('getTotalKills')->willReturn(0);

        $monsterRepo = $this->createMock(EntityRepository::class);
        $monsterRepo->method('count')->willReturn(25);
        $this->entityManager->method('getRepository')->willReturn($monsterRepo);

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, $this->capturedTemplateParams['discoveredCount']);
        $this->assertEquals(0, $this->capturedTemplateParams['totalKills']);
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
