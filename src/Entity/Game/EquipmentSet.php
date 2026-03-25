<?php

namespace App\Entity\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'game_equipment_sets')]
class EquipmentSet
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'slug', type: 'string', length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    /** @var Collection<int, EquipmentSetBonus> */
    #[ORM\OneToMany(targetEntity: EquipmentSetBonus::class, mappedBy: 'equipmentSet', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['requiredPieces' => 'ASC'])]
    private Collection $bonuses;

    /** @var Collection<int, Item> */
    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'equipmentSet')]
    private Collection $items;

    public function __construct()
    {
        $this->bonuses = new ArrayCollection();
        $this->items = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /** @return Collection<int, EquipmentSetBonus> */
    public function getBonuses(): Collection
    {
        return $this->bonuses;
    }

    public function addBonus(EquipmentSetBonus $bonus): void
    {
        if (!$this->bonuses->contains($bonus)) {
            $this->bonuses->add($bonus);
            $bonus->setEquipmentSet($this);
        }
    }

    /** @return Collection<int, Item> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setEquipmentSet($this);
        }
    }
}
