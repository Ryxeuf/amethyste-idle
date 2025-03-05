<?php

namespace App\Exception;

class PathCoordinatesNotFoundException extends NotFoundException
{
    protected $message = "Coordonnées de chemin introuvables";
}
