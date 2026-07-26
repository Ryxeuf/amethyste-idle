<?php

namespace App\GameEngine\Player;

use App\Entity\App\Player;
use App\Helper\CellHelper;
use App\Helper\PlayerHelper;

class PlayerActionHelper
{
    final public const HARVEST = 'harvest';
    final public const TOOL_SLOT_UNLOCK = 'tool_slot.unlock';
    final public const EQUIP_TOOL = 'equip.tool';
    final public const CRAFT = 'craft';
    final public const MOVEMENT_SWIM = 'movement.swim';
    final public const MOVEMENT_CLIMB = 'movement.climb';

    private ?array $actions = null;

    /** @var array<int, array<string, list<string>>> actions resolues par joueur */
    private array $actionsByPlayer = [];

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
    public function getUnlockedToolSlots(?Player $player = null): array
    {
        return $this->actionsFor($player)[self::TOOL_SLOT_UNLOCK] ?? [];
    }

    /**
     * Retourne la liste des slugs d'outils que le joueur peut équiper grâce à ses skills.
     *
     * @return string[]
     */
    public function getEquippableToolSlugs(?Player $player = null): array
    {
        return $this->actionsFor($player)[self::EQUIP_TOOL] ?? [];
    }

    /**
     * Recettes debloquees par les skills du joueur (ECO-20).
     *
     * Le champ lu est `recipes`, la ou `equip.tool` lit `slugs` et
     * `tool_slot.unlock` lit `slot`. Avant ce jalon, une action `craft` tombait
     * dans la branche generique qui cherche `spots` : elle ne contribuait donc
     * **rien**, et les 51 nœuds d'arbre qui debloquent des recettes ne
     * debloquaient rien du tout.
     *
     * Prend un joueur explicite : les commandes de craft (ECO-06) doivent
     * qualifier un artisan qui n'est pas forcement celui de la session.
     *
     * @return list<string>
     */
    public function getUnlockedRecipeSlugs(?Player $player = null): array
    {
        return array_values(array_unique($this->actionsFor($player)[self::CRAFT] ?? []));
    }

    public function hasUnlockedRecipe(string $recipeSlug, ?Player $player = null): bool
    {
        return \in_array($recipeSlug, $this->getUnlockedRecipeSlugs($player), true);
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
    public function getHarvestSpots(?Player $player = null): array
    {
        return $this->actionsFor($player)[self::HARVEST] ?? [];
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

    /**
     * Actions d'un joueur donne, ou du joueur de la session a defaut.
     *
     * @return array<string, list<string>>
     */
    private function actionsFor(?Player $player): array
    {
        return $player === null ? $this->getActions() : $this->resolveActions($player);
    }

    private function getActions(): array
    {
        if ($this->actions !== null) {
            return $this->actions;
        }

        $player = $this->playerHelper->getPlayer();
        $this->actions = $player === null ? [] : $this->resolveActions($player);

        return $this->actions;
    }

    /**
     * Agrege les actions des skills d'un joueur donne.
     *
     * @return array<string, list<string>>
     */
    private function resolveActions(Player $player): array
    {
        $cacheKey = $player->getId() ?? 0;
        if (isset($this->actionsByPlayer[$cacheKey]) && $cacheKey !== 0) {
            return $this->actionsByPlayer[$cacheKey];
        }

        $actions = [];
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

                    if (!isset($actions[$actionKey])) {
                        $actions[$actionKey] = [];
                    }

                    if ($actionKey === self::TOOL_SLOT_UNLOCK) {
                        $slot = $action['slot'] ?? null;
                        if (\is_string($slot) && $slot !== '') {
                            $actions[$actionKey][] = $slot;
                        }
                        continue;
                    }

                    // Chaque cle d'action porte ses donnees dans un champ qui lui
                    // est propre. Une cle inconnue tombe sur `spots` : c'est ce
                    // qui rendait les actions `craft` (champ `recipes`) muettes.
                    $field = match ($actionKey) {
                        self::EQUIP_TOOL => 'slugs',
                        self::CRAFT => 'recipes',
                        default => 'spots',
                    };

                    $values = $action[$field] ?? [];
                    if (!\is_array($values)) {
                        $values = [];
                    }
                    $actions[$actionKey] = array_merge($actions[$actionKey], array_values($values));
                }
            }
        }

        if ($cacheKey !== 0) {
            $this->actionsByPlayer[$cacheKey] = $actions;
        }

        return $actions;
    }
}
