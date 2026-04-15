<?php

namespace App\Enum;

/**
 * Paliers de renommee globale d'un joueur (reputation joueur distincte de la reputation par faction).
 *
 * Le score de renommee est cumulatif : il progresse via quetes, succes, evenements et aide au groupe.
 * Il ne descend pas naturellement (contrairement a la reputation de faction).
 */
enum PlayerRenownTier: string
{
    case Novice = 'novice';
    case Connu = 'connu';
    case Respecte = 'respecte';
    case Honore = 'honore';
    case Illustre = 'illustre';
    case Legendaire = 'legendaire';

    public function label(): string
    {
        return match ($this) {
            self::Novice => 'Novice',
            self::Connu => 'Connu',
            self::Respecte => 'Respecté',
            self::Honore => 'Honoré',
            self::Illustre => 'Illustre',
            self::Legendaire => 'Légendaire',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Novice => 'text-gray-400',
            self::Connu => 'text-green-400',
            self::Respecte => 'text-blue-400',
            self::Honore => 'text-purple-400',
            self::Illustre => 'text-amber-400',
            self::Legendaire => 'text-yellow-300',
        };
    }

    public function threshold(): int
    {
        return match ($this) {
            self::Novice => 0,
            self::Connu => 250,
            self::Respecte => 1000,
            self::Honore => 3000,
            self::Illustre => 8000,
            self::Legendaire => 20000,
        };
    }

    /**
     * Reduction appliquee sur le prix des achats chez les PNJ marchands.
     * Bonus progressif : 0 % au palier Novice, +1 % par palier, plafonne a 5 % au Legendaire.
     * La reduction se cumule multiplicativement avec la reduction de guilde controlante.
     */
    public function getShopDiscount(): float
    {
        return match ($this) {
            self::Novice => 0.0,
            self::Connu => 0.01,
            self::Respecte => 0.02,
            self::Honore => 0.03,
            self::Illustre => 0.04,
            self::Legendaire => 0.05,
        };
    }

    public static function fromScore(int $score): self
    {
        $current = self::Novice;
        foreach (self::cases() as $tier) {
            if ($score >= $tier->threshold()) {
                $current = $tier;
            }
        }

        return $current;
    }

    public function nextTier(): ?self
    {
        return match ($this) {
            self::Novice => self::Connu,
            self::Connu => self::Respecte,
            self::Respecte => self::Honore,
            self::Honore => self::Illustre,
            self::Illustre => self::Legendaire,
            self::Legendaire => null,
        };
    }

    /**
     * Points requis pour atteindre le palier suivant depuis un score donne.
     * Retourne null si deja au palier maximum.
     */
    public static function pointsToNextTier(int $score): ?int
    {
        $next = self::fromScore($score)->nextTier();
        if ($next === null) {
            return null;
        }

        return max(0, $next->threshold() - $score);
    }
}
