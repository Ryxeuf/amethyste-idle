<?php

namespace App\Controller\Game;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\Entity\Game\Monster;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\ExploreResult;
use App\GameEngine\Zone\ExploreService;
use App\GameEngine\Zone\HuntService;
use App\GameEngine\Zone\NotEnoughActionEnergyException;
use App\GameEngine\Zone\PlayerZoneSynchronizer;
use App\GameEngine\Zone\ZoneActionException;
use App\GameEngine\Zone\ZoneTravelException;
use App\GameEngine\Zone\ZoneTravelService;
use App\Helper\PlayerHelper;
use App\Repository\PlayerVisitedZoneRepository;
use App\Repository\ZoneConnectionRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        private readonly ExploreService $exploreService,
        private readonly HuntService $huntService,
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

        $zone = $this->resolveZone($player);
        if (null === $zone) {
            return $this->render('game/zone/index.html.twig', [
                'zone' => null,
                'connections' => [],
                'playersPresent' => [],
                'poiCounts' => [],
                'actions' => [],
                'huntTargets' => [],
                'huntCost' => $this->huntService->getHuntCost(),
                'poiLabels' => [],
                'typeLabels' => [],
                'travel' => null,
                'visitedZoneIds' => [],
                'justArrived' => null,
                'energy' => null,
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

        return $this->render('game/zone/index.html.twig', [
            'zone' => $zone,
            'connections' => $connections,
            'playersPresent' => $playersPresent,
            'poiCounts' => $poiCounts,
            'actions' => $this->buildActions($zone, $poiCounts),
            'huntTargets' => $this->huntService->getHuntTargets($player, $zone),
            'huntCost' => $this->huntService->getHuntCost(),
            'travel' => $travel,
            'visitedZoneIds' => $this->visitedZoneRepository->findVisitedZoneIds($player),
            'justArrived' => $arrived,
            'energy' => [
                'current' => $player->getActionEnergy(),
                'max' => $player->getMaxActionEnergy(),
                'nextPointIn' => $this->actionEnergyManager->secondsUntilNextPoint($player),
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
     * Actions conditionnees par la zone — structure extensible, branchees
     * sur les mecaniques reelles en ZON-08 (explorer), ZON-09 (chasser),
     * ZON-10 (recolter).
     *
     * @param array<string, int> $poiCounts
     *
     * @return list<array{key: string, label: string, enabled: bool}>
     */
    private function buildActions(Zone $zone, array $poiCounts): array
    {
        $actions = [
            ['key' => 'explore', 'label' => 'game.zone.actions.explore', 'enabled' => true, 'cost' => $this->exploreService->getExploreCost()],
        ];

        if (!$zone->isSafe()) {
            $actions[] = ['key' => 'hunt', 'label' => 'game.zone.actions.hunt', 'enabled' => false];
        }

        if (($poiCounts[ObjectLayer::TYPE_HARVEST_SPOT] ?? 0) > 0) {
            $actions[] = ['key' => 'gather', 'label' => 'game.zone.actions.gather', 'enabled' => false];
        }

        return $actions;
    }
}
