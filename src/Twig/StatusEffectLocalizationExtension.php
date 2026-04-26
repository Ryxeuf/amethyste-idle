<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\StatusEffect;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class StatusEffectLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_status_effect_name', [$this, 'localizedStatusEffectName']),
        ];
    }

    public function localizedStatusEffectName(?StatusEffect $effect): string
    {
        if ($effect === null) {
            return '';
        }

        return $effect->getLocalizedName($this->currentLocale());
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
