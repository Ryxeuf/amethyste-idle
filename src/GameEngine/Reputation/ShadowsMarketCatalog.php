<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Region;
use App\Enum\ReputationTier;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Le systeme propre des Ruelles, lu dans `config/game/factions.yaml` (FAC-06).
 *
 * Trois blocs : l'**approche** (le seuil d'explorations nocturnes qui fait
 * apparaitre la faction), le **receleur** (la coupe, le plafond de lots, le
 * palier requis — les trois garde-fous du canon) et les **rumeurs** (le prix
 * de l'information).
 *
 * **Ce que le loader refuse, et pourquoi.** Une coupe inferieure ou egale a
 * la taxe max de cite renverserait le HV — le marche gris deviendrait le
 * canal dominant, exactement ce que le canon interdit. Un plafond nul ferait
 * du receleur un canal de masse. Un seuil d'approche nul rendrait la faction
 * visible au premier pas — elle cesserait d'etre celle qui vous trouve.
 */
class ShadowsMarketCatalog
{
    /**
     * @var array{
     *   night_explorations: int,
     *   cut_percent: int,
     *   weekly_lot_cap: int,
     *   required_tier: ReputationTier,
     *   counter_pnj_slugs: list<string>,
     *   rumor_price_gils: int
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

    public function nightExplorationsThreshold(): int
    {
        return $this->definition()['night_explorations'];
    }

    public function fenceCutPercent(): int
    {
        return $this->definition()['cut_percent'];
    }

    public function weeklyLotCap(): int
    {
        return $this->definition()['weekly_lot_cap'];
    }

    public function fenceRequiredTier(): ReputationTier
    {
        return $this->definition()['required_tier'];
    }

    /**
     * @return list<string>
     */
    public function counterPnjSlugs(): array
    {
        return $this->definition()['counter_pnj_slugs'];
    }

    public function isCounter(?string $pnjSlug): bool
    {
        return null !== $pnjSlug && \in_array($pnjSlug, $this->definition()['counter_pnj_slugs'], true);
    }

    public function rumorPriceGils(): int
    {
        return $this->definition()['rumor_price_gils'];
    }

    /**
     * @return array{
     *   night_explorations: int,
     *   cut_percent: int,
     *   weekly_lot_cap: int,
     *   required_tier: ReputationTier,
     *   counter_pnj_slugs: list<string>,
     *   rumor_price_gils: int
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
     *   night_explorations: int,
     *   cut_percent: int,
     *   weekly_lot_cap: int,
     *   required_tier: ReputationTier,
     *   counter_pnj_slugs: list<string>,
     *   rumor_price_gils: int
     * }
     *
     * @throws FactionTensionDefinitionException
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new FactionTensionDefinitionException(sprintf('Shadows market catalogue not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new FactionTensionDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new FactionTensionDefinitionException(sprintf('Shadows market catalogue "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{
     *   night_explorations: int,
     *   cut_percent: int,
     *   weekly_lot_cap: int,
     *   required_tier: ReputationTier,
     *   counter_pnj_slugs: list<string>,
     *   rumor_price_gils: int
     * }
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $ruelles = $raw['ruelles'] ?? null;
        if (!\is_array($ruelles)) {
            throw new FactionTensionDefinitionException(sprintf('Catalogue "%s" must declare a "ruelles" block.', $source));
        }

        $threshold = $ruelles['approach']['night_explorations'] ?? null;
        if (!\is_int($threshold) || $threshold < 1) {
            // Un seuil nul rendrait la faction visible au premier pas : elle
            // cesserait d'etre celle qui vous trouve.
            throw new FactionTensionDefinitionException(sprintf('The approach threshold of "%s" must be a positive integer.', $source));
        }

        $fence = $ruelles['fence'] ?? null;
        if (!\is_array($fence)) {
            throw new FactionTensionDefinitionException(sprintf('The ruelles block of "%s" must declare a "fence" mapping.', $source));
        }

        $cut = $fence['cut_percent'] ?? null;
        if (!\is_int($cut) || $cut <= Region::MAX_TAX_RATE_PERCENT || $cut > 100) {
            // Le garde-fou du canon : la coupe est TOUJOURS superieure a la
            // taxe max de cite, sans quoi le marche gris renverse le HV.
            throw new FactionTensionDefinitionException(sprintf('The fence cut of "%s" must stay strictly above the city tax ceiling (%d %%).', $source, Region::MAX_TAX_RATE_PERCENT));
        }

        $cap = $fence['weekly_lot_cap'] ?? null;
        if (!\is_int($cap) || $cap < 1) {
            throw new FactionTensionDefinitionException(sprintf('The fence weekly lot cap of "%s" must be a positive integer.', $source));
        }

        $tier = $fence['required_tier'] ?? null;
        $requiredTier = \is_string($tier) ? ReputationTier::tryFrom($tier) : null;
        if (null === $requiredTier) {
            throw new FactionTensionDefinitionException(sprintf('The fence required tier of "%s" does not name a reputation tier.', $source));
        }

        $slugs = [];
        foreach ((array) ($fence['counter_pnj_slugs'] ?? []) as $slug) {
            if (!\is_string($slug) || trim($slug) === '') {
                throw new FactionTensionDefinitionException(sprintf('Fence counter slugs of "%s" must be strings.', $source));
            }
            $slugs[] = $slug;
        }
        if ([] === $slugs) {
            throw new FactionTensionDefinitionException(sprintf('The fence of "%s" needs at least one counter.', $source));
        }

        $rumorPrice = $ruelles['rumors']['price_gils'] ?? null;
        if (!\is_int($rumorPrice) || $rumorPrice < 1) {
            throw new FactionTensionDefinitionException(sprintf('The rumor price of "%s" must be a positive integer.', $source));
        }

        return [
            'night_explorations' => $threshold,
            'cut_percent' => $cut,
            'weekly_lot_cap' => $cap,
            'required_tier' => $requiredTier,
            'counter_pnj_slugs' => $slugs,
            'rumor_price_gils' => $rumorPrice,
        ];
    }
}
