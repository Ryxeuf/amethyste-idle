<?php

namespace App\Event\Map;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use Symfony\Contracts\EventDispatcher\Event;

class FishingEvent extends Event
{
    final public const NAME = 'event.map.fishing';

    public function __construct(
        private readonly Player $player,
        private readonly ObjectLayer $objectLayer,
        private readonly ?PlayerItem $caughtItem = null,
        private readonly bool $perfect = false,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getObjectLayer(): ObjectLayer
    {
        return $this->objectLayer;
    }

    public function getCaughtItem(): ?PlayerItem
    {
        return $this->caughtItem;
    }

    public function isSuccess(): bool
    {
        return $this->caughtItem !== null;
    }

    public function isPerfect(): bool
    {
        return $this->perfect && $this->isSuccess();
    }
}
