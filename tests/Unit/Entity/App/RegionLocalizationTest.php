<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Region;
use PHPUnit\Framework\TestCase;

class RegionLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $region = new Region();
        $region->setName('Plaines de l\'Eveil');

        $this->assertSame('Plaines de l\'Eveil', $region->getLocalizedName('en'));
        $this->assertSame('Plaines de l\'Eveil', $region->getLocalizedName('fr'));
        $this->assertSame('Plaines de l\'Eveil', $region->getLocalizedName(null));
        $this->assertSame('Plaines de l\'Eveil', $region->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $region = new Region();
        $region->setName('Plaines de l\'Eveil');
        $region->setNameTranslations(['en' => 'Plains of Awakening', 'de' => 'Ebenen des Erwachens']);

        $this->assertSame('Plains of Awakening', $region->getLocalizedName('en'));
        $this->assertSame('Ebenen des Erwachens', $region->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $region = new Region();
        $region->setName('Sanctuaire de Lumiere');
        $region->setNameTranslations(['en' => 'Sanctuary of Light']);

        $this->assertSame('Sanctuaire de Lumiere', $region->getLocalizedName('es'));
        $this->assertSame('Sanctuaire de Lumiere', $region->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $region = new Region();
        $region->setName('Terres Sauvages');
        $region->setNameTranslations([
            'en' => 'Wildlands',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Wildlands'], $region->getNameTranslations());
        $this->assertSame('Wildlands', $region->getLocalizedName('en'));
        $this->assertSame('Terres Sauvages', $region->getLocalizedName('de'));
        $this->assertSame('Terres Sauvages', $region->getLocalizedName('es'));
        $this->assertSame('Terres Sauvages', $region->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $region = new Region();
        $region->setName('Terres Sauvages');
        $region->setNameTranslations(['en' => 'Wildlands']);
        $region->setNameTranslations(null);

        $this->assertSame([], $region->getNameTranslations());
        $this->assertSame('Terres Sauvages', $region->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $region = new Region();
        $region->setName('Sanctuaire de Lumiere');
        $region->setNameTranslations(['en' => 'Sanctuary of Light']);
        $region->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $region->getNameTranslations());
        $this->assertSame('Sanctuaire de Lumiere', $region->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $region = new Region();
        $region->setName('Plaines de l\'Eveil');

        $this->assertSame([], $region->getNameTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $region = new Region();
        $region->setDescription('Vastes plaines verdoyantes.');

        $this->assertSame('Vastes plaines verdoyantes.', $region->getLocalizedDescription('en'));
        $this->assertSame('Vastes plaines verdoyantes.', $region->getLocalizedDescription('fr'));
        $this->assertSame('Vastes plaines verdoyantes.', $region->getLocalizedDescription(null));
        $this->assertSame('Vastes plaines verdoyantes.', $region->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $region = new Region();
        $region->setDescription('Vastes plaines verdoyantes.');
        $region->setDescriptionTranslations([
            'en' => 'Vast green plains.',
            'de' => 'Weite gruene Ebenen.',
        ]);

        $this->assertSame('Vast green plains.', $region->getLocalizedDescription('en'));
        $this->assertSame('Weite gruene Ebenen.', $region->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $region = new Region();
        $region->setDescription('Zone protegee par les anciens.');
        $region->setDescriptionTranslations(['en' => 'A zone protected by the ancients.']);

        $this->assertSame('Zone protegee par les anciens.', $region->getLocalizedDescription('es'));
        $this->assertSame('Zone protegee par les anciens.', $region->getLocalizedDescription('ja'));
    }

    public function testGetLocalizedDescriptionReturnsNullWhenBaseDescriptionIsNull(): void
    {
        $region = new Region();

        $this->assertNull($region->getLocalizedDescription('en'));
        $this->assertNull($region->getLocalizedDescription('fr'));
        $this->assertNull($region->getLocalizedDescription(null));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $region = new Region();
        $region->setDescription('Contrees dangereuses.');
        $region->setDescriptionTranslations([
            'en' => 'Dangerous lands.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Dangerous lands.'], $region->getDescriptionTranslations());
        $this->assertSame('Dangerous lands.', $region->getLocalizedDescription('en'));
        $this->assertSame('Contrees dangereuses.', $region->getLocalizedDescription('de'));
        $this->assertSame('Contrees dangereuses.', $region->getLocalizedDescription('es'));
        $this->assertSame('Contrees dangereuses.', $region->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $region = new Region();
        $region->setDescription('Contrees dangereuses.');
        $region->setDescriptionTranslations(['en' => 'Dangerous lands.']);
        $region->setDescriptionTranslations(null);

        $this->assertSame([], $region->getDescriptionTranslations());
        $this->assertSame('Contrees dangereuses.', $region->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $region = new Region();
        $region->setDescription('Zone protegee par les anciens.');
        $region->setDescriptionTranslations(['en' => 'A zone protected by the ancients.']);
        $region->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $region->getDescriptionTranslations());
        $this->assertSame('Zone protegee par les anciens.', $region->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $region = new Region();
        $region->setDescription('Vastes plaines verdoyantes.');

        $this->assertSame([], $region->getDescriptionTranslations());
    }
}
