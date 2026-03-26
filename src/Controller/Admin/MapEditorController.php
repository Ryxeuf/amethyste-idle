<?php

namespace App\Controller\Admin;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Pnj;
use App\GameEngine\Terrain\TilesetRegistry;
use App\Helper\CellHelper;
use App\Helper\MapCellValidator;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/maps', name: 'admin_map_')]
#[IsGranted('ROLE_WORLD_BUILDER')]
class MapEditorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TilesetRegistry $tilesetRegistry,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('/{id}/editor', name: 'editor', requirements: ['id' => '\d+'])]
    public function editor(Map $map): Response
    {
        return $this->render('admin/map/editor.html.twig', [
            'map' => $map,
        ]);
    }

    #[Route('/{id}/editor/cells', name: 'editor_cells', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editorCells(Map $map): JsonResponse
    {
        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();

        $cells = [];
        $mapWidth = 0;
        $mapHeight = 0;

        foreach ($map->getAreas() as $area) {
            $areaCoords = explode('.', $area->getCoordinates());
            $areaX = (int) $areaCoords[0];
            $areaY = (int) ($areaCoords[1] ?? 0);

            $areaMinGlobalX = $areaX * $areaWidth;
            $areaMinGlobalY = $areaY * $areaHeight;

            $areaData = $area->getFullDataArray();
            $cellsData = $areaData['cells'] ?? [];

            foreach ($cellsData as $lx => $column) {
                foreach ($column as $ly => $cellData) {
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

                    $movement = $cellData['mouvement'] ?? 0;

                    // Extract border info from slug if available
                    $borders = [0, 0, 0, 0]; // [north, east, south, west]
                    if (isset($cellData['slug'])) {
                        $slugData = CellHelper::getDataFromSlug($cellData['slug']);
                        $borders = [$slugData['north'], $slugData['east'], $slugData['south'], $slugData['west']];
                    }

                    $cells[] = [
                        'x' => $globalX,
                        'y' => $globalY,
                        'l' => $layerGids,
                        'm' => $movement,
                        'b' => $borders, // [north, east, south, west]
                    ];

                    if ($globalX + 1 > $mapWidth) {
                        $mapWidth = $globalX + 1;
                    }
                    if ($globalY + 1 > $mapHeight) {
                        $mapHeight = $globalY + 1;
                    }
                }
            }
        }

        return $this->json([
            'cells' => $cells,
            'mapWidth' => $mapWidth,
            'mapHeight' => $mapHeight,
        ]);
    }

    #[Route('/{id}/editor/tilesets', name: 'editor_tilesets', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editorTilesets(Map $map): JsonResponse
    {
        return $this->json(['tilesets' => $this->tilesetRegistry->getTilesetsForApi()]);
    }

    #[Route('/{id}/editor/entities', name: 'editor_entities', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editorEntities(Map $map): JsonResponse
    {
        $mobs = [];
        foreach ($this->em->getRepository(Mob::class)->findBy(['map' => $map]) as $mob) {
            $coords = explode('.', $mob->getCoordinates());
            $mobs[] = [
                'id' => $mob->getId(),
                'name' => $mob->getMonster()->getName(),
                'level' => $mob->getLevel(),
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
            ];
        }

        $pnjs = [];
        foreach ($this->em->getRepository(Pnj::class)->findBy(['map' => $map]) as $pnj) {
            $coords = explode('.', $pnj->getCoordinates());
            $pnjs[] = [
                'id' => $pnj->getId(),
                'name' => $pnj->getName(),
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
            ];
        }

        $portals = [];
        foreach ($this->em->getRepository(ObjectLayer::class)->findBy(['map' => $map, 'type' => ObjectLayer::TYPE_PORTAL]) as $portal) {
            $coords = explode('.', $portal->getCoordinates());
            $portals[] = [
                'id' => $portal->getId(),
                'name' => $portal->getName(),
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
                'destMapId' => $portal->getDestinationMapId(),
                'destCoords' => $portal->getDestinationCoordinates(),
            ];
        }

        $harvestSpots = [];
        foreach ($this->em->getRepository(ObjectLayer::class)->findBy(['map' => $map, 'type' => ObjectLayer::TYPE_HARVEST_SPOT]) as $spot) {
            $coords = explode('.', $spot->getCoordinates());
            $harvestSpots[] = [
                'id' => $spot->getId(),
                'name' => $spot->getName(),
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
            ];
        }

        $craftStations = [];
        $craftTypes = [ObjectLayer::TYPE_FORGE, ObjectLayer::TYPE_TANNERY, ObjectLayer::TYPE_ALCHEMY_LAB, ObjectLayer::TYPE_JEWELER_BENCH];
        foreach ($this->em->getRepository(ObjectLayer::class)->findBy(['map' => $map]) as $obj) {
            if (in_array($obj->getType(), $craftTypes, true)) {
                $coords = explode('.', $obj->getCoordinates());
                $craftStations[] = [
                    'id' => $obj->getId(),
                    'name' => $obj->getName(),
                    'type' => $obj->getType(),
                    'x' => (int) $coords[0],
                    'y' => (int) ($coords[1] ?? 0),
                ];
            }
        }

        return $this->json([
            'mobs' => $mobs,
            'pnjs' => $pnjs,
            'portals' => $portals,
            'harvestSpots' => $harvestSpots,
            'craftStations' => $craftStations,
        ]);
    }

    #[Route('/{id}/editor/update-cell', name: 'editor_update_cell', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateCell(Request $request, Map $map): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['x'], $data['y'], $data['movement'])) {
            return $this->json(['error' => 'Missing parameters'], 400);
        }

        $globalX = (int) $data['x'];
        $globalY = (int) $data['y'];
        $newMovement = (int) $data['movement'];

        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();

        $areaCoordX = intval($globalX / $areaWidth);
        $areaCoordY = intval($globalY / $areaHeight);
        $areaCoordStr = $areaCoordX . '.' . $areaCoordY;

        $area = null;
        foreach ($map->getAreas() as $a) {
            if ($a->getCoordinates() === $areaCoordStr) {
                $area = $a;
                break;
            }
        }

        if (!$area) {
            return $this->json(['error' => 'Area not found for coordinates'], 404);
        }

        $localX = $globalX % $areaWidth;
        $localY = $globalY % $areaHeight;

        $areaData = $area->getFullDataArray();
        if (!isset($areaData['cells'][$localX][$localY])) {
            return $this->json(['error' => 'Cell not found'], 404);
        }

        $areaData['cells'][$localX][$localY]['mouvement'] = $newMovement;
        $area->setFullData(json_encode($areaData));
        $this->em->flush();

        $this->adminLogger->log(
            'update',
            'Cell',
            null,
            sprintf('Collision %d,%d -> %d sur %s', $globalX, $globalY, $newMovement, $map->getName())
        );

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/editor/update-cells', name: 'editor_update_cells', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateCells(Request $request, Map $map): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['cells']) || !is_array($data['cells'])) {
            return $this->json(['error' => 'Missing cells parameter'], 400);
        }

        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();

        $areasCache = [];
        foreach ($map->getAreas() as $a) {
            $areasCache[$a->getCoordinates()] = $a;
        }

        $areaDataCache = [];
        $count = 0;

        foreach ($data['cells'] as $cell) {
            if (!isset($cell['x'], $cell['y'], $cell['movement'])) {
                continue;
            }

            $globalX = (int) $cell['x'];
            $globalY = (int) $cell['y'];
            $newMovement = (int) $cell['movement'];

            $areaCoordStr = intval($globalX / $areaWidth) . '.' . intval($globalY / $areaHeight);

            if (!isset($areasCache[$areaCoordStr])) {
                continue;
            }

            $area = $areasCache[$areaCoordStr];

            if (!isset($areaDataCache[$areaCoordStr])) {
                $areaDataCache[$areaCoordStr] = $area->getFullDataArray();
            }

            $localX = $globalX % $areaWidth;
            $localY = $globalY % $areaHeight;

            if (isset($areaDataCache[$areaCoordStr]['cells'][$localX][$localY])) {
                $areaDataCache[$areaCoordStr]['cells'][$localX][$localY]['mouvement'] = $newMovement;
                ++$count;
            }
        }

        foreach ($areaDataCache as $coordStr => $areaData) {
            $areasCache[$coordStr]->setFullData(json_encode($areaData));
        }

        $this->em->flush();

        $this->adminLogger->log(
            'update',
            'Cell',
            null,
            sprintf('%d cellules modifiees sur %s', $count, $map->getName())
        );

        return $this->json(['success' => true, 'count' => $count]);
    }

    #[Route('/{id}/editor/update-borders', name: 'editor_update_borders', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateBorders(Request $request, Map $map): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['cells']) || !is_array($data['cells'])) {
            return $this->json(['error' => 'Missing cells parameter'], 400);
        }

        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();

        $areasCache = [];
        foreach ($map->getAreas() as $a) {
            $areasCache[$a->getCoordinates()] = $a;
        }

        $areaDataCache = [];
        $count = 0;

        foreach ($data['cells'] as $cell) {
            if (!isset($cell['x'], $cell['y'], $cell['borders'])) {
                continue;
            }

            $globalX = (int) $cell['x'];
            $globalY = (int) $cell['y'];
            $borders = $cell['borders']; // [north, east, south, west]

            $areaCoordStr = intval($globalX / $areaWidth) . '.' . intval($globalY / $areaHeight);

            if (!isset($areasCache[$areaCoordStr])) {
                continue;
            }

            $area = $areasCache[$areaCoordStr];

            if (!isset($areaDataCache[$areaCoordStr])) {
                $areaDataCache[$areaCoordStr] = $area->getFullDataArray();
            }

            $localX = $globalX % $areaWidth;
            $localY = $globalY % $areaHeight;

            if (isset($areaDataCache[$areaCoordStr]['cells'][$localX][$localY]['slug'])) {
                $cellData = $areaDataCache[$areaCoordStr]['cells'][$localX][$localY];
                $movement = $cellData['mouvement'] ?? 0;
                $n = (int) ($borders[0] ?? 0);
                $e = (int) ($borders[1] ?? 0);
                $s = (int) ($borders[2] ?? 0);
                $w = (int) ($borders[3] ?? 0);

                $areaDataCache[$areaCoordStr]['cells'][$localX][$localY]['slug'] =
                    $localX . '.' . $localY . '_' . $movement . '_' . $n . ':' . $e . ':' . $s . ':' . $w;
                ++$count;
            }
        }

        foreach ($areaDataCache as $coordStr => $areaData) {
            $areasCache[$coordStr]->setFullData(json_encode($areaData));
        }

        $this->em->flush();

        $this->adminLogger->log(
            'update',
            'Border',
            null,
            sprintf('%d bordures modifiees sur %s', $count, $map->getName())
        );

        return $this->json(['success' => true, 'count' => $count]);
    }

    #[Route('/{id}/editor/paint-tiles', name: 'editor_paint_tiles', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function paintTiles(Request $request, Map $map): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['cells']) || !is_array($data['cells'])) {
            return $this->json(['error' => 'Missing cells parameter'], 400);
        }

        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();

        $areasCache = [];
        foreach ($map->getAreas() as $a) {
            $areasCache[$a->getCoordinates()] = $a;
        }

        $areaDataCache = [];
        $count = 0;

        foreach ($data['cells'] as $cell) {
            if (!isset($cell['x'], $cell['y'], $cell['layer'], $cell['gid'])) {
                continue;
            }

            $globalX = (int) $cell['x'];
            $globalY = (int) $cell['y'];
            $layer = (int) $cell['layer'];
            $gid = (int) $cell['gid'];

            if ($layer < 0 || $layer > 3) {
                continue;
            }

            $areaCoordStr = intval($globalX / $areaWidth) . '.' . intval($globalY / $areaHeight);

            if (!isset($areasCache[$areaCoordStr])) {
                continue;
            }

            $area = $areasCache[$areaCoordStr];

            if (!isset($areaDataCache[$areaCoordStr])) {
                $areaDataCache[$areaCoordStr] = $area->getFullDataArray();
            }

            $localX = $globalX % $areaWidth;
            $localY = $globalY % $areaHeight;

            if (!isset($areaDataCache[$areaCoordStr]['cells'][$localX][$localY])) {
                continue;
            }

            $cellData = &$areaDataCache[$areaCoordStr]['cells'][$localX][$localY];
            if (!isset($cellData['layers'])) {
                $cellData['layers'] = [null, null, null, null];
            }

            // Ensure layers array has enough entries
            while (\count($cellData['layers']) <= $layer) {
                $cellData['layers'][] = null;
            }

            if ($gid > 0) {
                $tileset = $this->tilesetRegistry->getTilesetForGid($gid);
                if ($tileset) {
                    $cellData['layers'][$layer] = [
                        'mapIdx' => $tileset['firstGid'],
                        'idxInMap' => $gid - $tileset['firstGid'],
                    ];
                }
            } else {
                $cellData['layers'][$layer] = null;
            }

            ++$count;
        }

        foreach ($areaDataCache as $coordStr => $areaData) {
            $areasCache[$coordStr]->setFullData(json_encode($areaData));
        }

        $this->em->flush();

        $this->adminLogger->log(
            'update',
            'Tile',
            null,
            sprintf('%d tiles peintes sur %s', $count, $map->getName())
        );

        return $this->json(['success' => true, 'count' => $count]);
    }

    #[Route('/{id}/editor/delete-entity', name: 'editor_delete_entity', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteEntity(Request $request, Map $map): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['entityType'], $data['entityId'])) {
            return $this->json(['error' => 'Missing parameters'], 400);
        }

        $entityType = $data['entityType'];
        $entityId = (int) $data['entityId'];

        $entity = match ($entityType) {
            'mob' => $this->em->getRepository(Mob::class)->find($entityId),
            'harvestSpot', 'portal', 'craftStation' => $this->em->getRepository(ObjectLayer::class)->find($entityId),
            'pnj' => $this->em->getRepository(Pnj::class)->find($entityId),
            default => null,
        };

        if (!$entity) {
            return $this->json(['error' => 'Entity not found'], 404);
        }

        if ($entity->getMap()?->getId() !== $map->getId()) {
            return $this->json(['error' => 'Entity does not belong to this map'], 403);
        }

        $name = $entity->getName();
        $this->em->remove($entity);
        $this->em->flush();

        $this->adminLogger->log(
            'delete',
            ucfirst($entityType),
            $entityId,
            sprintf('%s "%s" supprime de %s', $entityType, $name, $map->getName())
        );

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/editor/move-entity', name: 'editor_move_entity', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function moveEntity(Request $request, Map $map): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['entityType'], $data['entityId'], $data['x'], $data['y'])) {
            return $this->json(['error' => 'Missing parameters'], 400);
        }

        $entityType = $data['entityType'];
        $entityId = (int) $data['entityId'];
        $newX = (int) $data['x'];
        $newY = (int) $data['y'];

        $entity = match ($entityType) {
            'mob' => $this->em->getRepository(Mob::class)->find($entityId),
            'harvestSpot', 'portal', 'craftStation' => $this->em->getRepository(ObjectLayer::class)->find($entityId),
            'pnj' => $this->em->getRepository(Pnj::class)->find($entityId),
            default => null,
        };

        if (!$entity) {
            return $this->json(['error' => 'Entity not found'], 404);
        }

        if ($entity->getMap()?->getId() !== $map->getId()) {
            return $this->json(['error' => 'Entity does not belong to this map'], 403);
        }

        if (!MapCellValidator::isCellWalkable($map, $newX, $newY)) {
            return $this->json(['error' => 'La case cible est bloquee ou inexistante'], 400);
        }

        $oldCoords = $entity->getCoordinates();
        $entity->setCoordinates($newX . '.' . $newY);
        $this->em->flush();

        $name = $entity->getName();
        $this->adminLogger->log(
            'update',
            ucfirst($entityType),
            $entityId,
            sprintf('%s "%s" deplace de %s vers %d.%d sur %s', $entityType, $name, $oldCoords, $newX, $newY, $map->getName())
        );

        return $this->json(['success' => true]);
    }
}
