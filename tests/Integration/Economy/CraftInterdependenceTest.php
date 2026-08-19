<?php

namespace App\Tests\Integration\Economy;

use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\GameEngine\Progression\FoundTreeCatalog;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Interdependance des metiers (ECO-14).
 *
 * Une economie de production joueur ne tient que si les metiers ont besoin les
 * uns des autres. Un metier autosuffisant produit un joueur autosuffisant : il
 * n'a rien a acheter, donc rien a vendre non plus, et le marche se vide.
 *
 * L'audit qui a motive le jalon trouvait **trois metiers sur quatre entierement
 * autosuffisants** (forgeron, tanneur, et alchimiste a une recette de niveau 10
 * pres — donc en pratique). Seul le joaillier dependait d'un autre metier, et
 * seulement a partir du niveau 6.
 *
 * Deux garanties opposees se tiennent ici, et c'est leur tension qui compte :
 * les metiers doivent se croiser, **mais jamais au palier d'entree**, sous peine
 * de casser le plancher anti cold-start pose par ECO-02.
 */
final class CraftInterdependenceTest extends AbstractIntegrationTestCase
{
    /** @var array<string, string>|null slug produit → metier producteur */
    private ?array $producers = null;

    /** @var list<string>|null les metiers hors chaine, lus la ou ils se declarent */
    private ?array $lateralCrafts = null;

    public function testEveryCraftConsumesTheOutputOfAnother(): void
    {
        $selfSufficient = [];

        foreach ($this->consumptionGraph() as $craft => $suppliers) {
            if ([] === $suppliers) {
                $selfSufficient[] = $craft;
            }
        }

        self::assertSame([], $selfSufficient, sprintf(
            "Metier(s) autosuffisant(s) : %s.\n"
            . "Un metier qui ne consomme la sortie d'aucun autre produit un joueur autosuffisant : "
            . "il n'a rien a acheter, donc rien a vendre non plus (ECO-14).",
            implode(', ', $selfSufficient)
        ));
    }

    public function testEveryCraftOutputIsConsumedBySomeoneElse(): void
    {
        $consumedBy = [];
        foreach ($this->consumptionGraph() as $craft => $suppliers) {
            $consumedBy[$craft] ??= [];
            foreach ($suppliers as $supplier) {
                $consumedBy[$supplier][] = $craft;
            }
        }

        foreach ($consumedBy as $craft => $clients) {
            self::assertNotEmpty($clients, sprintf(
                'Aucun autre metier ne consomme la production de « %s » : sa sortie n\'a pas de demande.',
                $craft
            ));
        }
    }

    /**
     * Le palier d'entree reste accessible en solo (ECO-02).
     *
     * C'est la contrainte qui borne ECO-14 : croiser les metiers des le niveau 1
     * rendrait le premier craft dependant d'un autre joueur — exactement le
     * cold-start que le plancher T1 existe pour empecher.
     */
    public function testNoFirstTierRecipeDependsOnAnotherCraft(): void
    {
        $producers = $this->producers();
        $violations = [];

        foreach ($this->recipes(1) as $recipe) {
            foreach ($this->ingredientSlugs($recipe) as $slug) {
                if (isset($producers[$slug])) {
                    $violations[] = sprintf('%s ← %s (%s)', $recipe->getSlug(), $slug, $producers[$slug]);
                }
            }
        }

        self::assertSame([], $violations, sprintf(
            "Recette(s) de niveau 1 dependant d'un ingredient artisanal : %s.\n"
            . 'Le palier d\'entree doit rester realisable sans dependre d\'un autre joueur (ECO-02).',
            implode(' ; ', $violations)
        ));
    }

    /**
     * Metier → metiers dont il consomme la production.
     *
     * @return array<string, list<string>>
     */
    private function consumptionGraph(): array
    {
        $producers = $this->producers();
        $graph = [];

        foreach ($this->recipes() as $recipe) {
            $craft = $recipe->getCraft();
            $graph[$craft] ??= [];

            foreach ($this->ingredientSlugs($recipe) as $slug) {
                $producer = $producers[$slug] ?? null;
                if (null !== $producer && $producer !== $craft && !\in_array($producer, $graph[$craft], true)) {
                    $graph[$craft][] = $producer;
                }
            }
        }

        self::assertNotEmpty($graph, 'Aucune recette chargee : les fixtures de craft sont absentes.');

        return $graph;
    }

    /**
     * Slug d'un objet produit par une recette → metier qui le produit.
     *
     * @return array<string, string>
     */
    private function producers(): array
    {
        if (null !== $this->producers) {
            return $this->producers;
        }

        $producers = [];
        foreach ($this->recipes() as $recipe) {
            $result = $recipe->getResult();
            if ($result instanceof Item) {
                $producers[$result->getSlug()] = $recipe->getCraft();
            }
        }

        return $this->producers = $producers;
    }

    /**
     * Les recettes qui font l'economie — celles des arbres **retrouves** exclues (DOM-10).
     *
     * Un arbre retrouve est lateral par loi : *jamais necessaire*, et ce qu'il
     * produit s'obtient deja autrement. Le compter ici lui ferait dire
     * l'inverse de sa loi dans les deux sens a la fois — il apparaitrait comme
     * une **dependance** du palier d'entree (le fer se mine, il ne s'achete
     * pas), et comme un metier a qui l'on doit des clients. La chaine de
     * production se lit sans lui, ce qui est exactement sa place.
     *
     * @return list<Recipe>
     */
    private function recipes(?int $requiredLevel = null): array
    {
        $criteria = $requiredLevel === null ? [] : ['requiredLevel' => $requiredLevel];
        $lateral = $this->lateralCrafts();

        return array_values(array_filter(
            $this->em->getRepository(Recipe::class)->findBy($criteria),
            static fn (Recipe $recipe): bool => !\in_array(mb_strtolower($recipe->getCraft()), $lateral, true),
        ));
    }

    /**
     * @return list<string>
     */
    private function lateralCrafts(): array
    {
        if (null !== $this->lateralCrafts) {
            return $this->lateralCrafts;
        }

        $crafts = [];
        foreach ((new FoundTreeCatalog(\dirname(__DIR__, 3)))->trees() as $tree) {
            $crafts[] = mb_strtolower($tree['label']);
        }

        return $this->lateralCrafts = $crafts;
    }

    /**
     * @return list<string>
     */
    private function ingredientSlugs(Recipe $recipe): array
    {
        $slugs = [];
        foreach ($recipe->getIngredients() as $ingredient) {
            if (\is_array($ingredient) && isset($ingredient['slug'])) {
                $slugs[] = (string) $ingredient['slug'];
            }
        }

        return $slugs;
    }
}
