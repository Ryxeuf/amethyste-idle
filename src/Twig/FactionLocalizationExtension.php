<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\Faction;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FactionLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_faction_name', [$this, 'localizedFactionName']),
            new TwigFilter('localized_faction_description', [$this, 'localizedFactionDescription']),
        ];
    }

    public function localizedFactionName(?Faction $faction): string
    {
        if ($faction === null) {
            return '';
        }

        return $faction->getLocalizedName($this->currentLocale());
    }

    public function localizedFactionDescription(?Faction $faction): string
    {
        if ($faction === null) {
            return '';
        }

        return $faction->getLocalizedDescription($this->currentLocale());
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
