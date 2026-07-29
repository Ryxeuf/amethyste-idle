<?php

namespace App\GameEngine\Economy;

use App\Enum\Purity;
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
     * @return array{scope: array{slug_prefixes: list<string>, excluded_slugs: list<string>, included_slugs: list<string>}, draw: array{base_weights: array<string, int>, vitality_ceilings: list<array{at_least: float, band: Purity}>, skill_weight_per_point: int, skill_weight_cap: int}, signatures: array<string, array{weight_shift: int, night_weight_shift: int}>}
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
     * @return array{scope: array{slug_prefixes: list<string>, excluded_slugs: list<string>, included_slugs: list<string>}, draw: array{base_weights: array<string, int>, vitality_ceilings: list<array{at_least: float, band: Purity}>, skill_weight_per_point: int, skill_weight_cap: int}, signatures: array<string, array{weight_shift: int, night_weight_shift: int}>}
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
            'draw' => $this->normalizeDraw($raw['draw'] ?? null, $source),
            'signatures' => $this->normalizeSignatures($raw['signatures'] ?? [], $source),
        ];
    }

    /**
     * La signature d'amethyste de chaque zone (ZON-32).
     *
     * « Une zone, une facon dont le temps s'est depose. » La table est
     * facultative — une zone absente tire comme la reference, et c'est le
     * defaut voulu : livrer une zone neuve ne doit pas exiger d'en decrire la
     * geologie avant qu'on sache ce qu'elle sera.
     *
     * Un signe **nul** est valide, et c'est voulu : la Foret est inscrite a
     * zero parce qu'elle **est** la reference du monde. Le dire explicitement
     * vaut mieux que de le laisser deviner par une absence, qui ne distingue
     * pas « neutre par nature » de « pas encore decrit ».
     *
     * @return array<string, array{weight_shift: int, night_weight_shift: int}>
     */
    private function normalizeSignatures(mixed $raw, string $source): array
    {
        if (!\is_array($raw)) {
            throw new PurityDefinitionException(sprintf('"signatures" must be a mapping in "%s".', $source));
        }

        $signatures = [];
        foreach ($raw as $slug => $entry) {
            if (!\is_string($slug) || trim($slug) === '') {
                throw new PurityDefinitionException(sprintf('Signature keys must be zone slugs in "%s".', $source));
            }
            if (!\is_array($entry)) {
                throw new PurityDefinitionException(sprintf('Signature of zone "%s" must be a mapping in "%s".', $slug, $source));
            }

            $day = $entry['weight_shift'] ?? null;
            if (!is_numeric($day)) {
                throw new PurityDefinitionException(sprintf('Signature of zone "%s" needs a numeric "weight_shift" in "%s".', $slug, $source));
            }

            // Le signe de nuit est facultatif : la plupart des zones deposent le
            // temps de la meme facon a toute heure. Absent, il vaut celui du
            // jour — et non zero, qui ferait de chaque nuit une zone neutre.
            $night = $entry['night_weight_shift'] ?? $day;
            if (!is_numeric($night)) {
                throw new PurityDefinitionException(sprintf('Signature of zone "%s" has a non-numeric "night_weight_shift" in "%s".', $slug, $source));
            }

            $signatures[$slug] = [
                'weight_shift' => (int) $day,
                'night_weight_shift' => (int) $night,
            ];
        }

        return $signatures;
    }

    /**
     * Le tirage de la bande a la recolte (ECO-22).
     *
     * Trois refus, et chacun ferme une facon d'ecrire une regle qui ne
     * s'appliquerait jamais :
     *
     * - une **bande sans poids** ne sortirait pas du tirage, et son absence
     *   passerait pour de la malchance plutot que pour un oubli ;
     * - un **plafond qui ne descend pas** laisserait le parfait accessible a un
     *   filon a sec, ce qui annulerait le signal de vitalite que ZON-37 existe
     *   pour rendre lisible ;
     * - un **plafond sans plancher a zero** laisserait un filon epuise sans
     *   bande du tout, et la recolte rendrait alors des lots sans purete dans un
     *   perimetre qui en exige une.
     *
     * @return array{base_weights: array<string, int>, vitality_ceilings: list<array{at_least: float, band: Purity}>, skill_weight_per_point: int, skill_weight_cap: int}
     */
    private function normalizeDraw(mixed $draw, string $source): array
    {
        if (!\is_array($draw)) {
            throw new PurityDefinitionException(sprintf('Purity config "%s" must declare "draw".', $source));
        }

        $weights = [];
        $rawWeights = $draw['base_weights'] ?? null;
        if (!\is_array($rawWeights)) {
            throw new PurityDefinitionException(sprintf('"draw.base_weights" must be a mapping in "%s".', $source));
        }
        foreach (Purity::ordered() as $band) {
            $weight = $rawWeights[$band->value] ?? null;
            if (!is_numeric($weight) || (int) $weight < 0) {
                throw new PurityDefinitionException(sprintf('Band "%s" needs a non-negative weight in "draw.base_weights" of "%s".', $band->value, $source));
            }
            $weights[$band->value] = (int) $weight;
        }
        if (array_sum($weights) <= 0) {
            throw new PurityDefinitionException(sprintf('"draw.base_weights" must not be all zero in "%s".', $source));
        }

        $ceilings = [];
        $rawCeilings = $draw['vitality_ceilings'] ?? null;
        if (!\is_array($rawCeilings) || $rawCeilings === []) {
            throw new PurityDefinitionException(sprintf('"draw.vitality_ceilings" must list at least one ceiling in "%s".', $source));
        }
        $previous = null;
        foreach ($rawCeilings as $entry) {
            if (!\is_array($entry) || !is_numeric($entry['at_least'] ?? null)) {
                throw new PurityDefinitionException(sprintf('Each entry of "draw.vitality_ceilings" needs a numeric "at_least" in "%s".', $source));
            }
            $threshold = (float) $entry['at_least'];
            $band = \is_string($entry['band'] ?? null) ? Purity::tryFrom($entry['band']) : null;
            if ($band === null) {
                throw new PurityDefinitionException(sprintf('An entry of "draw.vitality_ceilings" names an unknown band in "%s".', $source));
            }
            if ($previous !== null && $threshold >= $previous) {
                throw new PurityDefinitionException(sprintf('"draw.vitality_ceilings" must descend: %.2f does not sit below the previous threshold in "%s".', $threshold, $source));
            }
            $previous = $threshold;
            $ceilings[] = ['at_least' => $threshold, 'band' => $band];
        }
        if ($ceilings[array_key_last($ceilings)]['at_least'] > 0.0) {
            throw new PurityDefinitionException(sprintf('"draw.vitality_ceilings" must end at 0 in "%s": an exhausted vein still yields a band.', $source));
        }

        return [
            'base_weights' => $weights,
            'vitality_ceilings' => $ceilings,
            'skill_weight_per_point' => $this->nonNegativeInt($draw['skill_weight_per_point'] ?? null, 'draw.skill_weight_per_point', $source),
            'skill_weight_cap' => $this->nonNegativeInt($draw['skill_weight_cap'] ?? null, 'draw.skill_weight_cap', $source),
        ];
    }

    private function nonNegativeInt(mixed $value, string $key, string $source): int
    {
        if (!is_numeric($value) || (int) $value < 0) {
            throw new PurityDefinitionException(sprintf('"%s" must be a non-negative integer in "%s".', $key, $source));
        }

        return (int) $value;
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
