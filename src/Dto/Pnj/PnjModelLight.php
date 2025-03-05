<?php

namespace App\Dto\Pnj;

use App\Entity\App\Pnj;

class PnjModelLight
{
    public int $id;

    public string $class;

    public string $name;
    public string $coordinates;

    public function __construct(Pnj $pnj)
    {
        $this->id          = $pnj->getId();
        $this->name        = $pnj->getName();
        $this->class       = $pnj->getClassType();
        $this->coordinates = $pnj->getCoordinates();
    }

}