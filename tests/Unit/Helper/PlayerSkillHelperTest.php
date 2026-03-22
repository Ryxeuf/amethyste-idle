<?php

namespace App\Tests\Unit\Helper;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayerSkillHelperTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private PlayerDomainHelper&MockObject $playerDomainHelper;
    private PlayerSkillHelper $helper;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->playerDomainHelper = $this->createMock(PlayerDomainHelper::class);
        $this->helper = new PlayerSkillHelper($this->playerHelper, $this->playerDomainHelper);
    }

    public function testGetTotalUsedPointsSumsAllDomains(): void
    {
        $player = $this->createPlayerWithUsedExperience([100, 150, 50]);

        $this->assertSame(300, $this->helper->getTotalUsedPoints($player));
    }

    public function testGetTotalUsedPointsReturnsZeroWithNoDomains(): void
    {
        $player = $this->createPlayerWithUsedExperience([]);

        $this->assertSame(0, $this->helper->getTotalUsedPoints($player));
    }

    public function testCanAcquireSkillUnderLimit(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 50, [$domain]);

        // 400 used + 50 cost = 450 <= 500 max → OK
        $player = $this->createPlayerWithUsedExperience([200, 200]);
        $player->method('getSkills')->willReturn(new ArrayCollection());
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(100);

        $this->assertTrue($this->helper->canAcquireSkill($skill));
    }

    public function testCanAcquireSkillAtExactLimit(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 50, [$domain]);

        // 450 used + 50 cost = 500 = 500 max → OK
        $player = $this->createPlayerWithUsedExperience([250, 200]);
        $player->method('getSkills')->willReturn(new ArrayCollection());
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(100);

        $this->assertTrue($this->helper->canAcquireSkill($skill));
    }

    public function testCanAcquireSkillOverLimitReturnsFalse(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 50, [$domain]);

        // 460 used + 50 cost = 510 > 500 max → blocked
        $player = $this->createPlayerWithUsedExperience([260, 200]);
        $player->method('getSkills')->willReturn(new ArrayCollection());
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(100);

        $this->assertFalse($this->helper->canAcquireSkill($skill));
    }

    public function testCanAcquireSkillAlreadyAcquiredReturnsFalse(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 10, [$domain]);

        $player = $this->createPlayerWithUsedExperience([0]);
        $player->method('getSkills')->willReturn(new ArrayCollection([$skill]));
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->assertFalse($this->helper->canAcquireSkill($skill));
    }

    public function testMaxTotalSkillPointsConstant(): void
    {
        $this->assertSame(500, PlayerSkillHelper::MAX_TOTAL_SKILL_POINTS);
    }

    /**
     * @param int[] $usedExperiences
     */
    private function createPlayerWithUsedExperience(array $usedExperiences): Player&MockObject
    {
        $domainExps = new ArrayCollection();
        foreach ($usedExperiences as $i => $used) {
            $de = new DomainExperience();
            $de->setTotalExperience(1000);
            $de->setUsedExperience($used);
            $domain = $this->createDomain($i + 1);
            $de->setDomain($domain);
            $domainExps->add($de);
        }

        $player = $this->createMock(Player::class);
        $player->method('getDomainExperiences')->willReturn($domainExps);

        return $player;
    }

    /**
     * @param Domain[] $domains
     */
    private function createSkill(string $slug, int $requiredPoints, array $domains): Skill
    {
        $skill = new Skill();
        $skill->setSlug($slug);
        $skill->setTitle("Skill $slug");
        $skill->setDescription('Description');
        $skill->setRequiredPoints($requiredPoints);
        $skill->setDamage(0);
        $skill->setHeal(0);
        $skill->setHit(0);
        $skill->setCritical(0);
        $skill->setLife(0);

        foreach ($domains as $domain) {
            $skill->addDomain($domain);
        }

        return $skill;
    }

    private function createDomain(int $id): Domain
    {
        $domain = new Domain();
        $domain->setTitle("Domain $id");
        $domain->setRandomSeed(1);
        $domain->setGraphHeight(5);

        $reflection = new \ReflectionClass($domain);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($domain, $id);

        return $domain;
    }
}
