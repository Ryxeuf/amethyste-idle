<?php

namespace App\GameEngine\Crafting;

use App\Enum\CraftSpecialization;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Les branches terminales de chaque arbre d'artisanat (DOM-04).
 *
 * GAME_DOMAINS § 6 : « une branche terminale exclusive **par arbre** ». Le
 * catalogue est declaratif — ajouter un metier, c'est ajouter un bloc dans
 * `config/game/craft_branches.yaml`, jamais toucher une classe.
 *
 * **Le loader refuse un arbre a moins de deux branches.** Une branche unique
 * n'est pas un choix : elle se prendrait toujours, et le renoncement — qui est
 * tout le sujet de la specialisation — disparaitrait sans que rien ne le dise.
 */
class CraftBranchCatalog
{
    /**
     * @var array<string, array{label: string, branches: array<string, array{label: string, description: string}>}>|null
     */
    private ?array $crafts = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/craft_branches.yaml';
    }

    /**
     * Les branches d'un arbre, indexees par cle.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public function branchesOf(CraftSpecialization $craft): array
    {
        return $this->crafts()[$craft->value]['branches'] ?? [];
    }

    public function hasBranch(CraftSpecialization $craft, string $branch): bool
    {
        return isset($this->branchesOf($craft)[$branch]);
    }

    public function labelOf(CraftSpecialization $craft, string $branch): ?string
    {
        return $this->branchesOf($craft)[$branch]['label'] ?? null;
    }

    /**
     * La premiere branche declaree d'un arbre.
     *
     * Sert **uniquement** a la migration des joueurs deja specialises : leur
     * ancienne specialisation designait un metier et pas une branche, et il
     * fallait bien en choisir une. Le respec existe precisement pour que ce
     * choix impose ne soit pas definitif.
     */
    public function firstBranchOf(CraftSpecialization $craft): ?string
    {
        return array_key_first($this->branchesOf($craft));
    }

    /**
     * Les arbres qui offrent une specialisation.
     *
     * @return list<CraftSpecialization>
     */
    public function specializableCrafts(): array
    {
        $crafts = [];
        foreach (CraftSpecialization::cases() as $craft) {
            if ($this->branchesOf($craft) !== []) {
                $crafts[] = $craft;
            }
        }

        return $crafts;
    }

    /**
     * @return array<string, array{label: string, branches: array<string, array{label: string, description: string}>}>
     */
    private function crafts(): array
    {
        if ($this->crafts === null) {
            $this->crafts = $this->load($this->defaultFile());
        }

        return $this->crafts;
    }

    /**
     * @return array<string, array{label: string, branches: array<string, array{label: string, description: string}>}>
     *
     * @throws CraftBranchDefinitionException
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new CraftBranchDefinitionException(sprintf('Craft branch catalogue not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new CraftBranchDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new CraftBranchDefinitionException(sprintf('Craft branch catalogue "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<string, array{label: string, branches: array<string, array{label: string, description: string}>}>
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $crafts = $raw['crafts'] ?? null;
        if (!\is_array($crafts) || $crafts === []) {
            throw new CraftBranchDefinitionException(sprintf('Craft branch catalogue "%s" must declare at least one craft.', $source));
        }

        $normalized = [];
        foreach ($crafts as $slug => $entry) {
            if (!\is_string($slug) || CraftSpecialization::tryFrom($slug) === null) {
                throw new CraftBranchDefinitionException(sprintf('"%s" is not a known craft in "%s".', \is_string($slug) ? $slug : \gettype($slug), $source));
            }

            if (!\is_array($entry) || !\is_string($entry['label'] ?? null)) {
                throw new CraftBranchDefinitionException(sprintf('Craft "%s" needs a "label" in "%s".', $slug, $source));
            }

            $branches = $entry['branches'] ?? null;
            if (!\is_array($branches) || \count($branches) < 2) {
                // Une branche unique n'est pas un choix : elle se prendrait
                // toujours, et le renoncement disparaitrait en silence.
                throw new CraftBranchDefinitionException(sprintf('Craft "%s" needs at least two branches in "%s": one branch is not a choice.', $slug, $source));
            }

            $normalizedBranches = [];
            foreach ($branches as $key => $branch) {
                if (!\is_string($key) || trim($key) === '') {
                    throw new CraftBranchDefinitionException(sprintf('Branch keys of craft "%s" must be slugs in "%s".', $slug, $source));
                }
                if (!\is_array($branch) || !\is_string($branch['label'] ?? null) || !\is_string($branch['description'] ?? null)) {
                    throw new CraftBranchDefinitionException(sprintf('Branch "%s" of craft "%s" needs a label and a description in "%s".', $key, $slug, $source));
                }

                $normalizedBranches[$key] = ['label' => $branch['label'], 'description' => $branch['description']];
            }

            $normalized[$slug] = ['label' => $entry['label'], 'branches' => $normalizedBranches];
        }

        return $normalized;
    }
}
