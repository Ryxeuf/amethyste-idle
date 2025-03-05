<?php

namespace App\Dto\Fight;

use App\Entity\App\Player;

class FightPlayer
{
    public int $id;
    public int $life;
    public int $maxLife;
    public int $energy;
    public int $maxEnergy;

    public function __construct(Player $player)
    {
        $this->id = $player->getId();
        $this->life = $player->getLife();
        $this->maxLife = $player->getMaxLife();
        $this->energy = $player->getEnergy();
        $this->maxEnergy = $player->getMaxEnergy();
    }
}
