<?php

namespace App\GameEngine\Season;

/**
 * Configuration de maree consequence invalide (FOY-15). Levee a la lecture,
 * jamais a l'usage : un fichier mal ecrit doit echouer au demarrage, pas se
 * decouvrir sur un arc de saison six semaines plus tard.
 */
class ConsequenceTideDefinitionException extends \RuntimeException
{
}
