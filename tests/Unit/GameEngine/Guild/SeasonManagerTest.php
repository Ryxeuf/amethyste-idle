<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\InfluenceSeason;
use App\Enum\SeasonStatus;
use App\GameEngine\Guild\SeasonManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SeasonManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $seasonRepo;
    private SeasonManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->seasonRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->with(InfluenceSeason::class)
            ->willReturn($this->seasonRepo);

        $this->manager = new SeasonManager($this->em);
    }

    public function testGetCurrentSeasonReturnsActiveOne(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Active);

        $this->seasonRepo->method('findOneBy')
            ->with(['status' => SeasonStatus::Active])
            ->willReturn($season);

        $result = $this->manager->getCurrentSeason();

        $this->assertSame($season, $result);
    }

    public function testGetCurrentSeasonReturnsNullWhenNoActive(): void
    {
        $this->seasonRepo->method('findOneBy')
            ->with(['status' => SeasonStatus::Active])
            ->willReturn(null);

        $this->assertNull($this->manager->getCurrentSeason());
    }

    public function testGetOrCreateNextSeasonReturnsExistingScheduled(): void
    {
        $scheduled = $this->createSeason(2, SeasonStatus::Scheduled);

        $this->seasonRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match (true) {
                isset($criteria['status']) && $criteria['status'] === SeasonStatus::Scheduled => $scheduled,
                default => null,
            });

        $result = $this->manager->getOrCreateNextSeason();

        $this->assertSame($scheduled, $result);
    }

    public function testGetOrCreateNextSeasonCreatesNewAfterLast(): void
    {
        $last = $this->createSeason(1, SeasonStatus::Completed);

        $this->seasonRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match (true) {
                isset($criteria['status']) && $criteria['status'] === SeasonStatus::Scheduled => null,
                default => $last,
            });

        $this->em->expects($this->once())->method('persist')
            ->with($this->callback(function (InfluenceSeason $season): bool {
                return $season->getSeasonNumber() === 2
                    && $season->getSlug() === 'saison-2'
                    && $season->isScheduled();
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->manager->getOrCreateNextSeason();

        $this->assertSame(2, $result->getSeasonNumber());
        $this->assertSame('saison-2', $result->getSlug());
    }

    public function testGetOrCreateNextSeasonCreatesFirstEver(): void
    {
        $this->seasonRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->once())->method('persist')
            ->with($this->callback(function (InfluenceSeason $season): bool {
                return $season->getSeasonNumber() === 1
                    && $season->getSlug() === 'saison-1'
                    && $season->isScheduled();
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->manager->getOrCreateNextSeason();

        $this->assertSame(1, $result->getSeasonNumber());
    }

    public function testStartSeasonSuccess(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Scheduled);

        // No active season exists
        $this->seasonRepo->method('findOneBy')
            ->with(['status' => SeasonStatus::Active])
            ->willReturn(null);

        $this->em->expects($this->once())->method('flush');

        $this->manager->startSeason($season);

        $this->assertTrue($season->isActive());
    }

    public function testStartSeasonFailsIfNotScheduled(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Completed);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Impossible de démarrer/');

        $this->manager->startSeason($season);
    }

    public function testStartSeasonFailsIfAnotherIsActive(): void
    {
        $season = $this->createSeason(2, SeasonStatus::Scheduled);
        $activeSeason = $this->createSeason(1, SeasonStatus::Active);

        $this->seasonRepo->method('findOneBy')
            ->with(['status' => SeasonStatus::Active])
            ->willReturn($activeSeason);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/déjà active/');

        $this->manager->startSeason($season);
    }

    public function testEndSeasonSuccess(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Active);

        $this->em->expects($this->once())->method('flush');

        $this->manager->endSeason($season);

        $this->assertTrue($season->isCompleted());
    }

    public function testEndSeasonFailsIfNotActive(): void
    {
        $season = $this->createSeason(1, SeasonStatus::Scheduled);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Impossible de terminer/');

        $this->manager->endSeason($season);
    }

    private function createSeason(int $number, SeasonStatus $status): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setName(sprintf('Saison %d', $number));
        $season->setSlug(sprintf('saison-%d', $number));
        $season->setSeasonNumber($number);
        $season->setStartsAt(new \DateTime('-7 days'));
        $season->setEndsAt(new \DateTime('+21 days'));
        $season->setStatus($status);
        $season->setCreatedAt(new \DateTime());
        $season->setUpdatedAt(new \DateTime());

        return $season;
    }
}
