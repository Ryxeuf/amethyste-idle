<?php

namespace App\GameEngine\Progression;

/**
 * Un levier mal ecrit, ou deux leviers a la meme place (ARC-03).
 *
 * Meme parti pris que `DomainRoleDefinitionException` : echec **a la lecture**.
 * Un taux de change absent ou une place partagee doit faire rougir la CI, jamais
 * se decouvrir six mois plus tard sous la forme d'un empilement silencieux —
 * c'est exactement ce que le critere d'admission du canon cherche a interdire.
 */
class CombatLeverDefinitionException extends \RuntimeException
{
}
