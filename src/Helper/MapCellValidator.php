<?php

namespace App\Helper;

use App\Entity\App\Map;

/**
 * Checks whether a cell on a map is walkable (not blocked).
 */
class MapCellValidator
{
    /**
     * Return the movement value for a global cell coordinate on the given map,
     * or null if the cell does not exist.
     */
    public static function getCellMovement(Map $map, int $x, int $y): ?int
    {
        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();

        $areaCoordStr = intval($x / $areaWidth) . '.' . intval($y / $areaHeight);

        foreach ($map->getAreas() as $area) {
            if ($area->getCoordinates() !== $areaCoordStr) {
                continue;
            }

            $localX = $x % $areaWidth;
            $localY = $y % $areaHeight;
            $areaData = $area->getFullDataArray();

            return $areaData['cells'][$localX][$localY]['mouvement'] ?? null;
        }

        return null;
    }

    /**
     * Check if a cell at global coordinates is walkable (exists and not blocked).
     */
    public static function isCellWalkable(Map $map, int $x, int $y): bool
    {
        $movement = self::getCellMovement($map, $x, $y);

        return $movement !== null && $movement !== CellHelper::MOVE_UNREACHABLE;
    }
}
