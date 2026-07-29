<?php

namespace App\Exception;

/**
 * La materia ne va pas dans cet emplacement (DOM-03).
 *
 * Le refus porte sur le **sertissage**, jamais sur le port : la piece se porte,
 * elle n'accepte simplement pas cette matiere-la. C'est ce qui separe un
 * emplacement type d'un interdit de classe (GAME_DOMAINS § 3, garde-fou 1).
 */
class MateriaSlotTypeException extends \Exception
{
    protected $message = 'Cet emplacement n\'accepte pas ce type de materia';
}
