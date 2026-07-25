<?php

namespace App\Entity\App;

use App\Entity\Game\Dungeon;
use App\Repository\GroupDungeonClearRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trace d'une reussite de donjon de groupe par un joueur (pivot PBBG, ZON-20).
 *
 * Une entree par membre a chaque run complete. Sert a calculer les recompenses
 * decroissantes : plus un joueur enchaine le meme donjon dans la fenetre
 * glissante (`zone.dungeon.lockout.window_hours`), plus la recompense fond —
 * on prefere la decroissance au blocage sec (protection de l'economie, variete
 * de contenu).
 */
#[ORM\Entity(repositoryClass: GroupDungeonClearRepository::class)]
#[ORM\Table(name: 'group_dungeon_clear')]
#[ORM\Index(name: 'idx_group_dungeon_clear_player_dungeon', columns: ['player_id', 'dungeon_id', 'cleared_at'])]
class GroupDungeonClear
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Dungeon::class)]
    #[ORM\JoinColumn(name: 'dungeon_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Dungeon $dungeon;

    #[ORM\ManyToOne(targetEntity: GroupDungeonRun::class)]
    #[ORM\JoinColumn(name: 'run_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GroupDungeonRun $run;

    #[ORM\Column(name: 'gils_awarded', type: 'integer', options: ['default' => 0])]
    private int $gilsAwarded = 0;

    #[ORM\Column(name: 'cleared_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $clearedAt;

    public function __construct(Player $player, Dungeon $dungeon, ?GroupDungeonRun $run, int $gilsAwarded, \DateTimeImmutable $clearedAt)
    {
        $this->player = $player;
        $this->dungeon = $dungeon;
        $this->run = $run;
        $this->gilsAwarded = $gilsAwarded;
        $this->clearedAt = $clearedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function getRun(): ?GroupDungeonRun
    {
        return $this->run;
    }

    public function getGilsAwarded(): int
    {
        return $this->gilsAwarded;
    }

    public function getClearedAt(): \DateTimeImmutable
    {
        return $this->clearedAt;
    }
}
