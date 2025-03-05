<?php

namespace App\Exception;

use Doctrine\ORM\EntityNotFoundException;

class PlayerNotFoundException extends EntityNotFoundException
{
    protected $message = "Joueur introuvable";
}