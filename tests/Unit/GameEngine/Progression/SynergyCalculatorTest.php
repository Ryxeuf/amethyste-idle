<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\DomainSynergy;
use App\GameEngine\Progression\SynergyCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SynergyCalculatorTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private SynergyCalculator $calculator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->calculator = new SynergyCalculator($this->entityManager);
    }

    public function testNoSynergiesReturnsEmpty(): void
    {
        $this->mockRepository([]);
        $player = $this->createPlayer([]);

        $result = $this->calculator->getActiveSynergies($player);

        $this->assertSame([], $result);
    }

    public function testSynergyActiveWhenBothDomainsAboveThreshold(): void
    {
        $domainA = $this->createDomain(1);
        $domainB = $this->createDomain(2);
        $synergy = $this->createSynergy($domainA, $domainB, 'damage', 10, 50);

        $this->mockRepository([$synergy]);
        $player = $this->createPlayer([
            ['domain' => $domainA, 'xp' => 60],
            ['domain' => $domainB, 'xp' => 55],
        ]);

        $result = $this->calculator->getActiveSynergies($player);

        $this->assertCount(1, $result);
        $this->assertSame($synergy, $result[0]['synergy']);
    }

    public function testSynergyInactiveWhenOneDomainBelowThreshold(): void
    {
        $domainA = $this->createDomain(1);
        $domainB = $this->createDomain(2);
        $synergy = $this->createSynergy($domainA, $domainB, 'damage', 10, 50);

        $this->mockRepository([$synergy]);
        $player = $this->createPlayer([
            ['domain' => $domainA, 'xp' => 60],
            ['domain' => $domainB, 'xp' => 30],
        ]);

        $result = $this->calculator->getActiveSynergies($player);

        $this->assertSame([], $result);
    }

    public function testSynergyInactiveWhenNoDomainXp(): void
    {
        $domainA = $this->createDomain(1);
        $domainB = $this->createDomain(2);
        $synergy = $this->createSynergy($domainA, $domainB, 'damage', 10, 50);

        $this->mockRepository([$synergy]);
        $player = $this->createPlayer([]);

        $result = $this->calculator->getActiveSynergies($player);

        $this->assertSame([], $result);
    }

    public function testSynergyBonusesAccumulate(): void
    {
        $domainA = $this->createDomain(1);
        $domainB = $this->createDomain(2);
        $domainC = $this->createDomain(3);

        $synergy1 = $this->createSynergy($domainA, $domainB, 'damage', 10, 50);
        $synergy2 = $this->createSynergy($domainA, $domainC, 'damage', 6, 50);
        $synergy3 = $this->createSynergy($domainB, $domainC, 'heal', 15, 50);

        $this->mockRepository([$synergy1, $synergy2, $synergy3]);
        $player = $this->createPlayer([
            ['domain' => $domainA, 'xp' => 100],
            ['domain' => $domainB, 'xp' => 80],
            ['domain' => $domainC, 'xp' => 60],
        ]);

        $bonuses = $this->calculator->getSynergyBonuses($player);

        $this->assertSame(16, $bonuses['damage']);
        $this->assertSame(15, $bonuses['heal']);
        $this->assertSame(0, $bonuses['hit']);
        $this->assertSame(0, $bonuses['critical']);
        $this->assertSame(0, $bonuses['life']);
    }

    public function testGetAllSynergiesWithStatusReturnsCorrectFlags(): void
    {
        $domainA = $this->createDomain(1);
        $domainB = $this->createDomain(2);
        $domainC = $this->createDomain(3);

        $synergy1 = $this->createSynergy($domainA, $domainB, 'damage', 10, 50);
        $synergy2 = $this->createSynergy($domainA, $domainC, 'heal', 12, 50);

        $this->mockRepository([$synergy1, $synergy2]);
        $player = $this->createPlayer([
            ['domain' => $domainA, 'xp' => 100],
            ['domain' => $domainB, 'xp' => 80],
            ['domain' => $domainC, 'xp' => 20],
        ]);

        $result = $this->calculator->getAllSynergiesWithStatus($player);

        $this->assertCount(2, $result);
        $this->assertTrue($result[0]['active']);
        $this->assertFalse($result[1]['active']);
    }

    public function testExactThresholdActivatesSynergy(): void
    {
        $domainA = $this->createDomain(1);
        $domainB = $this->createDomain(2);
        $synergy = $this->createSynergy($domainA, $domainB, 'critical', 8, 50);

        $this->mockRepository([$synergy]);
        $player = $this->createPlayer([
            ['domain' => $domainA, 'xp' => 50],
            ['domain' => $domainB, 'xp' => 50],
        ]);

        $result = $this->calculator->getActiveSynergies($player);

        $this->assertCount(1, $result);
    }

    /**
     * @param DomainSynergy[] $synergies
     */
    private function mockRepository(array $synergies): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn($synergies);
        $this->entityManager->method('getRepository')
            ->with(DomainSynergy::class)
            ->willReturn($repository);
    }

    private function createDomain(int $id): Domain
    {
        $domain = new Domain();
        $ref = new \ReflectionProperty(Domain::class, 'id');
        $ref->setValue($domain, $id);
        $domain->setTitle('Domain ' . $id);

        return $domain;
    }

    private function createSynergy(Domain $a, Domain $b, string $bonusType, int $bonusValue, int $threshold): DomainSynergy
    {
        $synergy = new DomainSynergy();
        $synergy->setDomainA($a);
        $synergy->setDomainB($b);
        $synergy->setName('Test Synergy');
        $synergy->setDescription('Test description');
        $synergy->setBonusType($bonusType);
        $synergy->setBonusValue($bonusValue);
        $synergy->setActivationThreshold($threshold);

        return $synergy;
    }

    /**
     * @param array<array{domain: Domain, xp: int}> $domainXps
     */
    private function createPlayer(array $domainXps): Player
    {
        $player = $this->createMock(Player::class);
        $experiences = [];

        foreach ($domainXps as $entry) {
            $de = new DomainExperience();
            $de->setDomain($entry['domain']);
            $de->setTotalExperience($entry['xp']);
            $experiences[] = $de;
        }

        $player->method('getDomainExperiences')->willReturn(new ArrayCollection($experiences));

        return $player;
    }
}
