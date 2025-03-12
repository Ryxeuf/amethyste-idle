<?php

namespace App\Twig\Components;

use App\Dto\Map\MapDynamicModel;
use App\Dto\Map\MapModel;
use App\Entity\App\Player;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use App\Transformer\MapModelTransformer;
use App\Helper\PlayerHelper;
use App\Dto\Map\MapStaticModel;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
class Map
{
    use DefaultActionTrait;

    public MapModel $map;
    public MapDynamicModel $mapDynamic;
    public MapStaticModel $mapStatic;

    public Player $player;
    
    // Propriétés pour les coordonnées du joueur
    #[LiveProp]
    public int $x;
    #[LiveProp]
    public int $y;

    public int $startX;
    public int $startY;
    public int $endX;
    public int $endY;
    
    // Taille de la carte
    public int $mapSize = 21;
    public int $cellSize = 32;

    /** @var CellModel[] */
    public array $visibleCells = [];
    
    public function __construct(
        private readonly MapModelTransformer $mapModelTransformer,
        private readonly PlayerHelper $playerHelper
    )
    {
        $this->player = $this->playerHelper->getPlayer();
        $this->map = $this->mapModelTransformer->transformPlayerMapModel($this->player);
        $this->mapDynamic = $this->mapModelTransformer->transformDynamicMapModel($this->player->getMap());
        $this->mapStatic = $this->mapModelTransformer->transformStaticMapModel($this->player->getMap());

        // Extraire les coordonnées du joueur
        $coordinates = $this->player->getCoordinates();
        if (empty($coordinates) || !str_contains($coordinates, '.')) {
            // Coordonnées par défaut si celles du joueur ne sont pas valides
            $this->x = 0;
            $this->y = 0;
        } else {
            [$this->x, $this->y] = array_map('intval', explode('.', $coordinates));

        }
        
        // // Vérifier les coordonnées du joueur et les limites de la carte
        // dump('Coordonnées du joueur: ' . $this->x . ',' . $this->y);
        // dump('Limites de la carte: startX=' . ($this->x - 10) . ', startY=' . ($this->y - 10) . 
        //      ', endX=' . ($this->x + 10) . ', endY=' . ($this->y + 10));
    }

    public function mount()
    {
        $this->updateCoordinates($this->x, $this->y);
        $this->updateCells();
    }

    public function updateCoordinates(int $x, int $y): void
    {
        $this->x = $x;
        $this->y = $y;
        $this->startX = $x - 10;
        $this->startY = $y - 10;
        $this->endX = $x + 10;
        $this->endY = $y + 10;
    }

    public function updateCells(): void
    {
        $this->visibleCells = [];
        foreach ($this->mapStatic->cells as $cell) {
            if ($cell->x >= $this->startX && $cell->x <= $this->endX && $cell->y >= $this->startY && $cell->y <= $this->endY) {
                $this->visibleCells[] = $cell;
            }
        }
    }
}
