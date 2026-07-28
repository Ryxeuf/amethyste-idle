<?php

namespace App\Enum;

/**
 * Rang d'un foyer (FOY-01).
 *
 * Le rang se lit sur la **somme** des quatre indices de sediment, jamais sur un
 * seul : une ville peut monter par le commerce, par la guerre ou par le savoir.
 * Les seuils vivent dans `config/game/settlements.yaml` et sont chiffres dans
 * [BALANCE.md § 23.3](../../docs/BALANCE.md) — jamais dans cette classe.
 */
enum SettlementRank: string
{
    case Ruin = 'ruin';
    case Camp = 'camp';
    case Hamlet = 'hamlet';
    case Town = 'town';
    case City = 'city';
    case Metropolis = 'metropolis';

    public function label(): string
    {
        return match ($this) {
            self::Ruin => 'Ruine',
            self::Camp => 'Campement',
            self::Hamlet => 'Hameau',
            self::Town => 'Bourg',
            self::City => 'Cite',
            self::Metropolis => 'Metropole',
        };
    }

    /**
     * Position dans l'echelle, de 0 (Ruine) a 5 (Metropole).
     *
     * Sert aux comparaisons — « ce service exige au moins le Bourg » — sans
     * exposer l'ordre de declaration de l'enum, qui n'est pas un contrat.
     */
    public function level(): int
    {
        return match ($this) {
            self::Ruin => 0,
            self::Camp => 1,
            self::Hamlet => 2,
            self::Town => 3,
            self::City => 4,
            self::Metropolis => 5,
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->level() >= $other->level();
    }

    /**
     * Rang immediatement superieur, ou `null` au sommet de l'echelle.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Ruin => self::Camp,
            self::Camp => self::Hamlet,
            self::Hamlet => self::Town,
            self::Town => self::City,
            self::City => self::Metropolis,
            self::Metropolis => null,
        };
    }

    /**
     * Rangs ordonnes du plus bas au plus haut.
     *
     * @return list<self>
     */
    public static function ordered(): array
    {
        $ranks = self::cases();
        usort($ranks, static fn (self $a, self $b): int => $a->level() <=> $b->level());

        return $ranks;
    }
}
