<?php

namespace App\Transformer;

use App\Dto\Cell\CellModel;
use App\Dto\Cell\ObjectLayerModel;
use App\Dto\Mob\MobModelLight;
use App\Dto\Player\PlayerModelLight;
use App\Dto\Pnj\PnjModelLight;
use App\Entity\App\Area;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Helper\CellHelper;
use App\Helper\PlayerHelper;
use App\ServiceProvider\CellActionsProvider;
use Doctrine\ORM\EntityManagerInterface;

class CellModelTransformer
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly CellActionsProvider $actionsProvider,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    // public function transformCell(Cell $cell, bool $withMap = true): CellModel
    // {
    //     $cellModel = new CellModel($cell, $withMap);

    //     if ($cell->getObjectLayer() && !$cell->getObjectLayer()->isDynamic()) {
    //         $cellModel->object = new ObjectLayerModel($cell->getObjectLayer());
    //     }
    //     $cellModel->mouvement = $this->getCellMouvement($cell);
    //     $cellModel->pnj = $this->transformCellPnj($cell);
    //     $cellModel->actions = $this->transformCellActions($cell);

    //     return $cellModel;
    // }

    // public function transformCellDynamicObject(Cell $cell): ?ObjectLayerModel
    // {
    //     if ($cell->getObjectLayer()?->isDynamic()) {
    //         return new ObjectLayerModel($cell->getObjectLayer());
    //     }

    //     return null;
    // }

    // public function getCellMouvement(Cell $cell): int
    // {
    //     $mouvement = 0;
    //     if (null !== $objectLayer = $cell->getObjectLayer()) {
    //         if ($objectLayer->getMovement() < 0) {
    //             $mouvement = $objectLayer->getMovement();
    //         } else {
    //             $mouvement += $objectLayer->getMovement();
    //         }
    //     }
    //     if ($cell->getPnj()) {
    //         $mouvement = -1;
    //     }

    //     return $mouvement;
    // }

    public function transformCellPlayers(int $x, int $y): array
    {
        $players = [];
        $cellPlayers = $this->entityManager->getRepository(Player::class)->findBy(['coordinates' => CellHelper::stringifyCoordinates($x, $y)]);
        foreach ($cellPlayers as $player) {
            $players[] = $this->transformCellPlayer($player);
        }

        return $players;
    }

    public function transformCellPlayer(Player $player): PlayerModelLight
    {
        $playerModel = new PlayerModelLight($player);
        if ($this->playerHelper->getPlayer()->getId() === $player->getId()) {
            $playerModel->self = true;
        }

        return $playerModel;
    }

    public function transformCellPnj(int $x, int $y): ?PnjModelLight
    {
        if (null !== $pnj = $this->entityManager->getRepository(Pnj::class)->findOneBy(['coordinates' => CellHelper::stringifyCoordinates($x, $y)])) {
            return new PnjModelLight($pnj);
        }

        return null;
    }

    public function transformCellActions(Area $area, int $x, int $y): array
    {
        return $this->actionsProvider->getActions($area, $x, $y);
    }

    public function transformCellMob(int $x, int $y): ?MobModelLight
    {
        if (null !== $mob = $this->entityManager->getRepository(Mob::class)->findOneBy(['coordinates' => CellHelper::stringifyCoordinates($x, $y)])) {
            return new MobModelLight($mob);
        }

        return null;
    }

}