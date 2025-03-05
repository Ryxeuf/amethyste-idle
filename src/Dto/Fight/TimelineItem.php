<?php

namespace App\Dto\Fight;

class TimelineItem
{
    final const TYPE_PLAYER = 'player';
    final const TYPE_MOB = 'mob';

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $slug;

    /**
     * @var string
     */
    public $type;

    /**
     * TimelineItem constructor.
     */
    public function __construct(string $name, string $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
    }

    public function isMob()
    {
        return $this->type == self::TYPE_MOB;
    }

    public function isPlayer()
    {
        return $this->type == self::TYPE_PLAYER;

    }
}