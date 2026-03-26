<?php

namespace App\GameEngine\Terrain;

use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\World;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Cree des cartes vierges (remplies d'herbe) de taille configurable.
 */
class MapFactory
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Cree une carte vierge avec une Area unique remplie d'herbe.
     */
    public function create(string $name, World $world, int $width, int $height): Map
    {
        $map = new Map();
        $map->setName($name);
        $map->setWorld($world);
        $map->setAreaWidth($width);
        $map->setAreaHeight($height);
        $map->setCoordinates('0.0');
        $map->setCreatedAt(new \DateTime());
        $map->setUpdatedAt(new \DateTime());

        $this->em->persist($map);

        $area = $this->createBlankArea($map, 0, 0, $width, $height);
        $this->em->persist($area);

        return $map;
    }

    private function createBlankArea(Map $map, int $areaX, int $areaY, int $width, int $height): Area
    {
        $slug = $map->getName() . '-area-' . $areaX . '-' . $areaY;
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $slug));

        $area = new Area();
        $area->setName($map->getName() . ' - Zone ' . $areaX . '.' . $areaY);
        $area->setSlug($slug);
        $area->setCoordinates($areaX . '.' . $areaY);
        $area->setMap($map);
        $area->setBiome('plains');
        $area->setZoneX(0);
        $area->setZoneY(0);
        $area->setZoneWidth($width);
        $area->setZoneHeight($height);
        $area->setCreatedAt(new \DateTime());
        $area->setUpdatedAt(new \DateTime());

        $fullData = $this->generateBlankCells($width, $height);
        $area->setFullData(json_encode($fullData, \JSON_THROW_ON_ERROR));

        return $area;
    }

    /**
     * Genere le JSON des cellules vierges (herbe, mouvement libre).
     *
     * @return array{cells: array<int, array<int, array<string, mixed>>>}
     */
    private function generateBlankCells(int $width, int $height): array
    {
        $grassGids = [
            TilesetRegistry::GID_GRASS_BASE,
            TilesetRegistry::GID_GRASS_ALT1,
            TilesetRegistry::GID_GRASS_ALT2,
            TilesetRegistry::GID_GRASS_ALT3,
        ];

        $cells = [];
        for ($x = 0; $x < $width; ++$x) {
            $column = [];
            for ($y = 0; $y < $height; ++$y) {
                $gid = $grassGids[($x * 7 + $y * 13) % \count($grassGids)];
                $localId = $gid - TilesetRegistry::FIRST_GID_TERRAIN;

                $column[$y] = [
                    'x' => $x,
                    'y' => $y,
                    'mouvement' => CellHelper::MOVE_DEFAULT,
                    'slug' => CellHelper::stringifyCoordinates($x, $y) . '_0_0:0:0:0',
                    'layers' => [
                        [
                            'mapIdx' => $gid,
                            'idxInMap' => $localId,
                            'tilesetName' => 'terrain',
                            'source' => 'terrain.png',
                        ],
                    ],
                ];
            }
            $cells[$x] = $column;
        }

        return ['cells' => $cells];
    }
}
