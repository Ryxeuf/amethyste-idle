<?php

namespace App\GameEngine\Player;

use App\Helper\CellHelper;
use App\Helper\PlayerHelper;

class PlayerActionHelper
{
    final public const HARVEST = 'harvest';
    final public const TOOL_SLOT_UNLOCK = 'tool_slot.unlock';
    final public const EQUIP_TOOL = 'equip.tool';
    final public const MOVEMENT_SWIM = 'movement.swim';
    final public const MOVEMENT_CLIMB = 'movement.climb';

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

    /**
     * Retourne la liste des types d'outils débloqués par les skills du joueur.
     *
     * @return string[]
     */
    public function getUnlockedToolSlots(): array
    {
        $actions = $this->getActions();

        return $actions[self::TOOL_SLOT_UNLOCK] ?? [];
    }

    /**
     * Retourne la liste des slugs d'outils que le joueur peut équiper grâce à ses skills.
     *
     * @return string[]
     */
    public function getEquippableToolSlugs(): array
    {
        $actions = $this->getActions();

        return $actions[self::EQUIP_TOOL] ?? [];
    }

    /**
     * Vérifie si le joueur peut équiper un outil donné (par slug).
     */
    public function canEquipTool(string $toolSlug): bool
    {
        return \in_array($toolSlug, $this->getEquippableToolSlugs(), true);
    }

    /**
     * Retourne la liste de tous les spots de récolte débloqués par le joueur.
     *
     * @return string[]
     */
    public function getHarvestSpots(): array
    {
        $actions = $this->getActions();

        return $actions[self::HARVEST] ?? [];
    }

    /**
     * Synchronise les emplacements d'outils débloqués sur le joueur
     * en fonction de ses skills actuels.
     */
    public function syncToolSlots(): void
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return;
        }

        foreach ($this->getUnlockedToolSlots() as $toolType) {
            $player->unlockToolSlot($toolType);
        }
    }

    /**
     * Compute the bitmask of movement abilities the current player has.
     * Always includes ABILITY_WALK. Adds ABILITY_SWIM / ABILITY_CLIMB
     * when the player owns a skill with the corresponding action.
     */
    public function getMovementAbilityMask(): int
    {
        $mask = CellHelper::ABILITY_WALK;

        if ($this->canDoAction(self::MOVEMENT_SWIM)) {
            $mask |= CellHelper::ABILITY_SWIM;
        }
        if ($this->canDoAction(self::MOVEMENT_CLIMB)) {
            $mask |= CellHelper::ABILITY_CLIMB;
        }

        return $mask;
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
                    if (!\is_array($action)) {
                        continue;
                    }
                    $actionKey = $action['action'] ?? null;
                    if (!\is_string($actionKey) || $actionKey === '') {
                        continue;
                    }

                    if ($actionKey === self::TOOL_SLOT_UNLOCK) {
                        $slot = $action['slot'] ?? null;
                        if (\is_string($slot) && $slot !== '') {
                            if (!isset($this->actions[$actionKey])) {
                                $this->actions[$actionKey] = [];
                            }
                            $this->actions[$actionKey][] = $slot;
                        }
                        continue;
                    }

                    if ($actionKey === self::EQUIP_TOOL) {
                        $slugs = $action['slugs'] ?? [];
                        if (!\is_array($slugs)) {
                            $slugs = [];
                        }
                        if (!isset($this->actions[$actionKey])) {
                            $this->actions[$actionKey] = [];
                        }
                        $this->actions[$actionKey] = array_merge($this->actions[$actionKey], $slugs);
                        continue;
                    }

                    $spots = $action['spots'] ?? [];
                    if (!\is_array($spots)) {
                        $spots = [];
                    }
                    if (!isset($this->actions[$actionKey])) {
                        $this->actions[$actionKey] = [];
                    }
                    $this->actions[$actionKey] = array_merge($this->actions[$actionKey], $spots);
                }
            }
        }

        return $this->actions;
    }
}
