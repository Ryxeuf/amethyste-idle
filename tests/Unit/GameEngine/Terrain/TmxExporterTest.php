<?php

namespace App\Tests\Unit\GameEngine\Terrain;

use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\World;
use App\GameEngine\Terrain\TmxExporter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class TmxExporterTest extends TestCase
{
    private TmxExporter $exporter;

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findBy')->willReturn([]);
        $em->method('getRepository')->willReturn($repo);

        $this->exporter = new TmxExporter($em);
    }

    public function testExportReturnsValidXml(): void
    {
        $map = $this->createTestMap(3, 3);

        $xml = $this->exporter->export($map);

        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'Exported TMX is not valid XML');
    }

    public function testExportContainsMapAttributes(): void
    {
        $map = $this->createTestMap(10, 8);

        $xml = $this->exporter->export($map);
        $doc = $this->loadXml($xml);
        $mapNode = $doc->documentElement;

        $this->assertSame('map', $mapNode->tagName);
        $this->assertSame('10', $mapNode->getAttribute('width'));
        $this->assertSame('8', $mapNode->getAttribute('height'));
        $this->assertSame('32', $mapNode->getAttribute('tilewidth'));
        $this->assertSame('32', $mapNode->getAttribute('tileheight'));
        $this->assertSame('orthogonal', $mapNode->getAttribute('orientation'));
    }

    public function testExportContainsFourTilesetReferences(): void
    {
        $map = $this->createTestMap(3, 3);

        $xml = $this->exporter->export($map);
        $doc = $this->loadXml($xml);

        $tilesets = $doc->getElementsByTagName('tileset');
        $this->assertSame(4, $tilesets->length);

        $this->assertSame('1', $tilesets->item(0)->getAttribute('firstgid'));
        $this->assertSame('tileset/Terrain.tsx', $tilesets->item(0)->getAttribute('source'));
        $this->assertSame('1025', $tilesets->item(1)->getAttribute('firstgid'));
        $this->assertSame('4097', $tilesets->item(2)->getAttribute('firstgid'));
        $this->assertSame('5161', $tilesets->item(3)->getAttribute('firstgid'));
    }

    public function testExportContainsFiveLayers(): void
    {
        $map = $this->createTestMap(3, 3);

        $xml = $this->exporter->export($map);
        $doc = $this->loadXml($xml);

        $layers = $doc->getElementsByTagName('layer');
        $this->assertSame(5, $layers->length);

        $names = [];
        for ($i = 0; $i < $layers->length; ++$i) {
            $names[] = $layers->item($i)->getAttribute('name');
        }
        $this->assertSame(['background', 'ground', 'decoration', 'overlay', 'collision'], $names);
    }

    public function testExportLayerContainsCsvData(): void
    {
        $map = $this->createTestMap(2, 2);

        $xml = $this->exporter->export($map);
        $doc = $this->loadXml($xml);

        $layers = $doc->getElementsByTagName('layer');
        $dataNode = $layers->item(0)->getElementsByTagName('data')->item(0);

        $this->assertSame('csv', $dataNode->getAttribute('encoding'));
        $this->assertNotEmpty($dataNode->textContent);
    }

    public function testExportPreservesTileGids(): void
    {
        $cells = [];
        // Cell (0,0): background has GID 355 (Terrain firstGid=1, localId=354)
        $cells[0][0] = [
            'x' => 0, 'y' => 0,
            'layers' => [
                ['mapIdx' => 1, 'idxInMap' => 354, 'tilesetName' => 'terrain', 'source' => 'tileset/Terrain.tsx'],
                null,
                null,
                null,
            ],
            'mouvement' => 0,
            'slug' => '0.0_0_0:0:0:0',
        ];
        // Cell (1,0): background has GID 293
        $cells[1][0] = [
            'x' => 1, 'y' => 0,
            'layers' => [
                ['mapIdx' => 1, 'idxInMap' => 292, 'tilesetName' => 'terrain', 'source' => 'tileset/Terrain.tsx'],
                null,
                null,
                null,
            ],
            'mouvement' => 0,
            'slug' => '1.0_0_0:0:0:0',
        ];
        // Cell (0,1): blocked wall
        $cells[0][1] = [
            'x' => 0, 'y' => 1,
            'layers' => [
                ['mapIdx' => 1, 'idxInMap' => 354, 'tilesetName' => 'terrain', 'source' => 'tileset/Terrain.tsx'],
                null,
                null,
                null,
            ],
            'mouvement' => -1,
            'slug' => '0.1_-1_-1:-1:-1:-1',
        ];
        $cells[1][1] = [
            'x' => 1, 'y' => 1,
            'layers' => [null, null, null, null],
            'mouvement' => 0,
            'slug' => '1.1_0_0:0:0:0',
        ];

        $map = $this->createTestMapWithCells(2, 2, $cells);

        $xml = $this->exporter->export($map);
        $doc = $this->loadXml($xml);

        // Check background layer CSV
        $bgLayer = $doc->getElementsByTagName('layer')->item(0);
        $csvData = trim($bgLayer->getElementsByTagName('data')->item(0)->textContent);
        $rows = array_filter(explode("\n", $csvData), fn ($r) => trim($r) !== '');

        // First row: GID 355, 293
        $firstRow = array_map('intval', explode(',', trim($rows[0], ',')));
        $this->assertSame(355, $firstRow[0]); // mapIdx(1) + idxInMap(354)
        $this->assertSame(293, $firstRow[1]); // mapIdx(1) + idxInMap(292)

        // Check collision layer — cell (0,1) should be wall GID 5162
        $collisionLayer = $doc->getElementsByTagName('layer')->item(4);
        $csvCollision = trim($collisionLayer->getElementsByTagName('data')->item(0)->textContent);
        $collisionRows = array_filter(explode("\n", $csvCollision), fn ($r) => trim($r) !== '');

        // Second row, first cell = wall
        $secondRow = array_map('intval', explode(',', trim($collisionRows[1], ',')));
        $this->assertSame(5162, $secondRow[0]); // FIRST_GID_COLLISIONS + 1
    }

    public function testExportCollisionDirectionalBorders(): void
    {
        $cells = [];
        // Cell with west-only border (idxInMap=2 in collision tile)
        $cells[0][0] = [
            'x' => 0, 'y' => 0,
            'layers' => [null, null, null, null],
            'mouvement' => 0,
            'slug' => '0.0_0_0:0:0:-1',
        ];

        $map = $this->createTestMapWithCells(1, 1, $cells);
        $xml = $this->exporter->export($map);
        $doc = $this->loadXml($xml);

        $collisionLayer = $doc->getElementsByTagName('layer')->item(4);
        $csv = trim($collisionLayer->getElementsByTagName('data')->item(0)->textContent);
        $values = array_map('intval', explode(',', trim(explode("\n", trim($csv))[0], ',')));

        // West-only = local ID 2 → GID = 5161 + 2 = 5163
        $this->assertSame(5163, $values[0]);
    }

    public function testGetFilename(): void
    {
        $world = $this->createMock(World::class);
        $world->method('getId')->willReturn(1);

        $map = $this->createMock(Map::class);
        $map->method('getWorld')->willReturn($world);
        $map->method('getName')->willReturn('Foret des Murmures');

        $filename = $this->exporter->getFilename($map);

        $this->assertSame('world-1-map-foret-des-murmures.tmx', $filename);
    }

    public function testExportEmptyMapHasZeroGids(): void
    {
        $map = $this->createTestMap(2, 2);
        $xml = $this->exporter->export($map);
        $doc = $this->loadXml($xml);

        $bgLayer = $doc->getElementsByTagName('layer')->item(0);
        $csv = trim($bgLayer->getElementsByTagName('data')->item(0)->textContent);
        $allValues = [];
        foreach (explode("\n", $csv) as $row) {
            $row = trim($row, ",\n\r ");
            if ($row === '') {
                continue;
            }
            foreach (explode(',', $row) as $v) {
                $allValues[] = (int) $v;
            }
        }

        $this->assertCount(4, $allValues); // 2x2
        $this->assertSame([0, 0, 0, 0], $allValues);
    }

    public function testExportNoObjectGroupWhenNoEntities(): void
    {
        $map = $this->createTestMap(3, 3);
        $xml = $this->exporter->export($map);
        $doc = $this->loadXml($xml);

        $objectGroups = $doc->getElementsByTagName('objectgroup');
        $this->assertSame(0, $objectGroups->length);
    }

    private function createTestMap(int $width, int $height): Map
    {
        $cells = [];
        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                $cells[$x][$y] = [
                    'x' => $x, 'y' => $y,
                    'layers' => [null, null, null, null],
                    'mouvement' => 0,
                    'slug' => $x . '.' . $y . '_0_0:0:0:0',
                ];
            }
        }

        return $this->createTestMapWithCells($width, $height, $cells);
    }

    private function createTestMapWithCells(int $width, int $height, array $cells): Map
    {
        $fullData = json_encode([
            'width' => $width,
            'height' => $height,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'cells' => $cells,
        ]);

        $world = $this->createMock(World::class);
        $world->method('getId')->willReturn(1);

        $area = $this->createMock(Area::class);
        $area->method('getCoordinates')->willReturn('0.0');
        $area->method('getFullDataArray')->willReturn(json_decode($fullData, true));

        $map = $this->createMock(Map::class);
        $map->method('getAreaWidth')->willReturn($width);
        $map->method('getAreaHeight')->willReturn($height);
        $map->method('getAreas')->willReturn(new ArrayCollection([$area]));
        $map->method('getWorld')->willReturn($world);
        $map->method('getName')->willReturn('TestMap');

        return $map;
    }

    private function loadXml(string $xml): \DOMDocument
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }
}
