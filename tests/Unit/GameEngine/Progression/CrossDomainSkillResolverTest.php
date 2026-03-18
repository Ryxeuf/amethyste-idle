<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\CrossDomainSkillResolver;
use App\Helper\PlayerDomainHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CrossDomainSkillResolverTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerDomainHelper&MockObject $playerDomainHelper;
    private CrossDomainSkillResolver $resolver;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->playerDomainHelper = $this->createMock(PlayerDomainHelper::class);
        $this->resolver = new CrossDomainSkillResolver(
            $this->entityManager,
            $this->playerDomainHelper,
        );
    }

    public function testCheckAutoUnlockReturnsTrueWhenOneDomainHasEnoughXp(): void
    {
        $player = $this->createMock(Player::class);
        $domain1 = $this->createDomain(1, 'Pyromancie');
        $domain2 = $this->createDomain(2, 'Soldat');

        $skill = new Skill();
        $skill->setSlug('cross-skill');
        $skill->setTitle('Compétence partagée');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(20);
        $skill->addDomain($domain1);
        $skill->addDomain($domain2);

        // Domain 1 has 10 XP (not enough), Domain 2 has 30 XP (enough)
        $this->playerDomainHelper->method('getAvailableDomainExperience')
            ->willReturnCallback(function (Domain $domain) {
                return $domain->getId() === 1 ? 10 : 30;
            });

        $this->assertTrue($this->resolver->checkAutoUnlock($player, $skill));
    }

    public function testCheckAutoUnlockReturnsFalseWhenNoDomainHasEnoughXp(): void
    {
        $player = $this->createMock(Player::class);
        $domain1 = $this->createDomain(1, 'Pyromancie');
        $domain2 = $this->createDomain(2, 'Soldat');

        $skill = new Skill();
        $skill->setSlug('cross-skill-2');
        $skill->setTitle('Compétence partagée');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(50);
        $skill->addDomain($domain1);
        $skill->addDomain($domain2);

        $this->playerDomainHelper->method('getAvailableDomainExperience')
            ->willReturn(10);

        $this->assertFalse($this->resolver->checkAutoUnlock($player, $skill));
    }

    public function testGrantXpToAllDomainsCreatesExperienceForAllDomains(): void
    {
        $player = $this->createMock(Player::class);
        $domain1 = $this->createDomain(1, 'Pyromancie');
        $domain2 = $this->createDomain(2, 'Soldat');

        $domainExp1 = new DomainExperience();
        $domainExp1->setPlayer($player);
        $domainExp1->setDomain($domain1);
        $domainExp1->setTotalExperience(100);

        $domainExp2 = new DomainExperience();
        $domainExp2->setPlayer($player);
        $domainExp2->setDomain($domain2);
        $domainExp2->setTotalExperience(100);

        $skill = new Skill();
        $skill->setSlug('cross-skill-3');
        $skill->setTitle('Compétence partagée');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(20);
        $skill->setDamage(5);
        $skill->setHit(2);
        $skill->setCritical(1);
        $skill->setHeal(3);
        $skill->addDomain($domain1);
        $skill->addDomain($domain2);

        $this->playerDomainHelper->method('getDomainExperience')
            ->willReturnCallback(function (Domain $domain) use ($domainExp1, $domainExp2) {
                return $domain->getId() === 1 ? $domainExp1 : $domainExp2;
            });

        $this->entityManager->expects($this->exactly(2))->method('persist');

        $result = $this->resolver->grantXpToAllDomains($player, $skill);

        $this->assertCount(2, $result);

        // Both domain experiences should have 20 used XP
        $this->assertSame(20, $domainExp1->getUsedExperience());
        $this->assertSame(20, $domainExp2->getUsedExperience());

        // Stats applied to both
        $this->assertSame(5, $domainExp1->getDamage());
        $this->assertSame(5, $domainExp2->getDamage());
        $this->assertSame(2, $domainExp1->getHit());
        $this->assertSame(2, $domainExp2->getHit());
        $this->assertSame(1, $domainExp1->getCritical());
        $this->assertSame(1, $domainExp2->getCritical());
        $this->assertSame(3, $domainExp1->getHeal());
        $this->assertSame(3, $domainExp2->getHeal());
    }

    public function testGrantXpCreatesNewDomainExperienceWhenMissing(): void
    {
        $player = $this->createMock(Player::class);
        $domain = $this->createDomain(1, 'Pyromancie');

        $skill = new Skill();
        $skill->setSlug('new-domain-skill');
        $skill->setTitle('Compétence');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(10);
        $skill->addDomain($domain);

        // No existing domain experience
        $this->playerDomainHelper->method('getDomainExperience')
            ->willReturn(null);

        $player->expects($this->once())->method('addDomainExperience');
        $this->entityManager->expects($this->once())->method('persist');

        $result = $this->resolver->grantXpToAllDomains($player, $skill);

        $this->assertCount(1, $result);
        $this->assertSame(10, $result[0]->getUsedExperience());
    }

    public function testSkillWithSingleDomainWorksCorrectly(): void
    {
        $player = $this->createMock(Player::class);
        $domain = $this->createDomain(1, 'Pyromancie');

        $skill = new Skill();
        $skill->setSlug('single-domain');
        $skill->setTitle('Compétence mono');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(15);
        $skill->addDomain($domain);

        $this->playerDomainHelper->method('getAvailableDomainExperience')
            ->willReturn(20);

        $this->assertTrue($this->resolver->checkAutoUnlock($player, $skill));
    }

    private function createDomain(int $id, string $title): Domain
    {
        $domain = new Domain();
        $domain->setTitle($title);
        $domain->setRandomSeed(1);
        $domain->setGraphHeight(5);

        // Use reflection to set ID
        $reflection = new \ReflectionClass($domain);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($domain, $id);

        return $domain;
    }
}
