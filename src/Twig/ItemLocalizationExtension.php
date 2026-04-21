<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\Item;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ItemLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_name', [$this, 'localizedName']),
            new TwigFilter('localized_description', [$this, 'localizedDescription']),
        ];
    }

    public function localizedName(?Item $item): string
    {
        if ($item === null) {
            return '';
        }

        return $item->getLocalizedName($this->currentLocale());
    }

    public function localizedDescription(?Item $item): string
    {
        if ($item === null) {
            return '';
        }

        return $item->getLocalizedDescription($this->currentLocale());
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
