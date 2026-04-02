<?php

namespace App\Event\Game;

use App\Entity\App\Player;
use App\Entity\Game\Achievement;
use Symfony\Contracts\EventDispatcher\Event;

class AchievementCompletedEvent extends Event
{
    final public const NAME = 'event.game.achievement.completed';

    public function __construct(
        private readonly Player $player,
        private readonly Achievement $achievement,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getAchievement(): Achievement
    {
        return $this->achievement;
    }
}
