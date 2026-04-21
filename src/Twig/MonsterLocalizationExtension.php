<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\Monster;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MonsterLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_monster_name', [$this, 'localizedMonsterName']),
        ];
    }

    public function localizedMonsterName(?Monster $monster): string
    {
        if ($monster === null) {
            return '';
        }

        return $monster->getLocalizedName($this->currentLocale());
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
