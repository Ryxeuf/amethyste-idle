<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\Race;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class RaceLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_race_name', [$this, 'localizedRaceName']),
            new TwigFilter('localized_race_description', [$this, 'localizedRaceDescription']),
        ];
    }

    public function localizedRaceName(?Race $race): string
    {
        if ($race === null) {
            return '';
        }

        return $race->getLocalizedName($this->currentLocale());
    }

    public function localizedRaceDescription(?Race $race): string
    {
        if ($race === null) {
            return '';
        }

        return $race->getLocalizedDescription($this->currentLocale());
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
