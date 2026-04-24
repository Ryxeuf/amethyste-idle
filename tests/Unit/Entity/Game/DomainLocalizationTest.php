<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Domain;
use PHPUnit\Framework\TestCase;

class DomainLocalizationTest extends TestCase
{
    public function testGetLocalizedTitleFallsBackToBaseTitleWhenNoTranslations(): void
    {
        $domain = new Domain();
        $domain->setTitle('Guerrier');

        $this->assertSame('Guerrier', $domain->getLocalizedTitle('en'));
        $this->assertSame('Guerrier', $domain->getLocalizedTitle('fr'));
        $this->assertSame('Guerrier', $domain->getLocalizedTitle(null));
        $this->assertSame('Guerrier', $domain->getLocalizedTitle(''));
    }

    public function testGetLocalizedTitleReturnsMatchingTranslation(): void
    {
        $domain = new Domain();
        $domain->setTitle('Guerrier');
        $domain->setTitleTranslations(['en' => 'Warrior', 'de' => 'Krieger']);

        $this->assertSame('Warrior', $domain->getLocalizedTitle('en'));
        $this->assertSame('Krieger', $domain->getLocalizedTitle('de'));
    }

    public function testGetLocalizedTitleFallsBackWhenLocaleMissing(): void
    {
        $domain = new Domain();
        $domain->setTitle('Mage');
        $domain->setTitleTranslations(['en' => 'Mage']);

        $this->assertSame('Mage', $domain->getLocalizedTitle('es'));
        $this->assertSame('Mage', $domain->getLocalizedTitle('ja'));
    }

    public function testSetTitleTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $domain = new Domain();
        $domain->setTitle('Artisan');
        $domain->setTitleTranslations([
            'en' => 'Craftsman',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Craftsman'], $domain->getTitleTranslations());
        $this->assertSame('Craftsman', $domain->getLocalizedTitle('en'));
        $this->assertSame('Artisan', $domain->getLocalizedTitle('de'));
        $this->assertSame('Artisan', $domain->getLocalizedTitle('es'));
        $this->assertSame('Artisan', $domain->getLocalizedTitle('it'));
    }

    public function testSetTitleTranslationsWithNullResetsStorage(): void
    {
        $domain = new Domain();
        $domain->setTitle('Soigneur');
        $domain->setTitleTranslations(['en' => 'Healer']);
        $domain->setTitleTranslations(null);

        $this->assertSame([], $domain->getTitleTranslations());
        $this->assertSame('Soigneur', $domain->getLocalizedTitle('en'));
    }

    public function testSetTitleTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $domain = new Domain();
        $domain->setTitle('Archer');
        $domain->setTitleTranslations(['en' => 'Archer']);
        $domain->setTitleTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $domain->getTitleTranslations());
        $this->assertSame('Archer', $domain->getLocalizedTitle('en'));
    }

    public function testGetTitleTranslationsDefaultsToEmptyArray(): void
    {
        $domain = new Domain();
        $domain->setTitle('Voleur');

        $this->assertSame([], $domain->getTitleTranslations());
    }
}
