<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\Region;
use App\Entity\App\RegionControl;
use App\Entity\App\RegionUpgrade;
use App\GameEngine\Guild\RegionUpgradeManager;
use App\GameEngine\Guild\TownControlManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegionUpgradeManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private TownControlManager&MockObject $townControlManager;
    private RegionUpgradeManager $manager;
    private EntityRepository&MockObject $upgradeRepo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->townControlManager = $this->createMock(TownControlManager::class);
        $this->upgradeRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->with(RegionUpgrade::class)
            ->willReturn($this->upgradeRepo);

        $this->manager = new RegionUpgradeManager($this->em, $this->townControlManager);
    }

    public function testCanPurchaseReturnsTrueWhenAffordable(): void
    {
        $guild = $this->createGuild(1, 'Alpha', 1000);
        $region = $this->createRegion('plaines');
        $control = $this->createControl($region, $guild);

        $this->townControlManager->method('getActiveControl')
            ->with($region)
            ->willReturn($control);

        $this->upgradeRepo->method('findBy')
            ->willReturn([]);

        $this->assertTrue($this->manager->canPurchase($guild, $region, 'shop_discount'));
    }

    public function testCanPurchaseReturnsFalseWhenBroke(): void
    {
        $guild = $this->createGuild(1, 'Alpha', 100);
        $region = $this->createRegion('plaines');
        $control = $this->createControl($region, $guild);

        $this->townControlManager->method('getActiveControl')
            ->with($region)
            ->willReturn($control);

        $this->upgradeRepo->method('findBy')
            ->willReturn([]);

        $this->assertFalse($this->manager->canPurchase($guild, $region, 'shop_discount'));
    }

    public function testCanPurchaseReturnsFalseWhenMaxLevel(): void
    {
        $guild = $this->createGuild(1, 'Alpha', 99999);
        $region = $this->createRegion('plaines');
        $control = $this->createControl($region, $guild);

        $existingUpgrade = new RegionUpgrade();
        $existingUpgrade->setRegionControl($control);
        $existingUpgrade->setUpgradeSlug('monument');
        $existingUpgrade->setLevel(1); // max for monument

        $this->townControlManager->method('getActiveControl')
            ->with($region)
            ->willReturn($control);

        $this->upgradeRepo->method('findBy')
            ->willReturn([$existingUpgrade]);

        $this->assertFalse($this->manager->canPurchase($guild, $region, 'monument'));
    }

    public function testCanPurchaseReturnsFalseForWrongGuild(): void
    {
        $guildA = $this->createGuild(1, 'Alpha', 99999);
        $guildB = $this->createGuild(2, 'Beta', 99999);
        $region = $this->createRegion('plaines');
        $control = $this->createControl($region, $guildA);

        $this->townControlManager->method('getActiveControl')
            ->with($region)
            ->willReturn($control);

        $this->assertFalse($this->manager->canPurchase($guildB, $region, 'shop_discount'));
    }

    public function testPurchaseDeductsCostAndCreatesUpgrade(): void
    {
        $guild = $this->createGuild(1, 'Alpha', 2000);
        $region = $this->createRegion('plaines');
        $control = $this->createControl($region, $guild);

        $this->townControlManager->method('getActiveControl')
            ->with($region)
            ->willReturn($control);

        $this->upgradeRepo->method('findBy')
            ->willReturn([]);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $upgrade = $this->manager->purchase($guild, $region, 'shop_discount');

        $this->assertSame('shop_discount', $upgrade->getUpgradeSlug());
        $this->assertSame(1, $upgrade->getLevel());
        $this->assertSame(500, $upgrade->getCostGils());
        $this->assertSame(1500, $guild->getGilsTreasury()); // 2000 - 500
    }

    public function testPurchaseUpgradesExistingLevel(): void
    {
        $guild = $this->createGuild(1, 'Alpha', 5000);
        $region = $this->createRegion('plaines');
        $control = $this->createControl($region, $guild);

        $existingUpgrade = new RegionUpgrade();
        $existingUpgrade->setRegionControl($control);
        $existingUpgrade->setUpgradeSlug('shop_discount');
        $existingUpgrade->setLevel(1);
        $existingUpgrade->setCostGils(500);
        $existingUpgrade->setActivatedAt(new \DateTime());
        $existingUpgrade->setCreatedAt(new \DateTime());
        $existingUpgrade->setUpdatedAt(new \DateTime());

        $this->townControlManager->method('getActiveControl')
            ->with($region)
            ->willReturn($control);

        $this->upgradeRepo->method('findBy')
            ->willReturn([$existingUpgrade]);

        $this->em->expects($this->once())->method('flush');

        $upgrade = $this->manager->purchase($guild, $region, 'shop_discount');

        $this->assertSame(2, $upgrade->getLevel());
        $this->assertSame(2000, $upgrade->getCostGils()); // 500 + 1500
        $this->assertSame(3500, $guild->getGilsTreasury()); // 5000 - 1500
    }

    public function testPurchaseThrowsForInsufficientFunds(): void
    {
        $guild = $this->createGuild(1, 'Alpha', 100);
        $region = $this->createRegion('plaines');
        $control = $this->createControl($region, $guild);

        $this->townControlManager->method('getActiveControl')
            ->with($region)
            ->willReturn($control);

        $this->upgradeRepo->method('findBy')
            ->willReturn([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->purchase($guild, $region, 'shop_discount');
    }

    public function testGetUpgradeSummaryWithNoUpgrades(): void
    {
        $region = $this->createRegion('plaines');

        $this->townControlManager->method('getActiveControl')
            ->with($region)
            ->willReturn(null);

        $summary = $this->manager->getUpgradeSummary($region);

        $this->assertCount(4, $summary);
        foreach ($summary as $entry) {
            $this->assertSame(0, $entry['currentLevel']);
            $this->assertNotNull($entry['nextCost']);
        }
    }

    private function createGuild(int $id, string $name, int $treasury = 0): Guild
    {
        $guild = new Guild();
        $guild->setName($name);
        $guild->setTag(strtoupper(substr($name, 0, 3)));
        $guild->setGilsTreasury($treasury);

        $ref = new \ReflectionProperty(Guild::class, 'id');
        $ref->setValue($guild, $id);

        return $guild;
    }

    private function createRegion(string $slug): Region
    {
        $region = new Region();
        $region->setName(ucfirst($slug));
        $region->setSlug($slug);
        $region->setIsContestable(true);

        return $region;
    }

    private function createControl(Region $region, Guild $guild): RegionControl
    {
        $control = new RegionControl();
        $control->setRegion($region);
        $control->setGuild($guild);
        $control->setStartedAt(new \DateTime());
        $control->setCreatedAt(new \DateTime());
        $control->setUpdatedAt(new \DateTime());

        return $control;
    }
}
