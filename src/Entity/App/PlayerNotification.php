<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'player_notification')]
#[ORM\Entity()]
class PlayerNotification
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(name: 'comment', type: 'text')]
    private $comment;

    #[ORM\Column(name: 'admin_comment', type: 'text', nullable: true)]
    private $admin_comment;

    #[ORM\Column(name: 'type', type: 'integer', nullable: true)]
    private $type;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id')]
    private $player;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return PlayerNotification
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return PlayerNotification
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set player
     *
     * @param Player $player
     *
     * @return PlayerNotification
     */
    public function setPlayer(Player $player = null)
    {
        $this->player = $player;

        return $this;
    }

    /**
     * Get player
     *
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * Set adminComment
     *
     * @param string $adminComment
     *
     * @return PlayerNotification
     */
    public function setAdminComment($adminComment)
    {
        $this->admin_comment = $adminComment;

        return $this;
    }

    /**
     * Get adminComment
     *
     * @return string
     */
    public function getAdminComment()
    {
        return $this->admin_comment;
    }
}
