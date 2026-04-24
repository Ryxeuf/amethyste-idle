<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\Achievement;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class AchievementLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_achievement_title', [$this, 'localizedAchievementTitle']),
            new TwigFilter('localized_achievement_description', [$this, 'localizedAchievementDescription']),
        ];
    }

    public function localizedAchievementTitle(?Achievement $achievement): string
    {
        if ($achievement === null) {
            return '';
        }

        return $achievement->getLocalizedTitle($this->currentLocale());
    }

    public function localizedAchievementDescription(?Achievement $achievement): string
    {
        if ($achievement === null) {
            return '';
        }

        return $achievement->getLocalizedDescription($this->currentLocale());
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
