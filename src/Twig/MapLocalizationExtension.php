<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\App\Map;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MapLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_map_name', [$this, 'localizedMapName']),
        ];
    }

    public function localizedMapName(?Map $map): string
    {
        if ($map === null) {
            return '';
        }

        return $map->getLocalizedName($this->currentLocale());
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
