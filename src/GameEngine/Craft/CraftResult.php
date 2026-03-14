<?php

namespace App\GameEngine\Craft;

use App\Entity\App\PlayerItem;
use App\Entity\Game\CraftRecipe;

readonly class CraftResult
{
    public function __construct(
        public PlayerItem $item,
        public string $quality,
        public int $experienceGained,
        public ?CraftRecipe $discoveredRecipe = null,
    ) {
    }
}
