<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\ZoneController;
use App\Entity\App\Map;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\GameEngine\Zone\PlayerZoneSynchronizer;
use App\Helper\PlayerHelper;
use App\Repository\ZoneConnectionRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class ZoneControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $playerRepository;
    private EntityRepository&MockObject $objectLayerRepository;
    private PlayerHelper&MockObject $playerHelper;
    private ZoneRepository&MockObject $zoneRepository;
    private ZoneConnectionRepository&MockObject $zoneConnectionRepository;
    private PlayerZoneSynchronizer&MockObject $playerZoneSynchronizer;
    private ZoneController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->playerRepository = $this->createMock(EntityRepository::class);
        $this->objectLayerRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Player::class, $this->playerRepository],
            [ObjectLayer::class, $this->objectLayerRepository],
        ]);

        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->zoneRepository = $this->createMock(ZoneRepository::class);
        $this->zoneConnectionRepository = $this->createMock(ZoneConnectionRepository::class);
        $this->playerZoneSynchronizer = $this->createMock(PlayerZoneSynchronizer::class);

        $this->controller = new ZoneController(
            $this->entityManager,
            $this->playerHelper,
            $this->zoneRepository,
            $this->zoneConnectionRepository,
            $this->playerZoneSynchronizer,
        );
        $this->controller->setContainer($this->createContainer());
    }

    private function buildZone(string $slug, string $type = Zone::TYPE_WILDERNESS, bool $safe = false): Zone
    {
        return (new Zone())->setSlug($slug)->setName(ucfirst($slug))->setType($type)->setIsSafe($safe);
    }

    private function buildObjectLayer(string $type): ObjectLayer
    {
        $objectLayer = new ObjectLayer();
        $objectLayer->setType($type);

        return $objectLayer;
    }

    public function testIndexRedirectsWhenNoActivePlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $response = $this->controller->index();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertNull($this->capturedTemplateParams);
    }

    public function testIndexRendersZoneWithConnectionsPlayersAndPoi(): void
    {
        $zone = $this->buildZone('foret-des-murmures');
        $target = $this->buildZone('village-de-lumiere', Zone::TYPE_CITY, true);
        $connection = new ZoneConnection($zone, $target, 300);

        $player = new Player();
        $player->setCurrentZone($zone);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->zoneConnectionRepository->expects($this->once())
            ->method('findEnabledFrom')->with($zone)->willReturn([$connection]);
        $this->playerRepository->method('findBy')->willReturn([$player]);
        $this->objectLayerRepository->method('findBy')->with(['zone' => $zone])->willReturn([
            $this->buildObjectLayer(ObjectLayer::TYPE_HARVEST_SPOT),
            $this->buildObjectLayer(ObjectLayer::TYPE_HARVEST_SPOT),
            $this->buildObjectLayer(ObjectLayer::TYPE_FORGE),
            $this->buildObjectLayer(ObjectLayer::TYPE_PORTAL),
        ]);

        $response = $this->controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($zone, $this->capturedTemplateParams['zone']);
        $this->assertSame([$connection], $this->capturedTemplateParams['connections']);
        $this->assertSame([$player], $this->capturedTemplateParams['playersPresent']);
        $this->assertSame(
            [ObjectLayer::TYPE_HARVEST_SPOT => 2, ObjectLayer::TYPE_FORGE => 1],
            $this->capturedTemplateParams['poiCounts'],
        );

        $actionKeys = array_column($this->capturedTemplateParams['actions'], 'key');
        $this->assertSame(['explore', 'hunt', 'gather'], $actionKeys);
    }

    public function testSafeZoneHidesHuntAction(): void
    {
        $zone = $this->buildZone('village-de-lumiere', Zone::TYPE_CITY, true);
        $player = new Player();
        $player->setCurrentZone($zone);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->zoneConnectionRepository->method('findEnabledFrom')->willReturn([]);
        $this->playerRepository->method('findBy')->willReturn([$player]);
        $this->objectLayerRepository->method('findBy')->willReturn([]);

        $this->controller->index();

        $actionKeys = array_column($this->capturedTemplateParams['actions'], 'key');
        $this->assertSame(['explore'], $actionKeys);
    }

    public function testFallsBackToHubWhenPlayerHasNoZone(): void
    {
        $hub = $this->buildZone(ZoneController::HUB_SLUG, Zone::TYPE_CITY, true);
        $player = new Player();
        $player->setMap(new Map());
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->playerZoneSynchronizer->expects($this->once())
            ->method('syncFromMap')->with($player, true)->willReturn(null);
        $this->zoneRepository->expects($this->once())
            ->method('findEnabledBySlug')->with(ZoneController::HUB_SLUG)->willReturn($hub);
        $this->entityManager->expects($this->once())->method('flush');

        $this->zoneConnectionRepository->method('findEnabledFrom')->willReturn([]);
        $this->playerRepository->method('findBy')->willReturn([$player]);
        $this->objectLayerRepository->method('findBy')->willReturn([]);

        $this->controller->index();

        $this->assertSame($hub, $player->getCurrentZone());
        $this->assertSame($hub, $this->capturedTemplateParams['zone']);
    }

    public function testRendersEmptyStateWhenNoZoneResolvable(): void
    {
        $player = new Player();
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->playerZoneSynchronizer->method('syncFromMap')->willReturn(null);
        $this->zoneRepository->method('findEnabledBySlug')->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $response = $this->controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($this->capturedTemplateParams['zone']);
        $this->assertSame([], $this->capturedTemplateParams['actions']);
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
