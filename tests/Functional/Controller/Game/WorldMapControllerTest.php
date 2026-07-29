<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\WorldMapController;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\GameEngine\Settlement\VassalageService;
use App\GameEngine\Zone\ExpeditionService;
use App\GameEngine\Zone\ZoneEventService;
use App\GameEngine\Zone\ZoneTravelService;
use App\Helper\PlayerHelper;
use App\Repository\PlayerVisitedZoneRepository;
use App\Repository\SettlementRepository;
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
    private SettlementRepository&MockObject $settlementRepository;
    private VassalageService&MockObject $vassalage;
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
        // FOY-09 : ces tests portent sur le graphe et la decouverte, pas sur les
        // foyers. Une carte sans foyer est l'etat d'un monde neuf.
        $this->settlementRepository = $this->createMock(SettlementRepository::class);
        $this->settlementRepository->method('findOneByZone')->willReturn(null);
        $this->vassalage = $this->createMock(VassalageService::class);

        $this->controller = new WorldMapController(
            $this->playerHelper,
            $this->zoneRepository,
            $this->zoneConnectionRepository,
            $this->visitedZoneRepository,
            $this->zoneTravelService,
            $this->zoneEventService,
            $this->expeditionService,
            $this->settlementRepository,
            $this->vassalage,
        );
        $this->controller->setContainer($this->createContainer());
    }

    private function buildZone(int $id, string $slug, ?int $x, ?int $y, bool $safe = false, ?string $shape = null): Zone
    {
        $zone = (new Zone())->setSlug($slug)->setName(ucfirst($slug))->setIsSafe($safe);
        $zone->setMapX($x)->setMapY($y)->setMapShape($shape);
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

    /**
     * Brouillard de guerre : trois etats, et un contour qui ne fuit pas.
     *
     * Le contour est la seule donnee de la carte qui dessine le terrain. L'emettre
     * pour une zone que le joueur n'a pas situee reviendrait a livrer le monde
     * entier a qui ouvre l'inspecteur — le brouillard ne serait qu'un decor.
     */
    public function testFogExposesShapesOnlyForWhatThePlayerCanSituate(): void
    {
        $hub = $this->buildZone(1, 'hub', 50, 55, true, '46,50 60,50 60,62 46,62');
        $forest = $this->buildZone(2, 'forest', 28, 38, false, '20,30 36,30 36,44 20,44');
        // Au-dela de la foret : jamais visitee, et voisine d'aucune zone connue.
        $glacier = $this->buildZone(3, 'glacier', 80, 6, false, '70,0 90,0 90,12 70,12');

        $player = new Player();
        $player->setCurrentZone($hub);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->zoneRepository->method('findAllEnabled')->willReturn([$hub, $forest, $glacier]);
        // Le joueur n'a jamais quitte le hub.
        $this->visitedZoneRepository->method('findVisitedZoneIds')->willReturn([]);

        // Graphe volontairement **a sens unique** : la foret n'a aucune liaison
        // retour vers le hub. Elle doit tout de meme etre reperee — le repere
        // se lit sur les aretes sortantes des zones connues, jamais sur celles
        // de la zone examinee, faute de quoi il ne marcherait que sur les
        // connexions bidirectionnelles.
        $hubToForest = new ZoneConnection($hub, $forest, 300);
        $forestToGlacier = new ZoneConnection($forest, $glacier, 600);
        $refId = new \ReflectionProperty(ZoneConnection::class, 'id');
        $refId->setValue($hubToForest, 10);
        $refId->setValue($forestToGlacier, 11);
        $this->zoneConnectionRepository->method('findEnabledFrom')->willReturnCallback(
            fn (Zone $z) => match ($z->getId()) {
                1 => [$hubToForest],
                2 => [$forestToGlacier],
                default => [],
            }
        );

        $this->controller->index();

        $this->assertNotNull($this->captured);
        $byId = [];
        foreach ($this->captured['nodes'] as $node) {
            $byId[$node['id']] = $node;
        }

        // Le hub : parcouru, donc a decouvert.
        $this->assertTrue($byId[1]['discovered']);
        $this->assertFalse($byId[1]['scouted']);
        $this->assertSame('46,50 60,50 60,62 46,62', $byId[1]['shape']);

        // La foret : jamais visitee, mais voisine du hub — reperee.
        $this->assertFalse($byId[2]['discovered']);
        $this->assertTrue($byId[2]['scouted']);
        $this->assertSame('20,30 36,30 36,44 20,44', $byId[2]['shape']);

        // Le glacier : deux pas plus loin, rien n'en transpire.
        $this->assertFalse($byId[3]['discovered']);
        $this->assertFalse($byId[3]['scouted']);
        $this->assertNull($byId[3]['shape']);
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
