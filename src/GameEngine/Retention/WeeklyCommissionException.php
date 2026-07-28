<?php

namespace App\GameEngine\Retention;

/**
 * Pool de commissions invalide (RET-02) : YAML malforme, activite inconnue,
 * objectif nul, ou gabarit sans domaine de rattachement.
 */
class WeeklyCommissionException extends \RuntimeException
{
}
