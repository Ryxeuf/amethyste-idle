<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Fight;

class PlayerItemHandler extends AbstractPayerItemHandler
{
    public function supports(Fight $fight, string $context): bool
    {
        return PlayerActionHandlerInterface::ACTION_ITEM === $context;
    }
}
