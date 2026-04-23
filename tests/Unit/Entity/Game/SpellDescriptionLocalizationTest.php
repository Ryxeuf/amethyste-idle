<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Spell;
use PHPUnit\Framework\TestCase;

class SpellDescriptionLocalizationTest extends TestCase
{
    public function testGetLocalizedDescriptionFallsBackToBaseWhenNoTranslations(): void
    {
        $spell = new Spell();
        $spell->setDescription('Inflige des degats de feu a une cible.');

        $this->assertSame('Inflige des degats de feu a une cible.', $spell->getLocalizedDescription('en'));
        $this->assertSame('Inflige des degats de feu a une cible.', $spell->getLocalizedDescription('fr'));
        $this->assertSame('Inflige des degats de feu a une cible.', $spell->getLocalizedDescription(null));
        $this->assertSame('Inflige des degats de feu a une cible.', $spell->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $spell = new Spell();
        $spell->setDescription('Inflige des degats de feu a une cible.');
        $spell->setDescriptionTranslations([
            'en' => 'Deals fire damage to one target.',
            'de' => 'Fugt einem Ziel Feuerschaden zu.',
        ]);

        $this->assertSame('Deals fire damage to one target.', $spell->getLocalizedDescription('en'));
        $this->assertSame('Fugt einem Ziel Feuerschaden zu.', $spell->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $spell = new Spell();
        $spell->setDescription('Soigne un allie pour 30 points de vie.');
        $spell->setDescriptionTranslations(['en' => 'Heals one ally for 30 HP.']);

        $this->assertSame('Soigne un allie pour 30 points de vie.', $spell->getLocalizedDescription('es'));
        $this->assertSame('Soigne un allie pour 30 points de vie.', $spell->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $spell = new Spell();
        $spell->setDescription('Invoque une rafale de vent.');
        $spell->setDescriptionTranslations([
            'en' => 'Summons a gust of wind.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Summons a gust of wind.'], $spell->getDescriptionTranslations());
        $this->assertSame('Summons a gust of wind.', $spell->getLocalizedDescription('en'));
        $this->assertSame('Invoque une rafale de vent.', $spell->getLocalizedDescription('de'));
        $this->assertSame('Invoque une rafale de vent.', $spell->getLocalizedDescription('es'));
        $this->assertSame('Invoque une rafale de vent.', $spell->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $spell = new Spell();
        $spell->setDescription('Paralyse la cible pendant deux tours.');
        $spell->setDescriptionTranslations(['en' => 'Paralyzes the target for two turns.']);
        $spell->setDescriptionTranslations(null);

        $this->assertSame([], $spell->getDescriptionTranslations());
        $this->assertSame('Paralyse la cible pendant deux tours.', $spell->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $spell = new Spell();
        $spell->setDescription('Draine la vie d\'une cible.');
        $spell->setDescriptionTranslations(['en' => 'Drains the life of a target.']);
        $spell->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $spell->getDescriptionTranslations());
        $this->assertSame('Draine la vie d\'une cible.', $spell->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $spell = new Spell();
        $spell->setDescription('Foudroie tous les ennemis en ligne.');

        $this->assertSame([], $spell->getDescriptionTranslations());
    }
}
