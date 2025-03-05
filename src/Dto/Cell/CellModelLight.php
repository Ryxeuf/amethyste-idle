<?php

namespace App\Dto\Cell;

use App\Dto\Map\MapModelLight;
use App\Entity\App\Map;

class CellModelLight
{
    public int            $x;
    public int            $y;
    public int            $movement = -1;
    public ?MapModelLight $map      = null;

    public function __construct(array $data, ?Map $map = null)
    {
        $this->x        = $data['x'];
        $this->y        = $data['y'];
        $this->movement = $data['mouvement'];

        if ($map !== null) {
            $this->map = new MapModelLight($map);
        }
    }
}
