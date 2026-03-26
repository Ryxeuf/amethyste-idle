<?php

namespace App\Entity\App;

use App\Entity\Game\EnchantmentDefinition;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'enchantments')]
#[ORM\Index(columns: ['player_item_id'], name: 'IDX_enchantment_player_item')]
#[ORM\Index(columns: ['expires_at'], name: 'IDX_enchantment_expires')]
class Enchantment
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: PlayerItem::class)]
    #[ORM\JoinColumn(name: 'player_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PlayerItem $playerItem;

    #[ORM\ManyToOne(targetEntity: EnchantmentDefinition::class)]
    #[ORM\JoinColumn(name: 'enchantment_definition_id', referencedColumnName: 'id', nullable: false)]
    private EnchantmentDefinition $definition;

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

    public function getPlayerItem(): PlayerItem
    {
        return $this->playerItem;
    }

    public function setPlayerItem(PlayerItem $playerItem): void
    {
        $this->playerItem = $playerItem;
    }

    public function getDefinition(): EnchantmentDefinition
    {
        return $this->definition;
    }

    public function setDefinition(EnchantmentDefinition $definition): void
    {
        $this->definition = $definition;
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
        return $this->expiresAt <= new \DateTime();
    }

    public function getRemainingSeconds(): int
    {
        $now = new \DateTime();
        if ($this->expiresAt <= $now) {
            return 0;
        }

        return $this->expiresAt->getTimestamp() - $now->getTimestamp();
    }
}
