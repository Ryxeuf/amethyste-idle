<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Enum\CraftSpecialization;
use App\GameEngine\Crafting\CraftSpecializationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CraftSpecializationServiceTest extends TestCase
{
    private CraftSpecializationService $service;

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->service = new CraftSpecializationService($em);
    }

    public function testGetAvailableSpecializationsReturnsAllCases(): void
    {
        $this->assertCount(4, $this->service->getAvailableSpecializations());
    }

    public function testCanChooseRefusesBelowThreshold(): void
    {
        $player = $this->buildPlayerWithDomainXp('Forgeron', 100);
        $check = $this->service->canChoose($player);
        $this->assertFalse($check['ok']);
        $this->assertStringContainsString('500', $check['reason']);
    }

    public function testCanChooseAcceptsAtThreshold(): void
    {
        $player = $this->buildPlayerWithDomainXp('Forgeron', CraftSpecializationService::REQUIRED_DOMAIN_XP);
        $check = $this->service->canChoose($player);
        $this->assertTrue($check['ok']);
    }

    public function testCanChooseRefusesIfAlreadySpecialized(): void
    {
        $player = $this->buildPlayerWithDomainXp('Forgeron', 1000);
        $player->setCraftSpecialization(CraftSpecialization::Forgeron);

        $check = $this->service->canChoose($player);
        $this->assertFalse($check['ok']);
        $this->assertStringContainsString('irreversible', $check['reason']);
    }

    public function testChooseFailsBelowThreshold(): void
    {
        $player = $this->buildPlayerWithDomainXp('Forgeron', 100);
        $result = $this->service->choose($player, CraftSpecialization::Forgeron);
        $this->assertFalse($result['success']);
        $this->assertNull($player->getCraftSpecialization());
    }

    public function testChooseSucceedsAndAssignsSpecialization(): void
    {
        $player = $this->buildPlayerWithDomainXp('Alchimiste', 600);
        $result = $this->service->choose($player, CraftSpecialization::Alchimiste);
        $this->assertTrue($result['success']);
        $this->assertSame(CraftSpecialization::Alchimiste, $player->getCraftSpecialization());
    }

    public function testGetQualityBonusReturnsBonusOnlyForMatchingCraft(): void
    {
        $player = new Player();
        $player->setCraftSpecialization(CraftSpecialization::Joaillier);

        $this->assertSame(
            CraftSpecializationService::QUALITY_BONUS_CHANCE,
            $this->service->getQualityBonusFor($player, 'joaillier')
        );
        $this->assertSame(0, $this->service->getQualityBonusFor($player, 'forgeron'));
    }

    public function testGetQualityBonusIsZeroWithoutSpecialization(): void
    {
        $player = new Player();
        $this->assertSame(0, $this->service->getQualityBonusFor($player, 'forgeron'));
    }

    public function testNonCraftDomainXpDoesNotUnlockSpecialization(): void
    {
        // XP dans un domaine qui n'est pas un metier d'artisanat (ex: Guerrier) ne doit pas debloquer.
        $player = $this->buildPlayerWithDomainXp('Guerrier', 1000);
        $check = $this->service->canChoose($player);
        $this->assertFalse($check['ok']);
    }

    private function buildPlayerWithDomainXp(string $domainTitle, int $xp): Player
    {
        $player = new Player();
        $reflection = new \ReflectionClass($player);
        $prop = $reflection->getProperty('domainExperiences');
        $prop->setAccessible(true);

        $domain = new Domain();
        $domain->setTitle($domainTitle);

        $domainExperience = new DomainExperience();
        $domainExperience->setDomain($domain);
        $domainExperience->setTotalExperience($xp);

        $prop->setValue($player, new ArrayCollection([$domainExperience]));

        return $player;
    }
}
