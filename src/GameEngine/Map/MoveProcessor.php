<?php

namespace App\GameEngine\Map;

use App\Exception\PathCoordinatesNotFoundException;
use App\Exception\PathNotFoundException;

class MoveProcessor
{
    public function __construct(
        private readonly MovementCalculator $movementCalculator,
    ) {
    }

    /**
     * @throws PathCoordinatesNotFoundException
     * @throws PathNotFoundException
     */
    public function move(int $mapId, int $x, int $y, int $targetX, int $targetY): array
    {
        $this->movementCalculator->loadMap($mapId);
        $cells = $this->movementCalculator->calculateMovement($x, $y, $targetX, $targetY);
        // On retire la première case qui correspond à la case de départ du mouvement
        array_shift($cells);

        return $cells;
    }
}
