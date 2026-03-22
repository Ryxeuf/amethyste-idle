<?php

namespace App\Event\Map;

use App\Entity\App\ObjectLayer;
use App\Entity\App\PlayerItem;
use Symfony\Contracts\EventDispatcher\Event;

class SpotHarvestEvent extends Event
{
    final public const NAME = 'event.map.spot.harvest';

    /**
     * @param ObjectLayer    $objectLayer    The harvested spot
     * @param PlayerItem[]   $harvestedItems The items actually harvested
     */
    public function __construct(
        private readonly ObjectLayer $objectLayer,
        private readonly array $harvestedItems = [],
    ) {
    }

    public function getObjectLayer(): ObjectLayer
    {
        return $this->objectLayer;
    }

    /**
     * @return PlayerItem[]
     */
    public function getHarvestedItems(): array
    {
        return $this->harvestedItems;
    }
}
