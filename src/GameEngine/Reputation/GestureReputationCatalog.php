<?php

namespace App\GameEngine\Reputation;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Le routage geste → faction, lu dans `config/game/factions.yaml` (FAC-02).
 *
 * GAME_WORLD § 6.4 b : les quetes amorcent, les gestes font le regime de
 * croisiere. Chaque route associe un geste du jeu (vendre a l'hotel des
 * ventes, abattre un mort-vivant, fondre une materia...) a la faction qu'il
 * nourrit. Le catalogue est declaratif — ajouter un geste, c'est ajouter une
 * ligne dans le fichier, jamais toucher une classe.
 *
 * **Une route peut viser une faction pas encore semee.** `materia_melt` vise
 * la Fonderie (FAC-04), `grey_market_sale` le marche gris (FAC-06) : le
 * crochet est declare des maintenant et reste **inerte** tant que la cible
 * n'existe pas — meme doctrine que les paires de tension de FAC-01.
 *
 * **Ce que le loader refuse.** Une route sans faction est un geste qui
 * nourrit le vide ; un montant nul ou negatif est un non-geste ecrit comme un
 * geste ; un plafond absent ou nul ferait des gestes une pompe sans fond.
 * Aucun de ces defauts ne se verrait a l'execution : ils produiraient des
 * silences, pas des erreurs.
 */
class GestureReputationCatalog
{
    /**
     * @var array{
     *   daily_cap: int,
     *   routes: array<string, array{faction: string, amount: int|null}>,
     *   undead_slugs: list<string>
     * }|null
     */
    private ?array $definition = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/factions.yaml';
    }

    /**
     * La route d'un geste : la faction visee, et le montant si le geste en
     * porte un fixe (`null` = le montant vient de l'appelant, p. ex. le
     * bareme par palier d'un kill).
     *
     * @return array{faction: string, amount: int|null}|null
     */
    public function routeFor(string $gesture): ?array
    {
        return $this->definition()['routes'][$gesture] ?? null;
    }

    /**
     * @return list<string> les gestes routes
     */
    public function gestures(): array
    {
        return array_keys($this->definition()['routes']);
    }

    /**
     * Le plafond de gain par faction et par jour sur les gestes.
     */
    public function dailyCap(): int
    {
        return $this->definition()['daily_cap'];
    }

    /**
     * @return list<string>
     */
    public function undeadSlugs(): array
    {
        return $this->definition()['undead_slugs'];
    }

    public function isUndead(string $monsterSlug): bool
    {
        return \in_array($monsterSlug, $this->definition()['undead_slugs'], true);
    }

    /**
     * @return array{
     *   daily_cap: int,
     *   routes: array<string, array{faction: string, amount: int|null}>,
     *   undead_slugs: list<string>
     * }
     */
    private function definition(): array
    {
        if ($this->definition === null) {
            $this->definition = $this->load($this->defaultFile());
        }

        return $this->definition;
    }

    /**
     * @return array{
     *   daily_cap: int,
     *   routes: array<string, array{faction: string, amount: int|null}>,
     *   undead_slugs: list<string>
     * }
     *
     * @throws FactionTensionDefinitionException
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new FactionTensionDefinitionException(sprintf('Faction gesture catalogue not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new FactionTensionDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new FactionTensionDefinitionException(sprintf('Faction gesture catalogue "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{
     *   daily_cap: int,
     *   routes: array<string, array{faction: string, amount: int|null}>,
     *   undead_slugs: list<string>
     * }
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $gestures = $raw['gestures'] ?? null;
        if (!\is_array($gestures)) {
            throw new FactionTensionDefinitionException(sprintf('Catalogue "%s" must declare a "gestures" block.', $source));
        }

        $dailyCap = $gestures['daily_cap'] ?? null;
        if (!\is_int($dailyCap) || $dailyCap <= 0) {
            // Sans plafond, un geste repetable devient une pompe : le regime
            // de croisiere doit avoir une vitesse de croisiere.
            throw new FactionTensionDefinitionException(sprintf('The gestures daily cap of "%s" must be a positive integer.', $source));
        }

        $rawRoutes = $gestures['routes'] ?? null;
        if (!\is_array($rawRoutes) || [] === $rawRoutes) {
            throw new FactionTensionDefinitionException(sprintf('The gestures block of "%s" must declare at least one route.', $source));
        }

        $routes = [];
        foreach ($rawRoutes as $gesture => $route) {
            if (!\is_string($gesture) || trim($gesture) === '' || !\is_array($route)) {
                throw new FactionTensionDefinitionException(sprintf('Each gesture route of "%s" must map a gesture key to a mapping.', $source));
            }

            $faction = $route['faction'] ?? null;
            if (!\is_string($faction) || trim($faction) === '') {
                // Un geste sans destinataire nourrirait le vide, en silence.
                throw new FactionTensionDefinitionException(sprintf('The gesture "%s" of "%s" needs a "faction" slug.', $gesture, $source));
            }

            $amount = $route['amount'] ?? null;
            if ($amount !== null && (!\is_int($amount) || $amount <= 0)) {
                throw new FactionTensionDefinitionException(sprintf('The amount of gesture "%s" in "%s" must be a positive integer when present.', $gesture, $source));
            }

            $routes[$gesture] = ['faction' => $faction, 'amount' => $amount];
        }

        $undeadSlugs = [];
        $rawUndead = $gestures['undead_slugs'] ?? [];
        if (!\is_array($rawUndead)) {
            throw new FactionTensionDefinitionException(sprintf('"gestures.undead_slugs" of "%s" must be a list.', $source));
        }
        foreach (array_values($rawUndead) as $slug) {
            if (!\is_string($slug) || trim($slug) === '') {
                throw new FactionTensionDefinitionException(sprintf('Undead slugs of "%s" must be strings.', $source));
            }
            $undeadSlugs[] = $slug;
        }

        return [
            'daily_cap' => $dailyCap,
            'routes' => $routes,
            'undead_slugs' => $undeadSlugs,
        ];
    }
}
