<?php

namespace App\Event\Map;

use App\Entity\App\ObjectLayer;
use Symfony\Contracts\EventDispatcher\Event;

class SpotHarvestEvent extends Event
{
    final public const NAME = "event.map.spot.harvest";

    public function __construct(private readonly ObjectLayer $objectLayer)
    {
    }

    public function getObjectLayer(): ObjectLayer
    {
        return $this->objectLayer;
    }
}