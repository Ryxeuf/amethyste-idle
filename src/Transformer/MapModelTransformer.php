<?php

namespace App\Transformer;

use App\Dto\Area\AreaModel;
use App\Dto\Cell\CellModel;
use App\Dto\Cell\ObjectLayerModel;
use App\Dto\Map\MapDynamicModel;
use App\Dto\Map\MapModel;
use App\Dto\Map\MapStaticModel;
use App\Dto\Mob\MobModelLight;
use App\Dto\Pnj\PnjModelLight;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;

class MapModelTransformer
{
    public function __construct(
        private readonly CellModelTransformer $cellModelTransformer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function transformMapModel(Map $map): MapModel
    {
        $model = new MapModel($map);

        foreach ($map->getAreas() as $area) {
            $model->areas[] = new AreaModel($area);
            $model->minX = min($model->minX, $area->getX() * $map->getAreaWidth());
            $model->minY = min($model->minY, $area->getY() * $map->getAreaHeight());
            $model->maxX = max($model->maxX, ($area->getX() + 1) * $map->getAreaWidth());
            $model->maxY = max($model->maxY, ($area->getY() + 1) * $map->getAreaHeight());
        }

        return $model;
    }

    public function transformStaticMapModel(Map $map): MapStaticModel
    {
        $model = new MapStaticModel($map);

        foreach ($map->getAreas() as $area) {
            $model->areas[] = new AreaModel($area);
            $model->minX = min($model->minX, $area->getX() * $map->getAreaWidth());
            $model->minY = min($model->minY, $area->getY() * $map->getAreaHeight());
            $model->maxX = max($model->maxX, ($area->getX() + 1) * $map->getAreaWidth());
            $model->maxY = max($model->maxY, ($area->getY() + 1) * $map->getAreaHeight());

            $areaData = $area->getFullDataArray();

            // Vérifier si les données de cellules existent
            if (isset($areaData['cells']) && is_array($areaData['cells'])) {
                foreach ($areaData['cells'] as $data) {
                    if (is_array($data)) {
                        foreach ($data as $cell) {
                            if (is_array($cell)) {
                                $model->cells[] = new CellModel($cell, $map, $area);
                            }
                        }
                    }
                }
            }
        }

        return $model;
    }

    public function transformPlayerMapModel(Player $player): MapModel
    {
        $map = $player->getMap();
        [$x, $y] = explode('.', $player->getCoordinates());

        $model = new MapModel($map);
        $model->minX = (int) $x - 11;
        $model->maxX = $model->minX + 22;
        $model->minY = (int) $y - 11;
        $model->maxY = $model->minY + 22;

        return $model;
    }

    public function transformDynamicMapModel(Map $map): MapDynamicModel
    {
        $model = new MapDynamicModel($map);

        $mapPlayers = $this->entityManager->getRepository(Player::class)->findBy(['map' => $map]);
        foreach ($mapPlayers as $mapPlayer) {
            $model->players[] = $this->cellModelTransformer->transformCellPlayer($mapPlayer);
        }

        $mapMobs = $this->entityManager->getRepository(Mob::class)->findBy(['map' => $map]);
        foreach ($mapMobs as $mapMob) {
            $model->mobs[] = new MobModelLight($mapMob);
        }

        $mapPnjs = $this->entityManager->getRepository(Pnj::class)->findBy(['map' => $map]);
        foreach ($mapPnjs as $mapPnj) {
            $model->pnjs[] = new PnjModelLight($mapPnj);
        }

        $objectLayers = $this->entityManager->getRepository(ObjectLayer::class)->findBy(['map' => $map]);
        foreach ($objectLayers as $objectLayer) {
            if ($objectLayer->isDynamic()) {
                $model->usableObjects[] = new ObjectLayerModel($objectLayer);
            } else {
                $model->objects[] = new ObjectLayerModel($objectLayer);
            }
        }

        return $model;
    }

    public function generateDijkstraTagMap(Map $map): array
    {
        $mapModel = $this->transformMapModel($map);
        $dijkstraMap = [];

        $baseMovements = [];
        $cells = [];
        foreach ($map->getAreas() as $area) {
            $data = $area->getFullDataArray();
            foreach ($data['cells'] as $datum) {
                foreach ($datum as $cell) {
                    $cellInfos = CellHelper::getCalculatedDataFromSlug($cell['slug'], $area);

                    // If cell movement is -1, the cell is unreachable, so no need to keep it in movement base
                    if ((int) $cellInfos['movement'] !== CellHelper::MOVE_UNREACHABLE) {
                        $baseMovements[$cellInfos['coordinates']] = (int) $cellInfos['movement'] === CellHelper::MOVE_DEFAULT ? 1 : (int) $cellInfos['movement'];
                    }
                    $cells[$cellInfos['coordinates']] = $cellInfos;
                }
            }
        }

        foreach ($cells as $cell) {
            if ((int) $cell['movement'] !== CellHelper::MOVE_UNREACHABLE) {
                $coordinates = $cell['coordinates'];

                $x = $cell['x'];
                $y = $cell['y'];

                $dijkstraMap[$coordinates] = [];

                $northCoordinates = $x . '.' . ($y - 1);
                if ((int) $cell['north'] === 0 && $y - 1 >= $mapModel->minY && isset($baseMovements[$northCoordinates])) {
                    $dijkstraMap[$coordinates][$northCoordinates] = (int) $baseMovements[$northCoordinates];
                }
                $southCoordinates = $x . '.' . ($y + 1);
                if ((int) $cell['south'] === 0 && $y + 1 < $mapModel->maxY && isset($baseMovements[$southCoordinates])) {
                    $dijkstraMap[$coordinates][$southCoordinates] = (int) $baseMovements[$southCoordinates];
                }
                $eastCoordinates = ($x + 1) . '.' . $y;
                if ((int) $cell['east'] === 0 && $x + 1 < $mapModel->maxX && isset($baseMovements[$eastCoordinates])) {
                    $dijkstraMap[$coordinates][$eastCoordinates] = (int) $baseMovements[$eastCoordinates];
                }
                $westCoordinates = ($x - 1) . '.' . $y;
                if ((int) $cell['west'] === 0 && $x - 1 >= $mapModel->minX && isset($baseMovements[$westCoordinates])) {
                    $dijkstraMap[$coordinates][$westCoordinates] = (int) $baseMovements[$westCoordinates];
                }
            }
        }

        return $dijkstraMap;
    }
}
