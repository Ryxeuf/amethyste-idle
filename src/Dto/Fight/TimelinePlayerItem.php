<?php

namespace App\Dto\Fight;

class TimelinePlayerItem extends TimelineItem
{
    public function __construct(string $name, string $slug)
    {
        parent::__construct($name, $slug);

        $this->type = self::TYPE_PLAYER;
    }
}
