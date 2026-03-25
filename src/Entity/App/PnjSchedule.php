<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;

/**
 * Horaire de routine d'un PNJ : à quelle heure in-game il se trouve à quelles coordonnées.
 */
#[ORM\Table(name: 'pnj_schedule')]
#[ORM\Entity()]
class PnjSchedule
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pnj::class)]
    #[ORM\JoinColumn(name: 'pnj_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Pnj $pnj;

    /**
     * Heure in-game (0-23) à laquelle le PNJ doit se trouver à ces coordonnées.
     */
    #[ORM\Column(name: 'hour', type: 'integer')]
    private int $hour;

    /**
     * Coordonnées cibles au format "x.y".
     */
    #[ORM\Column(name: 'coordinates', type: 'string', length: 20)]
    private string $coordinates;

    /**
     * Carte cible (permet les routines cross-map, mais en pratique même carte).
     */
    #[ORM\ManyToOne(targetEntity: Map::class)]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id', nullable: false)]
    private Map $map;

    /**
     * Libellé optionnel pour debug/admin (ex: "Travail à la forge", "Repos à la taverne").
     */
    #[ORM\Column(name: 'label', type: 'string', length: 255, nullable: true)]
    private ?string $label = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPnj(): Pnj
    {
        return $this->pnj;
    }

    public function setPnj(Pnj $pnj): self
    {
        $this->pnj = $pnj;

        return $this;
    }

    public function getHour(): int
    {
        return $this->hour;
    }

    public function setHour(int $hour): self
    {
        $this->hour = $hour;

        return $this;
    }

    public function getCoordinates(): string
    {
        return $this->coordinates;
    }

    public function setCoordinates(string $coordinates): self
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    public function getMap(): Map
    {
        return $this->map;
    }

    public function setMap(Map $map): self
    {
        $this->map = $map;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getX(): int
    {
        return (int) explode('.', $this->coordinates)[0];
    }

    public function getY(): int
    {
        return (int) (explode('.', $this->coordinates)[1] ?? 0);
    }
}
