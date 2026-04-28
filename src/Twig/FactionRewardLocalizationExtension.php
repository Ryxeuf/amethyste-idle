<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\FactionReward;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FactionRewardLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_faction_reward_label', [$this, 'localizedFactionRewardLabel']),
            new TwigFilter('localized_faction_reward_description', [$this, 'localizedFactionRewardDescription']),
        ];
    }

    public function localizedFactionRewardLabel(?FactionReward $reward): string
    {
        if ($reward === null) {
            return '';
        }

        return $reward->getLocalizedLabel($this->currentLocale());
    }

    public function localizedFactionRewardDescription(?FactionReward $reward): string
    {
        if ($reward === null) {
            return '';
        }

        return $reward->getLocalizedDescription($this->currentLocale()) ?? '';
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
