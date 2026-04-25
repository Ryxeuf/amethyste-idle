<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\StatusEffect;
use App\Twig\StatusEffectLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class StatusEffectLocalizationExtensionTest extends TestCase
{
    public function testFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Poison');
        $effect->setNameTranslations(['en' => 'Poison (EN)']);

        $extension = new StatusEffectLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Poison (EN)', $extension->localizedStatusEffectName($effect));
    }

    public function testFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Brulure');
        $effect->setNameTranslations(['de' => 'Verbrennung']);

        $extension = new StatusEffectLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Brulure', $extension->localizedStatusEffectName($effect));
    }

    public function testFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $effect = new StatusEffect();
        $effect->setName('Bouclier');
        $effect->setNameTranslations(['en' => 'Shield']);

        $extension = new StatusEffectLocalizationExtension(new RequestStack());

        $this->assertSame('Bouclier', $extension->localizedStatusEffectName($effect));
    }

    public function testFilterReturnsEmptyStringForNullEffect(): void
    {
        $extension = new StatusEffectLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedStatusEffectName(null));
    }

    public function testFilterIsRegistered(): void
    {
        $extension = new StatusEffectLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('localized_status_effect_name', $filters[0]->getName());
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
