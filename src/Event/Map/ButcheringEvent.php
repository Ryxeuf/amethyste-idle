<?php

namespace App\Event\Map;

use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use Symfony\Contracts\EventDispatcher\Event;

class ButcheringEvent extends Event
{
    final public const NAME = 'event.map.butchering';

    /**
     * @param PlayerItem[] $harvestedItems
     */
    public function __construct(
        private readonly Player $player,
        private readonly Mob $mob,
        private readonly array $harvestedItems = [],
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getMob(): Mob
    {
        return $this->mob;
    }

    /**
     * @return PlayerItem[]
     */
    public function getHarvestedItems(): array
    {
        return $this->harvestedItems;
    }
}
