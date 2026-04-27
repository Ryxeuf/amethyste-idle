<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\EnchantmentDefinition;
use PHPUnit\Framework\TestCase;

class EnchantmentLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setName('Tranchant de feu');

        $this->assertSame('Tranchant de feu', $definition->getLocalizedName('en'));
        $this->assertSame('Tranchant de feu', $definition->getLocalizedName('fr'));
        $this->assertSame('Tranchant de feu', $definition->getLocalizedName(null));
        $this->assertSame('Tranchant de feu', $definition->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setName('Tranchant de feu');
        $definition->setNameTranslations(['en' => 'Flame Edge', 'de' => 'Flammenklinge']);

        $this->assertSame('Flame Edge', $definition->getLocalizedName('en'));
        $this->assertSame('Flammenklinge', $definition->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setName('Protection de glace');
        $definition->setNameTranslations(['en' => 'Ice Ward']);

        $this->assertSame('Protection de glace', $definition->getLocalizedName('es'));
        $this->assertSame('Protection de glace', $definition->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setName('Tranchant de feu');
        $definition->setNameTranslations([
            'en' => 'Flame Edge',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Flame Edge'], $definition->getNameTranslations());
        $this->assertSame('Flame Edge', $definition->getLocalizedName('en'));
        $this->assertSame('Tranchant de feu', $definition->getLocalizedName('de'));
        $this->assertSame('Tranchant de feu', $definition->getLocalizedName('es'));
        $this->assertSame('Tranchant de feu', $definition->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setName('Tranchant de feu');
        $definition->setNameTranslations(['en' => 'Flame Edge']);
        $definition->setNameTranslations(null);

        $this->assertSame([], $definition->getNameTranslations());
        $this->assertSame('Tranchant de feu', $definition->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setName('Tranchant de feu');

        $this->assertSame([], $definition->getNameTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setDescription('Imprègne l\'arme d\'une flamme ardente.');

        $this->assertSame('Imprègne l\'arme d\'une flamme ardente.', $definition->getLocalizedDescription('en'));
        $this->assertSame('Imprègne l\'arme d\'une flamme ardente.', $definition->getLocalizedDescription('fr'));
        $this->assertSame('Imprègne l\'arme d\'une flamme ardente.', $definition->getLocalizedDescription(null));
        $this->assertSame('Imprègne l\'arme d\'une flamme ardente.', $definition->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setDescription('Imprègne l\'arme d\'une flamme ardente.');
        $definition->setDescriptionTranslations([
            'en' => 'Imbues the weapon with searing flame.',
            'de' => 'Erfuellt die Waffe mit gluehender Flamme.',
        ]);

        $this->assertSame('Imbues the weapon with searing flame.', $definition->getLocalizedDescription('en'));
        $this->assertSame('Erfuellt die Waffe mit gluehender Flamme.', $definition->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setDescription('Enveloppe l\'armure d\'un voile de givre.');
        $definition->setDescriptionTranslations(['en' => 'Wraps the armor in a veil of frost.']);

        $this->assertSame('Enveloppe l\'armure d\'un voile de givre.', $definition->getLocalizedDescription('es'));
    }

    public function testGetLocalizedDescriptionReturnsNullWhenBaseDescriptionIsNull(): void
    {
        $definition = new EnchantmentDefinition();

        $this->assertNull($definition->getLocalizedDescription('en'));
        $this->assertNull($definition->getLocalizedDescription(null));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setDescription('Imprègne l\'arme d\'une flamme ardente.');
        $definition->setDescriptionTranslations([
            'en' => 'Imbues the weapon with searing flame.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Imbues the weapon with searing flame.'], $definition->getDescriptionTranslations());
        $this->assertSame('Imbues the weapon with searing flame.', $definition->getLocalizedDescription('en'));
        $this->assertSame('Imprègne l\'arme d\'une flamme ardente.', $definition->getLocalizedDescription('de'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setDescription('Imprègne l\'arme d\'une flamme ardente.');
        $definition->setDescriptionTranslations(['en' => 'Imbues the weapon with searing flame.']);
        $definition->setDescriptionTranslations(null);

        $this->assertSame([], $definition->getDescriptionTranslations());
        $this->assertSame('Imprègne l\'arme d\'une flamme ardente.', $definition->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setDescription('Imprègne l\'arme d\'une flamme ardente.');

        $this->assertSame([], $definition->getDescriptionTranslations());
    }
}
