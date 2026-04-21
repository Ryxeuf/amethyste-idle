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

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $item = new Item();
        $item->setDescription('Epee forgee en acier trempe.');

        $this->assertSame('Epee forgee en acier trempe.', $item->getLocalizedDescription('en'));
        $this->assertSame('Epee forgee en acier trempe.', $item->getLocalizedDescription('fr'));
        $this->assertSame('Epee forgee en acier trempe.', $item->getLocalizedDescription(null));
        $this->assertSame('Epee forgee en acier trempe.', $item->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $item = new Item();
        $item->setDescription('Epee forgee en acier trempe.');
        $item->setDescriptionTranslations([
            'en' => 'A sword forged from tempered steel.',
            'de' => 'Ein aus gehartetem Stahl geschmiedetes Schwert.',
        ]);

        $this->assertSame('A sword forged from tempered steel.', $item->getLocalizedDescription('en'));
        $this->assertSame('Ein aus gehartetem Stahl geschmiedetes Schwert.', $item->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $item = new Item();
        $item->setDescription('Restaure 50 points de vie.');
        $item->setDescriptionTranslations(['en' => 'Restores 50 HP.']);

        $this->assertSame('Restaure 50 points de vie.', $item->getLocalizedDescription('es'));
        $this->assertSame('Restaure 50 points de vie.', $item->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $item = new Item();
        $item->setDescription('Baton noueux taille dans un chene millenaire.');
        $item->setDescriptionTranslations([
            'en' => 'A gnarled staff carved from an ancient oak.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
        ]);

        $this->assertSame(
            ['en' => 'A gnarled staff carved from an ancient oak.'],
            $item->getDescriptionTranslations()
        );
        $this->assertSame(
            'A gnarled staff carved from an ancient oak.',
            $item->getLocalizedDescription('en')
        );
        $this->assertSame(
            'Baton noueux taille dans un chene millenaire.',
            $item->getLocalizedDescription('de')
        );
        $this->assertSame(
            'Baton noueux taille dans un chene millenaire.',
            $item->getLocalizedDescription('es')
        );
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $item = new Item();
        $item->setDescription('Bouclier en bois renforce.');
        $item->setDescriptionTranslations(['en' => 'A reinforced wooden shield.']);
        $item->setDescriptionTranslations(null);

        $this->assertSame([], $item->getDescriptionTranslations());
        $this->assertSame('Bouclier en bois renforce.', $item->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $item = new Item();
        $item->setDescription('Dague effilee.');
        $item->setDescriptionTranslations(['en' => 'A sharpened dagger.']);
        $item->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $item->getDescriptionTranslations());
        $this->assertSame('Dague effilee.', $item->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $item = new Item();
        $item->setDescription('Orbe magique.');

        $this->assertSame([], $item->getDescriptionTranslations());
    }
}
