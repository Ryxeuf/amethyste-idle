<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * OBJ-02b — aucune recette infabricable (GAME_ITEMS §5.3, invariant 8).
 *
 * Les 8 recettes hors perimetre butaient toutes sur l'adamantite et
 * l'astretal, que GAME_ZONES §3 reserve aux extensions : elles sont retirees
 * et versees a la reserve (docs/EXTENSION_RESERVE.md). Ce test tient les deux
 * bouts : plus un ingredient fantome, et le hors-perimetre ne revient pas
 * sans ses filons.
 */
class RecipeCraftabilityTest extends TestCase
{
    /**
     * Le hors-perimetre verse a la reserve d'extension.
     *
     * @var list<string>
     */
    private const EXTENSION_ORES = ['ore-adamantite', 'ore-starmetal', 'ore-voidium'];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function recipeSource(): string
    {
        return (string) file_get_contents($this->root() . '/src/DataFixtures/RecipeFixtures.php');
    }

    /**
     * @return array<string, true> tous les slugs d'objets livres
     */
    private function itemSlugs(): array
    {
        $slugs = [];

        preg_match_all("/'slug' => '([a-z0-9-]+)'/", (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php'), $matches);
        foreach ($matches[1] as $slug) {
            $slugs[$slug] = true;
        }

        foreach (glob($this->root() . '/fixtures/game/item/*.yaml') ?: [] as $file) {
            preg_match_all("/slug: '([a-z0-9-]+)'/", (string) file_get_contents($file), $yaml);
            foreach ($yaml[1] as $slug) {
                $slugs[$slug] = true;
            }
        }

        $this->assertNotEmpty($slugs, 'Le test ne verifie rien si l\'extraction echoue.');

        return $slugs;
    }

    /**
     * Tout ingredient de toute recette est un objet livre. Un ingredient
     * fantome rend la recette **definitivement** infabricable, sans message —
     * le joueur voit la recette, jamais la raison.
     */
    public function testEveryIngredientResolvesToADeliveredItem(): void
    {
        $items = $this->itemSlugs();

        preg_match_all("/\['slug' => '([a-z0-9-]+)', 'quantity' => \d+\]/", $this->recipeSource(), $ingredients);
        $this->assertNotEmpty($ingredients[1], 'Le test ne verifie rien si aucune recette n\'est lue.');

        $ghosts = [];
        foreach (array_unique($ingredients[1]) as $slug) {
            if (!isset($items[$slug])) {
                $ghosts[] = $slug;
            }
        }

        sort($ghosts);
        $this->assertSame(
            [],
            $ghosts,
            sprintf('Des recettes consomment des objets qui n\'existent pas (OBJ-02b) : %s.', implode(', ', $ghosts)),
        );
    }

    /**
     * Le hors-perimetre reste dehors : ni les minerais d'extension, ni une
     * recette qui les consommerait. Ils reviendront par une extension, **avec
     * leurs filons** (docs/EXTENSION_RESERVE.md) — jamais avant.
     */
    public function testTheExtensionReserveStaysOut(): void
    {
        $items = $this->itemSlugs();
        $recipes = $this->recipeSource();

        foreach (self::EXTENSION_ORES as $ore) {
            $this->assertArrayNotHasKey(
                $ore,
                $items,
                sprintf('"%s" est revenu sans son filon : GAME_ZONES §3 le reserve aux extensions (EXTENSION_RESERVE.md).', $ore),
            );
            $this->assertStringNotContainsString(
                sprintf("'%s'", $ore),
                $recipes,
                sprintf('Une recette consomme "%s", un minerai que le jeu de base ne livre pas.', $ore),
            );
        }
    }
}
