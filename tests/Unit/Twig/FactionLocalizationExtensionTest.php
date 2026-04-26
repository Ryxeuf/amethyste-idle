<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\Faction;
use App\Twig\FactionLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FactionLocalizationExtensionTest extends TestCase
{
    public function testNameFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $faction = (new Faction())
            ->setName('Guilde des Marchands')
            ->setNameTranslations(['en' => 'Merchants Guild']);

        $extension = new FactionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Merchants Guild', $extension->localizedFactionName($faction));
    }

    public function testNameFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $faction = (new Faction())
            ->setName('Guilde des Marchands')
            ->setNameTranslations(['de' => 'Handelsgilde']);

        $extension = new FactionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Guilde des Marchands', $extension->localizedFactionName($faction));
    }

    public function testNameFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $faction = (new Faction())
            ->setName('Guilde des Marchands')
            ->setNameTranslations(['en' => 'Merchants Guild']);

        $extension = new FactionLocalizationExtension(new RequestStack());

        $this->assertSame('Guilde des Marchands', $extension->localizedFactionName($faction));
    }

    public function testNameFilterReturnsEmptyStringForNullFaction(): void
    {
        $extension = new FactionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedFactionName(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $faction = (new Faction())
            ->setDescription('Une guilde commerciale puissante.')
            ->setDescriptionTranslations(['en' => 'A powerful merchant guild.']);

        $extension = new FactionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('A powerful merchant guild.', $extension->localizedFactionDescription($faction));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $faction = (new Faction())
            ->setDescription('Une guilde commerciale puissante.')
            ->setDescriptionTranslations(['de' => 'Eine maechtige Handelsgilde.']);

        $extension = new FactionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Une guilde commerciale puissante.', $extension->localizedFactionDescription($faction));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenRequestStackIsEmpty(): void
    {
        $faction = (new Faction())
            ->setDescription('Une guilde commerciale puissante.')
            ->setDescriptionTranslations(['en' => 'A powerful merchant guild.']);

        $extension = new FactionLocalizationExtension(new RequestStack());

        $this->assertSame('Une guilde commerciale puissante.', $extension->localizedFactionDescription($faction));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullFaction(): void
    {
        $extension = new FactionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedFactionDescription(null));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new FactionLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_faction_name', $filters[0]->getName());
        $this->assertSame('localized_faction_description', $filters[1]->getName());
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
