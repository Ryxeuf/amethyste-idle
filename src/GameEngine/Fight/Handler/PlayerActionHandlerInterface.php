<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface PlayerActionHandlerInterface
{
    public const ACTION_ATTACK = 'attack';
    public const ACTION_SPELL = 'spell';
    public const ACTION_ITEM = 'item';

    public function supports(Fight $fight, string $context);

    public function applyAction(Fight $fight, Player $sender): bool;
}
