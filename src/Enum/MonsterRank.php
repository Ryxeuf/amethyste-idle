<?php

namespace App\Enum;

/**
 * Le rang d'un monstre : qu'est-ce que c'est ? (BES-01, GAME_BESTIARY §2.2).
 *
 * Absorbe `difficulty` (1-5) et `isBoss` — le booleen manquait le cran du
 * milieu. Le rang dit ce qu'on affronte ; le palier (`Monster::tier`) dit ou
 * ca vit. Deux axes orthogonaux, et aucun ne s'appelle « niveau » : le joueur
 * n'a pas de niveau (regle 6), une echelle 1-40 ne se comparait a rien.
 */
enum MonsterRank: string
{
    case Common = 'common';
    case Elite = 'elite';
    case Boss = 'boss';

    public function label(): string
    {
        return match ($this) {
            self::Common => 'Commun',
            self::Elite => 'Élite',
            self::Boss => 'Boss',
        };
    }

    /**
     * Ordre croissant de menace — sert aux comparaisons de seuil
     * (`MonsterItem::minRank`) sans jamais redevenir une echelle affichee.
     */
    public function level(): int
    {
        return match ($this) {
            self::Common => 0,
            self::Elite => 1,
            self::Boss => 2,
        };
    }

    public function atLeast(self $other): bool
    {
        return $this->level() >= $other->level();
    }
}
