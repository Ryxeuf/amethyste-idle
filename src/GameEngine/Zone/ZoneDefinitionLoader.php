<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Zone;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation du format declaratif de zone (ZON-11).
 *
 * Lit un fichier YAML (cf. `config/game/zones/*.yaml`) et le normalise en une
 * structure homogene consommee par ZoneImporter — a la fois par la commande
 * `app:zone:import` et par les fixtures (source de verite unique). La validation
 * ne touche jamais la base : elle est purement structurelle et unitairement
 * testable.
 */
class ZoneDefinitionLoader
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    /**
     * Fichier de definition du World 1 (defaut de la commande et des fixtures).
     */
    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/zones/world_1.yaml';
    }

    /**
     * @return array{zones: list<array<string, mixed>>, connections: list<array<string, mixed>>}
     *
     * @throws ZoneDefinitionException si le fichier est absent, illisible ou invalide
     */
    public function loadFile(string $path): array
    {
        if (!is_file($path)) {
            throw new ZoneDefinitionException(sprintf('Zone definition file not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ZoneDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new ZoneDefinitionException(sprintf('Zone definition "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{zones: list<array<string, mixed>>, connections: list<array<string, mixed>>}
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $rawZones = $raw['zones'] ?? [];
        if (!\is_array($rawZones) || $rawZones === []) {
            throw new ZoneDefinitionException(sprintf('Zone definition "%s" must declare a non-empty "zones" mapping.', $source));
        }

        $zones = [];
        $slugs = [];
        foreach ($rawZones as $slug => $definition) {
            if (!\is_string($slug) || trim($slug) === '') {
                throw new ZoneDefinitionException(sprintf('Zone slug must be a non-empty string in "%s".', $source));
            }
            if (!\is_array($definition)) {
                throw new ZoneDefinitionException(sprintf('Zone "%s" must be a mapping in "%s".', $slug, $source));
            }
            $zones[] = $this->normalizeZone($slug, $definition, $source);
            $slugs[$slug] = true;
        }

        $connections = $this->normalizeConnections($raw['connections'] ?? [], $slugs, $source);

        return ['zones' => $zones, 'connections' => $connections];
    }

    /**
     * @param array<array-key, mixed> $definition
     *
     * @return array<string, mixed>
     */
    private function normalizeZone(string $slug, array $definition, string $source): array
    {
        $name = $definition['name'] ?? null;
        if (!\is_string($name) || trim($name) === '') {
            throw new ZoneDefinitionException(sprintf('Zone "%s" is missing a "name" in "%s".', $slug, $source));
        }

        $type = $definition['type'] ?? Zone::TYPE_WILDERNESS;
        if (!\is_string($type) || !\in_array($type, Zone::getTypes(), true)) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" has an unknown type "%s" in "%s". Valid types: %s.', $slug, \is_string($type) ? $type : \gettype($type), $source, implode(', ', Zone::getTypes())));
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'name_en' => \is_string($definition['name_en'] ?? null) ? $definition['name_en'] : null,
            'description' => \is_string($definition['description'] ?? null) ? $definition['description'] : null,
            'description_en' => \is_string($definition['description_en'] ?? null) ? $definition['description_en'] : null,
            'type' => $type,
            'safe' => (bool) ($definition['safe'] ?? false),
            'enabled' => (bool) ($definition['enabled'] ?? true),
            'source_map' => \is_string($definition['source_map'] ?? null) ? $definition['source_map'] : null,
            // Position sur la carte du monde illustree (ZON-16), en pourcentage
            // 0-100 du cadre ; null = zone non placee (masquee de la carte).
            'map_x' => isset($definition['map_x']) ? (int) $definition['map_x'] : null,
            'map_y' => isset($definition['map_y']) ? (int) $definition['map_y'] : null,
            'explore' => $this->normalizeExplore($slug, $definition['explore'] ?? null, $source),
            'gather' => $this->normalizeGather($slug, $definition['gather'] ?? null, $source),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeExplore(string $slug, mixed $explore, string $source): ?array
    {
        if ($explore === null) {
            return null;
        }
        if (!\is_array($explore)) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid "explore" block in "%s".', $slug, $source));
        }

        $config = [];
        if (isset($explore['weights'])) {
            if (!\is_array($explore['weights'])) {
                throw new ZoneDefinitionException(sprintf('Zone "%s" has invalid "explore.weights" in "%s".', $slug, $source));
            }
            $weights = [];
            foreach ($explore['weights'] as $event => $weight) {
                if (\is_string($event) && is_numeric($weight)) {
                    $weights[$event] = (int) $weight;
                }
            }
            if ($weights !== []) {
                $config['weights'] = $weights;
            }
        }
        foreach (['chest_gils_min', 'chest_gils_max'] as $key) {
            if (isset($explore[$key]) && is_numeric($explore[$key])) {
                $config[$key] = (int) $explore[$key];
            }
        }

        return $config === [] ? null : $config;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function normalizeGather(string $slug, mixed $gather, string $source): ?array
    {
        if ($gather === null) {
            return null;
        }
        if (!\is_array($gather)) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid "gather" block in "%s".', $slug, $source));
        }

        $resources = [];
        foreach ($gather as $resource) {
            if (!\is_array($resource)) {
                throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid gather resource in "%s".', $slug, $source));
            }
            foreach (['slug', 'item', 'profession'] as $required) {
                if (!\is_string($resource[$required] ?? null) || trim((string) $resource[$required]) === '') {
                    throw new ZoneDefinitionException(sprintf('Gather resource of zone "%s" is missing "%s" in "%s".', $slug, $required, $source));
                }
            }
            $resources[] = [
                'slug' => (string) $resource['slug'],
                'item' => (string) $resource['item'],
                'profession' => (string) $resource['profession'],
                'capacity' => max(1, (int) ($resource['capacity'] ?? 1)),
                'respawn_seconds' => max(0, (int) ($resource['respawn_seconds'] ?? 0)),
                'yield_min' => max(1, (int) ($resource['yield_min'] ?? 1)),
                'yield_max' => max(1, (int) ($resource['yield_max'] ?? 1)),
            ];
        }

        return $resources === [] ? null : $resources;
    }

    /**
     * @param array<string, bool> $knownSlugs
     *
     * @return list<array<string, mixed>>
     */
    private function normalizeConnections(mixed $rawConnections, array $knownSlugs, string $source): array
    {
        if ($rawConnections === null) {
            return [];
        }
        if (!\is_array($rawConnections)) {
            throw new ZoneDefinitionException(sprintf('"connections" must be a list in "%s".', $source));
        }

        $connections = [];
        foreach ($rawConnections as $connection) {
            if (!\is_array($connection)) {
                throw new ZoneDefinitionException(sprintf('Each connection must be a mapping in "%s".', $source));
            }
            $from = $connection['from'] ?? null;
            $to = $connection['to'] ?? null;
            foreach (['from' => $from, 'to' => $to] as $key => $value) {
                if (!\is_string($value) || !isset($knownSlugs[$value])) {
                    throw new ZoneDefinitionException(sprintf('Connection "%s" references unknown zone "%s" in "%s".', $key, \is_string($value) ? $value : \gettype($value), $source));
                }
            }
            /** @var string $from */
            /** @var string $to */
            if ($from === $to) {
                throw new ZoneDefinitionException(sprintf('Connection cannot loop on itself ("%s") in "%s".', $from, $source));
            }

            $connections[] = [
                'from' => $from,
                'to' => $to,
                'travel_seconds' => max(0, (int) ($connection['travel_seconds'] ?? 60)),
                'bidirectional' => (bool) ($connection['bidirectional'] ?? false),
                'requires_discovery' => (bool) ($connection['requires_discovery'] ?? false),
                'enabled' => (bool) ($connection['enabled'] ?? true),
            ];
        }

        return $connections;
    }
}
