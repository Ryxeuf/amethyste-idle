<?php

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\MateriaCatalogFixtures;
use PHPUnit\Framework\TestCase;

/**
 * MAT-07 — le nettoyage.
 *
 * Deux invariants du plan materia : **aucune materia consommable** (la materia
 * est le build du personnage, jamais une charge qui s'epuise — GAME_MATERIA
 * §2.4) et **aucune materia sans accord** (chaque materia du catalogue est
 * ouverte par un nœud `materia.unlock` d'un arbre — les sept orphelines ont
 * ete raccrochees au nœud terminal de l'arbre de leur element).
 */
class MateriaCleanupTest extends TestCase
{
    private function source(string $path): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 3) . '/' . $path);
    }

    /**
     * Aucun exemplaire de materia ne porte de charges finies : un
     * `nb_usages` >= 0 sur une entree `materia_*` la ferait apparaitre comme
     * un consommable, alors que rien en combat ne la decompte.
     */
    public function testNoMateriaCarriesFiniteUsages(): void
    {
        $source = $this->source('src/DataFixtures/PlayerItemFixtures.php');

        preg_match_all(
            "/'generic_item' => '(materia_[a-z0-9_]+)',(?:\\s*\n\\s*'[a-z_]+' => [^\n]+,)*?\\s*\n\\s*'nb_usages' => (-?\\d+),/",
            $source,
            $matches,
            \PREG_SET_ORDER,
        );

        $offenders = [];
        foreach ($matches as $match) {
            if ((int) $match[2] >= 0) {
                $offenders[] = sprintf('%s (nb_usages: %s)', $match[1], $match[2]);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            sprintf('Des materia portent des charges finies alors que rien ne les decompte (MAT-07, GAME_MATERIA §2.4) : %s.', implode(', ', $offenders)),
        );
    }

    /**
     * Le catalogue derive ne fixe jamais de charges : une materia generee
     * garde le nb_usages illimite (-1) par defaut de l'entite.
     */
    public function testTheDerivedCatalogNeverSetsUsages(): void
    {
        $this->assertStringNotContainsString(
            'setNbUsages',
            $this->source('src/DataFixtures/MateriaCatalogFixtures.php'),
            'Le catalogue derive fixe des charges sur une materia : la materia est le build, jamais un consommable (MAT-07).',
        );
    }

    /**
     * Aucune materia sans accord : la liste des orphelines est vide depuis
     * MAT-07 — toute materia du catalogue est desormais ouverte par un nœud,
     * et `MateriaCatalogTest::testOrphanListIsAccurate` empeche une entree
     * raccrochee d'y rester.
     */
    public function testNoMateriaWithoutAnAccord(): void
    {
        $this->assertSame(
            [],
            MateriaCatalogFixtures::ORPHAN_SPELLS,
            'Une materia sans accord est revenue : la raccrocher a un nœud terminal de l\'arbre de son element plutot que de la laisser orpheline (MAT-07).',
        );
    }
}
