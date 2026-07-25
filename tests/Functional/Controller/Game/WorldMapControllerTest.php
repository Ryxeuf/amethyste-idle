<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\WorldMapController;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\GameEngine\Zone\ExpeditionService;
use App\GameEngine\Zone\ZoneEventService;
use App\GameEngine\Zone\ZoneTravelService;
use App\Helper\PlayerHelper;
use App\Repository\PlayerVisitedZoneRepository;
use App\Repository\ZoneConnectionRepository;
use App\Repository\ZoneRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class WorldMapControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private ZoneRepository&MockObject $zoneRepository;
    private ZoneConnectionRepository&MockObject $zoneConnectionRepository;
    private PlayerVisitedZoneRepository&MockObject $visitedZoneRepository;
    private ZoneTravelService&MockObject $zoneTravelService;
    private ZoneEventService&MockObject $zoneEventService;
    private ExpeditionService&MockObject $expeditionService;
    private WorldMapController $controller;

    /** @var array<string, mixed>|null */
    private ?array $captured = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->zoneRepository = $this->createMock(ZoneRepository::class);
        $this->zoneConnectionRepository = $this->createMock(ZoneConnectionRepository::class);
        $this->visitedZoneRepository = $this->createMock(PlayerVisitedZoneRepository::class);
        $this->zoneTravelService = $this->createMock(ZoneTravelService::class);
        $this->zoneEventService = $this->createMock(ZoneEventService::class);
        $this->zoneEventService->method('getActiveEventsForZone')->willReturn([]);
        $this->expeditionService = $this->createMock(ExpeditionService::class);
        $this->expeditionService->method('getActive')->willReturn(null);

        $this->controller = new WorldMapController(
            $this->playerHelper,
            $this->zoneRepository,
            $this->zoneConnectionRepository,
            $this->visitedZoneRepository,
            $this->zoneTravelService,
            $this->zoneEventService,
            $this->expeditionService,
        );
        $this->controller->setContainer($this->createContainer());
    }

    private function buildZone(int $id, string $slug, ?int $x, ?int $y, bool $safe = false): Zone
    {
        $zone = (new Zone())->setSlug($slug)->setName(ucfirst($slug))->setIsSafe($safe);
        $zone->setMapX($x)->setMapY($y);
        $ref = new \ReflectionProperty(Zone::class, 'id');
        $ref->setValue($zone, $id);

        return $zone;
    }

    public function testRedirectsWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $response = $this->controller->index();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertNull($this->captured);
    }

    public function testBuildsNodesEdgesAndTravelLinks(): void
    {
        $hub = $this->buildZone(1, 'hub', 50, 55, true);
        $forest = $this->buildZone(2, 'forest', 28, 38);
        $unplaced = $this->buildZone(3, 'void', null, null);

        $player = new Player();
        $player->setCurrentZone($hub);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->zoneRepository->method('findAllEnabled')->willReturn([$hub, $forest, $unplaced]);
        $this->visitedZoneRepository->method('findVisitedZoneIds')->willReturn([2]);

        // Connexion hub -> forest (traversable) ; utilisee pour aretes + lien de voyage.
        $connection = new ZoneConnection($hub, $forest, 300);
        $refId = new \ReflectionProperty(ZoneConnection::class, 'id');
        $refId->setValue($connection, 99);
        $this->zoneConnectionRepository->method('findEnabledFrom')->willReturnCallback(
            fn (Zone $z) => $z->getId() === 1 ? [$connection] : []
        );

        $this->controller->index();

        $this->assertNotNull($this->captured);
        // Seules les 2 zones placees apparaissent (la zone sans position est exclue).
        $this->assertCount(2, $this->captured['nodes']);

        $byId = [];
        foreach ($this->captured['nodes'] as $node) {
            $byId[$node['id']] = $node;
        }
        $this->assertTrue($byId[1]['current']);
        $this->assertTrue($byId[1]['discovered']);
        $this->assertNull($byId[1]['travelConnectionId']); // la zone courante n'est pas une cible
        $this->assertTrue($byId[2]['discovered']);
        $this->assertSame(99, $byId[2]['travelConnectionId']); // forest atteignable depuis le hub

        // Une arete hub<->forest.
        $this->assertCount(1, $this->captured['edges']);
        $this->assertTrue($this->captured['hasCurrent']);
    }

    private function createContainer(): ContainerInterface&MockObject
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $twig = $this->createMock(TwigEnvironment::class);
        $twig->method('render')->willReturnCallback(function (string $view, array $params): string {
            $this->captured = $params;

            return '<html></html>';
        });

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/game');

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $services = [
            'security.authorization_checker' => $authChecker,
            'twig' => $twig,
            'router' => $router,
            'request_stack' => $requestStack,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
