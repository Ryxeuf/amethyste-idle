<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Quest;
use PHPUnit\Framework\TestCase;

class QuestLocalizationTest extends TestCase
{
    public function testGetLocalizedNameFallsBackToBaseNameWhenNoTranslations(): void
    {
        $quest = new Quest();
        $quest->setName('La lame brisee');

        $this->assertSame('La lame brisee', $quest->getLocalizedName('en'));
        $this->assertSame('La lame brisee', $quest->getLocalizedName('fr'));
        $this->assertSame('La lame brisee', $quest->getLocalizedName(null));
        $this->assertSame('La lame brisee', $quest->getLocalizedName(''));
    }

    public function testGetLocalizedNameReturnsMatchingTranslation(): void
    {
        $quest = new Quest();
        $quest->setName('La lame brisee');
        $quest->setNameTranslations(['en' => 'The broken blade', 'de' => 'Die zerbrochene Klinge']);

        $this->assertSame('The broken blade', $quest->getLocalizedName('en'));
        $this->assertSame('Die zerbrochene Klinge', $quest->getLocalizedName('de'));
    }

    public function testGetLocalizedNameFallsBackWhenLocaleMissing(): void
    {
        $quest = new Quest();
        $quest->setName('Le secret du mage');
        $quest->setNameTranslations(['en' => "The mage's secret"]);

        $this->assertSame('Le secret du mage', $quest->getLocalizedName('es'));
        $this->assertSame('Le secret du mage', $quest->getLocalizedName('ja'));
    }

    public function testSetNameTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $quest = new Quest();
        $quest->setName('La chasse au gobelin');
        $quest->setNameTranslations([
            'en' => 'The goblin hunt',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'The goblin hunt'], $quest->getNameTranslations());
        $this->assertSame('The goblin hunt', $quest->getLocalizedName('en'));
        $this->assertSame('La chasse au gobelin', $quest->getLocalizedName('de'));
        $this->assertSame('La chasse au gobelin', $quest->getLocalizedName('es'));
        $this->assertSame('La chasse au gobelin', $quest->getLocalizedName('it'));
    }

    public function testSetNameTranslationsWithNullResetsStorage(): void
    {
        $quest = new Quest();
        $quest->setName('Retour au village');
        $quest->setNameTranslations(['en' => 'Return to the village']);
        $quest->setNameTranslations(null);

        $this->assertSame([], $quest->getNameTranslations());
        $this->assertSame('Retour au village', $quest->getLocalizedName('en'));
    }

    public function testSetNameTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $quest = new Quest();
        $quest->setName('La tour hantee');
        $quest->setNameTranslations(['en' => 'The haunted tower']);
        $quest->setNameTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $quest->getNameTranslations());
        $this->assertSame('La tour hantee', $quest->getLocalizedName('en'));
    }

    public function testGetNameTranslationsDefaultsToEmptyArray(): void
    {
        $quest = new Quest();
        $quest->setName('Une vielle legende');

        $this->assertSame([], $quest->getNameTranslations());
    }
}
