<?php

namespace App\Exception;

use Exception;

class ItemRequirementsException extends Exception
{
    protected $message = "Vous n'avez pas les compétences nécessaires pour cet objet";
}