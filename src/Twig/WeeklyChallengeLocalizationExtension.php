<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\App\WeeklyChallenge;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class WeeklyChallengeLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_challenge_title', [$this, 'localizedChallengeTitle']),
            new TwigFilter('localized_challenge_description', [$this, 'localizedChallengeDescription']),
        ];
    }

    public function localizedChallengeTitle(?WeeklyChallenge $challenge): string
    {
        if ($challenge === null) {
            return '';
        }

        return $challenge->getLocalizedTitle($this->currentLocale());
    }

    public function localizedChallengeDescription(?WeeklyChallenge $challenge): string
    {
        if ($challenge === null) {
            return '';
        }

        return $challenge->getLocalizedDescription($this->currentLocale());
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
