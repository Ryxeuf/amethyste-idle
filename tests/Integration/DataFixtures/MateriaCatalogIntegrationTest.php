<?php

namespace App\Tests\Integration\DataFixtures;

use App\DataFixtures\MateriaCatalogFixtures;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * MAT-03 — le catalogue a 200 (GAME_MATERIA §6, invariants 1, 5 et 7).
 *
 * Verifie sur la base reelle ce que la derivation promet : une materia par
 * `unlock` distinct, un slug deductible du sort, et plus aucun nœud d'arbre
 * qui promette ce qui n'existe pas.
 */
class MateriaCatalogIntegrationTest extends AbstractIntegrationTestCase
{
    /**
     * @return list<string> les slugs de sort distincts ouverts par un nœud
     */
    private function unlockSlugs(): array
    {
        $slugs = [];
        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            $actions = $skill->getActions() ?? [];
            $slug = $actions['materia']['unlock'] ?? null;
            if (\is_string($slug) && $slug !== '') {
                $slugs[$slug] = true;
            }
        }

        return array_keys($slugs);
    }

    /**
     * @return array<string, Item> les materia chargees, par slug de sort
     */
    private function materiaBySpell(): array
    {
        $bySpell = [];
        foreach ($this->em->getRepository(Item::class)->findBy(['type' => Item::TYPE_MATERIA]) as $item) {
            $spell = $item->getSpell();
            self::assertNotNull($spell, sprintf('La materia "%s" ne porte aucun sort.', $item->getSlug()));
            $bySpell[$spell->getSlug()] = $item;
        }

        return $bySpell;
    }

    /**
     * Invariants 1 et 7 : aucun nœud ne ment, et le catalogue est complet —
     * une materia par `unlock` distinct, plus les orphelines de MAT-07.
     */
    public function testCatalogMatchesTheTreesPromises(): void
    {
        $unlocks = $this->unlockSlugs();
        $bySpell = $this->materiaBySpell();

        self::assertGreaterThanOrEqual(200, \count($unlocks), 'Les arbres promettent 200 unlocks distincts.');

        $missing = array_values(array_diff($unlocks, array_keys($bySpell)));
        self::assertSame([], $missing, sprintf('Des nœuds promettent des materia qui n\'existent pas : %s.', implode(', ', \array_slice($missing, 0, 10))));

        self::assertSame(
            \count($unlocks) + \count(MateriaCatalogFixtures::ORPHAN_SPELLS),
            \count($bySpell),
            'Le catalogue porte exactement une materia par unlock distinct, plus les orphelines declarees.',
        );
    }

    /**
     * Invariant 5 : la derivation tient — slug `m<niveau>-<slug du sort>`,
     * element du sort, jamais redeclare.
     */
    public function testDerivationHolds(): void
    {
        foreach ($this->materiaBySpell() as $spellSlug => $item) {
            $spell = $item->getSpell();
            self::assertSame(
                sprintf('m%d-%s', $spell->getLevel(), $spellSlug),
                $item->getSlug(),
                sprintf('Le slug de la materia du sort "%s" ne suit pas la convention.', $spellSlug),
            );
            self::assertSame(
                $spell->getElement(),
                $item->getElement(),
                sprintf('La materia "%s" redeclare un element different de son sort.', $item->getSlug()),
            );
        }
    }
}
