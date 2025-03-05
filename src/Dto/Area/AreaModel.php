<?php

namespace App\Dto\Area;

use App\Entity\App\Area;

class AreaModel
{
    public int    $id;
    public int    $x;
    public int    $y;

    public function __construct(Area $area)
    {
        $this->id          = $area->getId();
        $this->x           = $area->getX();
        $this->y           = $area->getY();
    }

}
