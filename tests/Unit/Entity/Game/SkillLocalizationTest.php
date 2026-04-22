<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Skill;
use PHPUnit\Framework\TestCase;

class SkillLocalizationTest extends TestCase
{
    public function testGetLocalizedTitleFallsBackToBaseTitleWhenNoTranslations(): void
    {
        $skill = new Skill();
        $skill->setTitle('Frappe puissante');

        $this->assertSame('Frappe puissante', $skill->getLocalizedTitle('en'));
        $this->assertSame('Frappe puissante', $skill->getLocalizedTitle('fr'));
        $this->assertSame('Frappe puissante', $skill->getLocalizedTitle(null));
        $this->assertSame('Frappe puissante', $skill->getLocalizedTitle(''));
    }

    public function testGetLocalizedTitleReturnsMatchingTranslation(): void
    {
        $skill = new Skill();
        $skill->setTitle('Frappe puissante');
        $skill->setTitleTranslations(['en' => 'Power Strike', 'de' => 'Kraftschlag']);

        $this->assertSame('Power Strike', $skill->getLocalizedTitle('en'));
        $this->assertSame('Kraftschlag', $skill->getLocalizedTitle('de'));
    }

    public function testGetLocalizedTitleFallsBackWhenLocaleMissing(): void
    {
        $skill = new Skill();
        $skill->setTitle('Voie du mineur');
        $skill->setTitleTranslations(['en' => 'Miner Path']);

        $this->assertSame('Voie du mineur', $skill->getLocalizedTitle('es'));
        $this->assertSame('Voie du mineur', $skill->getLocalizedTitle('ja'));
    }

    public function testSetTitleTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $skill = new Skill();
        $skill->setTitle('Concentration');
        $skill->setTitleTranslations([
            'en' => 'Focus',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'ja' => 42,
        ]);

        $this->assertSame(['en' => 'Focus'], $skill->getTitleTranslations());
        $this->assertSame('Focus', $skill->getLocalizedTitle('en'));
        $this->assertSame('Concentration', $skill->getLocalizedTitle('de'));
        $this->assertSame('Concentration', $skill->getLocalizedTitle('es'));
        $this->assertSame('Concentration', $skill->getLocalizedTitle('ja'));
    }

    public function testSetTitleTranslationsWithNullResetsStorage(): void
    {
        $skill = new Skill();
        $skill->setTitle('Riposte');
        $skill->setTitleTranslations(['en' => 'Riposte']);
        $skill->setTitleTranslations(null);

        $this->assertSame([], $skill->getTitleTranslations());
        $this->assertSame('Riposte', $skill->getLocalizedTitle('en'));
    }

    public function testSetTitleTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $skill = new Skill();
        $skill->setTitle('Soin mineur');
        $skill->setTitleTranslations(['en' => 'Minor Heal']);
        $skill->setTitleTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $skill->getTitleTranslations());
        $this->assertSame('Soin mineur', $skill->getLocalizedTitle('en'));
    }

    public function testGetTitleTranslationsDefaultsToEmptyArray(): void
    {
        $skill = new Skill();
        $skill->setTitle('Rage berserker');

        $this->assertSame([], $skill->getTitleTranslations());
    }
}
