<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Mount;
use PHPUnit\Framework\TestCase;

class MountLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $mount = new Mount();
        $mount->setName('Cheval brun');

        $this->assertSame('Cheval brun', $mount->getLocalizedName('en'));
        $this->assertSame('Cheval brun', $mount->getLocalizedName('fr'));
        $this->assertSame('Cheval brun', $mount->getLocalizedName(null));
        $this->assertSame('Cheval brun', $mount->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $mount = new Mount();
        $mount->setName('Cheval brun');
        $mount->setNameTranslations(['en' => 'Brown Horse', 'de' => 'Braunes Pferd']);

        $this->assertSame('Brown Horse', $mount->getLocalizedName('en'));
        $this->assertSame('Braunes Pferd', $mount->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $mount = new Mount();
        $mount->setName('Chocobo jaune');
        $mount->setNameTranslations(['en' => 'Yellow Chocobo']);

        $this->assertSame('Chocobo jaune', $mount->getLocalizedName('es'));
        $this->assertSame('Chocobo jaune', $mount->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $mount = new Mount();
        $mount->setName('Cheval brun');
        $mount->setNameTranslations([
            'en' => 'Brown Horse',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Brown Horse'], $mount->getNameTranslations());
        $this->assertSame('Brown Horse', $mount->getLocalizedName('en'));
        $this->assertSame('Cheval brun', $mount->getLocalizedName('de'));
        $this->assertSame('Cheval brun', $mount->getLocalizedName('es'));
        $this->assertSame('Cheval brun', $mount->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $mount = new Mount();
        $mount->setName('Cheval brun');
        $mount->setNameTranslations(['en' => 'Brown Horse']);
        $mount->setNameTranslations(null);

        $this->assertSame([], $mount->getNameTranslations());
        $this->assertSame('Cheval brun', $mount->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $mount = new Mount();
        $mount->setName('Cheval brun');
        $mount->setNameTranslations(['en' => 'Brown Horse']);
        $mount->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $mount->getNameTranslations());
        $this->assertSame('Cheval brun', $mount->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $mount = new Mount();
        $mount->setName('Cheval brun');

        $this->assertSame([], $mount->getNameTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $mount = new Mount();
        $mount->setDescription('Une monture commune, fidele et endurante.');

        $this->assertSame('Une monture commune, fidele et endurante.', $mount->getLocalizedDescription('en'));
        $this->assertSame('Une monture commune, fidele et endurante.', $mount->getLocalizedDescription('fr'));
        $this->assertSame('Une monture commune, fidele et endurante.', $mount->getLocalizedDescription(null));
        $this->assertSame('Une monture commune, fidele et endurante.', $mount->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $mount = new Mount();
        $mount->setDescription('Une monture commune, fidele et endurante.');
        $mount->setDescriptionTranslations([
            'en' => 'A common, loyal and hardy mount.',
            'de' => 'Ein gewoehnliches, treues und ausdauerndes Reittier.',
        ]);

        $this->assertSame('A common, loyal and hardy mount.', $mount->getLocalizedDescription('en'));
        $this->assertSame('Ein gewoehnliches, treues und ausdauerndes Reittier.', $mount->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $mount = new Mount();
        $mount->setDescription('L\'oiseau geant legendaire.');
        $mount->setDescriptionTranslations(['en' => 'The legendary giant bird.']);

        $this->assertSame('L\'oiseau geant legendaire.', $mount->getLocalizedDescription('es'));
        $this->assertSame('L\'oiseau geant legendaire.', $mount->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $mount = new Mount();
        $mount->setDescription('Une monture commune.');
        $mount->setDescriptionTranslations([
            'en' => 'A common mount.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'A common mount.'], $mount->getDescriptionTranslations());
        $this->assertSame('A common mount.', $mount->getLocalizedDescription('en'));
        $this->assertSame('Une monture commune.', $mount->getLocalizedDescription('de'));
        $this->assertSame('Une monture commune.', $mount->getLocalizedDescription('es'));
        $this->assertSame('Une monture commune.', $mount->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $mount = new Mount();
        $mount->setDescription('Une monture commune.');
        $mount->setDescriptionTranslations(['en' => 'A common mount.']);
        $mount->setDescriptionTranslations(null);

        $this->assertSame([], $mount->getDescriptionTranslations());
        $this->assertSame('Une monture commune.', $mount->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $mount = new Mount();
        $mount->setDescription('Une monture commune.');
        $mount->setDescriptionTranslations(['en' => 'A common mount.']);
        $mount->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $mount->getDescriptionTranslations());
        $this->assertSame('Une monture commune.', $mount->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $mount = new Mount();
        $mount->setDescription('Une monture commune.');

        $this->assertSame([], $mount->getDescriptionTranslations());
    }
}
