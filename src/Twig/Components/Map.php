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
use App\DataStorage\MapStorage;
use App\SearchEngine\CellSearchEngine;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;

#[AsLiveComponent]
class Map
{
    use DefaultActionTrait;

    // public MapModel $map;
    public MapDynamicModel $mapDynamic;
    // public MapStaticModel $mapStatic;
    // public array $map;
    // public array $mapTag;

    public Player $player;
    
    // Propriétés pour les coordonnées du joueur
    #[LiveProp(writable: true)]
    public int $x;
    #[LiveProp(writable: true)]
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
        private readonly PlayerHelper $playerHelper,
        private readonly MapStorage $mapStorage,
        private readonly CellSearchEngine $cellSearchEngine,
    )
    {
        // ini_set('memory_limit', '128M');
        $this->player = $this->playerHelper->getPlayer();
        // $this->map = $this->mapModelTransformer->transformPlayerMapModel($this->player);
        $this->mapDynamic = $this->mapModelTransformer->transformDynamicMapModel($this->player->getMap());
        // $this->mapStatic = $this->mapModelTransformer->transformStaticMapModel($this->player->getMap());
        // $this->map = $this->mapStorage->getMap($this->player->getMap()->getId());
        // $this->mapTag = $this->mapStorage->getMapTag($this->player->getMap()->getId());

        // dump($this->mapStatic['areas']);
        // die;

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

    public function updateCells(): void
    {
        $cells = $this->cellSearchEngine->getMapCells($this->x, $this->y, $this->player->getMap()->getId());
        $this->visibleCells = [];
        foreach ($cells as $cell) {
            $this->visibleCells[$cell->x][$cell->y] = $cell;
        }
        // $idx = $this->visibleCells[0]['layers'][0]['idxInMap'];
        // dump($idx);
        // dump($idx / 16);
        // dump($idx % 16);
        // dump((int)floor($idx / 16)*32);
        // dump((int)floor($idx % 16)*32);
    }
}
