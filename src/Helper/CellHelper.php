<?php

namespace App\Helper;

use App\Entity\App\Area;

class CellHelper
{
    public const MOVE_UNREACHABLE = -1;
    public const MOVE_DEFAULT = 0;

    // Bitmask-based movement types (powers of 2) used as Dijkstra edge costs
    // and ability flags. The player's abilityMask must include all bits of the
    // edge cost for the path to be traversable: ($cost & $mask) === $cost.
    public const MOVE_WALK = 1;  // 0b001 — normal movement (default cost for MOVE_DEFAULT)
    public const MOVE_SWIM = 2;  // 0b010 — requires swim skill
    public const MOVE_CLIMB = 4; // 0b100 — requires climb skill

    // Ability mask bits (combine with bitwise OR for player capabilities)
    public const ABILITY_WALK = 0b001;
    public const ABILITY_SWIM = 0b010;
    public const ABILITY_CLIMB = 0b100;
    public const ABILITY_ALL = 0b111;

    public const MOVEMENT_LABELS = [
        self::MOVE_UNREACHABLE => 'Bloque',
        self::MOVE_DEFAULT => 'Libre',
        self::MOVE_SWIM => 'Eau (nage)',
        self::MOVE_CLIMB => 'Escalade',
    ];

    public static function getDataFromSlug(string $slug): array
    {
        [$coordinates, $movement, $borders] = explode('_', $slug);
        [$x, $y] = explode('.', $coordinates);
        [$north, $east, $south, $west] = explode(':', $borders);

        return [
            'coordinates' => $coordinates,
            'movement' => (int) $movement,
            'x' => (int) $x,
            'y' => (int) $y,
            'north' => (int) $north,
            'east' => (int) $east,
            'south' => (int) $south,
            'west' => (int) $west,
        ];
    }

    public static function getCalculatedDataFromSlug(string $slug, Area $area): array
    {
        $data = self::getDataFromSlug($slug);
        $offsetX = $area->getX() * $area->getMap()->getAreaWidth();
        $offsetY = $area->getY() * $area->getMap()->getAreaHeight();

        $data['x'] += $offsetX;
        $data['y'] += $offsetY;
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
        $cells = [];
        $areaData = $area->getFullDataArray();
        for ($fromX = $x - $distance; $fromX <= $x + $distance; ++$fromX) {
            for ($fromY = $y - $distance; $fromY <= $y + $distance; ++$fromY) {
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
