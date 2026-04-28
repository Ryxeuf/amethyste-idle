<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\WeeklyChallenge;
use PHPUnit\Framework\TestCase;

class WeeklyChallengeLocalizationTest extends TestCase
{
    public function testGetLocalizedTitleFallsBackToBaseTitleWhenNoTranslations(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setTitle('Chasseur infatigable');

        $this->assertSame('Chasseur infatigable', $challenge->getLocalizedTitle('en'));
        $this->assertSame('Chasseur infatigable', $challenge->getLocalizedTitle('fr'));
        $this->assertSame('Chasseur infatigable', $challenge->getLocalizedTitle(null));
        $this->assertSame('Chasseur infatigable', $challenge->getLocalizedTitle(''));
    }

    public function testGetLocalizedTitleReturnsMatchingTranslation(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setTitle('Chasseur infatigable');
        $challenge->setTitleTranslations(['en' => 'Tireless Hunter', 'de' => 'Unermuedlicher Jaeger']);

        $this->assertSame('Tireless Hunter', $challenge->getLocalizedTitle('en'));
        $this->assertSame('Unermuedlicher Jaeger', $challenge->getLocalizedTitle('de'));
    }

    public function testGetLocalizedTitleFallsBackWhenLocaleMissing(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setTitle('Forge ardente');
        $challenge->setTitleTranslations(['en' => 'Burning Forge']);

        $this->assertSame('Forge ardente', $challenge->getLocalizedTitle('es'));
        $this->assertSame('Forge ardente', $challenge->getLocalizedTitle('ja'));
    }

    public function testSetTitleTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setTitle('Moisson abondante');
        $challenge->setTitleTranslations([
            'en' => 'Bountiful Harvest',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Bountiful Harvest'], $challenge->getTitleTranslations());
        $this->assertSame('Bountiful Harvest', $challenge->getLocalizedTitle('en'));
        $this->assertSame('Moisson abondante', $challenge->getLocalizedTitle('de'));
        $this->assertSame('Moisson abondante', $challenge->getLocalizedTitle('es'));
        $this->assertSame('Moisson abondante', $challenge->getLocalizedTitle('it'));
    }

    public function testSetTitleTranslationsWithNullResetsStorage(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setTitle('Heros du peuple');
        $challenge->setTitleTranslations(['en' => 'Heroes of the People']);
        $challenge->setTitleTranslations(null);

        $this->assertSame([], $challenge->getTitleTranslations());
        $this->assertSame('Heros du peuple', $challenge->getLocalizedTitle('en'));
    }

    public function testSetTitleTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setTitle('Moisson abondante');
        $challenge->setTitleTranslations(['en' => 'Bountiful Harvest']);
        $challenge->setTitleTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $challenge->getTitleTranslations());
        $this->assertSame('Moisson abondante', $challenge->getLocalizedTitle('en'));
    }

    public function testGetTitleTranslationsDefaultsToEmptyArray(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setTitle('Chasseur infatigable');

        $this->assertSame([], $challenge->getTitleTranslations());
    }

    public function testGetLocalizedDescriptionFallsBackToBaseDescriptionWhenNoTranslations(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setDescription('Eliminez 50 monstres en equipe.');

        $this->assertSame('Eliminez 50 monstres en equipe.', $challenge->getLocalizedDescription('en'));
        $this->assertSame('Eliminez 50 monstres en equipe.', $challenge->getLocalizedDescription('fr'));
        $this->assertSame('Eliminez 50 monstres en equipe.', $challenge->getLocalizedDescription(null));
        $this->assertSame('Eliminez 50 monstres en equipe.', $challenge->getLocalizedDescription(''));
    }

    public function testGetLocalizedDescriptionReturnsMatchingTranslation(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setDescription('Eliminez 50 monstres en equipe.');
        $challenge->setDescriptionTranslations([
            'en' => 'Defeat 50 monsters as a team.',
            'de' => 'Besiegt 50 Monster im Team.',
        ]);

        $this->assertSame('Defeat 50 monsters as a team.', $challenge->getLocalizedDescription('en'));
        $this->assertSame('Besiegt 50 Monster im Team.', $challenge->getLocalizedDescription('de'));
    }

    public function testGetLocalizedDescriptionFallsBackWhenLocaleMissing(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setDescription('Fabriquez 20 objets.');
        $challenge->setDescriptionTranslations(['en' => 'Craft 20 items.']);

        $this->assertSame('Fabriquez 20 objets.', $challenge->getLocalizedDescription('es'));
        $this->assertSame('Fabriquez 20 objets.', $challenge->getLocalizedDescription('ja'));
    }

    public function testSetDescriptionTranslationsIgnoresBlankValuesAndInvalidKeys(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setDescription('Recoltez 100 ressources.');
        $challenge->setDescriptionTranslations([
            'en' => 'Gather 100 resources.',
            'de' => '   ',
            '' => 'Invalid key',
            'es' => '',
            'it' => 42,
        ]);

        $this->assertSame(['en' => 'Gather 100 resources.'], $challenge->getDescriptionTranslations());
        $this->assertSame('Gather 100 resources.', $challenge->getLocalizedDescription('en'));
        $this->assertSame('Recoltez 100 ressources.', $challenge->getLocalizedDescription('de'));
        $this->assertSame('Recoltez 100 ressources.', $challenge->getLocalizedDescription('es'));
        $this->assertSame('Recoltez 100 ressources.', $challenge->getLocalizedDescription('it'));
    }

    public function testSetDescriptionTranslationsWithNullResetsStorage(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setDescription('Completez 10 quetes.');
        $challenge->setDescriptionTranslations(['en' => 'Complete 10 quests.']);
        $challenge->setDescriptionTranslations(null);

        $this->assertSame([], $challenge->getDescriptionTranslations());
        $this->assertSame('Completez 10 quetes.', $challenge->getLocalizedDescription('en'));
    }

    public function testSetDescriptionTranslationsWithOnlyInvalidEntriesResetsToNull(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setDescription('Fabriquez 20 objets.');
        $challenge->setDescriptionTranslations(['en' => 'Craft 20 items.']);
        $challenge->setDescriptionTranslations(['en' => '   ', 'de' => '']);

        $this->assertSame([], $challenge->getDescriptionTranslations());
        $this->assertSame('Fabriquez 20 objets.', $challenge->getLocalizedDescription('en'));
    }

    public function testGetDescriptionTranslationsDefaultsToEmptyArray(): void
    {
        $challenge = new WeeklyChallenge();
        $challenge->setDescription('Eliminez 50 monstres.');

        $this->assertSame([], $challenge->getDescriptionTranslations());
    }
}
