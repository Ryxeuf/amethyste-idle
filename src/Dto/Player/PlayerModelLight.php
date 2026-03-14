<?php

namespace App\Dto\Player;

use App\Entity\App\Player as PlayerEntity;

class PlayerModelLight
{
    public int $id;
    public string $class;
    public string $name;
    public string $coordinates;
    public bool $self = false;

    public function __construct(PlayerEntity $player)
    {
        $this->id = $player->getId();
        $this->name = $player->getName();
        $this->class = $player->getClassType();
        $this->coordinates = $player->getCoordinates();
    }
}
