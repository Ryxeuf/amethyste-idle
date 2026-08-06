<?php

namespace App\Tests\Integration\Progression;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Enum\CombatLever;
use App\GameEngine\Progression\CombatBranchCatalog;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\DomainRoleDefinitionLoader;
use App\GameEngine\Progression\SkillLeverReader;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Les arbres patrons, verifies au gabarit (ARC-07).
 *
 * GAME_ARCHETYPES § 9. Les quatre arbres patrons sont ecrits en entier dans le
 * canon ; ce contrat verifie que ce qui est en base **est** ce que le canon
 * ecrit, et surtout que les invariants qui les bornent tiennent — parce qu'un
 * arbre ecrit a la main tombe juste une fois, et derive a la deuxieme.
 *
 * **Les arbres verifies sont nommes, en cliquet inverse** : la liste ne peut
 * que **grandir**, au fur et a mesure qu'ARC-07 puis ARC-08 les convertissent.
 * Un arbre retire de la liste serait un arbre qu'on a cesse de tenir.
 */
class PatronTreeContractTest extends AbstractIntegrationTestCase
{
    /**
     * Les arbres deja ecrits au gabarit.
     *
     * ARC-07a le Pyromancien, ARC-07b le Guerisseur. Le Soldat et l'Archer
     * suivent (ARC-07c→d), puis les vingt autres (ARC-08).
     *
     * @var array<string, string>
     */
    private const CONVERTED = [
        'Pyromancien' => 'pyromancy',
        'Guérisseur' => 'healer',
    ];

    /**
     * **Chaque branche tient exactement le budget de l'arbre.**.
     *
     * C'est l'invariant qui rend l'equilibrage verifiable : *l'arbre ecrit
     * 60 pb, le personnage en porte 50*. Un arbre dont une branche depasserait
     * serait plus fort que ses vingt-trois voisins sans que rien ne le dise, et
     * un arbre dont une branche resterait en dessous serait un piege — le
     * joueur qui la choisit paie le meme prix pour moins.
     */
    public function testEachBranchSpendsExactlyTheTreeBudget(): void
    {
        $budget = (new DomainRoleDefinitionLoader(\dirname(__DIR__, 3)))->load()['budget']['total'];

        foreach (self::CONVERTED as $title => $key) {
            foreach ($this->branchesOf($key) as $branch) {
                self::assertSame(
                    $budget,
                    array_sum($this->spendOf($title, $branch)),
                    sprintf('%s / %s : la branche ne depense pas le budget de l\'arbre.', $title, $branch)
                );
            }
        }
    }

    /**
     * Aucun levier ne depasse son plafond, branche comprise.
     *
     * Le plafond est **par arbre**, donc il se lit sur le total d'une branche
     * et jamais sur un nœud : trois nœuds a 7 pb sur `power` passeraient un a
     * un et casseraient la borne ensemble.
     */
    public function testNoLeverExceedsItsCapInAnyBranch(): void
    {
        foreach (self::CONVERTED as $title => $key) {
            foreach ($this->branchesOf($key) as $branch) {
                foreach ($this->spendOf($title, $branch) as $lever => $points) {
                    $cap = $this->capOf(CombatLever::from($lever));
                    self::assertLessThanOrEqual(
                        $cap,
                        $points,
                        sprintf('%s / %s : %s depense %d pb pour un plafond de %d.', $title, $branch, $lever, $points, $cap)
                    );
                }
            }
        }
    }

    /**
     * **Deux branches ne partagent aucun levier** (§ 6.1 bis, regle 2).
     *
     * Deux branches qui se recouvrent sont un choix de facade : le joueur croit
     * arbitrer, et achete la meme chose des deux cotes.
     */
    public function testTheTwoBranchesShareNoLever(): void
    {
        foreach (self::CONVERTED as $title => $key) {
            $branches = $this->branchesOf($key);
            self::assertCount(2, $branches, $title);

            $own = [];
            foreach ($branches as $branch) {
                $own[$branch] = array_keys($this->branchOnlySpend($title, $branch));
            }

            $shared = array_intersect(...array_values($own));
            self::assertSame([], array_values($shared), sprintf('%s : les deux branches achetent %s.', $title, implode(', ', $shared)));
        }
    }

    /**
     * **Chaque branche ouvre son accord** (§ 6.1 bis, regle 5).
     *
     * La regle qui decide si la fourche est un choix ou une decoration :
     * *deux branches qui ne different que par leurs passifs produisent le meme
     * combat, au tour pres.* Ce sont les gestes qui separent deux facons de
     * jouer, jamais les pourcentages.
     */
    public function testEachBranchOpensItsOwnAccord(): void
    {
        foreach (self::CONVERTED as $title => $key) {
            foreach ($this->branchesOf($key) as $branch) {
                $accords = 0;
                foreach ($this->nodesOf($title) as $skill) {
                    if ($this->branchOf($skill) !== $branch) {
                        continue;
                    }
                    if (\is_string($skill->getActions()['materia']['unlock'] ?? null)) {
                        ++$accords;
                    }
                }

                self::assertGreaterThan(0, $accords, sprintf('%s / %s : une branche sans geste est une decoration.', $title, $branch));
            }
        }
    }

    /**
     * Le capstone : **un seul**, conditionnel, et sur le levier principal.
     *
     * Sa condition doit etre atteignable au tour 2 avec le seul kit d'entree —
     * c'est ce qui distingue un sommet d'une carotte. Le test verifie qu'elle
     * existe ; qu'elle soit atteignable est verifie par
     * `ElementalMarkReachabilityTest`, qui tient la marque du jour 1.
     */
    public function testTheCapstoneIsUniqueConditionalAndOnThePrimaryLever(): void
    {
        $roles = (new DomainRoleDefinitionLoader(\dirname(__DIR__, 3)))->load();
        $reader = $this->reader();

        foreach (self::CONVERTED as $title => $key) {
            $capstones = [];
            foreach ($this->nodesOf($title) as $skill) {
                if ($skill->getRequiredPoints() !== 100) {
                    continue;
                }
                foreach ($reader->grantsOf($skill) as $grant) {
                    $capstones[] = $grant;
                }
            }

            self::assertCount(1, $capstones, sprintf('%s : un arbre a un sommet, et un seul.', $title));

            $capstone = $capstones[0];
            $role = $this->domain($title)->getRole();
            self::assertNotNull($role);

            self::assertSame($roles['capstone_cost'], $capstone->budgetPoints, sprintf('%s : le capstone ne coute pas ce que le canon lui donne.', $title));
            self::assertSame($roles['roles'][$role->value]['primary'], $capstone->lever->value, sprintf('%s : le capstone ne vise pas le levier principal de sa fonction.', $title));
            self::assertTrue($capstone->isConditional(), sprintf('%s : un capstone sans condition est un passif ordinaire qui coute cher.', $title));
        }
    }

    /**
     * La regle des 80/20 : au moins 40 pb dans la palette, au plus 10 dehors,
     * et sur **un seul** levier etranger — la teinte.
     */
    public function testEachBranchHoldsTheEightyTwentyRule(): void
    {
        $config = (new DomainRoleDefinitionLoader(\dirname(__DIR__, 3)))->load();

        foreach (self::CONVERTED as $title => $key) {
            $role = $this->domain($title)->getRole();
            self::assertNotNull($role);

            $palette = array_merge(
                [$config['roles'][$role->value]['primary']],
                $config['roles'][$role->value]['secondary'],
            );

            foreach ($this->branchesOf($key) as $branch) {
                $inside = 0;
                $outside = [];
                foreach ($this->spendOf($title, $branch) as $lever => $points) {
                    if (\in_array($lever, $palette, true)) {
                        $inside += $points;
                    } else {
                        $outside[$lever] = $points;
                    }
                }

                self::assertGreaterThanOrEqual($config['budget']['min_in_palette'], $inside, sprintf('%s / %s : la fonction n\'est plus lisible dans ses depenses.', $title, $branch));
                self::assertLessThanOrEqual($config['budget']['max_off_palette'], array_sum($outside), sprintf('%s / %s : la teinte deborde.', $title, $branch));
                self::assertLessThanOrEqual($config['budget']['max_off_palette_levers'], \count($outside), sprintf('%s / %s : une teinte vise un levier, pas deux.', $title, $branch));
            }
        }
    }

    // =====================================================================
    // Outils
    // =====================================================================

    /**
     * Ce qu'une branche depense, teinte comprise : les nœuds communs plus les
     * siens. Un joueur porte les deux — c'est la lecture du canon.
     *
     * @return array<string, int>
     */
    private function spendOf(string $title, string $branch): array
    {
        return $this->sumLevers($title, fn (?string $nodeBranch): bool => $nodeBranch === null || $nodeBranch === $branch);
    }

    /**
     * @return array<string, int>
     */
    private function branchOnlySpend(string $title, string $branch): array
    {
        return $this->sumLevers($title, fn (?string $nodeBranch): bool => $nodeBranch === $branch);
    }

    /**
     * @param callable(?string): bool $keep
     *
     * @return array<string, int>
     */
    private function sumLevers(string $title, callable $keep): array
    {
        $reader = $this->reader();
        $totals = [];

        foreach ($this->nodesOf($title) as $skill) {
            if (!$keep($this->branchOf($skill))) {
                continue;
            }

            foreach ($reader->grantsOf($skill) as $grant) {
                $totals[$grant->lever->value] = ($totals[$grant->lever->value] ?? 0) + $grant->netBudgetPoints();
            }
        }

        ksort($totals);

        return $totals;
    }

    /**
     * La branche que ce nœud declare, ou `null` s'il est commun.
     */
    private function branchOf(Skill $skill): ?string
    {
        foreach ($skill->getActions() ?? [] as $descriptor) {
            if (\is_array($descriptor) && ($descriptor['action'] ?? null) === 'specialization.branch') {
                $branch = (string) ($descriptor['branch'] ?? '');
                if ($branch !== '') {
                    return $branch;
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function branchesOf(string $treeKey): array
    {
        $catalog = new CombatBranchCatalog(\dirname(__DIR__, 3));

        return array_keys($catalog->branchesOf($treeKey));
    }

    /**
     * Les nœuds **propres** a cet arbre : ceux qu'aucun autre ne partage.
     *
     * Les echelons de port sont rattaches a tous les arbres qui enseignent leur
     * famille (`Skill::domains` est un ManyToMany, ONB-20b) ; les compter dans
     * le budget d'un arbre ferait payer au Pyromancien le baton du Paladin. Le
     * canon les range d'ailleurs hors budget — *un echelon est une porte,
     * jamais une recompense* (GAME_TREE_ANATOMY, ecart 5).
     *
     * @return list<Skill>
     */
    private function nodesOf(string $title): array
    {
        $domain = $this->domain($title);
        $nodes = [];

        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            if ($skill->getDomains()->count() === 1 && $skill->getDomains()->contains($domain)) {
                $nodes[] = $skill;
            }
        }

        return $nodes;
    }

    private function domain(string $title): Domain
    {
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['title' => $title]);
        self::assertNotNull($domain, $title);

        return $domain;
    }

    private function reader(): SkillLeverReader
    {
        /** @var SkillLeverReader $reader */
        $reader = self::getContainer()->get(SkillLeverReader::class);

        return $reader;
    }

    private function capOf(CombatLever $lever): int
    {
        /** @var CombatLeverScale $scale */
        $scale = self::getContainer()->get(CombatLeverScale::class);

        return $scale->capOf($lever);
    }
}
