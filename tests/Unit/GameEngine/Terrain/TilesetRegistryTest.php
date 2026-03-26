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

    public function testGetTilesetForGidForest(): void
    {
        $tileset = $this->registry->getTilesetForGid(1);

        $this->assertNotNull($tileset);
        $this->assertSame('forest', $tileset['name']);
        $this->assertSame(TilesetRegistry::FIRST_GID_FOREST, $tileset['firstGid']);
    }

    public function testGetTilesetForGidTerrain(): void
    {
        $tileset = $this->registry->getTilesetForGid(3073);

        $this->assertNotNull($tileset);
        $this->assertSame('terrain', $tileset['name']);
    }

    public function testGetTilesetForGidTerrainMiddle(): void
    {
        // GID 3427 = Terrain firstGid(3073) + 354
        $tileset = $this->registry->getTilesetForGid(3427);

        $this->assertNotNull($tileset);
        $this->assertSame('terrain', $tileset['name']);
    }

    public function testGetTilesetForGidCollisions(): void
    {
        $tileset = $this->registry->getTilesetForGid(TilesetRegistry::GID_COLLISION_WALL);

        $this->assertNotNull($tileset);
        $this->assertSame('collisions', $tileset['name']);
    }

    public function testGetTilesetForGidBaseChipPipo(): void
    {
        $tileset = $this->registry->getTilesetForGid(4115);

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
        // GID bien au-dela du dernier tileset (BaseChip_pipo: 4115 + 1064 = 5179)
        $tileset = $this->registry->getTilesetForGid(99999);

        $this->assertNull($tileset);
    }

    public function testGetLocalTileIdForest(): void
    {
        // GID 100 dans forest (firstGid=1) => local = 99
        $localId = $this->registry->getLocalTileId(100);

        $this->assertSame(99, $localId);
    }

    public function testGetLocalTileIdTerrain(): void
    {
        // GID 3427 dans Terrain (firstGid=3073) => local = 354
        $localId = $this->registry->getLocalTileId(3427);

        $this->assertSame(354, $localId);
    }

    public function testGetLocalTileIdCollision(): void
    {
        // GID 4098 dans Collisions (firstGid=4097) => local = 1
        $localId = $this->registry->getLocalTileId(4098);

        $this->assertSame(1, $localId);
    }

    public function testGetLocalTileIdBaseChip(): void
    {
        // GID 4115 dans BaseChip_pipo (firstGid=4115) => local = 0
        $localId = $this->registry->getLocalTileId(4115);

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
        // Derniere tile de forest: firstGid(1) + tileCount(3072) - 1 = 3072
        $tileset = $this->registry->getTilesetForGid(3072);
        $this->assertNotNull($tileset);
        $this->assertSame('forest', $tileset['name']);

        // Premiere tile de terrain: 3073
        $tileset = $this->registry->getTilesetForGid(3073);
        $this->assertNotNull($tileset);
        $this->assertSame('terrain', $tileset['name']);
    }
}
