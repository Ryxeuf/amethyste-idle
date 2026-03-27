<?php

namespace App\GameEngine\Terrain\Generator;

use App\Entity\App\Map;
use App\GameEngine\Terrain\TilesetRegistry;
use App\GameEngine\Terrain\WangTileResolver;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Generateur procedural de terrain.
 *
 * Pipeline : heightmap Perlin -> layers background/ground -> collisions -> auto-tiling transitions.
 * Ecrit directement dans Area.fullData de la carte.
 */
class MapGenerator
{
    public function __construct(
        private readonly TilesetRegistry $tilesetRegistry,
        private readonly WangTileResolver $wangTileResolver,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Genere le terrain procedural pour une carte existante.
     */
    public function generate(Map $map, BiomeDefinition $biome, int $difficulty = 1, ?int $seed = null): void
    {
        $seed ??= random_int(0, 2147483647);
        $width = $map->getAreaWidth();
        $height = $map->getAreaHeight();

        // 1. Generer la heightmap
        $perlin = new PerlinNoise($seed);
        $heightmap = $perlin->generateHeightmap($width, $height, 0.05, 4);

        // 2. Construire les cellules
        $cells = $this->buildCells($width, $height, $heightmap, $biome, $seed);

        // 3. Appliquer l'auto-tiling pour les transitions eau
        $this->applyAutoTiling($cells, $width, $height, $biome);

        // 4. Ecrire dans fullData de la premiere Area
        $area = $map->getAreas()->first();
        if (!$area) {
            return;
        }

        $fullData = [
            'width' => $width,
            'height' => $height,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'cells' => $cells,
        ];

        $area->setFullData(json_encode($fullData));
        $area->setBiome($biome->getSlug());
        $area->setWeather($biome->getWeather());
        $area->setMusic($biome->getMusic());

        $this->em->flush();
    }

    /**
     * Construit le tableau de cellules a partir de la heightmap et du biome.
     *
     * @param float[][] $heightmap Heightmap [x][y] entre 0 et 1
     *
     * @return array<int, array<int, array{x: int, y: int, layers: list<array{mapIdx: int, idxInMap: int}|null>, mouvement: int, slug: string}>>
     */
    private function buildCells(int $width, int $height, array $heightmap, BiomeDefinition $biome, int $seed): array
    {
        $backgroundGids = $biome->getBackgroundGids();
        $waterThreshold = $biome->getWaterThreshold();
        $sandThreshold = $biome->getSandThreshold();
        $rng = $seed;

        $cells = [];

        for ($x = 0; $x < $width; ++$x) {
            $column = [];
            for ($y = 0; $y < $height; ++$y) {
                $h = $heightmap[$x][$y];

                // Determiner le type de terrain
                if ($h < $waterThreshold) {
                    // Eau : layer ground avec water GID, mouvement bloque
                    $bgLayer = $this->gidToLayerData(TilesetRegistry::GID_GRASS_BASE);
                    $groundLayer = $this->gidToLayerData($this->getWaterCenterGid($biome));
                    $movement = CellHelper::MOVE_UNREACHABLE;
                } elseif ($h < $sandThreshold) {
                    // Transition sable/terre
                    $bgLayer = $this->gidToLayerData(TilesetRegistry::GID_GRASS_BASE);
                    $groundLayer = $this->gidToLayerData($this->getSandCenterGid($biome));
                    $movement = CellHelper::MOVE_DEFAULT;
                } else {
                    // Terrain principal du biome
                    $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
                    $bgGid = $backgroundGids[$rng % \count($backgroundGids)];
                    $bgLayer = $this->gidToLayerData($bgGid);
                    $groundLayer = null;
                    $movement = CellHelper::MOVE_DEFAULT;
                }

                $column[$y] = [
                    'x' => $x,
                    'y' => $y,
                    'layers' => [$bgLayer, $groundLayer, null, null],
                    'mouvement' => $movement,
                    'slug' => $x . '.' . $y . '_' . $movement . '_0:0:0:0',
                ];
            }
            $cells[$x] = $column;
        }

        return $cells;
    }

    /**
     * Applique l'auto-tiling Wang pour les transitions eau et sable.
     *
     * @param array<int, array<int, mixed>> $cells Cellules a modifier
     */
    private function applyAutoTiling(array &$cells, int $width, int $height, BiomeDefinition $biome): void
    {
        // Convertir les cellules en format "flat" attendu par WangTileResolver
        $flatCells = [];
        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                $flatCells[$x . '.' . $y] = $cells[$x][$y];
            }
        }

        // Auto-tiling eau
        $waterSlug = $biome->getWaterTerrainSlug();
        $modified = $this->wangTileResolver->resolveZone(
            $flatCells, 0, 0, $width - 1, $height - 1, 1, $waterSlug
        );

        // Appliquer les modifications
        foreach ($modified as $change) {
            $cx = $change['x'];
            $cy = $change['y'];
            $layer = $change['layer'];
            $gid = $change['gid'];

            if (!isset($cells[$cx][$cy])) {
                continue;
            }

            if ($gid > 0) {
                $cells[$cx][$cy]['layers'][$layer] = $this->gidToLayerData($gid);
            } else {
                $cells[$cx][$cy]['layers'][$layer] = null;
            }
        }

        // Auto-tiling transition sable (si different de l'eau)
        $sandSlug = $biome->getSandTerrainSlug();
        if ($sandSlug !== $waterSlug) {
            // Reconstruire flatCells avec les modifications eau
            $flatCells = [];
            for ($x = 0; $x < $width; ++$x) {
                for ($y = 0; $y < $height; ++$y) {
                    $flatCells[$x . '.' . $y] = $cells[$x][$y];
                }
            }

            $modified = $this->wangTileResolver->resolveZone(
                $flatCells, 0, 0, $width - 1, $height - 1, 1, $sandSlug
            );

            foreach ($modified as $change) {
                $cx = $change['x'];
                $cy = $change['y'];
                $layer = $change['layer'];
                $gid = $change['gid'];

                if (!isset($cells[$cx][$cy])) {
                    continue;
                }

                if ($gid > 0) {
                    $cells[$cx][$cy]['layers'][$layer] = $this->gidToLayerData($gid);
                } else {
                    $cells[$cx][$cy]['layers'][$layer] = null;
                }
            }
        }
    }

    /**
     * Convertit un GID global en format layer {mapIdx, idxInMap}.
     *
     * @return array{mapIdx: int, idxInMap: int}|null
     */
    private function gidToLayerData(int $gid): ?array
    {
        if ($gid <= 0) {
            return null;
        }

        $tileset = $this->tilesetRegistry->getTilesetForGid($gid);
        if (!$tileset) {
            return null;
        }

        return [
            'mapIdx' => $tileset['firstGid'],
            'idxInMap' => $gid - $tileset['firstGid'],
        ];
    }

    /**
     * Retourne le GID "center" (full) de l'eau pour ce biome.
     */
    private function getWaterCenterGid(BiomeDefinition $biome): int
    {
        $slug = $biome->getWaterTerrainSlug();

        return $this->getCenterGidForSlug($slug);
    }

    /**
     * Retourne le GID "center" (full) du sable/transition pour ce biome.
     */
    private function getSandCenterGid(BiomeDefinition $biome): int
    {
        $slug = $biome->getSandTerrainSlug();

        return $this->getCenterGidForSlug($slug);
    }

    /**
     * Retourne le GID global du centre pour un terrain slug donne.
     */
    private function getCenterGidForSlug(string $slug): int
    {
        $terrainData = $this->wangTileResolver->getAllTerrainData();

        if (isset($terrainData[$slug]['centerGid'])) {
            return $terrainData[$slug]['centerGid'];
        }

        // Fallback : water center GID
        return TilesetRegistry::FIRST_GID_TERRAIN + 124;
    }
}
