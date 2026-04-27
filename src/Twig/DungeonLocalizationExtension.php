<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\Dungeon;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class DungeonLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_dungeon_name', [$this, 'localizedDungeonName']),
            new TwigFilter('localized_dungeon_description', [$this, 'localizedDungeonDescription']),
        ];
    }

    public function localizedDungeonName(?Dungeon $dungeon): string
    {
        if ($dungeon === null) {
            return '';
        }

        return $dungeon->getLocalizedName($this->currentLocale());
    }

    public function localizedDungeonDescription(?Dungeon $dungeon): string
    {
        if ($dungeon === null) {
            return '';
        }

        return $dungeon->getLocalizedDescription($this->currentLocale());
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
