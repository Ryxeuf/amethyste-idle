<?php

namespace App\Exception;

use Doctrine\ORM\EntityNotFoundException;

class CellNotFoundException extends EntityNotFoundException
{
    protected $message = 'Destination introuvable';
}
