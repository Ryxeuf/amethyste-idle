<?php

namespace App\GameEngine\Settlement;

use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\Enum\SettlementType;
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
     *     workshop: array{rank_bonus: array<string, int>, type_bonus: array<string, array<string, int>>, line_bonus: array<string, array<string, int>>, cap: int, zone_line: array<string, string>},
     *     weekly_work: array{demands: array<string, list<string>>, targets: array<string, int>, rank_multipliers: array<string, int>},
     *     crue: array<string, int>,
     *     seed: array<string, array{rank: SettlementRank, stock: int}>,
     *     without_settlement: array<string, string>,
     *     paleness: array{rise_per_pressure: float, daily_recovery: float, max: float, visible_from: float, dulls_purity_from: float},
     *     restoration: array{cost_per_point: int, duration_days: int, daily_bonus: float, opens_from: float},
     *     doctrine: array{minimum_rank: SettlementRank, cost: int, lock_days: int, foundry: array{gather_bonus: float, paleness_multiplier: float}, readers: array{lore_multiplier: float, paleness_multiplier: float}}
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
     *     workshop: array{rank_bonus: array<string, int>, type_bonus: array<string, array<string, int>>, line_bonus: array<string, array<string, int>>, cap: int, zone_line: array<string, string>},
     *     weekly_work: array{demands: array<string, list<string>>, targets: array<string, int>, rank_multipliers: array<string, int>},
     *     crue: array<string, int>,
     *     seed: array<string, array{rank: SettlementRank, stock: int}>,
     *     without_settlement: array<string, string>,
     *     paleness: array{rise_per_pressure: float, daily_recovery: float, max: float, visible_from: float, dulls_purity_from: float},
     *     restoration: array{cost_per_point: int, duration_days: int, daily_bonus: float, opens_from: float},
     *     doctrine: array{minimum_rank: SettlementRank, cost: int, lock_days: int, foundry: array{gather_bonus: float, paleness_multiplier: float}, readers: array{lore_multiplier: float, paleness_multiplier: float}}
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
        $paleness = $this->normalizePaleness($raw['paleness'] ?? [], $source);

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
            'workshop' => $this->normalizeWorkshop($raw['workshop'] ?? [], $source),
            'weekly_work' => $this->normalizeWeeklyWork($raw['weekly_work'] ?? [], $source),
            'crue' => $this->normalizeCrue($raw['crue'] ?? [], $source),
            'housing' => $this->normalizeHousing($raw['housing'] ?? [], $source),
            'seed' => $this->normalizeSeed($raw['seed'] ?? [], $source),
            'without_settlement' => $this->normalizeWithout($raw['without_settlement'] ?? [], $source),
            'paleness' => $paleness,
            'restoration' => $this->normalizeRestoration($raw['restoration'] ?? [], $paleness, $source),
            'doctrine' => $this->normalizeDoctrine($raw['doctrine'] ?? [], $source),
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

            $capped = $entry['capped'] ?? true;
            if (!\is_bool($capped)) {
                throw new SettlementDefinitionException(sprintf('Sediment rule "%s" must declare "capped" as a boolean in "%s".', $action, $source));
            }

            $rules[$action] = new SedimentRule($action, $index, (float) $grains, $capped);
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
     * Bonus d'atelier (FOY-07).
     *
     * Deux verifications portent tout le poids de cette methode, et ce sont
     * celles qui attrapent des **lignes muettes** — des reglages ecrits de bonne
     * foi qui n'agiront jamais, et dont l'absence d'effet ne se remarquerait
     * qu'au moment ou quelqu'un se demanderait pourquoi sa Metropole ne donne
     * rien : une zone qui nomme une ligne de production inconnue de
     * `line_bonus`, et un type de foyer qui n'existe pas.
     *
     * Le bloc entier est **facultatif**. Un monde sans bonus d'atelier est un
     * monde jouable ; le refuser aurait fait echouer le chargement de toute
     * configuration anterieure a ce jalon.
     *
     * @return array{
     *     rank_bonus: array<string, int>,
     *     type_bonus: array<string, array<string, int>>,
     *     line_bonus: array<string, array<string, int>>,
     *     cap: int,
     *     zone_line: array<string, string>
     * }
     */
    private function normalizeWorkshop(mixed $workshop, string $source): array
    {
        if (!\is_array($workshop)) {
            throw new SettlementDefinitionException(sprintf('"workshop" must be a mapping in "%s".', $source));
        }

        $rankBonus = [];
        foreach ($this->mapping($workshop['rank_bonus'] ?? [], 'workshop.rank_bonus', $source) as $rank => $points) {
            $this->normalizeRank($rank, sprintf('workshop.rank_bonus.%s', $rank), $source);
            $rankBonus[$rank] = $this->normalizeBonusPoints($points, sprintf('workshop.rank_bonus.%s', $rank), $source);
        }

        $lineBonus = [];
        foreach ($this->mapping($workshop['line_bonus'] ?? [], 'workshop.line_bonus', $source) as $line => $crafts) {
            $lineBonus[$line] = $this->normalizeCraftBonus($crafts, sprintf('workshop.line_bonus.%s', $line), $source);
        }

        $typeBonus = [];
        foreach ($this->mapping($workshop['type_bonus'] ?? [], 'workshop.type_bonus', $source) as $type => $crafts) {
            if (SettlementType::tryFrom($type) === null) {
                throw new SettlementDefinitionException(sprintf('"workshop.type_bonus" names an unknown settlement type "%s" in "%s".', $type, $source));
            }
            $typeBonus[$type] = $this->normalizeCraftBonus($crafts, sprintf('workshop.type_bonus.%s', $type), $source);
        }

        $zoneLine = [];
        foreach ($this->mapping($workshop['zone_line'] ?? [], 'workshop.zone_line', $source) as $slug => $line) {
            if (!\is_string($line) || !isset($lineBonus[$line])) {
                throw new SettlementDefinitionException(sprintf('Zone "%s" names a production line ("%s") absent from "workshop.line_bonus" in "%s".', $slug, \is_string($line) ? $line : '?', $source));
            }
            $zoneLine[$slug] = $line;
        }

        return [
            'rank_bonus' => $rankBonus,
            'type_bonus' => $typeBonus,
            'line_bonus' => $lineBonus,
            'cap' => $this->normalizeBonusPoints($workshop['cap'] ?? 0, 'workshop.cap', $source),
            'zone_line' => $zoneLine,
        ];
    }

    /**
     * Le chantier de la semaine (RET-05).
     *
     * Trois refus, chacun contre une ligne qui ne s'appliquerait jamais :
     *
     * - un **type sans demande** laisserait les foyers de ce type sans chantier,
     *   c'est-a-dire sans le seul rendez-vous collectif qu'ils puissent offrir,
     *   et rien ne le dirait ;
     * - une **demande sans cible** produirait un besoin a zero, donc rempli
     *   d'avance — un chantier qui se termine sans que personne n'y touche ;
     * - un **rang sans multiplicateur** ferait tomber le calcul sur un defaut
     *   muet plutot que sur une decision de calibrage.
     *
     * Le bonus de cloture n'est pas ici : c'est un depot, et il vit avec le reste
     * de la table `sediment` (ligne `settlement_work`), comme celui de la
     * commission. Un chiffrage de depot a deux endroits aurait fini par diverger.
     *
     * Le bloc entier est facultatif : un monde sans chantier reste jouable.
     *
     * @return array{demands: array<string, list<string>>, targets: array<string, int>, rank_multipliers: array<string, int>}
     */
    private function normalizeWeeklyWork(mixed $work, string $source): array
    {
        if (!\is_array($work)) {
            throw new SettlementDefinitionException(sprintf('"weekly_work" must be a mapping in "%s".', $source));
        }

        $targets = [];
        foreach ($this->mapping($work['targets'] ?? [], 'weekly_work.targets', $source) as $activity => $target) {
            if (!is_numeric($target) || (int) $target < 1) {
                throw new SettlementDefinitionException(sprintf('"weekly_work.targets.%s" must be a positive integer in "%s".', $activity, $source));
            }
            $targets[$activity] = (int) $target;
        }

        $demands = [];
        foreach ($this->mapping($work['demands'] ?? [], 'weekly_work.demands', $source) as $type => $activities) {
            if (!\is_array($activities) || $activities === []) {
                throw new SettlementDefinitionException(sprintf('"weekly_work.demands.%s" must list at least one activity in "%s".', $type, $source));
            }

            $list = [];
            foreach ($activities as $activity) {
                if (!\is_string($activity) || !isset($targets[$activity])) {
                    throw new SettlementDefinitionException(sprintf('"weekly_work.demands.%s" names an activity without a target in "%s".', $type, $source));
                }
                $list[] = $activity;
            }
            $demands[$type] = $list;
        }

        $multipliers = [];
        if ($demands !== []) {
            foreach (SettlementRank::cases() as $rank) {
                $value = $work['rank_multipliers'][$rank->value] ?? null;
                if (!is_numeric($value) || (int) $value < 1) {
                    throw new SettlementDefinitionException(sprintf('Rank "%s" needs a positive "weekly_work.rank_multipliers" entry in "%s".', $rank->value, $source));
                }
                $multipliers[$rank->value] = (int) $value;
            }
        }

        return [
            'demands' => $demands,
            'targets' => $targets,
            'rank_multipliers' => $multipliers,
        ];
    }

    /**
     * Les quotas de la Crue (FOY-08).
     *
     * @return array<string, int> rang => actifs requis par foyer de ce rang ou plus
     */
    /**
     * Le logement par rang (FOY-18) : la capacite de parcelles de chaque
     * rang, croissante avec le rang — un Bourg qui logerait moins qu'un
     * Hameau inverserait l'echelle sans que rien ne le dise. Le bloc est
     * optionnel (absent = aucun rang ne loge, la regle est inerte), mais des
     * qu'un rang loge, Ruine et Campement sont refuses : on ne s'installe
     * pas dans ce qui peut disparaitre.
     *
     * @return array{parcels_per_rank: array<string, int>}
     */
    private function normalizeHousing(mixed $housing, string $source): array
    {
        if (!\is_array($housing)) {
            throw new SettlementDefinitionException(sprintf('"housing" must be a mapping in "%s".', $source));
        }

        $declared = $housing['parcels_per_rank'] ?? [];
        if (!\is_array($declared)) {
            throw new SettlementDefinitionException(sprintf('"housing.parcels_per_rank" must be a mapping in "%s".', $source));
        }

        foreach (array_keys($declared) as $key) {
            $rank = SettlementRank::tryFrom((string) $key);
            if (null === $rank) {
                throw new SettlementDefinitionException(sprintf('"housing.parcels_per_rank.%s" does not name a settlement rank in "%s".', $key, $source));
            }
            if (!$rank->isAtLeast(SettlementRank::Hamlet)) {
                throw new SettlementDefinitionException(sprintf('"housing.parcels_per_rank.%s" is below hamlet in "%s": one does not settle in what can vanish.', $key, $source));
            }
        }

        $capacities = [];
        $previous = 0;
        foreach (SettlementRank::ordered() as $rank) {
            $value = $declared[$rank->value] ?? null;
            if ($value === null) {
                continue;
            }
            if (!is_numeric($value) || (int) $value < 1) {
                throw new SettlementDefinitionException(sprintf('"housing.parcels_per_rank.%s" must be a positive integer in "%s".', $rank->value, $source));
            }

            $value = (int) $value;
            if ($value <= $previous) {
                throw new SettlementDefinitionException(sprintf('"housing.parcels_per_rank" must increase with rank: "%s" (%d) is not above the previous (%d) in "%s".', $rank->value, $value, $previous, $source));
            }

            $capacities[$rank->value] = $value;
            $previous = $value;
        }

        return ['parcels_per_rank' => $capacities];
    }

    private function normalizeCrue(mixed $crue, string $source): array
    {
        if (!\is_array($crue)) {
            throw new SettlementDefinitionException(sprintf('"crue" must be a mapping in "%s".', $source));
        }

        $quotas = [];
        $previous = 0;
        foreach (SettlementRank::ordered() as $rank) {
            $value = $crue['actives_per_settlement'][$rank->value] ?? null;
            if ($value === null) {
                continue;
            }
            if (!is_numeric($value) || (int) $value < 1) {
                throw new SettlementDefinitionException(sprintf('"crue.actives_per_settlement.%s" must be a positive integer in "%s".', $rank->value, $source));
            }

            $value = (int) $value;
            if ($value <= $previous) {
                // Un rang superieur moins exigeant qu'un rang inferieur rendrait
                // le quota du haut plus large que celui du bas : une Metropole
                // serait plus facile a tenir qu'un Bourg, et l'echelle
                // d'ouverture s'inverserait sans que rien ne le dise.
                throw new SettlementDefinitionException(sprintf('"crue.actives_per_settlement" must increase with rank: "%s" (%d) is not above the previous (%d) in "%s".', $rank->value, $value, $previous, $source));
            }

            $quotas[$rank->value] = $value;
            $previous = $value;
        }

        return $quotas;
    }

    /**
     * @return array<string, int>
     */
    private function normalizeCraftBonus(mixed $crafts, string $key, string $source): array
    {
        $normalized = [];
        foreach ($this->mapping($crafts, $key, $source) as $craft => $points) {
            $normalized[$craft] = $this->normalizeBonusPoints($points, sprintf('%s.%s', $key, $craft), $source);
        }

        return $normalized;
    }

    /**
     * Un bonus **negatif** est refuse : le foyer ajoute, il ne retranche jamais.
     * Une ville qui rendrait un etabli moins bon qu'ailleurs serait une punition
     * pour l'avoir frequentee.
     */
    private function normalizeBonusPoints(mixed $value, string $key, string $source): int
    {
        if (!is_numeric($value) || (int) $value < 0) {
            throw new SettlementDefinitionException(sprintf('"%s" must be a non-negative integer in "%s".', $key, $source));
        }

        return (int) $value;
    }

    /**
     * Sous-tableau a clefs de chaine, ou tableau vide.
     *
     * @return array<string, mixed>
     */
    private function mapping(mixed $value, string $key, string $source): array
    {
        if (!\is_array($value)) {
            throw new SettlementDefinitionException(sprintf('"%s" must be a mapping in "%s".', $key, $source));
        }

        $normalized = [];
        foreach ($value as $entry => $content) {
            if (!\is_string($entry) || trim($entry) === '') {
                throw new SettlementDefinitionException(sprintf('Keys of "%s" must be names in "%s".', $key, $source));
            }
            $normalized[$entry] = $content;
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

    /**
     * Les cinq curseurs de la Paleur (FOY-11).
     *
     * Deux invariants sont verifies ici parce qu'ils ne se voient nulle part
     * ailleurs : la recuperation doit rester **plus lente** que la montee
     * (abimer va plus vite que reparer, sinon la trace n'en est pas une), et
     * aucun seuil d'effet ne doit se trouver **au-dessus du plafond** — il ne
     * se declencherait jamais, et rien ne le dirait.
     *
     * Le plancher dur du socle de monde — un filon pali n'est **jamais**
     * sterile, ce qui le distingue d'une Etale (GAME_WORLD § 12.1) — est deja
     * garanti par `normalizeRate`, qui refuse tout taux hors de ]0, 1[. Le
     * redire ici serait un garde-fou qui ne peut pas se declencher.
     *
     * @param array<array-key, mixed> $raw
     *
     * @return array{rise_per_pressure: float, daily_recovery: float, max: float, visible_from: float, dulls_purity_from: float}
     */
    private function normalizePaleness(array $raw, string $source): array
    {
        $rise = $this->normalizeRate($raw['rise_per_pressure'] ?? null, 'paleness.rise_per_pressure', $source);
        $recovery = $this->normalizeRate($raw['daily_recovery'] ?? null, 'paleness.daily_recovery', $source);
        $max = $this->normalizeRate($raw['max'] ?? null, 'paleness.max', $source);

        if ($recovery >= $rise) {
            throw new SettlementDefinitionException(sprintf('"paleness.daily_recovery" (%.2f) must stay below "paleness.rise_per_pressure" (%.2f) in "%s" : abimer doit aller plus vite que reparer.', $recovery, $rise, $source));
        }

        $visible = $this->normalizeRate($raw['visible_from'] ?? null, 'paleness.visible_from', $source);
        $dulls = $this->normalizeRate($raw['dulls_purity_from'] ?? null, 'paleness.dulls_purity_from', $source);

        // Un seuil au-dessus du plafond ne se declenche **jamais** : l'effet
        // serait declare, jamais applique, et rien ne le dirait. C'est
        // exactement la famille de defaut muet que ce projet passe son temps a
        // deterrer.
        foreach (['paleness.visible_from' => $visible, 'paleness.dulls_purity_from' => $dulls] as $key => $threshold) {
            if ($threshold > $max) {
                throw new SettlementDefinitionException(sprintf('"%s" (%.2f) is above "paleness.max" (%.2f) in "%s" : le seuil ne serait jamais atteint.', $key, $threshold, $max, $source));
            }
        }

        return [
            'rise_per_pressure' => $rise,
            'daily_recovery' => $recovery,
            'max' => $max,
            'visible_from' => $visible,
            'dulls_purity_from' => $dulls,
        ];
    }

    /**
     * Le chantier de restauration (FOY-12).
     *
     * Deux invariants, et tous deux disent la meme chose sous deux angles :
     * **on n'achete pas un monde propre**.
     *
     * 1. Un chantier ne s'ouvre pas sous le seuil de visibilite. Payer pour
     *    effacer une trace que personne ne voit serait une depense sans public,
     *    alors que le jalon existe pour rendre la restauration *publique*.
     * 2. Le bonus quotidien ne peut pas atteindre la vitesse a laquelle on
     *    abime. S'il la depassait, une guilde riche pourrait presser un filon
     *    en continu et le tenir propre a coups de Gils — la Paleur cesserait
     *    d'etre une contrainte pour devenir une facture.
     *
     * @param array<array-key, mixed>                                                                                           $raw
     * @param array{rise_per_pressure: float, daily_recovery: float, max: float, visible_from: float, dulls_purity_from: float} $paleness
     *
     * @return array{cost_per_point: int, duration_days: int, daily_bonus: float, opens_from: float}
     */
    private function normalizeRestoration(array $raw, array $paleness, string $source): array
    {
        $costPerPoint = $this->normalizePositiveInt($raw['cost_per_point'] ?? null, 'restoration.cost_per_point', $source);
        $duration = $this->normalizePositiveInt($raw['duration_days'] ?? null, 'restoration.duration_days', $source);
        $bonus = $this->normalizeRate($raw['daily_bonus'] ?? null, 'restoration.daily_bonus', $source);
        $opensFrom = $this->normalizeRate($raw['opens_from'] ?? null, 'restoration.opens_from', $source);

        if ($opensFrom < $paleness['visible_from']) {
            throw new SettlementDefinitionException(sprintf('"restoration.opens_from" (%.2f) is below "paleness.visible_from" (%.2f) in "%s" : on n\'ouvre pas un chantier sur une trace que personne ne voit.', $opensFrom, $paleness['visible_from'], $source));
        }

        if ($bonus >= $paleness['rise_per_pressure']) {
            throw new SettlementDefinitionException(sprintf('"restoration.daily_bonus" (%.2f) must stay below "paleness.rise_per_pressure" (%.2f) in "%s" : payer ne doit jamais autoriser a presser un filon indefiniment.', $bonus, $paleness['rise_per_pressure'], $source));
        }

        return [
            'cost_per_point' => $costPerPoint,
            'duration_days' => $duration,
            'daily_bonus' => $bonus,
            'opens_from' => $opensFrom,
        ];
    }

    /**
     * Les deux ateliers de doctrine (FOY-13).
     *
     * L'invariant qui porte le jalon : **les deux ateliers doivent s'opposer**.
     * La Fonderie accelere la Paleur, les Lecteurs la ralentissent. Si les deux
     * multiplicateurs tombaient du meme cote — ou valaient 1 —, l'axe Extraire /
     * Preserver serait ecrit dans la documentation et absent du jeu : deux
     * boutons qui font la meme chose ne sont pas un choix.
     *
     * Le gain de chaque camp est verifie de la meme facon : un atelier qui
     * n'apporte rien est un cout sec, et personne ne le paierait deux fois.
     *
     * @param array<array-key, mixed> $raw
     *
     * @return array{minimum_rank: SettlementRank, cost: int, lock_days: int, foundry: array{gather_bonus: float, paleness_multiplier: float}, readers: array{lore_multiplier: float, paleness_multiplier: float}}
     */
    private function normalizeDoctrine(array $raw, string $source): array
    {
        $rank = $this->normalizeRank($raw['minimum_rank'] ?? null, 'doctrine.minimum_rank', $source);
        $cost = $this->normalizePositiveInt($raw['cost'] ?? null, 'doctrine.cost', $source);
        $lockDays = $this->normalizePositiveInt($raw['lock_days'] ?? null, 'doctrine.lock_days', $source);

        $foundryPaleness = $this->normalizePositiveFloat($raw['foundry']['paleness_multiplier'] ?? null, 'doctrine.foundry.paleness_multiplier', $source);
        $readersPaleness = $this->normalizePositiveFloat($raw['readers']['paleness_multiplier'] ?? null, 'doctrine.readers.paleness_multiplier', $source);

        if ($foundryPaleness <= 1.0) {
            throw new SettlementDefinitionException(sprintf('"doctrine.foundry.paleness_multiplier" (%.2f) must stay above 1 in "%s" : la Fonderie brule le cristal, elle ne le menage pas.', $foundryPaleness, $source));
        }

        if ($readersPaleness >= 1.0) {
            throw new SettlementDefinitionException(sprintf('"doctrine.readers.paleness_multiplier" (%.2f) must stay below 1 in "%s" : deux ateliers qui abiment autant ne sont pas un choix.', $readersPaleness, $source));
        }

        $gatherBonus = $this->normalizeRate($raw['foundry']['gather_bonus'] ?? null, 'doctrine.foundry.gather_bonus', $source);
        $loreMultiplier = $this->normalizePositiveFloat($raw['readers']['lore_multiplier'] ?? null, 'doctrine.readers.lore_multiplier', $source);

        if ($loreMultiplier <= 1.0) {
            throw new SettlementDefinitionException(sprintf('"doctrine.readers.lore_multiplier" (%.2f) must stay above 1 in "%s" : un atelier qui n\'apporte rien est un cout sec.', $loreMultiplier, $source));
        }

        return [
            'minimum_rank' => $rank,
            'cost' => $cost,
            'lock_days' => $lockDays,
            'foundry' => [
                'gather_bonus' => $gatherBonus,
                'paleness_multiplier' => $foundryPaleness,
            ],
            'readers' => [
                'lore_multiplier' => $loreMultiplier,
                'paleness_multiplier' => $readersPaleness,
            ],
        ];
    }

    /**
     * Facteur strictement positif, sans borne haute.
     *
     * `normalizeRate` refuse tout ce qui depasse 1 : elle sert aux taux, pas aux
     * multiplicateurs. Un atelier qui multiplie par 1,5 en aurait ete refuse.
     */
    private function normalizePositiveFloat(mixed $value, string $key, string $source): float
    {
        if (!is_numeric($value) || (float) $value <= 0.0) {
            throw new SettlementDefinitionException(sprintf('"%s" must be a positive number in "%s".', $key, $source));
        }

        return (float) $value;
    }
}
