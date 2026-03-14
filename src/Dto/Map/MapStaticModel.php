<?php

namespace App\Dto\Map;

use App\Dto\Cell\CellModel;

class MapStaticModel extends MapModel
{
    /** @var CellModel[] */
    public array $cells = [];

}
