<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\App\Region;
use App\Twig\RegionLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RegionLocalizationExtensionTest extends TestCase
{
    public function testNameFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $region = (new Region())
            ->setName('Plaines de l\'Eveil')
            ->setNameTranslations(['en' => 'Plains of Awakening']);

        $extension = new RegionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Plains of Awakening', $extension->localizedRegionName($region));
    }

    public function testNameFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $region = (new Region())
            ->setName('Plaines de l\'Eveil')
            ->setNameTranslations(['de' => 'Ebenen des Erwachens']);

        $extension = new RegionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Plaines de l\'Eveil', $extension->localizedRegionName($region));
    }

    public function testNameFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $region = (new Region())
            ->setName('Plaines de l\'Eveil')
            ->setNameTranslations(['en' => 'Plains of Awakening']);

        $extension = new RegionLocalizationExtension(new RequestStack());

        $this->assertSame('Plaines de l\'Eveil', $extension->localizedRegionName($region));
    }

    public function testNameFilterReturnsEmptyStringForNullRegion(): void
    {
        $extension = new RegionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedRegionName(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $region = (new Region())
            ->setDescription('Vastes plaines verdoyantes.')
            ->setDescriptionTranslations(['en' => 'Vast green plains.']);

        $extension = new RegionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Vast green plains.', $extension->localizedRegionDescription($region));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $region = (new Region())
            ->setDescription('Vastes plaines verdoyantes.')
            ->setDescriptionTranslations(['de' => 'Weite gruene Ebenen.']);

        $extension = new RegionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Vastes plaines verdoyantes.', $extension->localizedRegionDescription($region));
    }

    public function testDescriptionFilterReturnsEmptyStringWhenBaseDescriptionIsNull(): void
    {
        $region = (new Region())->setName('Region sans description');

        $extension = new RegionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedRegionDescription($region));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullRegion(): void
    {
        $extension = new RegionLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedRegionDescription(null));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new RegionLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_region_name', $filters[0]->getName());
        $this->assertSame('localized_region_description', $filters[1]->getName());
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
