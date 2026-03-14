<?php

namespace App\Event;

use App\Entity\App\Player;
use App\Entity\Game\Item;
use Symfony\Contracts\EventDispatcher\Event;

class GatheringEvent extends Event
{
    final public const NAME = 'event.gathering';

    public function __construct(
        private readonly Player $player,
        private readonly Item $item,
        private readonly int $quantity,
        private readonly string $gatheringType,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return string 'fishing' or 'skinning'
     */
    public function getGatheringType(): string
    {
        return $this->gatheringType;
    }
}
