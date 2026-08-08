<?php

namespace App\GameEngine\Balance;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\CombatBranchCatalog;
use App\GameEngine\Progression\SkillLeverReader;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les builds de reference du simulateur, **derives** des arbres reels (ARC-17c).
 *
 * GAME_ARCHETYPES § 9 sexies, premiere exigence du simulateur : *un build par
 * fonction x registre, arbre complet, jamais ecrit a la main*. Le canon dit
 * pourquoi, et c'est une raison de methode plutot que de confort :
 *
 * > **Ecrits en dur, ils se perimeraient au premier changement de fixture — et
 * > c'est exactement ce qu'on cherche a detecter.**
 *
 * Un build en dur mesurerait donc l'etat du jeu **au jour ou on l'a ecrit**, et
 * continuerait de le mesurer longtemps apres qu'il a cesse d'etre vrai. C'est le
 * defaut que les cliquets d'ARC-05a et d'ARC-06a evitent partout ailleurs.
 *
 * ## Ce que cette fabrique lit, et ce qu'elle ne recopie pas
 *
 * **Elle est le lecteur unique de « ce qu'une branche depense ».** La regle
 * existait deja — les nœuds communs plus ceux de la branche, en points de budget
 * nets — mais elle vivait **dans un test** (`PatronTreeContractTest`), donc hors
 * de portee de tout autre appelant. L'ecrire une seconde fois ici aurait produit
 * exactement ce qu'ARC-08a a nomme sur la loi de duree : ***une regle recopiee
 * derive de son original en silence.*** Le contrat des arbres patrons lit
 * desormais cette fabrique.
 *
 * ## Ce qui est couvert, et ce qui ne l'est pas
 *
 * Un build suppose un arbre **au gabarit** : sans leviers, il n'y a rien a
 * porter. Cinq arbres le sont (ARC-07a→d puis ARC-08a), et les dix-neuf autres
 * suivent avec ARC-08. La fabrique ne comble pas ce trou — elle le **declare** :
 * `coverage()` dit quelles cases de la grille fonction x registre sont tenues,
 * pour qu'un seuil du simulateur ne se mesure jamais sur une grille qu'on
 * croirait pleine.
 *
 * *Un simulateur qui tairait ce qu'il ne joue pas donnerait a ses moyennes une
 * autorite qu'elles n'ont pas.*
 */
final class ReferenceBuildFactory
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SkillLeverReader $leverReader,
        private readonly CombatBranchCatalog $branchCatalog,
    ) {
    }

    /**
     * Tous les builds jouables : une branche de chaque arbre au gabarit.
     *
     * **Deux builds par arbre et non un**, parce que la fourche existe pour que
     * deux personnages du meme arbre ne soient pas le meme personnage (ARC-14).
     * N'en garder qu'un moyennerait ce que la fourche separe.
     *
     * @return list<ReferenceBuild>
     */
    public function all(): array
    {
        $builds = [];

        foreach ($this->forkedCombatDomains() as $treeKey => $domain) {
            foreach (array_keys($this->branchCatalog->branchesOf($treeKey)) as $branch) {
                $builds[] = $this->build($domain, $treeKey, $branch);
            }
        }

        return $builds;
    }

    /**
     * Ce que cette branche porte, en points de budget par levier.
     *
     * **Les nœuds communs plus les siens** : un joueur porte les deux, et c'est
     * la lecture du canon. Les nœuds **partages** entre arbres sont exclus —
     * les echelons de port appartiennent a tous les arbres qui enseignent leur
     * famille, et les compter ferait payer au Pyromancien le baton du Paladin.
     * Le canon les range d'ailleurs hors budget : *un echelon est une porte,
     * jamais une recompense.*
     *
     * @return array<string, int>
     */
    public function spendOf(Domain $domain, string $branch): array
    {
        return $this->sumLevers($domain, fn (?string $nodeBranch): bool => null === $nodeBranch || $nodeBranch === $branch);
    }

    /**
     * Ce que cette branche porte **en propre**, teinte commune exclue.
     *
     * Sert a l'invariant qui interdit a deux branches de partager un levier :
     * un recouvrement ferait croire au joueur qu'il arbitre quand il achete la
     * meme chose des deux cotes.
     *
     * @return array<string, int>
     */
    public function branchOnlySpendOf(Domain $domain, string $branch): array
    {
        return $this->sumLevers($domain, fn (?string $nodeBranch): bool => $nodeBranch === $branch);
    }

    /**
     * Les gestes que cette branche ouvre **en propre** — l'accord de fourche.
     *
     * C'est la regle 5 du § 6.1 bis, celle qui decide si la fourche est un choix
     * ou une decoration : *deux branches qui ne different que par leurs passifs
     * produisent le meme combat, au tour pres.*
     *
     * @return list<string>
     */
    public function branchAccordsOf(Domain $domain, string $branch): array
    {
        $accords = [];

        foreach ($this->nodesOf($domain) as $skill) {
            if ($this->branchOf($skill) !== $branch) {
                continue;
            }

            $unlock = $skill->getActions()['materia']['unlock'] ?? null;
            if (\is_string($unlock) && '' !== $unlock) {
                $accords[] = $unlock;
            }
        }

        sort($accords);

        return $accords;
    }

    /**
     * Les cases de la grille fonction x registre que les builds couvrent.
     *
     * Rendue plutot que tue : c'est elle qui empeche un seuil de se prononcer
     * sur une fonction qu'aucun arbre ne joue encore.
     *
     * @return list<string>
     */
    public function coverage(): array
    {
        $cells = [];
        foreach ($this->all() as $build) {
            $cells[$build->cell()] = true;
        }

        $found = array_keys($cells);
        sort($found);

        return $found;
    }

    /**
     * Les arbres de combat qui ont une fourche — donc ceux qui sont au gabarit.
     *
     * La fourche est le bon marqueur, et pas la presence de leviers : ARC-14a
     * l'a livree **avant** ARC-07 precisement parce qu'un arbre au gabarit ne
     * tombe sur ses 390 points que si elle existe. Un arbre qui a une fourche a
     * ete converti ; un arbre converti a une fourche.
     *
     * @return array<string, Domain>
     */
    private function forkedCombatDomains(): array
    {
        $byTitle = [];
        foreach ($this->entityManager->getRepository(Domain::class)->findAll() as $domain) {
            if (null !== $domain->getRegister()) {
                $byTitle[(string) $domain->getTitle()] = $domain;
            }
        }

        $domains = [];
        foreach ($this->branchCatalog->trees() as $treeKey => $tree) {
            $label = (string) ($tree['label'] ?? '');
            if (isset($byTitle[$label])) {
                $domains[$treeKey] = $byTitle[$label];
            }
        }

        return $domains;
    }

    private function build(Domain $domain, string $treeKey, string $branch): ReferenceBuild
    {
        $role = $domain->getRole();
        $register = $domain->getRegister();

        if (null === $role || null === $register) {
            throw new \LogicException(sprintf('L\'arbre « %s » n\'a pas de case : un build sans fonction ni registre ne se compare a rien.', (string) $domain->getTitle()));
        }

        return new ReferenceBuild(
            domainTitle: (string) $domain->getTitle(),
            treeKey: $treeKey,
            branch: $branch,
            branchLabel: $this->branchCatalog->labelOf($treeKey, $branch) ?? $branch,
            role: $role,
            register: $register,
            element: (string) $domain->getElement(),
            leverBudget: $this->spendOf($domain, $branch),
            accords: $this->accordsOf($domain, $branch),
        );
    }

    /**
     * Les gestes que cette branche ouvre — ceux de l'arbre, plus les siens.
     *
     * Un accord de l'autre branche n'y figure pas : le personnage ne l'a pas
     * appris, et le lui preter reviendrait a simuler un build qui n'existe pas.
     *
     * @return list<string>
     */
    private function accordsOf(Domain $domain, string $branch): array
    {
        $accords = [];

        foreach ($this->nodesOf($domain) as $skill) {
            $nodeBranch = $this->branchOf($skill);
            if (null !== $nodeBranch && $nodeBranch !== $branch) {
                continue;
            }

            $unlock = $skill->getActions()['materia']['unlock'] ?? null;
            if (\is_string($unlock) && '' !== $unlock) {
                $accords[$unlock] = true;
            }
        }

        $slugs = array_keys($accords);
        sort($slugs);

        return $slugs;
    }

    /**
     * @param callable(?string): bool $keep
     *
     * @return array<string, int>
     */
    private function sumLevers(Domain $domain, callable $keep): array
    {
        $totals = [];

        foreach ($this->nodesOf($domain) as $skill) {
            if (!$keep($this->branchOf($skill))) {
                continue;
            }

            foreach ($this->leverReader->grantsOf($skill) as $grant) {
                $key = $grant->lever->value;
                $totals[$key] = ($totals[$key] ?? 0) + $grant->netBudgetPoints();
            }
        }

        ksort($totals);

        return $totals;
    }

    /**
     * La branche que ce nœud declare, ou `null` s'il est commun a l'arbre.
     */
    private function branchOf(Skill $skill): ?string
    {
        foreach ($skill->getActions() ?? [] as $descriptor) {
            if (!\is_array($descriptor) || 'specialization.branch' !== ($descriptor['action'] ?? null)) {
                continue;
            }

            $branch = (string) ($descriptor['branch'] ?? '');
            if ('' !== $branch) {
                return $branch;
            }
        }

        return null;
    }

    /**
     * Les nœuds **propres** a cet arbre : ceux qu'aucun autre ne partage.
     *
     * @return list<Skill>
     */
    private function nodesOf(Domain $domain): array
    {
        $nodes = [];

        foreach ($this->entityManager->getRepository(Skill::class)->findAll() as $skill) {
            if (1 === $skill->getDomains()->count() && $skill->getDomains()->contains($domain)) {
                $nodes[] = $skill;
            }
        }

        return $nodes;
    }
}
