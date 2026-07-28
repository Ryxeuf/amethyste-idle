<?php

namespace App\Entity\App;

use App\Repository\SettlementWeeklyWorkContributionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Ce qu'un joueur a mis dans le chantier de la semaine (RET-05).
 *
 * Les contributions sont **nominatives**, et c'est le point : un chantier rempli
 * doit pouvoir dire **qui** l'a rempli. Un compteur anonyme aurait fait du
 * chantier une jauge de plus ; nommer ceux qui l'ont poussee en fait une
 * reconnaissance, et c'est ce que l'horizon hebdomadaire cherche a produire
 * (GAME_PROGRESSION § 3 — « on compte sur moi »).
 */
#[ORM\Entity(repositoryClass: SettlementWeeklyWorkContributionRepository::class)]
#[ORM\Table(name: 'settlement_weekly_work_contribution')]
#[ORM\UniqueConstraint(name: 'uq_settlement_weekly_work_contribution', columns: ['work_id', 'player_id'])]
class SettlementWeeklyWorkContribution
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SettlementWeeklyWork::class, inversedBy: 'contributions')]
    #[ORM\JoinColumn(name: 'work_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private SettlementWeeklyWork $work;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(name: 'units', type: 'integer', options: ['default' => 0])]
    private int $units = 0;

    public function __construct(SettlementWeeklyWork $work, Player $player)
    {
        $this->work = $work;
        $this->player = $player;
        $work->addContribution($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWork(): SettlementWeeklyWork
    {
        return $this->work;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getUnits(): int
    {
        return $this->units;
    }

    public function addUnits(int $units): self
    {
        if ($units > 0) {
            $this->units += $units;
        }

        return $this;
    }
}
