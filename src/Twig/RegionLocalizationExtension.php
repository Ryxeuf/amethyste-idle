<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\App\Region;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class RegionLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_region_name', [$this, 'localizedRegionName']),
            new TwigFilter('localized_region_description', [$this, 'localizedRegionDescription']),
        ];
    }

    public function localizedRegionName(?Region $region): string
    {
        if ($region === null) {
            return '';
        }

        return $region->getLocalizedName($this->currentLocale());
    }

    public function localizedRegionDescription(?Region $region): string
    {
        if ($region === null) {
            return '';
        }

        return $region->getLocalizedDescription($this->currentLocale()) ?? '';
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
