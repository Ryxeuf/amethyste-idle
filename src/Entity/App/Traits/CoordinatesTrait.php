<?php

namespace App\Entity\App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait CoordinatesTrait
{
    /**
     * Coordonnées de la carte au sein du monde.
     */
    #[ORM\Column(name: 'coordinates', type: 'string')]
    protected string $coordinates;

    public function getCoordinates(): string
    {
        return $this->coordinates;
    }

    public function setCoordinates(string $coordinates): void
    {
        $this->coordinates = $coordinates;
    }

    public function getX(): int
    {
        [$x] = explode('.', $this->getCoordinates());

        return (int) $x;
    }

    public function getY(): int
    {
        [$x, $y] = explode('.', $this->getCoordinates());

        return (int) $y;
    }
}
