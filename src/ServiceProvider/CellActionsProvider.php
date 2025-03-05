<?php

namespace App\ServiceProvider;

use App\Dto\Cell\CellModel;
use App\Dto\CellAction\CellAction;
use App\Entity\App\Area;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Pnj;
use App\GameEngine\Player\PlayerActionHelper;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;

class CellActionsProvider
{
    /**
     * CellActionsProvider constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PlayerActionHelper $playerActionHelper)
    {
    }

    public function getActions(Area $area, int $x, int $y): array
    {
        $actions = [];

        $cells = CellHelper::getCellsInfos($area, $x, $y, 1);

        $cellsCoordinates = array_map(function (array $cellData) { return $cellData['x'] . '.' . $cellData['y']; }, $cells);

        $actions += $this->getPnjsActions($cellsCoordinates);
        $actions += $this->getObjectLayersAction($cellsCoordinates);

        return $actions;
    }

    /**
     * @param array $coordinates List of coordinates to look for PNJs
     */
    private function getPnjsActions(array $coordinates): array
    {
        $actions = [];
        $pnjs = $this->entityManager->getRepository(Pnj::class)->findBy(['coordinates' => $coordinates]);
        foreach ($pnjs as $pnj) {
            $action = new CellAction();
            $action->action = 'talk';
            $action->data = [
                'pnj' => [
                    'id' => $pnj->getId(),
                    'name' => $pnj->getName(),
                ]
            ];
            $actions[] = $action;
        }

        return $actions;
    }

    private function getObjectLayersAction(array $coordinates, int $distance = 1): array
    {
        $actions = [];
        $objectLayers = $this->entityManager->getRepository(ObjectLayer::class)->findBy(['coordinates' => $coordinates]);
        foreach ($objectLayers as $objectLayer) {
            if ($objectLayer->getUsedAt() === null && $objectLayer->getActions()) {
                foreach ($objectLayer->getActions() as $action) {
                    $inDistance = !isset($action['distance']) || $action['distance'] <= $distance;
                    if ($inDistance && $this->playerActionHelper->canDoAction($action['action'])) {
                        $cellAction = new CellAction();
                        $cellAction->action = $action['action'];
                        $cellAction->data = $action['data'] ?? [];
                        $cellAction->data['objectLayer'] = $objectLayer->getId();
                        $cellAction->spot = $objectLayer->getSlug();
                        $actions[] = $cellAction;
                    }
                }
            }
        }



        return $actions;

        if ($cell->getObjectLayer() && $cell->getObjectLayer()->getUsedAt() === null && $cell->getObjectLayer()->getActions()) {
            foreach ($cell->getObjectLayer()->getActions() as $action) {
                $inDistance = !isset($action['distance']) || (isset($action['distance']) && $action['distance'] <= $distance);
                if ($inDistance && $this->playerActionHelper->canDoAction($action['action'])) {
                    $cellAction = new CellAction();
                    $cellAction->action = $action['action'];
                    $cellAction->data = $action['data'] ?? [];
                    $cellAction->data['objectLayer'] = $cell->getObjectLayer()->getId();
                    $cellAction->spot = $cell->getObjectLayer()->getSlug();
                    $actions[] = $cellAction;
                }
            }
        }
    }


}