<?php

namespace App\Exception;

class PathNotFoundException extends NotFoundException
{
    protected $message = 'Aucun chemin disponible';
}
