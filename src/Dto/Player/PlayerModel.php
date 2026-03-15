<?php

namespace App\Dto\Player;

use App\Entity\App\Player as PlayerEntity;

class PlayerModel extends PlayerModelLight
{
    public int $life;
    public int $maxLife;
    public int $energy;
    public int $x;
    public int $y;
    public int $maxEnergy;
    public string $coordinates;
    public string $map;
    public \DateTime $createdAt;
    public bool $inFight = false;
    public bool $dead = false;
    public ?int $fightId = null;
    public ?int $mapId = null;
    public string $areaSlug;

    public function __construct(PlayerEntity $player, bool $self = false)
    {
        parent::__construct($player);

        $this->life = $player->getLife();
        $this->maxLife = $player->getMaxLife();
        $this->energy = $player->getEnergy();
        $this->maxEnergy = $player->getMaxEnergy();
        $this->createdAt = $player->getCreatedAt();
        $this->coordinates = $player->getCoordinates();
        $this->x = $player->getX();
        $this->y = $player->getY();
        $this->map = $player->getMap()?->getName() ?? '';
        $this->mapId = $player->getMap()?->getId() ?? null;
        $this->areaSlug = $player->getMap()?->getAreaByCoordinates($player->getX(), $player->getY())?->getSlug() ?? '';
        $this->self = $self;
    }
}
