<?php

namespace App\Event\Game;

use App\Entity\App\Player;
use App\Entity\Game\Quest;
use Symfony\Contracts\EventDispatcher\Event;

class QuestCompletedEvent extends Event
{
    final public const NAME = 'event.game.quest.completed';

    public function __construct(
        private readonly Player $player,
        private readonly Quest $quest,
        private readonly ?string $choiceMade = null,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getQuest(): Quest
    {
        return $this->quest;
    }

    public function getChoiceMade(): ?string
    {
        return $this->choiceMade;
    }
}
