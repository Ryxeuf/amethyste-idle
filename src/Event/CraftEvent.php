<?php

namespace App\Event;

use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use Symfony\Contracts\EventDispatcher\Event;

class CraftEvent extends Event
{
    final public const NAME = 'event.craft';

    public function __construct(
        private readonly Player $player,
        private readonly Recipe $recipe,
        private readonly Item $resultItem,
        private readonly int $quantity,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getRecipe(): Recipe
    {
        return $this->recipe;
    }

    public function getResultItem(): Item
    {
        return $this->resultItem;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
