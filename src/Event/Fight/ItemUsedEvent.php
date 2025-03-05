<?php

namespace App\Event\Fight;

use App\Entity\App\PlayerItem;
use Symfony\Contracts\EventDispatcher\Event;

class ItemUsedEvent extends Event
{
    final public const NAME = "event.fight.item.used";

    public function __construct(private readonly PlayerItem $item, private readonly bool $success = true)
    {
    }

    public function getItem(): PlayerItem
    {
        return $this->item;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
