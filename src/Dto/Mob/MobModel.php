<?php

namespace App\Dto\Mob;

use App\Entity\App\Mob as MobEntity;

class MobModel extends MobModelLight
{
    public string $name;
    public int $life;
    public int $maxLife;

    public function __construct(MobEntity $mob)
    {
        parent::__construct($mob);

        $this->name = $mob->getMonster()->getName();
        $this->life = $mob->getLife();
        $this->maxLife = $mob->getMonster()->getLife();
    }
}
