<?php

namespace App\Entity\App;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'guild_vault')]
class GuildVault
{
    use TimestampableEntity;

    public const DEFAULT_MAX_SLOTS = 30;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Guild::class)]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Guild $guild;

    #[ORM\Column(name: 'max_slots', type: 'integer', options: ['default' => 30])]
    private int $maxSlots = self::DEFAULT_MAX_SLOTS;

    /** @var Collection<int, PlayerItem> */
    #[ORM\OneToMany(targetEntity: PlayerItem::class, mappedBy: 'guildVault')]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuild(): Guild
    {
        return $this->guild;
    }

    public function setGuild(Guild $guild): self
    {
        $this->guild = $guild;

        return $this;
    }

    public function getMaxSlots(): int
    {
        return $this->maxSlots;
    }

    public function setMaxSlots(int $maxSlots): self
    {
        $this->maxSlots = $maxSlots;

        return $this;
    }

    /**
     * @return Collection<int, PlayerItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(PlayerItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setGuildVault($this);
        }

        return $this;
    }

    public function removeItem(PlayerItem $item): self
    {
        if ($this->items->removeElement($item)) {
            $item->setGuildVault(null);
        }

        return $this;
    }

    public function getOccupiedSlots(): int
    {
        return $this->items->count();
    }

    public function isFull(): bool
    {
        return $this->getOccupiedSlots() >= $this->maxSlots;
    }
}
