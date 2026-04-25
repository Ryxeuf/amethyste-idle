<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Race;
use PHPUnit\Framework\TestCase;

class RaceLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $race = new Race();
        $race->setName('Humain');

        $this->assertSame('Humain', $race->getLocalizedName('en'));
        $this->assertSame('Humain', $race->getLocalizedName('fr'));
        $this->assertSame('Humain', $race->getLocalizedName(null));
        $this->assertSame('Humain', $race->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $race = new Race();
        $race->setName('Humain');
        $race->setNameTranslations(['en' => 'Human', 'de' => 'Mensch']);

        $this->assertSame('Human', $race->getLocalizedName('en'));
        $this->assertSame('Mensch', $race->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $race = new Race();
        $race->setName('Elfe');
        $race->setNameTranslations(['en' => 'Elf']);

        $this->assertSame('Elfe', $race->getLocalizedName('es'));
        $this->assertSame('Elfe', $race->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $race = new Race();
        $race->setName('Nain');
        $race->setNameTranslations([
            'en' => 'Dwarf',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Dwarf'], $race->getNameTranslations());
        $this->assertSame('Dwarf', $race->getLocalizedName('en'));
        $this->assertSame('Nain', $race->getLocalizedName('de'));
        $this->assertSame('Nain', $race->getLocalizedName('es'));
        $this->assertSame('Nain', $race->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $race = new Race();
        $race->setName('Orc');
        $race->setNameTranslations(['en' => 'Orc']);
        $race->setNameTranslations(null);

        $this->assertSame([], $race->getNameTranslations());
        $this->assertSame('Orc', $race->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $race = new Race();
        $race->setName('Nain');
        $race->setNameTranslations(['en' => 'Dwarf']);
        $race->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $race->getNameTranslations());
        $this->assertSame('Nain', $race->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $race = new Race();
        $race->setName('Humain');

        $this->assertSame([], $race->getNameTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $race = new Race();
        $race->setDescription('Les humains sont la race la plus repandue.');

        $this->assertSame('Les humains sont la race la plus repandue.', $race->getLocalizedDescription('en'));
        $this->assertSame('Les humains sont la race la plus repandue.', $race->getLocalizedDescription('fr'));
        $this->assertSame('Les humains sont la race la plus repandue.', $race->getLocalizedDescription(null));
        $this->assertSame('Les humains sont la race la plus repandue.', $race->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $race = new Race();
        $race->setDescription('Les humains sont la race la plus repandue.');
        $race->setDescriptionTranslations([
            'en' => 'Humans are the most widespread race.',
            'de' => 'Menschen sind die am weitesten verbreitete Rasse.',
        ]);

        $this->assertSame('Humans are the most widespread race.', $race->getLocalizedDescription('en'));
        $this->assertSame('Menschen sind die am weitesten verbreitete Rasse.', $race->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $race = new Race();
        $race->setDescription('Les elfes sont agiles et precis.');
        $race->setDescriptionTranslations(['en' => 'Elves are nimble and precise.']);

        $this->assertSame('Les elfes sont agiles et precis.', $race->getLocalizedDescription('es'));
        $this->assertSame('Les elfes sont agiles et precis.', $race->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $race = new Race();
        $race->setDescription('Les nains sont robustes et endurants.');
        $race->setDescriptionTranslations([
            'en' => 'Dwarves are sturdy and resilient.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Dwarves are sturdy and resilient.'], $race->getDescriptionTranslations());
        $this->assertSame('Dwarves are sturdy and resilient.', $race->getLocalizedDescription('en'));
        $this->assertSame('Les nains sont robustes et endurants.', $race->getLocalizedDescription('de'));
        $this->assertSame('Les nains sont robustes et endurants.', $race->getLocalizedDescription('es'));
        $this->assertSame('Les nains sont robustes et endurants.', $race->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $race = new Race();
        $race->setDescription('Les orcs sont des guerriers nes.');
        $race->setDescriptionTranslations(['en' => 'Orcs are born warriors.']);
        $race->setDescriptionTranslations(null);

        $this->assertSame([], $race->getDescriptionTranslations());
        $this->assertSame('Les orcs sont des guerriers nes.', $race->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $race = new Race();
        $race->setDescription('Les elfes sont agiles et precis.');
        $race->setDescriptionTranslations(['en' => 'Elves are nimble and precise.']);
        $race->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $race->getDescriptionTranslations());
        $this->assertSame('Les elfes sont agiles et precis.', $race->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $race = new Race();
        $race->setDescription('Les humains sont la race la plus repandue.');

        $this->assertSame([], $race->getDescriptionTranslations());
    }
}
