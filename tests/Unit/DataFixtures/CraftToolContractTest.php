<?php

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\RecipeFixtures;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * OBJ-06 — les paliers d'outil et les sept metiers pourvus (GAME_ITEMS §4).
 *
 * Sur 4 paliers declares, seuls bronze et fer etaient atteignables, et trois
 * metiers d'artisanat n'avaient aucun outil. Le contrat tient les deux
 * invariants du plan depuis les donnees : **les 7 metiers pourvus** (tout
 * craft de recette a un type d'outil, tout type est equipable et etiquete) et
 * **les 4 paliers atteignables** (bronze et fer chez un marchand, acier et
 * mithril au craft du forgeron — l'outillage ne s'arrete plus au fer).
 */
class CraftToolContractTest extends TestCase
{
    private function projectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * @return array<string, array<string, mixed>> les outils de tool.yaml, par reference
     */
    private function tools(): array
    {
        $entries = Yaml::parseFile($this->projectDir() . '/fixtures/game/item/tool.yaml')[Item::class] ?? [];
        $tools = [];
        foreach ($entries as $reference => $entry) {
            if (\is_array($entry) && isset($entry['toolType'], $entry['toolTier'], $entry['slug'])) {
                // La cle Alice porte l'heritage (`pickaxe_steel (extends item)`) :
                // la reference Doctrine est le premier mot.
                $tools[explode(' ', (string) $reference)[0]] = $entry;
            }
        }

        $this->assertNotEmpty($tools, 'Le contrat ne verifie rien si tool.yaml n\'est pas lu.');

        return $tools;
    }

    /**
     * @return list<string> l'union des types d'outil declares par le code
     */
    private function declaredToolTypes(): array
    {
        return array_values(array_unique(array_merge(
            array_values(Item::CRAFT_TOOL_TYPES),
            array_values(Item::GATHER_TOOL_TYPES),
        )));
    }

    /**
     * Les 7 metiers pourvus : tout `craft` porte par une recette a un type
     * d'outil. Un metier hors de la table fabriquerait sans outil — le trou
     * silencieux que ce jalon referme.
     */
    public function testEveryRecipeCraftHasAToolType(): void
    {
        $source = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/RecipeFixtures.php');
        preg_match_all("/'craft' => '([a-z]+)'/", $source, $matches);
        $crafts = array_unique($matches[1]);
        $this->assertNotEmpty($crafts, 'Le contrat ne verifie rien si aucune recette n\'est lue.');

        foreach ($crafts as $craft) {
            $this->assertArrayHasKey(
                $craft,
                Item::CRAFT_TOOL_TYPES,
                sprintf('Le metier « %s » fabrique sans outil : il manque a Item::CRAFT_TOOL_TYPES.', $craft),
            );
        }
    }

    /**
     * Chaque type d'outil a ses quatre paliers en fixtures, et aucun outil ne
     * porte un type que le code ne declare pas.
     */
    public function testEveryToolTypeHasItsFourTiers(): void
    {
        $tiersByType = [];
        foreach ($this->tools() as $tool) {
            $tiersByType[(string) $tool['toolType']][] = (int) $tool['toolTier'];
        }

        foreach ($this->declaredToolTypes() as $type) {
            $tiers = $tiersByType[$type] ?? [];
            sort($tiers);
            $this->assertSame(
                [Item::TOOL_TIER_BRONZE, Item::TOOL_TIER_IRON, Item::TOOL_TIER_STEEL, Item::TOOL_TIER_MITHRIL],
                $tiers,
                sprintf('Le type d\'outil « %s » n\'a pas ses quatre paliers dans tool.yaml.', $type),
            );
        }

        $this->assertSame(
            [],
            array_values(array_diff(array_keys($tiersByType), $this->declaredToolTypes())),
            'Un outil de tool.yaml porte un type que le code ne declare pas.',
        );
    }

    /**
     * Chaque type est cable de bout en bout : un bit d'equipement, un
     * emplacement d'interface, une etiquette — et un bit n'est jamais partage.
     */
    public function testEveryToolTypeIsWiredToSlotLocationAndLabel(): void
    {
        foreach ($this->declaredToolTypes() as $type) {
            $this->assertArrayHasKey($type, PlayerItem::TOOL_TYPE_TO_GEAR, sprintf('« %s » n\'a pas de bit d\'equipement.', $type));
            $this->assertArrayHasKey($type, Item::TOOL_GEAR_LOCATIONS, sprintf('« %s » n\'a pas d\'emplacement d\'interface.', $type));
            $this->assertArrayHasKey($type, Item::TOOL_TYPE_LABELS, sprintf('« %s » n\'a pas d\'etiquette.', $type));
        }

        $bits = array_values(PlayerItem::TOOL_TYPE_TO_GEAR);
        $this->assertSame(\count($bits), \count(array_unique($bits)), 'Deux types d\'outil partagent un bit d\'equipement.');
    }

    /**
     * Les 4 paliers atteignables : bronze et fer chez un marchand, acier et
     * mithril par une recette de forgeron. L'outillage ne s'arrete plus au fer.
     */
    public function testEveryToolTierIsReachable(): void
    {
        $shopSources = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/PnjFixtures.php')
            . (string) file_get_contents($this->projectDir() . '/src/DataFixtures/VillageHubPnjFixtures.php');
        $craftedRefs = array_map(
            static fn (array $recipe): string => (string) $recipe['result_ref'],
            RecipeFixtures::toolRecipesData(),
        );

        foreach ($this->tools() as $reference => $tool) {
            if ((int) $tool['toolTier'] <= Item::TOOL_TIER_IRON) {
                $this->assertStringContainsString(
                    sprintf("'%s'", $tool['slug']),
                    $shopSources,
                    sprintf('L\'outil « %s » (palier %d) n\'est vendu par aucun PNJ.', $tool['slug'], $tool['toolTier']),
                );
            } else {
                $this->assertContains(
                    $reference,
                    $craftedRefs,
                    sprintf('L\'outil « %s » (palier %d) n\'a aucune recette de forgeron.', $tool['slug'], $tool['toolTier']),
                );
            }
        }
    }

    /**
     * La derivation des recettes d'outils : les deux paliers hauts des 12
     * types, tous au forgeron, chaque recette consommant un intermediaire
     * `crafted-` (la regle des lignes de production, ECO-25) et produisant un
     * outil qui existe au bon palier.
     */
    public function testToolRecipesCoverEveryTypeAndConsumeTheChain(): void
    {
        $recipes = RecipeFixtures::toolRecipesData();
        $types = $this->declaredToolTypes();
        $this->assertCount(2 * \count($types), $recipes, 'Deux paliers de recette par type d\'outil.');

        $tools = $this->tools();
        foreach ($recipes as $recipe) {
            $this->assertSame('forgeron', $recipe['craft']);
            $this->assertGreaterThanOrEqual(3, $recipe['required_level']);

            $hasCrafted = false;
            foreach ($recipe['ingredients'] as $ingredient) {
                if (str_starts_with((string) $ingredient['slug'], 'crafted-')) {
                    $hasCrafted = true;
                }
            }
            $this->assertTrue($hasCrafted, sprintf('« %s » ne consomme aucun intermediaire de la chaine.', $recipe['slug']));

            $this->assertArrayHasKey(
                (string) $recipe['result_ref'],
                $tools,
                sprintf('« %s » produit une reference d\'outil inconnue de tool.yaml.', $recipe['slug']),
            );
        }
    }

    /**
     * Tout outil livre est equipable a travers un arbre : son slug apparait
     * dans un `equip.tool` des fixtures de competences. Un outil qu'aucun
     * arbre n'autorise est un objet mort.
     */
    public function testEveryToolIsEquippableThroughSomeTree(): void
    {
        $source = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/Game/SkillFixtures.php');

        foreach ($this->tools() as $tool) {
            $this->assertStringContainsString(
                sprintf("'%s'", $tool['slug']),
                $source,
                sprintf('L\'outil « %s » n\'est accorde par aucun nœud d\'arbre (equip.tool).', $tool['slug']),
            );
        }
    }
}
