<?php

namespace App\Tests\Unit\DataFixtures;

use App\Enum\CraftSpecialization;
use App\GameEngine\Crafting\CraftBranchCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Les trois arbres de la Piste H au gabarit d'artisanat (DOM-06).
 *
 * ECO-29/30/31 avaient livre le cuisinier, le charpentier et le tailleur comme
 * des **catalogues de recettes** : huit nœuds, dont sept debloquaient une
 * recette. Il leur manquait ce qui fait un arbre — le geste qui s'affine, et le
 * choix qui engage.
 *
 * Ce qui se verrouille ici : la profondeur, les deux entrees gratuites, et
 * surtout que **les nœuds terminaux soient ceux du catalogue de branches**
 * (DOM-04). Une branche declaree a l'etabli et absente de l'arbre serait un
 * choix sans consequence visible ; un nœud d'arbre nommant une branche qui
 * n'existe pas serait un nœud inatteignable, et rien ne le dirait.
 */
class CraftTreeTemplateTest extends TestCase
{
    /**
     * L'arbre de chaque metier, et le nom de la fonction qui le suit dans le
     * fichier — la borne de lecture.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const TREES = [
        'cuisinier' => ['getCookSkills', 'getLumberjackSkills'],
        'charpentier' => ['getCarpenterSkills', 'getTailorSkills'],
        'tailleur' => ['getTailorSkills', 'getBlacksmithSkills'],
    ];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function tree(string $craft): string
    {
        [$name, $next] = self::TREES[$craft];

        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');

        $start = strpos($source, sprintf('private function %s(): array', $name));
        self::assertNotFalse($start, sprintf('L\'arbre "%s" a disparu.', $craft));

        $end = strpos($source, sprintf('private function %s(): array', $next), $start);
        self::assertNotFalse($end, sprintf('La fin de l\'arbre "%s" est introuvable.', $craft));

        return substr($source, $start, $end - $start);
    }

    /**
     * Nom de nœud => cout en points.
     *
     * @return array<string, int>
     */
    private function nodes(string $craft): array
    {
        $nodes = [];
        $current = null;

        foreach (explode("\n", $this->tree($craft)) as $line) {
            if (preg_match("/^            '([a-z_0-9]+)' => \[$/", $line, $match) === 1) {
                $current = $match[1];

                continue;
            }
            if ($current !== null && preg_match("/'requiredPoints' => (\d+)/", $line, $match) === 1) {
                $nodes[$current] = (int) $match[1];
                $current = null;
            }
        }

        self::assertNotEmpty($nodes, sprintf('L\'extraction de l\'arbre "%s" a echoue : rien n\'est verifie.', $craft));

        return $nodes;
    }

    /**
     * @return array<string, string> nom de nœud => branche declaree
     */
    private function branchNodes(string $craft): array
    {
        // Lecture ligne a ligne : les actions vivent dans un tableau imbrique,
        // et une expression qui traverserait deux niveaux de crochets ne
        // correspond a rien — **sans erreur**. Le test passerait en verifiant le
        // vide, ce qui est la famille de defaut que ce fichier traque.
        $branches = [];
        $current = null;

        foreach (explode("\n", $this->tree($craft)) as $line) {
            if (preg_match("/^            '([a-z_0-9]+)' => \[$/", $line, $match) === 1) {
                $current = $match[1];

                continue;
            }
            if ($current !== null && preg_match("/'branch' => '([a-z]+)'/", $line, $match) === 1) {
                $branches[$current] = $match[1];
            }
        }

        return $branches;
    }

    // =====================================================================
    // Le gabarit
    // =====================================================================

    public function testEachTreeReachesTheTemplateDepth(): void
    {
        foreach (array_keys(self::TREES) as $craft) {
            self::assertCount(15, $this->nodes($craft), sprintf('L\'arbre "%s" n\'est pas au gabarit.', $craft));
        }
    }

    /**
     * Deux entrees a 0 point par arbre.
     *
     * Une seule ferait de l'entree un couloir : le debutant n'aurait pas un
     * choix a faire, seulement un ordre a suivre.
     */
    public function testEachTreeOpensOnTwoFreeNodes(): void
    {
        foreach (array_keys(self::TREES) as $craft) {
            $free = \count(array_filter($this->nodes($craft), static fn (int $points): bool => $points === 0));

            self::assertSame(2, $free, sprintf('L\'arbre "%s" n\'ouvre pas sur deux nœuds gratuits.', $craft));
        }
    }

    /**
     * Aucun prerequis ne coute plus cher que le nœud qu'il ouvre.
     *
     * Le defaut est muet : le nœud s'affiche a 30 points, le joueur les a, et
     * l'apprentissage refuse — parce qu'un prerequis a 50 points le bloque. Deux
     * arbres portaient ce defaut avant ce jalon.
     */
    public function testNoPrerequisiteCostsMoreThanTheNodeItOpens(): void
    {
        foreach (array_keys(self::TREES) as $craft) {
            $costs = $this->nodes($craft);

            $inversions = [];
            $current = null;

            foreach (explode("\n", $this->tree($craft)) as $line) {
                if (preg_match("/^            '([a-z_0-9]+)' => \[$/", $line, $match) === 1) {
                    $current = $match[1];

                    continue;
                }
                if ($current === null || preg_match("/'requirements' => \[([^\]]+)\]/", $line, $match) !== 1) {
                    continue;
                }

                preg_match_all("/'([a-z_0-9]+)'/", $match[1], $required);
                foreach ($required[1] as $prerequisite) {
                    self::assertArrayHasKey($prerequisite, $costs, sprintf('Le prerequis "%s" ne nomme aucun nœud de l\'arbre "%s".', $prerequisite, $craft));

                    if ($costs[$prerequisite] > $costs[$current]) {
                        $inversions[] = sprintf('%s (%d) exige %s (%d)', $current, $costs[$current], $prerequisite, $costs[$prerequisite]);
                    }
                }
            }

            self::assertSame([], $inversions, sprintf('Ces nœuds de l\'arbre "%s" exigent un prerequis plus cher qu\'eux.', $craft));
        }
    }

    // =====================================================================
    // La branche terminale
    // =====================================================================

    /**
     * Chaque arbre porte **exactement deux** nœuds de branche.
     *
     * Un de moins, et l'une des deux branches n'aurait aucune trace dans
     * l'arbre ; un de plus, et le choix cesserait d'etre binaire.
     */
    public function testEachTreeCarriesExactlyOneNodePerBranch(): void
    {
        foreach (array_keys(self::TREES) as $craft) {
            self::assertCount(2, $this->branchNodes($craft), sprintf('L\'arbre "%s" ne porte pas deux nœuds de branche.', $craft));
        }
    }

    /**
     * Les branches nommees par l'arbre sont **celles du catalogue** (DOM-04).
     *
     * Une branche declaree a l'etabli et absente de l'arbre serait un choix sans
     * consequence visible ; un nœud nommant une branche inexistante serait
     * inatteignable pour toujours, et rien ne le dirait.
     */
    public function testTheTreeBranchesAreTheOnesTheWorkbenchOffers(): void
    {
        $catalog = new CraftBranchCatalog($this->root());

        foreach (array_keys(self::TREES) as $craft) {
            $declared = array_keys($catalog->branchesOf(CraftSpecialization::from($craft)));
            $inTree = array_values($this->branchNodes($craft));

            sort($declared);
            sort($inTree);

            self::assertSame($declared, $inTree, sprintf('Les branches de l\'arbre "%s" ne sont pas celles de l\'etabli.', $craft));
        }
    }

    /**
     * Les deux nœuds de branche partent du meme point, et coutent le meme prix.
     *
     * Sans quoi le choix ne serait pas un choix mais un ordre : une branche
     * moins chere ou plus tot atteignable serait prise par tout le monde.
     */
    public function testBothBranchNodesAreEquallyReachable(): void
    {
        foreach (array_keys(self::TREES) as $craft) {
            $costs = $this->nodes($craft);
            $branchNodes = array_keys($this->branchNodes($craft));

            self::assertSame(
                $costs[$branchNodes[0]],
                $costs[$branchNodes[1]],
                sprintf('Les deux branches de l\'arbre "%s" ne coutent pas le meme prix.', $craft),
            );
        }
    }
}
