<?php

namespace App\Twig\Components;

use App\Dto\Map\MapDynamicModel;
use App\Entity\App\Player;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use App\Transformer\MapModelTransformer;
use App\Helper\PlayerHelper;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use App\SearchEngine\CellSearchEngine;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use App\GameEngine\Map\MovementCalculator;
use App\GameEngine\Movement\PlayerMoveProcessor;

#[AsLiveComponent]
class Map
{
    use DefaultActionTrait;

    public Player $player;
    
    #[LiveProp(writable: true)]
    public int $x;
    #[LiveProp(writable: true)]
    public int $y;

    #[LiveProp(writable: true)]
    public int $startX;
    #[LiveProp(writable: true)]
    public int $startY;
    #[LiveProp(writable: true)]
    public int $endX;
    #[LiveProp(writable: true)]
    public int $endY;
    
    public int $mapSize = 21;
    public int $cellSize = 32;

    /** @var array<int, array<int, object>> */
    public array $visibleCells = [];

    private ?MapDynamicModel $mapDynamicCache = null;
    
    public function __construct(
        private readonly MapModelTransformer $mapModelTransformer,
        private readonly PlayerHelper $playerHelper,
        private readonly CellSearchEngine $cellSearchEngine,
        private readonly MovementCalculator $movementCalculator,
        private readonly PlayerMoveProcessor $playerMoveProcessor,
    )
    {
        $this->player = $this->playerHelper->getPlayer();
        $this->initXY();
    }

    public function getMapDynamic(): MapDynamicModel
    {
        if ($this->mapDynamicCache === null) {
            $this->mapDynamicCache = $this->mapModelTransformer->transformDynamicMapModel($this->player->getMap());
        }
        return $this->mapDynamicCache;
    }

    public function initXY(): void
    {
        $this->player = $this->playerHelper->getPlayer();
        $coordinates = $this->player->getCoordinates();
        if (empty($coordinates) || !str_contains($coordinates, '.')) {
            $this->x = 0;
            $this->y = 0;
        } else {
            [$this->x, $this->y] = array_map('intval', explode('.', $coordinates));
        }
    }

    public function mount(): void
    {
        $this->initXY();
        $this->updateCoordinates($this->x, $this->y);
        $this->updateCells();
    }

    #[LiveAction]
    public function updateCoordinates(#[LiveArg] int $x, #[LiveArg] int $y): void
    {
        $this->x = $x;
        $this->y = $y;
        $this->startX = $x - 10;
        $this->startY = $y - 10;
        $this->endX = $x + 10;
        $this->endY = $y + 10;
        $this->updateCells();
    }

    #[LiveAction]
    public function move(#[LiveArg] int $x, #[LiveArg] int $y): void
    {
        $this->movementCalculator->loadMap(10);
        $movements = $this->movementCalculator->calculateMovement($this->x, $this->y, $x, $y);

        if (empty($movements)) {
            $this->updateCells();
            return;
        }

        $this->playerMoveProcessor->processMove($this->player, $movements);
        $this->initXY();
        $this->updateCells();
    }

    public function updateCells(): void
    {
        $cells = $this->cellSearchEngine->getMapCells($this->x, $this->y, $this->player->getMap()->getId());
        $this->visibleCells = [];
        foreach ($cells as $cell) {
            $this->visibleCells[$cell->x][$cell->y] = $cell;
        }
    }
}
