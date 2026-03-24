<?php

namespace App\Enum;

enum GuildRank: string
{
    case Master = 'master';
    case Officer = 'officer';
    case Member = 'member';
    case Recruit = 'recruit';

    public function label(): string
    {
        return match ($this) {
            self::Master => 'Maître de guilde',
            self::Officer => 'Officier',
            self::Member => 'Membre',
            self::Recruit => 'Recrue',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Master => 'text-yellow-400 bg-yellow-400/10',
            self::Officer => 'text-blue-400 bg-blue-400/10',
            self::Member => 'text-green-400 bg-green-400/10',
            self::Recruit => 'text-gray-400 bg-gray-400/10',
        };
    }

    public function canInvite(): bool
    {
        return in_array($this, [self::Master, self::Officer], true);
    }

    public function canKick(): bool
    {
        return in_array($this, [self::Master, self::Officer], true);
    }

    public function canPromote(): bool
    {
        return $this === self::Master;
    }

    public function isAbove(self $other): bool
    {
        return $this->weight() > $other->weight();
    }

    private function weight(): int
    {
        return match ($this) {
            self::Master => 4,
            self::Officer => 3,
            self::Member => 2,
            self::Recruit => 1,
        };
    }
}
