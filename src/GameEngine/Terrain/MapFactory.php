<?php

namespace App\GameEngine\Terrain;

use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\World;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Cree des cartes vierges de taille configurable depuis l'admin.
 */
class MapFactory
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Cree une carte vierge avec une Area initialisee (toutes les cells walkables, GID 0).
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
        $area->setName($name . ' - Zone principale');
        $area->setSlug($this->slugify($name) . '-main');
        $area->setCoordinates('0.0');
        $area->setMap($map);
        $area->setFullData(json_encode($this->buildEmptyCellData($width, $height)));
        $area->setCreatedAt(new \DateTime());
        $area->setUpdatedAt(new \DateTime());

        $this->em->persist($area);
        $this->em->flush();

        return $map;
    }

    /**
     * Construit le fullData pour une carte vierge : chaque cell a 4 layers vides et mouvement 0.
     *
     * @return array{cells: array<int, array<int, array{layers: list<null>, mouvement: int, slug: string}>>}
     */
    private function buildEmptyCellData(int $width, int $height): array
    {
        $cells = [];

        for ($x = 0; $x < $width; ++$x) {
            $cells[$x] = [];
            for ($y = 0; $y < $height; ++$y) {
                $cells[$x][$y] = [
                    'layers' => [null, null, null, null],
                    'mouvement' => 0,
                    'slug' => $x . '_' . $y . '_0_0_0_0_0',
                ];
            }
        }

        return ['cells' => $cells];
    }

    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        return trim($text, '-');
    }
}
