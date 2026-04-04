<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Mob;
use App\Entity\Game\Spell;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface MobActionHandlerInterface
{
    public function supports(string $context): bool;

    public function getSpell(Mob $mob): Spell;
}
