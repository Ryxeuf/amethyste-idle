<?php

namespace App\Tests\Unit\GameEngine\Terrain;

use App\GameEngine\Terrain\TilesetRegistry;
use App\GameEngine\Terrain\WangTileResolver;
use PHPUnit\Framework\TestCase;

class WangTileResolverTest extends TestCase
{
    private WangTileResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new WangTileResolver();
    }

    // --- Detection terrain ---

    public function testDetectTerrainSlugWater(): void
    {
        // Water center = local 124, GID = 1 + 124 = 125
        $this->assertSame('water', $this->resolver->detectTerrainSlug(125));
    }

    public function testDetectTerrainSlugWaterTransition(): void
    {
        // Water corner tile: local 91 (bitfield 2), GID = 1 + 91 = 92
        $this->assertSame('water', $this->resolver->detectTerrainSlug(92));
    }

    public function testDetectTerrainSlugGrass(): void
    {
        // Grass center = local 289, GID = 1 + 289 = 290
        $this->assertSame('grass', $this->resolver->detectTerrainSlug(290));
    }

    public function testDetectTerrainSlugGrassVariant(): void
    {
        // Grass variant: local 352, GID = 1 + 352 = 353
        $this->assertSame('grass', $this->resolver->detectTerrainSlug(353));
    }

    public function testDetectTerrainSlugBrickRoad(): void
    {
        // Brick Road center = local 491, GID = 1 + 491 = 492
        $this->assertSame('brick_road', $this->resolver->detectTerrainSlug(492));
    }

    public function testDetectTerrainSlugReturnsNullForForestTileset(): void
    {
        $this->assertNull($this->resolver->detectTerrainSlug(TilesetRegistry::FIRST_GID_FOREST + 10));
    }

    public function testDetectTerrainSlugReturnsNullForUnknownGid(): void
    {
        // GID 0 = aucune tile
        $this->assertNull($this->resolver->detectTerrainSlug(0));
    }

    // --- Supported terrains ---

    public function testGetSupportedTerrainsContainsExpectedSlugs(): void
    {
        $slugs = $this->resolver->getSupportedTerrains();

        $this->assertContains('water', $slugs);
        $this->assertContains('grass', $slugs);
        $this->assertContains('sand', $slugs);
        $this->assertContains('earth', $slugs);
        $this->assertContains('brick_road', $slugs);
        $this->assertContains('snow', $slugs);
    }

    // --- Resolve : cas ile (cellule isolee) ---

    public function testResolveIsolatedWaterCell(): void
    {
        // Une seule cellule d'eau au milieu d'herbe → bitfield 15 (full)
        $cells = $this->buildGrid(5, 5);
        $this->setTerrain($cells, 2, 2, 'water');

        $gid = $this->resolver->resolve($cells, 2, 2, 1, 'water');

        // Water full = local 124, GID = 125
        $this->assertSame(TilesetRegistry::FIRST_GID_TERRAIN + 124, $gid);
    }

    // --- Resolve : cellule voisine recoit une transition ---

    public function testResolveNeighborGetsTransition(): void
    {
        // Eau en (2,2), le voisin NW (1,1) devrait avoir un coin BR actif → bitfield 2
        $cells = $this->buildGrid(5, 5);
        $this->setTerrain($cells, 2, 2, 'water');

        $gid = $this->resolver->resolve($cells, 1, 1, 1, 'water');

        // Water bitfield 2 (corner BR) = local 91, GID = 92
        $this->assertSame(TilesetRegistry::FIRST_GID_TERRAIN + 91, $gid);
    }

    public function testResolveNeighborNorthGetsTopEdge(): void
    {
        // Eau en (2,2), le voisin Nord (2,1) devrait avoir BL+BR → bitfield 3
        $cells = $this->buildGrid(5, 5);
        $this->setTerrain($cells, 2, 2, 'water');

        $gid = $this->resolver->resolve($cells, 2, 1, 1, 'water');

        // Water bitfield 3 = local 92, GID = 93
        $this->assertSame(TilesetRegistry::FIRST_GID_TERRAIN + 92, $gid);
    }

    public function testResolveNeighborNEGetsCornerBL(): void
    {
        // Eau en (2,2), le voisin NE (3,1) devrait avoir BL → bitfield 1
        $cells = $this->buildGrid(5, 5);
        $this->setTerrain($cells, 2, 2, 'water');

        $gid = $this->resolver->resolve($cells, 3, 1, 1, 'water');

        // Water bitfield 1 = local 93, GID = 94
        $this->assertSame(TilesetRegistry::FIRST_GID_TERRAIN + 93, $gid);
    }

    // --- Resolve : bord de carte (pas de voisin) ---

    public function testResolveCornerOfMap(): void
    {
        // Eau en (0,0), coin de la carte
        $cells = $this->buildGrid(3, 3);
        $this->setTerrain($cells, 0, 0, 'water');

        $gid = $this->resolver->resolve($cells, 0, 0, 1, 'water');

        // Les voisins hors carte retournent false → seuls les coins touchant (0,0) sont actifs
        // TL: (0,0) est water → actif
        // TR: (0,0) est water → actif
        // BR: (0,0) est water → actif
        // BL: (0,0) est water → actif
        // bitfield = 15 (full)
        $this->assertSame(TilesetRegistry::FIRST_GID_TERRAIN + 124, $gid);
    }

    // --- Resolve : peninsule (ligne de 3 tiles) ---

    public function testResolvePeninsula(): void
    {
        // 3 tiles d'eau horizontales : (1,2), (2,2), (3,2)
        $cells = $this->buildGrid(5, 5);
        $this->setTerrain($cells, 1, 2, 'water');
        $this->setTerrain($cells, 2, 2, 'water');
        $this->setTerrain($cells, 3, 2, 'water');

        // Centre (2,2) : tous ses voisins lateraux sont eau → all corners actifs → full
        $gid = $this->resolver->resolve($cells, 2, 2, 1, 'water');
        $this->assertSame(TilesetRegistry::FIRST_GID_TERRAIN + 124, $gid);

        // Bord gauche (1,2) : TR=oui(self), BR=oui(self+right), TL=oui(self), BL=oui(self) → full
        $gid = $this->resolver->resolve($cells, 1, 2, 1, 'water');
        $this->assertSame(TilesetRegistry::FIRST_GID_TERRAIN + 124, $gid);
    }

    // --- Resolve : cellule sans terrain retourne 0 ---

    public function testResolveEmptyCellReturnsZero(): void
    {
        $cells = $this->buildGrid(5, 5);

        $gid = $this->resolver->resolve($cells, 2, 2, 1, 'water');

        $this->assertSame(0, $gid);
    }

    // --- Resolve : terrain inconnu retourne 0 ---

    public function testResolveUnknownTerrainReturnsZero(): void
    {
        $cells = $this->buildGrid(3, 3);

        $gid = $this->resolver->resolve($cells, 1, 1, 1, 'nonexistent');

        $this->assertSame(0, $gid);
    }

    // --- ResolveZone ---

    public function testResolveZoneReturnsModifiedCells(): void
    {
        $cells = $this->buildGrid(5, 5);
        $this->setTerrain($cells, 2, 2, 'sand');

        $modified = $this->resolver->resolveZone($cells, 2, 2, 2, 2, 1, 'sand');

        // Devrait modifier la cellule (2,2) + les 8 voisins qui ont des transitions
        $this->assertNotEmpty($modified);

        // Verifier que chaque cellule modifiee a les champs requis
        foreach ($modified as $cell) {
            $this->assertArrayHasKey('x', $cell);
            $this->assertArrayHasKey('y', $cell);
            $this->assertArrayHasKey('layer', $cell);
            $this->assertArrayHasKey('gid', $cell);
            $this->assertSame(1, $cell['layer']);
            $this->assertGreaterThan(0, $cell['gid']);
        }
    }

    public function testResolveZoneSkipsUnchangedCells(): void
    {
        $cells = $this->buildGrid(5, 5);
        $this->setTerrain($cells, 2, 2, 'sand');

        // Premiere passe
        $modified1 = $this->resolver->resolveZone($cells, 2, 2, 2, 2, 1, 'sand');
        $this->assertNotEmpty($modified1);

        // Appliquer les changements
        foreach ($modified1 as $change) {
            $key = $change['x'] . '.' . $change['y'];
            $gid = $change['gid'];
            $tileset = $this->findTilesetForGid($gid);
            $cells[$key]['layers'][$change['layer']] = [
                'mapIdx' => $tileset['firstGid'],
                'idxInMap' => $gid - $tileset['firstGid'],
            ];
        }

        // Seconde passe → aucune modification
        $modified2 = $this->resolver->resolveZone($cells, 2, 2, 2, 2, 1, 'sand');
        $this->assertEmpty($modified2);
    }

    // --- GetTerrainData ---

    public function testGetTerrainDataWater(): void
    {
        $data = $this->resolver->getTerrainData('water');

        $this->assertNotNull($data);
        $this->assertSame(TilesetRegistry::FIRST_GID_TERRAIN + 124, $data['centerGid']);
        $this->assertArrayHasKey('tiles', $data);
        // 15 bitfields (1 a 15, dont diagonales 5 et 10)
        $this->assertCount(15, $data['tiles']);
    }

    public function testGetTerrainDataReturnsNullForUnknown(): void
    {
        $this->assertNull($this->resolver->getTerrainData('nonexistent'));
    }

    // --- getAllTerrainData ---

    public function testGetAllTerrainDataContainsAllSlugs(): void
    {
        $allData = $this->resolver->getAllTerrainData();
        $slugs = $this->resolver->getSupportedTerrains();

        foreach ($slugs as $slug) {
            $this->assertArrayHasKey($slug, $allData, "Missing terrain data for slug: $slug");
        }
    }

    // --- Helpers ---

    /**
     * Construit une grille de cellules vide.
     *
     * @return array<string, array{layers: list<null>}>
     */
    private function buildGrid(int $width, int $height): array
    {
        $cells = [];
        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                $cells[$x . '.' . $y] = [
                    'layers' => [null, null, null, null],
                ];
            }
        }

        return $cells;
    }

    /**
     * Place une tile "full" du terrain donne sur le layer 1.
     *
     * @param array<string, array{layers: list<array{mapIdx: int, idxInMap: int}|null>}> &$cells
     */
    private function setTerrain(array &$cells, int $x, int $y, string $terrainSlug): void
    {
        $data = $this->resolver->getTerrainData($terrainSlug);
        if ($data === null) {
            return;
        }

        $centerGid = $data['centerGid'];
        $tileset = $this->findTilesetForGid($centerGid);

        $cells[$x . '.' . $y]['layers'][1] = [
            'mapIdx' => $tileset['firstGid'],
            'idxInMap' => $centerGid - $tileset['firstGid'],
        ];
    }

    /**
     * @return array{firstGid: int}
     */
    private function findTilesetForGid(int $gid): array
    {
        if ($gid >= TilesetRegistry::FIRST_GID_COLLISIONS) {
            return ['firstGid' => TilesetRegistry::FIRST_GID_COLLISIONS];
        }
        if ($gid >= TilesetRegistry::FIRST_GID_BASECHIP_PIPO) {
            return ['firstGid' => TilesetRegistry::FIRST_GID_BASECHIP_PIPO];
        }
        if ($gid >= TilesetRegistry::FIRST_GID_FOREST) {
            return ['firstGid' => TilesetRegistry::FIRST_GID_FOREST];
        }

        return ['firstGid' => TilesetRegistry::FIRST_GID_TERRAIN];
    }
}
