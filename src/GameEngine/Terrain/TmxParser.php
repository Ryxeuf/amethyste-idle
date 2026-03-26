<?php

namespace App\GameEngine\Terrain;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Parses Tiled Map Editor (.tmx/.tsx) files into structured data.
 */
class TmxParser
{
    public const ABILITY_CLIMB = 0b10;
    public const ABILITY_SWIM = 0b100;

    /**
     * Parse a TMX file and return structured map data + collision slugs.
     *
     * @return array{map: array, slugs: string[]}
     */
    public function parse(string $fileContent, string $terrainPath, int $offsetX = 0, int $offsetY = 0): array
    {
        $fileCrawler = new Crawler($fileContent);

        $mapWidth = (int) $fileCrawler->attr('width');
        $mapHeight = (int) $fileCrawler->attr('height');

        $map = [
            'width' => $mapWidth,
            'height' => $mapHeight,
            'tileHeight' => (int) $fileCrawler->attr('tileheight'),
            'tileWidth' => (int) $fileCrawler->attr('tilewidth'),
            'cells' => [],
            'terrains' => [],
            'objects' => [],
        ];

        $map['terrains'] = $this->parseTilesets($fileCrawler, $terrainPath);
        [$map['cells'], $slugs] = $this->parseLayers($fileCrawler, $map['terrains'], $mapWidth, $mapHeight, $offsetX, $offsetY);
        $map['objects'] = $this->parseObjectLayers($fileCrawler, $map['tileWidth'], $map['tileHeight'], $mapWidth, $mapHeight, $offsetX, $offsetY);

        return ['map' => $map, 'slugs' => $slugs];
    }

    /**
     * Parse tileset references from a TMX file.
     *
     * @return array<string, array<string, int|string|null>>
     */
    public function parseTilesets(Crawler $fileCrawler, string $terrainPath): array
    {
        $terrains = [];
        $tilesets = $fileCrawler->filterXPath('//map/tileset');

        foreach ($tilesets as $tileset) {
            $tilesetCrawler = new Crawler($tileset);
            $sourcePath = $terrainPath . '/' . $tilesetCrawler->attr('source');
            $sourceContentPathA = explode('/', $sourcePath);
            array_pop($sourceContentPathA);
            $sourceContentPath = implode('/', $sourceContentPathA) . '/';

            $sourceContent = file_get_contents($sourcePath);
            $crawler = new Crawler($sourceContent);

            $tilesetNode = $crawler->filterXPath('//tileset');
            $imageNode = $crawler->filterXPath('//tileset/image');

            $sourcePathParts = explode('/', $tilesetCrawler->attr('source'));
            $tilesetName = array_pop($sourcePathParts);

            $sanitizePath = str_replace('../', '', $sourceContentPath);
            $sanitizePath = str_replace($terrainPath . '/', '', $sanitizePath);

            $animations = $this->parseTileAnimations($crawler);

            $terrains[$tilesetCrawler->attr('firstgid')] = [
                'fullPath' => $sourceContentPath,
                'sanitizePath' => $sanitizePath,
                'image' => $imageNode->attr('source'),
                'tilesetName' => $tilesetName,
                'tilewidth' => $tilesetNode->attr('tilewidth'),
                'tileheight' => $tilesetNode->attr('tileheight'),
                'tilecount' => $tilesetNode->attr('tilecount'),
                'columns' => $tilesetNode->attr('columns'),
                'imageWidth' => $imageNode->attr('width'),
                'imageHeight' => $imageNode->attr('height'),
                'firstgid' => (int) $tilesetCrawler->attr('firstgid'),
                'animations' => $animations,
            ];
        }

        return $terrains;
    }

    /**
     * Parse tile layers (visual + collision) and build the cell grid.
     *
     * @return array{0: array, 1: string[]} [cells, slugs]
     */
    public function parseLayers(Crawler $fileCrawler, array $terrains, int $mapWidth, int $mapHeight, int $offsetX, int $offsetY): array
    {
        $cells = [];
        $slugs = [];
        $layers = $fileCrawler->filterXPath('//map/layer');

        $layerIdx = 0;
        foreach ($layers as $layer) {
            $layerCrawler = new Crawler($layer);
            $layerData = $layerCrawler->filterXPath('//data')->html();
            $data = explode("\n", $layerData);
            $y = $offsetY * $mapHeight;

            foreach ($data as $values) {
                $x = $offsetX * $mapWidth;
                if ($values === '') {
                    continue;
                }

                $lineValues = explode(',', $values);
                foreach ($lineValues as $lineValue) {
                    if ($lineValue === '') {
                        continue;
                    }
                    $value = (int) $lineValue;

                    if (!isset($cells[$x][$y])) {
                        $cells[$x][$y] = [
                            'x' => $x,
                            'y' => $y,
                            'mouvement' => 0,
                            'cellIdx' => $value,
                            'layers' => [],
                        ];
                    }

                    if ($layerCrawler->attr('name') !== 'collision') {
                        $cells[$x][$y]['layers'][$layerIdx] = null;
                        if ($value !== 0) {
                            $cellMapIdx = key($terrains);
                            $tileset = '';
                            $source = '';
                            foreach ($terrains as $terrainIdx => $terrainValue) {
                                if ($value - (int) $terrainIdx >= 0) {
                                    $cellMapIdx = $terrainIdx;
                                    $tileset = $terrainValue['tilesetName'];
                                    $source = $terrainValue['sanitizePath'] . $terrainValue['tilesetName'];
                                } else {
                                    break;
                                }
                            }
                            $tilesetName = str_replace('.tsx', '', $tileset);
                            $cells[$x][$y]['layers'][$layerIdx] = [
                                'mapIdx' => $cellMapIdx,
                                'idxInMap' => $value - $cellMapIdx,
                                'tilesetName' => $tilesetName,
                                'source' => $source,
                            ];
                        }
                    } else {
                        $cellMapIdx = key($terrains);
                        foreach ($terrains as $terrainIdx => $terrainValue) {
                            $cellMapIdx = $value - (int) $terrainIdx > 0 ? $terrainIdx : $cellMapIdx;
                        }

                        $idxInMap = $value - $cellMapIdx;
                        $slug = $this->buildCollisionSlug($x, $y, $idxInMap);
                        $cells[$x][$y]['slug'] = $slug;
                        $slugs[] = $slug;

                        $cells[$x][$y]['mouvement'] = match ($idxInMap) {
                            1 => -1,
                            16 => 2,
                            default => 1,
                        };
                    }

                    ++$x;
                }
                ++$y;
            }
            ++$layerIdx;
        }

        return [$cells, $slugs];
    }

    /**
     * Parse object groups from Tiled Map Editor object layers.
     *
     * Supports the following object types (set via the "Type" / "Class" field in Tiled):
     *
     * - `portal`       — Teleportation point (properties: target_map_id, target_x, target_y)
     * - `mob_spawn`    — Monster spawn point (properties: monster_slug)
     * - `harvest_spot` — Gathering node (properties: slug, item_slug, item_min, item_max)
     * - `chest`        — Loot container (properties: item_slug, item_min, item_max)
     * - `zone`/`biome` — Rectangular region with biome metadata (properties: biome, weather,
     *                     music, light_level). Used by AreaSynchronizer to enrich Area entities.
     *
     * Pixel positions are converted to tile coordinates using tileWidth/tileHeight,
     * and offset by the chunk position (offsetX * mapWidth, offsetY * mapHeight).
     *
     * @return array<int, array>
     */
    public function parseObjectLayers(Crawler $fileCrawler, int $tileWidth, int $tileHeight, int $mapWidth, int $mapHeight, int $offsetX, int $offsetY): array
    {
        $objects = [];
        $objectGroups = $fileCrawler->filterXPath('//map/objectgroup');

        foreach ($objectGroups as $objectGroup) {
            $groupCrawler = new Crawler($objectGroup);
            $groupName = $groupCrawler->attr('name') ?? 'objects';

            $objectNodes = $groupCrawler->filterXPath('//object');
            foreach ($objectNodes as $object) {
                $objCrawler = new Crawler($object);
                $objType = $objCrawler->attr('type') ?? $objCrawler->attr('class') ?? '';
                $objName = $objCrawler->attr('name') ?? '';
                $objX = (int) floor((float) $objCrawler->attr('x') / $tileWidth) + $offsetX * $mapWidth;
                $objY = (int) floor((float) $objCrawler->attr('y') / $tileHeight) + $offsetY * $mapHeight;
                $objWidth = (int) ceil((float) ($objCrawler->attr('width') ?? $tileWidth) / $tileWidth);
                $objHeight = (int) ceil((float) ($objCrawler->attr('height') ?? $tileHeight) / $tileHeight);

                $properties = [];
                $propNodes = $objCrawler->filterXPath('//properties/property');
                foreach ($propNodes as $propNode) {
                    $propCrawler = new Crawler($propNode);
                    $properties[$propCrawler->attr('name')] = $propCrawler->attr('value') ?? $propCrawler->text('');
                }

                $objects[] = [
                    'group' => $groupName,
                    'type' => $objType,
                    'name' => $objName,
                    'x' => $objX,
                    'y' => $objY,
                    'width' => $objWidth,
                    'height' => $objHeight,
                    'properties' => $properties,
                ];
            }
        }

        return $objects;
    }

    /**
     * Validate parsed map data and return a list of errors.
     *
     * @return string[]
     */
    public function validateMap(array $map, string $terrainPath): array
    {
        $errors = [];

        foreach ($map['terrains'] as $terrain) {
            $imagePath = ($terrain['fullPath'] ?? $terrainPath . '/') . ($terrain['image'] ?? '');
            if (!file_exists($imagePath)) {
                $errors[] = sprintf('Tileset image not found: %s', $imagePath);
            }
        }

        $hasCollision = false;
        foreach ($map['cells'] as $col) {
            foreach ($col as $cell) {
                if (isset($cell['mouvement']) && $cell['mouvement'] === -1) {
                    $hasCollision = true;
                    break 2;
                }
            }
        }
        if (!$hasCollision) {
            $errors[] = 'No collision data found — did you add a "collision" layer in Tiled?';
        }

        $expectedWidth = $map['width'] ?? 0;
        $expectedHeight = $map['height'] ?? 0;
        $actualCols = count($map['cells']);
        if ($actualCols > 0) {
            $firstCol = reset($map['cells']);
            $actualRows = is_array($firstCol) ? count($firstCol) : 0;
            if ($actualCols !== $expectedWidth || $actualRows !== $expectedHeight) {
                $errors[] = sprintf(
                    'Dimension mismatch: expected %dx%d, got %dx%d cells',
                    $expectedWidth, $expectedHeight, $actualCols, $actualRows
                );
            }
        }

        foreach ($map['objects'] ?? [] as $obj) {
            $x = $obj['x'];
            $y = $obj['y'];
            $type = $obj['type'] ?? '';

            if (in_array($type, ['mob_spawn', 'npc_spawn', 'harvest_spot'])) {
                $cell = $map['cells'][$x][$y] ?? null;
                if ($cell !== null && ($cell['mouvement'] ?? 0) === -1) {
                    $errors[] = sprintf(
                        '%s "%s" at (%d,%d) is on a non-walkable cell',
                        $type, $obj['name'] ?? '', $x, $y
                    );
                }
            }

            if ($type === 'portal') {
                $props = $obj['properties'] ?? [];
                if (empty($props['target_map'])) {
                    $errors[] = sprintf(
                        'Portal "%s" at (%d,%d) missing "target_map" property',
                        $obj['name'] ?? '', $x, $y
                    );
                }
                if (empty($props['target_x']) || empty($props['target_y'])) {
                    $errors[] = sprintf(
                        'Portal "%s" at (%d,%d) missing "target_x" or "target_y" property',
                        $obj['name'] ?? '', $x, $y
                    );
                }
            }

            if ($type === 'mob_spawn') {
                $props = $obj['properties'] ?? [];
                if (empty($props['monster_slug'])) {
                    $errors[] = sprintf(
                        'mob_spawn "%s" at (%d,%d) missing "monster_slug" property',
                        $obj['name'] ?? '', $x, $y
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * Extract tile animation data from a TSX tileset.
     *
     * @return array<int, array<int, array{tileid: int, duration: int}>> keyed by local tile ID
     */
    private function parseTileAnimations(Crawler $crawler): array
    {
        $animations = [];
        $tileNodes = $crawler->filterXPath('//tileset/tile');

        foreach ($tileNodes as $tileNode) {
            $tileCrawler = new Crawler($tileNode);
            $animationFrames = $tileCrawler->filterXPath('//animation/frame');

            if ($animationFrames->count() === 0) {
                continue;
            }

            $tileId = (int) $tileCrawler->attr('id');
            $frames = [];

            foreach ($animationFrames as $frame) {
                $frameCrawler = new Crawler($frame);
                $frames[] = [
                    'tileid' => (int) $frameCrawler->attr('tileid'),
                    'duration' => (int) $frameCrawler->attr('duration'),
                ];
            }

            $animations[$tileId] = $frames;
        }

        return $animations;
    }

    private function buildCollisionSlug(int $x, int $y, int $idxInMap): string
    {
        return match ($idxInMap) {
            1 => $x . '.' . $y . '_-1_-1:-1:-1:-1',
            2 => $x . '.' . $y . '_0_0:0:0:-1',
            3 => $x . '.' . $y . '_0_0:-1:0:0',
            4 => $x . '.' . $y . '_0_0:0:-1:0',
            5 => $x . '.' . $y . '_0_-1:0:0:0',
            6 => $x . '.' . $y . '_0_-1:0:-1:0',
            7 => $x . '.' . $y . '_0_0:-1:0:-1',
            8 => $x . '.' . $y . '_0_-1:-1:0:0',
            9 => $x . '.' . $y . '_0_0:-1:-1:0',
            10 => $x . '.' . $y . '_0_0:0:-1:-1',
            11 => $x . '.' . $y . '_0_-1:0:0:-1',
            12 => $x . '.' . $y . '_0_0:-1:-1:-1',
            13 => $x . '.' . $y . '_0_-1:-1:0:-1',
            14 => $x . '.' . $y . '_0_-1:-1:-1:0',
            15 => $x . '.' . $y . '_0_-1:0:-1:-1',
            16 => $x . '.' . $y . '_2_0:0:0:0',
            default => $x . '.' . $y . '_0_0:0:0:0',
        };
    }
}
