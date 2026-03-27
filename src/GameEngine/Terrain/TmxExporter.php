<?php

namespace App\GameEngine\Terrain;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Pnj;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Exports a Map entity (stored as Area.fullData JSON) to Tiled-compatible TMX XML.
 */
class TmxExporter
{
    private const LAYER_NAMES = ['background', 'ground', 'decoration', 'overlay'];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Export a Map to TMX XML string.
     */
    public function export(Map $map): string
    {
        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();

        // Build the full cell grid from all areas
        $grid = $this->buildGrid($map, $areaWidth, $areaHeight);
        $mapWidth = $grid['width'];
        $mapHeight = $grid['height'];
        $cells = $grid['cells'];

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString(' ');
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('map');
        $xml->writeAttribute('version', '1.10');
        $xml->writeAttribute('tiledversion', '1.11.2');
        $xml->writeAttribute('orientation', 'orthogonal');
        $xml->writeAttribute('renderorder', 'left-up');
        $xml->writeAttribute('width', (string) $mapWidth);
        $xml->writeAttribute('height', (string) $mapHeight);
        $xml->writeAttribute('tilewidth', '32');
        $xml->writeAttribute('tileheight', '32');
        $xml->writeAttribute('infinite', '0');
        $xml->writeAttribute('nextlayerid', '7');
        $xml->writeAttribute('nextobjectid', '1');

        // Tilesets
        $this->writeTilesets($xml);

        // Tile layers (4 visual + collision)
        for ($layerIdx = 0; $layerIdx < 4; ++$layerIdx) {
            $this->writeTileLayer($xml, $layerIdx + 1, self::LAYER_NAMES[$layerIdx], $mapWidth, $mapHeight, $cells, $layerIdx);
        }
        $this->writeCollisionLayer($xml, 5, $mapWidth, $mapHeight, $cells);

        // Object groups (entities)
        $this->writeObjectGroups($xml, $map);

        $xml->endElement(); // map
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Build the filename for export.
     */
    public function getFilename(Map $map): string
    {
        $worldId = $map->getWorld()->getId();
        $mapName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $map->getName());
        $mapName = strtolower(trim((string) $mapName, '-'));

        return sprintf('world-%d-map-%s.tmx', $worldId, $mapName);
    }

    /**
     * @return array{width: int, height: int, cells: array<int, array<int, array>>}
     */
    private function buildGrid(Map $map, int $areaWidth, int $areaHeight): array
    {
        $cells = [];
        $maxX = 0;
        $maxY = 0;

        foreach ($map->getAreas() as $area) {
            $areaCoords = explode('.', $area->getCoordinates());
            $areaX = (int) $areaCoords[0];
            $areaY = (int) ($areaCoords[1] ?? 0);

            $areaMinGlobalX = $areaX * $areaWidth;
            $areaMinGlobalY = $areaY * $areaHeight;

            $areaData = $area->getFullDataArray();
            $cellsData = $areaData['cells'] ?? [];

            foreach ($cellsData as $lx => $column) {
                foreach ($column as $ly => $cellData) {
                    if ($cellData === null) {
                        continue;
                    }

                    $globalX = $areaMinGlobalX + ($cellData['x'] ?? $lx);
                    $globalY = $areaMinGlobalY + ($cellData['y'] ?? $ly);

                    $cells[$globalX][$globalY] = $cellData;

                    if ($globalX >= $maxX) {
                        $maxX = $globalX + 1;
                    }
                    if ($globalY >= $maxY) {
                        $maxY = $globalY + 1;
                    }
                }
            }
        }

        return ['width' => $maxX, 'height' => $maxY, 'cells' => $cells];
    }

    private function writeTilesets(\XMLWriter $xml): void
    {
        $sources = [
            TilesetRegistry::FIRST_GID_TERRAIN => 'tileset/Terrain.tsx',
            TilesetRegistry::FIRST_GID_FOREST => 'tileset/forest.tsx',
            TilesetRegistry::FIRST_GID_BASECHIP_PIPO => 'tileset/BaseChip_pipo.tsx',
            TilesetRegistry::FIRST_GID_COLLISIONS => 'tileset/Collisions.tsx',
        ];

        foreach ($sources as $firstGid => $source) {
            $xml->startElement('tileset');
            $xml->writeAttribute('firstgid', (string) $firstGid);
            $xml->writeAttribute('source', $source);
            $xml->endElement();
        }
    }

    private function writeTileLayer(\XMLWriter $xml, int $layerId, string $name, int $width, int $height, array $cells, int $layerIdx): void
    {
        $xml->startElement('layer');
        $xml->writeAttribute('id', (string) $layerId);
        $xml->writeAttribute('name', $name);
        $xml->writeAttribute('width', (string) $width);
        $xml->writeAttribute('height', (string) $height);

        $csv = $this->buildLayerCsv($width, $height, $cells, $layerIdx);

        $xml->startElement('data');
        $xml->writeAttribute('encoding', 'csv');
        $xml->text("\n" . $csv);
        $xml->endElement(); // data

        $xml->endElement(); // layer
    }

    private function buildLayerCsv(int $width, int $height, array $cells, int $layerIdx): string
    {
        $rows = [];
        for ($y = 0; $y < $height; ++$y) {
            $row = [];
            for ($x = 0; $x < $width; ++$x) {
                $cell = $cells[$x][$y] ?? null;
                $gid = 0;

                if ($cell !== null) {
                    $layers = $cell['layers'] ?? [];
                    $layer = $layers[$layerIdx] ?? null;
                    if ($layer !== null) {
                        $gid = ($layer['mapIdx'] ?? 0) + ($layer['idxInMap'] ?? 0);
                    }
                }

                $row[] = $gid;
            }
            $rows[] = implode(',', $row);
        }

        return implode(",\n", $rows) . "\n";
    }

    private function writeCollisionLayer(\XMLWriter $xml, int $layerId, int $width, int $height, array $cells): void
    {
        $xml->startElement('layer');
        $xml->writeAttribute('id', (string) $layerId);
        $xml->writeAttribute('name', 'collision');
        $xml->writeAttribute('width', (string) $width);
        $xml->writeAttribute('height', (string) $height);
        $xml->writeAttribute('visible', '0');

        $rows = [];
        for ($y = 0; $y < $height; ++$y) {
            $row = [];
            for ($x = 0; $x < $width; ++$x) {
                $cell = $cells[$x][$y] ?? null;
                $gid = 0;

                if ($cell !== null) {
                    $gid = $this->movementToCollisionGid($cell);
                }

                $row[] = $gid;
            }
            $rows[] = implode(',', $row);
        }

        $csv = implode(",\n", $rows) . "\n";

        $xml->startElement('data');
        $xml->writeAttribute('encoding', 'csv');
        $xml->text("\n" . $csv);
        $xml->endElement(); // data

        $xml->endElement(); // layer
    }

    /**
     * Convert cell movement & slug data back to a collision GID.
     */
    private function movementToCollisionGid(array $cell): int
    {
        $movement = $cell['mouvement'] ?? 0;

        if ($movement === -1) {
            // Full wall
            return TilesetRegistry::FIRST_GID_COLLISIONS + 1;
        }

        if ($movement === 2) {
            // Climb
            return TilesetRegistry::FIRST_GID_COLLISIONS + 16;
        }

        // Check directional borders from slug
        if (isset($cell['slug'])) {
            $slugData = CellHelper::getDataFromSlug($cell['slug']);
            $n = $slugData['north'] ?? 0;
            $e = $slugData['east'] ?? 0;
            $s = $slugData['south'] ?? 0;
            $w = $slugData['west'] ?? 0;

            return $this->bordersToCollisionGid($n, $e, $s, $w);
        }

        return 0;
    }

    /**
     * Map directional border values to local collision tile ID.
     */
    private function bordersToCollisionGid(int $n, int $e, int $s, int $w): int
    {
        if ($n === 0 && $e === 0 && $s === 0 && $w === 0) {
            return 0;
        }

        // Full wall
        if ($n === -1 && $e === -1 && $s === -1 && $w === -1) {
            return TilesetRegistry::FIRST_GID_COLLISIONS + 1;
        }

        // Directional borders — matching TmxParser::buildCollisionSlug() mapping
        $borderMap = [
            '0:0:0:-1' => 2,   // W only
            '0:-1:0:0' => 3,   // E only
            '0:0:-1:0' => 4,   // S only
            '-1:0:0:0' => 5,   // N only
            '-1:0:-1:0' => 6,  // N+S
            '0:-1:0:-1' => 7,  // E+W
            '-1:-1:0:0' => 8,  // N+E
            '0:-1:-1:0' => 9,  // E+S
            '0:0:-1:-1' => 10, // S+W
            '-1:0:0:-1' => 11, // N+W
            '0:-1:-1:-1' => 12, // E+S+W
            '-1:-1:0:-1' => 13, // N+E+W
            '-1:-1:-1:0' => 14, // N+E+S
            '-1:0:-1:-1' => 15, // N+S+W
        ];

        $key = $n . ':' . $e . ':' . $s . ':' . $w;

        if (isset($borderMap[$key])) {
            return TilesetRegistry::FIRST_GID_COLLISIONS + $borderMap[$key];
        }

        // Fallback: any non-zero border → wall
        return TilesetRegistry::FIRST_GID_COLLISIONS + 1;
    }

    private function writeObjectGroups(\XMLWriter $xml, Map $map): void
    {
        $objects = $this->collectObjects($map);

        if ($objects === []) {
            return;
        }

        $xml->startElement('objectgroup');
        $xml->writeAttribute('id', '6');
        $xml->writeAttribute('name', 'objects');

        $objectId = 1;
        foreach ($objects as $obj) {
            $xml->startElement('object');
            $xml->writeAttribute('id', (string) $objectId++);
            $xml->writeAttribute('name', $obj['name']);
            $xml->writeAttribute('type', $obj['type']);
            $xml->writeAttribute('x', (string) ($obj['x'] * 32));
            $xml->writeAttribute('y', (string) ($obj['y'] * 32));
            $xml->writeAttribute('width', '32');
            $xml->writeAttribute('height', '32');

            if ($obj['properties'] !== []) {
                $xml->startElement('properties');
                foreach ($obj['properties'] as $propName => $propValue) {
                    $xml->startElement('property');
                    $xml->writeAttribute('name', $propName);
                    $xml->writeAttribute('value', (string) $propValue);
                    $xml->endElement();
                }
                $xml->endElement(); // properties
            }

            $xml->endElement(); // object
        }

        $xml->endElement(); // objectgroup
    }

    /**
     * @return list<array{name: string, type: string, x: int, y: int, properties: array<string, string|int>}>
     */
    private function collectObjects(Map $map): array
    {
        $objects = [];

        // Portals
        foreach ($this->em->getRepository(ObjectLayer::class)->findBy(['map' => $map, 'type' => ObjectLayer::TYPE_PORTAL]) as $portal) {
            $coords = explode('.', $portal->getCoordinates());
            $properties = [];
            if ($portal->getDestinationMapId() !== null) {
                $properties['target_map_id'] = $portal->getDestinationMapId();
            }
            if ($portal->getDestinationCoordinates() !== null) {
                $destCoords = explode('.', $portal->getDestinationCoordinates());
                $properties['target_x'] = (int) $destCoords[0];
                $properties['target_y'] = (int) ($destCoords[1] ?? 0);
            }
            $objects[] = [
                'name' => $portal->getName(),
                'type' => 'portal',
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
                'properties' => $properties,
            ];
        }

        // Mob spawns
        foreach ($this->em->getRepository(Mob::class)->findBy(['map' => $map]) as $mob) {
            $coords = explode('.', $mob->getCoordinates());
            $objects[] = [
                'name' => $mob->getMonster()->getName(),
                'type' => 'mob_spawn',
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
                'properties' => [
                    'monster_slug' => $mob->getMonster()->getSlug(),
                    'level' => $mob->getLevel(),
                ],
            ];
        }

        // Harvest spots
        foreach ($this->em->getRepository(ObjectLayer::class)->findBy(['map' => $map, 'type' => ObjectLayer::TYPE_HARVEST_SPOT]) as $spot) {
            $coords = explode('.', $spot->getCoordinates());
            $properties = [];
            if ($spot->getRequiredToolType() !== null) {
                $properties['required_tool'] = $spot->getRequiredToolType();
            }
            if ($spot->getRespawnDelay() !== null) {
                $properties['respawn_delay'] = $spot->getRespawnDelay();
            }
            $objects[] = [
                'name' => $spot->getName(),
                'type' => 'harvest_spot',
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
                'properties' => $properties,
            ];
        }

        // PNJs
        foreach ($this->em->getRepository(Pnj::class)->findBy(['map' => $map]) as $pnj) {
            $coords = explode('.', $pnj->getCoordinates());
            $objects[] = [
                'name' => $pnj->getName(),
                'type' => 'npc_spawn',
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
                'properties' => [
                    'class_type' => $pnj->getClassType(),
                ],
            ];
        }

        return $objects;
    }
}
