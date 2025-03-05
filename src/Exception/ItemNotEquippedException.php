<?php

namespace App\Exception;

use Exception;

class ItemNotEquippedException extends Exception
{
    protected $message = "Objet non équipé";
}