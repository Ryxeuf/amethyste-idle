<?php

namespace App\Enum;

enum ReputationTier: string
{
    case Hostile = 'hostile';
    case Inconnu = 'inconnu';
    case Neutre = 'neutre';
    case Ami = 'ami';
    case Honore = 'honore';
    case Revere = 'revere';
    case Exalte = 'exalte';

    public function label(): string
    {
        return match ($this) {
            self::Hostile => 'Hostile',
            self::Inconnu => 'Inconnu',
            self::Neutre => 'Neutre',
            self::Ami => 'Ami',
            self::Honore => 'Honoré',
            self::Revere => 'Révéré',
            self::Exalte => 'Exalté',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Hostile => 'text-red-400',
            self::Inconnu => 'text-gray-400',
            self::Neutre => 'text-gray-300',
            self::Ami => 'text-green-400',
            self::Honore => 'text-blue-400',
            self::Revere => 'text-purple-400',
            self::Exalte => 'text-yellow-400',
        };
    }

    public function threshold(): int
    {
        return match ($this) {
            self::Hostile => -1,
            self::Inconnu => 0,
            self::Neutre => 500,
            self::Ami => 2000,
            self::Honore => 5000,
            self::Revere => 10000,
            self::Exalte => 20000,
        };
    }

    public static function fromReputation(int $reputation): self
    {
        if ($reputation < 0) {
            return self::Hostile;
        }

        $result = self::Inconnu;
        foreach ([self::Neutre, self::Ami, self::Honore, self::Revere, self::Exalte] as $tier) {
            if ($reputation >= $tier->threshold()) {
                $result = $tier;
            }
        }

        return $result;
    }

    public function nextTier(): ?self
    {
        return match ($this) {
            self::Hostile => self::Inconnu,
            self::Inconnu => self::Neutre,
            self::Neutre => self::Ami,
            self::Ami => self::Honore,
            self::Honore => self::Revere,
            self::Revere => self::Exalte,
            self::Exalte => null,
        };
    }
}
