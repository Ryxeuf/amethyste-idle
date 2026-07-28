<?php

namespace App\GameEngine\Economy;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation du perimetre de purete (ECO-21).
 *
 * Meme parti pris que `SettlementDefinitionLoader` : validation purement
 * structurelle, aucun acces a la base, et **echec a la lecture**. Un perimetre
 * mal ecrit doit faire rougir la CI plutot que de se decouvrir le jour ou un
 * joueur trouve une botte d'herbes marquee « parfaite ».
 *
 * Le fichier est **obligatoire** : contrairement au bloc d'atelier (FOY-07), un
 * perimetre absent ne serait pas un monde jouable mais un monde ou la purete ne
 * s'applique nulle part, en silence.
 */
class PurityDefinitionLoader
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/purity.yaml';
    }

    /**
     * @return array{scope: array{slug_prefixes: list<string>, excluded_slugs: list<string>, included_slugs: list<string>}}
     *
     * @throws PurityDefinitionException si le fichier est absent, illisible ou invalide
     */
    public function load(?string $path = null): array
    {
        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new PurityDefinitionException(sprintf('Purity config not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new PurityDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new PurityDefinitionException(sprintf('Purity config "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{scope: array{slug_prefixes: list<string>, excluded_slugs: list<string>, included_slugs: list<string>}}
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $scope = $raw['scope'] ?? null;
        if (!\is_array($scope)) {
            throw new PurityDefinitionException(sprintf('Purity config "%s" must declare "scope".', $source));
        }

        $prefixes = $this->slugList($scope['slug_prefixes'] ?? [], 'scope.slug_prefixes', $source);
        $excluded = $this->slugList($scope['excluded_slugs'] ?? [], 'scope.excluded_slugs', $source);
        $included = $this->slugList($scope['included_slugs'] ?? [], 'scope.included_slugs', $source);

        if ($prefixes === [] && $included === []) {
            // Un perimetre vide n'est pas un perimetre etroit : c'est une purete
            // qui ne s'applique nulle part, et rien ne le dirait. Le champ
            // `Recipe.quality` resterait endormi comme avant le jalon.
            throw new PurityDefinitionException(sprintf('Purity scope is empty in "%s": no prefix and no explicit slug would carry a band.', $source));
        }

        $overlap = array_values(array_intersect($excluded, $included));
        if ($overlap !== []) {
            // Ecrire une matiere des deux cotes est une contradiction, et la
            // resoudre silencieusement — dans un sens ou dans l'autre — ferait
            // dependre le perimetre de l'ordre du code.
            throw new PurityDefinitionException(sprintf('These slugs are both included and excluded in "%s": %s.', $source, implode(', ', $overlap)));
        }

        return [
            'scope' => [
                'slug_prefixes' => $prefixes,
                'excluded_slugs' => $excluded,
                'included_slugs' => $included,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function slugList(mixed $values, string $key, string $source): array
    {
        if (!\is_array($values)) {
            throw new PurityDefinitionException(sprintf('"%s" must be a list in "%s".', $key, $source));
        }

        $slugs = [];
        foreach ($values as $value) {
            if (!\is_string($value) || trim($value) === '') {
                throw new PurityDefinitionException(sprintf('"%s" must only contain non-empty slugs in "%s".', $key, $source));
            }
            $slugs[] = $value;
        }

        return $slugs;
    }
}
