<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\EnchantmentDefinition;
use App\Twig\EnchantmentLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EnchantmentLocalizationExtensionTest extends TestCase
{
    public function testNameFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setName('Tranchant de feu');
        $definition->setNameTranslations(['en' => 'Flame Edge']);

        $extension = new EnchantmentLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Flame Edge', $extension->localizedEnchantmentName($definition));
    }

    public function testNameFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setName('Tranchant de feu');
        $definition->setNameTranslations(['de' => 'Flammenklinge']);

        $extension = new EnchantmentLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Tranchant de feu', $extension->localizedEnchantmentName($definition));
    }

    public function testNameFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setName('Tranchant de feu');
        $definition->setNameTranslations(['en' => 'Flame Edge']);

        $extension = new EnchantmentLocalizationExtension(new RequestStack());

        $this->assertSame('Tranchant de feu', $extension->localizedEnchantmentName($definition));
    }

    public function testNameFilterReturnsEmptyStringForNullDefinition(): void
    {
        $extension = new EnchantmentLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedEnchantmentName(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $definition = new EnchantmentDefinition();
        $definition->setDescription('Imprègne l\'arme d\'une flamme.');
        $definition->setDescriptionTranslations(['en' => 'Imbues the weapon with flame.']);

        $extension = new EnchantmentLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Imbues the weapon with flame.', $extension->localizedEnchantmentDescription($definition));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullDefinitionOrNullBase(): void
    {
        $extension = new EnchantmentLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedEnchantmentDescription(null));

        $definition = new EnchantmentDefinition();
        $this->assertSame('', $extension->localizedEnchantmentDescription($definition));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new EnchantmentLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_enchantment_name', $filters[0]->getName());
        $this->assertSame('localized_enchantment_description', $filters[1]->getName());
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
