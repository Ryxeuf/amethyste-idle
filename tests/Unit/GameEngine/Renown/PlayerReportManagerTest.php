<?php

namespace App\Tests\Unit\GameEngine\Renown;

use App\Entity\App\Player;
use App\Entity\App\PlayerReport;
use App\Entity\User;
use App\Enum\PlayerReportReason;
use App\Enum\PlayerReportStatus;
use App\GameEngine\Renown\PlayerRenownManager;
use App\GameEngine\Renown\PlayerReportManager;
use App\Repository\PlayerReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayerReportManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerReportRepository&MockObject $repository;
    private PlayerRenownManager&MockObject $renownManager;
    private PlayerReportManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(PlayerReportRepository::class);
        $this->renownManager = $this->createMock(PlayerRenownManager::class);
        $this->manager = new PlayerReportManager($this->entityManager, $this->repository, $this->renownManager);
    }

    private function makePlayer(int $id, string $name = 'Tester'): Player
    {
        $player = new Player();
        $player->setName($name);
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($player, $id);

        return $player;
    }

    public function testSubmitReportPersists(): void
    {
        $reporter = $this->makePlayer(1, 'Alice');
        $reported = $this->makePlayer(2, 'Bob');

        $this->repository->method('countRecentReports')->willReturn(0);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $report = $this->manager->submitReport($reporter, $reported, PlayerReportReason::Spam, 'Spam excessif');

        $this->assertNotNull($report);
        $this->assertSame($reporter, $report->getReporter());
        $this->assertSame($reported, $report->getReportedPlayer());
        $this->assertSame(PlayerReportReason::Spam, $report->getReason());
        $this->assertSame(PlayerReportStatus::Pending, $report->getStatus());
        $this->assertSame('Spam excessif', $report->getDescription());
    }

    public function testSubmitReportRejectsSelfReport(): void
    {
        $player = $this->makePlayer(1);

        $this->entityManager->expects($this->never())->method('persist');

        $report = $this->manager->submitReport($player, $player, PlayerReportReason::Spam, 'Test');

        $this->assertNull($report);
    }

    public function testSubmitReportTrimsWhitespace(): void
    {
        $reporter = $this->makePlayer(1);
        $reported = $this->makePlayer(2);
        $this->repository->method('countRecentReports')->willReturn(0);

        $report = $this->manager->submitReport($reporter, $reported, PlayerReportReason::Other, '   description   ');

        $this->assertNotNull($report);
        $this->assertSame('description', $report->getDescription());
    }

    public function testSubmitReportRejectsEmptyDescription(): void
    {
        $reporter = $this->makePlayer(1);
        $reported = $this->makePlayer(2);

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->submitReport($reporter, $reported, PlayerReportReason::Spam, '   ');
    }

    public function testSubmitReportRejectsTooLongDescription(): void
    {
        $reporter = $this->makePlayer(1);
        $reported = $this->makePlayer(2);

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->submitReport($reporter, $reported, PlayerReportReason::Spam, str_repeat('a', PlayerReportManager::MAX_DESCRIPTION_LENGTH + 1));
    }

    public function testSubmitReportEnforcesCooldown(): void
    {
        $reporter = $this->makePlayer(1);
        $reported = $this->makePlayer(2);

        $this->repository->method('countRecentReports')->willReturn(1);
        $this->entityManager->expects($this->never())->method('persist');

        $report = $this->manager->submitReport($reporter, $reported, PlayerReportReason::Spam, 'Encore lui');

        $this->assertNull($report);
    }

    public function testAcceptReportAppliesRenownMalus(): void
    {
        $reporter = $this->makePlayer(1);
        $reported = $this->makePlayer(2);
        $report = new PlayerReport();
        $report->setReporter($reporter)->setReportedPlayer($reported)->setReason(PlayerReportReason::Cheating)->setDescription('Cheat');
        // Simule un rapport persiste : id assigne par Doctrine apres flush
        $idRef = new \ReflectionProperty(PlayerReport::class, 'id');
        $idRef->setValue($report, 42);

        $moderator = new User();

        $this->renownManager->expects($this->once())
            ->method('addRenown')
            ->with($reported, -PlayerReportManager::RENOWN_MALUS, $this->stringContains('Rapport accepte'));
        $this->entityManager->expects($this->once())->method('flush');

        $this->manager->acceptReport($report, $moderator);

        $this->assertSame(PlayerReportStatus::Accepted, $report->getStatus());
        $this->assertSame(PlayerReportManager::RENOWN_MALUS, $report->getRenownMalusApplied());
        $this->assertSame($moderator, $report->getReviewedBy());
        $this->assertNotNull($report->getReviewedAt());
    }

    public function testAcceptReportIsIdempotent(): void
    {
        $report = new PlayerReport();
        $report->setReporter($this->makePlayer(1))
            ->setReportedPlayer($this->makePlayer(2))
            ->setReason(PlayerReportReason::Spam)
            ->setDescription('x')
            ->setStatus(PlayerReportStatus::Rejected);

        $this->renownManager->expects($this->never())->method('addRenown');
        $this->entityManager->expects($this->never())->method('flush');

        $this->manager->acceptReport($report, new User());

        $this->assertSame(PlayerReportStatus::Rejected, $report->getStatus());
    }

    public function testRejectReportMarksReviewed(): void
    {
        $report = new PlayerReport();
        $report->setReporter($this->makePlayer(1))
            ->setReportedPlayer($this->makePlayer(2))
            ->setReason(PlayerReportReason::Spam)
            ->setDescription('x');

        $moderator = new User();
        $this->renownManager->expects($this->never())->method('addRenown');
        $this->entityManager->expects($this->once())->method('flush');

        $this->manager->rejectReport($report, $moderator);

        $this->assertSame(PlayerReportStatus::Rejected, $report->getStatus());
        $this->assertSame($moderator, $report->getReviewedBy());
        $this->assertSame(0, $report->getRenownMalusApplied());
    }
}
