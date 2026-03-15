<?php

namespace App\Entity\App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait CharacterStatsTrait
{
    public function isDead(): bool
    {
        return $this->getDiedAt() !== null || $this->getLife() === 0;
    }

    #[ORM\Column(name: 'life', type: 'integer')]
    protected $life;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $diedAt;

    public function getLife(): int
    {
        return $this->life;
    }

    public function setLife(int $life): void
    {
        $this->life = $life;
    }

    public function getDiedAt(): ?\DateTime
    {
        return $this->diedAt;
    }

    public function setDiedAt(?\DateTime $diedAt = null): void
    {
        $this->diedAt = $diedAt;
    }
}
