<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\StatusEffect;
use PHPUnit\Framework\TestCase;

class StatusEffectLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Poison virulent');

        $this->assertSame('Poison virulent', $effect->getLocalizedName('en'));
        $this->assertSame('Poison virulent', $effect->getLocalizedName('fr'));
        $this->assertSame('Poison virulent', $effect->getLocalizedName(null));
        $this->assertSame('Poison virulent', $effect->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Brulure');
        $effect->setNameTranslations(['en' => 'Burn', 'de' => 'Verbrennung']);

        $this->assertSame('Burn', $effect->getLocalizedName('en'));
        $this->assertSame('Verbrennung', $effect->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Paralysie');
        $effect->setNameTranslations(['en' => 'Paralysis']);

        $this->assertSame('Paralysie', $effect->getLocalizedName('es'));
        $this->assertSame('Paralysie', $effect->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Bouclier');
        $effect->setNameTranslations([
            'en' => 'Shield',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
        ]);

        $this->assertSame(['en' => 'Shield'], $effect->getNameTranslations());
        $this->assertSame('Shield', $effect->getLocalizedName('en'));
        $this->assertSame('Bouclier', $effect->getLocalizedName('de'));
        $this->assertSame('Bouclier', $effect->getLocalizedName('es'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Berserk');
        $effect->setNameTranslations(['en' => 'Berserk']);
        $effect->setNameTranslations(null);

        $this->assertSame([], $effect->getNameTranslations());
        $this->assertSame('Berserk', $effect->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Silence');
        $effect->setNameTranslations(['en' => 'Silence']);
        $effect->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $effect->getNameTranslations());
        $this->assertSame('Silence', $effect->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Regeneration');

        $this->assertSame([], $effect->getNameTranslations());
    }
}
