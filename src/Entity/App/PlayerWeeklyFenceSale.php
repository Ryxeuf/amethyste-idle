<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Les lots passes au receleur cette semaine (FAC-06).
 *
 * Le plafond hebdomadaire du marche gris : une ligne par joueur et par
 * semaine, un cumul — le pattern de la commission (RET-02), pas une ligne
 * par vente. La Confrerie n'aime pas les gros volumes, ca se voit.
 */
#[ORM\Entity]
#[ORM\Table(name: 'player_weekly_fence_sale')]
#[ORM\UniqueConstraint(name: 'uq_player_weekly_fence', columns: ['player_id', 'week_key'])]
class PlayerWeeklyFenceSale
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(name: 'week_key', type: 'string', length: 10)]
    private string $weekKey;

    #[ORM\Column(name: 'lots', type: 'integer', options: ['default' => 0])]
    private int $lots = 0;

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

    public function getWeekKey(): string
    {
        return $this->weekKey;
    }

    public function setWeekKey(string $weekKey): self
    {
        $this->weekKey = $weekKey;

        return $this;
    }

    public function getLots(): int
    {
        return $this->lots;
    }

    public function incrementLots(): void
    {
        ++$this->lots;
    }
}
