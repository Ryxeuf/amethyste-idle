<?php

namespace App\Dto\Cell;

use App\Entity\App\ObjectLayer;

class ObjectLayerModel
{
    public int    $mouvement;
    public string $slug;
    public string $type;
    public string $coordinates;
    public bool   $used;

    public function __construct(ObjectLayer $objectLayer)
    {
        $this->mouvement   = $objectLayer->getMovement();
        $this->used        = $objectLayer->getUsedAt() !== null;
        $this->slug        = $objectLayer->getSlug() . ($this->used ? '-used' : '');
        $this->type        = $objectLayer->getType();
        $this->coordinates = $objectLayer->getCoordinates();
    }
}
