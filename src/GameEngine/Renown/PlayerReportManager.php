<?php

namespace App\GameEngine\Renown;

use App\Entity\App\Player;
use App\Entity\App\PlayerReport;
use App\Entity\User;
use App\Enum\PlayerReportReason;
use App\Enum\PlayerReportStatus;
use App\Repository\PlayerReportRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gere les signalements entre joueurs (report systeme basique).
 *
 * Un rapport accepte par un moderateur applique un malus de renommee au joueur signale.
 */
class PlayerReportManager
{
    /** Malus de renommee applique a un rapport accepte. */
    public const RENOWN_MALUS = 50;

    /** Delai minimum entre deux rapports de la meme paire (anti-spam). */
    public const COOLDOWN_HOURS = 24;

    /** Longueur max de la description. */
    public const MAX_DESCRIPTION_LENGTH = 1000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerReportRepository $reportRepository,
        private readonly PlayerRenownManager $renownManager,
    ) {
    }

    /**
     * Soumet un nouveau rapport. Retourne null si le cooldown n'est pas respecte
     * ou si le rapporteur tente de se signaler lui-meme.
     *
     * @throws \InvalidArgumentException si la description est vide ou trop longue
     */
    public function submitReport(
        Player $reporter,
        Player $reportedPlayer,
        PlayerReportReason $reason,
        string $description,
    ): ?PlayerReport {
        if ($reporter->getId() === $reportedPlayer->getId()) {
            return null;
        }

        $trimmed = trim($description);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('La description ne peut pas etre vide.');
        }
        if (\strlen($trimmed) > self::MAX_DESCRIPTION_LENGTH) {
            throw new \InvalidArgumentException('Description trop longue (max ' . self::MAX_DESCRIPTION_LENGTH . ' caracteres).');
        }

        $since = (new \DateTimeImmutable())->modify('-' . self::COOLDOWN_HOURS . ' hours');
        if ($this->reportRepository->countRecentReports($reporter, $reportedPlayer, $since) > 0) {
            return null;
        }

        $report = new PlayerReport();
        $report->setReporter($reporter);
        $report->setReportedPlayer($reportedPlayer);
        $report->setReason($reason);
        $report->setDescription($trimmed);

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        return $report;
    }

    /**
     * Accepte un rapport et applique le malus de renommee au joueur signale.
     */
    public function acceptReport(PlayerReport $report, User $moderator): void
    {
        if ($report->getStatus() !== PlayerReportStatus::Pending) {
            return;
        }

        $malus = self::RENOWN_MALUS;
        $this->renownManager->addRenown(
            $report->getReportedPlayer(),
            -$malus,
            'Rapport accepte #' . $report->getId(),
        );

        $report->setStatus(PlayerReportStatus::Accepted);
        $report->setRenownMalusApplied($malus);
        $report->setReviewedBy($moderator);
        $report->setReviewedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    /**
     * Rejette un rapport sans appliquer de malus.
     */
    public function rejectReport(PlayerReport $report, User $moderator): void
    {
        if ($report->getStatus() !== PlayerReportStatus::Pending) {
            return;
        }

        $report->setStatus(PlayerReportStatus::Rejected);
        $report->setReviewedBy($moderator);
        $report->setReviewedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }
}
