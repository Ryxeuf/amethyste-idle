<?php

namespace App\Event\Map;

use App\Entity\App\ObjectLayer;
use Symfony\Contracts\EventDispatcher\Event;

class SpotAvailableEvent extends Event
{
    final public const NAME = "event.map.spot.available";

    public function __construct(private readonly ObjectLayer $objectLayer)
    {
    }

    public function getObjectLayer(): ObjectLayer
    {
        return $this->objectLayer;
    }
}