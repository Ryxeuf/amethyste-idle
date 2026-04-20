<?php

namespace App\Entity\App;

use App\Enum\RankingTab;
use App\Repository\PlayerSeasonRankingSnapshotRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Snapshot fige du classement top-N pour une saison et un onglet donne.
 * Une ligne par (season, tab, rank).
 */
#[ORM\Entity(repositoryClass: PlayerSeasonRankingSnapshotRepository::class)]
#[ORM\Table(name: 'player_season_ranking_snapshot')]
#[ORM\UniqueConstraint(name: 'uniq_season_tab_rank', columns: ['season_id', 'tab', 'rank_position'])]
#[ORM\Index(name: 'idx_ssrs_season_tab', columns: ['season_id', 'tab'])]
#[ORM\Index(name: 'idx_ssrs_player', columns: ['player_id'])]
class PlayerSeasonRankingSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: InfluenceSeason::class)]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private InfluenceSeason $season;

    #[ORM\Column(name: 'tab', type: 'string', length: 20, enumType: RankingTab::class)]
    private RankingTab $tab;

    #[ORM\Column(name: 'rank_position', type: 'integer')]
    private int $rank;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(name: 'player_name', type: 'string', length: 100)]
    private string $playerName;

    #[ORM\Column(name: 'total_value', type: 'bigint')]
    private string $totalValue;

    #[ORM\Column(name: 'snapshotted_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $snapshottedAt;

    public function __construct(
        InfluenceSeason $season,
        RankingTab $tab,
        int $rank,
        Player $player,
        int $totalValue,
    ) {
        if ($rank < 1) {
            throw new \InvalidArgumentException('Rank must be >= 1.');
        }
        if ($totalValue < 0) {
            throw new \InvalidArgumentException('Total value must be >= 0.');
        }

        $this->season = $season;
        $this->tab = $tab;
        $this->rank = $rank;
        $this->player = $player;
        $this->playerName = $player->getName();
        $this->totalValue = (string) $totalValue;
        $this->snapshottedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): InfluenceSeason
    {
        return $this->season;
    }

    public function getTab(): RankingTab
    {
        return $this->tab;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    public function getTotalValue(): int
    {
        return (int) $this->totalValue;
    }

    public function getSnapshottedAt(): \DateTimeImmutable
    {
        return $this->snapshottedAt;
    }
}
