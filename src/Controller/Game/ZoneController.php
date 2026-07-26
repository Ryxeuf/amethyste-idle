<?php

namespace App\Controller\Game;

use App\Entity\App\GameEvent;
use App\Entity\App\GroupDungeonRun;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\Entity\Game\Monster;
use App\GameEngine\Dungeon\GroupDungeonCombatService;
use App\GameEngine\Dungeon\GroupDungeonService;
use App\GameEngine\Mount\MountTravelSpeed;
use App\GameEngine\Social\ChatManager;
use App\GameEngine\World\GameTimeService;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\ExpeditionService;
use App\GameEngine\Zone\ExploreResult;
use App\GameEngine\Zone\ExploreService;
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
use App\Repository\PlayerShopRepository;
use App\Repository\PlayerVisitedZoneRepository;
use App\Repository\ZoneConnectionRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ecran de zone (pivot PBBG, ZON-05) : la vue principale du modele zone.
 * La zone courante conditionne les actions disponibles ; les actions
 * elles-memes (explorer, chasser, recolter) arrivent avec ZON-08..10,
 * le voyage avec ZON-06.
 */
class ZoneController extends AbstractController
{
    public const HUB_SLUG = 'village-de-lumiere';

    private const POI_TYPES = [
        ObjectLayer::TYPE_HARVEST_SPOT,
        ObjectLayer::TYPE_FORGE,
        ObjectLayer::TYPE_TANNERY,
        ObjectLayer::TYPE_ALCHEMY_LAB,
        ObjectLayer::TYPE_JEWELER_BENCH,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHelper $playerHelper,
        private readonly ZoneRepository $zoneRepository,
        private readonly ZoneConnectionRepository $zoneConnectionRepository,
        private readonly PlayerZoneSynchronizer $playerZoneSynchronizer,
        private readonly ZoneTravelService $zoneTravelService,
        private readonly PlayerVisitedZoneRepository $visitedZoneRepository,
        private readonly ActionEnergyManager $actionEnergyManager,
        private readonly LifeRegenManager $lifeRegenManager,
        private readonly ExploreService $exploreService,
        private readonly HuntService $huntService,
        private readonly GatherService $gatherService,
        private readonly ExpeditionService $expeditionService,
        private readonly ChatManager $chatManager,
        private readonly ZoneEventService $zoneEventService,
        private readonly GameTimeService $gameTimeService,
        private readonly ZoneBossService $zoneBossService,
        private readonly GroupDungeonService $groupDungeonService,
        private readonly GroupDungeonCombatService $groupDungeonCombatService,
        private readonly GroupDungeonClearRepository $groupDungeonClearRepository,
        private readonly MountTravelSpeed $mountTravelSpeed,
        private readonly PlayerShopRepository $playerShopRepository,
    ) {
    }

    #[Route('/game/zone', name: 'app_game_zone', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        // Arrivee automatique : regle un eventuel voyage arrive a terme.
        $arrived = $this->zoneTravelService->settleArrival($player);

        // Regeneration paresseuse de l'energie d'action (ZON-07).
        $this->actionEnergyManager->refresh($player, true);

        // Regeneration paresseuse des PV hors combat (ZON-12).
        $this->lifeRegenManager->refresh($player, true);

        // Resolution paresseuse d'une expedition terminee (ZON-13) : notifie une
        // seule fois (in-game + Mercure si connecte).
        $this->expeditionService->settle($player);

        $zone = $this->resolveZone($player);
        if (null === $zone) {
            return $this->render('game/zone/index.html.twig', [
                'zone' => null,
                'connections' => [],
                'mount' => null,
                'shopsPresent' => [],
                'playersPresent' => [],
                'poiCounts' => [],
                'actions' => [],
                'huntTargets' => [],
                'huntCost' => $this->huntService->getHuntCost(),
                'gatherables' => [],
                'gatherCost' => $this->gatherService->getGatherCost(),
                'poiLabels' => [],
                'typeLabels' => [],
                'travel' => null,
                'visitedZoneIds' => [],
                'justArrived' => null,
                'energy' => null,
                'life' => null,
                'expedition' => null,
                'zoneEvents' => [],
                'zoneBoss' => null,
                'groupDungeon' => null,
                'pnjsPresent' => [],
                'gameHour' => $this->gameTimeService->getHour(),
                'zoneChat' => null,
                'phase' => $this->gameTimeService->getPhase(),
            ]);
        }

        // La zone courante compte comme decouverte (deverrouille les liaisons rapides).
        $this->zoneTravelService->markZoneVisited($player, $zone);

        $connections = $this->zoneConnectionRepository->findEnabledFrom($zone);

        $playersPresent = $this->entityManager
            ->getRepository(Player::class)
            ->findBy(['currentZone' => $zone], ['name' => 'ASC'], 50);

        $poiCounts = $this->countPointsOfInterest($zone);

        $travel = null;
        if ($player->isTraveling() && null !== $player->getTravelArrivesAt()) {
            $remaining = $player->getTravelArrivesAt()->getTimestamp() - time();
            $travel = [
                'destination' => $player->getTravelToZone(),
                'arrivesAt' => $player->getTravelArrivesAt(),
                'remainingSeconds' => max(0, $remaining),
            ];
        }

        // Chaque liaison porte la duree **reellement subie**, monture comprise
        // (tache 130) : annoncer la duree de reference alors qu'une monture la
        // raccourcit ferait passer le bonus pour inoperant.
        $connectionRows = [];
        foreach ($connections as $connection) {
            $connectionRows[] = [
                'connection' => $connection,
                'seconds' => $this->mountTravelSpeed->effectiveTravelSeconds($player, $connection->getTravelSeconds()),
            ];
        }

        return $this->render('game/zone/index.html.twig', [
            'zone' => $zone,
            'connections' => $connectionRows,
            'mount' => $player->getActiveMount(),
            'playersPresent' => $playersPresent,
            'poiCounts' => $poiCounts,
            'actions' => $this->buildActions(),
            'huntTargets' => $this->huntService->getHuntTargets($player, $zone),
            'huntCost' => $this->huntService->getHuntCost(),
            'gatherables' => $this->gatherService->getGatherables($zone),
            'gatherCost' => $this->gatherService->getGatherCost(),
            'travel' => $travel,
            'visitedZoneIds' => $this->visitedZoneRepository->findVisitedZoneIds($player),
            'justArrived' => $arrived,
            'energy' => [
                'current' => $player->getActionEnergy(),
                'max' => $player->getMaxActionEnergy(),
                'nextPointIn' => $this->actionEnergyManager->secondsUntilNextPoint($player),
            ],
            'life' => [
                'current' => $player->getLife(),
                'max' => $player->getMaxLife(),
                'nextPointIn' => $this->lifeRegenManager->secondsUntilNextPoint($player),
                'fullIn' => $this->lifeRegenManager->secondsUntilFull($player),
            ],
            'expedition' => $this->buildExpedition($player, $zone),
            'zoneEvents' => $this->buildZoneEvents($player, $zone),
            'zoneBoss' => $this->buildZoneBoss($zone),
            'groupDungeon' => $this->buildGroupDungeon($player),
            // Les PNJ presents dans la zone (ZON-27) : depuis la suppression des
            // overlays carte, l'ecran de zone est le seul endroit d'ou les
            // atteindre — sans lui, les boutiques sont injoignables.
            // ECO-12 : les echoppes tenues par des joueurs, a cote des PNJ.
            // Une vitrine invisible ne sert a rien — et l'achat exige d'etre
            // sur place, donc c'est ici qu'elle doit apparaitre.
            'shopsPresent' => $this->playerShopRepository->findOpenInZone($zone),
            'pnjsPresent' => $this->entityManager
                ->getRepository(Pnj::class)
                ->findBy(['zone' => $zone], ['name' => 'ASC'], 20),
            // Heure in-game : conditionne l'ouverture des boutiques PNJ.
            'gameHour' => $this->gameTimeService->getHour(),
            'phase' => $this->gameTimeService->getPhase(),
            'zoneChat' => [
                'zoneId' => $zone->getId(),
                'messages' => array_reverse($this->chatManager->getZoneHistory($zone, 30)),
            ],
            'poiLabels' => [
                ObjectLayer::TYPE_HARVEST_SPOT => 'game.zone.poi.harvest_spot',
                ObjectLayer::TYPE_FORGE => 'game.zone.poi.forge',
                ObjectLayer::TYPE_TANNERY => 'game.zone.poi.tannery',
                ObjectLayer::TYPE_ALCHEMY_LAB => 'game.zone.poi.alchemy_lab',
                ObjectLayer::TYPE_JEWELER_BENCH => 'game.zone.poi.jeweler_bench',
            ],
            'typeLabels' => [
                Zone::TYPE_CITY => 'game.zone.type.city',
                Zone::TYPE_WILDERNESS => 'game.zone.type.wilderness',
                Zone::TYPE_INTERIOR => 'game.zone.type.interior',
                Zone::TYPE_DUNGEON => 'game.zone.type.dungeon',
            ],
        ]);
    }

    #[Route('/game/zone/explore', name: 'app_game_zone_explore', methods: ['POST'])]
    public function explore(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('explore', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        if (null !== ($blocked = $this->denyIfOnExpedition($player))) {
            return $blocked;
        }

        try {
            $result = $this->exploreService->explore($player);
        } catch (ZoneActionException|NotEnoughActionEnergyException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_game_zone');
        }

        if (ExploreResult::EVENT_MOB === $result->event && null !== $result->fight) {
            // Rencontre : le combat tour par tour existant prend la main.
            return $this->redirectToRoute('app_game_fight');
        }

        $this->addFlash('explore_result', ['key' => $result->messageKey, 'params' => $result->messageParams]);

        return $this->redirectToRoute('app_game_zone');
    }

    #[Route('/game/zone/hunt/{id}', name: 'app_game_zone_hunt', methods: ['POST'])]
    public function hunt(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('hunt_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        if (null !== ($blocked = $this->denyIfOnExpedition($player))) {
            return $blocked;
        }

        $monster = $this->entityManager->getRepository(Monster::class)->find($id);
        if (null === $monster) {
            $this->addFlash('error', 'game.zone.hunt.error.unknown_target');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $this->huntService->hunt($player, $monster);
        } catch (ZoneActionException|NotEnoughActionEnergyException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_game_zone');
        }

        // La proie est engagee : le combat tour par tour existant prend la main.
        return $this->redirectToRoute('app_game_fight');
    }

    #[Route('/game/zone/gather/{slug}', name: 'app_game_zone_gather', methods: ['POST'], requirements: ['slug' => '[a-z0-9\-]+'])]
    public function gather(string $slug, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('gather_' . $slug, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        if (null !== ($blocked = $this->denyIfOnExpedition($player))) {
            return $blocked;
        }

        try {
            $result = $this->gatherService->gather($player, $slug);
        } catch (ZoneActionException|NotEnoughActionEnergyException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_game_zone');
        }

        $this->addFlash('gather_result', ['key' => $result->messageKey, 'params' => $result->messageParams]);

        return $this->redirectToRoute('app_game_zone');
    }

    #[Route('/game/zone/travel/{id}', name: 'app_game_zone_travel', methods: ['POST'])]
    public function travel(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('travel_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        if (null !== ($blocked = $this->denyIfOnExpedition($player))) {
            return $blocked;
        }

        $connection = $this->entityManager->getRepository(ZoneConnection::class)->find($id);
        if (null === $connection) {
            $this->addFlash('error', 'game.zone.travel.error.unavailable');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $this->zoneTravelService->startTravel($player, $connection);
            $this->addFlash('success', 'game.zone.travel.flash.started');
        } catch (ZoneTravelException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_game_zone');
    }

    #[Route('/game/zone/expedition/start/{durationKey}', name: 'app_game_zone_expedition_start', methods: ['POST'], requirements: ['durationKey' => '[a-z]+'])]
    public function expeditionStart(string $durationKey, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('expedition_start_' . $durationKey, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $this->expeditionService->start($player, $durationKey);
            $this->addFlash('success', 'game.zone.expedition.flash.started');
        } catch (ZoneActionException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_game_zone');
    }

    #[Route('/game/zone/expedition/claim', name: 'app_game_zone_expedition_claim', methods: ['POST'])]
    public function expeditionClaim(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('expedition_claim', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $result = $this->expeditionService->claim($player);
        } catch (ZoneActionException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_game_zone');
        }

        $this->addFlash('expedition_result', [
            'zone' => $result->zoneName,
            'gils' => $result->gils,
            'items' => $result->items,
        ]);

        return $this->redirectToRoute('app_game_zone');
    }

    #[Route('/game/zone/presence', name: 'app_game_zone_presence', methods: ['GET'])]
    public function presence(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return new JsonResponse(['players' => []]);
        }

        $zone = $player->getCurrentZone();
        if (null === $zone) {
            return new JsonResponse(['players' => []]);
        }

        /** @var list<Player> $present */
        $present = $this->entityManager->getRepository(Player::class)
            ->findBy(['currentZone' => $zone], ['name' => 'ASC'], 50);

        $players = array_map(static fn (Player $p): array => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'self' => $p->getId() === $player->getId(),
        ], $present);

        return new JsonResponse(['players' => $players]);
    }

    #[Route('/game/zone/event/{id}/join', name: 'app_game_zone_event_join', methods: ['POST'])]
    public function joinEvent(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('zone_event_join_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        $event = $this->entityManager->getRepository(GameEvent::class)->find($id);
        if (null === $event) {
            $this->addFlash('error', 'game.zone.event.error.unknown');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $this->zoneEventService->join($player, $event);
            $this->addFlash('success', 'game.zone.event.flash.joined');
        } catch (ZoneActionException|NotEnoughActionEnergyException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_game_zone');
    }

    #[Route('/game/zone/boss/{id}/assault', name: 'app_game_zone_boss_assault', methods: ['POST'])]
    public function assaultBoss(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('zone_boss_assault_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        $event = $this->entityManager->getRepository(GameEvent::class)->find($id);
        if (null === $event) {
            $this->addFlash('error', 'game.zone.event.error.unknown');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $result = $this->zoneBossService->assault($player, $event);
        } catch (ZoneActionException|NotEnoughActionEnergyException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_game_zone');
        }

        $this->addFlash('boss_result', [
            'damage' => $result->damageDealt,
            'hpCurrent' => $result->hpCurrent,
            'hpMax' => $result->hpMax,
            'defeated' => $result->defeated,
        ]);

        return $this->redirectToRoute('app_game_zone');
    }

    /**
     * Boss de zone asynchrone actif dans la zone (ZON-18) : nom, PV partages,
     * cout d'assaut. null si aucun boss actif.
     *
     * @return array<string, mixed>|null
     */
    private function buildZoneBoss(Zone $zone): ?array
    {
        $boss = $this->zoneBossService->getActiveBossForZone($zone);
        if (null === $boss) {
            return null;
        }

        return [
            'eventId' => $boss->getGameEvent()->getId(),
            'name' => $boss->getMonster()->getName(),
            'eventName' => $boss->getGameEvent()->getName(),
            'hpCurrent' => $boss->getHpCurrent(),
            'hpMax' => $boss->getHpMax(),
            'hpPercent' => $boss->getHpPercent(),
            'cost' => $this->zoneBossService->getAssaultCost(),
        ];
    }

    /**
     * Donjon de groupe actif du joueur (ZON-19) pour l'ecran de zone : nom,
     * statut, role de leader. null si aucun run actif.
     *
     * @return array<string, mixed>|null
     */
    private function buildGroupDungeon(Player $player): ?array
    {
        $run = $this->groupDungeonService->getActiveRunForPlayer($player);
        if (null === $run) {
            return null;
        }

        // Etat de combat (resolution paresseuse des tours en retard, ZON-19 s.2).
        $combat = $this->groupDungeonCombatService->state($run);

        // Recompense obtenue par le joueur si le run vient d'etre complete (ZON-20).
        $rewardGils = null;
        if (GroupDungeonRun::STATUS_COMPLETED === $combat['status']) {
            $clear = $this->groupDungeonClearRepository->findForRunAndPlayer($run, $player);
            $rewardGils = null !== $clear ? $clear->getGilsAwarded() : null;
        }

        return [
            'runId' => $run->getId(),
            'dungeonName' => $run->getDungeon()->getName(),
            'status' => $run->getStatus(),
            'memberCount' => $run->getMembers()->count(),
            'isLeader' => $run->getLeader()->getId() === $player->getId(),
            'viewerId' => $player->getId(),
            'combat' => $combat,
            'isMyTurn' => ($combat['activePlayerId'] ?? null) === $player->getId(),
            'rewardGils' => $rewardGils,
        ];
    }

    /**
     * Evenements de zone actifs pour l'ecran de zone (ZON-15) : nom, type,
     * fin, cout, et si le joueur a deja rejoint.
     *
     * @return list<array<string, mixed>>
     */
    private function buildZoneEvents(Player $player, Zone $zone): array
    {
        $events = [];
        foreach ($this->zoneEventService->getActiveEventsForZone($zone) as $event) {
            $remaining = $event->getEndsAt()->getTimestamp() - time();
            $events[] = [
                'id' => $event->getId(),
                'name' => $event->getName(),
                'typeLabel' => $event->getTypeLabel(),
                'description' => $event->getDescription(),
                'remainingSeconds' => max(0, $remaining),
                'cost' => $this->zoneEventService->getEventCost(),
                'joined' => $this->zoneEventService->hasJoined($player, $event),
            ];
        }

        return $events;
    }

    /**
     * Bloque une action de zone (explorer/chasser/recolter/voyager) tant qu'une
     * expedition est en cours ou a recuperer : etat exclusif (ZON-13).
     */
    private function denyIfOnExpedition(Player $player): ?Response
    {
        if (null === $this->expeditionService->getActive($player)) {
            return null;
        }

        $this->addFlash('error', 'game.zone.expedition.error.busy');

        return $this->redirectToRoute('app_game_zone');
    }

    /**
     * Etat de l'expedition pour l'ecran de zone : soit une expedition en cours /
     * a recuperer, soit les paliers de duree proposables dans la zone courante.
     *
     * @return array<string, mixed>
     */
    private function buildExpedition(Player $player, Zone $zone): array
    {
        $active = $this->expeditionService->getActive($player);
        if (null !== $active) {
            $remaining = $active->getEndsAt()->getTimestamp() - time();

            return [
                'active' => true,
                'zone' => $active->getZone(),
                'durationKey' => $active->getDurationKey(),
                'endsAt' => $active->getEndsAt(),
                'remainingSeconds' => max(0, $remaining),
                'ready' => $active->isComplete(),
            ];
        }

        return [
            'active' => false,
            'eligible' => $this->expeditionService->isEligibleZone($zone),
            'durations' => $this->expeditionService->getDurations(),
        ];
    }

    /**
     * Zone courante du joueur, avec rattrapage : sync depuis la carte, puis
     * fallback sur le hub (meme regle que le backfill de migration ZON-03).
     */
    private function resolveZone(Player $player): ?Zone
    {
        $zone = $player->getCurrentZone() ?? $this->playerZoneSynchronizer->syncFromMap($player, true);
        if (null !== $zone) {
            return $zone;
        }

        $hub = $this->zoneRepository->findEnabledBySlug(self::HUB_SLUG);
        if (null !== $hub) {
            $player->setCurrentZone($hub);
            $this->entityManager->flush();
        }

        return $hub;
    }

    /**
     * @return array<string, int> comptage par type d'ObjectLayer visible sur l'ecran de zone
     */
    private function countPointsOfInterest(Zone $zone): array
    {
        $counts = [];
        $objectLayers = $this->entityManager
            ->getRepository(ObjectLayer::class)
            ->findBy(['zone' => $zone]);

        foreach ($objectLayers as $objectLayer) {
            $type = $objectLayer->getType();
            if (\in_array($type, self::POI_TYPES, true)) {
                $counts[$type] = ($counts[$type] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * Actions conditionnees par la zone. Explorer (ZON-08) est l'action de base
     * de toute zone ; Chasser (ZON-09) et Recolter (ZON-10) ont chacune leur
     * bloc dedie sur l'ecran de zone (proies du bestiaire, filons partages).
     *
     * @return list<array{key: string, label: string, enabled: bool, cost?: int}>
     */
    private function buildActions(): array
    {
        return [
            ['key' => 'explore', 'label' => 'game.zone.actions.explore', 'enabled' => true, 'cost' => $this->exploreService->getExploreCost()],
        ];
    }
}
