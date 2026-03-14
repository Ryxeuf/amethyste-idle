<?php

namespace App\Event\Fight;

use App\Entity\App\Mob;
use Symfony\Contracts\EventDispatcher\Event;

class MobDeadEvent extends Event
{
    final public const NAME = 'event.mob.dead';

    /**
     * @var Mob
     */
    protected $mob;

    /**
     * MobDeadEvent constructor.
     */
    public function __construct(Mob $mob)
    {
        $this->mob = $mob;
    }

    public function getMob(): Mob
    {
        return $this->mob;
    }
}
