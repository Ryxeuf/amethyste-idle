<?php

namespace App\SearchEngine;

use App\Dto\Search\SearchCell;
use App\Entity\App\Map;
use App\SearchEngine\Transformer\CellTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CellSearchEngine
{
    private const RADIUS = 10;
    private const CACHE_TTL = 3600; // 1 heure

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CellTransformer $cellTransformer,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return SearchCell[]
     */
    public function getMapCells(int $x, int $y, int $mapId): array
    {
        $cacheKey = sprintf('map_cells_%d_%d_%d', $mapId, $x, $y);

        $cellsData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($x, $y, $mapId) {
            $item->expiresAfter(self::CACHE_TTL);
            $cells = $this->loadMapCells($x, $y, $mapId);

            return array_map(fn (SearchCell $c) => $c->toArray(), $cells);
        });

        return array_map([SearchCell::class, 'fromArray'], $cellsData);
    }

    /**
     * @return SearchCell[]
     */
    private function loadMapCells(int $centerX, int $centerY, int $mapId): array
    {
        $minX = $centerX - self::RADIUS;
        $maxX = $centerX + self::RADIUS;
        $minY = $centerY - self::RADIUS;
        $maxY = $centerY + self::RADIUS;

        $map = $this->entityManager->getRepository(Map::class)->find($mapId);
        if ($map === null) {
            return [];
        }

        $world = $map->getWorld();
        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();

        $this->cellTransformer->resetTerrains();

        $cells = [];
        foreach ($map->getAreas() as $area) {
            $areaX = $area->getX();
            $areaY = $area->getY();
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
            $terrains = $areaData['terrains'] ?? [];
            if (!empty($terrains)) {
                $this->cellTransformer->addTerrains($terrains);
            }

            for ($localX = $localMinX; $localX <= $localMaxX; ++$localX) {
                for ($localY = $localMinY; $localY <= $localMaxY; ++$localY) {
                    $cellData = $cellsData[$localX][$localY] ?? null;
                    if ($cellData === null) {
                        continue;
                    }

                    // Convertir les coordonnées locales en globales pour SearchCell
                    $cellData['x'] = $areaMinGlobalX + ($cellData['x'] ?? $localX);
                    $cellData['y'] = $areaMinGlobalY + ($cellData['y'] ?? $localY);

                    $searchCell = $this->cellTransformer->transform($cellData, $area, $map, $world);
                    $cells[] = $searchCell;
                }
            }
        }

        return $cells;
    }
}
