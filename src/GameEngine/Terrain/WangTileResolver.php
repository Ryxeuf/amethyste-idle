<?php

namespace App\GameEngine\Terrain;

/**
 * Moteur d'auto-tiling corner-based (Wang tiles).
 *
 * Analyse les voisins d'une cellule pour determiner la tile de transition
 * correcte entre un terrain type et le sol de base (herbe).
 *
 * Utilise le systeme 4-corners : chaque cellule a 4 coins (TL, TR, BR, BL).
 * Un coin est "actif" si l'une des 4 cellules qui le partagent est du terrain cible.
 * Le bitfield resultant (TL<<3 | TR<<2 | BR<<1 | BL) donne l'index de la tile de transition.
 */
class WangTileResolver
{
    /**
     * Offsets standard depuis la tile "full" (centre) dans le tileset Terrain (32 colonnes).
     * Format : bitfield => offset depuis le local tile ID du centre.
     *
     * Layout 3x5 :
     *   row-3: inner-corner-missing-BR(13), inner-corner-missing-BL(14)
     *   row-2: inner-corner-missing-TR(11), inner-corner-missing-TL(7)
     *   row-1: corner-BR(2),               top-edge(3),               corner-BL(1)
     *   row+0: right-edge(6),              FULL(15),                  left-edge(9)
     *   row+1: corner-TR(4),               bottom-edge(12),           corner-TL(8)
     */
    private const STANDARD_OFFSETS = [
        13 => -96,  // -3 rows, +0 col
        14 => -95,  // -3 rows, +1 col
        11 => -64,  // -2 rows, +0 col
        7 => -63,   // -2 rows, +1 col
        2 => -33,   // -1 row,  -1 col
        3 => -32,   // -1 row,  +0 col
        1 => -31,   // -1 row,  +1 col
        6 => -1,    // +0 row,  -1 col
        15 => 0,    // center (full)
        9 => 1,     // +0 row,  +1 col
        4 => 31,    // +1 row,  -1 col
        12 => 32,   // +1 row,  +0 col
        8 => 33,    // +1 row,  +1 col
    ];

    /**
     * Terrain types avec leur local tile ID "full" (centre du bloc 3x5).
     * Extraits des <wangcolor> du fichier Terrain.tsx.
     *
     * @var array<string, array{colorId: int, centerLocalId: int}>
     */
    private const TERRAIN_TYPES = [
        'dark_dirt' => ['colorId' => 1, 'centerLocalId' => 100],
        'red_dirt' => ['colorId' => 2, 'centerLocalId' => 103],
        'black_dirt' => ['colorId' => 3, 'centerLocalId' => 106],
        'grey_dirt' => ['colorId' => 4, 'centerLocalId' => 109],
        'lava' => ['colorId' => 5, 'centerLocalId' => 112],
        'hole' => ['colorId' => 6, 'centerLocalId' => 115],
        'red_hole' => ['colorId' => 7, 'centerLocalId' => 118],
        'black_hole' => ['colorId' => 8, 'centerLocalId' => 121],
        'water' => ['colorId' => 9, 'centerLocalId' => 124],
        'trans_dirt' => ['colorId' => 12, 'centerLocalId' => 97],
        'grass' => ['colorId' => 13, 'centerLocalId' => 289],
        'dark_grass' => ['colorId' => 14, 'centerLocalId' => 295],
        'short_grass' => ['colorId' => 15, 'centerLocalId' => 298],
        'long_grass' => ['colorId' => 16, 'centerLocalId' => 301],
        'wheat' => ['colorId' => 17, 'centerLocalId' => 304],
        'earth' => ['colorId' => 18, 'centerLocalId' => 676],
        'sand' => ['colorId' => 19, 'centerLocalId' => 307],
        'sand_water' => ['colorId' => 20, 'centerLocalId' => 310],
        'snow' => ['colorId' => 21, 'centerLocalId' => 499],
        'snow_water' => ['colorId' => 22, 'centerLocalId' => 662],
        'snow_ice' => ['colorId' => 23, 'centerLocalId' => 502],
        'ice' => ['colorId' => 24, 'centerLocalId' => 496],
        'sewer' => ['colorId' => 26, 'centerLocalId' => 484],
        'sewer_water' => ['colorId' => 27, 'centerLocalId' => 481],
    ];

    /**
     * Brick Road (terrain 25) a un layout non-standard.
     * Mapping explicite : bitfield => local tile ID.
     */
    private const BRICK_ROAD_TILES = [
        1 => 525,
        2 => 524,
        3 => 460,
        4 => 556,
        6 => 429,
        7 => 461,
        8 => 557,
        9 => 427,
        11 => 459,
        12 => 396,
        13 => 395,
        14 => 397,
        15 => 491,
    ];

    /** @var array<int, array<int, int>> Cache : centerLocalId => [bitfield => localTileId] */
    private array $lookupCache = [];

    /** @var array<int, int> Cache inverse : localTileId => centerLocalId */
    private array $tileToCenter = [];

    /** @var array<int, string> Cache : centerLocalId => terrain slug */
    private array $centerToSlug = [];

    /** @var array<int, array<int, true>> Cache : centerLocalId => [localTileId => true] pour les tiles full */
    private array $fullTiles = [];

    public function __construct()
    {
        $this->buildLookupTables();
    }

    /**
     * Resout le GID de transition pour une cellule a la position (x, y).
     *
     * @param array<string, array{layers: list<array{mapIdx: int, idxInMap: int}|null>}> $cells
     *                                                                                          Les cellules indexees par "x.y", avec structure layers[].
     * @param int                                                                        $layer index du layer a analyser (0-3)
     *
     * @return int le GID global de la tile de transition, ou 0 si aucune transition
     */
    public function resolve(array $cells, int $x, int $y, int $layer, string $terrainSlug): int
    {
        if (!isset(self::TERRAIN_TYPES[$terrainSlug]) && $terrainSlug !== 'brick_road') {
            return 0;
        }

        $centerLocalId = $terrainSlug === 'brick_road'
            ? 491
            : self::TERRAIN_TYPES[$terrainSlug]['centerLocalId'];

        $lookup = $this->lookupCache[$centerLocalId] ?? [];
        if ($lookup === []) {
            return 0;
        }

        $bitfield = $this->computeCornerBitfield($cells, $x, $y, $layer, $centerLocalId);

        if ($bitfield === 0) {
            return 0;
        }

        $localTileId = $lookup[$bitfield] ?? $lookup[15];

        return TilesetRegistry::FIRST_GID_TERRAIN + $localTileId;
    }

    /**
     * Applique l'auto-tiling sur une zone rectangulaire.
     * Resout chaque cellule de la zone + ses bordures (1 cell autour).
     *
     * @param array<string, array{layers: list<array{mapIdx: int, idxInMap: int}|null>}> $cells
     * @param int                                                                        $layer       index du layer
     * @param string                                                                     $terrainSlug slug du terrain a resoudre
     *
     * @return list<array{x: int, y: int, layer: int, gid: int}> liste des cellules modifiees
     */
    public function resolveZone(array &$cells, int $startX, int $startY, int $endX, int $endY, int $layer, string $terrainSlug): array
    {
        $modified = [];

        // Etendre la zone d'un cell pour recalculer les bordures
        $resolveStartX = $startX - 1;
        $resolveStartY = $startY - 1;
        $resolveEndX = $endX + 1;
        $resolveEndY = $endY + 1;

        for ($y = $resolveStartY; $y <= $resolveEndY; ++$y) {
            for ($x = $resolveStartX; $x <= $resolveEndX; ++$x) {
                $key = $x . '.' . $y;
                if (!isset($cells[$key])) {
                    continue;
                }

                $gid = $this->resolve($cells, $x, $y, $layer, $terrainSlug);
                if ($gid === 0) {
                    continue;
                }

                // Verifier si la cellule a deja le bon GID
                $currentGid = $this->getCellGid($cells, $x, $y, $layer);
                if ($currentGid === $gid) {
                    continue;
                }

                $modified[] = [
                    'x' => $x,
                    'y' => $y,
                    'layer' => $layer,
                    'gid' => $gid,
                ];
            }
        }

        return $modified;
    }

    /**
     * Detecte le terrain type d'un GID global (dans le tileset Terrain).
     *
     * @return string|null le slug du terrain, ou null si non reconnu
     */
    public function detectTerrainSlug(int $gid): ?string
    {
        if ($gid < TilesetRegistry::FIRST_GID_TERRAIN || $gid >= TilesetRegistry::FIRST_GID_FOREST) {
            return null;
        }

        $localId = $gid - TilesetRegistry::FIRST_GID_TERRAIN;

        if (isset($this->tileToCenter[$localId])) {
            $centerId = $this->tileToCenter[$localId];

            return $this->centerToSlug[$centerId] ?? null;
        }

        return null;
    }

    /**
     * Retourne la liste des terrain slugs supportes.
     *
     * @return list<string>
     */
    public function getSupportedTerrains(): array
    {
        $slugs = array_keys(self::TERRAIN_TYPES);
        $slugs[] = 'brick_road';

        return $slugs;
    }

    /**
     * Retourne la table de lookup pour un terrain (utile pour l'export vers JS).
     *
     * @return array{centerGid: int, tiles: array<int, int>}|null
     */
    public function getTerrainData(string $slug): ?array
    {
        if ($slug === 'brick_road') {
            $centerLocalId = 491;
        } elseif (isset(self::TERRAIN_TYPES[$slug])) {
            $centerLocalId = self::TERRAIN_TYPES[$slug]['centerLocalId'];
        } else {
            return null;
        }

        if (!isset($this->lookupCache[$centerLocalId])) {
            return null;
        }

        $tiles = [];
        foreach ($this->lookupCache[$centerLocalId] as $bitfield => $localId) {
            $tiles[$bitfield] = TilesetRegistry::FIRST_GID_TERRAIN + $localId;
        }

        return [
            'centerGid' => TilesetRegistry::FIRST_GID_TERRAIN + $centerLocalId,
            'tiles' => $tiles,
        ];
    }

    /**
     * Retourne toutes les definitions de terrain pour l'API (export vers frontend).
     *
     * @return array<string, array{centerGid: int, tiles: array<int, int>}>
     */
    public function getAllTerrainData(): array
    {
        $result = [];
        foreach ($this->getSupportedTerrains() as $slug) {
            $data = $this->getTerrainData($slug);
            if ($data !== null) {
                $result[$slug] = $data;
            }
        }

        return $result;
    }

    /**
     * Calcule le bitfield 4-corners pour une cellule.
     *
     * Bit 3 = TL, Bit 2 = TR, Bit 1 = BR, Bit 0 = BL.
     * Un coin est actif si l'une des 4 cellules partageant ce coin est du terrain cible.
     *
     * @param array<string, array{layers: list<array{mapIdx: int, idxInMap: int}|null>}> $cells
     */
    private function computeCornerBitfield(array $cells, int $x, int $y, int $layer, int $centerLocalId): int
    {
        $bitfield = 0;

        // Top-Left corner : cells (x-1,y-1), (x,y-1), (x-1,y), (x,y)
        if ($this->isTerrainAt($cells, $x, $y, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x - 1, $y, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x, $y - 1, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x - 1, $y - 1, $layer, $centerLocalId)) {
            $bitfield |= 8; // TL
        }

        // Top-Right corner : cells (x,y-1), (x+1,y-1), (x,y), (x+1,y)
        if ($this->isTerrainAt($cells, $x, $y, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x + 1, $y, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x, $y - 1, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x + 1, $y - 1, $layer, $centerLocalId)) {
            $bitfield |= 4; // TR
        }

        // Bottom-Right corner : cells (x,y), (x+1,y), (x,y+1), (x+1,y+1)
        if ($this->isTerrainAt($cells, $x, $y, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x + 1, $y, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x, $y + 1, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x + 1, $y + 1, $layer, $centerLocalId)) {
            $bitfield |= 2; // BR
        }

        // Bottom-Left corner : cells (x-1,y), (x,y), (x-1,y+1), (x,y+1)
        if ($this->isTerrainAt($cells, $x, $y, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x - 1, $y, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x, $y + 1, $layer, $centerLocalId)
            || $this->isTerrainAt($cells, $x - 1, $y + 1, $layer, $centerLocalId)) {
            $bitfield |= 1; // BL
        }

        return $bitfield;
    }

    /**
     * Verifie si la cellule a la position donnee est une tile "full" (centre) du terrain cible.
     *
     * Seules les tiles full comptent comme terrain source pour eviter les cascades :
     * les tiles de transition ne doivent pas contaminer le calcul des voisins.
     *
     * @param array<string, array{layers: list<array{mapIdx: int, idxInMap: int}|null>}> $cells
     */
    private function isTerrainAt(array $cells, int $x, int $y, int $layer, int $centerLocalId): bool
    {
        $key = $x . '.' . $y;
        if (!isset($cells[$key])) {
            return false;
        }

        $localId = $this->getLocalTileIdFromCell($cells[$key], $layer);
        if ($localId === null) {
            return false;
        }

        // Seules les tiles "full" (centre + variantes) comptent comme terrain source
        return isset($this->fullTiles[$centerLocalId][$localId]);
    }

    /**
     * Retourne le GID global d'une cellule pour un layer.
     *
     * @param array<string, array<string, mixed>> $cells
     */
    private function getCellGid(array $cells, int $x, int $y, int $layer): int
    {
        $key = $x . '.' . $y;
        if (!isset($cells[$key]['layers'][$layer])) {
            return 0;
        }

        $layerData = $cells[$key]['layers'][$layer];
        if (!\is_array($layerData)) {
            return 0;
        }

        return (int) ($layerData['mapIdx'] ?? 0) + (int) ($layerData['idxInMap'] ?? 0);
    }

    /**
     * Extrait le local tile ID (dans le tileset Terrain) d'une cellule.
     *
     * @param array<string, mixed> $cellData
     */
    private function getLocalTileIdFromCell(array $cellData, int $layer): ?int
    {
        if (!isset($cellData['layers'][$layer])) {
            return null;
        }

        $layerData = $cellData['layers'][$layer];
        if (!\is_array($layerData)) {
            return null;
        }

        $mapIdx = (int) ($layerData['mapIdx'] ?? 0);
        $idxInMap = (int) ($layerData['idxInMap'] ?? 0);

        // Seulement les tiles du tileset Terrain
        if ($mapIdx !== TilesetRegistry::FIRST_GID_TERRAIN) {
            return null;
        }

        return $idxInMap;
    }

    private function buildLookupTables(): void
    {
        // Terrains standard (layout 3x5)
        foreach (self::TERRAIN_TYPES as $slug => $config) {
            $centerId = $config['centerLocalId'];
            $this->centerToSlug[$centerId] = $slug;
            $lookup = [];

            foreach (self::STANDARD_OFFSETS as $bitfield => $offset) {
                $localId = $centerId + $offset;
                if ($localId >= 0) {
                    $lookup[$bitfield] = $localId;
                    $this->tileToCenter[$localId] = $centerId;
                }
            }

            // Diagonales (5 et 10) : utiliser la tile full
            $lookup[5] = $centerId;
            $lookup[10] = $centerId;

            $this->lookupCache[$centerId] = $lookup;
            $this->fullTiles[$centerId] = [$centerId => true];
        }

        // Brick Road (layout non-standard)
        $brickCenterId = 491;
        $this->centerToSlug[$brickCenterId] = 'brick_road';
        $brickLookup = [];

        foreach (self::BRICK_ROAD_TILES as $bitfield => $localId) {
            $brickLookup[$bitfield] = $localId;
            $this->tileToCenter[$localId] = $brickCenterId;
        }

        // Diagonales
        $brickLookup[5] = $brickCenterId;
        $brickLookup[10] = $brickCenterId;

        $this->lookupCache[$brickCenterId] = $brickLookup;
        $this->fullTiles[$brickCenterId] = [$brickCenterId => true];

        // Ajouter les tiles variantes (full) connues
        $this->registerVariants();
    }

    /**
     * Enregistre les tiles variantes "full" qui partagent le meme terrain.
     */
    private function registerVariants(): void
    {
        $variants = [
            // Trans Dirt variantes non definies car tile 97 est deja le centre
            // Dark Dirt
            100 => [163, 164, 165],
            // Red Dirt
            103 => [166, 167, 168],
            // Black Dirt
            106 => [169, 170, 171],
            // Grey Dirt
            109 => [172, 173, 174],
            // Lava
            112 => [175, 176, 177],
            // Black Hole
            121 => [184, 185, 186],
            // Water
            124 => [187, 188, 189],
            // Grass
            289 => [292, 352, 353, 354],
            // Dark Grass
            295 => [358, 359, 360],
            // Sand
            307 => [370, 371, 372],
            // Brick Road
            491 => [398, 430, 462, 492, 493, 494],
            // Sewer
            484 => [547, 548, 549],
            // Sewer Water
            481 => [544, 545, 546],
            // Ice
            496 => [559, 560, 561],
            // Snow
            499 => [562, 563, 564],
            // Full Dirt (center = 537, type 10/11 — transitions entre eux, enregistres mais non resolus vs base)
            // Earth
            676 => [],
            // Snow Water
            662 => [],
        ];

        foreach ($variants as $centerId => $variantIds) {
            foreach ($variantIds as $variantId) {
                $this->tileToCenter[$variantId] = $centerId;
                $this->fullTiles[$centerId][$variantId] = true;
            }
        }
    }
}
