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
     *   rumor_price_gils: int,
     *   counterfeit_charges_min: int,
     *   counterfeit_charges_max: int,
     *   counterfeit_loot_chance_percent: int,
     *   counterfeit_backlash_percent: int,
     *   counterfeit_defuse_essence: int,
     *   counterfeit_eye_tier: ReputationTier,
     *   counterfeit_defuse_tier: ReputationTier,
     *   counterfeit_forge_tier: ReputationTier,
     *   counterfeit_forge_recipe_slug: string
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

    public function counterfeitChargesMin(): int
    {
        return $this->definition()['counterfeit_charges_min'];
    }

    public function counterfeitChargesMax(): int
    {
        return $this->definition()['counterfeit_charges_max'];
    }

    public function counterfeitLootChancePercent(): int
    {
        return $this->definition()['counterfeit_loot_chance_percent'];
    }

    public function counterfeitBacklashPercent(): int
    {
        return $this->definition()['counterfeit_backlash_percent'];
    }

    public function counterfeitDefuseEssence(): int
    {
        return $this->definition()['counterfeit_defuse_essence'];
    }

    public function counterfeitEyeTier(): ReputationTier
    {
        return $this->definition()['counterfeit_eye_tier'];
    }

    public function counterfeitDefuseTier(): ReputationTier
    {
        return $this->definition()['counterfeit_defuse_tier'];
    }

    public function counterfeitForgeTier(): ReputationTier
    {
        return $this->definition()['counterfeit_forge_tier'];
    }

    public function counterfeitForgeRecipeSlug(): string
    {
        return $this->definition()['counterfeit_forge_recipe_slug'];
    }

    /**
     * @return array{
     *   night_explorations: int,
     *   cut_percent: int,
     *   weekly_lot_cap: int,
     *   required_tier: ReputationTier,
     *   counter_pnj_slugs: list<string>,
     *   rumor_price_gils: int,
     *   counterfeit_charges_min: int,
     *   counterfeit_charges_max: int,
     *   counterfeit_loot_chance_percent: int,
     *   counterfeit_backlash_percent: int,
     *   counterfeit_defuse_essence: int,
     *   counterfeit_eye_tier: ReputationTier,
     *   counterfeit_defuse_tier: ReputationTier,
     *   counterfeit_forge_tier: ReputationTier,
     *   counterfeit_forge_recipe_slug: string
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
     *   rumor_price_gils: int,
     *   counterfeit_charges_min: int,
     *   counterfeit_charges_max: int,
     *   counterfeit_loot_chance_percent: int,
     *   counterfeit_backlash_percent: int,
     *   counterfeit_defuse_essence: int,
     *   counterfeit_eye_tier: ReputationTier,
     *   counterfeit_defuse_tier: ReputationTier,
     *   counterfeit_forge_tier: ReputationTier,
     *   counterfeit_forge_recipe_slug: string
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
     *   rumor_price_gils: int,
     *   counterfeit_charges_min: int,
     *   counterfeit_charges_max: int,
     *   counterfeit_loot_chance_percent: int,
     *   counterfeit_backlash_percent: int,
     *   counterfeit_defuse_essence: int,
     *   counterfeit_eye_tier: ReputationTier,
     *   counterfeit_defuse_tier: ReputationTier,
     *   counterfeit_forge_tier: ReputationTier,
     *   counterfeit_forge_recipe_slug: string
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

        $counterfeit = $ruelles['counterfeit'] ?? null;
        if (!\is_array($counterfeit)) {
            throw new FactionTensionDefinitionException(sprintf('The ruelles block of "%s" must declare a "counterfeit" mapping.', $source));
        }

        $chargesMin = $counterfeit['charges_min'] ?? null;
        $chargesMax = $counterfeit['charges_max'] ?? null;
        if (!\is_int($chargesMin) || $chargesMin < 2) {
            // Une contrefacon qui trahit au premier geste est un piege, pas
            // une trahison : le canon veut qu'elle marche, longtemps.
            throw new FactionTensionDefinitionException(sprintf('The counterfeit charges floor of "%s" must be an integer >= 2.', $source));
        }
        if (!\is_int($chargesMax) || $chargesMax < $chargesMin) {
            throw new FactionTensionDefinitionException(sprintf('The counterfeit charges ceiling of "%s" must be >= its floor.', $source));
        }

        $lootChance = $counterfeit['loot_chance_percent'] ?? null;
        if (!\is_int($lootChance) || $lootChance < 1 || $lootChance > 100) {
            // A zero, l'etat non identifie n'existerait nulle part et l'œil
            // du faussaire ne servirait a rien ; a cent, tout butin trahirait.
            throw new FactionTensionDefinitionException(sprintf('The counterfeit loot chance of "%s" must be between 1 and 100.', $source));
        }

        $backlash = $counterfeit['backlash_percent_max_life'] ?? null;
        if (!\is_int($backlash) || $backlash < 1 || $backlash > 100) {
            throw new FactionTensionDefinitionException(sprintf('The counterfeit backlash of "%s" must be between 1 and 100 percent of max life.', $source));
        }

        $defuseEssence = $counterfeit['defuse_essence'] ?? null;
        if (!\is_int($defuseEssence) || $defuseEssence < 1) {
            throw new FactionTensionDefinitionException(sprintf('The counterfeit defuse essence of "%s" must be a positive integer.', $source));
        }

        $tiers = [];
        foreach (['eye_tier', 'defuse_tier', 'forge_tier'] as $tierKey) {
            $tierValue = $counterfeit[$tierKey] ?? null;
            $tier = \is_string($tierValue) ? ReputationTier::tryFrom($tierValue) : null;
            if (null === $tier) {
                throw new FactionTensionDefinitionException(sprintf('The counterfeit %s of "%s" does not name a reputation tier.', $tierKey, $source));
            }
            $tiers[$tierKey] = $tier;
        }

        $forgeRecipeSlug = $counterfeit['forge_recipe_slug'] ?? null;
        if (!\is_string($forgeRecipeSlug) || trim($forgeRecipeSlug) === '') {
            throw new FactionTensionDefinitionException(sprintf('The counterfeit forge recipe of "%s" must name a recipe slug.', $source));
        }

        return [
            'night_explorations' => $threshold,
            'cut_percent' => $cut,
            'weekly_lot_cap' => $cap,
            'required_tier' => $requiredTier,
            'counter_pnj_slugs' => $slugs,
            'rumor_price_gils' => $rumorPrice,
            'counterfeit_charges_min' => $chargesMin,
            'counterfeit_charges_max' => $chargesMax,
            'counterfeit_loot_chance_percent' => $lootChance,
            'counterfeit_backlash_percent' => $backlash,
            'counterfeit_defuse_essence' => $defuseEssence,
            'counterfeit_eye_tier' => $tiers['eye_tier'],
            'counterfeit_defuse_tier' => $tiers['defuse_tier'],
            'counterfeit_forge_tier' => $tiers['forge_tier'],
            'counterfeit_forge_recipe_slug' => $forgeRecipeSlug,
        ];
    }
}
