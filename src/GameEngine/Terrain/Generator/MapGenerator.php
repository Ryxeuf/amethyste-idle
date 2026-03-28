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
        private readonly ObjectPlacer $objectPlacer,
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

        // 1. Generer la heightmap avec les parametres Perlin du biome
        $perlin = new PerlinNoise($seed);
        $heightmap = $perlin->generateHeightmap(
            $width,
            $height,
            $biome->getPerlinScale(),
            $biome->getPerlinOctaves()
        );

        // 2. Construire les cellules
        $cells = $this->buildCells($width, $height, $heightmap, $biome, $seed);

        // 3. Garantir une zone de spawn walkable au centre (5x5)
        $this->ensureSpawnZone($cells, $width, $height, $biome, $seed);

        // 4. Appliquer l'auto-tiling pour les transitions eau
        $this->applyAutoTiling($cells, $width, $height, $biome);

        // 5. Placer la vegetation (arbres) via automate cellulaire
        $this->placeTreesWithCellularAutomaton($cells, $width, $height, $biome, $seed);

        // 6. Placer les decorations non-bloquantes sur les cellules vides
        $this->placeDecorations($cells, $width, $height, $biome, $seed);

        // 7. Ecrire dans fullData de la premiere Area
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

        // 8. Assurer la connectivite (creuser des passages entre ilots isoles)
        $this->objectPlacer->ensureConnectivity($map);

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

    /**
     * Garantit une zone de spawn walkable au centre de la carte.
     * Force un carre 5x5 en terrain principal du biome, sans eau ni obstacle.
     *
     * @param array<int, array<int, mixed>> $cells
     */
    private function ensureSpawnZone(array &$cells, int $width, int $height, BiomeDefinition $biome, int $seed): void
    {
        $centerX = (int) ($width / 2);
        $centerY = (int) ($height / 2);
        $radius = 2; // 5x5 zone

        $backgroundGids = $biome->getBackgroundGids();
        $rng = $seed ^ 0xAAAAAAAA;

        for ($x = $centerX - $radius; $x <= $centerX + $radius; ++$x) {
            for ($y = $centerY - $radius; $y <= $centerY + $radius; ++$y) {
                if ($x < 0 || $x >= $width || $y < 0 || $y >= $height) {
                    continue;
                }

                $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
                $bgGid = $backgroundGids[$rng % \count($backgroundGids)];

                $cells[$x][$y] = [
                    'x' => $x,
                    'y' => $y,
                    'layers' => [$this->gidToLayerData($bgGid), null, null, null],
                    'mouvement' => CellHelper::MOVE_DEFAULT,
                    'slug' => $x . '.' . $y . '_0_0:0:0:0',
                ];
            }
        }
    }

    /**
     * Place des decorations non-bloquantes sur le layer overlay (index 3).
     * Utilise les GID de decorationGids du biome, places aleatoirement
     * sur ~5% des cellules walkables vides.
     *
     * @param array<int, array<int, mixed>> $cells
     */
    private function placeDecorations(array &$cells, int $width, int $height, BiomeDefinition $biome, int $seed): void
    {
        $decoGids = $biome->getDecorationGids();
        if ($decoGids === []) {
            return;
        }

        $decoCount = \count($decoGids);
        $rng = $seed ^ 0x12345678;

        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                if (!isset($cells[$x][$y])) {
                    continue;
                }

                $cell = $cells[$x][$y];

                // Uniquement sur les cellules walkables sans decoration ni arbre
                if ($cell['mouvement'] !== CellHelper::MOVE_DEFAULT
                    || $cell['layers'][2] !== null
                    || $cell['layers'][3] !== null) {
                    continue;
                }

                $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;

                // ~5% de chance de placer une decoration
                if (($rng % 100) >= 5) {
                    continue;
                }

                $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
                $gid = $decoGids[$rng % $decoCount];

                $cells[$x][$y]['layers'][3] = $this->gidToLayerData($gid);
            }
        }
    }

    /**
     * Place des arbres sur le layer decoration (index 2) via automate cellulaire.
     *
     * 1. Initialisation aleatoire selon la densite du biome (uniquement sur les cells walkables sans ground layer)
     * 2. Lissage par 3 iterations de l'automate cellulaire (regle 4-5 : une cell reste/devient arbre
     *    si elle a >= 4 voisins arbres sur 8)
     * 3. Attribution de GID d'arbres aleatoires du biome
     *
     * @param array<int, array<int, mixed>> $cells
     */
    private function placeTreesWithCellularAutomaton(array &$cells, int $width, int $height, BiomeDefinition $biome, int $seed): void
    {
        $density = $biome->getTreeDensity();
        $treeGids = $biome->getTreeGids();

        if ($density <= 0.0 || $treeGids === []) {
            return;
        }

        // 1. Initialisation : grille booleenne (true = arbre candidat)
        $grid = [];
        $rng = $seed ^ 0x5A5A5A5A; // XOR pour avoir un RNG different de buildCells

        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                $canPlace = false;

                if (isset($cells[$x][$y])) {
                    $cell = $cells[$x][$y];
                    // Uniquement sur les cells walkables sans ground layer (pas eau, pas sable)
                    $canPlace = $cell['mouvement'] === CellHelper::MOVE_DEFAULT
                        && $cell['layers'][1] === null;
                }

                if ($canPlace) {
                    $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
                    $grid[$x][$y] = ($rng % 1000) < (int) ($density * 1000);
                } else {
                    $grid[$x][$y] = false;
                }
            }
        }

        // 2. Lissage : 3 iterations d'automate cellulaire (regle 4-5)
        for ($iteration = 0; $iteration < 3; ++$iteration) {
            $newGrid = [];
            for ($x = 0; $x < $width; ++$x) {
                for ($y = 0; $y < $height; ++$y) {
                    if (!isset($cells[$x][$y]) || $cells[$x][$y]['mouvement'] !== CellHelper::MOVE_DEFAULT || $cells[$x][$y]['layers'][1] !== null) {
                        $newGrid[$x][$y] = false;
                        continue;
                    }

                    $neighbors = 0;
                    for ($dx = -1; $dx <= 1; ++$dx) {
                        for ($dy = -1; $dy <= 1; ++$dy) {
                            if ($dx === 0 && $dy === 0) {
                                continue;
                            }
                            $nx = $x + $dx;
                            $ny = $y + $dy;
                            if ($nx >= 0 && $nx < $width && $ny >= 0 && $ny < $height && ($grid[$nx][$ny] ?? false)) {
                                ++$neighbors;
                            }
                        }
                    }

                    $newGrid[$x][$y] = $neighbors >= 4;
                }
            }
            $grid = $newGrid;
        }

        // 3. Attribuer les GID d'arbres sur le layer decoration (index 2)
        $treeCount = \count($treeGids);
        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                if (!($grid[$x][$y] ?? false)) {
                    continue;
                }

                $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
                $treeGid = $treeGids[$rng % $treeCount];

                $cells[$x][$y]['layers'][2] = $this->gidToLayerData($treeGid);
                // Les arbres bloquent le passage
                $cells[$x][$y]['mouvement'] = CellHelper::MOVE_UNREACHABLE;
                $cells[$x][$y]['slug'] = $x . '.' . $y . '_-1_0:0:0:0';
            }
        }
    }
}
