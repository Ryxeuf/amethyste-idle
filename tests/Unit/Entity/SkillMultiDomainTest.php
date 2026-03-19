<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use PHPUnit\Framework\TestCase;

class SkillMultiDomainTest extends TestCase
{
    public function testSkillCanHaveMultipleDomains(): void
    {
        $skill = new Skill();
        $skill->setSlug('multi-domain-skill');
        $skill->setTitle('Compétence multi');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(10);

        $domain1 = new Domain();
        $domain1->setTitle('Pyromancie');
        $domain1->setRandomSeed(1);
        $domain1->setGraphHeight(5);

        $domain2 = new Domain();
        $domain2->setTitle('Soldat');
        $domain2->setRandomSeed(2);
        $domain2->setGraphHeight(5);

        $skill->addDomain($domain1);
        $skill->addDomain($domain2);

        $this->assertCount(2, $skill->getDomains());
        $this->assertSame($domain1, $skill->getDomain());
    }

    public function testGetDomainReturnsNullWhenNoDomains(): void
    {
        $skill = new Skill();
        $skill->setSlug('no-domain');
        $skill->setTitle('Sans domaine');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(0);

        $this->assertNull($skill->getDomain());
        $this->assertCount(0, $skill->getDomains());
    }

    public function testAddDomainPreventseDuplicates(): void
    {
        $skill = new Skill();
        $skill->setSlug('dedup-test');
        $skill->setTitle('Dedup');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(0);

        $domain = new Domain();
        $domain->setTitle('Pyromancie');
        $domain->setRandomSeed(1);
        $domain->setGraphHeight(5);

        $skill->addDomain($domain);
        $skill->addDomain($domain);

        $this->assertCount(1, $skill->getDomains());
    }

    public function testSetDomainRetrocompatibility(): void
    {
        $skill = new Skill();
        $skill->setSlug('compat-test');
        $skill->setTitle('Compat');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(0);

        $domain = new Domain();
        $domain->setTitle('Pyromancie');
        $domain->setRandomSeed(1);
        $domain->setGraphHeight(5);

        $skill->setDomain($domain);

        $this->assertSame($domain, $skill->getDomain());
        $this->assertCount(1, $skill->getDomains());
    }

    public function testRemoveDomain(): void
    {
        $skill = new Skill();
        $skill->setSlug('remove-test');
        $skill->setTitle('Remove');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(0);

        $domain1 = new Domain();
        $domain1->setTitle('Pyromancie');
        $domain1->setRandomSeed(1);
        $domain1->setGraphHeight(5);

        $domain2 = new Domain();
        $domain2->setTitle('Soldat');
        $domain2->setRandomSeed(2);
        $domain2->setGraphHeight(5);

        $skill->addDomain($domain1);
        $skill->addDomain($domain2);
        $this->assertCount(2, $skill->getDomains());

        $skill->removeDomain($domain1);
        $this->assertCount(1, $skill->getDomains());
    }

    public function testDomainInverseRelation(): void
    {
        $domain = new Domain();
        $domain->setTitle('Pyromancie');
        $domain->setRandomSeed(1);
        $domain->setGraphHeight(5);

        $skill = new Skill();
        $skill->setSlug('inverse-test');
        $skill->setTitle('Inverse');
        $skill->setDescription('Description');
        $skill->setRequiredPoints(0);

        $domain->addSkill($skill);

        $this->assertCount(1, $domain->getSkills());
        $this->assertTrue($skill->getDomains()->contains($domain));
    }
}
