<?php

namespace App\GameEngine\Progression;

/**
 * L'echelle de port est mal declaree (ONB-20b).
 *
 * Une exception plutot qu'un repli : une famille mal declaree produit soit une
 * arme que personne ne peut porter, soit une arme que tout le monde porte sans
 * l'avoir apprise. Les deux se voient tard, et jamais par celui qui a edite le
 * fichier.
 */
class EquipmentPortDefinitionException extends \RuntimeException
{
}
