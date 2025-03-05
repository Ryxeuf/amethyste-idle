<?php

namespace App\Event\Fight;

class PlayerSpellHitEvent extends PlayerActionHitEvent
{
    public const NAME = "event.fight.player_spell.hit";
}
