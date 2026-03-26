<?php

namespace App\Tests\Unit\GameEngine\Terrain;

use App\GameEngine\Terrain\TilesetRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

class TilesetRegistryTest extends TestCase
{
    private TilesetRegistry $registry;

    protected function setUp(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')
            ->willReturnCallback(fn (string $path) => '/assets/' . $path);

        $this->registry = new TilesetRegistry($packages);
    }

    public function testGetTilesetsReturnsFourTilesets(): void
    {
        $tilesets = $this->registry->getTilesets();

        $this->assertCount(4, $tilesets);
    }

    public function testGetTilesetsOrderedByFirstGid(): void
    {
        $tilesets = $this->registry->getTilesets();

        $gids = array_column($tilesets, 'firstGid');
        $sorted = $gids;
        sort($sorted);

        $this->assertSame($sorted, $gids);
    }

    public function testGetTilesetsContainsImagePath(): void
    {
        $tilesets = $this->registry->getTilesets();

        foreach ($tilesets as $tileset) {
            $this->assertArrayHasKey('imagePath', $tileset);
            $this->assertStringContainsString('terrain/', $tileset['imagePath']);
        }
    }

    public function testGetTilesetForGidTerrain(): void
    {
        // GID 1 = premiere tile du tileset Terrain (firstGid=1)
        $tileset = $this->registry->getTilesetForGid(1);

        $this->assertNotNull($tileset);
        $this->assertSame('terrain', $tileset['name']);
        $this->assertSame(TilesetRegistry::FIRST_GID_TERRAIN, $tileset['firstGid']);
    }

    public function testGetTilesetForGidForest(): void
    {
        // GID 1025 = premiere tile du tileset Forest (firstGid=1025)
        $tileset = $this->registry->getTilesetForGid(1025);

        $this->assertNotNull($tileset);
        $this->assertSame('forest', $tileset['name']);
        $this->assertSame(TilesetRegistry::FIRST_GID_FOREST, $tileset['firstGid']);
    }

    public function testGetTilesetForGidForestMiddle(): void
    {
        // GID 2000 = Forest firstGid(1025) + 975
        $tileset = $this->registry->getTilesetForGid(2000);

        $this->assertNotNull($tileset);
        $this->assertSame('forest', $tileset['name']);
    }

    public function testGetTilesetForGidCollisions(): void
    {
        $tileset = $this->registry->getTilesetForGid(TilesetRegistry::GID_COLLISION_WALL);

        $this->assertNotNull($tileset);
        $this->assertSame('collisions', $tileset['name']);
    }

    public function testGetTilesetForGidBaseChipPipo(): void
    {
        // GID 4097 = premiere tile du tileset BaseChip_pipo
        $tileset = $this->registry->getTilesetForGid(4097);

        $this->assertNotNull($tileset);
        $this->assertSame('BaseChip_pipo', $tileset['name']);
    }

    public function testGetTilesetForGidZeroReturnsNull(): void
    {
        $tileset = $this->registry->getTilesetForGid(0);

        $this->assertNull($tileset);
    }

    public function testGetTilesetForGidOutOfRangeReturnsNull(): void
    {
        // GID bien au-dela du dernier tileset (Collisions: 5161 + 18 = 5179)
        $tileset = $this->registry->getTilesetForGid(99999);

        $this->assertNull($tileset);
    }

    public function testGetLocalTileIdTerrain(): void
    {
        // GID 100 dans Terrain (firstGid=1) => local = 99
        $localId = $this->registry->getLocalTileId(100);

        $this->assertSame(99, $localId);
    }

    public function testGetLocalTileIdForest(): void
    {
        // GID 1379 dans Forest (firstGid=1025) => local = 354
        $localId = $this->registry->getLocalTileId(1379);

        $this->assertSame(354, $localId);
    }

    public function testGetLocalTileIdCollision(): void
    {
        // GID 5162 dans Collisions (firstGid=5161) => local = 1
        $localId = $this->registry->getLocalTileId(5162);

        $this->assertSame(1, $localId);
    }

    public function testGetLocalTileIdBaseChip(): void
    {
        // GID 4097 dans BaseChip_pipo (firstGid=4097) => local = 0
        $localId = $this->registry->getLocalTileId(4097);

        $this->assertSame(0, $localId);
    }

    public function testGetColumnsForName(): void
    {
        $this->assertSame(32, $this->registry->getColumnsForName('terrain'));
        $this->assertSame(16, $this->registry->getColumnsForName('forest'));
        $this->assertSame(8, $this->registry->getColumnsForName('BaseChip_pipo'));
        $this->assertSame(6, $this->registry->getColumnsForName('collisions'));
    }

    public function testGetColumnsForNameCaseInsensitive(): void
    {
        $this->assertSame(32, $this->registry->getColumnsForName('Terrain'));
        $this->assertSame(16, $this->registry->getColumnsForName('FOREST'));
    }

    public function testGetColumnsForNameUnknownReturnsDefault(): void
    {
        $this->assertSame(32, $this->registry->getColumnsForName('unknown'));
    }

    public function testGetTilesetsForApiFormat(): void
    {
        $tilesets = $this->registry->getTilesetsForApi();

        $this->assertCount(4, $tilesets);

        foreach ($tilesets as $tileset) {
            $this->assertArrayHasKey('name', $tileset);
            $this->assertArrayHasKey('image', $tileset);
            $this->assertArrayHasKey('columns', $tileset);
            $this->assertArrayHasKey('tileWidth', $tileset);
            $this->assertArrayHasKey('tileHeight', $tileset);
            $this->assertArrayHasKey('firstGid', $tileset);
            // Ne doit PAS contenir tileCount ou imageFile
            $this->assertArrayNotHasKey('tileCount', $tileset);
            $this->assertArrayNotHasKey('imageFile', $tileset);
        }
    }

    public function testGrassGidConstants(): void
    {
        $tileset = $this->registry->getTilesetForGid(TilesetRegistry::GID_GRASS_BASE);
        $this->assertNotNull($tileset);
        $this->assertSame('terrain', $tileset['name']);

        $localId = $this->registry->getLocalTileId(TilesetRegistry::GID_GRASS_BASE);
        $this->assertSame(293, $localId);
    }

    public function testFirstGidBoundaries(): void
    {
        // Derniere tile de Terrain: firstGid(1) + tileCount(1024) - 1 = 1024
        $tileset = $this->registry->getTilesetForGid(1024);
        $this->assertNotNull($tileset);
        $this->assertSame('terrain', $tileset['name']);

        // Premiere tile de Forest: 1025
        $tileset = $this->registry->getTilesetForGid(1025);
        $this->assertNotNull($tileset);
        $this->assertSame('forest', $tileset['name']);

        // Derniere tile de Forest: 1025 + 3072 - 1 = 4096
        $tileset = $this->registry->getTilesetForGid(4096);
        $this->assertNotNull($tileset);
        $this->assertSame('forest', $tileset['name']);

        // Premiere tile de BaseChip_pipo: 4097
        $tileset = $this->registry->getTilesetForGid(4097);
        $this->assertNotNull($tileset);
        $this->assertSame('BaseChip_pipo', $tileset['name']);

        // Derniere tile de BaseChip_pipo: 4097 + 1064 - 1 = 5160
        $tileset = $this->registry->getTilesetForGid(5160);
        $this->assertNotNull($tileset);
        $this->assertSame('BaseChip_pipo', $tileset['name']);

        // Premiere tile de Collisions: 5161
        $tileset = $this->registry->getTilesetForGid(5161);
        $this->assertNotNull($tileset);
        $this->assertSame('collisions', $tileset['name']);
    }
}
