<?php

namespace App\Enum;

enum GuildRank: string
{
    case Leader = 'leader';
    case Officer = 'officer';
    case Member = 'member';
    case Recruit = 'recruit';

    public function label(): string
    {
        return match ($this) {
            self::Leader => 'Maitre de guilde',
            self::Officer => 'Officier',
            self::Member => 'Membre',
            self::Recruit => 'Recrue',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Leader => 'text-yellow-400',
            self::Officer => 'text-purple-400',
            self::Member => 'text-blue-400',
            self::Recruit => 'text-gray-400',
        };
    }

    public function canInvite(): bool
    {
        return \in_array($this, [self::Leader, self::Officer], true);
    }

    public function canKick(): bool
    {
        return \in_array($this, [self::Leader, self::Officer], true);
    }

    public function canPromote(): bool
    {
        return $this === self::Leader;
    }

    public function isHigherThan(self $other): bool
    {
        $order = [self::Leader, self::Officer, self::Member, self::Recruit];

        return array_search($this, $order, true) < array_search($other, $order, true);
    }
}
