<?php

namespace App\GameEngine\Fight\Handler;

use App\ApiResource\FightResource;

class PlayerItemHandler extends AbstractPayerItemHandler
{
    public function supports(FightResource $fight, string $context)
    {
        return FightResource::ACTION_ITEM === $context;
    }
}
