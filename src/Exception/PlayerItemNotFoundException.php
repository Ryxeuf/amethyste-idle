<?php

namespace App\Exception;

use Doctrine\ORM\EntityNotFoundException;

class PlayerItemNotFoundException extends EntityNotFoundException
{
    protected $message = "Objet introuvable";
}