<?php

namespace App\Event\Game;

use App\Entity\App\Player;
use App\Entity\Game\CraftRecipe;
use Symfony\Contracts\EventDispatcher\Event;

class CraftEvent extends Event
{
    final public const NAME = "event.game.craft";

    public function __construct(
        private readonly Player $player,
        private readonly CraftRecipe $recipe,
        private readonly string $quality,
        private readonly int $experienceGained,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getRecipe(): CraftRecipe
    {
        return $this->recipe;
    }

    public function getQuality(): string
    {
        return $this->quality;
    }

    public function getExperienceGained(): int
    {
        return $this->experienceGained;
    }
}
