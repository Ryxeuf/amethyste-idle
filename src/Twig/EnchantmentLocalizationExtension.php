<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\EnchantmentDefinition;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class EnchantmentLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_enchantment_name', [$this, 'localizedEnchantmentName']),
            new TwigFilter('localized_enchantment_description', [$this, 'localizedEnchantmentDescription']),
        ];
    }

    public function localizedEnchantmentName(?EnchantmentDefinition $definition): string
    {
        if ($definition === null) {
            return '';
        }

        return $definition->getLocalizedName($this->currentLocale());
    }

    public function localizedEnchantmentDescription(?EnchantmentDefinition $definition): string
    {
        if ($definition === null) {
            return '';
        }

        return $definition->getLocalizedDescription($this->currentLocale()) ?? '';
    }

    private function currentLocale(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        $locale = $request->getLocale();

        return $locale === '' ? null : $locale;
    }
}
