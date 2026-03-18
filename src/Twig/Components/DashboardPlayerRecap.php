<?php

namespace App\Twig\Components;

use App\Entity\App\Player;
use App\Helper\PlayerHelper;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent()]
class DashboardPlayerRecap
{
    use DefaultActionTrait;

    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function getPlayer(): ?Player
    {
        return $this->playerHelper->getPlayer();
    }

    public function getMapName(): string
    {
        $player = $this->getPlayer();

        return $player?->getMap()?->getName() ?? '???';
    }

    public function getX(): int
    {
        $player = $this->getPlayer();

        return $player ? $player->getX() : 0;
    }

    public function getY(): int
    {
        $player = $this->getPlayer();

        return $player ? $player->getY() : 0;
    }

    public function getLife(): int
    {
        return $this->getPlayer()?->getLife() ?? 0;
    }

    public function getMaxLife(): int
    {
        return $this->getPlayer()?->getMaxLife() ?? 1;
    }

    public function getEnergy(): int
    {
        return $this->getPlayer()?->getEnergy() ?? 0;
    }

    public function getMaxEnergy(): int
    {
        return $this->getPlayer()?->getMaxEnergy() ?? 1;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->getPlayer()?->getCreatedAt() ?? new \DateTime();
    }

    public function getName(): string
    {
        return $this->getPlayer()?->getName() ?? 'Inconnu';
    }

    public function getClassType(): string
    {
        return $this->getPlayer()?->getClassType() ?? 'Aventurier';
    }

    public function getGils(): int
    {
        return $this->getPlayer()?->getGils() ?? 0;
    }

    public function isDead(): bool
    {
        return $this->getPlayer()?->isDead() ?? false;
    }

    public function isInFight(): bool
    {
        return $this->getPlayer()?->getFight() !== null;
    }
}
