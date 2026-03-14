<?php

namespace App\Exception;

class ItemRequirementsException extends \Exception
{
    protected $message = "Vous n'avez pas les compétences nécessaires pour cet objet";
}
