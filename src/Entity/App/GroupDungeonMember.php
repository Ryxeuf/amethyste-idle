<?php

namespace App\Entity\App;

use App\Repository\GroupDungeonMemberRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Membre d'un donjon de groupe (pivot PBBG, ZON-19).
 *
 * Instantane d'un participant fige a la formation du groupe : decouple le run
 * de la `Party` mutable. Une entree par joueur et par run.
 */
#[ORM\Entity(repositoryClass: GroupDungeonMemberRepository::class)]
#[ORM\Table(name: 'group_dungeon_member')]
#[ORM\UniqueConstraint(name: 'uniq_group_dungeon_member', columns: ['run_id', 'player_id'])]
#[ORM\Index(name: 'idx_group_dungeon_member_player', columns: ['player_id'])]
class GroupDungeonMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GroupDungeonRun::class, inversedBy: 'members')]
    #[ORM\JoinColumn(name: 'run_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private GroupDungeonRun $run;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    public function __construct(GroupDungeonRun $run, Player $player)
    {
        $this->run = $run;
        $this->player = $player;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRun(): GroupDungeonRun
    {
        return $this->run;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
