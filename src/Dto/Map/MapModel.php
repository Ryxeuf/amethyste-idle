<?php

namespace App\Dto\Map;

use App\Dto\Area\AreaModel;
use App\Entity\App\Map as MapEntity;

class MapModel extends MapModelLight
{
    public int $minX       = 0;
    public int $maxX       = 0;
    public int $minY       = 0;
    public int $maxY       = 0;
    public int $areaWidth  = 0;
    public int $areaHeight = 0;

    /** @var AreaModel[] */
    public array $areas = [];

    public function __construct(MapEntity $map)
    {
        parent::__construct($map);

        $this->areaWidth  = $map->getAreaWidth();
        $this->areaHeight = $map->getAreaHeight();
    }

}
