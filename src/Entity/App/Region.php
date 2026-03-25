<?php

namespace App\Entity\App;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'region')]
class Region
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(name: 'slug', type: 'string', length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'icon', type: 'string', length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(name: 'tax_rate', type: 'decimal', precision: 5, scale: 4, options: ['default' => '0.0500'])]
    private string $taxRate = '0.0500';

    #[ORM\Column(name: 'is_contestable', type: 'boolean', options: ['default' => true])]
    private bool $isContestable = true;

    #[ORM\ManyToOne(targetEntity: Map::class)]
    #[ORM\JoinColumn(name: 'capital_map_id', referencedColumnName: 'id', nullable: true)]
    private ?Map $capitalMap = null;

    /** @var Collection<int, Map> */
    #[ORM\OneToMany(targetEntity: Map::class, mappedBy: 'region')]
    private Collection $maps;

    public function __construct()
    {
        $this->maps = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getTaxRate(): string
    {
        return $this->taxRate;
    }

    public function getTaxRateFloat(): float
    {
        return (float) $this->taxRate;
    }

    public function setTaxRate(string $taxRate): self
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    public function isContestable(): bool
    {
        return $this->isContestable;
    }

    public function setIsContestable(bool $isContestable): self
    {
        $this->isContestable = $isContestable;

        return $this;
    }

    public function getCapitalMap(): ?Map
    {
        return $this->capitalMap;
    }

    public function setCapitalMap(?Map $capitalMap): self
    {
        $this->capitalMap = $capitalMap;

        return $this;
    }

    /**
     * @return Collection<int, Map>
     */
    public function getMaps(): Collection
    {
        return $this->maps;
    }

    public function addMap(Map $map): self
    {
        if (!$this->maps->contains($map)) {
            $this->maps->add($map);
            $map->setRegion($this);
        }

        return $this;
    }

    public function removeMap(Map $map): self
    {
        if ($this->maps->removeElement($map)) {
            if ($map->getRegion() === $this) {
                $map->setRegion(null);
            }
        }

        return $this;
    }
}
