<?php

namespace App\Helper;

use App\Entity\App\Area;

class CellHelper
{
    public const MOVE_UNREACHABLE = -1;
    public const MOVE_DEFAULT     = 0;

    public static function getDataFromSlug(string $slug): array
    {
        [$coordinates, $movement, $borders] = explode('_', $slug);
        [$x, $y] = explode('.', $coordinates);
        [$north, $east, $south, $west] = explode(':', $borders);

        return [
            'coordinates' => $coordinates,
            'movement'    => (int)$movement,
            'x'           => (int)$x,
            'y'           => (int)$y,
            'north'       => (int)$north,
            'east'        => (int)$east,
            'south'       => (int)$south,
            'west'        => (int)$west,
        ];
    }

    public static function getCalculatedDataFromSlug(string $slug, Area $area): array
    {
        $data    = self::getDataFromSlug($slug);
        $offsetX = $area->getX() * $area->getMap()->getAreaWidth();
        $offsetY = $area->getY() * $area->getMap()->getAreaHeight();

        $data['x']           += $offsetX;
        $data['y']           += $offsetY;
        $data['coordinates'] = self::stringifyCoordinates($data['x'], $data['y']);

        return $data;
    }

    public static function getCellInfos(Area $area, int $x, int $y): ?array
    {
        $areaData = $area->getFullDataArray();

        return $areaData['cells'][$x % $area->getMap()->getAreaWidth()][$y % $area->getMap()->getAreaHeight()] ?? null;
    }

    public static function getCellsInfos(Area $area, int $x, int $y, int $distance): array
    {
        $cells    = [];
        $areaData = $area->getFullDataArray();
        for ($fromX = $x - $distance; $fromX <= $x + $distance; $fromX++) {
            for ($fromY = $y - $distance; $fromY <= $y + $distance; $fromY++) {
                if (null !==
                    $cell = $areaData['cells'][$fromX % $area->getMap()->getAreaWidth()][$fromY % $area->getMap()->getAreaHeight()] ?? null) {
                    $cells[] = $cell;
                }
            }
        }

        return $cells;
    }

    public static function stringifyCoordinates(int $x, int $y): string
    {
        return $x . '.' . $y;
    }

}
