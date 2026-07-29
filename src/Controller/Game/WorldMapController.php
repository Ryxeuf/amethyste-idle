<?php

namespace App\Controller\Game;

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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Carte du monde illustree (pivot PBBG, ZON-16).
 *
 * Une illustration de fond, et par-dessus un rendu SVG schematique (aucun moteur
 * de rendu type PixiJS) : les zones placees (`Zone.mapX`/`mapY`) apparaissent
 * comme des noeuds relies par leurs connexions, colores par biome. Les zones
 * decouvertes sont cliquables — sur leur pastille, et sur leur contour
 * (`Zone.mapShape`) quand elles en ont un ; un clic vers une zone adjacente
 * lance le voyage (ZON-06). Indicateurs : evenement de zone actif (ZON-15) et
 * expedition en cours (ZON-13).
 *
 * **Le brouillard de guerre se decide ici, pas dans le gabarit.** Trois etats :
 * parcourue (`discovered`), reperee (`scouted` — voisine d'une zone parcourue),
 * inconnue. Le contour n'est emis que pour les deux premiers : la brume qui se
 * lit dans le HTML n'est pas une brume.
 */
class WorldMapController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly ZoneRepository $zoneRepository,
        private readonly ZoneConnectionRepository $zoneConnectionRepository,
        private readonly PlayerVisitedZoneRepository $visitedZoneRepository,
        private readonly ZoneTravelService $zoneTravelService,
        private readonly ZoneEventService $zoneEventService,
        private readonly ExpeditionService $expeditionService,
        private readonly SettlementRepository $settlementRepository,
        private readonly VassalageService $vassalage,
    ) {
    }

    #[Route('/game/world-map', name: 'app_game_world_map', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        // Regle une arrivee eventuelle pour refleter la zone courante a jour.
        $this->zoneTravelService->settleArrival($player);
        $currentZone = $player->getCurrentZone();

        $placedZones = array_values(array_filter(
            $this->zoneRepository->findAllEnabled(),
            static fn (Zone $zone): bool => $zone->hasMapPosition(),
        ));

        $visitedIds = $this->visitedZoneRepository->findVisitedZoneIds($player);
        $expedition = $this->expeditionService->getActive($player);
        $expeditionZoneId = null !== $expedition ? $expedition->getZone()->getId() : null;

        // Connexions sortantes, lues une fois par zone : elles servent trois
        // fois — le voyage depuis la zone courante, les aretes du graphe, et
        // le troisieme etat du brouillard (« reperee »).
        /** @var array<int, list<ZoneConnection>> $connectionsFrom */
        $connectionsFrom = [];
        foreach ($placedZones as $zone) {
            $connectionsFrom[$zone->getId()] = $this->zoneConnectionRepository->findEnabledFrom($zone);
        }
        // La zone courante peut n'etre pas placee sur la carte : ses connexions
        // n'ont alors pas ete lues ci-dessus, et le voyage doit rester possible.
        if (null !== $currentZone && !isset($connectionsFrom[$currentZone->getId()])) {
            $connectionsFrom[$currentZone->getId()] = $this->zoneConnectionRepository->findEnabledFrom($currentZone);
        }

        // Connexions traversables depuis la zone courante : cible -> id de connexion.
        $reachable = [];
        if (null !== $currentZone) {
            foreach ($connectionsFrom[$currentZone->getId()] as $connection) {
                $reachable[$connection->getToZone()->getId()] = $connection->getId();
            }
        }

        // FOY-09 : rang et suzeraine, lus une fois pour toute la carte.
        $settlementRanks = [];
        $overlords = [];
        foreach ($placedZones as $zone) {
            $settlement = $this->settlementRepository->findOneByZone($zone);
            if (null === $settlement) {
                continue;
            }

            $settlementRanks[$zone->getId()] = $settlement->getRank()->value;
            $overlord = $this->vassalage->overlordOf($settlement);
            if (null !== $overlord) {
                $overlords[$zone->getId()] = $overlord->getZone()->getName();
            }
        }

        $neighbours = [];
        foreach ($connectionsFrom as $zoneId => $connections) {
            $neighbours[$zoneId] = array_map(
                static fn (ZoneConnection $connection): int => $connection->getToZone()->getId(),
                $connections,
            );
        }

        $discoveredIds = $visitedIds;
        if (null !== $currentZone && !\in_array($currentZone->getId(), $discoveredIds, true)) {
            $discoveredIds[] = $currentZone->getId();
        }

        // Zones reperees : celles ou l'on peut aller depuis une zone deja
        // parcourue. On part des aretes **sortantes des zones connues**, et non
        // des aretes sortantes de la zone examinee : ces dernieres ne diraient
        // « reperee » que si le graphe portait aussi la liaison retour, ce qui
        // est vrai des connexions bidirectionnelles du monde livre mais que
        // rien n'impose au format.
        $scoutedIds = [];
        foreach ($discoveredIds as $discoveredId) {
            foreach ($neighbours[$discoveredId] ?? [] as $neighbourId) {
                $scoutedIds[$neighbourId] = true;
            }
        }

        $nodes = [];
        foreach ($placedZones as $zone) {
            $id = $zone->getId();
            $discovered = \in_array($id, $discoveredIds, true);
            // « Reperee » : jamais visitee, mais joignable depuis une zone qui
            // l'a ete. On en connait la place et la forme, pas le contenu —
            // c'est le « rumeur -> reperee -> cartographiee » de
            // GAME_ZONE_ACTIONS, rendu ici en trois epaisseurs de brume.
            $scouted = !$discovered && isset($scoutedIds[$id]);
            $nodes[] = [
                'id' => $id,
                'slug' => $zone->getSlug(),
                'name' => $zone->getName(),
                'type' => $zone->getType(),
                'safe' => $zone->isSafe(),
                'x' => $zone->getMapX(),
                'y' => $zone->getMapY(),
                // Le contour n'est emis que pour ce que le joueur situe deja.
                // Le taire pour le reste n'est pas une precaution decorative :
                // le brouillard se lirait sinon dans le HTML, et la carte se
                // devoilerait a qui ouvre l'inspecteur.
                'shape' => ($discovered || $scouted) ? $zone->getMapShape() : null,
                'discovered' => $discovered,
                'scouted' => $scouted,
                'current' => null !== $currentZone && $id === $currentZone->getId(),
                'hasEvent' => $discovered && [] !== $this->zoneEventService->getActiveEventsForZone($zone),
                'expedition' => $id === $expeditionZoneId,
                'travelConnectionId' => $reachable[$id] ?? null,
                // FOY-09 : le rang du foyer et sa suzeraine eventuelle. La carte
                // est le seul ecran d'ou la relation se **voit** — sur l'ecran de
                // zone on lit une phrase, ici on lit une geographie.
                'settlementRank' => $discovered ? $settlementRanks[$id] ?? null : null,
                'overlord' => $discovered ? $overlords[$id] ?? null : null,
            ];
        }

        // Aretes entre zones placees (une seule fois par paire).
        $edges = [];
        $seen = [];
        $positions = [];
        foreach ($placedZones as $zone) {
            $positions[$zone->getId()] = ['x' => $zone->getMapX(), 'y' => $zone->getMapY()];
        }
        foreach ($placedZones as $zone) {
            // On repart de l'adjacence deja lue : la refaire ici doublait les
            // requetes de connexion, une par zone placee.
            foreach ($neighbours[$zone->getId()] ?? [] as $toId) {
                if (!isset($positions[$toId])) {
                    continue;
                }
                $key = min($zone->getId(), $toId) . '-' . max($zone->getId(), $toId);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $edges[] = [
                    'x1' => $positions[$zone->getId()]['x'],
                    'y1' => $positions[$zone->getId()]['y'],
                    'x2' => $positions[$toId]['x'],
                    'y2' => $positions[$toId]['y'],
                ];
            }
        }

        return $this->render('game/zone/world_map.html.twig', [
            'nodes' => $nodes,
            'edges' => $edges,
            'hasCurrent' => null !== $currentZone,
        ]);
    }
}
