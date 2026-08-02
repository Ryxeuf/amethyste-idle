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
            // Palier de la zone (BES-01, GAME_ZONES §2) : T0 (sur) a T4.
            // C'est de lui que les monstres tiennent leur palier.
            'tier' => max(0, min(4, (int) ($definition['tier'] ?? 0))),
            'enabled' => (bool) ($definition['enabled'] ?? true),
            'source_map' => \is_string($definition['source_map'] ?? null) ? $definition['source_map'] : null,
            // Position sur la carte du monde illustree (ZON-16), en pourcentage
            // 0-100 du cadre ; null = zone non placee (masquee de la carte).
            'map_x' => isset($definition['map_x']) ? (int) $definition['map_x'] : null,
            'map_y' => isset($definition['map_y']) ? (int) $definition['map_y'] : null,
            // Contour cliquable sur la carte illustree (meme espace 0-100).
            'map_shape' => $this->normalizeMapShape($slug, $definition['map_shape'] ?? null, $source),
            // Bandeau de la zone (ZON-41), sous `public/images/`.
            'illustration' => $this->normalizeIllustration($slug, $definition['illustration'] ?? null, $source),
            'explore' => $this->normalizeExplore($slug, $definition['explore'] ?? null, $source),
            'gather' => $this->normalizeGather($slug, $definition['gather'] ?? null, $source),
            'mobs' => $this->normalizeMobs($slug, $definition['mobs'] ?? null, $source),
            'pnjs' => $this->normalizePnjs($slug, $definition['pnjs'] ?? null, $source),
        ];
    }

    /**
     * Bandeau de la zone, sous `public/images/` (ZON-41).
     *
     * Le champ existait sur l'entite, etait editable en administration et etait
     * **rendu** par l'ecran de zone — mais aucun chemin ne partait de la donnee.
     * Le defaut n'etait donc pas « le champ est vide » : c'est qu'une valeur
     * saisie a la main disparaissait au prochain rechargement des fixtures, et
     * n'existait dans aucun autre environnement. Un champ volatil.
     *
     * Trois regles, calquees sur `normalizeMapShape()` — *un chemin malforme
     * doit casser l'import, pas rendre une balise `<img>` morte* :
     *
     * 1. l'absence est valide, et reste la norme tant que l'image n'existe pas ;
     * 2. la forme est close (`zones/<nom>.webp`) : ni chemin absolu, ni `..`, ni
     *    extension libre — un champ qui part dans un attribut `src` ne doit pas
     *    pouvoir designer autre chose qu'un bandeau ;
     * 3. le nom de fichier **est** le slug de la zone. C'est la loi de nommage
     *    du document de prompts, tenue par le code plutot que par la discipline :
     *    une image ne peut pas se retrouver sur la mauvaise zone.
     *
     * Ce qui n'est **pas** verifie ici : que le fichier existe. Le loader ne
     * touche pas au disque, et les douze bandeaux arriveront un par un —
     * l'import le signale en avertissement (`ZoneImporter`), il ne le refuse pas.
     */
    private function normalizeIllustration(string $slug, mixed $illustration, string $source): ?string
    {
        if (null === $illustration) {
            return null;
        }
        if (!\is_string($illustration)) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid "illustration" in "%s": expected a string.', $slug, $source));
        }

        $expected = 'zones/' . $slug . '.webp';
        if ($illustration !== $expected) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" declares the illustration "%s" in "%s": expected exactly "%s".', $slug, $illustration, $source, $expected));
        }

        return $illustration;
    }

    /**
     * Contour de zone sur la carte illustree : « x,y x,y x,y… », dans le meme
     * espace 0-100 que `map_x`/`map_y`.
     *
     * La donnee part telle quelle dans l'attribut `points` d'un `<polygon>` SVG :
     * elle est validee ici plutot qu'echappee a l'affichage, parce qu'un contour
     * malforme doit casser l'import — pas rendre une carte muette.
     */
    private function normalizeMapShape(string $slug, mixed $shape, string $source): ?string
    {
        if (null === $shape) {
            return null;
        }
        if (!\is_string($shape)) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid "map_shape" in "%s": expected a string of "x,y" points.', $slug, $source));
        }

        $points = preg_split('/\s+/', trim($shape), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        if (\count($points) < 3) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" has a "map_shape" with %d point(s) in "%s": a polygon needs at least 3.', $slug, \count($points), $source));
        }

        foreach ($points as $point) {
            if (1 !== preg_match('/^(\d{1,3}),(\d{1,3})$/', $point, $matches)) {
                throw new ZoneDefinitionException(sprintf('Zone "%s" has a malformed point "%s" in its "map_shape" in "%s": expected "x,y" with integers.', $slug, $point, $source));
            }
            if ((int) $matches[1] > 100 || (int) $matches[2] > 100) {
                throw new ZoneDefinitionException(sprintf('Zone "%s" has an out-of-range point "%s" in its "map_shape" in "%s": coordinates are percentages (0-100).', $slug, $point, $source));
            }
        }

        return implode(' ', $points);
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

        // Variante nocturne (ZON-17) : surcharge partielle jouee la nuit
        // (weights, chest_gils_*, et pool de rencontres dedie `mob_slugs`).
        $night = $this->normalizeExploreNight($slug, $explore['night'] ?? null, $source);
        if (null !== $night) {
            $config['night'] = $night;
        }

        return $config === [] ? null : $config;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeExploreNight(string $slug, mixed $night, string $source): ?array
    {
        if ($night === null) {
            return null;
        }
        if (!\is_array($night)) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid "explore.night" block in "%s".', $slug, $source));
        }

        $config = [];
        if (isset($night['weights']) && \is_array($night['weights'])) {
            $weights = [];
            foreach ($night['weights'] as $event => $weight) {
                if (\is_string($event) && is_numeric($weight)) {
                    $weights[$event] = (int) $weight;
                }
            }
            if ($weights !== []) {
                $config['weights'] = $weights;
            }
        }
        foreach (['chest_gils_min', 'chest_gils_max'] as $key) {
            if (isset($night[$key]) && is_numeric($night[$key])) {
                $config[$key] = (int) $night[$key];
            }
        }
        if (isset($night['mob_slugs']) && \is_array($night['mob_slugs'])) {
            $slugs = array_values(array_filter(
                array_map(static fn ($s): string => \is_string($s) ? $s : '', $night['mob_slugs']),
                static fn (string $s): bool => '' !== $s,
            ));
            if ($slugs !== []) {
                $config['mob_slugs'] = $slugs;
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
            // ECO-24c — le gate de competence. La normalisation est une **liste
            // blanche** : une cle absente d'ici est silencieusement perdue entre
            // le YAML et la base, et le gate ne s'appliquerait jamais.
            $requiresSkill = \is_string($resource['requires_skill'] ?? null)
                ? trim((string) $resource['requires_skill'])
                : '';

            $normalized = [
                'slug' => (string) $resource['slug'],
                'item' => (string) $resource['item'],
                'profession' => (string) $resource['profession'],
                'capacity' => max(1, (int) ($resource['capacity'] ?? 1)),
                'respawn_seconds' => max(0, (int) ($resource['respawn_seconds'] ?? 0)),
                'yield_min' => max(1, (int) ($resource['yield_min'] ?? 1)),
                'yield_max' => max(1, (int) ($resource['yield_max'] ?? 1)),
            ];

            // Absente quand le filon n'est pas gate : le gate est opt-in, et une
            // cle a `null` partout alourdirait la config serialisee de toutes
            // les zones pour quatre filons.
            if ($requiresSkill !== '') {
                $normalized['requires_skill'] = $requiresSkill;
            }

            $resources[] = $normalized;
        }

        return $resources === [] ? null : $resources;
    }

    /**
     * Population de creatures d'une zone (ZON-26b).
     *
     * Jusqu'ici, un `Mob` n'atteignait sa zone que **par une carte** :
     * `WorldEntityZoneListener` derive `Mob.zone` de `Mob.map` via
     * `Zone::sourceMap`. Une zone declaree sans carte d'origine — c'est-a-dire
     * toute zone nouvelle depuis le pivot — ne pouvait donc **avoir aucune
     * rencontre**. C'est ce qui bloquait l'Acte 4.
     *
     * @return list<array<string, mixed>>|null
     */
    private function normalizeMobs(string $slug, mixed $mobs, string $source): ?array
    {
        if ($mobs === null) {
            return null;
        }
        if (!\is_array($mobs)) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid "mobs" block in "%s".', $slug, $source));
        }

        $population = [];
        foreach ($mobs as $entry) {
            if (!\is_array($entry)) {
                throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid mob entry in "%s".', $slug, $source));
            }
            if (!\is_string($entry['monster'] ?? null) || trim((string) $entry['monster']) === '') {
                throw new ZoneDefinitionException(sprintf('Mob entry of zone "%s" is missing "monster" in "%s".', $slug, $source));
            }

            $population[] = [
                'monster' => (string) $entry['monster'],
                // Le nombre d'individus, pas d'espece : trois gobelins sont
                // trois rencontres possibles, et un combat en cours n'asseche
                // pas la zone pour les autres joueurs.
                'count' => max(1, (int) ($entry['count'] ?? 1)),
                'nocturnal' => (bool) ($entry['nocturnal'] ?? false),
                'group_tag' => \is_string($entry['group_tag'] ?? null) ? (string) $entry['group_tag'] : null,
            ];
        }

        return $population === [] ? null : $population;
    }

    /**
     * Bloc `pnjs:` d'une zone (ZON-26b-b).
     *
     * Contrairement aux creatures, un PNJ est un **individu** : re-jouer
     * l'import ne doit pas le dupliquer. D'ou le `slug` obligatoire, qui sert
     * de cle d'idempotence.
     *
     * Le format couvre ce qu'il faut pour peupler une zone — identite, tenue de
     * boutique, horaires, replique d'accueil. Les arbres de dialogue et les
     * chaines de quete restent dans les fixtures : les decrire en YAML
     * demanderait un second langage, pour un gain nul sur le verrou que ce
     * jalon leve.
     *
     * @return list<array<string, mixed>>|null
     */
    private function normalizePnjs(string $slug, mixed $pnjs, string $source): ?array
    {
        if ($pnjs === null) {
            return null;
        }
        if (!\is_array($pnjs)) {
            throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid "pnjs" block in "%s".', $slug, $source));
        }

        $residents = [];
        foreach ($pnjs as $entry) {
            if (!\is_array($entry)) {
                throw new ZoneDefinitionException(sprintf('Zone "%s" has an invalid pnj entry in "%s".', $slug, $source));
            }
            if (!\is_string($entry['slug'] ?? null) || trim((string) $entry['slug']) === '') {
                throw new ZoneDefinitionException(sprintf('Pnj entry of zone "%s" is missing "slug" in "%s".', $slug, $source));
            }
            if (!\is_string($entry['name'] ?? null) || trim((string) $entry['name']) === '') {
                throw new ZoneDefinitionException(sprintf('Pnj "%s" of zone "%s" is missing "name" in "%s".', $entry['slug'], $slug, $source));
            }

            $residents[] = [
                'slug' => trim((string) $entry['slug']),
                'name' => trim((string) $entry['name']),
                'name_en' => \is_string($entry['name_en'] ?? null) ? (string) $entry['name_en'] : null,
                'class_type' => \is_string($entry['class_type'] ?? null) ? (string) $entry['class_type'] : 'villager',
                'life' => max(1, (int) ($entry['life'] ?? 10)),
                'portrait' => \is_string($entry['portrait'] ?? null) ? (string) $entry['portrait'] : null,
                'greeting' => \is_string($entry['greeting'] ?? null) ? (string) $entry['greeting'] : null,
                'shop_items' => $this->normalizePnjShop($slug, $entry, $source),
                'opens_at' => isset($entry['opens_at']) ? (int) $entry['opens_at'] : null,
                'closes_at' => isset($entry['closes_at']) ? (int) $entry['closes_at'] : null,
            ];
        }

        return $residents === [] ? null : $residents;
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return list<string>|null
     */
    private function normalizePnjShop(string $slug, array $entry, string $source): ?array
    {
        $shop = $entry['shop_items'] ?? null;
        if ($shop === null) {
            return null;
        }
        if (!\is_array($shop)) {
            throw new ZoneDefinitionException(sprintf('Pnj "%s" of zone "%s" has an invalid "shop_items" in "%s".', $entry['slug'], $slug, $source));
        }

        $items = [];
        foreach ($shop as $itemSlug) {
            if (!\is_string($itemSlug) || trim($itemSlug) === '') {
                throw new ZoneDefinitionException(sprintf('Pnj "%s" of zone "%s" has an invalid shop item in "%s".', $entry['slug'], $slug, $source));
            }
            $items[] = trim($itemSlug);
        }

        return $items === [] ? null : $items;
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
