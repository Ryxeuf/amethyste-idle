<?php

namespace App\GameEngine\Reputation;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Ce qu'etre Hostile coute, maison par maison — lu dans
 * `config/game/factions.yaml` (FAC-03).
 *
 * GAME_WORLD § 6.4 d : une reputation negative a des consequences, mais
 * **jamais sur la boucle cœur**. La garantie n'est pas une liste de gardes
 * dispersees dans le code : elle est dans le **vocabulaire ferme** des types
 * de consequence. Aucun type ne sait bloquer l'energie, le voyage de base, le
 * combat ou le plancher T1 — une consequence surcharge un prix, allonge un
 * trajet ou refuse un privilege ; elle ne ferme jamais un droit. Ajouter un
 * type, c'est passer par ici, ou le garde-fou se voit.
 *
 * **Un crochet inerte n'est pas invalide.** `altar_tax_ceiling` attend
 * l'Autel d'eveil, `poisoned_rumors` les rumeurs de FAC-06,
 * `buyback_floor_closed` et `materia_reading_refused` la Fonderie de FAC-04 :
 * les consequences sont declarees des maintenant et mordront le jour ou leur
 * substrat arrive — sans qu'on revienne ici.
 */
class HostileConsequenceCatalog
{
    /**
     * Le vocabulaire ferme des consequences. Les deux premieres sont des
     * surcharges (elles portent un `percent`), les autres des refus de
     * privilege — jamais de droit.
     */
    public const TYPES = [
        'shop_surcharge',
        'bastion_travel_surcharge',
        'altar_tax_ceiling',
        'poisoned_rumors',
        'buyback_floor_closed',
        'materia_reading_refused',
    ];

    /**
     * Les types qui portent un pourcentage de surcharge.
     */
    public const SURCHARGE_TYPES = ['shop_surcharge', 'bastion_travel_surcharge'];

    /**
     * @var array<string, list<array{type: string, percent: int|null}>>|null
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

    /**
     * @return list<array{type: string, percent: int|null}>
     */
    public function consequencesFor(string $factionSlug): array
    {
        return $this->definition()[$factionSlug] ?? [];
    }

    public function hasConsequence(string $factionSlug, string $type): bool
    {
        foreach ($this->consequencesFor($factionSlug) as $consequence) {
            if ($consequence['type'] === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Le pourcentage d'une surcharge, ou 0 si la faction ne la porte pas.
     */
    public function percentFor(string $factionSlug, string $type): int
    {
        foreach ($this->consequencesFor($factionSlug) as $consequence) {
            if ($consequence['type'] === $type) {
                return $consequence['percent'] ?? 0;
            }
        }

        return 0;
    }

    /**
     * @return list<string> les factions qui declarent au moins une consequence
     */
    public function factions(): array
    {
        return array_keys($this->definition());
    }

    /**
     * @return array<string, list<array{type: string, percent: int|null}>>
     */
    private function definition(): array
    {
        if ($this->definition === null) {
            $this->definition = $this->load($this->defaultFile());
        }

        return $this->definition;
    }

    /**
     * @return array<string, list<array{type: string, percent: int|null}>>
     *
     * @throws FactionTensionDefinitionException
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new FactionTensionDefinitionException(sprintf('Faction hostile catalogue not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new FactionTensionDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new FactionTensionDefinitionException(sprintf('Faction hostile catalogue "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<string, list<array{type: string, percent: int|null}>>
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $hostile = $raw['hostile'] ?? null;
        if (!\is_array($hostile) || !\is_array($hostile['consequences'] ?? null)) {
            throw new FactionTensionDefinitionException(sprintf('Catalogue "%s" must declare a "hostile.consequences" block.', $source));
        }

        $definition = [];
        foreach ($hostile['consequences'] as $slug => $consequences) {
            if (!\is_string($slug) || trim($slug) === '' || !\is_array($consequences) || [] === $consequences) {
                throw new FactionTensionDefinitionException(sprintf('Each hostile entry of "%s" must map a faction slug to a list of consequences.', $source));
            }

            $normalized = [];
            foreach (array_values($consequences) as $consequence) {
                if (!\is_array($consequence)) {
                    throw new FactionTensionDefinitionException(sprintf('Each hostile consequence of "%s" must be a mapping.', $source));
                }

                $type = $consequence['type'] ?? null;
                if (!\is_string($type) || !\in_array($type, self::TYPES, true)) {
                    // Le vocabulaire est ferme, et c'est le garde-fou : un type
                    // inconnu pourrait etre n'importe quoi — y compris un
                    // blocage de la boucle cœur, que rien d'autre ne verrait.
                    throw new FactionTensionDefinitionException(sprintf('Hostile consequence type "%s" of "%s" is not part of the closed vocabulary.', \is_string($type) ? $type : gettype($type), $source));
                }

                $percent = $consequence['percent'] ?? null;
                if (\in_array($type, self::SURCHARGE_TYPES, true)) {
                    if (!\is_int($percent) || $percent < 1 || $percent > 100) {
                        throw new FactionTensionDefinitionException(sprintf('The surcharge "%s" of "%s" needs a percent between 1 and 100.', $type, $source));
                    }
                } elseif ($percent !== null) {
                    throw new FactionTensionDefinitionException(sprintf('The consequence "%s" of "%s" is not a surcharge: it cannot carry a percent.', $type, $source));
                }

                $normalized[] = ['type' => $type, 'percent' => \is_int($percent) ? $percent : null];
            }

            $definition[$slug] = $normalized;
        }

        return $definition;
    }
}
