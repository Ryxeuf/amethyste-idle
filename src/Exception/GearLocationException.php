<?php

namespace App\Exception;

use Exception;

class GearLocationException extends Exception
{
    protected $message = "Équipement au mauvais endroit";

}