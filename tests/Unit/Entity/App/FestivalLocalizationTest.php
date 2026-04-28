<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Festival;
use PHPUnit\Framework\TestCase;

class FestivalLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $festival = new Festival();
        $festival->setName('Fete du Renouveau');

        $this->assertSame('Fete du Renouveau', $festival->getLocalizedName('en'));
        $this->assertSame('Fete du Renouveau', $festival->getLocalizedName('fr'));
        $this->assertSame('Fete du Renouveau', $festival->getLocalizedName(null));
        $this->assertSame('Fete du Renouveau', $festival->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $festival = new Festival();
        $festival->setName('Fete du Renouveau');
        $festival->setNameTranslations(['en' => 'Renewal Festival', 'de' => 'Fest der Erneuerung']);

        $this->assertSame('Renewal Festival', $festival->getLocalizedName('en'));
        $this->assertSame('Fest der Erneuerung', $festival->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $festival = new Festival();
        $festival->setName('Solstice de Flamme');
        $festival->setNameTranslations(['en' => 'Flame Solstice']);

        $this->assertSame('Solstice de Flamme', $festival->getLocalizedName('es'));
        $this->assertSame('Solstice de Flamme', $festival->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $festival = new Festival();
        $festival->setName('Nuit Eternelle');
        $festival->setNameTranslations([
            'en' => 'Eternal Night',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Eternal Night'], $festival->getNameTranslations());
        $this->assertSame('Eternal Night', $festival->getLocalizedName('en'));
        $this->assertSame('Nuit Eternelle', $festival->getLocalizedName('de'));
        $this->assertSame('Nuit Eternelle', $festival->getLocalizedName('es'));
        $this->assertSame('Nuit Eternelle', $festival->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $festival = new Festival();
        $festival->setName('Moisson des Ames');
        $festival->setNameTranslations(['en' => 'Soul Harvest']);
        $festival->setNameTranslations(null);

        $this->assertSame([], $festival->getNameTranslations());
        $this->assertSame('Moisson des Ames', $festival->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $festival = new Festival();
        $festival->setName('Solstice de Flamme');
        $festival->setNameTranslations(['en' => 'Flame Solstice']);
        $festival->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $festival->getNameTranslations());
        $this->assertSame('Solstice de Flamme', $festival->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $festival = new Festival();
        $festival->setName('Fete du Renouveau');

        $this->assertSame([], $festival->getNameTranslations());
    }

    public function testGetLocalizedDescriptionReturnsNullWhenDescriptionAndTranslationsAreNull(): void
    {
        $festival = new Festival();

        $this->assertNull($festival->getLocalizedDescription('en'));
        $this->assertNull($festival->getLocalizedDescription('fr'));
        $this->assertNull($festival->getLocalizedDescription(null));
        $this->assertNull($festival->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $festival = new Festival();
        $festival->setDescription('Le printemps eveille la nature.');

        $this->assertSame('Le printemps eveille la nature.', $festival->getLocalizedDescription('en'));
        $this->assertSame('Le printemps eveille la nature.', $festival->getLocalizedDescription('fr'));
        $this->assertSame('Le printemps eveille la nature.', $festival->getLocalizedDescription(null));
        $this->assertSame('Le printemps eveille la nature.', $festival->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $festival = new Festival();
        $festival->setDescription('Le printemps eveille la nature.');
        $festival->setDescriptionTranslations([
            'en' => 'Spring awakens nature.',
            'de' => 'Der Fruehling weckt die Natur.',
        ]);

        $this->assertSame('Spring awakens nature.', $festival->getLocalizedDescription('en'));
        $this->assertSame('Der Fruehling weckt die Natur.', $festival->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $festival = new Festival();
        $festival->setDescription('Le soleil brule fort.');
        $festival->setDescriptionTranslations(['en' => 'The sun burns hot.']);

        $this->assertSame('Le soleil brule fort.', $festival->getLocalizedDescription('es'));
        $this->assertSame('Le soleil brule fort.', $festival->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $festival = new Festival();
        $festival->setDescription('Le froid mord les os.');
        $festival->setDescriptionTranslations([
            'en' => 'The cold bites to the bone.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'The cold bites to the bone.'], $festival->getDescriptionTranslations());
        $this->assertSame('The cold bites to the bone.', $festival->getLocalizedDescription('en'));
        $this->assertSame('Le froid mord les os.', $festival->getLocalizedDescription('de'));
        $this->assertSame('Le froid mord les os.', $festival->getLocalizedDescription('es'));
        $this->assertSame('Le froid mord les os.', $festival->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $festival = new Festival();
        $festival->setDescription('Le froid mord les os.');
        $festival->setDescriptionTranslations(['en' => 'The cold bites to the bone.']);
        $festival->setDescriptionTranslations(null);

        $this->assertSame([], $festival->getDescriptionTranslations());
        $this->assertSame('Le froid mord les os.', $festival->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $festival = new Festival();
        $festival->setDescription('Le soleil brule fort.');
        $festival->setDescriptionTranslations(['en' => 'The sun burns hot.']);
        $festival->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $festival->getDescriptionTranslations());
        $this->assertSame('Le soleil brule fort.', $festival->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $festival = new Festival();
        $festival->setDescription('Le printemps eveille la nature.');

        $this->assertSame([], $festival->getDescriptionTranslations());
    }
}
