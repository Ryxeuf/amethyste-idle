<?php

namespace App\Entity\App\Traits;

use DateTime;
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

    /**
     * @return int
     */
    public function getLife(): int
    {
        return $this->life;
    }

    /**
     * @param int $life
     */
    public function setLife(int $life): void
    {
        $this->life = $life;
    }

    /**
     * @return DateTime
     */
    public function getDiedAt(): ?DateTime
    {
        return $this->diedAt;
    }

    /**
     * @param DateTime $diedAt
     */
    public function setDiedAt(?DateTime $diedAt = null): void
    {
        $this->diedAt = $diedAt;
    }
}
