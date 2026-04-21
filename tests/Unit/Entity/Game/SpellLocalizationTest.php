<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Spell;
use PHPUnit\Framework\TestCase;

class SpellLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $spell = new Spell();
        $spell->setName('Boule de feu');

        $this->assertSame('Boule de feu', $spell->getLocalizedName('en'));
        $this->assertSame('Boule de feu', $spell->getLocalizedName('fr'));
        $this->assertSame('Boule de feu', $spell->getLocalizedName(null));
        $this->assertSame('Boule de feu', $spell->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $spell = new Spell();
        $spell->setName('Boule de feu');
        $spell->setNameTranslations(['en' => 'Fireball', 'de' => 'Feuerball']);

        $this->assertSame('Fireball', $spell->getLocalizedName('en'));
        $this->assertSame('Feuerball', $spell->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $spell = new Spell();
        $spell->setName('Eclair glacial');
        $spell->setNameTranslations(['en' => 'Ice bolt']);

        $this->assertSame('Eclair glacial', $spell->getLocalizedName('es'));
        $this->assertSame('Eclair glacial', $spell->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $spell = new Spell();
        $spell->setName('Soin leger');
        $spell->setNameTranslations([
            'en' => 'Minor heal',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
        ]);

        $this->assertSame(['en' => 'Minor heal'], $spell->getNameTranslations());
        $this->assertSame('Minor heal', $spell->getLocalizedName('en'));
        $this->assertSame('Soin leger', $spell->getLocalizedName('de'));
        $this->assertSame('Soin leger', $spell->getLocalizedName('es'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $spell = new Spell();
        $spell->setName('Foudre');
        $spell->setNameTranslations(['en' => 'Lightning']);
        $spell->setNameTranslations(null);

        $this->assertSame([], $spell->getNameTranslations());
        $this->assertSame('Foudre', $spell->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $spell = new Spell();
        $spell->setName('Benediction');
        $spell->setNameTranslations(['en' => 'Blessing']);
        $spell->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $spell->getNameTranslations());
        $this->assertSame('Benediction', $spell->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $spell = new Spell();
        $spell->setName('Meteore');

        $this->assertSame([], $spell->getNameTranslations());
    }
}
