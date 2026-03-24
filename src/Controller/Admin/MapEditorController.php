<?php

namespace App\Controller\Admin;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Pnj;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/maps', name: 'admin_map_')]
#[IsGranted('ROLE_WORLD_BUILDER')]
class MapEditorController extends AbstractController
{
    private const TILESET_COLUMNS = [
        'terrain' => 32,
        'forest' => 16,
        'BaseChip_pipo' => 8,
        'collisions' => 6,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Packages $packages,
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
            $areaCoords = explode('.', $area->getCoordinates() ?? '0.0');
            $areaX = (int) ($areaCoords[0] ?? 0);
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

                    $cells[] = [
                        'x' => $globalX,
                        'y' => $globalY,
                        'l' => $layerGids,
                        'm' => $movement,
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

        return $this->json(['tilesets' => $tilesets]);
    }

    #[Route('/{id}/editor/entities', name: 'editor_entities', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editorEntities(Map $map): JsonResponse
    {
        $mobs = [];
        foreach ($this->em->getRepository(Mob::class)->findBy(['map' => $map]) as $mob) {
            $coords = explode('.', $mob->getCoordinates() ?? '0.0');
            $mobs[] = [
                'id' => $mob->getId(),
                'name' => $mob->getMonster()->getName(),
                'level' => $mob->getLevel(),
                'x' => (int) ($coords[0] ?? 0),
                'y' => (int) ($coords[1] ?? 0),
            ];
        }

        $pnjs = [];
        foreach ($this->em->getRepository(Pnj::class)->findBy(['map' => $map]) as $pnj) {
            $coords = explode('.', $pnj->getCoordinates() ?? '0.0');
            $pnjs[] = [
                'id' => $pnj->getId(),
                'name' => $pnj->getName(),
                'x' => (int) ($coords[0] ?? 0),
                'y' => (int) ($coords[1] ?? 0),
            ];
        }

        $portals = [];
        foreach ($this->em->getRepository(ObjectLayer::class)->findBy(['map' => $map, 'type' => ObjectLayer::TYPE_PORTAL]) as $portal) {
            $coords = explode('.', $portal->getCoordinates() ?? '0.0');
            $portals[] = [
                'id' => $portal->getId(),
                'name' => $portal->getName(),
                'x' => (int) ($coords[0] ?? 0),
                'y' => (int) ($coords[1] ?? 0),
                'destMapId' => $portal->getDestinationMapId(),
                'destCoords' => $portal->getDestinationCoordinates(),
            ];
        }

        $harvestSpots = [];
        foreach ($this->em->getRepository(ObjectLayer::class)->findBy(['map' => $map, 'type' => ObjectLayer::TYPE_HARVEST_SPOT]) as $spot) {
            $coords = explode('.', $spot->getCoordinates() ?? '0.0');
            $harvestSpots[] = [
                'id' => $spot->getId(),
                'name' => $spot->getName(),
                'x' => (int) ($coords[0] ?? 0),
                'y' => (int) ($coords[1] ?? 0),
            ];
        }

        $craftStations = [];
        $craftTypes = [ObjectLayer::TYPE_FORGE, ObjectLayer::TYPE_TANNERY, ObjectLayer::TYPE_ALCHEMY_LAB, ObjectLayer::TYPE_JEWELER_BENCH];
        foreach ($this->em->getRepository(ObjectLayer::class)->findBy(['map' => $map]) as $obj) {
            if (in_array($obj->getType(), $craftTypes, true)) {
                $coords = explode('.', $obj->getCoordinates() ?? '0.0');
                $craftStations[] = [
                    'id' => $obj->getId(),
                    'name' => $obj->getName(),
                    'type' => $obj->getType(),
                    'x' => (int) ($coords[0] ?? 0),
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
}
