<?php

namespace App\GameEngine\Terrain;

use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\World;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Cree des cartes vierges avec une Area initialisee (toutes cells a GID 0, mouvement 0).
 */
class MapFactory
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Cree une carte vierge de taille configurable.
     *
     * @param string $name   Nom unique de la carte
     * @param int    $width  Largeur en tiles (10-200)
     * @param int    $height Hauteur en tiles (10-200)
     * @param World  $world  Monde parent
     */
    public function createBlankMap(string $name, int $width, int $height, World $world): Map
    {
        $map = new Map();
        $map->setName($name);
        $map->setAreaWidth($width);
        $map->setAreaHeight($height);
        $map->setWorld($world);
        $map->setCreatedAt(new \DateTime());
        $map->setUpdatedAt(new \DateTime());
        $this->em->persist($map);

        $area = new Area();
        $area->setName($name . ' - zone principale');
        $area->setSlug('area-' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-0-0');
        $area->setCoordinates('0.0');
        $area->setMap($map);
        $area->setFullData(json_encode($this->buildBlankFullData($width, $height)));
        $area->setCreatedAt(new \DateTime());
        $area->setUpdatedAt(new \DateTime());
        $this->em->persist($area);

        return $map;
    }

    /**
     * Genere le fullData JSON pour une grille vierge.
     *
     * @return array{width: int, height: int, tileWidth: int, tileHeight: int, cells: array<int, array<int, array{x: int, y: int, layers: list<null>, mouvement: int, slug: string}>>}
     */
    private function buildBlankFullData(int $width, int $height): array
    {
        $cells = [];
        for ($x = 0; $x < $width; ++$x) {
            $column = [];
            for ($y = 0; $y < $height; ++$y) {
                $column[$y] = [
                    'x' => $x,
                    'y' => $y,
                    'layers' => [null, null, null, null],
                    'mouvement' => 0,
                    'slug' => $x . '.' . $y . '_0_0:0:0:0',
                ];
            }
            $cells[$x] = $column;
        }

        return [
            'width' => $width,
            'height' => $height,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'cells' => $cells,
        ];
    }
}
