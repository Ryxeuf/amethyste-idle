<?php

namespace App\GameEngine\Settlement;

use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation des parametres de foyer (FOY-01).
 *
 * « Rien en dur » (PLAN_SETTLEMENTS) : seuils de rang, taux de decroissance,
 * marge d'hysteresis et seed du monde vivent dans
 * `config/game/settlements.yaml`, jamais dans une constante de classe. Le
 * calibrage se fait sans redeploiement de code.
 *
 * Meme parti pris que `ZoneDefinitionLoader` : la validation est purement
 * structurelle et ne touche jamais la base. Un fichier mal ecrit doit echouer a
 * la lecture, pas se decouvrir sur un ecran de zone six semaines plus tard.
 */
class SettlementDefinitionLoader
{
    /**
     * Valeur d'`index` signifiant « reparti sur les quatre » (BALANCE § 23.1).
     * Ce n'est pas un cinquieme indice : c'est l'absence de specialisation.
     */
    public const SPREAD = 'spread';

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/settlements.yaml';
    }

    /**
     * @return array{
     *     ranks: array<string, int>,
     *     decay_rate: float,
     *     dominance_margin: float,
     *     sustain_days: int,
     *     minimum_type_rank: SettlementRank,
     *     sediment: array<string, SedimentRule>,
     *     daily_cap_per_player: int,
     *     diminishing_threshold: int,
     *     diminishing_factor: float,
     *     grace_days: int,
     *     rebuild_multiplier: int,
     *     services: array<string, SettlementRank>,
     *     never_gated: array<string, string>,
     *     seed: array<string, array{rank: SettlementRank, stock: int}>,
     *     without_settlement: array<string, string>
     * }
     *
     * @throws SettlementDefinitionException si le fichier est absent, illisible ou invalide
     */
    public function load(?string $path = null): array
    {
        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new SettlementDefinitionException(sprintf('Settlement config not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new SettlementDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new SettlementDefinitionException(sprintf('Settlement config "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{
     *     ranks: array<string, int>,
     *     decay_rate: float,
     *     dominance_margin: float,
     *     sustain_days: int,
     *     minimum_type_rank: SettlementRank,
     *     sediment: array<string, SedimentRule>,
     *     daily_cap_per_player: int,
     *     diminishing_threshold: int,
     *     diminishing_factor: float,
     *     grace_days: int,
     *     rebuild_multiplier: int,
     *     services: array<string, SettlementRank>,
     *     never_gated: array<string, string>,
     *     seed: array<string, array{rank: SettlementRank, stock: int}>,
     *     without_settlement: array<string, string>
     * }
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $cap = $this->normalizePositiveInt($raw['anti_exploit']['daily_cap_per_player'] ?? null, 'anti_exploit.daily_cap_per_player', $source);
        $threshold = $this->normalizePositiveInt($raw['anti_exploit']['diminishing_threshold'] ?? null, 'anti_exploit.diminishing_threshold', $source);
        if ($threshold >= $cap) {
            // Un seuil de rendements decroissants au-dessus du plafond ne
            // ralentit rien : le plafond tombe avant. La regle serait ecrite et
            // inoperante — exactement ce que ce chargeur existe pour empecher.
            throw new SettlementDefinitionException(sprintf('"anti_exploit.diminishing_threshold" (%d) must stay below "daily_cap_per_player" (%d) in "%s".', $threshold, $cap, $source));
        }

        $neverGated = $this->normalizeWithout($raw['never_gated'] ?? [], $source, 'never_gated');
        $services = $this->normalizeServices($raw['services'] ?? [], $neverGated, $source);

        return [
            'ranks' => $this->normalizeRanks($raw['ranks'] ?? null, $source),
            'decay_rate' => $this->normalizeRate($raw['decay']['daily_rate'] ?? null, 'decay.daily_rate', $source),
            'dominance_margin' => $this->normalizeRate($raw['type']['dominance_margin'] ?? null, 'type.dominance_margin', $source),
            'sustain_days' => $this->normalizePositiveInt($raw['type']['sustain_days'] ?? null, 'type.sustain_days', $source),
            'minimum_type_rank' => $this->normalizeRank($raw['type']['minimum_rank'] ?? null, 'type.minimum_rank', $source),
            'sediment' => $this->normalizeSediment($raw['sediment'] ?? null, $source),
            'daily_cap_per_player' => $cap,
            'diminishing_threshold' => $threshold,
            'diminishing_factor' => $this->normalizeRate($raw['anti_exploit']['diminishing_factor'] ?? null, 'anti_exploit.diminishing_factor', $source),
            'grace_days' => $this->normalizePositiveInt($raw['regression']['grace_days'] ?? null, 'regression.grace_days', $source),
            'rebuild_multiplier' => $this->normalizeMultiplier($raw['regression']['rebuild_multiplier'] ?? null, 'regression.rebuild_multiplier', $source),
            'services' => $services,
            'never_gated' => $neverGated,
            'seed' => $this->normalizeSeed($raw['seed'] ?? [], $source),
            'without_settlement' => $this->normalizeWithout($raw['without_settlement'] ?? [], $source),
        ];
    }

    /**
     * Seuils, valides **ordonnes** : un Bourg moins cher qu'un Hameau rendrait
     * le calcul de rang incoherent sans qu'aucun test de comportement ne le voie.
     *
     * @return array<string, int>
     */
    private function normalizeRanks(mixed $ranks, string $source): array
    {
        if (!\is_array($ranks) || $ranks === []) {
            throw new SettlementDefinitionException(sprintf('Settlement config "%s" must declare "ranks".', $source));
        }

        $thresholds = [];
        $previous = 0;
        foreach (SettlementRank::ordered() as $rank) {
            if ($rank === SettlementRank::Ruin) {
                continue;
            }
            $value = $ranks[$rank->value] ?? null;
            if (!is_numeric($value) || (int) $value < 1) {
                throw new SettlementDefinitionException(sprintf('Rank "%s" needs a positive threshold in "%s".', $rank->value, $source));
            }
            $value = (int) $value;
            if ($value <= $previous) {
                throw new SettlementDefinitionException(sprintf('Rank thresholds must increase: "%s" (%d) is not above the previous (%d) in "%s".', $rank->value, $value, $previous, $source));
            }
            $thresholds[$rank->value] = $value;
            $previous = $value;
        }

        return $thresholds;
    }

    private function normalizeRate(mixed $value, string $key, string $source): float
    {
        if (!is_numeric($value) || (float) $value <= 0.0 || (float) $value >= 1.0) {
            throw new SettlementDefinitionException(sprintf('"%s" must be a rate strictly between 0 and 1 in "%s".', $key, $source));
        }

        return (float) $value;
    }

    private function normalizePositiveInt(mixed $value, string $key, string $source): int
    {
        if (!is_numeric($value) || (int) $value < 1) {
            throw new SettlementDefinitionException(sprintf('"%s" must be a positive integer in "%s".', $key, $source));
        }

        return (int) $value;
    }

    /**
     * Multiplicateur de reascension : au moins 2.
     *
     * A 1 il ne multiplie rien, et la promesse « rebatir est moins cher »
     * deviendrait une ligne de documentation sans effet — le defaut muet que ce
     * chargeur existe pour interdire.
     */
    private function normalizeMultiplier(mixed $value, string $key, string $source): int
    {
        if (!is_numeric($value) || (int) $value < 2) {
            throw new SettlementDefinitionException(sprintf('"%s" must be an integer of at least 2 in "%s".', $key, $source));
        }

        return (int) $value;
    }

    private function normalizeRank(mixed $value, string $key, string $source): SettlementRank
    {
        $rank = \is_string($value) ? SettlementRank::tryFrom($value) : null;
        if ($rank === null) {
            throw new SettlementDefinitionException(sprintf('"%s" must name a known rank in "%s".', $key, $source));
        }

        return $rank;
    }

    /**
     * Table de depot (BALANCE § 23.1), validee **par ligne**.
     *
     * Un indice mal orthographie ferait une ligne muette : l'action continuerait
     * d'etre jouee, plus rien ne se deposerait, et personne ne verrait la
     * difference avant qu'une zone entiere ne cesse inexplicablement de monter.
     *
     * @return array<string, SedimentRule>
     */
    private function normalizeSediment(mixed $sediment, string $source): array
    {
        if (!\is_array($sediment) || $sediment === []) {
            throw new SettlementDefinitionException(sprintf('Settlement config "%s" must declare "sediment".', $source));
        }

        $rules = [];
        foreach ($sediment as $action => $entry) {
            if (!\is_string($action) || trim($action) === '') {
                throw new SettlementDefinitionException(sprintf('Sediment keys must be action names in "%s".', $source));
            }
            if (!\is_array($entry)) {
                throw new SettlementDefinitionException(sprintf('Sediment rule "%s" must be a mapping in "%s".', $action, $source));
            }

            $rawIndex = $entry['index'] ?? null;
            if ($rawIndex === self::SPREAD) {
                $index = null;
            } else {
                $index = \is_string($rawIndex) ? SettlementIndex::tryFrom($rawIndex) : null;
                if ($index === null) {
                    throw new SettlementDefinitionException(sprintf('Sediment rule "%s" names an unknown index "%s" in "%s".', $action, \is_string($rawIndex) ? $rawIndex : '?', $source));
                }
            }

            $grains = $entry['grains'] ?? null;
            if (!is_numeric($grains) || (float) $grains <= 0.0) {
                throw new SettlementDefinitionException(sprintf('Sediment rule "%s" needs a positive "grains" value in "%s".', $action, $source));
            }

            $rules[$action] = new SedimentRule($action, $index, (float) $grains);
        }

        return $rules;
    }

    /**
     * @return array<string, array{rank: SettlementRank, stock: int}>
     */
    private function normalizeSeed(mixed $seed, string $source): array
    {
        if (!\is_array($seed)) {
            throw new SettlementDefinitionException(sprintf('"seed" must be a mapping in "%s".', $source));
        }

        $normalized = [];
        foreach ($seed as $slug => $entry) {
            if (!\is_string($slug) || trim($slug) === '') {
                throw new SettlementDefinitionException(sprintf('Seed keys must be zone slugs in "%s".', $source));
            }
            if (!\is_array($entry)) {
                throw new SettlementDefinitionException(sprintf('Seed of zone "%s" must be a mapping in "%s".', $slug, $source));
            }

            $rank = $this->normalizeRank($entry['rank'] ?? null, sprintf('seed.%s.rank', $slug), $source);
            $stock = $entry['stock'] ?? null;
            if (!is_numeric($stock) || (int) $stock < 0) {
                throw new SettlementDefinitionException(sprintf('Seed of zone "%s" needs a non-negative "stock" in "%s".', $slug, $source));
            }

            $normalized[$slug] = ['rank' => $rank, 'stock' => (int) $stock];
        }

        return $normalized;
    }

    /**
     * Services ouverts par le rang (FOY-05).
     *
     * La verification qui compte n'est pas que le rang existe : c'est qu'un
     * service **deja pose dans le monde** ne se retrouve jamais ici. La decision
     * A — rien n'est retro-gate — serait sinon annulable par une ligne de YAML
     * ecrite de bonne foi, et le jour ou elle le serait, des joueurs perdraient
     * l'acces a une boutique qu'ils utilisaient la veille.
     *
     * @param array<string, string> $neverGated
     *
     * @return array<string, SettlementRank>
     */
    private function normalizeServices(mixed $services, array $neverGated, string $source): array
    {
        if (!\is_array($services)) {
            throw new SettlementDefinitionException(sprintf('"services" must be a mapping in "%s".', $source));
        }

        $normalized = [];
        foreach ($services as $service => $entry) {
            if (!\is_string($service) || trim($service) === '') {
                throw new SettlementDefinitionException(sprintf('Service keys must be names in "%s".', $source));
            }
            if (isset($neverGated[$service])) {
                throw new SettlementDefinitionException(sprintf('Service "%s" is declared never gated (%s) and cannot be gated in "%s".', $service, $neverGated[$service], $source));
            }
            if (!\is_array($entry)) {
                throw new SettlementDefinitionException(sprintf('Service "%s" must be a mapping in "%s".', $service, $source));
            }

            $normalized[$service] = $this->normalizeRank($entry['minimum_rank'] ?? null, sprintf('services.%s.minimum_rank', $service), $source);
        }

        return $normalized;
    }

    /**
     * @return array<string, string> clef => raison ecrite
     */
    private function normalizeWithout(mixed $without, string $source, string $key = 'without_settlement'): array
    {
        if (!\is_array($without)) {
            throw new SettlementDefinitionException(sprintf('"%s" must be a mapping in "%s".', $key, $source));
        }

        $normalized = [];
        foreach ($without as $slug => $reason) {
            if (!\is_string($slug) || !\is_string($reason) || trim($reason) === '') {
                // Exiger une raison ecrite est le point : sans elle, on ne
                // distingue pas une zone volontairement sans foyer d'un oubli.
                throw new SettlementDefinitionException(sprintf('Entry "%s" of "%s" must state its reason in writing, in "%s".', \is_string($slug) ? $slug : '?', $key, $source));
            }
            $normalized[$slug] = $reason;
        }

        return $normalized;
    }
}
