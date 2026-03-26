<?php

namespace App\Entity\App;

use App\Enum\Element;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'enchantment')]
#[ORM\Entity]
class Enchantment
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: PlayerItem::class)]
    #[ORM\JoinColumn(name: 'player_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PlayerItem $playerItem;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(name: 'name', type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(name: 'stat', type: 'string', length: 30)]
    private string $stat;

    #[ORM\Column(name: 'value', type: 'integer')]
    private int $value;

    #[ORM\Column(name: 'element', type: 'string', length: 20, nullable: true, enumType: Element::class)]
    private ?Element $element = null;

    #[ORM\Column(name: 'applied_at', type: 'datetime')]
    private \DateTime $appliedAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private \DateTime $expiresAt;

    public function __construct()
    {
        $this->appliedAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPlayerItem(): PlayerItem
    {
        return $this->playerItem;
    }

    public function setPlayerItem(PlayerItem $playerItem): void
    {
        $this->playerItem = $playerItem;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStat(): string
    {
        return $this->stat;
    }

    public function setStat(string $stat): void
    {
        $this->stat = $stat;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function getElement(): ?Element
    {
        return $this->element;
    }

    public function setElement(?Element $element): void
    {
        $this->element = $element;
    }

    public function getAppliedAt(): \DateTime
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(\DateTime $appliedAt): void
    {
        $this->appliedAt = $appliedAt;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function getRemainingSeconds(): int
    {
        $diff = $this->expiresAt->getTimestamp() - (new \DateTime())->getTimestamp();

        return max(0, $diff);
    }
}
