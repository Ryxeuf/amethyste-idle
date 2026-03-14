<?php

namespace App\Event\Fight;

class PlayerAttackHitEvent extends PlayerActionHitEvent
{
    public const NAME = 'event.fight.player_attack.hit';

    public function getAction(): string
    {
        return 'Attaque avec ' . parent::getAction();
    }
}
