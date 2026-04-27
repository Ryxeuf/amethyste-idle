<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Recipe;
use PHPUnit\Framework\TestCase;

class RecipeLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $recipe = new Recipe();
        $recipe->setName('Dague en fer');

        $this->assertSame('Dague en fer', $recipe->getLocalizedName('en'));
        $this->assertSame('Dague en fer', $recipe->getLocalizedName('fr'));
        $this->assertSame('Dague en fer', $recipe->getLocalizedName(null));
        $this->assertSame('Dague en fer', $recipe->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $recipe = new Recipe();
        $recipe->setName('Dague en fer');
        $recipe->setNameTranslations(['en' => 'Iron Dagger', 'de' => 'Eisendolch']);

        $this->assertSame('Iron Dagger', $recipe->getLocalizedName('en'));
        $this->assertSame('Eisendolch', $recipe->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $recipe = new Recipe();
        $recipe->setName('Epee courte');
        $recipe->setNameTranslations(['en' => 'Short Sword']);

        $this->assertSame('Epee courte', $recipe->getLocalizedName('es'));
        $this->assertSame('Epee courte', $recipe->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $recipe = new Recipe();
        $recipe->setName('Dague en fer');
        $recipe->setNameTranslations([
            'en' => 'Iron Dagger',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Iron Dagger'], $recipe->getNameTranslations());
        $this->assertSame('Iron Dagger', $recipe->getLocalizedName('en'));
        $this->assertSame('Dague en fer', $recipe->getLocalizedName('de'));
        $this->assertSame('Dague en fer', $recipe->getLocalizedName('es'));
        $this->assertSame('Dague en fer', $recipe->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $recipe = new Recipe();
        $recipe->setName('Dague en fer');
        $recipe->setNameTranslations(['en' => 'Iron Dagger']);
        $recipe->setNameTranslations(null);

        $this->assertSame([], $recipe->getNameTranslations());
        $this->assertSame('Dague en fer', $recipe->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $recipe = new Recipe();
        $recipe->setName('Dague en fer');
        $recipe->setNameTranslations(['en' => 'Iron Dagger']);
        $recipe->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $recipe->getNameTranslations());
        $this->assertSame('Dague en fer', $recipe->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $recipe = new Recipe();
        $recipe->setName('Dague en fer');

        $this->assertSame([], $recipe->getNameTranslations());
    }

    public function testGetLocalizedDescriptionReturnsNullWhenDescriptionAndTranslationsAreNull(): void
    {
        $recipe = new Recipe();

        $this->assertNull($recipe->getLocalizedDescription('en'));
        $this->assertNull($recipe->getLocalizedDescription('fr'));
        $this->assertNull($recipe->getLocalizedDescription(null));
        $this->assertNull($recipe->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $recipe = new Recipe();
        $recipe->setDescription('Forge une dague en fer tranchante.');

        $this->assertSame('Forge une dague en fer tranchante.', $recipe->getLocalizedDescription('en'));
        $this->assertSame('Forge une dague en fer tranchante.', $recipe->getLocalizedDescription('fr'));
        $this->assertSame('Forge une dague en fer tranchante.', $recipe->getLocalizedDescription(null));
        $this->assertSame('Forge une dague en fer tranchante.', $recipe->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $recipe = new Recipe();
        $recipe->setDescription('Forge une dague en fer tranchante.');
        $recipe->setDescriptionTranslations([
            'en' => 'Forges a sharp iron dagger.',
            'de' => 'Schmiedet einen scharfen Eisendolch.',
        ]);

        $this->assertSame('Forges a sharp iron dagger.', $recipe->getLocalizedDescription('en'));
        $this->assertSame('Schmiedet einen scharfen Eisendolch.', $recipe->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $recipe = new Recipe();
        $recipe->setDescription('Forge une epee courte equilibree.');
        $recipe->setDescriptionTranslations(['en' => 'Forges a balanced short sword.']);

        $this->assertSame('Forge une epee courte equilibree.', $recipe->getLocalizedDescription('es'));
        $this->assertSame('Forge une epee courte equilibree.', $recipe->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $recipe = new Recipe();
        $recipe->setDescription('Forge une dague en fer tranchante.');
        $recipe->setDescriptionTranslations([
            'en' => 'Forges a sharp iron dagger.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Forges a sharp iron dagger.'], $recipe->getDescriptionTranslations());
        $this->assertSame('Forges a sharp iron dagger.', $recipe->getLocalizedDescription('en'));
        $this->assertSame('Forge une dague en fer tranchante.', $recipe->getLocalizedDescription('de'));
        $this->assertSame('Forge une dague en fer tranchante.', $recipe->getLocalizedDescription('es'));
        $this->assertSame('Forge une dague en fer tranchante.', $recipe->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $recipe = new Recipe();
        $recipe->setDescription('Forge une dague en fer tranchante.');
        $recipe->setDescriptionTranslations(['en' => 'Forges a sharp iron dagger.']);
        $recipe->setDescriptionTranslations(null);

        $this->assertSame([], $recipe->getDescriptionTranslations());
        $this->assertSame('Forge une dague en fer tranchante.', $recipe->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $recipe = new Recipe();
        $recipe->setDescription('Forge une dague en fer tranchante.');
        $recipe->setDescriptionTranslations(['en' => 'Forges a sharp iron dagger.']);
        $recipe->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $recipe->getDescriptionTranslations());
        $this->assertSame('Forge une dague en fer tranchante.', $recipe->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $recipe = new Recipe();
        $recipe->setDescription('Forge une dague en fer tranchante.');

        $this->assertSame([], $recipe->getDescriptionTranslations());
    }
}
