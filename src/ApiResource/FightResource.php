<?php

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

class FightResource
{
    final public const ACTION_ATTACK = 'fight_attack';
    final public const ACTION_SPELL = 'fight_spell';
    final public const ACTION_ITEM = 'fight_item';
    final public const TARGET_TYPES = ['player', 'mob'];

    public int $id;

    public int $fight;

    public int $mob;

    public int $target;

    #[Assert\Choice(choices: FightResource::TARGET_TYPES)]
    public string $targetType;

    public int $item;

    public int $step;

    /**
     * @var int[]
     */
    public array $loots;
}
