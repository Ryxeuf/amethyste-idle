<?php

namespace App\Controller\Admin;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Pnj;
use App\Entity\Game\Monster;
use App\GameEngine\Terrain\TilesetRegistry;
use App\GameEngine\Terrain\WangTileResolver;
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
        private readonly WangTileResolver $wangTileResolver,
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
                'monsterId' => $mob->getMonster()->getId(),
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
                'classType' => $pnj->getClassType(),
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
                'requiredToolType' => $spot->getRequiredToolType(),
                'respawnDelay' => $spot->getRespawnDelay(),
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

    #[Route('/{id}/editor/entity-options', name: 'editor_entity_options', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function entityOptions(Map $map): JsonResponse
    {
        $monsters = $this->em->getRepository(Monster::class)->findAll();
        $monsterOptions = [];
        foreach ($monsters as $monster) {
            $monsterOptions[] = ['id' => $monster->getId(), 'name' => $monster->getName(), 'slug' => $monster->getSlug()];
        }

        usort($monsterOptions, fn ($a, $b) => $a['name'] <=> $b['name']);

        $maps = $this->em->getRepository(Map::class)->findAll();
        $mapOptions = [];
        foreach ($maps as $m) {
            $mapOptions[] = ['id' => $m->getId(), 'name' => $m->getName()];
        }

        usort($mapOptions, fn ($a, $b) => $a['name'] <=> $b['name']);

        $pnjs = $this->em->getRepository(Pnj::class)->findAll();
        $pnjOptions = [];
        $seen = [];
        foreach ($pnjs as $pnj) {
            $name = $pnj->getName();
            if (!isset($seen[$name])) {
                $pnjOptions[] = ['name' => $name, 'classType' => $pnj->getClassType()];
                $seen[$name] = true;
            }
        }

        usort($pnjOptions, fn ($a, $b) => $a['name'] <=> $b['name']);

        return $this->json([
            'monsters' => $monsterOptions,
            'maps' => $mapOptions,
            'pnjs' => $pnjOptions,
        ]);
    }

    #[Route('/{id}/editor/update-entity', name: 'editor_update_entity', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateEntity(Request $request, Map $map): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['entityType'], $data['entityId'], $data['properties'])) {
            return $this->json(['error' => 'Parametres manquants (entityType, entityId, properties)'], 400);
        }

        $entityType = $data['entityType'];
        $entityId = (int) $data['entityId'];
        $properties = $data['properties'];

        $entity = match ($entityType) {
            'mob' => $this->em->getRepository(Mob::class)->find($entityId),
            'harvestSpot', 'portal', 'craftStation' => $this->em->getRepository(ObjectLayer::class)->find($entityId),
            'pnj' => $this->em->getRepository(Pnj::class)->find($entityId),
            default => null,
        };

        if (!$entity) {
            return $this->json(['error' => 'Entite introuvable'], 404);
        }

        if ($entity->getMap()?->getId() !== $map->getId()) {
            return $this->json(['error' => 'L\'entite n\'appartient pas a cette carte'], 403);
        }

        try {
            match ($entityType) {
                'mob' => $this->updateMob($entity, $properties),
                'portal' => $this->updatePortal($entity, $properties),
                'harvestSpot' => $this->updateHarvestSpot($entity, $properties),
                'pnj' => $this->updatePnj($entity, $properties),
                'craftStation' => $this->updateCraftStation($entity, $properties),
                default => throw new \InvalidArgumentException("Type d'entite inconnu : {$entityType}"),
            };
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $this->em->flush();

        $coords = explode('.', $entity->getCoordinates());
        $x = (int) $coords[0];
        $y = (int) ($coords[1] ?? 0);
        $result = $this->buildEntityResult($entity, $entityType, $x, $y);

        $this->adminLogger->log(
            'update',
            ucfirst($entityType),
            $entityId,
            sprintf('%s "%s" modifie sur %s', $entityType, $entity->getName(), $map->getName())
        );

        return $this->json([
            'success' => true,
            'entity' => $result,
        ]);
    }

    #[Route('/{id}/editor/create-entity', name: 'editor_create_entity', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function createEntity(Request $request, Map $map): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['type'], $data['x'], $data['y'])) {
            return $this->json(['error' => 'Parametres manquants (type, x, y)'], 400);
        }

        $type = $data['type'];
        $x = (int) $data['x'];
        $y = (int) $data['y'];
        $properties = $data['properties'] ?? [];

        if (!MapCellValidator::isCellWalkable($map, $x, $y)) {
            return $this->json(['error' => 'La case cible est bloquee ou inexistante'], 400);
        }

        $coordinates = $x . '.' . $y;

        try {
            $entity = match ($type) {
                'mob' => $this->createMob($map, $coordinates, $properties),
                'portal' => $this->createPortal($map, $coordinates, $properties),
                'harvestSpot' => $this->createHarvestSpot($map, $coordinates, $properties),
                'pnj' => $this->createPnj($map, $coordinates, $properties),
                default => throw new \InvalidArgumentException("Type d'entite inconnu : {$type}"),
            };
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $this->em->flush();

        $result = $this->buildEntityResult($entity, $type, $x, $y);

        $this->adminLogger->log(
            'create',
            ucfirst($type),
            $result['id'],
            sprintf('%s "%s" cree en %d,%d sur %s', $type, $result['name'], $x, $y, $map->getName())
        );

        return $this->json([
            'success' => true,
            'entity' => $result,
        ]);
    }

    private function createMob(Map $map, string $coordinates, array $properties): Mob
    {
        if (!isset($properties['monsterId'])) {
            throw new \InvalidArgumentException('monsterId requis');
        }

        $monster = $this->em->getRepository(Monster::class)->find((int) $properties['monsterId']);
        if (!$monster) {
            throw new \InvalidArgumentException('Monstre introuvable');
        }

        $level = max(1, (int) ($properties['level'] ?? 1));

        $mob = new Mob();
        $mob->setMonster($monster);
        $mob->setMap($map);
        $mob->setCoordinates($coordinates);
        $mob->setLevel($level);
        $mob->setLife($monster->getLife());

        $this->em->persist($mob);

        return $mob;
    }

    private function createPortal(Map $map, string $coordinates, array $properties): ObjectLayer
    {
        $name = !empty($properties['name']) ? $properties['name'] : 'Portail';
        $destMapId = isset($properties['destMapId']) ? (int) $properties['destMapId'] : null;
        $destCoords = $properties['destCoords'] ?? null;

        $portal = new ObjectLayer();
        $portal->setType(ObjectLayer::TYPE_PORTAL);
        $portal->setName($name);
        $portal->setSlug('portal-' . time() . '-' . random_int(100, 999));
        $portal->setMap($map);
        $portal->setCoordinates($coordinates);
        $portal->setDestinationMapId($destMapId);
        $portal->setDestinationCoordinates($destCoords);
        $portal->setUsedAt(null);
        $portal->setItems(null);
        $portal->setActions(null);

        $this->em->persist($portal);

        return $portal;
    }

    private function createHarvestSpot(Map $map, string $coordinates, array $properties): ObjectLayer
    {
        $name = !empty($properties['name']) ? $properties['name'] : 'Spot de recolte';
        $slug = !empty($properties['slug']) ? $properties['slug'] : 'harvest-' . time() . '-' . random_int(100, 999);

        $spot = new ObjectLayer();
        $spot->setType(ObjectLayer::TYPE_HARVEST_SPOT);
        $spot->setName($name);
        $spot->setSlug($slug);
        $spot->setMap($map);
        $spot->setCoordinates($coordinates);
        $spot->setRespawnDelay(isset($properties['respawnDelay']) ? (int) $properties['respawnDelay'] : 300);
        $spot->setRequiredToolType($properties['requiredToolType'] ?? null);
        $spot->setUsedAt(null);
        $spot->setItems($properties['items'] ?? null);
        $spot->setActions(null);

        $this->em->persist($spot);

        return $spot;
    }

    private function createPnj(Map $map, string $coordinates, array $properties): Pnj
    {
        $name = !empty($properties['name']) ? $properties['name'] : 'PNJ';
        $classType = !empty($properties['classType']) ? $properties['classType'] : 'npc';

        $pnj = new Pnj();
        $pnj->setName($name);
        $pnj->setClassType($classType);
        $pnj->setLife(100);
        $pnj->setMaxLife(100);
        $pnj->setMap($map);
        $pnj->setCoordinates($coordinates);
        $pnj->setDialog([]);

        $this->em->persist($pnj);

        return $pnj;
    }

    private function updateMob(Mob $mob, array $properties): void
    {
        if (isset($properties['monsterId'])) {
            $monster = $this->em->getRepository(Monster::class)->find((int) $properties['monsterId']);
            if (!$monster) {
                throw new \InvalidArgumentException('Monstre introuvable');
            }
            $mob->setMonster($monster);
            $mob->setLife($monster->getLife());
        }

        if (isset($properties['level'])) {
            $mob->setLevel(max(1, (int) $properties['level']));
        }
    }

    private function updatePortal(ObjectLayer $portal, array $properties): void
    {
        if (isset($properties['name'])) {
            $portal->setName($properties['name']);
        }
        if (\array_key_exists('destMapId', $properties)) {
            $portal->setDestinationMapId($properties['destMapId'] !== null ? (int) $properties['destMapId'] : null);
        }
        if (\array_key_exists('destCoords', $properties)) {
            $portal->setDestinationCoordinates($properties['destCoords'] ?: null);
        }
    }

    private function updateHarvestSpot(ObjectLayer $spot, array $properties): void
    {
        if (isset($properties['name'])) {
            $spot->setName($properties['name']);
        }
        if (\array_key_exists('requiredToolType', $properties)) {
            $spot->setRequiredToolType($properties['requiredToolType'] ?: null);
        }
        if (isset($properties['respawnDelay'])) {
            $spot->setRespawnDelay((int) $properties['respawnDelay']);
        }
    }

    private function updatePnj(Pnj $pnj, array $properties): void
    {
        if (isset($properties['name']) && $properties['name'] !== '') {
            $pnj->setName($properties['name']);
        }
        if (isset($properties['classType']) && $properties['classType'] !== '') {
            $pnj->setClassType($properties['classType']);
        }
    }

    private function updateCraftStation(ObjectLayer $station, array $properties): void
    {
        if (isset($properties['name'])) {
            $station->setName($properties['name']);
        }
    }

    private function buildEntityResult(Mob|ObjectLayer|Pnj $entity, string $type, int $x, int $y): array
    {
        $base = [
            'id' => $entity->getId(),
            'type' => $type,
            'name' => $entity->getName(),
            'x' => $x,
            'y' => $y,
        ];

        return match ($type) {
            'mob' => array_merge($base, [
                'listKey' => 'mobs',
                'level' => $entity->getLevel(),
                'monsterId' => $entity->getMonster()->getId(),
            ]),
            'portal' => array_merge($base, [
                'listKey' => 'portals',
                'destMapId' => $entity->getDestinationMapId(),
                'destCoords' => $entity->getDestinationCoordinates(),
            ]),
            'harvestSpot' => array_merge($base, [
                'listKey' => 'harvestSpots',
                'requiredToolType' => $entity->getRequiredToolType(),
                'respawnDelay' => $entity->getRespawnDelay(),
            ]),
            'pnj' => array_merge($base, [
                'listKey' => 'pnjs',
                'classType' => $entity->getClassType(),
            ]),
            'craftStation' => array_merge($base, [
                'listKey' => 'craftStations',
                'stationType' => $entity->getType(),
            ]),
            default => $base,
        };
    }

    #[Route('/{id}/editor/auto-tile', name: 'editor_auto_tile', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function autoTile(Request $request, Map $map): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['startX'], $data['startY'], $data['endX'], $data['endY'])) {
            return $this->json(['error' => 'Missing zone parameters (startX, startY, endX, endY)'], 400);
        }

        $startX = (int) $data['startX'];
        $startY = (int) $data['startY'];
        $endX = (int) $data['endX'];
        $endY = (int) $data['endY'];
        $layer = isset($data['layer']) ? (int) $data['layer'] : 1;
        $terrainSlug = $data['terrainSlug'] ?? null;

        if ($layer < 0 || $layer > 3) {
            return $this->json(['error' => 'Invalid layer (0-3)'], 400);
        }

        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();

        // Charger toutes les areas et construire une vue "flat" des cellules
        $areasCache = [];
        foreach ($map->getAreas() as $a) {
            $areasCache[$a->getCoordinates()] = $a;
        }

        $areaDataCache = [];
        $cells = [];

        foreach ($areasCache as $coordStr => $area) {
            $areaCoords = explode('.', $coordStr);
            $areaX = (int) $areaCoords[0];
            $areaY = (int) ($areaCoords[1] ?? 0);
            $areaMinGlobalX = $areaX * $areaWidth;
            $areaMinGlobalY = $areaY * $areaHeight;

            $areaData = $area->getFullDataArray();
            $areaDataCache[$coordStr] = $areaData;

            foreach ($areaData['cells'] ?? [] as $lx => $column) {
                foreach ($column as $ly => $cellData) {
                    if ($cellData === null) {
                        continue;
                    }
                    $gx = $areaMinGlobalX + (int) $lx;
                    $gy = $areaMinGlobalY + (int) $ly;
                    $cells[$gx . '.' . $gy] = $cellData;
                }
            }
        }

        // Auto-detecter le terrain si non specifie
        if ($terrainSlug === null) {
            for ($y = $startY; $y <= $endY && $terrainSlug === null; ++$y) {
                for ($x = $startX; $x <= $endX && $terrainSlug === null; ++$x) {
                    $key = $x . '.' . $y;
                    if (!isset($cells[$key]['layers'][$layer]) || !\is_array($cells[$key]['layers'][$layer])) {
                        continue;
                    }
                    $layerData = $cells[$key]['layers'][$layer];
                    $gid = (int) ($layerData['mapIdx'] ?? 0) + (int) ($layerData['idxInMap'] ?? 0);
                    if ($gid > 0) {
                        $terrainSlug = $this->wangTileResolver->detectTerrainSlug($gid);
                    }
                }
            }
        }

        if ($terrainSlug === null) {
            return $this->json(['error' => 'Could not detect terrain type in the specified zone'], 400);
        }

        $modified = $this->wangTileResolver->resolveZone($cells, $startX, $startY, $endX, $endY, $layer, $terrainSlug);

        if ($modified === []) {
            return $this->json(['success' => true, 'count' => 0, 'cells' => []]);
        }

        // Appliquer les modifications dans les area data
        foreach ($modified as $change) {
            $gx = $change['x'];
            $gy = $change['y'];
            $changeLayer = $change['layer'];
            $gid = $change['gid'];

            $areaCoordStr = intval($gx / $areaWidth) . '.' . intval($gy / $areaHeight);
            if (!isset($areaDataCache[$areaCoordStr])) {
                continue;
            }

            $localX = $gx % $areaWidth;
            $localY = $gy % $areaHeight;

            if (!isset($areaDataCache[$areaCoordStr]['cells'][$localX][$localY])) {
                continue;
            }

            $cellData = &$areaDataCache[$areaCoordStr]['cells'][$localX][$localY];
            if (!isset($cellData['layers'])) {
                $cellData['layers'] = [null, null, null, null];
            }
            while (\count($cellData['layers']) <= $changeLayer) {
                $cellData['layers'][] = null;
            }

            if ($gid > 0) {
                $tileset = $this->tilesetRegistry->getTilesetForGid($gid);
                if ($tileset) {
                    $cellData['layers'][$changeLayer] = [
                        'mapIdx' => $tileset['firstGid'],
                        'idxInMap' => $gid - $tileset['firstGid'],
                    ];
                }
            }
        }

        // Sauvegarder les areas modifiees
        foreach ($areaDataCache as $coordStr => $areaData) {
            $areasCache[$coordStr]->setFullData(json_encode($areaData));
        }

        $this->em->flush();

        $this->adminLogger->log(
            'update',
            'AutoTile',
            null,
            sprintf('Auto-tiling %s : %d cells sur %s', $terrainSlug, \count($modified), $map->getName())
        );

        return $this->json([
            'success' => true,
            'count' => \count($modified),
            'cells' => $modified,
        ]);
    }

    #[Route('/{id}/editor/wangsets', name: 'editor_wangsets', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editorWangsets(Map $map): JsonResponse
    {
        return $this->json([
            'terrains' => $this->wangTileResolver->getAllTerrainData(),
            'supportedSlugs' => $this->wangTileResolver->getSupportedTerrains(),
        ]);
    }
}
