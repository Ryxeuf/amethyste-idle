<?php

namespace App\Entity\App;

use App\Enum\RankingTab;
use App\Repository\PlayerSeasonRewardRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Recompense (titre) attribuee a un joueur a la fin d'une saison, pour sa
 * position dans un classement donne (top-3 par onglet). Une seule recompense
 * par (saison, onglet, joueur) — garantie par l'index UNIQUE.
 */
#[ORM\Entity(repositoryClass: PlayerSeasonRewardRepository::class)]
#[ORM\Table(name: 'player_season_reward')]
#[ORM\UniqueConstraint(name: 'uniq_season_tab_player', columns: ['season_id', 'tab', 'player_id'])]
#[ORM\Index(name: 'idx_psr_player', columns: ['player_id'])]
#[ORM\Index(name: 'idx_psr_season', columns: ['season_id'])]
class PlayerSeasonReward
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: InfluenceSeason::class)]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private InfluenceSeason $season;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(name: 'tab', type: 'string', length: 20, enumType: RankingTab::class)]
    private RankingTab $tab;

    #[ORM\Column(name: 'rank_position', type: 'integer')]
    private int $rank;

    #[ORM\Column(name: 'title_label', type: 'string', length: 120)]
    private string $titleLabel;

    #[ORM\Column(name: 'cosmetic_icon', type: 'string', length: 60, nullable: true)]
    private ?string $cosmeticIcon = null;

    #[ORM\Column(name: 'awarded_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $awardedAt;

    public function __construct(
        InfluenceSeason $season,
        Player $player,
        RankingTab $tab,
        int $rank,
        string $titleLabel,
        ?string $cosmeticIcon = null,
    ) {
        if ($rank < 1 || $rank > 3) {
            throw new \InvalidArgumentException('Rank must be between 1 and 3 (podium only).');
        }
        if (trim($titleLabel) === '') {
            throw new \InvalidArgumentException('Title label must not be empty.');
        }

        $this->season = $season;
        $this->player = $player;
        $this->tab = $tab;
        $this->rank = $rank;
        $this->titleLabel = $titleLabel;
        $this->cosmeticIcon = $cosmeticIcon !== null && trim($cosmeticIcon) !== '' ? $cosmeticIcon : null;
        $this->awardedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): InfluenceSeason
    {
        return $this->season;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getTab(): RankingTab
    {
        return $this->tab;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getTitleLabel(): string
    {
        return $this->titleLabel;
    }

    public function getCosmeticIcon(): ?string
    {
        return $this->cosmeticIcon;
    }

    public function getAwardedAt(): \DateTimeImmutable
    {
        return $this->awardedAt;
    }
}
