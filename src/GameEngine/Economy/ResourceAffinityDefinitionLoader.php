<?php

namespace App\GameEngine\Economy;

use App\Enum\Element;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation de la table d'affinites (ZON-36).
 *
 * Meme parti pris que `PurityDefinitionLoader` : validation purement
 * structurelle, aucun acces a la base, et **echec a la lecture**. Une table mal
 * ecrite doit faire rougir la CI plutot que de se decouvrir le jour ou une
 * fusion demande des matieres Eau et n'en trouve aucune.
 *
 * Le fichier est **obligatoire**. Une table absente ne rendrait pas le monde
 * injouable — elle rendrait toute ressource sans affinite, en silence, et la
 * loi 10 redeviendrait ce qu'elle etait avant ce jalon : une phrase dans un
 * document.
 */
class ResourceAffinityDefinitionLoader
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/affinities.yaml';
    }

    /**
     * @return array{lines: array<string, list<string>>, line_slugs: array<string, list<string>>, corrections: array<string, Element>, without_affinity: list<string>, excluded: list<string>}
     *
     * @throws ResourceAffinityDefinitionException si le fichier est absent, illisible ou invalide
     */
    public function load(?string $path = null): array
    {
        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new ResourceAffinityDefinitionException(sprintf('Affinity table not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ResourceAffinityDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new ResourceAffinityDefinitionException(sprintf('Affinity table "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{lines: array<string, list<string>>, line_slugs: array<string, list<string>>, corrections: array<string, Element>, without_affinity: list<string>, excluded: list<string>}
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $lines = $this->elementKeyedLists($raw['lines'] ?? null, 'lines', $source);

        if ($lines === []) {
            // Une table sans ligne n'est pas une table etroite : c'est une loi
            // qui ne s'appliquerait nulle part, et rien ne le dirait.
            throw new ResourceAffinityDefinitionException(sprintf('Affinity table "%s" declares no line: no resource would carry an affinity.', $source));
        }

        $lineSlugs = $this->elementKeyedLists($raw['line_slugs'] ?? [], 'line_slugs', $source);
        $withoutAffinity = $this->slugList($raw['without_affinity'] ?? [], 'without_affinity', $source);
        $excluded = $this->slugList($raw['excluded'] ?? [], 'excluded', $source);
        $corrections = $this->normalizeCorrections($raw['corrections'] ?? [], $source);

        // Ecrire une matiere des deux cotes est une contradiction, et la
        // trancher silencieusement ferait dependre l'affinite de l'ordre du code
        // plutot que de la table.
        foreach ([
            ['without_affinity', 'excluded', array_intersect($withoutAffinity, $excluded)],
            ['corrections', 'excluded', array_intersect(array_keys($corrections), $excluded)],
            ['corrections', 'without_affinity', array_intersect(array_keys($corrections), $withoutAffinity)],
        ] as [$left, $right, $overlap]) {
            if ($overlap !== []) {
                throw new ResourceAffinityDefinitionException(sprintf('These slugs sit in both "%s" and "%s" of "%s": %s.', $left, $right, $source, implode(', ', array_values($overlap))));
            }
        }

        return [
            'lines' => $lines,
            'line_slugs' => $lineSlugs,
            'corrections' => $corrections,
            'without_affinity' => $withoutAffinity,
            'excluded' => $excluded,
        ];
    }

    /**
     * @return array<string, Element>
     */
    private function normalizeCorrections(mixed $raw, string $source): array
    {
        if (!\is_array($raw)) {
            throw new ResourceAffinityDefinitionException(sprintf('"corrections" must be a mapping in "%s".', $source));
        }

        $corrections = [];
        foreach ($raw as $slug => $element) {
            if (!\is_string($slug) || trim($slug) === '') {
                throw new ResourceAffinityDefinitionException(sprintf('Correction keys must be item slugs in "%s".', $source));
            }

            $affinity = \is_string($element) ? Element::tryFrom($element) : null;
            if ($affinity === null) {
                throw new ResourceAffinityDefinitionException(sprintf('Correction "%s" names an unknown element in "%s".', $slug, $source));
            }

            if ($affinity === Element::None) {
                // « Aucune » n'est pas une correction : c'est `without_affinity`.
                // Les deux ecritures donneraient le meme resultat et diraient
                // deux choses differentes — l'une « ce flux est le neant »,
                // l'autre « cette matiere n'est pas un flux, elle en est le
                // substrat ». Le canon tient a la seconde.
                throw new ResourceAffinityDefinitionException(sprintf('Correction "%s" uses "none" in "%s": declare it under "without_affinity" instead.', $slug, $source));
            }

            $corrections[$slug] = $affinity;
        }

        return $corrections;
    }

    /**
     * @return array<string, list<string>>
     */
    private function elementKeyedLists(mixed $raw, string $key, string $source): array
    {
        if ($raw === null) {
            throw new ResourceAffinityDefinitionException(sprintf('Affinity table "%s" must declare "%s".', $source, $key));
        }

        if (!\is_array($raw)) {
            throw new ResourceAffinityDefinitionException(sprintf('"%s" must be a mapping in "%s".', $key, $source));
        }

        $lists = [];
        foreach ($raw as $element => $values) {
            $named = \is_string($element) ? Element::tryFrom($element) : null;
            if ($named === null || $named === Element::None) {
                throw new ResourceAffinityDefinitionException(sprintf('"%s" is keyed by element in "%s"; "%s" is not one.', $key, $source, \is_string($element) ? $element : \gettype($element)));
            }

            $lists[$named->value] = $this->slugList($values, $key . '.' . $named->value, $source);
        }

        return $lists;
    }

    /**
     * @return list<string>
     */
    private function slugList(mixed $values, string $key, string $source): array
    {
        if (!\is_array($values)) {
            throw new ResourceAffinityDefinitionException(sprintf('"%s" must be a list in "%s".', $key, $source));
        }

        $slugs = [];
        foreach ($values as $value) {
            if (!\is_string($value) || trim($value) === '') {
                throw new ResourceAffinityDefinitionException(sprintf('"%s" must only contain non-empty slugs in "%s".', $key, $source));
            }
            $slugs[] = $value;
        }

        return $slugs;
    }
}
