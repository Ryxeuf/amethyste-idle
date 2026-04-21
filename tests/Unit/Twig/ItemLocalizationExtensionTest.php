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

    public function testFilterIsRegistered(): void
    {
        $extension = new ItemLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('localized_name', $filters[0]->getName());
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
