<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\Achievement;
use App\Twig\AchievementLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AchievementLocalizationExtensionTest extends TestCase
{
    public function testTitleFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $achievement = (new Achievement())
            ->setTitle('Tueur de slimes')
            ->setTitleTranslations(['en' => 'Slime Slayer']);

        $extension = new AchievementLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Slime Slayer', $extension->localizedAchievementTitle($achievement));
    }

    public function testTitleFilterFallsBackToBaseTitleWhenTranslationMissing(): void
    {
        $achievement = (new Achievement())
            ->setTitle('Tueur de slimes')
            ->setTitleTranslations(['de' => 'Schleimtoter']);

        $extension = new AchievementLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Tueur de slimes', $extension->localizedAchievementTitle($achievement));
    }

    public function testTitleFilterFallsBackToBaseTitleWhenRequestStackIsEmpty(): void
    {
        $achievement = (new Achievement())
            ->setTitle('Tueur de slimes')
            ->setTitleTranslations(['en' => 'Slime Slayer']);

        $extension = new AchievementLocalizationExtension(new RequestStack());

        $this->assertSame('Tueur de slimes', $extension->localizedAchievementTitle($achievement));
    }

    public function testTitleFilterReturnsEmptyStringForNullAchievement(): void
    {
        $extension = new AchievementLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedAchievementTitle(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $achievement = (new Achievement())
            ->setDescription('Tuez 10 slimes en combat.')
            ->setDescriptionTranslations(['en' => 'Defeat 10 slimes in combat.']);

        $extension = new AchievementLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Defeat 10 slimes in combat.', $extension->localizedAchievementDescription($achievement));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $achievement = (new Achievement())
            ->setDescription('Tuez 10 slimes en combat.')
            ->setDescriptionTranslations(['de' => 'Besiege 10 Schleime im Kampf.']);

        $extension = new AchievementLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Tuez 10 slimes en combat.', $extension->localizedAchievementDescription($achievement));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenRequestStackIsEmpty(): void
    {
        $achievement = (new Achievement())
            ->setDescription('Tuez 10 slimes en combat.')
            ->setDescriptionTranslations(['en' => 'Defeat 10 slimes in combat.']);

        $extension = new AchievementLocalizationExtension(new RequestStack());

        $this->assertSame('Tuez 10 slimes en combat.', $extension->localizedAchievementDescription($achievement));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullAchievement(): void
    {
        $extension = new AchievementLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedAchievementDescription(null));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new AchievementLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_achievement_title', $filters[0]->getName());
        $this->assertSame('localized_achievement_description', $filters[1]->getName());
    }

    private function stackWithLocale(string $locale): RequestStack
    {
        $request = Request::create('/');
        $request->setLocale($locale);

        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }
}
