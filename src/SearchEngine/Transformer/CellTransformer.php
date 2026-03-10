<?php

namespace App\SearchEngine\Transformer;

use App\Dto\Search\SearchCell;
use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\World;

class CellTransformer
{
    private array $terrains = [];

    public function resetTerrains(): void
    {
        $this->terrains = [];
    }

    public function addTerrains(array $terrains): self
    {
        foreach ($terrains as $key => $terrain) {
            $gid = $terrain['firstgid'] ?? (int) $key;
            $this->terrains[$gid] = array_merge([
                'columns' => 32,
                'tilewidth' => 32,
                'tileheight' => 32,
            ], $terrain);
        }

        return $this;
    }

    public function transform(array $data, Area $area, Map $map, World $world): SearchCell
    {
        $worldName = $world->getName();
        $mapName = $map->getName();
        $areaName = $area->getName();
        $slug = $data['slug'];
        $displayClass = 'cell';
        $movementCost = $data['mouvement'];
        $slugData = explode('_', $slug);
        $movesData = explode(':', $slugData[2]);
        $upCost = $movesData[0];
        $leftCost = $movesData[1];
        $downCost = $movesData[2];
        $rightCost = $movesData[3];

        $layers = [];
        $zIndex = 20;
        foreach ($data['layers'] as $layer) {
            if ($layer === null) {
                continue;
            }
            $style = ['z-index: '.$zIndex.' !important'];
            $zIndex++;

            $mapIdx = $layer['mapIdx'] ?? 0;
            $idxInMap = $layer['idxInMap'] ?? 0;
            $rawGid = $mapIdx + $idxInMap;

            $tileset = $this->findTilesetForGid($rawGid);
            $tileId = $rawGid - ($tileset['firstgid'] ?? 0);
            $tilesetName = $layer['tilesetName'] ?? $this->resolveTilesetName($tileset);

            $terrainName = pathinfo($tileset['image'] ?? 'terrain.png', PATHINFO_FILENAME);

            $layers[] = ['style' => implode('; ', $style), 'class' => $terrainName.' cell tileset-'.$tilesetName.'-cell-'.$tileId];
        }

        return new SearchCell(
            x: $data['x'],
            y: $data['y'],
            movementCost: (int)$movementCost,
            upCost: (int)$upCost,
            downCost: (int)$downCost,
            leftCost: (int)$leftCost,
            rightCost: (int)$rightCost,
            world: $worldName,
            map: [
                'id' => $map->getId(),
                'name' => $mapName,
                'areaWidth' => $map->getAreaWidth(),
                'areaHeight' => $map->getAreaHeight(),
            ],
            area: [
                'id' => $area->getId(),
                'name' => $areaName,
                'slug' => $area->getSlug(),
                'coordinates' => $area->getCoordinates(),
            ],
            slug: $slug,
            displayClass: $displayClass,
            layers: $layers,
        );
    }
    
    private function findTilesetForGid(int $gid): array
    {
        $selected = null;
        $maxFirstGid = 0;

        foreach ($this->terrains as $firstGid => $terrain) {
            if ($firstGid <= $gid && $firstGid > $maxFirstGid) {
                $maxFirstGid = $firstGid;
                $selected = $terrain;
            }
        }

        return $selected ?? [
            'image' => 'terrain.png',
            'firstgid' => 0,
            'columns' => 32,
            'tilewidth' => 32,
            'tileheight' => 32,
        ];
    }

    private function resolveTilesetName(array $terrain): string
    {
        if (isset($terrain['tilesetName'])) {
            return str_replace('.tsx', '', $terrain['tilesetName']);
        }

        $image = pathinfo($terrain['image'] ?? '', PATHINFO_FILENAME);

        return match ($image) {
            'terrain' => 'Terrain',
            'collisions' => 'Collisions',
            default => $image,
        };
    }
}
