<?php

declare(strict_types=1);

namespace App\Twig;

use App\Dto\Domain\DomainModel;
use App\Entity\Game\Domain;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class DomainLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_domain_title', [$this, 'localizedDomainTitle']),
        ];
    }

    public function localizedDomainTitle(Domain|DomainModel|null $domain): string
    {
        if ($domain === null) {
            return '';
        }

        $entity = $domain instanceof DomainModel ? $domain->entity : $domain;

        return $entity->getLocalizedTitle($this->currentLocale());
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
