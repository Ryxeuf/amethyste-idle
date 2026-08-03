<?php

namespace App\GameEngine\Progression;

/**
 * Une palette de fonction mal ecrite (ARC-01).
 *
 * Meme parti pris que `PurityDefinitionException` : echec **a la lecture**.
 * Une palette invalide doit faire rougir la CI, jamais se decouvrir le jour ou
 * un arbre achete un levier qu'il n'avait pas le droit d'acheter.
 */
class DomainRoleDefinitionException extends \RuntimeException
{
}
