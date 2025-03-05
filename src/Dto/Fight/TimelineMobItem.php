<?php

namespace App\Dto\Fight;

class TimelineMobItem extends TimelineItem
{
    public function __construct(string $name, string $slug)
    {
        parent::__construct($name, $slug);

        $this->type = self::TYPE_MOB;
    }
}
