<?php

namespace App\Dto\Map;

use App\Entity\App\Map as MapEntity;

class MapModelLight
{
    public int $id;
    public string $name;
    public string $versionHash;

    public function __construct(MapEntity $map)
    {
        $this->id = $map->getId();
        $this->name = $map->getName();
        $this->versionHash = $map->getUpdatedAt()->format('YmdHis');
    }
}
