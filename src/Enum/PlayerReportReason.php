<?php

namespace App\Enum;

enum PlayerReportReason: string
{
    case Harassment = 'harassment';
    case Spam = 'spam';
    case Cheating = 'cheating';
    case Inappropriate = 'inappropriate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Harassment => 'Harcelement',
            self::Spam => 'Spam',
            self::Cheating => 'Triche',
            self::Inappropriate => 'Contenu inapproprie',
            self::Other => 'Autre',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->value] = $case->label();
        }

        return $choices;
    }
}
