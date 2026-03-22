<?php

namespace App\Entity\App;

use App\Enum\FriendshipStatus;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'friendships')]
#[ORM\UniqueConstraint(name: 'friendship_unique', columns: ['player_id', 'friend_id'])]
class Friendship
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'friend_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $friend;

    #[ORM\Column(name: 'status', type: 'string', length: 20, enumType: FriendshipStatus::class)]
    private FriendshipStatus $status = FriendshipStatus::Pending;

    public function getId(): int
    {
        return $this->id;
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

    public function getFriend(): Player
    {
        return $this->friend;
    }

    public function setFriend(Player $friend): self
    {
        $this->friend = $friend;

        return $this;
    }

    public function getStatus(): FriendshipStatus
    {
        return $this->status;
    }

    public function setStatus(FriendshipStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
