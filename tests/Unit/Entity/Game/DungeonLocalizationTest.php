<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Dungeon;
use PHPUnit\Framework\TestCase;

class DungeonLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Racines de la foret');

        $this->assertSame('Racines de la foret', $dungeon->getLocalizedName('en'));
        $this->assertSame('Racines de la foret', $dungeon->getLocalizedName('fr'));
        $this->assertSame('Racines de la foret', $dungeon->getLocalizedName(null));
        $this->assertSame('Racines de la foret', $dungeon->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Racines de la foret');
        $dungeon->setNameTranslations(['en' => 'Roots of the Forest', 'de' => 'Wurzeln des Waldes']);

        $this->assertSame('Roots of the Forest', $dungeon->getLocalizedName('en'));
        $this->assertSame('Wurzeln des Waldes', $dungeon->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Le Nexus de la Convergence');
        $dungeon->setNameTranslations(['en' => 'The Nexus of Convergence']);

        $this->assertSame('Le Nexus de la Convergence', $dungeon->getLocalizedName('es'));
        $this->assertSame('Le Nexus de la Convergence', $dungeon->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Racines de la foret');
        $dungeon->setNameTranslations([
            'en' => 'Roots of the Forest',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Roots of the Forest'], $dungeon->getNameTranslations());
        $this->assertSame('Roots of the Forest', $dungeon->getLocalizedName('en'));
        $this->assertSame('Racines de la foret', $dungeon->getLocalizedName('de'));
        $this->assertSame('Racines de la foret', $dungeon->getLocalizedName('es'));
        $this->assertSame('Racines de la foret', $dungeon->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Racines de la foret');
        $dungeon->setNameTranslations(['en' => 'Roots of the Forest']);
        $dungeon->setNameTranslations(null);

        $this->assertSame([], $dungeon->getNameTranslations());
        $this->assertSame('Racines de la foret', $dungeon->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Racines de la foret');
        $dungeon->setNameTranslations(['en' => 'Roots of the Forest']);
        $dungeon->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $dungeon->getNameTranslations());
        $this->assertSame('Racines de la foret', $dungeon->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Racines de la foret');

        $this->assertSame([], $dungeon->getNameTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setDescription('Un reseau de galeries souterraines.');

        $this->assertSame('Un reseau de galeries souterraines.', $dungeon->getLocalizedDescription('en'));
        $this->assertSame('Un reseau de galeries souterraines.', $dungeon->getLocalizedDescription('fr'));
        $this->assertSame('Un reseau de galeries souterraines.', $dungeon->getLocalizedDescription(null));
        $this->assertSame('Un reseau de galeries souterraines.', $dungeon->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setDescription('Un reseau de galeries souterraines.');
        $dungeon->setDescriptionTranslations([
            'en' => 'A network of underground tunnels.',
            'de' => 'Ein Netz unterirdischer Tunnel.',
        ]);

        $this->assertSame('A network of underground tunnels.', $dungeon->getLocalizedDescription('en'));
        $this->assertSame('Ein Netz unterirdischer Tunnel.', $dungeon->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setDescription('Le coeur du cristal d\'Amethyste bat au plus profond.');
        $dungeon->setDescriptionTranslations(['en' => 'The heart of the Amethyst crystal beats deep within.']);

        $this->assertSame('Le coeur du cristal d\'Amethyste bat au plus profond.', $dungeon->getLocalizedDescription('es'));
        $this->assertSame('Le coeur du cristal d\'Amethyste bat au plus profond.', $dungeon->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setDescription('Un reseau de galeries souterraines.');
        $dungeon->setDescriptionTranslations([
            'en' => 'A network of underground tunnels.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'A network of underground tunnels.'], $dungeon->getDescriptionTranslations());
        $this->assertSame('A network of underground tunnels.', $dungeon->getLocalizedDescription('en'));
        $this->assertSame('Un reseau de galeries souterraines.', $dungeon->getLocalizedDescription('de'));
        $this->assertSame('Un reseau de galeries souterraines.', $dungeon->getLocalizedDescription('es'));
        $this->assertSame('Un reseau de galeries souterraines.', $dungeon->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setDescription('Un reseau de galeries souterraines.');
        $dungeon->setDescriptionTranslations(['en' => 'A network of underground tunnels.']);
        $dungeon->setDescriptionTranslations(null);

        $this->assertSame([], $dungeon->getDescriptionTranslations());
        $this->assertSame('Un reseau de galeries souterraines.', $dungeon->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setDescription('Un reseau de galeries souterraines.');
        $dungeon->setDescriptionTranslations(['en' => 'A network of underground tunnels.']);
        $dungeon->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $dungeon->getDescriptionTranslations());
        $this->assertSame('Un reseau de galeries souterraines.', $dungeon->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $dungeon = new Dungeon();
        $dungeon->setDescription('Un reseau de galeries souterraines.');

        $this->assertSame([], $dungeon->getDescriptionTranslations());
    }
}
