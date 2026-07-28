<?php

namespace App\GameEngine\Retention;

/**
 * Pool de commissions invalide (RET-02) : YAML malforme, activite inconnue,
 * objectif nul, ou gabarit sans domaine de rattachement.
 *
 * Sert aussi au **refus de livraison** (RET-02b), auquel cas le message est une
 * clef de traduction : un refus doit toujours pouvoir dire pourquoi.
 */
class WeeklyCommissionException extends \RuntimeException
{
}
