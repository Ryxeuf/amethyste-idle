<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\Item;
use App\Twig\ItemLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ItemLocalizationExtensionTest extends TestCase
{
    public function testFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $item = (new Item())
            ->setName('Epee en fer')
            ->setNameTranslations(['en' => 'Iron sword']);

        $extension = new ItemLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Iron sword', $extension->localizedName($item));
    }

    public function testFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $item = (new Item())
            ->setName('Epee en fer')
            ->setNameTranslations(['de' => 'Eisenschwert']);

        $extension = new ItemLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Epee en fer', $extension->localizedName($item));
    }

    public function testFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $item = (new Item())
            ->setName('Epee en fer')
            ->setNameTranslations(['en' => 'Iron sword']);

        $extension = new ItemLocalizationExtension(new RequestStack());

        $this->assertSame('Epee en fer', $extension->localizedName($item));
    }

    public function testFilterReturnsEmptyStringForNullItem(): void
    {
        $extension = new ItemLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedName(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $item = new Item();
        $item->setDescription('Epee forgee en acier trempe.');
        $item->setDescriptionTranslations(['en' => 'A sword forged from tempered steel.']);

        $extension = new ItemLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('A sword forged from tempered steel.', $extension->localizedDescription($item));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $item = new Item();
        $item->setDescription('Epee forgee en acier trempe.');
        $item->setDescriptionTranslations(['de' => 'Ein aus Stahl geschmiedetes Schwert.']);

        $extension = new ItemLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Epee forgee en acier trempe.', $extension->localizedDescription($item));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullItem(): void
    {
        $extension = new ItemLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedDescription(null));
    }

    public function testFilterIsRegistered(): void
    {
        $extension = new ItemLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $filterNames = array_map(static fn ($filter) => $filter->getName(), $filters);
        $this->assertContains('localized_name', $filterNames);
        $this->assertContains('localized_description', $filterNames);
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
