<?php

namespace App\SearchEngine\Transformer;

use App\Dto\Search\SearchCell;
use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\World;

class CellTransformer
{
    private array $terrains;

    public function addTerrains(array $terrains): self
    {
        foreach ($terrains as $terrain) {
            $this->terrains[$terrain['firstgid']] = $terrain;
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

            $idxInMap = $layer['idxInMap'];
            $tilesetName = $layer['tilesetName'];
            $terrain = $this->getTerrain($layer);
            $terrainName = str_replace('../../assets/styles/images/terrain/', '', $terrain['image']);
            $terrainName = str_replace('.png', '', $terrainName);

            // $style[] = 'background: url("'.str_replace('../../assets/styles', '.', $terrain['image']).'")';

            // Supposons que ces valeurs viennent de votre tableau de terrains
            $tileColumns = (int)$terrain['columns']; // Nombre de tuiles en largeur dans l'image
            $tileWidth = (int)$terrain['tilewidth']; // La largeur d'une tuile en pixels
            $tileHeight = (int)$terrain['tileheight']; // La hauteur d'une tuile en pixels
    
            // Calcul de la position X et Y en pixels
            $xPos = ($idxInMap % $tileColumns) * $tileWidth;
            $yPos = intdiv($idxInMap, $tileColumns) * $tileHeight;

            // $style[] = 'width: '.$tileWidth.'px';
            // $style[] = 'height: '.$tileHeight.'px';
            // $style[] = 'background-position: -'.$xPos.'px -'.$yPos.'px';
            // $style[] = 'background-image: url("'.str_replace('../../assets/styles', '.', $terrain['image']).'");';

            $layers[] = ['style' => implode('; ', $style), 'class' => $terrainName.' cell tileset-'.$tilesetName.'-cell-'.($idxInMap)];
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
            debug: [
                'layers' => $data['layers'],
                'terrains' => $this->terrains,
            ],
        );
    }
    
    private function getTerrain(array $layer): array
    {
        return $this->terrains[$layer['mapIdx']];
    }
}
