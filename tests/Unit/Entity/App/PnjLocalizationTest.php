<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Pnj;
use PHPUnit\Framework\TestCase;

class PnjLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $pnj = new Pnj();
        $pnj->setName('Forgeron Arden');

        $this->assertSame('Forgeron Arden', $pnj->getLocalizedName('en'));
        $this->assertSame('Forgeron Arden', $pnj->getLocalizedName('fr'));
        $this->assertSame('Forgeron Arden', $pnj->getLocalizedName(null));
        $this->assertSame('Forgeron Arden', $pnj->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $pnj = new Pnj();
        $pnj->setName('Forgeron Arden');
        $pnj->setNameTranslations(['en' => 'Arden the Blacksmith', 'de' => 'Schmied Arden']);

        $this->assertSame('Arden the Blacksmith', $pnj->getLocalizedName('en'));
        $this->assertSame('Schmied Arden', $pnj->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $pnj = new Pnj();
        $pnj->setName('Herboriste Mira');
        $pnj->setNameTranslations(['en' => 'Mira the Herbalist']);

        $this->assertSame('Herboriste Mira', $pnj->getLocalizedName('es'));
        $this->assertSame('Herboriste Mira', $pnj->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesInvalidKeysAndNonStrings(): void
    {
        $pnj = new Pnj();
        $pnj->setName('Garde du roi');
        $pnj->setNameTranslations([
            'en' => 'Royal Guard',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'ja' => 42,
        ]);

        $this->assertSame(['en' => 'Royal Guard'], $pnj->getNameTranslations());
        $this->assertSame('Royal Guard', $pnj->getLocalizedName('en'));
        $this->assertSame('Garde du roi', $pnj->getLocalizedName('de'));
        $this->assertSame('Garde du roi', $pnj->getLocalizedName('es'));
        $this->assertSame('Garde du roi', $pnj->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $pnj = new Pnj();
        $pnj->setName('Marchand ambulant');
        $pnj->setNameTranslations(['en' => 'Traveling Merchant']);
        $pnj->setNameTranslations(null);

        $this->assertSame([], $pnj->getNameTranslations());
        $this->assertSame('Marchand ambulant', $pnj->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $pnj = new Pnj();
        $pnj->setName('Alchimiste Orryn');
        $pnj->setNameTranslations(['en' => 'Alchemist Orryn']);
        $pnj->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $pnj->getNameTranslations());
        $this->assertSame('Alchimiste Orryn', $pnj->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $pnj = new Pnj();
        $pnj->setName('Aubergiste');

        $this->assertSame([], $pnj->getNameTranslations());
    }
}
