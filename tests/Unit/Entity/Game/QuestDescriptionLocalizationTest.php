<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Quest;
use PHPUnit\Framework\TestCase;

class QuestDescriptionLocalizationTest extends TestCase
{
    public function testGetLocalizedDescriptionFallsBackToBaseWhenNoTranslations(): void
    {
        $quest = new Quest();
        $quest->setDescription('Tuez 5 gobelins dans la foret.');

        $this->assertSame('Tuez 5 gobelins dans la foret.', $quest->getLocalizedDescription('en'));
        $this->assertSame('Tuez 5 gobelins dans la foret.', $quest->getLocalizedDescription('fr'));
        $this->assertSame('Tuez 5 gobelins dans la foret.', $quest->getLocalizedDescription(null));
        $this->assertSame('Tuez 5 gobelins dans la foret.', $quest->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $quest = new Quest();
        $quest->setDescription('Tuez 5 gobelins dans la foret.');
        $quest->setDescriptionTranslations([
            'en' => 'Slay 5 goblins in the forest.',
            'de' => 'Erschlagt 5 Goblins im Wald.',
        ]);

        $this->assertSame('Slay 5 goblins in the forest.', $quest->getLocalizedDescription('en'));
        $this->assertSame('Erschlagt 5 Goblins im Wald.', $quest->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $quest = new Quest();
        $quest->setDescription('Rapportez le cristal au mage.');
        $quest->setDescriptionTranslations(['en' => 'Return the crystal to the mage.']);

        $this->assertSame('Rapportez le cristal au mage.', $quest->getLocalizedDescription('es'));
        $this->assertSame('Rapportez le cristal au mage.', $quest->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $quest = new Quest();
        $quest->setDescription('Aidez le forgeron du village.');
        $quest->setDescriptionTranslations([
            'en' => 'Help the village blacksmith.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Help the village blacksmith.'], $quest->getDescriptionTranslations());
        $this->assertSame('Help the village blacksmith.', $quest->getLocalizedDescription('en'));
        $this->assertSame('Aidez le forgeron du village.', $quest->getLocalizedDescription('de'));
        $this->assertSame('Aidez le forgeron du village.', $quest->getLocalizedDescription('es'));
        $this->assertSame('Aidez le forgeron du village.', $quest->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $quest = new Quest();
        $quest->setDescription('Explorez la caverne oubliee.');
        $quest->setDescriptionTranslations(['en' => 'Explore the forgotten cave.']);
        $quest->setDescriptionTranslations(null);

        $this->assertSame([], $quest->getDescriptionTranslations());
        $this->assertSame('Explorez la caverne oubliee.', $quest->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $quest = new Quest();
        $quest->setDescription('Vainquez le dragon des abysses.');
        $quest->setDescriptionTranslations(['en' => 'Defeat the abyssal dragon.']);
        $quest->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $quest->getDescriptionTranslations());
        $this->assertSame('Vainquez le dragon des abysses.', $quest->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $quest = new Quest();
        $quest->setDescription('Escortez la caravane jusqu\'a la cite.');

        $this->assertSame([], $quest->getDescriptionTranslations());
    }
}
