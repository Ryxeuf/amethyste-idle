<?php

namespace App\GameEngine\Progression;

/**
 * Une fourche de combat mal declaree (ARC-14a).
 *
 * Refuser au chargement plutot qu'en jeu : une branche sans accord ne se verrait
 * pas en donnee et serait fatale en combat — deux branches qui ne different que
 * par leurs passifs produisent le meme combat, au tour pres.
 */
class CombatBranchDefinitionException extends \RuntimeException
{
}
