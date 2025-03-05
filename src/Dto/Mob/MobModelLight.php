<?php

namespace App\Dto\Mob;

use App\Entity\App\Mob as MobEntity;

class MobModelLight
{
    public int    $id;
    public string $slug;
    public string $coordinates;

    public function __construct(MobEntity $mob)
    {
        $this->id          = $mob->getId();
        $this->slug        = $mob->getMonster()->getSlug();
        $this->coordinates = $mob->getCoordinates();
    }
}
