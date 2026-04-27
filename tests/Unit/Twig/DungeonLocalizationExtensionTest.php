<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\Dungeon;
use App\Twig\DungeonLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DungeonLocalizationExtensionTest extends TestCase
{
    public function testNameFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $dungeon = (new Dungeon())
            ->setName('Racines de la foret')
            ->setNameTranslations(['en' => 'Roots of the Forest']);

        $extension = new DungeonLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Roots of the Forest', $extension->localizedDungeonName($dungeon));
    }

    public function testNameFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $dungeon = (new Dungeon())
            ->setName('Racines de la foret')
            ->setNameTranslations(['de' => 'Wurzeln des Waldes']);

        $extension = new DungeonLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Racines de la foret', $extension->localizedDungeonName($dungeon));
    }

    public function testNameFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $dungeon = (new Dungeon())
            ->setName('Racines de la foret')
            ->setNameTranslations(['en' => 'Roots of the Forest']);

        $extension = new DungeonLocalizationExtension(new RequestStack());

        $this->assertSame('Racines de la foret', $extension->localizedDungeonName($dungeon));
    }

    public function testNameFilterReturnsEmptyStringForNullDungeon(): void
    {
        $extension = new DungeonLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedDungeonName(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $dungeon = (new Dungeon())
            ->setDescription('Un reseau de galeries souterraines.')
            ->setDescriptionTranslations(['en' => 'A network of underground tunnels.']);

        $extension = new DungeonLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('A network of underground tunnels.', $extension->localizedDungeonDescription($dungeon));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $dungeon = (new Dungeon())
            ->setDescription('Un reseau de galeries souterraines.')
            ->setDescriptionTranslations(['de' => 'Ein Netz unterirdischer Tunnel.']);

        $extension = new DungeonLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Un reseau de galeries souterraines.', $extension->localizedDungeonDescription($dungeon));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenRequestStackIsEmpty(): void
    {
        $dungeon = (new Dungeon())
            ->setDescription('Un reseau de galeries souterraines.')
            ->setDescriptionTranslations(['en' => 'A network of underground tunnels.']);

        $extension = new DungeonLocalizationExtension(new RequestStack());

        $this->assertSame('Un reseau de galeries souterraines.', $extension->localizedDungeonDescription($dungeon));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullDungeon(): void
    {
        $extension = new DungeonLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedDungeonDescription(null));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new DungeonLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_dungeon_name', $filters[0]->getName());
        $this->assertSame('localized_dungeon_description', $filters[1]->getName());
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
