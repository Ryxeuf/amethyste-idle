<?php

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\RecipeFixtures;
use App\Entity\Game\Item;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * OBJ-07 — aucune matiere premiere sans debouche (GAME_ITEMS §5).
 *
 * Une matiere qui tombe des monstres ou sort d'un filon sans qu'aucune
 * recette ne la consomme est un poids mort d'inventaire — le champignon l'a
 * ete pendant des mois, en tombant de vingt tables de butin. Le contrat :
 * toute ressource **brute** (non produite par une recette) est l'ingredient
 * d'au moins une recette. Les produits finis (plats, fleches, necessaires)
 * sont hors perimetre par construction — ce sont des resultats de recette,
 * leur debouche est leur usage.
 */
class MaterialOutletContractTest extends TestCase
{
    /**
     * Dettes declarees, chacune avec sa raison. Une entree qui gagne un
     * consommateur doit etre retiree — c'est le meme aveu explicite que
     * `SkillRecipeConsistencyTest::RECIPES_TO_AUTHOR`, qui documente deja la
     * premiere.
     *
     * @var array<string, string>
     */
    private const DECLARED_DEBTS = [
        'ore-orichalcum' => 'recipe-orichalcum-ingot est une recette a ecrire (RECIPES_TO_AUTHOR) : le haut de la chaine du metal est un chantier a part (OBJ-02b)',
    ];

    private function projectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * @return array<string, string> slug => type, pour tous les items livres
     */
    private function items(): array
    {
        $items = [];

        $source = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/ItemFixtures.php');
        foreach (preg_split("/\n            '[a-z0-9_]+' => \[/", $source) ?: [] as $block) {
            if (preg_match("/'slug' => '([a-z0-9-]+)'/", $block, $slug)
                && preg_match("/'type' => '([a-z]+)'/", $block, $type)) {
                $items[$slug[1]] = $type[1];
            }
        }

        foreach ((array) glob($this->projectDir() . '/fixtures/game/item/*.yaml') as $file) {
            $entries = Yaml::parseFile((string) $file)[Item::class] ?? [];
            if (!\is_array($entries)) {
                continue;
            }
            foreach ($entries as $entry) {
                if (\is_array($entry) && isset($entry['slug'], $entry['type'])) {
                    $items[(string) $entry['slug']] = (string) $entry['type'];
                }
            }
        }

        $this->assertNotEmpty($items, 'Le contrat ne verifie rien si aucun item n\'est lu.');

        return $items;
    }

    /**
     * @return array{ingredients: list<string>, results: list<string>}
     */
    private function recipeFlows(): array
    {
        $source = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/RecipeFixtures.php');
        preg_match_all("/'slug' => '([a-z0-9-]+)', 'quantity'/", $source, $ingredients);
        preg_match_all("/'slug' => '([a-z0-9-]+)',\n                    'quantity'/", $source, $wrapped);
        preg_match_all("/'result_ref' => '([a-z0-9_]+)'/", $source, $results);

        $allIngredients = array_merge($ingredients[1], $wrapped[1]);
        $allResults = $results[1];

        // Les recettes derivees (OBJ-06) ne sont pas des litteraux de source :
        // on interroge la derivation elle-meme, pas une copie.
        foreach (RecipeFixtures::toolRecipesData() as $recipe) {
            foreach ($recipe['ingredients'] as $ingredient) {
                $allIngredients[] = (string) $ingredient['slug'];
            }
            $allResults[] = (string) $recipe['result_ref'];
        }

        return [
            'ingredients' => array_values(array_unique($allIngredients)),
            'results' => array_values(array_unique($allResults)),
        ];
    }

    /**
     * Toute ressource brute a un debouche : elle nourrit au moins une recette,
     * ou porte une dette declaree avec sa raison.
     */
    public function testEveryRawMaterialFeedsARecipe(): void
    {
        $flows = $this->recipeFlows();
        $resultSlugs = array_map(
            static fn (string $reference): string => str_replace('_', '-', $reference),
            $flows['results'],
        );

        $orphans = [];
        foreach ($this->items() as $slug => $type) {
            if ('resource' !== $type) {
                continue;
            }
            // Produite par une recette : c'est un produit ou un intermediaire,
            // pas une matiere brute — son debouche est son usage ou la suite
            // de la chaine, deja couverts par ProductionChainTest.
            if (\in_array($slug, $resultSlugs, true)) {
                continue;
            }
            if (\in_array($slug, $flows['ingredients'], true)) {
                continue;
            }
            if (isset(self::DECLARED_DEBTS[$slug])) {
                continue;
            }
            $orphans[] = $slug;
        }

        $this->assertSame(
            [],
            $orphans,
            'Ces matieres brutes ne nourrissent aucune recette — un butin ou une recolte sans debouche est un poids mort d\'inventaire (GAME_ITEMS §5).',
        );
    }

    /**
     * La dette declaree reste reelle : une entree dont la matiere a gagne un
     * consommateur est un mensonge de plus — elle doit partir.
     */
    public function testDeclaredDebtsAreStillReal(): void
    {
        $flows = $this->recipeFlows();

        foreach (array_keys(self::DECLARED_DEBTS) as $slug) {
            $this->assertNotContains(
                $slug,
                $flows['ingredients'],
                sprintf('« %s » a desormais un consommateur : retirer la dette declaree.', $slug),
            );
        }
    }

    /**
     * Le champignon, nommement : le butin le plus frequent du jeu nourrit la
     * cuisine **et** l'alchimie. C'est le cas d'espece qui a motive le jalon —
     * s'il regresse, tout le contrat a regresse.
     */
    public function testTheMushroomFeedsCookingAndAlchemy(): void
    {
        $source = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/RecipeFixtures.php');

        $this->assertStringContainsString("'recipe-mushroom-fricassee'", $source);
        $this->assertMatchesRegularExpression(
            "/'slug' => 'recipe-potion-base'.*?'mushroom'/s",
            $source,
            'Le champignon doit rester une matiere d\'entree de la base de potion.',
        );

        $this->assertSame('resource', $this->items()['mushroom'], 'Le champignon est une matiere : l\'onglet Materiaux doit le voir.');
    }
}
