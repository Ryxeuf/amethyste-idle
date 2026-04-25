<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\Race;
use App\Twig\RaceLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RaceLocalizationExtensionTest extends TestCase
{
    public function testNameFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $race = (new Race())
            ->setName('Humain')
            ->setNameTranslations(['en' => 'Human']);

        $extension = new RaceLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Human', $extension->localizedRaceName($race));
    }

    public function testNameFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $race = (new Race())
            ->setName('Humain')
            ->setNameTranslations(['de' => 'Mensch']);

        $extension = new RaceLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Humain', $extension->localizedRaceName($race));
    }

    public function testNameFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $race = (new Race())
            ->setName('Humain')
            ->setNameTranslations(['en' => 'Human']);

        $extension = new RaceLocalizationExtension(new RequestStack());

        $this->assertSame('Humain', $extension->localizedRaceName($race));
    }

    public function testNameFilterReturnsEmptyStringForNullRace(): void
    {
        $extension = new RaceLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedRaceName(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $race = (new Race())
            ->setDescription('Polyvalent et adaptable.')
            ->setDescriptionTranslations(['en' => 'Versatile and adaptable.']);

        $extension = new RaceLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Versatile and adaptable.', $extension->localizedRaceDescription($race));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $race = (new Race())
            ->setDescription('Polyvalent et adaptable.')
            ->setDescriptionTranslations(['de' => 'Vielseitig und anpassungsfaehig.']);

        $extension = new RaceLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Polyvalent et adaptable.', $extension->localizedRaceDescription($race));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenRequestStackIsEmpty(): void
    {
        $race = (new Race())
            ->setDescription('Polyvalent et adaptable.')
            ->setDescriptionTranslations(['en' => 'Versatile and adaptable.']);

        $extension = new RaceLocalizationExtension(new RequestStack());

        $this->assertSame('Polyvalent et adaptable.', $extension->localizedRaceDescription($race));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullRace(): void
    {
        $extension = new RaceLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedRaceDescription(null));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new RaceLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_race_name', $filters[0]->getName());
        $this->assertSame('localized_race_description', $filters[1]->getName());
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
