<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'party_invitation')]
#[ORM\UniqueConstraint(name: 'party_invitation_unique', columns: ['party_id', 'player_id'])]
class PartyInvitation
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Party::class)]
    #[ORM\JoinColumn(name: 'party_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Party $party;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'invited_by_id', referencedColumnName: 'id', nullable: false)]
    private Player $invitedBy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParty(): Party
    {
        return $this->party;
    }

    public function setParty(Party $party): self
    {
        $this->party = $party;

        return $this;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getInvitedBy(): Player
    {
        return $this->invitedBy;
    }

    public function setInvitedBy(Player $invitedBy): self
    {
        $this->invitedBy = $invitedBy;

        return $this;
    }
}
