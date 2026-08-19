<?php

namespace App\GameEngine\Season;

use App\Entity\App\GameEvent;
use App\Enum\ConsequenceTide;
use App\Enum\SettlementIndex;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation de la partition des marees (FOY-15, elargi par NAR-15).
 *
 * Le fichier portait les seules marees consequence ; il porte desormais les
 * **trois voix** de GAME_SEASONS § 0 — colonne vertebrale, rotation,
 * consequences. Un seul chargeur les lit, et c'est la reponse a la mise en garde
 * du plan (« ne pas creer un second selecteur concurrent ») : *une partition se
 * lit d'un seul endroit*.
 *
 * Meme parti pris que `SettlementDefinitionLoader` : la validation est purement
 * structurelle et ne touche jamais la base. Ajouter une maree, c'est ajouter un
 * bloc de donnees — jamais une branche de code.
 *
 * **Deux invariants sur les beats**, et tous deux valent pour la meme raison :
 * un arc troue ne se voit pas. Les quatre beats doivent etre **contigus** (la
 * fin de l'un ouvre le suivant) et **tenir dans la maree**. Un trou entre deux
 * fenetres laisserait des jours sans beat actif, et personne ne s'en
 * plaindrait — l'ecran afficherait simplement moins de choses.
 *
 * **La colonne vertebrale n'a pas de beats, et c'est voulu.** Elle **reserve**
 * un creneau, elle ne l'ecrit pas : le contenu de chaque maree canon arrive avec
 * son jalon (NAR-16 a NAR-19). Lui demander des beats des maintenant obligerait
 * a les inventer, c'est-a-dire a livrer sous le nom de « La Premiere Pierre » un
 * arc que personne n'a ecrit.
 *
 * @phpstan-type TideBeat array{beat: string, order: int, name: string, description: string, start_day: int, end_day: int}
 * @phpstan-type TideDefinition array{
 *     paleness_threshold: int,
 *     canon: array<int, array{theme: string, milestone: string}>,
 *     rotation: array<string, array{theme: string, feeds: list<SettlementIndex>, beats: list<TideBeat>}>,
 *     consequences: array<string, array{theme: string, beats: list<TideBeat>}>
 * }
 */
class TideDefinitionLoader
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

    /** @var TideDefinition|null */
    private ?array $cache = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/tides.yaml';
    }

    /**
     * @return TideDefinition
     *
     * @throws TideDefinitionException si le fichier est absent, illisible ou invalide
     */
    public function load(?string $path = null): array
    {
        $isDefault = null === $path;
        if ($isDefault && null !== $this->cache) {
            return $this->cache;
        }

        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new TideDefinitionException(sprintf('Consequence tide config not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new TideDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new TideDefinitionException(sprintf('Consequence tide config "%s" must be a mapping.', $path));
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
     * @return TideDefinition
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $threshold = $raw['paleness_threshold'] ?? null;
        if (!is_numeric($threshold) || (int) $threshold < 1) {
            throw new TideDefinitionException(sprintf('"paleness_threshold" must be a positive integer in "%s".', $source));
        }

        $rawTides = $raw['consequences'] ?? null;
        if (!\is_array($rawTides)) {
            throw new TideDefinitionException(sprintf('"consequences" must be a mapping in "%s".', $source));
        }

        $tides = [];
        // Chaque maree declaree par l'enum doit exister dans le fichier. Le
        // contraire — un cas de code sans composition — donnerait une maree
        // selectionnee dont l'arc serait vide : le theme s'afficherait, et rien
        // ne se passerait.
        foreach (ConsequenceTide::cases() as $tide) {
            $definition = $rawTides[$tide->value] ?? null;
            if (!\is_array($definition)) {
                throw new TideDefinitionException(sprintf('Consequence tide "%s" is missing from "%s".', $tide->value, $source));
            }

            $theme = $definition['theme'] ?? null;
            if (!\is_string($theme) || '' === trim($theme)) {
                throw new TideDefinitionException(sprintf('"consequences.%s.theme" must be a non-empty string in "%s".', $tide->value, $source));
            }

            $tides[$tide->value] = [
                'theme' => trim($theme),
                'beats' => $this->normalizeBeats($definition['beats'] ?? null, $tide->value, $source),
            ];
        }

        return [
            'paleness_threshold' => (int) $threshold,
            'canon' => $this->normalizeCanon($raw['canon'] ?? null, $source),
            'rotation' => $this->normalizeRotation($raw['rotation'] ?? null, $source),
            'consequences' => $tides,
        ];
    }

    /**
     * La colonne vertebrale : un creneau reserve, un theme, et le jalon qui
     * l'ecrira.
     *
     * **Aucun champ ou ecrire des beats** — c'est la forme qui tient la regle,
     * pas une verification : ce bloc ne peut pas se transformer en partition
     * ecrite d'avance, parce qu'il n'y a pas d'endroit ou l'ecrire.
     *
     * @return array<int, array{theme: string, milestone: string}>
     */
    private function normalizeCanon(mixed $raw, string $source): array
    {
        if (!\is_array($raw)) {
            throw new TideDefinitionException(sprintf('"canon" must be a mapping in "%s".', $source));
        }

        $canon = [];

        foreach ($raw as $season => $entry) {
            if (!is_numeric($season) || (int) $season < 1) {
                throw new TideDefinitionException(sprintf('Canon slot "%s" must be a positive season number in "%s".', (string) $season, $source));
            }

            if (!\is_array($entry)) {
                throw new TideDefinitionException(sprintf('Canon slot %d must be a mapping in "%s".', (int) $season, $source));
            }

            $theme = $entry['theme'] ?? null;
            $milestone = $entry['milestone'] ?? null;
            if (!\is_string($theme) || '' === trim($theme) || !\is_string($milestone) || '' === trim($milestone)) {
                throw new TideDefinitionException(sprintf('Canon slot %d must declare a theme and the milestone that writes it in "%s".', (int) $season, $source));
            }

            // Un creneau reserve deux fois n'est pas une reservation.
            if (isset($canon[(int) $season])) {
                throw new TideDefinitionException(sprintf('Canon slot %d is declared twice in "%s".', (int) $season, $source));
            }

            $canon[(int) $season] = ['theme' => trim($theme), 'milestone' => trim($milestone)];
        }

        ksort($canon);

        return $canon;
    }

    /**
     * Les gabarits rejouables, chacun avec les indices qu'il nourrit.
     *
     * Les indices sont refuses **a la lecture** contre `SettlementIndex` : un
     * gabarit qui nourrirait un indice inexistant ne serait jamais tire, et
     * l'erreur se lirait comme un choix d'equilibrage — le monde aurait
     * simplement l'air de ne jamais prescrire cette voie.
     *
     * @return array<string, array{theme: string, feeds: list<SettlementIndex>, beats: list<array{beat: string, order: int, name: string, description: string, start_day: int, end_day: int}>}>
     */
    private function normalizeRotation(mixed $raw, string $source): array
    {
        if (!\is_array($raw) || [] === $raw) {
            throw new TideDefinitionException(sprintf('"rotation" must be a non-empty mapping in "%s" : sans gabarit, un creneau libre reste vide.', $source));
        }

        $rotation = [];

        foreach ($raw as $key => $entry) {
            $key = (string) $key;

            if (!\is_array($entry)) {
                throw new TideDefinitionException(sprintf('Rotation template "%s" must be a mapping in "%s".', $key, $source));
            }

            $theme = $entry['theme'] ?? null;
            if (!\is_string($theme) || '' === trim($theme)) {
                throw new TideDefinitionException(sprintf('"rotation.%s.theme" must be a non-empty string in "%s".', $key, $source));
            }

            $rawFeeds = $entry['feeds'] ?? null;
            if (!\is_array($rawFeeds) || [] === $rawFeeds) {
                throw new TideDefinitionException(sprintf('"rotation.%s.feeds" must name at least one sediment index in "%s" : un gabarit qui ne nourrit rien ne peut pas etre prescrit.', $key, $source));
            }

            $feeds = [];
            foreach ($rawFeeds as $feed) {
                $index = \is_string($feed) ? SettlementIndex::tryFrom($feed) : null;
                if (!$index instanceof SettlementIndex) {
                    throw new TideDefinitionException(sprintf('"rotation.%s.feeds" names an unknown sediment index "%s" in "%s".', $key, \is_string($feed) ? $feed : \gettype($feed), $source));
                }
                $feeds[] = $index;
            }

            $rotation[$key] = [
                'theme' => trim($theme),
                'feeds' => array_values(array_unique($feeds, \SORT_REGULAR)),
                'beats' => $this->normalizeBeats($entry['beats'] ?? null, $key, $source),
            ];
        }

        return $rotation;
    }

    /**
     * L'arc d'une maree composable, quelle que soit sa voix.
     *
     * Rotation et consequence se composent de la meme facon — c'est ce qui
     * permet a `TideComposer` de n'avoir qu'un seul chemin. La colonne
     * vertebrale n'y figure pas : elle n'a pas d'arc a poser.
     *
     * @return array{theme: string, beats: list<array{beat: string, order: int, name: string, description: string, start_day: int, end_day: int}>}|null
     */
    public function composable(string $key): ?array
    {
        $definition = $this->load();

        foreach (['consequences', 'rotation'] as $voice) {
            if (isset($definition[$voice][$key])) {
                return [
                    'theme' => $definition[$voice][$key]['theme'],
                    'beats' => $definition[$voice][$key]['beats'],
                ];
            }
        }

        return null;
    }

    /**
     * @return list<array{beat: string, order: int, name: string, description: string, start_day: int, end_day: int}>
     */
    private function normalizeBeats(mixed $raw, string $tide, string $source): array
    {
        if (!\is_array($raw) || \count($raw) !== \count(self::BEATS)) {
            throw new TideDefinitionException(sprintf('"tides.%s.beats" must declare exactly %d beats in "%s".', $tide, \count(self::BEATS), $source));
        }

        $beats = [];
        $cursor = 0;

        foreach (array_values($raw) as $index => $entry) {
            if (!\is_array($entry)) {
                throw new TideDefinitionException(sprintf('Beat #%d of "%s" must be a mapping in "%s".', $index + 1, $tide, $source));
            }

            $expected = self::BEATS[$index];
            if (($entry['beat'] ?? null) !== $expected) {
                throw new TideDefinitionException(sprintf('Beat #%d of "%s" must be "%s" in "%s" : l\'arc suit toujours amorce -> montee -> climax -> resolution.', $index + 1, $tide, $expected, $source));
            }

            $start = $entry['start_day'] ?? null;
            $end = $entry['end_day'] ?? null;
            if (!is_numeric($start) || !is_numeric($end) || (int) $end <= (int) $start) {
                throw new TideDefinitionException(sprintf('Beat "%s" of "%s" must declare a positive window in "%s".', $expected, $tide, $source));
            }

            // Contiguïte : la fin de l'un ouvre le suivant. Un trou laisserait
            // des jours sans beat actif, et rien ne le signalerait.
            if ((int) $start !== $cursor) {
                throw new TideDefinitionException(sprintf('Beat "%s" of "%s" starts at day %d but the previous one ended at day %d in "%s" : les beats sont contigus.', $expected, $tide, (int) $start, $cursor, $source));
            }

            $cursor = (int) $end;

            $name = $entry['name'] ?? null;
            $description = $entry['description'] ?? null;
            if (!\is_string($name) || '' === trim($name) || !\is_string($description) || '' === trim($description)) {
                throw new TideDefinitionException(sprintf('Beat "%s" of "%s" must declare a name and a description in "%s".', $expected, $tide, $source));
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
            throw new TideDefinitionException(sprintf('The arc of "%s" ends at day %d instead of %d in "%s" : un arc doit couvrir la maree entiere.', $tide, $cursor, self::TIDE_DAYS, $source));
        }

        return $beats;
    }
}
