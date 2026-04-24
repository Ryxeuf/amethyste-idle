<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Achievement;
use PHPUnit\Framework\TestCase;

class AchievementLocalizationTest extends TestCase
{
    public function testGetLocalizedTitleFallsBackToBaseTitleWhenNoTranslations(): void
    {
        $achievement = new Achievement();
        $achievement->setTitle('Nettoyeur de gelees');

        $this->assertSame('Nettoyeur de gelees', $achievement->getLocalizedTitle('en'));
        $this->assertSame('Nettoyeur de gelees', $achievement->getLocalizedTitle('fr'));
        $this->assertSame('Nettoyeur de gelees', $achievement->getLocalizedTitle(null));
        $this->assertSame('Nettoyeur de gelees', $achievement->getLocalizedTitle(''));
    }

    public function testGetLocalizedTitleReturnsMatchingTranslation(): void
    {
        $achievement = new Achievement();
        $achievement->setTitle('Nettoyeur de gelees');
        $achievement->setTitleTranslations(['en' => 'Slime Cleaner', 'de' => 'Schleimreiniger']);

        $this->assertSame('Slime Cleaner', $achievement->getLocalizedTitle('en'));
        $this->assertSame('Schleimreiniger', $achievement->getLocalizedTitle('de'));
    }

    public function testGetLocalizedTitleFallsBackWhenLocaleMissing(): void
    {
        $achievement = new Achievement();
        $achievement->setTitle('Chasseur de gobelins');
        $achievement->setTitleTranslations(['en' => 'Goblin Hunter']);

        $this->assertSame('Chasseur de gobelins', $achievement->getLocalizedTitle('es'));
        $this->assertSame('Chasseur de gobelins', $achievement->getLocalizedTitle('ja'));
    }

    public function testSetTitleTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $achievement = new Achievement();
        $achievement->setTitle('Exterminateur');
        $achievement->setTitleTranslations([
            'en' => 'Exterminator',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Exterminator'], $achievement->getTitleTranslations());
        $this->assertSame('Exterminator', $achievement->getLocalizedTitle('en'));
        $this->assertSame('Exterminateur', $achievement->getLocalizedTitle('de'));
        $this->assertSame('Exterminateur', $achievement->getLocalizedTitle('es'));
        $this->assertSame('Exterminateur', $achievement->getLocalizedTitle('it'));
    }

    public function testSetTitleTranslationsWithNullResetsStorage(): void
    {
        $achievement = new Achievement();
        $achievement->setTitle('Arachnophobe');
        $achievement->setTitleTranslations(['en' => 'Arachnophobe']);
        $achievement->setTitleTranslations(null);

        $this->assertSame([], $achievement->getTitleTranslations());
        $this->assertSame('Arachnophobe', $achievement->getLocalizedTitle('en'));
    }

    public function testSetTitleTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $achievement = new Achievement();
        $achievement->setTitle('Tueur de dragons');
        $achievement->setTitleTranslations(['en' => 'Dragon Slayer']);
        $achievement->setTitleTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $achievement->getTitleTranslations());
        $this->assertSame('Tueur de dragons', $achievement->getLocalizedTitle('en'));
    }

    public function testGetTitleTranslationsDefaultsToEmptyArray(): void
    {
        $achievement = new Achievement();
        $achievement->setTitle('Exorciste');

        $this->assertSame([], $achievement->getTitleTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $achievement = new Achievement();
        $achievement->setDescription('Tuer 10 gelees');

        $this->assertSame('Tuer 10 gelees', $achievement->getLocalizedDescription('en'));
        $this->assertSame('Tuer 10 gelees', $achievement->getLocalizedDescription('fr'));
        $this->assertSame('Tuer 10 gelees', $achievement->getLocalizedDescription(null));
        $this->assertSame('Tuer 10 gelees', $achievement->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $achievement = new Achievement();
        $achievement->setDescription('Tuer 10 gelees');
        $achievement->setDescriptionTranslations(['en' => 'Kill 10 slimes', 'de' => '10 Schleime toeten']);

        $this->assertSame('Kill 10 slimes', $achievement->getLocalizedDescription('en'));
        $this->assertSame('10 Schleime toeten', $achievement->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $achievement = new Achievement();
        $achievement->setDescription('Tuer 50 gobelins');
        $achievement->setDescriptionTranslations(['en' => 'Kill 50 goblins']);

        $this->assertSame('Tuer 50 gobelins', $achievement->getLocalizedDescription('es'));
        $this->assertSame('Tuer 50 gobelins', $achievement->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $achievement = new Achievement();
        $achievement->setDescription('Vaincre le boss final');
        $achievement->setDescriptionTranslations([
            'en' => 'Defeat the final boss',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Defeat the final boss'], $achievement->getDescriptionTranslations());
        $this->assertSame('Defeat the final boss', $achievement->getLocalizedDescription('en'));
        $this->assertSame('Vaincre le boss final', $achievement->getLocalizedDescription('de'));
        $this->assertSame('Vaincre le boss final', $achievement->getLocalizedDescription('es'));
        $this->assertSame('Vaincre le boss final', $achievement->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $achievement = new Achievement();
        $achievement->setDescription('Collecter 100 herbes');
        $achievement->setDescriptionTranslations(['en' => 'Collect 100 herbs']);
        $achievement->setDescriptionTranslations(null);

        $this->assertSame([], $achievement->getDescriptionTranslations());
        $this->assertSame('Collecter 100 herbes', $achievement->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $achievement = new Achievement();
        $achievement->setDescription('Explorer toutes les zones');
        $achievement->setDescriptionTranslations(['en' => 'Explore all zones']);
        $achievement->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $achievement->getDescriptionTranslations());
        $this->assertSame('Explorer toutes les zones', $achievement->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $achievement = new Achievement();
        $achievement->setDescription('Atteindre le niveau 10 dans un domaine');

        $this->assertSame([], $achievement->getDescriptionTranslations());
    }
}
