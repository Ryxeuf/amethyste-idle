<?php

namespace App\Controller\Api;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\GameEngine\Map\MovementCalculator;
use App\GameEngine\Map\SpriteConfigProvider;
use App\GameEngine\Movement\PlayerMoveProcessor;
use App\GameEngine\Player\PnjDialogParser;
use App\Helper\CellHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/map')]
class MapApiController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly Packages $packages,
        private readonly SpriteConfigProvider $spriteConfigProvider,
    ) {
    }

    #[Route('/config', name: 'api_map_config', methods: ['GET'])]
    public function config(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        $map = $player->getMap();

        $tilesets = $this->extractTilesets($map);

        return $this->json([
            'tileSize' => 32,
            'viewRadius' => 15,
            'mapId' => $map->getId(),
            'tilesets' => $tilesets,
            'sprites' => $this->spriteConfigProvider->getFullConfig(),
        ]);
    }

    #[Route('/cells', name: 'api_map_cells', methods: ['GET'])]
    public function cells(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $x = $request->query->getInt('x', 0);
        $y = $request->query->getInt('y', 0);
        $radius = $request->query->getInt('radius', 15);
        $mapId = $request->query->getInt('mapId', $player->getMap()->getId());

        $map = $this->entityManager->getRepository(Map::class)->find($mapId);
        if (!$map) {
            return $this->json(['error' => 'Map not found'], 404);
        }

        $cells = $this->loadRawCells($map, $x, $y, $radius);

        return $this->json(['cells' => $cells]);
    }

    #[Route('/entities', name: 'api_map_entities', methods: ['GET'])]
    public function entities(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        $map = $player->getMap();
        $radius = $request->query->getInt('radius', 0);

        $pCoords = explode('.', $player->getCoordinates() ?? '0.0');
        $px = (int) ($pCoords[0] ?? 0);
        $py = (int) ($pCoords[1] ?? 0);

        $players = [];
        foreach ($this->entityManager->getRepository(Player::class)->findBy(['map' => $map]) as $p) {
            $coords = explode('.', $p->getCoordinates() ?? '0.0');
            $ex = (int) ($coords[0] ?? 0);
            $ey = (int) ($coords[1] ?? 0);
            if ($radius > 0 && (abs($ex - $px) > $radius || abs($ey - $py) > $radius)) {
                continue;
            }
            $players[] = [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'x' => $ex,
                'y' => $ey,
                'self' => $p->getId() === $player->getId(),
                'spriteKey' => 'player_default',
            ];
        }

        $mobs = [];
        foreach ($this->entityManager->getRepository(Mob::class)->findBy(['map' => $map]) as $mob) {
            $coords = explode('.', $mob->getCoordinates() ?? '0.0');
            $ex = (int) ($coords[0] ?? 0);
            $ey = (int) ($coords[1] ?? 0);
            if ($radius > 0 && (abs($ex - $px) > $radius || abs($ey - $py) > $radius)) {
                continue;
            }
            $spriteKey = 'mob_' . $mob->getMonster()->getSlug();
            $allSprites = $this->spriteConfigProvider->getFullConfig();
            if (!isset($allSprites[$spriteKey])) {
                $spriteKey = 'mob_zombie';
            }

            $mobs[] = [
                'id' => $mob->getId(),
                'name' => $mob->getMonster()->getName(),
                'slug' => $mob->getMonster()->getSlug(),
                'level' => $mob->getLevel(),
                'x' => $ex,
                'y' => $ey,
                'spriteKey' => $spriteKey,
            ];
        }

        $pnjs = [];
        foreach ($this->entityManager->getRepository(Pnj::class)->findBy(['map' => $map]) as $pnj) {
            $coords = explode('.', $pnj->getCoordinates() ?? '0.0');
            $ex = (int) ($coords[0] ?? 0);
            $ey = (int) ($coords[1] ?? 0);
            if ($radius > 0 && (abs($ex - $px) > $radius || abs($ey - $py) > $radius)) {
                continue;
            }
            $spriteKey = 'pnj_' . $pnj->getClassType();
            // Fallback to default if sprite key doesn't exist in config
            $allSprites = $this->spriteConfigProvider->getFullConfig();
            if (!isset($allSprites[$spriteKey])) {
                $spriteKey = 'pnj_default';
            }

            $pnjs[] = [
                'id' => $pnj->getId(),
                'name' => $pnj->getName(),
                'x' => $ex,
                'y' => $ey,
                'spriteKey' => $spriteKey,
            ];
        }

        $portals = [];
        foreach ($this->entityManager->getRepository(ObjectLayer::class)->findBy([
            'map' => $map,
            'type' => ObjectLayer::TYPE_PORTAL,
        ]) as $portal) {
            $coords = explode('.', $portal->getCoordinates() ?? '0.0');
            $ex = (int) ($coords[0] ?? 0);
            $ey = (int) ($coords[1] ?? 0);
            if ($radius > 0 && (abs($ex - $px) > $radius || abs($ey - $py) > $radius)) {
                continue;
            }
            $portals[] = [
                'id' => $portal->getId(),
                'name' => $portal->getName(),
                'x' => $ex,
                'y' => $ey,
            ];
        }

        $harvestSpots = [];
        foreach ($this->entityManager->getRepository(ObjectLayer::class)->findBy([
            'map' => $map,
            'type' => ObjectLayer::TYPE_HARVEST_SPOT,
        ]) as $spot) {
            $coords = explode('.', $spot->getCoordinates() ?? '0.0');
            $ex = (int) ($coords[0] ?? 0);
            $ey = (int) ($coords[1] ?? 0);
            if ($radius > 0 && (abs($ex - $px) > $radius || abs($ey - $py) > $radius)) {
                continue;
            }
            $harvestSpots[] = [
                'id' => $spot->getId(),
                'name' => $spot->getName(),
                'slug' => $spot->getSlug(),
                'x' => $ex,
                'y' => $ey,
                'available' => $spot->isAvailable(),
                'toolType' => $spot->getRequiredToolType(),
                'respawnDelay' => $spot->getRespawnDelay(),
                'remainingSeconds' => $spot->getRemainingRespawnSeconds(),
            ];
        }

        return $this->json([
            'players' => $players,
            'mobs' => $mobs,
            'pnjs' => $pnjs,
            'portals' => $portals,
            'harvestSpots' => $harvestSpots,
        ]);
    }

    #[Route('/move', name: 'api_map_move', methods: ['POST'])]
    public function move(
        Request $request,
        MovementCalculator $movementCalculator,
        PlayerMoveProcessor $playerMoveProcessor,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $data = json_decode($request->getContent(), true) ?? [];
        $targetX = (int) ($data['targetX'] ?? 0);
        $targetY = (int) ($data['targetY'] ?? 0);
        $fromX = isset($data['fromX']) ? (int) $data['fromX'] : null;
        $fromY = isset($data['fromY']) ? (int) $data['fromY'] : null;

        $coords = explode('.', $player->getCoordinates() ?? '0.0');
        $currentX = (int) ($coords[0] ?? 0);
        $currentY = (int) ($coords[1] ?? 0);

        if ($fromX !== null && $fromY !== null) {
            $currentX = $fromX;
            $currentY = $fromY;
            $player->setCoordinates(CellHelper::stringifyCoordinates($fromX, $fromY));
            $this->entityManager->flush();
        }

        try {
            $movementCalculator->loadMap($player->getMap()->getId());
            $movements = $movementCalculator->calculateMovement($currentX, $currentY, $targetX, $targetY);
        } catch (\Exception $e) {
            return $this->json(['error' => 'No path found', 'message' => $e->getMessage()], 400);
        }

        if (empty($movements)) {
            return $this->json(['path' => []]);
        }

        $traversedPath = $playerMoveProcessor->processMove($player, $movements);

        $path = array_map(fn (array $cell) => [
            'x' => (int) $cell['x'],
            'y' => (int) $cell['y'],
        ], $traversedPath);

        $response = ['path' => $path];

        // Include fight info if triggered
        $fight = $playerMoveProcessor->getTriggeredFight();
        if ($fight) {
            $response['fight'] = [
                'id' => $fight->getId(),
            ];
        }

        // Include portal info if triggered
        $portal = $playerMoveProcessor->getTriggeredPortal();
        if ($portal) {
            $response['portal'] = [
                'destinationMapId' => $portal->getDestinationMapId(),
                'destinationCoordinates' => $portal->getDestinationCoordinates(),
            ];
        }

        return $this->json($response);
    }

    #[Route('/teleport', name: 'api_map_teleport', methods: ['POST'])]
    public function teleport(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $data = json_decode($request->getContent(), true) ?? [];
        $destinationMapId = (int) ($data['mapId'] ?? 0);
        $destinationCoordinates = $data['coordinates'] ?? null;

        if (!$destinationMapId || !$destinationCoordinates) {
            return $this->json(['error' => 'Missing mapId or coordinates'], 400);
        }

        $destinationMap = $this->entityManager->getRepository(Map::class)->find($destinationMapId);
        if (!$destinationMap) {
            return $this->json(['error' => 'Destination map not found'], 404);
        }

        // Verify the player is on a portal
        $portal = $this->entityManager->getRepository(ObjectLayer::class)->findOneBy([
            'coordinates' => $player->getCoordinates(),
            'map' => $player->getMap(),
            'type' => ObjectLayer::TYPE_PORTAL,
        ]);

        if (!$portal) {
            return $this->json(['error' => 'No portal at current position'], 403);
        }

        // Teleport the player
        $player->setLastCoordinates($player->getCoordinates());
        $player->setMap($destinationMap);
        $player->setCoordinates($destinationCoordinates);
        $player->setIsMoving(false);

        $this->entityManager->flush();

        $coords = explode('.', $destinationCoordinates);

        return $this->json([
            'success' => true,
            'mapId' => $destinationMap->getId(),
            'x' => (int) ($coords[0] ?? 0),
            'y' => (int) ($coords[1] ?? 0),
        ]);
    }

    #[Route('/pnj/{id}/dialog', name: 'api_map_pnj_dialog', methods: ['GET'])]
    public function pnjDialog(int $id, PnjDialogParser $dialogParser): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj) {
            return $this->json(['error' => 'PNJ not found'], 404);
        }

        $dialog = $pnj->getDialog();
        if (empty($dialog)) {
            return $this->json(['sentences' => [], 'pnjName' => $pnj->getName()]);
        }

        $dialogParser->setPnj($pnj);
        $parsedDialog = $dialogParser->parseDialog($dialog);

        return $this->json([
            'sentences' => $parsedDialog,
            'pnjName' => $pnj->getName(),
        ]);
    }

    private const TILESET_COLUMNS = [
        'terrain' => 32,
        'forest' => 16,
        'BaseChip_pipo' => 8,
        'collisions' => 6,
    ];

    private function extractTilesets(Map $map): array
    {
        $tilesets = [];
        $seen = [];

        foreach ($map->getAreas() as $area) {
            $data = $area->getFullDataArray();
            $terrains = $data['terrains'] ?? [];
            foreach ($terrains as $firstGid => $terrain) {
                $gid = (int) ($terrain['firstgid'] ?? $firstGid);
                if (isset($seen[$gid])) {
                    continue;
                }
                $seen[$gid] = true;

                $image = $terrain['image'] ?? 'terrain.png';
                $name = pathinfo($image, PATHINFO_FILENAME);
                $publicPath = $this->packages->getUrl('styles/images/terrain/' . $image);
                $columns = self::TILESET_COLUMNS[$name] ?? (int) ($terrain['columns'] ?? 32);

                $tilesets[] = [
                    'name' => $name,
                    'image' => $publicPath,
                    'columns' => $columns,
                    'tileWidth' => (int) ($terrain['tilewidth'] ?? 32),
                    'tileHeight' => (int) ($terrain['tileheight'] ?? 32),
                    'firstGid' => $gid,
                ];
            }
            if (!empty($tilesets)) {
                break;
            }
        }

        usort($tilesets, fn ($a, $b) => $a['firstGid'] - $b['firstGid']);

        return $tilesets;
    }

    private function loadRawCells(Map $map, int $centerX, int $centerY, int $radius): array
    {
        $minX = $centerX - $radius;
        $maxX = $centerX + $radius;
        $minY = $centerY - $radius;
        $maxY = $centerY + $radius;

        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();
        $cells = [];

        foreach ($map->getAreas() as $area) {
            $areaCoords = explode('.', $area->getCoordinates() ?? '0.0');
            $areaX = (int) ($areaCoords[0] ?? 0);
            $areaY = (int) ($areaCoords[1] ?? 0);

            $areaMinGlobalX = $areaX * $areaWidth;
            $areaMinGlobalY = $areaY * $areaHeight;
            $areaMaxGlobalX = $areaMinGlobalX + $areaWidth - 1;
            $areaMaxGlobalY = $areaMinGlobalY + $areaHeight - 1;

            if ($maxX < $areaMinGlobalX || $minX > $areaMaxGlobalX
                || $maxY < $areaMinGlobalY || $minY > $areaMaxGlobalY) {
                continue;
            }

            $localMinX = max(0, $minX - $areaMinGlobalX);
            $localMaxX = min($areaWidth - 1, $maxX - $areaMinGlobalX);
            $localMinY = max(0, $minY - $areaMinGlobalY);
            $localMaxY = min($areaHeight - 1, $maxY - $areaMinGlobalY);

            $areaData = $area->getFullDataArray();
            $cellsData = $areaData['cells'] ?? [];

            for ($lx = $localMinX; $lx <= $localMaxX; ++$lx) {
                for ($ly = $localMinY; $ly <= $localMaxY; ++$ly) {
                    $cellData = $cellsData[$lx][$ly] ?? null;
                    if ($cellData === null) {
                        continue;
                    }

                    $globalX = $areaMinGlobalX + ($cellData['x'] ?? $lx);
                    $globalY = $areaMinGlobalY + ($cellData['y'] ?? $ly);

                    $layerGids = [];
                    foreach ($cellData['layers'] ?? [] as $layer) {
                        if ($layer === null) {
                            continue;
                        }
                        $gid = ($layer['mapIdx'] ?? 0) + ($layer['idxInMap'] ?? 0);
                        if ($gid > 0) {
                            $layerGids[] = $gid;
                        }
                    }

                    $cells[] = [
                        'x' => $globalX,
                        'y' => $globalY,
                        'l' => $layerGids,
                        'w' => ($cellData['mouvement'] ?? -1) !== -1,
                    ];
                }
            }
        }

        return $cells;
    }
}
