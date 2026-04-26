<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Faction;
use PHPUnit\Framework\TestCase;

class FactionLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $faction = new Faction();
        $faction->setName('Guilde des Marchands');

        $this->assertSame('Guilde des Marchands', $faction->getLocalizedName('en'));
        $this->assertSame('Guilde des Marchands', $faction->getLocalizedName('fr'));
        $this->assertSame('Guilde des Marchands', $faction->getLocalizedName(null));
        $this->assertSame('Guilde des Marchands', $faction->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $faction = new Faction();
        $faction->setName('Guilde des Marchands');
        $faction->setNameTranslations(['en' => 'Merchants Guild', 'de' => 'Handelsgilde']);

        $this->assertSame('Merchants Guild', $faction->getLocalizedName('en'));
        $this->assertSame('Handelsgilde', $faction->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $faction = new Faction();
        $faction->setName('Ordre des Chevaliers');
        $faction->setNameTranslations(['en' => 'Order of Knights']);

        $this->assertSame('Ordre des Chevaliers', $faction->getLocalizedName('es'));
        $this->assertSame('Ordre des Chevaliers', $faction->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $faction = new Faction();
        $faction->setName('Cercle des Mages');
        $faction->setNameTranslations([
            'en' => 'Circle of Mages',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Circle of Mages'], $faction->getNameTranslations());
        $this->assertSame('Circle of Mages', $faction->getLocalizedName('en'));
        $this->assertSame('Cercle des Mages', $faction->getLocalizedName('de'));
        $this->assertSame('Cercle des Mages', $faction->getLocalizedName('es'));
        $this->assertSame('Cercle des Mages', $faction->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $faction = new Faction();
        $faction->setName('Confrérie des Ombres');
        $faction->setNameTranslations(['en' => 'Brotherhood of Shadows']);
        $faction->setNameTranslations(null);

        $this->assertSame([], $faction->getNameTranslations());
        $this->assertSame('Confrérie des Ombres', $faction->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $faction = new Faction();
        $faction->setName('Cercle des Mages');
        $faction->setNameTranslations(['en' => 'Circle of Mages']);
        $faction->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $faction->getNameTranslations());
        $this->assertSame('Cercle des Mages', $faction->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $faction = new Faction();
        $faction->setName('Guilde des Marchands');

        $this->assertSame([], $faction->getNameTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $faction = new Faction();
        $faction->setDescription('Une guilde commerciale puissante.');

        $this->assertSame('Une guilde commerciale puissante.', $faction->getLocalizedDescription('en'));
        $this->assertSame('Une guilde commerciale puissante.', $faction->getLocalizedDescription('fr'));
        $this->assertSame('Une guilde commerciale puissante.', $faction->getLocalizedDescription(null));
        $this->assertSame('Une guilde commerciale puissante.', $faction->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $faction = new Faction();
        $faction->setDescription('Une guilde commerciale puissante.');
        $faction->setDescriptionTranslations([
            'en' => 'A powerful merchant guild.',
            'de' => 'Eine maechtige Handelsgilde.',
        ]);

        $this->assertSame('A powerful merchant guild.', $faction->getLocalizedDescription('en'));
        $this->assertSame('Eine maechtige Handelsgilde.', $faction->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $faction = new Faction();
        $faction->setDescription('Un ordre militaire prestigieux.');
        $faction->setDescriptionTranslations(['en' => 'A prestigious military order.']);

        $this->assertSame('Un ordre militaire prestigieux.', $faction->getLocalizedDescription('es'));
        $this->assertSame('Un ordre militaire prestigieux.', $faction->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $faction = new Faction();
        $faction->setDescription('Une assemblee de mages.');
        $faction->setDescriptionTranslations([
            'en' => 'An assembly of mages.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'An assembly of mages.'], $faction->getDescriptionTranslations());
        $this->assertSame('An assembly of mages.', $faction->getLocalizedDescription('en'));
        $this->assertSame('Une assemblee de mages.', $faction->getLocalizedDescription('de'));
        $this->assertSame('Une assemblee de mages.', $faction->getLocalizedDescription('es'));
        $this->assertSame('Une assemblee de mages.', $faction->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $faction = new Faction();
        $faction->setDescription('Un reseau clandestin.');
        $faction->setDescriptionTranslations(['en' => 'A clandestine network.']);
        $faction->setDescriptionTranslations(null);

        $this->assertSame([], $faction->getDescriptionTranslations());
        $this->assertSame('Un reseau clandestin.', $faction->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $faction = new Faction();
        $faction->setDescription('Un ordre militaire prestigieux.');
        $faction->setDescriptionTranslations(['en' => 'A prestigious military order.']);
        $faction->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $faction->getDescriptionTranslations());
        $this->assertSame('Un ordre militaire prestigieux.', $faction->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $faction = new Faction();
        $faction->setDescription('Une guilde commerciale puissante.');

        $this->assertSame([], $faction->getDescriptionTranslations());
    }
}
