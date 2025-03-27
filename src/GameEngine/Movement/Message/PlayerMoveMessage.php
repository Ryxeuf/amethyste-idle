<?php

namespace App\GameEngine\Movement\Message;

class PlayerMoveMessage
{
    public function __construct(public readonly string $content) {}
}
