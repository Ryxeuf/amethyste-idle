<?php

namespace App\GameEngine\Season;

use App\Entity\App\GameEvent;
use App\Enum\ConsequenceTide;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation des marees consequence (FOY-15).
 *
 * Meme parti pris que `SettlementDefinitionLoader` : la validation est purement
 * structurelle et ne touche jamais la base. Ajouter une maree consequence, c'est
 * ajouter un bloc de donnees — jamais une branche de code.
 *
 * **Deux invariants sur les beats**, et tous deux valent pour la meme raison :
 * un arc troue ne se voit pas. Les quatre beats doivent etre **contigus** (la
 * fin de l'un ouvre le suivant) et **tenir dans la maree**. Un trou entre deux
 * fenetres laisserait des jours sans beat actif, et personne ne s'en
 * plaindrait — l'ecran afficherait simplement moins de choses.
 */
class ConsequenceTideDefinitionLoader
{
    /**
     * Duree d'une maree, en jours. La meme que `SeasonManager::SEASON_DURATION_DAYS` ;
     * elle sert ici de borne haute a la derniere fenetre de beat.
     */
    public const TIDE_DAYS = 28;

    /**
     * Ordre canonique des beats (NAR-08). Un arc qui en manque un, ou qui les
     * declare dans le desordre, n'est pas un arc.
     */
    private const BEATS = [
        GameEvent::BEAT_AMORCE,
        GameEvent::BEAT_MONTEE,
        GameEvent::BEAT_CLIMAX,
        GameEvent::BEAT_RESOLUTION,
    ];

    /** @var array{paleness_threshold: int, tides: array<string, array{theme: string, beats: list<array{beat: string, order: int, name: string, description: string, start_day: int, end_day: int}>}>}|null */
    private ?array $cache = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/consequence_tides.yaml';
    }

    /**
     * @return array{paleness_threshold: int, tides: array<string, array{theme: string, beats: list<array{beat: string, order: int, name: string, description: string, start_day: int, end_day: int}>}>}
     *
     * @throws ConsequenceTideDefinitionException si le fichier est absent, illisible ou invalide
     */
    public function load(?string $path = null): array
    {
        $isDefault = null === $path;
        if ($isDefault && null !== $this->cache) {
            return $this->cache;
        }

        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new ConsequenceTideDefinitionException(sprintf('Consequence tide config not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ConsequenceTideDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new ConsequenceTideDefinitionException(sprintf('Consequence tide config "%s" must be a mapping.', $path));
        }

        $normalized = $this->normalize($raw, $path);

        // Seul le fichier livre est memoise : un chemin explicite sert aux tests
        // et ne doit jamais empoisonner la lecture suivante.
        if ($isDefault) {
            $this->cache = $normalized;
        }

        return $normalized;
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{paleness_threshold: int, tides: array<string, array{theme: string, beats: list<array{beat: string, order: int, name: string, description: string, start_day: int, end_day: int}>}>}
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $threshold = $raw['paleness_threshold'] ?? null;
        if (!is_numeric($threshold) || (int) $threshold < 1) {
            throw new ConsequenceTideDefinitionException(sprintf('"paleness_threshold" must be a positive integer in "%s".', $source));
        }

        $rawTides = $raw['tides'] ?? null;
        if (!\is_array($rawTides)) {
            throw new ConsequenceTideDefinitionException(sprintf('"tides" must be a mapping in "%s".', $source));
        }

        $tides = [];
        // Chaque maree declaree par l'enum doit exister dans le fichier. Le
        // contraire — un cas de code sans composition — donnerait une maree
        // selectionnee dont l'arc serait vide : le theme s'afficherait, et rien
        // ne se passerait.
        foreach (ConsequenceTide::cases() as $tide) {
            $definition = $rawTides[$tide->value] ?? null;
            if (!\is_array($definition)) {
                throw new ConsequenceTideDefinitionException(sprintf('Tide "%s" is missing from "%s".', $tide->value, $source));
            }

            $theme = $definition['theme'] ?? null;
            if (!\is_string($theme) || '' === trim($theme)) {
                throw new ConsequenceTideDefinitionException(sprintf('"tides.%s.theme" must be a non-empty string in "%s".', $tide->value, $source));
            }

            $tides[$tide->value] = [
                'theme' => trim($theme),
                'beats' => $this->normalizeBeats($definition['beats'] ?? null, $tide->value, $source),
            ];
        }

        return ['paleness_threshold' => (int) $threshold, 'tides' => $tides];
    }

    /**
     * @return list<array{beat: string, order: int, name: string, description: string, start_day: int, end_day: int}>
     */
    private function normalizeBeats(mixed $raw, string $tide, string $source): array
    {
        if (!\is_array($raw) || \count($raw) !== \count(self::BEATS)) {
            throw new ConsequenceTideDefinitionException(sprintf('"tides.%s.beats" must declare exactly %d beats in "%s".', $tide, \count(self::BEATS), $source));
        }

        $beats = [];
        $cursor = 0;

        foreach (array_values($raw) as $index => $entry) {
            if (!\is_array($entry)) {
                throw new ConsequenceTideDefinitionException(sprintf('Beat #%d of "%s" must be a mapping in "%s".', $index + 1, $tide, $source));
            }

            $expected = self::BEATS[$index];
            if (($entry['beat'] ?? null) !== $expected) {
                throw new ConsequenceTideDefinitionException(sprintf('Beat #%d of "%s" must be "%s" in "%s" : l\'arc suit toujours amorce -> montee -> climax -> resolution.', $index + 1, $tide, $expected, $source));
            }

            $start = $entry['start_day'] ?? null;
            $end = $entry['end_day'] ?? null;
            if (!is_numeric($start) || !is_numeric($end) || (int) $end <= (int) $start) {
                throw new ConsequenceTideDefinitionException(sprintf('Beat "%s" of "%s" must declare a positive window in "%s".', $expected, $tide, $source));
            }

            // Contiguïte : la fin de l'un ouvre le suivant. Un trou laisserait
            // des jours sans beat actif, et rien ne le signalerait.
            if ((int) $start !== $cursor) {
                throw new ConsequenceTideDefinitionException(sprintf('Beat "%s" of "%s" starts at day %d but the previous one ended at day %d in "%s" : les beats sont contigus.', $expected, $tide, (int) $start, $cursor, $source));
            }

            $cursor = (int) $end;

            $name = $entry['name'] ?? null;
            $description = $entry['description'] ?? null;
            if (!\is_string($name) || '' === trim($name) || !\is_string($description) || '' === trim($description)) {
                throw new ConsequenceTideDefinitionException(sprintf('Beat "%s" of "%s" must declare a name and a description in "%s".', $expected, $tide, $source));
            }

            $beats[] = [
                'beat' => $expected,
                'order' => $index + 1,
                'name' => trim($name),
                'description' => trim($description),
                'start_day' => (int) $start,
                'end_day' => (int) $end,
            ];
        }

        if ($cursor !== self::TIDE_DAYS) {
            throw new ConsequenceTideDefinitionException(sprintf('The arc of "%s" ends at day %d instead of %d in "%s" : un arc doit couvrir la maree entiere.', $tide, $cursor, self::TIDE_DAYS, $source));
        }

        return $beats;
    }
}
