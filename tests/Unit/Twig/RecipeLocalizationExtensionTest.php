<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\Game\Recipe;
use App\Twig\RecipeLocalizationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RecipeLocalizationExtensionTest extends TestCase
{
    public function testNameFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $recipe = (new Recipe())
            ->setName('Dague en fer')
            ->setNameTranslations(['en' => 'Iron Dagger']);

        $extension = new RecipeLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Iron Dagger', $extension->localizedRecipeName($recipe));
    }

    public function testNameFilterFallsBackToBaseNameWhenTranslationMissing(): void
    {
        $recipe = (new Recipe())
            ->setName('Dague en fer')
            ->setNameTranslations(['de' => 'Eisendolch']);

        $extension = new RecipeLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Dague en fer', $extension->localizedRecipeName($recipe));
    }

    public function testNameFilterFallsBackToBaseNameWhenRequestStackIsEmpty(): void
    {
        $recipe = (new Recipe())
            ->setName('Dague en fer')
            ->setNameTranslations(['en' => 'Iron Dagger']);

        $extension = new RecipeLocalizationExtension(new RequestStack());

        $this->assertSame('Dague en fer', $extension->localizedRecipeName($recipe));
    }

    public function testNameFilterReturnsEmptyStringForNullRecipe(): void
    {
        $extension = new RecipeLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedRecipeName(null));
    }

    public function testDescriptionFilterReturnsTranslationMatchingCurrentLocale(): void
    {
        $recipe = (new Recipe())
            ->setDescription('Forge une dague en fer tranchante.')
            ->setDescriptionTranslations(['en' => 'Forges a sharp iron dagger.']);

        $extension = new RecipeLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Forges a sharp iron dagger.', $extension->localizedRecipeDescription($recipe));
    }

    public function testDescriptionFilterFallsBackToBaseDescriptionWhenTranslationMissing(): void
    {
        $recipe = (new Recipe())
            ->setDescription('Forge une dague en fer tranchante.')
            ->setDescriptionTranslations(['de' => 'Schmiedet einen scharfen Eisendolch.']);

        $extension = new RecipeLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('Forge une dague en fer tranchante.', $extension->localizedRecipeDescription($recipe));
    }

    public function testDescriptionFilterReturnsEmptyStringWhenDescriptionIsNull(): void
    {
        $recipe = new Recipe();

        $extension = new RecipeLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedRecipeDescription($recipe));
    }

    public function testDescriptionFilterReturnsEmptyStringForNullRecipe(): void
    {
        $extension = new RecipeLocalizationExtension($this->stackWithLocale('en'));

        $this->assertSame('', $extension->localizedRecipeDescription(null));
    }

    public function testFiltersAreRegistered(): void
    {
        $extension = new RecipeLocalizationExtension(new RequestStack());

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertSame('localized_recipe_name', $filters[0]->getName());
        $this->assertSame('localized_recipe_description', $filters[1]->getName());
    }

    private function stackWithLocale(string $locale): RequestStack
    {
        $request = Request::create('/');
        $request->setLocale($locale);

        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }
}
