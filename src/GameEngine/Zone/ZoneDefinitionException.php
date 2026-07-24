<?php

namespace App\GameEngine\Zone;

/**
 * Definition declarative de zone invalide (ZON-11) : fichier YAML malforme,
 * cle obligatoire manquante ou connexion pointant vers une zone inconnue.
 */
class ZoneDefinitionException extends \RuntimeException
{
}
