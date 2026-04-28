<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\FactionReward;
use App\Twig\FactionRewardLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FactionRewardLocalizationExtensionTest extends TestCase
{
    public function testLabelFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $reward = (new FactionReward())
            ->setLabel('Remise marchande')
            ->setLabelTranslations(['en' => 'Merchant Discount']);

        $extension = new FactionRewardLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Merchant Discount', $extension->localizedFactionRewardLabel($reward));
    }

    public function testLabelFilterFallsBackToBaseLabelWhenTranslationMissing(): void
    {
        $reward = (new FactionReward())
            ->setLabel('Remise marchande')
            ->setLabelTranslations(['de' => 'Handelsrabatt']);

        $extension = new FactionRewardLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Remise marchande', $extension->localizedFactionRewardLabel($reward));
    }

    public function testLabelFilterFallsBackToBaseLabelWhenRequestStackIsEmpty(): void
    {
        $reward = (new FactionReward())
            ->setLabel('Remise marchande')
            ->setLabelTranslations(['en' => 'Merchant Discount']);

        $extension = new FactionRewardLocalizationExtension(new RequestStack());

        $this->assertSame('Remise marchande', $extension->localizedFactionRewardLabel($reward));
    }

    public function testLabelFilterReturnsEmptyStringForNullReward(): void
    {
        $extension = new FactionRewardLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedFactionRewardLabel(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $reward = (new FactionReward())
            ->setDescription('Réduction de 10% dans toutes les boutiques.')
            ->setDescriptionTranslations(['en' => '10% discount in all shops.']);

        $extension = new FactionRewardLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('10% discount in all shops.', $extension->localizedFactionRewardDescription($reward));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $reward = (new FactionReward())
            ->setDescription('Réduction de 10% dans toutes les boutiques.')
            ->setDescriptionTranslations(['de' => '10% Rabatt.']);

        $extension = new FactionRewardLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Réduction de 10% dans toutes les boutiques.', $extension->localizedFactionRewardDescription($reward));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenRequestStackIsEmpty(): void
    {
        $reward = (new FactionReward())
            ->setDescription('Réduction de 10% dans toutes les boutiques.')
            ->setDescriptionTranslations(['en' => '10% discount in all shops.']);

        $extension = new FactionRewardLocalizationExtension(new RequestStack());

        $this->assertSame('Réduction de 10% dans toutes les boutiques.', $extension->localizedFactionRewardDescription($reward));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullReward(): void
    {
        $extension = new FactionRewardLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedFactionRewardDescription(null));
    }

    public function testDescriptionFilterReturnsEmptyStringWhenDescriptionIsNull(): void
    {
        $reward = new FactionReward();

        $extension = new FactionRewardLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedFactionRewardDescription($reward));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new FactionRewardLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_faction_reward_label', $filters[0]->getName());
        $this->assertSame('localized_faction_reward_description', $filters[1]->getName());
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
