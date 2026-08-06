<?php

namespace App\GameEngine\Progression;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Les fourches des arbres de combat (ARC-14a).
 *
 * GAME_ARCHETYPES § 6.1 bis. Le palier 3 ecrit **deux branches** et une seule
 * s'apprend : *l'arbre ecrit 18 nœuds et 60 pb, le personnage en apprend 15 et
 * en porte 50.* C'est cette regle qui reconcilie les deux nombres du canon —
 * sans elle, un arbre au gabarit couterait 540 points et non 390.
 *
 * Le catalogue suit `CraftBranchCatalog` (DOM-04) de pres, et pour la meme
 * raison : ajouter une fourche doit etre un bloc de configuration, jamais une
 * ligne de code. Ce qu'il ne partage **pas** avec lui est la cle — un metier se
 * choisit par `CraftSpecialization`, un arbre de combat par son domaine — et
 * les melanger aurait fait porter au personnage une seule specialisation pour
 * les deux mondes, c'est-a-dire exactement le defaut que DOM-04 a corrige.
 *
 * **Deux branches, jamais trois** : le choix doit se raconter en une phrase.
 * Le chargeur le refuse plutot que de l'accepter en silence — un eventail rend
 * l'identite illisible, et c'est l'identite qui est le sujet.
 */
class CombatBranchCatalog
{
    /**
     * Le nombre de branches qu'une fourche porte, exactement.
     *
     * Une seule branche ne serait pas une fourche ; trois n'en seraient plus
     * une non plus — c'est un eventail, et il ne se raconte pas.
     */
    public const BRANCHES_PER_TREE = 2;

    /** @var array<string, array{label: string, branches: array<string, array{label: string, description: string, accord: string}>}>|null */
    private ?array $trees = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/combat_branches.yaml';
    }

    /**
     * @return array<string, array{label: string, branches: array<string, array{label: string, description: string, accord: string}>}>
     */
    public function trees(): array
    {
        if ($this->trees === null) {
            $this->trees = $this->load($this->defaultFile());
        }

        return $this->trees;
    }

    /**
     * Les arbres qui ont une fourche — donc ceux qu'ARC-07 peut ecrire.
     *
     * @return list<string>
     */
    public function forkedTrees(): array
    {
        return array_keys($this->trees());
    }

    /**
     * La cle de l'arbre qui porte ce libelle.
     *
     * **Le projet a deux identifiants de domaine, et c'est la source d'erreur
     * a laquelle ce jalon s'est heurte** : la cle de fixture est anglaise
     * (`pyromancy`, celle qu'`equipment_ports.yaml` emploie deja) quand
     * `Domain::getSlug()` derive du titre francais (`pyromancien`). Le
     * catalogue garde la cle anglaise, comme ses voisins, et fait le pont par
     * le **libelle** — qui **est** le titre du domaine, et qu'un test verifie
     * arbre par arbre pour que le pont ne casse pas en silence.
     */
    public function keyForLabel(string $label): ?string
    {
        foreach ($this->trees() as $key => $tree) {
            if ($tree['label'] === $label) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return array<string, array{label: string, description: string, accord: string}>
     */
    public function branchesOf(string $tree): array
    {
        return $this->trees()[$tree]['branches'] ?? [];
    }

    public function hasBranch(string $tree, string $branch): bool
    {
        return isset($this->trees()[$tree]['branches'][$branch]);
    }

    public function labelOf(string $tree, string $branch): ?string
    {
        return $this->trees()[$tree]['branches'][$branch]['label'] ?? null;
    }

    /**
     * Le geste que cette branche ouvre.
     *
     * **C'est ce qui decide si la fourche est un choix ou une decoration** :
     * mesure au § 9 bis, deux branches qui ne different que par leurs passifs
     * produisent le meme combat au tour pres (11 contre 11). Une branche sans
     * accord est donc refusee au chargement — le defaut serait invisible en
     * donnee et fatal en jeu.
     */
    public function accordOf(string $tree, string $branch): ?string
    {
        return $this->trees()[$tree]['branches'][$branch]['accord'] ?? null;
    }

    /**
     * @return array<string, array{label: string, branches: array<string, array{label: string, description: string, accord: string}>}>
     *
     * @throws CombatBranchDefinitionException
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new CombatBranchDefinitionException(sprintf('Combat branches config not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new CombatBranchDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new CombatBranchDefinitionException(sprintf('Combat branches config "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<string, array{label: string, branches: array<string, array{label: string, description: string, accord: string}>}>
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $trees = $raw['trees'] ?? null;
        if (!\is_array($trees) || $trees === []) {
            throw new CombatBranchDefinitionException(sprintf('"%s" declares no tree.', $source));
        }

        $normalized = [];
        foreach ($trees as $key => $tree) {
            if (!\is_string($key) || !\is_array($tree)) {
                throw new CombatBranchDefinitionException(sprintf('"%s": each tree must be a mapping keyed by its slug.', $source));
            }

            $label = $tree['label'] ?? null;
            if (!\is_string($label) || trim($label) === '') {
                throw new CombatBranchDefinitionException(sprintf('"%s": tree "%s" needs a label.', $source, $key));
            }

            $normalized[$key] = ['label' => $label, 'branches' => $this->normalizeBranches($tree['branches'] ?? null, $key, $source)];
        }

        return $normalized;
    }

    /**
     * @return array<string, array{label: string, description: string, accord: string}>
     */
    private function normalizeBranches(mixed $branches, string $tree, string $source): array
    {
        if (!\is_array($branches) || \count($branches) !== self::BRANCHES_PER_TREE) {
            throw new CombatBranchDefinitionException(sprintf('"%s": tree "%s" must declare exactly %d branches. One is not a fork; three is a fan, and a fan does not tell a story.', $source, $tree, self::BRANCHES_PER_TREE));
        }

        $normalized = [];
        foreach ($branches as $key => $branch) {
            if (!\is_string($key) || !\is_array($branch)) {
                throw new CombatBranchDefinitionException(sprintf('"%s": tree "%s" has a branch that is not a mapping.', $source, $tree));
            }

            foreach (['label', 'description', 'accord'] as $field) {
                $value = $branch[$field] ?? null;
                if (!\is_string($value) || trim($value) === '') {
                    throw new CombatBranchDefinitionException(sprintf('"%s": branch "%s" of tree "%s" needs a %s. A branch without an accord is a decoration — two branches that differ only by their passives produce the same fight, turn for turn.', $source, $key, $tree, $field));
                }
            }

            $normalized[$key] = [
                'label' => (string) $branch['label'],
                'description' => (string) $branch['description'],
                'accord' => (string) $branch['accord'],
            ];
        }

        return $normalized;
    }
}
