<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\Quest;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class QuestLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_quest_name', [$this, 'localizedQuestName']),
            new TwigFilter('localized_quest_description', [$this, 'localizedQuestDescription']),
        ];
    }

    public function localizedQuestName(?Quest $quest): string
    {
        if ($quest === null) {
            return '';
        }

        return $quest->getLocalizedName($this->currentLocale());
    }

    public function localizedQuestDescription(?Quest $quest): string
    {
        if ($quest === null) {
            return '';
        }

        return $quest->getLocalizedDescription($this->currentLocale());
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
