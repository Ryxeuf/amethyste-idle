<?php

namespace App\Dto\Cell;

use App\Dto\Map\MapModelLight;
use App\Entity\App\Map;
use App\Entity\App\Area;

class CellModelLight
{
    public int            $areaX;
    public int            $areaY;
    public int            $x;
    public int            $y;
    public int            $movement = -1;
    public ?MapModelLight $map      = null;

    public function __construct(array $data, ?Map $map = null, ?Area $area = null)
    {
        $this->areaX    = $data['x'];
        $this->areaY    = $data['y'];
        $this->movement = $data['mouvement'];
        $this->x        = $data['x'];
        $this->y        = $data['y'];
        
        if ($map !== null) {
            $this->map = new MapModelLight($map);
            $this->x = $area ? ($area->getX()) * $map->getAreaWidth() + $data['x'] : $data['x'];
            $this->y = $area ? ($area->getY()) * $map->getAreaHeight() + $data['y'] : $data['y'];
        }
    }
}
