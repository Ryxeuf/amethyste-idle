<?php


namespace App\GameEngine\Player;

use App\Helper\PlayerHelper;

class PlayerActionHelper
{
    final public const HARVEST = 'harvest';

    private ?array $actions = null;

    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function canDoAction(string $action): bool
    {
        return isset($this->getActions()[$action]);
    }

    public function canHarvest(string $spot): bool
    {
        $harvestable = [];
        $actions = $this->getActions();
        foreach ($actions as $action => $data) {
            if (self::HARVEST === $action) {
                $harvestable = array_merge($harvestable, $data);
            }
        }

        return in_array($spot, $harvestable);
    }

    private function getActions(): array
    {
        if ($this->actions !== null) {
            return $this->actions;
        }

        $this->actions = [];
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->actions;
        }
        foreach ($player->getSkills() as $skill) {
            if ($skill->getActions()) {
                foreach ($skill->getActions() as $action) {
                    if(!isset($this->actions[$action['action']])) {
                        $this->actions[$action['action']] = [];
                    }
                    $this->actions[$action['action']] = array_merge($this->actions[$action['action']], $action['spots']);
                }
            }
        }

        return $this->actions;
    }
}