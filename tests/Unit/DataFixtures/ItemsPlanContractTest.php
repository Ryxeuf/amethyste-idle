<?php

namespace App\Tests\Unit\DataFixtures;

use App\Entity\Game\Item;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * OBJ-08 — le contrat du plan objets (GAME_ITEMS §6).
 *
 * Les neuf invariants du cadrage, et qui les tient :
 *
 *  1. Cinq types, pas douze            → ItemTaxonomyTest (les 5 constantes, aucune valeur legacy)
 *  2. L'onglet Materiaux est complet   → ItemTaxonomyTest (familles de matieres) + ICI (tout ingredient est une ressource)
 *  3. Aucune piece ne porte d'element  → GearNeutralityTest (grille neutre 3 paliers x 7 formes)
 *  4. Les emplacements progressent     → MateriaSlotTypingTest (plancher 1/2/3 par palier)
 *  5. Tout emplacement est type        → MateriaSlotTypingTest (versant Spell ; le versant Technique est une dette declaree sur ARC)
 *  6. Aucun outil sans fonction        → ICI (tout type d'outil est exige par une recolte ou un craft)
 *  7. Aucun palier d'outil sans source → CraftToolContractTest (bronze/fer vendus, acier/mithril au forgeron) + GatherToolContractTest et DomainAccessManagerTest (le palier 1 offert avec l'arbre)
 *  8. Aucune recette infabricable      → RecipeCraftabilityTest (tout ingredient atteignable, extensions bannies)
 *  9. Aucun doublon de slug            → ItemCleanupTest (une matiere, un slug)
 *
 * Ce fichier ne re-teste pas ce que les autres tiennent deja : il porte les
 * deux morceaux qui manquaient, verifie que l'index ci-dessus ne pourrit pas
 * (les fichiers cites existent), et sert de table des matieres au contrat.
 */
class ItemsPlanContractTest extends TestCase
{
    private function projectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * L'index ne doit pas pourrir : chaque test cite dans le contrat existe.
     */
    public function testTheContractIndexNamesRealTests(): void
    {
        foreach ([
            'ItemTaxonomyTest',
            'GearNeutralityTest',
            'MateriaSlotTypingTest',
            'CraftToolContractTest',
            'GatherToolContractTest',
            'RecipeCraftabilityTest',
            'ItemCleanupTest',
        ] as $test) {
            $this->assertFileExists(
                __DIR__ . '/' . $test . '.php',
                sprintf('Le contrat cite %s, qui n\'existe plus : mettre l\'index a jour.', $test),
            );
        }

        $this->assertFileExists(
            \dirname(__DIR__) . '/GameEngine/Progression/DomainAccessManagerTest.php',
            'Le contrat cite DomainAccessManagerTest, qui n\'existe plus : mettre l\'index a jour.',
        );
    }

    /**
     * Invariant 2 — l'onglet Materiaux est complet : toute matiere premiere
     * ou intermediaire de craft est une ressource. Un ingredient d'un autre
     * type serait invisible de l'onglet qui sert a le trouver.
     */
    public function testEveryRecipeIngredientIsAResource(): void
    {
        $items = $this->itemTypesBySlug();

        $source = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/RecipeFixtures.php');
        preg_match_all("/'slug' => '([a-z0-9-]+)', 'quantity'/", $source, $matches);
        $ingredients = array_unique($matches[1]);
        $this->assertNotEmpty($ingredients, 'Le contrat ne verifie rien si aucun ingredient n\'est lu.');

        foreach ($ingredients as $slug) {
            $this->assertSame(
                Item::TYPE_RESOURCE,
                $items[$slug] ?? null,
                sprintf('L\'ingredient « %s » n\'est pas une ressource : l\'onglet Materiaux ne le montrera pas (GAME_ITEMS §6, invariant 2).', $slug),
            );
        }
    }

    /**
     * Invariant 6 — aucun outil sans fonction : tout type d'outil declare est
     * exige quelque part, par une profession de recolte ou par un metier
     * d'artisanat. C'etait le defaut d'origine du plan — 5 types sur 9, soit
     * 20 objets, sans aucune fonction mecanique.
     */
    public function testEveryToolTypeHasAFunction(): void
    {
        $demanded = array_unique(array_merge(
            array_values(Item::GATHER_TOOL_TYPES),
            array_values(Item::CRAFT_TOOL_TYPES),
        ));

        foreach (array_keys(Item::TOOL_GEAR_LOCATIONS) as $toolType) {
            $this->assertContains(
                $toolType,
                $demanded,
                sprintf('Le type d\'outil « %s » n\'est exige ni par une recolte ni par un craft : un outil sans fonction (GAME_ITEMS §6, invariant 6).', $toolType),
            );
        }

        // Et l'inverse : une exigence qui viserait un type sans emplacement
        // serait un outil impossible a porter.
        foreach ($demanded as $toolType) {
            $this->assertArrayHasKey($toolType, Item::TOOL_GEAR_LOCATIONS);
        }
    }

    /**
     * @return array<string, string> slug => type
     */
    private function itemTypesBySlug(): array
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
}
