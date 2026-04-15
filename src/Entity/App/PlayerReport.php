<?php

namespace App\Entity\App;

use App\Entity\User;
use App\Enum\PlayerReportReason;
use App\Enum\PlayerReportStatus;
use App\Repository\PlayerReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'player_report')]
#[ORM\Index(columns: ['reported_player_id', 'created_at'], name: 'idx_report_reported_created')]
#[ORM\Index(columns: ['reporter_id', 'reported_player_id', 'created_at'], name: 'idx_report_pair_created')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_report_status_created')]
#[ORM\Entity(repositoryClass: PlayerReportRepository::class)]
class PlayerReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'reporter_id', referencedColumnName: 'id', nullable: false)]
    private Player $reporter;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'reported_player_id', referencedColumnName: 'id', nullable: false)]
    private Player $reportedPlayer;

    #[ORM\Column(name: 'reason', type: 'string', length: 32, enumType: PlayerReportReason::class)]
    private PlayerReportReason $reason;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    #[ORM\Column(name: 'status', type: 'string', length: 16, enumType: PlayerReportStatus::class, options: ['default' => 'pending'])]
    private PlayerReportStatus $status = PlayerReportStatus::Pending;

    #[ORM\Column(name: 'renown_malus_applied', type: 'integer', options: ['default' => 0])]
    private int $renownMalusApplied = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reviewed_by_id', referencedColumnName: 'id', nullable: true)]
    private ?User $reviewedBy = null;

    #[ORM\Column(name: 'reviewed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReporter(): Player
    {
        return $this->reporter;
    }

    public function setReporter(Player $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getReportedPlayer(): Player
    {
        return $this->reportedPlayer;
    }

    public function setReportedPlayer(Player $reportedPlayer): self
    {
        $this->reportedPlayer = $reportedPlayer;

        return $this;
    }

    public function getReason(): PlayerReportReason
    {
        return $this->reason;
    }

    public function setReason(PlayerReportReason $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): PlayerReportStatus
    {
        return $this->status;
    }

    public function setStatus(PlayerReportStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRenownMalusApplied(): int
    {
        return $this->renownMalusApplied;
    }

    public function setRenownMalusApplied(int $renownMalusApplied): self
    {
        $this->renownMalusApplied = $renownMalusApplied;

        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): self
    {
        $this->reviewedBy = $reviewedBy;

        return $this;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): self
    {
        $this->reviewedAt = $reviewedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
