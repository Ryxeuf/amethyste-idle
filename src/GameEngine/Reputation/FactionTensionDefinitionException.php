<?php

namespace App\GameEngine\Reputation;

/**
 * Le catalogue de l'axe doctrinal est invalide (FAC-01).
 *
 * Une definition fausse ne doit jamais se rattraper en silence : une paire
 * asymetrique ou un palier inconnu produirait une decote appliquee au hasard,
 * et le joueur perdrait de la reputation sans qu'aucun ecran ne puisse dire
 * pourquoi.
 */
class FactionTensionDefinitionException extends \RuntimeException
{
}
