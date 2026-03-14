<?php

namespace App\Event;

use App\Entity\App\Player;
use Symfony\Contracts\EventDispatcher\Event;

class GatheringXpEvent extends Event
{
    final public const NAME = 'event.gathering.xp';

    public function __construct(
        private readonly Player $player,
        private readonly int $xp,
        private readonly string $gatheringType,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    /**
     * @return string 'fishing' or 'skinning'
     */
    public function getGatheringType(): string
    {
        return $this->gatheringType;
    }
}
