<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\EquipmentSet;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class EquipmentSetLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_equipment_set_name', [$this, 'localizedEquipmentSetName']),
            new TwigFilter('localized_equipment_set_description', [$this, 'localizedEquipmentSetDescription']),
        ];
    }

    public function localizedEquipmentSetName(?EquipmentSet $set): string
    {
        if ($set === null) {
            return '';
        }

        return $set->getLocalizedName($this->currentLocale());
    }

    public function localizedEquipmentSetDescription(?EquipmentSet $set): string
    {
        if ($set === null) {
            return '';
        }

        return $set->getLocalizedDescription($this->currentLocale());
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
