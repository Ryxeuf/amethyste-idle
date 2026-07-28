<?php

namespace App\GameEngine\Economy;

/**
 * Perimetre de purete invalide (ECO-21) : YAML malforme, perimetre vide, ou
 * matiere declaree a la fois incluse et exclue.
 */
class PurityDefinitionException extends \RuntimeException
{
}
