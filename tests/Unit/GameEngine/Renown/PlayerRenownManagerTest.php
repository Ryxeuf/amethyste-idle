<?php

namespace App\Tests\Unit\GameEngine\Renown;

use App\Entity\App\Player;
use App\Enum\PlayerRenownTier;
use App\GameEngine\Renown\PlayerRenownManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayerRenownManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerRenownManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->manager = new PlayerRenownManager($this->entityManager);
    }

    public function testAddRenownIncreasesScore(): void
    {
        $player = new Player();

        $this->entityManager->expects($this->once())->method('flush');

        $newScore = $this->manager->addRenown($player, 100, 'test');

        $this->assertSame(100, $newScore);
        $this->assertSame(100, $player->getRenownScore());
    }

    public function testAddRenownZeroSkipsFlush(): void
    {
        $player = new Player();
        $player->setRenownScore(50);

        $this->entityManager->expects($this->never())->method('flush');

        $newScore = $this->manager->addRenown($player, 0, 'test');

        $this->assertSame(50, $newScore);
    }

    public function testAddRenownNegativeCannotGoBelowZero(): void
    {
        $player = new Player();
        $player->setRenownScore(50);

        $this->entityManager->expects($this->once())->method('flush');

        $newScore = $this->manager->addRenown($player, -200, 'malus');

        $this->assertSame(0, $newScore);
    }

    public function testGetTierReflectsScore(): void
    {
        $player = new Player();
        $player->setRenownScore(1500);

        $this->assertSame(PlayerRenownTier::Respecte, $this->manager->getTier($player));
    }

    public function testGetPointsToNextTier(): void
    {
        $player = new Player();
        $player->setRenownScore(250);

        $this->assertSame(750, $this->manager->getPointsToNextTier($player));
    }

    public function testGetPointsToNextTierAtMaxReturnsNull(): void
    {
        $player = new Player();
        $player->setRenownScore(25000);

        $this->assertNull($this->manager->getPointsToNextTier($player));
    }

    public function testQuestRewardAmountIsLowerForDaily(): void
    {
        $this->assertSame(5, $this->manager->getQuestRewardAmount(true));
        $this->assertSame(25, $this->manager->getQuestRewardAmount(false));
    }

    public function testAchievementRewardAmountByCategory(): void
    {
        $this->assertSame(20, $this->manager->getAchievementRewardAmount('combat'));
        $this->assertSame(20, $this->manager->getAchievementRewardAmount('quest'));
        $this->assertSame(15, $this->manager->getAchievementRewardAmount('craft'));
        $this->assertSame(10, $this->manager->getAchievementRewardAmount('unknown-category'));
    }
}
