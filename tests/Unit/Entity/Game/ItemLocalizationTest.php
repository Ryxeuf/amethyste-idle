<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Item;
use PHPUnit\Framework\TestCase;

class ItemLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $item = new Item();
        $item->setName('Epee en fer');

        $this->assertSame('Epee en fer', $item->getLocalizedName('en'));
        $this->assertSame('Epee en fer', $item->getLocalizedName('fr'));
        $this->assertSame('Epee en fer', $item->getLocalizedName(null));
        $this->assertSame('Epee en fer', $item->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $item = new Item();
        $item->setName('Epee en fer');
        $item->setNameTranslations(['en' => 'Iron sword', 'de' => 'Eisenschwert']);

        $this->assertSame('Iron sword', $item->getLocalizedName('en'));
        $this->assertSame('Eisenschwert', $item->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $item = new Item();
        $item->setName('Potion de soin');
        $item->setNameTranslations(['en' => 'Healing potion']);

        $this->assertSame('Potion de soin', $item->getLocalizedName('es'));
        $this->assertSame('Potion de soin', $item->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $item = new Item();
        $item->setName('Baton noueux');
        $item->setNameTranslations([
            'en' => 'Gnarled staff',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
        ]);

        $this->assertSame(['en' => 'Gnarled staff'], $item->getNameTranslations());
        $this->assertSame('Gnarled staff', $item->getLocalizedName('en'));
        $this->assertSame('Baton noueux', $item->getLocalizedName('de'));
        $this->assertSame('Baton noueux', $item->getLocalizedName('es'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $item = new Item();
        $item->setName('Bouclier en bois');
        $item->setNameTranslations(['en' => 'Wooden shield']);
        $item->setNameTranslations(null);

        $this->assertSame([], $item->getNameTranslations());
        $this->assertSame('Bouclier en bois', $item->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $item = new Item();
        $item->setName('Dague');
        $item->setNameTranslations(['en' => 'Dagger']);
        $item->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $item->getNameTranslations());
        $this->assertSame('Dague', $item->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $item = new Item();
        $item->setName('Orbe magique');

        $this->assertSame([], $item->getNameTranslations());
    }
}
