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
use App\Enum\SettlementDoctrine;
use App\Enum\WeeklyCommissionReward;
use App\GameEngine\Dungeon\GroupDungeonCombatService;
use App\GameEngine\Dungeon\GroupDungeonService;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Materia\MateriaLootTable;
use App\GameEngine\Mount\MountTravelSpeed;
use App\GameEngine\Retention\WeeklyCommissionDelivery;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementDoctrineException;
use App\GameEngine\Settlement\SettlementDoctrineService;
use App\GameEngine\Settlement\SettlementPanelBuilder;
use App\GameEngine\Settlement\VeinRestorationException;
use App\GameEngine\Settlement\VeinRestorationService;
use App\GameEngine\Social\ChatManager;
use App\GameEngine\Tutorial\TrainingDummyOffer;
use App\GameEngine\World\GameTimeService;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\ExpeditionService;
use App\GameEngine\Zone\ExploreResult;
use App\GameEngine\Zone\ExploreService;
use App\GameEngine\Zone\GatherService;
use App\GameEngine\Zone\HuntService;
use App\GameEngine\Zone\LifeRegenManager;
use App\GameEngine\Zone\ManaRegenManager;
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
    /**
     * Nombre maximum d'habitants listes dans une zone.
     *
     * Le Fanal en compte quinze, et c'est de loin la zone la plus peuplee du
     * monde livre : le plafond n'est pas une regle de jeu, c'est un garde-fou
     * contre une requete qui deraperait.
     */
    private const MAX_PNJS_LISTED = 60;

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
        private readonly ZoneConnectionRepository $zoneConnectionRepository,
        private readonly PlayerZoneSynchronizer $playerZoneSynchronizer,
        private readonly ZoneTravelService $zoneTravelService,
        private readonly PlayerVisitedZoneRepository $visitedZoneRepository,
        private readonly ActionEnergyManager $actionEnergyManager,
        private readonly LifeRegenManager $lifeRegenManager,
        private readonly ManaRegenManager $manaRegenManager,
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
        private readonly SettlementPanelBuilder $settlementPanelBuilder,
        private readonly WeeklyCommissionDelivery $commissionDelivery,
        private readonly SettlementDefinitionLoader $settlementLoader,
        private readonly VeinRestorationService $veinRestorationService,
        private readonly SettlementDoctrineService $settlementDoctrineService,
        private readonly GameMasterPolicy $gameMasterPolicy,
        private readonly TrainingDummyOffer $trainingDummyOffer,
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
        // ARC-04a : les PM se rechargent en temps reel comme les PV — meme
        // mecanique paresseuse, meme point de lecture.
        $this->manaRegenManager->refresh($player, true);

        // Resolution paresseuse d'une expedition terminee (ZON-13) : notifie une
        // seule fois (in-game + Mercure si connecte).
        $this->expeditionService->settle($player);

        $zone = $this->resolveZone($player);
        if (null === $zone) {
            return $this->render('game/zone/index.html.twig', [
                'zone' => null,
                'connections' => [],
                'firstTravelOffered' => false,
                'isGameMaster' => $player->isGameMaster(),
                'mount' => null,
                'shopsPresent' => [],
                'playersPresent' => [],
                'poiCounts' => [],
                'actions' => [],
                'huntTargets' => [],
                'huntCost' => $this->huntService->getHuntCost(),
                'gatherables' => [],
                'gatherCost' => $this->gatherService->getGatherCost(),
                'palenessVisibleFrom' => $this->palenessVisibleFrom(),
                'restorations' => [],
                'doctrines' => [],
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
                'groupDungeonOffers' => [],
                'pnjsPresent' => [],
                'trainingDummy' => null,
                'gameHour' => $this->gameTimeService->getHour(),
                'zoneChat' => null,
                'phase' => $this->gameTimeService->getPhase(),
                'settlement' => null,
                'commission' => null,
            ]);
        }

        // La zone courante compte comme decouverte (deverrouille les liaisons rapides).
        $this->zoneTravelService->markZoneVisited($player, $zone);

        // MJ : la liste montre aussi les liaisons desactivees — le contenu en
        // preparation est precisement ce qu'il a besoin d'aller voir.
        $isGameMaster = $player->isGameMaster();
        $connections = $isGameMaster
            ? $this->zoneConnectionRepository->findAllFrom($zone)
            : $this->zoneConnectionRepository->findEnabledFrom($zone);

        // Un MJ incognito ne figure pas dans la liste : c'est tout l'objet du
        // mode — observer une zone sans que sa presence change ce qui s'y passe.
        $playersPresent = $this->gameMasterPolicy->visibleTo(
            $this->entityManager->getRepository(Player::class)->findBy(['currentZone' => $zone], ['name' => 'ASC'], 50),
            $player,
        );

        $poiCounts = $this->countPointsOfInterest($zone);

        $travel = $this->buildTravelProgress($player);

        // Chaque liaison porte la duree **reellement subie**, monture comprise
        // (tache 130) : annoncer la duree de reference alors qu'une monture la
        // raccourcit ferait passer le bonus pour inoperant.
        // ONB-10 : le premier voyage est offert. L'ecran doit l'annoncer **avant**
        // le depart, sinon la faveur passe pour un bug — un trajet de dix
        // minutes qui n'en prend aucune ne se lit pas comme un cadeau.
        $firstTravelOffered = !$isGameMaster && $player->hasFirstTravelOffer();

        $connectionRows = [];
        foreach ($connections as $connection) {
            $connectionRows[] = [
                'connection' => $connection,
                'seconds' => $isGameMaster || $firstTravelOffered
                    ? 0
                    : $this->mountTravelSpeed->effectiveTravelSeconds($player, $connection->getTravelSeconds()),
                'disabled' => !$connection->isEnabled() || !$connection->getToZone()->isEnabled(),
            ];
        }

        return $this->render('game/zone/index.html.twig', [
            'zone' => $zone,
            'connections' => $connectionRows,
            'firstTravelOffered' => $firstTravelOffered,
            'isGameMaster' => $isGameMaster,
            'mount' => $player->getActiveMount(),
            'playersPresent' => $playersPresent,
            'poiCounts' => $poiCounts,
            'actions' => $this->buildActions(),
            'huntTargets' => $this->huntService->getHuntTargets($player, $zone),
            'huntCost' => $this->huntService->getHuntCost(),
            'gatherables' => $this->gatherService->getGatherables($zone, $player),
            'gatherCost' => $this->gatherService->getGatherCost(),
            'palenessVisibleFrom' => $this->palenessVisibleFrom(),
            'restorations' => $this->veinRestorationService->offersFor($player, $zone),
            'doctrines' => $this->settlementDoctrineService->offersFor($player, $zone),
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
            // FOY-04 : le foyer de la zone. `null` quand la zone n'en a pas —
            // afficher une jauge a zero sur Lumiere laisserait croire a un
            // chantier abandonne alors qu'il n'y a simplement rien a batir.
            'settlement' => $this->settlementPanelBuilder->build($zone, $player),
            // RET-02b : la commission de la semaine, et ce qui manque pour la
            // livrer ici. Elle s'affiche dans **toutes** les zones : savoir
            // qu'il faut aller ailleurs fait partie du rendez-vous.
            'commission' => $this->buildCommission($player, $zone),
            'expedition' => $this->buildExpedition($player, $zone),
            'zoneEvents' => $this->buildZoneEvents($player, $zone),
            'zoneBoss' => $this->buildZoneBoss($zone),
            'groupDungeon' => $this->buildGroupDungeon($player),
            'groupDungeonOffers' => $this->buildGroupDungeonOffers($player, $zone),
            // Les PNJ presents dans la zone (ZON-27) : depuis la suppression des
            // overlays carte, l'ecran de zone est le seul endroit d'ou les
            // atteindre — sans lui, les boutiques sont injoignables.
            // ECO-12 : les echoppes tenues par des joueurs, a cote des PNJ.
            // Une vitrine invisible ne sert a rien — et l'achat exige d'etre
            // sur place, donc c'est ici qu'elle doit apparaitre.
            'shopsPresent' => $this->playerShopRepository->findOpenInZone($zone),
            // Le plafond etait a 20, et le tri est alphabetique : au Fanal, la
            // maitresse d'armes s'appelle Ysold. Un habitant tombe donc hors
            // liste par la seule vertu de son initiale — et personne ne peut le
            // deviner, puisqu'une liste tronquee ressemble a une liste complete.
            // Le plafond reste (une zone ne doit pas pouvoir noyer l'ecran),
            // mais au-dessus de ce que la plus peuplee des zones porte.
            'pnjsPresent' => $this->entityManager
                ->getRepository(Pnj::class)
                ->findBy(['zone' => $zone], ['name' => 'ASC'], self::MAX_PNJS_LISTED),
            // Le mannequin de l'acte I, quand l'etape en cours le reclame — et
            // jamais autrement. Il n'appartient a aucune zone (il n'existe que
            // le temps du combat) : c'est la quete qui l'amene ici, pas le lieu.
            'trainingDummy' => $this->trainingDummyOffer->pendingFor($player),
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

    /**
     * Ouvrir un chantier de restauration sur un filon pali (FOY-12).
     *
     * Le chantier ne coute **aucune energie** : ce n'est pas un geste de
     * personnage mais un acte de gouvernement, paye sur le tresor de la guilde.
     * Le facturer au budget d'action du joueur qui clique reviendrait a le lui
     * faire payer deux fois.
     */
    #[Route('/game/zone/restore/{slug}', name: 'app_game_zone_restore', methods: ['POST'], requirements: ['slug' => '[a-z0-9\-]+'])]
    public function restore(string $slug, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('restore_' . $slug, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        $zone = $this->resolveZone($player);
        if (null === $zone) {
            $this->addFlash('error', 'game.zone.gather.error.no_zone');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $restoration = $this->veinRestorationService->open($player, $zone, $slug);
        } catch (VeinRestorationException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_game_zone');
        }

        $this->addFlash('restoration_result', [
            'key' => 'game.zone.restoration.opened',
            'params' => ['%cost%' => $restoration->getCostGils()],
        ]);

        return $this->redirectToRoute('app_game_zone');
    }

    /**
     * Doter le foyer d'un atelier de doctrine (FOY-13).
     *
     * Comme la restauration, c'est un acte de gouvernement paye sur le tresor
     * de la guilde, et non un geste de personnage : aucune energie d'action.
     */
    #[Route('/game/zone/doctrine/{doctrine}', name: 'app_game_zone_doctrine', methods: ['POST'], requirements: ['doctrine' => '[a-z]+'])]
    public function doctrine(string $doctrine, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('doctrine_' . $doctrine, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        $chosen = SettlementDoctrine::tryFrom($doctrine);
        $zone = $this->resolveZone($player);
        if (null === $chosen || null === $zone) {
            $this->addFlash('error', 'game.zone.doctrine.error.no_settlement');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $this->settlementDoctrineService->adopt($player, $zone, $chosen);
        } catch (SettlementDoctrineException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_game_zone');
        }

        $this->addFlash('success', 'game.zone.doctrine.adopted.' . $chosen->value);

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

        $present = $this->gameMasterPolicy->visibleTo(
            $this->entityManager->getRepository(Player::class)->findBy(['currentZone' => $zone], ['name' => 'ASC'], 50),
            $player,
        );

        $players = array_map(static fn (Player $p): array => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'self' => $p->getId() === $player->getId(),
            'gameMaster' => $p->isGameMaster(),
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
     * La commission de la semaine, vue depuis cette zone (RET-02b).
     *
     * Le blocage est rendu tel quel : un bouton grise sans explication est la
     * facon la plus sure de faire croire a un bug. « Il faut y aller » est une
     * information utile ; « impossible » ne l'est pas.
     *
     * @return array<string, mixed>|null
     */
    private function buildCommission(Player $player, Zone $zone): ?array
    {
        $commission = $this->commissionDelivery->current($player);
        if (null === $commission) {
            return null;
        }

        $deliveryZone = $commission->getDeliveryZone();

        return [
            'commission' => $commission,
            'deliveryZone' => $deliveryZone,
            'here' => null !== $deliveryZone && $deliveryZone->getId() === $zone->getId(),
            'blocker' => $this->commissionDelivery->blocker($player, $commission),
            'rewards' => WeeklyCommissionReward::ordered(),
        ];
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
     * Donjons de groupe proposes par la zone courante.
     *
     * Sans ce point d'entree, le moteur de donjon de groupe (ZON-19/20) restait
     * inatteignable : aucun ecran n'exposait `app_game_zone_dungeon_launch`.
     *
     * Le motif de blocage vient de `GroupDungeonService::getLaunchBlocker()`, la
     * meme source que celle appliquee au POST — l'ecran ne peut donc pas proposer
     * un lancement que le service refuserait.
     *
     * @return list<array<string, mixed>>
     */
    private function buildGroupDungeonOffers(Player $player, Zone $zone): array
    {
        // Un run actif occupe deja l'ecran : rien a proposer.
        if (null !== $this->groupDungeonService->getActiveRunForPlayer($player)) {
            return [];
        }

        $offers = [];
        foreach ($this->groupDungeonService->findOfferedInZone($zone) as $dungeon) {
            $blocker = $this->groupDungeonService->getLaunchBlocker($player, $dungeon);
            $offers[] = [
                'id' => $dungeon->getId(),
                // L'entite, pas son nom : la vue la localise via les filtres
                // `localized_dungeon_name` / `localized_dungeon_description`.
                'dungeon' => $dungeon,
                'maxPlayers' => $dungeon->getMaxPlayers(),
                'requiredExperience' => $dungeon->getRequiredExperience(),
                // DON-04 : l'apercu de butin se **derive** de la table reelle
                // — la meme lecture que le tirage (`dungeonPaliers`), donc
                // impossible a desynchroniser. Plus aucun texte libre.
                'lootMateria' => implode('-', array_map(
                    static fn (int $palier): string => 'm' . $palier,
                    MateriaLootTable::dungeonPaliers($dungeon->getZone()?->getTier() ?? 1),
                )),
                'canLaunch' => null === $blocker,
                'blocker' => $blocker,
            ];
        }

        return $offers;
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
     * Etat du voyage en cours, prepare pour la barre de progression.
     *
     * `totalSeconds` vaut 0 quand le depart est inconnu — voyage entame avant
     * l'ajout de `travel_started_at`. La vue affiche alors le decompte seul :
     * une barre sans duree totale ne pourrait que mentir sur l'avancement.
     *
     * @return array{destination: Zone, startedAt: ?\DateTimeImmutable, arrivesAt: \DateTimeImmutable, totalSeconds: int, elapsedSeconds: int, remainingSeconds: int}|null
     */
    private function buildTravelProgress(Player $player): ?array
    {
        $destination = $player->getTravelToZone();
        $arrivesAt = $player->getTravelArrivesAt();
        if (null === $destination || null === $arrivesAt) {
            return null;
        }

        $startedAt = $player->getTravelStartedAt();
        $remaining = max(0, $arrivesAt->getTimestamp() - time());
        $total = null !== $startedAt ? max(0, $arrivesAt->getTimestamp() - $startedAt->getTimestamp()) : 0;

        return [
            'destination' => $destination,
            'startedAt' => $startedAt,
            'arrivesAt' => $arrivesAt,
            'totalSeconds' => $total,
            'elapsedSeconds' => max(0, $total - $remaining),
            'remainingSeconds' => $remaining,
        ];
    }

    /**
     * Zone courante du joueur, avec rattrapage : sync depuis la carte, puis
     * fallback sur le hub (meme regle que le backfill de migration ZON-03).
     */
    private function resolveZone(Player $player): ?Zone
    {
        return $this->playerZoneSynchronizer->resolveOrAssign($player, true);
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

    /**
     * Seuil a partir duquel la Paleur se voit (FOY-11).
     *
     * Il vit dans `settlements.yaml` et non dans le gabarit : sous ce seuil, la
     * Paleur existe mais ne fait rien, et un filon normalement frequente ne doit
     * pas porter un etat d'alerte pour une trace que personne ne ressent.
     */
    private function palenessVisibleFrom(): float
    {
        return $this->settlementLoader->load()['paleness']['visible_from'];
    }
}
