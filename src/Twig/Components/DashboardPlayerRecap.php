<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent()]
class DashboardPlayerRecap
{
    use DefaultActionTrait;

    public function getMapName(): string
    {
        return 'Fixture Map';
    }

    public function getX(): int
    {
        return 1;
    }

    public function getY(): int
    {
        return 1;
    }

    public function getLife(): int
    {
        return 90;
    }

    public function getMaxLife(): int
    {
        return 100;
    }

    public function getEnergy(): int
    {
        return 100;
    }

    public function getCreatedAt(): \DateTime
    {
        return new \DateTime();
    }

    public function getName(): string
    {
        return 'John Doe';
    }
}
