<?php

namespace App\Entity\App;

use App\Enum\TimeTrialStatus;
use App\Repository\TimeTrialRunRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tentative d'un joueur sur un parcours chronometre (tache 133).
 *
 * Le temps est mesure en secondes reelles entre le depart et la derniere
 * etape. Il n'est fige qu'a l'arrivee : tant que la course tourne, le chrono
 * se deduit de `startedAt`, ce qui evite d'avoir a le rafraichir.
 */
#[ORM\Entity(repositoryClass: TimeTrialRunRepository::class)]
#[ORM\Table(name: 'time_trial_run')]
#[ORM\Index(name: 'idx_time_trial_run_board', columns: ['trial_id', 'elapsed_seconds'])]
#[ORM\Index(name: 'idx_time_trial_run_player_status', columns: ['player_id', 'status'])]
class TimeTrialRun
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: TimeTrial::class)]
    #[ORM\JoinColumn(name: 'trial_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private TimeTrial $trial;

    #[ORM\Column(name: 'status', type: 'string', length: 20, enumType: TimeTrialStatus::class)]
    private TimeTrialStatus $status = TimeTrialStatus::Running;

    #[ORM\Column(name: 'reached_index', type: 'integer', options: ['default' => 0])]
    private int $reachedIndex = 0;

    #[ORM\Column(name: 'started_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    /**
     * Temps final, en secondes. Nul tant que la course n'est pas terminee.
     *
     * Duplique `finishedAt - startedAt`, mais le classement trie dessus : le
     * recalculer en SQL a chaque affichage couterait un index de moins.
     */
    #[ORM\Column(name: 'elapsed_seconds', type: 'integer', nullable: true)]
    private ?int $elapsedSeconds = null;

    public function __construct(Player $player, TimeTrial $trial, ?\DateTimeImmutable $startedAt = null)
    {
        $this->player = $player;
        $this->trial = $trial;
        $this->startedAt = $startedAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getTrial(): TimeTrial
    {
        return $this->trial;
    }

    public function getStatus(): TimeTrialStatus
    {
        return $this->status;
    }

    public function isRunning(): bool
    {
        return TimeTrialStatus::Running === $this->status;
    }

    public function getReachedIndex(): int
    {
        return $this->reachedIndex;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getElapsedSeconds(): ?int
    {
        return $this->elapsedSeconds;
    }

    /**
     * Prochaine zone attendue, ou null si toutes les etapes sont franchies.
     */
    public function nextCheckpoint(): ?string
    {
        return $this->trial->checkpointAt($this->reachedIndex);
    }

    /**
     * Enregistre le franchissement d'une etape.
     */
    public function advance(): void
    {
        ++$this->reachedIndex;
    }

    public function finish(\DateTimeImmutable $at): void
    {
        $this->status = TimeTrialStatus::Finished;
        $this->finishedAt = $at;
        $this->elapsedSeconds = max(0, $at->getTimestamp() - $this->startedAt->getTimestamp());
    }

    public function abandon(\DateTimeImmutable $at): void
    {
        $this->status = TimeTrialStatus::Abandoned;
        $this->finishedAt = $at;
    }

    public function expire(\DateTimeImmutable $at): void
    {
        $this->status = TimeTrialStatus::Expired;
        $this->finishedAt = $at;
    }

    /**
     * Temps ecoule, fige a l'arrivee ou courant tant que la course tourne.
     */
    public function elapsedSecondsAt(\DateTimeImmutable $now): int
    {
        if (null !== $this->elapsedSeconds) {
            return $this->elapsedSeconds;
        }

        return max(0, $now->getTimestamp() - $this->startedAt->getTimestamp());
    }

    public function hasExceededLimit(\DateTimeImmutable $now): bool
    {
        return $this->elapsedSecondsAt($now) > $this->trial->getTimeLimitSeconds();
    }
}
