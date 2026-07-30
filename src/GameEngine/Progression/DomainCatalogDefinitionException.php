<?php

namespace App\GameEngine\Progression;

/**
 * Le catalogue des arbres est mal declare (ONB-09).
 *
 * Une exception plutot qu'un repli silencieux : le catalogue est **public et
 * complet**, et un arbre qui disparaitrait de l'ecran faute d'entree serait un
 * arbre que personne ne saurait pouvoir ouvrir.
 */
class DomainCatalogDefinitionException extends \RuntimeException
{
}
