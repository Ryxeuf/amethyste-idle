<?php

namespace App\Exception;

use Exception;

class UnauthorizedMoveException extends Exception
{
    protected $message = "Mouvement impossible";
}
