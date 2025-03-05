<?php

namespace App\Event\Fight;

use Symfony\Contracts\EventDispatcher\Event;

abstract class MobActionEvent extends Event
{
    public function __construct(private readonly string $action)
    {
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
