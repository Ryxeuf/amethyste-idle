<?php

namespace App\Event\Game;

use App\Entity\App\Player;
use App\Entity\Game\Domain;
use Symfony\Contracts\EventDispatcher\Event;

class DomainLevelUpEvent extends Event
{
    final public const NAME = 'event.game.domain.level_up';

    public function __construct(
        private readonly Player $player,
        private readonly Domain $domain,
        private readonly int $oldLevel,
        private readonly int $newLevel,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getDomain(): Domain
    {
        return $this->domain;
    }

    public function getOldLevel(): int
    {
        return $this->oldLevel;
    }

    public function getNewLevel(): int
    {
        return $this->newLevel;
    }
}
