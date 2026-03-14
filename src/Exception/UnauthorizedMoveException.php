<?php

namespace App\Exception;

class UnauthorizedMoveException extends \Exception
{
    protected $message = 'Mouvement impossible';
}
