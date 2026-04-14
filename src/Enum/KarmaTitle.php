<?php

namespace App\Enum;

/**
 * Titre global de reputation du joueur, base sur le score de reputation.
 * Distinct de ReputationTier qui s'applique aux factions.
 */
enum KarmaTitle: string
{
    case Infame = 'infame';
    case Novice = 'novice';
    case Connu = 'connu';
    case Respecte = 'respecte';
    case Honore = 'honore';
    case Heros = 'heros';
    case Legendaire = 'legendaire';

    public function label(): string
    {
        return match ($this) {
            self::Infame => 'Infâme',
            self::Novice => 'Novice',
            self::Connu => 'Connu',
            self::Respecte => 'Respecté',
            self::Honore => 'Honoré',
            self::Heros => 'Héros',
            self::Legendaire => 'Légendaire',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Infame => 'text-red-400',
            self::Novice => 'text-gray-300',
            self::Connu => 'text-green-400',
            self::Respecte => 'text-blue-400',
            self::Honore => 'text-indigo-400',
            self::Heros => 'text-purple-400',
            self::Legendaire => 'text-yellow-400',
        };
    }

    /**
     * Score minimum requis pour atteindre ce titre.
     * Infame est reserve aux scores strictement negatifs.
     */
    public function threshold(): int
    {
        return match ($this) {
            self::Infame => \PHP_INT_MIN,
            self::Novice => 0,
            self::Connu => 200,
            self::Respecte => 1000,
            self::Honore => 3000,
            self::Heros => 8000,
            self::Legendaire => 20000,
        };
    }

    public static function fromScore(int $score): self
    {
        if ($score < 0) {
            return self::Infame;
        }

        $result = self::Novice;
        foreach ([self::Connu, self::Respecte, self::Honore, self::Heros, self::Legendaire] as $tier) {
            if ($score >= $tier->threshold()) {
                $result = $tier;
            }
        }

        return $result;
    }

    public function nextTitle(): ?self
    {
        return match ($this) {
            self::Infame => self::Novice,
            self::Novice => self::Connu,
            self::Connu => self::Respecte,
            self::Respecte => self::Honore,
            self::Honore => self::Heros,
            self::Heros => self::Legendaire,
            self::Legendaire => null,
        };
    }
}
