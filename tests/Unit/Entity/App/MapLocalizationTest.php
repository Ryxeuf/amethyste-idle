<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Map;
use PHPUnit\Framework\TestCase;

class MapLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $map = new Map();
        $map->setName('Foret des murmures');

        $this->assertSame('Foret des murmures', $map->getLocalizedName('en'));
        $this->assertSame('Foret des murmures', $map->getLocalizedName('fr'));
        $this->assertSame('Foret des murmures', $map->getLocalizedName(null));
        $this->assertSame('Foret des murmures', $map->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $map = new Map();
        $map->setName('Foret des murmures');
        $map->setNameTranslations(['en' => 'Whispering Forest', 'de' => 'Fluesterwald']);

        $this->assertSame('Whispering Forest', $map->getLocalizedName('en'));
        $this->assertSame('Fluesterwald', $map->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $map = new Map();
        $map->setName('Mines profondes');
        $map->setNameTranslations(['en' => 'Deep Mines']);

        $this->assertSame('Mines profondes', $map->getLocalizedName('es'));
        $this->assertSame('Mines profondes', $map->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $map = new Map();
        $map->setName('Marais Brumeux');
        $map->setNameTranslations([
            'en' => 'Misty Swamp',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Misty Swamp'], $map->getNameTranslations());
        $this->assertSame('Misty Swamp', $map->getLocalizedName('en'));
        $this->assertSame('Marais Brumeux', $map->getLocalizedName('de'));
        $this->assertSame('Marais Brumeux', $map->getLocalizedName('es'));
        $this->assertSame('Marais Brumeux', $map->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $map = new Map();
        $map->setName('Marais Brumeux');
        $map->setNameTranslations(['en' => 'Misty Swamp']);
        $map->setNameTranslations(null);

        $this->assertSame([], $map->getNameTranslations());
        $this->assertSame('Marais Brumeux', $map->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $map = new Map();
        $map->setName('Mines profondes');
        $map->setNameTranslations(['en' => 'Deep Mines']);
        $map->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $map->getNameTranslations());
        $this->assertSame('Mines profondes', $map->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $map = new Map();
        $map->setName('Foret des murmures');

        $this->assertSame([], $map->getNameTranslations());
    }
}
