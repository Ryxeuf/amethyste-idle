<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Game\Recipe;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class RecipeLocalizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_recipe_name', [$this, 'localizedRecipeName']),
            new TwigFilter('localized_recipe_description', [$this, 'localizedRecipeDescription']),
        ];
    }

    public function localizedRecipeName(?Recipe $recipe): string
    {
        if ($recipe === null) {
            return '';
        }

        return $recipe->getLocalizedName($this->currentLocale());
    }

    public function localizedRecipeDescription(?Recipe $recipe): string
    {
        if ($recipe === null) {
            return '';
        }

        return $recipe->getLocalizedDescription($this->currentLocale()) ?? '';
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
