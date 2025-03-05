<?php

namespace App\Exception;

use Exception;

class ItemNotMateriaException extends Exception
{
    protected $message = "Ce n'est pas une materia";

}