<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\EquipmentSet;
use PHPUnit\Framework\TestCase;

class EquipmentSetLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $set = new EquipmentSet();
        $set->setName('Set du Gardien');

        $this->assertSame('Set du Gardien', $set->getLocalizedName('en'));
        $this->assertSame('Set du Gardien', $set->getLocalizedName('fr'));
        $this->assertSame('Set du Gardien', $set->getLocalizedName(null));
        $this->assertSame('Set du Gardien', $set->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $set = new EquipmentSet();
        $set->setName('Set du Gardien');
        $set->setNameTranslations(['en' => 'Guardian Set', 'de' => 'Wachterset']);

        $this->assertSame('Guardian Set', $set->getLocalizedName('en'));
        $this->assertSame('Wachterset', $set->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $set = new EquipmentSet();
        $set->setName("Set de l'Ombre");
        $set->setNameTranslations(['en' => 'Shadow Set']);

        $this->assertSame("Set de l'Ombre", $set->getLocalizedName('es'));
        $this->assertSame("Set de l'Ombre", $set->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $set = new EquipmentSet();
        $set->setName('Set du Veilleur');
        $set->setNameTranslations([
            'en' => 'Watcher Set',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Watcher Set'], $set->getNameTranslations());
        $this->assertSame('Watcher Set', $set->getLocalizedName('en'));
        $this->assertSame('Set du Veilleur', $set->getLocalizedName('de'));
        $this->assertSame('Set du Veilleur', $set->getLocalizedName('es'));
        $this->assertSame('Set du Veilleur', $set->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $set = new EquipmentSet();
        $set->setName('Set du Gardien');
        $set->setNameTranslations(['en' => 'Guardian Set']);
        $set->setNameTranslations(null);

        $this->assertSame([], $set->getNameTranslations());
        $this->assertSame('Set du Gardien', $set->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $set = new EquipmentSet();
        $set->setName('Set du Gardien');
        $set->setNameTranslations(['en' => 'Guardian Set']);
        $set->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $set->getNameTranslations());
        $this->assertSame('Set du Gardien', $set->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $set = new EquipmentSet();
        $set->setName('Set du Gardien');

        $this->assertSame([], $set->getNameTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $set = new EquipmentSet();
        $set->setDescription("L'equipement traditionnel des gardiens du royaume.");

        $this->assertSame("L'equipement traditionnel des gardiens du royaume.", $set->getLocalizedDescription('en'));
        $this->assertSame("L'equipement traditionnel des gardiens du royaume.", $set->getLocalizedDescription('fr'));
        $this->assertSame("L'equipement traditionnel des gardiens du royaume.", $set->getLocalizedDescription(null));
        $this->assertSame("L'equipement traditionnel des gardiens du royaume.", $set->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $set = new EquipmentSet();
        $set->setDescription("L'equipement traditionnel des gardiens du royaume.");
        $set->setDescriptionTranslations([
            'en' => "The traditional equipment of the kingdom's guardians.",
            'de' => 'Die traditionelle Ausruestung der Wachter.',
        ]);

        $this->assertSame("The traditional equipment of the kingdom's guardians.", $set->getLocalizedDescription('en'));
        $this->assertSame('Die traditionelle Ausruestung der Wachter.', $set->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $set = new EquipmentSet();
        $set->setDescription('Forge dans les tenebres.');
        $set->setDescriptionTranslations(['en' => 'Forged in the darkness.']);

        $this->assertSame('Forge dans les tenebres.', $set->getLocalizedDescription('es'));
        $this->assertSame('Forge dans les tenebres.', $set->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $set = new EquipmentSet();
        $set->setDescription('Une polyvalence rare.');
        $set->setDescriptionTranslations([
            'en' => 'A rare versatility.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'A rare versatility.'], $set->getDescriptionTranslations());
        $this->assertSame('A rare versatility.', $set->getLocalizedDescription('en'));
        $this->assertSame('Une polyvalence rare.', $set->getLocalizedDescription('de'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $set = new EquipmentSet();
        $set->setDescription('Forge dans les tenebres.');
        $set->setDescriptionTranslations(['en' => 'Forged in the darkness.']);
        $set->setDescriptionTranslations(null);

        $this->assertSame([], $set->getDescriptionTranslations());
        $this->assertSame('Forge dans les tenebres.', $set->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $set = new EquipmentSet();
        $set->setDescription('Forge dans les tenebres.');
        $set->setDescriptionTranslations(['en' => 'Forged in the darkness.']);
        $set->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $set->getDescriptionTranslations());
        $this->assertSame('Forge dans les tenebres.', $set->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $set = new EquipmentSet();
        $set->setDescription("L'equipement traditionnel des gardiens du royaume.");

        $this->assertSame([], $set->getDescriptionTranslations());
    }
}
