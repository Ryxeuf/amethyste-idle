<?php

namespace App\GameEngine\Settlement;

/**
 * Parametres de foyer invalides (FOY-01) : YAML malforme, seuil de rang
 * manquant ou non ordonne, taux hors bornes, ou zone declaree sans foyer sans
 * en donner la raison.
 */
class SettlementDefinitionException extends \RuntimeException
{
}
