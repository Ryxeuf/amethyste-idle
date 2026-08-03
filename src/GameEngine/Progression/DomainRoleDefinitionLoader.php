<?php

namespace App\GameEngine\Progression;

use App\Enum\DomainRole;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation des palettes de fonction (ARC-01).
 *
 * Le fichier est **obligatoire** et **complet** : les quatre fonctions y sont,
 * ou la lecture echoue. Une palette absente ne serait pas une fonction sans
 * contrainte, ce serait une fonction dont l'auteur pourrait tout acheter — et
 * rien ne le dirait.
 *
 * Trois refus structurels, un par regle du canon (GAME_ARCHETYPES § 5) :
 *
 * - **cinq leviers par palette** — un principal, quatre secondaires, sans
 *   doublon interne : une palette a quatre leviers rendrait la regle des 80/20
 *   plus dure pour une fonction que pour les autres, sans que ce soit ecrit ;
 * - **le principal est exclusif** — `power`, `grip`, `mending` et `guard`
 *   n'apparaissent que dans une palette. Deux fonctions qui partageraient leur
 *   cœur seraient la meme fonction sous deux noms ;
 * - **au plus deux secondaires communs** a deux palettes — au-dela, les deux
 *   fonctions achetent la meme chose et le troisieme axe cesse de distinguer.
 */
class DomainRoleDefinitionLoader
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/domain_roles.yaml';
    }

    /**
     * @return array{budget: array{total: int, min_in_palette: int, max_off_palette: int, max_off_palette_levers: int}, capstone_cost: int, roles: array<string, array{promise: string, structural_cost: string, primary: string, primary_cap: int, secondary: list<string>, intents: array<string, int>}>}
     *
     * @throws DomainRoleDefinitionException si le fichier est absent, illisible ou invalide
     */
    public function load(?string $path = null): array
    {
        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new DomainRoleDefinitionException(sprintf('Domain roles config not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new DomainRoleDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new DomainRoleDefinitionException(sprintf('Domain roles config "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{budget: array{total: int, min_in_palette: int, max_off_palette: int, max_off_palette_levers: int}, capstone_cost: int, roles: array<string, array{promise: string, structural_cost: string, primary: string, primary_cap: int, secondary: list<string>, intents: array<string, int>}>}
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        return [
            'budget' => $this->normalizeBudget($raw['budget'] ?? null, $source),
            'capstone_cost' => $this->positiveInt($raw['capstone_cost'] ?? null, 'capstone_cost', $source),
            'roles' => $this->normalizeRoles($raw['roles'] ?? null, $source),
        ];
    }

    /**
     * La palette **effective** d'une fonction (§ 5.0) : ce qui reste achetable
     * hors du sommet, une fois le capstone paye sur le principal.
     *
     * C'est un calcul, jamais une donnee — le canon insiste : « la palette
     * effective se calcule, elle ne se lit pas ». A `guard` (plafond 15) et un
     * capstone a 14, il reste 1 pb : moins que le nœud le plus modeste, donc
     * rien. L'arbre d'encaisse repartit alors quatre leviers et pose son
     * sommet, ce qui **est** la decision du canon et non une amputation.
     */
    public function remainingOnPrimary(DomainRole $role, ?string $path = null): int
    {
        $config = $this->load($path);

        return $config['roles'][$role->value]['primary_cap'] - $config['capstone_cost'];
    }

    /**
     * @return array{total: int, min_in_palette: int, max_off_palette: int, max_off_palette_levers: int}
     */
    private function normalizeBudget(mixed $budget, string $source): array
    {
        if (!\is_array($budget)) {
            throw new DomainRoleDefinitionException(sprintf('Domain roles config "%s" must declare "budget".', $source));
        }

        $total = $this->positiveInt($budget['total'] ?? null, 'budget.total', $source);
        $minInPalette = $this->positiveInt($budget['min_in_palette'] ?? null, 'budget.min_in_palette', $source);
        $maxOffPalette = $this->positiveInt($budget['max_off_palette'] ?? null, 'budget.max_off_palette', $source);
        $maxOffLevers = $this->positiveInt($budget['max_off_palette_levers'] ?? null, 'budget.max_off_palette_levers', $source);

        // La regle des 80/20 doit **fermer** : ce qui est impose dans la
        // palette plus ce qui est tolere dehors vaut exactement le budget.
        // Sinon l'auteur dispose de points que personne n'a decides.
        if ($minInPalette + $maxOffPalette !== $total) {
            throw new DomainRoleDefinitionException(sprintf(
                'The 80/20 rule does not close in "%s": %d in palette + %d outside must equal the %d point budget.',
                $source,
                $minInPalette,
                $maxOffPalette,
                $total,
            ));
        }

        return [
            'total' => $total,
            'min_in_palette' => $minInPalette,
            'max_off_palette' => $maxOffPalette,
            'max_off_palette_levers' => $maxOffLevers,
        ];
    }

    /**
     * @return array<string, array{promise: string, structural_cost: string, primary: string, primary_cap: int, secondary: list<string>, intents: array<string, int>}>
     */
    private function normalizeRoles(mixed $raw, string $source): array
    {
        if (!\is_array($raw)) {
            throw new DomainRoleDefinitionException(sprintf('Domain roles config "%s" must declare "roles".', $source));
        }

        $roles = [];
        $primaries = [];

        foreach (DomainRole::cases() as $role) {
            $entry = $raw[$role->value] ?? null;
            if (!\is_array($entry)) {
                throw new DomainRoleDefinitionException(sprintf('Function "%s" has no palette in "%s".', $role->value, $source));
            }

            $promise = $this->nonEmptyString($entry['promise'] ?? null, $role->value . '.promise', $source);
            // § 10.1 : « si le cout est vide, l'archetype n'est pas fini ».
            $cost = $this->nonEmptyString($entry['structural_cost'] ?? null, $role->value . '.structural_cost', $source);
            $primary = $this->nonEmptyString($entry['primary'] ?? null, $role->value . '.primary', $source);
            $cap = $this->positiveInt($entry['primary_cap'] ?? null, $role->value . '.primary_cap', $source);
            $secondary = $this->leverList($entry['secondary'] ?? null, $role->value . '.secondary', $source);

            if (\count($secondary) !== 4) {
                throw new DomainRoleDefinitionException(sprintf(
                    'Function "%s" declares %d secondary levers in "%s": a palette is one primary and four secondaries.',
                    $role->value,
                    \count($secondary),
                    $source,
                ));
            }

            if (\in_array($primary, $secondary, true)) {
                throw new DomainRoleDefinitionException(sprintf('Function "%s" lists its primary lever twice in "%s".', $role->value, $source));
            }

            if (isset($primaries[$primary])) {
                throw new DomainRoleDefinitionException(sprintf(
                    'Lever "%s" is the primary of both "%s" and "%s" in "%s": a primary is exclusive.',
                    $primary,
                    $primaries[$primary],
                    $role->value,
                    $source,
                ));
            }
            $primaries[$primary] = $role->value;

            $roles[$role->value] = [
                'promise' => $promise,
                'structural_cost' => $cost,
                'primary' => $primary,
                'primary_cap' => $cap,
                'secondary' => $secondary,
                'intents' => $this->intents($entry['intents'] ?? null, $role->value, $source),
            ];
        }

        $this->assertPalettesStayApart($roles, $source);

        return $roles;
    }

    /**
     * Deux palettes ne partagent jamais plus de deux leviers secondaires.
     *
     * @param array<string, array{primary: string, secondary: list<string>}> $roles
     */
    private function assertPalettesStayApart(array $roles, string $source): void
    {
        $names = array_keys($roles);

        foreach ($names as $i => $left) {
            foreach (\array_slice($names, $i + 1) as $right) {
                $shared = array_values(array_intersect($roles[$left]['secondary'], $roles[$right]['secondary']));

                if (\count($shared) > 2) {
                    throw new DomainRoleDefinitionException(sprintf(
                        'Functions "%s" and "%s" share %d secondary levers (%s) in "%s": two is the most two palettes may have in common.',
                        $left,
                        $right,
                        \count($shared),
                        implode(', ', $shared),
                        $source,
                    ));
                }

                // Le principal de l'un peut etre le secondaire de l'autre — une
                // teinte y donne acces a 10 pb —, mais jamais son principal :
                // ce cas est deja refuse a la lecture de chaque palette.
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function intents(mixed $raw, string $role, string $source): array
    {
        if (!\is_array($raw) || $raw === []) {
            throw new DomainRoleDefinitionException(sprintf('Function "%s" must declare at least one intent requirement in "%s".', $role, $source));
        }

        $intents = [];
        foreach ($raw as $intent => $count) {
            if (!\is_string($intent) || trim($intent) === '') {
                throw new DomainRoleDefinitionException(sprintf('Function "%s" has a nameless intent in "%s".', $role, $source));
            }
            $intents[$intent] = $this->positiveInt($count, sprintf('%s.intents.%s', $role, $intent), $source);
        }

        return $intents;
    }

    /**
     * @return list<string>
     */
    private function leverList(mixed $values, string $key, string $source): array
    {
        if (!\is_array($values)) {
            throw new DomainRoleDefinitionException(sprintf('"%s" must be a list in "%s".', $key, $source));
        }

        $levers = [];
        foreach ($values as $value) {
            if (!\is_string($value) || trim($value) === '') {
                throw new DomainRoleDefinitionException(sprintf('"%s" must only contain lever names in "%s".', $key, $source));
            }
            if (\in_array($value, $levers, true)) {
                throw new DomainRoleDefinitionException(sprintf('"%s" lists "%s" twice in "%s".', $key, $value, $source));
            }
            $levers[] = $value;
        }

        return $levers;
    }

    private function nonEmptyString(mixed $value, string $key, string $source): string
    {
        if (!\is_string($value) || trim($value) === '') {
            throw new DomainRoleDefinitionException(sprintf('"%s" must be a non-empty string in "%s".', $key, $source));
        }

        return $value;
    }

    private function positiveInt(mixed $value, string $key, string $source): int
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            throw new DomainRoleDefinitionException(sprintf('"%s" must be a positive integer in "%s".', $key, $source));
        }

        return (int) $value;
    }
}
