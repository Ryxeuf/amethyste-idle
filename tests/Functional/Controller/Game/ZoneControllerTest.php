<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\ZoneController;
use App\Entity\App\Map;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\Entity\Game\Monster;
use App\GameEngine\Dungeon\GroupDungeonCombatService;
use App\GameEngine\Dungeon\GroupDungeonService;
use App\GameEngine\Social\ChatManager;
use App\GameEngine\World\GameTimeService;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\ExpeditionService;
use App\GameEngine\Zone\ExploreResult;
use App\GameEngine\Zone\ExploreService;
use App\GameEngine\Zone\GatherResult;
use App\GameEngine\Zone\GatherService;
use App\GameEngine\Zone\HuntService;
use App\GameEngine\Zone\LifeRegenManager;
use App\GameEngine\Zone\NotEnoughActionEnergyException;
use App\GameEngine\Zone\PlayerZoneSynchronizer;
use App\GameEngine\Zone\ZoneActionException;
use App\GameEngine\Zone\ZoneBossService;
use App\GameEngine\Zone\ZoneEventService;
use App\GameEngine\Zone\ZoneTravelException;
use App\GameEngine\Zone\ZoneTravelService;
use App\Helper\PlayerHelper;
use App\Repository\GroupDungeonClearRepository;
use App\Repository\PlayerVisitedZoneRepository;
use App\Repository\ZoneConnectionRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment as TwigEnvironment;

class ZoneControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $playerRepository;
    private EntityRepository&MockObject $objectLayerRepository;
    private EntityRepository&MockObject $zoneConnectionEntityRepository;
    private EntityRepository&MockObject $monsterRepository;
    private PlayerHelper&MockObject $playerHelper;
    private ZoneRepository&MockObject $zoneRepository;
    private ZoneConnectionRepository&MockObject $zoneConnectionRepository;
    private PlayerZoneSynchronizer&MockObject $playerZoneSynchronizer;
    private ZoneTravelService&MockObject $zoneTravelService;
    private PlayerVisitedZoneRepository&MockObject $visitedZoneRepository;
    private ActionEnergyManager&MockObject $actionEnergyManager;
    private LifeRegenManager&MockObject $lifeRegenManager;
    private ExploreService&MockObject $exploreService;
    private HuntService&MockObject $huntService;
    private GatherService&MockObject $gatherService;
    private ExpeditionService&MockObject $expeditionService;
    private ChatManager&MockObject $chatManager;
    private ZoneEventService&MockObject $zoneEventService;
    private GameTimeService&MockObject $gameTimeService;
    private ZoneBossService&MockObject $zoneBossService;
    private GroupDungeonService&MockObject $groupDungeonService;
    private GroupDungeonCombatService&MockObject $groupDungeonCombatService;
    private GroupDungeonClearRepository&MockObject $groupDungeonClearRepository;
    private CsrfTokenManagerInterface&MockObject $csrfTokenManager;
    private Session $session;
    private ZoneController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->playerRepository = $this->createMock(EntityRepository::class);
        $this->objectLayerRepository = $this->createMock(EntityRepository::class);
        $this->zoneConnectionEntityRepository = $this->createMock(EntityRepository::class);
        $this->monsterRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Player::class, $this->playerRepository],
            [ObjectLayer::class, $this->objectLayerRepository],
            [ZoneConnection::class, $this->zoneConnectionEntityRepository],
            [Monster::class, $this->monsterRepository],
        ]);

        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->zoneRepository = $this->createMock(ZoneRepository::class);
        $this->zoneConnectionRepository = $this->createMock(ZoneConnectionRepository::class);
        $this->playerZoneSynchronizer = $this->createMock(PlayerZoneSynchronizer::class);
        $this->zoneTravelService = $this->createMock(ZoneTravelService::class);
        $this->visitedZoneRepository = $this->createMock(PlayerVisitedZoneRepository::class);
        $this->actionEnergyManager = $this->createMock(ActionEnergyManager::class);
        $this->lifeRegenManager = $this->createMock(LifeRegenManager::class);
        $this->exploreService = $this->createMock(ExploreService::class);
        $this->exploreService->method('getExploreCost')->willReturn(5);
        $this->huntService = $this->createMock(HuntService::class);
        $this->huntService->method('getHuntCost')->willReturn(5);
        $this->huntService->method('getHuntTargets')->willReturn([]);
        $this->gatherService = $this->createMock(GatherService::class);
        $this->gatherService->method('getGatherCost')->willReturn(3);
        $this->gatherService->method('getGatherables')->willReturn([]);
        $this->expeditionService = $this->createMock(ExpeditionService::class);
        $this->expeditionService->method('getActive')->willReturn(null);
        $this->expeditionService->method('isEligibleZone')->willReturn(true);
        $this->expeditionService->method('getDurations')->willReturn(['short' => 3600, 'medium' => 14400, 'long' => 43200]);
        $this->chatManager = $this->createMock(ChatManager::class);
        $this->chatManager->method('getZoneHistory')->willReturn([]);
        $this->zoneEventService = $this->createMock(ZoneEventService::class);
        $this->zoneEventService->method('getActiveEventsForZone')->willReturn([]);
        $this->zoneEventService->method('getEventCost')->willReturn(10);
        $this->gameTimeService = $this->createMock(GameTimeService::class);
        $this->gameTimeService->method('getPhase')->willReturn('day');
        $this->zoneBossService = $this->createMock(ZoneBossService::class);
        $this->zoneBossService->method('getActiveBossForZone')->willReturn(null);
        $this->groupDungeonService = $this->createMock(GroupDungeonService::class);
        $this->groupDungeonService->method('getActiveRunForPlayer')->willReturn(null);
        $this->groupDungeonCombatService = $this->createMock(GroupDungeonCombatService::class);
        $this->groupDungeonClearRepository = $this->createMock(GroupDungeonClearRepository::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->controller = new ZoneController(
            $this->entityManager,
            $this->playerHelper,
            $this->zoneRepository,
            $this->zoneConnectionRepository,
            $this->playerZoneSynchronizer,
            $this->zoneTravelService,
            $this->visitedZoneRepository,
            $this->actionEnergyManager,
            $this->lifeRegenManager,
            $this->exploreService,
            $this->huntService,
            $this->gatherService,
            $this->expeditionService,
            $this->chatManager,
            $this->zoneEventService,
            $this->gameTimeService,
            $this->zoneBossService,
            $this->groupDungeonService,
            $this->groupDungeonCombatService,
            $this->groupDungeonClearRepository,
        );
        $this->controller->setContainer($this->createContainer());
    }

    private function buildZone(string $slug, string $type = Zone::TYPE_WILDERNESS, bool $safe = false): Zone
    {
        $zone = (new Zone())->setSlug($slug)->setName(ucfirst($slug))->setType($type)->setIsSafe($safe);
        // Id requis par l'ecran de zone (chat de zone, ZON-14) ; simule la persistance.
        $ref = new \ReflectionProperty(Zone::class, 'id');
        $ref->setValue($zone, abs(crc32($slug)) % 100000 + 1);

        return $zone;
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
        $player->setMaxLife(100);
        $player->setLife(100);
        $player->setCurrentZone($zone);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->zoneTravelService->expects($this->once())->method('settleArrival')->with($player)->willReturn(null);
        $this->zoneTravelService->expects($this->once())->method('markZoneVisited')->with($player, $zone);
        $this->visitedZoneRepository->method('findVisitedZoneIds')->willReturn([7]);

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
        $this->assertNull($this->capturedTemplateParams['travel']);
        $this->assertNull($this->capturedTemplateParams['justArrived']);
        $this->assertSame([7], $this->capturedTemplateParams['visitedZoneIds']);
        $this->assertSame(100, $this->capturedTemplateParams['energy']['current']);
        $this->assertSame(100, $this->capturedTemplateParams['energy']['max']);

        $actionKeys = array_column($this->capturedTemplateParams['actions'], 'key');
        $this->assertSame(['explore'], $actionKeys);

        $exploreAction = $this->capturedTemplateParams['actions'][0];
        $this->assertTrue($exploreAction['enabled']);
        $this->assertSame(5, $exploreAction['cost']);

        // Chasser (ZON-09) et Recolter (ZON-10) ont leur propre bloc.
        $this->assertSame([], $this->capturedTemplateParams['gatherables']);
        $this->assertSame(3, $this->capturedTemplateParams['gatherCost']);
    }

    public function testExploreRedirectsToFightOnMobEncounter(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $fight = $this->createMock(\App\Entity\App\Fight::class);
        $this->exploreService->expects($this->once())->method('explore')->with($player)
            ->willReturn(new ExploreResult(ExploreResult::EVENT_MOB, 'game.zone.explore.result.mob', ['%monster%' => 'Slime'], $fight));

        $response = $this->controller->explore(new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame([], $this->session->getFlashBag()->get('explore_result'));
    }

    public function testExploreFlashesResultForNonCombatEvent(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $this->exploreService->method('explore')
            ->willReturn(new ExploreResult(ExploreResult::EVENT_CHEST, 'game.zone.explore.result.chest', ['%gils%' => 12]));

        $response = $this->controller->explore(new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            [['key' => 'game.zone.explore.result.chest', 'params' => ['%gils%' => 12]]],
            $this->session->getFlashBag()->get('explore_result'),
        );
    }

    public function testExploreShowsErrorFlashWhenRefused(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $this->exploreService->method('explore')
            ->willThrowException(new NotEnoughActionEnergyException('game.zone.energy.error.not_enough'));

        $response = $this->controller->explore(new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.energy.error.not_enough'], $this->session->getFlashBag()->get('error'));
    }

    public function testExploreRejectsInvalidCsrfToken(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);
        $this->exploreService->expects($this->never())->method('explore');

        $response = $this->controller->explore(new Request(request: ['_token' => 'bad']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.travel.error.invalid_token'], $this->session->getFlashBag()->get('error'));
    }

    public function testHuntRedirectsToFightOnSuccess(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $monster = new Monster();
        $this->monsterRepository->method('find')->with(9)->willReturn($monster);

        $fight = $this->createMock(\App\Entity\App\Fight::class);
        $this->huntService->expects($this->once())->method('hunt')->with($player, $monster)->willReturn($fight);

        $response = $this->controller->hunt(9, new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame([], $this->session->getFlashBag()->get('error'));
    }

    public function testHuntShowsErrorFlashWhenRefused(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $monster = new Monster();
        $this->monsterRepository->method('find')->willReturn($monster);
        $this->huntService->method('hunt')
            ->willThrowException(new ZoneActionException('game.zone.hunt.error.no_prey'));

        $response = $this->controller->hunt(9, new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.hunt.error.no_prey'], $this->session->getFlashBag()->get('error'));
    }

    public function testHuntFlashesErrorForUnknownMonster(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $this->monsterRepository->method('find')->willReturn(null);
        $this->huntService->expects($this->never())->method('hunt');

        $response = $this->controller->hunt(404, new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.hunt.error.unknown_target'], $this->session->getFlashBag()->get('error'));
    }

    public function testHuntRejectsInvalidCsrfToken(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);
        $this->huntService->expects($this->never())->method('hunt');

        $response = $this->controller->hunt(9, new Request(request: ['_token' => 'bad']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.travel.error.invalid_token'], $this->session->getFlashBag()->get('error'));
    }

    public function testGatherFlashesResultOnSuccess(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $this->gatherService->expects($this->once())->method('gather')->with($player, 'filon-de-fer')
            ->willReturn(new GatherResult('filon-de-fer', 'Minerai de fer', 2, 18, 'game.zone.gather.result.success', ['%count%' => 2, '%item%' => 'Minerai de fer']));

        $response = $this->controller->gather('filon-de-fer', new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            [['key' => 'game.zone.gather.result.success', 'params' => ['%count%' => 2, '%item%' => 'Minerai de fer']]],
            $this->session->getFlashBag()->get('gather_result'),
        );
    }

    public function testGatherShowsErrorFlashWhenRefused(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $this->gatherService->method('gather')
            ->willThrowException(new ZoneActionException('game.zone.gather.error.depleted'));

        $response = $this->controller->gather('filon-de-fer', new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.gather.error.depleted'], $this->session->getFlashBag()->get('error'));
    }

    public function testGatherRejectsInvalidCsrfToken(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);
        $this->gatherService->expects($this->never())->method('gather');

        $response = $this->controller->gather('filon-de-fer', new Request(request: ['_token' => 'bad']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.travel.error.invalid_token'], $this->session->getFlashBag()->get('error'));
    }

    public function testIndexExposesTravelStateWhileTraveling(): void
    {
        $zone = $this->buildZone('village-de-lumiere', Zone::TYPE_CITY, true);
        $destination = $this->buildZone('crete-de-ventombre');
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $player->setCurrentZone($zone);
        $player->setTravelToZone($destination);
        $player->setTravelArrivesAt(new \DateTimeImmutable('+300 seconds'));
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->zoneConnectionRepository->method('findEnabledFrom')->willReturn([]);
        $this->playerRepository->method('findBy')->willReturn([$player]);
        $this->objectLayerRepository->method('findBy')->willReturn([]);

        $this->controller->index();

        $travel = $this->capturedTemplateParams['travel'];
        $this->assertSame($destination, $travel['destination']);
        $this->assertGreaterThan(290, $travel['remainingSeconds']);
        $this->assertLessThanOrEqual(300, $travel['remainingSeconds']);
    }

    public function testSafeZoneHidesHuntAction(): void
    {
        $zone = $this->buildZone('village-de-lumiere', Zone::TYPE_CITY, true);
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
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
        $player->setMaxLife(100);
        $player->setLife(100);
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
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->playerZoneSynchronizer->method('syncFromMap')->willReturn(null);
        $this->zoneRepository->method('findEnabledBySlug')->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $response = $this->controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($this->capturedTemplateParams['zone']);
        $this->assertSame([], $this->capturedTemplateParams['actions']);
    }

    public function testTravelStartsAndRedirectsWithSuccessFlash(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $connection = new ZoneConnection($this->buildZone('village'), $this->buildZone('foret'), 300);
        $this->zoneConnectionEntityRepository->method('find')->with(12)->willReturn($connection);

        $this->zoneTravelService->expects($this->once())
            ->method('startTravel')->with($player, $connection)
            ->willReturn(new \DateTimeImmutable('+5 minutes'));

        $response = $this->controller->travel(12, new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.travel.flash.started'], $this->session->getFlashBag()->get('success'));
    }

    public function testTravelRefusalShowsErrorFlash(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $connection = new ZoneConnection($this->buildZone('village'), $this->buildZone('foret'), 300);
        $this->zoneConnectionEntityRepository->method('find')->willReturn($connection);

        $this->zoneTravelService->method('startTravel')
            ->willThrowException(new ZoneTravelException('game.zone.travel.error.in_fight'));

        $response = $this->controller->travel(12, new Request(request: ['_token' => 'tok']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.travel.error.in_fight'], $this->session->getFlashBag()->get('error'));
    }

    public function testTravelRejectsInvalidCsrfToken(): void
    {
        $player = new Player();
        $player->setMaxLife(100);
        $player->setLife(100);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);
        $this->zoneTravelService->expects($this->never())->method('startTravel');

        $response = $this->controller->travel(12, new Request(request: ['_token' => 'bad']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['game.zone.travel.error.invalid_token'], $this->session->getFlashBag()->get('error'));
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
        $router->method('generate')->willReturn('/game/zone');

        $this->session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($this->session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $services = [
            'security.authorization_checker' => $authChecker,
            'twig' => $twig,
            'router' => $router,
            'security.csrf.token_manager' => $this->csrfTokenManager,
            'request_stack' => $requestStack,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
