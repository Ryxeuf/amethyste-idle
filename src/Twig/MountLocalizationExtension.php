<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\Mount;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MountLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_mount_name', [$this, 'localizedMountName']),
            new TwigFilter('localized_mount_description', [$this, 'localizedMountDescription']),
        ];
    }

    public function localizedMountName(?Mount $mount): string
    {
        if ($mount === null) {
            return '';
        }

        return $mount->getLocalizedName($this->currentLocale());
    }

    public function localizedMountDescription(?Mount $mount): string
    {
        if ($mount === null) {
            return '';
        }

        return $mount->getLocalizedDescription($this->currentLocale());
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
