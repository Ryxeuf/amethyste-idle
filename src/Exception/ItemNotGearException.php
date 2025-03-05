<?php

namespace App\Exception;

use Exception;

class ItemNotGearException extends Exception
{
    protected $message = "Ce n'est pas un équipement";

}