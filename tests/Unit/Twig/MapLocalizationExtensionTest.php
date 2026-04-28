<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\App\Map;
use App\Twig\MapLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class MapLocalizationExtensionTest extends TestCase
{
    public function testNameFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $map = new Map();
        $map->setName('Foret des murmures');
        $map->setNameTranslations(['en' => 'Whispering Forest']);

        $extension = new MapLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Whispering Forest', $extension->localizedMapName($map));
    }

    public function testNameFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $map = new Map();
        $map->setName('Foret des murmures');
        $map->setNameTranslations(['de' => 'Fluesterwald']);

        $extension = new MapLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Foret des murmures', $extension->localizedMapName($map));
    }

    public function testNameFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $map = new Map();
        $map->setName('Foret des murmures');
        $map->setNameTranslations(['en' => 'Whispering Forest']);

        $extension = new MapLocalizationExtension(new RequestStack());

        $this->assertSame('Foret des murmures', $extension->localizedMapName($map));
    }

    public function testNameFilterReturnsEmptyStringForNullMap(): void
    {
        $extension = new MapLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedMapName(null));
    }

    public function testFilterIsRegistered(): void
    {
        $extension = new MapLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('localized_map_name', $filters[0]->getName());
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
