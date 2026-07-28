<?php

namespace App\GameEngine\Guild;

use App\Enum\InfluenceActivityType;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation du pool de gabarits de defis hebdomadaires (RET-01).
 *
 * Meme parti pris que `ZoneDefinitionLoader` : la validation est purement
 * structurelle, ne touche jamais la base, et est unitairement testable. Un
 * gabarit mal ecrit doit echouer a la lecture du fichier, pas six jours plus
 * tard sur un ecran de guilde vide.
 */
class WeeklyChallengeTemplateLoader
{
    /**
     * Nombre de defis actifs par semaine si le fichier ne le precise pas.
     */
    public const int DEFAULT_PER_WEEK = 3;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/weekly_challenges.yaml';
    }

    /**
     * @return array{per_week: int, challenges: list<WeeklyChallengeTemplate>}
     *
     * @throws WeeklyChallengeTemplateException si le fichier est absent, illisible ou invalide
     */
    public function load(?string $path = null): array
    {
        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new WeeklyChallengeTemplateException(sprintf('Weekly challenge pool not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new WeeklyChallengeTemplateException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new WeeklyChallengeTemplateException(sprintf('Weekly challenge pool "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{per_week: int, challenges: list<WeeklyChallengeTemplate>}
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $rawChallenges = $raw['challenges'] ?? [];
        if (!\is_array($rawChallenges) || $rawChallenges === []) {
            throw new WeeklyChallengeTemplateException(sprintf('Weekly challenge pool "%s" must declare a non-empty "challenges" list.', $source));
        }

        $challenges = [];
        $seenSlugs = [];
        foreach ($rawChallenges as $entry) {
            if (!\is_array($entry)) {
                throw new WeeklyChallengeTemplateException(sprintf('Each challenge must be a mapping in "%s".', $source));
            }

            $template = $this->normalizeChallenge($entry, $source);
            if (isset($seenSlugs[$template->slug])) {
                throw new WeeklyChallengeTemplateException(sprintf('Duplicate challenge slug "%s" in "%s".', $template->slug, $source));
            }
            $seenSlugs[$template->slug] = true;
            $challenges[] = $template;
        }

        $perWeek = $raw['per_week'] ?? self::DEFAULT_PER_WEEK;
        if (!is_numeric($perWeek) || (int) $perWeek < 1) {
            throw new WeeklyChallengeTemplateException(sprintf('"per_week" must be a positive integer in "%s".', $source));
        }

        return [
            'per_week' => (int) $perWeek,
            'challenges' => $challenges,
        ];
    }

    /**
     * @param array<array-key, mixed> $entry
     */
    private function normalizeChallenge(array $entry, string $source): WeeklyChallengeTemplate
    {
        foreach (['slug', 'activity', 'title', 'description'] as $required) {
            if (!\is_string($entry[$required] ?? null) || trim((string) $entry[$required]) === '') {
                throw new WeeklyChallengeTemplateException(sprintf('Challenge is missing "%s" in "%s".', $required, $source));
            }
        }

        $slug = trim((string) $entry['slug']);
        $activity = InfluenceActivityType::tryFrom(trim((string) $entry['activity']));
        if ($activity === null) {
            throw new WeeklyChallengeTemplateException(sprintf('Challenge "%s" has an unknown activity "%s" in "%s".', $slug, $entry['activity'], $source));
        }

        // `challenge` est le canal de **versement** des bonus d'un defi
        // (cf. ChallengeTracker::awardBonusPoints). Un defi qui s'en reclamerait
        // se nourrirait de ses propres recompenses.
        if ($activity === InfluenceActivityType::Challenge) {
            throw new WeeklyChallengeTemplateException(sprintf('Challenge "%s" cannot use the reserved activity "challenge" in "%s".', $slug, $source));
        }

        $target = $entry['target'] ?? null;
        if (!is_numeric($target) || (int) $target < 1) {
            throw new WeeklyChallengeTemplateException(sprintf('Challenge "%s" must declare a positive "target" in "%s".', $slug, $source));
        }

        $bonusPoints = $entry['bonus_points'] ?? null;
        if (!is_numeric($bonusPoints) || (int) $bonusPoints < 1) {
            throw new WeeklyChallengeTemplateException(sprintf('Challenge "%s" must declare positive "bonus_points" in "%s".', $slug, $source));
        }

        return new WeeklyChallengeTemplate(
            slug: $slug,
            activity: $activity,
            title: trim((string) $entry['title']),
            titleEn: \is_string($entry['title_en'] ?? null) ? trim((string) $entry['title_en']) : null,
            description: trim((string) $entry['description']),
            descriptionEn: \is_string($entry['description_en'] ?? null) ? trim((string) $entry['description_en']) : null,
            target: (int) $target,
            bonusPoints: (int) $bonusPoints,
        );
    }
}
