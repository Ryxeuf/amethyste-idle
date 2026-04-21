<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Monster;
use PHPUnit\Framework\TestCase;

class MonsterLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $monster = new Monster();
        $monster->setName('Gobelin des cavernes');

        $this->assertSame('Gobelin des cavernes', $monster->getLocalizedName('en'));
        $this->assertSame('Gobelin des cavernes', $monster->getLocalizedName('fr'));
        $this->assertSame('Gobelin des cavernes', $monster->getLocalizedName(null));
        $this->assertSame('Gobelin des cavernes', $monster->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $monster = new Monster();
        $monster->setName('Gobelin des cavernes');
        $monster->setNameTranslations(['en' => 'Cave goblin', 'de' => 'Hohlenkobold']);

        $this->assertSame('Cave goblin', $monster->getLocalizedName('en'));
        $this->assertSame('Hohlenkobold', $monster->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $monster = new Monster();
        $monster->setName('Loup sombre');
        $monster->setNameTranslations(['en' => 'Dark wolf']);

        $this->assertSame('Loup sombre', $monster->getLocalizedName('es'));
        $this->assertSame('Loup sombre', $monster->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $monster = new Monster();
        $monster->setName('Araignee geante');
        $monster->setNameTranslations([
            'en' => 'Giant spider',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
        ]);

        $this->assertSame(['en' => 'Giant spider'], $monster->getNameTranslations());
        $this->assertSame('Giant spider', $monster->getLocalizedName('en'));
        $this->assertSame('Araignee geante', $monster->getLocalizedName('de'));
        $this->assertSame('Araignee geante', $monster->getLocalizedName('es'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $monster = new Monster();
        $monster->setName('Ogre de pierre');
        $monster->setNameTranslations(['en' => 'Stone ogre']);
        $monster->setNameTranslations(null);

        $this->assertSame([], $monster->getNameTranslations());
        $this->assertSame('Ogre de pierre', $monster->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $monster = new Monster();
        $monster->setName('Squelette');
        $monster->setNameTranslations(['en' => 'Skeleton']);
        $monster->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $monster->getNameTranslations());
        $this->assertSame('Squelette', $monster->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $monster = new Monster();
        $monster->setName('Dragon rouge');

        $this->assertSame([], $monster->getNameTranslations());
    }
}
