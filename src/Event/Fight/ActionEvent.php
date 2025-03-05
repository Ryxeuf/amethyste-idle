<?php

namespace App\Event\Fight;

use Symfony\Contracts\EventDispatcher\Event;

class ActionEvent extends Event
{
    final public const NAME = "event.fight.action";

    /**
     * @var int
     */
    protected $fightId;

    /**
     * ActionEvent constructor.
     */
    public function __construct(int $fightId)
    {
        $this->fightId = $fightId;
    }

    public function getFightId(): int
    {
        return $this->fightId;
    }
}
