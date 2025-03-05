<?php

namespace App\Event\Fight;

class PlayerAttackMissEvent extends PlayerActionMissEvent
{
    public const NAME = "event.fight.player_attack.miss";

    public function getAction(): string
    {
        return 'Attaque avec ' . parent::getAction();
    }
}
