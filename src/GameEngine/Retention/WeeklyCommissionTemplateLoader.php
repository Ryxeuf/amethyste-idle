<?php

namespace App\GameEngine\Retention;

use App\Enum\InfluenceActivityType;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation du pool de commissions (RET-02).
 *
 * Meme parti pris que `WeeklyChallengeTemplateLoader` : la validation est
 * purement structurelle, ne touche jamais la base, et **echoue a la lecture**.
 * Un gabarit mal ecrit doit faire rougir la CI, pas se decouvrir un lundi matin
 * sur l'ecran d'un joueur qui n'a pas de commission.
 */
class WeeklyCommissionTemplateLoader
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/weekly_commissions.yaml';
    }

    /**
     * @return array{per_week: int, commissions: list<WeeklyCommissionTemplate>}
     *
     * @throws WeeklyCommissionException
     */
    public function load(?string $path = null): array
    {
        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new WeeklyCommissionException(sprintf('Commission pool not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new WeeklyCommissionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new WeeklyCommissionException(sprintf('Commission pool "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{per_week: int, commissions: list<WeeklyCommissionTemplate>}
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $perWeek = $raw['per_week'] ?? null;
        if (!is_numeric($perWeek) || (int) $perWeek < 1) {
            throw new WeeklyCommissionException(sprintf('"per_week" must be a positive integer in "%s".', $source));
        }

        $entries = $raw['commissions'] ?? null;
        if (!\is_array($entries) || $entries === []) {
            throw new WeeklyCommissionException(sprintf('Commission pool "%s" must declare at least one commission.', $source));
        }

        $templates = [];
        $slugs = [];
        foreach ($entries as $entry) {
            if (!\is_array($entry)) {
                throw new WeeklyCommissionException(sprintf('Each commission must be a mapping in "%s".', $source));
            }

            $slug = $entry['slug'] ?? null;
            if (!\is_string($slug) || trim($slug) === '') {
                throw new WeeklyCommissionException(sprintf('A commission is missing its "slug" in "%s".', $source));
            }
            if (isset($slugs[$slug])) {
                // Un slug en double casserait la variation d'une semaine a
                // l'autre sans rien casser d'autre : le tirage retomberait sur
                // la meme entree, et la repetition passerait pour du hasard.
                throw new WeeklyCommissionException(sprintf('Duplicate commission slug "%s" in "%s".', $slug, $source));
            }
            $slugs[$slug] = true;

            $activity = \is_string($entry['activity'] ?? null) ? InfluenceActivityType::tryFrom($entry['activity']) : null;
            if ($activity === null) {
                throw new WeeklyCommissionException(sprintf('Commission "%s" names an unknown activity in "%s".', $slug, $source));
            }
            if ($activity === InfluenceActivityType::Challenge) {
                // Reserve au versement des bonus de guilde : ce n'est pas une
                // activite qu'un joueur peut faire, donc pas un objectif.
                throw new WeeklyCommissionException(sprintf('Commission "%s" cannot use the reserved "challenge" activity in "%s".', $slug, $source));
            }

            $domain = $entry['domain'] ?? null;
            if (!\is_string($domain) || trim($domain) === '') {
                throw new WeeklyCommissionException(sprintf('Commission "%s" must name the domain it belongs to in "%s".', $slug, $source));
            }

            $target = $entry['target'] ?? null;
            if (!is_numeric($target) || (int) $target < 1) {
                throw new WeeklyCommissionException(sprintf('Commission "%s" needs a positive "target" in "%s".', $slug, $source));
            }

            $templates[] = new WeeklyCommissionTemplate(
                $slug,
                $activity,
                $domain,
                $this->text($entry, 'title', $slug, $source),
                $this->text($entry, 'title_en', $slug, $source),
                $this->text($entry, 'description', $slug, $source),
                $this->text($entry, 'description_en', $slug, $source),
                (int) $target,
            );
        }

        return ['per_week' => (int) $perWeek, 'commissions' => $templates];
    }

    /**
     * @param array<array-key, mixed> $entry
     */
    private function text(array $entry, string $key, string $slug, string $source): string
    {
        $value = $entry[$key] ?? null;
        if (!\is_string($value) || trim($value) === '') {
            // Les deux langues sont exigees a la lecture : une commission qui
            // n'existe qu'en francais s'afficherait vide pour la moitie des
            // joueurs, et rien d'autre ne le dirait.
            throw new WeeklyCommissionException(sprintf('Commission "%s" is missing "%s" in "%s".', $slug, $key, $source));
        }

        return $value;
    }
}
