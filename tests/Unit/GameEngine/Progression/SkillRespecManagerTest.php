<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\SkillRespecManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SkillRespecManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private SkillRespecManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->manager = new SkillRespecManager($this->entityManager);
    }

    public function testGetRespecCostWithNoSkills(): void
    {
        $player = $this->createPlayerWithSkills(0, 1000, 0);

        $this->assertSame(0, $this->manager->getRespecCost($player));
    }

    public function testGetRespecCostFirstRespec(): void
    {
        $player = $this->createPlayerWithSkills(10, 1000, 0);

        // 50 * 10 * 1.25^0 = 500
        $this->assertSame(500, $this->manager->getRespecCost($player));
    }

    public function testGetRespecCostSecondRespec(): void
    {
        $player = $this->createPlayerWithSkills(10, 1000, 1);

        // 50 * 10 * 1.25^1 = 625
        $this->assertSame(625, $this->manager->getRespecCost($player));
    }

    public function testGetRespecCostThirdRespec(): void
    {
        $player = $this->createPlayerWithSkills(10, 1000, 2);

        // 50 * 10 * 1.25^2 = 781.25 → ceil = 782
        $this->assertSame(782, $this->manager->getRespecCost($player));
    }

    public function testCanRespecReturnsFalseWithNoSkills(): void
    {
        $player = $this->createPlayerWithSkills(0, 1000, 0);

        $this->assertFalse($this->manager->canRespec($player));
    }

    public function testCanRespecReturnsFalseWithInsufficientGils(): void
    {
        $player = $this->createPlayerWithSkills(10, 100, 0);

        // Cost = 500, player has 100
        $this->assertFalse($this->manager->canRespec($player));
    }

    public function testCanRespecReturnsTrueWithEnoughGils(): void
    {
        $player = $this->createPlayerWithSkills(5, 1000, 0);

        // Cost = 250, player has 1000
        $this->assertTrue($this->manager->canRespec($player));
    }

    public function testCanRespecReturnsFalseWhenInFight(): void
    {
        $player = $this->createPlayerWithSkills(5, 1000, 0);
        $fight = $this->createMock(\App\Entity\App\Fight::class);
        $player->method('getFight')->willReturn($fight);

        $this->assertFalse($this->manager->canRespec($player));
    }

    public function testRespecSuccess(): void
    {
        $domain = $this->createDomain(1, 'Pyromancie');

        $skill1 = $this->createSkill('skill-1', 20, 5, 2, 1, 3, 10);
        $skill1->addDomain($domain);
        $skill2 = $this->createSkill('skill-2', 15, 3, 1, 0, 0, 5);
        $skill2->addDomain($domain);

        $domainExp = new DomainExperience();
        $domainExp->setDomain($domain);
        $domainExp->setTotalExperience(100);
        $domainExp->setUsedExperience(35);
        $domainExp->setDamage(8);
        $domainExp->setHeal(3);
        $domainExp->setHit(3);
        $domainExp->setCritical(1);

        $player = $this->createRealPlayer([$skill1, $skill2], 1000, 0, [$domainExp], 100);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->manager->respec($player);

        $this->assertTrue($result);
        $this->assertSame(0, $player->getSkills()->count());
        $this->assertSame(0, $domainExp->getUsedExperience());
        $this->assertSame(0, $domainExp->getDamage());
        $this->assertSame(0, $domainExp->getHeal());
        $this->assertSame(0, $domainExp->getHit());
        $this->assertSame(0, $domainExp->getCritical());
        $this->assertSame(1, $player->getRespecCount());
        // Cost = 50 * 2 * 1.25^0 = 100, so 1000 - 100 = 900
        $this->assertSame(900, $player->getGils());
        // MaxLife reduced by total life bonus (10 + 5 = 15): 100 - 15 = 85
        $this->assertSame(85, $player->getMaxLife());
    }

    public function testRespecFailsWithInsufficientGils(): void
    {
        $skill = $this->createSkill('skill-1', 20, 0, 0, 0, 0, 0);

        $player = $this->createRealPlayer([$skill], 10, 0, [], 50);

        $result = $this->manager->respec($player);

        $this->assertFalse($result);
        $this->assertSame(1, $player->getSkills()->count());
        $this->assertSame(0, $player->getRespecCount());
    }

    public function testRespecCostScalesWithRespecCount(): void
    {
        $player = $this->createPlayerWithSkills(4, 10000, 3);

        // 50 * 4 * 1.25^3 = 200 * 1.953125 = 390.625 → ceil = 391
        $this->assertSame(391, $this->manager->getRespecCost($player));
    }

    /**
     * @param Skill[] $skills
     * @param DomainExperience[] $domainExps
     */
    private function createRealPlayer(array $skills, int $gils, int $respecCount, array $domainExps, int $maxLife): Player
    {
        $player = new Player();
        $player->setName('TestPlayer');
        $player->setMaxLife($maxLife);
        $player->setLife($maxLife);
        $player->setEnergy(100);
        $player->setMaxEnergy(100);
        $player->setClassType('warrior');
        $player->setCoordinates('5.5');
        $player->setLastCoordinates('5.5');
        $player->setGils($gils);
        $player->setRespecCount($respecCount);

        foreach ($skills as $skill) {
            $player->addSkill($skill);
        }

        foreach ($domainExps as $de) {
            $de->setPlayer($player);
            $player->addDomainExperience($de);
        }

        return $player;
    }

    private function createPlayerWithSkills(int $skillCount, int $gils, int $respecCount): Player&MockObject
    {
        $skills = new ArrayCollection();
        for ($i = 0; $i < $skillCount; ++$i) {
            $skills->add($this->createSkill("skill-$i", 10, 0, 0, 0, 0, 0));
        }

        $player = $this->createMock(Player::class);
        $player->method('getSkills')->willReturn($skills);
        $player->method('getGils')->willReturn($gils);
        $player->method('getRespecCount')->willReturn($respecCount);
        $player->method('getFight')->willReturn(null);
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection());

        return $player;
    }

    private function createSkill(string $slug, int $requiredPoints, int $damage, int $heal, int $hit, int $critical, int $life): Skill
    {
        $skill = new Skill();
        $skill->setSlug($slug);
        $skill->setTitle("Skill $slug");
        $skill->setDescription('Description');
        $skill->setRequiredPoints($requiredPoints);
        $skill->setDamage($damage);
        $skill->setHeal($heal);
        $skill->setHit($hit);
        $skill->setCritical($critical);
        $skill->setLife($life);

        return $skill;
    }

    private function createDomain(int $id, string $title): Domain
    {
        $domain = new Domain();
        $domain->setTitle($title);
        $domain->setRandomSeed(1);
        $domain->setGraphHeight(5);

        $reflection = new \ReflectionClass($domain);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($domain, $id);

        return $domain;
    }
}
